@extends('layouts.admin')
@section('title', $heading)
@section('content')
<h1 class="serif">{{ $heading }}</h1>
@if ($heading === 'Support clients')
    <p class="muted">Ouvrir un compte permet de le consulter. Les opérations financières restent dans l’administration.</p>
@endif
<form method="GET" class="field" style="max-width:360px;">
    <label for="q">Recherche</label>
    <input id="q" name="q" value="{{ $q }}" placeholder="Nom ou téléphone">
</form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Téléphone</th><th>Statut</th><th>Solde</th><th>Investi</th><th>Équipe</th><th>KYC</th><th>Inscription</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->phone }}</td>
                <td>@include('partials.status', ['status' => $user->status])</td>
                <td>{{ money($user->wallet->available_balance ?? 0) }}</td>
                <td>{{ money($user->wallet->invested_balance ?? 0) }}</td>
                <td>{{ $user->team_count }}</td>
                <td>{{ $user->kyc_status->label() }}</td>
                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                <td class="admin-actions">
                    <a href="{{ route('admin.users.show', $user) }}">Voir</a>
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">@csrf<button class="btn-z-ghost small" type="submit">Ouvrir le compte</button></form>
                    @if ($user->status->value === 'active')
                        <form method="POST" action="{{ route('admin.users.block', $user) }}">@csrf<input name="reason" placeholder="Motif" required><button class="btn-z-ghost small" type="submit">Bloquer</button></form>
                    @else
                        <form method="POST" action="{{ route('admin.users.unblock', $user) }}">@csrf<input name="reason" placeholder="Motif" required><button class="btn-z-ghost small" type="submit">Débloquer</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
