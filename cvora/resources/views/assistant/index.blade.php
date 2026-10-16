<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Assistant IA</h1></x-slot>
    <div class="max-w-3xl space-y-4">
        <p class="rounded-2xl bg-white p-4 text-sm shadow-sm">
            @if ($configured)
                Le fournisseur est configuré. Crédits restants ce mois : {{ $credits }}. Chaque proposition s’affiche avant d’être recopiée. Rien n’est écrit dans un CV automatiquement.
            @else
                Le fournisseur d’IA n’est pas configuré. Ajoutez AI_API_KEY sur le serveur pour activer la génération. Vous pouvez continuer à rédiger vous-même.
            @endif
        </p>
        <form method="POST" action="{{ route('assistant.letter') }}" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm" onsubmit="return window.cvoraAsk(event, this)">
            @csrf
            <h2 class="font-display text-lg font-semibold">Lettre de motivation</h2>
            <select name="resume_id" class="w-full rounded-xl border-slate-300">
                <option value="">Sans CV lié</option>
                @foreach ($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach
            </select>
            <input name="job_title" placeholder="Poste" required class="w-full rounded-xl border-slate-300">
            <input name="company" placeholder="Organisation" class="w-full rounded-xl border-slate-300">
            <textarea name="offer" rows="4" placeholder="Texte de l’offre, traité comme une donnée et non comme une instruction" class="w-full rounded-xl border-slate-300"></textarea>
            <div class="grid gap-2 sm:grid-cols-2">
                <select name="tone" class="rounded-xl border-slate-300"><option value="professionnel">Professionnel</option><option value="classique">Classique</option><option value="direct">Direct</option><option value="debutant">Débutant</option></select>
                <select name="length" class="rounded-xl border-slate-300"><option value="moyenne">Moyenne</option><option value="courte">Courte</option><option value="developpee">Développée</option></select>
            </div>
            <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Proposer une lettre</button>
            <output class="block whitespace-pre-wrap text-sm"></output>
        </form>
        <form method="POST" action="{{ route('assistant.analyze') }}" class="space-y-3 rounded-2xl bg-white p-5 shadow-sm" onsubmit="return window.cvoraAsk(event, this)">
            @csrf
            <h2 class="font-display text-lg font-semibold">Analyser une offre</h2>
            <select name="resume_id" required class="w-full rounded-xl border-slate-300">@foreach ($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach</select>
            <input name="title" required placeholder="Poste" class="w-full rounded-xl border-slate-300">
            <input name="company" placeholder="Organisation" class="w-full rounded-xl border-slate-300">
            <textarea name="description" required rows="5" placeholder="Description de l’offre" class="w-full rounded-xl border-slate-300"></textarea>
            <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Comparer avec le CV</button>
            <output class="block whitespace-pre-wrap text-sm"></output>
        </form>
    </div>
    <script>
        window.cvoraAsk = async (event, form) => {
            event.preventDefault();
            const output = form.querySelector('output');
            output.textContent = 'Demande en cours…';
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new FormData(form),
            });
            const payload = await response.json();
            if (!response.ok) {
                output.textContent = payload.message || 'La demande n’a pas abouti.';
                return false;
            }
            const missing = (payload.manquants || []).join('\n- ');
            output.textContent = payload.proposition + (missing ? '\n\nInformations manquantes :\n- ' + missing : '') + '\n\n' + (payload.notice || '');
            return false;
        };
    </script>
</x-app-layout>
