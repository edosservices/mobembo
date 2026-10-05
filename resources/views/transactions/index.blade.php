@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
<h1 style="font-size:2.1rem;">Transactions</h1>
<nav class="chips" aria-label="Filtres">
    <a href="{{ route('transactions.index') }}" @class(['chip-link', 'is-on' => $type === ''])>Tout</a>
    <a href="{{ route('transactions.index', ['type' => 'deposit']) }}" @class(['chip-link', 'is-on' => $type === 'deposit'])>Dépôts</a>
    <a href="{{ route('transactions.index', ['type' => 'withdrawal']) }}" @class(['chip-link', 'is-on' => $type === 'withdrawal'])>Retraits</a>
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
            <time>{{ \App\Support\TransactionPresenter::date($entry) }}</time>
            <em>{{ \App\Support\TransactionPresenter::clientStatus($entry) }}</em>
        </article>
    @empty
        <p class="muted">Aucune opération pour le moment.</p>
    @endforelse
</div>
{{ $entries->links() }}
@endsection
