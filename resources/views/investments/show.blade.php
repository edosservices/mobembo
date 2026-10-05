@extends('layouts.app')
@section('title', 'Investissement')
@section('content')
<div class="kicker">{{ $investment->project->location }}</div>
<h1 style="font-size:2.4rem;">{{ $investment->project->name }}</h1>
@include('partials.status', ['status' => $investment->status])
<div class="grid-3" style="margin-top:1rem;">
    <div class="stat"><span>Capital investi</span><strong>{{ money($investment->amount) }}</strong></div>
    <div class="stat"><span>Revenus générés</span><strong>{{ money($investment->returns_credited) }}</strong></div>
    <div class="stat"><span>Estimation du jour</span><strong>{{ money($investment->estimatedDailyReturn()) }}</strong></div>
    <div class="stat"><span>Estimation cumulée</span><strong>{{ money($investment->estimatedReturn()) }}</strong></div>
    <div class="stat"><span>Capital restitué</span><strong>{{ money($investment->capital_returned) }}</strong></div>
    <div class="stat"><span>Prochaine échéance</span><strong class="money sm">{{ $investment->project->next_distribution_on?->format('d/m/Y') ?? $investment->ends_at->format('d/m/Y') }}</strong></div>
</div>
<div class="note" style="margin-top:.8rem;">
    Date d’investissement {{ $investment->invested_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}.
    Rendement prévu {{ number_format((float) $investment->expected_return_percent, 2, ',', ' ') }} % sur {{ $investment->duration_days }} jours.
    L’estimation n’est pas un crédit. La date ci-dessus indique l’échéance prévue, pas un versement automatique.
</div>
<p><a href="{{ route('projects.show', $investment->project) }}">Voir le projet</a></p>
@endsection
