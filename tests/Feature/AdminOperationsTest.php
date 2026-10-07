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

    public function test_admin_can_edit_a_whole_plan_and_a_client_profile_without_touching_the_ledger(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Client Avant']);
        $this->credit($user, '30.00');
        $project = $this->project(['name' => 'Plan ancien', 'duration_days' => 90, 'min_investment' => '10.00']);
        $before = LedgerEntry::query()->count();

        $this->actingAs($admin)->get(route('admin.projects.edit', $project))
            ->assertOk()
            ->assertSee('Modifier le plan')
            ->assertSee('Règles économiques')
            ->assertSee('Investissement minimum');

        $this->actingAs($admin)->put(route('admin.projects.update', $project), [
            'name' => 'Plan complet',
            'slug' => 'plan-complet',
            'location' => 'Lubumbashi',
            'category' => 'Commercial',
            'currency' => 'USD',
            'target_amount' => '200000.00',
            'min_investment' => '25.00',
            'duration_days' => 120,
            'expected_return_percent' => '9,5',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Conditions mises à jour.',
            'description' => 'Description mise à jour.',
            'status' => ProjectStatus::Open->value,
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame('Plan complet', $project->name);
        $this->assertSame('plan-complet', $project->slug);
        $this->assertSame(120, $project->duration_days);
        $this->assertSame('25.00', Money::of($project->min_investment));
        $this->assertSame($before, LedgerEntry::query()->count());

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Client Après',
            'phone' => $user->phone,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Client Après', $user->fresh()->name);
        $this->assertSame('30.00', Money::of($user->wallet()->first()->available_balance));
    }

    public function test_admin_can_publish_whatsapp_and_telegram_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Rejoindre WhatsApp')
            ->assertDontSee('Rejoindre Telegram');
        $this->get(route('contact'))->assertOk()->assertDontSee('Rejoindre WhatsApp');

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Groupes WhatsApp et Telegram')
            ->assertSee('name="whatsapp_url"', false)
            ->assertSee('name="telegram_url"', false);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...$this->settingsPayload(),
            'whatsapp_url' => 'javascript:alert(1)',
            'telegram_url' => 'https://t.me/zelvora',
        ])->assertSessionHasErrors('whatsapp_url');

        $this->assertNull(PlatformSetting::current()->fresh()->whatsapp_url);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...$this->settingsPayload(),
            'whatsapp_url' => 'https://chat.whatsapp.com/zelvora',
            'telegram_url' => 'https://t.me/zelvora',
        ])->assertRedirect()->assertSessionHas('success');

        $settings = PlatformSetting::current()->fresh();
        $this->assertSame('https://chat.whatsapp.com/zelvora', $settings->whatsapp_url);
        $this->assertSame('https://t.me/zelvora', $settings->telegram_url);

        auth()->logout();

        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Rejoindre WhatsApp')
            ->assertDontSee('Rejoindre Telegram');

        $this->get(route('contact'))->assertOk()->assertSee('Rejoindre WhatsApp')->assertSee('Rejoindre Telegram');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rejoindre WhatsApp')
            ->assertSee('Rejoindre Telegram');
    }

    public function test_a_client_cannot_open_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.users.impersonate', $other))
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function settingsPayload(): array
    {
        return [
            'withdrawal_fee_percent' => '5',
            'withdrawal_fee_fixed' => '0.00',
            'withdrawal_min' => '5.00',
            'withdrawal_max' => '10000.00',
            'referral_enabled' => '1',
            'referral_trigger' => 'approved_deposit',
            'referral_rate_percent' => '10',
            'legal_disclaimer' => 'Les rendements affichés sont des estimations.',
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
