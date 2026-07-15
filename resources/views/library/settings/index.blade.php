@extends('layouts.app')
@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan Sistem')
@section('page-subtitle', 'Konfigurasi aplikasi perpustakaan')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('library.settings.update') }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-building me-2"></i>Informasi Perpustakaan</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Perpustakaan <span class="text-danger">*</span></label>
                            <input type="text" name="library_name" class="form-control"
                                value="{{ old('library_name', $settings['library_name'] ?? 'Perpustakaan SiPerpus') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat Perpustakaan</label>
                            <textarea name="library_address" class="form-control" rows="2">{{ old('library_address', $settings['library_address'] ?? '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="library_phone" class="form-control"
                                value="{{ old('library_phone', $settings['library_phone'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-gear me-2"></i>Aturan Peminjaman</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Durasi Pinjam (hari) <span class="text-danger">*</span></label>
                            <input type="number" name="loan_days" class="form-control"
                                value="{{ old('loan_days', $settings['loan_days'] ?? 7) }}" min="1" max="90" required>
                            <small class="text-muted">Default jangka waktu peminjaman</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Maks. Buku per Pinjaman <span class="text-danger">*</span></label>
                            <input type="number" name="max_books_per_loan" class="form-control"
                                value="{{ old('max_books_per_loan', $settings['max_books_per_loan'] ?? 3) }}" min="1" max="20" required>
                            <small class="text-muted">Jumlah maksimal buku per transaksi</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Denda per Hari (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="fine_per_day" class="form-control"
                                    value="{{ old('fine_per_day', $settings['fine_per_day'] ?? 1000) }}" min="0" required>
                            </div>
                            <small class="text-muted">Denda keterlambatan per hari per buku</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-footer bg-transparent d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
