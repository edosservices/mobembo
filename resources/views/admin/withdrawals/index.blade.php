@extends('layouts.admin')
@section('title', 'Retraits')
@section('content')
<h1 class="serif">Retraits</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\ReviewStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Utilisateur</th><th>Demandé</th><th>Frais</th><th>Net</th><th>Méthode</th><th>Numéro</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($withdrawals as $withdrawal)
            <tr>
                <td>{{ $withdrawal->user->name }}</td>
                <td>{{ money($withdrawal->amount) }}</td>
                <td>{{ money($withdrawal->fee) }}</td>
                <td>{{ money($withdrawal->net_amount) }}</td>
                <td>{{ $withdrawal->method->label() }}</td>
                <td>{{ $withdrawal->phone }}</td>
                <td>{{ $withdrawal->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>@include('partials.status', ['status' => $withdrawal->status])</td>
                <td class="admin-actions">
                    <a href="{{ route('admin.withdrawals.show', $withdrawal) }}">Voir</a>
                    @if ($withdrawal->status->value === 'pending')
                        <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">@csrf<button class="btn-z small" type="submit">Approuver</button></form>
                        <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}">@csrf<input name="reason" placeholder="Motif" required><button class="btn-z-ghost small" type="submit">Rejeter</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9">Aucun retrait.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $withdrawals->links() }}
@endsection
