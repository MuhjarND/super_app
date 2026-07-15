@extends('layouts.app')
@section('title', 'Edit Eksemplar')
@section('page-title', 'Edit Eksemplar')
@section('page-subtitle', $bookCopy->copy_code)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil me-2"></i>Edit Eksemplar</div>
            <div class="card-body">
                <form method="POST" action="{{ route('library.book-copies.update', $bookCopy) }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Buku <span class="text-danger">*</span></label>
                        <select name="book_id" class="form-select" required>
                            @foreach($books as $book)
                            <option value="{{ $book->id }}" {{ old('book_id', $bookCopy->book_id) == $book->id ? 'selected' : '' }}>
                                {{ $book->title }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kode Eksemplar <span class="text-danger">*</span></label>
                        <input type="text" name="copy_code" class="form-control @error('copy_code') is-invalid @enderror"
                            value="{{ old('copy_code', $bookCopy->copy_code) }}" required>
                        @error('copy_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="tersedia" {{ old('status', $bookCopy->status)=='tersedia'?'selected':'' }}>Tersedia</option>
                            <option value="dipinjam" {{ old('status', $bookCopy->status)=='dipinjam'?'selected':'' }}>Dipinjam</option>
                            <option value="rusak" {{ old('status', $bookCopy->status)=='rusak'?'selected':'' }}>Rusak</option>
                            <option value="hilang" {{ old('status', $bookCopy->status)=='hilang'?'selected':'' }}>Hilang</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $bookCopy->notes) }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
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
