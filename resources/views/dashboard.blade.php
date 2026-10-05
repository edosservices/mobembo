@extends('layouts.app')
@section('title', 'Tableau de bord · ZELVORA')
@section('content')
<div class="kicker">Portefeuille</div>
<h1 style="font-size:2.5rem;margin-bottom:.8rem;">Bonjour {{ auth()->user()->name }}.</h1>
<div class="grid-4">
    <div class="stat"><span>Solde disponible</span><strong>{{ money($summary['available']) }}</strong></div>
    <div class="stat"><span>Montant investi</span><strong>{{ money($summary['invested']) }}</strong></div>
    <div class="stat"><span>Revenus crédités</span><strong>{{ money($summary['returns_total']) }}</strong></div>
    <div class="stat"><span>Revenus du jour</span><strong>{{ money($summary['returns_today']) }}</strong></div>
    <div class="stat"><span>Bonus</span><strong>{{ money($summary['bonus']) }}</strong></div>
    <div class="stat"><span>Commissions</span><strong>{{ money($summary['commissions']) }}</strong></div>
    <div class="stat"><span>Investissements actifs</span><strong>{{ $summary['active_count'] }}</strong></div>
    <div class="stat"><span>Retrait en attente</span><strong>{{ money($summary['locked']) }}</strong></div>
</div>
<p class="note" style="margin-top:.8rem;">Estimation non garantie du jour : {{ money($summary['estimate_today']) }}. Ce chiffre n’est pas de l’argent reçu. Seuls les revenus crédités figurent dans le solde.</p>
<div class="quick" style="margin:1rem 0;">
    <a href="{{ route('deposits.create') }}">Déposer</a>
    <a href="{{ route('projects.index') }}">Investir</a>
    <a href="{{ route('withdrawals.create') }}">Retirer</a>
    <a href="{{ route('referral') }}">Parrainer</a>
</div>
<h2>Investissements actifs</h2>
<div class="grid-2">
    @forelse ($investments as $investment)
        <a class="panel" href="{{ route('investments.show', $investment) }}" style="text-decoration:none;">
            <strong>{{ $investment->project->name }}</strong>
            <div class="money sm">{{ money($investment->amount) }}</div>
            <div class="muted">Revenus crédités {{ money($investment->returns_credited) }} · Estimation {{ money($investment->estimatedReturn()) }}</div>
        </a>
    @empty
        <p class="muted">Aucun investissement actif. Le solde disponible reste sur votre portefeuille tant que vous ne choisissez pas un projet.</p>
    @endforelse
</div>
@endsection
