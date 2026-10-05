@extends('layouts.app')
@section('title', 'Profil')
@section('content')
<h1 style="font-size:2.3rem;">Profil</h1>
<section class="panel level-card">
    <p class="kicker">Niveau actuel</p>
    <p class="level-badge">{{ $team['level']['name'] }}</p>
    <p>{{ $user->name }}</p>
    <p class="muted">{{ $user->phone }} · Inscrit le {{ $user->created_at->timezone(config('app.timezone'))->format('d/m/Y') }}</p>
    <p>{{ $team['active'] }} / {{ $team['target'] }} membres actifs</p>
    <div class="progress" role="img" aria-label="{{ $team['progress'] }} % vers le niveau suivant">
        <span style="width: {{ $team['progress'] }}%"></span>
    </div>
    @if ($team['next'])
        <p class="fine-print">{{ $team['remaining'] }} membres actifs avant {{ $team['next']['name'] }}</p>
    @else
        <p class="fine-print">Niveau {{ $team['level']['name'] }} atteint.</p>
    @endif
    <ul class="benefit-list">
        @foreach ($team['benefits'] as $benefit)
            <li>{{ $benefit }}</li>
        @endforeach
        <li>Commission de parrainage actuelle : {{ str_replace('.', ',', bcadd($team['rate'], '0', 2)) }} %</li>
    </ul>
</section>
<section>
    <h2>Badges</h2>
    <div class="badge-grid">
        @foreach ($badges as $badge)
            <article @class(['badge-tile', 'is-locked' => ! $badge['unlocked']])>
                <span aria-hidden="true">{{ $badge['icon'] }}</span>
                <strong>{{ $badge['name'] }}</strong>
                <p>{{ $badge['description'] }}</p>
                <em>{{ $badge['unlocked'] ? 'Débloqué' : 'Verrouillé' }}</em>
            </article>
        @endforeach
    </div>
</section>
<div class="grid-2" style="margin-top:1rem;">
    <form class="panel" method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PUT')
        <div class="field"><label>Téléphone</label><input value="{{ $user->phone }}" disabled></div>
        <div class="field"><label for="name">Nom complet</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <p class="muted">Code de parrainage : {{ $user->referral_code ?: '—' }}</p>
        <button class="btn-z" type="submit">Enregistrer</button>
        <p><a href="{{ route('kyc.edit') }}">Vérification d'identité</a> · <a href="{{ route('referral') }}">Mon équipe</a></p>
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
