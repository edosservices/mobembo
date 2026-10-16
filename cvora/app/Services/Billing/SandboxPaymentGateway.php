<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Models\PaymentTransaction;

class SandboxPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'sandbox';
    }

    public function isConfigured(): bool
    {
        return is_string(config('cvora.payment.sandbox_secret'))
            && config('cvora.payment.sandbox_secret') !== '';
    }

    public function start(PaymentTransaction $transaction): array
    {
        return [
            'redirect_url' => null,
            'provider_reference' => null,
            'message' => 'Bac à sable : aucun argent n’est débité. Le paiement n’est confirmé que par une notification signée.',
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('cvora.payment.sandbox_secret');

        if (! is_string($secret) || $secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signature);
    }
}
