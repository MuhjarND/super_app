@extends('layouts.app')

@section('title', 'Kuasa Pengguna Barang')

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero">
        <h1 class="inventory-module-title mb-0">Kuasa Pengguna Barang</h1>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-user-shield text-muted mr-1" style="font-size:.82rem"></i> Pengelola Utama Barang</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="inventory-module-panel">
                        <div class="inventory-module-panel-body">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.authority.store') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ optional($authority)->id }}">
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ optional($authority)->name }}" required>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>NIP</label>
                                        <input type="text" name="nip" class="form-control form-control-sm" value="{{ optional($authority)->nip }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Jabatan</label>
                                        <input type="text" name="position" class="form-control form-control-sm" value="{{ optional($authority)->position }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="3">{{ optional($authority)->notes }}</textarea>
                                </div>
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1" {{ optional($authority)->is_active ?? true ? 'checked' : '' }}>
                                    <label for="is_active" class="custom-control-label">Aktif</label>
                                </div>
                                <button class="btn app-create-btn btn-block btn-sm">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
