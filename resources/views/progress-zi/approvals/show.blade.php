@extends('layouts.app')

@section('title', 'Review Pimpinan Progress ZI')

@push('styles')
<style>
    .zi-approval-preview-frame {
        width: 100%;
        height: 76vh;
        border: none;
        border-radius: 0 0 16px 16px;
        background: #fff;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-4 mb-3 mb-lg-0">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h3 class="card-title mb-0">Status Review</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">{!! $approval->status_badge !!}</div>
                <div class="small text-muted">Diminta oleh</div>
                <div class="font-weight-bold mb-3">{{ optional($approval->requester)->name ?: '-' }}</div>
                <div class="small text-muted">Diminta pada</div>
                <div class="font-weight-bold mb-3">{{ optional($approval->requested_at)->format('d/m/Y H:i') ?: '-' }} WIT</div>
                @if($approval->request_notes)
                    <div class="small text-muted">Catatan Pengajuan</div>
                    <div class="border rounded p-2 mb-3">{{ $approval->request_notes }}</div>
                @endif

                @if($approval->status === 'pending')
                    <form method="POST" action="{{ route('progress-zi.approvals.approve', $approval) }}" class="mb-3">
                        @csrf
                        <div class="form-group">
                            <label>Catatan Persetujuan</label>
                            <textarea class="form-control" name="review_notes" rows="3"></textarea>
                        </div>
                        <button class="btn btn-success btn-block">Setujui Review</button>
                    </form>
                    <form method="POST" action="{{ route('progress-zi.approvals.reject', $approval) }}">
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
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h3 class="card-title mb-0">Review Pimpinan Progress ZI</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted">Area</div>
                    <div class="font-weight-bold">{{ optional($activity->area)->code }} - {{ optional($activity->area)->name }}</div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Sub Poin Pedoman</div>
                    <div class="font-weight-bold">
                        {{ optional(optional($activity->guidelineSubPoint)->point)->code }}.{{ optional($activity->guidelineSubPoint)->code }}
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
                <div class="card shadow-sm border-0 mb-0">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center" style="gap:12px;">
                            <strong>Preview Dokumen</strong>
                            <a href="{{ route('progress-zi.approvals.bundle', $approval) }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="fas fa-file-pdf mr-1"></i>Bundel PDF
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        @if($bundlePreviewUrl || $previewUrl)
                            <iframe id="ziApprovalPreviewFrame" class="zi-approval-preview-frame" src="{{ $bundlePreviewUrl ?: $previewUrl }}"></iframe>
                        @else
                            <div class="p-4 text-muted">Belum ada dokumen yang bisa dipreview langsung.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
