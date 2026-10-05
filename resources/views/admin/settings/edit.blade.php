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
    <div class="field"><label>Pourcentage de commission</label><input name="referral_rate_percent" value="{{ old('referral_rate_percent', $settings->referral_rate_percent) }}" required></div>
    <p class="muted">Le rendement prévu de chaque plan se modifie dans Projets.</p>
    <h2>Numéros de réception</h2>
    <p class="muted">Laissez vide tant que le numéro n’est pas attribué. La page de dépôt n’affiche que les numéros enregistrés.</p>
    <div class="grid-2">
        <div class="field"><label>M-Pesa</label><input name="mpesa_number" value="{{ old('mpesa_number', $settings->mpesa_number) }}"></div>
        <div class="field"><label>Airtel Money</label><input name="airtel_number" value="{{ old('airtel_number', $settings->airtel_number) }}"></div>
        <div class="field"><label>Orange Money</label><input name="orange_number" value="{{ old('orange_number', $settings->orange_number) }}"></div>
    </div>
    <h2>Conformité</h2>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="kyc_required_for_withdrawal" value="1" style="width:auto;" @checked($settings->kyc_required_for_withdrawal)> Exiger un KYC vérifié avant tout retrait</label>
    <label style="display:flex;gap:.5rem;align-items:center;margin-top:.6rem;"><input type="checkbox" name="otp_enabled" value="1" style="width:auto;" @checked($settings->otp_enabled)> Exiger un code SMS à la connexion</label>
    <div class="field" style="margin-top:.8rem;"><label>Mention légale</label><textarea name="legal_disclaimer" required>{{ old('legal_disclaimer', $settings->legal_disclaimer) }}</textarea></div>
    <button class="btn-z" type="submit">Enregistrer</button>
</form>
@endsection
