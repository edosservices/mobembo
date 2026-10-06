@extends('layouts.auth')
@section('title', 'Connexion · ZELVORA')
@section('content')
<p class="kicker">ZELVORA</p>
<h1>Connexion</h1>
<p class="lede">Entrez le numéro et le mot de passe de votre compte.</p>
<form method="POST" action="{{ route('login') }}" id="login-form">
    @csrf
    <div class="field">
        <label for="phone">Numéro de téléphone</label>
        <input id="phone" name="phone" inputmode="tel" autocomplete="username" value="{{ old('phone') }}" placeholder="0812345678" required>
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <div class="auth-password">
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="button" data-password-toggle aria-controls="password" aria-pressed="false">Afficher</button>
        </div>
    </div>
    <label class="auth-remember"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
    <button class="btn btn-dark btn-lg rounded-pill w-100" type="submit">Se connecter</button>
    <a class="btn btn-outline-dark rounded-pill w-100 mt-2" href="{{ route('register') }}">Créer un compte</a>
</form>
@endsection
@section('scripts')
<script>
    document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
        const input = document.getElementById(event.currentTarget.getAttribute('aria-controls'));
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        event.currentTarget.textContent = visible ? 'Afficher' : 'Masquer';
        event.currentTarget.setAttribute('aria-pressed', visible ? 'false' : 'true');
    });
    document.getElementById('login-form')?.addEventListener('submit', (event) => {
        const button = event.currentTarget.querySelector('[type="submit"]');
        button.disabled = true;
        button.textContent = 'Connexion…';
    });
</script>
@endsection
