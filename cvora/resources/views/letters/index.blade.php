<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between"><h1 class="font-display text-2xl font-semibold">Lettres de motivation</h1><a href="{{ route('letters.create') }}" class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Nouvelle lettre</a></div>
    </x-slot>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($letters as $letter)
            <article class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="font-display text-lg font-semibold">{{ $letter->title }}</h2>
                <p class="text-sm text-slate-500">{{ $letter->company }} {{ $letter->job_title }}</p>
                <a class="mt-3 inline-block text-sm font-semibold text-brand" href="{{ route('letters.edit', $letter) }}">Modifier</a>
            </article>
        @empty
            <p class="text-sm text-slate-600">Aucune lettre enregistrée.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $letters->links() }}</div>
</x-app-layout>
