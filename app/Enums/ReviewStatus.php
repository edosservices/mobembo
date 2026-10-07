<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Demandé',
            self::Processing => 'En traitement',
            self::Approved => 'Payé',
            self::Rejected => 'Refusé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Processing => 'info',
            self::Approved => 'ok',
            self::Rejected => 'danger',
        };
    }
}
