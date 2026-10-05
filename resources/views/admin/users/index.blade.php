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
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Nom</th><th>Téléphone</th><th>Statut</th><th>Solde</th><th>Investi</th><th>Équipe</th><th>KYC</th><th>Inscription</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->phone }}</td>
                <td>@include('partials.status', ['status' => $user->status])</td>
                <td class="text-nowrap">{{ money($user->wallet->available_balance ?? 0) }}</td>
                <td class="text-nowrap">{{ money($user->wallet->invested_balance ?? 0) }}</td>
                <td>{{ $user->team_count }}</td>
                <td class="text-nowrap">{{ $user->kyc_status->label() }}</td>
                <td class="text-nowrap">{{ $user->created_at->format('d/m/Y') }}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.users.show', $user) }}">Voir</a>
                            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">@csrf<button class="dropdown-item" type="submit">Ouvrir le compte</button></form>
                            @if ($user->status->value === 'active')
                                <form method="POST" action="{{ route('admin.users.block', $user) }}">@csrf<input name="reason" placeholder="Motif" required minlength="5"><button class="btn-z-ghost small" type="submit">Bloquer</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.users.unblock', $user) }}">@csrf<input name="reason" placeholder="Motif" required minlength="5"><button class="btn-z-ghost small" type="submit">Débloquer</button></form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
