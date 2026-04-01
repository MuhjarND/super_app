@extends('layouts.app')

@section('title', 'Monitoring Kegiatan ZI')

@push('styles')
<style>
    .zi-monitor-shell { display:grid; gap:18px; }
    .zi-monitor-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 52%, #3b82f6 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.18); }
    .zi-monitor-title { font-size:1.5rem; font-weight:800; margin-bottom:6px; }
    .zi-monitor-subtitle { opacity:.86; font-size:.9rem; max-width:900px; }
    .zi-monitor-filter { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 24px rgba(15,23,42,.05); padding:18px 20px; }
    .zi-area-card { background:#fff; border:1px solid #e5e7eb; border-radius:20px; overflow:hidden; box-shadow:0 10px 24px rgba(15,23,42,.05); }
    .zi-area-head { padding:18px 20px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; border-bottom:1px solid #eef2f7; cursor:pointer; transition:background .15s ease; }
    .zi-area-head:hover { background:#f8fbff; }
    .zi-area-code { font-size:.74rem; font-weight:800; letter-spacing:.08em; color:#2563eb; text-transform:uppercase; }
    .zi-area-title { font-size:1rem; font-weight:800; color:#0f172a; margin-top:4px; }
    .zi-area-desc { font-size:.78rem; color:#64748b; margin-top:5px; line-height:1.5; }
    .zi-area-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
    .zi-area-toggle { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; border:1px solid #dbeafe; background:#eff6ff; color:#2563eb; transition:all .18s ease; }
    .zi-area-head[aria-expanded="false"] .zi-area-toggle i { transform:rotate(-90deg); }
    .zi-area-toggle i { transition:transform .18s ease; }
    .zi-area-body { padding:18px 20px 20px; background:#fbfdff; }
    .zi-point-card { border:1px solid #e2e8f0; border-radius:18px; background:#fff; overflow:hidden; margin-bottom:14px; box-shadow:0 8px 20px rgba(15,23,42,.03); }
    .zi-point-head { padding:14px 16px; background:#f8fbff; border-bottom:1px solid #edf2f7; }
    .zi-point-line, .zi-subpoint-line { display:flex; align-items:flex-start; gap:10px; }
    .zi-point-code-inline, .zi-subpoint-code-inline { font-weight:800; color:#1d4ed8; min-width:22px; }
    .zi-subpoint-code-inline { color:#0f766e; text-transform:lowercase; }
    .zi-point-title { font-size:.95rem; font-weight:800; color:#0f172a; line-height:1.55; }
    .zi-subpoint-row { display:grid; grid-template-columns:1.4fr 1.35fr .85fr auto; gap:14px; padding:15px 16px; border-bottom:1px solid #eef2f7; align-items:start; }
    .zi-subpoint-row:last-child { border-bottom:none; }
    .zi-subpoint-title { font-size:.87rem; font-weight:700; color:#0f172a; line-height:1.6; }
    .zi-indicator-list { display:grid; gap:8px; }
    .zi-indicator-item { border:1px solid #dbeafe; border-radius:12px; background:#f8fbff; padding:10px 11px; }
    .zi-indicator-title { font-size:.79rem; color:#0f172a; line-height:1.55; font-weight:700; }
    .zi-indicator-meta { font-size:.73rem; color:#475569; line-height:1.6; margin-top:6px; }
    .zi-indicator-meta strong { color:#1d4ed8; font-weight:800; }
    .zi-periodic-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; background:#fff7ed; border:1px solid #fdba74; color:#c2410c; font-size:.67rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
    .zi-status-wrap { display:grid; gap:8px; }
    .zi-status-hint { font-size:.75rem; color:#64748b; line-height:1.55; }
    .zi-preview-link { display:inline-flex; align-items:center; gap:6px; font-size:.77rem; font-weight:700; color:#2563eb; cursor:pointer; }
    .zi-action-stack { display:grid; gap:8px; min-width:190px; }
    .zi-empty { color:#94a3b8; font-size:.78rem; padding:8px 0; }
    .zi-kpi-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#fff; border:1px solid #dbeafe; color:#1e40af; font-size:.72rem; font-weight:700; }
    .zi-kpi-chip.is-danger { border-color:#fecaca; background:#fef2f2; color:#b91c1c; }
    .zi-kpi-chip.is-success { border-color:#bbf7d0; background:#f0fdf4; color:#15803d; }
    .zi-kpi-chip.is-progress { border-color:#bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .zi-section-label { font-size:.7rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:8px; }
    .zi-modal-evidence-list { max-height:240px; overflow:auto; display:grid; gap:10px; }
    .zi-modal-evidence-card { border:1px solid #e2e8f0; border-radius:12px; padding:10px 12px; background:#f8fafc; }
    .zi-evidence-mini-title { font-size:.8rem; font-weight:700; color:#0f172a; }
    .zi-modal-preview-frame { width:100%; height:72vh; border:none; border-radius:0 0 16px 16px; }
    .zi-preview-layout { display:grid; grid-template-columns:280px 1fr; min-height:72vh; }
    .zi-preview-sidebar { border-right:1px solid #e2e8f0; background:#f8fafc; overflow:auto; padding:14px; display:grid; gap:10px; }
    .zi-preview-item { width:100%; text-align:left; border:1px solid #dbeafe; background:#fff; border-radius:12px; padding:10px 11px; }
    .zi-preview-item.active { background:#eff6ff; border-color:#60a5fa; }
    .zi-preview-item-title { font-size:.8rem; font-weight:700; color:#0f172a; line-height:1.45; }
    .zi-preview-item-meta { font-size:.72rem; color:#64748b; margin-top:4px; }
    .zi-suggestion-list { display:grid; gap:8px; max-height:220px; overflow:auto; }
    .zi-suggestion-btn { width:100%; text-align:left; border:1px solid #dbeafe; background:#eff6ff; color:#1e3a8a; border-radius:12px; padding:10px 12px; }
    .zi-suggestion-btn:hover { background:#dbeafe; }
    .zi-suggestion-type { display:inline-flex; align-items:center; gap:6px; font-size:.67rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#2563eb; margin-bottom:4px; }
    .zi-suggestion-title { font-size:.8rem; font-weight:700; color:#0f172a; line-height:1.45; }
    .zi-suggestion-meta { font-size:.72rem; color:#64748b; margin-top:4px; }
    @media (max-width: 1199.98px) {
        .zi-subpoint-row { grid-template-columns:1fr; }
        .zi-action-stack { min-width:0; }
    }
    @media (max-width: 991.98px) {
        .zi-preview-layout { grid-template-columns:1fr; }
        .zi-preview-sidebar { border-right:none; border-bottom:1px solid #e2e8f0; max-height:220px; }
    }
</style>
@endpush

@section('content')
<div class="zi-monitor-shell">
    <div class="zi-monitor-hero">
        <div class="zi-monitor-title">Monitoring Kegiatan ZI</div>
        <div class="zi-monitor-subtitle">Halaman ini menjadi pusat tindak lanjut sub poin pedoman Zona Integritas. Kegiatan dilakukan dalam bentuk rapat monitoring dan evaluasi, eviden diunggah langsung dari sini, lalu diajukan ke review pimpinan melalui approval.</div>
    </div>

    <div class="zi-monitor-filter">
        <form method="GET" class="row">
            <div class="col-md-3 form-group mb-md-0">
                <label>Periode</label>
                <select class="form-control" name="period_id">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                        <option value="{{ $period->id }}" {{ optional($selectedPeriod)->id === $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group mb-md-0">
                <label>Area</label>
                <select class="form-control" name="area_id">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" {{ (string) ($filters['area_id'] ?? '') === (string) $area->id ? 'selected' : '' }}>{{ $area->code }} - {{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label>Status Kegiatan</label>
                <select class="form-control" name="status">
                    <option value="">Semua</option>
                    @foreach(['belum_mulai' => 'Belum Mulai','dijadwalkan' => 'Dijadwalkan','sedang_berjalan' => 'Sedang Berjalan','sudah_terlaksana' => 'Sudah Terlaksana','selesai' => 'Selesai','perlu_perbaikan' => 'Perlu Perbaikan'] as $key => $label)
                        <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group mb-md-0">
                <label>PIC</label>
                <select class="form-control" name="pic_user_id">
                    <option value="">Semua</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string) ($filters['pic_user_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 form-group d-flex align-items-end mb-0" style="gap:10px;">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('progress-zi.activities.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    @forelse($areas as $area)
        @php
            $totalSubPoints = $area->guidelinePoints->sum(function ($point) { return $point->subPoints->count(); });
            $coveredSubPoints = $area->guidelinePoints->sum(function ($point) use ($area) {
                return $point->subPoints->filter(function ($subPoint) use ($area) {
                    $subPointActivities = $area->activities->where('zi_guideline_sub_point_id', $subPoint->id)->values();
                    $rapatActivities = $subPointActivities->filter(function ($activity) {
                        return $activity->source_type === 'rapat' || $activity->source_reference_type === 'rapat';
                    });
                    $requiresPeriodic = $subPoint->indicators->contains(function ($indicator) {
                        return (bool) $indicator->is_periodic;
                    });

                    if ($requiresPeriodic) {
                        return $rapatActivities->count() > 1;
                    }

                    return $subPointActivities->isNotEmpty();
                })->count();
            });
            $coverageClass = $coveredSubPoints === 0 ? 'is-danger' : ($coveredSubPoints === $totalSubPoints && $totalSubPoints > 0 ? 'is-success' : 'is-progress');
            $canManageArea = auth()->user()->canManageProgressZiArea($area);
            $canCreateMeeting = $canManageArea && auth()->user()->canManageRapat();
        @endphp
        <div class="zi-area-card">
            <div class="zi-area-head" data-toggle="collapse" data-target="#ziAreaBody{{ $area->id }}" aria-expanded="false" aria-controls="ziAreaBody{{ $area->id }}">
                <div>
                    <div class="zi-area-code">{{ $area->code }}</div>
                    <div class="zi-area-title">{{ $area->name }}</div>
                    <div class="zi-area-desc">{{ $area->description ?: 'Belum ada deskripsi area.' }}</div>
                </div>
                <div class="zi-area-meta">
                    <span class="zi-kpi-chip"><i class="fas fa-bookmark"></i>{{ $area->guidelinePoints->count() }} poin</span>
                    <span class="zi-kpi-chip {{ $coverageClass }}"><i class="fas fa-stream"></i>{{ $coveredSubPoints }}/{{ $totalSubPoints }} sub poin ditindaklanjuti</span>
                    <span class="zi-kpi-chip"><i class="fas fa-user-tie"></i>{{ $area->pic_names !== '-' ? $area->pic_names : 'PIC belum ditetapkan' }}</span>
                    <span class="zi-area-toggle"><i class="fas fa-chevron-down"></i></span>
                </div>
            </div>

            <div id="ziAreaBody{{ $area->id }}" class="collapse">
                <div class="zi-area-body">
                    @forelse($area->guidelinePoints as $point)
                        <div class="zi-point-card">
                            <div class="zi-point-head">
                                <div class="zi-point-line">
                                    <div class="zi-point-code-inline">{{ $point->code }}.</div>
                                    <div class="zi-point-title">{{ $point->title }}</div>
                                </div>
                            </div>

                            @forelse($point->subPoints as $subPoint)
                                @php
                                    $subPointActivities = $area->activities->where('zi_guideline_sub_point_id', $subPoint->id)->sortByDesc('id')->values();
                                    $latestActivity = $subPointActivities->first();
                                    $latestApproval = optional($latestActivity)->latestApproval;
                                    $rapatActivities = $subPointActivities->filter(function ($activity) {
                                        return $activity->source_type === 'rapat' || $activity->source_reference_type === 'rapat';
                                    })->values();
                                    $requiresPeriodic = $subPoint->indicators->contains(function ($indicator) {
                                        return (bool) $indicator->is_periodic;
                                    });
                                    $periodicCompleted = !$requiresPeriodic || $rapatActivities->count() > 1 || ($latestApproval && $latestApproval->status === 'approved');
                                    $allEvidences = $subPointActivities->flatMap(function ($activity) {
                                        return $activity->realizations->flatMap(function ($realization) {
                                            return $realization->evidences;
                                        });
                                    })->values();
                                    $evidenceCount = $allEvidences->count();
                                    if (!$latestActivity) {
                                        $statusClass = 'secondary';
                                        $statusLabel = 'Belum Ditindaklanjuti';
                                    } elseif ($latestApproval && $latestApproval->status === 'approved' && $periodicCompleted) {
                                        $statusClass = 'success';
                                        $statusLabel = 'Selesai';
                                    } elseif ($latestApproval && $latestApproval->status === 'pending') {
                                        $statusClass = 'warning';
                                        $statusLabel = 'Dalam Proses Review';
                                    } elseif ($evidenceCount > 0) {
                                        $statusClass = 'primary';
                                        $statusLabel = 'Diupload';
                                    } else {
                                        $statusClass = 'info';
                                        $statusLabel = 'Dijadwalkan';
                                    }
                                    $canUploadEvidence = $canManageArea;
                                    $canSubmitReview = $latestActivity && $canManageArea && $evidenceCount > 0 && (!$latestApproval || $latestApproval->status === 'rejected');
                                    $canPreview = $statusLabel === 'Selesai' && $latestActivity && $latestActivity->monitoring_preview_url;
                                @endphp
                                <div class="zi-subpoint-row">
                                    <div>
                                        <div class="zi-subpoint-line">
                                            <div class="zi-subpoint-code-inline">{{ strtolower($subPoint->code) }}.</div>
                                            <div class="zi-subpoint-title">{{ $subPoint->title }}</div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="zi-section-label">Indikator Patokan</div>
                                        <div class="zi-indicator-list">
                                            @forelse($subPoint->indicators as $indicator)
                                                <div class="zi-indicator-item">
                                                    @if($indicator->is_periodic)
                                                        <div class="zi-periodic-chip"><i class="fas fa-sync-alt"></i>Berkala</div>
                                                    @endif
                                                    <div class="zi-indicator-title">{{ $indicator->indicator_text }}</div>
                                                    @if($indicator->evidence_example)
                                                        <div class="zi-indicator-meta"><strong>Contoh Eviden:</strong> {{ $indicator->evidence_example }}</div>
                                                    @endif
                                                    @if($indicator->implementation_note)
                                                        <div class="zi-indicator-meta"><strong>Catatan Implementasi:</strong> {{ $indicator->implementation_note }}</div>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="zi-empty">Belum ada indikator penilaian.</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <div class="zi-section-label">Status</div>
                                        <div class="zi-status-wrap">
                                            <span class="badge badge-{{ $statusClass }} app-status-badge">{{ $statusLabel }}</span>
                                            @if($latestActivity)
                                                <div class="zi-status-hint">
                                                    {{ $latestActivity->name }}<br>
                                                    PIC {{ optional($latestActivity->pic)->name ?: '-' }} • {{ $evidenceCount }} eviden
                                                    @if($requiresPeriodic)
                                                        <br>Agenda berkala: {{ $rapatActivities->count() }} kali
                                                        @if(!$periodicCompleted)
                                                            <br>Masih membutuhkan lebih dari 1 agenda/kegiatan.
                                                        @endif
                                                    @endif
                                                </div>
                                            @else
                                                <div class="zi-status-hint">Belum ada agenda kegiatan dan eviden.</div>
                                            @endif
                                            @if($canPreview)
                                                <a href="#" class="zi-preview-link zi-open-preview" data-title="Preview Eviden - {{ $subPoint->title }}" data-url="{{ $latestActivity->monitoring_preview_url }}" data-preview-list="#ziPreviewListTemplate{{ $subPoint->id }}">
                                                    <i class="fas fa-eye"></i> Preview Eviden
                                                </a>
                                                <a href="{{ route('progress-zi.activities.evidences.bundle', $latestActivity) }}" target="_blank" class="zi-preview-link">
                                                    <i class="fas fa-file-pdf"></i> Bundel PDF
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <div class="zi-action-stack">
                                            <button type="button"
                                                    class="btn btn-primary btn-sm zi-open-meeting-modal"
                                                    data-toggle="modal"
                                                    data-target="#createRapatModal"
                                                    data-area-id="{{ $area->id }}"
                                                    data-area-name="{{ $area->code }} - {{ $area->name }}"
                                                    data-subpoint-id="{{ $subPoint->id }}"
                                                    data-subpoint-code="{{ strtolower($subPoint->code) }}"
                                                    data-subpoint-title="{{ $subPoint->title }}"
                                                    data-period-id="{{ optional($selectedPeriod)->id }}"
                                                    data-default-title="Rapat Monitoring dan Evaluasi - {{ $subPoint->title }}"
                                                    {{ $canCreateMeeting ? '' : 'disabled' }}>
                                                    <i class="fas fa-calendar-plus mr-1"></i>Adakan Kegiatan
                                                </button>

                                            <button type="button"
                                                class="btn btn-success btn-sm zi-open-evidence-modal"
                                                data-toggle="modal"
                                                data-target="#uploadEvidenceModal"
                                                data-activity-id="{{ optional($latestActivity)->id }}"
                                                data-area-id="{{ $area->id }}"
                                                data-period-id="{{ optional($selectedPeriod)->id }}"
                                                data-subpoint-id="{{ $subPoint->id }}"
                                                data-area-name="{{ $area->code }} - {{ $area->name }}"
                                                data-subpoint-title="{{ $subPoint->title }}"
                                                data-existing-evidence="#ziEvidenceListTemplate{{ $subPoint->id }}"
                                                data-recommendations="#ziRecommendationListTemplate{{ $subPoint->id }}"
                                                {{ $canUploadEvidence ? '' : 'disabled' }}>
                                                    <i class="fas fa-upload mr-1"></i>Upload Eviden
                                            </button>

                                            <form method="POST" action="{{ $latestActivity ? route('progress-zi.activities.submit-review', $latestActivity) : '#' }}">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm btn-block" {{ $canSubmitReview ? '' : 'disabled' }}>
                                                    <i class="fas fa-clipboard-check mr-1"></i>Review Pimpinan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-none" id="ziEvidenceListTemplate{{ $subPoint->id }}">
                                    @forelse($allEvidences as $evidence)
                                        <div class="zi-modal-evidence-card">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="zi-evidence-mini-title">{{ $evidence->title }}</div>
                                                    <div class="small text-muted">{{ $evidence->source_reference_label }}</div>
                                                </div>
                                                {!! $evidence->status_badge !!}
                                            </div>
                                            @if($evidence->description)
                                                <div class="small text-muted mt-2">{{ $evidence->description }}</div>
                                            @endif
                                            @if($evidence->preview_url)
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 zi-open-preview" data-title="{{ $evidence->title }}" data-url="{{ $evidence->preview_url }}" data-preview-list="#ziPreviewListTemplate{{ $subPoint->id }}">Preview Eviden</button>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted small">Belum ada eviden untuk sub poin ini.</div>
                                    @endforelse
                                </div>
                                <div class="d-none" id="ziPreviewListTemplate{{ $subPoint->id }}">
                                    @php($previewableEvidences = $allEvidences->filter(function ($evidence) { return !empty($evidence->preview_url); })->values())
                                    @forelse($previewableEvidences as $evidence)
                                        <button type="button" class="zi-preview-item" data-url="{{ $evidence->preview_url }}" data-title="{{ $evidence->title }}">
                                            <div class="zi-preview-item-title">{{ $evidence->title }}</div>
                                            <div class="zi-preview-item-meta">{{ $evidence->source_reference_label }}</div>
                                        </button>
                                    @empty
                                        <div class="text-muted small">Belum ada eviden yang bisa dipreview.</div>
                                    @endforelse
                                </div>
                                <div class="d-none" id="ziRecommendationListTemplate{{ $subPoint->id }}">
                                    @php($recommendations = $subPointRecommendations[$subPoint->id] ?? [])
                                    @forelse($recommendations as $recommendation)
                                        <button type="button"
                                            class="zi-suggestion-btn zi-apply-recommendation"
                                            data-linked-source="{{ $recommendation['linked_source'] }}"
                                            data-title="{{ $recommendation['title'] }}"
                                            data-description="{{ $recommendation['meta'] }}">
                                            <div class="zi-suggestion-type"><i class="fas fa-magic"></i>{{ $recommendation['type'] }}</div>
                                            <div class="zi-suggestion-title">{{ $recommendation['title'] }}</div>
                                            @if(!empty($recommendation['meta']))
                                                <div class="zi-suggestion-meta">{{ $recommendation['meta'] }}</div>
                                            @endif
                                        </button>
                                    @empty
                                        <div class="text-muted small">Belum ada saran eviden otomatis untuk sub poin ini.</div>
                                    @endforelse
                                </div>
                            @empty
                                <div class="px-3 py-4 text-center text-muted">Belum ada sub poin pedoman pada poin ini.</div>
                            @endforelse
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Belum ada poin pedoman untuk area ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm border-0">
            <div class="card-body text-center text-muted py-4">Belum ada area atau pedoman yang bisa dimonitor.</div>
        </div>
    @endforelse
</div>

@if(auth()->user()->canManageRapat())
    @include('rapat.partials.form-modal', [
        'modalId' => 'createRapatModal',
        'formId' => 'createRapatForm',
        'title' => 'Adakan Rapat Monitoring dan Evaluasi',
        'submitLabel' => 'Simpan Rapat',
        'action' => route('rapat.store'),
        'method' => 'POST',
        'kategoriSuratOptions' => $kategoriSuratOptions,
        'participants' => $meetingParticipants,
        'approvers' => $meetingApprovers,
    ])
@endif

<div class="modal fade" id="uploadEvidenceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Eviden Sub Poin</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="#" id="uploadEvidenceForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="zi_period_id" id="uploadEvidencePeriodId">
                <input type="hidden" name="zi_area_id" id="uploadEvidenceAreaId">
                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <div class="small text-muted">Sub Poin</div>
                        <div class="font-weight-bold" id="uploadEvidenceSubPoint">-</div>
                        <div class="small text-muted mt-1" id="uploadEvidenceArea">-</div>
                    </div>
                    <div class="form-group">
                        <label>Mode Eviden</label>
                        <select class="form-control" name="mode" id="uploadEvidenceMode" required>
                            <option value="manual">Upload Manual</option>
                            <option value="linked">Hubungkan dari Modul Lain</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Judul Eviden</label>
                        <input type="text" class="form-control" name="title" id="uploadEvidenceTitle">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-group" id="uploadEvidenceFileGroup">
                        <label>File Eviden</label>
                        <input type="file" class="form-control-file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    </div>
                    <div class="form-group d-none" id="uploadEvidenceLinkedGroup">
                        <label>Sumber Eviden Terhubung</label>
                        <select class="form-control" name="linked_source" id="uploadEvidenceLinkedSource">
                            <option value="">-- Pilih Eviden --</option>
                            @foreach($evidenceSourceOptions as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="border rounded p-3 bg-light mb-3">
                        <div class="small text-muted mb-2">Saran Eviden Otomatis</div>
                        <div class="zi-suggestion-list" id="recommendedEvidenceList">
                            <div class="text-muted small">Belum ada rekomendasi eviden.</div>
                        </div>
                    </div>
                    <div class="border rounded p-3 bg-light">
                        <div class="small text-muted mb-2">Eviden yang Sudah Ada</div>
                        <div class="zi-modal-evidence-list" id="existingEvidenceList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button class="btn btn-success">Simpan Eviden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="ziEvidencePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ziEvidencePreviewTitle">Preview Eviden</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <div class="zi-preview-layout">
                    <div class="zi-preview-sidebar" id="ziEvidencePreviewList">
                        <div class="text-muted small">Belum ada eviden yang bisa dipreview.</div>
                    </div>
                    <div>
                        <iframe id="ziEvidencePreviewFrame" class="zi-modal-preview-frame" src="about:blank"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function ensureProgressZiContextFields() {
            var form = document.getElementById('createRapatForm');
            if (!form) {
                return;
            }

            ['zi_source_context', 'zi_period_id', 'zi_area_id', 'zi_guideline_sub_point_id'].forEach(function (name) {
                if (!form.querySelector('[name="' + name + '"]')) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }
            });
        }

        function toggleEvidenceMode() {
            var mode = document.getElementById('uploadEvidenceMode').value;
            document.getElementById('uploadEvidenceFileGroup').classList.toggle('d-none', mode !== 'manual');
            document.getElementById('uploadEvidenceLinkedGroup').classList.toggle('d-none', mode !== 'linked');
        }

        document.querySelectorAll('.zi-open-meeting-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                ensureProgressZiContextFields();
                var form = document.getElementById('createRapatForm');
                if (!form) {
                    return;
                }

                form.querySelector('[name="zi_source_context"]').value = 'progress_zi';
                form.querySelector('[name="zi_period_id"]').value = button.getAttribute('data-period-id') || '';
                form.querySelector('[name="zi_area_id"]').value = button.getAttribute('data-area-id') || '';
                form.querySelector('[name="zi_guideline_sub_point_id"]').value = button.getAttribute('data-subpoint-id') || '';

                document.getElementById('createJudul').value = button.getAttribute('data-default-title') || '';
                document.getElementById('createDeskripsi').value = 'Rapat monitoring dan evaluasi untuk tindak lanjut sub poin ' + (button.getAttribute('data-subpoint-code') || '-') + '. ' + (button.getAttribute('data-subpoint-title') || '');
                document.getElementById('createStatus').value = 'pending_approval';
                document.getElementById('createTanggal').value = '{{ now()->format('Y-m-d') }}';
                document.getElementById('createWaktuMulai').value = '{{ now()->timezone('Asia/Jayapura')->format('H:i') }}';
            });
        });

        document.querySelectorAll('.zi-open-evidence-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                var activityId = button.getAttribute('data-activity-id');
                var subPointId = button.getAttribute('data-subpoint-id');
                var areaId = button.getAttribute('data-area-id');
                var periodId = button.getAttribute('data-period-id');

                if (activityId) {
                    document.getElementById('uploadEvidenceForm').setAttribute('action', '{{ url('progress-zi/activities') }}/' + activityId + '/evidences');
                } else {
                    document.getElementById('uploadEvidenceForm').setAttribute('action', '{{ url('progress-zi/sub-points') }}/' + subPointId + '/evidences');
                }
                document.getElementById('uploadEvidencePeriodId').value = periodId || '';
                document.getElementById('uploadEvidenceAreaId').value = areaId || '';
                document.getElementById('uploadEvidenceSubPoint').textContent = button.getAttribute('data-subpoint-title') || '-';
                document.getElementById('uploadEvidenceArea').textContent = button.getAttribute('data-area-name') || '-';
                document.getElementById('uploadEvidenceTitle').value = 'Eviden - ' + (button.getAttribute('data-subpoint-title') || '');
                document.getElementById('uploadEvidenceLinkedSource').value = '';
                document.getElementById('uploadEvidenceMode').value = 'manual';
                toggleEvidenceMode();

                var targetSelector = button.getAttribute('data-existing-evidence');
                var template = targetSelector ? document.querySelector(targetSelector) : null;
                document.getElementById('existingEvidenceList').innerHTML = template ? template.innerHTML : '<div class="text-muted small">Belum ada eviden.</div>';

                var recommendationSelector = button.getAttribute('data-recommendations');
                var recommendationTemplate = recommendationSelector ? document.querySelector(recommendationSelector) : null;
                document.getElementById('recommendedEvidenceList').innerHTML = recommendationTemplate ? recommendationTemplate.innerHTML : '<div class="text-muted small">Belum ada rekomendasi eviden.</div>';
            });
        });

        document.addEventListener('click', function (event) {
            var previewItem = event.target.closest('.zi-preview-item');
            if (previewItem) {
                event.preventDefault();
                document.querySelectorAll('#ziEvidencePreviewList .zi-preview-item').forEach(function (item) {
                    item.classList.remove('active');
                });
                previewItem.classList.add('active');
                document.getElementById('ziEvidencePreviewTitle').textContent = previewItem.getAttribute('data-title') || 'Preview Eviden';
                document.getElementById('ziEvidencePreviewFrame').setAttribute('src', previewItem.getAttribute('data-url') || 'about:blank');
                return;
            }

            var trigger = event.target.closest('.zi-open-preview');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            var previewListContainer = document.getElementById('ziEvidencePreviewList');
            var previewListSelector = trigger.getAttribute('data-preview-list');
            var previewListTemplate = previewListSelector ? document.querySelector(previewListSelector) : null;
            previewListContainer.innerHTML = previewListTemplate ? previewListTemplate.innerHTML : '<div class="text-muted small">Belum ada eviden yang bisa dipreview.</div>';
            document.getElementById('ziEvidencePreviewTitle').textContent = trigger.getAttribute('data-title') || 'Preview Eviden';
            document.getElementById('ziEvidencePreviewFrame').setAttribute('src', trigger.getAttribute('data-url') || 'about:blank');
            var selectedItem = previewListContainer.querySelector('.zi-preview-item[data-url="' + (trigger.getAttribute('data-url') || '') + '"]') || previewListContainer.querySelector('.zi-preview-item');
            if (selectedItem) {
                selectedItem.classList.add('active');
            }
            $('#ziEvidencePreviewModal').modal('show');
        });

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('.zi-apply-recommendation');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            document.getElementById('uploadEvidenceMode').value = 'linked';
            toggleEvidenceMode();
            document.getElementById('uploadEvidenceLinkedSource').value = trigger.getAttribute('data-linked-source') || '';
            document.getElementById('uploadEvidenceTitle').value = trigger.getAttribute('data-title') || '';
            var descriptionField = document.querySelector('#uploadEvidenceForm textarea[name="description"]');
            if (descriptionField && !descriptionField.value) {
                descriptionField.value = trigger.getAttribute('data-description') || '';
            }
        });

        document.getElementById('uploadEvidenceMode').addEventListener('change', toggleEvidenceMode);
        toggleEvidenceMode();

        @if(auth()->user()->canManageRapat())
            $('#createRapatModal').on('shown.bs.modal', function () {
                $(this).find('.select2').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $('#createRapatModal')
                    });
                });
            });

            $('#createRapatForm').on('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route('rapat.store') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        showToast(res.message, 'success');
                        location.reload();
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON?.errors;
                        let message = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                        if (errors) {
                            message = Object.values(errors).flat().join('<br>');
                        }
                        showToast(message, 'error');
                    }
                });
            });
        @endif
    })();
</script>
@endpush
