@extends('layouts.app')

@section('title', 'Detail Kegiatan ZI')

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
        <div>
            <h4 class="mb-1">{{ $activity->name }}</h4>
            <div class="text-muted">{{ optional($activity->period)->name }} • {{ optional($activity->area)->name }} • PIC {{ optional($activity->pic)->name ?: '-' }}</div>
            @if($activity->guidelineSubPoint)
                <div class="mt-2">
                    <span class="badge badge-info app-status-badge">{{ $activity->guideline_reference_label }}</span>
                    <span class="small text-muted ml-1">{{ $activity->guidelineSubPoint->title }}</span>
                </div>
            @endif
        </div>
        <div class="d-flex" style="gap:10px;">
            <span>{!! $activity->progress_badge !!}</span>
            <span>{!! $activity->status_badge !!}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        @if($activity->guidelineSubPoint)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white"><strong>Acuan Pedoman ZI</strong></div>
                <div class="card-body">
                    <div><strong>{{ $activity->guideline_reference_label }}</strong></div>
                    <div class="text-muted mt-1">{{ $activity->guidelineSubPoint->title }}</div>
                    @if($activity->guidelineSubPoint->description)
                        <div class="small text-muted mt-2">{{ $activity->guidelineSubPoint->description }}</div>
                    @endif
                    @if(optional($activity->guidelineSubPoint->point)->description)
                        <div class="small mt-3"><strong>Catatan Poin:</strong></div>
                        <div class="small text-muted">{{ $activity->guidelineSubPoint->point->description }}</div>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('progress-zi.guidelines.index', ['area_id' => optional(optional($activity->guidelineSubPoint->point)->area)->id]) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-book-open mr-1"></i>Buka Pedoman
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>Indikator</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Indikator</th>
                                <th>Status</th>
                                <th>Eviden</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity->indicators as $indicator)
                                <tr>
                                    <td>
                                        <strong>{{ $indicator->name }}</strong>
                                        <div class="text-muted small">{{ $indicator->target_fulfillment_text ?: '-' }}</div>
                                        <div class="small text-muted">Bobot {{ rtrim(rtrim(number_format($indicator->weight, 2), '0'), '.') }}</div>
                                        @if($indicator->guidelineIndicator)
                                            <div class="mt-2">
                                                <span class="badge badge-info app-status-badge">{{ $indicator->guideline_reference_label }}</span>
                                                <div class="small text-muted mt-1">{{ $indicator->guidelineIndicator->indicator_text }}</div>
                                            </div>
                                        @endif
                                        @if($indicator->reviews->isNotEmpty())
                                            <div class="mt-2">
                                                @foreach($indicator->reviews->take(3) as $review)
                                                    <div class="small text-muted">{{ optional($review->reviewed_at)->translatedFormat('d M Y H:i') ?: '-' }} • {{ optional($review->reviewer)->name ?: '-' }} • {!! $review->status_badge !!}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {!! $indicator->status_badge !!}
                                        @if($indicator->latestReview)
                                            <div class="small text-muted mt-1">{{ optional($indicator->latestReview->reviewer)->name ?: '-' }}</div>
                                        @endif
                                        <div class="mt-2">
                                            <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#editIndicatorModal{{ $indicator->id }}">
                                                <i class="fas fa-pen mr-1"></i>Edit
                                            </button>
                                        </div>
                                    </td>
                                    <td>{{ $indicator->evidences->count() }} eviden</td>
                                </tr>
                                @if($canVerify)
                                    <tr>
                                        <td colspan="3">
                                            <form method="POST" action="{{ route('progress-zi.indicators.review', $indicator) }}" class="row">
                                                @csrf
                                                <div class="col-md-4 form-group mb-2">
                                                    <select class="form-control form-control-sm" name="status">
                                                        @foreach(['belum_terpenuhi' => 'Belum Terpenuhi','sebagian_terpenuhi' => 'Sebagian','terpenuhi' => 'Terpenuhi','diverifikasi' => 'Diverifikasi','ditolak' => 'Ditolak'] as $key => $label)
                                                            <option value="{{ $key }}" {{ $indicator->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group mb-2">
                                                    <select class="form-control form-control-sm" name="review_decision">
                                                        <option value="approved">Approve</option>
                                                        <option value="revisi">Revisi</option>
                                                        <option value="rejected">Reject</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group mb-2">
                                                    <input class="form-control form-control-sm" name="review_notes" placeholder="Catatan">
                                                </div>
                                                <div class="col-md-2 form-group mb-2">
                                                    <button class="btn btn-sm btn-primary btn-block">Simpan</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                                <div class="modal fade" id="editIndicatorModal{{ $indicator->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Indikator</h5>
                                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <form method="POST" action="{{ route('progress-zi.indicators.update', $indicator) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label>Indikator Pedoman</label>
                                                        <select class="form-control" name="zi_guideline_indicator_id">
                                                            <option value="">-- Belum dihubungkan --</option>
                                                            @foreach($guidelineIndicatorOptions as $guidelineIndicator)
                                                                <option value="{{ $guidelineIndicator->id }}" {{ (int) $indicator->zi_guideline_indicator_id === (int) $guidelineIndicator->id ? 'selected' : '' }}>{{ $guidelineIndicator->code ?: '-' }} - {{ $guidelineIndicator->indicator_text }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Nama Indikator</label>
                                                        <input class="form-control" name="name" value="{{ $indicator->name }}" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Deskripsi</label>
                                                        <textarea class="form-control" name="description" rows="2">{{ $indicator->description }}</textarea>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="col-md-4 form-group">
                                                            <label>Bobot</label>
                                                            <input class="form-control" name="weight" value="{{ $indicator->weight }}">
                                                        </div>
                                                        <div class="col-md-8 form-group">
                                                            <label>Target</label>
                                                            <input class="form-control" name="target_fulfillment_text" value="{{ $indicator->target_fulfillment_text }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-row">
                                                        <div class="col-md-6 form-group">
                                                            <label>Min. Eviden</label>
                                                            <input type="number" class="form-control" name="minimum_evidence_count" value="{{ $indicator->minimum_evidence_count }}" min="0">
                                                        </div>
                                                        <div class="col-md-6 form-group">
                                                            <label>Status</label>
                                                            <select class="form-control" name="status">
                                                                @foreach(['belum_diisi' => 'Belum Diisi','belum_terpenuhi' => 'Belum Terpenuhi','sebagian_terpenuhi' => 'Sebagian Terpenuhi','terpenuhi' => 'Terpenuhi','diverifikasi' => 'Diverifikasi','ditolak' => 'Ditolak'] as $key => $label)
                                                                    <option value="{{ $key }}" {{ $indicator->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="custom-control custom-switch mb-0">
                                                        <input class="custom-control-input" type="checkbox" id="indicatorEvidenceRequired{{ $indicator->id }}" name="is_evidence_required" value="1" {{ $indicator->is_evidence_required ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="indicatorEvidenceRequired{{ $indicator->id }}">Eviden wajib</label>
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
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Belum ada indikator.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Tambah Indikator</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('progress-zi.indicators.store', $activity) }}">
                    @csrf
                    <div class="form-group">
                        <label>Indikator Pedoman</label>
                        <select class="form-control" name="zi_guideline_indicator_id">
                            <option value="">-- Belum dihubungkan --</option>
                            @foreach($guidelineIndicatorOptions as $guidelineIndicator)
                                <option value="{{ $guidelineIndicator->id }}">{{ $guidelineIndicator->code ?: '-' }} - {{ $guidelineIndicator->indicator_text }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Jika kegiatan sudah terhubung ke sub poin pedoman, indikator kegiatan bisa ditautkan ke indikator penilaian acuannya.</small>
                    </div>
                    <div class="form-group">
                        <label>Nama Indikator</label>
                        <input class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <label>Bobot</label>
                            <input class="form-control" name="weight" value="1">
                        </div>
                        <div class="col-md-8 form-group">
                            <label>Target</label>
                            <input class="form-control" name="target_fulfillment_text">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label>Min. Eviden</label>
                            <input type="number" class="form-control" name="minimum_evidence_count" value="1" min="0">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Status Awal</label>
                            <select class="form-control" name="status">
                                <option value="belum_diisi">Belum Diisi</option>
                                <option value="belum_terpenuhi">Belum Terpenuhi</option>
                                <option value="sebagian_terpenuhi">Sebagian Terpenuhi</option>
                                <option value="terpenuhi">Terpenuhi</option>
                            </select>
                        </div>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input class="custom-control-input" type="checkbox" id="indicatorEvidenceRequired" name="is_evidence_required" value="1" checked>
                        <label class="custom-control-label" for="indicatorEvidenceRequired">Eviden wajib</label>
                    </div>
                    <button class="btn btn-primary">Tambah Indikator</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Rekomendasi Eviden Otomatis</strong>
                <span class="small text-muted">Diprioritaskan dari modul yang paling relevan dengan area dan indikator kegiatan.</span>
            </div>
            <div class="card-body">
                @if($evidenceRecommendations->isEmpty())
                    <div class="text-muted">Belum ada rekomendasi eviden yang relevan untuk kegiatan ini.</div>
                @elseif($activity->realizations->isEmpty())
                    <div class="alert alert-warning mb-0">Buat minimal satu realisasi terlebih dahulu agar rekomendasi eviden bisa langsung ditautkan.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Rekomendasi Eviden</th>
                                    <th>Skor</th>
                                    <th style="width:120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($evidenceRecommendations as $recommendation)
                                    <tr>
                                        <td><span class="badge badge-info app-status-badge">{{ $recommendation['type'] }}</span></td>
                                        <td>
                                            <strong>{{ $recommendation['title'] }}</strong>
                                            <div class="small text-muted">{{ $recommendation['meta'] ?: '-' }}</div>
                                        </td>
                                        <td><span class="badge badge-secondary app-status-badge">{{ $recommendation['score'] }} cocok</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('progress-zi.evidences.store', $activity->realizations->first()) }}">
                                                @csrf
                                                <input type="hidden" name="mode" value="linked">
                                                <input type="hidden" name="linked_source" value="{{ $recommendation['linked_source'] }}">
                                                @foreach($activity->indicators as $indicator)
                                                    <input type="hidden" name="indicator_ids[]" value="{{ $indicator->id }}">
                                                @endforeach
                                                <button class="btn btn-sm btn-primary">Tautkan</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white"><strong>Tambah Realisasi</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('progress-zi.realizations.store', $activity) }}">
                    @csrf
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <label>Tanggal</label>
                            <input type="date" class="form-control" name="realization_date" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Sumber Data</label>
                            <select class="form-control" name="source_type">
                                <option value="manual">Manual</option>
                                <option value="persuratan">Persuratan</option>
                                <option value="rapat">Rapat</option>
                                <option value="cuti">Cuti</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Uraian Pelaksanaan</label>
                        <textarea class="form-control" name="implementation_summary" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Hasil Kegiatan</label>
                        <textarea class="form-control" name="result_summary" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6 form-group">
                            <label>Kendala</label>
                            <textarea class="form-control" name="obstacles" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tindak Lanjut</label>
                            <textarea class="form-control" name="follow_up" rows="2"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary">Simpan Realisasi</button>
                </form>
            </div>
        </div>

        <div id="zi-eviden-section"></div>
        @foreach($activity->realizations as $realization)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $realization->realization_date->translatedFormat('d F Y') }}</strong>
                        <div class="small text-muted">Sumber {{ $realization->source_label }} • {{ optional($realization->creator)->name ?: '-' }}</div>
                    </div>
                    <span class="badge badge-info app-status-badge">{{ $realization->evidences->count() }} eviden</span>
                </div>
                <div class="card-body">
                    <div class="mb-2"><strong>Pelaksanaan:</strong><div class="text-muted">{{ $realization->implementation_summary }}</div></div>
                    <div class="mb-2"><strong>Hasil:</strong><div class="text-muted">{{ $realization->result_summary ?: '-' }}</div></div>
                    <div class="mb-2"><strong>Kendala:</strong><div class="text-muted">{{ $realization->obstacles ?: '-' }}</div></div>
                    <div class="mb-3"><strong>Tindak Lanjut:</strong><div class="text-muted">{{ $realization->follow_up ?: '-' }}</div></div>

                    <div class="border rounded p-3 mb-3">
                        <form method="POST" action="{{ route('progress-zi.evidences.store', $realization) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-row">
                                <div class="col-md-3 form-group">
                                    <label>Mode</label>
                                    <select class="form-control progress-evidence-mode" name="mode">
                                        <option value="manual">Upload Manual</option>
                                        <option value="linked">Hubungkan Modul</option>
                                    </select>
                                </div>
                                <div class="col-md-9 form-group">
                                    <label>Judul Eviden</label>
                                    <input class="form-control" name="title">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea class="form-control" name="description" rows="2"></textarea>
                            </div>
                            <div class="form-row progress-manual-row">
                                <div class="col-md-6 form-group">
                                    <label>File Eviden</label>
                                    <input type="file" class="form-control-file" name="file">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Hubungkan ke Indikator</label>
                                    <select class="form-control select2" name="indicator_ids[]" multiple data-placeholder="Pilih indikator">
                                        @foreach($activity->indicators as $indicator)
                                            <option value="{{ $indicator->id }}">{{ $indicator->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row progress-linked-row" style="display:none;">
                                <div class="col-md-8 form-group">
                                    <label>Sumber Data Modul</label>
                                    <select class="form-control select2" name="linked_source" data-placeholder="Pilih eviden terhubung">
                                        <option value=""></option>
                                        @foreach($evidenceSourceOptions as $group => $options)
                                            @if($options->isNotEmpty())
                                                <optgroup label="{{ ucwords(str_replace('_', ' ', $group)) }}">
                                                    @foreach($options as $option)
                                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Hubungkan ke Indikator</label>
                                    <select class="form-control select2" name="indicator_ids[]" multiple data-placeholder="Pilih indikator">
                                        @foreach($activity->indicators as $indicator)
                                            <option value="{{ $indicator->id }}">{{ $indicator->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-success btn-sm">Tambah Eviden</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Eviden</th>
                                    <th>Indikator</th>
                                    <th>Status</th>
                                    <th style="width:120px;">Berkas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($realization->evidences as $evidence)
                                    <tr>
                                        <td>
                                            <strong>{{ $evidence->title }}</strong>
                                            <div class="small text-muted">{{ $evidence->source_reference_label }} • {{ $evidence->description ?: '-' }}</div>
                                            @if($evidence->reviews->isNotEmpty())
                                                <div class="mt-2">
                                                    @foreach($evidence->reviews->take(3) as $review)
                                                        <div class="small text-muted">{{ optional($review->reviewed_at)->translatedFormat('d M Y H:i') ?: '-' }} • {{ optional($review->reviewer)->name ?: '-' }} • {!! $review->status_badge !!}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($evidence->indicators as $indicator)
                                                <div class="small">{{ $indicator->name }}</div>
                                            @empty
                                                <div class="small text-muted">Belum terhubung</div>
                                            @endforelse
                                        </td>
                                        <td>
                                            {!! $evidence->status_badge !!}
                                            @if($evidence->latestReview)
                                                <div class="small text-muted mt-1">{{ optional($evidence->latestReview->reviewer)->name ?: '-' }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($evidence->preview_url)
                                                <a href="{{ $evidence->preview_url }}" target="_blank" class="btn btn-sm btn-light">Buka</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($canVerify)
                                        <tr>
                                            <td colspan="4">
                                                <form method="POST" action="{{ route('progress-zi.evidences.review', $evidence) }}" class="row">
                                                    @csrf
                                                    <div class="col-md-3 form-group mb-2">
                                                        <select class="form-control form-control-sm" name="status">
                                                            <option value="valid">Valid</option>
                                                            <option value="revisi">Revisi</option>
                                                            <option value="tidak_valid">Tidak Valid</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 form-group mb-2">
                                                        <select class="form-control form-control-sm" name="review_decision">
                                                            <option value="approved">Approve</option>
                                                            <option value="revisi">Revisi</option>
                                                            <option value="rejected">Reject</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group mb-2">
                                                        <input class="form-control form-control-sm" name="review_notes" placeholder="Catatan reviewer">
                                                    </div>
                                                    <div class="col-md-2 form-group mb-2">
                                                        <button class="btn btn-sm btn-primary btn-block">Simpan</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">Belum ada eviden.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        @if($activity->realizations->isEmpty())
            <div class="card shadow-sm border-0">
                <div class="card-body text-center text-muted py-4">Belum ada realisasi kegiatan.</div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
        $(document).on('change', '.progress-evidence-mode', function () {
            const container = $(this).closest('form');
            const mode = $(this).val();
            container.find('.progress-manual-row').toggle(mode === 'manual');
            container.find('.progress-linked-row').toggle(mode === 'linked');
        });
    });
</script>
@endpush
