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

    .leave-balance-section {
        margin-bottom: 18px;
    }

    .leave-balance-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .leave-balance-header h5 {
        margin: 0;
        font-weight: 800;
        color: #0f172a;
    }

    .leave-balance-year {
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .leave-balance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 10px;
    }

    .leave-balance-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        padding: 13px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
    }

    .leave-balance-card.is-annual {
        border-color: rgba(99, 102, 241, 0.25);
        background: #fbfdff;
    }

    .leave-balance-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 800;
    }

    .leave-balance-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        border-radius: 999px;
        background: var(--primary-50, #eef2ff);
        color: var(--primary, #4f46e5);
        padding: 4px 9px;
        font-size: 0.78rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .leave-annual-summary {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        padding: 11px 12px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid rgba(99, 102, 241, 0.16);
    }

    .leave-annual-summary span {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .leave-annual-summary strong {
        display: block;
        color: var(--primary, #4f46e5);
        font-size: 1.55rem;
        font-weight: 900;
        line-height: 1;
    }

    .leave-annual-breakdown {
        display: grid;
        gap: 7px;
    }

    .leave-annual-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        color: #334155;
        font-size: 0.82rem;
    }

    .leave-annual-row strong {
        color: #0f172a;
        font-size: 0.88rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .leave-balance-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .leave-balance-stat {
        min-width: 0;
    }

    .leave-balance-label {
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .leave-balance-value {
        color: #0f172a;
        font-size: 0.96rem;
        font-weight: 900;
        line-height: 1.25;
    }

    .leave-balance-empty {
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        color: #64748b;
        padding: 14px;
        text-align: center;
        background: #fff;
    }

    .leave-approval-modal-list {
        display: grid;
        gap: 7px;
        min-width: 220px;
    }

    .leave-approval-modal-item {
        padding: 8px 10px;
        border: 1px solid #edf2f7;
        border-radius: 12px;
        background: #fbfdff;
    }

    .leave-approval-modal-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 3px;
        font-size: 0.78rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
    }

    .leave-approval-modal-meta,
    .leave-table-note {
        color: #64748b;
        font-size: 0.74rem;
        line-height: 1.35;
    }

    .leave-approval-modal-status {
        margin-left: auto;
        white-space: nowrap;
    }

    .leave-approval-trigger {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border-radius: 999px;
        font-weight: 800;
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

        .leave-balance-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .leave-approval-modal-list {
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

<section class="leave-balance-section">
    <div class="leave-balance-header">
        <div>
            <h5>Rekapan Saldo Cuti</h5>
            <div class="leave-balance-year">Tahun {{ $balanceYear ?? now()->year }}</div>
        </div>
    </div>
    <div class="leave-balance-grid">
        @forelse($leaveBalanceSummaries ?? [] as $summary)
            @php
                $isAnnualLeave = optional($summary['leave_type'])->code === \App\LeaveType::CODE_TAHUNAN;
                $summaryYear = (int) ($summary['year'] ?? ($balanceYear ?? now()->year));
            @endphp
            <div class="leave-balance-card {{ $isAnnualLeave ? 'is-annual' : '' }}">
                <div class="leave-balance-title">
                    <span>{{ $summary['leave_type']->name }}</span>
                    <span class="leave-balance-pill">{{ $summary['remaining_balance'] }} hari</span>
                </div>
                @if($isAnnualLeave)
                    <div class="leave-annual-summary">
                        <div>
                            <span>Sisa Cuti Tahunan</span>
                            <strong>{{ $summary['remaining_balance'] }}</strong>
                        </div>
                        <span>hari tersedia</span>
                    </div>
                    <div class="leave-annual-breakdown">
                        <div class="leave-annual-row">
                            <span>{{ $summaryYear }}</span>
                            <strong>{{ $summary['entitlement'] }} hari</strong>
                        </div>
                        <div class="leave-annual-row">
                            <span>{{ $summaryYear - 1 }}</span>
                            <strong>{{ $summary['carry_forward_previous_year'] ?? 0 }} hari</strong>
                        </div>
                        <div class="leave-annual-row">
                            <span>{{ $summaryYear - 2 }}</span>
                            <strong>{{ $summary['carry_forward_two_years_ago'] ?? 0 }} hari</strong>
                        </div>
                        <div class="leave-annual-row">
                            <span>Total hak</span>
                            <strong>{{ $summary['total_balance'] }} hari</strong>
                        </div>
                        <div class="leave-annual-row">
                            <span>Terpakai</span>
                            <strong>{{ $summary['used_days'] }} hari</strong>
                        </div>
                        <div class="leave-annual-row">
                            <span>Tertahan</span>
                            <strong>{{ $summary['reserved_days'] }} hari</strong>
                        </div>
                        <div class="leave-table-note">Maksimal sisa {{ $summaryYear - 1 }} dan {{ $summaryYear - 2 }} masing-masing 6 hari.</div>
                    </div>
                @else
                    <div class="leave-balance-stats">
                        <div class="leave-balance-stat">
                            <div class="leave-balance-label">Total Hak</div>
                            <div class="leave-balance-value">{{ $summary['total_balance'] }} hari</div>
                        </div>
                        <div class="leave-balance-stat">
                            <div class="leave-balance-label">Saldo Bawaan</div>
                            <div class="leave-balance-value">{{ $summary['carry_forward'] }} hari</div>
                        </div>
                        <div class="leave-balance-stat">
                            <div class="leave-balance-label">Terpakai</div>
                            <div class="leave-balance-value">{{ $summary['used_days'] }} hari</div>
                        </div>
                        <div class="leave-balance-stat">
                            <div class="leave-balance-label">Tertahan</div>
                            <div class="leave-balance-value">{{ $summary['reserved_days'] }} hari</div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="leave-balance-empty">Belum ada jenis cuti yang memakai saldo.</div>
        @endforelse
    </div>
</section>

<div class="card border-0 shadow-sm leave-table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Keterangan Cuti</th>
                        <th>Alamat Selama Cuti</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $leaveRequest)
                        @php
                            $canEdit = in_array($leaveRequest->status, [\App\LeaveRequest::STATUS_DRAFT, \App\LeaveRequest::STATUS_REJECTED, \App\LeaveRequest::STATUS_CHANGED, \App\LeaveRequest::STATUS_DEFERRED], true) && is_null($leaveRequest->locked_at);
                            $documents = $leaveRequest->documents->map(function ($document) use ($leaveRequest) {
                                return [
                                    'name' => $document->original_name,
                                    'url' => route('cuti.documents.show', [$leaveRequest, $document]),
                                ];
                            })->values();
                            $approvalFlow = $leaveRequest->approvals->map(function ($approval) {
                                return [
                                    'step' => $approval->step_no,
                                    'role' => $approval->role_label,
                                    'status' => $approval->status_label,
                                    'status_badge' => $approval->status_badge,
                                    'approver' => optional($approval->approver)->name ?: 'Belum ditentukan',
                                    'acted_at' => $approval->acted_at ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y H:i') . ' WIT' : '-',
                                    'note' => $approval->note ?: '-',
                                ];
                            })->values();
                            $approvalSummary = $approvalFlow->isNotEmpty()
                                ? $leaveRequest->approvals->where('status', 'approved')->count() . '/' . $leaveRequest->approvals->count() . ' selesai'
                                : 'Belum ada alur';
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
                            data-is-abroad="{{ $leaveRequest->is_abroad ? 1 : 0 }}"
                            data-abroad-country="{{ e($leaveRequest->abroad_country) }}"
                            data-documents='@json($documents)'
                        >
                            <td data-label="Nomor">{{ $leaveRequest->display_number }}</td>
                            <td data-label="Jenis">{{ optional($leaveRequest->leaveType)->name }}</td>
                            <td data-label="Periode">{{ optional($leaveRequest->start_date)->translatedFormat('d M Y') }} - {{ optional($leaveRequest->end_date)->translatedFormat('d M Y') }}</td>
                            <td data-label="Keterangan Cuti">
                                <div class="leave-table-note">{{ $leaveRequest->purpose ?: '-' }}</div>
                            </td>
                            <td data-label="Alamat Selama Cuti">
                                <div class="leave-table-note">{{ $leaveRequest->leave_address ?: '-' }}</div>
                                @if($leaveRequest->is_abroad)
                                    <div class="leave-table-note mt-1"><i class="fas fa-plane-departure mr-1"></i>Luar negeri: {{ $leaveRequest->abroad_country ?: '-' }}</div>
                                @endif
                            </td>
                            <td data-label="Status">{!! $leaveRequest->status_badge !!}</td>
                            <td data-label="Approval">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm leave-approval-trigger"
                                    data-leave-approval-trigger
                                    data-number="{{ $leaveRequest->display_number }}"
                                    data-approval-summary="{{ $approvalSummary }}"
                                    data-approval-flow='@json($approvalFlow)'
                                >
                                    <i class="fas fa-tasks"></i>
                                    Status Approval
                                </button>
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
                            <td colspan="8" class="text-center text-muted py-4">Belum ada pengajuan cuti.</td>
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

<div class="modal fade" id="leaveApprovalStatusModal" tabindex="-1" role="dialog" aria-labelledby="leaveApprovalStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="leaveApprovalStatusModalLabel">Status Approval</h5>
                    <div class="text-muted" id="leaveApprovalStatusMeta" style="font-size: 0.76rem;"></div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="leaveApprovalStatusList" class="leave-approval-modal-list"></div>
            </div>
        </div>
    </div>
</div>
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
            is_abroad: @json(old('is_abroad')),
            abroad_country: @json(old('abroad_country')),
        };
        var queryOpen = @json(request('open'));
        var queryEdit = @json(request('edit'));
        var approvalStatusModal = $('#leaveApprovalStatusModal');
        var approvalStatusMeta = $('#leaveApprovalStatusMeta');
        var approvalStatusList = $('#leaveApprovalStatusList');

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[char];
            });
        }

        function renderApprovalFlow(flow) {
            if (!Array.isArray(flow) || !flow.length) {
                approvalStatusList.html('<div class="leave-approval-modal-meta">Belum ada alur approval.</div>');
                return;
            }

            approvalStatusList.html(flow.map(function (item) {
                return '<div class="leave-approval-modal-item">' +
                    '<div class="leave-approval-modal-title">' +
                        '<span>Step ' + escapeHtml(item.step) + ' - ' + escapeHtml(item.role) + '</span>' +
                        '<span class="leave-approval-modal-status">' + (item.status_badge || escapeHtml(item.status)) + '</span>' +
                    '</div>' +
                    '<div class="leave-approval-modal-meta">Pejabat: ' + escapeHtml(item.approver) + '</div>' +
                    '<div class="leave-approval-modal-meta">Diproses: ' + escapeHtml(item.acted_at) + '</div>' +
                    '<div class="leave-approval-modal-meta">Catatan: ' + escapeHtml(item.note) + '</div>' +
                '</div>';
            }).join(''));
        }

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
            editForm.find('input[name="is_abroad"]').prop('checked', Number(data.is_abroad || 0) === 1);
            editForm.find('input[name="abroad_country"]').val(data.abroad_country || '');
            toggleAbroadFields(editForm);
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
                is_abroad: row.data('is-abroad'),
                abroad_country: row.data('abroad-country'),
                documents: row.data('documents')
            };
        }

        function toggleAbroadFields(scope) {
            var root = scope ? $(scope) : $(document);
            root.find('[data-leave-abroad-toggle]').each(function () {
                var checkbox = $(this);
                var form = checkbox.closest('form');
                var countryField = form.find('[data-leave-abroad-country]');
                countryField.toggleClass('d-none', !checkbox.is(':checked'));
                countryField.find('input[name="abroad_country"]').prop('required', checkbox.is(':checked'));
                if (!checkbox.is(':checked')) {
                    countryField.find('input[name="abroad_country"]').val('');
                }
            });
        }

        $(document).on('click', '[data-leave-edit-trigger]', function () {
            var targetId = $(this).data('target-id');
            var data = getRowDataById(targetId);
            fillEditModal(data);
            editModal.modal('show');
        });

        $(document).on('change', '[data-leave-abroad-toggle]', function () {
            toggleAbroadFields($(this).closest('form'));
        });

        $(document).on('click', '[data-leave-approval-trigger]', function () {
            var button = $(this);
            approvalStatusMeta.text((button.data('number') || '-') + ' | ' + (button.data('approval-summary') || ''));
            renderApprovalFlow(button.data('approval-flow'));
            approvalStatusModal.modal('show');
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
                is_abroad: oldValues.is_abroad,
                abroad_country: oldValues.abroad_country,
                documents: (getRowDataById(oldLeaveId) || {}).documents || []
            });
            editModal.modal('show');
        } else if (oldMode === 'create' && {{ $errors->any() ? 'true' : 'false' }}) {
            toggleAbroadFields($('#createLeaveRequestForm'));
            $('#createLeaveRequestModal').modal('show');
        } else if (queryOpen === 'create') {
            toggleAbroadFields($('#createLeaveRequestForm'));
            $('#createLeaveRequestModal').modal('show');
        } else if (queryEdit) {
            var queryData = getRowDataById(queryEdit);
            if (queryData) {
                fillEditModal(queryData);
                editModal.modal('show');
            }
        }

        toggleAbroadFields($('#createLeaveRequestForm'));
        toggleAbroadFields($('#editLeaveRequestForm'));
    })();
</script>
@endpush
