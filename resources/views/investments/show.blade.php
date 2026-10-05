@extends('layouts.app')
@section('title', $investment->project->name)
@section('content')
<p class="kicker">Investissement</p>
<h1 style="font-size:clamp(2rem, 6vw, 2.8rem);">{{ $investment->project->name }}</h1>
@include('partials.status', ['status' => $investment->status])
<div class="stat" style="margin-top:1rem;">
    <span>Investissement</span>
    <strong>{{ money($investment->amount) }}</strong>
</div>
<div class="dash-stats" style="margin-top:.8rem;">
    <div class="stat"><span>Taux journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::percentLabel($investment->dailyReturnPercent()) }}</strong></div>
    <div class="stat"><span>Début</span><strong class="money sm">{{ $investment->starts_at->format('d/m/Y') }}</strong></div>
    <div class="stat"><span>Fin</span><strong class="money sm">{{ $investment->ends_at->format('d/m/Y') }}</strong></div>
    <div class="stat"><span>Jours écoulés</span><strong class="money sm">{{ $investment->elapsedDays() }} jours</strong></div>
    <div class="stat"><span>Jours restants</span><strong class="money sm">{{ $investment->remainingDays() }} jours</strong></div>
    <div class="stat"><span>Revenu journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedDailyAmount()) }}</strong></div>
    <div class="stat"><span>Revenus réellement crédités</span><strong class="money sm">{{ money($investment->returns_credited) }}</strong></div>
</div>
<p style="margin-top:1rem;">Il reste {{ $investment->remainingDays() }} jours</p>
<div class="progress" role="img" aria-label="{{ str_replace('.', ',', $investment->progressPercent()) }} % de la durée écoulée">
    <span style="width: {{ min(100, (float) $investment->progressPercent()) }}%"></span>
</div>
<p class="muted">{{ str_replace('.', ',', $investment->progressPercent()) }} % de la durée écoulée. Estimation cumulée non créditée : {{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}.</p>
<div class="note">Le taux journalier est calculé à partir du rendement prévu sur la durée. Il n’est pas ajouté au solde. Seuls les revenus réellement crédités, {{ money($investment->returns_credited) }}, proviennent d’une distribution enregistrée.</div>
@endsection
