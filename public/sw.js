self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }
});

self.addEventListener('push', (event) => {
    let payload = { title: 'ZELVORA', body: 'Nouvelle notification', url: '/notifications' };

    try {
        payload = { ...payload, ...(event.data ? event.data.json() : {}) };
    } catch (error) {
        payload.body = event.data ? event.data.text() : payload.body;
    }

    event.waitUntil(self.registration.showNotification(payload.title, {
        body: payload.body,
        icon: '/images/icons/icon-192.png',
        data: { url: payload.url || '/notifications' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/notifications';
    event.waitUntil(clients.openWindow(url));
});
