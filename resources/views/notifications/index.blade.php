@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div style="display:flex;justify-content:space-between;gap:1rem;align-items:center;flex-wrap:wrap;">
    <h1 style="font-size:2.2rem;">Notifications</h1>
    <div style="display:flex;gap:.5rem;">
        <button class="btn-z-ghost small" type="button" id="toggle-sound">Activer le son</button>
        <button class="btn-z-ghost small" type="button" id="enable-push">Activer les notifications</button>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-z-ghost small" type="submit">Tout lire</button></form>
    </div>
</div>
<p class="fine-print" id="push-status">Le son des mouvements est activé. Le navigateur l’autorise après la première action sur la page. Une notification système n’est envoyée que si vous l’acceptez.</p>
@forelse ($notifications as $notification)
    <article class="panel" style="margin-bottom:.6rem;{{ $notification->read_at ? 'opacity:.7;' : '' }}">
        <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
        <p>{{ $notification->data['body'] ?? '' }}</p>
        <div class="muted">{{ $notification->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $notification->data['kind'] ?? 'message' }}</div>
        @if (is_string($notification->data['url'] ?? null) && (str_starts_with($notification->data['url'], '/') || str_starts_with($notification->data['url'], url('/'))))
            <p><a href="{{ $notification->data['url'] }}">Voir le détail</a></p>
        @endif
        @if (! $notification->read_at)
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button class="btn-z-ghost small" type="submit">Marquer lu</button></form>
        @endif
    </article>
@empty
    <p class="muted">Aucune notification.</p>
@endforelse
{{ $notifications->links() }}
<script>
    const soundKey = 'zelvora_notification_sound';
    const soundButton = document.getElementById('toggle-sound');
    const paintSound = () => {
        if (!soundButton) {
            return;
        }
        const on = window.localStorage.getItem(soundKey) !== 'off';
        soundButton.textContent = on ? 'Désactiver le son' : 'Activer le son';
    };
    paintSound();
    soundButton?.addEventListener('click', () => {
        const on = window.localStorage.getItem(soundKey) !== 'off';
        window.localStorage.setItem(soundKey, on ? 'off' : 'on');
        paintSound();
    });
    document.getElementById('enable-push')?.addEventListener('click', async () => {
        const status = document.getElementById('push-status');
        if (!('Notification' in window)) {
            status.textContent = 'Ce navigateur ne propose pas les notifications système.';
            return;
        }
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            status.textContent = 'Les notifications système restent désactivées.';
            return;
        }
        const key = document.querySelector('meta[name="vapid-public"]')?.content || '';
        if (!key || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            status.textContent = 'Notifications autorisées sur cet appareil. L’envoi à distance attend les clés du serveur.';
            return;
        }
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: decodeKey(key),
        });
        const token = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;
        await fetch('{{ route('notifications.push') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || '',
            },
            body: JSON.stringify(subscription),
        });
        status.textContent = 'Notifications système activées pour cet appareil.';
    });
    function decodeKey(value) {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = atob(base64);
        const output = new Uint8Array(raw.length);
        for (let index = 0; index < raw.length; index += 1) {
            output[index] = raw.charCodeAt(index);
        }
        return output;
    }
</script>
@endsection
