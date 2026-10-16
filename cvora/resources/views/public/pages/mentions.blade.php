<x-public-layout>
    <article class="mx-auto max-w-3xl px-4 py-14 text-sm leading-7 text-slate-700">
        <h1 class="font-display text-4xl font-semibold text-ink">Mentions légales</h1>
        <p class="mt-4">CVora est un service de préparation de candidatures. L’éditeur renseigne son identité, son adresse et son contact dans cette page avant une mise en production.</p>
        <p class="mt-3">Contact : {{ \App\Models\Setting::getValue('contact.email', 'contact@cvora.app') }}.</p>
        <p class="mt-3">Hébergement : à compléter avec le nom de l’hébergeur retenu pour la production.</p>
    </article>
</x-public-layout>
