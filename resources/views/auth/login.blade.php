@extends('layouts.public')
@section('title', 'Connexion · ZELVORA')
@section('content')
<div class="grid-2">
    <div>
        <div class="kicker">Connexion</div>
        <h1 style="font-size:3rem;">Revenir à votre portefeuille.</h1>
        <p class="lede">Utilisez le numéro de téléphone enregistré. La vérification par SMS pourra être ajoutée plus tard, sans changer l’identifiant.</p>
    </div>
    <form class="panel" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="field">
            <label for="phone">Numéro de téléphone</label>
            <input id="phone" name="phone" inputmode="tel" autocomplete="username" value="{{ old('phone') }}" placeholder="0812345678" required>
        </div>
        <div class="field">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <label style="display:flex;gap:.5rem;align-items:center;font-weight:500;"><input type="checkbox" name="remember" value="1" style="width:auto;"> Rester connecté</label>
        <button class="btn-z full" type="submit" style="margin-top:1rem;">Se connecter</button>
        <p class="muted">Pas encore de compte ? <a href="{{ route('register') }}">Inscription</a></p>
    </form>
</div>
@endsection
