<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body class="site-app">
    @include('partials.support-banner')
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="mark">Z</span>
            <span><strong>ZELVORA</strong><small>{{ auth()->user()->name }}</small></span>
        </a>
        <nav class="nav-links">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Accueil</a>
            <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*')])>Investir</a>
            <a href="{{ route('investments.index') }}" @class(['active' => request()->routeIs('investments.*')])>Portefeuille</a>
            <a href="{{ route('transactions.index') }}">Transactions</a>
            <a href="{{ route('referral') }}">Parrainage</a>
            <a href="{{ route('notifications.index') }}">Notifications @if($navUnread)<span class="badge-z tone-warn">{{ $navUnread }}</span>@endif</a>
            <a href="{{ route('profile.edit') }}">Profil</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}">Admin</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-z-ghost small" type="submit">Sortir</button></form>
        </nav>
    </header>
    <main class="wrap page">
        @include('partials.alerts')
        @yield('content')
    </main>
    <nav class="bottom-nav">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])><strong>⌂</strong>Accueil</a>
        <a href="{{ route('projects.index') }}" @class(['active' => request()->routeIs('projects.*')])><strong>▣</strong>Investir</a>
        <a href="{{ route('investments.index') }}" @class(['active' => request()->routeIs('investments.*')])><strong>▤</strong>Portefeuille</a>
        <a href="{{ route('transactions.index') }}"><strong>≡</strong>Transactions</a>
        <a href="{{ route('profile.edit') }}"><strong>●</strong>Profil</a>
    </nav>
</body>
</html>
