<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Funded = 'funded';
    case Suspended = 'suspended';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Ouvert',
            self::Funded => 'Financé',
            self::Suspended => 'Suspendu',
            self::Closed => 'Clôturé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'ok',
            self::Funded => 'info',
            self::Draft => 'muted',
            self::Suspended => 'warn',
            self::Closed => 'muted',
        };
    }
}
