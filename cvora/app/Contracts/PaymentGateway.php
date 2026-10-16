<?php

namespace App\Contracts;

use App\Models\PaymentTransaction;

interface PaymentGateway
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @return array{redirect_url: ?string, provider_reference: ?string, message: string}
     */
    public function start(PaymentTransaction $transaction): array;

    public function verifySignature(string $rawBody, ?string $signature): bool;
}
