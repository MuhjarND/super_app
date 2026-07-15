@extends('layouts.app')

@section('title', 'Jadwal Perawatan Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="schedule-page-head">
        <div>
            <h1>Jadwal Perawatan</h1>
            <p>Monitoring waktu perawatan alat dan mesin.</p>
        </div>
        @if($canSchedule)
            <button type="button" class="btn app-create-btn" data-toggle="modal" data-target="#scheduleCreateModal">
                <i class="fas fa-plus mr-1"></i> Buat Jadwal
            </button>
        @endif
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

@push('styles')
<style>
    .schedule-page-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .schedule-page-head h1 { margin:0; font-size:1.42rem; font-weight:750; color:#0f172a; }
    .schedule-page-head p { margin:3px 0 0; font-size:.86rem; color:#64748b; }
    .schedule-filter { display:grid; grid-template-columns:minmax(220px,1fr) 190px auto; gap:10px; padding:16px; border-bottom:1px solid #e8edf4; }
    .schedule-list { display:grid; gap:12px; padding:18px; }
    .schedule-card { border:1px solid #dce5f1; border-radius:16px; padding:16px; background:#fff; box-shadow:0 6px 18px rgba(15,23,42,.035); }
    .schedule-card.is-due { border-color:#fecaca; background:linear-gradient(135deg,#fff,#fff7f7); }
    .schedule-card-main { display:grid; grid-template-columns:minmax(220px,1.2fr) minmax(180px,.8fr) minmax(170px,.7fr) auto; gap:18px; align-items:center; }
    .schedule-asset-code { color:#315da8; font-size:.74rem; font-weight:800; letter-spacing:.04em; }
    .schedule-asset-name { color:#10234b; font-size:.95rem; font-weight:750; margin-top:2px; }
    .schedule-description { color:#64748b; font-size:.79rem; margin-top:5px; line-height:1.45; }
    .schedule-meta-label { display:block; margin-bottom:3px; color:#94a3b8; font-size:.66rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
    .schedule-meta-value { color:#1e293b; font-size:.84rem; font-weight:650; }
    .schedule-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:.72rem; font-weight:750; }
    .schedule-badge.primary { background:#eaf1ff; color:#28569e; }
    .schedule-badge.danger { background:#fee2e2; color:#b91c1c; }
    .schedule-badge.success { background:#dcfce7; color:#166534; }
    .schedule-badge.secondary { background:#eef2f7; color:#64748b; }
    .schedule-notification { margin-top:7px; color:#64748b; font-size:.72rem; }
    .schedule-actions { display:flex; align-items:center; justify-content:flex-end; gap:6px; flex-wrap:wrap; }
    .schedule-actions form { margin:0; }
    .schedule-empty { padding:44px 18px; text-align:center; color:#94a3b8; }

    @media (max-width: 991.98px) {
        .schedule-card-main { grid-template-columns:1fr 1fr; }
        .schedule-actions { justify-content:flex-start; }
    }

    @media (max-width: 767.98px) {
        .schedule-page-head .btn { width:100%; }
        .schedule-filter { grid-template-columns:1fr; padding:13px; }
        .schedule-list { padding:12px; }
        .schedule-card { padding:14px; border-radius:14px; }
        .schedule-card-main { grid-template-columns:1fr; gap:12px; }
        .schedule-actions .btn { min-width:40px; min-height:38px; }
    }
</style>
@endpush

<div class="inventory-module-shell">
    <div class="inventory-module-board-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
        <div class="inventory-module-board-title">Daftar Jadwal</div>
        <span class="inventory-module-chip">{{ $schedules->total() }} jadwal</span>
    </div>

    <form method="GET" action="{{ route('perawatan-alat-mesin.schedules.index') }}" class="schedule-filter">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari barang atau keterangan...">
        <select name="status" class="form-control">
            <option value="">Semua status</option>
            <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
        </select>
        <div class="d-flex" style="gap:8px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            <a href="{{ route('perawatan-alat-mesin.schedules.index') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
        </div>
    </form>

    <div class="schedule-list">
        @forelse($schedules as $schedule)
            @php
                $isDue = $schedule->status === 'scheduled' && $schedule->scheduled_at && $schedule->scheduled_at->isPast();
                $notificationTotal = $schedule->notifications->count();
                $notificationSent = $schedule->notifications->where('status', 'sent')->count();
                $notificationFailed = $schedule->notifications->where('status', 'failed')->count();
            @endphp
            <article class="schedule-card {{ $isDue ? 'is-due' : '' }}">
                <div class="schedule-card-main">
                    <div>
                        <div class="schedule-asset-code">{{ optional($schedule->item)->code ?: 'BARANG' }}</div>
                        <div class="schedule-asset-name">{{ $schedule->asset_label }}</div>
                        <div class="schedule-description">{{ $schedule->description }}</div>
                    </div>
                    <div>
                        <span class="schedule-meta-label">Waktu Perawatan</span>
                        <div class="schedule-meta-value">
                            <i class="far fa-calendar-alt mr-1 text-primary"></i>
                            {{ optional($schedule->scheduled_at)->translatedFormat('d M Y, H:i') }} WIT
                        </div>
                        @if(optional(optional($schedule->detail)->room)->name)
                            <div class="schedule-notification"><i class="fas fa-map-marker-alt mr-1"></i>{{ $schedule->detail->room->name }}</div>
                        @endif
                    </div>
                    <div>
                        <span class="schedule-meta-label">Status</span>
                        <span class="schedule-badge {{ $schedule->status_color }}">
                            <i class="fas {{ $schedule->status === 'completed' ? 'fa-check' : ($schedule->status === 'cancelled' ? 'fa-ban' : 'fa-clock') }}"></i>
                            {{ $schedule->status_label }}
                        </span>
                        <div class="schedule-notification">
                            @if($schedule->notification_completed_at)
                                <span class="text-success"><i class="fab fa-whatsapp mr-1"></i>WA terkirim ke seluruh penerima</span>
                            @elseif($notificationFailed > 0)
                                <span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>{{ $notificationSent }}/{{ $notificationTotal }} terkirim</span>
                            @elseif($notificationTotal > 0)
                                <span><i class="fab fa-whatsapp mr-1"></i>{{ $notificationSent }}/{{ $notificationTotal }} terkirim</span>
                            @elseif($isDue)
                                <span><i class="fas fa-hourglass-half mr-1"></i>Menunggu proses pengingat</span>
                            @else
                                <span><i class="far fa-bell mr-1"></i>Pengingat saat jatuh tempo</span>
                            @endif
                        </div>
                    </div>
                    @if($canSchedule)
                        <div class="schedule-actions">
                            @if($schedule->status === 'scheduled')
                                <button type="button" class="btn btn-sm btn-outline-primary js-edit-schedule"
                                    data-toggle="modal" data-target="#scheduleEditModal"
                                    data-url="{{ route('perawatan-alat-mesin.schedules.update', $schedule) }}"
                                    data-item="{{ $schedule->inventory_item_id }}"
                                    data-detail="{{ $schedule->inventory_item_detail_id }}"
                                    data-scheduled="{{ optional($schedule->scheduled_at)->format('Y-m-d\TH:i') }}"
                                    data-description="{{ e($schedule->description) }}" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('perawatan-alat-mesin.schedules.complete', $schedule) }}" onsubmit="return confirm('Tandai jadwal ini selesai?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-success" title="Selesai"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="POST" action="{{ route('perawatan-alat-mesin.schedules.cancel', $schedule) }}" onsubmit="return confirm('Batalkan jadwal ini?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary" title="Batalkan"><i class="fas fa-ban"></i></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('perawatan-alat-mesin.schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="schedule-empty"><i class="far fa-calendar-times d-block mb-2" style="font-size:1.7rem;"></i>Belum ada jadwal perawatan.</div>
        @endforelse
    </div>

    @if($schedules->hasPages())
        <div class="px-3 pb-3">{{ $schedules->links() }}</div>
    @endif
</div>

@if($canSchedule)
    @include('persediaan.schedules.partials.form-modal', [
        'modalId' => 'scheduleCreateModal',
        'modalTitle' => 'Buat Jadwal Perawatan',
        'formId' => 'scheduleCreateForm',
        'formAction' => route('perawatan-alat-mesin.schedules.store'),
        'method' => 'POST',
    ])
    @include('persediaan.schedules.partials.form-modal', [
        'modalId' => 'scheduleEditModal',
        'modalTitle' => 'Edit Jadwal Perawatan',
        'formId' => 'scheduleEditForm',
        'formAction' => '#',
        'method' => 'PUT',
    ])
@endif
@endsection

@if($canSchedule)
@push('scripts')
<script>
(function () {
    function filterDetails(itemSelect, detailSelect) {
        var itemId = itemSelect.value;
        Array.prototype.forEach.call(detailSelect.options, function (option, index) {
            if (index === 0) return;
            option.hidden = itemId && option.getAttribute('data-item') !== itemId;
        });
        if (detailSelect.selectedOptions.length && detailSelect.selectedOptions[0].hidden) {
            detailSelect.value = '';
        }
    }

    document.querySelectorAll('[data-schedule-item]').forEach(function (itemSelect) {
        var detailSelect = document.querySelector(itemSelect.getAttribute('data-detail-target'));
        if (!detailSelect) return;
        itemSelect.addEventListener('change', function () { filterDetails(itemSelect, detailSelect); });
        filterDetails(itemSelect, detailSelect);
    });

    document.querySelectorAll('.js-edit-schedule').forEach(function (button) {
        button.addEventListener('click', function () {
            var form = document.getElementById('scheduleEditForm');
            form.setAttribute('action', button.getAttribute('data-url'));
            var item = form.querySelector('[name="inventory_item_id"]');
            var detail = form.querySelector('[name="inventory_item_detail_id"]');
            item.value = button.getAttribute('data-item') || '';
            filterDetails(item, detail);
            detail.value = button.getAttribute('data-detail') || '';
            form.querySelector('[name="scheduled_at"]').value = button.getAttribute('data-scheduled') || '';
            form.querySelector('[name="description"]').value = button.getAttribute('data-description') || '';
        });
    });
})();
</script>
@endpush
@endif
