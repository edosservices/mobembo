<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SystemEvent;
use Illuminate\Support\Facades\Http;

class UnipayPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'unipay';
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== ''
            && $this->merchantId() !== ''
            && $this->apiKey() !== ''
            && $this->webhookSecret() !== '';
    }

    public function start(PaymentTransaction $transaction): array
    {
        if (! $this->isConfigured()) {
            return [
                'redirect_url' => null,
                'provider_reference' => null,
                'message' => 'UniPay n’est pas configuré. Aucun paiement n’a été lancé.',
            ];
        }

        try {
            $response = Http::withToken($this->apiKey())
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->post(rtrim($this->baseUrl(), '/').'/api/v1/payments', [
                    'merchant_id' => $this->merchantId(),
                    'reference' => $transaction->reference,
                    'amount_cents' => $transaction->amount_cents,
                    'currency' => $transaction->currency,
                    'callback_url' => route('payments.webhook', ['provider' => 'unipay']),
                ]);
        } catch (\Throwable $exception) {
            SystemEvent::record('error', 'unipay', 'Le prestataire n’a pas répondu.');

            return [
                'redirect_url' => null,
                'provider_reference' => null,
                'message' => 'UniPay n’a pas répondu. Le paiement reste en attente et n’est pas considéré comme réussi.',
            ];
        }

        if (! $response->successful()) {
            SystemEvent::record('error', 'unipay', 'Réponse refusée par le prestataire.', [
                'status' => $response->status(),
            ]);

            return [
                'redirect_url' => null,
                'provider_reference' => null,
                'message' => 'UniPay a refusé l’initialisation. Aucun argent n’est marqué comme encaissé.',
            ];
        }

        return [
            'redirect_url' => $response->json('payment_url'),
            'provider_reference' => $response->json('provider_reference'),
            'message' => 'Redirection vers UniPay. L’offre sera activée après confirmation signée.',
        ];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = $this->webhookSecret();

        if ($secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    private function baseUrl(): string
    {
        return (string) config('cvora.payment.unipay.base_url');
    }

    private function merchantId(): string
    {
        return (string) config('cvora.payment.unipay.merchant_id');
    }

    private function apiKey(): string
    {
        return (string) config('cvora.payment.unipay.api_key');
    }

    private function webhookSecret(): string
    {
        return (string) config('cvora.payment.unipay.webhook_secret');
    }
}
