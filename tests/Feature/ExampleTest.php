<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_entry_opens_the_login_then_the_right_interface(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Connexion')
            ->assertSee('Se connecter')
            ->assertSee('Créer un compte')
            ->assertSee('Numéro de téléphone')
            ->assertSee('Mot de passe')
            ->assertSee('images/logo.png', false)
            ->assertSee('images/hero.jpg', false)
            ->assertDontSee('id="welcome-community"', false)
            ->assertDontSee('Des adresses que l’on a envie de retenir.');

        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('login'))->assertRedirect(route('admin.dashboard'));

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

        auth()->logout();

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Rejoindre WhatsApp', false)
            ->assertSee('https://chat.whatsapp.com/zelvora', false)
            ->assertSee('Rejoindre Telegram', false)
            ->assertSee('https://t.me/zelvora', false)
            ->assertDontSee('id="welcome-community"', false);

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="welcome-community"', false)
            ->assertSee('Bienvenue sur ZELVORA')
            ->assertSee('Rejoignez notre communauté WhatsApp et Telegram')
            ->assertSee('zelvora_welcome_seen', false);
    }

    public function test_the_welcome_modal_hides_a_missing_community_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="welcome-community"', false);

        PlatformSetting::current()->forceFill([
            'whatsapp_url' => 'https://chat.whatsapp.com/seul',
            'telegram_url' => null,
        ])->save();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="welcome-community"', false)
            ->assertSee('https://chat.whatsapp.com/seul', false)
            ->assertSee('Rejoindre WhatsApp', false)
            ->assertDontSee('Rejoindre Telegram');

        PlatformSetting::current()->forceFill([
            'whatsapp_url' => null,
            'telegram_url' => 'https://t.me/seul',
        ])->save();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('https://t.me/seul', false)
            ->assertSee('Rejoindre Telegram', false)
            ->assertDontSee('Rejoindre WhatsApp')
            ->assertDontSee('https://chat.whatsapp.com/zelvora', false);

        PlatformSetting::current()->forceFill([
            'whatsapp_url' => null,
            'telegram_url' => null,
        ])->save();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="welcome-community"', false);
    }

    public function test_logout_returns_to_the_login_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));
    }
}
