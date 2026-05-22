@extends('layouts.app')

@section('title', 'Persediaan Belum Siap')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-database mr-1"></i> Persediaan Belum Siap</div>
        </div>
        <div class="inventory-module-board-body">
            <div class="inventory-module-empty mb-0">
                <i class="fas fa-tools"></i>
                Jalankan migrasi database terlebih dahulu.
            </div>
        </div>
    </div>
</div>
@endsection
