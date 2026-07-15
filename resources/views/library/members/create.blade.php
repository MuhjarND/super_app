@extends('layouts.app')
@section('title', 'Tambah Anggota')
@section('page-title', 'Tambah Anggota Baru')
@section('page-subtitle', 'Daftarkan anggota perpustakaan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form method="POST" action="{{ route('library.members.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header"><i class="bi bi-person-plus me-2"></i>Data Anggota</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor Anggota <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="member_number" class="form-control @error('member_number') is-invalid @enderror"
                                    value="{{ old('member_number', $nextNumber) }}" required>
                                <span class="input-group-text bg-light" title="Auto-generate">
                                    <i class="bi bi-magic" style="color:#4f46e5;"></i>
                                </span>
                            </div>
                            @error('member_number')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" placeholder="Nama lengkap anggota" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                <option value="">Pilih</option>
                                <option value="L" {{ old('gender')=='L'?'selected':'' }}>Laki-laki</option>
                                <option value="P" {{ old('gender')=='P'?'selected':'' }}>Perempuan</option>
                            </select>
                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kelas / Jabatan</label>
                            <input type="text" name="class_position" class="form-control"
                                value="{{ old('class_position') }}" placeholder="Mis: X IPA 1 / Guru">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="aktif" selected>Aktif</option>
                                <option value="nonaktif">Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ old('phone') }}" placeholder="Nomor telepon/HP">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email') }}" placeholder="Alamat email (opsional)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2"
                                placeholder="Alamat lengkap...">{{ old('address') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Berlaku Sampai</label>
                            <input type="date" name="valid_until" class="form-control"
                                value="{{ old('valid_until') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Anggota
                    </button>
                    <a href="{{ route('library.members.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
