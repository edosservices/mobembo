@extends('layouts.admin')
@section('content')
<h1 class="serif">Journal d’audit</h1>
<form method="GET" class="field" style="max-width:320px;"><label>Action</label><input name="action" value="{{ $action }}" placeholder="deposit_approved"></form>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Admin</th><th>Client</th><th>Action</th><th>Ancien</th><th>Nouveau</th><th>Delta</th><th>Motif</th></tr></thead>
        <tbody>
        @foreach ($logs as $log)
            <tr>
                <td>{{ $log->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->admin->name ?? '—' }}</td>
                <td>{{ $log->user->name ?? '—' }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->old_amount === null ? '—' : money($log->old_amount) }}</td>
                <td>{{ $log->new_amount === null ? '—' : money($log->new_amount) }}</td>
                <td>{{ $log->delta_amount === null ? '—' : money($log->delta_amount) }}</td>
                <td>{{ $log->reason }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $logs->links() }}
@endsection
