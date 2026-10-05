<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="@yield('body-class', 'site-public')">
    @include('partials.support-banner')
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="ZELVORA, investissement immobilier">
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
        <nav id="site-nav" class="nav-links">
            <a href="{{ route('home') }}">Accueil</a>
            <a href="{{ route('projects.index') }}">Projets</a>
            <a href="{{ route('home') }}#comment-ca-marche">Comment ça marche</a>
            <a href="{{ route('faq') }}">FAQ</a>
            @auth
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">Espace</a>
            @else
                <a href="{{ route('login') }}">Connexion</a>
                <a class="btn-z small" href="{{ route('register') }}">Commencer</a>
            @endauth
        </nav>
    </header>
    <main class="@yield('main-class', 'wrap page')">
        <div class="@yield('alerts-class', '')">
            @include('partials.alerts')
        </div>
        @yield('content')
    </main>
    @include('partials.site-footer')
    <script>
        document.querySelector('.nav-toggle')?.addEventListener('click', (event) => {
            const nav = document.getElementById('site-nav');
            const open = nav.classList.toggle('is-open');
            event.currentTarget.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.reveal').forEach((element) => {
            if (reduce) {
                element.classList.add('is-in');
                return;
            }
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.16 });
            observer.observe(element);
        });
        document.querySelectorAll('[data-count]').forEach((element) => {
            const target = Number(element.dataset.count);
            if (!Number.isFinite(target)) return;
            if (reduce) {
                element.textContent = target.toLocaleString('fr-FR') + (element.dataset.suffix || '');
                return;
            }
            const started = performance.now();
            const tick = (now) => {
                const progress = Math.min(1, (now - started) / 700);
                const value = Math.round(target * progress);
                element.textContent = value.toLocaleString('fr-FR') + (element.dataset.suffix || '');
                if (progress < 1) requestAnimationFrame(tick);
            };
            const start = () => requestAnimationFrame(tick);
            const box = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    start();
                    box.disconnect();
                }
            }, { threshold: 0.4 });
            box.observe(element);
        });
    </script>
</body>
</html>
