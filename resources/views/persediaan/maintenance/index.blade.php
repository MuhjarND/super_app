@extends('layouts.app')

@section('title', 'Transaksi Perawatan')

@section('content-header')
<div class="container-fluid">
    <div class="maintenance-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1 class="maintenance-hero-title mb-0">Transaksi Perawatan</h1>
        @if(auth()->user()->canManageInventoryModule())
        <button type="button" class="btn app-create-btn" data-toggle="modal" data-target="#maintenanceCreateModal">
            <i class="fas fa-plus mr-1"></i> Tambah
        </button>
        @endif
    </div>
</div>
@endsection

@section('content')
            <style>
                .maintenance-hero { padding: 6px 2px 10px; }
                .maintenance-hero-title { font-size: 1.3rem; font-weight: 800; line-height: 1.2; color: #0f172a; }

                .maintenance-shell {
                    border: 1px solid rgba(148, 163, 184, 0.18);
                    border-radius: 16px;
                    margin-top: 2px;
                    background: #fff;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
                    overflow: hidden;
                }

                .maintenance-board-header {
                    padding: 14px 18px;
                    border-bottom: 1px solid rgba(148, 163, 184, 0.14);
                }
                .maintenance-board-title { font-size: 0.92rem; font-weight: 700; color: #0f172a; }
                .maintenance-board-count { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
                .maintenance-board-body { padding: 16px; }

                .maintenance-filter-card {
                    border: 1px solid rgba(191, 219, 254, 0.6);
                    border-radius: 12px;
                    padding: 12px 14px;
                    margin-bottom: 14px;
                    background: rgba(248, 250, 252, 0.8);
                }
                .maintenance-filter-card label { font-size: 0.78rem; margin-bottom: 4px; color: #475569; font-weight: 600; }

                .maintenance-item-card {
                    border: 1px solid rgba(191, 219, 254, 0.7);
                    border-radius: 14px;
                    background: #fff;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
                    margin-bottom: 10px;
                }

                .maintenance-item-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    padding: 14px 16px;
                    cursor: pointer;
                    transition: background 0.15s;
                }
                .maintenance-item-header:hover { background: rgba(248, 250, 252, 0.8); }

                .maintenance-item-trigger {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex: 1 1 auto;
                    min-width: 0;
                }

                .maintenance-item-icon {
                    width: 38px;
                    height: 38px;
                    border-radius: 10px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #eef4ff;
                    color: #3b6fcf;
                    font-size: 14px;
                    flex-shrink: 0;
                    transition: all 0.2s;
                }
                .maintenance-item-card.is-open .maintenance-item-icon { background: #3b6fcf; color: #fff; }
                .maintenance-item-card.is-open .maintenance-item-icon i { transform: rotate(90deg); }
                .maintenance-item-code { font-size: 0.82rem; font-weight: 700; letter-spacing: 0.06em; color: #3b6fcf; margin-bottom: 1px; }
                .maintenance-item-name { font-size: 0.88rem; font-weight: 700; color: #1e293b; line-height: 1.3; }

                .maintenance-summary { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: flex-end; }
                .maintenance-chip { display: inline-flex; align-items: center; padding: 5px 11px; border-radius: 999px; background: #eef4ff; color: #47658f; font-weight: 700; font-size: 0.78rem; }
                .maintenance-total { font-size: 0.85rem; font-weight: 800; color: #0f9f68; white-space: nowrap; }

                .maintenance-item-body { padding: 0 16px 16px; display: none; }
                .maintenance-item-card.is-open .maintenance-item-body { display: block; }

                .maintenance-table-wrap { border: 1px solid rgba(191, 219, 254, 0.6); border-radius: 10px; overflow: hidden; }
                .maintenance-table thead th { background: #edf4ff; color: #21427e; font-size: 0.82rem; font-weight: 700; border-bottom: 1px solid rgba(191, 219, 254, 0.82); }
                .maintenance-table td, .maintenance-table th { padding: 10px 14px; vertical-align: middle; border-color: rgba(226, 232, 240, 0.9); font-size: 0.85rem; }
                .maintenance-empty { border: 1px dashed rgba(148, 163, 184, 0.35); border-radius: 10px; padding: 14px; color: #94a3b8; background: rgba(248, 250, 252, 0.85); text-align: center; font-size: 0.84rem; }

                @media (max-width: 991.98px) {
                    .maintenance-hero .app-create-btn { width: 100%; justify-content: center; }
                    .maintenance-item-header { flex-direction: column; align-items: stretch; }
                    .maintenance-summary { justify-content: space-between; }
                }

                @media (max-width: 767.98px) {
                    .maintenance-hero-title { font-size: 1.1rem; }
                    .maintenance-shell { border-radius: 12px; }
                    .maintenance-board-header, .maintenance-board-body, .maintenance-item-header, .maintenance-item-body { padding-left: 12px; padding-right: 12px; }
                    .maintenance-chip, .maintenance-total, .maintenance-item-code, .maintenance-item-name, .maintenance-table td, .maintenance-table th { font-size: 0.78rem; }
                }
            </style>

            <div class="maintenance-shell">
                <div class="maintenance-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="maintenance-board-title">Per Barang</div>
                    <div class="maintenance-board-count">{{ $groupedItems->count() }} barang</div>
                </div>

                <div class="maintenance-board-body">
                    <div class="maintenance-filter-card">
                        <form method="GET" action="{{ route('perawatan-alat-mesin.maintenance.index') }}">
                            <div class="form-row">
                                <div class="form-group col-md-3 mb-md-0">
                                    <label>Barang</label>
                                    <select name="inventory_item_id" id="maintenance_filter_item" class="form-control">
                                        <option value="">Semua</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" {{ request('inventory_item_id') == $item->id ? 'selected' : '' }}>{{ $item->code }} - {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3 mb-md-0">
                                    <label>Sub Barang</label>
                                    <select name="inventory_item_detail_id" id="maintenance_filter_detail" class="form-control">
                                        <option value="">Semua</option>
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
                                        <button class="btn btn-outline-primary btn-block"><i class="fas fa-filter"></i></button>
                                        <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="btn btn-outline-secondary btn-block"><i class="fas fa-undo"></i></a>
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
                                    <span class="maintenance-chip">{{ $row['detail_count'] }} sub</span>
                                    <div class="maintenance-total">Rp {{ number_format($row['subtotal'], 0, ',', '.') }}</div>
                                </div>
                            </div>

                            <div class="maintenance-item-body">
                                @if($row['detail_rows']->count())
                                    <div class="maintenance-table-wrap">
                                        <div class="table-responsive">
                                            <table class="table maintenance-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>NUP / Kode</th>
                                                        <th>Nama</th>
                                                        <th width="180">Subtotal</th>
                                                        <th width="60"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($row['detail_rows'] as $detailRow)
                                                        @php $detail = $detailRow['detail']; @endphp
                                                        <tr>
                                                            <td>{{ $detail ? ($detail->sub_code ?: ($detail->nup ?: '-')) : 'Langsung' }}</td>
                                                            <td>{{ $detail ? $detail->name : 'Barang induk' }}</td>
                                                            <td class="font-weight-700 text-right">Rp {{ number_format($detailRow['subtotal'], 0, ',', '.') }}</td>
                                                            <td class="text-right">
                                                                @if($detail)
                                                                    <a href="{{ route('perawatan-alat-mesin.maintenance.show', $detail) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i class="fas fa-arrow-right"></i></a>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <div class="maintenance-empty"><i class="far fa-folder-open" style="margin-right:4px"></i> Belum ada transaksi</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="maintenance-empty"><i class="fas fa-filter" style="margin-right:4px"></i> Tidak ada data sesuai filter</div>
                    @endforelse
                </div>
            </div>

@if(auth()->user()->canManageInventoryModule())
<div class="modal fade" id="maintenanceCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('perawatan-alat-mesin.maintenance.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Transaksi</h5>
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
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn app-create-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        function filterDetailOptions(itemSelector, detailSelector) {
            var itemSelect = document.querySelector(itemSelector);
            var detailSelect = document.querySelector(detailSelector);
            if (!itemSelect || !detailSelect) return;

            var selectedItem = itemSelect.value;
            Array.prototype.forEach.call(detailSelect.options, function (option, index) {
                if (index === 0) { option.hidden = false; return; }
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
                if (card) card.classList.toggle('is-open');
            });
        });

        var filterItem = document.getElementById('maintenance_filter_item');
        if (filterItem) {
            filterItem.addEventListener('change', function () { filterDetailOptions('#maintenance_filter_item', '#maintenance_filter_detail'); });
            filterDetailOptions('#maintenance_filter_item', '#maintenance_filter_detail');
        }

        var createItem = document.getElementById('maintenance_create_item');
        if (createItem) {
            createItem.addEventListener('change', function () { filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail'); });
            filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail');
        }
    })();
</script>
@endpush
