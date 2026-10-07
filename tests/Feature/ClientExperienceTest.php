<?php

namespace Tests\Feature;

use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Models\Deposit;
use App\Models\PaymentDestination;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\ReferralProgressService;
use App\Services\ReferralService;
use App\Services\WalletService;
use App\Support\InvestmentQuote;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_card_uses_the_real_estimate_for_the_minimum(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $user = User::factory()->create();
        $project = $this->project([
            'min_investment' => '50.00',
            'duration_days' => 270,
            'expected_return_percent' => '7.5000',
            'funded_amount' => '83000.00',
            'target_amount' => '100000.00',
        ]);
        $quote = $project->quote();

        $this->assertSame('3.75', $quote->totalReturn);
        $this->assertSame('53.75', $quote->maturity);
        $this->assertSame(0, LedgerEntry::query()->count());

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee($project->name)
            ->assertSee('Rendement journalier estimatif')
            ->assertSee(ReturnEstimator::percentLabel($quote->dailyPercent))
            ->assertSee(ReturnEstimator::amountLabel($quote->dailyAmount))
            ->assertSee(money($quote->totalReturn))
            ->assertSee(money($quote->maturity))
            ->assertSee('Investir');
    }

    public function test_dashboard_shows_real_balances_and_an_empty_chart_without_history(): void
    {
        $user = User::factory()->create(['name' => 'Amina Kabila']);
        app(WalletService::class)->ensure($user);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bonjour Amina')
            ->assertSee('0,00 $')
            ->assertSee('Votre historique apparaîtra ici après vos premières opérations.')
            ->assertDontSee('1 250,00 $');

        $this->credit($user, '40.00');

        $this->actingAs($user)
            ->get(route('dashboard', ['range' => '30d']))
            ->assertOk()
            ->assertSee('40,00 $')
            ->assertDontSee('Votre historique apparaîtra ici après vos premières opérations.');
    }

    public function test_investment_detail_keeps_estimates_apart_from_credited_income(): void
    {
        $this->travelTo('2026-10-07 11:00:00');
        $user = User::factory()->create();
        $this->credit($user, '100.00');
        $project = $this->project(['min_investment' => '50.00', 'duration_days' => 270, 'expected_return_percent' => '7.5000']);

        $this->actingAs($user)->post(route('investments.store', $project), [
            'amount' => '50',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $investment = $user->investments()->first();
        $this->assertSame('0.02', Money::of($investment->returns_credited));

        $this->actingAs($user)
            ->get(route('investments.show', $investment))
            ->assertOk()
            ->assertSee('Estimation')
            ->assertSee('Revenus réellement crédités')
            ->assertSee('3,75 $')
            ->assertSee('53,75 $')
            ->assertSee('0,02 $');

        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::InvestmentReturn)->count());
    }

    public function test_approved_deposit_pays_one_audited_commission_and_rejection_pays_none(): void
    {
        Storage::fake('local');
        $referrer = User::factory()->create();
        $user = User::factory()->create(['referred_by_id' => $referrer->id]);
        $admin = User::factory()->admin()->create();
        PlatformSetting::current();

        $this->actingAs($user)->post(route('deposits.store'), $this->depositPayload('100'));
        $deposit = Deposit::query()->first();
        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $referrer->wallet->refresh();
        $this->assertSame('10.00', Money::of($referrer->wallet->available_balance));
        $this->assertSame(1, ReferralCommission::query()->count());
        $this->assertSame(1, LedgerEntry::query()->where('user_id', $referrer->id)->where('type', LedgerType::ReferralCommission)->count());

        app(ReferralService::class)->reward(
            \App\Enums\ReferralTrigger::ApprovedDeposit,
            $user,
            '100.00',
            Deposit::class,
            $deposit->id,
            $admin,
        );
        $referrer->wallet->refresh();
        $this->assertSame('10.00', Money::of($referrer->wallet->available_balance));
        $this->assertSame(1, ReferralCommission::query()->count());

        $other = User::factory()->create(['referred_by_id' => $referrer->id]);
        $this->actingAs($other)->post(route('deposits.store'), $this->depositPayload('80'));
        $rejected = Deposit::query()->where('user_id', $other->id)->first();
        $this->actingAs($admin)->post(route('admin.deposits.reject', $rejected), [
            'reason' => 'Justificatif illisible',
        ]);

        $this->assertSame(1, ReferralCommission::query()->count());
        $referrer->wallet->refresh();
        $this->assertSame('10.00', Money::of($referrer->wallet->available_balance));
    }

    public function test_level_follows_active_members_and_ignores_a_plain_signup(): void
    {
        $referrer = User::factory()->create();
        User::factory()->create(['referred_by_id' => $referrer->id]);
        $progress = app(ReferralProgressService::class);

        $this->assertSame('STARTER', $progress->snapshot($referrer)['level']['name']);
        $this->assertSame(0, $progress->snapshot($referrer)['active']);
        $this->assertSame(5, $progress->snapshot($referrer)['remaining']);

        foreach (range(1, 5) as $index) {
            $member = User::factory()->create(['referred_by_id' => $referrer->id]);
            $this->approvedDeposit($member, '20.00', $index);
        }

        $snapshot = $progress->snapshot($referrer);
        $this->assertSame('PRO', $snapshot['level']['name']);
        $this->assertSame(5, $snapshot['active']);
        $this->assertSame(6, $snapshot['referred']);
        $this->assertSame(1, $snapshot['inactive']);
        $this->assertSame('100.00', $snapshot['deposits']);

        $this->actingAs($referrer)
            ->get(route('referral'))
            ->assertOk()
            ->assertSee('Mon équipe')
            ->assertSee('PRO')
            ->assertSee('Actif')
            ->assertSee('Inactif');

        $this->actingAs($referrer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('5 membres actifs avant ELITE')
            ->assertSee('5 filleuls actifs')
            ->assertSee('Débloqué')
            ->assertSee('Verrouillé');
    }

    public function test_transactions_can_be_filtered_without_a_large_selector(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->credit($user, '30.00', LedgerType::Deposit, [
            'description' => 'Dépôt de test',
            'idempotency_key' => 'dep-'.Str::uuid(),
        ]);
        app(WalletService::class)->debitAvailable($user, '5.00', LedgerType::Withdrawal, [
            'description' => 'Retrait de test',
            'idempotency_key' => 'wd-'.Str::uuid(),
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index', ['type' => 'deposit']))
            ->assertOk()
            ->assertSee('Dépôts')
            ->assertSee('Filtrer')
            ->assertSee('Dépôt de test')
            ->assertDontSee('Retrait de test');
    }

    public function test_the_client_sees_a_payment_number_that_the_admin_can_change(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.payments.store'), [
            'method' => 'airtel_money',
            'holder_name' => 'Amina Mukendi',
            'phone' => '0890000001',
        ])->assertRedirect();

        $this->actingAs($client)->get(route('deposits.create'))
            ->assertOk()
            ->assertSee('Amina Mukendi')
            ->assertSee('0890000001')
            ->assertSee('Retour')
            ->assertSee('Menu')
            ->assertSee('J’ai déjà envoyé');

        $destination = PaymentDestination::query()->first();
        $this->actingAs($admin)->put(route('admin.payments.update', $destination), [
            'method' => 'airtel_money',
            'holder_name' => 'Amina Mukendi',
            'phone' => '0890000002',
        ])->assertRedirect();

        $this->actingAs($client)->get(route('deposits.create'))
            ->assertSee('0890000002')
            ->assertDontSee('0890000001');
    }

    public function test_admin_can_replace_level_thresholds(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'withdrawal_fee_percent' => '5',
            'withdrawal_fee_fixed' => '0.00',
            'withdrawal_min' => '5.00',
            'withdrawal_max' => '10000.00',
            'referral_enabled' => '1',
            'referral_trigger' => 'approved_deposit',
            'referral_rate_percent' => '10',
            'legal_disclaimer' => 'Les rendements affichés sont des estimations.',
            'level_starter' => '0',
            'level_pro' => '2',
            'level_elite' => '4',
            'level_vip' => '6',
        ])->assertRedirect();

        $referrer = User::factory()->create();
        foreach (range(1, 2) as $index) {
            $member = User::factory()->create(['referred_by_id' => $referrer->id]);
            $this->approvedDeposit($member, '10.00', $index);
        }

        $this->assertSame('PRO', app(ReferralProgressService::class)->snapshot($referrer)['level']['name']);
    }

    private function credit(User $user, string $amount): void
    {
        app(WalletService::class)->credit($user, $amount, LedgerType::AdminAdjustment, [
            'description' => 'Crédit de test',
            'idempotency_key' => 'test-'.Str::uuid(),
        ]);
    }

    private function approvedDeposit(User $user, string $amount, int $index): void
    {
        Deposit::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => 'orange_money',
            'reference' => 'DEP-'.$index.'-'.Str::upper(Str::random(4)),
            'proof_path' => 'deposits/preuve.jpg',
            'status' => ReviewStatus::Approved,
            'idempotency_key' => 'dep-'.Str::uuid(),
        ]);
    }

    private function project(array $overrides = []): Project
    {
        return Project::query()->create(array_merge([
            'name' => 'Congo Vista',
            'slug' => 'congo-vista-'.Str::lower(Str::random(4)),
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Appartements résidentiels',
            'target_amount' => '100000.00',
            'funded_amount' => '0.00',
            'min_investment' => '50.00',
            'duration_days' => 270,
            'expected_return_percent' => '7.5000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation non garantie.',
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
