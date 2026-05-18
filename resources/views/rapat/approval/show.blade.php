@extends('layouts.app')

@section('title', 'Detail Approval Rapat')

@push('styles')
    <style>
        .approval-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(380px, 0.95fr);
            gap: 18px;
        }

        .approval-detail-card {
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
        }

        .approval-detail-card .card-header {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
        }

        .approval-stage-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.78rem;
            font-weight: 800;
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

        .approval-step-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .approval-step-item:last-child {
            border-bottom: none;
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

            .approval-detail-card .card-header {
                padding: 14px 16px;
                flex-direction: column;
                gap: 10px;
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

            .approval-step-item {
                flex-direction: column;
                align-items: flex-start;
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
                <h1 class="mb-1">Detail Approval Dokumen</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Review dokumen undangan rapat sebelum paraf atau tanda tangani.</div>
            </div>
            <a href="{{ route('approval.index', ['category' => 'undangan']) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="approval-detail-grid">
        <div>
            <div class="card approval-detail-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-start">
                    <div>
                        <div class="approval-doc-title">{{ $rapatApproval->rapat->judul }}</div>
                        <div class="approval-meta">{{ $rapatApproval->rapat->nomor_undangan }} | {{ optional($rapatApproval->rapat->tanggal)->translatedFormat('d F Y') }} | {{ $rapatApproval->rapat->waktu_mulai_formatted }} WIT</div>
                        <div class="approval-meta">Approver aktif: {{ $rapatApproval->approver_name_snapshot }}</div>
                    </div>
                    <span class="approval-stage-chip">{{ $rapatApproval->stage_label }}</span>
                </div>
                <div class="card-body">
                    <div class="approval-section">
                        <div class="approval-section-title">Informasi Dokumen</div>
                        <div class="approval-meta">Status Dokumen: {{ $rapatApproval->rapat->status_label }}</div>
                        <div class="approval-meta">Kategori Surat: {{ $rapatApproval->rapat->kategori_surat_label }}</div>
                        <div class="approval-meta">Tempat: {{ $rapatApproval->rapat->tempat }}</div>
                        <div class="approval-meta">Pembuat: {{ optional($rapatApproval->rapat->creator)->name ?: '-' }}</div>
                        <div class="approval-meta">Peserta: {{ $rapatApproval->rapat->pesertas->count() }} orang</div>
                        @if($rapatApproval->rapat->lampiran_tambahan_path)
                            <div class="approval-meta mt-2"><a href="{{ route('rapat.lampiran', $rapatApproval->rapat) }}" target="_blank">Buka lampiran tambahan</a></div>
                        @endif
                    </div>

                    <div class="approval-section">
                        <div class="approval-section-title">Urutan Approval</div>
                        @foreach($rapatApproval->rapat->approvals->sortBy('step_order') as $step)
                            @php
                                $badgeMap = [
                                    'pending' => ['warning', 'Pending'],
                                    'waiting' => ['secondary', 'Waiting'],
                                    'approved' => ['success', 'Approved'],
                                    'rejected' => ['danger', 'Rejected'],
                                ][$step->status] ?? ['secondary', ucfirst($step->status)];
                            @endphp
                            <div class="approval-step-item">
                                <div>
                                    <div style="font-size:0.88rem;font-weight:800;color:#0f172a;">{{ $step->stage_label }} - {{ $step->approver_name_snapshot }}</div>
                                    <div class="approval-meta">{{ $step->approver_jabatan_snapshot ?: 'Tanpa jabatan' }}</div>
                                    @if($step->acted_at)
                                        <div class="approval-meta">{{ $step->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') }} WIT</div>
                                    @endif
                                    @if($step->catatan)
                                        <div class="approval-meta">Catatan: {{ $step->catatan }}</div>
                                    @endif
                                </div>
                                <div><span class="badge badge-{{ $badgeMap[0] }}">{{ $badgeMap[1] }}</span></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="approval-section">
                        <div class="approval-section-title">Riwayat Approval</div>
                        @forelse($rapatApproval->rapat->approvalHistories->sortByDesc('acted_at') as $entry)
                            <div class="history-row">
                                <div style="font-size:0.88rem;font-weight:800;color:#0f172a;">{{ (int) $entry->step_order === 1 ? 'Paraf' : ((int) $entry->step_order === 2 ? 'Tanda Tangani' : 'Step ' . $entry->step_order) }} - {{ $entry->approver_name_snapshot }}</div>
                                <div class="approval-meta">{{ ucfirst($entry->action) }} | {{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                                @if($entry->catatan)
                                    <div class="approval-meta">Catatan: {{ $entry->catatan }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="approval-meta">Belum ada riwayat approval.</div>
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
                                <button type="button" class="btn btn-success approval-action-btn" onclick="openApprovalSignatureModal()">
                                    <i class="fas fa-check mr-1"></i> {{ $rapatApproval->stage_label }}
                                </button>
                                <button type="button" class="btn btn-danger approval-action-btn" onclick="submitApprovalDecision({{ $rapatApproval->id }}, 'reject')">
                                    <i class="fas fa-times mr-1"></i> Tolak Dokumen
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="approval-section">
                            <div class="approval-section-title">Status Tindakan</div>
                            <div class="approval-meta">Dokumen ini tidak berada pada status yang bisa Anda proses saat ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="approval-pdf-preview">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
                    <strong>Preview Dokumen</strong>
                    <a href="{{ route('rapat.undangan.preview', $rapatApproval->rapat) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-external-link-alt mr-1"></i> Buka PDF
                    </a>
                </div>
                <iframe src="{{ route('rapat.undangan.preview', $rapatApproval->rapat) }}" style="width:100%;height:920px;border:0;" title="Preview Undangan Rapat"></iframe>
            </div>
        </div>
    </div>

    @if($canAct)
        <div class="modal fade" id="rapatApprovalSignatureModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tanda Tangan Digital</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.signature-pad', [
                            'id' => 'rapatApprovalSignaturePad',
                            'name' => 'signature_data',
                            'label' => 'Bubuhkan Tanda Tangan',
                            'required' => true,
                        ])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-success approval-action-btn" onclick="submitApprovalDecision({{ $rapatApproval->id }}, 'approve')">
                            Simpan & Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        let approvalDecisionInFlight = false;

        function openApprovalSignatureModal() {
            $('#rapatApprovalSignatureModal').modal('show');
        }

        function submitApprovalDecision(approvalId, action) {
            if (approvalDecisionInFlight) {
                return;
            }

            const note = $('#approval-note').val();
            const url = action === 'approve'
                ? '{{ url('/rapat/approval') }}/' + approvalId + '/approve'
                : '{{ url('/rapat/approval') }}/' + approvalId + '/reject';

            if (action === 'reject' && !String(note || '').trim()) {
                showToast('Catatan reject wajib diisi.', 'error');
                $('#approval-note').focus();
                return;
            }

            let signatureData = null;
            if (action === 'approve') {
                const signatureField = document.querySelector('#rapatApprovalSignatureModal .js-signature-pad');
                if (!window.AppSignaturePad.sync(signatureField)) {
                    showToast('Tanda tangan wajib diisi sebelum menyetujui dokumen.', 'error');
                    return;
                }
                signatureData = signatureField.querySelector('input[name="signature_data"]').value;
            }

            if (action === 'reject' && !window.confirm('Tolak dokumen undangan rapat ini?')) {
                return;
            }

            approvalDecisionInFlight = true;
            $('.approval-action-btn').prop('disabled', true);

            $.ajax({
                url: url,
                method: 'POST',
                loadingMessage: action === 'approve' ? 'Memproses approval dokumen...' : 'Memproses reject dokumen...',
                data: {
                    _token: '{{ csrf_token() }}',
                    catatan: note,
                    signature_data: signatureData
                },
                success: function (res) {
                    showToast(res.message, 'success');
                    window.location.href = '{{ route('approval.index', ['category' => 'undangan']) }}';
                },
                error: function (xhr) {
                    const errors = xhr.responseJSON?.errors;
                    let message = xhr.responseJSON?.message || 'Gagal memproses approval.';
                    if (errors) {
                        message = Object.values(errors).flat().join(' ');
                    } else if (xhr.responseText && !xhr.responseJSON) {
                        message = 'Permintaan gagal diproses. Silakan muat ulang halaman dan coba lagi.';
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
