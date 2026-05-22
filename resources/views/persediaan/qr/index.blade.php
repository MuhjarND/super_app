@extends('layouts.app')

@section('title', 'Cetak QR Code')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="inventory-module-title mb-0">Cetak QR Code</h1>
        <a href="{{ route('perawatan-alat-mesin.qrcode.print') }}" target="_blank" class="btn app-create-btn btn-sm"><i class="fas fa-print mr-1"></i> Cetak Semua</a>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-qrcode text-muted mr-1" style="font-size:.82rem"></i> Daftar QR Sub Barang</div>
        </div>
        <div class="table-responsive">
            <table class="table inventory-module-table mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Sub Barang</th>
                        <th>QR Token</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($details as $detail)
                        <tr>
                            <td>{{ $detail->sub_code }}</td>
                            <td>{{ optional($detail->item)->name ?: '-' }}</td>
                            <td>{{ $detail->name }}</td>
                            <td><code>{{ $detail->qr_token }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Belum ada sub barang
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="inventory-module-board-body pt-3 pb-3">{{ $details->links() }}</div>
    </div>
</div>
@endsection
