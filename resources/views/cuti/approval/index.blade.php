@extends('layouts.app')

@section('title', 'Approval Cuti')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="mb-1">Approval Cuti</h3><p class="text-muted mb-0">Daftar pengajuan cuti yang menunggu tindakan Anda.</p></div></div>
<div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Nomor</th><th>Pegawai</th><th>Jenis</th><th>Periode</th><th>Step</th><th>Aksi</th></tr></thead><tbody>@forelse($approvals as $approval)<tr><td>{{ optional($approval->leaveRequest)->display_number ?: '-' }}</td><td>{{ optional(optional($approval->leaveRequest)->user)->name }}</td><td>{{ optional(optional($approval->leaveRequest)->leaveType)->name }}</td><td>{{ optional(optional($approval->leaveRequest)->start_date)->translatedFormat('d M Y') }} - {{ optional(optional($approval->leaveRequest)->end_date)->translatedFormat('d M Y') }}</td><td>{{ $approval->step_no }} / {{ $approval->role_label }}</td><td class="app-action-cell"><div class="app-action-group"><a href="{{ route('cuti.approval.show', $approval) }}" class="app-icon-btn process"><i class="fas fa-file-signature"></i></a></div></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada approval cuti yang pending.</td></tr>@endforelse</tbody></table></div></div></div>
@endsection
