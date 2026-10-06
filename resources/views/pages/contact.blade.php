@extends('layouts.public')
@section('title', 'Contact · ZELVORA')
@section('content')
<p class="kicker">Contact</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Écrire à ZELVORA</h1>
<div class="panel">
    <p>Pour une question sur votre compte, connectez-vous puis écrivez depuis votre espace personnel.</p>
    <p>Les numéros M-Pesa, Airtel Money et Orange Money sont indiqués sur la page de dépôt.</p>
    <p><a class="btn-z" href="{{ route('register') }}">Commencer à investir</a></p>
    @include('partials.community-links')
</div>
@endsection
