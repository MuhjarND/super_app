@extends('layouts.app')
@section('title', 'Detail Pengembalian')
@section('page-title', 'Detail Pengembalian')
@section('page-subtitle', $return->loan->loan_number)

@section('content')
<div class="row g-3 justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-return-left me-2 text-success"></i>
                Pengembalian: <code>{{ $return->loan->loan_number }}</code>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3" style="background:#f0fdf4;border-radius:12px;">
                            <div class="text-muted mb-1" style="font-size:12px;font-weight:600;text-transform:uppercase;">Anggota</div>
                            <div style="font-weight:700;font-size:15px;">{{ $return->loan->member->name }}</div>
                            <code>{{ $return->loan->member->member_number }}</code>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3" style="background:#f8fafc;border-radius:12px;">
                            <div class="row" style="font-size:13px;">
                                <div class="col-6 mb-2">
                                    <span class="text-muted">Tgl. Pinjam</span><br>
                                    <strong>{{ $return->loan->loan_date->format('d M Y') }}</strong>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="text-muted">Jatuh Tempo</span><br>
                                    <strong>{{ $return->loan->due_date->format('d M Y') }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">Tgl. Kembali</span><br>
                                    <strong>{{ $return->return_date->format('d M Y') }}</strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">Petugas</span><br>
                                    <strong>{{ $return->user->name }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="mt-4 mb-3" style="font-weight:700;">Buku Dikembalikan</h6>
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Cover</th>
                            <th>Buku</th>
                            <th>Kode</th>
                            <th>Denda</th>
                            <th>Status Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($return->loan->loanItems as $item)
                        <tr>
                            <td><img src="{{ $item->bookCopy->book->cover_url }}"
                                style="width:40px;height:54px;object-fit:cover;border-radius:6px;"></td>
                            <td>
                                <div style="font-weight:600;font-size:13.5px;">{{ $item->bookCopy->book->title }}</div>
                                <small class="text-muted">{{ $item->bookCopy->book->author }}</small>
                            </td>
                            <td><code style="font-size:11.5px;">{{ $item->bookCopy->copy_code }}</code></td>
                            <td>
                                @if($item->fine)
                                    <strong style="color:#dc2626;">Rp{{ number_format($item->fine->total_amount, 0, ',', '.') }}</strong>
                                    <div style="font-size:12px;color:#64748b;">{{ $item->fine->days_late }} hari × Rp{{ number_format($item->fine->amount_per_day, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-muted">Tidak ada denda</span>
                                @endif
                            </td>
                            <td>
                                @if($item->fine)
                                    <span class="badge bg-{{ $item->fine->status_badge }}">
                                        {{ $item->fine->status == 'lunas' ? 'Lunas' : 'Belum Dibayar' }}
                                    </span>
                                    @if($canManageLibrary && $item->fine->status !== 'lunas')
                                    <form method="POST" action="{{ route('library.fines.pay', $item->fine) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success ms-1">
                                            <i class="bi bi-check-circle me-1"></i>Bayar
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    <span class="badge bg-success">Tepat Waktu</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-end mt-2">
                    <a href="{{ route('library.returns.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
