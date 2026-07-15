@extends('layouts.app')
@section('title', 'Laporan Denda')
@section('page-title', 'Laporan Denda')

@section('content')
<div class="page-header">
    <div><h1>Laporan Denda</h1><p>Total {{ count($fines) }} data denda</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('library.reports.fines', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('library.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="belum_dibayar" {{ request('status')=='belum_dibayar'?'selected':'' }}>Belum Dibayar</option>
                    <option value="lunas" {{ request('status')=='lunas'?'selected':'' }}>Lunas</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:20px;font-weight:800;color:#dc2626;">Rp{{ number_format($fines->where('status','belum_dibayar')->sum('total_amount'), 0, ',', '.') }}</div>
                <div style="font-size:13px;color:#64748b;">Belum Dibayar</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:20px;font-weight:800;color:#059669;">Rp{{ number_format($fines->where('status','lunas')->sum('total_amount'), 0, ',', '.') }}</div>
                <div style="font-size:13px;color:#64748b;">Sudah Dibayar</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size:20px;font-weight:800;color:#4f46e5;">Rp{{ number_format($fines->sum('total_amount'), 0, ',', '.') }}</div>
                <div style="font-size:13px;color:#64748b;">Total Denda</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-modern mb-0">
            <thead>
                <tr><th>#</th><th>Anggota</th><th>Buku</th><th>No. Pinjam</th><th>Terlambat</th><th>Total Denda</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($fines as $i => $fine)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-weight:600;font-size:13px;">{{ $fine->member->name }}</td>
                    <td style="font-size:13px;max-width:160px;">{{ $fine->loanItem->bookCopy->book->title }}</td>
                    <td><code style="font-size:11.5px;">{{ $fine->loanItem->loan->loan_number }}</code></td>
                    <td><span class="badge bg-danger">{{ $fine->days_late }} hari</span></td>
                    <td style="font-weight:700;color:#dc2626;">Rp{{ number_format($fine->total_amount, 0, ',', '.') }}</td>
                    <td><span class="badge bg-{{ $fine->status_badge }}">{{ $fine->status == 'lunas' ? 'Lunas' : 'Belum Dibayar' }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data denda</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
