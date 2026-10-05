<?php

namespace App\Support;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use Illuminate\Support\Carbon;

class TransactionPresenter
{
    public static function signedAmount(LedgerEntry $entry): string
    {
        $formatted = Money::format($entry->amount);

        if (Money::cmp($entry->amount, '0') > 0 && ! str_starts_with($formatted, '+')) {
            return '+'.$formatted;
        }

        return $formatted;
    }

    public static function clientStatus(LedgerEntry $entry): string
    {
        if ($entry->status === LedgerStatus::Pending) {
            return 'En attente';
        }

        if ($entry->status === LedgerStatus::Reversed) {
            return 'Annulé';
        }

        return match ($entry->type) {
            LedgerType::Deposit => 'Approuvé',
            LedgerType::Withdrawal, LedgerType::Investment => 'Confirmé',
            default => 'Créditée',
        };
    }

    public static function date(LedgerEntry $entry): string
    {
        $date = $entry->created_at instanceof Carbon
            ? $entry->created_at->timezone(config('app.timezone'))
            : now();
        $months = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];

        return $date->format('d').' '.$months[(int) $date->format('n')].' '.$date->format('Y');
    }
}
