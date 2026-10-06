@extends('layouts.auth')
@section('title', 'Inscription · ZELVORA')
@section('content')
<p class="kicker">ZELVORA</p>
<h1>Créer un compte</h1>
<p class="lede">Un numéro de téléphone suffit. Ajoutez un code de parrainage si vous en avez un.</p>
<form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field"><label for="name">Nom complet</label><input id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label for="phone">Numéro de téléphone</label><input id="phone" name="phone" inputmode="tel" value="{{ old('phone') }}" placeholder="0812345678" required></div>
        <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" required></div>
        <div class="field"><label for="password_confirmation">Confirmation</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
        <div class="field"><label for="referral_code">Code de parrainage, facultatif</label><input id="referral_code" name="referral_code" value="{{ old('referral_code', request('ref')) }}" placeholder="ZLV00000"></div>
        <button class="btn btn-dark btn-lg rounded-pill w-100" type="submit">Créer mon compte</button>
        <button class="btn btn-outline-dark rounded-pill w-100 mt-2" type="button" id="install-reopen">Télécharger l'APK</button>
        <a class="btn btn-link w-100 mt-2" href="{{ route('login') }}">Déjà un compte ? Se connecter</a>
    </form>
<dialog id="install-dialog">
    <h2>Télécharger l'APK</h2>
    <button class="btn-z full" type="button" id="install-android">Télécharger l'APK</button>
    <div id="install-steps" hidden>
        <ol>
            <li>Ouvrez le menu du navigateur.</li>
            <li>Choisissez Installer l'application, ou Ajouter à l'écran d'accueil.</li>
            <li>Confirmez. L'icône ZELVORA apparaît sur l'écran.</li>
        </ol>
    </div>
    <button class="btn-z-ghost full" type="button" id="install-later">Plus tard</button>
</dialog>
<script>
    const installDialog = document.getElementById('install-dialog');
    const installSteps = document.getElementById('install-steps');
    let deferredInstall = null;
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstall = event;
    });
    const openInstall = () => installDialog?.showModal();
    const rememberLater = () => localStorage.setItem('zelvora-install-later', '1');
    document.getElementById('install-reopen')?.addEventListener('click', openInstall);
    document.getElementById('install-later')?.addEventListener('click', () => {
        rememberLater();
        installDialog.close();
    });
    document.getElementById('install-android')?.addEventListener('click', async () => {
        if (!deferredInstall) {
            installSteps.hidden = false;
            return;
        }
        try {
            deferredInstall.prompt();
            const choice = await deferredInstall.userChoice;
            deferredInstall = null;
            if (choice.outcome === 'accepted') {
                localStorage.setItem('zelvora-install', 'done');
                installDialog.close();
                return;
            }
        } catch (error) {
            deferredInstall = null;
        }
        installSteps.hidden = false;
    });
    const installed = window.matchMedia('(display-mode: standalone)').matches || localStorage.getItem('zelvora-install') === 'done';
    if (!installed && localStorage.getItem('zelvora-install-later') !== '1') {
        openInstall();
    }
</script>
@endsection
