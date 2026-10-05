@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;">
    <h1 class="serif">Projets</h1>
    <a class="btn-z" href="{{ route('admin.projects.create') }}">Nouveau projet</a>
</div>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Lieu</th><th>Objectif</th><th>Financé</th><th>Restant</th><th>Rendement du projet</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }}</td>
                <td>{{ $project->location }}</td>
                <td>{{ money($project->target_amount) }}</td>
                <td>{{ money($project->funded_amount) }}</td>
                <td>{{ money($project->remainingAmount()) }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.projects.return', $project) }}" class="percent-form">
                        @csrf
                        <input name="expected_return_percent" value="{{ $project->expected_return_percent }}" inputmode="decimal" aria-label="Rendement prévu de {{ $project->name }}">
                        <button class="btn-z small" type="submit">Enregistrer</button>
                    </form>
                    <span class="muted">{{ \App\Support\ReturnEstimator::percentLabel($project->dailyReturnPercent()) }}</span>
                </td>
                <td>@include('partials.status', ['status' => $project->status])</td>
                <td><a href="{{ route('admin.projects.edit', $project) }}">Modifier le projet</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $projects->links() }}
@endsection
