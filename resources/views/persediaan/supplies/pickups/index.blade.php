@extends('layouts.app')

@section('title', 'Barang Diambil')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')
@php
    $previousFilters = array_filter((array) old(), 'is_string');
@endphp

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
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-history text-muted mr-1"></i> Riwayat Pengambilan</div>
            <form method="GET" action="{{ route('persediaan.pickups.index') }}" class="supply-filter-form mt-2">
                <div class="form-row align-items-end">
                    <div class="col-12 col-md-4 form-group mb-2">
                        <label for="pickupSearch" class="mb-1">Pencarian</label>
                        <input type="search" name="search" id="pickupSearch" value="{{ $previousFilters['search'] ?? $filters['search'] ?? '' }}" maxlength="255" class="form-control form-control-sm" placeholder="Nama barang, kode, pegawai, atau no. pengajuan">
                    </div>
                    <div class="col-6 col-md-4 form-group mb-2">
                        <label for="pickupFrom" class="mb-1">Tanggal awal</label>
                        <input type="date" name="from" id="pickupFrom" value="{{ $previousFilters['from'] ?? $filters['from'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-4 form-group mb-2">
                        <label for="pickupTo" class="mb-1">Tanggal akhir</label>
                        <input type="date" name="to" id="pickupTo" value="{{ $previousFilters['to'] ?? $filters['to'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="supply-action-row">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                    <button type="submit" formaction="{{ route('persediaan.pickups.report.pdf') }}" class="btn btn-sm app-create-btn"><i class="fas fa-print mr-1"></i> Cetak PDF</button>
                    <a href="{{ route('persediaan.pickups.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
                <small class="form-text text-muted">Isi kedua tanggal untuk mencetak laporan.</small>
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
                        <th>Tanda tangan</th>
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
                            <td data-label="Tanda tangan">
                                @php
                                    $pickupVerificationUrl = \Illuminate\Support\Facades\URL::signedRoute('persediaan.pickups.verify', ['pickup' => $pickup->id]);
                                    $signatureExists = $pickup->receiver_signature_path
                                        && \Illuminate\Support\Facades\Storage::disk('public')->exists($pickup->receiver_signature_path);
                                @endphp
                                @if($signatureExists)
                                    <a href="{{ $pickupVerificationUrl }}" target="_blank" class="d-inline-flex align-items-center" title="Lihat bukti penerimaan">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($pickup->receiver_signature_path) }}" alt="Tanda tangan penerima" class="supply-signature-thumb">
                                        <span class="sr-only">Lihat bukti penerimaan</span>
                                    </a>
                                @else
                                    <span class="badge badge-warning">Belum ada tanda tangan</span>
                                @endif
                                <div class="inventory-module-muted mt-1">
                                    <a href="{{ $pickupVerificationUrl }}" target="_blank">Lihat bukti</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Tidak ada pengambilan barang pada daftar ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $pickups->links() }}</div>
@endsection
