<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'CVora') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mist font-sans text-ink antialiased">
        <div x-data="{ open: false }" class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
            <aside class="border-b border-slate-200 bg-white lg:min-h-screen lg:border-b-0 lg:border-r">
                <div class="flex items-center justify-between px-4 py-4">
                    <a href="{{ route('dashboard') }}"><x-logo /></a>
                    <button type="button" class="rounded-lg p-2 text-slate-600 lg:hidden" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-label="Ouvrir le menu">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 5h14v2H3V5Zm0 4h14v2H3V9Zm0 4h14v2H3v-2Z"/></svg>
                    </button>
                </div>
                <nav class="space-y-1 px-3 pb-4" :class="open ? 'block' : 'hidden lg:block'" aria-label="Espace personnel">
                    @php
                        $links = [
                            ['dashboard', 'Vue d’ensemble', 'dashboard'],
                            ['resumes.index', 'Mes CV', 'resumes.*'],
                            ['resumes.import', 'Importer un CV', 'resumes.import'],
                            ['letters.index', 'Lettres de motivation', 'letters.*'],
                            ['assistant.index', 'Assistant IA', 'assistant.*'],
                            ['applications.index', 'Mes candidatures', 'applications.*'],
                            ['offers.index', 'Offres d’emploi', 'offers.*'],
                            ['templates', 'Modèles', 'templates'],
                            ['profile.edit', 'Mon profil', 'profile.*'],
                            ['billing.index', 'Abonnement', 'billing.*'],
                            ['settings.edit', 'Paramètres', 'settings.*'],
                        ];
                    @endphp
                    @foreach ($links as [$route, $label, $active])
                        <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs($active) ? 'bg-brand-soft text-brand' : 'text-slate-600 hover:bg-mist' }}">{{ $label }}</a>
                    @endforeach
                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-brand-soft text-brand' : 'text-slate-600 hover:bg-mist' }}">Administration</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="pt-2">
                        @csrf
                        <button class="w-full rounded-xl px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-mist">Déconnexion</button>
                    </form>
                </nav>
            </aside>
            <div class="min-w-0">
                @isset($header)
                    <header class="border-b border-slate-200 bg-white">
                        <div class="px-4 py-5 sm:px-6">{{ $header }}</div>
                    </header>
                @endisset
                @if (session('status'))
                    <p class="mx-4 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
                @endif
                <main class="px-4 py-6 sm:px-6">{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
