@extends('layouts.app')

@section('title', 'Rekapan ZI')

@push('styles')
<style>
    .zi-shell { display:grid; gap:18px; }
    .zi-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 52%, #3b82f6 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.18); }
    .zi-hero-title { font-size:1.55rem; font-weight:800; margin-bottom:6px; }
    .zi-hero-meta { opacity:.86; font-size:.92rem; }
    .zi-kpi-grid { display:grid; grid-template-columns:repeat(6, minmax(0,1fr)); gap:16px; }
    .zi-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:18px; box-shadow:0 10px 26px rgba(15, 23, 42, .05); }
    .zi-kpi .value { font-size:1.55rem; font-weight:800; color:#0f172a; line-height:1; margin-bottom:6px; }
    .zi-kpi .label { font-size:.78rem; color:#64748b; }
    .zi-row { display:grid; grid-template-columns:1.1fr .9fr; gap:18px; }
    .zi-wide-panel { width:100%; }
    .zi-trend-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px; width:100%; }
    .zi-panel { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 26px rgba(15,23,42,.05); overflow:hidden; }
    .zi-panel-head { padding:18px 20px 14px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .zi-panel-head h5 { margin:0; font-size:.98rem; font-weight:800; color:#0f172a; }
    .zi-panel-head p { margin:3px 0 0; font-size:.78rem; color:#64748b; }
    .zi-panel-body { padding:16px 20px 18px; }
    .zi-progress-list, .zi-attention-list, .zi-trend-list { display:grid; gap:12px; }
    .zi-progress-item { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid #f1f5f9; }
    .zi-progress-item:last-child { border-bottom:none; }
    .zi-progress-bar { width:100%; height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-top:8px; }
    .zi-progress-bar span { display:block; height:100%; background:linear-gradient(90deg, #0d9488, #10b981); border-radius:999px; }
    .zi-attention-item, .zi-trend-item { padding:12px 14px; border:1px solid #e2e8f0; border-radius:14px; background:#f8fafc; }
    .zi-attention-title { font-weight:700; color:#0f172a; font-size:.86rem; }
    .zi-attention-meta, .zi-trend-meta { font-size:.75rem; color:#64748b; margin-top:4px; }
    .zi-chart-grid { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:10px; }
    .zi-chart-box { padding:12px; border-radius:14px; background:#f8fafc; border:1px solid #e2e8f0; text-align:center; }
    .zi-chart-box strong { display:block; font-size:1.15rem; color:#0f172a; }
    .zi-chart-box span { font-size:.74rem; color:#64748b; }
    .zi-trend-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }
    @media (max-width: 991.98px) { .zi-kpi-grid, .zi-row, .zi-chart-grid, .zi-trend-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="zi-shell">
    <div class="zi-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-end" style="gap:16px;">
            <div>
                <div class="zi-hero-title">Rekapan Zona Integritas</div>
                <div class="zi-hero-meta">Monitoring area perubahan, kegiatan, indikator, eviden, dan tren capaian periode dalam satu panel.</div>
            </div>
            <form method="GET" class="d-flex align-items-center" style="gap:10px; min-width:280px;">
                <select name="period_id" class="form-control">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}" {{ optional($selectedPeriod)->id === $period->id ? 'selected' : '' }}>{{ $period->name }} ({{ $period->year }})</option>
                    @endforeach
                </select>
                <button class="btn btn-light btn-sm px-3" type="submit">Terapkan</button>
            </form>
        </div>
    </div>

    <div class="zi-kpi-grid">
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['area_count'] }}</div><div class="label">Area Perubahan</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['sub_point_covered_count'] }}/{{ $dashboard['summary']['sub_point_count'] }}</div><div class="label">Sub Poin Ditindaklanjuti</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['periodic_sub_point_count'] }}</div><div class="label">Sub Poin Berkala</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['activity_count'] }}</div><div class="label">Kegiatan / Program Kerja</div></div>
        <div class="zi-kpi"><div class="value">{{ $dashboard['summary']['indicator_count'] }}</div><div class="label">Indikator</div></div>
        <div class="zi-kpi"><div class="value">{{ rtrim(rtrim(number_format($dashboard['summary']['period_score'], 1), '0'), '.') }}%</div><div class="label">Nilai Periode</div></div>
    </div>

    @if(auth()->user()->isSuperAdmin() || auth()->user()->canManageProgressZiMasterData())
        <div class="zi-panel">
            <div class="zi-panel-head">
                <div>
                    <h5>Tren Periode</h5>
                    <p>Perbandingan nilai progress antar periode untuk memantau arah capaian Zona Integritas.</p>
                </div>
                <a href="{{ route('progress-zi.activities.index') }}" class="btn btn-light btn-sm">Buka Monitoring</a>
            </div>
            <div class="zi-panel-body">
                <div class="zi-trend-grid">
                    @forelse($dashboard['period_trends'] as $trend)
                        <div class="zi-trend-item">
                            <div class="zi-trend-top">
                                <div>
                                    <div class="zi-attention-title">{{ $trend['name'] }}</div>
                                    <div class="zi-trend-meta">{{ $trend['activity_count'] }} kegiatan • {{ $trend['indicator_count'] }} indikator</div>
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
            <div>
                <h5>Progress per Area</h5>
                <p>Nilai area dihitung dari rata-rata nilai kegiatan pada area terkait, beserta coverage sub poin yang sudah ditindaklanjuti.</p>
            </div>
            <a href="{{ route('progress-zi.activities.index') }}" class="app-create-btn" style="padding:8px 14px;"><i class="fas fa-tasks"></i>Lihat Monitoring</a>
        </div>
        <div class="zi-panel-body">
            <div class="zi-progress-list">
                @forelse($dashboard['area_progress'] as $item)
                    <div class="zi-progress-item">
                        <div>
                            <div class="zi-attention-title">{{ $item['name'] }}</div>
                            <div class="zi-attention-meta">PIC: {{ $item['pic'] ?: '-' }} • {{ $item['coverage']['covered'] }}/{{ $item['coverage']['total'] }} sub poin</div>
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
                <div>
                    <h5>Ringkasan Status</h5>
                    <p>Distribusi cepat pelaksanaan kegiatan ZI.</p>
                </div>
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
            <div class="zi-panel-head"><div><h5>Indikator & Eviden Perhatian</h5><p>Item yang perlu dipenuhi atau diperbaiki lebih dulu.</p></div><a href="{{ route('approval.index') }}" class="btn btn-light btn-sm">Buka Approval</a></div>
            <div class="zi-panel-body">
                <div class="zi-attention-list">
                    @foreach($dashboard['indicator_attention'] as $indicator)
                        <div class="zi-attention-item">
                            <div class="zi-attention-title">Indikator: {{ $indicator->name }}</div>
                            <div class="zi-attention-meta">{{ optional(optional($indicator->activity)->area)->name }} • {{ optional($indicator->activity)->name }} • {!! $indicator->status_badge !!}</div>
                        </div>
                    @endforeach
                    @foreach($dashboard['evidence_attention'] as $evidence)
                        <div class="zi-attention-item">
                            <div class="zi-attention-title">Eviden: {{ $evidence->title }}</div>
                            <div class="zi-attention-meta">{{ optional(optional($evidence->realization)->activity)->name }} • {{ $evidence->source_reference_label }} • {!! $evidence->status_badge !!}</div>
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
        <div class="zi-panel-head"><div><h5>Kegiatan Overdue</h5><p>Kegiatan yang melewati target akhir dan belum selesai.</p></div></div>
        <div class="zi-panel-body">
            <div class="zi-attention-list">
                @forelse($dashboard['overdue_activities'] as $activity)
                    <div class="zi-attention-item">
                        <div class="zi-attention-title"><a href="{{ route('progress-zi.activities.show', $activity) }}">{{ $activity->name }}</a></div>
                        <div class="zi-attention-meta">{{ optional($activity->area)->name }} • target {{ optional($activity->target_end_date)->translatedFormat('d F Y') }} • PIC {{ optional($activity->pic)->name ?: '-' }}</div>
                    </div>
                @empty
                    <div class="text-muted">Tidak ada kegiatan overdue.</div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection
