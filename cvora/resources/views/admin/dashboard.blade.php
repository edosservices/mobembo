<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Administration</h1></x-slot>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['Comptes' => $users, 'CV (nombre)' => $resumes, 'Lettres (nombre)' => $letters, 'Paiements confirmés' => $paid] as $label => $value)
            <article class="rounded-2xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">{{ $label }}</p><p class="font-display text-3xl font-semibold">{{ $value }}</p></article>
        @endforeach
    </div>
    <p class="mt-4 text-sm text-slate-600">Générations IA réussies ce mois : {{ $aiCalls }}. Les contenus de CV ne sont pas affichés ici.</p>
    <nav class="mt-4 flex flex-wrap gap-3 text-sm font-semibold text-brand">
        <a href="{{ route('admin.users.index') }}">Comptes</a>
        <a href="{{ route('admin.plans.index') }}">Offres</a>
        <a href="{{ route('admin.templates.index') }}">Modèles</a>
        <a href="{{ route('admin.transactions.index') }}">Transactions</a>
        <a href="{{ route('admin.settings.edit') }}">Réglages</a>
        <a href="{{ route('admin.events.index') }}">Journaux</a>
        <a href="{{ route('admin.messages.index') }}">Messages</a>
    </nav>
</x-app-layout>
