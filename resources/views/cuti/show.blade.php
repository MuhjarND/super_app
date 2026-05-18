@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti')

@push('styles')
<style>
    .leave-show-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 18px;
    }

    .leave-show-item strong {
        display: block;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .leave-show-preview-frame {
        width: 100%;
        height: 860px;
        border: 0;
        background: #fff;
    }

    .leave-show-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 800;
        border-bottom-color: #e2e8f0;
    }

    .leave-show-table th,
    .leave-show-table td {
        padding: 12px 14px;
        vertical-align: top;
    }

    .leave-show-action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    @media (max-width: 767.98px) {
        .leave-show-top {
            flex-direction: column;
            align-items: stretch !important;
        }

        .leave-show-top .app-action-group {
            width: 100%;
        }

        .leave-show-top .app-action-group > * {
            flex: 1 1 0;
        }

        .leave-show-grid {
            grid-template-columns: 1fr;
        }

        .leave-show-preview-frame {
            height: 68vh;
        }

        .leave-show-table,
        .leave-show-table thead,
        .leave-show-table tbody,
        .leave-show-table tr,
        .leave-show-table th,
        .leave-show-table td {
            display: block;
            width: 100%;
        }

        .leave-show-table thead {
            display: none;
        }

        .leave-show-table tbody tr {
            padding: 12px 14px 10px;
            border-bottom: 1px solid #e8eaed;
        }

        .leave-show-table tbody tr:last-child {
            border-bottom: 0;
        }

        .leave-show-table td {
            padding: 0 0 10px;
            border: 0;
        }

        .leave-show-table td:last-child {
            padding-bottom: 0;
        }

        .leave-show-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .leave-show-action-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@include('admin._alerts')
<div class="d-flex justify-content-between align-items-center mb-3 leave-show-top" style="gap:12px;">
    <div>
        <h3 class="mb-1">Detail Pengajuan Cuti</h3>
        <p class="text-muted mb-0">{{ $leaveRequest->display_number }}</p>
    </div>
    <div class="app-action-group">
        @if(in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_APPROVED, \App\LeaveRequest::STATUS_REJECTED], true))
            <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="app-icon-btn file" data-mobile-label="Buka PDF"><i class="fas fa-file-alt"></i></a>
        @endif
        <a href="{{ route('cuti.index') }}" class="app-icon-btn cancel" data-mobile-label="Kembali"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="leave-show-grid">
            <div class="leave-show-item"><strong>Pegawai</strong>{{ optional($leaveRequest->user)->name }}</div>
            <div class="leave-show-item"><strong>Jenis</strong>{{ optional($leaveRequest->leaveType)->name }}</div>
            <div class="leave-show-item"><strong>Periode</strong>{{ $leaveRequest->period_label }}</div>
            <div class="leave-show-item"><strong>Status</strong>{!! $leaveRequest->status_badge !!}</div>
            <div class="leave-show-item"><strong>Nomor Cuti</strong>{{ $leaveRequest->display_number }}</div>
            <div class="leave-show-item"><strong>Hari Kerja</strong>{{ $leaveRequest->requested_days ?: 0 }} hari</div>
            <div class="leave-show-item" style="grid-column: 1 / -1;"><strong>Tujuan</strong>{{ $leaveRequest->purpose }}</div>
            <div class="leave-show-item"><strong>Alamat Selama Cuti</strong>{{ $leaveRequest->leave_address ?: '-' }}</div>
            <div class="leave-show-item"><strong>Telepon</strong>{{ $leaveRequest->contact_phone ?: '-' }}</div>
        </div>
    </div>
</div>
@if(isset($leaveApproval))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Preview Form Pengajuan Cuti</strong>
            <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-external-link-alt mr-1"></i> Buka PDF</a>
        </div>
        <div class="card-body p-0">
            <iframe src="{{ route('cuti.surat', $leaveRequest) }}" class="leave-show-preview-frame"></iframe>
        </div>
    </div>
@endif
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><strong>Dokumen Pendukung</strong></div>
    <div class="card-body p-0">
        <table class="table mb-0 leave-show-table">
            <thead><tr><th>Nama File</th><th>Jenis</th><th>Verifikasi</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->documents as $document)
                    <tr>
                        <td data-label="Nama File">{{ $document->original_name }}</td>
                        <td data-label="Jenis">{{ $document->document_type_label }}</td>
                        <td data-label="Verifikasi">
                            <span class="badge badge-{{ $document->is_verified ? 'success' : 'warning' }}">
                                {{ $document->is_verified ? 'Terverifikasi' : 'Belum Verifikasi' }}
                            </span>
                        </td>
                        <td class="app-action-cell" data-label="Aksi">
                            <div class="app-action-group">
                            <a href="{{ route('cuti.documents.show', [$leaveRequest, $document]) }}" target="_blank" class="app-icon-btn file" data-mobile-label="Buka"><i class="fas fa-paperclip"></i></a>
                            @if(isset($leaveApproval) && in_array($leaveApproval->role_name, ['verifikator_dokumen', 'ppk'], true))
                                <form action="{{ route('cuti.approval.verify-document', $leaveApproval) }}" method="POST" class="d-inline-block ml-2">
                                    @csrf
                                    <input type="hidden" name="document_id" value="{{ $document->id }}">
                                    <input type="hidden" name="is_verified" value="{{ $document->is_verified ? 0 : 1 }}">
                                    <input type="hidden" name="verification_note" value="{{ $document->is_verified ? 'Verifikasi dibatalkan.' : 'Dokumen valid.' }}">
                                    <button type="submit" class="app-icon-btn {{ $document->is_verified ? 'archive' : 'approve' }}" data-mobile-label="{{ $document->is_verified ? 'Batal' : 'Verifikasi' }}">
                                        <i class="fas {{ $document->is_verified ? 'fa-undo' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada dokumen pendukung.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><strong>Approval</strong></div>
    <div class="card-body p-0">
        <table class="table mb-0 leave-show-table">
            <thead><tr><th>Step</th><th>Role</th><th>Approver</th><th>Status</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->approvals as $approval)
                    <tr>
                        <td data-label="Step">{{ $approval->step_no }}</td>
                        <td data-label="Role">{{ $approval->role_label }}</td>
                        <td data-label="Approver">{{ optional($approval->approver)->name ?: '-' }}</td>
                        <td data-label="Status">{!! $approval->status_badge !!}</td>
                        <td data-label="Catatan">{{ $approval->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada approval.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><strong>Audit Trail</strong></div>
    <div class="card-body p-0">
        <table class="table mb-0 leave-show-table">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Event</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->audits as $audit)
                    <tr>
                        <td data-label="Waktu">{{ optional($audit->created_at)->translatedFormat('d F Y H:i') }} WIT</td>
                        <td data-label="Aktor">{{ optional($audit->actor)->name ?: '-' }}</td>
                        <td data-label="Event">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</td>
                        <td data-label="Catatan">{{ $audit->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada audit trail.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="leave-show-action-grid">
    @if(!isset($leaveApproval) && in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_REJECTED], true))
        <a href="{{ route('cuti.index', ['edit' => $leaveRequest->id]) }}" class="btn btn-outline-secondary btn-sm">Edit Draft</a>
        <form action="{{ route('cuti.submit', $leaveRequest) }}" method="POST" class="d-inline">@csrf <button type="submit" class="btn btn-primary btn-sm">Submit Pengajuan</button></form>
    @endif
    @if(!isset($leaveApproval) && in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_SUBMITTED, \App\LeaveRequest::STATUS_UNDER_REVIEW, \App\LeaveRequest::STATUS_VERIFIED], true))
        <form action="{{ route('cuti.cancel', $leaveRequest) }}" method="POST" class="d-inline">@csrf <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Batalkan pengajuan cuti ini?')">Batalkan</button></form>
    @endif
    @if(isset($leaveApproval))
        <div class="d-inline-block card border-0 shadow-sm mb-0"><div class="card-body"><button type="button" class="btn btn-success btn-sm btn-block" data-toggle="modal" data-target="#leaveApprovalSignatureModal">Approve</button></div></div>
        <form action="{{ route('cuti.approval.reject', $leaveApproval) }}" method="POST" class="d-inline-block card border-0 shadow-sm mb-0"><div class="card-body">@csrf <textarea name="note" class="form-control mb-2" rows="3" placeholder="Catatan penolakan" required></textarea><button type="submit" class="btn btn-danger btn-sm btn-block">Reject</button></div></form>
    @endif
</div>

@if(isset($leaveApproval))
    <div class="modal fade" id="leaveApprovalSignatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Cuti</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cuti.approval.approve', $leaveApproval) }}" method="POST" class="requires-signature-pad">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <div class="form-group">
                            <label>Catatan Approval</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Catatan approval"></textarea>
                        </div>
                        @include('partials.signature-pad', ['id' => 'leaveApprovalSignaturePad', 'name' => 'signature_data', 'label' => 'Bubuhkan Tanda Tangan', 'required' => true])
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
@endsection
