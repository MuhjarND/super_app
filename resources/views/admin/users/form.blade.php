@extends('layouts.app')

@php
    $isEdit = $user->exists;
@endphp

@section('title', $isEdit ? 'Edit User' : 'Tambah User')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1>{{ $isEdit ? 'Edit User' : 'Tambah User' }}</h1>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Nama</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Password {{ $isEdit ? '(opsional)' : '' }}</label>
                        <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }}>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Role</label>
                        <select name="role_id" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ (string) old('role_id', $selectedRole) === (string) $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Jabatan</label>
                        <select name="jabatan_id" class="form-control">
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach($jabatans as $jabatan)
                                <option value="{{ $jabatan->id }}" {{ (string) old('jabatan_id', $user->jabatan_id) === (string) $jabatan->id ? 'selected' : '' }}>
                                    {{ $jabatan->nama }}{{ $jabatan->unit ? ' - ' . $jabatan->unit->nama : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Unit</label>
                        <select name="unit_id" class="form-control">
                            <option value="">-- Pilih Unit --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (string) old('unit_id', $user->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>NIP</label>
                        <input type="text" name="nip" class="form-control" value="{{ old('nip', $user->nip) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}">
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
@endsection
