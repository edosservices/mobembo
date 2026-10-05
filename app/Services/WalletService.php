<?php

namespace App\Services;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Exceptions\FinancialException;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use App\Support\Money;
use Illuminate\Support\Str;

class WalletService
{
    public function ensure(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['currency' => 'USD'],
        );
    }

    public function credit(User $user, string $amount, LedgerType $type, array $context = []): LedgerEntry
    {
        $amount = Money::of($amount);

        if (Money::cmp($amount, '0') <= 0) {
            throw new FinancialException('Le montant à créditer doit être positif.');
        }

        return $this->apply($user, $amount, '0.00', '0.00', $amount, $type, LedgerStatus::Completed, $context);
    }

    public function debitAvailable(User $user, string $amount, LedgerType $type, array $context = []): LedgerEntry
    {
        $amount = Money::of($amount);

        if (Money::cmp($amount, '0') <= 0) {
            throw new FinancialException('Le montant à débiter doit être positif.');
        }

        return $this->apply($user, Money::sub('0', $amount), '0.00', '0.00', Money::sub('0', $amount), $type, LedgerStatus::Completed, $context);
    }

    public function invest(User $user, string $amount, array $context = []): LedgerEntry
    {
        $amount = Money::of($amount);

        return $this->apply(
            $user,
            Money::sub('0', $amount),
            $amount,
            '0.00',
            Money::sub('0', $amount),
            LedgerType::Investment,
            LedgerStatus::Completed,
            $context,
        );
    }

    public function returnCapital(User $user, string $amount, array $context = []): LedgerEntry
    {
        $amount = Money::of($amount);

        return $this->apply(
            $user,
            $amount,
            Money::sub('0', $amount),
            '0.00',
            $amount,
            LedgerType::CapitalReturn,
            LedgerStatus::Completed,
            $context,
        );
    }

    /**
     * @return array{0: LedgerEntry, 1: LedgerEntry}
     */
    public function holdWithdrawal(User $user, string $net, string $fee, array $context = []): array
    {
        $net = Money::of($net);
        $fee = Money::of($fee);
        $gross = Money::add($net, $fee);

        return Finance::run(function () use ($user, $net, $fee, $gross, $context) {
            $wallet = $this->lock($user);
            $this->assertFunds($wallet, $gross);

            $wallet->forceFill([
                'available_balance' => Money::sub($wallet->available_balance, $gross),
                'locked_balance' => Money::add($wallet->locked_balance, $gross),
            ])->save();

            $netEntry = $this->write($wallet, $user, Money::sub('0', $net), LedgerType::Withdrawal, LedgerStatus::Pending, $context);
            $feeEntry = $this->write($wallet, $user, Money::sub('0', $fee), LedgerType::WithdrawalFee, LedgerStatus::Pending, [
                ...$context,
                'idempotency_key' => ($context['idempotency_key'] ?? $context['reference'] ?? Str::uuid()).'-fee',
                'description' => $context['fee_description'] ?? 'Frais de retrait',
            ]);

            return [$netEntry, $feeEntry];
        });
    }

    public function settleWithdrawal(User $user, string $gross): void
    {
        Finance::run(function () use ($user, $gross) {
            $wallet = $this->lock($user);
            $nextLocked = Money::sub($wallet->locked_balance, $gross);

            if (Money::cmp($nextLocked, '0') < 0) {
                throw new FinancialException('Le montant bloqué est insuffisant pour solder ce retrait.');
            }

            $wallet->forceFill(['locked_balance' => $nextLocked])->save();
        });
    }

    public function releaseWithdrawal(User $user, string $gross): void
    {
        $gross = Money::of($gross);

        Finance::run(function () use ($user, $gross) {
            $wallet = $this->lock($user);
            $nextLocked = Money::sub($wallet->locked_balance, $gross);

            if (Money::cmp($nextLocked, '0') < 0) {
                throw new FinancialException('Le montant bloqué est insuffisant pour annuler ce retrait.');
            }

            // Les écritures d'origine passent au statut « reversed » et sortent de la somme.
            // On ne crée pas de contre-écriture, sinon le solde serait compté deux fois.
            $wallet->forceFill([
                'available_balance' => Money::add($wallet->available_balance, $gross),
                'locked_balance' => $nextLocked,
            ])->save();
        });
    }

    public function ledgerAvailable(User $user): string
    {
        $total = '0.00';

        LedgerEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [LedgerStatus::Completed, LedgerStatus::Pending])
            ->orderBy('id')
            ->each(function (LedgerEntry $entry) use (&$total) {
                $total = Money::add($total, $entry->amount);
            });

        return $total;
    }

    /**
     * @return list<array{user_id: int, wallet: string, ledger: string}>
     */
    public function findDrift(): array
    {
        $drift = [];

        Wallet::query()->with('user')->orderBy('id')->each(function (Wallet $wallet) use (&$drift) {
            $ledger = $this->ledgerAvailable($wallet->user);
            if (Money::cmp($wallet->available_balance, $ledger) !== 0) {
                $drift[] = [
                    'user_id' => $wallet->user_id,
                    'wallet' => Money::of($wallet->available_balance),
                    'ledger' => $ledger,
                ];
            }
        });

        return $drift;
    }

    private function apply(
        User $user,
        string $availableDelta,
        string $investedDelta,
        string $lockedDelta,
        string $signedAmount,
        LedgerType $type,
        LedgerStatus $status,
        array $context,
    ): LedgerEntry {
        return Finance::run(function () use ($user, $availableDelta, $investedDelta, $lockedDelta, $signedAmount, $type, $status, $context) {
            if (! empty($context['idempotency_key'])) {
                $existing = LedgerEntry::query()->where('idempotency_key', $context['idempotency_key'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            $wallet = $this->lock($user);
            $available = Money::add($wallet->available_balance, $availableDelta);
            $invested = Money::add($wallet->invested_balance, $investedDelta);
            $locked = Money::add($wallet->locked_balance, $lockedDelta);

            if (Money::cmp($available, '0') < 0) {
                throw new FinancialException('Solde disponible insuffisant.');
            }

            if (Money::cmp($invested, '0') < 0 || Money::cmp($locked, '0') < 0) {
                throw new FinancialException('Le portefeuille ne peut pas devenir négatif.');
            }

            $wallet->forceFill([
                'available_balance' => $available,
                'invested_balance' => $invested,
                'locked_balance' => $locked,
            ])->save();

            return $this->write($wallet, $user, $signedAmount, $type, $status, $context);
        });
    }

    private function assertFunds(Wallet $wallet, string $amount): void
    {
        if (Money::cmp($wallet->available_balance, $amount) < 0) {
            throw new FinancialException('Solde disponible insuffisant.');
        }
    }

    private function lock(User $user): Wallet
    {
        $this->ensure($user);

        return Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }

    private function write(Wallet $wallet, User $user, string $signedAmount, LedgerType $type, LedgerStatus $status, array $context): LedgerEntry
    {
        return LedgerEntry::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => Money::of($signedAmount),
            'currency' => $wallet->currency,
            'status' => $status,
            'reference' => $context['reference'] ?? null,
            'description' => $context['description'] ?? $type->label(),
            'balance_after' => $wallet->available_balance,
            'related_type' => $context['related_type'] ?? null,
            'related_id' => $context['related_id'] ?? null,
            'idempotency_key' => $context['idempotency_key'] ?? null,
            'metadata' => $context['metadata'] ?? null,
            'created_by' => $context['created_by'] ?? null,
        ]);
    }
}
