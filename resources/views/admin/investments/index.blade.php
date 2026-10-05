@extends('layouts.admin')
@section('content')
<h1 class="serif">Investissements</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\InvestmentStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Client</th><th>Projet</th><th>Montant</th><th>Revenus</th><th>Statut</th><th>Action</th></tr></thead>
        <tbody>
        @foreach ($investments as $investment)
            <tr>
                <td>{{ $investment->user->name }}</td>
                <td>{{ $investment->project->name }}</td>
                <td class="text-nowrap">{{ money($investment->amount) }}</td>
                <td class="text-nowrap">{{ money($investment->returns_credited) }}</td>
                <td>@include('partials.status', ['status' => $investment->status])</td>
                <td>
                    @if (! in_array($investment->status->value, ['completed', 'cancelled']))
                        <div class="dropdown">
                            <button class="btn btn-sm btn-dark rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">Actions</button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <form method="POST" action="{{ route('admin.investments.update', $investment) }}">
                                    @csrf
                                    <select name="status" aria-label="Statut de l'investissement">
                                        <option value="active">Actif</option>
                                        <option value="suspended">Suspendu</option>
                                        <option value="cancelled">Annulé</option>
                                    </select>
                                    <input name="reason" placeholder="Motif" required minlength="5">
                                    <button class="btn-z small" type="submit">Appliquer</button>
                                </form>
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $investments->links() }}
@endsection
