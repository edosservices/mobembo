@extends('layouts.app')
@section('title', 'Transférer')
@section('content')
<h1 style="font-size:2.2rem;">Transférer</h1>
<p class="muted">Le bénéficiaire reçoit le montant indiqué. Les frais de 2 % sont ajoutés à votre débit. Le capital investi ne peut pas être transféré.</p>
<p class="muted">Solde retirable : {{ money($wallet->available_balance ?? 0) }}.</p>

@if ($quote && $recipient)
    <section class="panel transfer-confirm">
        <h2>Confirmer le transfert</h2>
        <p class="kicker">Bénéficiaire</p>
        <p>Nom : {{ $recipient->name }}</p>
        <p>Téléphone : {{ $recipient->phone }}</p>
        <div class="row g-2">
            <div class="col-6 col-md-3"><div class="stat"><span>Montant</span><strong>{{ money($quote['amount']) }}</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Frais de transfert</span><strong>{{ str_replace('.', ',', $quote['percent']) }} %</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Frais</span><strong>{{ money($quote['fee']) }}</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Total débité</span><strong>{{ money($quote['total']) }}</strong></div></div>
        </div>
        <p class="fine-print">Montant reçu par le bénéficiaire : {{ money($quote['received']) }}.</p>
        <form method="POST" action="{{ route('transfers.store') }}">
            @csrf
            <input type="hidden" name="name" value="{{ $name }}">
            <input type="hidden" name="phone" value="{{ $phone }}">
            <input type="hidden" name="amount" value="{{ $quote['amount'] }}">
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <button class="btn-z" type="submit">Confirmer le transfert</button>
            <a class="btn-z-ghost" href="{{ route('transfers.create') }}">Modifier</a>
        </form>
    </section>
@else
    <form class="panel" method="POST" action="{{ route('transfers.preview') }}">
        @csrf
        <div class="field"><label for="name">Nom du bénéficiaire</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label for="phone">Numéro de téléphone</label><input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" placeholder="0812345678"></div>
        <div class="field"><label for="amount">Montant</label><input id="amount" name="amount" value="{{ old('amount') }}" inputmode="decimal" required></div>
        <div class="row g-2">
            <div class="col-6 col-md-3"><div class="stat"><span>Montant</span><strong id="gross">—</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Frais 2 %</span><strong id="fee">—</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Total débité</span><strong id="total">—</strong></div></div>
            <div class="col-6 col-md-3"><div class="stat"><span>Montant reçu</span><strong id="received">—</strong></div></div>
        </div>
        <p class="fine-print">Cet aperçu est indicatif. Le serveur vérifie le compte et recalcule les frais avant le débit.</p>
        <button class="btn-z" type="submit">Vérifier le bénéficiaire</button>
    </form>
    <script>
        const amount = document.getElementById('amount');
        const fmt = (n) => n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $';
        const paint = () => {
            const gross = parseFloat(String(amount.value).replace(',', '.')) || 0;
            const fee = Math.round(gross * 2) / 100;
            document.getElementById('gross').textContent = fmt(gross);
            document.getElementById('fee').textContent = fmt(fee);
            document.getElementById('total').textContent = fmt(gross + fee);
            document.getElementById('received').textContent = fmt(gross);
        };
        amount.addEventListener('input', paint);
        paint();
    </script>
@endif
@endsection
