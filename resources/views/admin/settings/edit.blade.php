@extends('layouts.admin')
@section('content')
<h1 class="serif">Paramètres</h1>
<form class="panel" method="POST" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <h2>Pourcentages</h2>
    <p class="muted">Ces taux s’appliquent aux prochaines opérations. Ils ne modifient ni les soldes ni les investissements déjà enregistrés.</p>
    <h2>Frais de retrait</h2>
    <div class="grid-2">
        <div class="field"><label>Pourcentage des frais de retrait</label><input name="withdrawal_fee_percent" value="{{ old('withdrawal_fee_percent', $settings->withdrawal_fee_percent) }}" required></div>
        <div class="field"><label>Montant fixe</label><input name="withdrawal_fee_fixed" value="{{ old('withdrawal_fee_fixed', $settings->withdrawal_fee_fixed) }}" required></div>
        <div class="field"><label>Minimum</label><input name="withdrawal_min" value="{{ old('withdrawal_min', $settings->withdrawal_min) }}" required></div>
        <div class="field"><label>Maximum</label><input name="withdrawal_max" value="{{ old('withdrawal_max', $settings->withdrawal_max) }}" required></div>
    </div>
    <h2>Parrainage</h2>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="referral_enabled" value="1" style="width:auto;" @checked(old('referral_enabled', $settings->referral_enabled))> Commissions activées</label>
    <div class="field" style="margin-top:.7rem;">
        <label>Opération qui déclenche la commission</label>
        <select name="referral_trigger">
            @foreach (\App\Enums\ReferralTrigger::cases() as $trigger)
                <option value="{{ $trigger->value }}" @selected(old('referral_trigger', $settings->referral_trigger->value) === $trigger->value)>{{ $trigger->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label>Commission de parrainage (%)</label><input name="referral_rate_percent" value="{{ old('referral_rate_percent', $settings->referral_rate_percent) }}" required></div>
    <h2>Niveaux</h2>
    <p class="muted">Le niveau dépend du nombre de membres actifs : un compte ouvert avec au moins un dépôt approuvé. Une inscription seule ne change pas le niveau.</p>
    <div class="grid-2">
        @foreach (['starter' => 'STARTER', 'pro' => 'PRO', 'elite' => 'ELITE', 'vip' => 'VIP'] as $key => $label)
            @php($current = collect($levels)->firstWhere('key', $key))
            <div class="field"><label>Nom affiché · {{ $label }}</label><input name="level_name_{{ $key }}" value="{{ old('level_name_'.$key, $current['name'] ?? $label) }}" required></div>
            <div class="field"><label>Membres actifs minimum · {{ $label }}</label><input name="level_{{ $key }}" value="{{ old('level_'.$key, $current['min_active'] ?? 0) }}" required></div>
            <div class="field"><label>Avantages · {{ $label }}</label><textarea name="benefit_{{ $key }}" rows="3">{{ old('benefit_'.$key, implode("\n", $current['benefits'] ?? config('zelvora.level_benefits.'.$key, []))) }}</textarea></div>
        @endforeach
    </div>
    <p class="muted">Le rendement du projet se modifie dans Projets.</p>
    <h2>Numéros de réception</h2>
    <p class="muted">Chaque moyen a un nom et un numéro. Le client les voit dans la fenêtre de dépôt, après avoir choisi son moyen de paiement.</p>
    <div class="grid-2">
        <div class="field"><label>Nom du compte M-Pesa</label><input name="mpesa_holder" value="{{ old('mpesa_holder', $settings->mpesa_holder) }}"></div>
        <div class="field"><label>Numéro M-Pesa</label><input name="mpesa_number" value="{{ old('mpesa_number', $settings->mpesa_number) }}"></div>
        <div class="field"><label>Nom du compte Airtel Money</label><input name="airtel_holder" value="{{ old('airtel_holder', $settings->airtel_holder) }}"></div>
        <div class="field"><label>Numéro Airtel Money</label><input name="airtel_number" value="{{ old('airtel_number', $settings->airtel_number) }}"></div>
        <div class="field"><label>Nom du compte Orange Money</label><input name="orange_holder" value="{{ old('orange_holder', $settings->orange_holder) }}"></div>
        <div class="field"><label>Numéro Orange Money</label><input name="orange_number" value="{{ old('orange_number', $settings->orange_number) }}"></div>
    </div>
    <h2>Conformité</h2>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="kyc_required_for_withdrawal" value="1" style="width:auto;" @checked($settings->kyc_required_for_withdrawal)> Exiger un KYC vérifié avant tout retrait</label>
    <label style="display:flex;gap:.5rem;align-items:center;margin-top:.6rem;"><input type="checkbox" name="otp_enabled" value="1" style="width:auto;" @checked($settings->otp_enabled)> Exiger un code SMS à la connexion</label>
    <div class="field" style="margin-top:.8rem;"><label>Mention légale</label><textarea name="legal_disclaimer" required>{{ old('legal_disclaimer', $settings->legal_disclaimer) }}</textarea></div>
    <h2>Groupes WhatsApp et Telegram</h2>
    <p class="muted">Ces liens apparaissent dans le pied de page, sur la page contact et dans l’espace client. Laissez un champ vide pour masquer le bouton correspondant.</p>
    <div class="grid-2">
        <div class="field">
            <label for="whatsapp_url">Lien du groupe WhatsApp</label>
            <input id="whatsapp_url" name="whatsapp_url" type="url" inputmode="url" placeholder="https://" value="{{ old('whatsapp_url', $settings->whatsapp_url) }}">
        </div>
        <div class="field">
            <label for="telegram_url">Lien du groupe Telegram</label>
            <input id="telegram_url" name="telegram_url" type="url" inputmode="url" placeholder="https://" value="{{ old('telegram_url', $settings->telegram_url) }}">
        </div>
    </div>
    <button class="btn-z" type="submit">Enregistrer les modifications</button>
</form>
<section class="panel" style="margin-top:1rem;">
    <h2>Ajouter un numéro</h2>
    <p class="muted">Ces numéros s’ajoutent à ceux du formulaire ci-dessus. Ils apparaissent aussi dans la fenêtre du client, selon le moyen choisi.</p>
    <form method="POST" action="{{ route('admin.payments.store') }}">
        @csrf
        <div class="grid-2">
            <div class="field">
                <label for="new-method">Moyen</label>
                <select id="new-method" name="method" required>
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label for="new-name">Nom</label><input id="new-name" name="holder_name" required></div>
            <div class="field"><label for="new-phone">Numéro</label><input id="new-phone" name="phone" required></div>
        </div>
        <button class="btn-z" type="submit">Ajouter le numéro</button>
    </form>
    <div class="member-list" style="margin-top:1rem;">
        @forelse ($destinations as $destination)
            <form class="panel" method="POST" action="{{ route('admin.payments.update', $destination) }}">
                @csrf @method('PUT')
                <div class="grid-2">
                    <div class="field">
                        <label>Moyen</label>
                        <select name="method" required>
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected($destination->method === $method)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>Nom</label><input name="holder_name" value="{{ $destination->holder_name }}" required></div>
                    <div class="field"><label>Numéro</label><input name="phone" value="{{ $destination->phone }}" required></div>
                </div>
                <button class="btn-z small" type="submit">Enregistrer ce numéro</button>
            </form>
            <form method="POST" action="{{ route('admin.payments.destroy', $destination) }}">
                @csrf @method('DELETE')
                <button class="btn-z-ghost small" type="submit">Retirer</button>
            </form>
        @empty
            <p class="muted">Aucun numéro supplémentaire.</p>
        @endforelse
    </div>
</section>
@endsection
