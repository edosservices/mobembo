@extends('layouts.admin')
@section('content')
<h1 class="serif">Retraits</h1>
<form method="GET"><select name="status" onchange="this.form.submit()"><option value="">Tous</option>@foreach (\App\Enums\ReviewStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>@endforeach</select></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Client</th><th>Demandé</th><th>Frais</th><th>Net</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach ($withdrawals as $withdrawal)
            <tr>
                <td>{{ $withdrawal->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $withdrawal->user->name }}</td>
                <td>{{ money($withdrawal->amount) }}</td>
                <td>{{ money($withdrawal->fee) }}</td>
                <td>{{ money($withdrawal->net_amount) }}</td>
                <td><a href="{{ route('admin.withdrawals.show', $withdrawal) }}">@include('partials.status', ['status' => $withdrawal->status])</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $withdrawals->links() }}
@endsection
