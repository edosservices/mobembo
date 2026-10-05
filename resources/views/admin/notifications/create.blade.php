@extends('layouts.admin')
@section('content')
<h1 class="serif">Envoyer une notification</h1>
<form class="panel" method="POST" action="{{ route('admin.notifications.store') }}">
    @csrf
    <div class="field"><label>Destinataires</label><select name="audience"><option value="all">Tous les clients</option><option value="user">Un numéro</option></select></div>
    <div class="field"><label>Téléphone, si un seul client</label><input name="phone" placeholder="+243..."></div>
    <div class="field"><label>Titre</label><input name="title" required></div>
    <div class="field"><label>Message</label><textarea name="body" required></textarea></div>
    <button class="btn-z" type="submit">Envoyer</button>
</form>
@endsection
