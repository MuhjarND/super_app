@extends('layouts.app')

@section('title', 'Detail Approval Notulen')

@push('styles')
    <style>
        .approval-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(420px, 0.95fr);
            gap: 18px;
        }

        .approval-card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .approval-card .card-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
        }

        .approval-doc-title {
            font-size: 1.08rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-meta {
            font-size: 0.84rem;
            color: #64748b;
            line-height: 1.55;
        }

        .approval-section {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #f8fafc;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .approval-section-title {
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 10px;
        }

        .approval-pdf-preview {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            position: sticky;
            top: 14px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .approval-note {
            min-height: 120px;
            border-radius: 12px;
            resize: vertical;
        }

        .approval-action-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .history-row {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-row:last-child {
            border-bottom: none;
        }

        @media (max-width: 991.98px) {
            .approval-detail-grid {
                grid-template-columns: 1fr;
            }

            .approval-pdf-preview {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .content-header .container-fluid {
                display: block !important;
            }

            .content-header .btn {
                width: 100%;
                margin-top: 10px;
            }

            .approval-card .card-header {
                padding: 14px 16px;
            }

            .approval-doc-title {
                font-size: 1rem;
                line-height: 1.35;
            }

            .approval-meta {
                font-size: 0.8rem;
            }

            .approval-section {
                padding: 12px 13px;
            }

            .approval-action-bar .btn {
                width: 100%;
            }

            .approval-pdf-preview iframe {
                height: 68vh !important;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">Detail Approval Notulen</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Periksa PDF notulen sebelum menandatangani dokumen final.</div>
            </div>
            <a href="{{ route('approval.index', ['category' => 'notulensi']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="approval-detail-grid">
        <div>
            <div class="card approval-card mb-3">
                <div class="card-header">
                    <div class="approval-doc-title">{{ optional($notulensiApproval->notulensi)->judul ?: optional(optional($notulensiApproval->notulensi)->rapat)->judul ?: '-' }}</div>
                    <div class="approval-meta">{{ optional(optional($notulensiApproval->notulensi)->rapat)->nomor_undangan ?: '-' }} | {{ optional(optional($notulensiApproval->notulensi)->rapat->tanggal)->translatedFormat('d F Y') }} | {{ optional(optional($notulensiApproval->notulensi)->rapat)->waktu_mulai_formatted }} WIT</div>
                    <div class="approval-meta">Approver aktif: {{ $notulensiApproval->approver_name_snapshot }}</div>
                </div>
                <div class="card-body">
                    <div class="approval-section">
                        <div class="approval-section-title">Informasi Notulen</div>
                        <div class="approval-meta">Status: {!! optional($notulensiApproval->notulensi)->status_badge !!}</div>
                        <div class="approval-meta">Notulis: {{ optional(optional($notulensiApproval->notulensi)->notulis)->name ?: '-' }}</div>
                        <div class="approval-meta">Kategori Surat: {{ optional(optional($notulensiApproval->notulensi)->rapat)->kategori_surat_label ?: '-' }}</div>
                        <div class="approval-meta">Peserta: {{ optional(optional($notulensiApproval->notulensi)->rapat)->pesertas ? optional($notulensiApproval->notulensi->rapat)->pesertas->count() : 0 }} orang</div>
                    </div>

                    <div class="approval-section">
                        <div class="approval-section-title">Riwayat Approval</div>
                        @forelse($notulensiApproval->histories as $entry)
                            <div class="history-row">
                                <div style="font-size:0.88rem;font-weight:800;color:#0f172a;">{{ ucfirst($entry->action) }} - {{ $entry->approver_name_snapshot }}</div>
                                <div class="approval-meta">{{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                                @if($entry->catatan)
                                    <div class="approval-meta">Catatan: {{ $entry->catatan }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="approval-meta">Belum ada riwayat approval notulen.</div>
                        @endforelse
                    </div>

                    @if($canAct)
                        <div class="approval-section">
                            <div class="approval-section-title">Tindakan</div>
                            <div class="form-group mb-3">
                                <label for="approval-note" class="mb-1">Catatan Approval / Reject</label>
                                <textarea id="approval-note" class="form-control approval-note" placeholder="Isi catatan bila diperlukan. Untuk reject, catatan wajib diisi."></textarea>
                            </div>
                            <div class="approval-action-bar">
                                <button type="button" class="btn btn-success approval-action-btn" onclick="submitNotulensiApprovalDecision({{ $notulensiApproval->id }}, 'approve')">
                                    <i class="fas fa-check mr-1"></i> Tanda Tangani Notulen
                                </button>
                                <button type="button" class="btn btn-danger approval-action-btn" onclick="submitNotulensiApprovalDecision({{ $notulensiApproval->id }}, 'reject')">
                                    <i class="fas fa-times mr-1"></i> Tolak Notulen
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="approval-section">
                            <div class="approval-section-title">Status Tindakan</div>
                            <div class="approval-meta">Notulen ini tidak berada pada status yang bisa Anda proses saat ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="approval-pdf-preview">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                    <strong>Preview Dokumen</strong>
                    <a href="{{ route('rapat.notulensi.pdf', $notulensiApproval->notulensi) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt mr-1"></i> Buka PDF
                    </a>
                </div>
                <iframe src="{{ route('rapat.notulensi.pdf', $notulensiApproval->notulensi) }}" style="width:100%;height:920px;border:0;" title="Preview Notulen"></iframe>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let approvalDecisionInFlight = false;

        function submitNotulensiApprovalDecision(approvalId, action) {
            if (approvalDecisionInFlight) {
                return;
            }

            const note = $('#approval-note').val();
            const url = action === 'approve'
                ? '{{ url('/rapat/notulensi-approval') }}/' + approvalId + '/approve'
                : '{{ url('/rapat/notulensi-approval') }}/' + approvalId + '/reject';

            if (action === 'reject' && !String(note || '').trim()) {
                showToast('Catatan reject wajib diisi.', 'error');
                $('#approval-note').focus();
                return;
            }

            if (action === 'reject' && !window.confirm('Tolak dokumen notulen ini?')) {
                return;
            }

            approvalDecisionInFlight = true;
            $('.approval-action-btn').prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                loadingMessage: action === 'approve' ? 'Memproses approval notulen...' : 'Memproses reject notulen...',
                data: {
                    _token: '{{ csrf_token() }}',
                    catatan: note
                },
                success: function (res) {
                    showToast(res.message, 'success');
                    window.location.href = '{{ route('approval.index', ['category' => 'notulensi']) }}';
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    let message = xhr.responseJSON?.message || 'Gagal memproses approval notulen.';
                    if (errors) {
                        message = Object.values(errors).flat().join(' ');
                    }
                    showToast(message, 'error');
                },
                complete: function () {
                    approvalDecisionInFlight = false;
                    $('.approval-action-btn').prop('disabled', false);
                }
            });
        }
    </script>
@endpush
