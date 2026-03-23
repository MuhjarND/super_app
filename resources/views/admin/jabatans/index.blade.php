@extends('layouts.app')

@section('title', 'Kelola Jabatan')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Kelola Jabatan</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createJabatanModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Jabatan
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.jabatans.index') }}">
                <div class="row">
                    <div class="col-md-5 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama atau kode jabatan">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <select name="unit_id" class="form-control">
                            <option value="">Semua Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (string) ($filters['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <input type="number" name="level" class="form-control" value="{{ $filters['level'] ?? '' }}"
                            placeholder="Level">
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.jabatans.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Level</th>
                            <th>Parent</th>
                            <th>Unit</th>
                            <th>Dipakai</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jabatans as $jabatan)
                            <tr>
                                <td>{{ $jabatan->nama }}</td>
                                <td>{{ $jabatan->kode }}</td>
                                <td>{{ $jabatan->level }}</td>
                                <td>{{ optional($jabatan->parent)->nama ?? '-' }}</td>
                                <td>{{ optional($jabatan->unit)->nama ?? '-' }}</td>
                                <td>{{ $jabatan->users_count }}</td>
                                <td class="app-action-cell">
                                    <div class="app-action-group">
                                    <button class="app-icon-btn edit" data-toggle="modal"
                                        data-target="#editJabatanModal{{ $jabatan->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.jabatans.destroy', $jabatan) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete"
                                            onclick="return confirm('Hapus jabatan ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editJabatanModal{{ $jabatan->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Jabatan</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.jabatans.update', $jabatan) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Nama Jabatan</label>
                                                        <input type="text" name="nama" class="form-control" value="{{ $jabatan->nama }}" required>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Kode</label>
                                                        <input type="text" name="kode" class="form-control" value="{{ $jabatan->kode }}" required>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Level</label>
                                                        <input type="number" name="level" class="form-control" value="{{ $jabatan->level }}" required>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Parent Jabatan</label>
                                                        <select name="parent_id" class="form-control">
                                                            <option value="">-- Tidak Ada --</option>
                                                            @foreach($parents as $parent)
                                                                @if($parent->id !== $jabatan->id)
                                                                    <option value="{{ $parent->id }}" {{ (string) $jabatan->parent_id === (string) $parent->id ? 'selected' : '' }}>
                                                                        {{ $parent->nama }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Unit</label>
                                                        <select name="unit_id" class="form-control">
                                                            <option value="">-- Pilih Unit --</option>
                                                            @foreach($units as $unit)
                                                                <option value="{{ $unit->id }}" {{ (string) $jabatan->unit_id === (string) $unit->id ? 'selected' : '' }}>
                                                                    {{ $unit->nama }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
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
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data jabatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $jabatans->links() }}
        </div>
    </div>

    <div class="modal fade" id="createJabatanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Jabatan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.jabatans.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nama Jabatan</label>
                                <input type="text" name="nama" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Kode</label>
                                <input type="text" name="kode" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Level</label>
                                <input type="number" name="level" class="form-control" value="1" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Parent Jabatan</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">-- Tidak Ada --</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Unit</label>
                                <select name="unit_id" class="form-control">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
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
