@extends('layouts.app')
@section('title', 'Data Peminjaman')
@section('page-title', 'Peminjaman Buku')
@section('page-subtitle', 'Kelola transaksi peminjaman')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Peminjaman</h1>
        <p>Total {{ $loans->total() }} transaksi</p>
    </div>
    @if($canManageLibrary)<a href="{{ route('library.loans.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Pinjam Buku
    </a>@endif
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="No. pinjam, nama anggota..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="dipinjam" {{ request('status')=='dipinjam'?'selected':'' }}>Dipinjam</option>
                    <option value="dikembalikan" {{ request('status')=='dikembalikan'?'selected':'' }}>Dikembalikan</option>
                    <option value="terlambat" {{ request('status')=='terlambat'?'selected':'' }}>Terlambat</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" placeholder="Dari" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" placeholder="Sampai" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <a href="{{ route('library.loans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                        <th>Buku Dipinjam</th>
                        <th>Tgl. Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td><code style="font-size:11.5px;">{{ $loan->loan_number }}</code></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text-sm bg-primary bg-opacity-10 text-primary" style="font-size:11px;font-weight:700;">
                                    {{ strtoupper(substr($loan->member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">{{ $loan->member->name }}</div>
                                    <small class="text-muted">{{ $loan->member->member_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                {{ $loan->loanItems->count() }} buku
                            </span>
                        </td>
                        <td style="font-size:13px;">{{ $loan->loan_date->format('d M Y') }}</td>
                        <td style="font-size:13px;">
                            {{ $loan->due_date->format('d M Y') }}
                            @if($loan->isOverdue())
                            <br><small class="text-danger fw-bold">+{{ $loan->due_date->diffInDays(now()) }}h terlambat</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $loan->status_badge }} badge-status">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('library.loans.show', $loan) }}" class="btn btn-sm btn-icon btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageLibrary && $loan->status !== 'dikembalikan')
                                <a href="{{ route('library.returns.create', ['loan_id' => $loan->id]) }}"
                                    class="btn btn-sm btn-icon btn-outline-success" title="Kembalikan">
                                    <i class="bi bi-arrow-return-left"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-arrow-left-right d-block"></i>Belum ada transaksi peminjaman
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($loans->hasPages())
    <div class="card-footer bg-transparent">{{ $loans->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
