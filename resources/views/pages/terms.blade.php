@extends('layouts.public')
@section('title', 'Conditions · ZELVORA')
@section('content')
<p class="kicker">Conditions</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Conditions d’utilisation</h1>
<div class="panel">
    <p>L’accès à ZELVORA suppose un compte actif. Le téléphone est l’identifiant. Les informations de projet, y compris le rendement prévu, sont données à titre indicatif.</p>
    <p>Un investissement débite le solde disponible et augmente le financement du projet. Il ne crée pas de revenu. Les retraits et les dépôts restent soumis à validation administrative et aux frais affichés avant confirmation.</p>
    <p>Avant une ouverture au public, l’opérateur doit respecter les obligations juridiques et de conformité applicables en RDC. <a href="{{ route('legal') }}">Lire les mentions</a>.</p>
</div>
@endsection
