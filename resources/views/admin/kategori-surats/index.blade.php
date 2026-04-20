@extends('layouts.app')

@section('title', 'Kelola Kategori Surat')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .admin-kategori-top {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }

            .admin-kategori-top .btn,
            .admin-kategori-filter .btn {
                width: 100%;
            }

            .admin-kategori-filter .row {
                display: block;
            }

            .admin-kategori-filter .form-group,
            .admin-kategori-filter .d-flex {
                margin-bottom: 10px;
            }

            .admin-kategori-table,
            .admin-kategori-table thead,
            .admin-kategori-table tbody,
            .admin-kategori-table tr,
            .admin-kategori-table th,
            .admin-kategori-table td {
                display: block;
                width: 100%;
            }

            .admin-kategori-table thead {
                display: none;
            }

            .admin-kategori-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e8eaed;
            }

            .admin-kategori-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .admin-kategori-table td:last-child {
                padding-bottom: 0;
            }

            .admin-kategori-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.74rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center admin-kategori-top">
                <h1>Kelola Kategori Surat</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createKategoriModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Kategori
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.kategori-surats.index') }}" class="admin-kategori-filter">
                <div class="row">
                    <div class="col-md-7 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari kode, nama, atau keterangan kategori">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <select name="aktif" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="1" {{ (string) ($filters['aktif'] ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ (string) ($filters['aktif'] ?? '') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.kategori-surats.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-kategori-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategoriSurats as $kategori)
                            <tr>
                                <td data-label="Kode">{{ $kategori->kode }}</td>
                                <td data-label="Nama">{{ $kategori->nama }}</td>
                                <td data-label="Status">
                                    <span class="badge badge-{{ $kategori->aktif ? 'success' : 'secondary' }}">
                                        {{ $kategori->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td data-label="Keterangan">{{ $kategori->keterangan ?: '-' }}</td>
                                <td class="app-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                    <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal"
                                        data-target="#editKategoriModal{{ $kategori->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.kategori-surats.destroy', $kategori) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus"
                                            onclick="return confirm('Hapus kategori surat ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editKategoriModal{{ $kategori->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kategori Surat</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.kategori-surats.update', $kategori) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Kode</label>
                                                    <input type="text" name="kode" class="form-control" value="{{ $kategori->kode }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nama</label>
                                                    <input type="text" name="nama" class="form-control" value="{{ $kategori->nama }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="4">{{ $kategori->keterangan }}</textarea>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="aktif" id="aktif{{ $kategori->id }}" class="form-check-input" {{ $kategori->aktif ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="aktif{{ $kategori->id }}">Aktif</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data kategori surat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $kategoriSurats->links() }}
        </div>
    </div>

    <div class="modal fade" id="createKategoriModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Surat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.kategori-surats.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="aktif" id="aktifCreateKategori" class="form-check-input" checked>
                            <label class="form-check-label" for="aktifCreateKategori">Aktif</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
