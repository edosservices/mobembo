<?php

namespace App\Http\Controllers;

use App\Services\Billing\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, PaymentManager $payments): JsonResponse
    {
        try {
            $transaction = $payments->handleNotification(
                $provider,
                $request->getContent(),
                $request->header('X-Payment-Signature'),
            );
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Notification refusée.';
            $status = str_contains(mb_strtolower($message), 'signature') ? 401 : 422;

            return response()->json(['message' => $message], $status);
        }

        return response()->json([
            'status' => $transaction->status->value,
            'reference' => $transaction->reference,
        ]);
    }
}
