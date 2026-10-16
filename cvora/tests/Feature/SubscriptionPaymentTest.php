<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_signed_sandbox_notification_activates_the_plan_once(): void
    {
        $user = $this->member();
        $plan = Plan::query()->where('slug', 'premium')->firstOrFail();
        $plan->update(['price_cents' => 1500000]);

        $this->actingAs($user)->post(route('billing.checkout', $plan), [
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $transaction = PaymentTransaction::query()->firstOrFail();
        $this->assertSame(TransactionStatus::Pending, $transaction->status);

        $this->notify($transaction->reference, 'paid', $transaction->amount_cents)->assertOk();
        $this->notify($transaction->reference, 'paid', $transaction->amount_cents)->assertOk();

        $this->assertSame(TransactionStatus::Paid, $transaction->fresh()->status);
        $this->assertSame(1, $user->subscriptions()->where('status', SubscriptionStatus::Active)->count());
        $this->assertSame($plan->id, $user->fresh()->currentSubscription->plan_id);
    }

    public function test_an_invalid_signature_is_rejected(): void
    {
        $user = $this->member();
        $plan = Plan::query()->where('slug', 'premium')->firstOrFail();
        $plan->update(['price_cents' => 1000]);
        $this->actingAs($user)->post(route('billing.checkout', $plan), [
            'idempotency_key' => (string) Str::uuid(),
        ]);
        $transaction = PaymentTransaction::query()->firstOrFail();

        $this->call('POST', route('payments.webhook', 'sandbox'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYMENT_SIGNATURE' => 'non',
        ], json_encode([
            'reference' => $transaction->reference,
            'status' => 'paid',
            'amount_cents' => $transaction->amount_cents,
        ]))->assertStatus(401);

        $this->assertSame(TransactionStatus::Pending, $transaction->fresh()->status);
    }

    private function notify(string $reference, string $status, int $amount): TestResponse
    {
        $body = json_encode([
            'reference' => $reference,
            'status' => $status,
            'amount_cents' => $amount,
        ], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'test-sandbox-secret');

        return $this->call('POST', route('payments.webhook', 'sandbox'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYMENT_SIGNATURE' => $signature,
        ], $body);
    }
}
