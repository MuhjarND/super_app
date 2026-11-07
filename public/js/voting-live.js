(function () {
    'use strict';
    document.querySelectorAll('[data-voting-live]').forEach(function (root) {
        const status = root.querySelector('[data-live-connection]');
        const retry = root.querySelector('[data-live-refresh]');
        const results = root.querySelector('[data-results-grid]');
        const numbers = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
        let timer = null, controller = null, running = false, lastSnapshot = '';

        function node(tag, className, text) {
            const element = document.createElement(tag);
            if (className) element.className = className;
            if (text !== undefined) element.textContent = text;
            return element;
        }

        function render(data) {
            ['total_participants', 'total_voted', 'pending_count'].forEach(function (key) {
                root.querySelectorAll('[data-live-count="' + key + '"]').forEach(function (el) { el.textContent = numbers.format(data[key]); });
            });
            root.querySelector('[data-live-voting-status]').textContent = ({ draft:'Draft', aktif:'Aktif', selesai:'Selesai' })[data.status] || data.status;
            root.querySelector('[data-live-complete]').hidden = !data.all_voted;
            document.querySelectorAll('[data-live-ballot-editor]').forEach(function (el) { el.hidden = data.total_voted > 0; });
            root.querySelectorAll('[data-live-lock-notice]').forEach(function (el) { el.hidden = data.total_voted === 0; });
            root.querySelectorAll('option[value="draft"]').forEach(function (el) { el.disabled = data.total_voted > 0; });
            const pending = root.querySelector('[data-live-pending]');
            if (pending) {
                pending.replaceChildren();
                data.pending.forEach(function (person) { pending.appendChild(node('div', 'voting-pending-name', person.name)); });
                if (!data.pending.length) pending.appendChild(node('div', '', data.total_participants ? 'Semua peserta sudah voting.' : 'Belum ada peserta terdaftar.'));
            }
            results.replaceChildren();
            data.items.forEach(function (item) {
                const card = node('section', 'voting-result-card');
                card.dataset.resultItem = item.id;
                const head = node('div', 'voting-result-header'), title = node('div');
                title.append(node('h2', '', item.judul), node('span', 'voting-result-meta', item.uses_candidates ? 'Pemilihan kandidat' : 'Voting pilihan jawaban'));
                head.append(title, node('span', 'voting-result-total', numbers.format(item.total_votes) + ' suara'));
                card.appendChild(head);
                const top = Math.max(0, ...item.candidates.map(function (choice) { return choice.count; }));
                item.candidates.forEach(function (choice) {
                    const row = node('div', 'voting-choice-result');
                    row.dataset.resultChoice = choice.id;
                    row.dataset.leading = String(choice.count > 0 && choice.count === top);
                    const heading = node('div', 'voting-choice-header'), identity = node('strong', 'voting-choice-identity');
                    if (choice.image_url) {
                        const url = new URL(choice.image_url, location.href);
                        if (url.origin === location.origin && ['http:', 'https:'].includes(url.protocol)) {
                            const img = node('img', 'voting-choice-image'); img.src = url.href; img.alt = ''; identity.appendChild(img);
                        }
                    }
                    identity.appendChild(node('span', '', choice.nama));
                    heading.append(identity, node('span', 'voting-choice-count', numbers.format(choice.count) + ' suara (' + numbers.format(choice.percentage) + '%)'));
                    const track = node('div', 'voting-choice-track'), fill = node('div', 'voting-choice-fill');
                    fill.style.width = Math.max(0, Math.min(100, choice.percentage)) + '%';
                    track.appendChild(fill); row.append(heading, track); card.appendChild(row);
                });
                results.appendChild(card);
            });
        }

        async function refresh() {
            if (running || document.hidden) return;
            clearTimeout(timer);
            running = true;
            retry.disabled = true;
            controller = new AbortController();
            const timeout = setTimeout(function () { controller.abort(); }, 10000);
            try {
                const response = await fetch(root.dataset.statsUrl, { headers: { Accept: 'application/json' }, cache:'no-store', signal:controller.signal });
                if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('unavailable');
                const data = await response.json();
                const snapshot = JSON.stringify({ ...data, updated_at: null });
                if (snapshot !== lastSnapshot) { render(data); lastSnapshot = snapshot; }
                status.dataset.state = 'connected';
                status.textContent = 'Terhubung · diperbarui ' + new Date().toLocaleTimeString('id-ID', { timeZone:'Asia/Jayapura' }) + ' WIT · otomatis setiap 2 detik';
            } catch (error) {
                status.dataset.state = 'error';
                status.textContent = 'Koneksi terputus. Hasil terakhir tetap ditampilkan; mencoba kembali…';
            } finally {
                clearTimeout(timeout); running = false; retry.disabled = false;
                timer = setTimeout(refresh, status.dataset.state === 'error' ? 5000 : 2000);
            }
        }
        retry.addEventListener('click', refresh);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); else clearTimeout(timer); });
        window.addEventListener('online', refresh);
        window.addEventListener('pagehide', function () { clearTimeout(timer); if (controller) controller.abort(); });
        refresh();
    });
}());
