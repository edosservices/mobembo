@extends('layouts.app')
@section('title', 'Déposer')
@section('content')
<h1 style="font-size:2.4rem;">Déposer</h1>
<p class="note">Le montant reste « en attente ». Il n’est ajouté au solde disponible qu’après vérification de la preuve.</p>
<div class="panel">
    <h2>Numéro ZELVORA</h2>
    @if ($settings->mpesa_number || $settings->airtel_number || $settings->orange_number)
        <ul>
            @if ($settings->mpesa_number)<li>M-Pesa : {{ $settings->mpesa_number }}</li>@endif
            @if ($settings->airtel_number)<li>Airtel Money : {{ $settings->airtel_number }}</li>@endif
            @if ($settings->orange_number)<li>Orange Money : {{ $settings->orange_number }}</li>@endif
        </ul>
    @else
        <p class="muted">Le numéro de réception s’affiche ici. S’il est absent, contactez l’administration avant d’envoyer les fonds.</p>
    @endif
</div>
<form class="panel" method="POST" action="{{ route('deposits.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="field"><label for="amount">Montant</label><input id="amount" name="amount" inputmode="decimal" value="{{ old('amount') }}" required></div>
    <div class="field">
        <label for="method">Moyen de paiement</label>
        <select id="method" name="method">
            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="reference">Référence de transaction</label><input id="reference" name="reference" value="{{ old('reference') }}" required></div>
    <div class="field"><label for="proof">Preuve de paiement</label><input id="proof" name="proof" type="file" accept="image/*,.pdf" required></div>
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
    <button class="btn-z" type="submit">Envoyer la demande</button>
</form>
<h2>Vos dépôts</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Montant</th><th>Moyen</th><th>Référence</th><th>Statut</th></tr></thead>
        <tbody>
        @forelse ($deposits as $deposit)
            <tr>
                <td>{{ $deposit->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ money($deposit->amount) }}</td>
                <td>{{ $deposit->method->label() }}</td>
                <td>{{ $deposit->reference }}</td>
                <td>@include('partials.status', ['status' => $deposit->status]) @if($deposit->rejection_reason)<div class="muted">{{ $deposit->rejection_reason }}</div>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun dépôt.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
