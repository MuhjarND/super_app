@extends('layouts.app')
@section('title', 'Barcode Eksemplar')
@section('page-title', 'Manajemen Barcode')
@section('page-subtitle', 'Generate dan cetak barcode eksemplar buku')

@section('content')
<div class="page-header">
    <div>
        <h1>Barcode Eksemplar</h1>
        <p>Kelola barcode untuk setiap eksemplar buku</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Cari kode eksemplar atau judul buku..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Cari</button>
                <a href="{{ route('library.barcode.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($copies as $copy)
    <div class="col-md-4 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body p-3">
                <div style="font-size:11px;color:#64748b;margin-bottom:4px;">{{ $copy->book->title }}</div>
                <code style="font-size:11px;display:block;margin-bottom:12px;background:#f1f5f9;padding:4px 8px;border-radius:6px;">
                    {{ $copy->copy_code }}
                </code>
                <div style="overflow:hidden;max-width:160px;margin:0 auto 12px;">
                    {!! '<img src="' . route('library.barcode.svg', $copy) . '" style="width:100%;height:auto;">' !!}
                </div>
                <span class="badge bg-{{ $copy->status_badge }} badge-status mb-2 d-block">
                    {{ ucfirst($copy->status) }}
                </span>
                <div class="d-flex gap-1 justify-content-center">
                    <a href="{{ route('library.barcode.show', $copy) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('library.barcode.print') }}?ids={{ $copy->id }}" target="_blank"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-printer"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="empty-state"><i class="bi bi-upc-scan d-block"></i>Belum ada eksemplar buku</div>
        </div>
    </div>
    @endforelse
</div>

@if($copies->hasPages())
<div class="mt-3">{{ $copies->links('pagination::bootstrap-4') }}</div>
@endif
@endsection
