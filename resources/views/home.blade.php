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
        <p class="lede">Investissez dans l'immobilier à votre rythme. Découvrez des opportunités immobilières et construisez progressivement votre patrimoine.</p>
        <div class="actions hero-actions">
            <a class="btn-z gold" href="{{ route('register') }}">Commencer à investir</a>
            <a class="btn-z-ghost light" href="{{ route('projects.index') }}">Découvrir les projets</a>
        </div>
    </div>
</section>

<section class="band reveal" id="chiffres">
    <div class="wrap stat-row">
        <article>
            <strong data-count="{{ $stats['projects'] }}">{{ $stats['projects'] }}</strong>
            <span>Projets disponibles</span>
        </article>
        <article>
            @if ($stats['minimum'] !== null)
                <strong data-count="{{ (int) $stats['minimum'] }}" data-suffix=" $">{{ (int) $stats['minimum'] }} $</strong>
            @else
                <strong>—</strong>
            @endif
            <span>À partir de</span>
        </article>
        <article>
            @if ($stats['maximum_plan'] !== null)
                <strong data-count="{{ (int) $stats['maximum_plan'] }}" data-suffix=" $">{{ number_format((int) $stats['maximum_plan'], 0, ',', ' ') }} $</strong>
            @else
                <strong>—</strong>
            @endif
            <span>Plan le plus élevé</span>
        </article>
        <article>
            <strong data-count="100" data-suffix=" %">100 %</strong>
            <span>Suivi depuis votre espace</span>
        </article>
    </div>
</section>

<section class="section reveal" id="pourquoi">
    <div class="wrap">
        <p class="kicker">Pourquoi ZELVORA</p>
        <h2>Investissez dans des opportunités immobilières sélectionnées.</h2>
        <div class="why-grid">
            <article class="why-card">
                <span aria-hidden="true">▣</span>
                <h3>Projets immobiliers</h3>
                <p>Découvrez des opportunités présentées avec leurs conditions.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">▢</span>
                <h3>À votre rythme</h3>
                <p>Choisissez un projet et un montant depuis votre téléphone.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">◈</span>
                <h3>Portefeuille</h3>
                <p>Suivez votre solde, vos investissements et vos revenus.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">▤</span>
                <h3>Suivi clair</h3>
                <p>Chaque projet affiche sa durée, son minimum et sa progression.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-muted reveal" id="opportunites">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="kicker">Nos opportunités</p>
                <h2>Découvrez des projets immobiliers sélectionnés.</h2>
            </div>
            <a class="btn-z-ghost" href="{{ route('projects.index') }}">Découvrir les projets</a>
        </div>
        <div class="opportunity-grid">
            @forelse ($projects as $project)
                @include('partials.project-card', ['project' => $project])
            @empty
                <p class="muted">De nouvelles opportunités seront publiées ici.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="section reveal" id="comment-ca-marche">
    <div class="wrap">
        <p class="kicker">Comment ça marche</p>
        <h2>Cinq étapes pour investir.</h2>
        <ol class="timeline">
            <li><span>01</span><div><h3>Créer un compte</h3><p>Inscrivez-vous avec votre numéro de téléphone.</p></div></li>
            <li><span>02</span><div><h3>Faire un dépôt</h3><p>Envoyez vos fonds par M-Pesa, Airtel Money ou Orange Money, puis joignez la preuve.</p></div></li>
            <li><span>03</span><div><h3>Choisir un projet</h3><p>Parcourez les opportunités et retenez celle qui correspond à votre stratégie.</p></div></li>
            <li><span>04</span><div><h3>Investir</h3><p>Indiquez le montant et confirmez votre investissement.</p></div></li>
            <li><span>05</span><div><h3>Suivre</h3><p>Consultez votre portefeuille et vos revenus depuis votre espace personnel.</p></div></li>
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
                    <li><span>Projets</span><em>Opportunités</em></li>
                    <li><span>Revenus</span><em>Crédités</em></li>
                    <li><span>Historique</span><em>Opérations</em></li>
                </ul>
            </div>
        </div>
        <div>
            <p class="kicker">Mobile</p>
            <h2>Votre portefeuille immobilier dans votre poche.</h2>
            <p class="lede">Investissez dans des opportunités immobilières sélectionnées et suivez votre portefeuille depuis votre espace personnel.</p>
            <a class="btn-z" href="{{ auth()->check() ? route('dashboard') : route('register') }}">Commencer à investir</a>
        </div>
    </div>
</section>

<section class="visual-band reveal" style="background-image: url('{{ asset('images/architecture.jpg') }}')">
    <div class="overlay"></div>
    <div class="wrap visual-copy">
        <h2>Investissez dans l'immobilier à votre rythme.</h2>
        <p>Découvrez des opportunités immobilières et construisez progressivement votre patrimoine.</p>
        <a class="btn-z gold" href="{{ route('projects.index') }}">Découvrir les projets</a>
    </div>
</section>

<section class="section reveal" id="faq">
    <div class="wrap faq-list">
        <p class="kicker">FAQ</p>
        <h2>Questions fréquentes</h2>
        <details open>
            <summary>Qu'est-ce que ZELVORA ?</summary>
            <p>ZELVORA vous permet d’investir dans des opportunités immobilières et de suivre votre portefeuille depuis votre espace personnel.</p>
        </details>
        <details>
            <summary>Comment effectuer un dépôt ?</summary>
            <p>Depuis votre espace, choisissez M-Pesa, Airtel Money ou Orange Money, envoyez le montant au numéro indiqué, puis joignez la preuve de paiement.</p>
        </details>
        <details>
            <summary>Quel est le montant minimum ?</summary>
            <p>Le montant minimum dépend du projet sélectionné. Chaque projet affiche clairement son montant minimum avant l'investissement.</p>
        </details>
        <details>
            <summary>Puis-je retirer mon argent ?</summary>
            <p>Vous pouvez demander un retrait depuis votre espace personnel. Les demandes sont traitées selon les conditions applicables à votre compte.</p>
        </details>
        <details>
            <summary>Comment fonctionnent les rendements ?</summary>
            <p>Chaque projet présente un rendement prévu et une durée. Les montants affichés permettent d'estimer les revenus potentiels. Les revenus réellement crédités apparaissent dans votre portefeuille lorsqu'une distribution est enregistrée.</p>
        </details>
        <p class="fine-print">Les performances présentées sont indicatives et dépendent des conditions de chaque projet.</p>
    </div>
</section>
@endsection
