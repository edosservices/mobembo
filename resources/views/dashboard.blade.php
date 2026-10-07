@extends('layouts.app')
@section('title', 'Tableau de bord · ZELVORA')
@section('content')
@include('partials.operating-notice')
<header class="dash-head">
    <h1>Bonjour {{ $firstName }}</h1>
    <p>Voici l'état de votre portefeuille.</p>
</header>
<div class="row g-2 g-md-3 balance-grid">
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-invested h-100 border-0" data-balance="invested">
            <div class="card-body">
                <i class="bi bi-building" aria-hidden="true"></i>
                <span>Capital investi</span>
                <strong>{{ money($summary['invested']) }}</strong>
                <small>Non retirable</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-available h-100 border-0" data-balance="available">
            <div class="card-body">
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <span>Solde retirable</span>
                <strong>{{ money($summary['available']) }}</strong>
                <small>Disponible maintenant</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-profit h-100 border-0" data-balance="profit">
            <div class="card-body">
                <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
                <span>Profit total</span>
                <strong>{{ money($summary['profit_available']) }}</strong>
                <small>Encore disponible</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-bonus h-100 border-0" data-balance="bonus">
            <div class="card-body">
                <i class="bi bi-gift" aria-hidden="true"></i>
                <span>Bonus</span>
                <strong>{{ money($summary['bonus']) }}</strong>
                <small>Encore disponible</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-commission h-100 border-0" data-balance="commission">
            <div class="card-body">
                <i class="bi bi-people" aria-hidden="true"></i>
                <span>Commissions</span>
                <strong>{{ money($summary['commissions']) }}</strong>
                <small>Encore disponibles</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-total h-100 border-0" data-balance="total">
            <div class="card-body">
                <i class="bi bi-pie-chart" aria-hidden="true"></i>
                <span>Total portefeuille</span>
                <strong>{{ money($summary['portfolio_total']) }}</strong>
                <small>Disponible, réservé et investi</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-pending h-100 border-0" data-balance="pending">
            <div class="card-body">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                <span>Retraits en attente</span>
                <strong>{{ money($summary['withdrawals_pending']) }}</strong>
                <small>Demandés ou en traitement</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-paid h-100 border-0" data-balance="paid">
            <div class="card-body">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <span>Retraits effectués</span>
                <strong>{{ money($summary['withdrawals_paid']) }}</strong>
                <small>Déjà payés</small>
            </div>
        </article>
    </div>
    <div class="col-6 col-lg-4 col-xxl-3">
        <article class="card balance-card balance-daily h-100 border-0" data-balance="daily">
            <div class="card-body">
                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                <span>Revenu journalier</span>
                <strong>{{ money($summary['estimate_today']) }}</strong>
                <small>Aujourd’hui <span data-balance-extra="daily_today">{{ money($summary['returns_today']) }}</span></small>
            </div>
        </article>
    </div>
</div>
<div class="dash-actions">
    <a class="btn-z" data-dashboard-deposit href="{{ route('deposits.create') }}">+ Faire un dépôt</a>
    <a class="btn-z-ghost" data-dashboard-invest href="{{ $investUrl }}">Investir</a>
    @if (\App\Support\Money::cmp($summary['available'], '0') > 0)
        <a class="btn-z" data-withdraw-gains href="{{ route('withdrawals.create') }}">Retirer mes gains</a>
    @endif
</div>

<section class="panel chart-card">
    <div class="section-head">
        <h2>Évolution du solde</h2>
        <div class="chips" role="tablist">
            @foreach (['7d' => '7 jours', '30d' => '30 jours', '90d' => '3 mois', '1y' => '1 an'] as $key => $label)
                <a href="{{ route('dashboard', ['range' => $key]) }}" @class(['chip-link', 'is-on' => $chart['range'] === $key])>{{ $label }}</a>
            @endforeach
        </div>
    </div>
    @if ($chart['empty'])
        <p class="empty-chart">Votre historique apparaîtra ici après vos premières opérations.</p>
    @else
        <svg class="chart" viewBox="0 0 320 120" preserveAspectRatio="none" role="img" aria-label="Évolution du solde disponible">
            <polyline points="{{ $chart['polyline'] }}" fill="none" stroke="#b8956c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></polyline>
            @foreach ($chart['dots'] as $dot)
                <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="3" fill="#111"></circle>
            @endforeach
        </svg>
        <p class="fine-print">De {{ money($chart['min']) }} à {{ money($chart['max']) }}, d'après les opérations enregistrées.</p>
    @endif
</section>

<div class="dash-split">
    <section>
        <h2>Activité récente</h2>
        <div class="txn-list">
            @forelse ($activity as $entry)
                <article class="txn">
                    <strong @class(['pos' => \App\Support\Money::cmp($entry->amount, '0') > 0])>{{ \App\Support\TransactionPresenter::signedAmount($entry) }}</strong>
                    <span>{{ $entry->type->label() }}</span>
                    <span class="desc">{{ $entry->description }}</span>
                    <time>{{ \App\Support\TransactionPresenter::date($entry) }}</time>
                    <em>{{ \App\Support\TransactionPresenter::clientStatus($entry) }}</em>
                </article>
            @empty
                <p class="muted">Aucune opération pour le moment.</p>
            @endforelse
        </div>
        <p><a class="btn-z-ghost small" href="{{ route('transactions.index') }}">Voir toutes les transactions</a></p>
    </section>
    <section class="panel team-card">
        <p class="kicker">Votre équipe</p>
        <h2>{{ $team['level']['name'] }}</h2>
        <dl class="plan-facts">
            <div><dt>Membres parrainés</dt><dd>{{ $team['referred'] }}</dd></div>
            <div><dt>Membres actifs</dt><dd>{{ $team['active'] }}</dd></div>
            <div><dt>Dépôts de l'équipe</dt><dd>{{ money($team['deposits']) }}</dd></div>
            <div><dt>Investissements de l'équipe</dt><dd>{{ money($team['investments']) }}</dd></div>
            <div><dt>Commissions générées</dt><dd>{{ money($team['commissions']) }}</dd></div>
            <div><dt>Taux actuel de commission</dt><dd>{{ str_replace('.', ',', bcadd($team['rate'], '0', 2)) }} %</dd></div>
        </dl>
        <p class="muted">Code de parrainage</p>
        <p class="ref-code">{{ $team['code'] ?: '—' }}</p>
        @if ($team['link'])
            <p class="ref-link">{{ $team['link'] }}</p>
            <button class="btn-z-ghost small" type="button" data-copy="{{ $team['link'] }}">Copier le lien</button>
        @endif
        <p><a class="btn-z small" href="{{ route('referral') }}">Inviter un membre</a></p>
    </section>
</div>

<h2>Investissements actifs</h2>
<div class="holding-list">
    @forelse ($investments as $investment)
        <article class="panel holding">
            <div>
                <strong>{{ $investment->project->name }}</strong>
                <div class="money sm">{{ money($investment->amount) }}</div>
                <p class="muted">{{ \App\Support\ReturnEstimator::percentLabel($investment->dailyReturnPercent()) }}</p>
                <p class="muted">Gain estimatif accumulé {{ \App\Support\ReturnEstimator::amountLabel($investment->estimatedAccruedReturn()) }}</p>
                <p class="muted">{{ $investment->remainingDays() }} jours restants</p>
            </div>
            <a class="btn-z-ghost small" href="{{ route('investments.show', $investment) }}">Voir</a>
        </article>
    @empty
        <p class="muted">Aucun investissement actif.</p>
    @endforelse
</div>
@endsection
