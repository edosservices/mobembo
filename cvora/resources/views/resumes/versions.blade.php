<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Historique — {{ $resume->title }}</h1></x-slot>
    @if (! $allowed)
        <p class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm">L’historique des versions est réservé à une offre qui l’inclut. Les versions déjà prises restent stockées.</p>
    @endif
    <ul class="space-y-3">
        @forelse ($versions as $version)
            <li class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm">
                <div>
                    <p class="font-medium">{{ $version->label ?: 'Version' }} · {{ $version->source }}</p>
                    <p class="text-sm text-slate-500">{{ $version->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                </div>
                @if ($allowed)
                    <form method="POST" action="{{ route('resumes.restore', [$resume, $version]) }}">@csrf<button class="text-sm font-semibold text-brand">Restaurer</button></form>
                @endif
            </li>
        @empty
            <li class="text-sm text-slate-600">Aucune version enregistrée. Utilisez « Enregistrer une version » dans l’éditeur.</li>
        @endforelse
    </ul>
</x-app-layout>
