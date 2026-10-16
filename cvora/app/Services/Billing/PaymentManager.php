<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentManager
{
    public function __construct(
        private readonly SandboxPaymentGateway $sandbox,
        private readonly UnipayPaymentGateway $unipay,
    ) {}

    public function gatewayFor(string $provider): PaymentGateway
    {
        return match ($provider) {
            'sandbox' => $this->sandbox,
            'unipay' => $this->unipay,
            default => throw ValidationException::withMessages([
                'payment' => 'Prestataire de paiement inconnu.',
            ]),
        };
    }

    public function mode(): string
    {
        $mode = (string) config('cvora.payment.mode');

        return $mode === 'unipay' ? 'unipay' : 'sandbox';
    }

    public function checkout(User $user, Plan $plan, string $idempotencyKey): PaymentTransaction
    {
        if ($plan->price_cents === null) {
            throw ValidationException::withMessages([
                'plan' => 'Le tarif de cette offre n’est pas encore défini par l’administrateur.',
            ]);
        }

        if ($plan->price_cents === 0) {
            return $this->activateFree($user, $plan, $idempotencyKey);
        }

        $existing = PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing !== null) {
            if ($existing->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'payment' => 'Cette demande de paiement ne vous appartient pas.',
                ]);
            }

            return $existing;
        }

        $provider = $this->mode();

        if ($provider === 'sandbox' && app()->environment('production')) {
            throw ValidationException::withMessages([
                'payment' => 'Le bac à sable est désactivé en production. Configurez UniPay.',
            ]);
        }

        $transaction = PaymentTransaction::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'amount_cents' => $plan->price_cents,
            'currency' => $plan->currency,
            'status' => TransactionStatus::Pending,
            'provider' => $provider,
            'reference' => 'CVO-'.Str::upper(Str::ulid()),
            'idempotency_key' => $idempotencyKey,
        ]);

        $started = $this->gatewayFor($provider)->start($transaction);
        $transaction->forceFill([
            'payload' => [
                'message' => $started['message'],
                'provider_reference' => $started['provider_reference'],
                'redirect_url' => $started['redirect_url'],
            ],
        ])->save();

        return $transaction;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleNotification(string $provider, string $rawBody, ?string $signature): PaymentTransaction
    {
        if ($provider === 'sandbox' && (app()->environment('production') || $this->mode() !== 'sandbox')) {
            throw ValidationException::withMessages([
                'payment' => 'Les notifications de bac à sable sont refusées.',
            ]);
        }

        $gateway = $this->gatewayFor($provider);

        if (! $gateway->verifySignature($rawBody, $signature)) {
            SystemEvent::record('warning', 'payment', 'Signature de paiement refusée.', [
                'provider' => $provider,
            ]);

            throw ValidationException::withMessages([
                'payment' => 'La signature du paiement est invalide.',
            ]);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'payment' => 'La notification de paiement est illisible.',
            ]);
        }

        $reference = (string) ($payload['reference'] ?? '');
        $status = (string) ($payload['status'] ?? '');
        $amount = $payload['amount_cents'] ?? null;

        return DB::transaction(function () use ($reference, $status, $amount, $provider): PaymentTransaction {
            $transaction = PaymentTransaction::query()
                ->where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($transaction === null || $transaction->provider !== $provider) {
                throw ValidationException::withMessages([
                    'payment' => 'Transaction introuvable pour cette notification.',
                ]);
            }

            if ((int) $amount !== (int) $transaction->amount_cents) {
                SystemEvent::record('error', 'payment', 'Montant de notification différent de la transaction.', [
                    'reference' => $reference,
                ]);

                throw ValidationException::withMessages([
                    'payment' => 'Le montant confirmé ne correspond pas à la transaction.',
                ]);
            }

            if ($transaction->status === TransactionStatus::Paid) {
                return $transaction;
            }

            if ($status !== 'paid') {
                $transaction->forceFill(['status' => TransactionStatus::Failed])->save();

                return $transaction;
            }

            $transaction->forceFill([
                'status' => TransactionStatus::Paid,
                'paid_at' => now(),
            ])->save();

            $this->activatePlan($transaction);

            return $transaction->refresh();
        });
    }

    private function activateFree(User $user, Plan $plan, string $idempotencyKey): PaymentTransaction
    {
        return DB::transaction(function () use ($user, $plan, $idempotencyKey): PaymentTransaction {
            $existing = PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $existing;
            }

            $transaction = PaymentTransaction::query()->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'amount_cents' => 0,
                'currency' => $plan->currency,
                'status' => TransactionStatus::Paid,
                'provider' => 'gratuit',
                'reference' => 'CVO-'.Str::upper(Str::ulid()),
                'idempotency_key' => $idempotencyKey,
                'paid_at' => now(),
            ]);

            $this->activatePlan($transaction);

            return $transaction->refresh();
        });
    }

    private function activatePlan(PaymentTransaction $transaction): void
    {
        $user = $transaction->user;
        $plan = $transaction->plan;

        if ($user === null || $plan === null) {
            return;
        }

        $user->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->update([
                'status' => SubscriptionStatus::Replaced,
                'ends_at' => now(),
            ]);

        $ends = match ($plan->interval) {
            'year' => now()->addYear(),
            'month' => now()->addMonth(),
            default => null,
        };

        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => $ends,
            'provider' => $transaction->provider,
            'provider_reference' => $transaction->reference,
        ]);

        $transaction->forceFill(['subscription_id' => $subscription->id])->save();
    }
}
