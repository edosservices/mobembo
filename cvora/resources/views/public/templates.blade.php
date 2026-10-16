<x-public-layout>
    <div class="mx-auto max-w-6xl px-4 py-14">
        <h1 class="font-display text-4xl font-semibold">Modèles de CV</h1>
        <p class="mt-3 max-w-2xl text-slate-600">Chaque modèle organise les mêmes informations différemment. L’aperçu utilise des libellés génériques, pas un parcours réel.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach ($templates as $template)
                <article class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="h-64 overflow-hidden bg-mist">
                        <iframe title="Aperçu {{ $template->name }}" src="{{ route('templates.preview', $template->slug) }}" class="h-[640px] w-[800px] origin-top-left scale-50 pointer-events-none"></iframe>
                    </div>
                    <div class="p-4">
                        <h2 class="font-display text-lg font-semibold">{{ $template->name }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $template->description }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-public-layout>
