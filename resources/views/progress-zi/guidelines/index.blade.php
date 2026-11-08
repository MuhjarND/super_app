@extends('progress-zi.layout')

@section('title', 'Panduan Eviden ZI')

@push('styles')
<style>
    .zi-guide-shell { display:grid; gap:16px; }
    .zi-group-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; }
    .zi-group-card { display:block; padding:16px 18px; border:1px solid #dbeafe; border-radius:16px; background:linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); color:#0f172a; box-shadow:0 4px 12px rgba(15,23,42,.04); transition:all .16s ease; }
    .zi-group-card:hover { text-decoration:none; border-color:#93c5fd; box-shadow:0 10px 22px rgba(59,130,246,.12); transform:translateY(-1px); }
    .zi-group-card.active { border-color:#2563eb; background:linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%); box-shadow:0 14px 28px rgba(37,99,235,.14); }
    .zi-group-eyebrow { font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#475569; }
    .zi-group-title { font-size:1rem; font-weight:800; color:#0f172a; margin-top:4px; }
    .zi-group-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px; }
    .zi-group-stat { display:inline-flex; align-items:center; gap:5px; padding:5px 9px; border-radius:999px; background:#fff; border:1px solid #dbeafe; color:#1d4ed8; font-size:.7rem; font-weight:800; }
    .zi-guide-layout { display:grid; grid-template-columns:300px 1fr; gap:16px; align-items:start; }
    .zi-guide-panel { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 12px rgba(15,23,42,.04); overflow:hidden; }
    .zi-guide-panel-head { padding:14px 18px 12px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:12px; }
    .zi-guide-panel-head h5 { margin:0; font-size:.92rem; font-weight:800; color:#0f172a; }
    .zi-guide-panel-body { padding:14px 18px 16px; }
    .zi-area-list { display:grid; gap:8px; }
    .zi-area-card { display:block; padding:12px 14px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; color:#0f172a; transition:all .16s ease; }
    .zi-area-card:hover { text-decoration:none; border-color:#a5b4fc; box-shadow:0 6px 18px rgba(59,130,246,.1); transform:translateY(-1px); }
    .zi-area-card.active { background:#eef2ff; border-color:#818cf8; }
    .zi-area-code { font-size:.7rem; font-weight:800; letter-spacing:.06em; color:#4f46e5; text-transform:uppercase; }
    .zi-area-name { font-size:.88rem; font-weight:800; margin-top:2px; color:#0f172a; }
    .zi-area-pic { font-size:.72rem; color:#64748b; font-weight:700; line-height:1.45; margin-top:7px; }
    .zi-member-list { display:grid; gap:6px; min-width:0; }
    .zi-member-list--inline { display:flex; align-items:center; flex-wrap:wrap; gap:6px; }
    .zi-member-chip { display:flex; align-items:flex-start; gap:7px; min-width:0; padding:6px 8px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; color:#334155; font-size:.72rem; font-weight:700; line-height:1.35; }
    .zi-member-list--inline .zi-member-chip { align-items:center; padding:5px 8px; background:#fff; }
    .zi-member-avatar { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; flex:0 0 22px; border-radius:7px; background:#e0e7ff; color:#4338ca; font-size:.59rem; font-weight:900; letter-spacing:.02em; }
    .zi-member-name { min-width:0; overflow-wrap:anywhere; }
    .zi-member-empty { color:#94a3b8; font-size:.72rem; font-weight:600; }
    .zi-member-heading { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; color:#64748b; font-size:.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .zi-area-stats { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-top:8px; }
    .zi-count-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border-radius:999px; background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:.7rem; font-weight:700; }
    .zi-guide-summary { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .zi-guide-kpi { padding:12px 14px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .zi-guide-kpi strong { display:block; font-size:1.2rem; color:#0f172a; line-height:1; margin-bottom:4px; }
    .zi-guide-kpi span { font-size:.74rem; color:#64748b; font-weight:600; }
    .zi-point-card { border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; margin-bottom:14px; }
    .zi-point-card:last-child { margin-bottom:0; }
    .zi-point-head { padding:14px 16px; background:linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); border-bottom:1px solid #edf2f7; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .zi-point-label { font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#4f46e5; margin-bottom:4px; }
    .zi-point-title { font-size:.92rem; font-weight:800; color:#0f172a; line-height:1.4; }
    .zi-point-desc { font-size:.78rem; color:#64748b; margin-top:4px; line-height:1.5; }
    .zi-subpoint-list { display:grid; gap:12px; padding:14px 16px 16px; }
    .zi-subpoint-card { border:1px solid #e5e7eb; border-radius:14px; background:#fcfdff; padding:12px 14px; }
    .zi-subpoint-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px; }
    .zi-subpoint-code { color:#0f766e; font-size:.72rem; font-weight:800; text-transform:uppercase; }
    .zi-subpoint-title { font-size:.86rem; font-weight:700; color:#0f172a; line-height:1.45; }
    .zi-subpoint-desc { font-size:.75rem; color:#64748b; line-height:1.5; margin-top:3px; }
    .zi-indicator-list { display:grid; gap:8px; margin-top:10px; }
    .zi-indicator-card { border:1px solid #e0e7ff; background:#f8fbff; border-radius:12px; padding:10px 12px; }
    .zi-indicator-label { font-size:.7rem; font-weight:800; color:#4338ca; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
    .zi-indicator-text { font-size:.82rem; color:#0f172a; line-height:1.5; font-weight:600; }
    .zi-indicator-meta { display:grid; gap:4px; margin-top:8px; }
    .zi-indicator-meta-item { font-size:.73rem; color:#475569; line-height:1.45; }
    .zi-indicator-meta-item strong { color:#0f172a; }
    .zi-periodic-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 7px; border-radius:999px; background:#fff7ed; border:1px solid #fdba74; color:#c2410c; font-size:.64rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; margin:6px 0 2px; }
    .zi-guide-empty { padding:28px 20px; text-align:center; color:#64748b; border:1px dashed #cbd5e1; border-radius:14px; background:#f8fafc; font-size:.82rem; }
    .zi-inline-actions { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
    .zi-guide-topbar { background:#fff; border:1px solid #e5e7eb; border-radius:16px; box-shadow:0 4px 12px rgba(15,23,42,.04); padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    @media (max-width: 991.98px) {
        .zi-group-grid, .zi-guide-layout, .zi-guide-summary { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('zi-content')
<div class="zi-guide-shell">
    <div class="zi-guide-topbar">
        <div>
            <h5 class="mb-1">Panduan Penyusunan Eviden ZI</h5>
            <div class="small text-muted">Acuan tetap dari PANDUAN PENYUSUNAN EVIDEN.xlsx. Pilih area untuk membaca panduan setiap poin eviden.</div>
        </div>
        <a href="{{ route('progress-zi.activities.index', ['area_id' => optional($selectedArea)->id, 'group_type' => $selectedGroupType]) }}" class="btn btn-primary btn-sm">Kumpulkan Eviden</a>
    </div>
    <details class="border rounded bg-white p-3">
        <summary class="font-weight-bold">Petunjuk umum penyusunan eviden</summary>
        <div class="mt-3 small" style="white-space:pre-wrap;">{{ app(\App\Services\ProgressZi\ZiEvidenceGuideService::class)->document()['introduction'] }}</div>
    </details>
    <div class="zi-group-grid">
        @foreach($groupOptions as $groupType => $groupLabel)
            @php $groupAreas = $groupedAreas->get($groupType, collect()); @endphp
            <a href="{{ route('progress-zi.guidelines.index', ['group_type' => $groupType]) }}" class="zi-group-card {{ $selectedGroupType === $groupType ? 'active' : '' }}">
                <div class="zi-group-eyebrow">Kelompok ZI</div>
                <div class="zi-group-title">{{ $groupLabel }}</div>
                <div class="zi-group-meta"><span class="zi-group-stat">{{ $groupAreas->count() }} area</span></div>
            </a>
        @endforeach
    </div>
    <div class="zi-guide-layout">
        <div class="zi-guide-panel">
            <div class="zi-guide-panel-head"><h5>{{ $groupOptions[$selectedGroupType] ?? 'Area ZI' }}</h5></div>
            <div class="zi-guide-panel-body zi-area-list">
                @forelse($visibleAreas as $area)
                    <a href="{{ route('progress-zi.guidelines.index', ['area_id' => $area->id, 'group_type' => $selectedGroupType]) }}" class="zi-area-card {{ optional($selectedArea)->id === $area->id ? 'active' : '' }}">
                        <div class="zi-area-code">{{ $area->code }}</div>
                        <div class="zi-area-name">{{ $area->name }}</div>
                        <div class="small text-muted mt-2">{{ $area->guidelinePoints->sum(function ($point) { return $point->subPoints->count(); }) }} poin eviden</div>
                    </a>
                @empty
                    <div class="zi-guide-empty">Belum ada panduan untuk kelompok ini.</div>
                @endforelse
            </div>
        </div>
        <div class="zi-guide-panel">
            <div class="zi-guide-panel-head"><h5>{{ optional($selectedArea)->name ?: 'Pilih Area' }}</h5></div>
            <div class="zi-guide-panel-body">
                @if($selectedArea)
                    @foreach($selectedArea->guidelinePoints as $point)
                        <div class="zi-point-card">
                            <div class="zi-point-head"><div class="zi-point-title">{{ $point->title }}</div></div>
                            <div class="zi-subpoint-list">
                                @foreach($point->subPoints as $subPoint)
                                    @php $guide = $subPoint->evidence_guide; @endphp
                                    <div class="zi-subpoint-card">
                                        <div class="zi-subpoint-code">Poin Eviden {{ $guide && $guide['code'] !== '-' ? strtoupper($guide['code']) : '' }}</div>
                                        <div class="zi-subpoint-title mb-3">{{ $guide['title'] ?? $subPoint->title }}</div>
                                        @include('progress-zi.partials.evidence-guide', ['guide' => $guide])
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="zi-guide-empty">Panduan belum tersedia.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
