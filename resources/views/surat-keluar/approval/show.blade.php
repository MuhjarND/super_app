@extends('layouts.app')

@section('title', 'Approval Surat Keluar')

@push('styles')
<style>
    .surat-keluar-approval-top {
        gap: 14px;
    }

    .surat-keluar-approval-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(320px, 0.9fr);
        gap: 16px;
    }

    .surat-keluar-approval-card {
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .surat-keluar-approval-section-title {
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 12px;
    }

    .surat-keluar-approval-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }

    .surat-keluar-approval-meta-item strong {
        display: block;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .surat-keluar-approval-meta-item span {
        display: block;
        color: #0f172a;
        font-size: 0.94rem;
        line-height: 1.5;
    }

    .surat-keluar-preview-frame {
        width: 100%;
        height: 860px;
        border: 0;
        border-radius: 0 0 18px 18px;
        background: #fff;
    }

    .surat-keluar-history-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 800;
        border-bottom-color: #e2e8f0;
    }

    .surat-keluar-history-table th,
    .surat-keluar-history-table td {
        padding: 12px 14px;
        vertical-align: top;
    }

    .surat-keluar-approval-action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    @media (max-width: 991.98px) {
        .surat-keluar-approval-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .surat-keluar-approval-top {
            flex-direction: column;
            align-items: stretch !important;
        }

        .surat-keluar-approval-top .app-action-group {
            justify-content: stretch;
            width: 100%;
        }

        .surat-keluar-approval-top .app-action-group > * {
            flex: 1 1 0;
        }

        .surat-keluar-approval-meta {
            grid-template-columns: 1fr;
        }

        .surat-keluar-approval-action-grid {
            grid-template-columns: 1fr;
        }

        .surat-keluar-history-table,
        .surat-keluar-history-table thead,
        .surat-keluar-history-table tbody,
        .surat-keluar-history-table tr,
        .surat-keluar-history-table th,
        .surat-keluar-history-table td {
            display: block;
            width: 100%;
        }

        .surat-keluar-history-table thead {
            display: none;
        }

        .surat-keluar-history-table tbody tr {
            padding: 12px 14px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .surat-keluar-history-table tbody tr:last-child {
            border-bottom: 0;
        }

        .surat-keluar-history-table td {
            padding: 0 0 10px;
            border: 0;
            font-size: 0.92rem;
        }

        .surat-keluar-history-table td:last-child {
            padding-bottom: 0;
        }

        .surat-keluar-history-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .surat-keluar-preview-frame {
            height: 68vh;
        }

        .surat-keluar-approval-card .card-body,
        .surat-keluar-approval-card .card-header {
            padding-left: 14px;
            padding-right: 14px;
        }
    }
</style>
@endpush

@section('content')
@include('admin._alerts')

<div class="d-flex justify-content-between align-items-center mb-3 surat-keluar-approval-top">
    <div>
        <h3 class="mb-1">Approval Surat Keluar</h3>
        <p class="text-muted mb-0">{{ optional($suratKeluarApproval->suratKeluar)->nomor_surat_formatted ?: '-' }}</p>
    </div>
    <div class="app-action-group">
        <a href="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" target="_blank" class="app-icon-btn preview" data-mobile-label="Buka PDF"><i class="fas fa-eye"></i></a>
        <a href="{{ route('approval.index', ['category' => 'surat_keluar']) }}" class="app-icon-btn cancel" data-mobile-label="Kembali"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<div class="surat-keluar-approval-grid">
    <div>
        <div class="card surat-keluar-approval-card border-0 mb-3">
            <div class="card-body">
                <div class="surat-keluar-approval-section-title">Informasi Dokumen</div>
                <div class="surat-keluar-approval-meta">
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Template</strong>
                        <span>{{ $suratKeluarApproval->template_name ?: '-' }}</span>
                    </div>
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Status</strong>
                        <span><span class="badge badge-{{ $suratKeluarApproval->status_badge_class }}">{{ $suratKeluarApproval->status_label }}</span></span>
                    </div>
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Penanda Tangan</strong>
                        <span>{{ $suratKeluarApproval->signer_name_snapshot ?: '-' }}</span>
                    </div>
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Jabatan TTD</strong>
                        <span>{{ $suratKeluarApproval->signer_title_snapshot ?: '-' }}</span>
                    </div>
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Diajukan Oleh</strong>
                        <span>{{ optional($suratKeluarApproval->requester)->name ?: optional(optional($suratKeluarApproval->suratKeluar)->creator)->name ?: '-' }}</span>
                    </div>
                    <div class="surat-keluar-approval-meta-item">
                        <strong>Perihal</strong>
                        <span>{{ optional($suratKeluarApproval->suratKeluar)->perihal ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card surat-keluar-approval-card border-0 mb-3">
            <div class="card-header bg-white"><strong>Riwayat Approval</strong></div>
            <div class="card-body p-0">
                <table class="table mb-0 surat-keluar-history-table">
                    <thead><tr><th>Waktu</th><th>Aktor</th><th>Aksi</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse($suratKeluarApproval->histories as $history)
                            <tr>
                                <td data-label="Waktu">{{ optional($history->acted_at)->translatedFormat('d F Y H:i') }} WIT</td>
                                <td data-label="Aktor">{{ optional($history->approver)->name ?: $history->signer_name_snapshot ?: '-' }}</td>
                                <td data-label="Aksi">{{ ucfirst($history->action) }}</td>
                                <td data-label="Catatan">{{ $history->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat approval.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($canAct)
            <div class="surat-keluar-approval-action-grid">
                <div class="card surat-keluar-approval-card border-0 mb-0">
                    <div class="card-body">
                        <div class="surat-keluar-approval-section-title">Persetujuan</div>
                        <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#suratKeluarApprovalSignatureModal">Approve</button>
                    </div>
                </div>
                <form action="{{ route('surat-keluar.approval.reject', $suratKeluarApproval) }}" method="POST" class="card surat-keluar-approval-card border-0 mb-0">
                    @csrf
                    <div class="card-body">
                        <div class="surat-keluar-approval-section-title">Penolakan</div>
                        <textarea name="note" class="form-control mb-3" rows="3" placeholder="Catatan penolakan" required></textarea>
                        <button type="submit" class="btn btn-danger btn-block">Reject</button>
                    </div>
                </form>
            </div>

            <div class="modal fade" id="suratKeluarApprovalSignatureModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Approve Surat Keluar</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <form action="{{ route('surat-keluar.approval.approve', $suratKeluarApproval) }}" method="POST" class="requires-signature-pad">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Catatan Approval</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Catatan approval"></textarea>
                                </div>
                                @include('partials.signature-pad', [
                                    'id' => 'suratKeluarApprovalSignaturePad',
                                    'name' => 'signature_data',
                                    'label' => 'Bubuhkan Tanda Tangan',
                                    'required' => true,
                                ])
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
    </div>

    <div>
        <div class="card surat-keluar-approval-card border-0 mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Preview Dokumen</strong>
                <a href="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fas fa-external-link-alt mr-1"></i> Buka PDF</a>
            </div>
            <div class="card-body p-0">
                <iframe src="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" class="surat-keluar-preview-frame"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection
