<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\AvailableComposition;
use App\Services\TransferService;
use App\Services\WalletService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferAndBonusBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reserved_withdrawal_reduces_the_remaining_bonus_once_until_it_is_paid_or_restored(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $this->bonus($user, '20.00');

        $this->actingAs($user)->get(route('withdrawals.create', ['amount' => '10']))
            ->assertOk()
            ->assertSee('1,20 $')
            ->assertSee('8,80 $')
            ->assertSee('Retrait demandé')
            ->assertSee('En traitement')
            ->assertSee('Retrait effectué');

        $this->actingAs($user)->post(route('withdrawals.store'), $this->withdrawal('10'))->assertRedirect();

        $withdrawal = $user->withdrawals()->firstOrFail();
        $this->assertSame('1.20', Money::of($withdrawal->fee));
        $this->assertSame('8.80', Money::of($withdrawal->net_amount));
        $this->assertSame('10.00', Money::of($user->wallet()->first()->available_balance));
        $this->assertSame('10.00', Money::of($user->wallet()->first()->locked_balance));
        $this->assertSame('10.00', $this->bonusOf($user));
        $wallet = $user->wallet()->first();
        $this->assertSame('20.00', Money::add($wallet->available_balance, $wallet->locked_balance));

        $dashboard = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $dashboard->assertSee('col-6', false);
        $dashboard->assertSee('data-balance="bonus"', false);
        $this->assertSame(1, preg_match('/data-balance="bonus"(.*?)data-balance="/s', $dashboard->getContent(), $bonusCard));
        $this->assertStringContainsString('10,00 $', $bonusCard[1]);
        $this->assertStringNotContainsString('20,00 $', $bonusCard[1]);

        $this->actingAs($admin)->post(route('admin.withdrawals.process', $withdrawal))->assertRedirect();
        $this->assertSame('10.00', Money::of($user->wallet()->first()->fresh()->available_balance));
        $this->assertSame('10.00', Money::of($user->wallet()->first()->locked_balance));
        $this->assertSame('10.00', $this->bonusOf($user));
        $this->assertSame(1, $user->withdrawals()->count());

        $this->actingAs($admin)->post(route('admin.withdrawals.approve', $withdrawal))->assertRedirect();
        $user->wallet()->first()->refresh();
        $this->assertSame('10.00', Money::of($user->wallet()->first()->available_balance));
        $this->assertSame('0.00', Money::of($user->wallet()->first()->locked_balance));
        $this->assertSame('10.00', $this->bonusOf($user));
        $this->assertSame('approved', $withdrawal->refresh()->status->value);

        $other = User::factory()->create();
        $this->bonus($other, '20.00');
        $this->actingAs($other)->post(route('withdrawals.store'), $this->withdrawal('10'))->assertRedirect();
        $refused = $other->withdrawals()->firstOrFail();
        $this->assertSame('10.00', $this->bonusOf($other));
        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $refused), [
            'reason' => 'Compte incorrect',
        ])->assertRedirect();
        $this->assertSame('rejected', $refused->refresh()->status->value);
        $this->assertSame('20.00', Money::of($other->wallet()->first()->fresh()->available_balance));
        $this->assertSame('0.00', Money::of($other->wallet()->first()->locked_balance));
        $this->assertSame('20.00', $this->bonusOf($other));
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_transfer_debits_the_sender_by_the_amount_plus_two_percent_and_credits_the_recipient(): void
    {
        $sender = User::factory()->create(['name' => 'Edouard Kabila']);
        $recipient = User::factory()->create(['name' => 'Jean Dupont', 'phone' => '+243810009991']);
        $this->bonus($sender, '150.00');

        $this->actingAs($sender)->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Déposer')
            ->assertSee('Retirer')
            ->assertSee('Transférer');

        $this->actingAs($sender)->post(route('transfers.preview'), [
            'name' => 'jean dupont',
            'phone' => '0810009991',
            'amount' => '100',
        ])->assertOk()
            ->assertSee('Jean Dupont')
            ->assertSee('+243810009991')
            ->assertSee('100,00 $')
            ->assertSee('2,00 $')
            ->assertSee('102,00 $');

        $key = (string) Str::uuid();
        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Jean Dupont',
            'phone' => '+243810009991',
            'amount' => '100',
            'idempotency_key' => $key,
        ])->assertRedirect(route('transactions.index', ['type' => 'transfer']));

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Jean Dupont',
            'phone' => '+243810009991',
            'amount' => '100',
            'idempotency_key' => $key,
        ])->assertRedirect();

        $sender->wallet()->first()->refresh();
        $recipient->wallet()->first()->refresh();
        $this->assertSame('48.00', Money::of($sender->wallet()->first()->available_balance));
        $this->assertSame('100.00', Money::of($recipient->wallet()->first()->available_balance));
        $this->assertSame('0.00', Money::of($sender->wallet()->first()->invested_balance));
        $this->assertSame('48.00', $this->bonusOf($sender));
        $this->assertSame('0.00', app(AvailableComposition::class)->remaining($recipient)['bonus']);

        $reference = LedgerEntry::query()->where('user_id', $sender->id)->where('type', LedgerType::TransferOut)->value('reference');
        $this->assertMatchesRegularExpression('/^TRF-\d{6}$/', (string) $reference);
        $this->assertSame(1, LedgerEntry::query()->where('reference', $reference)->where('type', LedgerType::TransferOut)->count());
        $this->assertSame(1, LedgerEntry::query()->where('reference', $reference)->where('type', LedgerType::TransferFee)->count());
        $this->assertSame(1, LedgerEntry::query()->where('reference', $reference)->where('type', LedgerType::TransferIn)->count());
        $this->assertSame('-100.00', Money::of(LedgerEntry::query()->where('type', LedgerType::TransferOut)->value('amount')));
        $this->assertSame('-2.00', Money::of(LedgerEntry::query()->where('type', LedgerType::TransferFee)->value('amount')));
        $this->assertSame('100.00', Money::of(LedgerEntry::query()->where('type', LedgerType::TransferIn)->value('amount')));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $sender->id,
            'data->kind' => 'transfer_sent',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'data->kind' => 'transfer_received',
        ]);

        $this->actingAs($sender)->get(route('transactions.index', ['type' => 'transfer']))
            ->assertOk()
            ->assertSee('Transfert envoyé')
            ->assertSee('Frais de transfert')
            ->assertSee($reference);
        $this->actingAs($recipient)->get(route('transactions.index', ['type' => 'transfer']))
            ->assertOk()
            ->assertSee('Transfert reçu')
            ->assertSee($reference);

        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_a_transfer_is_refused_without_debit_when_the_recipient_cannot_receive_it(): void
    {
        $sender = User::factory()->create(['name' => 'Amina Sender', 'phone' => '+243810008001']);
        $known = User::factory()->create(['name' => 'Claire Bemba', 'phone' => '+243810008002']);
        $blocked = User::factory()->create([
            'name' => 'Paul Bloque',
            'phone' => '+243810008003',
            'status' => AccountStatus::Blocked,
        ]);
        $this->bonus($sender, '40.00');

        $before = Money::of($sender->wallet()->first()->fresh()->available_balance);

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Inconnu',
            'phone' => '0810008009',
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Mauvais Nom',
            'phone' => '0810008002',
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Paul Bloque',
            'phone' => '0810008003',
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Amina Sender',
            'phone' => '0810008001',
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Claire Bemba',
            'phone' => '0810008002',
            'amount' => '100',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');

        $this->assertSame($before, Money::of($sender->wallet()->first()->fresh()->available_balance));
        $this->assertSame('0.00', Money::of($known->wallet()->first()->available_balance ?? 0));
        $this->assertSame('0.00', Money::of($blocked->wallet()->first()->available_balance ?? 0));
        $this->assertSame(0, LedgerEntry::query()->whereIn('type', [
            LedgerType::TransferOut,
            LedgerType::TransferIn,
            LedgerType::TransferFee,
        ])->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    public function test_invested_capital_cannot_be_transferred_and_the_service_replay_does_not_double_the_movement(): void
    {
        $sender = User::factory()->create(['name' => 'Mado Invest']);
        $recipient = User::factory()->create(['name' => 'Joel Reçoit', 'phone' => '+243810007771']);
        $wallets = app(WalletService::class);
        $wallets->credit($sender, '100.00', LedgerType::AdminAdjustment, [
            'description' => 'Dépôt de test',
            'idempotency_key' => 'credit-'.Str::uuid(),
        ]);
        $wallets->invest($sender, '100.00', [
            'description' => 'Capital investi',
            'idempotency_key' => 'invest-'.Str::uuid(),
        ]);

        $this->actingAs($sender)->post(route('transfers.store'), [
            'name' => 'Joel Reçoit',
            'phone' => '0810007771',
            'amount' => '10',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHas('error');
        $this->assertSame('0.00', Money::of($sender->wallet()->first()->fresh()->available_balance));
        $this->assertSame('100.00', Money::of($sender->wallet()->first()->invested_balance));

        $wallets->credit($sender, '30.00', LedgerType::Bonus, [
            'description' => 'Bonus',
            'idempotency_key' => 'bonus-'.Str::uuid(),
        ]);
        $key = (string) Str::uuid();
        $service = app(TransferService::class);
        $first = $service->send($sender, 'Joel Reçoit', '0810007771', '10', $key);
        $second = $service->send($sender, 'Joel Reçoit', '0810007771', '10', $key);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('19.80', Money::of($sender->wallet()->first()->fresh()->available_balance));
        $this->assertSame('100.00', Money::of($sender->wallet()->first()->invested_balance));
        $this->assertSame('10.00', Money::of($recipient->wallet()->first()->fresh()->available_balance));
        $this->assertSame(1, LedgerEntry::query()->where('type', LedgerType::TransferOut)->count());
        $this->assertSame([], app(WalletService::class)->findDrift());
    }

    private function bonus(User $user, string $amount): void
    {
        app(WalletService::class)->credit($user, $amount, LedgerType::Bonus, [
            'description' => 'Bonus de test',
            'idempotency_key' => 'bonus-'.Str::uuid(),
        ]);
    }

    private function bonusOf(User $user): string
    {
        return app(AvailableComposition::class)->remaining($user)['bonus'];
    }

    private function withdrawal(string $amount): array
    {
        return [
            'amount' => $amount,
            'method' => 'airtel_money',
            'phone' => '0891234567',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
