<?php

namespace App\Support;

final class Money
{
    public static function format(?int $cents, string $currency = 'CDF'): string
    {
        if ($cents === null) {
            return 'Tarif à définir';
        }

        if ($cents === 0) {
            return 'Gratuit';
        }

        $amount = number_format($cents / 100, 0, ',', ' ');

        return $currency === 'USD' ? $amount.' USD' : $amount.' FC';
    }
}
