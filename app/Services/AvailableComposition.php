<?php

namespace App\Services;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money;

/**
 * Répartit le solde disponible encore présent dans le wallet
 * entre bonus, commissions, profits et le reste.
 *
 * Les sorties (retrait réservé ou payé, frais, transfert, investissement)
 * consomment d'abord le bonus, puis les commissions, puis les profits,
 * puis les autres crédits. Une écriture extournée est ignorée :
 * le refus d'un retrait rend donc le bonus.
 */
class AvailableComposition
{
    /**
     * @return array{bonus: string, commission: string, profit: string, other: string}
     */
    public function remaining(User $user): array
    {
        $buckets = [
            'bonus' => '0.00',
            'commission' => '0.00',
            'profit' => '0.00',
            'other' => '0.00',
        ];

        LedgerEntry::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [LedgerStatus::Completed, LedgerStatus::Pending])
            ->orderBy('id')
            ->get(['id', 'type', 'amount'])
            ->each(function (LedgerEntry $entry) use (&$buckets) {
                $amount = Money::of($entry->amount);

                if (Money::cmp($amount, '0') > 0) {
                    $key = $this->creditBucket($entry->type);
                    $buckets[$key] = Money::add($buckets[$key], $amount);

                    return;
                }

                if (Money::cmp($amount, '0') < 0) {
                    $this->consume($buckets, Money::sub('0', $amount));
                }
            });

        return $buckets;
    }

    private function creditBucket(LedgerType $type): string
    {
        return match ($type) {
            LedgerType::Bonus => 'bonus',
            LedgerType::ReferralCommission => 'commission',
            LedgerType::InvestmentReturn => 'profit',
            default => 'other',
        };
    }

    /**
     * @param  array{bonus: string, commission: string, profit: string, other: string}  $buckets
     */
    private function consume(array &$buckets, string $amount): void
    {
        $left = Money::of($amount);

        foreach (['bonus', 'commission', 'profit', 'other'] as $key) {
            if (Money::cmp($left, '0') <= 0) {
                return;
            }

            $take = Money::cmp($buckets[$key], $left) >= 0 ? $left : $buckets[$key];
            $buckets[$key] = Money::sub($buckets[$key], $take);
            $left = Money::sub($left, $take);
        }
    }
}
