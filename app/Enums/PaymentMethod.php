<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Mpesa = 'mpesa';
    case AirtelMoney = 'airtel_money';
    case OrangeMoney = 'orange_money';

    public function label(): string
    {
        return match ($this) {
            self::Mpesa => 'M-Pesa',
            self::AirtelMoney => 'Airtel Money',
            self::OrangeMoney => 'Orange Money',
        };
    }
}
