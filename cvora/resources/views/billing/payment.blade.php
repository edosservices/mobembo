<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Paiement {{ $transaction->reference }}</h1></x-slot>
    <div class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        <p class="text-sm">Offre : {{ $transaction->plan?->name }}. Montant : {{ \App\Support\Money::format($transaction->amount_cents, $transaction->currency) }}. Statut : {{ $transaction->status->value }}.</p>
        <p class="mt-3 text-sm text-slate-600">{{ $transaction->payload['message'] ?? '' }}</p>
        @if (! empty($transaction->payload['redirect_url']))
            <a class="mt-4 inline-flex rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white" href="{{ $transaction->payload['redirect_url'] }}">Continuer vers le prestataire</a>
        @endif
        @if ($sandboxReady && $transaction->status->value === 'pending')
            <form method="POST" action="{{ route('billing.sandbox', $transaction) }}" class="mt-4 rounded-xl bg-amber-50 p-4">
                @csrf
                <p class="text-sm">Bac à sable. Ce bouton envoie une notification signée. Aucun argent n’est débité et ce n’est pas un paiement réel.</p>
                <button class="mt-3 rounded-full bg-ink px-4 py-2 text-sm font-semibold text-white">Envoyer la notification de test</button>
            </form>
        @elseif ($mode === 'sandbox' && ! $sandboxReady)
            <p class="mt-4 text-sm">Le bac à sable n’acceptera une confirmation que si SANDBOX_WEBHOOK_SECRET est défini, hors production.</p>
        @endif
    </div>
</x-app-layout>
