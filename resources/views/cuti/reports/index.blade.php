@extends('layouts.app')

@section('title', 'Laporan Pengajuan Cuti')

@section('content')
@include('admin._alerts')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-1">Laporan Pengajuan Cuti</h3><p class="text-muted mb-0">Rekap pengajuan, approval, dan status dokumen final cuti.</p></div>
    <div class="app-action-group">
        <a href="{{ route('cuti.reports.pdf', request()->query()) }}" class="app-icon-btn pdf"><i class="fas fa-file-pdf"></i></a>
        <a href="{{ route('cuti.reports.excel', request()->query()) }}" class="app-icon-btn download"><i class="fas fa-file-excel"></i></a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-3"><div class="card-body"><form method="GET" action="{{ route('cuti.reports.index') }}"><div class="row"><div class="col-md-2 form-group mb-md-0"><select name="status" class="form-control"><option value="">Semua Status</option>@foreach(['draft'=>'Draft','submitted'=>'Diajukan','under_review'=>'Ditinjau','verified'=>'Terverifikasi','approved'=>'Disetujui','rejected'=>'Ditolak','cancelled'=>'Dibatalkan','completed'=>'Selesai'] as $value => $label)<option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div><div class="col-md-2 form-group mb-md-0"><select name="leave_type_id" class="form-control"><option value="">Semua Jenis</option>@foreach($leaveTypes as $leaveType)<option value="{{ $leaveType->id }}" {{ (string) ($filters['leave_type_id'] ?? '') === (string) $leaveType->id ? 'selected' : '' }}>{{ $leaveType->name }}</option>@endforeach</select></div><div class="col-md-2 form-group mb-md-0"><select name="unit_id" class="form-control"><option value="">Semua Unit</option>@foreach($units as $unit)<option value="{{ $unit->id }}" {{ (string) ($filters['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>{{ $unit->nama }}</option>@endforeach</select></div><div class="col-md-2 form-group mb-md-0"><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div><div class="col-md-2 form-group mb-md-0"><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div><div class="col-md-2 form-group mb-md-0"><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Nomor / nama"></div></div><div class="row mt-2"><div class="col-md-12 d-flex justify-content-end" style="gap:6px;"><button type="submit" class="btn btn-primary btn-sm">Filter</button><a href="{{ route('cuti.reports.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a></div></div></form></div></div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Nomor</th><th>Pegawai</th><th>Jenis</th><th>Periode</th><th>Hari</th><th>Status</th><th>Approval</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $leaveRequest)
                        <tr>
                            <td>{{ $leaveRequest->display_number }}</td>
                            <td>{{ optional($leaveRequest->user)->name }}<br><small class="text-muted">{{ optional(optional($leaveRequest->user)->unit)->nama ?: '-' }}</small></td>
                            <td>{{ optional($leaveRequest->leaveType)->name }}</td>
                            <td>{{ $leaveRequest->period_label }}</td>
                            <td>
                                {{ $leaveRequest->requested_days }} hari
                                @if($leaveRequest->travel_leave_requested)
                                    <br><small class="text-info">Termasuk 1 hari cuti perjalanan</small>
                                @endif
                            </td>
                            <td>{!! $leaveRequest->status_badge !!}</td>
                            <td><small class="text-muted">{{ $leaveRequest->approvals->where('status','approved')->count() }}/{{ $leaveRequest->approvals->count() }} selesai</small></td>
                            <td class="app-action-cell">
                                <div class="app-action-group">
                                    @if(in_array($leaveRequest->status, ['approved','rejected']))
                                        <a href="{{ route('cuti.surat', $leaveRequest) }}" target="_blank" class="app-icon-btn file" title="Buka PDF cuti"><i class="fas fa-file-alt"></i></a>
                                    @endif
                                    @if($canEditLeaveDates)
                                        <button type="button" class="app-icon-btn edit js-edit-reported-dates"
                                            title="Edit tanggal pengambilan cuti"
                                            data-toggle="modal" data-target="#reportedDatesModal"
                                            data-update-url="{{ route('cuti.reports.dates.update', $leaveRequest) }}"
                                            data-leave-request-id="{{ $leaveRequest->id }}"
                                            data-user-name="{{ optional($leaveRequest->user)->name }}"
                                            data-leave-type-name="{{ optional($leaveRequest->leaveType)->name }}"
                                            data-start-date="{{ optional($leaveRequest->start_date)->toDateString() }}"
                                            data-end-date="{{ optional($leaveRequest->end_date)->toDateString() }}">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                    @endif
                                    @if(!in_array($leaveRequest->status, ['approved','rejected']) && !$canEditLeaveDates)
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pengajuan cuti.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">{{ $leaveRequests->links() }}</div>
</div>

@if($canEditLeaveDates)
<div class="modal fade" id="reportedDatesModal" tabindex="-1" aria-labelledby="reportedDatesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportedDatesModalLabel">Edit Tanggal Pengambilan Cuti</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST" id="reportedDatesForm" action="{{ old('edit_leave_request_id') ? route('cuti.reports.dates.update', old('edit_leave_request_id')) : '#' }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="edit_leave_request_id" id="reportedDatesLeaveRequestId" value="{{ old('edit_leave_request_id') }}">
                <input type="hidden" name="edit_employee_name" id="reportedDatesEmployeeNameInput" value="{{ old('edit_employee_name') }}">
                <input type="hidden" name="edit_leave_type_name" id="reportedDatesLeaveTypeNameInput" value="{{ old('edit_leave_type_name') }}">
                <div class="modal-body">
                    <div class="alert alert-light border">
                        <strong id="reportedDatesEmployeeName">-</strong><br>
                        <span id="reportedDatesLeaveTypeName">-</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="reportedStartDate">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="reportedStartDate" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="reportedEndDate">Tanggal Selesai</label>
                            <input type="date" name="end_date" id="reportedEndDate" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="reportedDatesNote">Alasan Perubahan</label>
                        <textarea name="change_note" id="reportedDatesNote" class="form-control @error('change_note') is-invalid @enderror" rows="3" maxlength="500" required>{{ old('change_note') }}</textarea>
                        @error('change_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Jumlah hari efektif dan saldo cuti akan dihitung ulang otomatis.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Simpan Tanggal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if($canEditLeaveDates)
@push('scripts')
<script>
    $(function () {
        $('.js-edit-reported-dates').on('click', function () {
            var $button = $(this);
            var employeeName = $button.data('user-name') || '-';
            var leaveTypeName = $button.data('leave-type-name') || '-';
            $('#reportedDatesForm').attr('action', $button.data('update-url'));
            $('#reportedDatesLeaveRequestId').val($button.data('leave-request-id'));
            $('#reportedDatesEmployeeNameInput').val(employeeName);
            $('#reportedDatesLeaveTypeNameInput').val(leaveTypeName);
            $('#reportedDatesEmployeeName').text(employeeName);
            $('#reportedDatesLeaveTypeName').text(leaveTypeName);
            $('#reportedStartDate').val($button.data('start-date'));
            $('#reportedEndDate').val($button.data('end-date'));
            $('#reportedDatesNote').val('');
        });

        @if(old('edit_leave_request_id') && ($errors->has('start_date') || $errors->has('end_date') || $errors->has('change_note')))
            $('#reportedDatesEmployeeName').text(@json(old('edit_employee_name', 'Pegawai')));
            $('#reportedDatesLeaveTypeName').text(@json(old('edit_leave_type_name', 'Jenis cuti')));
            $('#reportedDatesModal').modal('show');
        @endif
    });
</script>
@endpush
@endif
