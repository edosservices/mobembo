@extends('layouts.app')
@section('title', 'Déposer')
@section('content')
<h1 style="font-size:2.1rem;">Déposer</h1>
<p class="fine-print">Indiquez le montant, choisissez le moyen, envoyez l’argent au numéro affiché, puis joignez la preuve. Le solde est crédité après validation.</p>
<form class="panel" method="POST" action="{{ route('deposits.store') }}" enctype="multipart/form-data" id="deposit-form">
    @csrf
    <div class="field"><label for="amount">Montant</label><input id="amount" name="amount" inputmode="decimal" value="{{ old('amount') }}" required></div>
    <fieldset class="method-picks">
        <legend>Moyen de paiement</legend>
        @foreach (\App\Enums\PaymentMethod::cases() as $method)
            <label>
                <input type="radio" name="method" value="{{ $method->value }}" @checked(old('method', \App\Enums\PaymentMethod::Mpesa->value) === $method->value) required>
                <span>{{ $method->label() }}</span>
            </label>
        @endforeach
    </fieldset>
    <button class="btn-z" type="button" id="show-number">Voir le numéro</button>
    <dialog id="pay-dialog">
        <p class="kicker">Envoi</p>
        <h2>Envoyez l’argent à ce numéro</h2>
        <div id="pay-targets"></div>
        <div class="dialog-actions">
            <button class="btn-z" type="button" id="already-sent">J’ai déjà envoyé</button>
            <button class="btn-z-ghost" type="button" id="close-pay">Fermer</button>
        </div>
    </dialog>
    <div id="proof-step" @if (! $errors->any()) hidden @endif>
        <div class="field"><label for="reference">Référence de la transaction</label><input id="reference" name="reference" value="{{ old('reference') }}" required></div>
        <div class="field"><label for="proof">Preuve de paiement</label><input id="proof" name="proof" type="file" accept="image/*" required></div>
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
        <button class="btn-z" type="submit">Envoyer</button>
    </div>
</form>
<h2>Vos dépôts</h2>
<div class="txn-list">
    @forelse ($deposits as $deposit)
        <article class="txn">
            <strong>{{ money($deposit->amount) }}</strong>
            <span>{{ $deposit->method->label() }}</span>
            <time>{{ $deposit->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</time>
            <em>{{ $deposit->status->label() }}</em>
            @if ($deposit->rejection_reason)
                <span class="desc">{{ $deposit->rejection_reason }}</span>
            @endif
        </article>
    @empty
        <p class="muted">Aucun dépôt.</p>
    @endforelse
</div>
<script>
    const destinations = @json($destinations);
    const dialog = document.getElementById('pay-dialog');
    const targets = document.getElementById('pay-targets');
    const proof = document.getElementById('proof-step');

    function selectedMethod() {
        return document.querySelector('input[name="method"]:checked')?.value;
    }

    function renderTargets() {
        const rows = destinations[selectedMethod()] || [];
        targets.replaceChildren();
        if (rows.length === 0) {
            const empty = document.createElement('p');
            empty.textContent = 'Aucun numéro n’est encore indiqué pour ce moyen.';
            targets.appendChild(empty);
            return;
        }
        rows.forEach((row) => {
            const card = document.createElement('article');
            card.className = 'pay-target';
            if (row.name) {
                const name = document.createElement('strong');
                name.textContent = row.name;
                card.appendChild(name);
            }
            const phone = document.createElement('p');
            phone.className = 'ref-code';
            phone.textContent = row.phone;
            card.appendChild(phone);
            const copy = document.createElement('button');
            copy.type = 'button';
            copy.className = 'btn-z-ghost small';
            copy.textContent = 'Copier le numéro';
            copy.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(row.phone);
                    copy.textContent = 'Numéro copié';
                } catch (error) {
                    copy.textContent = row.phone;
                }
            });
            card.appendChild(copy);
            targets.appendChild(card);
        });
    }

    document.getElementById('show-number').addEventListener('click', () => {
        if (!document.getElementById('amount').reportValidity()) return;
        renderTargets();
        dialog.showModal();
    });
    document.getElementById('close-pay').addEventListener('click', () => dialog.close());
    document.getElementById('already-sent').addEventListener('click', () => {
        if ((destinations[selectedMethod()] || []).length === 0) return;
        proof.hidden = false;
        dialog.close();
        document.getElementById('proof').focus();
    });
</script>
@endsection