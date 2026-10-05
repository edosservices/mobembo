@extends('layouts.admin')
@section('title', 'Parrainage')
@section('content')
<h1 class="serif">Parrainage</h1>
<p class="muted">La commission est le taux de la plateforme. Elle ne change pas selon le niveau. Les seuils se règlent dans <a href="{{ route('admin.settings.edit') }}">Paramètres</a>.</p>
<div class="dash-stats">
    <div class="stat"><span>Parrains</span><strong>{{ $sponsors }}</strong></div>
    <div class="stat"><span>Filleuls</span><strong>{{ $members }}</strong></div>
    <div class="stat"><span>Commissions distribuées</span><strong>{{ money($paid) }}</strong></div>
    <div class="stat"><span>Bonus distribués</span><strong>{{ money($bonus) }}</strong></div>
    <div class="stat"><span>Taux actuel</span><strong>{{ str_replace('.', ',', bcadd((string) $rate, '0', 2)) }} %</strong></div>
</div>
<h2>Niveaux configurés</h2>
<div class="dash-stats">
    @foreach ($levels as $level)
        <article class="panel">
            <strong>{{ $level['name'] }}</strong>
            <p>Membres actifs requis : {{ $level['min_active'] }}</p>
            <p>Commission : {{ str_replace('.', ',', bcadd((string) $rate, '0', 2)) }} %</p>
            <ul>
                @foreach ($benefits[$level['key']] ?? [] as $benefit)
                    <li>{{ $benefit }}</li>
                @endforeach
            </ul>
        </article>
    @endforeach
</div>
<h2>Parrains les plus actifs</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Commissions</th></tr></thead>
        <tbody>
        @forelse ($top as $user)
            <tr><td>{{ $user->name }}</td><td>{{ money($user->commissions_total ?? 0) }}</td></tr>
        @empty
            <tr><td colspan="2">Aucune commission enregistrée.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<h2>Commissions</h2>
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
