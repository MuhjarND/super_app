@extends('layouts.app')

@section('title', 'Detail Barang Alat dan Mesin')

@php
    $detailCount = $inventoryItem->details->count();
    $activeDetailCount = $inventoryItem->details->where('is_active', true)->count();
    $inactiveDetailCount = $detailCount - $activeDetailCount;
@endphp

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="inventory-module-title mb-1">{{ $inventoryItem->name }}</h1>
            <p class="inventory-module-subtitle mb-0">Kode {{ $inventoryItem->code }} | Total sub barang {{ $detailCount }} | Nominal perawatan Rp {{ number_format($maintenanceTotal, 0, ',', '.') }}</p>
        </div>
        <a href="{{ route('perawatan-alat-mesin.items.index') }}" class="btn btn-outline-primary">Kembali</a>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')
<style>
    .inventory-detail-form label {
        font-size: 0.92rem;
        margin-bottom: 6px;
        color: #334155;
        font-weight: 600;
    }

    .inventory-detail-table thead th {
        background: #edf4ff;
        color: #21427e;
        font-size: 0.88rem;
        font-weight: 700;
        border-bottom: 1px solid rgba(191, 219, 254, 0.82);
    }

    .inventory-detail-table td,
    .inventory-detail-table th {
        padding: 13px 16px;
        vertical-align: middle;
        border-color: rgba(226, 232, 240, 0.9);
        font-size: 0.95rem;
    }

    .inventory-photo-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }

    @media (max-width: 767.98px) {
        .inventory-module-hero {
            gap: 10px !important;
        }

        .inventory-module-title {
            font-size: 1.3rem !important;
            line-height: 1.25;
        }

        .inventory-module-subtitle {
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .inventory-module-hero .btn {
            width: 100%;
        }

        .inventory-module-board-body {
            padding: 16px 14px 18px;
        }

        .inventory-module-panel-header.d-flex {
            align-items: flex-start !important;
        }

        .inventory-detail-form .form-row {
            display: block;
        }

        .inventory-detail-form .form-row > .form-group {
            max-width: 100%;
        }

        .inventory-detail-table,
        .inventory-detail-table thead,
        .inventory-detail-table tbody,
        .inventory-detail-table tr,
        .inventory-detail-table th,
        .inventory-detail-table td {
            display: block;
            width: 100%;
        }

        .inventory-detail-table thead {
            display: none;
        }

        .inventory-detail-table tbody tr {
            margin: 0 0 12px;
            padding: 12px 12px 10px;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 14px;
            background: #fff;
        }

        .inventory-detail-table tbody tr:last-child {
            margin-bottom: 0;
        }

        .inventory-detail-table td {
            padding: 0 0 10px;
            border: 0;
            font-size: 0.92rem;
        }

        .inventory-detail-table td:last-child {
            padding-bottom: 0;
        }

        .inventory-detail-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
        }
    }
</style>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Detail barang alat dan mesin</div>
            <div class="inventory-module-board-subtitle">Kelola barang induk, sub barang, foto, dan histori perawatan.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row mb-4">
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Sub Barang</div>
                        <div class="inventory-module-stat-value">{{ $detailCount }}</div>
                        <div class="inventory-module-stat-note">Total NUP / unit detail</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Aktif</div>
                        <div class="inventory-module-stat-value text-success">{{ $activeDetailCount }}</div>
                        <div class="inventory-module-stat-note">Unit tersedia</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Nonaktif</div>
                        <div class="inventory-module-stat-value text-danger">{{ $inactiveDetailCount }}</div>
                        <div class="inventory-module-stat-note">Perlu pembaruan</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Perawatan</div>
                        <div class="inventory-module-stat-value text-primary" style="font-size: 1.45rem;">Rp {{ number_format($maintenanceTotal, 0, ',', '.') }}</div>
                        <div class="inventory-module-stat-note">Akumulasi biaya</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="inventory-module-panel mb-4">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Perbarui Barang Induk</div>
                            <p class="inventory-module-panel-subtitle">Sunting identitas utama barang induk.</p>
                        </div>
                        <div class="inventory-module-panel-body inventory-detail-form">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.items.update', $inventoryItem) }}">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>Kode Barang</label>
                                    <input type="text" name="code" value="{{ $inventoryItem->code }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Nama Barang</label>
                                    <input type="text" name="name" value="{{ $inventoryItem->name }}" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi</label>
                                    <textarea name="description" class="form-control" rows="4">{{ $inventoryItem->description }}</textarea>
                                </div>
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" name="is_active" id="item_edit_active" class="custom-control-input" value="1" {{ $inventoryItem->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="item_edit_active">Barang aktif</label>
                                </div>
                                <button class="btn app-create-btn btn-block">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>

                    <div class="inventory-module-panel">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Tambah Sub Barang</div>
                            <p class="inventory-module-panel-subtitle">Tambahkan unit detail untuk barang induk ini.</p>
                        </div>
                        <div class="inventory-module-panel-body inventory-detail-form">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.details.store', $inventoryItem) }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Kode Sub Barang</label>
                                    <input type="text" name="sub_code" class="form-control" placeholder="Kosongkan untuk generate otomatis">
                                </div>
                                <div class="form-group">
                                    <label>NUP</label>
                                    <input type="text" name="nup" class="form-control" placeholder="Nomor urut pendaftaran">
                                </div>
                                <div class="form-group">
                                    <label>Nama Sub Barang</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Tanggal Perolehan</label>
                                        <input type="date" name="acquisition_date" class="form-control">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Harga Perolehan</label>
                                        <input type="number" min="0" step="0.01" name="acquisition_value" class="form-control">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Satuan</label>
                                        <select name="inventory_unit_id" class="form-control">
                                            <option value="">-</option>
                                            @foreach($unitOptions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Kondisi</label>
                                        <select name="inventory_condition_id" class="form-control">
                                            <option value="">-</option>
                                            @foreach($conditionOptions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Ruang</label>
                                        <select name="inventory_room_id" class="form-control">
                                            <option value="">-</option>
                                            @foreach($roomOptions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Merk</label>
                                        <select name="inventory_brand_id" class="form-control">
                                            <option value="">-</option>
                                            @foreach($brandOptions as $option)
                                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Foto Barang</label>
                                    <input type="file" name="photo" class="form-control-file">
                                </div>
                                <div class="form-group">
                                    <label>Keterangan</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Catatan kondisi atau lokasi barang"></textarea>
                                </div>
                                <button class="btn app-create-btn btn-block">Simpan Sub Barang</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="inventory-module-panel mb-4">
                        <div class="inventory-module-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="inventory-module-panel-title">Sub Barang / Detail Barang</div>
                                <p class="inventory-module-panel-subtitle">Daftar unit detail yang sudah terdaftar untuk barang ini.</p>
                            </div>
                            <span class="inventory-module-chip">{{ $detailCount }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode / NUP</th>
                                        <th>Nama</th>
                                        <th>Kondisi</th>
                                        <th>Ruang</th>
                                        <th>Foto</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventoryItem->details as $detail)
                                        <tr>
                                            <td data-label="Kode / NUP">
                                                <div class="font-weight-600">{{ $detail->sub_code }}</div>
                                                <small class="inventory-module-muted d-block">{{ $detail->nup ?: '-' }}</small>
                                            </td>
                                            <td data-label="Nama">
                                                <div class="font-weight-600">{{ $detail->name }}</div>
                                                <small class="inventory-module-muted d-block">Rp {{ number_format($detail->acquisition_value, 0, ',', '.') }}</small>
                                            </td>
                                            <td data-label="Kondisi">{{ optional($detail->condition)->name ?: '-' }}</td>
                                            <td data-label="Ruang">{{ optional($detail->room)->name ?: '-' }}</td>
                                            <td data-label="Foto">
                                                @if($detail->photo_path)
                                                    <a href="{{ route('perawatan-alat-mesin.details.photo', $detail) }}" target="_blank" class="inventory-photo-link text-primary">
                                                        <i class="far fa-image"></i> Preview
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td data-label="Status">{!! $detail->status_badge !!}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-5">Belum ada sub barang.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="inventory-module-panel">
                        <div class="inventory-module-panel-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="inventory-module-panel-title">Histori Transaksi Perawatan</div>
                                <p class="inventory-module-panel-subtitle">Ringkasan transaksi perawatan untuk seluruh sub barang pada item ini.</p>
                            </div>
                            <span class="inventory-module-chip">{{ $inventoryItem->maintenanceTransactions->count() }} transaksi</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-detail-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Sub Barang</th>
                                        <th>Deskripsi</th>
                                        <th>Nominal</th>
                                        <th>Lampiran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventoryItem->maintenanceTransactions as $transaction)
                                        <tr>
                                            <td data-label="Tanggal">{{ optional($transaction->transaction_date)->format('d-m-Y') }}</td>
                                            <td data-label="Sub Barang">{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                                            <td data-label="Deskripsi">{{ $transaction->description }}</td>
                                            <td data-label="Nominal">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                            <td data-label="Lampiran">{{ $transaction->attachments->count() }} file</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada histori transaksi.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
