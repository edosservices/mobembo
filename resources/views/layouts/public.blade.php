<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ route('home') }}">
            <span class="mark">Z</span>
            <span><strong>ZELVORA</strong><small>Votre avenir prend la valeur.</small></span>
        </a>
        <nav class="nav-links">
            <a href="{{ route('home') }}">Accueil</a>
            <a href="{{ route('legal') }}">Mentions</a>
            @auth
                <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">Espace</a>
            @else
                <a href="{{ route('login') }}">Connexion</a>
                <a class="btn-z small" href="{{ route('register') }}">Ouvrir un compte</a>
            @endauth
        </nav>
    </header>
    <main class="wrap page">
        @include('partials.alerts')
        @yield('content')
    </main>
    <footer class="site wrap">Les rendements affichés sont des estimations non garanties. ZELVORA n’est pas une banque. <a href="{{ route('legal') }}">Lire les mentions</a>.</footer>
</body>
</html>
