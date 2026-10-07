@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.withdrawals.index') }}">Retraits</a></p>
<h1 class="serif">Net {{ money($withdrawal->net_amount) }}</h1>
<p>{{ $withdrawal->user->name }} · {{ $withdrawal->phone }} · {{ $withdrawal->method->label() }}</p>
<div class="grid-3">
    <div class="stat"><span>Demandé</span><strong>{{ money($withdrawal->amount) }}</strong></div>
    <div class="stat"><span>Frais</span><strong>{{ money($withdrawal->fee) }}</strong></div>
    <div class="stat"><span>Net à verser</span><strong>{{ money($withdrawal->net_amount) }}</strong></div>
</div>
<p>@include('partials.status', ['status' => $withdrawal->status]) · {{ $withdrawal->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
@if (in_array($withdrawal->status->value, ['pending', 'processing'], true))
    <div class="grid-2">
        @if ($withdrawal->status->value === 'pending')
            <form class="panel" method="POST" action="{{ route('admin.withdrawals.process', $withdrawal) }}">@csrf<button class="btn-z" type="submit">Passer en traitement</button></form>
        @endif
        <form class="panel" method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal) }}">@csrf<button class="btn-z ok" type="submit">Approuver le paiement</button></form>
        <form class="panel" method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}">@csrf<input name="reason" placeholder="Motif du refus" required minlength="5"><button class="btn-z danger" type="submit">Refuser</button></form>
    </div>
@else
    <p class="muted">{{ $withdrawal->rejection_reason }}</p>
@endif
@endsection
