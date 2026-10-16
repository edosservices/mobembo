<x-public-layout>
    <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 lg:grid-cols-2 lg:py-20">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-brand">Votre talent mérite le meilleur CV.</p>
            <h1 class="mt-3 font-display text-4xl font-semibold leading-tight text-ink sm:text-5xl">Des candidatures qui ouvrent des portes.</h1>
            <p class="mt-4 text-lg text-slate-600">Créez, améliorez et actualisez votre CV avec l'IA. Rédigez des lettres de motivation personnalisées et préparez vos candidatures en quelques minutes.</p>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}" class="rounded-full bg-brand px-5 py-3 text-center text-sm font-semibold text-white hover:bg-brand-light">Créer mon CV gratuitement</a>
                <a href="{{ route('templates') }}" class="rounded-full border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-ink hover:bg-mist">Découvrir les modèles</a>
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-mist p-4 shadow-sm">
            <div class="rounded-2xl bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-display text-xl font-semibold">Prénom Nom</p>
                        <p class="text-sm text-brand">Poste visé</p>
                    </div>
                    <span class="rounded-full bg-brand-soft px-3 py-1 text-xs font-medium text-brand">Aperçu d’un modèle</span>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-600">Résumé rédigé par le candidat. Cette zone montre la structure d’un CV, pas un parcours réel.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-mist p-3 text-sm"><p class="font-semibold">Expérience</p><p class="text-slate-600">Intitulé — Organisation</p></div>
                    <div class="rounded-xl bg-mist p-3 text-sm"><p class="font-semibold">Formation</p><p class="text-slate-600">Diplôme — Établissement</p></div>
                </div>
            </div>
        </div>
    </section>

    <section id="fonctionnalites" class="bg-mist">
        <div class="mx-auto max-w-6xl px-4 py-16">
            <h2 class="font-display text-3xl font-semibold">Ce que CVora permet de faire</h2>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Création de CV professionnels', 'Un éditeur par étapes, avec aperçu et plusieurs mises en page.'],
                    ['Importation et actualisation', 'Un PDF ou un DOCX est lu, puis vous confirmez chaque information.'],
                    ['Lettres de motivation', 'Une lettre liée à votre CV et, si vous le souhaitez, à une offre.'],
                    ['Adaptation à une offre', 'L’assistant signale les écarts. Il n’écrit rien dans le CV sans vous.'],
                    ['Téléchargement PDF et Word', 'Des fichiers lisibles, avec les accents français, selon votre offre.'],
                    ['Vos données restent les vôtres', 'Aucun diplôme ni aucune expérience n’est inventé à votre place.'],
                ] as [$title, $text])
                    <article class="rounded-2xl bg-white p-5 shadow-sm">
                        <h3 class="font-display text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $text }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16">
        <div class="flex items-end justify-between gap-4">
            <h2 class="font-display text-3xl font-semibold">Galerie des modèles</h2>
            <a href="{{ route('templates') }}" class="text-sm font-semibold text-brand">Voir les modèles</a>
        </div>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($templates as $template)
                <a href="{{ route('templates.preview', $template->slug) }}" class="rounded-2xl border border-slate-200 p-4 hover:border-brand">
                    <p class="font-display font-semibold">{{ $template->name }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $template->description }}</p>
                    <p class="mt-3 text-xs font-medium text-brand">{{ $template->is_premium ? 'Selon l’offre' : 'Inclus dans l’offre gratuite' }}</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-16">
            <h2 class="font-display text-3xl font-semibold">Trois étapes</h2>
            <ol class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach (['Créez un compte et partez de votre profil, ou importez un CV existant.', 'Relisez, corrigez et choisissez un modèle. L’assistant propose, vous décidez.', 'Enregistrez, préparez la lettre et téléchargez quand votre offre le permet.'] as $index => $step)
                    <li class="rounded-2xl bg-mist p-5">
                        <span class="grid h-8 w-8 place-items-center rounded-full bg-brand text-sm font-semibold text-white">{{ $index + 1 }}</span>
                        <p class="mt-3 text-sm leading-6">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="tarifs" class="mx-auto max-w-6xl px-4 py-16">
        <h2 class="font-display text-3xl font-semibold">Tarifs</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Les prix et les quotas sont enregistrés par l’administrateur. Aucun montant payant n’est imposé par le code.</p>
        <div class="mt-8 grid gap-4 lg:grid-cols-3">
            @foreach ($plans as $plan)
                <article class="rounded-2xl border border-slate-200 p-5">
                    <h3 class="font-display text-xl font-semibold">{{ $plan->name }}</h3>
                    <p class="mt-2 text-2xl font-semibold text-brand">{{ \App\Support\Money::format($plan->price_cents, $plan->currency) }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $plan->description }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-mist">
        <div class="mx-auto max-w-3xl px-4 py-16">
            <h2 class="font-display text-3xl font-semibold">Questions fréquentes</h2>
            <div class="mt-6 space-y-3">
                @foreach ([
                    ['CVora garantit-il un emploi ?', 'Non. Le CV et la lettre aident à présenter votre parcours. Aucun résultat d’embauche n’est promis.'],
                    ['L’assistant peut-il inventer un diplôme ?', 'Non. Il reformule les faits que vous fournissez. Rien n’est enregistré sans votre validation.'],
                    ['Un paiement est-il simulé ?', 'Non. Sans confirmation signée du prestataire, l’offre payante reste en attente. Le bac à sable est clairement séparé et ne débite rien.'],
                    ['Mes fichiers sont-ils publics ?', 'Non. Les imports restent dans un stockage privé et les exports passent par une route réservée à votre compte.'],
                ] as [$question, $answer])
                    <details class="rounded-2xl bg-white p-4">
                        <summary class="cursor-pointer font-medium">{{ $question }}</summary>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-16 text-center">
        <h2 class="font-display text-3xl font-semibold">Préparez votre prochaine candidature.</h2>
        <a href="{{ route('register') }}" class="mt-6 inline-flex rounded-full bg-brand px-5 py-3 text-sm font-semibold text-white hover:bg-brand-light">Créer mon CV gratuitement</a>
    </section>
</x-public-layout>
