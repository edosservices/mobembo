<?php

namespace App\Http\Presenters;

use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;

class ApiPresenter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'kyc_status' => $user->kyc_status->value,
            'referral_code' => $user->referral_code,
            'must_change_password' => $user->must_change_password,
        ];
    }

    public static function project(Project $project): array
    {
        return [
            'id' => $project->id,
            'uuid' => $project->uuid,
            'name' => $project->name,
            'slug' => $project->slug,
            'location' => $project->location,
            'category' => $project->category,
            'description' => $project->description,
            'target_amount' => (string) $project->target_amount,
            'funded_amount' => (string) $project->funded_amount,
            'remaining_amount' => $project->remainingAmount(),
            'progress_percent' => $project->progressPercent(),
            'min_investment' => (string) $project->min_investment,
            'duration_days' => $project->duration_days,
            'expected_return_percent' => (string) $project->expected_return_percent,
            'expected_return_label' => 'Rendement prévu sur la durée, estimé et non garanti. Il n’est pas crédité automatiquement.',
            'status' => $project->status->value,
            'status_label' => $project->status->label(),
            'is_demo' => $project->is_demo,
            'investable' => $project->isInvestable(),
            'image_url' => $project->imageUrl(),
        ];
    }

    public static function investment(Investment $investment): array
    {
        return [
            'id' => $investment->id,
            'project' => $investment->project ? self::project($investment->project) : ['id' => $investment->project_id],
            'amount' => (string) $investment->amount,
            'expected_return_percent' => (string) $investment->expected_return_percent,
            'estimated_return' => $investment->estimatedReturn(),
            'estimated_daily_return' => $investment->estimatedDailyReturn(),
            'estimated_label' => 'Estimation non garantie, non créditée.',
            'returns_credited' => (string) $investment->returns_credited,
            'capital_returned' => (string) $investment->capital_returned,
            'invested_at' => $investment->invested_at?->toIso8601String(),
            'starts_at' => $investment->starts_at?->toDateString(),
            'ends_at' => $investment->ends_at?->toDateString(),
            'status' => $investment->status->value,
            'status_label' => $investment->status->label(),
        ];
    }

    public static function ledger(LedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'type' => $entry->type->value,
            'type_label' => $entry->type->label(),
            'amount' => (string) $entry->amount,
            'currency' => $entry->currency,
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'reference' => $entry->reference,
            'description' => $entry->description,
            'balance_after' => $entry->balance_after === null ? null : (string) $entry->balance_after,
            'created_at' => $entry->created_at?->toIso8601String(),
        ];
    }
}
