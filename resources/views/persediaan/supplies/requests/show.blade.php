@extends('layouts.app')

@section('title', 'Detail Pengajuan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center flex-wrap gap-2">
        <h1 class="inventory-module-title mb-0">Detail Pengajuan</h1>
        <span class="inventory-module-chip">{{ $supplyRequest->request_number }}</span>
    </div>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        @if($canManage && $supplyRequest->status === \App\SupplyRequest::STATUS_PENDING)
            <button type="button" class="btn btn-sm app-create-btn" data-toggle="modal" data-target="#fulfillSupplyRequestModal">
                <i class="fas fa-box-open mr-1"></i> Serahkan
            </button>
        @endif
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-info-circle text-muted mr-1"></i> Ringkasan Pengajuan</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row mb-3">
                <div class="col-md-3 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Pemohon</div>
                        <div class="supply-stat-value">{{ optional($supplyRequest->requester)->name ?: '-' }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Tanggal</div>
                        <div class="supply-stat-value">{{ optional($supplyRequest->submitted_at)->translatedFormat('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Jumlah</div>
                        <div class="supply-stat-value">{{ $supplyRequest->quantity_summary }}</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="inventory-module-stat">
                        <div class="inventory-module-stat-label">Status</div>
                        <div class="mt-2">{!! $supplyRequest->status_badge !!}</div>
                    </div>
                </div>
            </div>

            <div class="inventory-module-panel mb-3">
                <div class="inventory-module-panel-header">
                    <div class="inventory-module-panel-title"><i class="fas fa-comment-alt text-muted mr-1"></i> Keperluan</div>
                </div>
                <div class="inventory-module-panel-body supply-readable-text">
                    <p class="mb-0">{{ $supplyRequest->purpose }}</p>
                    @if($supplyRequest->operator_note)
                        <hr class="my-2">
                        <strong>Catatan:</strong>
                        <p class="mb-0 text-muted">{{ $supplyRequest->operator_note }}</p>
                    @endif
                </div>
            </div>

            <div class="inventory-module-panel">
                <div class="inventory-module-panel-header">
                    <div class="inventory-module-panel-title"><i class="fas fa-th-list text-muted mr-1"></i> Daftar Barang</div>
                </div>
                <div class="inventory-module-panel-body p-0">
                    <table class="table inventory-module-table supply-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Diminta</th>
                                <th>Diserahkan</th>
                                <th>Stok Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($supplyRequest->items as $item)
                                <tr>
                                    <td data-label="Nama Barang">
                                        <strong>{{ $item->item_name_snapshot }}</strong>
                                        @unless($item->supply_item_id)
                                            <div class="inventory-module-muted">Barang baru</div>
                                        @endunless
                                    </td>
                                    <td data-label="Diminta">{{ $item->quantity_label }}</td>
                                    <td data-label="Diserahkan">{{ number_format($item->quantity_fulfilled, 0, ',', '.') }} {{ $item->unit_snapshot }}</td>
                                    <td data-label="Stok">{{ $item->item ? $item->item->stock_label : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($canManage && $supplyRequest->status === \App\SupplyRequest::STATUS_PENDING)
                <div class="inventory-module-panel mt-3">
                    <div class="inventory-module-panel-header">
                        <div class="inventory-module-panel-title"><i class="fas fa-gavel text-muted mr-1"></i> Aksi Operator</div>
                    </div>
                    <div class="inventory-module-panel-body">
                        <div class="supply-action-row">
                            <button type="button" class="btn btn-sm app-create-btn" data-toggle="modal" data-target="#fulfillSupplyRequestModal">
                                <i class="fas fa-signature mr-1"></i> Serahkan
                            </button>
                            <form method="POST" action="{{ route('persediaan.requests.reject', $supplyRequest) }}" class="form-inline mb-0 ml-md-3">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="operator_note" class="form-control" placeholder="Catatan penolakan" required>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tolak pengajuan ini?')">Tolak</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($canManage && $supplyRequest->status === \App\SupplyRequest::STATUS_PENDING)
    <div class="modal fade" id="fulfillSupplyRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2 px-3">
                    <h5 class="modal-title">Serahkan Barang</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('persediaan.requests.fulfill', $supplyRequest) }}" class="mb-0">
                    @csrf
                    <div class="modal-body py-2 px-3">
                        <div class="alert alert-info py-2 px-3 mb-2 supply-note-alert">Serah terima memakai tanda tangan profil user penerima.</div>
                        <div class="form-group mb-2">
                            <label class="mb-1">Catatan</label>
                            <textarea name="operator_note" rows="2" class="form-control form-control-sm" placeholder="Opsional"></textarea>
                        </div>
                        @include('partials.profile-signature-notice')
                    </div>
                    <div class="modal-footer py-2 px-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm app-create-btn">Simpan & Serahkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
