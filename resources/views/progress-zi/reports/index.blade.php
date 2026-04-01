@extends('layouts.app')

@section('title', 'Laporan Progress ZI')

@push('styles')
<style>
    .zi-report-shell { display:grid; gap:18px; }
    .zi-report-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 55%, #2d7dd2 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.16); }
    .zi-report-title { font-size:1.45rem; font-weight:800; margin-bottom:6px; }
    .zi-report-subtitle { opacity:.84; font-size:.9rem; }
    .zi-report-kpis { display:grid; grid-template-columns:repeat(7, minmax(0,1fr)); gap:14px; }
    .zi-report-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:18px; box-shadow:0 10px 24px rgba(15, 23, 42, .05); }
    .zi-report-kpi strong { display:block; font-size:1.45rem; color:#0f172a; line-height:1; margin-bottom:6px; }
    .zi-report-kpi span { font-size:.76rem; color:#64748b; }
    .zi-report-grid { display:grid; grid-template-columns:1.2fr .8fr; gap:18px; }
    .zi-report-panel { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 24px rgba(15, 23, 42, .05); overflow:hidden; }
    .zi-report-panel-head { padding:18px 20px 14px; border-bottom:1px solid #eef2f7; }
    .zi-report-panel-head h5 { margin:0; font-size:.98rem; font-weight:800; color:#0f172a; }
    .zi-report-panel-head p { margin:4px 0 0; font-size:.77rem; color:#64748b; }
    .zi-report-panel-body { padding:18px 20px 20px; }
    .zi-score-item { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
    .zi-score-item:last-child { border-bottom:none; }
    .zi-score-item .meta { font-size:.75rem; color:#64748b; margin-top:3px; }
    .zi-score-bar { width:100%; height:9px; margin-top:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; }
    .zi-score-bar span { display:block; height:100%; background:linear-gradient(90deg, #0d9488, #10b981); border-radius:999px; }
    .zi-filter-grid { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:12px; }
    .zi-table-title { font-weight:700; color:#0f172a; }
    .zi-table-meta { color:#64748b; font-size:.75rem; margin-top:4px; }
    @media (max-width: 991.98px) {
        .zi-report-kpis,
        .zi-report-grid,
        .zi-filter-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="zi-report-shell">
    <div class="zi-report-hero">
        <div class="d-flex justify-content-between align-items-end flex-wrap" style="gap:16px;">
            <div>
                <div class="zi-report-title">Laporan Progress Zona Integritas</div>
                <div class="zi-report-subtitle">Rekap area, kegiatan, indikator, eviden, dan perhatian utama per periode.</div>
            </div>
            <div class="d-flex flex-wrap" style="gap:10px;">
                <a href="{{ route('progress-zi.reports.matrix', request()->query()) }}" class="btn btn-light btn-sm"><i class="fas fa-th-large mr-1"></i>Matriks</a>
                <a href="{{ route('progress-zi.reports.pdf', request()->query()) }}" class="btn btn-light btn-sm"><i class="fas fa-file-pdf mr-1"></i>Export PDF</a>
                <a href="{{ route('progress-zi.reports.excel', request()->query()) }}" class="btn btn-light btn-sm"><i class="fas fa-file-excel mr-1"></i>Export Excel</a>
            </div>
        </div>
    </div>

    <div class="zi-report-kpis">
        <div class="zi-report-kpi"><strong>{{ $summary['area_count'] }}</strong><span>Area</span></div>
        <div class="zi-report-kpi"><strong>{{ $summary['sub_point_covered_count'] }}/{{ $summary['sub_point_count'] }}</strong><span>Sub Poin Ditindaklanjuti</span></div>
        <div class="zi-report-kpi"><strong>{{ $summary['periodic_sub_point_count'] }}</strong><span>Sub Poin Berkala</span></div>
        <div class="zi-report-kpi"><strong>{{ $summary['activity_count'] }}</strong><span>Kegiatan</span></div>
        <div class="zi-report-kpi"><strong>{{ $summary['indicator_count'] }}</strong><span>Indikator</span></div>
        <div class="zi-report-kpi"><strong>{{ $summary['evidence_count'] }}</strong><span>Eviden</span></div>
        <div class="zi-report-kpi"><strong>{{ rtrim(rtrim(number_format($summary['avg_progress'], 1), '0'), '.') }}%</strong><span>Rata-rata Progress</span></div>
    </div>

    <div class="zi-report-panel">
        <div class="zi-report-panel-head">
            <h5>Filter Laporan</h5>
            <p>Gunakan filter untuk mempersempit laporan per periode, area, PIC, status, dan item perhatian.</p>
        </div>
        <div class="zi-report-panel-body">
            <form method="GET" class="zi-filter-grid">
                <div class="form-group mb-0">
                    <label>Periode</label>
                    <select name="period_id" class="form-control">
                        <option value="">Semua Periode</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ (string) $filters['period_id'] === (string) $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Area</label>
                    <select name="area_id" class="form-control">
                        <option value="">Semua Area</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ (string) $filters['area_id'] === (string) $area->id ? 'selected' : '' }}>{{ $area->code }} - {{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        @foreach(['belum_mulai' => 'Belum Mulai', 'dijadwalkan' => 'Dijadwalkan', 'sedang_berjalan' => 'Sedang Berjalan', 'sudah_terlaksana' => 'Sudah Terlaksana', 'selesai' => 'Selesai', 'perlu_perbaikan' => 'Perlu Perbaikan'] as $key => $label)
                            <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>PIC</label>
                    <select name="pic_user_id" class="form-control">
                        <option value="">Semua PIC</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string) $filters['pic_user_id'] === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Perhatian</label>
                    <select name="attention" class="form-control">
                        <option value="">Semua</option>
                        <option value="overdue" {{ $filters['attention'] === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="indicator" {{ $filters['attention'] === 'indicator' ? 'selected' : '' }}>Indikator</option>
                        <option value="evidence" {{ $filters['attention'] === 'evidence' ? 'selected' : '' }}>Eviden</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Pencarian</label>
                    <input type="text" class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="Nama kegiatan / indikator">
                </div>
                <div class="form-group mb-0 d-flex align-items-end" style="gap:10px;">
                    <button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button>
                    <a href="{{ route('progress-zi.reports.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="zi-report-grid">
        <div class="zi-report-panel">
            <div class="zi-report-panel-head">
                <h5>Daftar Kegiatan</h5>
                <p>Ringkasan kegiatan, progress indikator, eviden, dan perhatian per item.</p>
            </div>
            <div class="zi-report-panel-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kegiatan</th>
                                <th>PIC</th>
                                <th>Target</th>
                                <th>Progress</th>
                                <th>Indikator</th>
                                <th>Eviden</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                                @php
                                    $indicatorAttention = $activity->indicators->whereIn('status', ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'ditolak'])->count();
                                    $evidenceCount = $activity->realizations->sum(function ($realization) { return $realization->evidences->count(); });
                                    $evidenceAttention = $activity->realizations->flatMap->evidences->whereIn('status', ['terupload', 'terhubung', 'revisi', 'tidak_valid'])->count();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="zi-table-title"><a href="{{ route('progress-zi.activities.show', $activity) }}">{{ $activity->name }}</a></div>
                                        <div class="zi-table-meta">{{ optional($activity->area)->code }} • {{ optional($activity->area)->name }}</div>
                                    </td>
                                    <td>{{ optional($activity->pic)->name ?: '-' }}</td>
                                    <td>
                                        <div>{{ optional($activity->target_start_date)->translatedFormat('d M Y') ?: '-' }}</div>
                                        <div class="zi-table-meta">s/d {{ optional($activity->target_end_date)->translatedFormat('d M Y') ?: '-' }}</div>
                                    </td>
                                    <td>{!! $activity->progress_badge !!}</td>
                                    <td>
                                        <div>{{ $activity->indicators->count() }} total</div>
                                        <div class="zi-table-meta">{{ $indicatorAttention }} perlu perhatian</div>
                                    </td>
                                    <td>
                                        <div>{{ $evidenceCount }} eviden</div>
                                        <div class="zi-table-meta">{{ $evidenceAttention }} perlu review</div>
                                    </td>
                                    <td>{!! $activity->status_badge !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada data kegiatan untuk filter ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(method_exists($activities, 'links'))
                <div class="card-footer bg-white border-0">{{ $activities->links() }}</div>
            @endif
        </div>

        <div class="zi-report-panel">
            <div class="zi-report-panel-head">
                <h5>Nilai per Area</h5>
                <p>Area dihitung dari rata-rata progress kegiatan yang aktif pada filter saat ini.</p>
            </div>
            <div class="zi-report-panel-body">
                @forelse($areaScores as $area)
                    <div class="zi-score-item">
                        <div style="flex:1;">
                            <div class="zi-table-title">{{ $area['code'] }} - {{ $area['name'] }}</div>
                            <div class="meta">PIC: {{ $area['pic'] ?: '-' }}</div>
                            <div class="zi-score-bar"><span style="width: {{ min(100, max(0, $area['score'])) }}%"></span></div>
                        </div>
                        <div>{!! '<span class="badge badge-' . ($area['score'] >= 100 ? 'success' : ($area['score'] >= 50 ? 'warning' : 'secondary')) . ' app-status-badge">' . rtrim(rtrim(number_format($area['score'], 1), '0'), '.') . '%</span>' !!}</div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada area yang bisa dihitung.</div>
                @endforelse
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="zi-table-title">{{ $summary['period_name'] }}</div>
                        <div class="zi-table-meta">{{ $summary['overdue_count'] }} kegiatan overdue pada filter saat ini.</div>
                    </div>
                    <div>{!! '<span class="badge badge-' . ($summary['avg_progress'] >= 100 ? 'success' : ($summary['avg_progress'] >= 50 ? 'warning' : 'secondary')) . ' app-status-badge">' . rtrim(rtrim(number_format($summary['avg_progress'], 1), '0'), '.') . '%</span>' !!}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
