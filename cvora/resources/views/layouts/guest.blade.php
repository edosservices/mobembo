<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'CVora') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-mist font-sans text-ink antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('home') }}"><x-logo /></a>
            <div class="mt-6 w-full max-w-md rounded-2xl bg-white p-6 shadow-sm">
                {{ $slot }}
            </div>
            <p class="mt-4 max-w-md text-center text-xs text-slate-500">La connexion par téléphone pourra être ajoutée plus tard. Le numéro est déjà prévu sur le compte, sans simuler d’envoi de code.</p>
        </div>
    </body>
</html>
