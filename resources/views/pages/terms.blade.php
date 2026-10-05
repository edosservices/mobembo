@extends('layouts.public')
@section('title', 'Conditions · ZELVORA')
@section('content')
<p class="kicker">Conditions</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Conditions d’utilisation</h1>
<div class="panel">
    <p>L’accès à ZELVORA se fait avec un compte actif. Votre numéro de téléphone est l’identifiant du compte.</p>
    <p>Les informations affichées sur un projet, y compris le rendement prévu, sont indicatives et dépendent des conditions de ce projet.</p>
    <p>Un investissement est confirmé depuis votre espace personnel. Les dépôts et les retraits sont traités selon les conditions applicables à votre compte.</p>
    <p>Les revenus crédités apparaissent dans votre portefeuille après leur distribution. <a href="{{ route('legal') }}">Lire les mentions</a>.</p>
</div>
@endsection
