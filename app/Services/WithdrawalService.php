<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\LedgerStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReviewStatus;
use App\Exceptions\FinancialException;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Money;
use Illuminate\Support\Str;

class WithdrawalService
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit,
        private Notifier $notifier,
    ) {}

    /**
     * @return array{amount: string, fee: string, net: string}
     */
    public function quote(string $amount): array
    {
        $settings = PlatformSetting::current();
        $amount = Money::of($amount);
        $fee = Money::add(
            Money::of($settings->withdrawal_fee_fixed),
            Money::percent($amount, $settings->withdrawal_fee_percent),
        );
        $net = Money::sub($amount, $fee);

        return [
            'amount' => $amount,
            'fee' => $fee,
            'net' => $net,
            'min' => Money::of($settings->withdrawal_min),
            'max' => Money::of($settings->withdrawal_max),
            'percent' => Money::of($settings->withdrawal_fee_percent),
            'fixed' => Money::of($settings->withdrawal_fee_fixed),
        ];
    }

    public function request(User $user, string $amount, PaymentMethod $method, string $phone, string $idempotencyKey): Withdrawal
    {
        $this->assertActive($user);
        $settings = PlatformSetting::current();

        if ($settings->kyc_required_for_withdrawal && $user->kyc_status !== KycStatus::Verified) {
            throw new FinancialException('Le retrait exige un dossier KYC vérifié. Déposez vos pièces depuis votre profil.');
        }

        $quote = $this->quote($amount);

        if (Money::cmp($quote['amount'], $quote['min']) < 0) {
            throw new FinancialException('Le montant minimum de retrait est de '.Money::format($quote['min']).'.');
        }

        if (Money::cmp($quote['amount'], $quote['max']) > 0) {
            throw new FinancialException('Le montant maximum de retrait est de '.Money::format($quote['max']).'.');
        }

        if (Money::cmp($quote['fee'], $quote['amount']) >= 0 || Money::cmp($quote['net'], '0') <= 0) {
            throw new FinancialException('Les frais absorbent le montant demandé. Augmentez le montant.');
        }

        return Finance::run(function () use ($user, $method, $phone, $idempotencyKey, $quote) {
            $existing = Withdrawal::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ($existing->user_id !== $user->id) {
                    throw new FinancialException('Cette opération a déjà été enregistrée pour un autre compte.');
                }

                return $existing;
            }

            $withdrawal = Withdrawal::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'amount' => $quote['amount'],
                'fee' => $quote['fee'],
                'net_amount' => $quote['net'],
                'currency' => 'USD',
                'method' => $method,
                'phone' => $phone,
                'status' => ReviewStatus::Pending,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->notifier->send(
                $user,
                'withdrawal_requested',
                'Retrait demandé',
                'Demande de '.Money::format($quote['amount']).'. Frais '.Money::format($quote['fee']).', net '.Money::format($quote['net']).'.',
            );

            $this->wallets->holdWithdrawal($user, $quote['net'], $quote['fee'], [
                'reference' => 'WD-'.$withdrawal->id,
                'description' => 'Demande de retrait '.$method->label(),
                'fee_description' => 'Frais de retrait réservés',
                'related_type' => Withdrawal::class,
                'related_id' => $withdrawal->id,
                'idempotency_key' => 'withdrawal-'.$withdrawal->id.'-net',
                'metadata' => ['phone' => $phone],
            ]);

            return $withdrawal;
        });
    }

    public function approve(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        return Finance::run(function () use ($withdrawal, $admin) {
            $withdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== ReviewStatus::Pending) {
                throw new FinancialException('Cette demande de retrait a déjà été traitée.');
            }

            if ($withdrawal->user_id === $admin->id) {
                throw new FinancialException('Un administrateur ne peut pas valider son propre retrait.');
            }

            $user = $withdrawal->user()->firstOrFail();
            $wallet = $this->wallets->ensure($user);
            $old = Money::of($wallet->available_balance);

            LedgerEntry::query()
                ->where('related_type', Withdrawal::class)
                ->where('related_id', $withdrawal->id)
                ->where('status', LedgerStatus::Pending)
                ->update(['status' => LedgerStatus::Completed->value]);

            $this->wallets->settleWithdrawal($user, (string) $withdrawal->amount);

            $withdrawal->forceFill([
                'status' => ReviewStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ])->save();

            $wallet->refresh();

            $this->audit->record(
                $admin,
                $user,
                'withdrawal_approved',
                $old,
                Money::of($wallet->available_balance),
                Money::sub('0', $withdrawal->amount),
                'Retrait approuvé. Net à verser '.Money::format($withdrawal->net_amount).', frais '.Money::format($withdrawal->fee).'.',
                ['withdrawal_id' => $withdrawal->id, 'net' => Money::of($withdrawal->net_amount), 'fee' => Money::of($withdrawal->fee)],
            );

            $this->notifier->send(
                $user,
                'withdrawal_approved',
                'Retrait approuvé',
                'Votre retrait est approuvé. Montant demandé '.Money::format($withdrawal->amount).', frais '.Money::format($withdrawal->fee).', net '.Money::format($withdrawal->net_amount).'.',
            );

            return $withdrawal;
        });
    }

    public function reject(Withdrawal $withdrawal, User $admin, string $reason): Withdrawal
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new FinancialException('Indiquez le motif du refus.');
        }

        return Finance::run(function () use ($withdrawal, $admin, $reason) {
            $withdrawal = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($withdrawal->status !== ReviewStatus::Pending) {
                throw new FinancialException('Cette demande de retrait a déjà été traitée.');
            }

            $user = $withdrawal->user()->firstOrFail();
            $wallet = $this->wallets->ensure($user);
            $old = Money::of($wallet->available_balance);

            LedgerEntry::query()
                ->where('related_type', Withdrawal::class)
                ->where('related_id', $withdrawal->id)
                ->where('status', LedgerStatus::Pending)
                ->update(['status' => LedgerStatus::Reversed->value]);

            $this->wallets->releaseWithdrawal($user, (string) $withdrawal->amount);

            $withdrawal->forceFill([
                'status' => ReviewStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $wallet->refresh();

            $this->audit->record(
                $admin,
                $user,
                'withdrawal_rejected',
                $old,
                Money::of($wallet->available_balance),
                $withdrawal->amount,
                $reason,
                ['withdrawal_id' => $withdrawal->id],
            );

            $this->notifier->send(
                $user,
                'withdrawal_rejected',
                'Retrait refusé',
                'Votre demande de retrait de '.Money::format($withdrawal->amount).' a été refusée. Le montant a été rendu disponible. Motif : '.$reason,
            );

            return $withdrawal;
        });
    }

    private function assertActive(User $user): void
    {
        if ($user->status !== AccountStatus::Active) {
            throw new FinancialException('Ce compte est bloqué.');
        }
    }
}
