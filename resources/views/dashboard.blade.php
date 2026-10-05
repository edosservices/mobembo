@extends('layouts.app')
@section('title', 'Tableau de bord · ZELVORA')
@section('content')
<h1 style="font-size:clamp(2rem, 6vw, 2.8rem);margin-bottom:1rem;">Bonjour {{ $firstName }}</h1>
<div class="dash-stats">
    <div class="stat"><span>Solde disponible</span><strong>{{ money($summary['available']) }}</strong></div>
    <div class="stat"><span>Bonus</span><strong>{{ money($summary['bonus']) }}</strong></div>
    <div class="stat"><span>Commissions</span><strong>{{ money($summary['commissions']) }}</strong></div>
    <div class="stat"><span>Montant investi</span><strong>{{ money($summary['invested']) }}</strong></div>
    <div class="stat"><span>Revenus crédités</span><strong>{{ money($summary['returns_total']) }}</strong></div>
    <div class="stat"><span>Investissements actifs</span><strong>{{ $summary['active_count'] }}</strong></div>
</div>
<div class="dash-actions">
    <a class="btn-z" data-dashboard-invest href="{{ $investUrl }}">Investir</a>
    <a class="btn-z-ghost" data-dashboard-deposit href="{{ route('deposits.create') }}">Faire un dépôt</a>
</div>
<h2>Investissements actifs</h2>
<div class="holding-list">
    @forelse ($investments as $investment)
        <article class="panel holding">
            <div>
                <strong>{{ $investment->project->name }}</strong>
                <div class="money sm">{{ money($investment->amount) }}</div>
                <p class="muted">{{ \App\Support\ReturnEstimator::percentLabel($investment->dailyReturnPercent()) }}</p>
                <p class="muted">{{ $investment->remainingDays() }} jours restants</p>
            </div>
            <a class="btn-z-ghost small" href="{{ route('investments.show', $investment) }}">Voir</a>
        </article>
    @empty
        <p class="muted">Aucun investissement actif.</p>
    @endforelse
</div>
@endsection
