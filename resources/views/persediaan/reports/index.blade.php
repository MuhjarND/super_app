@extends('layouts.app')

@section('title', 'Laporan Perawatan Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="inventory-module-title mb-1">Laporan Transaksi Perawatan</h1>
            <p class="inventory-module-subtitle mb-0">Filter transaksi, cetak PDF, dan ekspor Excel dari modul perawatan alat dan mesin.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('perawatan-alat-mesin.reports.pdf', request()->all()) }}" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
            <a href="{{ route('perawatan-alat-mesin.reports.excel', request()->all()) }}" class="btn btn-success"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        </div>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Laporan transaksi perawatan</div>
            <div class="inventory-module-board-subtitle">Filter rentang tanggal dan lihat rekap nominal per barang beserta daftar transaksi.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="inventory-module-panel mb-4">
                <div class="inventory-module-panel-body">
                    <form method="GET" class="form-row align-items-end">
                        <div class="form-group col-md-3"><label>Dari</label><input type="date" name="from" value="{{ $filters['from'] }}" class="form-control"></div>
                        <div class="form-group col-md-3"><label>Sampai</label><input type="date" name="to" value="{{ $filters['to'] }}" class="form-control"></div>
                        <div class="form-group col-md-3"><button class="btn app-create-btn btn-block">Terapkan Filter</button></div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Rekap Nominal per Barang</div>
                            <p class="inventory-module-panel-subtitle">Nominal akumulasi perawatan per barang induk.</p>
                        </div>
                        <div class="inventory-module-panel-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($byItem as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="font-weight-600">{{ optional($row->item)->name ?: '-' }}</div>
                                            <small class="inventory-module-muted">{{ optional($row->item)->code ?: '-' }}</small>
                                        </div>
                                        <span class="inventory-module-chip">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Belum ada data.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Daftar Transaksi</div>
                            <p class="inventory-module-panel-subtitle">Histori transaksi sesuai filter yang sedang dipakai.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-module-table mb-0">
                                <thead><tr><th>Tanggal</th><th>Barang</th><th>Sub Barang</th><th>Nominal</th><th>Deskripsi</th></tr></thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                        <tr>
                                            <td>{{ optional($transaction->transaction_date)->format('d-m-Y') }}</td>
                                            <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                                            <td>{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                                            <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                            <td>{{ $transaction->description }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot><tr><th colspan="3">Total</th><th colspan="2">Rp {{ number_format($totalAmount, 0, ',', '.') }}</th></tr></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
