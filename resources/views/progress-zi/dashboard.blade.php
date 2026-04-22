@extends('layouts.app')

@section('title', 'Rekapan ZI')

@push('styles')
<style>
    .zi-shell { display:grid; gap:16px; }
    .zi-kpi-grid { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:14px; }
    .zi-group-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:16px; }
    .zi-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 4px 12px rgba(15, 23, 42, .04); }
    .zi-kpi .value { font-size:1.4rem; font-weight:800; color:#0f172a; line-height:1; margin-bottom:5px; }
    .zi-kpi .label { font-size:.76rem; color:#64748b; }
    .zi-row { display:grid; grid-template-columns:1.1fr .9fr; gap:16px; }
    .zi-wide-panel { width:100%; }
    .zi-trend-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:10px; width:100%; }
    .zi-panel { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 12px rgba(15,23,42,.04); overflow:hidden; }
    .zi-panel-head { padding:14px 18px 12px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .zi-panel-head h5 { margin:0; font-size:.92rem; font-weight:800; color:#0f172a; }
    .zi-panel-body { padding:14px 18px 16px; }
    .zi-progress-list, .zi-attention-list, .zi-trend-list { display:grid; gap:10px; }
    .zi-progress-item { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; padding:10px 0; border-bottom:1px solid #f1f5f9; }
    .zi-progress-item:last-child { border-bottom:none; }
    .zi-progress-bar { width:100%; height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-top:6px; }
    .zi-progress-bar span { display:block; height:100%; background:linear-gradient(90deg, #0d9488, #10b981); border-radius:999px; }
    .zi-attention-item, .zi-trend-item { padding:10px 12px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; }
    .zi-attention-title { font-weight:700; color:#0f172a; font-size:.82rem; }
    .zi-attention-meta, .zi-trend-meta { font-size:.73rem; color:#64748b; margin-top:3px; }
    .zi-chart-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:8px; }
    .zi-chart-box { padding:10px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; text-align:center; }
    .zi-chart-box strong { display:block; font-size:1.05rem; color:#0f172a; }
    .zi-chart-box span { font-size:.72rem; color:#64748b; }
    .zi-trend-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .zi-filter-bar { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 12px rgba(15,23,42,.04); padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    @media (max-width: 991.98px) { .zi-kpi-grid, .zi-group-grid, .zi-row, .zi-chart-grid, .zi-trend-grid { grid-template-columns:1fr; } .zi-filter-bar { flex-direction:column; align-items:stretch; } }
</style>
@endpush

@section('content')
<div class="zi-shell">
    <div class="zi-filter-bar">
        <h5 style="margin:0; font-size:.92rem; font-weight:800; color:#0f172a;">Rekapan Zona Integritas</h5>
        <form method="GET" class="d-flex align-items-center" style="gap:8px;">
            <select name="period_id" class="form-control">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" {{ optional($selectedPeriod)->id === $period->id ? 'selected' : '' }}>{{ $period->name }} ({{ $period->year }})</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm px-3" type="submit">Terapkan</button>
        </form>
    </div>

    <div class="zi-kpi-grid">
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['group_count'] }}</div><div class="label">Kelompok</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['area_count'] }}</div><div class="label">Area</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['sub_point_covered_count'] }}/{{ $dashboard['summary']['sub_point_count'] }}</div><div class="label">Sub Poin</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['periodic_sub_point_count'] }}</div><div class="label">Berkala</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['activity_count'] }}</div><div class="label">Kegiatan</div></div>
        <div class="zi-kpi"><div class="value">{{ rtrim(rtrim(number_format($dashboard['summary']['period_score'], 1), '0'), '.') }}%</div><div class="label">Nilai Periode</div></div>
    </div>

    <div class="zi-panel zi-wide-panel">
        <div class="zi-panel-head">
            <h5>Struktur Kelompok ZI</h5>
        </div>
        <div class="zi-panel-body">
            <div class="zi-chart-grid">
                @foreach($dashboard['group_summary'] as $group)
                    <div class="zi-chart-box">
                        <strong>{{ $group['area_count'] }}</strong>
                        <span>{{ $group['group_label'] }} &bull; {{ $group['sub_point_covered_count'] }}/{{ $group['sub_point_count'] }} sub poin &bull; {{ rtrim(rtrim(number_format($group['score'], 1), '0'), '.') }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="zi-group-grid">
        @foreach($dashboard['group_summary'] as $groupType => $group)
            @php
                $groupAreas = $dashboard['group_area_progress']->get($groupType, collect());
            @endphp
            <div class="zi-panel">
                <div class="zi-panel-head">
                    <h5>{{ $group['group_label'] }}</h5>
                    <span class="badge badge-light">{{ $group['area_count'] }} area</span>
                </div>
                <div class="zi-panel-body">
                    <div class="zi-chart-grid mb-3" style="grid-template-columns:repeat(2, minmax(0,1fr));">
                        <div class="zi-chart-box">
                            <strong>{{ $group['sub_point_covered_count'] }}/{{ $group['sub_point_count'] }}</strong>
                            <span>Sub Poin</span>
                        </div>
                        <div class="zi-chart-box">
                            <strong>{{ rtrim(rtrim(number_format($group['score'], 1), '0'), '.') }}%</strong>
                            <span>Skor</span>
                        </div>
                    </div>
                    <div class="zi-progress-list">
                        @forelse($groupAreas as $item)
                            <div class="zi-progress-item">
                                <div>
                                    <div class="zi-attention-title">{{ $item['name'] }}</div>
                                    <div class="zi-trend-meta">{{ $item['code'] }}</div>
                                    <div class="zi-attention-meta">PIC: {{ $item['pic'] ?: '-' }} &bull; {{ $item['coverage']['covered'] }}/{{ $item['coverage']['total'] }} sub poin</div>
                                    <div class="zi-progress-bar"><span style="width: {{ min(100, max(0, $item['score'])) }}%"></span></div>
                                </div>
                                <div>{!! '<span class="badge badge-' . ($item['score'] >= 100 ? 'success' : ($item['score'] >= 50 ? 'warning' : 'secondary')) . ' app-status-badge">' . rtrim(rtrim(number_format($item['score'], 1), '0'), '.') . '%</span>' !!}</div>
                            </div>
                        @empty
                            <div class="text-muted">Belum ada data area pada bagian ini.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(auth()->user()->isSuperAdmin() || auth()->user()->canManageProgressZiMasterData())
        <div class="zi-panel">
            <div class="zi-panel-head">
                <h5>Tren Periode</h5>
                <a href="{{ route('progress-zi.activities.index') }}" class="btn btn-light btn-sm">Buka Monitoring</a>
            </div>
            <div class="zi-panel-body">
                <div class="zi-trend-grid">
                    @forelse($dashboard['period_trends'] as $trend)
                        <div class="zi-trend-item">
                            <div class="zi-trend-top">
                                <div>
                                    <div class="zi-attention-title">{{ $trend['name'] }}</div>
                                    <div class="zi-trend-meta">{{ $trend['activity_count'] }} kegiatan &bull; {{ $trend['indicator_count'] }} indikator</div>
                                </div>
                                <div>{!! '<span class="badge badge-' . ($trend['score'] >= 100 ? 'success' : ($trend['score'] >= 50 ? 'warning' : 'secondary')) . ' app-status-badge">' . rtrim(rtrim(number_format($trend['score'], 1), '0'), '.') . '%</span>' !!}</div>
                            </div>
                            <div class="zi-progress-bar"><span style="width: {{ min(100, max(0, $trend['score'])) }}%"></span></div>
                        </div>
                    @empty
                        <div class="text-muted">Belum ada tren periode yang bisa ditampilkan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    <div class="zi-panel zi-wide-panel">
        <div class="zi-panel-head">
            <h5>Progress per Area</h5>
            <a href="{{ route('progress-zi.activities.index') }}" class="btn btn-light btn-sm"><i class="fas fa-tasks mr-1"></i>Monitoring</a>
        </div>
        <div class="zi-panel-body">
            <div class="zi-progress-list">
                @forelse($dashboard['area_progress'] as $item)
                    <div class="zi-progress-item">
                        <div>
                            <div class="zi-attention-title">{{ $item['name'] }}</div>
                            <div class="zi-trend-meta">{{ $item['group_label'] }} &bull; {{ $item['code'] }}</div>
                            <div class="zi-attention-meta">PIC: {{ $item['pic'] ?: '-' }} &bull; {{ $item['coverage']['covered'] }}/{{ $item['coverage']['total'] }} sub poin</div>
                            <div class="zi-progress-bar"><span style="width: {{ min(100, max(0, $item['score'])) }}%"></span></div>
                        </div>
                        <div>{!! '<span class="badge badge-' . ($item['score'] >= 100 ? 'success' : ($item['score'] >= 50 ? 'warning' : 'secondary')) . ' app-status-badge">' . rtrim(rtrim(number_format($item['score'], 1), '0'), '.') . '%</span>' !!}</div>
                    </div>
                @empty
                    <div class="text-muted">Belum ada data area untuk periode ini.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="zi-row">
        <div class="zi-panel">
            <div class="zi-panel-head">
                <h5>Ringkasan Status</h5>
            </div>
            <div class="zi-panel-body">
                <div class="zi-chart-grid mb-3">
                    <div class="zi-chart-box"><strong>{{ $dashboard['status_chart']['belum_mulai'] }}</strong><span>Belum Mulai</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['status_chart']['berjalan'] }}</strong><span>Sedang Berjalan</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['status_chart']['selesai'] }}</strong><span>Selesai</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['status_chart']['perlu_perbaikan'] }}</strong><span>Perlu Perbaikan</span></div>
                </div>
                <div class="zi-chart-grid">
                    <div class="zi-chart-box"><strong>{{ $dashboard['summary']['overdue_count'] }}</strong><span>Overdue</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['summary']['indicator_attention_count'] }}</strong><span>Indikator Perhatian</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['summary']['evidence_attention_count'] }}</strong><span>Eviden Perhatian</span></div>
                    <div class="zi-chart-box"><strong>{{ $dashboard['summary']['evidence_count'] }}</strong><span>Total Eviden</span></div>
                </div>
            </div>
        </div>
        <div class="zi-panel">
            <div class="zi-panel-head"><h5>Perhatian</h5><a href="{{ route('approval.index') }}" class="btn btn-light btn-sm">Approval</a></div>
            <div class="zi-panel-body">
                <div class="zi-attention-list">
                    @foreach($dashboard['indicator_attention'] as $indicator)
                        <div class="zi-attention-item">
                            <div class="zi-attention-title">Indikator: {{ $indicator->name }}</div>
                            <div class="zi-attention-meta">{{ optional(optional($indicator->activity)->area)->name }} &bull; {{ optional($indicator->activity)->name }} &bull; {!! $indicator->status_badge !!}</div>
                        </div>
                    @endforeach
                    @foreach($dashboard['evidence_attention'] as $evidence)
                        <div class="zi-attention-item">
                            <div class="zi-attention-title">Eviden: {{ $evidence->title }}</div>
                            <div class="zi-attention-meta">{{ optional(optional($evidence->realization)->activity)->name }} &bull; {{ $evidence->source_reference_label }} &bull; {!! $evidence->status_badge !!}</div>
                        </div>
                    @endforeach
                    @if($dashboard['indicator_attention']->isEmpty() && $dashboard['evidence_attention']->isEmpty())
                        <div class="text-muted">Belum ada item perhatian.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="zi-panel zi-wide-panel">
        <div class="zi-panel-head"><h5>Kegiatan Overdue</h5></div>
        <div class="zi-panel-body">
            <div class="zi-attention-list">
                @forelse($dashboard['overdue_activities'] as $activity)
                    <div class="zi-attention-item">
                        <div class="zi-attention-title"><a href="{{ route('progress-zi.activities.show', $activity) }}">{{ $activity->name }}</a></div>
                        <div class="zi-attention-meta">{{ optional($activity->area)->name }} &bull; target {{ optional($activity->target_end_date)->translatedFormat('d F Y') }} &bull; PIC {{ optional($activity->pic)->name ?: '-' }}</div>
                    </div>
                @empty
                    <div class="text-muted">Tidak ada kegiatan overdue.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

