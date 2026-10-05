<?php

namespace App\Enums;

enum InvestmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
            self::Suspended => 'Suspendu',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'ok',
            self::Completed => 'info',
            self::Cancelled => 'danger',
            self::Suspended => 'warn',
        };
    }
}
