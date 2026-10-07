<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body class="site-app">
    @include('partials.support-banner')
    <header class="topbar">
        <button class="corner-btn" type="button" data-back data-fallback="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) hidden @endif>Retour</button>
        <a class="brand" href="{{ route('dashboard') }}">
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="ZELVORA">
            <span><small>{{ auth()->user()->name }}</small></span>
        </a>
        <button class="corner-btn nav-toggle" type="button" aria-expanded="false" aria-controls="app-nav">Menu</button>
        <nav id="app-nav" class="nav-links">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Accueil</a>
            <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*')])>Investir</a>
            <a href="{{ route('investments.index') }}" @class(['active' => request()->routeIs('investments.*')])>Portefeuille</a>
            <a href="{{ route('transactions.index') }}">Transactions</a>
            <a href="{{ route('referral') }}">Parrainage</a>
            <a href="{{ route('notifications.index') }}" data-live-nav="notifications">Notifications <span class="badge-z tone-warn" data-unread-badge @unless($navUnread) hidden @endunless>{{ $navUnread ?: '' }}</span></a>
            <a href="{{ route('profile.edit') }}">Profil</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}">Admin</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-z-ghost small" type="submit">Sortir</button></form>
        </nav>
    </header>
    <main class="wrap page">
        <p class="live-status" data-live-status>
            <span class="live-dot" aria-hidden="true"></span>
            <span data-live-label>Connexion…</span>
        </p>
        @include('partials.alerts')
        @yield('content')
        @include('partials.community-links')
    </main>
    <nav class="bottom-nav">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])><strong>⌂</strong>Accueil</a>
        <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*')])><strong>▣</strong>Investir</a>
        <a href="{{ route('investments.index') }}" @class(['active' => request()->routeIs('investments.*')])><strong>▤</strong>Portefeuille</a>
        <a href="{{ route('transactions.index') }}" @class(['active' => request()->routeIs('transactions.*')])><strong>≡</strong>Transactions</a>
        <a href="{{ route('profile.edit') }}" @class(['active' => request()->routeIs('profile.*')])><strong>●</strong>Profil</a>
    </nav>
    <div class="toast-container live-toasts" id="live-toasts" aria-live="polite" aria-atomic="true"></div>
    @include('partials.welcome-community-modal')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('[data-back]')?.addEventListener('click', () => {
            const fallback = document.querySelector('[data-back]').dataset.fallback;
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.href = fallback;
        });
        document.querySelector('.nav-toggle')?.addEventListener('click', (event) => {
            const nav = document.getElementById('app-nav');
            const open = nav.classList.toggle('is-open');
            event.currentTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                const value = button.getAttribute('data-copy');
                try {
                    await navigator.clipboard.writeText(value);
                    button.textContent = 'Lien copié';
                } catch (error) {
                    button.textContent = value;
                }
            });
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}');
        }
    </script>
    @include('partials.notification-sound')
    <script>
        const welcome = document.getElementById('welcome-community');
        if (welcome && window.bootstrap && !localStorage.getItem('zelvora_welcome_seen')) {
            window.setTimeout(() => {
                window.bootstrap.Modal.getOrCreateInstance(welcome).show();
            }, 600);
            welcome.addEventListener('hidden.bs.modal', () => {
                localStorage.setItem('zelvora_welcome_seen', '1');
            });
            welcome.querySelectorAll('a[target="_blank"]').forEach((link) => {
                link.addEventListener('click', () => localStorage.setItem('zelvora_welcome_seen', '1'));
            });
        }
    </script>
</body>
</html>
