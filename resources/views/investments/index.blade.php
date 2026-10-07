@extends('layouts.app')
@section('title', 'Portefeuille')
@section('content')
<h1 style="font-size:2.3rem;">Portefeuille</h1>
<div class="table-wrap panel">
    <table>
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
<h2>Historique des profits</h2>
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
@endsection
