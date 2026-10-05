@extends('layouts.admin')
@section('content')
<h1 class="serif">Dépôts</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\ReviewStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Client</th><th>Montant</th><th>Moyen</th><th>Référence</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach ($deposits as $deposit)
            <tr>
                <td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $deposit->user->name }}</td>
                <td>{{ money($deposit->amount) }}</td>
                <td>{{ $deposit->method->label() }}</td>
                <td>{{ $deposit->reference }}</td>
                <td><a href="{{ route('admin.deposits.show', $deposit) }}">@include('partials.status', ['status' => $deposit->status])</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $deposits->links() }}
@endsection
