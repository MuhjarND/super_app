(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('calendarFilterForm');
        const calendarEl = document.getElementById('integratedCalendar');
        const display = document.getElementById('calendarDisplay');
        const message = document.getElementById('calendarMessage');
        if (!form || !display || !calendarEl) return;
        if (typeof FullCalendar === 'undefined') {
            message.textContent = 'Kalender belum dapat dimuat. Periksa koneksi lalu muat ulang halaman.';
            message.hidden = false;
            return;
        }
        if (window.integratedCalendarInstance) window.integratedCalendarInstance.destroy();
        const palette = {
            rapat: ['Rapat', '#4f46e5', '#eef2ff'],
            agenda_pimpinan: ['Agenda Pimpinan', '#64748b', '#f1f5f9'],
            virtual_meeting: ['Virtual Meeting', '#7c3aed', '#f3e8ff'],
            cuti: ['Cuti', '#dc2626', '#fee2e2'],
            zi: ['Progress ZI', '#d97706', '#fef3c7'],
            surat_tugas: ['Surat Tugas', '#16a34a', '#dcfce7']
        };
        let activeRequest = null;
        let lastTvHeight = null;
        let resizeFrame = null;
        let wasTvMode = false;
        const fullscreenButton = document.getElementById('calendarFullscreenButton');
        const publicationForm = document.getElementById('calendarPublicationForm');
        const publicationTitle = document.getElementById('calendarPublicTitle');
        const publicationStatus = document.getElementById('calendarPublicationStatus');
        const bulkButton = document.getElementById('calendarBulkPublication');
        const bulkForm = document.getElementById('calendarBulkPublicationForm');
        const bulkList = document.getElementById('calendarBulkPublicationList');
        const bulkStatus = document.getElementById('calendarBulkPublicationStatus');
        const bulkResult = document.getElementById('calendarBulkPublicationResult');
        let bulkBatch = null;
        let reviewedBatch = null;
        let savingBulk = false;
        let selectedEvent = null;
        const fullscreenNotice = 'Fullscreen browser belum dapat diaktifkan. Mode layar TV tetap aktif.';
        const board = display.querySelector('.calendar-board');
        const witTime = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jayapura', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
        const witDate = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jayapura', year: 'numeric', month: '2-digit', day: '2-digit' });

        function showMessage(text) { message.textContent = text; message.hidden = !text; }
        async function savePublication(url, payload) {
            const response = await fetch(url, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });
            if (response.status === 409) throw new Error('Jadwal atau judul berubah. Buka ulang daftar publikasi untuk meninjau kembali.');
            if (!response.ok) throw new Error('Publikasi gagal disimpan.');
            return response.json();
        }
        function setBulkBatch(events, range) {
            if (!bulkButton) return;
            const start = range.startStr.slice(0, 10);
            const end = range.endStr.slice(0, 10);
            const from = Date.parse(start + 'T00:00:00+09:00');
            const until = Date.parse(end + 'T00:00:00+09:00');
            bulkBatch = {
                start: start,
                end: end,
                events: events.filter(function (event) {
                    return event.extendedProps.bulk_publication_title && Date.parse(event.start) < until && Date.parse(event.end) > from;
                })
            };
            bulkButton.disabled = !bulkBatch.events.length;
        }
        function tvMode() { return document.fullscreenElement === display || display.classList.contains('is-tv-mode'); }
        function witNow() {
            const parts = Object.fromEntries(witDate.formatToParts(new Date()).map(function (p) { return [p.type, p.value]; }));
            return parts.year + '-' + parts.month + '-' + parts.day + 'T' + witTime.format(new Date()) + ':00';
        }
        function updateClock() {
            document.getElementById('calendarTvClock').textContent = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Jayapura', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
            }).format(new Date()) + ' WIT';
        }
        function addListItem(parent, title, detail) {
            const item = document.createElement('div'); item.className = 'calendar-list-item';
            const heading = document.createElement('div'); heading.className = 'calendar-list-item-title'; heading.textContent = title;
            const meta = document.createElement('div'); meta.className = 'calendar-list-item-meta'; meta.textContent = detail;
            item.append(heading, meta); parent.appendChild(item);
        }
        function renderSummary(meta) {
            const counts = meta.counts || {};
            const conflicts = meta.conflicts || [];
            document.getElementById('calendarCountAll').textContent = counts.all || 0;
            const summary = document.getElementById('calendarConflictSummary');
            summary.hidden = conflicts.length === 0;
            summary.textContent = conflicts.map(function (item) { return item.date + ': ' + item.count + ' agenda pada tanggal yang sama'; }).join(' · ');
            const modules = document.getElementById('calendarModuleCounts'); modules.replaceChildren();
            Object.keys(palette).forEach(function (key) {
                const row = document.createElement('div'); row.className = 'calendar-module-count';
                const label = document.createElement('span'); label.textContent = palette[key][0];
                const count = document.createElement('strong'); count.textContent = counts[key] || 0;
                row.append(label, count); modules.appendChild(row);
            });
            const conflictList = document.getElementById('calendarConflictList'); conflictList.replaceChildren();
            conflicts.forEach(function (item) { addListItem(conflictList, item.date, item.count + ' agenda · ' + (item.titles || []).join(', ')); });
            if (!conflicts.length) addListItem(conflictList, 'Tidak ada benturan', 'Belum ada tanggal dengan beberapa agenda pada rentang ini.');
            const upcoming = document.getElementById('calendarUpcomingList'); upcoming.replaceChildren();
            (meta.upcoming || []).forEach(function (item) { addListItem(upcoming, item.title, [item.date, item.module, item.status].filter(Boolean).join(' · ')); });
            if (!(meta.upcoming || []).length) addListItem(upcoming, 'Belum ada agenda', 'Tidak ada agenda mendatang untuk filter yang dipilih.');
        }
        // Start with office hours, then expand so early, late and overnight agendas remain visible.
        function fitEventHours(events) {
            let minimum = 7; let maximum = 17;
            events.filter(function (item) { return !item.allDay; }).forEach(function (item) {
                const start = new Date(item.start); const end = item.end ? new Date(item.end) : new Date(start.getTime() + 3600000);
                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return;
                const startParts = witTime.format(start).split(':').map(Number);
                const endParts = witTime.format(end).split(':').map(Number);
                if (witDate.format(start) !== witDate.format(end)) { minimum = 0; maximum = 24; return; }
                minimum = Math.min(minimum, startParts[0]);
                maximum = Math.max(maximum, Math.min(24, endParts[0] + (endParts[1] ? 1 : 0) + 1));
            });
            calendar.batchRendering(function () {
                calendar.setOption('slotMinTime', String(minimum).padStart(2, '0') + ':00:00');
                calendar.setOption('slotMaxTime', String(maximum).padStart(2, '0') + ':00:00');
            });
        }
        function updateSize() {
            if (resizeFrame) cancelAnimationFrame(resizeFrame);
            resizeFrame = requestAnimationFrame(function () {
                resizeFrame = null;
                if (tvMode()) {
                    const height = Math.max(300, Math.floor(board.clientHeight - 18));
                    if (lastTvHeight !== height) { lastTvHeight = height; calendar.setOption('height', height); }
                } else if (lastTvHeight !== null) { lastTvHeight = null; calendar.setOption('height', 'auto'); }
                calendar.updateSize();
            });
        }

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id', timeZone: 'Asia/Jayapura', now: witNow,
            initialView: window.innerWidth < 768 ? 'listWeek' : 'timeGridWeek',
            headerToolbar: { left: 'prev,next title today', center: '', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
            buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', day: 'Hari', list: 'Agenda' },
            height: 'auto', expandRows: true, firstDay: 1, allDayText: 'Seharian', slotMinTime: '07:00:00', slotMaxTime: '17:00:00',
            slotDuration: '00:30:00', slotLabelInterval: '01:00:00', scrollTime: '07:00:00', scrollTimeReset: false,
            navLinks: true, nowIndicator: true, editable: false, dayMaxEvents: true,
            dayHeaderFormat: { weekday: 'short', day: 'numeric' },
            slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            noEventsContent: 'Tidak ada agenda pada rentang ini.',
            eventDidMount: function (info) {
                const tone = palette[info.event.extendedProps.module_key] || palette.rapat;
                info.el.style.setProperty('--calendar-event-accent', tone[1]);
                info.el.style.setProperty('--calendar-event-tint', tone[2]);
                info.el.setAttribute('aria-label', [info.event.title, info.event.extendedProps.time_label, tone[0]].filter(Boolean).join(' · '));
                info.el.title = info.el.getAttribute('aria-label');
            },
            datesSet: function (info) {
                const labels = { timeGridWeek: 'minggu ini', listWeek: 'minggu ini', dayGridMonth: 'bulan ini', timeGridDay: 'hari ini' };
                document.getElementById('calendarRangeLabel').textContent = labels[info.view.type] || 'pada rentang ini';
                updateSize();
            },
            loading: function (loading) { board.setAttribute('aria-busy', String(loading)); },
            events: function (info, success, failure) {
                if (activeRequest) activeRequest.abort();
                setBulkBatch([], info);
                const request = new AbortController(); activeRequest = request;
                const params = new URLSearchParams(new FormData(form));
                params.set('start', info.startStr); params.set('end', info.endStr); params.set('_ts', String(Date.now()));
                fetch(display.dataset.eventsUrl + '?' + params.toString(), {
                    cache: 'no-store', credentials: 'same-origin', showLoader: false, signal: request.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) {
                    if (!response.ok) throw new Error('Gagal memuat agenda.');
                    return response.json();
                }).then(function (payload) {
                    if (request.signal.aborted) { success([]); return; }
                    const events = Array.from(new Map((payload.events || []).map(function (event) { return [String(event.id), event]; })).values());
                    setBulkBatch(events, info);
                    showMessage(''); renderSummary(payload.meta || {}); fitEventHours(events); success(events); updateSize();
                }).catch(function (error) {
                    if (error.name === 'AbortError') { success([]); return; }
                    showMessage('Agenda belum dapat dimuat. Periksa koneksi lalu tekan Muat Ulang.'); failure(error);
                });
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                const props = info.event.extendedProps || {};
                if (publicationForm) {
                    selectedEvent = info.event;
                    publicationTitle.value = props.public_title || '';
                    publicationStatus.textContent = '';
                }
                const fields = { calendarEventTitle: info.event.title, calendarEventModule: props.module_label,
                    calendarEventStatus: props.status_label, calendarEventTime: props.time_label, calendarEventCategory: props.kategori,
                    calendarEventUnit: props.unit, calendarEventPic: props.pic, calendarEventLocation: props.location,
                    calendarEventParticipants: props.participants, calendarEventDescription: props.description };
                Object.keys(fields).forEach(function (id) { document.getElementById(id).textContent = fields[id] || '-'; });
                document.getElementById('calendarEventStatus').className = 'calendar-status-badge ' + (['dijadwalkan','berjalan','selesai','tertunda','overdue'].includes(props.status_key) ? props.status_key : '');
                const link = document.getElementById('calendarEventLink');
                let safeUrl = null;
                try { const target = new URL(info.event.url, window.location.href); if (info.event.url && target.origin === window.location.origin) safeUrl = target.href; } catch (error) { /* No source link available. */ }
                link.href = safeUrl || '#'; link.classList.toggle('d-none', !safeUrl);
                document.getElementById('calendarEventLinkFallback').classList.toggle('d-none', !!safeUrl);
                $('#calendarEventModal').modal('show');
            }
        });

        if (publicationForm) {
            publicationForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (!selectedEvent) return;

                const item = selectedEvent;
                const button = publicationForm.querySelector('button[type="submit"]');
                button.disabled = true;
                publicationStatus.textContent = 'Menyimpan...';

                try {
                    const data = await savePublication(publicationForm.action, {
                        event_key: item.id,
                        start: item.start.toISOString(),
                        public_title: publicationTitle.value.trim() || null
                    });
                    item.setExtendedProp('public_title', data.public_title);
                    calendar.refetchEvents();
                    if (selectedEvent === item) {
                        publicationStatus.textContent = data.public_title ? 'Publikasi disimpan.' : 'Publikasi ditarik.';
                    }
                } catch (error) {
                    if (selectedEvent === item) publicationStatus.textContent = 'Publikasi gagal disimpan.';
                } finally {
                    button.disabled = false;
                }
            });
        }
        if (bulkForm) {
            const submit = bulkForm.querySelector('button[type="submit"]');
            const limit = Number(bulkForm.dataset.limit);
            const selected = function () { return Array.from(bulkList.querySelectorAll('input:checked')); };

            function updateBulkStatus() {
                if (savingBulk) return;
                const count = selected().length;
                submit.disabled = count === 0 || count > limit;
                bulkStatus.textContent = count > limit ? 'Pilih maksimal ' + limit + ' kegiatan.' : count + ' kegiatan dipilih.';
            }

            bulkButton.addEventListener('click', function () {
                if (savingBulk || !bulkBatch || !bulkBatch.events.length) return;
                reviewedBatch = bulkBatch;
                const rows = reviewedBatch.events.map(function (event, index) {
                    const row = document.createElement('label');
                    row.className = 'd-flex align-items-start border-bottom py-2 mb-0';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'mr-2 mt-1';
                    checkbox.value = String(index);
                    checkbox.checked = true;
                    const text = document.createElement('span');
                    text.textContent = event.extendedProps.bulk_publication_title;
                    const details = document.createElement('small');
                    details.className = 'd-block text-muted';
                    details.textContent = event.extendedProps.time_label || '';
                    text.appendChild(details);
                    row.append(checkbox, text);
                    return row;
                });
                bulkList.replaceChildren(...rows);
                bulkResult.textContent = '';
                updateBulkStatus();
                $('#calendarBulkPublicationModal').modal('show');
            });

            bulkList.addEventListener('change', updateBulkStatus);
            bulkForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const checked = selected();
                if (savingBulk || !reviewedBatch || !checked.length || checked.length > limit || submit.disabled) return;
                const events = checked.map(function (checkbox) {
                    const item = reviewedBatch.events[Number(checkbox.value)];
                    return { id: item.id, title: item.extendedProps.bulk_publication_title };
                });
                submit.disabled = true;
                savingBulk = true;
                bulkStatus.textContent = 'Menyimpan...';

                try {
                    const data = await savePublication(bulkForm.action, {
                        start: reviewedBatch.start, end: reviewedBatch.end, events: events
                    });
                    $('#calendarBulkPublicationModal').modal('hide');
                    bulkResult.textContent = data.published + ' kegiatan dipublikasikan.'
                        + (data.skipped ? ' ' + data.skipped + ' kegiatan sudah dipublikasikan sebelumnya.' : '');
                    calendar.refetchEvents();
                } catch (error) {
                    savingBulk = false;
                    updateBulkStatus();
                    bulkStatus.textContent = error.message;
                    calendar.refetchEvents();
                } finally {
                    savingBulk = false;
                }
            });
        }
        window.integratedCalendarInstance = calendar;
        calendar.render(); updateClock();

        function refreshCalendar() { if (!document.hidden) calendar.refetchEvents(); }
        form.addEventListener('submit', function (event) { event.preventDefault(); refreshCalendar(); });
        form.querySelectorAll('select').forEach(function (element) { element.addEventListener('change', refreshCalendar); });
        function syncFullscreenState() {
            const enabled = tvMode(); document.body.classList.toggle('calendar-tv-active', enabled);
            if (!enabled && message.textContent === fullscreenNotice) showMessage('');
            fullscreenButton.setAttribute('aria-pressed', String(enabled));
            fullscreenButton.querySelector('span').textContent = enabled ? 'Keluar Fullscreen' : 'Fullscreen / Layar TV';
            fullscreenButton.querySelector('i').className = 'fas ' + (enabled ? 'fa-compress' : 'fa-expand') + ' mr-2';
            if (wasTvMode && !enabled) fullscreenButton.focus({ preventScroll: true });
            wasTvMode = enabled; updateClock(); updateSize();
        }
        async function leaveFullscreen() {
            if (document.fullscreenElement === display) await document.exitFullscreen();
            display.classList.remove('is-tv-mode'); syncFullscreenState();
        }
        fullscreenButton.addEventListener('click', async function () {
            if (tvMode()) { await leaveFullscreen(); return; }
            try {
                if (!display.requestFullscreen) throw new Error('Fullscreen unavailable');
                await display.requestFullscreen(); syncFullscreenState();
            } catch (error) {
                display.classList.add('is-tv-mode'); syncFullscreenState();
                showMessage(fullscreenNotice);
            }
        });
        document.addEventListener('fullscreenchange', syncFullscreenState);
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && display.classList.contains('is-tv-mode')) { display.classList.remove('is-tv-mode'); syncFullscreenState(); }
        });
        window.addEventListener('resize', updateSize);
        window.addEventListener('pageshow', function (event) { if (event.persisted) refreshCalendar(); });
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { updateClock(); refreshCalendar(); } });
        const refreshTimer = setInterval(function () { if (tvMode()) { updateClock(); refreshCalendar(); } }, 60000);
        const clockTimer = setInterval(updateClock, 30000);
        window.addEventListener('pagehide', function (event) {
            if (activeRequest) activeRequest.abort();
            if (!event.persisted) { clearInterval(refreshTimer); clearInterval(clockTimer); }
        });
    });
}());
