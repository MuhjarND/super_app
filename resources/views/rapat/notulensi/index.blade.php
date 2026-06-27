@extends('layouts.app')

@section('title', 'Notulensi')

@push('styles')
    <style>
        .notulensi-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .notulensi-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            color: #0f172a;
        }

        .notulensi-action-cell {
            width: 132px;
            vertical-align: middle !important;
        }

        .notulensi-action-cell .app-action-group {
            flex-wrap: nowrap;
            justify-content: flex-end;
        }

        .notulensi-action-cell form {
            margin: 0;
        }

        @media (max-width: 767.98px) {
            .notulensi-action-cell {
                width: auto;
            }

            .notulensi-action-cell .app-action-group {
                flex-wrap: wrap;
                justify-content: flex-start;
            }
        }
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
                        <th>Rapat</th>
                        <th>Waktu WIT</th>
                        <th>Kategori</th>
                        <th>Pembuat</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($belumAda as $rapat)
                        <tr>
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
                            <td class="app-action-cell notulensi-action-cell" data-label="Aksi">
                                <div class="app-action-group">
                                    <a href="{{ route('rapat.notulensi.create', $rapat) }}" class="app-icon-btn process" data-mobile-label="Buat" title="Buat notulensi">
                                        <i class="fas fa-file-signature"></i>
                                    </a>
                                    <button type="button" class="app-icon-btn upload" data-mobile-label="Upload" title="Upload file" onclick='openUploadModal("{{ route('rapat.notulensi.upload-from-rapat', $rapat) }}", @json($rapat->judul))'>
                                        <i class="fas fa-upload"></i>
                                    </button>
                                    <form action="{{ route('rapat.notulensi.skip', $rapat) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="app-icon-btn cancel" data-mobile-label="Tanpa Notulen" title="Tandai tanpa notulen" onclick="return confirm('Tandai rapat ini tanpa notulen?')">
                                            <i class="fas fa-ban"></i>
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
                        <th>Rapat</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Notulis</th>
                        <th>Tindak Lanjut</th>
                        <th>Diperbarui</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sudahAda as $rapat)
                        <tr>
                            <td>
                                <div class="font-weight-bold">{{ $rapat->judul }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $rapat->nomor_undangan }}</div>
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $rapat->notulensi->mode)) }}</td>
                            <td>{!! $rapat->notulensi->status_badge !!}</td>
                            <td>{{ optional($rapat->notulensi->notulis)->name ?: '-' }}</td>
                            <td>{{ $rapat->notulensi->tindakLanjuts->where('status', 'pending')->count() }} pending</td>
                            <td>{{ optional($rapat->notulensi->updated_at)->timezone('Asia/Jayapura')->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="app-action-cell notulensi-action-cell" data-label="Aksi">
                                <div class="app-action-group">
                                    <a href="{{ route('rapat.notulensi.edit', $rapat->notulensi) }}" class="app-icon-btn edit" data-mobile-label="Edit" title="Edit notulensi">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="{{ route('rapat.notulensi.pdf', $rapat->notulensi) }}" target="_blank" class="app-icon-btn pdf" data-mobile-label="PDF" title="Buka PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    @if($rapat->notulensi->file_path)
                                        <a href="{{ route('rapat.notulensi.file', $rapat->notulensi) }}" target="_blank" class="app-icon-btn file" data-mobile-label="File" title="Buka file">
                                            <i class="fas fa-paperclip"></i>
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

    </script>
@endpush
