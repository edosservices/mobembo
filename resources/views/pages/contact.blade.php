@extends('layouts.public')
@section('title', 'Contact · ZELVORA')
@section('content')
<p class="kicker">Contact</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Écrire à ZELVORA</h1>
<div class="panel">
    <p>Les demandes liées à un compte se font après connexion, depuis le profil et les opérations déjà enregistrées.</p>
    <p>Les numéros de réception Mobile Money apparaissent sur la page de dépôt uniquement lorsqu’ils ont été enregistrés dans l’administration. ZELVORA n’affiche pas de numéro fictif.</p>
    <p><a class="btn-z" href="{{ route('register') }}">Créer un compte</a></p>
</div>
@endsection
