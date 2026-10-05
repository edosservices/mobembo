@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.users.index') }}">Utilisateurs</a></p>
<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
    <h1 class="serif">{{ $user->name }}</h1>
    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
        @csrf
        <button class="btn-z" type="submit">Accéder au compte</button>
    </form>
</div>
<p>{{ $user->phone }} · @include('partials.status', ['status' => $user->status]) · KYC {{ $user->kyc_status->label() }} · Parrain {{ $user->referrer->name ?? 'aucun' }} · Inscrit le {{ $user->created_at->format('d/m/Y H:i') }}</p>
<div class="grid-4">
    <div class="stat"><span>Disponible</span><strong>{{ money($summary['available']) }}</strong></div>
    <div class="stat"><span>Bloqué au retrait</span><strong>{{ money($summary['locked']) }}</strong></div>
    <div class="stat"><span>Investi</span><strong>{{ money($summary['invested']) }}</strong></div>
    <div class="stat"><span>Revenus</span><strong>{{ money($summary['returns_total']) }}</strong></div>
</div>
<div class="grid-2" style="margin-top:1rem;">
    <form class="panel" method="POST" action="{{ route('admin.users.bonus', $user) }}">
        @csrf
        <h2>Ajouter un bonus</h2>
        <div class="field"><label>Montant</label><input name="amount" required></div>
        <div class="field"><label>Motif</label><input name="reason" required placeholder="Bonus promotionnel"></div>
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <button class="btn-z" type="submit">Créditer le bonus</button>
    </form>
    <form class="panel" method="POST" action="{{ route('admin.users.adjust', $user) }}">
        @csrf
        <h2>Ajustement</h2>
        <div class="field"><label>Sens</label><select name="direction"><option value="credit">Crédit</option><option value="debit">Débit</option></select></div>
        <div class="field"><label>Montant</label><input name="amount" required></div>
        <div class="field"><label>Motif</label><input name="reason" required></div>
        <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <button class="btn-z" type="submit">Enregistrer l’ajustement</button>
    </form>
    <form class="panel" method="POST" action="{{ $user->status->value === 'active' ? route('admin.users.block', $user) : route('admin.users.unblock', $user) }}">
        @csrf
        <h2>{{ $user->status->value === 'active' ? 'Bloquer le compte' : 'Débloquer le compte' }}</h2>
        <div class="field"><label>Motif</label><input name="reason" required></div>
        <button class="btn-z {{ $user->status->value === 'active' ? 'danger' : 'ok' }}" type="submit">{{ $user->status->value === 'active' ? 'Bloquer le compte' : 'Débloquer le compte' }}</button>
    </form>
    <form class="panel" method="POST" action="{{ route('admin.users.password', $user) }}">
        @csrf
        <h2>Mot de passe</h2>
        <p class="muted">Un mot de passe temporaire sera affiché une seule fois. Le client devra le changer.</p>
        <button class="btn-z-ghost" type="submit">Réinitialiser</button>
    </form>
</div>
<form class="panel" method="POST" action="{{ route('admin.users.kyc', $user) }}" style="margin-top:1rem;">
    @csrf
    <h2>KYC</h2>
    <div class="field">
        <label>Statut</label>
        <select name="kyc_status">
            @foreach (\App\Enums\KycStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected($user->kyc_status === $status)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Note</label><input name="kyc_note" value="{{ $user->kyc_note }}"></div>
    <button class="btn-z" type="submit">Mettre à jour le KYC</button>
</form>
<h2>Historique des opérations</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Statut</th><th>Description</th></tr></thead>
        <tbody>
        @foreach ($transactions as $entry)
            <tr>
                <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $entry->type->label() }}</td>
                <td>{{ money($entry->amount) }}</td>
                <td>{{ $entry->status->label() }}</td>
                <td>{{ $entry->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<p><a href="{{ route('admin.transactions.index', ['q' => $user->phone]) }}">Tout l’historique</a></p>
<h2>Journal d’activité</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Action</th><th>Ancien</th><th>Nouveau</th><th>Delta</th><th>Motif</th></tr></thead>
        <tbody>
        @foreach ($audits as $log)
            <tr>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->old_amount === null ? '—' : money($log->old_amount) }}</td>
                <td>{{ $log->new_amount === null ? '—' : money($log->new_amount) }}</td>
                <td>{{ $log->delta_amount === null ? '—' : money($log->delta_amount) }}</td>
                <td>{{ $log->reason }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
