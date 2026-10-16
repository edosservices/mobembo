<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">{{ $letter->exists ? 'Modifier la lettre' : 'Nouvelle lettre' }}</h1></x-slot>
    <form method="POST" action="{{ $letter->exists ? route('letters.update', $letter) : route('letters.store') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @if ($letter->exists) @method('PUT') @endif
        <div class="space-y-3 rounded-2xl bg-white p-5 shadow-sm">
            <label class="block text-sm">Titre<input name="title" value="{{ old('title', $letter->title) }}" class="mt-1 w-full rounded-xl border-slate-300" required></label>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Poste<input name="job_title" value="{{ old('job_title', $letter->job_title) }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
                <label class="text-sm">Organisation<input name="company" value="{{ old('company', $letter->company) }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
            </div>
            <label class="block text-sm">CV source
                <select name="resume_id" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Aucun</option>
                    @foreach ($resumes as $resume)
                        <option value="{{ $resume->id }}" @selected(old('resume_id', $letter->resume_id) == $resume->id)>{{ $resume->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Offre liée
                <select name="job_offer_id" class="mt-1 w-full rounded-xl border-slate-300">
                    <option value="">Aucune</option>
                    @foreach ($offers as $offer)
                        <option value="{{ $offer->id }}" @selected(old('job_offer_id', $letter->job_offer_id) == $offer->id)>{{ $offer->title }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Ton
                    <select name="tone" class="mt-1 w-full rounded-xl border-slate-300">
                        @foreach (['classique' => 'Classique', 'professionnel' => 'Professionnel', 'direct' => 'Direct', 'debutant' => 'Débutant'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('tone', $letter->tone) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Longueur
                    <select name="length" class="mt-1 w-full rounded-xl border-slate-300">
                        @foreach (['courte' => 'Courte', 'moyenne' => 'Moyenne', 'developpee' => 'Développée'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('length', $letter->length) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <label class="block text-sm">Extrait de l’offre<textarea name="offer_excerpt" rows="3" class="mt-1 w-full rounded-xl border-slate-300">{{ old('offer_excerpt', $letter->offer_excerpt) }}</textarea></label>
            <label class="block text-sm">Lettre<textarea name="content" rows="14" class="mt-1 w-full rounded-xl border-slate-300" required>{{ old('content', $letter->content) }}</textarea></label>
            <p class="text-sm text-slate-500">Écrivez à partir de votre parcours. N’affirmez pas une compétence ou un résultat que vous ne pouvez pas soutenir.</p>
            @foreach ($errors->all() as $error)<p class="text-sm text-red-600">{{ $error }}</p>@endforeach
            <div class="flex flex-wrap gap-2">
                <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
                @if ($letter->exists)
                    <a class="rounded-full border border-slate-300 px-4 py-2 text-sm" href="{{ route('letters.preview', $letter) }}">Aperçu</a>
                    <a class="rounded-full border border-slate-300 px-4 py-2 text-sm" href="{{ route('letters.export.pdf', $letter) }}">PDF</a>
                    <a class="rounded-full border border-slate-300 px-4 py-2 text-sm" href="{{ route('letters.export.docx', $letter) }}">Word</a>
                @endif
            </div>
        </div>
        @if ($letter->exists)
            <aside class="rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="font-display font-semibold">Versions</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($versions as $version)
                        <li>{{ $version->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $version->source }}</li>
                    @empty
                        <li class="text-slate-500">La version précédente est gardée à chaque modification du texte.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('letters.destroy', $letter) }}" class="mt-4">@csrf @method('DELETE')<button class="text-sm text-red-600">Supprimer la lettre</button></form>
            </aside>
        @endif
    </form>
</x-app-layout>
