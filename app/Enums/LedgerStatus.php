<?php

namespace App\Enums;

enum LedgerStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Completed => 'Comptabilisé',
            self::Reversed => 'Extourné',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Completed => 'ok',
            self::Reversed => 'muted',
        };
    }
}
