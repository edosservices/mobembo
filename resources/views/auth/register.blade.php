@extends('layouts.public')
@section('title', 'Inscription · ZELVORA')
@section('content')
<div class="grid-2">
    <div>
        <div class="kicker">Inscription</div>
        <h1 style="font-size:3rem;">Un numéro suffit pour commencer.</h1>
        <p class="lede">Créez votre espace avec votre numéro de téléphone. Ajoutez un code de parrainage si vous en avez un.</p>
    </div>
    <form class="panel" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field"><label for="name">Nom complet</label><input id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label for="phone">Numéro de téléphone</label><input id="phone" name="phone" inputmode="tel" value="{{ old('phone') }}" placeholder="0812345678" required></div>
        <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" required></div>
        <div class="field"><label for="password_confirmation">Confirmation</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
        <div class="field"><label for="referral_code">Code de parrainage, facultatif</label><input id="referral_code" name="referral_code" value="{{ old('referral_code', request('ref')) }}" placeholder="ZLV00000"></div>
        <button class="btn-z full" type="submit">Créer mon compte</button>
        <button class="btn-z-ghost full" type="button" id="install-reopen">Télécharger pour Android</button>
    </form>
</div>
<dialog id="install-dialog">
    <p class="kicker">Android</p>
    <h2>Téléchargez l'APK pour Android</h2>
    <p>Ajoutez ZELVORA à votre écran d'accueil. Il n'y a pas encore d'application officielle : votre téléphone peut l'installer depuis cette page.</p>
    <button class="btn-z full" type="button" id="install-android">Installer sur l'écran d'accueil</button>
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
