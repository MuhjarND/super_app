(function () {
    'use strict';

    const script = document.getElementById('papedaAppBadge');
    if (!script) {
        return;
    }

    const endpoint = script.dataset.endpoint;
    let currentCount = normalizeCount(script.dataset.count) || 0;
    let lastRefreshAt = 0;

    function normalizeCount(value) {
        const count = Number(value);
        return Number.isFinite(count) ? Math.max(0, Math.floor(count)) : null;
    }

    async function applyBadge(value) {
        const count = normalizeCount(value);
        if (count === null) {
            return;
        }

        currentCount = count;

        try {
            if (count > 0 && typeof navigator.setAppBadge === 'function') {
                await navigator.setAppBadge(count);
            } else if (count === 0 && typeof navigator.clearAppBadge === 'function') {
                await navigator.clearAppBadge();
            }
        } catch (_error) {
            // Browser yang tidak mendukung badge tetap dapat memakai notifikasi biasa.
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(function (registration) {
                const worker = registration.active || registration.waiting;
                if (worker) {
                    worker.postMessage({ type: 'PAPEDA_SET_BADGE', count: count });
                }
            }).catch(function () {
                return null;
            });
        }
    }

    async function refresh(force) {
        if (!endpoint || (!force && Date.now() - lastRefreshAt < 30000)) {
            return;
        }

        lastRefreshAt = Date.now();

        try {
            const response = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
                showLoader: false
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            await applyBadge(payload.count);
        } catch (_error) {
            // Pertahankan badge terakhir ketika perangkat sedang offline.
        }
    }

    window.PapedaAppBadge = {
        set: applyBadge,
        refresh: function () {
            return refresh(true);
        },
        get count() {
            return currentCount;
        }
    };

    applyBadge(currentCount);

    window.addEventListener('pageshow', function () {
        refresh(false);
    });

    window.addEventListener('focus', function () {
        refresh(false);
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            refresh(false);
        }
    });

    window.setInterval(function () {
        if (document.visibilityState === 'visible') {
            refresh(false);
        }
    }, 60000);
})();
