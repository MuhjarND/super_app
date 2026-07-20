@extends('layouts.app')

@section('title', 'Tindak Lanjut Notulensi')

@push('styles')
    <style>
        .followup-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .followup-item-title {
            font-weight: 700;
            color: #0f172a;
        }

        .followup-item-subtitle {
            font-size: 0.8rem;
            color: #64748b;
        }

        .followup-status-cell {
            min-width: 190px;
        }

        .followup-status-wrap {
            position: relative;
            display: inline-flex;
            width: 172px;
            max-width: 100%;
        }

        .followup-status-select {
            width: 100%;
            height: 34px;
            border-radius: 10px;
            border: 1px solid #dbe3ee;
            padding: 0 34px 0 12px;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 34px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #fff;
            background-image: none;
            box-shadow: none;
            transition: all 0.18s ease;
        }

        .followup-status-select.status-pending {
            color: #c26a09;
            border-color: #f6b04c;
            background: #fff8e6;
        }

        .followup-status-select.status-process {
            color: #0f766e;
            border-color: #42d8cb;
            background: #e9fbf8;
        }

        .followup-status-select.status-completed {
            color: #166534;
            border-color: #7adf9f;
            background: #edfdf3;
        }

        .followup-status-select:focus {
            outline: none;
            box-shadow: 0 0 0 0.16rem rgba(59, 130, 246, 0.12);
        }

        .followup-status-arrow {
            position: absolute;
            right: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #475569;
            pointer-events: none;
            font-size: 0.74rem;
        }

        .followup-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 800;
            margin-top: 6px;
        }

        .followup-status-badge.pending {
            background: #fff0bf;
            color: #c26a09;
        }

        .followup-status-badge.process {
            background: #c9f5ef;
            color: #0f766e;
        }

        .followup-status-badge.completed {
            background: #d8f9e1;
            color: #166534;
        }

        .followup-status-stack {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .eviden-box {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .eviden-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .evidence-preview-frame {
            width: 100%;
            height: 70vh;
            border: 0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .evidence-preview-image {
            max-width: 100%;
            max-height: 70vh;
            display: block;
            margin: 0 auto;
        }

        .followup-detail-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #475569;
            font-size: 0.77rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .followup-detail-btn:hover {
            color: #4338ca;
            text-decoration: none;
        }

        .followup-detail-text {
            font-size: 0.92rem;
            color: #0f172a;
            line-height: 1.6;
            white-space: pre-wrap;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Tindak Lanjut Notulensi</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Daftar tugas tindak lanjut rekomendasi notulen yang perlu diselesaikan.</div>
        </div>
    </div>
@endsection

@section('content')
    @if(auth()->user()->canMonitorAllMeetingFollowUps() && !auth()->user()->canAccessMeetingMinutes())
        <div class="alert alert-info d-flex align-items-center mb-3">
            <i class="fas fa-eye mr-2"></i>
            <span>Mode monitoring pimpinan: seluruh tindak lanjut rapat ditampilkan dalam akses baca.</span>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            {{ collect($errors->all())->implode(' ') }}
        </div>
    @endif

    @php($followUpItems = $pendingItems->concat($completedItems)->values())
    <div class="card followup-card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>Daftar Tindak Lanjut</strong>
            <span class="badge badge-primary">{{ $followUpItems->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Rapat</th>
                            <th>Detail Tindak Lanjut</th>
                            <th>Status</th>
                            <th>Eviden</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($followUpItems as $item)
                            @php($canManageItem = auth()->user()->canManageMeetingFollowUp($item))
                            <tr>
                                <td>
                                    <div class="followup-item-title">{{ optional(optional($item->notulensi)->rapat)->judul ?: '-' }}</div>
                                    <div class="followup-item-subtitle">{{ optional(optional($item->notulensi)->rapat)->nomor_undangan ?: '-' }}</div>
                                    <div class="followup-item-subtitle mt-1">PIC: {{ optional($item->user)->name ?: '-' }}</div>
                                </td>
                                <td>
                                    <button type="button"
                                        class="followup-detail-btn"
                                        data-rapat-title="{{ e(optional(optional($item->notulensi)->rapat)->judul ?: '-') }}"
                                        data-participant-name="{{ e(optional($item->user)->name ?: '-') }}"
                                        data-detail-text="{{ e($item->deskripsi_snapshot ?: 'Belum ada detail tindak lanjut.') }}">
                                        <i class="fas fa-file-alt"></i> Lihat detail
                                    </button>
                                </td>
                                <td class="followup-status-cell">
                                    <div class="followup-status-stack">
                                        @if($canManageItem)
                                            <div class="followup-status-wrap">
                                                <select class="followup-status-select status-{{ $item->status }}" onchange="updateFollowUpStatus(this, {{ $item->id }}, '{{ $item->status }}')">
                                                    <option value="pending" {{ $item->status === 'pending' ? 'selected' : '' }}>Belum Ditindaklanjuti</option>
                                                    <option value="process" {{ $item->status === 'process' ? 'selected' : '' }}>Proses</option>
                                                    <option value="completed" {{ $item->status === 'completed' ? 'selected' : '' }}>Selesai</option>
                                                </select>
                                                <span class="followup-status-arrow"><i class="fas fa-chevron-down"></i></span>
                                            </div>
                                        @endif
                                        <span class="followup-status-badge {{ $item->status }}">
                                            {{ $item->status === 'pending' ? 'Belum Ditindaklanjuti' : ($item->status === 'process' ? 'Proses' : 'Selesai') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="eviden-box">
                                        @if($item->eviden_path)
                                            <div class="small text-success font-weight-bold">{{ $item->eviden_name ?: 'Eviden terupload' }}</div>
                                        @else
                                            <div class="small text-muted">Belum ada eviden</div>
                                        @endif
                                        <div class="eviden-actions">
                                            @if($canManageItem)
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEvidenceUploadModal({{ $item->id }}, '{{ e(optional(optional($item->notulensi)->rapat)->judul ?: '-') }}')">
                                                    <i class="fas fa-upload mr-1"></i>{{ $item->eviden_path ? 'Ganti Eviden' : 'Upload Eviden' }}
                                                </button>
                                            @endif
                                            @if($item->eviden_path)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="openEvidencePreviewModal('{{ route('rapat.notulensi.follow-ups.eviden.view', $item) }}', '{{ e($item->eviden_name ?: 'Eviden') }}', '{{ e($item->eviden_mime ?: '') }}')">
                                                    <i class="fas fa-eye mr-1"></i>Buka
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    @if($canManageItem && $item->status !== 'completed')
                                        <button type="button" class="btn btn-sm btn-primary" {{ !$item->eviden_path ? 'disabled' : '' }} onclick="openFollowUpModal({{ $item->id }}, '{{ e(optional(optional($item->notulensi)->rapat)->judul ?: '-') }}')">
                                            <i class="fas fa-check mr-1"></i>Selesaikan
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Tidak ada tindak lanjut yang tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="followUpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="followUpForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Selesaikan Tindak Lanjut</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 text-muted" id="followUpTarget"></div>
                        <div class="form-group mb-0">
                            <label>Catatan Penyelesaian</label>
                            <textarea name="catatan_penyelesaian" class="form-control" rows="4" placeholder="Isi hasil tindak lanjut bila diperlukan."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tandai Selesai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="evidenceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="evidenceForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Eviden Tindak Lanjut</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 text-muted" id="evidenceTarget"></div>
                        <div class="form-group mb-0">
                            <label>File Eviden</label>
                            <input type="file" name="eviden_file" class="form-control-file" required>
                            <small class="form-text text-muted">Format: PDF, JPG, JPEG, PNG, DOC, DOCX, XLS, XLSX. Maksimal 10MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload Eviden</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="evidencePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="evidencePreviewTitle">Preview Eviden</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="evidencePreviewContent"></div>
                </div>
                <div class="modal-footer">
                    <a href="#" target="_blank" class="btn btn-outline-primary" id="evidencePreviewOpenBtn">
                        <i class="fas fa-external-link-alt mr-1"></i>Buka di Tab Baru
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="followUpDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="followUpDetailTitle">Detail Tindak Lanjut</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="text-muted mb-2" id="followUpDetailMeta"></div>
                    <div class="followup-detail-text" id="followUpDetailContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openFollowUpModal(id, title) {
            $('#followUpForm').attr('action', '{{ url('rapat/notulensi/tindak-lanjut') }}/' + id + '/complete');
            $('#followUpTarget').text(title);
            $('#followUpModal').modal('show');
        }

        function openEvidenceUploadModal(id, title) {
            $('#evidenceForm').attr('action', '{{ url('rapat/notulensi/tindak-lanjut') }}/' + id + '/eviden');
            $('#evidenceTarget').text(title);
            $('#evidenceModal').modal('show');
        }

        function openEvidencePreviewModal(url, title, mime) {
            $('#evidencePreviewTitle').text(title || 'Preview Eviden');
            $('#evidencePreviewOpenBtn').attr('href', url);

            let content = '<iframe class="evidence-preview-frame" src="' + url + '"></iframe>';
            if (String(mime || '').indexOf('image/') === 0) {
                content = '<img class="evidence-preview-image" src="' + url + '" alt="' + (title || 'Eviden') + '">';
            } else if (['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'].includes(String(mime || ''))) {
                content = '<div class="text-center py-5">' +
                    '<div class="mb-3 text-muted">Preview langsung untuk file ini belum tersedia.</div>' +
                    '<a href="' + url + '" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-download mr-1"></i>Buka / Download Eviden</a>' +
                    '</div>';
            }

            $('#evidencePreviewContent').html(content);
            $('#evidencePreviewModal').modal('show');
        }

        function openFollowUpDetailModal(rapatTitle, participantName, detailText) {
            $('#followUpDetailTitle').text('Detail Tindak Lanjut');
            $('#followUpDetailMeta').text(rapatTitle + ' | PIC: ' + participantName);
            $('#followUpDetailContent').text(detailText || 'Belum ada detail tindak lanjut.');
            $('#followUpDetailModal').modal('show');
        }

        function updateFollowUpStatus(select, id, originalStatus) {
            const newStatus = $(select).val();

            $.ajax({
                url: '{{ url('rapat/notulensi/tindak-lanjut') }}/' + id + '/status',
                method: 'POST',
                loadingMessage: 'Memperbarui status tindak lanjut...',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: newStatus
                },
                success: function (res) {
                    showToast(res.message, 'success');
                    window.location.reload();
                },
                error: function (xhr) {
                    $(select).val(originalStatus);
                    let message = xhr.responseJSON?.message || 'Gagal memperbarui status tindak lanjut.';
                    if (xhr.responseJSON?.errors) {
                        message = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    }
                    showToast(message, 'error');
                }
            });
        }

        $(function () {
            $(document).on('click', '.followup-detail-btn', function () {
                const $button = $(this);
                openFollowUpDetailModal(
                    $button.data('rapat-title') || '-',
                    $button.data('participant-name') || '-',
                    $button.data('detail-text') || 'Belum ada detail tindak lanjut.'
                );
            });
        });
    </script>
@endpush
