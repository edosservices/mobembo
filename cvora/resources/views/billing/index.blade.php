<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Abonnement</h1></x-slot>
    <p class="text-sm text-slate-600">Offre actuelle : {{ $subscription?->plan?->name ?? 'Aucune' }}. Crédits IA restants : {{ $credits }}. Mode de paiement : {{ $mode === 'sandbox' ? 'bac à sable, aucun argent n’est débité' : 'UniPay' }}.</p>
    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        @foreach ($plans as $plan)
            <article class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="font-display text-xl font-semibold">{{ $plan->name }}</h2>
                <p class="mt-1 text-2xl font-semibold text-brand">{{ \App\Support\Money::format($plan->price_cents, $plan->currency) }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ $plan->description }}</p>
                <form method="POST" action="{{ route('billing.checkout', $plan) }}" class="mt-4">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Choisir</button>
                </form>
            </article>
        @endforeach
    </div>
    @if ($errors->any())
        <ul class="mt-4 text-sm text-red-600">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <h2 class="mt-8 font-display text-lg font-semibold">Transactions</h2>
    <ul class="mt-2 space-y-2 text-sm">
        @forelse ($transactions as $transaction)
            <li class="rounded-xl bg-white px-4 py-3">{{ $transaction->reference }} · {{ $transaction->status->value }} · {{ \App\Support\Money::format($transaction->amount_cents, $transaction->currency) }}</li>
        @empty
            <li>Aucune transaction.</li>
        @endforelse
    </ul>
</x-app-layout>
