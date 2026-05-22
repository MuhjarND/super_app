@extends('layouts.app')

@section('title', 'Barang Persediaan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="inventory-module-title mb-0">Barang Persediaan</h1>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-clipboard-list mr-1"></i> Pengajuan</a>
        <button type="button" class="btn btn-sm app-create-btn" data-toggle="modal" data-target="#createSupplyItemModal">
            <i class="fas fa-plus mr-1"></i> Tambah
        </button>
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="inventory-module-board-title"><i class="fas fa-boxes text-muted mr-1"></i> Data Barang</div>
            <span class="inventory-module-chip">{{ $items->total() }}</span>
        </div>
        <div class="inventory-module-board-body p-0">
            <table class="table inventory-module-table supply-table mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Gambar</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Stok</th>
                        <th>Min</th>
                        <th>Status</th>
                        <th width="80"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td data-label="Kode">{{ $item->code ?: '-' }}</td>
                            <td data-label="Gambar">
                                @if($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="supply-image-thumb">
                                @else
                                    <span class="supply-image-thumb d-inline-flex align-items-center justify-content-center"><i class="fas fa-box text-muted"></i></span>
                                @endif
                            </td>
                            <td data-label="Nama Barang">
                                <strong>{{ $item->name }}</strong>
                                @if($item->description)<div class="inventory-module-muted">{{ $item->description }}</div>@endif
                            </td>
                            <td data-label="Satuan">{{ $item->unit }}</td>
                            <td data-label="Stok"><span class="supply-stock-pill {{ $item->is_low_stock ? 'low' : '' }}">{{ $item->stock_label }}</span></td>
                            <td data-label="Min">{{ number_format($item->minimum_stock, 0, ',', '.') }}</td>
                            <td data-label="Status">
                                <span class="badge badge-{{ $item->is_active ? 'success' : 'secondary' }} app-status-badge">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td data-label="Aksi">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editSupplyItemModal{{ $item->id }}" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Belum ada barang persediaan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $items->links() }}</div>

<div class="modal fade" id="createSupplyItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Barang</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST" action="{{ route('persediaan.items.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @include('persediaan.supplies.items._form', ['mode' => 'create', 'item' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn app-create-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($items as $item)
    <div class="modal fade" id="editSupplyItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Barang</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('persediaan.items.update', $item) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        @include('persediaan.supplies.items._form', ['mode' => 'edit', 'item' => $item])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn app-create-btn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<script>
    (function () {
        var hasErrors = {{ $errors->any() ? 'true' : 'false' }};
        if (hasErrors) {
            $('#createSupplyItemModal').modal('show');
        }
    })();
</script>
@endpush
