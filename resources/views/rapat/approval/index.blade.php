@extends('layouts.app')

@section('title', 'Approval Rapat')

@push('styles')
    <style>
        .approval-accordion .card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            margin-bottom: 16px;
        }

        .approval-accordion .card-header {
            padding: 0;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .approval-toggle {
            width: 100%;
            padding: 18px 20px;
            border: none;
            background: transparent;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .approval-toggle:focus {
            outline: none;
        }

        .approval-toggle-left {
            min-width: 0;
        }

        .approval-toggle-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .approval-toggle-sub {
            font-size: 0.82rem;
            color: #64748b;
        }

        .approval-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            border-radius: 999px;
            padding: 0 12px;
            font-size: 0.85rem;
            font-weight: 800;
            color: #fff;
        }

        .approval-count-badge.pending {
            background: #d97706;
        }

        .approval-count-badge.waiting {
            background: #475569;
        }

        .approval-count-badge.history {
            background: #1d4ed8;
        }

        .approval-list-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef2f7;
        }

        .approval-list-item:last-child {
            border-bottom: none;
        }

        .approval-doc-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-doc-meta {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.55;
        }

        .approval-inline-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .approval-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .approval-list-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .approval-stage-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 800;
            border: 1px solid #cbd5e1;
        }

        .approval-empty {
            padding: 22px 20px;
            font-size: 0.9rem;
            color: #64748b;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .approval-list-item {
                flex-direction: column;
            }

            .approval-list-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Approval Dokumen Rapat</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Pilih dokumen dari daftar, lalu buka detail untuk paraf atau tanda tangani.</div>
        </div>
    </div>
@endsection

@section('content')
    <div class="approval-accordion" id="approvalAccordion">
        <div class="card">
            <div class="card-header" id="headingPending">
                <button class="approval-toggle" type="button" data-toggle="collapse" data-target="#collapsePending" aria-expanded="true" aria-controls="collapsePending">
                    <div class="approval-toggle-left">
                        <div class="approval-toggle-title">Daftar Yang Harus Di-Approval</div>
                        <div class="approval-toggle-sub">Dokumen yang sedang menunggu tindakan Anda saat ini.</div>
                    </div>
                    <span class="approval-count-badge pending">{{ $pendingApprovals->count() }}</span>
                </button>
            </div>
            <div id="collapsePending" class="collapse show" aria-labelledby="headingPending" data-parent="#approvalAccordion">
                <div class="card-body p-0">
                    @forelse($pendingApprovals as $approval)
                        <div class="approval-list-item">
                            <div>
                                <div class="approval-doc-title">{{ $approval->rapat->judul }}</div>
                                <div class="approval-doc-meta">{{ $approval->rapat->nomor_undangan }} | {{ optional($approval->rapat->tanggal)->translatedFormat('d F Y') }} | {{ $approval->rapat->waktu_mulai_formatted }} WIT</div>
                                <div class="approval-doc-meta">Approver: {{ $approval->approver_name_snapshot }} | Status: {{ $approval->rapat->status_label }}</div>
                                <div class="approval-inline-tags">
                                    <span class="approval-tag">{{ $approval->stage_label }}</span>
                                    <span class="approval-tag">{{ $approval->rapat->kategori_surat_label }}</span>
                                    <span class="approval-tag">{{ $approval->rapat->pesertas->count() }} peserta</span>
                                </div>
                            </div>
                            <div class="approval-list-actions">
                                <span class="approval-stage-badge">{{ $approval->stage_label }}</span>
                                <a href="{{ route('rapat.approval.show', $approval) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-file-signature mr-1"></i> Proses Dokumen
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="approval-empty">Tidak ada dokumen yang perlu diproses saat ini.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" id="headingWaiting">
                <button class="approval-toggle collapsed" type="button" data-toggle="collapse" data-target="#collapseWaiting" aria-expanded="false" aria-controls="collapseWaiting">
                    <div class="approval-toggle-left">
                        <div class="approval-toggle-title">Daftar Yang Menunggu Paraf</div>
                        <div class="approval-toggle-sub">Dokumen yang belum sampai ke tahap Anda karena paraf belum selesai.</div>
                    </div>
                    <span class="approval-count-badge waiting">{{ $waitingApprovals->count() }}</span>
                </button>
            </div>
            <div id="collapseWaiting" class="collapse" aria-labelledby="headingWaiting" data-parent="#approvalAccordion">
                <div class="card-body p-0">
                    @forelse($waitingApprovals as $approval)
                        <div class="approval-list-item">
                            <div>
                                <div class="approval-doc-title">{{ $approval->rapat->judul }}</div>
                                <div class="approval-doc-meta">{{ $approval->rapat->nomor_undangan }} | {{ optional($approval->rapat->tanggal)->translatedFormat('d F Y') }} | {{ $approval->rapat->waktu_mulai_formatted }} WIT</div>
                                <div class="approval-doc-meta">Akan masuk ke tahap: {{ $approval->stage_label }} | Approver: {{ $approval->approver_name_snapshot }}</div>
                                <div class="approval-inline-tags">
                                    <span class="approval-tag">{{ $approval->rapat->kategori_surat_label }}</span>
                                    <span class="approval-tag">{{ $approval->rapat->pesertas->count() }} peserta</span>
                                </div>
                            </div>
                            <div class="approval-list-actions">
                                <span class="approval-stage-badge">{{ $approval->stage_label }}</span>
                                <a href="{{ route('rapat.approval.show', $approval) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye mr-1"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="approval-empty">Tidak ada dokumen yang sedang menunggu paraf.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" id="headingHistory">
                <button class="approval-toggle collapsed" type="button" data-toggle="collapse" data-target="#collapseHistory" aria-expanded="false" aria-controls="collapseHistory">
                    <div class="approval-toggle-left">
                        <div class="approval-toggle-title">Riwayat Approval</div>
                        <div class="approval-toggle-sub">Riwayat tindakan approval yang sudah tercatat di sistem.</div>
                    </div>
                    <span class="approval-count-badge history">{{ $historyEntries->count() }}</span>
                </button>
            </div>
            <div id="collapseHistory" class="collapse" aria-labelledby="headingHistory" data-parent="#approvalAccordion">
                <div class="card-body p-0">
                    @forelse($historyEntries as $entry)
                        <div class="approval-list-item">
                            <div>
                                <div class="approval-doc-title">{{ optional($entry->rapat)->judul ?: '-' }}</div>
                                <div class="approval-doc-meta">{{ optional($entry->rapat)->nomor_undangan ?: '-' }} | {{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                                <div class="approval-doc-meta">{{ $entry->approver_name_snapshot }} | {{ ucfirst($entry->action) }}{{ $entry->catatan ? ' | Catatan: ' . $entry->catatan : '' }}</div>
                            </div>
                            <div class="approval-list-actions">
                                <span class="approval-stage-badge">{{ (int) $entry->step_order === 1 ? 'Paraf' : ((int) $entry->step_order === 2 ? 'Tanda Tangani' : 'Step ' . $entry->step_order) }}</span>
                                @if($entry->approval)
                                    <a href="{{ route('rapat.approval.show', $entry->approval) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-history mr-1"></i> Lihat Detail
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="approval-empty">Belum ada riwayat approval.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
