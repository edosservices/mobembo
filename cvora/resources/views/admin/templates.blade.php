<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Modèles</h1></x-slot>
    @foreach ($templates as $template)
        <form method="POST" action="{{ route('admin.templates.update', $template) }}" class="mb-3 grid gap-2 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-5">
            @csrf @method('PUT')
            <input name="name" value="{{ $template->name }}" class="rounded-lg border-slate-300">
            <input name="description" value="{{ $template->description }}" class="rounded-lg border-slate-300 md:col-span-2">
            <select name="is_premium" class="rounded-lg border-slate-300"><option value="0" @selected(! $template->is_premium)>Inclus</option><option value="1" @selected($template->is_premium)>Selon l’offre</option></select>
            <select name="is_active" class="rounded-lg border-slate-300"><option value="1" @selected($template->is_active)>Visible</option><option value="0">Masqué</option></select>
            <button class="rounded-full bg-brand px-3 py-2 text-sm font-semibold text-white md:col-span-5 md:w-fit">Enregistrer</button>
        </form>
    @endforeach
</x-app-layout>
