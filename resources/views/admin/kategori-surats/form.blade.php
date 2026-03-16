@extends('layouts.app')

@php
    $isEdit = $kategoriSurat->exists;
@endphp

@section('title', $isEdit ? 'Edit Kategori Surat' : 'Tambah Kategori Surat')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>{{ $isEdit ? 'Edit Kategori Surat' : 'Tambah Kategori Surat' }}</h1>
                <a href="{{ route('admin.kategori-surats.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <form method="POST" action="{{ $isEdit ? route('admin.kategori-surats.update', $kategoriSurat) : route('admin.kategori-surats.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="{{ old('kode', $kategoriSurat->kode) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Nama</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', $kategoriSurat->nama) }}" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="4">{{ old('keterangan', $kategoriSurat->keterangan) }}</textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <div class="form-check">
                            <input type="checkbox" name="aktif" id="aktif" class="form-check-input" {{ old('aktif', $kategoriSurat->aktif ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="aktif">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
