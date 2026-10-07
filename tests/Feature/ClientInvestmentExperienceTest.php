<?php

namespace Tests\Feature;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletService;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientInvestmentExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_rate_is_the_duration_yield_divided_by_days_and_is_not_credited(): void
    {
        $this->assertSame('0.0361', ReturnEstimator::dailyPercent('6.50', 180));
        $this->assertSame('0.0361', ReturnEstimator::dailyAmount('100.00', '6.50', 180));
        $this->assertSame('0.0247', ReturnEstimator::dailyPercent('9', 365));
        $this->assertSame('0.1235', ReturnEstimator::dailyAmount('500.00', '9', 365));

        $before = LedgerEntry::query()->count();
        ReturnEstimator::dailyAmount('100.00', '6.50', 180);
        $this->assertSame($before, LedgerEntry::query()->count());
    }

    public function test_investment_dates_elapsed_days_and_accrual_do_not_touch_the_ledger(): void
    {
        $this->travelTo('2026-10-05 15:00:00');
        $user = User::factory()->create();
        $this->credit($user, '500.00');
        $project = $this->project([
            'duration_days' => 365,
            'expected_return_percent' => '9.0000',
            'min_investment' => '10.00',
        ]);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '500',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = Investment::query()->firstOrFail();
        $this->assertSame('2026-10-05', $investment->starts_at->toDateString());
        $this->assertSame('2027-10-05', $investment->ends_at->toDateString());
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        $this->travelTo('2026-11-06 10:00:00');
        $investment->refresh();
        $ledgerBefore = LedgerEntry::query()->count();
        $available = Money::of($user->wallet()->first()->available_balance);

        $this->assertSame(32, $investment->elapsedDays());
        $this->assertSame(25, $investment->accrualDays());
        $this->assertSame(333, $investment->remainingDays());
        $this->assertSame('0.17', $investment->estimatedDailyAmount());
        $this->assertSame('4.25', $investment->estimatedAccruedReturn());
        $this->assertSame($ledgerBefore, LedgerEntry::query()->count());
        $this->assertSame('0.17', Money::of($investment->returns_credited));
        $user->wallet->refresh();
        $this->assertSame($available, Money::of($user->wallet->available_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_sufficient_balance_invests_and_insufficient_balance_is_refused(): void
    {
        $this->travelTo('2026-10-10 11:00:00');
        $user = User::factory()->create();
        $this->credit($user, '40.00');
        $project = $this->project(['min_investment' => '10.00']);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame(InvestmentStatus::Active, Investment::query()->first()->status);
        $user->wallet->refresh();
        $this->assertSame('30.00', Money::of($user->wallet->available_balance));

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $poor = User::factory()->create();
        $this->credit($poor, '4.00');
        $this->actingAs($poor)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $poor->wallet->refresh();
        $this->assertSame('4.00', Money::of($poor->wallet->available_balance));
        $this->assertSame(1, Investment::query()->count());
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_project_page_asks_for_a_deposit_when_the_balance_is_below_the_minimum(): void
    {
        $user = User::factory()->create();
        $this->credit($user, '10.00');
        $project = $this->project(['min_investment' => '50.00', 'slug' => 'projet-eleve']);

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Solde insuffisant')
            ->assertSee('Votre solde : '.money('10.00'), false)
            ->assertSee('Minimum requis : '.money('50.00'), false)
            ->assertSee(route('deposits.create'), false);
    }

    public function test_dashboard_invest_button_follows_the_available_balance(): void
    {
        $empty = User::factory()->create();
        $this->actingAs($empty)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-invest href="'.route('deposits.create').'"', false)
            ->assertSee('data-dashboard-deposit href="'.route('deposits.create').'"', false);

        $funded = User::factory()->create();
        $this->credit($funded, '15.00');
        $this->actingAs($funded)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-invest href="'.route('projects.index').'"', false);
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
            'duration_days' => 180,
            'expected_return_percent' => '8.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation non garantie, crédit uniquement sur distribution réelle.',
            'status' => ProjectStatus::Active,
            'is_demo' => true,
        ], $overrides));
    }
}
