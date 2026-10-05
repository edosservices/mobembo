@extends('layouts.app')
@section('title', 'Parrainage')
@section('content')
<h1 style="font-size:2.3rem;">Parrainage</h1>
<div class="grid-3">
    <div class="stat"><span>Code</span><strong>{{ auth()->user()->referral_code }}</strong></div>
    <div class="stat"><span>Filleuls</span><strong>{{ $filleuls->total() }}</strong></div>
    <div class="stat"><span>Filleuls actifs</span><strong>{{ $activeCount }}</strong></div>
    <div class="stat"><span>Commissions</span><strong>{{ money($total) }}</strong></div>
</div>
<div class="panel" style="margin-top:.8rem;">
    <label>Lien d’invitation</label>
    <input readonly value="{{ url('/register?ref='.auth()->user()->referral_code) }}">
    <p class="muted">Partagez ce lien. {{ $settings->referral_enabled ? 'Commission actuelle : '.$settings->referral_rate_percent.' % sur '.$settings->referral_trigger->label().'.' : 'Les commissions ne sont pas activées pour le moment.' }}</p>
</div>
<h2>Historique des commissions</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Filleul</th><th>Base</th><th>Commission</th></tr></thead>
        <tbody>
        @forelse ($commissions as $commission)
            <tr>
                <td>{{ $commission->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ $commission->referred->name }}</td>
                <td>{{ money($commission->base_amount) }}</td>
                <td>{{ money($commission->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Aucune commission.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<h2>Filleuls</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Nom</th><th>Téléphone</th><th>Statut</th><th>Inscription</th></tr></thead>
        <tbody>
        @foreach ($filleuls as $filleul)
            <tr>
                <td>{{ $filleul->name }}</td>
                <td>{{ $filleul->phone }}</td>
                <td>@include('partials.status', ['status' => $filleul->status])</td>
                <td>{{ $filleul->created_at->format('d/m/Y') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $filleuls->links() }}
@endsection
