@extends('layouts.app')
@section('title', $member->name)
@section('page-title', 'Profil Anggota')
@section('page-subtitle', $member->name)

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card text-center">
            <div class="card-body p-4">
                <div class="avatar-text-sm mx-auto mb-3" style="width:80px;height:80px;font-size:28px;font-weight:700;
                    background:{{ $member->gender == 'L' ? '#e0e7ff' : '#fce7f3' }};
                    color:{{ $member->gender == 'L' ? '#3730a3' : '#be185d' }};
                    border-radius:50%;">
                    {{ strtoupper(substr($member->name, 0, 1)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $member->name }}</h5>
                <p class="text-muted mb-2">{{ $member->class_position ?? 'Anggota' }}</p>
                <span class="badge bg-{{ $member->status == 'aktif' ? 'success' : 'secondary' }} badge-status">
                    {{ ucfirst($member->status) }}
                </span>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    @if($canManageLibrary)<a href="{{ route('library.members.edit', $member) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('library.loans.create', ['member_id' => $member->id]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-left-right me-1"></i> Pinjam
                    </a>@endif
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Info Anggota</div>
            <div class="card-body">
                <table class="table table-sm" style="font-size:13px;">
                    <tr><td class="text-muted" style="width:40%;">No. Anggota</td>
                        <td><code>{{ $member->member_number }}</code></td></tr>
                    <tr><td class="text-muted">Kelamin</td>
                        <td>{{ $member->gender == 'L' ? '♂ Laki-laki' : '♀ Perempuan' }}</td></tr>
                    <tr><td class="text-muted">HP</td><td>{{ $member->phone ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Email</td><td>{{ $member->email ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Alamat</td><td>{{ $member->address ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Valid s/d</td><td>{{ optional($member->valid_until)->format('d M Y') ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        @if($unpaidFines->count() > 0)
        <div class="card mt-3 border-danger" style="border:2px solid #ef4444!important;">
            <div class="card-header text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Denda Belum Dibayar</div>
            <div class="card-body">
                @foreach($unpaidFines as $fine)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div style="font-size:13px;">{{ $fine->loanItem->bookCopy->book->title }}</div>
                    <div style="font-weight:700;color:#dc2626;">Rp{{ number_format($fine->total_amount, 0, ',', '.') }}</div>
                </div>
                @endforeach
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Total</strong>
                    <strong class="text-danger">Rp{{ number_format($unpaidFines->sum('total_amount'), 0, ',', '.') }}</strong>
                </div>
                @if($canManageLibrary)<form method="POST" action="{{ route('library.fines.pay-all') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="member_id" value="{{ $member->id }}">
                    <button type="submit" class="btn btn-danger btn-sm w-100">
                        <i class="bi bi-check-circle me-1"></i> Lunasi Semua Denda
                    </button>
                </form>@endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        @if($activeLoans->count() > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-arrow-left-right text-warning me-2"></i>Peminjaman Aktif</div>
                <span class="badge bg-warning text-dark">{{ $activeLoans->count() }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr><th>No. Pinjam</th><th>Buku</th><th>Jatuh Tempo</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($activeLoans as $loan)
                        @foreach($loan->loanItems as $item)
                        <tr>
                            <td><code style="font-size:11px;">{{ $loan->loan_number }}</code></td>
                            <td style="font-size:13px;">{{ $item->bookCopy->book->title }}</td>
                            <td style="font-size:13px;">
                                {{ $loan->due_date->format('d M Y') }}
                                @if($loan->due_date->isPast())
                                <span class="badge bg-danger ms-1">Terlambat</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $loan->status_badge }}">{{ ucfirst($loan->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Peminjaman</div>
            <div class="card-body p-0">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr><th>No. Pinjam</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($loanHistory as $loan)
                        <tr>
                            <td><code style="font-size:11px;">{{ $loan->loan_number }}</code></td>
                            <td style="font-size:13px;">{{ $loan->loan_date->format('d M Y') }}</td>
                            <td style="font-size:13px;">{{ $loan->due_date->format('d M Y') }}</td>
                            <td><span class="badge bg-{{ $loan->status_badge }}">{{ ucfirst($loan->status) }}</span></td>
                            <td>
                                <a href="{{ route('library.loans.show', $loan) }}" class="btn btn-sm btn-icon btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada riwayat peminjaman</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($loanHistory->hasPages())
            <div class="card-footer bg-transparent">{{ $loanHistory->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
