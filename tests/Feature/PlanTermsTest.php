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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanTermsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_a_plan_with_an_image_without_rewriting_open_positions(): void
    {
        Storage::fake('public');
        $this->travelTo('2026-10-05 10:00:00');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->credit($user, '200.00');

        $this->actingAs($admin)->post(route('admin.projects.store'), [
            ...$this->planPayload(),
            'image' => UploadedFile::fake()->image('gold.jpg', 40, 40),
        ])->assertRedirect();

        $project = Project::query()->where('slug', 'zelvora-gold')->firstOrFail();
        Storage::disk('public')->assertExists($project->image_path);
        $this->assertSame('ZELVORA GOLD', $project->name);
        $this->assertSame('Construisez votre avenir financier', $project->slogan);
        $this->assertSame('10.00', Money::of($project->min_investment));
        $this->assertSame('1500.00', Money::of($project->max_investment));
        $this->assertSame(15, $project->duration_days);
        $this->assertSame('30.0000', number_format((float) $project->expected_return_percent, 4, '.', ''));
        $this->assertTrue($project->is_active);

        $this->actingAs($admin)->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Aperçu du calcul')
            ->assertSee('Gain total')
            ->assertSee('Jours ouvrés')
            ->assertSee('Capital à l’échéance')
            ->assertSee('30,00 $');

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = Investment::query()->firstOrFail();
        $this->assertSame('30.00', Money::of($investment->planned_return));
        $this->assertSame(11, $investment->profit_days);
        $this->assertSame('2.73', Money::of($investment->daily_return));
        $this->assertSame('2026-10-20', $investment->ends_at->toDateString());

        $this->actingAs($admin)->put(route('admin.projects.update', $project), [
            ...$this->planPayload(),
            'expected_return_percent' => '40',
            'duration_days' => 30,
            'slogan' => 'Nouveau slogan',
            'min_investment' => '20.00',
            'max_investment' => '900.00',
            'is_active' => '1',
        ])->assertRedirect();

        $project->refresh();
        $investment->refresh();
        $this->assertSame('40.0000', number_format((float) $project->expected_return_percent, 4, '.', ''));
        $this->assertSame(30, $project->duration_days);
        $this->assertSame('30.00', Money::of($investment->planned_return));
        $this->assertSame(11, $investment->profit_days);
        $this->assertSame('30.0000', number_format((float) $investment->expected_return_percent, 4, '.', ''));
        $this->assertSame(15, $investment->duration_days);
    }

    public function test_inactive_plan_blocks_new_positions_and_keeps_the_current_cycle(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->credit($user, '200.00');
        $project = $this->gold();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.projects.update', $project), [
            ...$this->planPayload(),
            'is_active' => '0',
            'status' => ProjectStatus::Open->value,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->assertSame(1, Investment::query()->count());
        $this->travelTo('2026-10-06 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->assertSame(2, InvestmentProfit::query()->count());
        $this->assertSame(InvestmentStatus::Active, Investment::query()->first()->status);
    }

    public function test_server_rejects_amounts_outside_the_plan_bounds_and_an_insufficient_balance(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        $user = User::factory()->create();
        $this->credit($user, '50.00');
        $project = $this->gold();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '9',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '1501',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->assertSame(0, Investment::query()->count());
        $this->assertSame('50.00', Money::of($user->wallet()->first()->available_balance));
    }

    public function test_gold_position_credits_exactly_thirty_dollars_and_returns_capital_once(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        $user = User::factory()->create();
        $this->credit($user, '200.00');
        $project = $this->gold();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => Investment::query()->value('idempotency_key'),
        ])->assertRedirect();

        $this->assertSame(1, Investment::query()->count());
        $investment = Investment::query()->firstOrFail();
        $firstProfit = LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->firstOrFail();
        $this->assertSame('102.73', Money::of($firstProfit->balance_after));
        $this->assertSame('2.73', Money::of($investment->returns_credited));

        $cursor = Carbon::parse('2026-10-05')->startOfDay();
        $end = Carbon::parse('2026-10-20')->startOfDay();

        while ($cursor->lt($end)) {
            if (BusinessCalendar::growsOn($cursor)) {
                $this->travelTo($cursor->copy()->setTime(0, 10));
                $this->artisan('investments:process-daily-profits', ['--date' => $cursor->toDateString()])->assertSuccessful();
                $this->artisan('investments:process-daily-profits', ['--date' => $cursor->toDateString()])->assertSuccessful();
            }
            $cursor->addDay();
        }

        $amounts = InvestmentProfit::query()->orderBy('profit_date')->pluck('amount')->map(fn ($amount) => Money::of($amount))->all();
        $this->assertSame(PlanMath::schedule('30.00', 11), $amounts);
        $this->assertSame('30.00', Money::of(Investment::query()->value('returns_credited')));

        $this->travelTo('2026-10-20 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $investment->refresh();
        $user->wallet->refresh();
        $this->assertSame(InvestmentStatus::Completed, $investment->status);
        $this->assertSame('100.00', Money::of($investment->capital_returned));
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::CapitalReturn)->count());
        $this->assertSame(11, InvestmentProfit::query()->count());
        $this->assertSame('0.00', Money::of($user->wallet->invested_balance));
        $this->assertSame('230.00', Money::of($user->wallet->available_balance));
        $this->assertSame(1, $user->notifications()->where('data->kind', 'investment_completed')->count());
        $this->assertSame(1, $user->notifications()->where('data->kind', 'capital_returned')->count());
        $this->assertSame([], app(WalletService::class)->findDrift());

        $this->actingAs($user)->get(route('investments.show', $investment))
            ->assertOk()
            ->assertSee('Montant investi')
            ->assertSee('Gain total prévu')
            ->assertSee('Profits déjà crédités')
            ->assertSee('Profits restants')
            ->assertSee('Capital à récupérer à l’échéance')
            ->assertSee('Montant total généré')
            ->assertSee('130,00 $')
            ->assertSee('Terminé');
    }

    public function test_a_five_hundred_dollar_plan_returns_exactly_one_hundred_of_profit(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        $user = User::factory()->create();
        $this->credit($user, '500.00');
        $project = $this->project([
            'name' => 'ZELVORA SELECT',
            'slug' => 'zelvora-select',
            'min_investment' => '100.00',
            'max_investment' => '2000.00',
            'duration_days' => 30,
            'expected_return_percent' => '20.0000',
        ]);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '500',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = Investment::query()->firstOrFail();
        $this->assertSame('100.00', Money::of($investment->planned_return));
        $this->assertSame(
            PlanMath::schedule('100.00', $investment->profit_days),
            $this->play($investment->starts_at, $investment->ends_at),
        );

        $this->travelTo($investment->ends_at->copy()->setTime(0, 10));
        $this->artisan('investments:process-daily-profits')->assertSuccessful();
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $investment->refresh();
        $user->wallet->refresh();
        $this->assertSame('100.00', Money::of($investment->returns_credited));
        $this->assertSame('500.00', Money::of($investment->capital_returned));
        $this->assertSame(InvestmentStatus::Completed, $investment->status);
        $this->assertSame('600.00', Money::of($user->wallet->available_balance));
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::CapitalReturn)->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_four_active_positions_block_a_fifth_until_one_is_completed(): void
    {
        $this->travelTo('2026-10-05 10:00:00');
        $user = User::factory()->create();
        $this->credit($user, '80.00');
        $project = $this->gold(['min_investment' => '10.00', 'duration_days' => 1, 'expected_return_percent' => '10.0000']);

        foreach (range(1, 4) as $ignored) {
            $this->actingAs($user)->post(route('investments.store', $project), [
                'amount' => '10',
                'idempotency_key' => (string) Str::uuid(),
            ])->assertRedirect();
        }

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->travelTo('2026-10-06 00:10:00');
        $this->artisan('investments:process-daily-profits')->assertSuccessful();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame(1, Investment::query()->where('status', InvestmentStatus::Active)->count());
        $this->assertSame(4, Investment::query()->where('status', InvestmentStatus::Completed)->count());
    }

    /**
     * @return list<string>
     */
    private function play(Carbon $start, Carbon $end): array
    {
        $cursor = $start->copy()->startOfDay();
        $limit = $end->copy()->startOfDay();

        while ($cursor->lt($limit)) {
            if (BusinessCalendar::growsOn($cursor)) {
                $this->travelTo($cursor->copy()->setTime(0, 10));
                $this->artisan('investments:process-daily-profits', ['--date' => $cursor->toDateString()])->assertSuccessful();
            }
            $cursor->addDay();
        }

        return InvestmentProfit::query()->orderBy('profit_date')->pluck('amount')->map(fn ($amount) => Money::of($amount))->all();
    }

    private function gold(array $overrides = []): Project
    {
        return $this->project(array_merge([
            'name' => 'ZELVORA GOLD',
            'slug' => 'zelvora-gold',
            'slogan' => 'Construisez votre avenir financier',
            'min_investment' => '10.00',
            'max_investment' => '1500.00',
            'duration_days' => 15,
            'expected_return_percent' => '30.0000',
        ], $overrides));
    }

    private function planPayload(): array
    {
        return [
            'name' => 'ZELVORA GOLD',
            'slogan' => 'Construisez votre avenir financier',
            'slug' => 'zelvora-gold',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'currency' => 'USD',
            'target_amount' => '100000.00',
            'min_investment' => '10.00',
            'max_investment' => '1500.00',
            'duration_days' => 15,
            'expected_return_percent' => '30',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Le capital est restitué à l’échéance.',
            'description' => 'Plan or.',
            'status' => ProjectStatus::Open->value,
            'is_active' => '1',
        ];
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
            'name' => 'Plan test',
            'slug' => 'plan-'.Str::lower(Str::random(5)),
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 15,
            'expected_return_percent' => '30.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Capital restitué à échéance.',
            'status' => ProjectStatus::Open,
            'is_active' => true,
            'is_demo' => false,
        ], $overrides));
    }
}
