@extends('layouts.app')

@section('title', 'Master Barang Alat dan Mesin')

@php
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
        <div>
            <h1 class="inventory-module-title mb-1">Master Barang</h1>
            <p class="inventory-module-subtitle mb-0">Kelola barang induk alat dan mesin beserta total sub barang yang sudah terdaftar.</p>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari kode atau nama barang">
            <button class="btn app-create-btn" type="submit">Cari</button>
        </form>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .inventory-module-hero {
            gap: 12px;
        }

        .inventory-module-hero form {
            width: 100%;
        }

        .inventory-module-hero form .form-control,
        .inventory-module-hero form .btn {
            width: 100%;
        }

        .inventory-module-hero form {
            display: grid !important;
            grid-template-columns: 1fr;
        }

        .inventory-module-panel,
        .inventory-module-board {
            border-radius: 14px;
        }

        .inventory-items-mobile-table table,
        .inventory-items-mobile-table thead,
        .inventory-items-mobile-table tbody,
        .inventory-items-mobile-table tr,
        .inventory-items-mobile-table th,
        .inventory-items-mobile-table td {
            display: block;
            width: 100%;
        }

        .inventory-items-mobile-table thead {
            display: none;
        }

        .inventory-items-mobile-table tbody tr {
            border-bottom: 1px solid #e8eaed;
            padding: 14px;
        }

        .inventory-items-mobile-table tbody tr:last-child {
            border-bottom: 0;
        }

        .inventory-items-mobile-table tbody td {
            border: 0;
            padding: 6px 0;
        }

        .inventory-items-mobile-table tbody td::before {
            content: attr(data-label);
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    }
</style>
@endpush

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Master barang alat dan mesin</div>
            <div class="inventory-module-board-subtitle">Struktur barang induk untuk pencatatan sub barang, QR, dan histori perawatan.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Total Barang</div>
                        <div class="inventory-module-stat-value">{{ number_format($itemStats['total']) }}</div>
                        <div class="inventory-module-stat-note">Barang induk alat dan mesin</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Aktif</div>
                        <div class="inventory-module-stat-value text-success">{{ number_format($itemStats['active']) }}</div>
                        <div class="inventory-module-stat-note">Siap digunakan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Nonaktif</div>
                        <div class="inventory-module-stat-value text-danger">{{ number_format($itemStats['inactive']) }}</div>
                        <div class="inventory-module-stat-note">Perlu pembaruan data</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Sub Barang</div>
                        <div class="inventory-module-stat-value text-primary">{{ number_format($itemStats['details']) }}</div>
                        <div class="inventory-module-stat-note">Akumulasi unit detail</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Tambah Barang Induk</div>
                            <p class="inventory-module-panel-subtitle">Buat data barang utama sebelum menambah sub barang atau detail unit.</p>
                        </div>
                        <div class="inventory-module-panel-body">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.items.store') }}">
                                @csrf
                                <div class="form-group">
                                    <label>Kode Barang</label>
                                    <input type="text" name="code" class="form-control" required placeholder="Contoh: 01.02.03">
                                </div>
                                <div class="form-group">
                                    <label>Nama Barang</label>
                                    <input type="text" name="name" class="form-control" required placeholder="Nama barang induk">
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Informasi tambahan barang"></textarea>
                                </div>
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" name="is_active" id="item_active" class="custom-control-input" value="1" checked>
                                    <label class="custom-control-label" for="item_active">Barang aktif</label>
                                </div>
                                <button class="btn app-create-btn btn-block">Simpan Barang</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="inventory-module-panel-title">Daftar Barang Induk</div>
                                <p class="inventory-module-panel-subtitle">Pilih barang untuk mengelola sub barang, foto, QR code, dan histori perawatan.</p>
                            </div>
                            <span class="inventory-module-chip">{{ $items->total() }} data</span>
                        </div>
                        <div class="table-responsive inventory-items-mobile-table">
                            <table class="table inventory-module-table mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 20%;">Kode</th>
                                        <th>Barang</th>
                                        <th style="width: 15%;">Sub Barang</th>
                                        <th style="width: 14%;">Status</th>
                                        <th style="width: 16%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($items as $item)
                                        <tr>
                                            <td data-label="Kode"><strong>{{ $item->code }}</strong></td>
                                            <td data-label="Barang">
                                                <div class="font-weight-600">{{ $item->name }}</div>
                                                <div class="inventory-module-muted">{{ $item->description ?: 'Belum ada deskripsi barang.' }}</div>
                                            </td>
                                            <td data-label="Sub Barang"><span class="inventory-module-chip">{{ $item->details_count }} unit</span></td>
                                            <td data-label="Status">{!! $item->status_badge !!}</td>
                                            <td data-label="Aksi"><a href="{{ route('perawatan-alat-mesin.items.show', $item) }}" class="btn btn-sm app-create-btn">Kelola</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada barang induk.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($items, 'links'))
                            <div class="inventory-module-panel-body pt-3 pb-3">{{ $items->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
