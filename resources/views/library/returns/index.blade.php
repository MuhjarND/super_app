@extends('layouts.app')
@section('title', 'Pengembalian Buku')
@section('page-title', 'Data Pengembalian')
@section('page-subtitle', 'Riwayat pengembalian buku')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Pengembalian</h1>
        <p>Total {{ $returns->total() }} transaksi pengembalian</p>
    </div>
    @if($canManageLibrary)<a href="{{ route('library.returns.create') }}" class="btn btn-success">
        <i class="bi bi-arrow-return-left me-1"></i> Proses Pengembalian
    </a>@endif
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="No. pinjam, nama anggota..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <a href="{{ route('library.returns.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                        <th>No. Pinjam</th>
                        <th>Anggota</th>
                        <th>Jml. Buku</th>
                        <th>Tgl. Kembali</th>
                        <th>Terlambat</th>
                        <th>Denda</th>
                        <th>Petugas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                    @php
                        $daysLate = max(0, \Carbon\Carbon::parse($ret->loan->due_date)->diffInDays(\Carbon\Carbon::parse($ret->return_date), false) * -1);
                        $totalFine = $ret->loan->loanItems->sum(fn($i) => optional($i->fine)->total_amount ?? 0);
                    @endphp
                    <tr>
                        <td><code style="font-size:11.5px;">{{ $ret->loan->loan_number }}</code></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text-sm bg-success bg-opacity-10 text-success" style="font-size:11px;font-weight:700;">
                                    {{ strtoupper(substr($ret->loan->member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">{{ $ret->loan->member->name }}</div>
                                    <small class="text-muted">{{ $ret->loan->member->member_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $ret->loan->loanItems->count() }} buku</span></td>
                        <td style="font-size:13px;">{{ $ret->return_date->format('d M Y') }}</td>
                        <td>
                            @if($daysLate > 0)
                                <span class="badge bg-danger">{{ $daysLate }} hari</span>
                            @else
                                <span class="badge bg-success">Tepat waktu</span>
                            @endif
                        </td>
                        <td>
                            @if($totalFine > 0)
                                <span style="font-weight:600;color:#dc2626;">Rp{{ number_format($totalFine, 0, ',', '.') }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td style="font-size:13px;">{{ $ret->user->name }}</td>
                        <td>
                            <a href="{{ route('library.returns.show', $ret) }}" class="btn btn-sm btn-icon btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state"><i class="bi bi-arrow-return-left d-block"></i>Belum ada data pengembalian</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($returns->hasPages())
    <div class="card-footer bg-transparent">{{ $returns->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
