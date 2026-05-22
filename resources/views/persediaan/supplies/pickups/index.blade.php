@extends('layouts.app')

@section('title', 'Barang Diambil')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="inventory-module-title mb-0">Barang Diambil</h1>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.create') }}" class="btn btn-sm app-create-btn"><i class="fas fa-shopping-cart mr-1"></i> Ajukan</a>
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-clipboard-list mr-1"></i> Pengajuan</a>
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="inventory-module-board-title"><i class="fas fa-history text-muted mr-1"></i> Riwayat Pengambilan</div>
            <form method="GET" action="{{ route('persediaan.pickups.index') }}" class="form-inline mb-0 supply-filter-form">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm mr-1 supply-search-compact" placeholder="Cari...">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="inventory-module-board-body p-0">
            <table class="table inventory-module-table supply-table mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Pegawai</th>
                        <th>Paraf</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pickups as $pickup)
                        <tr>
                            <td data-label="No">{{ $loop->iteration + ($pickups->currentPage() - 1) * $pickups->perPage() }}</td>
                            <td data-label="Tanggal">{{ optional($pickup->pickup_date)->translatedFormat('d/m/Y') }}</td>
                            <td data-label="Nama Barang">
                                <strong>{{ $pickup->item_name_snapshot }}</strong>
                                @if($pickup->request)
                                    <div class="inventory-module-muted">
                                        <a href="{{ route('persediaan.requests.show', $pickup->request) }}">{{ $pickup->request->request_number }}</a>
                                    </div>
                                @endif
                            </td>
                            <td data-label="Jumlah">{{ $pickup->quantity_label }}</td>
                            <td data-label="Pegawai">{{ optional($pickup->user)->name ?: '-' }}</td>
                            <td data-label="Paraf">
                                @if($pickup->receiver_signature_path)
                                    <img src="{{ asset('storage/' . $pickup->receiver_signature_path) }}" alt="Paraf penerima" class="supply-signature-thumb">
                                @else
                                    <span class="inventory-module-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Belum ada pengambilan barang
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $pickups->appends(request()->query())->links() }}</div>
@endsection
