<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_cannot_open_the_admin_dashboard(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_an_administrator_sees_counts_without_resume_text(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedCatalog();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Comptes')
            ->assertDontSee('contenu du CV');
    }

    public function test_an_administrator_can_change_a_plan_price(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedCatalog();
        $plan = Plan::query()->where('slug', 'premium')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), [
            'name' => 'Premium',
            'description' => 'Offre ajustée',
            'price_cents' => 2500000,
            'currency' => 'CDF',
            'is_active' => 1,
            'is_public' => 1,
            'limits' => json_encode($plan->limits()),
        ])->assertRedirect();

        $this->assertSame(2500000, $plan->fresh()->price_cents);
    }
}
