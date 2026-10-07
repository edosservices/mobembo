@extends('layouts.app')
@php($quote = $investment->quote())
@section('title', $investment->project->name)
@section('content')
<p class="kicker">Investissement</p>
<h1 style="font-size:clamp(2rem, 6vw, 2.8rem);">{{ $investment->project->name }}</h1>
@include('partials.status', ['status' => $investment->status])
@include('partials.operating-notice')

<section class="detail-block">
    <h2>Estimation</h2>
    <div class="dash-stats">
        <div class="stat"><span>Capital investi</span><strong>{{ money($quote->capital) }}</strong></div>
        <div class="stat"><span>Rendement journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::percentLabel($quote->dailyPercent) }}</strong></div>
        <div class="stat"><span>Gain estimatif / jour</span><strong class="money sm">{{ \App\Support\ReturnEstimator::amountLabel($quote->dailyAmount) }}</strong></div>
        <div class="stat"><span>Rendement total estimatif</span><strong class="money sm" data-total-return>{{ money($quote->totalReturn) }}</strong></div>
        <div class="stat"><span>Total estimatif à échéance</span><strong class="money sm" data-maturity>{{ money($quote->maturity) }}</strong></div>
        <div class="stat"><span>Date de début</span><strong class="money sm">{{ $investment->starts_at->format('d/m/Y') }}</strong></div>
        <div class="stat"><span>Date de fin</span><strong class="money sm">{{ $investment->ends_at->format('d/m/Y') }}</strong></div>
        <div class="stat"><span>Jours écoulés</span><strong class="money sm">{{ $investment->elapsedDays() }}</strong></div>
        <div class="stat"><span>Jours ouvrés comptés</span><strong class="money sm">{{ $investment->accrualDays() }}</strong></div>
        <div class="stat"><span>Gain estimatif accumulé</span><strong class="money sm">{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</strong></div>
        <div class="stat"><span>Jours restants</span><strong class="money sm">{{ $investment->remainingDays() }}</strong></div>
    </div>
    <p style="margin-top:1rem;">Progression temporelle</p>
    <div class="progress" role="img" aria-label="{{ str_replace('.', ',', $investment->progressPercent()) }} % de la durée écoulée">
        <span style="width: {{ min(100, (float) $investment->progressPercent()) }}%"></span>
    </div>
    <p class="fine-print">Le gain estimatif accumulé augmente chaque jour du lundi au vendredi. Le samedi est le jour de maintenance. Ce montant n’est pas un revenu déjà crédité.</p>
</section>

<section class="panel credited-card">
    <h2>Revenus réellement crédités</h2>
    <p class="money">{{ money($investment->returns_credited) }}</p>
    <p class="fine-print">Le profit quotidien est crédité automatiquement les jours ouvrés, dès le jour de l’investissement, dans le solde retirable. Le capital investi reste séparé.</p>
    @if ($investment->profits->isNotEmpty())
        <div class="txn-list">
            @foreach ($investment->profits as $profit)
                <article class="txn">
                    <strong class="pos">+{{ money($profit->amount) }}</strong>
                    <time>{{ $profit->profit_date->format('d/m/Y') }}</time>
                    <em>Profit quotidien</em>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
