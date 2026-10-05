@extends('layouts.app')

@section('title', 'Cetak Formulir Pengajuan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="inventory-module-title mb-1">Cetak Formulir Pengajuan</h1>
        <div class="inventory-module-subtitle">Formulir permintaan ATK per pengajuan, dengan kop surat resmi.</div>
    </div>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Daftar Pengajuan</a>
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-filter text-muted mr-1"></i> Filter Cetak</div>
        </div>
        <div class="inventory-module-board-body">
            <form method="GET" action="{{ route('persediaan.requests.report') }}" class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label for="reportMonth" class="mb-1">Bulan Pengajuan</label>
                    <input type="month" name="month" id="reportMonth" class="form-control form-control-sm" value="{{ $filters['month'] ?? '' }}">
                    <small class="form-text text-muted">Kosongkan untuk seluruh riwayat pengajuan.</small>
                </div>
                @if($canManage)
                    <div class="col-md-5 mb-3">
                        <label for="reportUser" class="mb-1">Pegawai</label>
                        <select name="user_id" id="reportUser" class="form-control form-control-sm">
                            <option value="">Semua pegawai</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}{{ $user->nip ? ' - ' . $user->nip : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3 mb-3 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                    <a href="{{ route('persediaan.requests.report.pdf', array_filter($filters, function ($value) { return $value !== null && $value !== ''; })) }}" class="btn btn-sm app-create-btn" target="_blank" rel="noopener">
                        <i class="fas fa-file-pdf mr-1"></i> Cetak PDF
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="inventory-module-board mt-3">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="inventory-module-board-title"><i class="fas fa-list text-muted mr-1"></i> Daftar Formulir</div>
            <span class="inventory-module-chip">{{ $requests->total() }} pengajuan</span>
        </div>
        <div class="inventory-module-board-body p-0">
            <table class="table inventory-module-table supply-table mb-0">
                <thead>
                    <tr>
                        <th>Nomor</th>
                        @if($canManage)<th>Pegawai</th>@endif
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Status</th>
                        <th width="95"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td data-label="Nomor">{{ $request->request_number }}</td>
                            @if($canManage)<td data-label="Pegawai">{{ optional($request->requester)->name ?: '-' }}</td>@endif
                            <td data-label="Tanggal">{{ optional($request->submitted_at ?: $request->created_at)->translatedFormat('d/m/Y') ?: '-' }}</td>
                            <td data-label="Barang">{{ $request->items_summary ?: '-' }}</td>
                            <td data-label="Status">{!! $request->status_badge !!}</td>
                            <td data-label="Aksi">
                                <a href="{{ route('persediaan.requests.print', $request) }}" class="app-icon-btn detail" data-mobile-label="Cetak" title="Cetak formulir"><i class="fas fa-print"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Tidak ada pengajuan pada filter ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $requests->appends(request()->query())->links() }}</div>
@endsection
