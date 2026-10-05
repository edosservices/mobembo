@extends('layouts.public')
@section('title', 'ZELVORA — Votre avenir prend la valeur.')
@section('content')
<section class="hero">
    <div>
        <div class="kicker">Immobilier · RDC</div>
        <h1>Votre avenir prend la valeur.</h1>
        <p class="lede">ZELVORA permet de déposer des fonds, de financer des projets immobiliers et de suivre chaque mouvement. Les rendements affichés sont des prévisions. Ils ne sont crédités que lorsqu’une distribution réelle est enregistrée.</p>
        <div class="actions">
            <a class="btn-z" href="{{ route('register') }}">Ouvrir un compte</a>
            <a class="btn-z-ghost" href="{{ route('login') }}">Se connecter</a>
        </div>
    </div>
    <div class="panel">
        <div class="kicker">Comment ça se passe</div>
        <div class="steps" style="margin-top:1rem;">
            <div class="step"><span class="num">1</span><div><strong>Compte par téléphone</strong><div class="muted">Nom, numéro RDC et mot de passe. L’e-mail n’est pas demandé.</div></div></div>
            <div class="step"><span class="num">2</span><div><strong>Dépôt vérifié</strong><div class="muted">M-Pesa, Airtel Money ou Orange Money. Le solde bouge après validation.</div></div></div>
            <div class="step"><span class="num">3</span><div><strong>Projet immobilier</strong><div class="muted">Vous choisissez un projet et le montant part de votre solde disponible.</div></div></div>
            <div class="step"><span class="num">4</span><div><strong>Retrait avec frais clairs</strong><div class="muted">La demande reste en attente jusqu’à l’approbation d’un administrateur.</div></div></div>
        </div>
    </div>
</section>
<section style="margin-top:1.5rem;">
    <h2>Projets ouverts</h2>
    <div class="grid-3">
        @forelse ($projects as $project)
            @include('partials.project-card', ['project' => $project])
        @empty
            <p class="muted">Aucun projet ouvert pour le moment.</p>
        @endforelse
    </div>
</section>
<section class="note" style="margin-top:1.2rem;">{{ $disclaimer }}</section>
@endsection
