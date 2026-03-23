@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti')

@section('content')
@include('admin._alerts')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Detail Pengajuan Cuti</h3>
        <p class="text-muted mb-0">{{ $leaveRequest->display_number }}</p>
    </div>
    <div class="app-action-group">
        @if(in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_APPROVED, \App\LeaveRequest::STATUS_REJECTED], true))
            <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="app-icon-btn file"><i class="fas fa-file-alt"></i></a>
        @endif
        <a href="{{ route('cuti.index') }}" class="app-icon-btn cancel"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><strong>Pegawai:</strong> {{ optional($leaveRequest->user)->name }}</div>
            <div class="col-md-6"><strong>Jenis:</strong> {{ optional($leaveRequest->leaveType)->name }}</div>
            <div class="col-md-6 mt-2"><strong>Periode:</strong> {{ $leaveRequest->period_label }}</div>
            <div class="col-md-6 mt-2"><strong>Status:</strong> {!! $leaveRequest->status_badge !!}</div>
            <div class="col-md-6 mt-2"><strong>Nomor Cuti:</strong> {{ $leaveRequest->display_number }}</div>
            <div class="col-md-6 mt-2"><strong>Hari Kerja:</strong> {{ $leaveRequest->requested_days ?: 0 }} hari</div>
            <div class="col-md-12 mt-2"><strong>Tujuan:</strong> {{ $leaveRequest->purpose }}</div>
            <div class="col-md-8 mt-2"><strong>Alamat Selama Cuti:</strong> {{ $leaveRequest->leave_address ?: '-' }}</div>
            <div class="col-md-4 mt-2"><strong>Telepon:</strong> {{ $leaveRequest->contact_phone ?: '-' }}</div>
        </div>
    </div>
</div>
@if(isset($leaveApproval))
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Preview Form Pengajuan Cuti</strong>
            <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="app-icon-btn preview"><i class="fas fa-eye"></i></a>
        </div>
        <div class="card-body p-0">
            <iframe src="{{ route('cuti.surat', $leaveRequest) }}" style="width:100%;height:860px;border:0;"></iframe>
        </div>
    </div>
@endif
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><strong>Dokumen Pendukung</strong></div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Nama File</th><th>Jenis</th><th>Verifikasi</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->documents as $document)
                    <tr>
                        <td>{{ $document->original_name }}</td>
                        <td>{{ $document->document_type_label }}</td>
                        <td>
                            <span class="badge badge-{{ $document->is_verified ? 'success' : 'warning' }}">
                                {{ $document->is_verified ? 'Terverifikasi' : 'Belum Verifikasi' }}
                            </span>
                        </td>
                        <td class="app-action-cell">
                            <div class="app-action-group">
                            <a href="{{ route('cuti.documents.show', [$leaveRequest, $document]) }}" target="_blank" class="app-icon-btn file"><i class="fas fa-paperclip"></i></a>
                            @if(isset($leaveApproval) && in_array($leaveApproval->role_name, ['verifikator_dokumen', 'ppk'], true))
                                <form action="{{ route('cuti.approval.verify-document', $leaveApproval) }}" method="POST" class="d-inline-block ml-2">
                                    @csrf
                                    <input type="hidden" name="document_id" value="{{ $document->id }}">
                                    <input type="hidden" name="is_verified" value="{{ $document->is_verified ? 0 : 1 }}">
                                    <input type="hidden" name="verification_note" value="{{ $document->is_verified ? 'Verifikasi dibatalkan.' : 'Dokumen valid.' }}">
                                    <button type="submit" class="app-icon-btn {{ $document->is_verified ? 'archive' : 'approve' }}">
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
        <table class="table mb-0">
            <thead><tr><th>Step</th><th>Role</th><th>Approver</th><th>Status</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->approvals as $approval)
                    <tr>
                        <td>{{ $approval->step_no }}</td>
                        <td>{{ $approval->role_label }}</td>
                        <td>{{ optional($approval->approver)->name ?: '-' }}</td>
                        <td>{!! $approval->status_badge !!}</td>
                        <td>{{ $approval->note ?: '-' }}</td>
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
        <table class="table mb-0">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Event</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($leaveRequest->audits as $audit)
                    <tr>
                        <td>{{ optional($audit->created_at)->translatedFormat('d F Y H:i') }} WIT</td>
                        <td>{{ optional($audit->actor)->name ?: '-' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</td>
                        <td>{{ $audit->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada audit trail.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="d-flex flex-wrap" style="gap:8px;">
    @if(!isset($leaveApproval) && in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_REJECTED], true))
        <a href="{{ route('cuti.index', ['edit' => $leaveRequest->id]) }}" class="btn btn-outline-secondary btn-sm">Edit Draft</a>
        <form action="{{ route('cuti.submit', $leaveRequest) }}" method="POST" class="d-inline">@csrf <button type="submit" class="btn btn-primary btn-sm">Submit Pengajuan</button></form>
    @endif
    @if(!isset($leaveApproval) && in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_SUBMITTED, \App\LeaveRequest::STATUS_UNDER_REVIEW, \App\LeaveRequest::STATUS_VERIFIED], true))
        <form action="{{ route('cuti.cancel', $leaveRequest) }}" method="POST" class="d-inline">@csrf <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Batalkan pengajuan cuti ini?')">Batalkan</button></form>
    @endif
    @if(isset($leaveApproval))
        <form action="{{ route('cuti.approval.approve', $leaveApproval) }}" method="POST" class="d-inline-block">@csrf <input type="hidden" name="action" value="approve"><textarea name="note" class="form-control mb-2" rows="3" placeholder="Catatan approval"></textarea><button type="submit" class="btn btn-success btn-sm">Approve</button></form>
        <form action="{{ route('cuti.approval.reject', $leaveApproval) }}" method="POST" class="d-inline-block">@csrf <textarea name="note" class="form-control mb-2" rows="3" placeholder="Catatan penolakan" required></textarea><button type="submit" class="btn btn-danger btn-sm">Reject</button></form>
    @endif
</div>
@endsection
