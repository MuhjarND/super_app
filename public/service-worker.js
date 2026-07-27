'use strict';

const CACHE_NAME = 'papeda-shell-v1';
const SHELL_ASSETS = [
    '/site.webmanifest',
    '/icons/logo-app-192.png',
    '/icons/logo-app-512.png'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(SHELL_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('push', event => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (_error) {
        data = { body: event.data ? event.data.text() : 'Terdapat tindak lanjut baru.' };
    }

    const title = data.title || 'PAPEDA';
    const options = {
        body: data.body || 'Terdapat tindak lanjut baru untuk Anda.',
        icon: '/icons/logo-app-192.png',
        badge: '/icons/logo-app-192.png',
        tag: data.tag || 'papeda-notification',
        renotify: true,
        vibrate: [180, 80, 180],
        data: {
            url: data.url || '/tindak-lanjut-terpadu',
            module: data.module || 'general'
        }
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = new URL(event.notification.data.url || '/tindak-lanjut-terpadu', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
            for (const client of clients) {
                if ('focus' in client && client.url === targetUrl) {
                    return client.focus();
                }
            }

            return self.clients.openWindow ? self.clients.openWindow(targetUrl) : null;
        })
    );
});
