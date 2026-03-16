@extends('layouts.app')

@php
    $isEdit = $unit->exists;
@endphp

@section('title', $isEdit ? 'Edit Unit' : 'Tambah Unit')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>{{ $isEdit ? 'Edit Unit' : 'Tambah Unit' }}</h1>
                <a href="{{ route('admin.units.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <form method="POST" action="{{ $isEdit ? route('admin.units.update', $unit) : route('admin.units.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Nama Unit</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', $unit->nama) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="{{ old('kode', $unit->kode) }}" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="4">{{ old('keterangan', $unit->keterangan) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
