<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Offres d’emploi</h1></x-slot>
    <form method="POST" action="{{ route('offers.store') }}" class="max-w-2xl space-y-3 rounded-2xl bg-white p-5 shadow-sm">
        @csrf
        <input name="title" required placeholder="Poste" class="w-full rounded-xl border-slate-300">
        <input name="company" placeholder="Organisation" class="w-full rounded-xl border-slate-300">
        <textarea name="description" required rows="5" placeholder="Collez l’offre. Elle ne modifie pas votre CV." class="w-full rounded-xl border-slate-300"></textarea>
        <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer l’offre</button>
    </form>
    <ul class="mt-4 space-y-3">
        @foreach ($offers as $offer)
            <li class="rounded-2xl bg-white p-4 shadow-sm">
                <p class="font-medium">{{ $offer->title }}</p>
                <p class="text-sm text-slate-500">{{ $offer->company }}</p>
                <form method="POST" action="{{ route('offers.destroy', $offer) }}">@csrf @method('DELETE')<button class="text-sm text-red-600">Supprimer</button></form>
            </li>
        @endforeach
    </ul>
</x-app-layout>
