@extends('layouts.app')

@section('title', 'Matriks Pedoman vs Kegiatan')

@push('styles')
<style>
    .zi-matrix-shell { display:grid; gap:18px; }
    .zi-matrix-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 55%, #2d7dd2 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.16); }
    .zi-matrix-title { font-size:1.45rem; font-weight:800; margin-bottom:6px; }
    .zi-matrix-subtitle { opacity:.84; font-size:.9rem; }
    .zi-matrix-filter { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 24px rgba(15, 23, 42, .05); padding:18px 20px; }
    .zi-matrix-area { background:#fff; border:1px solid #e5e7eb; border-radius:18px; overflow:hidden; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .zi-matrix-area-head { padding:18px 20px 14px; border-bottom:1px solid #eef2f7; display:flex; justify-content:space-between; gap:16px; align-items:flex-start; }
    .zi-matrix-area-title { font-size:1rem; font-weight:800; color:#0f172a; }
    .zi-matrix-area-meta { font-size:.78rem; color:#64748b; margin-top:4px; }
    .zi-matrix-table td, .zi-matrix-table th { vertical-align:top; }
    .zi-matrix-table thead th { white-space:nowrap; font-size:.76rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; }
    .zi-matrix-guideline { font-weight:700; color:#0f172a; line-height:1.55; }
    .zi-matrix-indicator { color:#334155; font-size:.82rem; line-height:1.6; }
    .zi-matrix-activity { border:1px solid #dbeafe; background:#f8fbff; border-radius:12px; padding:10px 11px; margin-bottom:8px; }
    .zi-matrix-activity:last-child { margin-bottom:0; }
    .zi-matrix-activity-title { font-weight:700; color:#0f172a; font-size:.84rem; }
    .zi-matrix-activity-meta { color:#64748b; font-size:.74rem; margin-top:4px; line-height:1.5; }
    .zi-matrix-empty { color:#94a3b8; font-size:.78rem; padding:8px 0; }
</style>
@endpush

@section('content')
<div class="zi-matrix-shell">
    <div class="zi-matrix-hero">
        <div class="zi-matrix-title">Matriks Pedoman vs Kegiatan</div>
        <div class="zi-matrix-subtitle">Bandingkan acuan pedoman Zona Integritas dengan kegiatan dan indikator yang sudah dijalankan pada periode aktif.</div>
    </div>

    <div class="zi-matrix-filter">
        <form method="GET" class="row">
            <div class="col-md-4 form-group mb-md-0">
                <label>Periode</label>
                <select name="period_id" class="form-control">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}" {{ optional($selectedPeriod)->id === $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 form-group mb-md-0">
                <label>Area</label>
                <select name="area_id" class="form-control">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ (int) $selectedAreaId === (int) $area->id ? 'selected' : '' }}>{{ $area->code }} - {{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 form-group d-flex align-items-end mb-0" style="gap:10px;">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('progress-zi.reports.matrix') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    @forelse($areas as $area)
        <div class="zi-matrix-area">
            <div class="zi-matrix-area-head">
                <div>
                    <div class="zi-matrix-area-title">{{ $area->code }} - {{ $area->name }}</div>
                    <div class="zi-matrix-area-meta">PIC Area: {{ optional($area->pic)->name ?: '-' }} • {{ optional($selectedPeriod)->name ?: 'Semua Periode' }}</div>
                </div>
                <div class="d-flex" style="gap:8px; flex-wrap:wrap;">
                    <span class="badge badge-info app-status-badge">{{ $area->guidelinePoints->count() }} poin</span>
                    <span class="badge badge-secondary app-status-badge">{{ $area->activities->count() }} kegiatan</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table zi-matrix-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:28%;">Pedoman</th>
                            <th style="width:26%;">Indikator Penilaian</th>
                            <th style="width:30%;">Kegiatan Terkait</th>
                            <th style="width:16%;">Status Keterhubungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasRows = false; @endphp
                        @foreach($area->guidelinePoints as $point)
                            @foreach($point->subPoints as $subPoint)
                                @php
                                    $hasRows = true;
                                    $activities = $area->activities->where('zi_guideline_sub_point_id', $subPoint->id);
                                    $coverage = $activities->isEmpty() ? 'Belum ditindaklanjuti' : 'Sudah ada kegiatan';
                                    $coverageClass = $activities->isEmpty() ? 'danger' : 'success';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="zi-matrix-guideline">Poin {{ $point->code }} / Sub {{ strtoupper($subPoint->code) }}</div>
                                        <div class="zi-matrix-indicator mt-1">{{ $subPoint->title }}</div>
                                    </td>
                                    <td>
                                        @forelse($subPoint->indicators as $indicator)
                                            <div class="mb-2">
                                                <div class="zi-matrix-guideline" style="font-size:.8rem;">{{ $indicator->code ?: 'Indikator' }}</div>
                                                <div class="zi-matrix-indicator">{{ $indicator->indicator_text }}</div>
                                            </div>
                                        @empty
                                            <div class="zi-matrix-empty">Belum ada indikator penilaian.</div>
                                        @endforelse
                                    </td>
                                    <td>
                                        @forelse($activities as $activity)
                                            <div class="zi-matrix-activity">
                                                <div class="zi-matrix-activity-title"><a href="{{ route('progress-zi.activities.show', $activity) }}">{{ $activity->name }}</a></div>
                                                <div class="zi-matrix-activity-meta">
                                                    PIC {{ optional($activity->pic)->name ?: '-' }} • {!! $activity->status_badge !!}
                                                    <br>Indikator terkait:
                                                    {{ $activity->indicators->count() }}
                                                    @php $linkedIndicators = $activity->indicators->whereNotNull('zi_guideline_indicator_id')->count(); @endphp
                                                    • Terhubung ke pedoman: {{ $linkedIndicators }}
                                                </div>
                                            </div>
                                        @empty
                                            <div class="zi-matrix-empty">Belum ada kegiatan yang mengacu ke sub poin ini.</div>
                                        @endforelse
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $coverageClass }} app-status-badge">{{ $coverage }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if(!$hasRows)
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada pedoman atau kegiatan yang bisa ditampilkan untuk area ini.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">Belum ada data area untuk matriks pedoman.</div>
        </div>
    @endforelse
</div>
@endsection
