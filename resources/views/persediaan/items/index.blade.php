@extends('layouts.app')

@section('title', 'Master Barang Alat dan Mesin')

@php
    $canManageInventory = auth()->user()->canManageInventoryModule();
    $itemStats = [
        'total' => $items->total(),
        'active' => $items->getCollection()->where('is_active', true)->count(),
        'inactive' => $items->getCollection()->where('is_active', false)->count(),
        'details' => $items->getCollection()->sum('details_count'),
    ];
@endphp

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1 class="inventory-module-title mb-0">Master Barang</h1>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama...">
            <button class="btn app-create-btn" type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .inventory-module-hero { gap: 12px; }
        .inventory-module-hero form { width: 100%; }
        .inventory-module-hero form .form-control { flex: 1; }

        .inv-items-table table,
        .inv-items-table thead,
        .inv-items-table tbody,
        .inv-items-table tr,
        .inv-items-table th,
        .inv-items-table td { display: block; width: 100%; }
        .inv-items-table thead { display: none; }
        .inv-items-table tbody tr { border-bottom: 1px solid #e8eaed; padding: 12px; }
        .inv-items-table tbody tr:last-child { border-bottom: 0; }
        .inv-items-table tbody td { border: 0; padding: 4px 0; }
        .inv-items-table tbody td::before {
            content: attr(data-label);
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    }
</style>
@endpush

<div class="inventory-module-shell">
    <div class="inventory-module-board-header">
        <div class="inventory-module-board-title">Data Barang</div>
    </div>
    <div class="inventory-module-board-body">
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Total</div>
                    <div class="inventory-module-stat-value">{{ number_format($itemStats['total']) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Aktif</div>
                    <div class="inventory-module-stat-value text-success">{{ number_format($itemStats['active']) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Nonaktif</div>
                    <div class="inventory-module-stat-value text-danger">{{ number_format($itemStats['inactive']) }}</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="inventory-module-stat">
                    <div class="inventory-module-stat-label">Sub Barang</div>
                    <div class="inventory-module-stat-value text-primary">{{ number_format($itemStats['details']) }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            @if($canManageInventory)
            <div class="col-lg-4 mb-3">
                <div class="inventory-module-panel h-100">
                    <div class="inventory-module-panel-header">
                        <div class="inventory-module-panel-title"><i class="fas fa-plus-circle text-muted mr-1" style="font-size:.82rem"></i> Tambah Barang</div>
                    </div>
                    <div class="inventory-module-panel-body">
                        <form method="POST" action="{{ route('perawatan-alat-mesin.items.store') }}">
                            @csrf
                            <div class="form-group">
                                <label>Kode Barang</label>
                                <input type="text" name="code" class="form-control" required placeholder="01.02.03">
                            </div>
                            <div class="form-group">
                                <label>Nama Barang</label>
                                <input type="text" name="name" class="form-control" required placeholder="Nama barang induk">
                            </div>
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Opsional"></textarea>
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" name="is_active" id="item_active" class="custom-control-input" value="1" checked>
                                <label class="custom-control-label" for="item_active">Aktif</label>
                            </div>
                            <button class="btn app-create-btn btn-block">Simpan</button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <div class="{{ $canManageInventory ? 'col-lg-8' : 'col-12' }} mb-3">
                <div class="inventory-module-panel h-100">
                    <div class="inventory-module-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="inventory-module-panel-title"><i class="fas fa-list text-muted mr-1" style="font-size:.82rem"></i> Daftar Barang</div>
                        <span class="inventory-module-chip">{{ $items->total() }}</span>
                    </div>
                    <div class="table-responsive inv-items-table">
                        <table class="table inventory-module-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 18%;">Kode</th>
                                    <th>Barang</th>
                                    <th style="width: 13%;">Sub</th>
                                    <th style="width: 12%;">Status</th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td data-label="Kode"><strong>{{ $item->code }}</strong></td>
                                        <td data-label="Barang">
                                            <div class="font-weight-600">{{ $item->name }}</div>
                                            @if($item->description)<div class="inventory-module-muted">{{ Str::limit($item->description, 60) }}</div>@endif
                                        </td>
                                        <td data-label="Sub"><span class="inventory-module-chip">{{ $item->details_count }}</span></td>
                                        <td data-label="Status">{!! $item->status_badge !!}</td>
                                        <td data-label=""><a href="{{ route('perawatan-alat-mesin.items.show', $item) }}" class="btn btn-sm btn-outline-primary" title="{{ $canManageInventory ? 'Kelola' : 'Detail' }}"><i class="fas fa-arrow-right"></i></a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4"><div class="inventory-module-empty border-0 bg-transparent p-0"><i class="far fa-folder-open"></i> Belum ada barang</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($items, 'links'))
                        <div class="inventory-module-panel-body pt-2 pb-2">{{ $items->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
