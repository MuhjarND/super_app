@extends('layouts.app')

@section('title', 'Approval Cuti')

@push('styles')
    <style>
        .leave-approval-board {
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .leave-approval-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef2f7;
        }

        .leave-approval-item:last-child {
            border-bottom: none;
        }

        .leave-approval-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .leave-approval-meta {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.55;
        }

        .leave-approval-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .leave-approval-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .leave-approval-empty {
            padding: 24px 20px;
            text-align: center;
            color: #64748b;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .leave-approval-item {
                flex-direction: column;
                padding: 16px;
            }

            .leave-approval-actions {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h3 class="mb-1">Approval Cuti</h3>
            <p class="text-muted mb-0">Daftar pengajuan cuti yang menunggu tindakan Anda.</p>
        </div>
    </div>

    <div class="card leave-approval-board border-0 shadow-sm">
        <div class="card-body p-0">
            @forelse($approvals as $approval)
                <div class="leave-approval-item">
                    <div>
                        <div class="leave-approval-title">{{ optional($approval->leaveRequest)->display_number ?: 'Pengajuan cuti' }}</div>
                        <div class="leave-approval-meta">{{ optional(optional($approval->leaveRequest)->user)->name ?: '-' }} | {{ optional(optional($approval->leaveRequest)->leaveType)->name ?: '-' }}</div>
                        <div class="leave-approval-meta">{{ optional(optional($approval->leaveRequest)->start_date)->translatedFormat('d M Y') }} - {{ optional(optional($approval->leaveRequest)->end_date)->translatedFormat('d M Y') }}</div>
                        <div class="leave-approval-tags">
                            <span class="leave-approval-tag">Step {{ $approval->step_no }}</span>
                            <span class="leave-approval-tag">{{ $approval->role_label }}</span>
                        </div>
                    </div>
                    <div class="leave-approval-actions app-action-group">
                        <a href="{{ route('cuti.approval.show', $approval) }}" class="app-icon-btn process" data-mobile-label="Proses">
                            <i class="fas fa-file-signature"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="leave-approval-empty">Tidak ada approval cuti yang pending.</div>
            @endforelse
        </div>
    </div>
@endsection
