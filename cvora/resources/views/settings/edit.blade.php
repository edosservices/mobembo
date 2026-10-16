<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Paramètres</h1></x-slot>
    <form method="POST" action="{{ route('settings.update') }}" class="max-w-xl space-y-4 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <label class="block text-sm">Modèle proposé pour un nouveau CV
            <select name="template" class="mt-1 w-full rounded-xl border-slate-300">
                <option value="">Moderne</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->slug }}" @selected(($preferences['template'] ?? '') === $template->slug)>{{ $template->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="notify_email" value="1" @checked($preferences['notify_email'] ?? false)> Recevoir les messages de service par e-mail</label>
        <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
    </form>
</x-app-layout>
