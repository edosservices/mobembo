@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.projects.index') }}">Projets</a> · <a href="{{ route('admin.projects.edit', $project) }}">Modifier</a></p>
<h1 class="serif">{{ $project->name }}</h1>
<p>{{ $project->location }} · @include('partials.status', ['status' => $project->status]) · Financé {{ money($project->funded_amount) }} / {{ money($project->target_amount) }} · Restant {{ money($project->remainingAmount()) }}</p>
@if ($project->imageUrl())
    <p><img src="{{ $project->imageUrl() }}" alt="" style="width:min(100%, 420px);border-radius:1rem;"></p>
@endif
<form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return confirm('Supprimer ou suspendre ce projet ?');">
    @csrf
    @method('DELETE')
    <button class="btn-z-ghost" type="submit">Supprimer ou suspendre</button>
</form>
<div class="note">Estimation de revenus encore non distribués sur les investissements actifs : {{ money($estimatedOutstanding) }}. Ce n’est pas un montant dû. Une distribution doit correspondre à un produit réel du projet.</div>
<div class="grid-2" style="margin-top:1rem;">
    <form class="panel" method="POST" action="{{ route('admin.projects.distribute', $project) }}">
        @csrf
        <h2>Enregistrer une distribution réelle</h2>
        <div class="field"><label>Montant total à répartir</label><input name="total_amount" required></div>
        <div class="field"><label>Origine économique</label><textarea name="reason" required placeholder="Loyers encaissés en octobre, nets des charges."></textarea></div>
        <button class="btn-z" type="submit">Créditer au prorata</button>
    </form>
    <form class="panel" method="POST" action="{{ route('admin.projects.close', $project) }}">
        @csrf
        <h2>Clôturer et restituer le capital</h2>
        <div class="field"><label>Motif</label><input name="reason" required></div>
        <button class="btn-z danger" type="submit">Clôturer le projet</button>
    </form>
</div>
<h2>Investissements</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Client</th><th>Montant</th><th>Revenus crédités</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach ($investments as $investment)
            <tr>
                <td>{{ $investment->user->name }}</td>
                <td>{{ money($investment->amount) }}</td>
                <td>{{ money($investment->returns_credited) }}</td>
                <td>@include('partials.status', ['status' => $investment->status])</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $investments->links() }}
<h2>Distributions passées</h2>
<ul>
    @foreach ($distributions as $distribution)
        <li>{{ $distribution->distributed_at->format('d/m/Y H:i') }} — {{ money($distribution->total_amount) }} — {{ $distribution->reason }}</li>
    @endforeach
</ul>
@endsection
