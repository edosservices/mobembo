<article class="opportunity">
    <a class="cover" href="{{ route('projects.show', $project) }}#investir">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="{{ $project->name }}">
        @endif
    </a>
    <div class="body">
        <span class="badge-z">{{ $project->category }}</span>
        <h2>{{ $project->name }}</h2>
        <p class="from">À partir de</p>
        <p class="price">{{ money($project->min_investment) }}</p>
        <p class="muted">Rendement journalier estimatif</p>
        <p>{{ \App\Support\ReturnEstimator::percentLabel($project->dailyReturnPercent()) }}</p>
        <p class="muted">Durée</p>
        <p>{{ $project->duration_days }} jours</p>
        <div class="progress" role="img" aria-label="{{ str_replace('.', ',', $project->progressPercent()) }} % financé">
            <span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span>
        </div>
        <p class="funded-line">Progression {{ str_replace('.', ',', $project->progressPercent()) }} %</p>
        <a class="btn-z full" href="{{ route('projects.show', $project) }}#investir">Investir</a>
    </div>
</article>
