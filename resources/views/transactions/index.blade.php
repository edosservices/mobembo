@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
<h1 style="font-size:2.1rem;">Transactions</h1>
<div class="row g-2 op-actions">
    <div class="col-12 col-sm-4"><a class="op-btn op-deposit" href="{{ route('deposits.create') }}"><i class="bi bi-plus-circle" aria-hidden="true"></i> Déposer</a></div>
    <div class="col-12 col-sm-4"><a class="op-btn op-withdraw" href="{{ route('withdrawals.create') }}"><i class="bi bi-arrow-up-circle" aria-hidden="true"></i> Retirer</a></div>
    <div class="col-12 col-sm-4"><a class="op-btn op-transfer" href="{{ route('transfers.create') }}"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> Transférer</a></div>
</div>
<nav class="chips" aria-label="Filtres">
    <a href="{{ route('transactions.index') }}" @class(['chip-link', 'is-on' => $type === ''])>Tout</a>
    <a href="{{ route('transactions.index', ['type' => 'deposit']) }}" @class(['chip-link', 'is-on' => $type === 'deposit'])>Dépôts</a>
    <a href="{{ route('transactions.index', ['type' => 'withdrawal']) }}" @class(['chip-link', 'is-on' => $type === 'withdrawal'])>Retraits</a>
    <a href="{{ route('transactions.index', ['type' => 'transfer']) }}" @class(['chip-link', 'is-on' => $type === 'transfer'])>Transferts</a>
    <a href="{{ route('transactions.index', ['type' => 'investment']) }}" @class(['chip-link', 'is-on' => $type === 'investment'])>Investissements</a>
</nav>
<details class="filter-advanced">
    <summary>Filtrer</summary>
    <form method="GET" class="field">
        <label for="type">Type</label>
        <select id="type" name="type">
            <option value="">Tous</option>
            @foreach ($types as $item)
                <option value="{{ $item->value }}" @selected($type === $item->value)>{{ $item->label() }}</option>
            @endforeach
        </select>
        <button class="btn-z small" type="submit">Appliquer</button>
    </form>
</details>
<div class="txn-list">
    @forelse ($entries as $entry)
        <article class="txn">
            <strong @class(['pos' => \App\Support\Money::cmp($entry->amount, '0') > 0])>{{ \App\Support\TransactionPresenter::signedAmount($entry) }}</strong>
            <span>{{ $entry->type->label() }}</span>
            <span class="desc">{{ $entry->description }}</span>
            @if ($entry->reference)
                <span class="txn-ref">{{ $entry->reference }}</span>
            @endif
            <time>{{ \App\Support\TransactionPresenter::date($entry) }}</time>
            <em>{{ \App\Support\TransactionPresenter::clientStatus($entry) }}</em>
        </article>
    @empty
        <p class="muted">Aucune opération pour le moment.</p>
    @endforelse
</div>
{{ $entries->links() }}
@endsection
