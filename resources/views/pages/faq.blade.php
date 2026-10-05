@extends('layouts.public')
@section('title', 'FAQ · ZELVORA')
@section('content')
<p class="kicker">FAQ</p>
<h1 style="font-size:clamp(2rem,5vw,3.2rem);">Questions fréquentes</h1>
<div class="faq-list">
    <details open>
        <summary>Qu'est-ce que ZELVORA ?</summary>
        <p>ZELVORA vous permet d’investir dans des opportunités immobilières et de suivre votre portefeuille depuis votre espace personnel.</p>
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
</div>
<p class="fine-print">Les performances présentées sont indicatives et dépendent des conditions de chaque projet.</p>
@endsection
