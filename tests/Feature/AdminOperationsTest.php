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
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_a_plan_percent_without_touching_balances_or_open_investments(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->credit($user, '50.00');
        $project = $this->project(['expected_return_percent' => '8.0000']);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $before = LedgerEntry::query()->count();
        $available = Money::of($user->wallet()->first()->available_balance);

        $this->actingAs($admin)->post(route('admin.projects.return', $project), [
            'expected_return_percent' => '10,5',
        ])->assertRedirect()->assertSessionHas('success');

        $project->refresh();
        $investment = Investment::query()->firstOrFail();
        $this->assertSame('10.5000', number_format((float) $project->expected_return_percent, 4, '.', ''));
        $this->assertSame('8.0000', number_format((float) $investment->expected_return_percent, 4, '.', ''));
        $this->assertSame($before, LedgerEntry::query()->count());
        $user->wallet->refresh();
        $this->assertSame($available, Money::of($user->wallet->available_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_admin_can_open_a_client_account_without_moving_money(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Client Depannage']);
        $this->credit($user, '40.00');
        $project = $this->project();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user))
            ->assertRedirect(route('dashboard'));

        $session = [
            'impersonator_id' => $admin->id,
            'impersonated_user_id' => $user->id,
        ];

        $this->actingAs($user)
            ->withSession($session)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dépannage du compte de Client Depannage');

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('investments.store', $project), [
                'amount' => '10',
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Investment::query()->count());
        $this->assertSame('40.00', Money::of($user->wallet()->first()->available_balance));

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('support.leave'))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_change_withdrawal_and_referral_percents_without_touching_the_ledger(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->credit($user, '25.00');
        $before = LedgerEntry::query()->count();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'withdrawal_fee_percent' => '3,5',
            'withdrawal_fee_fixed' => '1.00',
            'withdrawal_min' => '5.00',
            'withdrawal_max' => '10000.00',
            'referral_enabled' => '1',
            'referral_trigger' => 'approved_deposit',
            'referral_rate_percent' => '4,25',
            'legal_disclaimer' => 'Les rendements affichés sont des estimations.',
        ])->assertRedirect()->assertSessionHas('success');

        $settings = PlatformSetting::current()->fresh();
        $this->assertSame('3.5000', number_format((float) $settings->withdrawal_fee_percent, 4, '.', ''));
        $this->assertSame('4.2500', number_format((float) $settings->referral_rate_percent, 4, '.', ''));
        $this->assertSame($before, LedgerEntry::query()->count());
        $this->assertSame('25.00', Money::of($user->wallet()->first()->available_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_client_cannot_open_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.users.impersonate', $other))
            ->assertForbidden();
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
            'slug' => 'plan-test-'.Str::lower(Str::random(5)),
            'description' => 'Plan de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 180,
            'expected_return_percent' => '8.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation non garantie.',
            'status' => ProjectStatus::Active,
            'is_demo' => false,
        ], $overrides));
    }
}
