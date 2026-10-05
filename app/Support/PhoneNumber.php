<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(mixed $input): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $input) ?? '';

        if (str_starts_with($digits, '243') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+243'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '+243'.$digits;
        }

        return null;
    }
}
