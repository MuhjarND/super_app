@extends('layouts.app')

@section('title', 'Kalender Terpadu')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
    <style>
        .calendar-shell { display: grid; gap: 18px; }
        .calendar-chip { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; background: #ffffff; border: 1px solid #dbe7ff; padding: 9px 13px; color: #312e81; font-size: 0.8rem; font-weight: 700; }
        .calendar-layout { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 18px; align-items: start; }
        .calendar-board, .calendar-side { background: #fff; border: 1px solid #e8eaed; border-radius: 22px; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05); overflow: hidden; }
        .calendar-filter-bar { padding: 18px 20px; border-bottom: 1px solid #eef2f7; display: grid; grid-template-columns: 180px 220px 180px auto; gap: 12px; align-items: end; }
        .calendar-filter-group label { display: block; margin-bottom: 6px; font-size: 0.76rem; font-weight: 700; color: #475569; }
        .calendar-toggle-group { display: flex; flex-wrap: wrap; gap: 8px; min-height: 42px; align-items: center; }
        .calendar-toggle-chip { position: relative; }
        .calendar-toggle-chip input { position: absolute; opacity: 0; pointer-events: none; }
        .calendar-toggle-chip span { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; border: 1px solid #dbe4f0; background: #fff; color: #475569; padding: 9px 12px; font-size: 0.79rem; font-weight: 700; cursor: pointer; transition: all 0.15s ease; }
        .calendar-toggle-chip input:checked + span { color: #4338ca; border-color: #c7d2fe; background: #eef2ff; box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.12); }
        .calendar-workspace { padding: 16px 18px 20px; }
        .calendar-canvas { border: 1px solid #e5edf8; border-radius: 18px; background: #fff; padding: 14px; }
        .calendar-side-head { padding: 18px 20px 14px; border-bottom: 1px solid #eef2f7; }
        .calendar-side-head h5 { margin: 0; font-size: 0.98rem; font-weight: 800; color: #0f172a; }
        .calendar-side-head p { margin: 4px 0 0; font-size: 0.78rem; color: #64748b; }
        .calendar-side-body { padding: 16px 18px 20px; display: grid; gap: 16px; }
        .calendar-stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .calendar-stat-box { border: 1px solid #e5edf8; background: #f8fbff; border-radius: 14px; padding: 14px; }
        .calendar-stat-box .value { font-size: 1.2rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 6px; }
        .calendar-stat-box .label { font-size: 0.76rem; color: #64748b; line-height: 1.35; }
        .calendar-list { display: grid; gap: 10px; }
        .calendar-list-item { border: 1px solid #edf2f7; border-radius: 14px; padding: 12px 13px; background: #fff; }
        .calendar-list-item-title { font-size: 0.82rem; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
        .calendar-list-item-meta { font-size: 0.74rem; color: #64748b; line-height: 1.45; }
        .calendar-legend { display: grid; gap: 8px; }
        .calendar-legend-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 0.79rem; color: #334155; }
        .calendar-legend-label { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; }
        .calendar-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; }
        .calendar-dot.rapat { background: #4f46e5; }
        .calendar-dot.agenda-pimpinan { background: #64748b; }
        .calendar-dot.virtual-meeting { background: #7c3aed; }
        .calendar-dot.cuti { background: #dc2626; }
        .calendar-dot.zi { background: #d97706; }
        .calendar-dot.surat-tugas { background: #16a34a; }
        .fc .fc-toolbar.fc-header-toolbar { margin-bottom: 1rem; gap: 10px; flex-wrap: wrap; }
        .fc .fc-toolbar-title { font-size: 1.08rem; font-weight: 800; color: #0f172a; }
        .fc .fc-button { background: #fff !important; border: 1px solid #dbe4f0 !important; color: #475569 !important; box-shadow: none !important; border-radius: 10px !important; padding: 0.45rem 0.8rem !important; font-weight: 700; text-transform: capitalize; }
        .fc .fc-button.fc-button-active, .fc .fc-button:hover { background: #eef2ff !important; color: #4338ca !important; border-color: #c7d2fe !important; }
        .fc-theme-standard th, .fc-theme-standard td, .fc-theme-standard .fc-scrollgrid { border-color: #edf2f7; }
        .fc .fc-col-header-cell-cushion, .fc .fc-daygrid-day-number { color: #334155; font-weight: 700; text-decoration: none; }
        .fc .fc-event { border: none; border-radius: 10px; padding: 2px 4px; font-size: 0.74rem; font-weight: 700; box-shadow: none; }
        .fc .fc-daygrid-dot-event {
            display: block;
            margin-top: 3px;
            padding: 5px 8px;
            border-radius: 8px;
            background: var(--fc-event-bg-color, #4f46e5);
            color: var(--fc-event-text-color, #ffffff);
            border: none;
            box-shadow: none;
        }
        .fc .fc-daygrid-dot-event:hover {
            background: var(--fc-event-bg-color, #4f46e5);
            color: var(--fc-event-text-color, #ffffff);
        }
        .fc .fc-daygrid-dot-event .fc-event-title,
        .fc .fc-daygrid-dot-event .fc-event-time {
            color: inherit;
        }
        .fc .fc-daygrid-dot-event .fc-event-title {
            font-weight: 700;
        }
        .fc .fc-daygrid-dot-event .fc-daygrid-event-dot {
            display: none;
        }
        .fc .fc-list-event-title a, .fc .fc-list-event-time { color: #0f172a; text-decoration: none; }
        .calendar-status-badge, .calendar-module-badge { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: 0.72rem; font-weight: 700; }
        .calendar-module-badge { background: #eef2ff; color: #4338ca; }
        .calendar-status-badge.dijadwalkan { background: #e0f2fe; color: #075985; }
        .calendar-status-badge.berjalan { background: #e0e7ff; color: #4338ca; }
        .calendar-status-badge.selesai { background: #dcfce7; color: #166534; }
        .calendar-status-badge.tertunda { background: #fee2e2; color: #b91c1c; }
        .calendar-status-badge.overdue { background: #ffedd5; color: #c2410c; }
        .calendar-modal-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .calendar-modal-meta-item { border: 1px solid #eef2f7; background: #f8fafc; border-radius: 14px; padding: 12px 13px; }
        .calendar-modal-meta-label { font-size: 0.72rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 5px; }
        .calendar-modal-meta-value { font-size: 0.84rem; color: #0f172a; font-weight: 600; line-height: 1.45; }
        .calendar-modal-description { margin-top: 16px; border-top: 1px solid #eef2f7; padding-top: 14px; color: #475569; line-height: 1.6; font-size: 0.88rem; }
        @media (max-width: 1199.98px) { .calendar-layout { grid-template-columns: 1fr; } }
        @media (max-width: 991.98px) {
            .calendar-filter-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .calendar-workspace { padding: 14px; }
            .calendar-canvas { padding: 10px; overflow-x: auto; }
        }
        @media (max-width: 767.98px) {
            .calendar-board,
            .calendar-side {
                border-radius: 14px;
            }

            .calendar-filter-bar { grid-template-columns: 1fr; }
            .calendar-modal-meta, .calendar-stat-grid { grid-template-columns: 1fr; }
            .fc .fc-toolbar.fc-header-toolbar {
                align-items: stretch;
            }
            .fc .fc-toolbar-title {
                font-size: 0.96rem;
            }
            .fc .fc-button {
                padding: 0.4rem 0.6rem !important;
                font-size: 0.76rem !important;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="page-header-card">
        <div>
            <h3>Kalender Terpadu</h3>
            <p class="text-muted mb-0">Monitoring rapat, agenda pimpinan, virtual meeting, cuti, surat tugas, dan Progress ZI.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="calendar-shell">
<div class="calendar-layout">
            <section class="calendar-board">
                <form id="calendarFilterForm" class="calendar-filter-bar">
                    <div class="calendar-filter-group">
                        <label for="calendarScope">Scope</label>
                        <select class="form-control" id="calendarScope" name="scope">
                            <option value="all" {{ ($calendarFilters['scope'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="mine" {{ ($calendarFilters['scope'] ?? '') === 'mine' ? 'selected' : '' }}>Saya</option>
                        </select>
                    </div>

                    <div class="calendar-filter-group">
                        <label for="calendarUnit">Unit</label>
                        <select class="form-control" id="calendarUnit" name="unit_id">
                            <option value="">Semua unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (string) ($calendarFilters['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="calendar-filter-group">
                        <label for="calendarStatus">Status</label>
                        <select class="form-control" id="calendarStatus" name="status">
                            <option value="">Semua status</option>
                            <option value="dijadwalkan" {{ ($calendarFilters['status'] ?? '') === 'dijadwalkan' ? 'selected' : '' }}>Dijadwalkan</option>
                            <option value="berjalan" {{ ($calendarFilters['status'] ?? '') === 'berjalan' ? 'selected' : '' }}>Berjalan</option>
                            <option value="selesai" {{ ($calendarFilters['status'] ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                            <option value="tertunda" {{ ($calendarFilters['status'] ?? '') === 'tertunda' ? 'selected' : '' }}>Tertunda</option>
                            <option value="overdue" {{ ($calendarFilters['status'] ?? '') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
                    </div>
                    <div class="calendar-filter-group d-flex">
                        <button type="submit" class="btn app-create-btn btn-block"><i class="fas fa-sync-alt"></i> Muat Ulang</button>
                    </div>
                </form>

                <div class="calendar-workspace"><div class="calendar-canvas"><div id="integratedCalendar"></div></div></div>
            </section>

            <aside class="calendar-side">
                <div class="calendar-side-head">
                    <h5>Ringkasan kalender</h5>
                    <p>Jumlah event aktif, benturan jadwal, dan event mendatang pada rentang yang sedang dibuka.</p>
                </div>
                <div class="calendar-side-body">
                    <div class="calendar-stat-grid">
                        <div class="calendar-stat-box"><div class="value" id="calendarCountAll">0</div><div class="label">Total event</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarConflictCount">0</div><div class="label">Tanggal benturan</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarCountRapat">0</div><div class="label">Rapat</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarCountAgendaPimpinan">0</div><div class="label">Agenda pimpinan</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarCountVirtualMeeting">0</div><div class="label">Virtual meeting</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarCountCuti">0</div><div class="label">Cuti</div></div>
                        <div class="calendar-stat-box"><div class="value" id="calendarCountSuratTugas">0</div><div class="label">Surat tugas</div></div>
                    </div>

                    <section>
                        <div class="calendar-side-head p-0 border-0 mb-2"><div><h5 style="font-size:0.9rem;">Legenda modul</h5><p>Ringkasan warna event dari modul yang sudah terhubung ke kalender.</p></div></div>
                        <div class="calendar-legend">
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot rapat"></span> Rapat</span><span id="calendarLegendRapat">0</span></div>
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot agenda-pimpinan"></span> Agenda Pimpinan</span><span id="calendarLegendAgendaPimpinan">0</span></div>
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot virtual-meeting"></span> Virtual Meeting</span><span id="calendarLegendVirtualMeeting">0</span></div>
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot cuti"></span> Cuti</span><span id="calendarLegendCuti">0</span></div>
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot zi"></span> Progress ZI</span><span id="calendarLegendZi">0</span></div>
                            <div class="calendar-legend-row"><span class="calendar-legend-label"><span class="calendar-dot surat-tugas"></span> Surat Tugas</span><span id="calendarLegendSuratTugas">0</span></div>
                        </div>
                    </section>

                    <section>
                        <div class="calendar-side-head p-0 border-0 mb-2"><div><h5 style="font-size:0.9rem;">Benturan jadwal</h5><p>Tanggal yang memuat lebih dari satu agenda pada hasil filter saat ini.</p></div></div>
                        <div id="calendarConflictList" class="calendar-list"><div class="calendar-list-item"><div class="calendar-list-item-title">Belum ada data</div><div class="calendar-list-item-meta">Kalender akan menampilkan benturan setelah event dimuat.</div></div></div>
                    </section>

                    <section>
                        <div class="calendar-side-head p-0 border-0 mb-2"><div><h5 style="font-size:0.9rem;">Agenda mendatang</h5><p>Event berikutnya berdasarkan filter dan rentang kalender saat ini.</p></div></div>
                        <div id="calendarUpcomingList" class="calendar-list"><div class="calendar-list-item"><div class="calendar-list-item-title">Belum ada data</div><div class="calendar-list-item-meta">Event mendatang akan tampil setelah kalender dimuat.</div></div></div>
                    </section>
                </div>
            </aside>
        </div>
    </div>

    <div class="modal fade" id="calendarEventModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="d-flex flex-wrap align-items-center mb-2" style="gap:8px;"><span class="calendar-module-badge" id="calendarEventModule">Modul</span><span class="calendar-status-badge" id="calendarEventStatus">Status</span></div>
                        <h5 class="modal-title mb-0" id="calendarEventTitle">Detail event</h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="calendar-modal-meta">
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Waktu</div><div class="calendar-modal-meta-value" id="calendarEventTime">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Kategori</div><div class="calendar-modal-meta-value" id="calendarEventCategory">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Unit Kerja</div><div class="calendar-modal-meta-value" id="calendarEventUnit">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">PIC / Pelaksana</div><div class="calendar-modal-meta-value" id="calendarEventPic">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Lokasi / Referensi</div><div class="calendar-modal-meta-value" id="calendarEventLocation">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Petugas / Peserta</div><div class="calendar-modal-meta-value" id="calendarEventParticipants">-</div></div>
                        <div class="calendar-modal-meta-item"><div class="calendar-modal-meta-label">Aksi</div><div class="calendar-modal-meta-value"><a href="#" class="btn btn-primary btn-sm d-none" id="calendarEventLink" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-1"></i> Buka Modul Asal</a><span id="calendarEventLinkFallback">Tidak ada tautan langsung.</span></div></div>
                    </div>
                    <div class="calendar-modal-description" id="calendarEventDescription">-</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/id.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('calendarFilterForm');
            const calendarEl = document.getElementById('integratedCalendar');

            function serializeFilters(extra = {}) {
                const formData = new FormData(form);
                const params = new URLSearchParams();
                formData.forEach(function (value, key) {
                    if (value !== '') {
                        params.append(key, value);
                    }
                });
                Object.keys(extra).forEach(function (key) {
                    if (extra[key]) {
                        params.set(key, extra[key]);
                    }
                });
                return params;
            }

            function renderSummary(meta) {
                meta = meta || { counts: {}, conflicts: [], upcoming: [] };
                document.getElementById('calendarCountAll').textContent = meta.counts.all || 0;
                document.getElementById('calendarCountRapat').textContent = meta.counts.rapat || 0;
                document.getElementById('calendarCountAgendaPimpinan').textContent = meta.counts.agenda_pimpinan || 0;
                document.getElementById('calendarCountVirtualMeeting').textContent = meta.counts.virtual_meeting || 0;
                document.getElementById('calendarCountCuti').textContent = meta.counts.cuti || 0;
                document.getElementById('calendarCountSuratTugas').textContent = meta.counts.surat_tugas || 0;
                document.getElementById('calendarLegendRapat').textContent = meta.counts.rapat || 0;
                document.getElementById('calendarLegendAgendaPimpinan').textContent = meta.counts.agenda_pimpinan || 0;
                document.getElementById('calendarLegendVirtualMeeting').textContent = meta.counts.virtual_meeting || 0;
                document.getElementById('calendarLegendCuti').textContent = meta.counts.cuti || 0;
                document.getElementById('calendarLegendZi').textContent = meta.counts.zi || 0;
                document.getElementById('calendarLegendSuratTugas').textContent = meta.counts.surat_tugas || 0;
                document.getElementById('calendarConflictCount').textContent = (meta.conflicts || []).length;
                const conflictList = document.getElementById('calendarConflictList');
                if ((meta.conflicts || []).length) {
                    conflictList.innerHTML = meta.conflicts.map(function (conflict) { return `<div class="calendar-list-item"><div class="calendar-list-item-title">${conflict.date}</div><div class="calendar-list-item-meta">${conflict.count} event pada tanggal yang sama.<br>${conflict.titles.join(', ')}</div></div>`; }).join('');
                } else {
                    conflictList.innerHTML = '<div class="calendar-list-item"><div class="calendar-list-item-title">Tidak ada benturan</div><div class="calendar-list-item-meta">Rentang kalender saat ini belum menunjukkan benturan jadwal.</div></div>';
                }
                const upcomingList = document.getElementById('calendarUpcomingList');
                if ((meta.upcoming || []).length) {
                    upcomingList.innerHTML = meta.upcoming.map(function (item) { return `<div class="calendar-list-item"><div class="calendar-list-item-title">${item.title}</div><div class="calendar-list-item-meta">${item.date} &bull; ${item.module} &bull; ${item.status}</div></div>`; }).join('');
                } else {
                    upcomingList.innerHTML = '<div class="calendar-list-item"><div class="calendar-list-item-title">Belum ada agenda</div><div class="calendar-list-item-meta">Tidak ada event mendatang untuk filter yang dipilih.</div></div>';
                }
            }

            const isMobileViewport = window.innerWidth < 768;

            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'id',
                initialView: isMobileViewport ? 'listWeek' : 'dayGridMonth',
                headerToolbar: isMobileViewport
                    ? { left: 'prev,next', center: 'title', right: 'listWeek,timeGridDay' }
                    : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', day: 'Hari', list: 'Agenda' },
                height: 'auto',
                firstDay: 1,
                navLinks: true,
                nowIndicator: true,
                eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
                eventDidMount: function (info) {
                    const bg = info.event.backgroundColor || info.event.extendedProps.backgroundColor || '#4f46e5';
                    const text = info.event.textColor || '#ffffff';
                    info.el.style.backgroundColor = bg;
                    info.el.style.borderColor = bg;
                    info.el.style.color = text;

                    info.el.querySelectorAll('.fc-event-main, .fc-event-title, .fc-event-time, .fc-list-event-title a, .fc-list-event-time').forEach(function (node) {
                        node.style.color = text;
                    });
                },
                events: function (fetchInfo, successCallback, failureCallback) {
                    const params = serializeFilters({ start: fetchInfo.startStr, end: fetchInfo.endStr });
                    fetch(`{{ route('calendar.integrated.events') }}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function (response) {
                            if (!response.ok) { throw new Error('Gagal memuat kalender.'); }
                            return response.json();
                        })
                        .then(function (payload) {
                            renderSummary(payload.meta || {});
                            successCallback(payload.events || []);
                        })
                        .catch(function (error) { failureCallback(error); });
                },
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    const props = info.event.extendedProps || {};
                    document.getElementById('calendarEventTitle').textContent = info.event.title || '-';
                    document.getElementById('calendarEventModule').textContent = props.module_label || 'Modul';
                    const statusEl = document.getElementById('calendarEventStatus');
                    statusEl.textContent = props.status_label || 'Status';
                    statusEl.className = 'calendar-status-badge ' + (props.status_key || '');
                    document.getElementById('calendarEventTime').textContent = props.time_label || '-';
                    document.getElementById('calendarEventCategory').textContent = props.kategori || '-';
                    document.getElementById('calendarEventUnit').textContent = props.unit || '-';
                    document.getElementById('calendarEventPic').textContent = props.pic || '-';
                    document.getElementById('calendarEventLocation').textContent = props.location || '-';
                    document.getElementById('calendarEventParticipants').textContent = props.participants || '-';
                    document.getElementById('calendarEventDescription').textContent = props.description || '-';
                    const linkEl = document.getElementById('calendarEventLink');
                    const fallbackEl = document.getElementById('calendarEventLinkFallback');
                    if (info.event.url) {
                        linkEl.href = info.event.url;
                        linkEl.classList.remove('d-none');
                        fallbackEl.classList.add('d-none');
                    } else {
                        linkEl.classList.add('d-none');
                        fallbackEl.classList.remove('d-none');
                    }
                    $('#calendarEventModal').modal('show');
                }
            });

            calendar.render();
            form.addEventListener('submit', function (event) { event.preventDefault(); calendar.refetchEvents(); });
            form.querySelectorAll('select, input[type="checkbox"]').forEach(function (element) {
                element.addEventListener('change', function () { calendar.refetchEvents(); });
            });
        });
    </script>
@endpush

