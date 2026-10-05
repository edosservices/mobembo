@extends('layouts.admin')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;">
    <h1 class="serif">Projets</h1>
    <a class="btn-z" href="{{ route('admin.projects.create') }}">Nouveau projet</a>
</div>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Lieu</th><th>Objectif</th><th>Financé</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }} @if($project->is_demo)<span class="badge-z tone-warn">Démo</span>@endif</td>
                <td>{{ $project->location }}</td>
                <td>{{ money($project->target_amount) }}</td>
                <td>{{ money($project->funded_amount) }}</td>
                <td>@include('partials.status', ['status' => $project->status])</td>
                <td><a href="{{ route('admin.projects.show', $project) }}">Ouvrir</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $projects->links() }}
@endsection
