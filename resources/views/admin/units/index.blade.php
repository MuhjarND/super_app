@extends('layouts.app')

@section('title', 'Kelola Unit')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Kelola Unit</h1>
                <button class="btn btn-primary" data-toggle="modal" data-target="#createUnitModal">
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
            <form method="GET" action="{{ route('admin.units.index') }}">
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
                <table class="table mb-0">
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
                                <td>{{ $unit->nama }}</td>
                                <td>{{ $unit->kode }}</td>
                                <td>{{ $unit->keterangan ?: '-' }}</td>
                                <td>{{ $unit->users_count }}</td>
                                <td>{{ $unit->jabatans_count }}</td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                        data-target="#editUnitModal{{ $unit->id }}">Edit</button>
                                    <form action="{{ route('admin.units.destroy', $unit) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus unit ini?')">Hapus</button>
                                    </form>
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
