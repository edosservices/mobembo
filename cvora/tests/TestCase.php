<?php

namespace Tests;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\User;
use App\Services\Accounts\AccountProvisioner;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ResumeTemplateSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedCatalog(): void
    {
        $this->seed([
            PlanSeeder::class,
            ResumeTemplateSeeder::class,
            SettingSeeder::class,
        ]);
    }

    protected function member(string $plan = 'gratuit'): User
    {
        $this->seedCatalog();
        $user = User::factory()->create();
        app(AccountProvisioner::class)->provision($user);

        if ($plan !== 'gratuit') {
            $target = Plan::query()->where('slug', $plan)->firstOrFail();
            $user->subscriptions()->where('status', SubscriptionStatus::Active->value)->update([
                'status' => SubscriptionStatus::Replaced->value,
                'ends_at' => now(),
            ]);
            $user->subscriptions()->create([
                'plan_id' => $target->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
            ]);
        }

        return $user->fresh();
    }
}
