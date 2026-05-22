@extends('layouts.app')

@section('title', 'Persediaan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="inventory-module-title mb-0">Persediaan</h1>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.create') }}" class="btn btn-sm app-create-btn"><i class="fas fa-shopping-cart mr-1"></i> Ajukan</a>
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-clipboard-list mr-1"></i> Pengajuan</a>
        <a href="{{ route('persediaan.pickups.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-box-open mr-1"></i> Pengambilan</a>
        @if($canManage)
            <a href="{{ route('persediaan.items.index') }}" class="btn btn-sm btn-outline-dark"><i class="fas fa-warehouse mr-1"></i> Kelola</a>
        @endif
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center">
            <div class="inventory-module-board-title"><i class="fas fa-cubes text-muted mr-1"></i> Ringkasan Stok</div>
            <span class="inventory-module-chip">{{ $stats['item_count'] }}</span>
        </div>
        <div class="inventory-module-board-body">
            <div class="row mb-3">
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Barang Aktif</div>
                        <div class="inventory-module-stat-value">{{ number_format($stats['item_count'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Total Stok</div>
                        <div class="inventory-module-stat-value">{{ number_format($stats['stock_total'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Menunggu</div>
                        <div class="inventory-module-stat-value">{{ number_format($stats['pending_count'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Bulan Ini</div>
                        <div class="inventory-module-stat-value">{{ number_format($stats['month_pickup_count'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            <div class="inventory-module-panel mb-3">
                <div class="inventory-module-panel-header d-flex justify-content-between align-items-center">
                    <div class="inventory-module-panel-title"><i class="fas fa-th-large text-muted mr-1"></i> Katalog ATK</div>
                    <a href="{{ route('persediaan.requests.create') }}" class="btn btn-xs app-create-btn btn-sm">Buat Pengajuan</a>
                </div>
                <div class="inventory-module-panel-body">
                    @if($availableItems->count())
                        <div class="supply-catalog-grid">
                            @foreach($availableItems as $item)
                                <div class="supply-item-card">
                                    <div class="supply-shop-meta-row align-items-start mb-2">
                                        <div>
                                            <div class="supply-item-name">{{ $item->name }}</div>
                                            <div class="supply-item-meta">{{ $item->code ?: '-' }}</div>
                                        </div>
                                        <span class="supply-stock-pill {{ $item->is_low_stock ? 'low' : '' }}">{{ $item->stock_label }}</span>
                                    </div>
                                    @if($item->description)
                                        <p class="supply-item-meta mb-2">{{ \Illuminate\Support\Str::limit($item->description, 45) }}</p>
                                    @endif
                                    <a href="{{ route('persediaan.requests.create', ['item' => $item->id]) }}" class="btn btn-sm btn-outline-primary btn-block">
                                        <i class="fas fa-plus mr-1"></i> Ajukan
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="inventory-module-empty"><i class="far fa-folder-open"></i> Belum ada barang aktif</div>
                    @endif
                </div>
            </div>

            <div class="inventory-module-panel">
                <div class="inventory-module-panel-header">
                    <div class="inventory-module-panel-title"><i class="fas fa-history text-muted mr-1"></i> Pengajuan Terbaru</div>
                </div>
                <div class="inventory-module-panel-body p-0">
                    <table class="table inventory-module-table supply-table mb-0">
                        <thead>
                            <tr>
                                <th>No</th>
                                @if($canManage)<th>Pegawai</th>@endif
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $request)
                                <tr>
                                    <td data-label="No"><a href="{{ route('persediaan.requests.show', $request) }}">{{ $request->request_number }}</a></td>
                                    @if($canManage)<td data-label="Pegawai">{{ optional($request->requester)->name ?: '-' }}</td>@endif
                                    <td data-label="Nama Barang">{{ $request->items_summary ?: '-' }}</td>
                                    <td data-label="Jumlah">{{ $request->quantity_summary }}</td>
                                    <td data-label="Keperluan">{{ \Illuminate\Support\Str::limit($request->purpose, 60) }}</td>
                                    <td data-label="Status">{!! $request->status_badge !!}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManage ? 6 : 5 }}" class="text-center py-4">
                                        <div class="inventory-module-empty border-0 bg-transparent p-0">
                                            <i class="far fa-folder-open"></i> Belum ada pengajuan
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
