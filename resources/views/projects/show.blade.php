@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', $project->name.' · ZELVORA')
@section('content')
<article class="project-detail">
    <p class="kicker">{{ $project->category }}</p>
    <h1>{{ $project->name }}</h1>
    <p class="place">{{ $project->location }}</p>
    <div class="detail-badges">
        @include('partials.status', ['status' => $project->status])
        @if ($project->is_demo)<span class="badge-z tone-warn">Démonstration</span>@endif
    </div>
    <figure class="detail-hero">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="Visuel de démonstration pour {{ $project->name }}">
        @endif
    </figure>
    @if ($project->is_demo)
        <p class="demo-label">Image illustrative. Elle ne représente pas un actif détenu par ZELVORA.</p>
    @endif
    <p class="detail-copy">{{ $project->description }}</p>

    <section class="detail-block">
        <h2>Opportunité</h2>
        <div class="grid-3">
            <div class="stat"><span>Montant total</span><strong class="money sm">{{ money($project->target_amount) }}</strong></div>
            <div class="stat"><span>Déjà financé</span><strong class="money sm">{{ money($project->funded_amount) }}</strong></div>
            <div class="stat"><span>Restant</span><strong class="money sm">{{ money($project->remainingAmount()) }}</strong></div>
        </div>
        <div class="progress" style="margin:.9rem 0 .4rem;"><span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span></div>
        <p>Progression {{ str_replace('.', ',', $project->progressPercent()) }} %</p>
    </section>

    <section class="detail-block">
        <h2>Investissement</h2>
        <div class="grid-3">
            <div class="stat"><span>Minimum</span><strong class="money sm">{{ money($project->min_investment) }}</strong></div>
            <div class="stat"><span>Durée</span><strong class="money sm">{{ $project->duration_days }} jours</strong></div>
            <div class="stat"><span>Rendement</span><strong class="money sm">Prévisionnel</strong></div>
        </div>
        <div class="note">Estimation affichée : {{ number_format((float) $project->expected_return_percent, 2, ',', ' ') }} % sur la durée. {{ $project->distribution_frequency->label() }}. {{ $project->economic_terms }}</div>
    </section>

    <section class="detail-block panel" id="investir">
        <h2>Votre investissement</h2>
        @auth
            @if ($project->isInvestable())
                <form method="POST" action="{{ route('investments.store', $project) }}" id="invest-form">
                    @csrf
                    <div class="field">
                        <label for="amount">Montant à investir</label>
                        <input id="amount" name="amount" inputmode="decimal" value="{{ old('amount', $project->min_investment) }}" required>
                    </div>
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
                    <button class="btn-z full" type="button" id="preview-invest">Investir maintenant</button>
                    <div id="invest-preview" class="confirm-box" @if ($errors->any()) @else hidden @endif>
                        <h3>Résumé avant confirmation</h3>
                        <p>Montant investi <strong id="sum-amount">—</strong></p>
                        <p>Frais éventuels <strong>0,00 $</strong></p>
                        <p>Capital investi <strong id="sum-capital">—</strong></p>
                        <p class="muted">Aucun frais n’est prélevé sur l’investissement. Le montant quitte le solde disponible et finance ce projet. Le rendement prévu n’est pas crédité.</p>
                        <p class="muted">Conditions : minimum {{ money($project->min_investment) }}, durée {{ $project->duration_days }} jours, rendement prévisionnel selon les conditions du projet.</p>
                        <button class="btn-z gold full" type="submit">Confirmer l'investissement</button>
                    </div>
                </form>
            @else
                <p class="muted">Ce projet n’accepte pas de nouvel apport.</p>
            @endif
        @else
            <p>Connectez-vous pour investir depuis votre solde disponible.</p>
            <a class="btn-z" href="{{ route('login', ['next' => '/projets/'.$project->slug]) }}">Investir maintenant</a>
        @endauth
    </section>
</article>
@if ($mine->isNotEmpty())
    <h2>Vos apports sur ce projet</h2>
    @foreach ($mine as $investment)
        <p><a href="{{ route('investments.show', $investment) }}">{{ money($investment->amount) }} · @include('partials.status', ['status' => $investment->status])</a></p>
    @endforeach
@endif
@auth
<script>
const input = document.getElementById('amount');
const preview = document.getElementById('invest-preview');
const button = document.getElementById('preview-invest');
const format = (value) => {
    const amount = Number(String(value).replace(/\s/g, '').replace(',', '.'));
    if (!Number.isFinite(amount)) return '—';
    return amount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $';
};
const paint = () => {
    const label = format(input.value);
    document.getElementById('sum-amount').textContent = label;
    document.getElementById('sum-capital').textContent = label;
};
button?.addEventListener('click', () => {
    if (!input.reportValidity()) return;
    paint();
    preview.hidden = false;
    preview.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'nearest' });
});
input?.addEventListener('input', () => { if (!preview.hidden) paint(); });
if (preview && !preview.hidden) paint();
</script>
@endauth
@endsection
