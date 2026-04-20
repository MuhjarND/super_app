@extends('layouts.app')

@section('title', 'Jenis Cuti')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .leave-type-top {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px;
        }

        .leave-type-top .btn,
        .leave-type-filter .btn {
            width: 100%;
        }

        .leave-type-filter .row {
            display: block;
        }

        .leave-type-filter .form-group,
        .leave-type-filter .d-flex {
            margin-bottom: 10px;
        }

        .leave-type-table,
        .leave-type-table thead,
        .leave-type-table tbody,
        .leave-type-table tr,
        .leave-type-table th,
        .leave-type-table td {
            display: block;
            width: 100%;
        }

        .leave-type-table thead {
            display: none;
        }

        .leave-type-table tbody tr {
            padding: 14px 14px 12px;
            border-bottom: 1px solid #e8eaed;
        }

        .leave-type-table td {
            padding: 0 0 10px;
            border: 0;
        }

        .leave-type-table td:last-child {
            padding-bottom: 0;
        }

        .leave-type-table td::before {
            content: attr(data-label);
            display: block;
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #94a3b8;
        }
    }
</style>
@endpush

@section('content')
@include('admin._alerts')
<div class="d-flex justify-content-between align-items-center mb-3 leave-type-top">
    <div>
        <h3 class="mb-1">Jenis Cuti</h3>
        <p class="text-muted mb-0">Kelola tipe cuti dan rule dasar yang berlaku.</p>
    </div>
    <button class="btn app-create-btn" data-toggle="modal" data-target="#createLeaveTypeModal"><i class="fas fa-plus"></i> Tambah Jenis</button>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <form method="GET" action="{{ route('cuti.master.types.index') }}" class="leave-type-filter">
            <div class="row">
                <div class="col-md-8 form-group mb-md-0"><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode, nama, atau deskripsi"></div>
                <div class="col-md-2 form-group mb-md-0">
                    <select name="status" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex" style="gap:6px;">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    <a href="{{ route('cuti.master.types.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 leave-type-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Rule</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveTypes as $leaveType)
                        <tr>
                            <td data-label="Kode">{{ $leaveType->code }}</td>
                            <td data-label="Nama">
                                <div class="font-weight-600">{{ $leaveType->name }}</div>
                                <small class="text-muted">{{ $leaveType->description ?: '-' }}</small>
                            </td>
                            <td data-label="Rule">
                                <div class="small text-muted">
                                    Masa kerja: {{ (int) $leaveType->service_years_required }} th<br>
                                    Maks hari: {{ $leaveType->max_days ?: '-' }} | Maks bulan: {{ $leaveType->max_months ?: '-' }}<br>
                                    Saldo: {{ $leaveType->requires_balance ? 'Ya' : 'Tidak' }} | Dokumen: {{ $leaveType->requires_document ? 'Ya' : 'Tidak' }}
                                </div>
                            </td>
                            <td data-label="Status"><span class="badge badge-{{ $leaveType->status === 'active' ? 'success' : 'secondary' }}">{{ $leaveType->status_label }}</span></td>
                            <td class="app-action-cell" data-label="Aksi">
                                <div class="app-action-group">
                                <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal" data-target="#editLeaveTypeModal{{ $leaveType->id }}"><i class="fas fa-pen"></i></button>
                                <form method="POST" action="{{ route('cuti.master.types.destroy', $leaveType) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus" onclick="return confirm('Hapus jenis cuti ini?')"><i class="fas fa-trash"></i></button>
                                </form>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="editLeaveTypeModal{{ $leaveType->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg"><div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">Edit Jenis Cuti</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                                <form method="POST" action="{{ route('cuti.master.types.update', $leaveType) }}">@csrf @method('PUT')
                                    <div class="modal-body">@include('cuti.master.types.partials.form', ['item' => $leaveType])</div>
                                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                                </form>
                            </div></div>
                        </div>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada jenis cuti.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">{{ $leaveTypes->links() }}</div>
</div>
<div class="modal fade" id="createLeaveTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Tambah Jenis Cuti</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form method="POST" action="{{ route('cuti.master.types.store') }}">@csrf
            <div class="modal-body">@include('cuti.master.types.partials.form', ['item' => null])</div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>
@endsection
