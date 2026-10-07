<meta name="vapid-public" content="{{ config('services.webpush.public_key') }}">
<meta name="notification-count" content="{{ route('notifications.count') }}">
<script>
    (() => {
        const countUrl = document.querySelector('meta[name="notification-count"]')?.content;
        if (!countUrl) {
            return;
        }
        const storageKey = 'zelvora_notification_sound';
        let lastCount = null;
        const enabled = () => window.localStorage.getItem(storageKey) === 'on';
        const beep = () => {
            if (!enabled()) {
                return;
            }
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.frequency.value = 880;
            gain.gain.value = 0.04;
            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.12);
        };
        const poll = async () => {
            try {
                const response = await fetch(countUrl, { headers: { 'Accept': 'application/json' } });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                const unread = Number(payload.unread || 0);
                if (lastCount !== null && unread > lastCount) {
                    beep();
                }
                lastCount = unread;
            } catch (error) {
                lastCount = lastCount ?? 0;
            }
        };
        poll();
        window.setInterval(poll, 45000);
    })();
</script>
