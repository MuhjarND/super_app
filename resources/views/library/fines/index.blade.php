@extends('layouts.app')
@section('title', 'Manajemen Denda')
@section('page-title', 'Denda Keterlambatan')
@section('page-subtitle', 'Kelola pembayaran denda anggota')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="stat-card stat-card-danger h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);"><i class="bi bi-exclamation-circle-fill"></i></div>
            <div class="stat-value">Rp{{ number_format($totalUnpaid, 0, ',', '.') }}</div>
            <div class="stat-label">Total Belum Dibayar</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card stat-card-success h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-value">Rp{{ number_format($totalPaid, 0, ',', '.') }}</div>
            <div class="stat-label">Total Sudah Dibayar</div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau nomor anggota..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="belum_dibayar" {{ request('status')=='belum_dibayar'?'selected':'' }}>Belum Dibayar</option>
                    <option value="lunas" {{ request('status')=='lunas'?'selected':'' }}>Lunas</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <a href="{{ route('library.fines.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>No. Pinjam</th>
                        <th>Keterlambatan</th>
                        <th>Total Denda</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fines as $fine)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text-sm bg-danger bg-opacity-10 text-danger" style="font-size:11px;font-weight:700;">
                                    {{ strtoupper(substr($fine->member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">{{ $fine->member->name }}</div>
                                    <small class="text-muted">{{ $fine->member->member_number }}</small>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:13px;max-width:160px;">{{ $fine->loanItem->bookCopy->book->title }}</td>
                        <td><code style="font-size:11.5px;">{{ $fine->loanItem->loan->loan_number }}</code></td>
                        <td>
                            <span class="badge bg-danger">{{ $fine->days_late }} hari</span>
                            <div style="font-size:12px;color:#64748b;">Rp{{ number_format($fine->amount_per_day, 0, ',', '.') }}/hari</div>
                        </td>
                        <td style="font-weight:700;color:#dc2626;font-size:14px;">
                            Rp{{ number_format($fine->total_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $fine->status_badge }} badge-status">
                                {{ $fine->status == 'lunas' ? 'Lunas' : 'Belum Dibayar' }}
                            </span>
                            @if($fine->status == 'lunas' && $fine->paid_at)
                            <div style="font-size:11px;color:#64748b;">{{ $fine->paid_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('library.fines.show', $fine) }}" class="btn btn-sm btn-icon btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageLibrary && $fine->status !== 'lunas')
                                <form method="POST" action="{{ route('library.fines.pay', $fine) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('Konfirmasi pembayaran denda?')">
                                        <i class="bi bi-check-circle me-1"></i>Bayar
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="bi bi-cash-coin d-block"></i>Belum ada data denda</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($fines->hasPages())
    <div class="card-footer bg-transparent">{{ $fines->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
