@extends('layouts.admin')
@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
    <h1 class="serif mb-0">Projets immobiliers</h1>
    <a class="btn-z" href="{{ route('admin.projects.create') }}">Nouveau projet</a>
</div>
<div class="table-responsive panel projects-board">
    <table class="table table-sm table-hover align-middle mb-0 table-fit">
        <colgroup>
            <col style="width:13%"><col style="width:12%"><col style="width:9%"><col style="width:9%"><col style="width:7%">
            <col style="width:8%"><col style="width:12%"><col style="width:8%"><col style="width:12%"><col style="width:10%">
        </colgroup>
        <thead><tr><th>Nom</th><th>Catégorie</th><th>Objectif</th><th>Financé</th><th>Progression</th><th>Minimum</th><th>Rendement estimatif</th><th>Durée</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @foreach ($projects as $project)
            <tr>
                <td class="clip" title="{{ $project->name }}">{{ $project->name }}</td>
                <td class="clip" title="{{ $project->category }}">{{ $project->category }}</td>
                <td class="text-nowrap">{{ money($project->target_amount) }}</td>
                <td class="text-nowrap">{{ money($project->funded_amount) }}</td>
                <td class="text-nowrap">{{ str_replace('.', ',', $project->progressPercent()) }} %</td>
                <td class="text-nowrap">{{ money($project->min_investment) }}</td>
                <td class="yield-cell">
                    <strong>{{ str_replace('.', ',', rtrim(rtrim((string) $project->expected_return_percent, '0'), '.')) }} %</strong>
                    <span class="muted">{{ \App\Support\ReturnEstimator::percentLabel($project->dailyReturnPercent()) }}</span>
                </td>
                <td class="text-nowrap">{{ $project->duration_days }} jours</td>
                <td>@include('partials.status', ['status' => $project->status])</td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-dark rounded-pill" href="{{ route('admin.projects.edit', $project) }}">Modifier</a>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-outline-dark rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">Plus</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{ route('admin.projects.edit', $project) }}">Modifier le plan</a>
                            <a class="dropdown-item" href="{{ route('admin.projects.show', $project) }}">Voir les investissements</a>
                            @if ($project->status !== \App\Enums\ProjectStatus::Suspended)
                                <form method="POST" action="{{ route('admin.projects.suspend', $project) }}">
                                    @csrf
                                    <input name="reason" placeholder="Motif" required minlength="5" aria-label="Motif de suspension de {{ $project->name }}">
                                    <button class="btn-z-ghost small" type="submit">Suspendre</button>
                                </form>
                            @endif
                            @if (! in_array($project->status, [\App\Enums\ProjectStatus::Closed, \App\Enums\ProjectStatus::Finished], true))
                                <form method="POST" action="{{ route('admin.projects.close', $project) }}">
                                    @csrf
                                    <input name="reason" placeholder="Motif de clôture" required minlength="5" aria-label="Motif de clôture de {{ $project->name }}">
                                    <button class="btn-z-ghost small" type="submit">Terminer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $projects->links() }}
@endsection
