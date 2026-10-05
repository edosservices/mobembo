@extends('layouts.app')
@section('title', $investment->project->name)
@section('content')
<p class="kicker">Investissement</p>
<h1 style="font-size:clamp(2rem, 6vw, 2.8rem);">{{ $investment->project->name }}</h1>
@include('partials.status', ['status' => $investment->status])
<div class="stat" style="margin-top:1rem;">
    <span>Montant</span>
    <strong>{{ money($investment->amount) }}</strong>
</div>
<div class="dash-stats" style="margin-top:.8rem;">
    <div class="stat"><span>Rendement journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::percentLabel($investment->dailyReturnPercent()) }}</strong></div>
    <div class="stat"><span>Date de début</span><strong class="money sm">{{ $investment->starts_at->format('d/m/Y') }}</strong></div>
    <div class="stat"><span>Date de fin</span><strong class="money sm">{{ $investment->ends_at->format('d/m/Y') }}</strong></div>
    <div class="stat"><span>Jours écoulés</span><strong class="money sm">{{ $investment->elapsedDays() }} jours</strong></div>
    <div class="stat"><span>Jours restants</span><strong class="money sm">{{ $investment->remainingDays() }} jours</strong></div>
    <div class="stat"><span>Revenu journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedDailyAmount()) }}</strong></div>
    <div class="stat"><span>Revenus crédités</span><strong class="money sm">{{ money($investment->returns_credited) }}</strong></div>
</div>
<p style="margin-top:1rem;">{{ $investment->remainingDays() }} jours restants</p>
<div class="progress" role="img" aria-label="{{ str_replace('.', ',', $investment->progressPercent()) }} % de la durée écoulée">
    <span style="width: {{ min(100, (float) $investment->progressPercent()) }}%"></span>
</div>
<p class="fine-print">Les revenus crédités apparaissent dans votre portefeuille après leur distribution.</p>
@endsection
