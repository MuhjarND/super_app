@extends('layouts.app')

@section('title', 'Kelola Bidang')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .admin-bidangs-top {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }

            .admin-bidangs-top .btn,
            .admin-bidangs-filter .btn {
                width: 100%;
            }

            .admin-bidangs-filter .row {
                display: block;
            }

            .admin-bidangs-filter .form-group,
            .admin-bidangs-filter .d-flex {
                margin-bottom: 10px;
            }

            .admin-bidangs-table,
            .admin-bidangs-table thead,
            .admin-bidangs-table tbody,
            .admin-bidangs-table tr,
            .admin-bidangs-table th,
            .admin-bidangs-table td {
                display: block;
                width: 100%;
            }

            .admin-bidangs-table thead {
                display: none;
            }

            .admin-bidangs-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e5e7eb;
            }

            .admin-bidangs-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .admin-bidangs-table td:last-child {
                padding-bottom: 0;
            }

            .admin-bidangs-table td::before {
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
            <div class="d-flex justify-content-between align-items-center admin-bidangs-top">
                <h1>Kelola Bidang</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createBidangModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Bidang
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.bidangs.index') }}" class="admin-bidangs-filter">
                <div class="row">
                    <div class="col-md-10 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama, kode, atau keterangan bidang">
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.bidangs.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-bidangs-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Keterangan</th>
                            <th>Jumlah User</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bidangs as $bidang)
                            <tr>
                                <td data-label="Nama">{{ $bidang->nama }}</td>
                                <td data-label="Kode">{{ $bidang->kode }}</td>
                                <td data-label="Keterangan">{{ $bidang->keterangan ?: '-' }}</td>
                                <td data-label="Jumlah User">{{ $bidang->users_count }}</td>
                                <td class="app-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                    <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal"
                                        data-target="#editBidangModal{{ $bidang->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.bidangs.destroy', $bidang) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus"
                                            onclick="return confirm('Hapus bidang ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editBidangModal{{ $bidang->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Bidang</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.bidangs.update', $bidang) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Nama Bidang</label>
                                                    <input type="text" name="nama" class="form-control" value="{{ $bidang->nama }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode</label>
                                                    <input type="text" name="kode" class="form-control" value="{{ $bidang->kode }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="4">{{ $bidang->keterangan }}</textarea>
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
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data bidang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $bidangs->links() }}
        </div>
    </div>

    <div class="modal fade" id="createBidangModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Bidang</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.bidangs.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Bidang</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Kode</label>
                            <input type="text" name="kode" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="4"></textarea>
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
