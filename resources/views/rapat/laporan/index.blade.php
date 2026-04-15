@extends('layouts.app')

@section('title', 'Laporan Rapat')

@push('styles')
    <style>
        .laporan-card { border-radius: 16px; border: 1px solid #e5e7eb; }
        .meeting-action-toggle-col { width: 46px; }
        .laporan-file-col { width: 110px; white-space: nowrap; }
        .meeting-action-toggle { width: 28px; height: 28px; border: none; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .meeting-action-toggle.is-open { background: linear-gradient(135deg, #475569, #64748b); }
        .meeting-action-row { display: none; }
        .meeting-action-row td { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 16px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .meeting-action-meta { color: #64748b; font-size: 0.82rem; margin-right: 10px; }
        .meeting-action-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 7px 12px; font-size: 0.82rem; font-weight: 700; border: 1px solid transparent; background: #fff; color: #1f2937; }
        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
        .meeting-action-btn.info { background: #ecfeff; color: #0f766e; border-color: #a5f3fc; }
        .meeting-action-btn.dark { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
        .laporan-file-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            cursor: pointer;
        }
        .laporan-preview-frame {
            width: 100%;
            height: 72vh;
            border: none;
            border-radius: 10px;
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .content-header .container-fluid {
                display: block !important;
            }

            .content-header .btn {
                width: 100%;
                margin-top: 10px;
            }

            .laporan-mobile-table,
            .laporan-mobile-table thead,
            .laporan-mobile-table tbody,
            .laporan-mobile-table tr,
            .laporan-mobile-table th,
            .laporan-mobile-table td {
                display: block;
                width: 100%;
            }

            .laporan-mobile-table thead {
                display: none;
            }

            .laporan-mobile-table tbody tr:not(.meeting-action-row) {
                padding: 14px 14px 10px;
                border-bottom: 1px solid #e5e7eb;
            }

            .laporan-mobile-table td {
                padding: 0 0 10px;
                border: 0;
                width: 100% !important;
            }

            .laporan-mobile-table td:last-child {
                padding-bottom: 0;
            }

            .laporan-mobile-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.74rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .meeting-action-toggle-col,
            .laporan-file-col {
                width: 100%;
                white-space: normal;
            }

            .meeting-action-row td::before {
                content: none;
            }

            .meeting-action-panel {
                flex-direction: column;
                align-items: stretch;
            }

            .meeting-action-btn,
            .laporan-file-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">Laporan Rapat</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Daftar laporan aktif, preview, download, upload file final, dan pengarsipan.</div>
            </div>
            <a href="{{ route('rapat.laporan.arsip') }}" class="btn btn-outline-secondary btn-sm">Buka Arsip</a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card laporan-card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 laporan-mobile-table">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Laporan</th>
                        <th>Jenis</th>
                        <th>Rapat</th>
                        <th class="laporan-file-col">Final File</th>
                        <th>Status</th>
                        <th>Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporans as $laporan)
                        <tr>
                            <td class="meeting-action-toggle-col" data-label="Aksi">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td data-label="Laporan">
                                <div class="font-weight-bold">{{ $laporan->judul }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $laporan->deskripsi }}</div>
                            </td>
                            <td data-label="Jenis">{{ $laporan->jenis_label }}</td>
                            <td data-label="Rapat">
                                <div>{{ $laporan->rapat->judul }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $laporan->rapat->nomor_undangan }}</div>
                            </td>
                            <td class="laporan-file-col" data-label="Final File">
                                @if($laporan->file_path)
                                    <button type="button"
                                        class="laporan-file-link"
                                        onclick="openLaporanPreviewModal('{{ route('rapat.laporan.preview', $laporan) }}', '{{ addslashes($laporan->judul) }}')">
                                        <i class="fas fa-file-pdf"></i> Berkas
                                    </button>
                                @else
                                    <span class="badge badge-light border">Generator</span>
                                @endif
                            </td>
                            <td data-label="Status">{!! $laporan->status_badge !!}</td>
                            <td data-label="Diperbarui">{{ optional($laporan->updated_at)->timezone('Asia/Jayapura')->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="7">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan laporan</span>
                                    @if(auth()->user()->canAccessMeetingMinutes() && $laporan->jenis === 'tindak_lanjut')
                                        <a href="{{ route('rapat.laporan.edit', $laporan) }}" class="meeting-action-btn secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endif
                                    @if($laporan->is_ready || $laporan->file_path)
                                        <button type="button" class="meeting-action-btn primary" onclick="openLaporanPreviewModal('{{ route('rapat.laporan.preview', $laporan) }}', '{{ addslashes($laporan->judul) }}')">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                        <a href="{{ route('rapat.laporan.download', $laporan) }}" class="meeting-action-btn success">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    @else
                                        <span class="meeting-action-btn secondary"><i class="fas fa-clock"></i> Belum Siap</span>
                                    @endif
                                    @if(auth()->user()->canAccessMeetingMinutes())
                                        <button type="button" class="meeting-action-btn info" onclick="openUploadLaporanModal('{{ route('rapat.laporan.upload', $laporan) }}', '{{ $laporan->judul }}')">
                                            <i class="fas fa-upload"></i> Upload
                                        </button>
                                        <form action="{{ route('rapat.laporan.archive', $laporan) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="meeting-action-btn dark" onclick="return confirm('Arsipkan laporan ini?')">
                                                <i class="fas fa-archive"></i> Arsip
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada laporan aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="uploadLaporanModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="uploadLaporanForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload File Laporan Final</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-muted mb-2" id="uploadLaporanTarget"></div>
                        <div class="form-group">
                            <label>File Laporan</label>
                            <input type="file" name="laporan_file" class="form-control-file" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                        </div>
                        <div class="form-group mb-0">
                            <label>Deskripsi Upload</label>
                            <textarea name="deskripsi_upload" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="laporanPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="laporanPreviewTitle">Preview Berkas</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-2">
                    <iframe id="laporanPreviewFrame" class="laporan-preview-frame" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openUploadLaporanModal(actionUrl, title) {
            $('#uploadLaporanForm').attr('action', actionUrl);
            $('#uploadLaporanTarget').text(title);
            $('#uploadLaporanModal').modal('show');
        }

        function openLaporanPreviewModal(url, title) {
            $('#laporanPreviewTitle').text('Preview Berkas - ' + title);
            $('#laporanPreviewFrame').attr('src', url);
            $('#laporanPreviewModal').modal('show');
        }

        $(function () {
            $(document).on('click', '.meeting-action-toggle', function () {
                const $button = $(this);
                const $actionRow = $button.closest('tr').next('.meeting-action-row');
                const isOpen = $actionRow.is(':visible');

                $('.meeting-action-row').hide();
                $('.meeting-action-toggle').removeClass('is-open').text('+');

                if (!isOpen) {
                    $actionRow.show();
                    $button.addClass('is-open').text('-');
                }
            });

            $('#laporanPreviewModal').on('hidden.bs.modal', function () {
                $('#laporanPreviewFrame').attr('src', 'about:blank');
            });
        });
    </script>
@endpush
