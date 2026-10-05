@extends('layouts.admin')
@section('content')
<h1 class="serif">Commissions de parrainage</h1>
<p class="muted">Les règles se modifient dans <a href="{{ route('admin.settings.edit') }}">Paramètres</a>. Une inscription sans opération réelle ne crée pas de ligne ici.</p>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Parrain</th><th>Filleul</th><th>Déclencheur</th><th>Base</th><th>Commission</th></tr></thead>
        <tbody>
        @foreach ($commissions as $commission)
            <tr>
                <td>{{ $commission->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $commission->referrer->name }}</td>
                <td>{{ $commission->referred->name }}</td>
                <td>{{ $commission->trigger->label() }}</td>
                <td>{{ money($commission->base_amount) }}</td>
                <td>{{ money($commission->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $commissions->links() }}
@endsection
