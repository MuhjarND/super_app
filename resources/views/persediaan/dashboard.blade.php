@extends('layouts.app')

@section('title', 'Perawatan Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="inventory-module-title mb-1">Perawatan Alat dan Mesin</h1>
            <p class="inventory-module-subtitle mb-0">Ringkasan alat, mesin, sub barang, ruang, dan transaksi perawatan.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('perawatan-alat-mesin.items.index') }}" class="btn app-create-btn"><i class="fas fa-boxes mr-1"></i> Master Barang</a>
            <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="btn btn-outline-primary"><i class="fas fa-tools mr-1"></i> Transaksi Perawatan</a>
        </div>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Rekapan modul perawatan</div>
            <div class="inventory-module-board-subtitle">Pantau total barang, sub barang, ruang, dan nominal perawatan terbaru.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Barang Induk</div>
                        <div class="inventory-module-stat-value">{{ $stats['item_count'] }}</div>
                        <div class="inventory-module-stat-note">Data barang utama</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Sub Barang</div>
                        <div class="inventory-module-stat-value">{{ $stats['detail_count'] }}</div>
                        <div class="inventory-module-stat-note">Unit detail tercatat</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Perawatan Bulan Ini</div>
                        <div class="inventory-module-stat-value text-success">Rp {{ number_format($stats['month_total'], 0, ',', '.') }}</div>
                        <div class="inventory-module-stat-note">Akumulasi bulan berjalan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Ruang</div>
                        <div class="inventory-module-stat-value text-primary">{{ $stats['room_count'] }}</div>
                        <div class="inventory-module-stat-note">Lokasi penyimpanan</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Transaksi Perawatan Terbaru</div>
                            <p class="inventory-module-panel-subtitle">Aktivitas perawatan terbaru di seluruh sub barang.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-module-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Barang</th>
                                        <th>Sub Barang</th>
                                        <th>Nominal</th>
                                        <th>Lampiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTransactions as $transaction)
                                        <tr>
                                            <td>{{ optional($transaction->transaction_date)->format('d-m-Y') }}</td>
                                            <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                                            <td>{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                                            <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                            <td>{{ $transaction->attachments->count() }} file</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada transaksi perawatan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="inventory-module-panel mb-4">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Barang dengan Nominal Tertinggi</div>
                            <p class="inventory-module-panel-subtitle">Prioritas barang dengan biaya perawatan terbesar.</p>
                        </div>
                        <div class="inventory-module-panel-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($topItems as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="font-weight-600">{{ optional($row->item)->name ?: '-' }}</div>
                                            <small class="inventory-module-muted">{{ optional($row->item)->code ?: '-' }}</small>
                                        </div>
                                        <span class="inventory-module-chip">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Belum ada data nominal perawatan.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="inventory-module-panel">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Ringkasan Kondisi Barang</div>
                            <p class="inventory-module-panel-subtitle">Sebaran kondisi terkini seluruh sub barang.</p>
                        </div>
                        <div class="inventory-module-panel-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($conditionSummary as $row)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ optional($row->condition)->name ?: 'Belum diatur' }}</span>
                                        <span class="inventory-module-chip">{{ $row->total }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Belum ada data kondisi.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
