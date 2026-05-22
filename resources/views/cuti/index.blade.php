@extends('layouts.app')

@section('title', 'Pengajuan Cuti')

@push('styles')
<style>
    .leave-mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .leave-table-card .table {
        min-width: 1080px;
    }

    .leave-flow-list,
    .leave-history-list {
        display: grid;
        gap: 7px;
        min-width: 220px;
    }

    .leave-flow-item,
    .leave-history-item {
        padding: 8px 10px;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        background: #fbfdff;
    }

    .leave-flow-title,
    .leave-history-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 3px;
        font-size: 0.78rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
    }

    .leave-flow-meta,
    .leave-history-meta {
        color: #64748b;
        font-size: 0.74rem;
        line-height: 1.35;
    }

    .leave-flow-status {
        margin-left: auto;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .leave-mobile-header {
            flex-direction: column;
            align-items: stretch;
        }

        .leave-mobile-header .app-create-btn {
            width: 100%;
            justify-content: center;
        }

        .leave-table-card {
            border-radius: 14px;
        }

        .leave-table-card .table-responsive {
            padding: 0;
        }

        .leave-table-card table,
        .leave-table-card thead,
        .leave-table-card tbody,
        .leave-table-card tr,
        .leave-table-card th,
        .leave-table-card td {
            display: block;
            width: 100%;
        }

        .leave-table-card thead {
            display: none;
        }

        .leave-table-card tbody tr {
            border-bottom: 1px solid #e8eaed;
            padding: 14px;
        }

        .leave-table-card tbody tr:last-child {
            border-bottom: 0;
        }

        .leave-table-card tbody td {
            border: 0;
            padding: 6px 0;
        }

        .leave-table-card tbody td::before {
            content: attr(data-label);
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .leave-table-card .app-action-cell {
            text-align: left;
        }

        .leave-table-card .table {
            min-width: 0;
        }

        .leave-flow-list,
        .leave-history-list {
            min-width: 0;
        }
    }
</style>
@endpush

@section('content')
@include('admin._alerts')

<div class="leave-mobile-header">
    <div>
        <h3 class="mb-1">Pengajuan Cuti</h3>
        <p class="text-muted mb-0">Daftar pengajuan cuti pegawai yang Anda buat.</p>
    </div>
    <button type="button" class="btn app-create-btn" data-toggle="modal" data-target="#createLeaveRequestModal">
        <i class="fas fa-plus"></i> Buat Pengajuan
    </button>
</div>

<div class="card border-0 shadow-sm leave-table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Alur Approval</th>
                        <th>Riwayat Singkat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $leaveRequest)
                        @php
                            $canEdit = in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_REJECTED], true) && is_null($leaveRequest->locked_at);
                            $documents = $leaveRequest->documents->map(function ($document) use ($leaveRequest) {
                                return [
                                    'name' => $document->original_name,
                                    'url' => route('cuti.documents.show', [$leaveRequest, $document]),
                                ];
                            })->values();
                        @endphp
                        <tr
                            data-leave-request-row
                            data-id="{{ $leaveRequest->id }}"
                            data-update-url="{{ route('cuti.update', $leaveRequest) }}"
                            data-leave-type-id="{{ $leaveRequest->leave_type_id }}"
                            data-start-date="{{ optional($leaveRequest->start_date)->toDateString() }}"
                            data-end-date="{{ optional($leaveRequest->end_date)->toDateString() }}"
                            data-purpose="{{ e($leaveRequest->purpose) }}"
                            data-leave-address="{{ e($leaveRequest->leave_address) }}"
                            data-documents='@json($documents)'
                        >
                            <td data-label="Nomor">{{ $leaveRequest->display_number }}</td>
                            <td data-label="Jenis">{{ optional($leaveRequest->leaveType)->name }}</td>
                            <td data-label="Periode">{{ optional($leaveRequest->start_date)->translatedFormat('d M Y') }} - {{ optional($leaveRequest->end_date)->translatedFormat('d M Y') }}</td>
                            <td data-label="Status">{!! $leaveRequest->status_badge !!}</td>
                            <td data-label="Alur Approval">
                                <div class="leave-flow-list">
                                    @forelse($leaveRequest->approvals as $approval)
                                        <div class="leave-flow-item">
                                            <div class="leave-flow-title">
                                                <span>Step {{ $approval->step_no }} - {{ $approval->role_label }}</span>
                                                <span class="leave-flow-status">{!! $approval->status_badge !!}</span>
                                            </div>
                                            <div class="leave-flow-meta">{{ optional($approval->approver)->name ?: 'Belum ditentukan' }}</div>
                                        </div>
                                    @empty
                                        <div class="leave-flow-meta">Belum ada alur approval.</div>
                                    @endforelse
                                </div>
                            </td>
                            <td data-label="Riwayat Singkat">
                                <div class="leave-history-list">
                                    @forelse($leaveRequest->audits->take(2) as $audit)
                                        <div class="leave-history-item">
                                            <div class="leave-history-title">{{ ucfirst(str_replace('_', ' ', $audit->event)) }}</div>
                                            <div class="leave-history-meta">
                                                {{ optional($audit->created_at)->translatedFormat('d M Y H:i') }} WIT
                                                <br>
                                                {{ optional($audit->actor)->name ?: '-' }}
                                            </div>
                                        </div>
                                    @empty
                                        <div class="leave-history-meta">Belum ada riwayat.</div>
                                    @endforelse
                                </div>
                            </td>
                            <td data-label="Aksi" class="app-action-cell">
                                <div class="app-action-group">
                                    <a href="{{ route('cuti.show', $leaveRequest) }}" class="app-icon-btn detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($canEdit)
                                        <button
                                            type="button"
                                            class="app-icon-btn edit"
                                            data-leave-edit-trigger
                                            data-target-id="{{ $leaveRequest->id }}"
                                        >
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan cuti.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $leaveRequests->appends(request()->query())->links() }}
</div>

@include('cuti.partials.form-modal', ['mode' => 'create', 'leaveTypes' => $leaveTypes])
@include('cuti.partials.form-modal', ['mode' => 'edit', 'leaveTypes' => $leaveTypes])
@endsection

@push('scripts')
<script>
    (function () {
        var editModal = $('#editLeaveRequestModal');
        var editForm = $('#editLeaveRequestForm');
        var editDocsWrapper = $('#editLeaveRequestExistingDocs');
        var editDocsList = $('#editLeaveRequestExistingDocsList');
        var updateUrlTemplate = @json(url('cuti'));
        var oldMode = @json(old('_leave_form_mode'));
        var oldLeaveId = @json(old('_leave_request_id'));
        var oldValues = {
            leave_type_id: @json(old('leave_type_id')),
            start_date: @json(old('start_date')),
            end_date: @json(old('end_date')),
            purpose: @json(old('purpose')),
            leave_address: @json(old('leave_address')),
        };
        var queryOpen = @json(request('open'));
        var queryEdit = @json(request('edit'));

        function fillEditModal(data) {
            if (!data || !data.id) {
                return;
            }

            editForm.attr('action', data.update_url || (updateUrlTemplate + '/' + data.id));
            editForm.find('input[name="_leave_request_id"]').val(data.id);
            editForm.find('select[name="leave_type_id"]').val(data.leave_type_id || '');
            editForm.find('input[name="start_date"]').val(data.start_date || '');
            editForm.find('input[name="end_date"]').val(data.end_date || '');
            editForm.find('input[name="purpose"]').val(data.purpose || '');
            editForm.find('input[name="leave_address"]').val(data.leave_address || '');
            var docs = Array.isArray(data.documents) ? data.documents : [];
            if (docs.length) {
                editDocsList.html(docs.map(function (doc) {
                    return '<div><a href="' + doc.url + '" target="_blank">' + doc.name + '</a></div>';
                }).join(''));
                editDocsWrapper.removeClass('d-none');
            } else {
                editDocsList.empty();
                editDocsWrapper.addClass('d-none');
            }
        }

        function getRowDataById(id) {
            var row = $('[data-leave-request-row][data-id="' + id + '"]');
            if (!row.length) {
                return null;
            }

            return {
                id: row.data('id'),
                update_url: row.data('update-url'),
                leave_type_id: row.data('leave-type-id'),
                start_date: row.data('start-date'),
                end_date: row.data('end-date'),
                purpose: row.data('purpose'),
                leave_address: row.data('leave-address'),
                documents: row.data('documents')
            };
        }

        $(document).on('click', '[data-leave-edit-trigger]', function () {
            var targetId = $(this).data('target-id');
            var data = getRowDataById(targetId);
            fillEditModal(data);
            editModal.modal('show');
        });

        if (oldMode === 'edit' && oldLeaveId) {
            fillEditModal({
                id: oldLeaveId,
                update_url: updateUrlTemplate + '/' + oldLeaveId,
                leave_type_id: oldValues.leave_type_id,
                start_date: oldValues.start_date,
                end_date: oldValues.end_date,
                purpose: oldValues.purpose,
                leave_address: oldValues.leave_address,
                documents: (getRowDataById(oldLeaveId) || {}).documents || []
            });
            editModal.modal('show');
        } else if (oldMode === 'create' && {{ $errors->any() ? 'true' : 'false' }}) {
            $('#createLeaveRequestModal').modal('show');
        } else if (queryOpen === 'create') {
            $('#createLeaveRequestModal').modal('show');
        } else if (queryEdit) {
            var queryData = getRowDataById(queryEdit);
            if (queryData) {
                fillEditModal(queryData);
                editModal.modal('show');
            }
        }
    })();
</script>
@endpush
