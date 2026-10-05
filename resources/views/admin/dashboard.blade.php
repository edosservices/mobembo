@extends('layouts.admin')
@section('title', 'Dashboard · Administration')
@section('content')
<header class="dash-head">
    <h1>Bonjour {{ $firstName }}</h1>
    <p>Voici l'état de la plateforme ZELVORA.</p>
</header>
<div class="dash-stats">
    @foreach ([
        'Utilisateurs inscrits' => $stats['users_total'],
        'Utilisateurs actifs' => $stats['users_active'],
        'Total des dépôts' => money($stats['deposits_total']),
        'Dépôts en attente' => $stats['deposits_pending'],
        'Total des retraits' => money($stats['withdrawals_total']),
        'Retraits en attente' => $stats['withdrawals_pending'],
        'Capital investi' => money($stats['invested']),
        'Revenus réellement distribués' => money($stats['returns']),
    ] as $label => $value)
        <div class="stat"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
    @endforeach
</div>
<div class="dash-stats" style="margin-top:.7rem;">
    @foreach ([
        'Projets ouverts' => $stats['projects_open'],
        'Projets actifs' => $stats['projects_active'],
        'Projets terminés' => $stats['projects_finished'],
        'Investissements actifs' => $stats['investments_active'],
        'Commissions distribuées' => money($stats['commissions']),
        'Bonus distribués' => money($stats['bonus']),
    ] as $label => $value)
        <div class="stat"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
    @endforeach
</div>

<section class="panel" style="margin-top:1rem;">
    <div class="section-head">
        <h2>Activité de la plateforme</h2>
        <div class="chips">
            @foreach (['7d' => '7 jours', '30d' => '30 jours', '90d' => '3 mois', '1y' => '1 an'] as $key => $label)
                <a href="{{ route('admin.dashboard', ['range' => $key]) }}" @class(['chip-link', 'is-on' => $activity['range'] === $key])>{{ $label }}</a>
            @endforeach
        </div>
    </div>
    @if ($activity['empty'])
        <p class="empty-chart">Aucune opération sur cette période.</p>
    @else
        <svg class="chart" viewBox="0 0 320 120" preserveAspectRatio="none" role="img" aria-label="Dépôts, retraits et investissements">
            <polyline points="{{ $activity['deposits'] }}" fill="none" stroke="#b8956c" stroke-width="2.5"></polyline>
            <polyline points="{{ $activity['withdrawals'] }}" fill="none" stroke="#1d4e89" stroke-width="2.5"></polyline>
            <polyline points="{{ $activity['investments'] }}" fill="none" stroke="#111" stroke-width="2.5"></polyline>
            <line x1="0" y1="100" x2="320" y2="100" stroke="#e6e1d8" stroke-width="1"></line>
        </svg>
        <p class="legend"><i class="swatch gold"></i>Dépôts approuvés <i class="swatch navy"></i>Retraits approuvés <i class="swatch ink"></i>Investissements</p>
    @endif
</section>

<div class="dash-split">
    <section class="panel">
        <h2>Croissance des utilisateurs</h2>
        @if ($growth['empty'])
            <p class="empty-chart">Aucune inscription ni opération sur cette période.</p>
        @else
            <svg class="chart" viewBox="0 0 320 120" preserveAspectRatio="none" role="img" aria-label="Nouveaux utilisateurs et utilisateurs actifs">
                <polyline points="{{ $growth['registered'] }}" fill="none" stroke="#b8956c" stroke-width="2.5"></polyline>
                <polyline points="{{ $growth['active'] }}" fill="none" stroke="#1d4e89" stroke-width="2.5"></polyline>
                <line x1="0" y1="100" x2="320" y2="100" stroke="#e6e1d8" stroke-width="1"></line>
            </svg>
            <p class="legend"><i class="swatch gold"></i>Nouveaux comptes <i class="swatch navy"></i>Comptes avec une opération réelle</p>
        @endif
    </section>
    <section class="panel">
        <h2>Répartition des fonds</h2>
        @if ($funds['empty'])
            <p class="empty-chart">Aucun montant enregistré pour le moment.</p>
        @else
            @foreach ($funds['rows'] as $row)
                <p class="fund-line"><span>{{ $row['label'] }}</span><strong>{{ money($row['amount']) }}</strong></p>
                <div class="progress" role="img" aria-label="{{ $row['label'] }}"><span style="width: {{ $row['width'] }}%"></span></div>
            @endforeach
        @endif
    </section>
</div>

<section style="margin-top:1rem;">
    <h2>Actions nécessitant votre attention</h2>
    @if ($attention['total'] === 0 && $attention['support'] === 0)
        <p class="panel">Tout est à jour.</p>
    @endif
    <div class="dash-stats">
        <article class="stat"><span>Dépôts en attente</span><strong>{{ $attention['deposits'] }}</strong><a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}">Voir les dépôts</a></article>
        <article class="stat"><span>Retraits en attente</span><strong>{{ $attention['withdrawals'] }}</strong><a href="{{ route('admin.withdrawals.index', ['status' => 'pending']) }}">Voir les retraits</a></article>
        <article class="stat"><span>Investissements à contrôler</span><strong>{{ $attention['investments'] }}</strong><a href="{{ route('admin.investments.index', ['status' => 'suspended']) }}">Voir les investissements</a></article>
        <article class="stat"><span>Demandes KYC</span><strong>{{ $attention['kyc'] }}</strong><a href="{{ route('admin.users.index') }}">Voir les KYC</a></article>
        <article class="stat"><span>Support en attente</span><strong>{{ $attention['support'] }}</strong><a href="{{ route('admin.support') }}">Voir le support</a></article>
    </div>
</section>

<section style="margin-top:1.2rem;">
    <h2>Activité récente</h2>
    <div class="table-responsive panel">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Type</th><th>Montant</th><th>Statut</th><th>Action</th></tr></thead>
            <tbody>
            @forelse ($recent as $entry)
                <tr>
                    <td>{{ $entry->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                    <td>{{ $entry->user->name ?? '—' }}</td>
                    <td>{{ $entry->type->label() }}</td>
                    <td>{{ money($entry->amount) }}</td>
                    <td>{{ $entry->status->label() }}</td>
                    <td>@if ($entry->user && ! $entry->user->isAdmin())<a href="{{ route('admin.users.show', $entry->user) }}">Voir</a>@else — @endif</td>
                </tr>
            @empty
                <tr><td colspan="6">Aucune opération.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p><a class="btn-z-ghost small" href="{{ route('admin.transactions.index') }}">Voir toutes les transactions</a></p>
</section>
@endsection
