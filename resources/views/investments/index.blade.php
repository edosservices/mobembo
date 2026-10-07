@extends('layouts.app')
@section('title', 'Portefeuille')
@section('content')
<div class="portfolio-page">
    <h1 class="portfolio-title">Portefeuille</h1>

    <article class="card border-0 daily-revenue-card" data-balance="daily">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calendar2-check flex-shrink-0" aria-hidden="true"></i>
                <div class="min-w-0">
                    <span class="daily-label">Revenu journalier</span>
                    <strong>{{ money($summary['estimate_today']) }}</strong>
                </div>
            </div>
            <p class="daily-note mb-0">Crédité aujourd’hui <span data-balance-extra="daily_today">{{ money($summary['returns_today']) }}</span>. Progression du lundi au vendredi, sur chaque position ouverte.</p>
        </div>
    </article>

    <div class="row row-cols-3 g-2 portfolio-services">
        <div class="col">
            <a class="card portfolio-service service-deposit h-100 border-0 text-decoration-none" href="{{ route('deposits.create') }}">
                <div class="card-body">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i>
                    <strong>Déposer</strong>
                    <span>Ajouter des fonds</span>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card portfolio-service service-withdraw h-100 border-0 text-decoration-none" href="{{ route('withdrawals.create') }}">
                <div class="card-body">
                    <i class="bi bi-arrow-up-circle" aria-hidden="true"></i>
                    <strong>Retirer</strong>
                    <span>Récupérer un gain</span>
                </div>
            </a>
        </div>
        <div class="col">
            <a class="card portfolio-service service-transfer h-100 border-0 text-decoration-none" href="{{ route('transfers.create') }}">
                <div class="card-body">
                    <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                    <strong>Transférer</strong>
                    <span>Envoyer à un membre</span>
                </div>
            </a>
        </div>
    </div>

    <div class="d-md-none portfolio-holdings">
        @forelse ($investments as $investment)
            <article class="card border-0 shadow-sm holding-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <a class="holding-name" href="{{ route('investments.show', $investment) }}">{{ $investment->project->name }}</a>
                        @include('partials.status', ['status' => $investment->status])
                    </div>
                    <dl class="row g-1 mb-0">
                        <div class="col-6"><dt>Montant</dt><dd>{{ money($investment->amount) }}</dd></div>
                        <div class="col-6"><dt>Revenus crédités</dt><dd>{{ money($investment->returns_credited) }}</dd></div>
                        <div class="col-6"><dt>Revenu estimatif</dt><dd>{{ money($investment->estimatedReturn()) }}</dd></div>
                        <div class="col-6"><dt>Accumulé estimatif</dt><dd>{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</dd></div>
                        <div class="col-12"><dt>Date de fin</dt><dd>{{ $investment->ends_at->format('d/m/Y') }}</dd></div>
                    </dl>
                </div>
            </article>
        @empty
            <p class="muted">Aucun investissement.</p>
        @endforelse
    </div>

    <div class="d-none d-md-block table-wrap panel">
        <table class="portfolio-table">
            <thead><tr><th>Projet</th><th>Montant</th><th>Revenus crédités</th><th>Revenu estimatif</th><th>Accumulé estimatif</th><th>Statut</th><th>Date de fin</th></tr></thead>
            <tbody>
            @forelse ($investments as $investment)
                <tr>
                    <td><a href="{{ route('investments.show', $investment) }}">{{ $investment->project->name }}</a></td>
                    <td>{{ money($investment->amount) }}</td>
                    <td>{{ money($investment->returns_credited) }}</td>
                    <td>{{ money($investment->estimatedReturn()) }}</td>
                    <td>{{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</td>
                    <td>@include('partials.status', ['status' => $investment->status])</td>
                    <td>{{ $investment->ends_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Aucun investissement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $investments->links() }}

    <h2 class="portfolio-subtitle">Historique des profits</h2>
    <div class="txn-list">
        @forelse ($profits as $profit)
            <article class="txn">
                <strong class="pos">+{{ money($profit->amount) }}</strong>
                <span>{{ $profit->project->name ?? 'Plan' }}</span>
                <time>{{ $profit->profit_date->format('d/m/Y') }}</time>
                <em>Profit quotidien</em>
            </article>
        @empty
            <p class="muted">Aucun profit crédité pour le moment.</p>
        @endforelse
    </div>
</div>
@endsection
