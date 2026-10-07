<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifier;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivePortfolioExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_home_keeps_its_balances_inside_bootstrap_cards(): void
    {
        $user = User::factory()->create(['name' => 'Amina Kabila']);
        app(WalletService::class)->ensure($user);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bonjour Amina')
            ->assertSee('card balance-card balance-invested h-100 border-0', false)
            ->assertSee('card balance-card balance-available h-100 border-0', false)
            ->assertSee('card balance-card balance-bonus h-100 border-0', false)
            ->assertSee('card-body', false)
            ->assertSee('col-6', false)
            ->assertSee('data-live-status', false)
            ->assertSee('En direct', false)
            ->assertSee(route('notifications.feed'), false);
    }

    public function test_portfolio_offers_deposit_withdrawal_and_transfer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('investments.index'))
            ->assertOk()
            ->assertSee('Portefeuille')
            ->assertSee('Déposer')
            ->assertSee('Retirer')
            ->assertSee('Transférer')
            ->assertSee('portfolio-service service-deposit', false)
            ->assertSee('portfolio-service service-withdraw', false)
            ->assertSee('portfolio-service service-transfer', false)
            ->assertSee('a.portfolio-service.service-deposit { background: #9f2d2d !important;', false)
            ->assertSee('white-space: nowrap !important;', false)
            ->assertSee(route('deposits.create'), false)
            ->assertSee(route('withdrawals.create'), false)
            ->assertSee(route('transfers.create'), false)
            ->assertSee('Aucun investissement.')
            ->assertSee('Commencer à investir')
            ->assertSee('Aucun profit crédité pour le moment.')
            ->assertSee('page-portfolio', false);
    }

    public function test_live_feed_reports_only_movements_after_the_cursor(): void
    {
        $user = User::factory()->create();
        app(WalletService::class)->ensure($user);
        app(Notifier::class)->send($user, 'transfer_received', 'Transfert reçu', 'Vous avez reçu 25,00 $.', '/transactions');
        app(Notifier::class)->send($user, 'external', 'Lien externe', 'Ignoré.', 'https://example.test/piege');

        $this->actingAs($user)->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('movements', [])
            ->assertJsonPath('balances', null)
            ->assertJsonPath('unread', 2);

        $response = $this->actingAs($user)->getJson(route('notifications.feed', [
            'since' => now()->subMinute()->format('Y-m-d H:i:s'),
        ]));

        $response->assertOk()
            ->assertJsonPath('unread', 2)
            ->assertJsonCount(2, 'movements')
            ->assertJsonFragment([
                'title' => 'Transfert reçu',
                'body' => 'Vous avez reçu 25,00 $.',
                'kind' => 'transfer_received',
                'url' => '/transactions',
            ])
            ->assertJsonFragment([
                'title' => 'Lien externe',
                'url' => null,
            ])
            ->assertJsonPath('balances.available', money('0.00'))
            ->assertJsonPath('balances.bonus', money('0.00'));

        $this->actingAs($user)->getJson(route('notifications.feed', ['since' => 'demain']))
            ->assertOk()
            ->assertJsonPath('movements', []);
    }

    public function test_live_feed_requires_an_authenticated_client(): void
    {
        $this->getJson(route('notifications.feed'))->assertUnauthorized();
    }
}
