<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Réglages</h1></x-slot>
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-xl space-y-3 rounded-2xl bg-white p-5 shadow-sm">
        @csrf @method('PUT')
        @foreach ([
            'features.registration' => 'Inscriptions',
            'features.ai' => 'Assistant IA',
            'features.exports' => 'Exportations',
            'features.imports' => 'Importations',
            'features.payments' => 'Paiements',
            'auth.require_email_verification' => 'Exiger la vérification e-mail',
        ] as $key => $label)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="{{ str_replace('.', '_', $key) }}" value="1" @checked($flags[$key])> {{ $label }}</label>
        @endforeach
        <label class="block text-sm">Modèle IA (vide = valeur d’environnement)<input name="ai_model" value="{{ $aiModel }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <label class="block text-sm">URL de base IA<input name="ai_base_url" value="{{ $aiBaseUrl }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <p class="text-sm">Clé API : {{ $aiConfigured ? 'présente dans l’environnement' : 'absente' }}. Elle n’est pas modifiable ici.</p>
        <label class="block text-sm">E-mail de contact<input name="contact_email" value="{{ $contactEmail }}" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
    </form>
</x-app-layout>
