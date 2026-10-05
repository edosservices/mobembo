<?php

use App\Support\Money;

if (! function_exists('money')) {
    function money(mixed $amount, string $symbol = '$'): string
    {
        return Money::format($amount, $symbol);
    }
}
