@extends('layouts.app')

@section('title', 'Laporan Perawatan')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="inventory-module-title mb-0">Laporan Perawatan</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('perawatan-alat-mesin.reports.pdf', request()->all()) }}" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
            <a href="{{ route('perawatan-alat-mesin.reports.excel', request()->all()) }}" class="btn btn-sm btn-success"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        </div>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-chart-line text-muted mr-1" style="font-size:.82rem"></i> Rekap Transaksi Perawatan</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="inventory-module-panel mb-3">
                <div class="inventory-module-panel-body py-2">
                    <form method="GET" class="form-row align-items-end">
                        <div class="form-group col-md-3 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1">Dari</label>
                            <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-3 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1">Sampai</label>
                            <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="form-group col-md-3 mb-0">
                            <button class="btn app-create-btn btn-sm btn-block">Terapkan Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-3">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title"><i class="fas fa-tags text-muted mr-1" style="font-size:.78rem"></i> Rekap Nominal per Barang</div>
                        </div>
                        <div class="inventory-module-panel-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($byItem as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" style="font-size:.88rem">
                                        <div>
                                            <div class="font-weight-600">{{ optional($row->item)->name ?: '-' }}</div>
                                            <small class="inventory-module-muted">{{ optional($row->item)->code ?: '-' }}</small>
                                        </div>
                                        <span class="inventory-module-chip">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center py-3"><span class="inventory-module-muted">Belum ada data</span></li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mb-3">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title"><i class="fas fa-list text-muted mr-1" style="font-size:.78rem"></i> Daftar Transaksi</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-module-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Sub Barang</th>
                                        <th>Nominal</th>
                                        <th>Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $transaction)
                                        <tr>
                                            <td>{{ optional($transaction->transaction_date)->format('d/m/Y') }}</td>
                                            <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                                            <td>{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                                            <td class="font-weight-600">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                            <td>{{ $transaction->description }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                                    <i class="far fa-folder-open"></i> Belum ada transaksi
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">Total:</th>
                                        <th colspan="2" class="text-primary font-weight-bold">Rp {{ number_format($totalAmount, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
