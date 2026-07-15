@extends('layouts.app')
@section('title', 'Tambah Eksemplar')
@section('page-title', 'Tambah Eksemplar Buku')
@section('page-subtitle', 'Generate kode eksemplar baru')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-collection me-2"></i>Form Tambah Eksemplar</div>
            <div class="card-body">
                <form method="POST" action="{{ route('library.book-copies.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Buku <span class="text-danger">*</span></label>
                        <select name="book_id" class="form-select @error('book_id') is-invalid @enderror" required>
                            <option value="">Pilih Buku</option>
                            @foreach($books as $book)
                            <option value="{{ $book->id }}"
                                {{ (old('book_id', optional($selectedBook)->id) == $book->id) ? 'selected' : '' }}>
                                {{ $book->title }} — {{ $book->author }}
                            </option>
                            @endforeach
                        </select>
                        @error('book_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Eksemplar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="copy_code" id="copy_code"
                                class="form-control @error('copy_code') is-invalid @enderror"
                                value="{{ old('copy_code', $nextCode) }}" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateCode()" title="Generate kode baru">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <small class="text-muted">Format: BK-TAHUN-NOMOR (auto-generate)</small>
                        @error('copy_code')<div class="text-danger" style="font-size:12px;">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="tersedia" {{ old('status')=='tersedia'?'selected':'' }}>Tersedia</option>
                            <option value="rusak" {{ old('status')=='rusak'?'selected':'' }}>Rusak</option>
                            <option value="hilang" {{ old('status')=='hilang'?'selected':'' }}>Hilang</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="alert alert-info" style="font-size:13px;">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Tambah Multiple Eksemplar:</strong> Isi jumlah eksemplar di bawah untuk menambah lebih dari satu sekaligus.
                            Kode akan di-generate otomatis untuk setiap eksemplar.
                        </div>
                        <label class="form-label">Jumlah Eksemplar</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="20">
                        <small class="text-muted">Jika lebih dari 1, kode eksemplar akan di-generate otomatis</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="Kondisi buku, nomor inventaris, dll...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>
                        <a href="{{ route('library.book-copies.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function generateCode() {
    const year = new Date().getFullYear();
    const rand = Math.floor(Math.random() * 900000) + 100000;
    document.getElementById('copy_code').value = `BK-${year}-${String(rand).padStart(6,'0')}`;
}
</script>
@endpush
