@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;">
    <h1 class="serif">Projets immobiliers</h1>
    <a class="btn-z" href="{{ route('admin.projects.create') }}">Nouveau projet</a>
</div>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Catégorie</th><th>Objectif</th><th>Financé</th><th>Progression</th><th>Minimum</th><th>Rendement estimatif</th><th>Durée</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }}</td>
                <td>{{ $project->category }}</td>
                <td>{{ money($project->target_amount) }}</td>
                <td>{{ money($project->funded_amount) }}</td>
                <td>{{ str_replace('.', ',', $project->progressPercent()) }} %</td>
                <td>{{ money($project->min_investment) }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.projects.return', $project) }}" class="percent-form">
                        @csrf
                        <input name="expected_return_percent" value="{{ $project->expected_return_percent }}" inputmode="decimal" aria-label="Rendement prévu de {{ $project->name }}">
                        <button class="btn-z small" type="submit">Enregistrer</button>
                    </form>
                    <span class="muted">{{ \App\Support\ReturnEstimator::percentLabel($project->dailyReturnPercent()) }}</span>
                </td>
                <td>{{ $project->duration_days }} jours</td>
                <td>@include('partials.status', ['status' => $project->status])</td>
                <td class="admin-actions">
                    <a href="{{ route('admin.projects.edit', $project) }}">Modifier</a>
                    <a href="{{ route('admin.projects.show', $project) }}">Voir les investissements</a>
                    @if ($project->status !== \App\Enums\ProjectStatus::Suspended)
                        <form method="POST" action="{{ route('admin.projects.suspend', $project) }}">
                            @csrf
                            <input name="reason" placeholder="Motif" required minlength="5">
                            <button class="btn-z-ghost small" type="submit">Suspendre</button>
                        </form>
                    @endif
                    @if (! in_array($project->status, [\App\Enums\ProjectStatus::Closed, \App\Enums\ProjectStatus::Finished], true))
                        <form method="POST" action="{{ route('admin.projects.close', $project) }}">
                            @csrf
                            <input name="reason" placeholder="Motif de clôture" required minlength="5">
                            <button class="btn-z-ghost small" type="submit">Terminer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $projects->links() }}
@endsection
