@extends('layouts.app')

@section('title', 'Cetak QR Code Alat dan Mesin')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="inventory-module-title mb-1">Cetak QR Code</h1>
            <p class="inventory-module-subtitle mb-0">Cetak QR code untuk setiap sub barang alat dan mesin.</p>
        </div>
        <a href="{{ route('perawatan-alat-mesin.qrcode.print') }}" target="_blank" class="btn app-create-btn"><i class="fas fa-print mr-1"></i> Cetak Semua</a>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Daftar QR sub barang</div>
            <div class="inventory-module-board-subtitle">Gunakan daftar ini untuk memastikan setiap sub barang sudah memiliki QR token.</div>
        </div>
        <div class="table-responsive">
            <table class="table inventory-module-table mb-0">
                <thead><tr><th>Kode</th><th>Barang</th><th>Sub Barang</th><th>QR Token</th></tr></thead>
                <tbody>
                    @forelse($details as $detail)
                        <tr>
                            <td>{{ $detail->sub_code }}</td>
                            <td>{{ optional($detail->item)->name ?: '-' }}</td>
                            <td>{{ $detail->name }}</td>
                            <td><small>{{ $detail->qr_token }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada sub barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="inventory-module-board-body pt-3 pb-3">{{ $details->links() }}</div>
    </div>
</div>
@endsection
