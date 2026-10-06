<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_introduces_zelvora(): void
    {
        Project::query()->create([
            'name' => 'Résidence Absente',
            'slug' => 'residence-absente',
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '1000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 30,
            'expected_return_percent' => '5.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation.',
            'status' => ProjectStatus::Active,
            'is_demo' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ZELVORA')
            ->assertSee('Votre argent travaille pour vous pendant que vous dormez.')
            ->assertSee('Votre patrimoine commence ici.')
            ->assertSee('Des adresses que l’on a envie de retenir.')
            ->assertDontSee('Résidence Absente')
            ->assertDontSee('Rejoindre WhatsApp')
            ->assertDontSee('Rejoindre Telegram')
            ->assertDontSee('Comment effectuer un dépôt');

        $this->get(route('faq'))
            ->assertOk()
            ->assertDontSee('Comment effectuer un dépôt');
    }

    public function test_footer_logos_follow_the_links_saved_by_the_admin(): void
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
            'whatsapp_url' => 'https://chat.whatsapp.com/zelvora',
            'telegram_url' => 'https://t.me/zelvora',
        ])->assertRedirect()->assertSessionHas('success');

        $settings = PlatformSetting::current()->fresh();
        $this->assertSame('https://chat.whatsapp.com/zelvora', $settings->whatsapp_url);
        $this->assertSame('https://t.me/zelvora', $settings->telegram_url);

        $this->get('/')
            ->assertOk()
            ->assertSee('Rejoindre WhatsApp', false)
            ->assertSee('https://chat.whatsapp.com/zelvora', false)
            ->assertSee('Rejoindre Telegram', false)
            ->assertSee('https://t.me/zelvora', false)
            ->assertDontSee('Résidence Absente');
    }
}
