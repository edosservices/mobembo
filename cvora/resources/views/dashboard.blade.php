<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-slate-500">Bonjour</p>
            <h1 class="font-display text-2xl font-semibold">{{ auth()->user()->name }}</h1>
        </div>
    </x-slot>
    <div class="grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">CV enregistrés</p>
            <p class="mt-1 font-display text-3xl font-semibold">{{ $resumeCount }}</p>
        </article>
        <article class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Lettres enregistrées</p>
            <p class="mt-1 font-display text-3xl font-semibold">{{ $letterCount }}</p>
        </article>
        <article class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Offre actuelle</p>
            <p class="mt-1 font-display text-2xl font-semibold">{{ $planName }}</p>
            <p class="text-sm text-slate-500">{{ $credits }} crédit{{ $credits > 1 ? 's' : '' }} IA restant{{ $credits > 1 ? 's' : '' }} ce mois</p>
        </article>
    </div>
    <div class="mt-4 grid gap-4 lg:grid-cols-[220px_1fr]">
        <article class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Progression du profil</p>
            <div class="mx-auto mt-3 grid h-24 w-24 place-items-center rounded-full" style="background: conic-gradient(#2563EB {{ $progress['percent'] }}%, #DBEAFE 0)">
                <div class="grid h-16 w-16 place-items-center rounded-full bg-white font-semibold">{{ $progress['percent'] }}%</div>
            </div>
            @if ($progress['missing'] !== [])
                <ul class="mt-3 space-y-1 text-sm text-slate-600">
                    @foreach ($progress['missing'] as $item)
                        <li>À compléter : {{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm text-slate-600">Les sections suivies sont renseignées.</p>
            @endif
        </article>
        <article class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-semibold">Actions rapides</h2>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('resumes.store') }}">@csrf<button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Créer un CV</button></form>
                <a href="{{ route('resumes.import') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold">Importer</a>
                <a href="{{ route('letters.create') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold">Nouvelle lettre</a>
                <a href="{{ route('assistant.index') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold">Assistant IA</a>
            </div>
            <h2 class="mt-6 font-display text-lg font-semibold">Derniers documents</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @forelse ($resumes as $resume)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <a class="font-medium text-brand" href="{{ route('resumes.edit', $resume) }}">{{ $resume->title }}</a>
                        <span class="text-slate-500">CV · {{ $resume->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Aucun CV pour le moment.</li>
                @endforelse
                @foreach ($letters as $letter)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <a class="font-medium text-brand" href="{{ route('letters.edit', $letter) }}">{{ $letter->title }}</a>
                        <span class="text-slate-500">Lettre · {{ $letter->updated_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    </div>
</x-app-layout>
