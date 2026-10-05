<?php

namespace App\Enums;

enum DistributionFrequency: string
{
    case AtMaturity = 'at_maturity';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::AtMaturity => 'À l’échéance du projet',
            self::Manual => 'Distribution déclarée manuellement',
        };
    }
}
