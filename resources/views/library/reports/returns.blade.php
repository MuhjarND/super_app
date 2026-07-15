@extends('layouts.app')
@section('title', 'Laporan Pengembalian')
@section('page-title', 'Laporan Pengembalian')

@section('content')
<div class="page-header">
    <div><h1>Laporan Pengembalian</h1><p>Total {{ count($returns) }} pengembalian</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('library.reports.returns', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('library.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
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
<div class="card">
    <div class="card-body p-0">
        <table class="table table-modern mb-0">
            <thead>
                <tr><th>#</th><th>No. Pinjam</th><th>Anggota</th><th>Tgl. Kembali</th><th>Jml. Buku</th><th>Terlambat</th><th>Denda</th></tr>
            </thead>
            <tbody>
                @forelse($returns as $i => $ret)
                @php
                    $days = max(0, \Carbon\Carbon::parse($ret->loan->due_date)->diffInDays(\Carbon\Carbon::parse($ret->return_date), false) * -1);
                    $fine = $ret->loan->loanItems->sum(fn($it) => optional($it->fine)->total_amount ?? 0);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><code style="font-size:11.5px;">{{ $ret->loan->loan_number }}</code></td>
                    <td style="font-weight:600;font-size:13px;">{{ $ret->loan->member->name }}</td>
                    <td>{{ $ret->return_date->format('d/m/Y') }}</td>
                    <td>{{ $ret->loan->loanItems->count() }}</td>
                    <td>
                        @if($days > 0)
                            <span class="badge bg-danger">{{ $days }} hari</span>
                        @else
                            <span class="badge bg-success">Tepat waktu</span>
                        @endif
                    </td>
                    <td>{{ $fine > 0 ? 'Rp' . number_format($fine, 0, ',', '.') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
