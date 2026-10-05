@extends('layouts.admin')
@section('content')
<h1 class="serif">Utilisateurs</h1>
<form method="GET" class="field" style="max-width:360px;">
    <label for="q">Recherche</label>
    <input id="q" name="q" value="{{ $q }}" placeholder="Nom ou téléphone">
</form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>ID</th><th>Nom</th><th>Téléphone</th><th>Solde</th><th>Investi</th><th>Gains</th><th>Statut</th><th>Parrain</th><th>Inscription</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->name }}</td>
                <td>{{ $user->phone }}</td>
                <td>{{ money($user->wallet->available_balance ?? 0) }}</td>
                <td>{{ money($user->wallet->invested_balance ?? 0) }}</td>
                <td>{{ money($user->returns_total ?? 0) }}</td>
                <td>@include('partials.status', ['status' => $user->status])</td>
                <td>{{ $user->referrer->referral_code ?? '—' }}</td>
                <td>{{ $user->created_at->format('d/m/Y') }}</td>
                <td><a href="{{ route('admin.users.show', $user) }}">Voir</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
