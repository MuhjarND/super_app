@extends('layouts.app')
@section('title', 'Laporan Buku')
@section('page-title', 'Laporan Data Buku')

@section('content')
<div class="page-header">
    <div>
        <h1>Laporan Data Buku</h1>
        <p>Total {{ count($books) }} buku</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('library.reports.books', array_merge(request()->query(), ['export' => 'pdf'])) }}"
            class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('library.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach(\App\Library\Category::orderBy('name')->get() as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
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
                        <th>#</th>
                        <th>Judul Buku</th>
                        <th>Penulis</th>
                        <th>Penerbit</th>
                        <th>Kategori</th>
                        <th>Rak</th>
                        <th>Eksemplar</th>
                        <th>ISBN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $i => $book)
                    <tr>
                        <td style="font-size:13px;">{{ $i + 1 }}</td>
                        <td style="font-weight:600;font-size:13.5px;">{{ $book->title }}</td>
                        <td style="font-size:13px;">{{ $book->author }}</td>
                        <td style="font-size:13px;">{{ $book->publisher ?? '—' }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $book->category->name }}</span></td>
                        <td style="font-size:13px;">{{ $book->shelf->name ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $book->copies_count }}</span></td>
                        <td style="font-size:12px;font-family:monospace;">{{ $book->isbn ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
