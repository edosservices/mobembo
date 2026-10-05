@extends('layouts.app')
@section('title', 'Mon équipe')
@section('content')
<p class="kicker">Parrainage</p>
<h1 style="font-size:2.2rem;">Mon équipe</h1>
<p class="level-badge">{{ $team['level']['name'] }}</p>
<div class="dash-stats">
    <div class="stat"><span>Membres parrainés</span><strong>{{ $team['referred'] }}</strong></div>
    <div class="stat"><span>Membres actifs</span><strong>{{ $team['active'] }}</strong></div>
    <div class="stat"><span>Membres inactifs</span><strong>{{ $team['inactive'] }}</strong></div>
    <div class="stat"><span>Dépôts de l'équipe</span><strong class="money sm">{{ money($team['deposits']) }}</strong></div>
    <div class="stat"><span>Investissements de l'équipe</span><strong class="money sm">{{ money($team['investments']) }}</strong></div>
    <div class="stat"><span>Commissions générées</span><strong class="money sm">{{ money($team['commissions']) }}</strong></div>
</div>
<section class="panel" style="margin-top:1rem;">
    <p class="kicker">Invitation</p>
    <p>Code de parrainage</p>
    <p class="ref-code">{{ $team['code'] ?: '—' }}</p>
    @if ($team['link'])
        <p class="ref-link">{{ $team['link'] }}</p>
        <button class="btn-z" type="button" data-copy="{{ $team['link'] }}">Copier le lien</button>
    @else
        <p class="muted">Aucun code n'est associé à ce compte.</p>
    @endif
    <p class="fine-print">Taux actuel de commission : {{ str_replace('.', ',', bcadd($team['rate'], '0', 2)) }} %. Une commission est versée lorsqu'un dépôt parrainé est approuvé.</p>
</section>
<h2>Membres directs</h2>
<div class="member-list">
    @forelse ($members as $member)
        <article class="panel member">
            <strong>{{ $member->name }}</strong>
            <span>{{ $member->created_at->timezone(config('app.timezone'))->format('d/m/Y') }}</span>
            <span>{{ \App\Support\Money::cmp($member->approved_deposits ?? 0, '0') > 0 && $member->status->value === 'active' ? 'Actif' : 'Inactif' }}</span>
            <span>Dépôts {{ money($member->approved_deposits ?? 0) }}</span>
            <span>Investissements {{ money($member->invested_total ?? 0) }}</span>
            <span>Commission {{ money($member->commission_total ?? 0) }}</span>
        </article>
    @empty
        <p class="muted">Aucun membre pour le moment.</p>
    @endforelse
</div>
{{ $members->links() }}
<h2>Commissions</h2>
<div class="txn-list">
    @forelse ($commissions as $commission)
        <article class="txn">
            <strong class="pos">+{{ money($commission->amount) }}</strong>
            <span>{{ $commission->referred->name ?? 'Membre' }}</span>
            <time>{{ $commission->created_at->timezone(config('app.timezone'))->format('d/m/Y') }}</time>
            <em>Créditée</em>
        </article>
    @empty
        <p class="muted">Aucune commission pour le moment.</p>
    @endforelse
</div>
@endsection
