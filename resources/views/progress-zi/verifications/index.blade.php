@extends('layouts.app')

@section('title', 'Verifikasi Progress ZI')

@push('styles')
<style>
    .zi-verify-shell { display:grid; gap:18px; }
    .zi-verify-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 55%, #2563eb 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.16); }
    .zi-verify-title { font-size:1.45rem; font-weight:800; margin-bottom:6px; }
    .zi-verify-subtitle { opacity:.84; font-size:.9rem; }
    .zi-verify-kpis { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; }
    .zi-verify-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:18px; padding:18px; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .zi-verify-kpi strong { display:block; font-size:1.4rem; line-height:1; color:#0f172a; margin-bottom:6px; }
    .zi-verify-kpi span { font-size:.76rem; color:#64748b; }
    .zi-verify-grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
    .zi-verify-panel { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 24px rgba(15,23,42,.05); overflow:hidden; }
    .zi-verify-panel-head { padding:18px 20px 14px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .zi-verify-panel-head h5 { margin:0; font-size:.98rem; font-weight:800; color:#0f172a; }
    .zi-verify-panel-head p { margin:4px 0 0; font-size:.77rem; color:#64748b; }
    @media (max-width: 991.98px) {
        .zi-verify-kpis,
        .zi-verify-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="zi-verify-shell">
    <div class="zi-verify-hero">
        <div class="d-flex justify-content-between align-items-end flex-wrap" style="gap:16px;">
            <div>
                <div class="zi-verify-title">Verifikasi Progress ZI</div>
                <div class="zi-verify-subtitle">Review indikator dan eviden yang masih perlu validasi, revisi, atau penolakan.</div>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:10px; min-width:280px;">
                <form method="GET" class="d-flex align-items-center flex-wrap mb-0" style="gap:10px;">
                    <select name="period_id" class="form-control">
                        <option value="">Semua Periode</option>
                        @foreach($periods as $period)
                            <option value="{{ $period->id }}" {{ (string) $periodId === (string) $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-light btn-sm" type="submit">Filter</button>
                </form>
                <a href="{{ route('progress-zi.verifications.pdf', request()->query()) }}" class="btn btn-light btn-sm"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="{{ route('progress-zi.verifications.excel', request()->query()) }}" class="btn btn-light btn-sm"><i class="fas fa-file-excel mr-1"></i>Excel</a>
            </div>
        </div>
    </div>

    <div class="zi-verify-kpis">
        <div class="zi-verify-kpi"><strong>{{ $indicators->count() }}</strong><span>Indikator Perlu Review</span></div>
        <div class="zi-verify-kpi"><strong>{{ $evidences->count() }}</strong><span>Eviden Perlu Review</span></div>
        <div class="zi-verify-kpi"><strong>{{ $indicators->where('status', 'ditolak')->count() }}</strong><span>Indikator Ditolak</span></div>
        <div class="zi-verify-kpi"><strong>{{ $evidences->whereIn('status', ['revisi', 'tidak_valid'])->count() }}</strong><span>Eviden Revisi / Tidak Valid</span></div>
    </div>

    <div class="zi-verify-grid">
        <div class="zi-verify-panel">
            <div class="zi-verify-panel-head">
                <div>
                    <h5>Indikator Perlu Review</h5>
                    <p>Prioritaskan indikator yang belum terpenuhi, sebagian terpenuhi, atau ditolak.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Indikator</th>
                            <th>Kegiatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($indicators as $indicator)
                            <tr>
                                <td>
                                    <a href="{{ route('progress-zi.activities.show', $indicator->activity) }}"><strong>{{ $indicator->name }}</strong></a>
                                    <div class="small text-muted">{{ $indicator->target_fulfillment_text ?: '-' }}</div>
                                </td>
                                <td>
                                    {{ optional(optional($indicator->activity)->area)->name }}
                                    <div class="small text-muted">{{ optional($indicator->activity)->name }}</div>
                                </td>
                                <td>
                                    {!! $indicator->status_badge !!}
                                    @if($indicator->latestReview)
                                        <div class="small text-muted mt-1">{{ optional($indicator->latestReview->reviewer)->name ?: '-' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Tidak ada indikator yang perlu review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="zi-verify-panel">
            <div class="zi-verify-panel-head">
                <div>
                    <h5>Eviden Perlu Review</h5>
                    <p>Periksa eviden yang baru diunggah, terhubung, revisi, atau tidak valid.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Eviden</th>
                            <th>Kegiatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evidences as $evidence)
                            <tr>
                                <td>
                                    <a href="{{ route('progress-zi.activities.show', $evidence->realization->activity) }}"><strong>{{ $evidence->title }}</strong></a>
                                    <div class="small text-muted">{{ $evidence->source_reference_label }}</div>
                                </td>
                                <td>
                                    {{ optional(optional($evidence->realization)->activity)->name }}
                                    <div class="small text-muted">{{ optional(optional(optional($evidence->realization)->activity)->area)->name }}</div>
                                </td>
                                <td>
                                    {!! $evidence->status_badge !!}
                                    @if($evidence->latestReview)
                                        <div class="small text-muted mt-1">{{ optional($evidence->latestReview->reviewer)->name ?: '-' }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Tidak ada eviden yang perlu review.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
