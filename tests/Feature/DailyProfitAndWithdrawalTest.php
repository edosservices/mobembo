<?php

namespace Tests\Feature;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Models\Investment;
use App\Models\InvestmentProfit;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletService;
use App\Support\BusinessCalendar;
use App\Support\Money;
use App\Support\PlanMath;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyProfitAndWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_business_day_is_credited_once_and_the_page_does_not_credit_again(): void
    {
        $this->travelTo('2026-10-07 15:00:00');
        [$user, $project] = $this->investor('200.00');

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = Investment::query()->firstOrFail();
        $this->assertSame(InvestmentStatus::Active, $investment->status);
        $this->assertSame('2026-10-07', $investment->starts_at->toDateString());
        $this->assertSame('5.00', Money::of($investment->returns_credited));
        $this->assertSame(1, InvestmentProfit::query()->count());
        $this->assertSame('daily_profit', InvestmentProfit::query()->value('type'));

        $user->wallet->refresh();
        $this->assertSame('105.00', Money::of($user->wallet->available_balance));
        $this->assertSame('100.00', Money::of($user->wallet->invested_balance));

        $this->actingAs($user)->get(route('investments.show', $investment))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Solde retirable');
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->assertSame(1, InvestmentProfit::query()->count());
        $this->assertSame('105.00', Money::of($user->wallet->refresh()->available_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_the_first_profit_is_credited_only_on_a_weekday(): void
    {
        foreach (['2026-10-05', '2026-10-06', '2026-10-09'] as $date) {
            $this->travelTo($date.' 10:00:00');
            [$user, $project] = $this->investor('100.00', [
                'duration_days' => 10,
                'expected_return_percent' => '10.0000',
                'slug' => 'plan-'.$date,
            ]);

            $this->actingAs($user)->post(route('investments.store', $project), [
                'amount' => '100',
                'idempotency_key' => (string) Str::uuid(),
            ])->assertRedirect();

            $investment = Investment::query()->where('user_id', $user->id)->firstOrFail();
            $profit = InvestmentProfit::query()->where('investment_id', $investment->id)->firstOrFail();
            $this->assertSame($date, $profit->profit_date->toDateString());
            $opened = Carbon::parse($date)->startOfDay();
            $openDays = BusinessCalendar::scheduledProfitDays($opened, $opened->copy()->addDays(10));
            $this->assertSame(PlanMath::ordinaryDaily('10.00', $openDays), Money::of($user->wallet->refresh()->available_balance));
            $this->assertSame('100.00', Money::of($user->wallet->invested_balance));
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $user->id,
                'data->kind' => 'daily_profit',
            ]);

            $this->artisan('investments:process-daily-profits', ['--date' => $date])->assertSuccessful();
            $this->artisan('investments:process-daily-profits', ['--date' => $date])->assertSuccessful();
            $this->assertSame(1, InvestmentProfit::query()->where('investment_id', $investment->id)->count());
            $opened = Carbon::parse($date)->startOfDay();
            $openDays = BusinessCalendar::scheduledProfitDays($opened, $opened->copy()->addDays(10));
            $this->assertSame(PlanMath::ordinaryDaily('10.00', $openDays), Money::of($user->wallet->refresh()->available_balance));
        }

        foreach (['2026-10-10', '2026-10-11'] as $date) {
            $this->travelTo($date.' 10:00:00');
            [$user, $project] = $this->investor('100.00', [
                'duration_days' => 10,
                'expected_return_percent' => '10.0000',
                'slug' => 'plan-weekend-'.$date,
            ]);

            $this->actingAs($user)->post(route('investments.store', $project), [
                'amount' => '100',
                'idempotency_key' => (string) Str::uuid(),
            ])->assertRedirect();

            $investment = Investment::query()->where('user_id', $user->id)->firstOrFail();
            $this->assertSame(0, InvestmentProfit::query()->where('investment_id', $investment->id)->count());
            $this->assertSame('0.00', Money::of($user->wallet->refresh()->available_balance));
            $this->assertSame('100.00', Money::of($user->wallet->invested_balance));
            $this->artisan('investments:process-daily-profits', ['--date' => $date])->assertSuccessful();
            $this->assertSame(0, InvestmentProfit::query()->where('investment_id', $investment->id)->count());
        }

        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_weekend_investment_receives_its_first_profit_on_monday(): void
    {
        foreach (['2026-10-10', '2026-10-11'] as $date) {
            $this->travelTo($date.' 10:00:00');
            [$user, $project] = $this->investor('100.00', [
                'duration_days' => 10,
                'expected_return_percent' => '10.0000',
                'slug' => 'plan-monday-'.$date,
            ]);

            $this->actingAs($user)->post(route('investments.store', $project), [
                'amount' => '100',
                'idempotency_key' => (string) Str::uuid(),
            ])->assertRedirect();

            $this->travelTo('2026-10-11 00:10:00');
            $this->artisan('investments:process-daily-profits', ['--date' => '2026-10-11'])->assertSuccessful();
            $investment = Investment::query()->where('user_id', $user->id)->firstOrFail();
            $this->assertSame(0, InvestmentProfit::query()->where('investment_id', $investment->id)->count());

            $this->travelTo('2026-10-12 00:10:00');
            $this->artisan('investments:process-daily-profits', ['--date' => '2026-10-12'])->assertSuccessful();
            $this->artisan('investments:process-daily-profits', ['--date' => '2026-10-12'])->assertSuccessful();
            $this->assertSame(
                ['2026-10-12'],
                InvestmentProfit::query()->where('investment_id', $investment->id)->orderBy('profit_date')->pluck('profit_date')->map->toDateString()->all(),
            );
            $opened = Carbon::parse($date)->startOfDay();
            $openDays = BusinessCalendar::scheduledProfitDays($opened, $opened->copy()->addDays(10));
            $this->assertSame(PlanMath::ordinaryDaily('10.00', $openDays), Money::of($user->wallet->refresh()->available_balance));
            $this->assertSame('100.00', Money::of($user->wallet->invested_balance));
        }

        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_investments_opened_before_daily_profits_are_not_backfilled(): void
    {
        $this->travelTo('2026-10-12 08:00:00');
        [$user, $project] = $this->investor('100.00');
        app(WalletService::class)->invest($user, '100.00', [
            'description' => 'Capital déjà placé',
            'idempotency_key' => 'legacy-invest-'.$user->id,
        ]);

        Investment::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'project_id' => $project->id,
            'amount' => '100.00',
            'currency' => 'USD',
            'expected_return_percent' => '10.0000',
            'duration_days' => 10,
            'returns_credited' => '0.00',
            'capital_returned' => '0.00',
            'invested_at' => '2026-09-01 10:00:00',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-11-01',
            'profit_effective_from' => '2026-10-12',
            'status' => InvestmentStatus::Active,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $this->assertSame(
            ['2026-10-12'],
            InvestmentProfit::query()->orderBy('profit_date')->pluck('profit_date')->map->toDateString()->all(),
        );
        $legacy = Investment::query()->firstOrFail();
        $index = BusinessCalendar::profitDayIndex($legacy->starts_at, Carbon::parse('2026-10-12'));
        $expected = PlanMath::amountForIndex($legacy->planned_return, (int) $legacy->profit_days, (int) $index);
        $this->assertSame($expected, Money::of($legacy->returns_credited));
        $this->assertSame($expected, Money::of($user->wallet->refresh()->available_balance));
        $this->assertSame('100.00', Money::of($user->wallet->invested_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_the_daily_command_catches_up_without_paying_twice_or_on_the_weekend(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        [$user, $project] = $this->investor('100.00', ['duration_days' => 10, 'expected_return_percent' => '10.0000']);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->travelTo('2026-10-09 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $this->assertSame(5, InvestmentProfit::query()->count());
        $this->assertSame('6.25', Money::of(Investment::query()->value('returns_credited')));

        $this->travelTo('2026-10-10 08:00:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->assertSame(5, InvestmentProfit::query()->count());

        $this->travelTo('2026-10-12 08:00:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->assertSame(6, InvestmentProfit::query()->count());
        $user->wallet->refresh();
        $this->assertSame('7.50', Money::of($user->wallet->available_balance));
        $this->assertSame('100.00', Money::of($user->wallet->invested_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_finished_investment_returns_capital_once_and_frees_a_plan_slot(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        [$user, $project] = $this->investor('80.00', ['duration_days' => 1, 'expected_return_percent' => '10.0000', 'min_investment' => '10.00']);

        foreach (range(1, 4) as $index) {
            $this->actingAs($user)->post(route('investments.store', $project), [
                'amount' => '10',
                'idempotency_key' => (string) Str::uuid(),
            ])->assertRedirect();
        }

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');
        $this->assertSame(4, Investment::query()->where('status', InvestmentStatus::Active)->count());

        $this->travelTo('2026-10-06 00:05:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $this->assertSame(4, $user->notifications()->where('data->kind', 'investment_completed')->count());
        $this->assertSame(0, Investment::query()->where('status', InvestmentStatus::Active)->count());
        $this->assertSame(4, Investment::query()->where('status', InvestmentStatus::Completed)->count());
        $user->wallet->refresh();
        $this->assertSame('0.00', Money::of($user->wallet->invested_balance));
        $this->assertSame('84.00', Money::of($user->wallet->available_balance));

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
        $this->assertSame(1, Investment::query()->where('status', InvestmentStatus::Active)->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_withdrawal_uses_the_withdrawable_balance_with_a_minimum_and_a_twelve_percent_fee(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $user = User::factory()->create();
        $this->credit($user, '20.00');
        $settings = PlatformSetting::current();
        $this->assertSame('3.50', Money::of($settings->withdrawal_min));
        $this->assertSame('12.00', Money::of($settings->withdrawal_fee_percent));

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('3.49'))
            ->assertSessionHas('error');
        $this->assertSame('20.00', Money::of($user->wallet->refresh()->available_balance));

        $replay = $this->withdrawal('10');
        $this->actingAs($user)->post(route('withdrawals.store'), $replay)
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('withdrawals.store'), $replay)
            ->assertRedirect()
            ->assertSessionHas('success');

        $withdrawal = $user->withdrawals()->first();
        $this->assertSame('1.20', Money::of($withdrawal->fee));
        $this->assertSame('8.80', Money::of($withdrawal->net_amount));
        $this->assertSame('10.00', Money::of($user->wallet->refresh()->available_balance));
        $this->assertSame('10.00', Money::of($user->wallet->locked_balance));
        $this->assertSame(1, $user->withdrawals()->count());
        $this->assertSame(1, $user->notifications()->where('data->kind', 'withdrawal_requested')->count());

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('10.01'))
            ->assertSessionHas('error');
        $this->assertSame(1, $user->withdrawals()->count());

        $investor = User::factory()->create();
        $this->credit($investor, '30.00');
        $project = $this->project(['min_investment' => '10.00', 'duration_days' => 180, 'expected_return_percent' => '8.0000']);
        $this->actingAs($investor)->post(route('investments.store', $project), [
            'amount' => '20',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();
        $investor->wallet->refresh();
        $this->assertSame('20.00', Money::of($investor->wallet->invested_balance));
        $this->actingAs($investor)->post(route('withdrawals.store'), $this->withdrawal('20'))
            ->assertSessionHas('error');
        $this->assertSame(0, $investor->withdrawals()->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_bonus_and_commission_are_withdrawable_and_notified(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $admin = User::factory()->admin()->create();
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by_id' => $referrer->id]);
        PlatformSetting::current()->forceFill(['referral_rate_percent' => '10.0000'])->save();

        $this->actingAs($admin)->post(route('admin.users.bonus', $user), [
            'amount' => '10',
            'reason' => 'Bonus de bienvenue',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $user->wallet->refresh();
        $this->assertSame('10.00', Money::of($user->wallet->available_balance));
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->kind' => 'bonus_received',
        ]);

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('4'))
            ->assertRedirect();
        $this->assertSame('6.00', Money::of($user->wallet->refresh()->available_balance));

        $this->credit($user, '50.00');
        $deposit = \App\Models\Deposit::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'amount' => '50.00',
            'currency' => 'USD',
            'method' => 'airtel_money',
            'reference' => 'AT-COMM01',
            'reference_lock' => 'airtel_money:AT-COMM01',
            'proof_path' => 'proofs/test.jpg',
            'status' => \App\Enums\ReviewStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit))->assertRedirect();

        $referrer->wallet->refresh();
        $this->assertSame('5.00', Money::of($referrer->wallet->available_balance));
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $referrer->id,
            'data->kind' => 'commission_received',
        ]);
        $this->actingAs($referrer)->get(route('referral'))
            ->assertOk()
            ->assertSee('Commission gagnée')
            ->assertSee('Commission disponible')
            ->assertSee('5,00 $');

        $this->actingAs($referrer)->post(route('withdrawals.store'), $this->withdrawal('3.50'))
            ->assertRedirect();
        $this->assertSame('1.50', Money::of($referrer->wallet->refresh()->available_balance));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_withdrawal_follows_requested_processing_paid_or_rejected(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->credit($user, '20.00');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Retirer mes gains')
            ->assertSee('Capital investi')
            ->assertSee('Solde retirable');

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('3.50'))
            ->assertRedirect();

        $withdrawal = $user->withdrawals()->firstOrFail();
        $this->assertSame('pending', $withdrawal->status->value);
        $this->assertSame('0.42', Money::of($withdrawal->fee));
        $this->assertSame('3.08', Money::of($withdrawal->net_amount));
        $this->assertSame('16.50', Money::of($user->wallet->refresh()->available_balance));
        $this->assertSame('3.50', Money::of($user->wallet->locked_balance));

        $this->actingAs($other)->post(route('admin.withdrawals.process', $withdrawal))->assertForbidden();
        $this->actingAs($user)->get(route('admin.withdrawals.show', $withdrawal))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.withdrawals.process', $withdrawal))->assertRedirect();
        $withdrawal->refresh();
        $this->assertSame('processing', $withdrawal->status->value);
        $this->assertNotNull($withdrawal->processing_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->kind' => 'withdrawal_processing',
        ]);

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal))->assertRedirect();
        $withdrawal->refresh();
        $this->assertSame('approved', $withdrawal->status->value);
        $this->assertSame('16.50', Money::of($user->wallet->refresh()->available_balance));
        $this->assertSame('0.00', Money::of($user->wallet->locked_balance));
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'data->kind' => 'withdrawal_paid',
        ]);

        $this->actingAs($admin)->post(route('admin.withdrawals.process', $withdrawal))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('approved', $withdrawal->refresh()->status->value);

        $second = User::factory()->create();
        $this->credit($second, '10.00');
        $this->actingAs($second)->post(route('withdrawals.store'), $this->withdrawal('10'))->assertRedirect();
        $refused = $second->withdrawals()->firstOrFail();
        $this->actingAs($admin)->post(route('admin.withdrawals.process', $refused))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $refused), [
            'reason' => 'Compte incorrect',
        ])->assertRedirect();
        $refused->refresh();
        $this->assertSame('rejected', $refused->status->value);
        $this->assertSame('Compte incorrect', $refused->rejection_reason);
        $this->assertSame('10.00', Money::of($second->wallet->refresh()->available_balance));
        $this->assertSame('0.00', Money::of($second->wallet->locked_balance));
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $second->id,
            'data->kind' => 'withdrawal_rejected',
        ]);

        $this->actingAs($second)->get(route('withdrawals.create'))
            ->assertOk()
            ->assertSee('Compte incorrect')
            ->assertSee('••••')
            ->assertSee('Retrait demandé')
            ->assertSee('En traitement')
            ->assertSee('Retrait effectué');

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('-1'))
            ->assertSessionHasErrors('amount');

        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    private function investor(string $amount, array $project = []): array
    {
        $user = User::factory()->create();
        $this->credit($user, $amount);

        return [$user, $this->project($project)];
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
            'name' => 'Résidence profit',
            'slug' => 'residence-profit-'.Str::lower(Str::random(5)),
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 2,
            'expected_return_percent' => '10.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Profit quotidien.',
            'status' => ProjectStatus::Active,
            'is_demo' => false,
        ], $overrides));
    }

    private function withdrawal(string $amount): array
    {
        return [
            'amount' => $amount,
            'method' => 'airtel_money',
            'phone' => '0891234567',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
