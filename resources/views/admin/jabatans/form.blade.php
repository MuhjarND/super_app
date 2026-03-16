@extends('layouts.app')

@php
    $isEdit = $jabatan->exists;
@endphp

@section('title', $isEdit ? 'Edit Jabatan' : 'Tambah Jabatan')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>{{ $isEdit ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h1>
                <a href="{{ route('admin.jabatans.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <form method="POST" action="{{ $isEdit ? route('admin.jabatans.update', $jabatan) : route('admin.jabatans.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Nama Jabatan</label>
                        <input type="text" name="nama" class="form-control" value="{{ old('nama', $jabatan->nama) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="{{ old('kode', $jabatan->kode) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Level</label>
                        <input type="number" name="level" class="form-control" value="{{ old('level', $jabatan->level ?: 1) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Parent Jabatan</label>
                        <select name="parent_id" class="form-control">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ (string) old('parent_id', $jabatan->parent_id) === (string) $parent->id ? 'selected' : '' }}>
                                    {{ $parent->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Unit</label>
                        <select name="unit_id" class="form-control">
                            <option value="">-- Pilih Unit --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (string) old('unit_id', $jabatan->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
