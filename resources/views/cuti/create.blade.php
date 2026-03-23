@extends('layouts.app')

@section('title', 'Pengajuan Cuti')

@section('content')
@include('admin._alerts')
<div class="alert alert-info border-0 shadow-sm">
    Pengisian pengajuan cuti sekarang dilakukan dari halaman <a href="{{ route('cuti.index') }}" class="font-weight-600">Pengajuan Cuti</a> melalui pop-up form.
</div>
@endsection
