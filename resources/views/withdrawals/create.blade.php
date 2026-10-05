@extends('layouts.app')
@section('title', 'Retirer')
@section('content')
<h1 style="font-size:2.4rem;">Retirer</h1>
<p class="muted">Frais : {{ number_format((float) $settings->withdrawal_fee_percent, 2, ',', ' ') }} % + {{ money($settings->withdrawal_fee_fixed) }}. Minimum {{ money($settings->withdrawal_min) }}, maximum {{ money($settings->withdrawal_max) }}.</p>
@if ($settings->kyc_required_for_withdrawal)
    <p class="fine-print">Une vérification d’identité est demandée avant un retrait. <a href="{{ route('kyc.edit') }}">Envoyer un document</a>.</p>
@endif
<form class="panel" method="POST" action="{{ route('withdrawals.store') }}">
    @csrf
    <div class="field"><label for="amount">Montant demandé</label><input id="amount" name="amount" inputmode="decimal" value="{{ old('amount', request('amount')) }}" required></div>
    <div class="field">
        <label for="method">Moyen de réception</label>
        <select id="method" name="method">
            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="phone">Numéro de réception</label><input id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}" required></div>
    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
    <div class="grid-3">
        <div class="stat"><span>Montant demandé</span><strong id="gross">—</strong></div>
        <div class="stat"><span>Frais</span><strong id="fee">—</strong></div>
        <div class="stat"><span>Montant net</span><strong id="net">—</strong></div>
    </div>
    <p class="fine-print">Les frais et le montant net sont confirmés au moment de votre demande.</p>
    <button class="btn-z" type="submit">Demander le retrait</button>
</form>
<h2>Demandes récentes</h2>
<div class="table-wrap panel">
    <table>
        <thead><tr><th>Date</th><th>Demandé</th><th>Frais</th><th>Net</th><th>Statut</th></tr></thead>
        <tbody>
        @forelse ($recent as $withdrawal)
            <tr>
                <td>{{ $withdrawal->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ money($withdrawal->amount) }}</td>
                <td>{{ money($withdrawal->fee) }}</td>
                <td>{{ money($withdrawal->net_amount) }}</td>
                <td>@include('partials.status', ['status' => $withdrawal->status])</td>
            </tr>
        @empty
            <tr><td colspan="5">Aucune demande.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<script>
const percent = {{ (float) $settings->withdrawal_fee_percent }};
const fixed = {{ (float) $settings->withdrawal_fee_fixed }};
const amount = document.getElementById('amount');
const fmt = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $';
const paint = () => {
    const gross = parseFloat(String(amount.value).replace(',', '.')) || 0;
    const fee = fixed + gross * percent / 100;
    document.getElementById('gross').textContent = fmt(gross);
    document.getElementById('fee').textContent = fmt(fee);
    document.getElementById('net').textContent = fmt(Math.max(0, gross - fee));
};
amount.addEventListener('input', paint);
paint();
</script>
@endsection
