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

    public static function dailyPercent(mixed $percent, int $days): string
    {
        $days = max(1, $days);
        $raw = bcdiv(self::plain($percent), (string) $days, 8);

        return self::roundScale($raw, 4);
    }

    public static function dailyAmount(mixed $capital, mixed $percent, int $days): string
    {
        $raw = bcdiv(bcmul(Money::of($capital), self::dailyPercent($percent, $days), 8), '100', 8);

        return self::roundScale($raw, 4);
    }

    public static function percentLabel(string $rate): string
    {
        return str_replace('.', ',', $rate).' % / jour';
    }

    public static function amountLabel(string $amount): string
    {
        return str_replace('.', ',', $amount).' $';
    }

    private static function plain(mixed $percent): string
    {
        $value = trim((string) $percent);

        if (! preg_match('/^\d+(\.\d+)?$/', $value)) {
            return '0';
        }

        return $value;
    }

    private static function roundScale(string $value, int $scale): string
    {
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        $increment = '0.'.str_repeat('0', $scale).'5';
        $rounded = bcadd($value, $increment, $scale + 1);
        $truncated = bcadd($rounded, '0', $scale);

        if (! str_contains($truncated, '.')) {
            $truncated .= '.'.str_repeat('0', $scale);
        } else {
            [$whole, $fraction] = explode('.', $truncated, 2);
            $truncated = $whole.'.'.str_pad(substr($fraction, 0, $scale), $scale, '0');
        }

        return ($negative ? '-' : '').$truncated;
    }
}
