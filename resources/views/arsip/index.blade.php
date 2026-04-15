@extends('layouts.app')

@section('title', 'Arsip')

@push('styles')
<style>
    .archive-doc-number {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.92rem;
        line-height: 1.25;
        word-break: break-word;
    }

    .archive-doc-meta,
    .archive-sub-meta {
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.35;
    }

    .archive-subject {
        color: #334155;
        font-size: 0.88rem;
        line-height: 1.35;
        font-weight: 500;
        word-break: break-word;
    }

    .archive-recipient {
        color: #334155;
        font-size: 0.84rem;
        line-height: 1.35;
        word-break: break-word;
    }

    .archive-date-label {
        display: block;
        color: #94a3b8;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 3px;
    }

    .archive-date-value {
        display: block;
        color: #0f172a;
        font-size: 0.84rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .archive-type-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 3px 9px;
        font-size: 0.67rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 6px;
        background: #eef2ff;
        color: #4338ca;
    }

    @media (max-width: 767.98px) {
        .archive-filter-actions {
            margin-top: 10px;
        }

        .archive-mobile-table,
        .archive-mobile-table thead,
        .archive-mobile-table tbody,
        .archive-mobile-table tr,
        .archive-mobile-table th,
        .archive-mobile-table td {
            display: block;
            width: 100%;
        }

        .archive-mobile-table thead {
            display: none;
        }

        .archive-mobile-table tbody tr {
            padding: 14px 14px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .archive-mobile-table tbody tr:last-child {
            border-bottom: 0;
        }

        .archive-mobile-table td {
            min-width: 0 !important;
            max-width: none !important;
            padding: 0 0 10px;
            border: 0;
        }

        .archive-mobile-table td:last-child {
            padding-bottom: 0;
        }

        .archive-mobile-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .archive-preview-btn,
        .archive-preview-newtab {
            width: 100%;
        }
    }
</style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Arsip</h1>
                    <div class="text-muted" style="font-size: 0.82rem;">Arsip gabungan surat masuk, surat keluar, rapat/agenda, dan cuti.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header">
            <form method="GET" action="{{ route('arsip.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-6 form-group mb-md-0">
                        <label class="mb-1">Pencarian</label>
                        <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nomor, kategori, perihal, jenis dokumen, atau pembuat...">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label class="mb-1">Jenis Dokumen</label>
                        <select name="type" class="form-control">
                            <option value="">Semua Dokumen</option>
                            <option value="surat_masuk" {{ ($filters['type'] ?? '') === 'surat_masuk' ? 'selected' : '' }}>Surat Masuk</option>
                            <option value="surat_keluar" {{ ($filters['type'] ?? '') === 'surat_keluar' ? 'selected' : '' }}>Surat Keluar</option>
                            <option value="rapat" {{ ($filters['type'] ?? '') === 'rapat' ? 'selected' : '' }}>Rapat</option>
                            <option value="agenda" {{ ($filters['type'] ?? '') === 'agenda' ? 'selected' : '' }}>Agenda</option>
                            <option value="cuti" {{ ($filters['type'] ?? '') === 'cuti' ? 'selected' : '' }}>Cuti</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex archive-filter-actions" style="gap:8px;">
                        <button type="submit" class="btn btn-primary btn-block">Cari</button>
                        <a href="{{ route('arsip.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 archive-mobile-table">
                    <thead>
                        <tr>
                            <th>Dokumen</th>
                            <th>Uraian Dokumen</th>
                            <th>Tujuan / Penerima</th>
                            <th>Tanggal</th>
                            <th>Lampiran</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($archives as $item)
                            <tr>
                                <td style="min-width: 240px;" data-label="Dokumen">
                                    <span class="archive-type-badge">{{ $item['type_label'] }}</span>
                                    <div class="archive-doc-number">{{ $item['number'] }}</div>
                                    <div class="archive-doc-meta">{{ $item['category'] }}</div>
                                </td>
                                <td style="min-width: 300px; max-width: 360px;" data-label="Uraian Dokumen">
                                    <div class="archive-subject">{{ \Illuminate\Support\Str::limit($item['subject'], 110) }}</div>
                                    <div class="archive-sub-meta mt-1">Dibuat oleh: {{ $item['creator'] }}</div>
                                </td>
                                <td style="min-width: 200px;" data-label="Tujuan / Penerima">
                                    <div class="archive-recipient">{{ $item['recipient'] }}</div>
                                </td>
                                <td style="min-width: 165px;" data-label="Tanggal">
                                    <span class="archive-date-label">Tanggal Surat</span>
                                    <span class="archive-date-value">{{ $item['date'] }}</span>
                                    <span class="archive-date-label mt-2">Diinput</span>
                                    <span class="archive-date-value">{{ $item['input_date'] }}</span>
                                </td>
                                <td data-label="Lampiran">
                                    @if($item['file_url'])
                                        <button
                                            type="button"
                                            class="badge badge-primary app-status-badge border-0 archive-preview-btn"
                                            onclick="openArchivePreview('{{ $item['file_url'] }}', '{{ e($item['subject_plain']) }}')"
                                        >
                                            Berkas
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td data-label="Status">{!! $item['status_html'] !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada dokumen arsip yang dapat ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $archives->links() }}
        </div>
    </div>

    <div class="modal fade" id="archivePreviewModal" tabindex="-1" role="dialog" aria-labelledby="archivePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="archivePreviewModalLabel">Preview Berkas Arsip</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="px-3 py-2 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <div id="archivePreviewTitle" class="text-truncate pr-3" style="font-size: 0.86rem; font-weight: 600; color: #334155;"></div>
                        <a href="#" id="archivePreviewOpenNew" target="_blank" class="btn btn-sm btn-outline-primary archive-preview-newtab">
                            <i class="fas fa-external-link-alt mr-1"></i> Buka Tab Baru
                        </a>
                    </div>
                    <iframe
                        id="archivePreviewFrame"
                        src=""
                        style="width:100%;height:78vh;border:0;display:block;"
                        title="Preview Berkas Arsip"
                    ></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openArchivePreview(url, title) {
        $('#archivePreviewTitle').text(title || 'Preview Berkas Arsip');
        $('#archivePreviewOpenNew').attr('href', url);
        $('#archivePreviewFrame').attr('src', url);
        $('#archivePreviewModal').modal('show');
    }

    $('#archivePreviewModal').on('hidden.bs.modal', function () {
        $('#archivePreviewFrame').attr('src', '');
        $('#archivePreviewTitle').text('');
        $('#archivePreviewOpenNew').attr('href', '#');
    });
</script>
@endpush
