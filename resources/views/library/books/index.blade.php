@extends('layouts.app')
@section('title', 'Data Buku')
@section('page-title', 'Data Buku')
@section('page-subtitle', $canManageLibrary ? 'Manajemen koleksi buku perpustakaan' : 'Cari dan pinjam buku yang tersedia')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Buku</h1>
        <p>Total {{ $books->total() }} buku ditemukan</p>
    </div>
    @if($canManageLibrary)<a href="{{ route('library.books.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Buku
    </a>@endif
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('library.books.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari judul, penulis, ISBN..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="shelf_id" class="form-select">
                    <option value="">Semua Rak</option>
                    @foreach($shelves as $shelf)
                    <option value="{{ $shelf->id }}" {{ request('shelf_id') == $shelf->id ? 'selected' : '' }}>
                        {{ $shelf->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-search"></i>
                </button>
                <a href="{{ route('library.books.index') }}" class="btn btn-outline-secondary flex-fill">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Rak</th>
                        <th>Eksemplar</th>
                        <th>Tersedia</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                    <tr>
                        <td>
                            <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                                style="width:40px;height:55px;object-fit:cover;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,.15);">
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:13.5px;max-width:200px;">{{ $book->title }}</div>
                            @if($book->isbn)
                            <small class="text-muted">ISBN: {{ $book->isbn }}</small>
                            @endif
                        </td>
                        <td style="font-size:13px;">{{ $book->author }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:12px;">
                                {{ $book->category->name }}
                            </span>
                        </td>
                        <td style="font-size:13px;">{{ $book->shelf->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                {{ $book->copies_count }}
                            </span>
                        </td>
                        <td>
                            @php $available = $book->copies->where('status', 'tersedia')->count(); @endphp
                            <span class="badge bg-{{ $available > 0 ? 'success' : 'danger' }} bg-opacity-10
                                text-{{ $available > 0 ? 'success' : 'danger' }}">
                                {{ $available }} tersedia
                            </span>
                        </td>
                        <td>
                            @php
                                $firstCopy = $book->copies->first();
                                $availableCopy = $book->copies->firstWhere('status', 'tersedia');
                            @endphp
                            <div class="d-flex gap-1">
                                <a href="{{ route('library.books.show', $book) }}" class="btn btn-sm btn-icon btn-outline-info" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageLibrary)<a href="{{ route('library.books.edit', $book) }}" class="btn btn-sm btn-icon btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>@endif
                                @if($availableCopy)
                                    <a href="{{ route('library.loans.create', ['copy_code' => $availableCopy->copy_code]) }}" class="btn btn-sm btn-icon btn-primary" title="Pinjam buku">
                                        <i class="bi bi-book"></i>
                                    </a>
                                @endif
                                @if($canManageLibrary && $firstCopy)
                                    <a href="{{ route('library.barcode.show', $firstCopy) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Barcode">
                                        <i class="bi bi-upc-scan"></i>
                                    </a>
                                @elseif($canManageLibrary)
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Tambahkan eksemplar untuk membuat barcode" disabled>
                                        <i class="bi bi-upc-scan"></i>
                                    </button>
                                @endif
                                @if($canManageLibrary)<form method="POST" action="{{ route('library.books.destroy', $book) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus buku ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>@endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-book d-block"></i>
                                Belum ada data buku.<br>
                                @if($canManageLibrary)<a href="{{ route('library.books.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Buku
                                </a>@endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($books->hasPages())
    <div class="card-footer bg-transparent">
        {{ $books->links('pagination::bootstrap-4') }}
    </div>
    @endif
</div>
@endsection
