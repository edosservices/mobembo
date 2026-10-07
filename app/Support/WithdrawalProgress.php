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
            ReviewStatus::Processing => ['done', 'current', 'waiting'],
            ReviewStatus::Rejected => ['done', 'refused', 'waiting'],
            ReviewStatus::Pending => ['done', 'waiting', 'waiting'],
            default => ['current', 'waiting', 'waiting'],
        };

        $timezone = (string) config('app.timezone');
        $requestedAt = $withdrawal?->created_at?->timezone($timezone)->format('d/m/Y H:i');
        $processingAt = $withdrawal?->processing_at?->timezone($timezone)->format('d/m/Y H:i');
        $paidAt = $status === ReviewStatus::Approved
            ? $withdrawal?->reviewed_at?->timezone($timezone)->format('d/m/Y H:i')
            : null;

        $processing = match ($status) {
            ReviewStatus::Rejected => 'Retrait refusé'.($withdrawal?->rejection_reason ? ' : '.$withdrawal->rejection_reason : '.'),
            ReviewStatus::Processing, ReviewStatus::Approved => $processingAt
                ? 'En traitement depuis le '.$processingAt.'.'
                : 'Vérification en cours.',
            ReviewStatus::Pending => BusinessCalendar::withdrawalsOpen()
                ? 'Vérification en cours. Cela peut prendre de quelques minutes à quelques heures.'
                : 'La vérification reprend lundi.',
            default => 'Vérification du lundi au samedi.',
        };

        return [
            [
                'title' => 'Retrait demandé',
                'state' => $states[0],
                'detail' => $requestedAt
                    ? 'Demandé le '.$requestedAt.'.'
                    : 'Indiquez le montant, puis envoyez la demande.',
            ],
            [
                'title' => 'En traitement',
                'state' => $states[1],
                'detail' => $processing,
            ],
            [
                'title' => 'Retrait effectué',
                'state' => $states[2],
                'detail' => $paidAt
                    ? 'Payé le '.$paidAt.'. Les fonds sont versés dans votre compte.'
                    : 'Les fonds sont versés dans votre compte.',
            ],
        ];
    }
}
