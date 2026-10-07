@extends('layouts.app')
@section('title', 'Portefeuille')
@section('content')
<div class="portfolio-page">
    <header class="portfolio-head">
        <h1 class="portfolio-title">Portefeuille</h1>
    </header>

    <article class="daily-revenue-card" data-balance="daily">
        <div class="daily-main">
            <span class="daily-icon" aria-hidden="true"><i class="bi bi-calendar2-check"></i></span>
            <div class="daily-copy">
                <span class="daily-label">Revenu journalier</span>
                <strong>{{ money($summary['estimate_today']) }}</strong>
            </div>
        </div>
        <div class="daily-note">
            <p>Crédité aujourd’hui : <span data-balance-extra="daily_today">{{ money($summary['returns_today']) }}</span></p>
            <p>Progression du lundi au vendredi, sur chaque position ouverte.</p>
        </div>
    </article>

    <div class="portfolio-services">
        <a class="portfolio-service service-deposit" href="{{ route('deposits.create') }}">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>
            <strong>Déposer</strong>
            <span>Ajouter des fonds</span>
        </a>
        <a class="portfolio-service service-withdraw" href="{{ route('withdrawals.create') }}">
            <i class="bi bi-arrow-up-circle" aria-hidden="true"></i>
            <strong>Retirer</strong>
            <span>Récupérer un gain</span>
        </a>
        <a class="portfolio-service service-transfer" href="{{ route('transfers.create') }}">
            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
            <strong>Transférer</strong>
            <span>Envoyer à un membre</span>
        </a>
    </div>

    @if ($investments->isEmpty())
        <section class="portfolio-empty" aria-label="Aucun investissement">
            <i class="bi bi-briefcase" aria-hidden="true"></i>
            <h2>Aucun investissement.</h2>
            <p>Vos positions s’afficheront ici dès votre premier placement.</p>
            <a class="portfolio-empty-action" href="{{ route('projects.index') }}">Commencer à investir</a>
        </section>
    @else
        <section class="portfolio-positions" aria-label="Positions ouvertes">
            <h2 class="portfolio-subtitle">Vos positions</h2>
            <div class="portfolio-holdings">
                @foreach ($investments as $investment)
                    <article class="holding-card">
                        <div class="holding-top">
                            <a class="holding-name" href="{{ route('investments.show', $investment) }}">{{ $investment->project->name }}</a>
                            @include('partials.status', ['status' => $investment->status])
                        </div>
                        <dl class="holding-meta">
                            <div><dt>Montant</dt><dd>{{ money($investment->amount) }}</dd></div>
                            <div><dt>Revenus crédités</dt><dd>{{ money($investment->returns_credited) }}</dd></div>
                            <div><dt>Revenu estimatif</dt><dd>{{ money($investment->estimatedReturn()) }}</dd></div>
                            <div><dt>Accumulé estimatif</dt><dd>{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</dd></div>
                            <div class="holding-wide"><dt>Date de fin</dt><dd>{{ $investment->ends_at->format('d/m/Y') }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>

            <div class="portfolio-table-wrap">
                <table class="portfolio-table">
                    <thead>
                        <tr>
                            <th>Projet</th>
                            <th>Montant</th>
                            <th>Revenus crédités</th>
                            <th>Revenu estimatif</th>
                            <th>Accumulé estimatif</th>
                            <th>Statut</th>
                            <th>Date de fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($investments as $investment)
                            <tr>
                                <td><a href="{{ route('investments.show', $investment) }}">{{ $investment->project->name }}</a></td>
                                <td>{{ money($investment->amount) }}</td>
                                <td>{{ money($investment->returns_credited) }}</td>
                                <td>{{ money($investment->estimatedReturn()) }}</td>
                                <td>{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</td>
                                <td>@include('partials.status', ['status' => $investment->status])</td>
                                <td>{{ $investment->ends_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($investments->hasPages())
        <div class="portfolio-pages">{{ $investments->links() }}</div>
    @endif

    <section class="profit-history" aria-label="Historique des profits">
        <div class="profit-head">
            <h2 class="portfolio-subtitle">Historique des profits</h2>
            <p class="profit-lead">Les montants crédités du lundi au vendredi.</p>
        </div>
        @forelse ($profits as $profit)
            <article class="txn">
                <strong class="pos">+{{ money($profit->amount) }}</strong>
                <span>{{ $profit->project->name ?? 'Plan' }}</span>
                <time>{{ $profit->profit_date->format('d/m/Y') }}</time>
                <em>Profit quotidien</em>
            </article>
        @empty
            <div class="portfolio-empty portfolio-empty-soft">
                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                <p>Aucun profit crédité pour le moment.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
