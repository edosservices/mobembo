@php($quote = $project->quote())
<article class="opportunity plan-card h-100" data-plan-card="{{ $project->slug }}">
    <a class="cover" href="{{ route('projects.show', $project) }}#investir">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="{{ $project->name }}">
        @endif
    </a>
    <div class="body text-center">
        <span class="badge-z">{{ $project->category }}</span>
        <h2>{{ $project->name }}</h2>
        <div class="row row-cols-2 g-2 plan-facts">
            <div class="col">
                <div class="fact">
                    <span>À partir de</span>
                    <strong>{{ money($project->min_investment) }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Durée</span>
                    <strong>{{ $project->duration_days }} jours</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Rendement estimatif</span>
                    <strong>{{ $quote->totalPercentLabel() }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Rendement journalier estimatif</span>
                    <strong data-daily-percent>{{ \App\Support\ReturnEstimator::percentLabel($quote->dailyPercent) }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Gain estimatif / jour</span>
                    <strong data-daily-amount>{{ \App\Support\ReturnEstimator::amountLabel($quote->dailyAmount) }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Gain estimatif total</span>
                    <strong data-total-return>{{ money($quote->totalReturn) }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Capital investi</span>
                    <strong>{{ money($quote->capital) }}</strong>
                </div>
            </div>
            <div class="col">
                <div class="fact">
                    <span>Total estimatif à l'échéance</span>
                    <strong data-maturity>{{ money($quote->maturity) }}</strong>
                </div>
            </div>
        </div>
        <div class="progress" role="img" aria-label="{{ str_replace('.', ',', $project->progressPercent()) }} % financé">
            <span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span>
        </div>
        <p class="funded-line">Progression {{ str_replace('.', ',', $project->progressPercent()) }} %</p>
        <a class="btn-z full" href="{{ route('projects.show', $project) }}#investir">Investir</a>
    </div>
</article>
