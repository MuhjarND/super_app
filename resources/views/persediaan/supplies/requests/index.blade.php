@extends('layouts.app')

@section('title', 'Pengajuan Persediaan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="inventory-module-title mb-0">{{ $canManage ? 'Pengajuan Persediaan' : 'Pengajuan Saya' }}</h1>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.create') }}" class="btn btn-sm app-create-btn"><i class="fas fa-shopping-cart mr-1"></i> Ajukan</a>
        <a href="{{ route('persediaan.pickups.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-box-open mr-1"></i> Pengambilan</a>
        <a href="{{ route('persediaan.requests.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
    </div>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="inventory-module-board-title"><i class="fas fa-clipboard-list text-muted mr-1"></i> Daftar Pengajuan</div>
            <form method="GET" action="{{ route('persediaan.requests.index') }}" class="form-inline mb-0 supply-filter-form">
                <select name="status" class="form-control form-control-sm mr-1">
                    <option value="">Semua status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="fulfilled" {{ request('status') === 'fulfilled' ? 'selected' : '' }}>Sudah Diambil</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="inventory-module-board-body p-0">
            <table class="table inventory-module-table supply-table mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        @if($canManage)<th>Pegawai</th>@endif
                        <th>Nama</th>
                        <th>Jumlah</th>
                        <th>Keperluan</th>
                        <th>Status</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $request)
                        <tr>
                            <td data-label="No">{{ $request->request_number }}</td>
                            @if($canManage)<td data-label="Pegawai">{{ optional($request->requester)->name ?: '-' }}</td>@endif
                            <td data-label="Nama">{{ $request->items_summary ?: '-' }}</td>
                            <td data-label="Jumlah">{{ $request->quantity_summary }}</td>
                            <td data-label="Keperluan">{{ \Illuminate\Support\Str::limit($request->purpose, 80) }}</td>
                            <td data-label="Status">{!! $request->status_badge !!}</td>
                            <td data-label="Aksi">
                                <div class="app-action-group">
                                    <a href="{{ route('persediaan.requests.show', $request) }}" class="app-icon-btn detail" data-mobile-label="Detail" title="Detail"><i class="fas fa-eye"></i></a>
                                    @if(!$canManage && $request->status === \App\SupplyRequest::STATUS_PENDING)
                                        <form method="POST" action="{{ route('persediaan.requests.cancel', $request) }}" onsubmit="return confirm('Batalkan pengajuan ini?')" class="mb-0">
                                            @csrf
                                            <button type="submit" class="app-icon-btn cancel text-danger" data-mobile-label="Batal" title="Batal"><i class="fas fa-ban"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 7 : 6 }}" class="text-center py-4">
                                <div class="inventory-module-empty border-0 bg-transparent p-0">
                                    <i class="far fa-folder-open"></i> Belum ada pengajuan
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
