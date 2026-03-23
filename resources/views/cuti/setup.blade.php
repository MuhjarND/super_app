@extends('layouts.app')

@section('title', 'Cuti')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="mb-3">{{ $title ?? 'Modul Cuti Belum Diaktifkan' }}</h4>
                <p class="text-muted mb-3">{{ $message ?? 'Schema modul cuti belum dijalankan pada database.' }}</p>
                <div class="alert alert-warning mb-0">Aktivasi berikutnya memerlukan review migration manual dan persetujuan Anda. Saya tidak menjalankan migrasi otomatis.</div>
            </div>
        </div>
    </div>
</div>
@endsection
