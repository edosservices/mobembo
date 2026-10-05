@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<h1 style="font-size:2.3rem;">Profil</h1>
<div class="grid-2">
    <form class="panel" method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PUT')
        <div class="field"><label>Téléphone</label><input value="{{ $user->phone }}" disabled></div>
        <div class="field"><label for="name">Nom complet</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <p class="muted">Code de parrainage : {{ $user->referral_code }} · KYC : {{ $user->kyc_status->label() }}</p>
        <button class="btn-z" type="submit">Enregistrer</button>
        <p><a href="{{ route('kyc.edit') }}">Dossier KYC</a> · <a href="{{ route('referral') }}">Parrainage</a></p>
    </form>
    <form class="panel" method="POST" action="{{ route('profile.password') }}">
        @csrf @method('PUT')
        <h2 style="margin-top:0;">Mot de passe</h2>
        <div class="field"><label for="current_password">Mot de passe actuel</label><input id="current_password" name="current_password" type="password" required></div>
        <div class="field"><label for="password">Nouveau mot de passe</label><input id="password" name="password" type="password" required></div>
        <div class="field"><label for="password_confirmation">Confirmation</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
        <button class="btn-z" type="submit">Changer le mot de passe</button>
    </form>
</div>
@endsection
