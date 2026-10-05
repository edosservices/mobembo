@extends('layouts.admin')
@section('title', 'Dépôts')
@section('content')
<h1 class="serif">Dépôts</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\ReviewStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Utilisateur</th><th>Méthode</th><th>Montant</th><th>Référence</th><th>Preuve</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($deposits as $deposit)
            <tr>
                <td>{{ $deposit->user->name }}</td>
                <td>{{ $deposit->method->label() }}</td>
                <td class="text-nowrap">{{ money($deposit->amount) }}</td>
                <td>{{ $deposit->reference }}</td>
                <td><a href="{{ route('admin.deposits.proof', $deposit) }}">Voir</a></td>
                <td class="text-nowrap">{{ $deposit->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>@include('partials.status', ['status' => $deposit->status])</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-dark rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.deposits.show', $deposit) }}">Voir détails</a>
                            @if ($deposit->status->value === 'pending')
                                <form method="POST" action="{{ route('admin.deposits.approve', $deposit) }}">@csrf<button class="dropdown-item" type="submit">Approuver</button></form>
                                <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}">@csrf<input name="reason" placeholder="Motif" required minlength="5"><button class="btn-z-ghost small" type="submit">Rejeter</button></form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">Aucun dépôt.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $deposits->links() }}
@endsection
