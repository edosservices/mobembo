<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Vérifier l’import</h1></x-slot>
    <form method="POST" action="{{ route('resumes.import.confirm', $import) }}" class="max-w-3xl space-y-4 rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        @if ($import->warning)
            <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $import->warning }}</p>
        @endif
        <p class="text-sm text-slate-600">Rien n’est enregistré tant que vous ne confirmez pas. Un CV existant n’est remplacé que si vous cochez la case.</p>
        <label class="block text-sm">Titre<input name="title" value="{{ old('title', 'CV importé') }}" class="mt-1 w-full rounded-xl border-slate-300" required></label>
        @foreach (['first_name' => 'Prénom', 'last_name' => 'Nom', 'email' => 'E-mail', 'phone' => 'Téléphone', 'city' => 'Ville', 'title' => 'Titre'] as $key => $label)
            <label class="block text-sm">{{ $label }}<input name="content[personal][{{ $key }}]" value="{{ old('content.personal.'.$key, $content['personal'][$key] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
        @endforeach
        <label class="block text-sm">Résumé<textarea name="content[summary]" rows="5" class="mt-1 w-full rounded-xl border-slate-300">{{ old('content.summary', $content['summary']) }}</textarea></label>
        @foreach ($content['experiences'] as $index => $item)
            <fieldset class="rounded-xl border border-slate-200 p-3">
                <legend class="px-1 text-sm">Expérience</legend>
                <input name="content[experiences][{{ $index }}][title]" value="{{ $item['title'] }}" class="w-full rounded-lg border-slate-300">
                <input name="content[experiences][{{ $index }}][company]" value="{{ $item['company'] }}" class="mt-2 w-full rounded-lg border-slate-300">
                <textarea name="content[experiences][{{ $index }}][description]" class="mt-2 w-full rounded-lg border-slate-300">{{ $item['description'] }}</textarea>
            </fieldset>
        @endforeach
        @foreach ($content['education'] as $index => $item)
            <fieldset class="rounded-xl border border-slate-200 p-3">
                <legend class="px-1 text-sm">Formation</legend>
                <input name="content[education][{{ $index }}][school]" value="{{ $item['school'] }}" class="w-full rounded-lg border-slate-300">
                <input name="content[education][{{ $index }}][degree]" value="{{ $item['degree'] }}" class="mt-2 w-full rounded-lg border-slate-300">
            </fieldset>
        @endforeach
        @foreach ($content['skills'] as $index => $item)
            <input name="content[skills][{{ $index }}][name]" value="{{ $item['name'] }}" class="w-full rounded-lg border-slate-300">
        @endforeach
        <label class="block text-sm">Remplacer un CV existant
            <select name="replace_resume_id" class="mt-1 w-full rounded-xl border-slate-300">
                <option value="">Créer un nouveau CV</option>
                @foreach ($resumes as $resume)
                    <option value="{{ $resume->id }}">{{ $resume->title }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="confirm_replace" value="1"> Je confirme le remplacement du CV choisi. L’ancienne version sera conservée.</label>
        @foreach ($errors->all() as $error)
            <p class="text-sm text-red-600">{{ $error }}</p>
        @endforeach
        <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer les informations vérifiées</button>
    </form>
</x-app-layout>
