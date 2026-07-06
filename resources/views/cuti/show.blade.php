@extends('layouts.app')

@section('title', isset($leaveApproval) ? 'Approval Cuti' : 'Detail Pengajuan Cuti')

@push('styles')
<style>
    .leave-simple-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 16px;
    }

    .leave-simple-title {
        font-size: 1.22rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 4px;
    }

    .leave-simple-subtitle {
        color: #64748b;
        font-size: 0.86rem;
        margin: 0;
    }

    .leave-simple-card {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        margin-bottom: 14px;
    }

    .leave-simple-card .card-header {
        background: #ffffff;
        border-bottom: 1px solid #eef2f7;
        padding: 14px 18px;
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
    }

    .leave-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .leave-summary-item {
        border: 1px solid #eef2f7;
        border-radius: 14px;
        padding: 12px 14px;
        background: #fbfdff;
    }

    .leave-summary-label {
        display: block;
        margin-bottom: 4px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
    }

    .leave-summary-value {
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .leave-list-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 13px 18px;
        border-bottom: 1px solid #eef2f7;
    }

    .leave-list-row:last-child {
        border-bottom: 0;
    }

    .leave-list-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.9rem;
    }

    .leave-list-meta {
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .leave-approval-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .leave-request-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .leave-request-actions form {
        margin: 0;
    }

    .leave-request-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 38px;
        padding: 0.48rem 0.95rem;
        border-radius: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .leave-action-note {
        margin-top: 10px;
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    @media (max-width: 767.98px) {
        .leave-simple-header {
            flex-direction: column;
            align-items: stretch;
        }

        .leave-summary-grid,
        .leave-approval-actions {
            grid-template-columns: 1fr;
        }

        .leave-request-actions {
            display: grid;
            grid-template-columns: 1fr;
        }

        .leave-request-actions .btn,
        .leave-request-actions form {
            width: 100%;
        }

        .leave-list-row {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>
@endpush

@section('content')
@include('admin._alerts')

@php
    $isApprovalMode = isset($leaveApproval);
    $canOpenPdf = in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_APPROVED, \App\LeaveRequest::STATUS_REJECTED, \App\LeaveRequest::STATUS_CHANGED, \App\LeaveRequest::STATUS_DEFERRED, \App\LeaveRequest::STATUS_COMPLETED], true) || $isApprovalMode;
    $canEditSubmit = in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_REJECTED, \App\LeaveRequest::STATUS_CHANGED, \App\LeaveRequest::STATUS_DEFERRED], true);
    $canCancelRequest = in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_SUBMITTED, \App\LeaveRequest::STATUS_UNDER_REVIEW, \App\LeaveRequest::STATUS_VERIFIED], true);
@endphp

<div class="leave-simple-header">
    <div>
        <h3 class="leave-simple-title">{{ $isApprovalMode ? 'Approval Cuti' : 'Detail Pengajuan Cuti' }}</h3>
        <p class="leave-simple-subtitle">{{ $leaveRequest->display_number }} | {{ optional($leaveRequest->leaveType)->name ?: '-' }}</p>
    </div>
    <div class="app-action-group">
        @if($canOpenPdf)
            <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-file-pdf mr-1"></i> Buka PDF A4
            </a>
        @endif
        <a href="{{ $isApprovalMode ? route('cuti.approval.index') : route('cuti.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>
</div>

@if($isApprovalMode)
    <div class="card leave-simple-card">
        <div class="card-header">Aksi Approval</div>
        <div class="card-body">
            <div class="leave-summary-grid mb-3">
                <div class="leave-summary-item">
                    <span class="leave-summary-label">Tahap</span>
                    <div class="leave-summary-value">Step {{ $leaveApproval->step_no }}</div>
                </div>
                <div class="leave-summary-item">
                    <span class="leave-summary-label">Role</span>
                    <div class="leave-summary-value">{{ $leaveApproval->role_label }}</div>
                </div>
                <div class="leave-summary-item">
                    <span class="leave-summary-label">Status</span>
                    <div class="leave-summary-value">{!! $leaveApproval->status_badge !!}</div>
                </div>
            </div>
            <div class="leave-approval-actions">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#leaveApprovalSignatureModal">
                    <i class="fas fa-check mr-1"></i> Setujui
                </button>
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#leaveApprovalChangeModal">
                    <i class="fas fa-edit mr-1"></i> Perubahan
                </button>
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#leaveApprovalDeferModal">
                    <i class="fas fa-clock mr-1"></i> Ditangguhkan
                </button>
                <form action="{{ route('cuti.approval.reject', $leaveApproval) }}" method="POST">
                    @csrf
                    <textarea name="note" class="form-control mb-2" rows="2" placeholder="Catatan penolakan" required></textarea>
                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Tolak pengajuan cuti ini?')">
                        <i class="fas fa-times mr-1"></i> Tolak
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="card leave-simple-card">
    <div class="card-header">Ringkasan Pengajuan</div>
    <div class="card-body">
        <div class="leave-summary-grid">
            <div class="leave-summary-item">
                <span class="leave-summary-label">Pegawai</span>
                <div class="leave-summary-value">{{ optional($leaveRequest->user)->name ?: '-' }}</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Periode</span>
                <div class="leave-summary-value">{{ $leaveRequest->period_label }}</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Hari Kerja</span>
                <div class="leave-summary-value">{{ $leaveRequest->requested_days ?: 0 }} hari</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Status</span>
                <div class="leave-summary-value">{!! $leaveRequest->status_badge !!}</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Telepon</span>
                <div class="leave-summary-value">{{ $leaveRequest->contact_phone ?: optional($leaveRequest->user)->no_hp ?: '-' }}</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Cuti Luar Negeri</span>
                <div class="leave-summary-value">{{ $leaveRequest->is_abroad ? ($leaveRequest->abroad_country ?: 'Ya') : 'Tidak' }}</div>
            </div>
            <div class="leave-summary-item">
                <span class="leave-summary-label">Nomor</span>
                <div class="leave-summary-value">{{ $leaveRequest->display_number }}</div>
            </div>
            @if($leaveRequest->revision_note)
                <div class="leave-summary-item" style="grid-column: 1 / -1;">
                    <span class="leave-summary-label">Catatan Keputusan</span>
                    <div class="leave-summary-value">{{ $leaveRequest->revision_note }}</div>
                </div>
            @endif
            @if($leaveRequest->deferred_reason)
                <div class="leave-summary-item" style="grid-column: 1 / -1;">
                    <span class="leave-summary-label">Alasan Penangguhan</span>
                    <div class="leave-summary-value">{{ $leaveRequest->deferred_reason }}</div>
                </div>
            @endif
            <div class="leave-summary-item" style="grid-column: 1 / -1;">
                <span class="leave-summary-label">Alasan</span>
                <div class="leave-summary-value">{{ $leaveRequest->purpose ?: '-' }}</div>
            </div>
            <div class="leave-summary-item" style="grid-column: 1 / -1;">
                <span class="leave-summary-label">Alamat Selama Cuti</span>
                <div class="leave-summary-value">{{ $leaveRequest->leave_address ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card leave-simple-card">
    <div class="card-header">Dokumen Pendukung</div>
    <div class="card-body p-0">
        @forelse($leaveRequest->documents as $document)
            <div class="leave-list-row">
                <div>
                    <div class="leave-list-title">{{ $document->original_name }}</div>
                    <div class="leave-list-meta">{{ $document->document_type_label }} | {{ $document->is_verified ? 'Terverifikasi' : 'Belum diverifikasi' }}</div>
                </div>
                <div class="app-action-group">
                    <a href="{{ route('cuti.documents.show', [$leaveRequest, $document]) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-paperclip mr-1"></i> Buka
                    </a>
                    @if($isApprovalMode && in_array($leaveApproval->role_name, ['verifikator_dokumen', 'ppk'], true))
                        <form action="{{ route('cuti.approval.verify-document', $leaveApproval) }}" method="POST" class="d-inline-block">
                            @csrf
                            <input type="hidden" name="document_id" value="{{ $document->id }}">
                            <input type="hidden" name="is_verified" value="{{ $document->is_verified ? 0 : 1 }}">
                            <input type="hidden" name="verification_note" value="{{ $document->is_verified ? 'Verifikasi dibatalkan.' : 'Dokumen valid.' }}">
                            <button type="submit" class="btn btn-{{ $document->is_verified ? 'outline-warning' : 'outline-success' }} btn-sm">
                                <i class="fas {{ $document->is_verified ? 'fa-undo' : 'fa-check' }} mr-1"></i>{{ $document->is_verified ? 'Batalkan' : 'Verifikasi' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="leave-list-row"><div class="leave-list-meta">Belum ada dokumen pendukung.</div></div>
        @endforelse
    </div>
</div>

@if(!$isApprovalMode && ($canEditSubmit || $canCancelRequest))
    <div class="card leave-simple-card">
        <div class="card-header">Aksi Pengajuan</div>
        <div class="card-body">
            <div class="leave-request-actions">
                @if($canEditSubmit)
                    <a href="{{ route('cuti.index', ['edit' => $leaveRequest->id]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-pen"></i> Edit Draft
                    </a>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#leaveApplicantSignatureModal">
                        <i class="fas fa-signature"></i> Submit Pengajuan
                    </button>
                @endif
                @if($canCancelRequest)
                    <form action="{{ route('cuti.cancel', $leaveRequest) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Batalkan pengajuan cuti ini?')">
                            <i class="fas fa-ban"></i> Batalkan
                        </button>
                    </form>
                @endif
            </div>
            @if($canEditSubmit)
                <div class="leave-action-note">
                    Submit pengajuan akan memakai tanda tangan yang tersimpan pada Profil Saya sebelum pengajuan masuk ke alur approval.
                </div>
            @endif
        </div>
    </div>
@endif

@if($isApprovalMode)
    <div class="modal fade" id="leaveApprovalChangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Keputusan Perubahan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cuti.approval.change', $leaveApproval) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Catatan Perubahan</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Tuliskan perubahan yang harus dilakukan" required></textarea>
                        </div>
                        @include('partials.profile-signature-notice')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leaveApprovalDeferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Keputusan Ditangguhkan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cuti.approval.defer', $leaveApproval) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Alasan Penangguhan</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Tuliskan alasan penangguhan" required></textarea>
                        </div>
                        @include('partials.profile-signature-notice')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Simpan Penangguhan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($isApprovalMode)
    <div class="modal fade" id="leaveApprovalSignatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Setujui Cuti</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cuti.approval.approve', $leaveApproval) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <div class="form-group">
                            <label>Catatan Approval</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Catatan approval"></textarea>
                        </div>
                        @include('partials.profile-signature-notice')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan & Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if(!$isApprovalMode && $canEditSubmit)
    <div class="modal fade" id="leaveApplicantSignatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Pengajuan Cuti</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cuti.submit', $leaveRequest) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @include('partials.profile-signature-notice')
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan & Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
