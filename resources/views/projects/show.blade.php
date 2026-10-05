@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="grid-2">
    <div>
        <div class="kicker">{{ $project->location }} · {{ $project->category }}</div>
        <h1 style="font-size:2.6rem;">{{ $project->name }}</h1>
        @include('partials.status', ['status' => $project->status])
        @if ($project->is_demo)<span class="badge-z tone-warn">Démonstration</span>@endif
        <p>{{ $project->description }}</p>
        <div class="grid-3">
            <div class="stat"><span>Objectif</span><strong class="money sm">{{ money($project->target_amount) }}</strong></div>
            <div class="stat"><span>Déjà financé</span><strong class="money sm">{{ money($project->funded_amount) }}</strong></div>
            <div class="stat"><span>Reste</span><strong class="money sm">{{ money($project->remainingAmount()) }}</strong></div>
        </div>
        <div class="progress" style="margin:.8rem 0;"><span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span></div>
        <p>Progression {{ str_replace('.', ',', $project->progressPercent()) }} % · Minimum {{ money($project->min_investment) }} · Durée {{ $project->duration_days }} jours</p>
        <div class="note">Rendement prévu : {{ number_format((float) $project->expected_return_percent, 2, ',', ' ') }} % sur la durée du projet. Estimation non garantie. {{ $project->distribution_frequency->label() }}. {{ $project->economic_terms }}</div>
    </div>
    <form class="panel" method="POST" action="{{ route('investments.store', $project) }}">
        @csrf
        <h2 style="margin-top:0;">Investir</h2>
        <div class="field">
            <label for="amount">Montant</label>
            <input id="amount" name="amount" inputmode="decimal" value="{{ old('amount', $project->min_investment) }}" required>
        </div>
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <p>Rendement prévu sur la durée : <strong id="estimate">—</strong></p>
        <p class="muted">Cette estimation n’est pas créditée. Seule une distribution déclarée par l’administration augmente les revenus.</p>
        <button class="btn-z full" type="submit" @disabled(! $project->isInvestable())>Confirmer l’investissement</button>
        @unless ($project->isInvestable())
            <p class="muted">Ce projet n’accepte pas de nouvel apport.</p>
        @endunless
    </form>
</div>
@if ($mine->isNotEmpty())
    <h2>Vos apports sur ce projet</h2>
    @foreach ($mine as $investment)
        <p><a href="{{ route('investments.show', $investment) }}">{{ money($investment->amount) }} · @include('partials.status', ['status' => $investment->status])</a></p>
    @endforeach
@endif
<script>
const rate = {{ (float) $project->expected_return_percent }};
const input = document.getElementById('amount');
const out = document.getElementById('estimate');
const paint = () => {
    const amount = parseFloat(String(input.value).replace(',', '.')) || 0;
    const value = amount * rate / 100;
    out.textContent = value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $ (estimé, non garanti)';
};
input.addEventListener('input', paint);
paint();
</script>
@endsection
