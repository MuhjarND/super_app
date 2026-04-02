@extends('layouts.app')

@section('title', 'Transaksi Perawatan Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="maintenance-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="maintenance-hero-title mb-1">Transaksi Perawatan Alat dan Mesin</h1>
            <p class="maintenance-hero-subtitle mb-0">Monitoring subtotal perawatan per barang dan histori transaksi per sub barang.</p>
        </div>
        <button type="button" class="btn app-create-btn" data-toggle="modal" data-target="#maintenanceCreateModal">
            <i class="fas fa-plus mr-1"></i> Tambah Transaksi
        </button>
    </div>
</div>
@endsection

@section('content')
            <style>
                .maintenance-hero {
                    padding: 8px 2px 12px;
                }

                .maintenance-hero-title {
                    font-size: 1.55rem;
                    font-weight: 700;
                    line-height: 1.15;
                    color: #0f172a;
                }

                .maintenance-hero-subtitle {
                    font-size: 0.98rem;
                    color: #64748b;
                }

                .maintenance-shell {
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.10), rgba(245, 158, 11, 0.10));
                    border-radius: 24px;
                    padding: 1px;
                    margin-top: 2px;
                }

                .maintenance-board {
                    background: #fff;
                    border-radius: 23px;
                    overflow: hidden;
                    border: 1px solid rgba(148, 163, 184, 0.16);
                    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.07);
                }

                .maintenance-board-header {
                    padding: 18px 22px;
                    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
                }

                .maintenance-board-title {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #0f172a;
                    margin-bottom: 4px;
                }

                .maintenance-board-subtitle {
                    color: #64748b;
                    font-size: 0.95rem;
                }

                .maintenance-board-count {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #1e293b;
                }

                .maintenance-board-body {
                    padding: 20px;
                }

                .maintenance-filter-card {
                    border: 1px solid rgba(191, 219, 254, 0.7);
                    border-radius: 18px;
                    padding: 15px 16px;
                    margin-bottom: 16px;
                    background: rgba(248, 250, 252, 0.9);
                }

                .maintenance-filter-card label {
                    font-size: 0.92rem;
                    margin-bottom: 6px;
                    color: #334155;
                    font-weight: 600;
                }

                .maintenance-item-card {
                    border: 1px solid rgba(191, 219, 254, 0.9);
                    border-radius: 20px;
                    background: #fff;
                    overflow: hidden;
                    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
                    margin-bottom: 14px;
                }

                .maintenance-item-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 14px;
                    padding: 18px 22px;
                    cursor: pointer;
                }

                .maintenance-item-trigger {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    flex: 1 1 auto;
                    min-width: 0;
                }

                .maintenance-item-icon {
                    width: 46px;
                    height: 46px;
                    border-radius: 14px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #e8f0ff;
                    color: #234b8f;
                    font-size: 18px;
                    flex-shrink: 0;
                }

                .maintenance-item-icon i {
                    transition: transform 0.2s ease;
                }

                .maintenance-item-card.is-open .maintenance-item-icon i {
                    transform: rotate(90deg);
                }

                .maintenance-item-code {
                    font-size: 0.95rem;
                    font-weight: 700;
                    letter-spacing: 0.08em;
                    color: #335ea8;
                    margin-bottom: 4px;
                }

                .maintenance-item-name {
                    font-size: 0.95rem;
                    font-weight: 700;
                    color: #17366b;
                    line-height: 1.35;
                }

                .maintenance-summary {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex-wrap: wrap;
                    justify-content: flex-end;
                }

                .maintenance-chip {
                    display: inline-flex;
                    align-items: center;
                    padding: 8px 14px;
                    border-radius: 999px;
                    background: #eef4ff;
                    color: #47658f;
                    font-weight: 700;
                    font-size: 0.9rem;
                }

                .maintenance-total {
                    font-size: 0.92rem;
                    font-weight: 800;
                    color: #0f9f68;
                    white-space: nowrap;
                }

                .maintenance-item-body {
                    padding: 0 22px 22px;
                    display: none;
                }

                .maintenance-item-card.is-open .maintenance-item-body {
                    display: block;
                }

                .maintenance-table-wrap {
                    border: 1px solid rgba(191, 219, 254, 0.78);
                    border-radius: 16px;
                    overflow: hidden;
                }

                .maintenance-table thead th {
                    background: #edf4ff;
                    color: #21427e;
                    font-size: 0.88rem;
                    font-weight: 700;
                    border-bottom: 1px solid rgba(191, 219, 254, 0.82);
                }

                .maintenance-table td,
                .maintenance-table th {
                    padding: 13px 16px;
                    vertical-align: middle;
                    border-color: rgba(226, 232, 240, 0.9);
                    font-size: 0.95rem;
                }

                .maintenance-empty {
                    border: 1px dashed rgba(148, 163, 184, 0.45);
                    border-radius: 16px;
                    padding: 18px;
                    color: #64748b;
                    background: rgba(248, 250, 252, 0.85);
                    text-align: center;
                }

                @media (max-width: 991.98px) {
                    .maintenance-item-header {
                        flex-direction: column;
                        align-items: stretch;
                    }

                    .maintenance-summary {
                        justify-content: space-between;
                    }
                }
            </style>

            <div class="maintenance-shell">
                <div class="maintenance-board">
                    <div class="maintenance-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="maintenance-board-title">Transaksi perawatan barang dan mesin</div>
                            <div class="maintenance-board-subtitle">Akumulasi nominal perawatan per barang dan histori transaksi per sub barang.</div>
                        </div>
                        <div class="maintenance-board-count">{{ $groupedItems->count() }} barang</div>
                    </div>

                    <div class="maintenance-board-body">
                        <div class="maintenance-filter-card">
                            <form method="GET" action="{{ route('perawatan-alat-mesin.maintenance.index') }}">
                                <div class="form-row">
                                    <div class="form-group col-md-3 mb-md-0">
                                        <label>Barang</label>
                                        <select name="inventory_item_id" id="maintenance_filter_item" class="form-control">
                                            <option value="">Semua barang</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" {{ request('inventory_item_id') == $item->id ? 'selected' : '' }}>{{ $item->code }} - {{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3 mb-md-0">
                                        <label>Sub Barang</label>
                                        <select name="inventory_item_detail_id" id="maintenance_filter_detail" class="form-control">
                                            <option value="">Semua sub barang</option>
                                            @foreach($details as $detail)
                                                <option value="{{ $detail->id }}" data-item="{{ $detail->inventory_item_id }}" {{ request('inventory_item_detail_id') == $detail->id ? 'selected' : '' }}>
                                                    {{ $detail->sub_code }} - {{ $detail->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2 mb-md-0">
                                        <label>Dari</label>
                                        <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                                    </div>
                                    <div class="form-group col-md-2 mb-md-0">
                                        <label>Sampai</label>
                                        <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                                    </div>
                                    <div class="form-group col-md-2 d-flex align-items-end mb-0">
                                        <div class="w-100 d-flex gap-2">
                                            <button class="btn btn-outline-primary btn-block">Filter</button>
                                            <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        @forelse($groupedItems as $row)
                            @php
                                $item = $row['item'];
                                $isOpen = (int) $expandedItemId === (int) $item->id;
                            @endphp
                            <div class="maintenance-item-card {{ $isOpen ? 'is-open' : '' }}" data-item-card="{{ $item->id }}">
                                <div class="maintenance-item-header" data-toggle-item="{{ $item->id }}">
                                    <div class="maintenance-item-trigger">
                                        <span class="maintenance-item-icon"><i class="fas fa-chevron-right"></i></span>
                                        <div>
                                            <div class="maintenance-item-code">{{ $item->code }}</div>
                                            <div class="maintenance-item-name">{{ $item->name }}</div>
                                        </div>
                                    </div>
                                    <div class="maintenance-summary">
                                        <span class="maintenance-chip">{{ $row['detail_count'] }} sub barang</span>
                                        <div class="maintenance-total">Rp {{ number_format($row['subtotal'], 2, ',', '.') }}</div>
                                    </div>
                                </div>

                                <div class="maintenance-item-body">
                                    @if($row['detail_rows']->count())
                                        <div class="maintenance-table-wrap">
                                            <div class="table-responsive">
                                                <table class="table maintenance-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>NUP / Kode sub barang</th>
                                                            <th>Nama sub barang</th>
                                                            <th width="200">Subtotal perawatan</th>
                                                            <th width="128"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($row['detail_rows'] as $detailRow)
                                                            @php $detail = $detailRow['detail']; @endphp
                                                            <tr>
                                                                <td>{{ $detail ? ($detail->sub_code ?: ($detail->nup ?: '-')) : 'Tanpa sub barang' }}</td>
                                                                <td>{{ $detail ? $detail->name : 'Transaksi langsung pada barang induk' }}</td>
                                                                <td class="font-weight-700 text-right">Rp {{ number_format($detailRow['subtotal'], 2, ',', '.') }}</td>
                                                                <td class="text-right">
                                                                    @if($detail)
                                                                        <a href="{{ route('perawatan-alat-mesin.maintenance.show', $detail) }}" class="btn btn-sm btn-primary">Detail</a>
                                                                    @else
                                                                        <span class="text-muted small">-</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="maintenance-empty">Belum ada transaksi perawatan pada barang ini.</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="maintenance-empty">Belum ada data transaksi perawatan yang sesuai filter.</div>
                        @endforelse
                    </div>
                </div>
            </div>

<div class="modal fade" id="maintenanceCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('perawatan-alat-mesin.maintenance.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Transaksi Perawatan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Barang</label>
                            <select name="inventory_item_id" id="maintenance_create_item" class="form-control" required>
                                <option value="">Pilih barang</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sub Barang</label>
                            <select name="inventory_item_detail_id" id="maintenance_create_detail" class="form-control">
                                <option value="">Tanpa sub barang</option>
                                @foreach($details as $detail)
                                    <option value="{{ $detail->id }}" data-item="{{ $detail->inventory_item_id }}">{{ $detail->sub_code }} - {{ $detail->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal</label>
                            <input type="date" name="transaction_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nominal</label>
                            <input type="number" min="0" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Lampiran</label>
                            <input type="file" name="attachments[]" class="form-control-file" multiple>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Keterangan</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn app-create-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function filterDetailOptions(itemSelector, detailSelector) {
            var itemSelect = document.querySelector(itemSelector);
            var detailSelect = document.querySelector(detailSelector);
            if (!itemSelect || !detailSelect) {
                return;
            }

            var selectedItem = itemSelect.value;
            Array.prototype.forEach.call(detailSelect.options, function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                option.hidden = selectedItem && option.dataset.item !== selectedItem;
            });

            if (detailSelect.selectedOptions.length && detailSelect.selectedOptions[0].hidden) {
                detailSelect.value = '';
            }
        }

        document.querySelectorAll('[data-toggle-item]').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                var itemId = trigger.getAttribute('data-toggle-item');
                var card = document.querySelector('[data-item-card="' + itemId + '"]');
                if (card) {
                    card.classList.toggle('is-open');
                }
            });
        });

        var filterItem = document.getElementById('maintenance_filter_item');
        if (filterItem) {
            filterItem.addEventListener('change', function () {
                filterDetailOptions('#maintenance_filter_item', '#maintenance_filter_detail');
            });
            filterDetailOptions('#maintenance_filter_item', '#maintenance_filter_detail');
        }

        var createItem = document.getElementById('maintenance_create_item');
        if (createItem) {
            createItem.addEventListener('change', function () {
                filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail');
            });
            filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail');
        }
    })();
</script>
@endpush

