@extends('layouts.app')

@section('title', 'Approval Notulen')

@push('styles')
    <style>
        .approval-wrap .card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            margin-bottom: 16px;
        }

        .approval-wrap .card-header {
            padding: 18px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .approval-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .approval-subtitle {
            font-size: 0.82rem;
            color: #64748b;
        }

        .approval-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef2f7;
        }

        .approval-item:last-child {
            border-bottom: none;
        }

        .approval-doc-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-meta {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.55;
        }

        .approval-empty {
            padding: 22px 20px;
            font-size: 0.9rem;
            color: #64748b;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .approval-item {
                flex-direction: column;
                padding: 16px;
            }

            .approval-wrap .card-header {
                padding: 15px 16px;
                align-items: flex-start !important;
                gap: 10px;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Approval Notulen</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Notulen yang sudah dibuat notulis dan menunggu approval final.</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="approval-wrap">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="approval-title">Daftar Pending Approval</div>
                    <div class="approval-subtitle">Notulen yang perlu Anda periksa dan tanda tangani.</div>
                </div>
                <span class="badge badge-danger">{{ $pendingApprovals->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($pendingApprovals as $approval)
                    <div class="approval-item">
                        <div>
                            <div class="approval-doc-title">{{ optional($approval->notulensi)->judul ?: optional(optional($approval->notulensi)->rapat)->judul ?: '-' }}</div>
                            <div class="approval-meta">{{ optional(optional($approval->notulensi)->rapat)->nomor_undangan ?: '-' }} | {{ optional(optional($approval->notulensi)->rapat->tanggal)->translatedFormat('d F Y') }}</div>
                            <div class="approval-meta">Notulis: {{ optional(optional($approval->notulensi)->notulis)->name ?: '-' }} | Approver: {{ $approval->approver_name_snapshot }}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <a href="{{ route('rapat.notulensi-approval.show', $approval) }}" class="btn btn-primary btn-sm" data-mobile-label="Proses">
                                <i class="fas fa-file-signature mr-1"></i> Proses Notulen
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="approval-empty">Tidak ada notulen yang menunggu approval saat ini.</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="approval-title">Riwayat Approval Notulen</div>
                    <div class="approval-subtitle">Tindakan approval yang sudah tercatat untuk dokumen notulen.</div>
                </div>
                <span class="badge badge-primary">{{ $historyEntries->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($historyEntries as $entry)
                    <div class="approval-item">
                        <div>
                            <div class="approval-doc-title">{{ optional(optional($entry->notulensi)->rapat)->judul ?: optional($entry->notulensi)->judul ?: '-' }}</div>
                            <div class="approval-meta">{{ ucfirst($entry->action) }} | {{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                            <div class="approval-meta">{{ $entry->approver_name_snapshot }}{{ $entry->catatan ? ' | Catatan: ' . $entry->catatan : '' }}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            @if($entry->approval)
                                <a href="{{ route('rapat.notulensi-approval.show', $entry->approval) }}" class="btn btn-outline-secondary btn-sm" data-mobile-label="Detail">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="approval-empty">Belum ada riwayat approval notulen.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
