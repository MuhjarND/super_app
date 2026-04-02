@extends('layouts.app')

@section('title', 'Persediaan')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1>Persediaan</h1>
                <p class="text-muted mb-0">Ruang kerja terpisah untuk modul persediaan yang akan dikembangkan setelah modul perawatan alat dan mesin stabil.</p>
            </div>
            <span class="badge-dev">DEV</span>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase font-weight-600 mb-2">Fokus Modul</div>
                            <h4 class="mb-2">Persediaan Terpisah</h4>
                            <p class="text-muted mb-0">Modul ini disiapkan untuk stok, mutasi, dan kontrol persediaan yang berbeda dari siklus perawatan barang alat dan mesin.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase font-weight-600 mb-2">Status</div>
                            <h4 class="mb-2">Belum Diimplementasikan</h4>
                            <p class="text-muted mb-0">Navigasi sudah dipisah agar struktur aplikasi rapi dan tidak tercampur dengan modul perawatan alat dan mesin.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase font-weight-600 mb-2">Arah Lanjut</div>
                            <h4 class="mb-2">Siap Dikembangkan</h4>
                            <p class="text-muted mb-0">Saat ruang lingkup persediaan sudah jelas, modul ini bisa diisi tanpa mengganggu struktur route dan menu yang sudah dipisahkan sekarang.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Rencana Struktur Awal</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <div class="border rounded-lg p-3 h-100">
                                <div class="font-weight-700 mb-2">Master</div>
                                <div class="text-muted small">Barang persediaan, kategori, satuan, supplier, dan gudang.</div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="border rounded-lg p-3 h-100">
                                <div class="font-weight-700 mb-2">Transaksi</div>
                                <div class="text-muted small">Barang masuk, barang keluar, penyesuaian stok, dan mutasi.</div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="border rounded-lg p-3 h-100">
                                <div class="font-weight-700 mb-2">Laporan</div>
                                <div class="text-muted small">Stok akhir, kartu barang, rekap transaksi, dan monitoring minimum stok.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
