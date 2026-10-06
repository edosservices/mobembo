<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletService;
use App\Support\BusinessCalendar;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperatingCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimated_revenue_grows_on_weekdays_and_stays_flat_on_saturday(): void
    {
        $this->travelTo('2026-10-05 09:00:00');
        $user = User::factory()->create();
        $this->credit($user, '100.00');
        $project = $this->project();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = Investment::query()->firstOrFail();
        $this->assertSame(1, $investment->accrualDays());
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        $this->travelTo('2026-10-09 12:00:00');
        $investment->refresh();
        $friday = $investment->estimatedAccruedReturn();
        $this->assertSame(5, $investment->accrualDays());

        $this->travelTo('2026-10-10 12:00:00');
        $investment->refresh();
        $this->assertTrue(BusinessCalendar::isMaintenance());
        $this->assertSame(5, $investment->accrualDays());
        $this->assertSame($friday, $investment->estimatedAccruedReturn());

        $this->travelTo('2026-10-11 12:00:00');
        $investment->refresh();
        $this->assertSame(5, $investment->accrualDays());

        $this->travelTo('2026-10-12 12:00:00');
        $investment->refresh();
        $this->assertSame(6, $investment->accrualDays());
        $this->assertSame('0.00', Money::of($investment->returns_credited));
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        $this->actingAs($user)->get(route('investments.show', $investment))
            ->assertOk()
            ->assertSee('Gain estimatif accumulé')
            ->assertSee('lundi au vendredi')
            ->assertSee('jour de maintenance');
    }

    public function test_withdrawal_page_shows_three_steps_and_sunday_waits_until_monday(): void
    {
        $this->travelTo('2026-10-12 10:00:00');
        $user = User::factory()->create();
        $this->credit($user, '40.00');
        PlatformSetting::current()->forceFill([
            'withdrawal_fee_percent' => 0,
            'withdrawal_fee_fixed' => 0,
            'withdrawal_min' => 5,
            'withdrawal_max' => 10000,
        ])->save();

        $this->actingAs($user)->get(route('withdrawals.create'))
            ->assertOk()
            ->assertSee('Retrait effectué')
            ->assertSee('Vérification')
            ->assertSee('Les fonds sont versés dans votre compte')
            ->assertSee('lundi au samedi')
            ->assertSee('quelques minutes');

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => '20',
            'method' => 'airtel_money',
            'phone' => '0891111111',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($user)->get(route('withdrawals.create'))
            ->assertOk()
            ->assertSee('Vérification en cours');

        $this->travelTo('2026-10-11 10:00:00');
        $this->assertFalse(BusinessCalendar::withdrawalsOpen(Carbon::parse('2026-10-11')));
        $this->actingAs($user)->get(route('withdrawals.create'))
            ->assertOk()
            ->assertSee('reprennent lundi')
            ->assertSee('La vérification reprend lundi');

        $user->wallet->refresh();
        $this->assertSame('20.00', Money::of($user->wallet->available_balance));
        $this->assertSame('20.00', Money::of($user->wallet->locked_balance));
    }

    private function credit(User $user, string $amount): void
    {
        app(WalletService::class)->credit($user, $amount, LedgerType::AdminAdjustment, [
            'description' => 'Crédit de test',
            'idempotency_key' => 'test-'.Str::uuid(),
        ]);
    }

    private function project(): Project
    {
        return Project::query()->create([
            'name' => 'Résidence calendrier',
            'slug' => 'residence-calendrier',
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 180,
            'expected_return_percent' => '8.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation.',
            'status' => ProjectStatus::Active,
            'is_demo' => false,
        ]);
    }
}
