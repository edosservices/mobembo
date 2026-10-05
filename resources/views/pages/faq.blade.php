@extends('layouts.public')
@section('title', 'FAQ · ZELVORA')
@section('content')
<p class="kicker">FAQ</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Questions fréquentes</h1>
<div class="faq-list">
    <details open>
        <summary>Qu'est-ce que ZELVORA ?</summary>
        <p>Une plateforme qui présente des projets immobiliers et permet de suivre dépôts, investissements, retraits et distributions depuis un compte ouvert avec un numéro de téléphone.</p>
    </details>
    <details>
        <summary>Comment effectuer un dépôt ?</summary>
        <p>Choisissez M-Pesa, Airtel Money ou Orange Money, envoyez les fonds au numéro configuré par l’administration, puis envoyez la preuve. Le solde disponible change après validation.</p>
    </details>
    <details>
        <summary>Quel est le montant minimum ?</summary>
        <p>À partir de 10 $ selon les projets disponibles.</p>
    </details>
    <details>
        <summary>Puis-je retirer mon argent ?</summary>
        <p>Les retraits portent sur le solde disponible. Ils sont soumis aux frais, aux conditions applicables et à la validation administrative.</p>
    </details>
    <details>
        <summary>Comment fonctionnent les rendements ?</summary>
        <p>Un rendement affiché peut être prévisionnel. Une distribution réelle doit correspondre aux revenus effectivement enregistrés pour le projet. Elle est alors visible dans le portefeuille, l’historique, le ledger, l’investissement et les notifications.</p>
    </details>
</div>
@endsection
