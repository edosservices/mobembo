@extends('layouts.admin')
@section('content')
<h1 class="serif">{{ $project->exists ? 'Modifier le projet' : 'Nouveau projet' }}</h1>
<form class="panel" method="POST" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($project->exists) @method('PUT') @endif
    <div class="grid-2">
        <div class="field"><label>Nom</label><input name="name" value="{{ old('name', $project->name) }}" required></div>
        <div class="field"><label>Localisation</label><input name="location" value="{{ old('location', $project->location) }}" required></div>
        <div class="field"><label>Catégorie</label><input name="category" value="{{ old('category', $project->category) }}" placeholder="Résidentiel" required></div>
        <div class="field">
            <label>Statut</label>
            <select name="status">
                @foreach (\App\Enums\ProjectStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Objectif</label><input name="target_amount" value="{{ old('target_amount', $project->target_amount) }}" required></div>
        <div class="field"><label>Investissement minimum</label><input name="min_investment" value="{{ old('min_investment', $project->min_investment) }}" required></div>
        <div class="field"><label>Durée (jours)</label><input name="duration_days" value="{{ old('duration_days', $project->duration_days) }}" required></div>
        <div class="field"><label>Rendement prévu sur la durée (%)</label><input name="expected_return_percent" value="{{ old('expected_return_percent', $project->expected_return_percent) }}" required></div>
        <div class="field">
            <label>Rythme de distribution</label>
            <select name="distribution_frequency">
                @foreach (\App\Enums\DistributionFrequency::cases() as $frequency)
                    <option value="{{ $frequency->value }}" @selected(old('distribution_frequency', $project->distribution_frequency?->value ?? 'at_maturity') === $frequency->value)>{{ $frequency->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Prochaine distribution prévue</label><input type="date" name="next_distribution_on" value="{{ old('next_distribution_on', optional($project->next_distribution_on)->format('Y-m-d')) }}"></div>
        <div class="field"><label>Début</label><input type="date" name="starts_at" value="{{ old('starts_at', optional($project->starts_at)->format('Y-m-d')) }}"></div>
        <div class="field"><label>Fin</label><input type="date" name="ends_at" value="{{ old('ends_at', optional($project->ends_at)->format('Y-m-d')) }}"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="description" required>{{ old('description', $project->description) }}</textarea></div>
    <div class="field"><label>Règles économiques</label><textarea name="economic_terms" required>{{ old('economic_terms', $project->economic_terms) }}</textarea></div>
    <div class="field"><label>Image</label><input type="file" name="image" accept="image/*"></div>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="is_demo" value="1" style="width:auto;" @checked(old('is_demo', $project->is_demo))> Projet de démonstration</label>
    <button class="btn-z" type="submit" style="margin-top:1rem;">Enregistrer</button>
</form>
@endsection
