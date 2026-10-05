@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Projets · ZELVORA')
@section('content')
<div class="kicker">Opportunités</div>
<h1 style="font-size:clamp(2rem, 5vw, 3.2rem);">Projets immobiliers</h1>
<p class="lede">Chaque fiche indique le minimum, la durée, la progression et un rendement prévu. Ce pourcentage n’est pas un versement.</p>
<div class="opportunity-grid" style="margin-top:1.4rem;">
    @forelse ($projects as $project)
        @include('partials.project-card', ['project' => $project])
    @empty
        <p class="muted">Aucun projet publié.</p>
    @endforelse
</div>
<div style="margin-top:1.2rem;">{{ $projects->links() }}</div>
@endsection
