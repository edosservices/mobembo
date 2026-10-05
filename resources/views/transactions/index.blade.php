@extends('layouts.app')
@section('title', 'Transactions')
@section('content')
<h1 style="font-size:2.3rem;">Transactions</h1>
<form method="GET" class="field" style="max-width:280px;">
    <label for="type">Type</label>
    <select id="type" name="type" onchange="this.form.submit()">
        <option value="">Tous</option>
        @foreach ($types as $item)
            <option value="{{ $item->value }}" @selected($type === $item->value)>{{ $item->label() }}</option>
        @endforeach
    </select>
</form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Statut</th><th>Description</th><th>Solde après</th></tr></thead>
        <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td>{{ $entry->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>@include('partials.status', ['status' => $entry->type])</td>
                <td>{{ money($entry->amount) }}</td>
                <td>@include('partials.status', ['status' => $entry->status])</td>
                <td>{{ $entry->description }} @if($entry->reference)<div class="muted">{{ $entry->reference }}</div>@endif</td>
                <td>{{ $entry->balance_after === null ? '—' : money($entry->balance_after) }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Aucune écriture. Le solde se reconstitue à partir de ce registre.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $entries->links() }}
@endsection
