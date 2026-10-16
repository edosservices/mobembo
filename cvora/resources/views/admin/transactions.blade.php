<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Transactions</h1></x-slot>
    <ul class="space-y-2 text-sm">
        @foreach ($transactions as $transaction)
            <li class="rounded-xl bg-white px-4 py-3">{{ $transaction->reference }} · {{ $transaction->user?->email ?? 'compte supprimé' }} · {{ $transaction->plan?->name }} · {{ $transaction->status->value }} · {{ \App\Support\Money::format($transaction->amount_cents, $transaction->currency) }}</li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $transactions->links() }}</div>
</x-app-layout>
