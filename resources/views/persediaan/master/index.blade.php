@extends('layouts.app')

@section('title', $meta['title'])

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero">
        <h1 class="inventory-module-title mb-1">{{ $meta['title'] }}</h1>
        <p class="inventory-module-subtitle mb-0">Kelola data master alat dan mesin agar konsisten dengan modul perawatan.</p>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">{{ $meta['title'] }}</div>
            <div class="inventory-module-board-subtitle">Tambah dan perbarui data referensi yang dipakai di seluruh modul perawatan.</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Tambah Data</div>
                            <p class="inventory-module-panel-subtitle">Masukkan referensi baru untuk {{ strtolower($meta['title']) }}.</p>
                        </div>
                        <div class="inventory-module-panel-body">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.master.store', $type) }}">
                                @csrf
                                <div class="form-group">
                                    <label>{{ $meta['name_label'] }}</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>{{ $meta['description_label'] }}</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <button class="btn app-create-btn btn-block">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-4">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title">Daftar Data</div>
                            <p class="inventory-module-panel-subtitle">Edit cepat dan hapus data referensi yang tidak dipakai.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-module-table mb-0">
                                <thead><tr><th>Nama</th><th>Keterangan</th><th width="260">Aksi</th></tr></thead>
                                <tbody>
                                    @forelse($rows as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ $row->description ?: '-' }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('perawatan-alat-mesin.master.update', [$type, $row->id]) }}" class="d-flex gap-2 align-items-start flex-wrap">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" value="{{ $row->name }}" class="form-control form-control-sm" style="max-width: 150px;" required>
                                                    <input type="text" name="description" value="{{ $row->description }}" class="form-control form-control-sm" style="max-width: 180px;">
                                                    <button class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                                <form method="POST" action="{{ route('perawatan-alat-mesin.master.destroy', [$type, $row->id]) }}" onsubmit="return confirm('Hapus data ini?')" class="mt-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
