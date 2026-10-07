<?php

namespace Tests\Feature;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Models\Investment;
use App\Models\InvestmentProfit;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletService;
use App\Support\BusinessCalendar;
use App\Support\Money;
use App\Support\PlanMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanPositionsAndDailyRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_four_positions_on_the_same_plan_each_earn_a_weekday_profit_and_a_fifth_is_refused(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $user = User::factory()->create();
        $this->credit($user, '25.00');
        $plan = $this->project(['name' => 'Plan cinq', 'min_investment' => '5.00', 'duration_days' => 30, 'expected_return_percent' => '12.0000']);
        $other = $this->project(['name' => 'Autre plan', 'slug' => 'autre-plan', 'min_investment' => '5.00', 'duration_days' => 30, 'expected_return_percent' => '12.0000']);
        $start = now()->startOfDay();
        $openDays = BusinessCalendar::scheduledProfitDays($start, $start->copy()->addDays(30));
        $daily = PlanMath::ordinaryDaily(PlanMath::totalGain('5.00', '12'), $openDays);
        $four = '0.00';

        for ($position = 1; $position <= 4; $position++) {
            $four = Money::add($four, $daily);
        }

        $keys = [];

        for ($position = 1; $position <= 4; $position++) {
            $key = (string) Str::uuid();
            $keys[] = $key;
            $this->actingAs($user)->post(route('investments.store', $plan), [
                'amount' => '5',
                'idempotency_key' => $key,
            ])->assertRedirect();
        }

        $this->actingAs($user)->post(route('investments.store', $plan), [
            'amount' => '5',
            'idempotency_key' => $keys[3],
        ])->assertRedirect();

        $this->assertSame(4, Investment::query()->where('project_id', $plan->id)->count());
        $this->assertSame(4, Investment::query()->where('status', InvestmentStatus::Active)->count());

        $this->actingAs($user)->post(route('investments.store', $plan), [
            'amount' => '5',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error', 'Vous avez déjà 4 investissements actifs sur ce plan. Une nouvelle position sera possible lorsqu’un d’eux sera terminé.');

        $this->actingAs($user)->post(route('investments.store', $other), [
            'amount' => '5',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $user->wallet()->first()->refresh();
        $wallet = $user->wallet()->first();
        $profits = Money::add($four, $daily);

        $this->assertSame('25.00', Money::of($wallet->invested_balance));
        $this->assertSame($profits, Money::of($wallet->available_balance));
        $this->assertSame(5, Investment::query()->count());
        $this->assertSame(5, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());
        $this->assertSame(5, InvestmentProfit::query()->count());
        Investment::query()->where('project_id', $plan->id)->each(function (Investment $investment) use ($daily) {
            $this->assertSame($daily, Money::of($investment->returns_credited));
        });

        $this->actingAs($user)->get(route('projects.show', $plan))
            ->assertOk()
            ->assertSee('Positions ouvertes : 4 / 4')
            ->assertSee('Vous avez déjà 4 investissements actifs sur ce plan')
            ->assertDontSee('name="amount"', false);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Revenu journalier')
            ->assertSee('data-balance="daily"', false)
            ->assertSee(money($profits));

        $this->actingAs($user)->get(route('investments.index'))
            ->assertOk()
            ->assertSee('Revenu journalier')
            ->assertSee('Crédité aujourd’hui')
            ->assertSee(money($profits));

        $this->travelTo('2026-10-08 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $user->wallet()->first()->refresh();
        $this->assertSame(Money::add($profits, $profits), Money::of($user->wallet()->first()->available_balance));
        $this->assertSame('25.00', Money::of($user->wallet()->first()->invested_balance));
        $this->assertSame(10, InvestmentProfit::query()->count());

        $this->travelTo('2026-10-10 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->assertSame(15, InvestmentProfit::query()->count());
        $this->assertSame(5, InvestmentProfit::query()->whereDate('profit_date', '2026-10-09')->count());
        $this->assertSame(0, InvestmentProfit::query()->whereDate('profit_date', '2026-10-10')->count());
        $threeDays = '0.00';
        foreach (range(1, 15) as $ignored) {
            $threeDays = Money::add($threeDays, $daily);
        }
        $this->assertSame($threeDays, Money::of($user->wallet()->first()->refresh()->available_balance));
        $this->assertSame('25.00', Money::of($user->wallet()->first()->invested_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_an_empty_dashboard_shows_a_zero_daily_revenue_card(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->ensure($user);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Revenu journalier')
            ->assertSee('data-balance="daily"', false)
            ->assertSee(money('0.00'));

        $this->actingAs($user)->get(route('investments.index'))
            ->assertOk()
            ->assertSee('Revenu journalier')
            ->assertSee('Progression du lundi au vendredi');
    }

    private function credit(User $user, string $amount): void
    {
        app(WalletService::class)->credit($user, $amount, LedgerType::AdminAdjustment, [
            'description' => 'Crédit de test',
            'idempotency_key' => 'test-'.Str::uuid(),
        ]);
    }

    private function project(array $overrides = []): Project
    {
        return Project::query()->create(array_merge([
            'name' => 'Résidence test',
            'slug' => 'residence-test-'.Str::lower(Str::random(5)),
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '5.00',
            'duration_days' => 30,
            'expected_return_percent' => '12.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation non garantie.',
            'status' => ProjectStatus::Active,
            'is_demo' => true,
        ], $overrides));
    }
}
