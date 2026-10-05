@extends('layouts.admin')
@section('content')
<h1 class="serif">Historique des opérations</h1>
<form method="GET" class="grid-2">
    <div class="field"><label>Client</label><input name="q" value="{{ $q }}" placeholder="Nom ou téléphone"></div>
    <div class="field"><label>Type</label><select name="type"><option value="">Tous</option>@foreach ($types as $item)<option value="{{ $item->value }}" @selected($type === $item->value)>{{ $item->label() }}</option>@endforeach</select></div>
    <button class="btn-z small" type="submit">Filtrer</button>
</form>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Date</th><th>Client</th><th>Type</th><th>Montant</th><th>Statut</th><th>Description</th></tr></thead>
        <tbody>
        @foreach ($entries as $entry)
            <tr>
                <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $entry->user->name }}</td>
                <td>{{ $entry->type->label() }}</td>
                <td>{{ money($entry->amount) }}</td>
                <td>{{ $entry->status->label() }}</td>
                <td class="cell-note" title="{{ $entry->description }}">{{ $entry->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $entries->links() }}
@endsection
