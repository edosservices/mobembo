<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration · ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside id="admin-nav" class="side">
        <a class="brand admin-brand" href="{{ route('admin.dashboard') }}">
            <img class="brand-logo" src="{{ asset('images/logo.png') }}" alt="ZELVORA">
            <span>Administration</span>
        </a>
        <nav>
            @foreach ([
                'admin.dashboard' => 'Dashboard',
                'admin.users.index' => 'Utilisateurs',
                'admin.deposits.index' => 'Dépôts',
                'admin.withdrawals.index' => 'Retraits',
                'admin.investments.index' => 'Investissements',
                'admin.projects.index' => 'Projets immobiliers',
                'admin.referrals.index' => 'Parrainage',
                'admin.adjustments.index' => 'Bonus et ajustements',
                'admin.transactions.index' => 'Transactions',
                'admin.notifications.create' => 'Notifications',
                'admin.settings.edit' => 'Paramètres',
                'admin.audit.index' => 'Audit',
                'admin.support' => 'Support clients',
            ] as $route => $label)
                <a href="{{ route($route) }}" @class(['active' => request()->routeIs($route) || request()->routeIs(preg_replace('/\.(index|edit|create)$/', '.*', $route))])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="side-foot">
            <a href="{{ route('dashboard') }}">Retour à l'espace client</a>
            <a href="{{ route('profile.edit') }}"><span>Profil administrateur</span>{{ auth()->user()->name }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
        </div>
    </aside>
    <button class="admin-backdrop" id="admin-backdrop" type="button" aria-label="Fermer le menu"></button>
    <div class="admin-main">
        <header class="admin-top">
            <button class="corner-btn" type="button" id="admin-menu" aria-controls="admin-nav" aria-expanded="false">Menu</button>
            <strong>Administration</strong>
        </header>
        @include('partials.alerts')
        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const adminMenu = document.getElementById('admin-menu');
    const adminNav = document.getElementById('admin-nav');
    const adminBackdrop = document.getElementById('admin-backdrop');
    const setAdminNav = (open) => {
        adminNav?.classList.toggle('is-open', open);
        adminBackdrop?.classList.toggle('is-open', open);
        adminMenu?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    adminMenu?.addEventListener('click', () => setAdminNav(!adminNav.classList.contains('is-open')));
    adminBackdrop?.addEventListener('click', () => setAdminNav(false));
    adminNav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setAdminNav(false)));
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((element) => {
        bootstrap.Dropdown.getOrCreateInstance(element, {
            autoClose: 'outside',
            popperConfig(config) {
                return { ...config, strategy: 'fixed' };
            },
        });
    });
</script>
</body>
</html>
