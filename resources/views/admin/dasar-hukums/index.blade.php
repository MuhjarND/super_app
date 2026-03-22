@extends('layouts.app')

@section('title', 'Kelola Dasar Hukum')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Kelola Dasar Hukum</h1>
                <button class="btn btn-primary" data-toggle="modal" data-target="#createDasarHukumModal">
                    <i class="fas fa-plus mr-1"></i> Tambah Dasar Hukum
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.dasar-hukums.index') }}">
                <div class="row">
                    <div class="col-md-7 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari tema, kata kunci, atau isi dasar hukum">
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
                        <a href="{{ route('admin.dasar-hukums.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Tema</th>
                            <th>Kategori Surat</th>
                            <th>Kata Kunci</th>
                            <th>Urutan</th>
                            <th>Status</th>
                            <th>Uraian</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dasarHukums as $item)
                            <tr>
                                <td>{{ $item->tema }}</td>
                                <td>{{ optional($item->kategoriSuratKode)->kode ? optional($item->kategoriSuratKode)->kode . ' - ' . optional($item->kategoriSuratKode)->nama : '-' }}</td>
                                <td>{{ $item->kata_kunci ?: '-' }}</td>
                                <td>{{ $item->urutan }}</td>
                                <td>
                                    <span class="badge badge-{{ $item->aktif ? 'success' : 'secondary' }}">
                                        {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit(strip_tags($item->uraian), 100) }}</td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                        data-target="#editDasarHukumModal{{ $item->id }}">Edit</button>
                                    <form action="{{ route('admin.dasar-hukums.destroy', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus dasar hukum ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>

                            <div class="modal fade" id="editDasarHukumModal{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Dasar Hukum</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.dasar-hukums.update', $item) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Tema</label>
                                                    <input type="text" name="tema" class="form-control" value="{{ $item->tema }}" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kategori Surat</label>
                                                    <select name="kategori_surat_kode_id" class="form-control">
                                                        <option value="">Semua / Umum</option>
                                                        @foreach($kategoriOptions as $option)
                                                            <option value="{{ $option->id }}" {{ (int) $item->kategori_surat_kode_id === (int) $option->id ? 'selected' : '' }}>
                                                                {{ $option->kode }} - {{ $option->nama }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kata Kunci</label>
                                                    <textarea name="kata_kunci" class="form-control" rows="2" placeholder="Pisahkan dengan koma, contoh: zona integritas, monev, reformasi birokrasi">{{ $item->kata_kunci }}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Uraian Dasar Hukum</label>
                                                    <textarea name="uraian" class="form-control" rows="5" required>{{ $item->uraian }}</textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Urutan</label>
                                                    <input type="number" name="urutan" class="form-control" value="{{ $item->urutan }}">
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" name="aktif" id="aktifDasarHukum{{ $item->id }}" class="form-check-input" {{ $item->aktif ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="aktifDasarHukum{{ $item->id }}">Aktif</label>
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
                                <td colspan="7" class="text-center text-muted py-4">Belum ada master dasar hukum.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $dasarHukums->links() }}
        </div>
    </div>

    <div class="modal fade" id="createDasarHukumModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Dasar Hukum</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.dasar-hukums.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tema</label>
                            <input type="text" name="tema" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori Surat</label>
                            <select name="kategori_surat_kode_id" class="form-control">
                                <option value="">Semua / Umum</option>
                                @foreach($kategoriOptions as $option)
                                    <option value="{{ $option->id }}">{{ $option->kode }} - {{ $option->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kata Kunci</label>
                            <textarea name="kata_kunci" class="form-control" rows="2" placeholder="Pisahkan dengan koma, contoh: zona integritas, monev, reformasi birokrasi"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Uraian Dasar Hukum</label>
                            <textarea name="uraian" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Urutan</label>
                            <input type="number" name="urutan" class="form-control" value="0">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="aktif" id="aktifCreateDasarHukum" class="form-check-input" checked>
                            <label class="form-check-label" for="aktifCreateDasarHukum">Aktif</label>
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
