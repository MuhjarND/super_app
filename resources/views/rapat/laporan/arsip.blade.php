@extends('layouts.app')

@section('title', 'Arsip Laporan Rapat')

@push('styles')
    <style>
        .meeting-action-toggle-col { width: 46px; }
        .meeting-action-toggle { width: 28px; height: 28px; border: none; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .meeting-action-toggle.is-open { background: linear-gradient(135deg, #475569, #64748b); }
        .meeting-action-row { display: none; }
        .meeting-action-row td { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 16px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .meeting-action-meta { color: #64748b; font-size: 0.82rem; margin-right: 10px; }
        .meeting-action-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 7px 12px; font-size: 0.82rem; font-weight: 700; border: 1px solid transparent; background: #fff; color: #1f2937; }
        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
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

            .laporan-arsip-table,
            .laporan-arsip-table thead,
            .laporan-arsip-table tbody,
            .laporan-arsip-table tr,
            .laporan-arsip-table th,
            .laporan-arsip-table td {
                display: block;
                width: 100%;
            }

            .laporan-arsip-table thead {
                display: none;
            }

            .laporan-arsip-table tbody tr:not(.meeting-action-row) {
                padding: 14px 14px 10px;
                border-bottom: 1px solid #e5e7eb;
            }

            .laporan-arsip-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .laporan-arsip-table td:last-child {
                padding-bottom: 0;
            }

            .laporan-arsip-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.74rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .meeting-action-toggle-col {
                width: 100%;
            }

            .meeting-action-row td::before {
                content: none;
            }

            .meeting-action-panel {
                flex-direction: column;
                align-items: stretch;
            }

            .meeting-action-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">Arsip Laporan</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Laporan yang sudah diarsipkan dan bisa dikembalikan ke daftar aktif.</div>
            </div>
            <a href="{{ route('rapat.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke Aktif</a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="border-radius:16px; border:1px solid #e5e7eb;">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 laporan-arsip-table">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Laporan</th>
                        <th>Jenis</th>
                        <th>Rapat</th>
                        <th>Diarsipkan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporans as $laporan)
                        <tr>
                            <td class="meeting-action-toggle-col" data-label="Aksi">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td data-label="Laporan">{{ $laporan->judul }}</td>
                            <td data-label="Jenis">{{ $laporan->jenis_label }}</td>
                            <td data-label="Rapat">{{ $laporan->rapat->judul }}</td>
                            <td data-label="Diarsipkan">{{ optional($laporan->archived_at)->timezone('Asia/Jayapura')->format('d/m/Y H:i') ?: '-' }}</td>
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="5">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan arsip</span>
                                    <button type="button" class="meeting-action-btn primary" onclick="openLaporanPreviewModal('{{ route('rapat.laporan.preview', $laporan) }}', '{{ addslashes($laporan->judul) }}')">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                    <form action="{{ route('rapat.laporan.unarchive', $laporan) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="meeting-action-btn success">
                                            <i class="fas fa-box-open"></i> Unarsip
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada arsip laporan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
