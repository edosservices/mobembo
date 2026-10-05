<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body>
<div class="admin-shell">
    <aside class="side">
        <a class="brand" href="{{ route('admin.dashboard') }}" style="margin-bottom:1rem;"><span class="mark">Z</span><span><strong>ZELVORA</strong><small style="color:#b9c0cc;">Administration</small></span></a>
        @foreach ([
            'admin.dashboard' => 'Dashboard',
            'admin.users.index' => 'Utilisateurs',
            'admin.projects.index' => 'Projets',
            'admin.investments.index' => 'Investissements',
            'admin.deposits.index' => 'Dépôts',
            'admin.withdrawals.index' => 'Retraits',
            'admin.referrals.index' => 'Parrainage',
            'admin.transactions.index' => 'Transactions',
            'admin.notifications.create' => 'Notifications',
            'admin.audit.index' => 'Audit',
            'admin.settings.edit' => 'Paramètres',
        ] as $route => $label)
            <a href="{{ route($route) }}" @class(['active' => request()->routeIs(str_replace('.index', '.*', str_replace('.edit', '.*', str_replace('.create', '.*', $route)))) || request()->routeIs($route)])>{{ $label }}</a>
        @endforeach
        <a href="{{ route('dashboard') }}">Espace client</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-z-ghost small" type="submit" style="margin-top:.6rem;color:white;border-color:#445;">Sortir</button></form>
    </aside>
    <main class="admin-main">
        @include('partials.alerts')
        @yield('content')
    </main>
</div>
</body>
</html>
