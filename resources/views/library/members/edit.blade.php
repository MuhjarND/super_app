@extends('layouts.app')
@section('title', 'Edit Anggota')
@section('page-title', 'Edit Anggota')
@section('page-subtitle', $member->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <form method="POST" action="{{ route('library.members.update', $member) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Data Anggota</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor Anggota <span class="text-danger">*</span></label>
                            <input type="text" name="member_number" class="form-control @error('member_number') is-invalid @enderror"
                                value="{{ old('member_number', $member->member_number) }}" required>
                            @error('member_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $member->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="L" {{ old('gender', $member->gender)=='L'?'selected':'' }}>Laki-laki</option>
                                <option value="P" {{ old('gender', $member->gender)=='P'?'selected':'' }}>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kelas / Jabatan</label>
                            <input type="text" name="class_position" class="form-control"
                                value="{{ old('class_position', $member->class_position) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif" {{ old('status', $member->status)=='aktif'?'selected':'' }}>Aktif</option>
                                <option value="nonaktif" {{ old('status', $member->status)=='nonaktif'?'selected':'' }}>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $member->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $member->email) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $member->address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Berlaku Sampai</label>
                            <input type="date" name="valid_until" class="form-control"
                                value="{{ old('valid_until', optional($member->valid_until)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto Baru</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('library.members.show', $member) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
