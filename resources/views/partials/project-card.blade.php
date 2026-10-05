<article class="opportunity">
    <a class="cover" href="{{ route('projects.show', $project) }}">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="Visuel de démonstration pour {{ $project->name }}">
        @endif
    </a>
    <div class="body">
        <div class="card-badges">
            <span class="badge-z">{{ $project->category }}</span>
            @include('partials.status', ['status' => $project->status])
        </div>
        <h2>{{ $project->name }}</h2>
        <p class="place">{{ $project->location }}</p>
        @if ($project->is_demo)
            <p class="demo-label">Visuel et données de démonstration</p>
        @endif
        <p class="from">À partir de</p>
        <p class="price">{{ money($project->min_investment) }}</p>
        <div class="card-facts">
            <div>
                <span>Rendement prévu</span>
                <strong>Selon les conditions du projet</strong>
            </div>
            <div>
                <span>Durée</span>
                <strong>{{ $project->duration_days }} jours</strong>
            </div>
        </div>
        <div class="progress" role="img" aria-label="{{ str_replace('.', ',', $project->progressPercent()) }} % financé">
            <span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span>
        </div>
        <p class="funded-line">{{ str_replace('.', ',', $project->progressPercent()) }} % financé</p>
        <a class="btn-z full" href="{{ route('projects.show', $project) }}">Voir le projet</a>
    </div>
</article>
