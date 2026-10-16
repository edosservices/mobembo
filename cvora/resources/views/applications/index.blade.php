<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Mes candidatures</h1></x-slot>
    <p class="mb-4 text-sm text-slate-600">Ce suivi note où vous en êtes. CVora n’envoie pas la candidature à l’employeur.</p>
    <form method="POST" action="{{ route('applications.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow-sm md:grid-cols-2">
        @csrf
        <select name="resume_id" class="rounded-xl border-slate-300"><option value="">CV</option>@foreach ($resumes as $resume)<option value="{{ $resume->id }}">{{ $resume->title }}</option>@endforeach</select>
        <select name="cover_letter_id" class="rounded-xl border-slate-300"><option value="">Lettre</option>@foreach ($letters as $letter)<option value="{{ $letter->id }}">{{ $letter->title }}</option>@endforeach</select>
        <select name="job_offer_id" class="rounded-xl border-slate-300"><option value="">Offre</option>@foreach ($offers as $offer)<option value="{{ $offer->id }}">{{ $offer->title }}</option>@endforeach</select>
        <select name="status" class="rounded-xl border-slate-300">@foreach ($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select>
        <input type="date" name="applied_at" class="rounded-xl border-slate-300">
        <input name="notes" placeholder="Notes" class="rounded-xl border-slate-300">
        <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white md:col-span-2 md:w-fit">Ajouter</button>
    </form>
    <ul class="mt-4 space-y-3">
        @foreach ($applications as $application)
            <li class="rounded-2xl bg-white p-4 text-sm shadow-sm">
                <p class="font-medium">{{ $application->jobOffer?->title ?? 'Candidature' }} · {{ $application->status->label() }}</p>
                <p class="text-slate-500">{{ $application->resume?->title }} {{ $application->coverLetter?->title }}</p>
                <form method="POST" action="{{ route('applications.destroy', $application) }}">@csrf @method('DELETE')<button class="text-red-600">Retirer</button></form>
            </li>
        @endforeach
    </ul>
</x-app-layout>
