@extends('layouts.app')
@section('title', 'Laporan Keterlambatan')
@section('page-title', 'Laporan Keterlambatan')

@section('content')
<div class="page-header">
    <div><h1>Laporan Keterlambatan</h1><p>Total {{ count($loans) }} transaksi terlambat</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('library.reports.lates', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('library.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" placeholder="Jatuh tempo dari" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" placeholder="Jatuh tempo sampai" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-modern mb-0">
            <thead>
                <tr><th>#</th><th>No. Pinjam</th><th>Anggota</th><th>Jatuh Tempo</th><th>Hari Terlambat</th><th>Buku</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($loans as $i => $loan)
                @php $daysLate = $loan->due_date->diffInDays(now()); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><code style="font-size:11.5px;">{{ $loan->loan_number }}</code></td>
                    <td style="font-weight:600;font-size:13px;">{{ $loan->member->name }}</td>
                    <td>{{ $loan->due_date->format('d/m/Y') }}</td>
                    <td><span class="badge bg-danger">{{ $daysLate }} hari</span></td>
                    <td>{{ $loan->loanItems->count() }} buku</td>
                    <td><span class="badge bg-{{ $loan->status_badge }}">{{ ucfirst($loan->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data keterlambatan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
