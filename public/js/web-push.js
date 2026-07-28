(function () {
    'use strict';

    const button = document.getElementById('webPushToggle');
    if (!button || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        if (button) {
            button.classList.add('d-none');
        }
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const configUrl = button.dataset.configUrl;
    const storeUrl = button.dataset.storeUrl;
    const destroyUrl = button.dataset.destroyUrl;
    let publicKey = null;
    let registration = null;
    let subscribed = false;

    function base64ToUint8Array(value) {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        return Uint8Array.from(Array.prototype.map.call(raw, char => char.charCodeAt(0)));
    }

    function setState(active) {
        subscribed = active;
        button.classList.toggle('is-active', active);
        button.title = active ? 'Notifikasi perangkat aktif' : 'Aktifkan notifikasi perangkat';
        button.setAttribute('aria-label', button.title);
        const icon = button.querySelector('i');
        if (icon) {
            icon.style.color = active ? '#10b981' : '#64748b';
        }
    }

    function feedback(message, isError) {
        if (window.toastr) {
            (isError ? window.toastr.error : window.toastr.success)(message);
            return;
        }
        window.alert(message);
    }

    async function saveSubscription(subscription) {
        const json = subscription.toJSON();
        const response = await fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({
                endpoint: json.endpoint,
                keys: json.keys,
                content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0]
            }),
            showLoader: false
        });

        if (!response.ok) {
            throw new Error('Penyimpanan langganan notifikasi gagal.');
        }
    }

    async function initialize() {
        const response = await fetch(configUrl, {
            headers: { 'Accept': 'application/json' },
            showLoader: false
        });
        const config = await response.json();

        if (!config.enabled || !config.public_key) {
            button.classList.add('d-none');
            return;
        }

        publicKey = config.public_key;
        registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/',
            updateViaCache: 'none'
        });
        await registration.update().catch(function () {
            return null;
        });
        const subscription = await registration.pushManager.getSubscription();
        setState(Boolean(subscription));

        if (subscription) {
            await saveSubscription(subscription);
        }
    }

    button.addEventListener('click', async function (event) {
        event.preventDefault();
        button.classList.add('disabled');

        try {
            registration = registration || await navigator.serviceWorker.ready;
            let subscription = await registration.pushManager.getSubscription();

            if (subscribed && subscription) {
                const endpoint = subscription.endpoint;
                await subscription.unsubscribe();
                await fetch(destroyUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ endpoint: endpoint }),
                    showLoader: false
                });
                setState(false);
                feedback('Notifikasi perangkat dinonaktifkan.', false);
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Izin notifikasi belum diberikan pada perangkat ini.');
            }

            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: base64ToUint8Array(publicKey)
            });
            await saveSubscription(subscription);
            setState(true);
            await registration.showNotification('PAPEDA', {
                body: 'Notifikasi PAPEDA berhasil diaktifkan pada perangkat ini.',
                icon: '/icons/logo-app-192.png',
                badge: '/icons/logo-app-192.png',
                tag: 'papeda-notification-enabled',
                timestamp: Date.now(),
                vibrate: [180, 80, 180],
                actions: [
                    {
                        action: 'open-papeda',
                        title: 'Buka PAPEDA'
                    }
                ],
                data: {
                    url: '/dashboard',
                    module: 'general'
                }
            });
            feedback('Notifikasi perangkat berhasil diaktifkan.', false);
        } catch (error) {
            feedback(error.message || 'Notifikasi perangkat tidak dapat diaktifkan.', true);
        } finally {
            button.classList.remove('disabled');
        }
    });

    initialize().catch(function () {
        button.classList.add('d-none');
    });
})();
