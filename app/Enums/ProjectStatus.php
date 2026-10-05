<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case AlmostComplete = 'almost_complete';
    case Complete = 'complete';
    case Active = 'active';
    case Finished = 'finished';
    case Suspended = 'suspended';
    case Funded = 'funded';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Open => 'Ouvert',
            self::AlmostComplete => 'Presque complet',
            self::Complete => 'Complet',
            self::Active => 'Actif',
            self::Finished => 'Terminé',
            self::Suspended => 'Suspendu',
            self::Funded => 'Financé',
            self::Closed => 'Clôturé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open, self::Active => 'ok',
            self::AlmostComplete => 'warn',
            self::Complete, self::Funded => 'info',
            self::Suspended => 'danger',
            self::Draft, self::Finished, self::Closed => 'muted',
        };
    }

    public function acceptsInvestment(): bool
    {
        return match ($this) {
            self::Open, self::Active, self::AlmostComplete => true,
            default => false,
        };
    }

    public function isPublic(): bool
    {
        return match ($this) {
            self::Draft, self::Suspended => false,
            default => true,
        };
    }

    /**
     * @return list<self>
     */
    public static function publicCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->isPublic()));
    }

    /**
     * @return list<self>
     */
    public static function investableCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status) => $status->acceptsInvestment()));
    }
}
