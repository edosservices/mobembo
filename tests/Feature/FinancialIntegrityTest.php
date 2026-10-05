<?php

namespace Tests\Feature;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Models\AuditLog;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\DistributionService;
use App\Services\WalletService;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_links_a_referrer_without_paying_a_commission(): void
    {
        $referrer = User::factory()->create(['referral_code' => 'ZLV74200']);

        $this->post('/register', [
            'name' => 'Jean Kabila',
            'phone' => '0811111111',
            'password' => 'secretpass',
            'password_confirmation' => 'secretpass',
            'referral_code' => 'zlv74200',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('phone', '+243811111111')->first();
        $this->assertNotNull($user);
        $this->assertSame($referrer->id, $user->referred_by_id);
        $this->assertSame(0, ReferralCommission::query()->count());
        $this->assertSame('0.00', Money::of($user->wallet->available_balance));
    }

    public function test_deposit_is_not_spendable_until_approval_and_cannot_be_credited_twice(): void
    {
        Storage::fake('local');
        [$user, $admin] = $this->pair();

        $this->actingAs($user)->post(route('deposits.store'), [
            'amount' => '40,50',
            'method' => 'mpesa',
            'reference' => 'MPESA-1001',
            'proof' => UploadedFile::fake()->image('preuve.jpg'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('deposits.create'));

        $user->wallet->refresh();
        $this->assertSame('0.00', Money::of($user->wallet->available_balance));
        $deposit = Deposit::query()->first();
        $this->assertSame(ReviewStatus::Pending, $deposit->status);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit))->assertRedirect();
        $user->wallet->refresh();
        $this->assertSame('40.50', Money::of($user->wallet->available_balance));

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit))->assertSessionHas('error');
        $user->wallet->refresh();
        $this->assertSame('40.50', Money::of($user->wallet->available_balance));
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::Deposit)->count());
        $this->assertTrue(AuditLog::query()->where('action', 'deposit_approved')->where('user_id', $user->id)->exists());
    }

    public function test_referral_commission_is_paid_on_an_approved_deposit_not_on_signup(): void
    {
        Storage::fake('local');
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by_id' => $referrer->id]);
        $admin = User::factory()->admin()->create();
        PlatformSetting::current();

        $this->actingAs($user)->post(route('deposits.store'), $this->depositPayload('80'));
        $deposit = Deposit::query()->first();
        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $referrer->wallet->refresh();
        $this->assertSame('1.60', Money::of($referrer->wallet->available_balance));
        $this->assertSame(1, ReferralCommission::query()->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $referrer->id,
        ]);
    }

    public function test_investment_debits_the_wallet_and_estimates_do_not_create_income(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->credit($user, '100.00');
        $project = $this->project();

        $before = LedgerEntry::query()->count();
        ReturnEstimator::daily('100', '12', 180);
        $this->assertSame($before, LedgerEntry::query()->count());

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '25',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $user->wallet->refresh();
        $this->assertSame('75.00', Money::of($user->wallet->available_balance));
        $this->assertSame('25.00', Money::of($user->wallet->invested_balance));
        $project->refresh();
        $this->assertSame('25.00', Money::of($project->funded_amount));
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        Artisan::call('schedule:run');
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '1',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        app(DistributionService::class)->distribute($project, '10.00', 'Loyers encaissés du mois, nets de charges.', $admin);

        $user->wallet->refresh();
        $investment = Investment::query()->first();
        $this->assertSame('10.00', Money::of($investment->returns_credited));
        $this->assertSame('85.00', Money::of($user->wallet->available_balance));
        $this->assertSame('25.00', Money::of($user->wallet->invested_balance));
    }

    public function test_withdrawal_reserves_funds_applies_fees_and_rejection_restores_them(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->credit($user, '100.00');
        PlatformSetting::current()->forceFill([
            'withdrawal_fee_percent' => 5,
            'withdrawal_fee_fixed' => 0,
            'withdrawal_min' => 5,
            'withdrawal_max' => 10000,
        ])->save();

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => '100',
            'method' => 'airtel_money',
            'phone' => '0899999999',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $user->wallet->refresh();
        $this->assertSame('0.00', Money::of($user->wallet->available_balance));
        $this->assertSame('100.00', Money::of($user->wallet->locked_balance));
        $withdrawal = $user->withdrawals()->first();
        $this->assertSame('5.00', Money::of($withdrawal->fee));
        $this->assertSame('95.00', Money::of($withdrawal->net_amount));

        $this->actingAs($user)->post(route('withdrawals.store'), [
            'amount' => '10',
            'method' => 'airtel_money',
            'phone' => '0899999999',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal), [
            'reason' => 'Numéro injoignable',
        ])->assertRedirect();

        $user->wallet->refresh();
        $this->assertSame('100.00', Money::of($user->wallet->available_balance));
        $this->assertSame('0.00', Money::of($user->wallet->locked_balance));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_bonus_is_audited_and_a_client_cannot_open_admin_pages(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.bonus', $user), [
            'amount' => '20',
            'reason' => 'Bonus promotionnel',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $user->wallet->refresh();
        $this->assertSame('20.00', Money::of($user->wallet->available_balance));
        $log = AuditLog::query()->where('action', 'bonus')->first();
        $this->assertSame('0.00', Money::of($log->old_amount));
        $this->assertSame('20.00', Money::of($log->new_amount));
        $this->assertSame('20.00', Money::of($log->delta_amount));
        $this->assertSame($admin->id, $log->admin_id);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->post('/solde', ['amount' => '9999'])->assertNotFound();
    }

    public function test_blocked_user_cannot_invest_and_api_login_returns_a_token(): void
    {
        $user = User::factory()->create(['status' => \App\Enums\AccountStatus::Blocked]);
        $project = $this->project();

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('login'));

        $active = User::factory()->create();
        $this->postJson('/api/v1/auth/login', [
            'phone' => $active->phone,
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['phone']]);

        $this->postJson('/api/v1/auth/login', [
            'phone' => $user->phone,
            'password' => 'password',
        ])->assertForbidden();
    }

    public function test_guest_can_open_a_project_and_an_open_status_investment_stays_estimated(): void
    {
        $project = $this->project([
            'status' => ProjectStatus::Open,
            'slug' => 'projet-ouvert',
            'min_investment' => '10.00',
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Investir')
            ->assertSee('Rendement journalier estimatif');

        $user = User::factory()->create();
        $this->credit($user, '40.00');

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame('10.00', Money::of(Investment::query()->value('amount')));
        $this->assertSame(0, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_overfunding_is_refused_and_reconciliation_holds(): void
    {
        $user = User::factory()->create();
        $this->credit($user, '500.00');
        $project = $this->project(['target_amount' => '30.00', 'min_investment' => '10.00']);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '30',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $project->refresh();
        $this->assertSame(ProjectStatus::Funded, $project->status);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->assertSame(InvestmentStatus::Active, Investment::query()->first()->status);
        $this->assertSame([], app(WalletService::class)->findDrift());
        $this->artisan('zelvora:reconcile')->assertSuccessful();
    }

    private function pair(): array
    {
        return [User::factory()->create(), User::factory()->admin()->create()];
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

    private function depositPayload(string $amount): array
    {
        return [
            'amount' => $amount,
            'method' => 'orange_money',
            'reference' => 'OM-'.Str::upper(Str::random(6)),
            'proof' => UploadedFile::fake()->image('preuve.jpg'),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
