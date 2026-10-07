@extends('layouts.admin')
@section('title', 'Retraits')
@section('content')
<h1 class="serif">Retraits</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\ReviewStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Utilisateur</th><th>Demandé</th><th>Frais</th><th>Net</th><th>Méthode</th><th>Numéro</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($withdrawals as $withdrawal)
            <tr>
                <td>{{ $withdrawal->user->name }}</td>
                <td class="text-nowrap">{{ money($withdrawal->amount) }}</td>
                <td class="text-nowrap">{{ money($withdrawal->fee) }}</td>
                <td class="text-nowrap">{{ money($withdrawal->net_amount) }}</td>
                <td class="text-nowrap">{{ $withdrawal->method->label() }}</td>
                <td class="text-nowrap">{{ $withdrawal->phone }}</td>
                <td class="text-nowrap">{{ $withdrawal->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>@include('partials.status', ['status' => $withdrawal->status])</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.withdrawals.show', $withdrawal) }}">Voir</a>
                            @if (in_array($withdrawal->status->value, ['pending', 'processing'], true))
                                @if ($withdrawal->status->value === 'pending')
                                    <form method="POST" action="{{ route('admin.withdrawals.process', $withdrawal) }}">@csrf<button class="dropdown-item" type="submit">Passer en traitement</button></form>
                                @endif
                                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">@csrf<button class="dropdown-item" type="submit">Approuver le paiement</button></form>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}">@csrf<input name="reason" placeholder="Motif" required minlength="5"><button class="btn-z-ghost small" type="submit">Refuser</button></form>
                            @endif
                        </div>
                    </div>
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
