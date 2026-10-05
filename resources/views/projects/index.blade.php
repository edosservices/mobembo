@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Projets · ZELVORA')
@section('content')
<div class="kicker">Opportunités</div>
<h1 style="font-size:clamp(2rem, 5vw, 3.2rem);">Nos opportunités</h1>
<p class="lede">Découvrez des projets immobiliers sélectionnés et choisissez celui qui correspond à votre stratégie.</p>
<div class="opportunity-grid" style="margin-top:1.4rem;">
    @forelse ($projects as $project)
        @include('partials.project-card', ['project' => $project])
    @empty
        <p class="muted">De nouvelles opportunités seront publiées ici.</p>
    @endforelse
</div>
<div style="margin-top:1.2rem;">{{ $projects->links() }}</div>
@endsection
