<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Blocked => 'Bloqué',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'ok',
            self::Blocked => 'danger',
        };
    }
}
