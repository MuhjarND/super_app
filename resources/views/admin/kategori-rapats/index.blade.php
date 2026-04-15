@extends('layouts.app')

@section('title', 'Kelola Kategori Rapat')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Kelola Kategori Rapat</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createKategoriRapatModal">
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
            <form method="GET" action="{{ route('admin.kategori-rapats.index') }}">
                <div class="row">
                    <div class="col-md-5 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari kode, nama, atau keterangan">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <select name="aktif" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="1" {{ (string) ($filters['aktif'] ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ (string) ($filters['aktif'] ?? '') === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="butuh_pakaian" class="form-control">
                            <option value="">Semua Pakaian</option>
                            <option value="1" {{ (string) ($filters['butuh_pakaian'] ?? '') === '1' ? 'selected' : '' }}>Butuh Pakaian</option>
                            <option value="0" {{ (string) ($filters['butuh_pakaian'] ?? '') === '0' ? 'selected' : '' }}>Tanpa Pakaian</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.kategori-rapats.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Pakaian</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kategoriRapats as $kategori)
                            <tr>
                                <td data-label="Kode">{{ $kategori->kode }}</td>
                                <td data-label="Nama">{{ $kategori->nama }}</td>
                                <td data-label="Pakaian">
                                    <span class="badge badge-{{ $kategori->butuh_pakaian ? 'warning' : 'secondary' }}">
                                        {{ $kategori->butuh_pakaian ? 'Wajib' : 'Tidak' }}
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <span class="badge badge-{{ $kategori->aktif ? 'success' : 'secondary' }}">
                                        {{ $kategori->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td data-label="Keterangan">{{ $kategori->keterangan ?: '-' }}</td>
                                <td class="app-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                    <button class="app-icon-btn edit" data-toggle="modal"
                                        data-target="#editKategoriRapatModal{{ $kategori->id }}" data-mobile-label="Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.kategori-rapats.destroy', $kategori) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete"
                                            onclick="return confirm('Hapus kategori rapat ini?')" data-mobile-label="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editKategoriRapatModal{{ $kategori->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kategori Rapat</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.kategori-rapats.update', $kategori) }}">
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
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" name="butuh_pakaian" id="butuhPakaian{{ $kategori->id }}" class="form-check-input" {{ $kategori->butuh_pakaian ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="butuhPakaian{{ $kategori->id }}">Butuh Pakaian</label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="aktif" id="aktifKategoriRapat{{ $kategori->id }}" class="form-check-input" {{ $kategori->aktif ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="aktifKategoriRapat{{ $kategori->id }}">Aktif</label>
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
                                <td colspan="6" class="text-center text-muted py-4">Belum ada kategori rapat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $kategoriRapats->links() }}
        </div>
    </div>

    <div class="modal fade" id="createKategoriRapatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Rapat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.kategori-rapats.store') }}">
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
                        <div class="form-check mb-2">
                            <input type="checkbox" name="butuh_pakaian" id="createButuhPakaian" class="form-check-input">
                            <label class="form-check-label" for="createButuhPakaian">Butuh Pakaian</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="aktif" id="createAktifKategoriRapat" class="form-check-input" checked>
                            <label class="form-check-label" for="createAktifKategoriRapat">Aktif</label>
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
