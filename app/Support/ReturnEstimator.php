<?php

namespace App\Support;

/**
 * Calcule un rendement prévu pour l'affichage.
 * Cette classe n'écrit jamais dans le ledger.
 */
class ReturnEstimator
{
    public static function total(mixed $capital, mixed $percent): string
    {
        return Money::percent($capital, $percent);
    }

    public static function daily(mixed $capital, mixed $percent, int $days): string
    {
        $days = max(1, $days);
        $total = self::total($capital, $percent);

        return Money::truncate(bcdiv($total, (string) $days, 8));
    }
}
