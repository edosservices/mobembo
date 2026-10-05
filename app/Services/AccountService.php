<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\LedgerType;
use App\Enums\UserRole;
use App\Exceptions\FinancialException;
use App\Models\KycDocument;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

class AccountService
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit,
        private Notifier $notifier,
    ) {}

    public function block(User $user, User $admin, string $reason): void
    {
        $this->guardSubject($user, $admin);
        $reason = $this->reason($reason);
        $balance = Money::of($user->wallet->available_balance);

        $user->forceFill(['status' => AccountStatus::Blocked])->save();
        $user->tokens()->delete();

        $this->audit->record($admin, $user, 'user_blocked', $balance, $balance, '0.00', $reason);
        $this->notifier->send($user, 'account_blocked', 'Compte bloqué', 'Votre compte a été bloqué. Motif : '.$reason);
    }

    public function unblock(User $user, User $admin, string $reason): void
    {
        $this->guardSubject($user, $admin);
        $reason = $this->reason($reason);
        $balance = Money::of($user->wallet->available_balance);

        $user->forceFill(['status' => AccountStatus::Active])->save();

        $this->audit->record($admin, $user, 'user_unblocked', $balance, $balance, '0.00', $reason);
        $this->notifier->send($user, 'account_unblocked', 'Compte rétabli', 'Votre compte est de nouveau actif.');
    }

    public function bonus(User $user, User $admin, string $amount, string $reason, string $idempotencyKey): void
    {
        $this->guardSubject($user, $admin);
        $reason = $this->reason($reason);
        $amount = Money::of($amount);

        if (Money::cmp($amount, '0.01') < 0) {
            throw new FinancialException('Le bonus doit être positif.');
        }

        if ($this->alreadyApplied($idempotencyKey)) {
            return;
        }

        $wallet = $this->wallets->ensure($user);
        $old = Money::of($wallet->available_balance);

        $this->wallets->credit($user, $amount, LedgerType::Bonus, [
            'description' => 'Bonus — '.$reason,
            'reference' => 'BONUS-'.Str::upper(Str::random(8)),
            'created_by' => $admin->id,
            'idempotency_key' => $idempotencyKey,
            'metadata' => ['reason' => $reason],
        ]);

        $wallet->refresh();

        $this->audit->record($admin, $user, 'bonus', $old, Money::of($wallet->available_balance), $amount, $reason);
        $this->notifier->send($user, 'bonus_received', 'Bonus reçu', 'Un bonus de '.Money::format($amount).' a été ajouté. Motif : '.$reason);
    }

    public function adjust(User $user, User $admin, string $signedAmount, string $reason, string $idempotencyKey): void
    {
        $this->guardSubject($user, $admin);
        $reason = $this->reason($reason);
        $signed = Money::of($signedAmount);

        if (Money::cmp($signed, '0') === 0) {
            throw new FinancialException('L’ajustement ne peut pas être nul.');
        }

        if ($this->alreadyApplied($idempotencyKey)) {
            return;
        }

        $wallet = $this->wallets->ensure($user);
        $old = Money::of($wallet->available_balance);

        if (Money::cmp($signed, '0') > 0) {
            $this->wallets->credit($user, $signed, LedgerType::AdminAdjustment, [
                'description' => 'Ajustement — '.$reason,
                'created_by' => $admin->id,
                'idempotency_key' => $idempotencyKey,
                'metadata' => ['reason' => $reason],
            ]);
        } else {
            $this->wallets->debitAvailable($user, ltrim($signed, '-'), LedgerType::AdminAdjustment, [
                'description' => 'Ajustement — '.$reason,
                'created_by' => $admin->id,
                'idempotency_key' => $idempotencyKey,
                'metadata' => ['reason' => $reason],
            ]);
        }

        $wallet->refresh();

        $this->audit->record(
            $admin,
            $user,
            'admin_adjustment',
            $old,
            Money::of($wallet->available_balance),
            $signed,
            $reason,
        );
    }

    public function resetPassword(User $user, User $admin): string
    {
        $this->guardSubject($user, $admin);
        $password = Str::password(12, symbols: true);
        $balance = Money::of($user->wallet->available_balance);

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        $this->audit->record($admin, $user, 'password_reset', $balance, $balance, '0.00', 'Réinitialisation du mot de passe par un administrateur');

        return $password;
    }

    public function reviewKyc(User $user, User $admin, KycStatus $status, ?string $note): void
    {
        if (! in_array($status, [KycStatus::Verified, KycStatus::Rejected, KycStatus::Pending], true)) {
            throw new FinancialException('Statut KYC invalide.');
        }

        $user->forceFill([
            'kyc_status' => $status,
            'kyc_note' => $note,
        ])->save();

        KycDocument::query()->where('user_id', $user->id)->where('status', KycStatus::Pending)->update([
            'status' => $status->value,
            'review_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $balance = Money::of($user->wallet->available_balance);
        $this->audit->record($admin, $user, 'kyc_'.$status->value, $balance, $balance, '0.00', $note ?: 'Revue KYC');
    }

    private function alreadyApplied(string $idempotencyKey): bool
    {
        return LedgerEntry::query()->where('idempotency_key', $idempotencyKey)->exists();
    }

    private function guardSubject(User $user, User $admin): void
    {
        if ($user->role === UserRole::Admin) {
            throw new FinancialException('Cette action ne s’applique pas à un compte administrateur.');
        }

        if ($user->id === $admin->id) {
            throw new FinancialException('Vous ne pouvez pas appliquer cette action à votre propre compte.');
        }
    }

    private function reason(string $reason): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new FinancialException('Le motif est obligatoire (5 caractères minimum).');
        }

        return $reason;
    }
}
