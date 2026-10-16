<?php

namespace App\Services\Accounts;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;

class AccountProvisioner
{
    public function provision(User $user): void
    {
        $user->profile()->firstOrCreate([], [
            'country' => 'République démocratique du Congo',
        ]);

        $hasCurrent = $user->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->exists();

        if ($hasCurrent) {
            return;
        }

        $plan = Plan::query()->where('slug', 'gratuit')->where('is_active', true)->first();

        if ($plan === null) {
            return;
        }

        $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
        ]);
    }
}
