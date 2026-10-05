@php($quote = $project->quote())
<article class="opportunity plan-card" data-plan-card="{{ $project->slug }}">
    <a class="cover" href="{{ route('projects.show', $project) }}#investir">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="{{ $project->name }}">
        @endif
    </a>
    <div class="body">
        <span class="badge-z">{{ $project->category }}</span>
        <h2>{{ $project->name }}</h2>
        <dl class="plan-facts">
            <div><dt>À partir de</dt><dd>{{ money($project->min_investment) }}</dd></div>
            <div><dt>Durée</dt><dd>{{ $project->duration_days }} jours</dd></div>
            <div><dt>Rendement estimatif</dt><dd>{{ $quote->totalPercentLabel() }}</dd></div>
            <div><dt>Rendement journalier estimatif</dt><dd data-daily-percent>{{ \App\Support\ReturnEstimator::percentLabel($quote->dailyPercent) }}</dd></div>
            <div><dt>Gain estimatif / jour</dt><dd data-daily-amount>{{ \App\Support\ReturnEstimator::amountLabel($quote->dailyAmount) }}</dd></div>
            <div><dt>Gain estimatif total</dt><dd data-total-return>{{ money($quote->totalReturn) }}</dd></div>
            <div><dt>Capital investi</dt><dd>{{ money($quote->capital) }}</dd></div>
            <div><dt>Total estimatif à l'échéance</dt><dd data-maturity>{{ money($quote->maturity) }}</dd></div>
        </dl>
        <div class="progress" role="img" aria-label="{{ str_replace('.', ',', $project->progressPercent()) }} % financé">
            <span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span>
        </div>
        <p class="funded-line">Progression {{ str_replace('.', ',', $project->progressPercent()) }} %</p>
        <a class="btn-z full" href="{{ route('projects.show', $project) }}#investir">Investir</a>
    </div>
</article>
