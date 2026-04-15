@extends('layouts.app')

@section('title', 'Kelola Unit')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .admin-units-top {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }

            .admin-units-top .btn,
            .admin-units-filter .btn {
                width: 100%;
            }

            .admin-units-filter .row {
                display: block;
            }

            .admin-units-filter .form-group,
            .admin-units-filter .d-flex {
                margin-bottom: 10px;
            }

            .admin-units-table,
            .admin-units-table thead,
            .admin-units-table tbody,
            .admin-units-table tr,
            .admin-units-table th,
            .admin-units-table td {
                display: block;
                width: 100%;
            }

            .admin-units-table thead {
                display: none;
            }

            .admin-units-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e5e7eb;
            }

            .admin-units-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .admin-units-table td:last-child {
                padding-bottom: 0;
            }

            .admin-units-table td::before {
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
            <div class="d-flex justify-content-between align-items-center admin-units-top">
                <h1>Kelola Unit</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createUnitModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Unit
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.units.index') }}" class="admin-units-filter">
                <div class="row">
                    <div class="col-md-10 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama, kode, atau keterangan unit">
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.units.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-units-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Keterangan</th>
                            <th>User</th>
                            <th>Jabatan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($units as $unit)
                            <tr>
                                <td data-label="Nama">{{ $unit->nama }}</td>
                                <td data-label="Kode">{{ $unit->kode }}</td>
                                <td data-label="Keterangan">{{ $unit->keterangan ?: '-' }}</td>
                                <td data-label="User">{{ $unit->users_count }}</td>
                                <td data-label="Jabatan">{{ $unit->jabatans_count }}</td>
                                <td class="app-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                    <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal"
                                        data-target="#editUnitModal{{ $unit->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.units.destroy', $unit) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus"
                                            onclick="return confirm('Hapus unit ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editUnitModal{{ $unit->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Unit</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.units.update', $unit) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Nama Unit</label>
                                                    <input type="text" name="nama" class="form-control" value="{{ $unit->nama }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kode</label>
                                                    <input type="text" name="kode" class="form-control" value="{{ $unit->kode }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="4">{{ $unit->keterangan }}</textarea>
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
                                <td colspan="6" class="text-center text-muted py-4">Belum ada data unit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $units->links() }}
        </div>
    </div>

    <div class="modal fade" id="createUnitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Unit</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.units.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Unit</label>
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
