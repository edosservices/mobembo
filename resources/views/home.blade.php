@extends('layouts.public')
@section('title', 'ZELVORA — Investissez dans l’immobilier')
@section('body-class', 'site-public home-page')
@section('main-class', 'page-bare')
@section('alerts-class', 'wrap alert-wrap')
@section('content')
<section class="hero-screen" style="background-image: url('{{ asset('images/hero.jpg') }}')">
    <div class="overlay"></div>
    <div class="hero-copy">
        <p class="kicker">ZELVORA</p>
        <h1>Votre patrimoine commence ici.</h1>
        <p class="lede">Résidences, hôtels et appartements sélectionnés. Vous choisissez un projet, vous investissez depuis votre téléphone et vous suivez votre portefeuille au même endroit.</p>
        <div class="actions hero-actions">
            <a class="btn-z gold" href="{{ route('register') }}">Commencer à investir</a>
            <a class="btn-z-ghost light" href="{{ route('projects.index') }}">Voir les projets</a>
        </div>
    </div>
</section>

<section class="band reveal" id="chiffres">
    <div class="wrap">
        <div class="row row-cols-2 row-cols-md-4 g-4 stat-row text-center">
            <div class="col">
                <strong data-count="{{ $stats['projects'] }}">{{ $stats['projects'] }}</strong>
                <span>Projets disponibles</span>
            </div>
            <div class="col">
                @if ($stats['minimum'] !== null)
                    <strong data-count="{{ (int) $stats['minimum'] }}" data-suffix=" $">{{ (int) $stats['minimum'] }} $</strong>
                @else
                    <strong>—</strong>
                @endif
                <span>À partir de</span>
            </div>
            <div class="col">
                @if ($stats['maximum_plan'] !== null)
                    <strong data-count="{{ (int) $stats['maximum_plan'] }}" data-suffix=" $">{{ number_format((int) $stats['maximum_plan'], 0, ',', ' ') }} $</strong>
                @else
                    <strong>—</strong>
                @endif
                <span>Plan le plus élevé</span>
            </div>
            <div class="col">
                <strong data-count="100" data-suffix=" %">100 %</strong>
                <span>Suivi depuis votre espace</span>
            </div>
        </div>
    </div>
</section>

<section class="section reveal" id="pourquoi">
    <div class="wrap text-center">
        <p class="kicker">Pourquoi ZELVORA</p>
        <h2 class="mx-auto">L’immobilier, présenté avec clarté.</h2>
        <p class="lede mx-auto">Chaque opportunité indique sa durée, son minimum et son rendement estimatif. Vous décidez ensuite, sans détour.</p>
        <div class="row g-3 text-start">
            <div class="col-md-6 col-xl-3">
                <article class="why-card h-100">
                    <span aria-hidden="true">▣</span>
                    <h3>Des lieux choisis</h3>
                    <p>Résidences, suites et appartements présentés avec leur image et leurs conditions.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="why-card h-100">
                    <span aria-hidden="true">▢</span>
                    <h3>À votre rythme</h3>
                    <p>Vous retenez le projet et le montant qui correspondent à ce que vous souhaitez engager.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="why-card h-100">
                    <span aria-hidden="true">◈</span>
                    <h3>Un portefeuille lisible</h3>
                    <p>Solde, investissements et revenus crédités restent visibles dans votre espace.</p>
                </article>
            </div>
            <div class="col-md-6 col-xl-3">
                <article class="why-card h-100">
                    <span aria-hidden="true">▤</span>
                    <h3>Un suivi net</h3>
                    <p>La progression de chaque projet et la durée restante sont affichées sans ambiguïté.</p>
                </article>
            </div>
        </div>
    </div>
</section>

<section class="section section-muted reveal">
    <div class="wrap text-center">
        <p class="kicker">L’esprit des lieux</p>
        <h2 class="mx-auto">Des adresses que l’on a envie de retenir.</h2>
        <p class="lede mx-auto">Quelques images de l’univers ZELVORA. Le détail de chaque projet, son minimum et son rendement se consultent dans la sélection.</p>
        <div class="row g-3 gallery-row">
            <div class="col-md-4">
                <figure class="gallery-shot">
                    <img src="{{ asset('images/projects/urban-stay.jpg') }}" alt="Suite urbaine avec terrasse et piscine">
                    <figcaption>Suites urbaines</figcaption>
                </figure>
            </div>
            <div class="col-md-4">
                <figure class="gallery-shot">
                    <img src="{{ asset('images/projects/kivu-pearl.jpg') }}" alt="Résidence contemporaine au bord d’une piscine">
                    <figcaption>Résidences</figcaption>
                </figure>
            </div>
            <div class="col-md-4">
                <figure class="gallery-shot">
                    <img src="{{ asset('images/projects/congo-vista.jpg') }}" alt="Intérieur d’un appartement meublé">
                    <figcaption>Appartements</figcaption>
                </figure>
            </div>
        </div>
        <a class="btn-z" href="{{ route('projects.index') }}">Voir les projets</a>
    </div>
</section>

<section class="section reveal" id="comment-ca-marche">
    <div class="wrap">
        <p class="kicker">Comment ça marche</p>
        <h2>Cinq étapes, puis votre portefeuille.</h2>
        <ol class="timeline">
            <li><span>01</span><div><h3>Créer un compte</h3><p>Inscrivez-vous avec votre numéro de téléphone.</p></div></li>
            <li><span>02</span><div><h3>Alimenter votre solde</h3><p>Ajoutez des fonds depuis votre espace, par le moyen de paiement indiqué.</p></div></li>
            <li><span>03</span><div><h3>Choisir un projet</h3><p>Parcourez la sélection et retenez l’adresse qui vous convient.</p></div></li>
            <li><span>04</span><div><h3>Investir</h3><p>Indiquez le montant, relisez le résumé, puis confirmez.</p></div></li>
            <li><span>05</span><div><h3>Suivre</h3><p>Consultez votre portefeuille, vos échéances et vos revenus crédités.</p></div></li>
        </ol>
    </div>
</section>

<section class="section reveal">
    <div class="wrap mobile-band">
        <div class="phone" aria-hidden="true">
            <div class="phone-screen">
                <p class="phone-kicker">Espace personnel</p>
                <strong>ZELVORA</strong>
                <ul>
                    <li><span>Solde</span><em>Disponible</em></li>
                    <li><span>Investissements</span><em>Portefeuille</em></li>
                    <li><span>Projets</span><em>Sélection</em></li>
                    <li><span>Revenus</span><em>Crédités</em></li>
                    <li><span>Historique</span><em>Opérations</em></li>
                </ul>
            </div>
        </div>
        <div>
            <p class="kicker">Mobile</p>
            <h2>Votre portefeuille, dans la poche.</h2>
            <p class="lede">Le même suivi, du premier regard jusqu’au revenu crédité, sur l’écran que vous avez déjà en main.</p>
            <a class="btn-z" href="{{ auth()->check() ? route('dashboard') : route('register') }}">Commencer à investir</a>
        </div>
    </div>
</section>

<section class="visual-band reveal" style="background-image: url('{{ asset('images/architecture.jpg') }}')">
    <div class="overlay"></div>
    <div class="wrap visual-copy">
        <h2>Construisez votre part, projet après projet.</h2>
        <p>Une sélection immobilière, un espace personnel, et le temps de choisir.</p>
        <a class="btn-z gold" href="{{ route('projects.index') }}">Voir les projets</a>
    </div>
</section>

<section class="section reveal" id="faq">
    <div class="wrap faq-list">
        <p class="kicker">FAQ</p>
        <h2>Questions fréquentes</h2>
        <details open>
            <summary>Qu’est-ce que ZELVORA ?</summary>
            <p>ZELVORA présente des opportunités immobilières et vous permet de suivre votre portefeuille depuis votre espace personnel.</p>
        </details>
        <details>
            <summary>Quel est le montant minimum ?</summary>
            <p>Il dépend du projet. Chaque fiche l’affiche avant que vous confirmiez un investissement.</p>
        </details>
        <details>
            <summary>Puis-je retirer mon argent ?</summary>
            <p>Une demande de retrait se fait depuis votre espace. Elle est traitée selon les conditions de votre compte.</p>
        </details>
        <details>
            <summary>Comment fonctionnent les rendements ?</summary>
            <p>Chaque projet indique un rendement prévu et une durée. Ces montants servent à estimer un potentiel. Un revenu n’apparaît dans votre portefeuille qu’une fois la distribution enregistrée.</p>
        </details>
        <p class="fine-print">Les performances présentées sont indicatives et dépendent des conditions de chaque projet.</p>
    </div>
</section>
@endsection
