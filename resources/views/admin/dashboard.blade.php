@extends('layouts.admin')
@section('title', 'Dashboard admin')
@section('content')
<h1 class="serif">Pilotage</h1>
<p class="fine-print">Le rendement du projet se règle dans Projets. Les frais de retrait et la commission de parrainage se règlent dans Paramètres.</p>
<div class="grid-4">
    @foreach ([
        'Utilisateurs' => $stats['users_total'],
        'Actifs' => $stats['users_active'],
        'Bloqués' => $stats['users_blocked'],
        'Dépôts en attente' => $stats['deposits_pending'],
        'Dépôts validés' => money($stats['deposits_amount']),
        'Retraits en attente' => $stats['withdrawals_pending'],
        'Retraits nets versés' => money($stats['withdrawals_amount']),
        'Capital investi' => money($stats['invested']),
        'Revenus distribués' => money($stats['returns']),
        'Commissions' => money($stats['commissions']),
        'Projets ouverts' => $stats['projects_active'],
        'Volume des dépôts' => money($stats['volume']),
    ] as $label => $value)
        <div class="stat"><span>{{ $label }}</span><strong class="money sm">{{ $value }}</strong></div>
    @endforeach
</div>
<div class="panel" style="margin-top:1rem;">
    <strong>14 derniers jours</strong>
    <div class="muted">Barre foncée : dépôts approuvés. Barre dorée : investissements.</div>
    <div class="bars" style="margin-top:1rem;">
        @foreach ($series as $point)
            <div style="flex:1;">
                <div class="bar-col">
                    <div class="bar deposits" style="height: {{ $point['deposits_height'] }}%"></div>
                    <div class="bar invested" style="height: {{ $point['invested_height'] }}%"></div>
                </div>
                <div class="bar-label">{{ $point['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
