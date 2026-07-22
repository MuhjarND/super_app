@extends('layouts.app')
@section('title', 'Tambah Buku')
@section('page-title', 'Tambah Buku Baru')
@section('page-subtitle', 'Input data buku koleksi perpustakaan')

@section('content')
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="font-weight-bold mb-1">Buku belum dapat disimpan.</div>
        <ul class="mb-0 pl-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('library.books.store') }}" enctype="multipart/form-data" data-loading-text="Menyimpan buku...">
            @csrf
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-book me-2"></i>Informasi Buku</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title') }}" placeholder="Masukkan judul buku" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Penulis <span class="text-danger">*</span></label>
                                    <input type="text" name="author" class="form-control @error('author') is-invalid @enderror"
                                        value="{{ old('author') }}" placeholder="Nama penulis" required>
                                    @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Penerbit</label>
                                    <input type="text" name="publisher" class="form-control"
                                        value="{{ old('publisher') }}" placeholder="Nama penerbit">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tahun Terbit</label>
                                    <input type="number" name="year" class="form-control"
                                        value="{{ old('year', date('Y')) }}" min="1900" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" name="isbn" class="form-control @error('isbn') is-invalid @enderror"
                                        value="{{ old('isbn') }}" placeholder="ISBN (opsional)">
                                    @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rak/Lokasi</label>
                                    <select name="shelf_id" class="form-select">
                                        <option value="">Pilih Rak (opsional)</option>
                                        @foreach($shelves as $shelf)
                                        <option value="{{ $shelf->id }}" {{ old('shelf_id') == $shelf->id ? 'selected' : '' }}>
                                            {{ $shelf->code }} — {{ $shelf->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="description" class="form-control" rows="4"
                                        placeholder="Ringkasan isi buku...">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-image me-2"></i>Cover Buku</div>
                        <div class="card-body text-center">
                            <div id="coverPreview" style="width:100%;height:200px;background:#f1f5f9;border-radius:12px;
                                display:flex;align-items:center;justify-content:center;margin-bottom:12px;overflow:hidden;">
                                <i class="bi bi-image" style="font-size:48px;color:#cbd5e1;"></i>
                            </div>
                            <input type="file" name="cover" id="coverInput" class="form-control" accept="image/*"
                                onchange="previewCover(this)">
                            <small class="text-muted d-block mt-2">JPG, PNG max 2MB</small>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Buku
                                </button>
                                <a href="{{ route('library.books.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewCover(input) {
    const preview = document.getElementById('coverPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
