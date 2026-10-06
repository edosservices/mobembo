<?php

namespace App\Support;

use App\Enums\ReviewStatus;
use App\Models\Withdrawal;

class WithdrawalProgress
{
    /**
     * @return list<array{title: string, state: string, detail: string}>
     */
    public static function steps(?Withdrawal $withdrawal = null): array
    {
        $status = $withdrawal?->status;
        $states = match ($status) {
            ReviewStatus::Approved => ['done', 'done', 'done'],
            ReviewStatus::Rejected => ['done', 'refused', 'waiting'],
            ReviewStatus::Pending => ['done', 'current', 'waiting'],
            default => ['current', 'waiting', 'waiting'],
        };

        $verification = match ($status) {
            ReviewStatus::Approved => 'Vérification terminée.',
            ReviewStatus::Rejected => 'Vérification refusée. Le montant reste disponible sur votre compte.',
            ReviewStatus::Pending => BusinessCalendar::withdrawalsOpen()
                ? 'Vérification en cours. Cela peut prendre de quelques minutes à quelques heures.'
                : 'La vérification reprend lundi.',
            default => 'La vérification a lieu du lundi au samedi.',
        };

        return [
            [
                'title' => 'Retrait effectué',
                'state' => $states[0],
                'detail' => $status === null
                    ? 'Indiquez le montant, puis envoyez la demande.'
                    : 'Votre demande est enregistrée.',
            ],
            [
                'title' => 'Vérification',
                'state' => $states[1],
                'detail' => $verification,
            ],
            [
                'title' => 'Les fonds sont versés dans votre compte',
                'state' => $states[2],
                'detail' => $status === ReviewStatus::Approved
                    ? 'Le versement est confirmé.'
                    : 'Cette étape suit la vérification.',
            ],
        ];
    }
}
