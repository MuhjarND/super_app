@extends('layouts.app')
@section('title', 'Barcode ' . $bookCopy->copy_code)
@section('page-title', 'Detail Barcode')
@section('page-subtitle', $bookCopy->copy_code)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card text-center">
            <div class="card-body p-5">
                <div class="mb-4">
                    <img src="{{ $bookCopy->book->cover_url }}" alt="{{ $bookCopy->book->title }}"
                        style="width:100px;height:130px;object-fit:cover;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.15);">
                </div>
                <h5 class="fw-700" style="font-weight:700;margin-bottom:4px;">{{ $bookCopy->book->title }}</h5>
                <p class="text-muted mb-4">{{ $bookCopy->book->author }}</p>

                <div style="background:#f8fafc;border-radius:16px;padding:24px;display:inline-block;min-width:280px;">
                    <div style="background:white;border-radius:10px;padding:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);">
                        <div style="font-size:11px;color:#64748b;margin-bottom:8px;font-weight:600;">KODE EKSEMPLAR</div>
                        {!! $barcode !!}
                        <div style="font-size:14px;font-weight:700;font-family:monospace;margin-top:8px;color:#1e293b;">
                            {{ $bookCopy->copy_code }}
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <span class="badge bg-{{ $bookCopy->status_badge }} badge-status">
                        {{ ucfirst($bookCopy->status) }}
                    </span>
                </div>

                <div class="d-flex gap-2 justify-content-center mt-4">
                    <a href="{{ route('library.barcode.print') }}?ids={{ $bookCopy->id }}" target="_blank" class="btn btn-primary">
                        <i class="bi bi-printer me-1"></i> Cetak Label
                    </a>
                    <a href="{{ route('library.barcode.svg', $bookCopy) }}" download="{{ $bookCopy->copy_code }}.svg"
                        class="btn btn-outline-secondary">
                        <i class="bi bi-download me-1"></i> Download SVG
                    </a>
                    <a href="{{ route('library.barcode.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
