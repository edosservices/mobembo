@extends(auth()->check() ? 'layouts.app' : 'layouts.public')
@section('title', 'Projets · ZELVORA')
@section('content')
<div class="kicker">Opportunités</div>
<h1 class="text-center" style="font-size:clamp(2rem, 5vw, 3.2rem);">Nos opportunités</h1>
<p class="lede text-center mx-auto">Chaque projet indique son minimum, sa durée et son rendement estimatif. Choisissez celui qui vous convient.</p>
<div class="row row-cols-1 row-cols-lg-2 g-4" style="margin-top:1.4rem;">
    @forelse ($projects as $project)
        <div class="col d-flex">
            @include('partials.project-card', ['project' => $project])
        </div>
    @empty
        <p class="muted">De nouvelles opportunités seront publiées ici.</p>
    @endforelse
</div>
<div style="margin-top:1.2rem;">{{ $projects->links() }}</div>
@endsection
