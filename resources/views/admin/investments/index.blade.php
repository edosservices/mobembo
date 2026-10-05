@extends('layouts.admin')
@section('content')
<h1 class="serif">Investissements</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\InvestmentStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Client</th><th>Projet</th><th>Montant</th><th>Revenus</th><th>Statut</th><th>Action</th></tr></thead>
        <tbody>
        @foreach ($investments as $investment)
            <tr>
                <td>{{ $investment->user->name }}</td>
                <td>{{ $investment->project->name }}</td>
                <td>{{ money($investment->amount) }}</td>
                <td>{{ money($investment->returns_credited) }}</td>
                <td>@include('partials.status', ['status' => $investment->status])</td>
                <td>
                    @if (! in_array($investment->status->value, ['completed', 'cancelled']))
                        <form method="POST" action="{{ route('admin.investments.update', $investment) }}">
                            @csrf
                            <select name="status">
                                <option value="active">Actif</option>
                                <option value="suspended">Suspendu</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                            <input name="reason" placeholder="Motif" required>
                            <button class="btn-z small" type="submit">Appliquer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $investments->links() }}
@endsection
