@extends('layouts.public')
@section('title', 'ZELVORA — Investissez dans l’immobilier')
@section('body-class', 'site-public home-page')
@section('main-class', 'page-bare')
@section('alerts-class', 'wrap alert-wrap')
@section('content')
<section class="hero-screen" style="background-image: url('{{ asset('images/hero.jpg') }}')">
    <div class="overlay"></div>
    <div class="hero-copy">
        <p class="kicker">Immobilier · ZELVORA</p>
        <h1>Investissez dans l'immobilier.<br>Construisez votre avenir.</h1>
        <p class="lede">Découvrez des opportunités immobilières sélectionnées et suivez vos investissements directement depuis votre téléphone.</p>
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
            <span>Investissement minimum</span>
        </article>
        <article>
            @if ($stats['maximum_plan'] !== null)
                <strong data-count="{{ (int) $stats['maximum_plan'] }}" data-suffix=" $">{{ number_format((int) $stats['maximum_plan'], 0, ',', ' ') }} $</strong>
            @else
                <strong>—</strong>
            @endif
            <span>Plan maximum</span>
        </article>
        <article>
            <strong data-count="100" data-suffix=" %">100 %</strong>
            <span>Suivi digital</span>
        </article>
    </div>
</section>

<section class="section reveal" id="pourquoi">
    <div class="wrap">
        <p class="kicker">Pourquoi ZELVORA</p>
        <h2>Une lecture claire de chaque opportunité.</h2>
        <div class="why-grid">
            <article class="why-card">
                <span aria-hidden="true">▣</span>
                <h3>Projets immobiliers</h3>
                <p>Découvrez différentes opportunités immobilières.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">▢</span>
                <h3>Investissement mobile</h3>
                <p>Gérez vos investissements directement depuis votre téléphone.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">◈</span>
                <h3>Transparence</h3>
                <p>Consultez vos opérations et l'historique de votre portefeuille.</p>
            </article>
            <article class="why-card">
                <span aria-hidden="true">▤</span>
                <h3>Suivi</h3>
                <p>Suivez vos investissements et les performances prévues des projets.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-muted reveal" id="opportunites">
    <div class="wrap">
        <div class="section-head">
            <div>
                <p class="kicker">Nos opportunités</p>
                <h2>Des conditions lisibles.</h2>
            </div>
            <a class="btn-z-ghost" href="{{ route('projects.index') }}">Voir tous les projets</a>
        </div>
        <div class="opportunity-grid">
            @forelse ($projects as $project)
                @include('partials.project-card', ['project' => $project])
            @empty
                <p class="muted">Aucun projet n’est publié pour le moment.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="section reveal" id="comment-ca-marche">
    <div class="wrap">
        <p class="kicker">Comment ça marche</p>
        <h2>Cinq étapes, sans rendement inventé.</h2>
        <ol class="timeline">
            <li><span>01</span><div><h3>Créer un compte</h3><p>Nom, téléphone et mot de passe.</p></div></li>
            <li><span>02</span><div><h3>Déposer des fonds</h3><p>Choisir son moyen de paiement et envoyer les fonds au numéro ZELVORA indiqué, lorsqu’il est configuré. La preuve reste en attente jusqu’à validation.</p></div></li>
            <li><span>03</span><div><h3>Choisir un projet</h3><p>Explorer les différentes opportunités.</p></div></li>
            <li><span>04</span><div><h3>Investir</h3><p>Choisir le montant et confirmer. Le solde disponible est débité dans une opération unique.</p></div></li>
            <li><span>05</span><div><h3>Suivre</h3><p>Consulter son portefeuille et les distributions réelles lorsqu’elles sont enregistrées.</p></div></li>
        </ol>
    </div>
</section>

<section class="section reveal">
    <div class="wrap mobile-band">
        <div class="phone" aria-hidden="true">
            <div class="phone-screen">
                <p class="phone-kicker">Aperçu</p>
                <strong>ZELVORA</strong>
                <ul>
                    <li><span>Solde</span><em>Compte</em></li>
                    <li><span>Investissements</span><em>Portefeuille</em></li>
                    <li><span>Projets</span><em>Opportunités</em></li>
                    <li><span>Revenus</span><em>Distributions</em></li>
                    <li><span>Transactions</span><em>Historique</em></li>
                </ul>
            </div>
        </div>
        <div>
            <p class="kicker">Mobile</p>
            <h2>Votre portefeuille immobilier dans votre poche.</h2>
            <p class="lede">Solde, investissements, projets, revenus crédités et transactions restent accessibles depuis le téléphone. Un revenu n’apparaît que s’il a été enregistré.</p>
            <a class="btn-z" href="{{ auth()->check() ? route('dashboard') : route('register') }}">Ouvrir mon espace</a>
        </div>
    </div>
</section>

<section class="visual-band reveal" style="background-image: url('{{ asset('images/architecture.jpg') }}')">
    <div class="overlay"></div>
    <div class="wrap visual-copy">
        <h2>Votre capital mérite des opportunités réelles.</h2>
        <p>Explorez des projets immobiliers présentés avec leurs informations, leurs conditions et leur progression.</p>
        <a class="btn-z gold" href="{{ route('projects.index') }}">Explorer les projets</a>
    </div>
</section>

<section class="section reveal" id="faq">
    <div class="wrap faq-list">
        <p class="kicker">FAQ</p>
        <h2>Les questions utiles avant d’investir.</h2>
        <details open>
            <summary>Qu'est-ce que ZELVORA ?</summary>
            <p>ZELVORA présente des opportunités immobilières et permet de suivre les dépôts, investissements, retraits et distributions depuis un compte identifié par le téléphone. Ce n’est pas une banque. Un pourcentage affiché est une prévision, pas un crédit automatique.</p>
        </details>
        <details>
            <summary>Comment effectuer un dépôt ?</summary>
            <p>Depuis votre espace, choisissez M-Pesa, Airtel Money ou Orange Money, envoyez les fonds au numéro indiqué par l’administration, puis joignez la preuve et la référence. Le montant reste en attente jusqu’à validation. Aucun numéro n’est inventé : s’il n’est pas encore configuré, il n’est pas affiché.</p>
        </details>
        <details>
            <summary>Quel est le montant minimum ?</summary>
            <p>À partir de {{ $stats['minimum'] !== null ? money($stats['minimum']) : '10,00 $' }}, selon les projets disponibles.</p>
        </details>
        <details>
            <summary>Puis-je retirer mon argent ?</summary>
            <p>Une demande de retrait utilise le solde disponible, affiche les frais, le brut et le net, puis reste en attente. Elle est soumise aux conditions applicables et à la validation administrative. Le capital investi n’est pas retiré comme un solde : il revient lors d’une restitution enregistrée.</p>
        </details>
        <details>
            <summary>Comment fonctionnent les rendements ?</summary>
            <p>Un rendement affiché peut être prévisionnel. Une distribution réelle doit correspondre aux revenus effectivement enregistrés pour le projet. Elle apparaît alors dans le portefeuille, l’historique, le ledger, l’investissement et les notifications. Posséder un investissement ne crée pas d’argent tout seul.</p>
        </details>
        <p class="note">{{ $disclaimer }}</p>
    </div>
</section>
@endsection
