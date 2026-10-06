<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ZELVORA')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    @include('partials.pwa')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/zelvora.css') }}">
</head>
<body class="auth-body">
    <div class="container-fluid g-0">
        <div class="row g-0 min-vh-100">
            <div class="col-12 col-lg-6 auth-visual" style="background-image: url('{{ asset('images/hero.jpg') }}')">
                <div class="auth-visual-copy">
                    <img class="auth-logo" src="{{ asset('images/logo.png') }}" alt="ZELVORA">
                    <p>L’immobilier, suivi depuis votre espace.</p>
                </div>
            </div>
            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center auth-stage">
                <div class="auth-panel w-100">
                    @include('partials.alerts')
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    @yield('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset('sw.js') }}');
        }
    </script>
</body>
</html>
