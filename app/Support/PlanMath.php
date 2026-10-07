<?php

namespace App\Support;

/**
 * Répartition exacte du gain total d'une position.
 * Aucune écriture ledger : le dernier jour absorbe l'écart d'arrondi.
 */
class PlanMath
{
    public static function totalGain(mixed $capital, mixed $percent): string
    {
        return Money::percent($capital, $percent);
    }

    public static function ordinaryDaily(mixed $totalGain, int $profitDays): string
    {
        if ($profitDays < 1) {
            return '0.00';
        }

        $raw = bcdiv(Money::of($totalGain), (string) $profitDays, 8);

        return Money::of($raw);
    }

    public static function creditAmount(string $planned, string $alreadyCredited, string $ordinaryDaily, bool $lastDay): string
    {
        $remaining = Money::sub($planned, $alreadyCredited);

        if (Money::cmp($remaining, '0') <= 0) {
            return '0.00';
        }

        if ($lastDay || Money::cmp($ordinaryDaily, $remaining) >= 0) {
            return $remaining;
        }

        return Money::of($ordinaryDaily);
    }

    public static function amountForIndex(mixed $totalGain, int $profitDays, int $index): string
    {
        if ($profitDays < 1 || $index < 0 || $index >= $profitDays) {
            return '0.00';
        }

        $planned = Money::of($totalGain);
        $ordinary = self::ordinaryDaily($planned, $profitDays);
        $credited = '0.00';
        $amount = '0.00';

        for ($cursor = 0; $cursor <= $index; $cursor++) {
            $amount = self::creditAmount($planned, $credited, $ordinary, $cursor === $profitDays - 1);
            $credited = Money::add($credited, $amount);
        }

        return $amount;
    }

    /**
     * @return list<string>
     */
    public static function schedule(mixed $totalGain, int $profitDays): array
    {
        $amounts = [];

        for ($index = 0; $index < $profitDays; $index++) {
            $amounts[] = self::amountForIndex($totalGain, $profitDays, $index);
        }

        return $amounts;
    }

    public static function economicTotal(mixed $capital, mixed $totalGain): string
    {
        return Money::add($capital, $totalGain);
    }
}
