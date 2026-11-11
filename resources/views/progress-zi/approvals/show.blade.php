@extends('progress-zi.layout')

@section('title', 'Review Eviden Progress ZI')

@push('styles')
<style>
    .zi-approval-preview-frame {
        width: 100%;
        height: 76vh;
        border: none;
        border-radius: 0 0 16px 16px;
        background: #fff;
    }

    @media (max-width: 767.98px) {
        .zi-approval-preview-frame {
            height: 66vh;
        }

        .zi-approval-actions .btn {
            width: 100%;
        }

        .zi-approval-card .card-body,
        .zi-approval-card .card-header {
            padding-left: 14px;
            padding-right: 14px;
        }

        .zi-approval-preview-head {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .zi-approval-preview-head .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('zi-content')
<div class="row">
    <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="card shadow-sm border-0 zi-approval-card">
            <div class="card-header bg-white">
                <h3 class="card-title mb-0">Status Review</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Review cukup diproses salah satu dari Sekretaris atau Panitera.</p>
                <div class="mb-3">{!! $approval->status_badge !!}</div>
                @if($approval->status !== 'pending')
                    <div class="small text-muted">Direview oleh</div>
                    <div class="font-weight-bold mb-3">{{ optional($approval->approver)->name ?: '-' }}</div>
                @endif
                <div class="small text-muted">Diminta oleh</div>
                <div class="font-weight-bold mb-3">{{ optional($approval->requester)->name ?: '-' }}</div>
                <div class="small text-muted">Diminta pada</div>
                <div class="font-weight-bold mb-3">{{ optional($approval->requested_at)->format('d/m/Y H:i') ?: '-' }} WIT</div>
                @if($approval->request_notes)
                    <div class="small text-muted">Catatan Pengajuan</div>
                    <div class="border rounded p-2 mb-3">{{ $approval->request_notes }}</div>
                @endif

                @if($approval->status === 'pending' && auth()->user()->canVerifyProgressZi())
                    <form method="POST" action="{{ route('progress-zi.approvals.approve', $approval) }}" class="mb-3 zi-approval-actions">
                        @csrf
                        <div class="form-group">
                            <label>Catatan Persetujuan</label>
                            <textarea class="form-control" name="review_notes" rows="3"></textarea>
                        </div>
                        <button class="btn btn-success btn-block">Setujui Review</button>
                    </form>
                    <form method="POST" action="{{ route('progress-zi.approvals.reject', $approval) }}" class="zi-approval-actions">
                        @csrf
                        <div class="form-group">
                            <label>Catatan Perbaikan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="review_notes" rows="3" required></textarea>
                        </div>
                        <button class="btn btn-danger btn-block">Kembalikan untuk Perbaikan</button>
                    </form>
                @elseif($approval->review_notes)
                    <div class="small text-muted">Catatan Review</div>
                    <div class="border rounded p-2">{{ $approval->review_notes }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 zi-approval-card">
            <div class="card-header bg-white">
                <h3 class="card-title mb-0">Review Eviden Progress ZI</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted">Area</div>
                    <div class="font-weight-bold">{{ optional($activity->area)->code }} - {{ optional($activity->area)->name }}</div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Sub Poin Pedoman</div>
                    <div class="font-weight-bold">
                        {{ optional(optional($activity->guidelineSubPoint)->point)->code }}.{{ optional($activity->guidelineSubPoint)->display_code }}
                        {{ optional($activity->guidelineSubPoint)->title }}
                    </div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Kegiatan</div>
                    <div class="font-weight-bold">{{ $activity->name }}</div>
                    @if($activity->description)
                        <div class="text-muted mt-1">{{ $activity->description }}</div>
                    @endif
                </div>
                <div class="mb-3">
                    <strong>Daftar Eviden</strong>
                    @if($activity->guidelineSubPoint && $activity->guidelineSubPoint->evidence_guide)
                        <details class="border rounded p-3 mt-2 mb-3">
                            <summary class="font-weight-bold">Panduan Penyusunan Eviden</summary>
                            <div class="mt-3">@include('progress-zi.partials.evidence-guide', ['guide' => $activity->guidelineSubPoint->evidence_guide])</div>
                        </details>
                    @endif
                    <p class="small text-muted mt-1">Buka tautan Google Drive untuk memeriksa isi eviden. Akses mengikuti pengaturan berbagi pemilik dokumen.</p>
                    @forelse($evidences as $evidence)
                        <div class="border rounded p-3 mb-2">
                            <div class="font-weight-bold">{{ $evidence->title }}</div>
                            <div class="small text-muted">{{ $evidence->source_reference_label }} · {{ $evidence->status_label }}</div>
                            @if($evidence->description)
                                <div class="small mt-2"><strong>Catatan Eviden:</strong><div style="white-space:pre-wrap; overflow-wrap:anywhere;">{{ $evidence->description }}</div></div>
                            @endif
                            @if($evidence->preview_url)
                                <a href="{{ $evidence->preview_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary mt-2">{{ $evidence->drive_url ? 'Buka Google Drive' : 'Buka Eviden Lama' }} <i class="fas fa-external-link-alt ml-1"></i></a>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">Belum ada eviden.</div>
                    @endforelse
                </div>
                @php
                    $hasLegacyEvidences = $evidences->contains(function ($evidence) { return !$evidence->drive_url; });
                @endphp
                <div class="card shadow-sm border-0 mb-0 zi-approval-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center zi-approval-preview-head" style="gap:12px;">
                            <strong>{{ $hasLegacyEvidences ? 'Ringkasan dan Eviden Lama' : 'Ringkasan Eviden' }}</strong>
                            <a href="{{ route('progress-zi.approvals.bundle', $approval) }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt mr-1"></i>Buka PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($hasLegacyEvidences && ($bundlePreviewUrl || $previewUrl))
                            <iframe id="ziApprovalPreviewFrame" class="zi-approval-preview-frame" src="{{ $bundlePreviewUrl ?: $previewUrl }}"></iframe>
                        @else
                            <div class="p-3 text-muted">Ringkasan PDF memuat daftar tautan eviden. Gunakan tombol Buka Google Drive di atas untuk memeriksa isi file atau folder.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
