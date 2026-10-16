<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h1 class="font-display text-2xl font-semibold">Mes CV</h1>
            <form method="POST" action="{{ route('resumes.store') }}">@csrf<button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Créer un CV</button></form>
        </div>
    </x-slot>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($resumes as $resume)
            <article class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="font-display text-lg font-semibold">{{ $resume->title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $resume->template?->name ?? 'Moderne' }} · {{ $resume->updated_at?->timezone(config('app.timezone'))->format('d/m/Y') }}</p>
                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <a class="font-semibold text-brand" href="{{ route('resumes.edit', $resume) }}">Modifier</a>
                    <a href="{{ route('resumes.preview', $resume) }}">Aperçu</a>
                    <form method="POST" action="{{ route('resumes.duplicate', $resume) }}">@csrf<button>Dupliquer</button></form>
                    <form method="POST" action="{{ route('resumes.destroy', $resume) }}">@csrf @method('DELETE')<button class="text-red-600">Supprimer</button></form>
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-600">Vous n’avez pas encore de CV.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $resumes->links() }}</div>
</x-app-layout>
