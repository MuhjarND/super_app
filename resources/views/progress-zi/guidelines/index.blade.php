@extends('layouts.app')

@section('title', 'Pedoman ZI')

@push('styles')
<style>
    .zi-guide-shell { display:grid; gap:18px; }
    .zi-guide-hero { background: linear-gradient(135deg, #0f3352 0%, #175d8f 52%, #3b82f6 100%); color:#fff; border-radius:18px; padding:24px 26px; box-shadow:0 18px 40px rgba(15, 51, 82, 0.18); }
    .zi-guide-title { font-size:1.55rem; font-weight:800; margin-bottom:6px; }
    .zi-guide-meta { opacity:.88; font-size:.92rem; max-width:820px; }
    .zi-guide-layout { display:grid; grid-template-columns:320px 1fr; gap:18px; align-items:start; }
    .zi-guide-panel { background:#fff; border:1px solid #e5e7eb; border-radius:18px; box-shadow:0 10px 26px rgba(15,23,42,.05); overflow:hidden; }
    .zi-guide-panel-head { padding:18px 20px 14px; border-bottom:1px solid #eef2f7; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .zi-guide-panel-head h5 { margin:0; font-size:1rem; font-weight:800; color:#0f172a; }
    .zi-guide-panel-head p { margin:4px 0 0; font-size:.78rem; color:#64748b; }
    .zi-guide-panel-body { padding:16px 20px 18px; }
    .zi-area-list { display:grid; gap:10px; }
    .zi-area-card { display:block; padding:14px 15px; border:1px solid #e2e8f0; border-radius:14px; background:#fff; color:#0f172a; transition:all .16s ease; }
    .zi-area-card:hover { text-decoration:none; border-color:#93c5fd; box-shadow:0 8px 24px rgba(59,130,246,.12); transform:translateY(-1px); }
    .zi-area-card.active { background:#eff6ff; border-color:#60a5fa; }
    .zi-area-code { font-size:.72rem; font-weight:800; letter-spacing:.08em; color:#2563eb; text-transform:uppercase; }
    .zi-area-name { font-size:.95rem; font-weight:800; margin-top:4px; color:#0f172a; }
    .zi-area-desc { font-size:.75rem; color:#64748b; margin-top:6px; line-height:1.5; }
    .zi-area-stats { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:10px; }
    .zi-count-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#f8fafc; border:1px solid #e2e8f0; color:#334155; font-size:.72rem; font-weight:700; }
    .zi-guide-summary { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .zi-guide-kpi { padding:14px 16px; border:1px solid #e5e7eb; border-radius:16px; background:#fff; }
    .zi-guide-kpi strong { display:block; font-size:1.35rem; color:#0f172a; line-height:1; margin-bottom:6px; }
    .zi-guide-kpi span { font-size:.76rem; color:#64748b; font-weight:600; }
    .zi-point-card { border:1px solid #e5e7eb; border-radius:18px; overflow:hidden; background:#fff; box-shadow:0 10px 24px rgba(15,23,42,.04); margin-bottom:16px; }
    .zi-point-head { padding:16px 18px; background:linear-gradient(180deg, #f8fbff 0%, #ffffff 100%); border-bottom:1px solid #edf2f7; display:flex; align-items:flex-start; justify-content:space-between; gap:14px; }
    .zi-point-label { font-size:.76rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#2563eb; margin-bottom:6px; }
    .zi-point-title { font-size:1rem; font-weight:800; color:#0f172a; line-height:1.4; }
    .zi-point-desc { font-size:.8rem; color:#64748b; margin-top:6px; line-height:1.6; }
    .zi-subpoint-list { display:grid; gap:14px; padding:16px 18px 18px; }
    .zi-subpoint-card { border:1px solid #e5e7eb; border-radius:16px; background:#fcfdff; padding:14px 15px; }
    .zi-subpoint-head { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:10px; }
    .zi-subpoint-code { color:#0f766e; font-size:.75rem; font-weight:800; text-transform:uppercase; }
    .zi-subpoint-title { font-size:.92rem; font-weight:700; color:#0f172a; line-height:1.5; }
    .zi-subpoint-desc { font-size:.77rem; color:#64748b; line-height:1.55; margin-top:4px; }
    .zi-indicator-list { display:grid; gap:10px; margin-top:12px; }
    .zi-indicator-card { border:1px solid #dbeafe; background:#f8fbff; border-radius:14px; padding:12px 13px; }
    .zi-indicator-label { font-size:.72rem; font-weight:800; color:#1d4ed8; text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
    .zi-indicator-text { font-size:.84rem; color:#0f172a; line-height:1.6; font-weight:600; }
    .zi-indicator-meta { display:grid; gap:6px; margin-top:10px; }
    .zi-indicator-meta-item { font-size:.75rem; color:#475569; line-height:1.55; }
    .zi-indicator-meta-item strong { color:#0f172a; }
    .zi-periodic-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; background:#fff7ed; border:1px solid #fdba74; color:#c2410c; font-size:.67rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; margin:8px 0 2px; }
    .zi-guide-empty { padding:34px 22px; text-align:center; color:#64748b; border:1px dashed #cbd5e1; border-radius:18px; background:#f8fafc; }
    .zi-inline-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    @media (max-width: 991.98px) {
        .zi-guide-layout, .zi-guide-summary { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="zi-guide-shell">
    <div class="zi-guide-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-end" style="gap:16px;">
            <div>
                <div class="zi-guide-title">Pedoman Zona Integritas</div>
                <div class="zi-guide-meta">Menu ini menjadi patokan pelaksanaan kegiatan Progress ZI. Struktur acuan dibaca per area, lalu diturunkan ke poin, sub poin, dan indikator penilaian agar PIC kegiatan memakai referensi yang sama saat menindaklanjuti eviden dan realisasi.</div>
            </div>
            @if($canManage && $selectedArea)
                <button class="app-create-btn" data-toggle="modal" data-target="#createPointModal">
                    <i class="fas fa-plus"></i>Tambah Poin
                </button>
            @endif
        </div>
    </div>

    <div class="zi-guide-layout">
        <div class="zi-guide-panel">
            <div class="zi-guide-panel-head">
                <div>
                    <h5>Area Perubahan</h5>
                    <p>Pilih area untuk membaca pedoman acuan pelaksanaan.</p>
                </div>
            </div>
            <div class="zi-guide-panel-body">
                <div class="zi-area-list">
                    @forelse($areas as $area)
                        @php
                            $pointCount = $area->guidelinePoints->count();
                            $subPointCount = $area->guidelinePoints->sum(function ($point) { return $point->subPoints->count(); });
                            $indicatorCount = $area->guidelinePoints->sum(function ($point) {
                                return $point->subPoints->sum(function ($subPoint) {
                                    return $subPoint->indicators->count();
                                });
                            });
                        @endphp
                        <a href="{{ route('progress-zi.guidelines.index', ['area_id' => $area->id]) }}" class="zi-area-card {{ optional($selectedArea)->id === $area->id ? 'active' : '' }}">
                            <div class="zi-area-code">{{ $area->code }}</div>
                            <div class="zi-area-name">{{ $area->name }}</div>
                            <div class="zi-area-desc">{{ $area->description ?: 'Belum ada deskripsi area.' }}</div>
                            <div class="zi-area-stats">
                                <span class="zi-count-chip"><i class="fas fa-bookmark"></i>{{ $pointCount }} poin</span>
                                <span class="zi-count-chip"><i class="fas fa-stream"></i>{{ $subPointCount }} sub poin</span>
                                <span class="zi-count-chip"><i class="fas fa-check-circle"></i>{{ $indicatorCount }} indikator</span>
                            </div>
                        </a>
                    @empty
                        <div class="zi-guide-empty">Belum ada area perubahan yang tersedia.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="zi-guide-panel">
            <div class="zi-guide-panel-head">
                <div>
                    <h5>{{ optional($selectedArea)->name ?: 'Pedoman Area' }}</h5>
                    <p>{{ optional($selectedArea)->description ?: 'Pilih area di panel kiri untuk melihat pedoman acuan pelaksanaannya.' }}</p>
                </div>
                @if($selectedArea && $selectedArea->pic_names !== '-')
                    <span class="zi-count-chip"><i class="fas fa-user-tie"></i>PIC {{ $selectedArea->pic_names }}</span>
                @endif
            </div>
            <div class="zi-guide-panel-body">
                @if($selectedArea)
                    @php
                        $selectedPointCount = $selectedArea->guidelinePoints->count();
                        $selectedSubPointCount = $selectedArea->guidelinePoints->sum(function ($point) { return $point->subPoints->count(); });
                        $selectedIndicatorCount = $selectedArea->guidelinePoints->sum(function ($point) {
                            return $point->subPoints->sum(function ($subPoint) {
                                return $subPoint->indicators->count();
                            });
                        });
                    @endphp
                    <div class="zi-guide-summary">
                        <div class="zi-guide-kpi"><strong>{{ $selectedPointCount }}</strong><span>Poin Area</span></div>
                        <div class="zi-guide-kpi"><strong>{{ $selectedSubPointCount }}</strong><span>Sub Poin Acuan</span></div>
                        <div class="zi-guide-kpi"><strong>{{ $selectedIndicatorCount }}</strong><span>Indikator Penilaian</span></div>
                    </div>

                    @forelse($selectedArea->guidelinePoints as $point)
                        <div class="zi-point-card">
                            <div class="zi-point-head">
                                <div>
                                    <div class="zi-point-label">{{ $point->code }}</div>
                                    <div class="zi-point-title">{{ $point->title }}</div>
                                    @if($point->description)
                                        <div class="zi-point-desc">{{ $point->description }}</div>
                                    @endif
                                </div>
                                @if($canManage)
                                    <div class="zi-inline-actions">
                                        <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#createSubPointModal{{ $point->id }}">
                                            <i class="fas fa-plus mr-1"></i>Sub Poin
                                        </button>
                                        <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#editPointModal{{ $point->id }}">
                                            <i class="fas fa-pen mr-1"></i>Edit
                                        </button>
                                        <form method="POST" action="{{ route('progress-zi.guidelines.points.destroy', $point) }}" onsubmit="return confirm('Hapus poin pedoman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash mr-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            <div class="zi-subpoint-list">
                                @forelse($point->subPoints as $subPoint)
                                    <div class="zi-subpoint-card">
                                        <div class="zi-subpoint-head">
                                            <div>
                                                <div class="zi-subpoint-code">{{ strtolower($subPoint->code) }}</div>
                                                <div class="zi-subpoint-title">{{ $subPoint->title }}</div>
                                                @if($subPoint->description)
                                                    <div class="zi-subpoint-desc">{{ $subPoint->description }}</div>
                                                @endif
                                            </div>
                                            @if($canManage)
                                                <div class="zi-inline-actions">
                                                    <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#createIndicatorModal{{ $subPoint->id }}">
                                                        <i class="fas fa-plus mr-1"></i>Indikator
                                                    </button>
                                                    <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#editSubPointModal{{ $subPoint->id }}">
                                                        <i class="fas fa-pen mr-1"></i>Edit
                                                    </button>
                                                    <form method="POST" action="{{ route('progress-zi.guidelines.sub-points.destroy', $subPoint) }}" onsubmit="return confirm('Hapus sub poin pedoman ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash mr-1"></i>Hapus
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="zi-indicator-list">
                                            @forelse($subPoint->indicators as $indicator)
                                                <div class="zi-indicator-card">
                                                    <div class="d-flex justify-content-between align-items-start" style="gap:10px;">
                                                        <div class="zi-indicator-label mb-0">{{ $indicator->code ? 'Indikator ' . $indicator->code : 'Indikator Penilaian' }}</div>
                                                        @if($canManage)
                                                            <div class="zi-inline-actions">
                                                                <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#editIndicatorModal{{ $indicator->id }}">
                                                                    <i class="fas fa-pen mr-1"></i>Edit
                                                                </button>
                                                                <form method="POST" action="{{ route('progress-zi.guidelines.indicators.destroy', $indicator) }}" onsubmit="return confirm('Hapus indikator penilaian ini?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button class="btn btn-danger btn-sm">
                                                                        <i class="fas fa-trash mr-1"></i>Hapus
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    @if($indicator->is_periodic)
                                                        <div class="zi-periodic-chip"><i class="fas fa-sync-alt"></i>Berkala</div>
                                                    @endif
                                                    <div class="zi-indicator-text">{{ $indicator->indicator_text }}</div>
                                                    <div class="zi-indicator-meta">
                                                        <div class="zi-indicator-meta-item"><strong>Agenda Berkala:</strong> {{ $indicator->is_periodic ? 'Ya, wajib lebih dari 1 kali' : 'Tidak' }}</div>
                                                        @if($indicator->evidence_example)
                                                            <div class="zi-indicator-meta-item"><strong>Contoh Eviden:</strong> {{ $indicator->evidence_example }}</div>
                                                        @endif
                                                        @if($indicator->implementation_note)
                                                            <div class="zi-indicator-meta-item"><strong>Catatan Implementasi:</strong> {{ $indicator->implementation_note }}</div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($canManage)
                                                    <div class="modal fade" id="editIndicatorModal{{ $indicator->id }}" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Indikator Penilaian</h5>
                                                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                                </div>
                                                                <form method="POST" action="{{ route('progress-zi.guidelines.indicators.update', $indicator) }}">
                                                                    @csrf
                                                                    @method('PUT')
                                                                    <div class="modal-body">
                                                                        <div class="form-group">
                                                                            <label>Kode</label>
                                                                            <input type="text" name="code" class="form-control" value="{{ $indicator->code }}">
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Indikator Penilaian</label>
                                                                            <textarea name="indicator_text" rows="3" class="form-control" required>{{ $indicator->indicator_text }}</textarea>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Contoh Eviden</label>
                                                                            <textarea name="evidence_example" rows="2" class="form-control">{{ $indicator->evidence_example }}</textarea>
                                                                        </div>
                                                                        <div class="form-group">
                                                                            <label>Catatan Implementasi</label>
                                                                            <textarea name="implementation_note" rows="2" class="form-control">{{ $indicator->implementation_note }}</textarea>
                                                                        </div>
                                                                        <div class="custom-control custom-switch mb-3">
                                                                            <input class="custom-control-input" type="checkbox" id="indicatorPeriodic{{ $indicator->id }}" name="is_periodic" value="1" {{ $indicator->is_periodic ? 'checked' : '' }}>
                                                                            <label class="custom-control-label" for="indicatorPeriodic{{ $indicator->id }}">Agenda kegiatan harus dilakukan berkala</label>
                                                                        </div>
                                                                        <div class="form-group mb-0">
                                                                            <label>Urutan</label>
                                                                            <input type="number" name="sort_order" class="form-control" value="{{ $indicator->sort_order }}" min="0">
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                                                        <button class="btn btn-primary">Simpan</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @empty
                                                <div class="text-muted small">Belum ada indikator penilaian untuk sub poin ini.</div>
                                            @endforelse
                                        </div>
                                    </div>

                                    @if($canManage)
                                        <div class="modal fade" id="editSubPointModal{{ $subPoint->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Sub Poin</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('progress-zi.guidelines.sub-points.update', $subPoint) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Kode</label>
                                                                <input type="text" name="code" class="form-control" value="{{ $subPoint->code }}" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Judul Sub Poin</label>
                                                                <textarea name="title" rows="3" class="form-control" required>{{ $subPoint->title }}</textarea>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Deskripsi Tambahan</label>
                                                                <textarea name="description" rows="2" class="form-control">{{ $subPoint->description }}</textarea>
                                                            </div>
                                                            <div class="form-group mb-0">
                                                                <label>Urutan</label>
                                                                <input type="number" name="sort_order" class="form-control" value="{{ $subPoint->sort_order }}" min="0">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                                            <button class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="createIndicatorModal{{ $subPoint->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Tambah Indikator Penilaian</h5>
                                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('progress-zi.guidelines.indicators.store', $subPoint) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Kode</label>
                                                                <input type="text" name="code" class="form-control" placeholder="1">
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Indikator Penilaian</label>
                                                                <textarea name="indicator_text" rows="3" class="form-control" required></textarea>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Contoh Eviden</label>
                                                                <textarea name="evidence_example" rows="2" class="form-control"></textarea>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Catatan Implementasi</label>
                                                                <textarea name="implementation_note" rows="2" class="form-control"></textarea>
                                                            </div>
                                                            <div class="custom-control custom-switch mb-3">
                                                                <input class="custom-control-input" type="checkbox" id="createIndicatorPeriodic{{ $subPoint->id }}" name="is_periodic" value="1">
                                                                <label class="custom-control-label" for="createIndicatorPeriodic{{ $subPoint->id }}">Agenda kegiatan harus dilakukan berkala</label>
                                                            </div>
                                                            <div class="form-group mb-0">
                                                                <label>Urutan</label>
                                                                <input type="number" name="sort_order" class="form-control" value="{{ $subPoint->indicators->count() + 1 }}" min="0">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                                            <button class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="zi-guide-empty">Belum ada sub poin untuk poin ini.</div>
                                @endforelse
                            </div>
                        </div>

                        @if($canManage)
                            <div class="modal fade" id="editPointModal{{ $point->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Poin Pedoman</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('progress-zi.guidelines.points.update', $point) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Kode Poin</label>
                                                    <input type="text" name="code" class="form-control" value="{{ $point->code }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Judul Poin</label>
                                                    <input type="text" name="title" class="form-control" value="{{ $point->title }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Deskripsi</label>
                                                    <textarea name="description" rows="3" class="form-control">{{ $point->description }}</textarea>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Urutan</label>
                                                    <input type="number" name="sort_order" class="form-control" value="{{ $point->sort_order }}" min="0">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                                <button class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="createSubPointModal{{ $point->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Tambah Sub Poin</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('progress-zi.guidelines.sub-points.store', $point) }}">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Kode</label>
                                                    <input type="text" name="code" class="form-control" placeholder="a" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Judul Sub Poin</label>
                                                    <textarea name="title" rows="3" class="form-control" required></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Deskripsi Tambahan</label>
                                                    <textarea name="description" rows="2" class="form-control"></textarea>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Urutan</label>
                                                    <input type="number" name="sort_order" class="form-control" value="{{ $point->subPoints->count() + 1 }}" min="0">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                                <button class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="zi-guide-empty">Belum ada poin pedoman untuk area ini.</div>
                    @endforelse
                @else
                    <div class="zi-guide-empty">Pilih salah satu area perubahan di panel kiri untuk melihat pedoman ZI.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canManage && $selectedArea)
    <div class="modal fade" id="createPointModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Poin Pedoman</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('progress-zi.guidelines.points.store', $selectedArea) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode Poin</label>
                            <input type="text" name="code" class="form-control" placeholder="I" required>
                        </div>
                        <div class="form-group">
                            <label>Judul Poin</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label>Urutan</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ $selectedArea->guidelinePoints->count() + 1 }}" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
