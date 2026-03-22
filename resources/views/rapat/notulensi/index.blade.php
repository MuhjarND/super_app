@extends('layouts.app')

@section('title', 'Notulensi')

@push('styles')
    <style>
        .notulensi-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .notulensi-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .meeting-action-toggle-col {
            width: 46px;
        }

        .meeting-action-toggle {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .meeting-action-toggle.is-open {
            background: linear-gradient(135deg, #475569, #64748b);
        }

        .meeting-action-row {
            display: none;
        }

        .meeting-action-row td {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 12px 16px;
        }

        .meeting-action-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .meeting-action-meta {
            color: #64748b;
            font-size: 0.82rem;
            margin-right: 10px;
        }

        .meeting-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid transparent;
            background: #fff;
            color: #1f2937;
        }

        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .meeting-action-btn.dark { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="mb-1">Notulensi</h1>
                    <div class="text-muted" style="font-size: 0.82rem;">Daftar rapat yang belum memiliki notulensi dan yang sudah selesai dikerjakan.</div>
                </div>
                <a href="{{ route('rapat.notulensi.follow-ups') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-tasks mr-1"></i> Tindak Lanjut
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card notulensi-card mb-3">
        <div class="card-header bg-white">
            <div class="notulensi-section-title">Belum Ada</div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Rapat</th>
                        <th>Waktu WIT</th>
                        <th>Kategori</th>
                        <th>Pembuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($belumAda as $rapat)
                        <tr>
                            <td class="meeting-action-toggle-col">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $rapat->judul }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $rapat->nomor_undangan }}</div>
                            </td>
                            <td>
                                <div>{{ optional($rapat->tanggal)->translatedFormat('d M Y') }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                            </td>
                            <td>{{ $rapat->kategori_surat_label }}</td>
                            <td>{{ optional($rapat->creator)->name ?: '-' }}</td>
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="5">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan notulensi</span>
                                    <a href="{{ route('rapat.notulensi.create', $rapat) }}" class="meeting-action-btn primary">
                                        <i class="fas fa-file-signature"></i> Buat
                                    </a>
                                    <button type="button" class="meeting-action-btn success" onclick="openUploadModal('{{ route('rapat.notulensi.upload-from-rapat', $rapat) }}', '{{ $rapat->judul }}')">
                                        <i class="fas fa-upload"></i> Upload File
                                    </button>
                                    <form action="{{ route('rapat.notulensi.skip', $rapat) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="meeting-action-btn dark" onclick="return confirm('Tandai rapat ini tanpa notulen?')">
                                            <i class="fas fa-ban"></i> Tanpa Notulen
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Semua rapat pada daftar ini sudah memiliki notulensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card notulensi-card">
        <div class="card-header bg-white">
            <div class="notulensi-section-title">Sudah Ada</div>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Rapat</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Notulis</th>
                        <th>Tindak Lanjut</th>
                        <th>Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sudahAda as $rapat)
                        <tr>
                            <td class="meeting-action-toggle-col">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $rapat->judul }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $rapat->nomor_undangan }}</div>
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $rapat->notulensi->mode)) }}</td>
                            <td>{!! $rapat->notulensi->status_badge !!}</td>
                            <td>{{ optional($rapat->notulensi->notulis)->name ?: '-' }}</td>
                            <td>{{ $rapat->notulensi->tindakLanjuts->where('status', 'pending')->count() }} pending</td>
                            <td>{{ optional($rapat->notulensi->updated_at)->timezone('Asia/Jayapura')->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="7">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan notulensi</span>
                                    <a href="{{ route('rapat.notulensi.edit', $rapat->notulensi) }}" class="meeting-action-btn primary">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <a href="{{ route('rapat.notulensi.pdf', $rapat->notulensi) }}" target="_blank" class="meeting-action-btn success">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    @if($rapat->notulensi->file_path)
                                        <a href="{{ route('rapat.notulensi.file', $rapat->notulensi) }}" target="_blank" class="meeting-action-btn secondary">
                                            <i class="fas fa-paperclip"></i> File
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada notulensi yang dibuat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="uploadNotulensiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="uploadNotulensiForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload File Notulensi</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 text-muted" id="uploadNotulensiTarget"></div>
                        <div class="form-group">
                            <label>File Notulensi</label>
                            <input type="file" name="notulensi_file" class="form-control-file" accept=".pdf,.doc,.docx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Upload dan Selesaikan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openUploadModal(actionUrl, title) {
            $('#uploadNotulensiForm').attr('action', actionUrl);
            $('#uploadNotulensiTarget').text(title);
            $('#uploadNotulensiModal').modal('show');
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
        });
    </script>
@endpush
