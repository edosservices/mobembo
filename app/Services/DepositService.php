<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\LedgerType;
use App\Enums\PaymentMethod;
use App\Enums\ReferralTrigger;
use App\Enums\ReviewStatus;
use App\Exceptions\FinancialException;
use App\Models\Deposit;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;

class DepositService
{
    public function __construct(
        private WalletService $wallets,
        private ReferralService $referrals,
        private AuditService $audit,
        private Notifier $notifier,
    ) {}

    public function submit(User $user, string $amount, PaymentMethod $method, string $reference, string $proofPath, string $idempotencyKey): Deposit
    {
        $this->assertActive($user);
        $amount = Money::of($amount);

        if (Money::cmp($amount, '0.01') < 0) {
            throw new FinancialException('Le montant du dépôt doit être positif.');
        }

        $reference = $this->normalizeReference($reference);
        $lock = $method->value.':'.$reference;

        return Finance::run(function () use ($user, $amount, $method, $reference, $proofPath, $idempotencyKey, $lock) {
            $existing = Deposit::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ($existing->user_id !== $user->id) {
                    throw new FinancialException('Cette opération a déjà été enregistrée pour un autre compte.');
                }

                return $existing;
            }

            if (Deposit::query()->where('reference_lock', $lock)->exists()) {
                throw new FinancialException('Cette référence de paiement a déjà été soumise.');
            }

            $deposit = Deposit::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => 'USD',
                'method' => $method,
                'reference' => $reference,
                'reference_lock' => $lock,
                'proof_path' => $proofPath,
                'status' => ReviewStatus::Pending,
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->notifier->send(
                $user,
                'deposit_received',
                'Dépôt reçu',
                'Votre dépôt de '.Money::format($amount).' est enregistré et en attente de vérification.',
            );

            return $deposit;
        });
    }

    public function approve(Deposit $deposit, User $admin): Deposit
    {
        return Finance::run(function () use ($deposit, $admin) {
            $deposit = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== ReviewStatus::Pending) {
                throw new FinancialException('Ce dépôt a déjà été traité.');
            }

            if ($deposit->user_id === $admin->id) {
                throw new FinancialException('Un administrateur ne peut pas valider son propre dépôt.');
            }

            $user = User::query()->whereKey($deposit->user_id)->lockForUpdate()->firstOrFail();

            if ($user->status !== AccountStatus::Active) {
                throw new FinancialException('Le compte est bloqué. Débloquez-le avant d’approuver le dépôt.');
            }

            $wallet = $this->wallets->ensure($user);
            $old = Money::of($wallet->available_balance);

            $entry = $this->wallets->credit($user, (string) $deposit->amount, LedgerType::Deposit, [
                'reference' => $deposit->reference,
                'description' => 'Dépôt '.$deposit->method->label().' approuvé',
                'related_type' => Deposit::class,
                'related_id' => $deposit->id,
                'idempotency_key' => 'deposit-credit-'.$deposit->id,
                'created_by' => $admin->id,
            ]);

            $deposit->forceFill([
                'status' => ReviewStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'ledger_entry_id' => $entry->id,
            ])->save();

            $wallet->refresh();
            $new = Money::of($wallet->available_balance);

            $this->audit->record(
                $admin,
                $user,
                'deposit_approved',
                $old,
                $new,
                $deposit->amount,
                'Dépôt '.$deposit->method->label().' '.$deposit->reference.' approuvé',
                ['deposit_id' => $deposit->id],
            );

            $this->referrals->reward(
                ReferralTrigger::ApprovedDeposit,
                $user,
                (string) $deposit->amount,
                Deposit::class,
                $deposit->id,
                $admin,
            );

            $this->notifier->send(
                $user,
                'deposit_approved',
                'Dépôt approuvé',
                'Votre dépôt de '.Money::format($deposit->amount).' a été crédité sur votre solde disponible.',
            );

            return $deposit->refresh();
        });
    }

    public function reject(Deposit $deposit, User $admin, string $reason): Deposit
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new FinancialException('Indiquez le motif du refus.');
        }

        return Finance::run(function () use ($deposit, $admin, $reason) {
            $deposit = Deposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($deposit->status !== ReviewStatus::Pending) {
                throw new FinancialException('Ce dépôt a déjà été traité.');
            }

            $user = $deposit->user()->firstOrFail();
            $balance = Money::of($user->wallet->available_balance);

            $deposit->forceFill([
                'status' => ReviewStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'reference_lock' => null,
            ])->save();

            $this->audit->record($admin, $user, 'deposit_rejected', $balance, $balance, '0.00', $reason, [
                'deposit_id' => $deposit->id,
                'amount' => Money::of($deposit->amount),
            ]);

            $this->notifier->send(
                $user,
                'deposit_rejected',
                'Dépôt refusé',
                'Votre dépôt de '.Money::format($deposit->amount).' a été refusé. Motif : '.$reason,
            );

            return $deposit;
        });
    }

    private function assertActive(User $user): void
    {
        if ($user->status !== AccountStatus::Active) {
            throw new FinancialException('Ce compte est bloqué.');
        }
    }

    private function normalizeReference(string $reference): string
    {
        $reference = strtoupper(preg_replace('/\s+/', '', trim($reference)) ?? '');

        if (! preg_match('/^[A-Z0-9\-]{4,64}$/', $reference)) {
            throw new FinancialException('La référence de paiement est invalide.');
        }

        return $reference;
    }
}
