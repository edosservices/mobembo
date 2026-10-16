<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Prepared = 'prepared';
    case Sent = 'sent';
    case Interview = 'interview';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Préparée',
            self::Sent => 'Envoyée',
            self::Interview => 'Entretien',
            self::Closed => 'Clôturée',
        };
    }
}
