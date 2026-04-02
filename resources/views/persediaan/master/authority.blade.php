@extends('layouts.app')

@section('title', 'Kuasa Pengguna Barang')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero">
        <h1 class="inventory-module-title mb-1">Kuasa Pengguna Barang</h1>
        <p class="inventory-module-subtitle mb-0">Data penanggung jawab utama yang dipakai pada laporan dan dokumen perawatan alat dan mesin.</p>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">Pengelola utama barang</div>
            <div class="inventory-module-board-subtitle">Simpan identitas kuasa pengguna barang yang akan tampil di laporan dan dokumen.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="inventory-module-panel">
                        <div class="inventory-module-panel-body">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.authority.store') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ optional($authority)->id }}">
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" name="name" class="form-control" value="{{ optional($authority)->name }}" required>
                                </div>
                                <div class="form-group">
                                    <label>NIP</label>
                                    <input type="text" name="nip" class="form-control" value="{{ optional($authority)->nip }}">
                                </div>
                                <div class="form-group">
                                    <label>Jabatan</label>
                                    <input type="text" name="position" class="form-control" value="{{ optional($authority)->position }}">
                                </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="notes" class="form-control" rows="3">{{ optional($authority)->notes }}</textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ optional($authority)->is_active ?? true ? 'checked' : '' }}>
                                    <label for="is_active" class="form-check-label">Aktif</label>
                                </div>
                                <button class="btn app-create-btn btn-block">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
