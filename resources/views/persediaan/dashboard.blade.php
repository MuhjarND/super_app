@extends('layouts.app')

@section('title', 'Perawatan Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1 class="inventory-module-title mb-0">Perawatan Alat & Mesin</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('perawatan-alat-mesin.items.index') }}" class="btn app-create-btn"><i class="fas fa-boxes mr-1"></i> Master Barang</a>
            <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="btn btn-outline-primary"><i class="fas fa-tools mr-1"></i> Transaksi</a>
        </div>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board-header d-flex justify-content-between align-items-center">
        <div class="inventory-module-board-title">Ringkasan</div>
    </div>
    <div class="inventory-module-board-body">
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Barang Induk</div>
                    <div class="inventory-module-stat-value">{{ $stats['item_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Sub Barang</div>
                    <div class="inventory-module-stat-value">{{ $stats['detail_count'] }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Perawatan Bulan Ini</div>
                    <div class="inventory-module-stat-value text-success">Rp {{ number_format($stats['month_total'], 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Ruang</div>
                    <div class="inventory-module-stat-value text-primary">{{ $stats['room_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-3">
                <div class="inventory-module-panel h-100">
                    <div class="inventory-module-panel-header">
                        <div class="inventory-module-panel-title"><i class="fas fa-clock text-muted mr-1" style="font-size:.82rem"></i> Transaksi Terbaru</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table inventory-module-table mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Sub</th>
                                    <th>Nominal</th>
                                    <th style="width:60px"><i class="fas fa-paperclip"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->transaction_date)->format('d/m/Y') }}</td>
                                        <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                                        <td><span class="inventory-module-muted">{{ optional($transaction->detail)->sub_code ?: '-' }}</span></td>
                                        <td class="font-weight-600">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                        <td><span class="inventory-module-chip">{{ $transaction->attachments->count() }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4"><div class="inventory-module-empty border-0 bg-transparent p-0"><i class="far fa-folder-open"></i> Belum ada transaksi</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-3">
                <div class="inventory-module-panel mb-3">
                    <div class="inventory-module-panel-header">
                        <div class="inventory-module-panel-title"><i class="fas fa-sort-amount-up text-muted mr-1" style="font-size:.82rem"></i> Nominal Tertinggi</div>
                    </div>
                    <div class="inventory-module-panel-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($topItems as $row)
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

                <div class="inventory-module-panel">
                    <div class="inventory-module-panel-header">
                        <div class="inventory-module-panel-title"><i class="fas fa-heartbeat text-muted mr-1" style="font-size:.82rem"></i> Kondisi Barang</div>
                    </div>
                    <div class="inventory-module-panel-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($conditionSummary as $row)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3" style="font-size:.88rem">
                                    <span>{{ optional($row->condition)->name ?: 'Belum diatur' }}</span>
                                    <span class="inventory-module-chip">{{ $row->total }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-center py-3"><span class="inventory-module-muted">Belum ada data</span></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
