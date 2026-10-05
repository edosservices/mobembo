@extends('layouts.app')
@section('title', 'Projets · ZELVORA')
@section('content')
<div class="kicker">Marketplace</div>
<h1 style="font-size:2.4rem;">Projets immobiliers</h1>
<div class="grid-3">
    @foreach ($projects as $project)
        @include('partials.project-card', ['project' => $project])
    @endforeach
</div>
<div style="margin-top:1rem;">{{ $projects->links() }}</div>
@endsection
