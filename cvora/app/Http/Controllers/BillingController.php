<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Setting;
use App\Services\Billing\PaymentManager;
use App\Services\Billing\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, QuotaService $quotas, PaymentManager $payments): View
    {
        return view('billing.index', [
            'plans' => Plan::query()->publiclyListed()->get(),
            'subscription' => $quotas->currentSubscription($request->user()),
            'credits' => $quotas->remainingCredits($request->user()),
            'transactions' => $request->user()->paymentTransactions()->latest()->limit(10)->get(),
            'mode' => $payments->mode(),
        ]);
    }

    public function checkout(Request $request, Plan $plan, PaymentManager $payments): RedirectResponse
    {
        if (! $plan->is_active || ! $plan->is_public) {
            abort(404);
        }

        if (! Setting::enabled('features.payments', true) && ($plan->price_cents ?? 1) > 0) {
            throw ValidationException::withMessages([
                'plan' => 'Les paiements sont désactivés.',
            ]);
        }

        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $transaction = $payments->checkout($request->user(), $plan, $data['idempotency_key']);

        if ($transaction->status->value === 'paid') {
            return redirect()->route('billing.index')->with('status', 'Votre offre est active.');
        }

        return redirect()->route('billing.payment', $transaction);
    }

    public function payment(Request $request, PaymentTransaction $transaction, PaymentManager $payments): View
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        return view('billing.payment', [
            'transaction' => $transaction->load('plan'),
            'mode' => $payments->mode(),
            'sandboxReady' => $payments->mode() === 'sandbox' && $payments->gatewayFor('sandbox')->isConfigured() && ! app()->environment('production'),
        ]);
    }

    public function sandbox(Request $request, PaymentTransaction $transaction, PaymentManager $payments): RedirectResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);
        abort_unless($payments->mode() === 'sandbox' && ! app()->environment('production'), 404);

        $gateway = $payments->gatewayFor('sandbox');

        if (! $gateway->isConfigured()) {
            throw ValidationException::withMessages([
                'payment' => 'Définissez SANDBOX_WEBHOOK_SECRET. Aucun paiement fictif n’est accepté sans signature.',
            ]);
        }

        $body = json_encode([
            'reference' => $transaction->reference,
            'status' => 'paid',
            'amount_cents' => $transaction->amount_cents,
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $body, (string) config('cvora.payment.sandbox_secret'));
        $payments->handleNotification('sandbox', $body, $signature);

        return redirect()->route('billing.index')->with('status', 'Notification de test acceptée. Aucun argent n’a été débité.');
    }
}
