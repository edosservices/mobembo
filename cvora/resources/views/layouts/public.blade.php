<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="CVora aide à créer, améliorer et actualiser un CV et une lettre de motivation.">
        <title>{{ $title ?? 'CVora' }} — Votre talent mérite le meilleur CV.</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-white font-sans text-ink antialiased">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('home') }}" class="shrink-0"><x-logo /></a>
                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex" aria-label="Navigation principale">
                    <a class="hover:text-brand" href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a>
                    <a class="hover:text-brand" href="{{ route('templates') }}">Modèles</a>
                    <a class="hover:text-brand" href="{{ route('pricing') }}">Tarifs</a>
                    <a class="hover:text-brand" href="{{ route('resources') }}">Ressources</a>
                </nav>
                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 text-sm font-medium text-slate-700 hover:bg-mist">Tableau de bord</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-full px-3 py-2 text-sm font-medium text-slate-700 hover:bg-mist sm:inline">Connexion</a>
                    @endauth
                    <a href="{{ route('register') }}" class="rounded-full bg-brand px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-light sm:px-4">Créer mon CV gratuitement</a>
                </div>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="border-t border-slate-200 bg-ink text-slate-200">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <x-logo tone="dark" />
                    <p class="mt-3 text-sm text-slate-300">Votre talent mérite le meilleur CV.</p>
                </div>
                <div class="text-sm">
                    <p class="font-display font-semibold text-white">Produit</p>
                    <ul class="mt-3 space-y-2">
                        <li><a class="hover:text-white" href="{{ route('home') }}#fonctionnalites">Fonctionnalités</a></li>
                        <li><a class="hover:text-white" href="{{ route('templates') }}">Modèles</a></li>
                        <li><a class="hover:text-white" href="{{ route('pricing') }}">Tarifs</a></li>
                    </ul>
                </div>
                <div class="text-sm">
                    <p class="font-display font-semibold text-white">Informations</p>
                    <ul class="mt-3 space-y-2">
                        <li><a class="hover:text-white" href="{{ route('resources') }}">Ressources</a></li>
                        <li><a class="hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                        <li><a class="hover:text-white" href="{{ route('privacy') }}">Confidentialité</a></li>
                        <li><a class="hover:text-white" href="{{ route('terms') }}">Conditions d’utilisation</a></li>
                        <li><a class="hover:text-white" href="{{ route('legal') }}">Mentions légales</a></li>
                    </ul>
                </div>
                <div class="text-sm">
                    <p class="font-display font-semibold text-white">Contact</p>
                    <p class="mt-3 text-slate-300">{{ \App\Models\Setting::getValue('contact.email', 'contact@cvora.app') }}</p>
                    <p class="mt-2 text-slate-400">Conçu pour le marché francophone, avec une attention particulière pour la RDC.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
