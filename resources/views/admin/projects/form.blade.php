@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.projects.index') }}">Projets</a></p>
<h1 class="serif">{{ $project->exists ? 'Modifier le plan' : 'Nouveau plan' }}</h1>
<p class="muted">Tous les champs du plan se règlent ici. Les investissements déjà ouverts conservent leur taux et leur capital.</p>
<form class="panel plan-form" method="POST" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($project->exists) @method('PUT') @endif
    <h2>Identité</h2>
    <div class="grid-2">
        <div class="field"><label>Nom</label><input name="name" value="{{ old('name', $project->name) }}" required></div>
        <div class="field"><label>Adresse publique</label><input name="slug" value="{{ old('slug', $project->slug) }}" placeholder="residence-gombe"></div>
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
        <div class="field"><label>Devise</label><input name="currency" value="{{ old('currency', $project->currency ?: 'USD') }}" maxlength="8" required></div>
    </div>
    <h2>Montants et durée</h2>
    <div class="grid-2">
        <div class="field"><label>Objectif</label><input name="target_amount" value="{{ old('target_amount', $project->target_amount) }}" required></div>
        <div class="field"><label>Investissement minimum</label><input name="min_investment" value="{{ old('min_investment', $project->min_investment) }}" required></div>
        <div class="field"><label>Durée (jours)</label><input name="duration_days" value="{{ old('duration_days', $project->duration_days) }}" required></div>
        <div class="field">
            <label>Rendement du plan (%)</label>
            <input name="expected_return_percent" value="{{ old('expected_return_percent', $project->expected_return_percent) }}" required>
        </div>
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
    @if ($project->exists)
        <div class="grid-2">
            <div class="stat"><span>Déjà financé, calculé par les investissements</span><strong>{{ money($project->funded_amount) }}</strong></div>
            <div class="stat"><span>Restant</span><strong>{{ money($project->remainingAmount()) }}</strong></div>
        </div>
    @endif
    <h2>Textes visibles par le client</h2>
    <div class="field"><label>Description</label><textarea name="description" required>{{ old('description', $project->description) }}</textarea></div>
    <div class="field"><label>Règles économiques</label><textarea name="economic_terms" required>{{ old('economic_terms', $project->economic_terms) }}</textarea></div>
    @if ($project->imageUrl())
        <p><img src="{{ $project->imageUrl() }}" alt="" style="width:min(100%, 220px);border-radius:0.8rem;"></p>
    @endif
    <div class="field"><label>Image</label><input type="file" name="image" accept="image/*"></div>
    <button class="btn-z" type="submit">Enregistrer le plan</button>
</form>
@endsection