@extends('layouts.app')
@section('title', $book->title)
@section('page-title', 'Detail Buku')
@section('page-subtitle', $book->title)

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body p-4">
                <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                    style="width:180px;height:240px;object-fit:cover;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15);margin-bottom:16px;">
                <h5 class="fw-700" style="font-weight:700;">{{ $book->title }}</h5>
                <p class="text-muted mb-3">{{ $book->author }}</p>
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    @php $availableCopy = $book->copies->firstWhere('status', 'tersedia'); @endphp
                    @if($availableCopy)
                    <a href="{{ route('library.loans.create', ['copy_code' => $availableCopy->copy_code]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-book me-1"></i> Pinjam Buku
                    </a>
                    @endif
                    @if($canManageLibrary)<a href="{{ route('library.books.edit', $book) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('library.book-copies.create', ['book_id' => $book->id]) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Eksemplar
                    </a>@endif
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Informasi Buku</div>
            <div class="card-body">
                <table class="table table-sm" style="font-size:13px;">
                    <tr>
                        <td class="text-muted" style="width:40%;">ISBN</td>
                        <td>{{ $book->isbn ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Penerbit</td>
                        <td>{{ $book->publisher ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tahun</td>
                        <td>{{ $book->year ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kategori</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $book->category->name }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Rak</td>
                        <td>{{ $book->shelf ? $book->shelf->code . ' — ' . $book->shelf->name : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @if($book->description)
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-file-text me-2"></i>Deskripsi</div>
            <div class="card-body" style="font-size:14px;line-height:1.7;color:#475569;">
                {{ $book->description }}
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-collection me-2"></i>Daftar Eksemplar ({{ $book->copies->count() }})</div>
                <div class="d-flex gap-2">
                    @php $availCount = $book->copies->where('status','tersedia')->count(); @endphp
                    <span class="badge bg-success">{{ $availCount }} tersedia</span>
                    <span class="badge bg-warning text-dark">{{ $book->copies->where('status','dipinjam')->count() }} dipinjam</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Kode Eksemplar</th>
                            <th>Status</th>
                            @if($canManageLibrary)
                                <th>Peminjam Saat Ini</th>
                                <th>Barcode</th>
                            @endif
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($book->copies as $copy)
                        <tr>
                            <td><code style="font-size:12px;">{{ $copy->copy_code }}</code></td>
                            <td>
                                <span class="badge bg-{{ $copy->status_badge }} badge-status">
                                    {{ ucfirst($copy->status) }}
                                </span>
                            </td>
                            @if($canManageLibrary)
                            <td style="font-size:13px;">
                                @if($copy->activeLoanItem)
                                    <div>{{ $copy->activeLoanItem->loan->member->name }}</div>
                                    <small class="text-muted">s/d {{ $copy->activeLoanItem->loan->due_date->format('d M Y') }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('library.barcode.show', $copy) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-upc-scan"></i>
                                </a>
                            </td>
                            @endif
                            <td>
                                @if(!$canManageLibrary && $copy->status === 'tersedia')
                                    <a href="{{ route('library.loans.create', ['copy_code' => $copy->copy_code]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-book me-1"></i> Pinjam
                                    </a>
                                @endif
                                @if($canManageLibrary)<a href="{{ route('library.book-copies.edit', $copy) }}" class="btn btn-sm btn-icon btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('library.book-copies.destroy', $copy) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus eksemplar ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>@endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $canManageLibrary ? 5 : 3 }}" class="text-center py-4 text-muted">Belum ada eksemplar</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
