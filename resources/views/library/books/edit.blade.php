@extends('layouts.app')
@section('title', 'Edit Buku')
@section('page-title', 'Edit Buku')
@section('page-subtitle', $book->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('library.books.update', $book) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Informasi Buku</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Judul Buku <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title', $book->title) }}" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Penulis <span class="text-danger">*</span></label>
                                    <input type="text" name="author" class="form-control @error('author') is-invalid @enderror"
                                        value="{{ old('author', $book->author) }}" required>
                                    @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Penerbit</label>
                                    <input type="text" name="publisher" class="form-control"
                                        value="{{ old('publisher', $book->publisher) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tahun Terbit</label>
                                    <input type="number" name="year" class="form-control"
                                        value="{{ old('year', $book->year) }}" min="1900" max="{{ date('Y') }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" name="isbn" class="form-control @error('isbn') is-invalid @enderror"
                                        value="{{ old('isbn', $book->isbn) }}">
                                    @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $book->category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rak/Lokasi</label>
                                    <select name="shelf_id" class="form-select">
                                        <option value="">Pilih Rak (opsional)</option>
                                        @foreach($shelves as $shelf)
                                        <option value="{{ $shelf->id }}" {{ old('shelf_id', $book->shelf_id) == $shelf->id ? 'selected' : '' }}>
                                            {{ $shelf->code }} — {{ $shelf->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="description" class="form-control" rows="4">{{ old('description', $book->description) }}</textarea>
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
                                @if($book->cover)
                                    <img src="{{ $book->cover_url }}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
                                @else
                                    <i class="bi bi-image" style="font-size:48px;color:#cbd5e1;"></i>
                                @endif
                            </div>
                            <input type="file" name="cover" id="coverInput" class="form-control" accept="image/*"
                                onchange="previewCover(this)">
                            <small class="text-muted d-block mt-2">Kosongkan jika tidak ingin mengubah cover</small>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                </button>
                                <a href="{{ route('library.books.show', $book) }}" class="btn btn-outline-secondary">
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
