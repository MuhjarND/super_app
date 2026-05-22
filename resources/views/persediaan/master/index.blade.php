@extends('layouts.app')

@section('title', $meta['title'])

@section('content-header')
<div class="container-fluid">
    <div class="inventory-module-hero">
        <h1 class="inventory-module-title mb-0">{{ $meta['title'] }}</h1>
    </div>
</div>
@endsection

@section('content')
@include('persediaan.partials.module-styles')

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title">{{ $meta['title'] }}</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title"><i class="fas fa-plus text-muted mr-1" style="font-size:.78rem"></i> Tambah Data</div>
                        </div>
                        <div class="inventory-module-panel-body">
                            <form method="POST" action="{{ route('perawatan-alat-mesin.master.store', $type) }}">
                                @csrf
                                <div class="form-group">
                                    <label>{{ $meta['name_label'] }}</label>
                                    <input type="text" name="name" class="form-control form-control-sm" required>
                                </div>
                                <div class="form-group">
                                    <label>{{ $meta['description_label'] }}</label>
                                    <textarea name="description" class="form-control form-control-sm" rows="3"></textarea>
                                </div>
                                <button class="btn app-create-btn btn-block btn-sm">Simpan</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-3">
                    <div class="inventory-module-panel h-100">
                        <div class="inventory-module-panel-header">
                            <div class="inventory-module-panel-title"><i class="fas fa-list text-muted mr-1" style="font-size:.78rem"></i> Daftar Referensi</div>
                        </div>
                        <div class="table-responsive">
                            <table class="table inventory-module-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Keterangan</th>
                                        <th width="320">Kelola</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ $row->description ?: '-' }}</td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 4px;">
                                                    <form method="POST" action="{{ route('perawatan-alat-mesin.master.update', [$type, $row->id]) }}" class="d-flex align-items-center mb-0" style="gap: 4px;">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="text" name="name" value="{{ $row->name }}" class="form-control form-control-sm" style="width: 110px;" required>
                                                        <input type="text" name="description" value="{{ $row->description }}" class="form-control form-control-sm" style="width: 130px;" placeholder="-">
                                                        <button class="btn btn-sm btn-primary" title="Update"><i class="fas fa-check"></i></button>
                                                    </form>
                                                    <form method="POST" action="{{ route('perawatan-alat-mesin.master.destroy', [$type, $row->id]) }}" onsubmit="return confirm('Hapus data ini?')" class="mb-0">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                                    <i class="far fa-folder-open"></i> Belum ada data
                                                </div>
                                            </td>
                                        </tr>
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
