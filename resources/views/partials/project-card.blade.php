<article class="project-card">
    <div class="cover">
        @if ($project->imageUrl())
            <img src="{{ $project->imageUrl() }}" alt="">
        @endif
        <span class="chip">{{ $project->location }}</span>
    </div>
    <div class="body">
        <div class="meta">
            <span>{{ $project->category }}</span>
            @include('partials.status', ['status' => $project->status])
        </div>
        <h2 class="serif" style="font-size:1.35rem;margin:0 0 .4rem;">{{ $project->name }}</h2>
        @if ($project->is_demo)
            <div class="chip">Démonstration</div>
        @endif
        <p class="muted">Objectif {{ money($project->target_amount) }} · Financé {{ money($project->funded_amount) }}</p>
        <div class="progress" aria-hidden="true"><span style="width: {{ min(100, (float) $project->progressPercent()) }}%"></span></div>
        <div class="meta"><span>Progression {{ str_replace('.', ',', $project->progressPercent()) }} %</span><span>Min. {{ money($project->min_investment) }}</span></div>
        <p class="muted">Durée {{ $project->duration_days }} jours · Rendement prévu {{ rtrim(rtrim(number_format((float) $project->expected_return_percent, 2, ',', ' '), '0'), ',') }} % sur la durée, estimé et non garanti.</p>
        @auth
            <a class="btn-z full" href="{{ route('projects.show', $project) }}">Investir</a>
        @else
            <a class="btn-z full" href="{{ route('register') }}">Créer un compte pour investir</a>
        @endauth
    </div>
</article>
