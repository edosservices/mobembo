@extends('layouts.public')
@section('title', 'Inscription · ZELVORA')
@section('content')
<div class="grid-2">
    <div>
        <div class="kicker">Inscription</div>
        <h1 style="font-size:3rem;">Un numéro suffit pour commencer.</h1>
        <p class="lede">Aucune adresse e-mail n’est demandée. Si vous arrivez par un lien d’invitation, le parrain est associé au compte. L’inscription seule ne crée pas de revenu.</p>
    </div>
    <form class="panel" method="POST" action="{{ route('register') }}">
        @csrf
        <div class="field"><label for="name">Nom complet</label><input id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label for="phone">Numéro de téléphone</label><input id="phone" name="phone" inputmode="tel" value="{{ old('phone') }}" placeholder="0812345678" required></div>
        <div class="field"><label for="password">Mot de passe</label><input id="password" name="password" type="password" required></div>
        <div class="field"><label for="password_confirmation">Confirmation</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
        <div class="field"><label for="referral_code">Code de parrainage, facultatif</label><input id="referral_code" name="referral_code" value="{{ old('referral_code', request('ref')) }}" placeholder="ZLV00000"></div>
        <button class="btn-z full" type="submit">Créer mon compte</button>
    </form>
</div>
@endsection
