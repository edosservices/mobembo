@php($quote = $project->quote())
<article class="card h-100 border-0 shadow-sm overflow-hidden home-plan">
    <a class="home-plan-cover" href="{{ route('projects.show', $project) }}">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="{{ $project->name }}">
        @else
            <span class="home-plan-fallback">{{ $project->location }}</span>
        @endif
    </a>
    <div class="card-body d-flex flex-column">
        <p class="kicker mb-1">{{ $project->location }}</p>
        <h3 class="card-title h4">{{ $project->name }}</h3>
        <ul class="list-unstyled home-plan-facts mb-3">
            <li><span>Minimum</span><strong>{{ money($project->min_investment) }}</strong></li>
            <li><span>Durée</span><strong>{{ $project->duration_days }} jours</strong></li>
            <li><span>Rendement estimé</span><strong>{{ $quote->totalPercentLabel() }}</strong></li>
        </ul>
        <a class="btn btn-dark rounded-pill mt-auto" href="{{ route('projects.show', $project) }}">Consulter le plan</a>
    </div>
</article>
