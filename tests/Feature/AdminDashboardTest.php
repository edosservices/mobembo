<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Models\Deposit;
use App\Models\Project;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_lands_on_the_platform_dashboard_and_a_client_cannot(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Administrateur ZELVORA']);
        $client = User::factory()->create();

        $this->post(route('login'), [
            'phone' => $admin->phone,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('état de la plateforme ZELVORA.')
            ->assertSee('Utilisateurs inscrits')
            ->assertSee('Administration')
            ->assertSee('Tout est à jour.')
            ->assertDontSee('Faire un dépôt')
            ->assertDontSee('25 000');

        $this->actingAs($client)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($client)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('état de votre portefeuille.');
    }

    public function test_pending_deposits_and_real_user_counts_appear_on_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create(['name' => 'Client Visible']);
        Deposit::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $client->id,
            'amount' => '40.00',
            'method' => 'mpesa',
            'reference' => 'DEP-ADMIN',
            'proof_path' => 'deposits/preuve.jpg',
            'status' => ReviewStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('1')
            ->assertDontSee('Tout est à jour.');

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Client Visible')
            ->assertDontSee('Accueil');

        $this->actingAs($admin)->get(route('admin.deposits.index'))
            ->assertOk()
            ->assertSee('DEP-ADMIN')
            ->assertSee('Approuver');

        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee('Aucune opération sur cette période.');
    }

    public function test_pending_withdrawals_and_real_chart_points_come_from_the_database(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->create(['name' => 'Client Retrait']);

        Withdrawal::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $client->id,
            'amount' => '100.00',
            'fee' => '5.00',
            'net_amount' => '95.00',
            'method' => 'mpesa',
            'phone' => '0810000095',
            'status' => ReviewStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        Deposit::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $client->id,
            'amount' => '80.00',
            'method' => 'airtel_money',
            'reference' => 'DEP-GRAPH',
            'proof_path' => 'deposits/preuve.jpg',
            'status' => ReviewStatus::Approved,
            'reviewed_at' => now(),
            'idempotency_key' => (string) Str::uuid(),
        ]);

        Project::query()->create([
            'name' => 'Résidence Kasaï',
            'slug' => 'residence-kasai',
            'description' => 'Immeuble de rapport.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '10000.00',
            'min_investment' => '50.00',
            'duration_days' => 180,
            'expected_return_percent' => '8.0000',
            'economic_terms' => 'Revenus locatifs estimés.',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard', ['range' => '7d']))
            ->assertOk()
            ->assertSee('Retraits en attente')
            ->assertSee('80,00 $')
            ->assertDontSee('Aucune opération sur cette période.')
            ->assertDontSee('Tout est à jour.')
            ->assertSee('polyline', false);

        $this->actingAs($admin)->get(route('admin.withdrawals.index'))
            ->assertOk()
            ->assertSee('Client Retrait')
            ->assertSee('100,00 $')
            ->assertSee('5,00 $')
            ->assertSee('95,00 $')
            ->assertSee('0810000095')
            ->assertSee('Approuver');

        $this->actingAs($admin)->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Résidence Kasaï')
            ->assertSee('Suspendre')
            ->assertSee('Terminer')
            ->assertDontSee('Investir');

        $this->actingAs($admin)->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('STARTER')
            ->assertSee('Parrains');

        $this->actingAs($admin)->get(route('admin.audit.index'))->assertOk()->assertSee('Administrateur');
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('Commission de parrainage');
    }
}
