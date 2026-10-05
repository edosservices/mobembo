@extends('layouts.app')
@section('title', 'KYC')
@section('content')
<h1 style="font-size:2.3rem;">Vérification d’identité</h1>
<p class="note">Le KYC est préparé pour une mise en conformité avant lancement public. Le statut actuel est : {{ auth()->user()->kyc_status->label() }}.</p>
<form class="panel" method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="field">
        <label for="document_type">Document</label>
        <select id="document_type" name="document_type">
            <option value="id_card">Carte d’identité</option>
            <option value="passport">Passeport</option>
            <option value="proof_of_address">Justificatif de domicile</option>
        </select>
    </div>
    <div class="field"><label for="document">Fichier</label><input id="document" type="file" name="document" required></div>
    <button class="btn-z" type="submit">Envoyer</button>
</form>
<ul>
    @foreach ($documents as $document)
        <li>{{ $document->typeLabel() }} — {{ $document->status->label() }} — {{ $document->created_at->format('d/m/Y H:i') }}</li>
    @endforeach
</ul>
@endsection
