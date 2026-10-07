<meta name="vapid-public" content="{{ config('services.webpush.public_key') }}">
<meta name="notification-count" content="{{ route('notifications.count') }}">
<meta name="notification-feed" content="{{ route('notifications.feed') }}">
<script>
    (() => {
        const feedUrl = document.querySelector('meta[name="notification-feed"]')?.content;
        if (!feedUrl) {
            return;
        }

        const soundKey = 'zelvora_notification_sound';
        const cursorKey = 'zelvora_live_cursor';
        const seenKey = 'zelvora_live_seen';
        const status = document.querySelector('[data-live-status]');
        const label = document.querySelector('[data-live-label]');
        const badge = document.querySelector('[data-unread-badge]');
        const toasts = document.getElementById('live-toasts');
        let cursor = window.localStorage.getItem(cursorKey) || '';
        let seen = new Set(JSON.parse(window.localStorage.getItem(seenKey) || '[]'));
        let audio = null;
        let pendingSound = false;
        let timer = null;

        const soundEnabled = () => window.localStorage.getItem(soundKey) !== 'off';
        const remember = (id) => {
            seen.add(id);
            const ids = [...seen].slice(-40);
            seen = new Set(ids);
            window.localStorage.setItem(seenKey, JSON.stringify(ids));
        };
        const unlock = () => {
            const Context = window.AudioContext || window.webkitAudioContext;
            if (!Context) {
                return;
            }
            audio = audio || new Context();
            if (audio.state === 'suspended') {
                audio.resume();
            }
            if (pendingSound) {
                pendingSound = false;
                chime();
            }
        };
        const chime = () => {
            if (!soundEnabled()) {
                pendingSound = false;
                return;
            }
            if (!audio || audio.state !== 'running') {
                pendingSound = true;
                return;
            }
            const now = audio.currentTime;
            [740, 988].forEach((frequency, index) => {
                const oscillator = audio.createOscillator();
                const gain = audio.createGain();
                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, now);
                gain.gain.exponentialRampToValueAtTime(0.07, now + 0.02 + index * 0.08);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.26 + index * 0.08);
                oscillator.connect(gain);
                gain.connect(audio.destination);
                oscillator.start(now + index * 0.08);
                oscillator.stop(now + 0.3 + index * 0.08);
            });
        };
        const setLive = (online) => {
            status?.classList.toggle('is-live', online);
            if (label) {
                label.textContent = online ? 'En direct' : 'Reconnexion…';
            }
        };
        const paintBadge = (unread) => {
            if (!badge) {
                return;
            }
            const count = Number(unread || 0);
            badge.hidden = count < 1;
            badge.textContent = count > 0 ? String(count) : '';
        };
        const applyBalances = (balances) => {
            if (!balances) {
                return;
            }
            document.querySelectorAll('[data-balance]').forEach((card) => {
                const value = balances[card.dataset.balance];
                const amount = card.querySelector('strong');
                if (value && amount) {
                    amount.textContent = value;
                }
            });
            document.querySelectorAll('[data-balance-extra]').forEach((node) => {
                const value = balances[node.dataset.balanceExtra];
                if (value) {
                    node.textContent = value;
                }
            });
        };
        const announce = (movement) => {
            chime();
            if (!toasts || !window.bootstrap) {
                return;
            }
            const toast = document.createElement('div');
            toast.className = 'toast live-toast border-0';
            toast.setAttribute('role', 'status');
            const body = document.createElement('div');
            body.className = 'toast-body';
            const title = document.createElement('strong');
            title.textContent = movement.title || 'Nouveau mouvement';
            body.append(title);
            if (movement.body) {
                const text = document.createElement('div');
                text.textContent = movement.body;
                body.append(text);
            }
            if (movement.url) {
                const link = document.createElement('a');
                link.href = movement.url;
                link.textContent = 'Voir le détail';
                body.append(link);
            }
            toast.append(body);
            toasts.append(toast);
            window.bootstrap.Toast.getOrCreateInstance(toast, { delay: 7000 }).show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        };
        const tick = async () => {
            try {
                const url = new URL(feedUrl, window.location.origin);
                if (cursor) {
                    url.searchParams.set('since', cursor);
                }
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    setLive(false);
                    return;
                }
                const payload = await response.json();
                const knownCursor = Boolean(cursor);
                setLive(true);
                paintBadge(payload.unread);
                (payload.movements || []).forEach((movement) => {
                    if (!movement.id || seen.has(movement.id)) {
                        return;
                    }
                    remember(movement.id);
                    if (knownCursor) {
                        announce(movement);
                    }
                });
                if (payload.latest_id) {
                    remember(payload.latest_id);
                }
                applyBalances(payload.balances);
                if (payload.cursor) {
                    cursor = payload.cursor;
                    window.localStorage.setItem(cursorKey, cursor);
                }
            } catch (error) {
                setLive(false);
            }
        };
        const schedule = () => {
            window.clearTimeout(timer);
            const delay = document.hidden ? 15000 : 2500;
            timer = window.setTimeout(async () => {
                await tick();
                schedule();
            }, delay);
        };

        document.addEventListener('pointerdown', unlock, { once: true });
        document.addEventListener('keydown', unlock, { once: true });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                tick();
            }
        });
        tick().finally(schedule);
    })();
</script>
