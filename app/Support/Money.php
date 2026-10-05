<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function of(mixed $amount): string
    {
        if (is_int($amount)) {
            return sprintf('%d.00', $amount);
        }

        if (is_float($amount)) {
            $amount = number_format($amount, 4, '.', '');
        }

        $value = trim((string) $amount);

        if ($value === '') {
            return '0.00';
        }

        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Montant invalide.');
        }

        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');

        if (! str_contains($value, '.')) {
            $result = $value.'.00';
        } else {
            [$whole, $fraction] = explode('.', $value, 2);
            $whole = $whole === '' ? '0' : $whole;
            $digits = str_pad($fraction, 3, '0');
            $keep = substr($digits, 0, 2);
            $next = (int) substr($digits, 2, 1);
            $result = $whole.'.'.$keep;

            if ($next >= 5) {
                $result = bcadd($result, '0.01', 2);
            }
        }

        if ($negative && $result !== '0.00') {
            return '-'.$result;
        }

        return $result;
    }

    public static function normalizeInput(mixed $input): string
    {
        $value = trim((string) $input);
        $value = str_replace(["\u{00A0}", ' '], '', $value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    public static function add(mixed $left, mixed $right): string
    {
        return bcadd(self::of($left), self::of($right), 2);
    }

    public static function sub(mixed $left, mixed $right): string
    {
        return bcsub(self::of($left), self::of($right), 2);
    }

    public static function percent(mixed $amount, mixed $rate): string
    {
        $raw = bcdiv(bcmul(self::of($amount), self::of($rate), 8), '100', 8);

        return self::of($raw);
    }

    public static function cmp(mixed $left, mixed $right): int
    {
        return bccomp(self::of($left), self::of($right), 2);
    }

    public static function truncate(mixed $amount): string
    {
        $value = self::plain($amount);
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');

        if (! str_contains($value, '.')) {
            $result = $value.'.00';
        } else {
            [$whole, $fraction] = explode('.', $value, 2);
            $whole = $whole === '' ? '0' : $whole;
            $fraction = substr(str_pad($fraction, 2, '0'), 0, 2);
            $result = $whole.'.'.$fraction;
        }

        if ($negative && $result !== '0.00') {
            return '-'.$result;
        }

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $weights
     * @return array<int|string, string>
     */
    public static function allocate(mixed $total, array $weights): array
    {
        $total = self::of($total);
        $base = '0.00';

        foreach ($weights as $weight) {
            $base = self::add($base, $weight);
        }

        if (self::cmp($base, '0') <= 0) {
            throw new InvalidArgumentException('La base de répartition est nulle.');
        }

        $shares = [];
        $allocated = '0.00';

        foreach ($weights as $key => $weight) {
            $raw = bcdiv(bcmul($total, self::of($weight), 8), $base, 8);
            $share = self::truncate($raw);
            $shares[$key] = $share;
            $allocated = self::add($allocated, $share);
        }

        $cents = (int) bcmul(self::sub($total, $allocated), '100', 0);
        $keys = array_keys($shares);
        $index = 0;

        while ($cents > 0 && $keys !== []) {
            $key = $keys[$index % count($keys)];
            $shares[$key] = self::add($shares[$key], '0.01');
            $cents--;
            $index++;

            if ($index > 100000) {
                break;
            }
        }

        return $shares;
    }

    public static function format(mixed $amount, string $symbol = '$'): string
    {
        $normalized = self::of($amount);
        $negative = str_starts_with($normalized, '-');
        $absolute = ltrim($normalized, '-');
        [$whole, $fraction] = explode('.', $absolute);
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ' ', $whole) ?: '0';

        return ($negative ? '−' : '').$whole.','.$fraction.' '.$symbol;
    }

    private static function plain(mixed $amount): string
    {
        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_float($amount)) {
            return number_format($amount, 8, '.', '');
        }

        $value = trim((string) $amount);

        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Montant invalide.');
        }

        return $value;
    }
}
