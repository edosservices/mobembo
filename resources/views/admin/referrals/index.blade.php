@extends('layouts.admin')
@section('title', 'Parrainage')
@section('content')
<h1 class="serif">Parrainage</h1>
<p class="muted">La commission est le taux de la plateforme. Elle ne change pas selon le niveau. Les prochains calculs utilisent les valeurs enregistrées ici.</p>
<form class="panel" method="POST" action="{{ route('admin.referrals.update') }}">
    @csrf @method('PUT')
    <h2>Paramètres de parrainage</h2>
    <label style="display:flex;gap:.5rem;align-items:center;"><input type="checkbox" name="referral_enabled" value="1" style="width:auto;" @checked(old('referral_enabled', $settings->referral_enabled))> Commissions activées</label>
    <div class="field" style="margin-top:.7rem;">
        <label for="referral_trigger">Opération qui déclenche la commission</label>
        <select id="referral_trigger" name="referral_trigger">
            @foreach (\App\Enums\ReferralTrigger::cases() as $trigger)
                <option value="{{ $trigger->value }}" @selected(old('referral_trigger', $settings->referral_trigger->value) === $trigger->value)>{{ $trigger->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="referral_rate_percent">Commission de parrainage (%)</label><input id="referral_rate_percent" name="referral_rate_percent" value="{{ old('referral_rate_percent', $settings->referral_rate_percent) }}" required></div>
    <h2>Niveaux</h2>
    <p class="muted">Un membre actif a un compte ouvert et au moins un dépôt approuvé. Chaque niveau doit demander plus de membres que le précédent.</p>
    <div class="grid-2">
        @foreach (['starter' => 'STARTER', 'pro' => 'PRO', 'elite' => 'ELITE', 'vip' => 'VIP'] as $key => $label)
            @php($current = collect($levels)->firstWhere('key', $key))
            <div class="field"><label>Nom affiché · {{ $label }}</label><input name="level_name_{{ $key }}" value="{{ old('level_name_'.$key, $current['name'] ?? $label) }}" required></div>
            <div class="field"><label>Membres actifs minimum · {{ $label }}</label><input name="level_{{ $key }}" value="{{ old('level_'.$key, $current['min_active'] ?? 0) }}" required></div>
            <div class="field"><label>Avantages · {{ $label }}</label><textarea name="benefit_{{ $key }}" rows="3">{{ old('benefit_'.$key, implode("\n", $current['benefits'] ?? config('zelvora.level_benefits.'.$key, []))) }}</textarea></div>
        @endforeach
    </div>
    <button class="btn-z" type="submit">Enregistrer les paramètres</button>
</form>
<div class="dash-stats">
    <div class="stat"><span>Parrains</span><strong>{{ $sponsors }}</strong></div>
    <div class="stat"><span>Filleuls</span><strong>{{ $members }}</strong></div>
    <div class="stat"><span>Commissions distribuées</span><strong>{{ money($paid) }}</strong></div>
    <div class="stat"><span>Bonus distribués</span><strong>{{ money($bonus) }}</strong></div>
    <div class="stat"><span>Taux actuel</span><strong>{{ str_replace('.', ',', bcadd((string) $rate, '0', 2)) }} %</strong></div>
</div>
<h2>Niveaux configurés</h2>
<div class="dash-stats">
    @foreach ($levels as $level)
        <article class="panel">
            <strong>{{ $level['name'] }}</strong>
            <p>Membres actifs requis : {{ $level['min_active'] }}</p>
            <p>Commission : {{ str_replace('.', ',', bcadd((string) $rate, '0', 2)) }} %</p>
            <ul>
                @foreach ($level['benefits'] ?? ($benefits[$level['key']] ?? []) as $benefit)
                    <li>{{ $benefit }}</li>
                @endforeach
            </ul>
        </article>
    @endforeach
</div>
<h2>Parrains les plus actifs</h2>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Nom</th><th>Commissions</th></tr></thead>
        <tbody>
        @forelse ($top as $user)
            <tr><td>{{ $user->name }}</td><td>{{ money($user->commissions_total ?? 0) }}</td></tr>
        @empty
            <tr><td colspan="2">Aucune commission enregistrée.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<h2>Commissions</h2>
<div class="table-responsive panel">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead><tr><th>Date</th><th>Parrain</th><th>Filleul</th><th>Déclencheur</th><th>Base</th><th>Commission</th></tr></thead>
        <tbody>
        @foreach ($commissions as $commission)
            <tr>
                <td>{{ $commission->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $commission->referrer->name }}</td>
                <td>{{ $commission->referred->name }}</td>
                <td>{{ $commission->trigger->label() }}</td>
                <td>{{ money($commission->base_amount) }}</td>
                <td>{{ money($commission->amount) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $commissions->links() }}
@endsection
