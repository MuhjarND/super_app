@extends('layouts.app')
@section('title', 'Detail Peminjaman')
@section('page-title', 'Detail Peminjaman')
@section('page-subtitle', $loan->loan_number)

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Info Transaksi</div>
            <div class="card-body">
                <table class="table table-sm" style="font-size:13px;">
                    <tr><td class="text-muted" style="width:45%;">No. Pinjam</td>
                        <td><code>{{ $loan->loan_number }}</code></td></tr>
                    <tr><td class="text-muted">Status</td>
                        <td><span class="badge bg-{{ $loan->status_badge }}">{{ ucfirst($loan->status) }}</span></td></tr>
                    <tr><td class="text-muted">Tgl. Pinjam</td>
                        <td>{{ $loan->loan_date->format('d M Y') }}</td></tr>
                    <tr><td class="text-muted">Jatuh Tempo</td>
                        <td>
                            {{ $loan->due_date->format('d M Y') }}
                            @if($loan->isOverdue())
                            <br><span class="badge bg-danger">+{{ $loan->due_date->diffInDays(now()) }}h terlambat</span>
                            @endif
                        </td></tr>
                    @if($loan->returnRecord)
                    <tr><td class="text-muted">Tgl. Kembali</td>
                        <td>{{ $loan->returnRecord->return_date->format('d M Y') }}</td></tr>
                    @endif
                    <tr><td class="text-muted">Petugas</td>
                        <td>{{ $loan->user->name }}</td></tr>
                    @if($loan->note)
                    <tr><td class="text-muted">Catatan</td>
                        <td>{{ $loan->note }}</td></tr>
                    @endif
                </table>

                @if($canManageLibrary && $loan->status !== 'dikembalikan')
                <a href="{{ route('library.returns.create', ['loan_id' => $loan->id]) }}"
                    class="btn btn-success btn-sm w-100 mt-2">
                    <i class="bi bi-arrow-return-left me-1"></i> Proses Pengembalian
                </a>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Anggota</div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-text-sm bg-primary bg-opacity-10 text-primary"
                        style="width:48px;height:48px;font-size:16px;font-weight:700;">
                        {{ strtoupper(substr($loan->member->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;">{{ $loan->member->name }}</div>
                        <code style="font-size:12px;">{{ $loan->member->member_number }}</code>
                        <div style="font-size:12.5px;color:#64748b;">{{ $loan->member->class_position ?? '' }}</div>
                    </div>
                </div>
                <a href="{{ route('library.members.show', $loan->member) }}" class="btn btn-outline-primary btn-sm w-100 mt-3">
                    <i class="bi bi-arrow-right me-1"></i> Lihat Profil Anggota
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-book me-2"></i>Buku Dipinjam</div>
            <div class="card-body p-0">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Buku</th>
                            <th>Kode Eksemplar</th>
                            <th>Status Kembali</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loan->loanItems as $item)
                        <tr>
                            <td>
                                <img src="{{ $item->bookCopy->book->cover_url }}"
                                    style="width:40px;height:54px;object-fit:cover;border-radius:6px;">
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13.5px;">{{ $item->bookCopy->book->title }}</div>
                                <small class="text-muted">{{ $item->bookCopy->book->author }}</small>
                            </td>
                            <td><code style="font-size:12px;">{{ $item->bookCopy->copy_code }}</code></td>
                            <td>
                                @if($item->returned_at)
                                    <span class="badge bg-success">Dikembalikan</span>
                                    <div style="font-size:11.5px;color:#64748b;">{{ $item->returned_at->format('d M Y') }}</div>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Kembali</span>
                                @endif
                            </td>
                            <td>
                                @if($item->fine)
                                    <div style="font-weight:600;color:{{ $item->fine->status == 'lunas' ? '#059669' : '#dc2626' }};">
                                        Rp{{ number_format($item->fine->total_amount, 0, ',', '.') }}
                                    </div>
                                    <span class="badge bg-{{ $item->fine->status_badge }}" style="font-size:11px;">
                                        {{ $item->fine->status == 'lunas' ? 'Lunas' : 'Belum Dibayar' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($loan->loanItems->filter(fn($i) => $i->fine)->count() > 0)
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Ringkasan Denda</div>
            <div class="card-body">
                @php $totalFine = $loan->loanItems->filter(fn($i) => $i->fine)->sum(fn($i) => $i->fine->total_amount); @endphp
                @php $paidFine = $loan->loanItems->filter(fn($i) => $i->fine && $i->fine->status=='lunas')->sum(fn($i) => $i->fine->total_amount); @endphp
                <div class="row g-2">
                    <div class="col-4 text-center">
                        <div style="font-size:18px;font-weight:700;color:#dc2626;">Rp{{ number_format($totalFine, 0, ',', '.') }}</div>
                        <div style="font-size:12px;color:#64748b;">Total Denda</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:18px;font-weight:700;color:#059669;">Rp{{ number_format($paidFine, 0, ',', '.') }}</div>
                        <div style="font-size:12px;color:#64748b;">Sudah Dibayar</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:18px;font-weight:700;color:#d97706;">Rp{{ number_format($totalFine - $paidFine, 0, ',', '.') }}</div>
                        <div style="font-size:12px;color:#64748b;">Belum Dibayar</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
