@extends('layouts.app')
@section('title', 'Detail Denda')
@section('page-title', 'Detail Denda')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2 text-danger"></i>Detail Denda</div>
            <div class="card-body">
                <div class="p-3 mb-3" style="background:{{ $fine->status == 'lunas' ? '#f0fdf4' : '#fef2f2' }};border-radius:12px;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size:24px;font-weight:800;color:{{ $fine->status == 'lunas' ? '#059669' : '#dc2626' }};">
                                Rp{{ number_format($fine->total_amount, 0, ',', '.') }}
                            </div>
                            <div style="font-size:13px;color:#64748b;">{{ $fine->days_late }} hari × Rp{{ number_format($fine->amount_per_day, 0, ',', '.') }}/hari</div>
                        </div>
                        <span class="badge bg-{{ $fine->status_badge }}" style="font-size:14px;padding:8px 16px;">
                            {{ $fine->status == 'lunas' ? 'LUNAS' : 'BELUM DIBAYAR' }}
                        </span>
                    </div>
                </div>

                <table class="table table-sm" style="font-size:13.5px;">
                    <tr>
                        <td class="text-muted" style="width:35%;">Anggota</td>
                        <td><strong>{{ $fine->member->name }}</strong> ({{ $fine->member->member_number }})</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Buku</td>
                        <td>{{ $fine->loanItem->bookCopy->book->title }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kode Eksemplar</td>
                        <td><code>{{ $fine->loanItem->bookCopy->copy_code }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">No. Peminjaman</td>
                        <td><code>{{ $fine->loanItem->loan->loan_number }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jatuh Tempo</td>
                        <td>{{ $fine->loanItem->loan->due_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Keterlambatan</td>
                        <td><span class="badge bg-danger">{{ $fine->days_late }} hari</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Denda/Hari</td>
                        <td>Rp{{ number_format($fine->amount_per_day, 0, ',', '.') }}</td>
                    </tr>
                    @if($fine->status == 'lunas')
                    <tr>
                        <td class="text-muted">Dibayar Pada</td>
                        <td>{{ $fine->paid_at->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Diterima Oleh</td>
                        <td>{{ optional($fine->paidByUser)->name ?? '—' }}</td>
                    </tr>
                    @endif
                </table>

                @if($canManageLibrary && $fine->status !== 'lunas')
                <form method="POST" action="{{ route('library.fines.pay', $fine) }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-success w-100"
                        onclick="return confirm('Konfirmasi pembayaran denda Rp{{ number_format($fine->total_amount, 0) }}?')">
                        <i class="bi bi-check-circle me-2"></i>
                        Konfirmasi Pembayaran — Rp{{ number_format($fine->total_amount, 0, ',', '.') }}
                    </button>
                </form>
                @endif

                <div class="mt-3">
                    <a href="{{ route('library.fines.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
