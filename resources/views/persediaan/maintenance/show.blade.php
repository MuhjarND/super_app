@extends('layouts.app')

@section('title', 'History Transaksi Perawatan')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h1 class="inventory-module-title mb-0">Histori Perawatan</h1>
        <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')
<style>
    .history-photo {
        width: 220px;
        height: 220px;
        border-radius: 14px;
        border: 1px solid rgba(203, 213, 225, 0.95);
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .history-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .history-photo-placeholder {
        color: #c7ccd4;
        font-size: 82px;
    }

    .history-meta {
        margin-top: 14px;
        line-height: 1.5;
        color: #0f172a;
        font-size: 0.86rem;
    }

    .history-meta strong {
        font-weight: 700;
    }

    .history-action-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .history-table thead th {
        background: #edf4ff;
        color: #21427e;
        font-size: 0.82rem;
        font-weight: 700;
        border-bottom: 1px solid rgba(191, 219, 254, 0.82);
    }

    .history-table td,
    .history-table th {
        padding: 10px 14px;
        vertical-align: middle;
        border-color: rgba(226, 232, 240, 0.9);
        font-size: 0.86rem;
    }

    .history-chip {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        background: #16a34a;
        color: #fff;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .attachment-actions {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .attachment-preview-frame {
        width: 100%;
        min-height: 72vh;
        border: 0;
        border-radius: 12px;
        background: #f8fafc;
    }

    @media (max-width: 767.98px) {
        .inventory-module-hero {
            gap: 10px !important;
        }

        .inventory-module-title {
            font-size: 1.28rem !important;
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

        .history-photo {
            width: 100%;
            height: 190px;
            border-radius: 14px;
        }

        .history-meta {
            margin-top: 14px;
            font-size: 0.92rem;
        }

        .history-action-row .btn {
            flex: 1 1 100%;
        }

        .history-table,
        .history-table thead,
        .history-table tbody,
        .history-table tr,
        .history-table th,
        .history-table td {
            display: block;
            width: 100%;
        }

        .history-table thead {
            display: none;
        }

        .history-table tbody tr {
            margin: 0 0 12px;
            padding: 12px 12px 10px;
            border: 1px solid rgba(203, 213, 225, 0.95);
            border-radius: 14px;
            background: #fff;
        }

        .history-table tbody tr:last-child {
            margin-bottom: 0;
        }

        .history-table td {
            padding: 0 0 10px;
            border: 0;
            font-size: 0.92rem;
        }

        .history-table td:last-child {
            padding-bottom: 0;
        }

        .history-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
        }

        .attachment-actions {
            align-items: flex-start;
            flex-direction: column;
        }

        .history-table td[data-label="Aksi"] .d-flex {
            justify-content: flex-start !important;
        }
    }
</style>

<div class="inventory-module-shell">
    <div>
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Detail & Histori</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title"><i class="fas fa-cube text-muted mr-1" style="font-size:.78rem"></i> Sub Barang</div>
                        </div>
                        <div class="inventory-module-panel-body">
                            <div class="history-photo">
                                @if($inventoryItemDetail->photo_path)
                                    <img src="{{ route('perawatan-alat-mesin.details.photo', $inventoryItemDetail) }}" alt="{{ $inventoryItemDetail->name }}">
                                @else
                                    <div class="history-photo-placeholder"><i class="far fa-image"></i></div>
                                @endif
                            </div>

                            <div class="history-meta">
                                <div><strong>Barang</strong> {{ optional($inventoryItemDetail->item)->name ?: '-' }}</div>
                                <div><strong>Kode</strong> {{ $inventoryItemDetail->sub_code ?: ($inventoryItemDetail->nup ?: '-') }}</div>
                                <div><strong>Nama</strong> {{ $inventoryItemDetail->name ?: '-' }}</div>
                                <div><strong>Perolehan</strong> {{ optional($inventoryItemDetail->acquisition_date)->format('d/m/Y') ?: '-' }}</div>
                                <div><strong>Harga</strong> {{ $inventoryItemDetail->acquisition_value !== null ? 'Rp ' . number_format((float) $inventoryItemDetail->acquisition_value, 0, ',', '.') : '-' }}</div>
                                <div><strong>Satuan</strong> {{ optional($inventoryItemDetail->unit)->name ?: '-' }}</div>
                                <div><strong>Ruang</strong> {{ optional($inventoryItemDetail->room)->name ?: '-' }}</div>
                                <div><strong>Merk</strong> {{ optional($inventoryItemDetail->brand)->name ?: '-' }}</div>
                                <div><strong>Kondisi</strong>
                                    @if(optional($inventoryItemDetail->condition)->name)
                                        <span class="history-chip">{{ $inventoryItemDetail->condition->name }}</span>
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="mt-2 text-success font-weight-700"><strong>Total</strong> Rp {{ number_format($transactionTotal, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header d-flex justify-content-between align-items-center">
                            <div class="inventory-module-panel-title"><i class="fas fa-history text-muted mr-1" style="font-size:.78rem"></i> Transaksi</div>
                            <span class="inventory-module-chip">{{ $inventoryItemDetail->maintenanceTransactions->count() }}</span>
                        </div>
                        <div class="inventory-module-panel-body">
                            <div class="history-action-row">
                                <button type="button" class="btn app-create-btn btn-sm" data-toggle="modal" data-target="#maintenanceCreateModal"><i class="fas fa-plus mr-1"></i> Tambah</button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered history-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th>Nominal</th>
                                            <th><i class="fas fa-paperclip"></i></th>
                                            <th width="100"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($inventoryItemDetail->maintenanceTransactions as $transaction)
                                            <tr>
                                                <td data-label="Kode transaksi">{{ str_pad($transaction->id, 3, '0', STR_PAD_LEFT) }}</td>
                                                <td data-label="Tanggal">{{ optional($transaction->transaction_date)->format('Y-m-d') }}</td>
                                                <td data-label="Keterangan">{{ $transaction->description }}</td>
                                                <td data-label="Nominal">{{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                                <td data-label="Lampiran">
                                                    @if($transaction->attachments->count())
                                                        <div class="d-flex flex-column" style="gap: 6px;">
                                                            @foreach($transaction->attachments as $attachment)
                                                                <div class="attachment-actions">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary js-preview-attachment" data-url="{{ route('perawatan-alat-mesin.maintenance.attachments.file', $attachment) }}" data-name="{{ $attachment->original_name ?: basename($attachment->file_path) }}" title="Preview"><i class="fas fa-eye"></i></button>
                                                                    <a href="{{ route('perawatan-alat-mesin.maintenance.attachments.file', $attachment) }}" target="_blank" style="font-size:.8rem">{{ Str::limit($attachment->original_name ?: basename($attachment->file_path), 20) }}</a>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-right" data-label="Aksi">
                                                    <div class="d-flex flex-wrap justify-content-end" style="gap: 6px;">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-primary js-open-edit"
                                                            data-id="{{ $transaction->id }}"
                                                            data-item="{{ $transaction->inventory_item_id }}"
                                                            data-detail="{{ $transaction->inventory_item_detail_id }}"
                                                            data-date="{{ optional($transaction->transaction_date)->format('Y-m-d') }}"
                                                            data-description="{{ e($transaction->description) }}"
                                                            data-amount="{{ $transaction->amount }}"
                                                            data-update-url="{{ route('perawatan-alat-mesin.maintenance.update', $transaction) }}"
                                                        title="Edit"><i class="fas fa-pen"></i></button>
                                                        <form method="POST" action="{{ route('perawatan-alat-mesin.maintenance.destroy', $transaction) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="redirect_detail_id" value="{{ $inventoryItemDetail->id }}">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6"><div class="inventory-module-empty"><i class="far fa-folder-open" style="margin-right:4px"></i> Belum ada transaksi</div></td>
                                            </tr>
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
</div>

<div class="modal fade" id="maintenanceCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('perawatan-alat-mesin.maintenance.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="redirect_detail_id" value="{{ $inventoryItemDetail->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Transaksi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Barang</label>
                            <select name="inventory_item_id" id="maintenance_create_item" class="form-control" required>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" {{ $inventoryItemDetail->inventory_item_id == $item->id ? 'selected' : '' }}>{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sub Barang</label>
                            <select name="inventory_item_detail_id" id="maintenance_create_detail" class="form-control">
                                @foreach($details as $detail)
                                    <option value="{{ $detail->id }}" data-item="{{ $detail->inventory_item_id }}" {{ $inventoryItemDetail->id == $detail->id ? 'selected' : '' }}>{{ $detail->sub_code }} - {{ $detail->name }}</option>
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

<div class="modal fade" id="maintenanceEditModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="#" id="maintenanceEditForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_detail_id" value="{{ $inventoryItemDetail->id }}">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Transaksi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Barang</label>
                            <select name="inventory_item_id" id="maintenance_edit_item" class="form-control" required>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sub Barang</label>
                            <select name="inventory_item_detail_id" id="maintenance_edit_detail" class="form-control">
                                @foreach($details as $detail)
                                    <option value="{{ $detail->id }}" data-item="{{ $detail->inventory_item_id }}">{{ $detail->sub_code }} - {{ $detail->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Tanggal</label>
                            <input type="date" name="transaction_date" id="maintenance_edit_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nominal</label>
                            <input type="number" min="0" step="0.01" name="amount" id="maintenance_edit_amount" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Lampiran tambahan</label>
                            <input type="file" name="attachments[]" class="form-control-file" multiple>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Keterangan</label>
                        <textarea name="description" id="maintenance_edit_description" class="form-control" rows="3" required></textarea>
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

<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attachmentPreviewTitle">Preview Lampiran</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <iframe id="attachmentPreviewFrame" class="attachment-preview-frame" src="about:blank"></iframe>
            </div>
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
            Array.prototype.forEach.call(detailSelect.options, function (option) {
                option.hidden = selectedItem && option.dataset.item !== selectedItem;
            });
        }

        filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail');

        var createItem = document.getElementById('maintenance_create_item');
        if (createItem) {
            createItem.addEventListener('change', function () {
                filterDetailOptions('#maintenance_create_item', '#maintenance_create_detail');
            });
        }

        var editItem = document.getElementById('maintenance_edit_item');
        if (editItem) {
            editItem.addEventListener('change', function () {
                filterDetailOptions('#maintenance_edit_item', '#maintenance_edit_detail');
            });
        }

        document.querySelectorAll('.js-open-edit').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('maintenanceEditForm').setAttribute('action', button.getAttribute('data-update-url'));
                document.getElementById('maintenance_edit_item').value = button.getAttribute('data-item') || '';
                filterDetailOptions('#maintenance_edit_item', '#maintenance_edit_detail');
                document.getElementById('maintenance_edit_detail').value = button.getAttribute('data-detail') || '';
                document.getElementById('maintenance_edit_date').value = button.getAttribute('data-date') || '';
                document.getElementById('maintenance_edit_amount').value = button.getAttribute('data-amount') || '';
                document.getElementById('maintenance_edit_description').value = button.getAttribute('data-description') || '';

                if (window.jQuery) {
                    window.jQuery('#maintenanceEditModal').modal('show');
                }
            });
        });

        document.querySelectorAll('.js-preview-attachment').forEach(function (button) {
            button.addEventListener('click', function () {
                var url = button.getAttribute('data-url');
                var name = button.getAttribute('data-name') || 'Preview Lampiran';
                document.getElementById('attachmentPreviewTitle').textContent = name;
                document.getElementById('attachmentPreviewFrame').setAttribute('src', url);

                if (window.jQuery) {
                    window.jQuery('#attachmentPreviewModal').modal('show');
                }
            });
        });

        if (window.jQuery) {
            window.jQuery('#attachmentPreviewModal').on('hidden.bs.modal', function () {
                document.getElementById('attachmentPreviewFrame').setAttribute('src', 'about:blank');
            });
        }
    })();
</script>
@endpush
