@extends('layouts.admin')
@section('content')
<h1 class="serif">Journal d’activité</h1>
<form method="GET" class="field" style="max-width:320px;"><label>Action</label><input name="action" value="{{ $action }}" placeholder="deposit_approved"></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Administrateur</th><th>Action</th><th>Utilisateur</th><th>Montant</th><th>Avant</th><th>Après</th><th>Raison</th><th>Date</th><th>IP</th></tr></thead>
        <tbody>
        @foreach ($logs as $log)
            <tr>
                <td>{{ $log->admin->name ?? '—' }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->user->name ?? '—' }}</td>
                <td>{{ $log->delta_amount === null ? '—' : money($log->delta_amount) }}</td>
                <td>{{ $log->old_amount === null ? '—' : money($log->old_amount) }}</td>
                <td>{{ $log->new_amount === null ? '—' : money($log->new_amount) }}</td>
                <td>{{ $log->reason }}</td>
                <td>{{ $log->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->ip_address ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $logs->links() }}
@endsection
