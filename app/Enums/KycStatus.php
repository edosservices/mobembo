<?php

namespace App\Enums;

enum KycStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Non soumis',
            self::Pending => 'En revue',
            self::Verified => 'Vérifié',
            self::Rejected => 'Refusé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Verified => 'ok',
            self::Pending => 'warn',
            self::Rejected => 'danger',
            self::NotSubmitted => 'muted',
        };
    }
}
