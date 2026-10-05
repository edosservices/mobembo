@extends('layouts.admin')
@section('title', 'Bonus et ajustements')
@section('content')
<h1 class="serif">Bonus et ajustements</h1>
<p class="muted">Un bonus ou un ajustement se saisit depuis la fiche du client. Cette page ne fait que les afficher.</p>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Client</th><th>Type</th><th>Montant</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @forelse ($entries as $entry)
            <tr>
                <td>{{ $entry->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ $entry->user->name ?? '—' }}</td>
                <td>{{ $entry->type->label() }}</td>
                <td>{{ money($entry->amount) }}</td>
                <td>{{ $entry->status->label() }}</td>
                <td>@if ($entry->user && ! $entry->user->isAdmin())<a href="{{ route('admin.users.show', $entry->user) }}">Voir</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6">Aucune opération.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $entries->links() }}
@endsection
