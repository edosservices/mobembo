@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.projects.index') }}">Projets</a></p>
<h1 class="serif">{{ $project->exists ? 'Modifier le plan' : 'Nouveau plan' }}</h1>
<p class="muted">Tous les champs du plan se règlent ici. Les investissements déjà ouverts conservent leur taux et leur capital.</p>
<form class="panel plan-form" method="POST" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($project->exists) @method('PUT') @endif
    <h2>Identité</h2>
    <div class="grid-2">
        <div class="field"><label>Nom</label><input name="name" value="{{ old('name', $project->name) }}" required></div>
        <div class="field"><label>Slogan</label><input name="slogan" value="{{ old('slogan', $project->slogan) }}" maxlength="180" placeholder="Construisez votre avenir financier"></div>
        <div class="field"><label>Adresse publique</label><input name="slug" value="{{ old('slug', $project->slug) }}" placeholder="residence-gombe"></div>
        <div class="field"><label>Localisation</label><input name="location" value="{{ old('location', $project->location) }}" required></div>
        <div class="field"><label>Catégorie</label><input name="category" value="{{ old('category', $project->category) }}" placeholder="Résidentiel" required></div>
        <div class="field">
            <label>Statut</label>
            <select name="status">
                @foreach (\App\Enums\ProjectStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Devise</label><input name="currency" value="{{ old('currency', $project->currency ?: 'USD') }}" maxlength="8" required></div>
    </div>
    <h2>Montants et durée</h2>
    <div class="grid-2">
        <div class="field"><label>Objectif</label><input name="target_amount" value="{{ old('target_amount', $project->target_amount) }}" required></div>
        <div class="field"><label>Investissement minimum</label><input name="min_investment" value="{{ old('min_investment', $project->min_investment) }}" required></div>
        <div class="field"><label>Montant maximum</label><input name="max_investment" value="{{ old('max_investment', $project->max_investment) }}" inputmode="decimal" placeholder="1500.00"></div>
        <div class="field"><label>Durée (jours)</label><input name="duration_days" value="{{ old('duration_days', $project->duration_days) }}" required></div>
        <div class="field">
            <label>Plan ouvert aux nouvelles positions</label>
            <select name="is_active">
                <option value="1" @selected(old('is_active', $project->exists ? ($project->is_active ? '1' : '0') : '1') == '1')>Actif</option>
                <option value="0" @selected(old('is_active', $project->exists ? ($project->is_active ? '1' : '0') : '1') == '0')>Inactif</option>
            </select>
        </div>
        <div class="field">
            <label>Rendement du plan (%)</label>
            <input name="expected_return_percent" value="{{ old('expected_return_percent', $project->expected_return_percent) }}" required>
        </div>
        <div class="field">
            <label>Rythme de distribution</label>
            <select name="distribution_frequency">
                @foreach (\App\Enums\DistributionFrequency::cases() as $frequency)
                    <option value="{{ $frequency->value }}" @selected(old('distribution_frequency', $project->distribution_frequency?->value ?? 'at_maturity') === $frequency->value)>{{ $frequency->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field"><label>Prochaine distribution prévue</label><input type="date" name="next_distribution_on" value="{{ old('next_distribution_on', optional($project->next_distribution_on)->format('Y-m-d')) }}"></div>
        <div class="field"><label>Début</label><input type="date" name="starts_at" value="{{ old('starts_at', optional($project->starts_at)->format('Y-m-d')) }}"></div>
        <div class="field"><label>Fin</label><input type="date" name="ends_at" value="{{ old('ends_at', optional($project->ends_at)->format('Y-m-d')) }}"></div>
    </div>
    @if ($project->exists)
        <div class="grid-2">
            <div class="stat"><span>Déjà financé, calculé par les investissements</span><strong>{{ money($project->funded_amount) }}</strong></div>
            <div class="stat"><span>Restant</span><strong>{{ money($project->remainingAmount()) }}</strong></div>
        </div>
    @endif
    <h2>Textes visibles par le client</h2>
    <div class="field"><label>Description</label><textarea name="description" required>{{ old('description', $project->description) }}</textarea></div>
    <div class="field"><label>Règles économiques</label><textarea name="economic_terms" required>{{ old('economic_terms', $project->economic_terms) }}</textarea></div>
    @if ($project->imageUrl())
        <p><img id="plan-image-current" src="{{ $project->imageUrl() }}" alt="" style="width:min(100%, 220px);border-radius:0.8rem;"></p>
    @endif
    <div class="field"><label>Image</label><input id="plan-image" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
    <img id="plan-image-preview" alt="" hidden style="width:min(100%, 220px);border-radius:0.8rem;margin-bottom:1rem;">

    @php($previewCapital = \App\Support\Money::of(old('preview_capital', '100')))
    @php($previewDays = max(1, (int) old('duration_days', $project->duration_days ?: 15)))
    @php($previewYield = old('expected_return_percent', $project->expected_return_percent ?: '30'))
    @php($previewStart = now()->startOfDay())
    @php($previewOpenDays = \App\Support\BusinessCalendar::scheduledProfitDays($previewStart, $previewStart->copy()->addDays($previewDays)))
    @php($previewGain = \App\Support\PlanMath::totalGain($previewCapital, $previewYield))
    @php($previewDaily = \App\Support\PlanMath::ordinaryDaily($previewGain, $previewOpenDays))
    <h2>Aperçu du calcul</h2>
    <p class="fine-print">Cet aperçu est indicatif. Le serveur recalcule le gain, les jours ouvrés et l’échéance au moment de l’investissement, puis les fige sur la position.</p>
    <div class="field"><label>Montant exemple</label><input id="plan-preview-capital" value="{{ $previewCapital }}" inputmode="decimal"></div>
    <div class="panel" id="plan-preview" style="display:grid;gap:0.35rem;">
        <p>Durée <strong data-preview="days">{{ $previewDays }} jours</strong></p>
        <p>Rendement <strong data-preview="yield">{{ str_replace('.', ',', \App\Support\ReturnEstimator::durationPercent($previewYield)) }} %</strong></p>
        <p>Gain total <strong data-preview="gain">{{ money($previewGain) }}</strong></p>
        <p>Jours ouvrés <strong data-preview="open-days">{{ $previewOpenDays }}</strong></p>
        <p>Gain journalier <strong data-preview="daily">{{ money($previewDaily) }}</strong></p>
        <p>Capital à l’échéance <strong data-preview="capital">{{ money($previewCapital) }}</strong></p>
        <p>Valeur totale <strong data-preview="economic">{{ money(\App\Support\PlanMath::economicTotal($previewCapital, $previewGain)) }}</strong></p>
    </div>
    <button class="btn-z" type="submit">Enregistrer le plan</button>
</form>
<script>
    (function () {
        const form = document.querySelector('.plan-form');
        if (!form) return;
        const money = (value) => {
            const negative = value < 0;
            const absolute = Math.abs(value);
            const cents = Math.round((absolute + Number.EPSILON) * 100);
            const whole = Math.floor(cents / 100);
            const fraction = String(cents % 100).padStart(2, '0');
            return (negative ? '−' : '') + whole.toLocaleString('fr-FR') + ',' + fraction + ' $';
        };
        const weekdayCount = (duration) => {
            const start = new Date();
            start.setHours(0, 0, 0, 0);
            let count = 0;
            for (let offset = 0; offset < duration; offset += 1) {
                const day = new Date(start.getTime());
                day.setDate(start.getDate() + offset);
                const iso = day.getDay() === 0 ? 7 : day.getDay();
                if (iso >= 1 && iso <= 5) count += 1;
            }
            return count;
        };
        const read = (name) => form.querySelector('[name="' + name + '"]');
        const paint = () => {
            const capital = Number(String(document.getElementById('plan-preview-capital').value).replace(',', '.')) || 0;
            const duration = Math.max(0, parseInt(read('duration_days').value || '0', 10));
            const yieldPercent = Number(String(read('expected_return_percent').value).replace(',', '.')) || 0;
            const openDays = weekdayCount(duration);
            const gain = Math.round((capital * yieldPercent / 100) * 100) / 100;
            const daily = openDays > 0 ? Math.round((gain / openDays) * 100) / 100 : 0;
            const set = (key, value) => {
                const node = form.querySelector('[data-preview="' + key + '"]');
                if (node) node.textContent = value;
            };
            set('days', duration + ' jours');
            set('yield', yieldPercent.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %');
            set('gain', money(gain));
            set('open-days', String(openDays));
            set('daily', money(daily));
            set('capital', money(capital));
            set('economic', money(Math.round((capital + gain) * 100) / 100));
        };
        ['duration_days', 'expected_return_percent', 'min_investment', 'max_investment'].forEach((name) => {
            read(name)?.addEventListener('input', paint);
        });
        document.getElementById('plan-preview-capital')?.addEventListener('input', paint);
        document.getElementById('plan-image')?.addEventListener('change', (event) => {
            const file = event.target.files && event.target.files[0];
            const preview = document.getElementById('plan-image-preview');
            if (!file || !preview) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    }());
</script>
@endsection