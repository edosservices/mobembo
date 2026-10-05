<?php

namespace App\Enums;

enum ReferralTrigger: string
{
    case ApprovedDeposit = 'approved_deposit';
    case Investment = 'investment';

    public function label(): string
    {
        return match ($this) {
            self::ApprovedDeposit => 'Dépôt approuvé du filleul',
            self::Investment => 'Investissement du filleul',
        };
    }
}
