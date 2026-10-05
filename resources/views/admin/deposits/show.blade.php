@extends('layouts.admin')
@section('content')
<p><a href="{{ route('admin.deposits.index') }}">Dépôts</a></p>
<h1 class="serif">{{ money($deposit->amount) }}</h1>
<p>{{ $deposit->user->name }} · {{ $deposit->user->phone }} · {{ $deposit->method->label() }} · Réf. {{ $deposit->reference }}</p>
<p>Soumis le {{ $deposit->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · @include('partials.status', ['status' => $deposit->status])</p>
<p><a class="btn-z" href="{{ route('admin.deposits.proof', $deposit) }}">Voir la preuve</a></p>
@if ($deposit->status->value === 'pending')
    <div class="grid-2" style="margin-top:1rem;">
        <form class="panel" method="POST" action="{{ route('admin.deposits.approve', $deposit) }}">@csrf<button class="btn-z ok" type="submit">Approuver</button></form>
        <form class="panel" method="POST" action="{{ route('admin.deposits.reject', $deposit) }}">@csrf<input name="reason" placeholder="Motif du refus" required><button class="btn-z danger" type="submit">Refuser</button></form>
    </div>
@else
    <p class="muted">Traité le {{ optional($deposit->reviewed_at)->format('d/m/Y H:i') }} par {{ $deposit->reviewer->name ?? '—' }}. {{ $deposit->rejection_reason }}</p>
@endif
@endsection
