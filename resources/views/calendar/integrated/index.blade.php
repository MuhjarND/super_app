@extends('layouts.app')
@section('title', 'Kalender Terpadu')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/integrated-calendar.css') }}?v={{ filemtime(public_path('css/integrated-calendar.css')) }}">
@endpush

@section('content')
<div class="integrated-calendar-page">
    <header class="calendar-page-heading">
        <h1>Kalender Terpadu</h1>
        <p>Lihat pembagian waktu kegiatan kantor dalam satu minggu.</p>
    </header>

    <form id="calendarFilterForm" class="calendar-filter-bar" data-skip-loader>
        <div class="calendar-filter-group">
            <label for="calendarScope">Cakupan</label>
            <select class="form-control" id="calendarScope" name="scope">
                <option value="all" {{ ($calendarFilters['scope'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua agenda</option>
                <option value="mine" {{ ($calendarFilters['scope'] ?? '') === 'mine' ? 'selected' : '' }}>Agenda saya</option>
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
                @foreach(['dijadwalkan'=>'Dijadwalkan', 'berjalan'=>'Berjalan', 'selesai'=>'Selesai', 'tertunda'=>'Tertunda', 'overdue'=>'Overdue'] as $value=>$label)
                    <option value="{{ $value }}" {{ ($calendarFilters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @foreach($calendarFilters['modules'] ?? ['rapat','agenda_pimpinan','virtual_meeting','cuti','zi','surat_tugas'] as $module)
            <input type="hidden" name="modules[]" value="{{ $module }}">
        @endforeach
        <button type="submit" class="btn calendar-button"><i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>Muat Ulang</button>
    </form>

    <div id="calendarDisplay" class="calendar-display" data-events-url="{{ route('calendar.integrated.events') }}">
        <header class="calendar-tv-heading">
            <div><strong>PAPEDA · Kalender Terpadu</strong><span>Pengadilan Tinggi Agama Papua Barat</span></div>
            <time id="calendarTvClock"></time>
        </header>
        <div class="calendar-legend" aria-label="Legenda sumber agenda">
            @foreach(['rapat'=>['Rapat','indigo'], 'agenda_pimpinan'=>['Agenda Pimpinan','slate'], 'virtual_meeting'=>['Virtual Meeting','violet'], 'cuti'=>['Cuti','rose'], 'zi'=>['Progress ZI','amber'], 'surat_tugas'=>['Surat Tugas','green']] as $key=>$item)
                <span class="calendar-legend-label"><span class="calendar-dot calendar-tone-{{ $item[1] }}" aria-hidden="true"></span>{{ $item[0] }}</span>
            @endforeach
        </div>
        <section class="calendar-board" aria-label="Jadwal kegiatan" aria-busy="false">
            <div id="integratedCalendar"></div>
        </section>
        <footer class="calendar-bottom-bar">
            <div class="calendar-summary" aria-live="polite">
                <strong><span id="calendarCountAll">0</span> agenda <span id="calendarRangeLabel">pada rentang ini</span></strong>
                <span id="calendarConflictSummary" class="calendar-conflict-summary" hidden></span>
            </div>
            <button type="button" class="btn calendar-button calendar-fullscreen-button" id="calendarFullscreenButton" aria-controls="calendarDisplay" aria-pressed="false">
                <i class="fas fa-expand mr-2" aria-hidden="true"></i><span>Fullscreen / Layar TV</span>
            </button>
        </footer>
        <div id="calendarMessage" class="calendar-message" role="status" hidden></div>
        <p class="calendar-tv-hint">Pembaruan otomatis setiap 1 menit · Tekan Esc atau tombol keluar untuk kembali.</p>
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

    </div>

    <details class="calendar-extra">
        <summary>Ringkasan dan agenda mendatang</summary>
        <div class="calendar-extra-grid">
            <section><h2>Ringkasan modul</h2><div id="calendarModuleCounts"></div></section>
            <section><h2>Tanggal dengan beberapa agenda</h2><div id="calendarConflictList" class="calendar-list"></div></section>
            <section><h2>Agenda mendatang</h2><div id="calendarUpcomingList" class="calendar-list"></div></section>
        </div>
    </details>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/id.global.min.js"></script>
<script src="{{ asset('js/integrated-calendar.js') }}?v={{ filemtime(public_path('js/integrated-calendar.js')) }}"></script>
@endpush

