@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;">
    <h1 style="font-size:2.2rem;">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-z-ghost small" type="submit">Tout lire</button></form>
</div>
@forelse ($notifications as $notification)
    <article class="panel" style="margin-bottom:.6rem;{{ $notification->read_at ? 'opacity:.7;' : '' }}">
        <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
        <p>{{ $notification->data['body'] ?? '' }}</p>
        <div class="muted">{{ $notification->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
        @if (! $notification->read_at)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="btn-z-ghost small" type="submit">Marquer lu</button></form>
        @endif
    </article>
@empty
    <p class="muted">Aucune notification.</p>
@endforelse
{{ $notifications->links() }}
@endsection
