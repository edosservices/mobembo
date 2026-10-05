@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', $project->name.' · ZELVORA')
@section('content')
<article class="project-detail">
    <p class="kicker">{{ $project->category }}</p>
    <h1>{{ $project->name }}</h1>
    <p class="place">Localisation · {{ $project->location }}</p>
    <div class="detail-badges">
        @include('partials.status', ['status' => $project->status])
    </div>
    <figure class="detail-hero">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="{{ $project->name }}">
        @endif
    </figure>
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
            <div class="stat"><span>Rendement journalier estimatif</span><strong class="money sm">{{ \App\Support\ReturnEstimator::percentLabel($project->dailyReturnPercent()) }}</strong></div>
        </div>
        <div class="note">{{ number_format((float) $project->expected_return_percent, 2, ',', ' ') }} % sur la durée du projet. Ce taux journalier est une estimation. Il n’est pas crédité. {{ $project->economic_terms }}</div>
    </section>

    <section class="detail-block panel" id="investir">
        <h2>Votre investissement</h2>
        @guest
            <a class="btn-z" href="{{ route('login', ['next' => '/projets/'.$project->slug.'#investir']) }}">Investir</a>
        @else
            @if (! $project->isInvestable())
                <p class="muted">Ce projet n’accepte pas de nouvel apport.</p>
            @elseif (! $canFundMinimum)
                <h3>Solde insuffisant</h3>
                <p>Votre solde : {{ money($available) }}</p>
                <p>Minimum requis : {{ money($project->min_investment) }}</p>
                <a class="btn-z" href="{{ route('deposits.create') }}">Faire un dépôt</a>
            @else
                <form method="POST" action="{{ route('investments.preview', $project) }}">
                    @csrf
                    <div class="field">
                        <label for="amount">Montant à investir</label>
                        <input id="amount" name="amount" inputmode="decimal" value="{{ old('amount', $preview['amount'] ?? $project->min_investment) }}" required>
                    </div>
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $preview['idempotency_key'] ?? (string) \Illuminate\Support\Str::uuid()) }}">
                    <button class="btn-z full" type="submit">Investir</button>
                </form>
                @if ($preview)
                    <div class="confirm-box">
                        <h3>Résumé avant confirmation</h3>
                        <p>Montant <strong>{{ money($preview['amount']) }}</strong></p>
                        <p>Projet <strong>{{ $project->name }}</strong></p>
                        <p>Rendement journalier estimatif <strong>{{ \App\Support\ReturnEstimator::percentLabel($preview['daily_percent']) }}</strong></p>
                        <p>Durée <strong>{{ $preview['duration_days'] }} jours</strong></p>
                        <p>Date de début <strong>{{ \Illuminate\Support\Carbon::parse($preview['starts_at'])->format('d/m/Y') }}</strong></p>
                        <p>Date de fin <strong>{{ \Illuminate\Support\Carbon::parse($preview['ends_at'])->format('d/m/Y') }}</strong></p>
                        <p>Revenu journalier estimatif <strong>{{ \App\Support\ReturnEstimator::amountLabel($preview['daily_amount']) }}</strong></p>
                        <p>Revenu total estimatif <strong>{{ money($preview['total']) }}</strong></p>
                        <p>Frais <strong>{{ money($preview['fee']) }}</strong></p>
                        <p class="muted">Ces revenus sont des estimations. Ils ne sont pas ajoutés au solde.</p>
                        <form method="POST" action="{{ route('investments.store', $project) }}">
                            @csrf
                            <input type="hidden" name="amount" value="{{ $preview['amount'] }}">
                            <input type="hidden" name="idempotency_key" value="{{ $preview['idempotency_key'] }}">
                            <button class="btn-z gold full" type="submit">Confirmer l'investissement</button>
                        </form>
                    </div>
                @endif
            @endif
        @endguest
    </section>
</article>
@if ($mine->isNotEmpty())
    <h2>Vos apports sur ce projet</h2>
    @foreach ($mine as $investment)
        <p><a href="{{ route('investments.show', $investment) }}">{{ money($investment->amount) }} · @include('partials.status', ['status' => $investment->status])</a></p>
    @endforeach
@endif
@endsection
