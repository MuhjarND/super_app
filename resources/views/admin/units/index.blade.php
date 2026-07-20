@extends('layouts.app')

@section('title', 'Kelola Unit')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .admin-units-top {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }

            .admin-units-top .btn,
            .admin-units-filter .btn {
                width: 100%;
            }

            .admin-units-filter .row {
                display: block;
            }

            .admin-units-filter .form-group,
            .admin-units-filter .d-flex {
                margin-bottom: 10px;
            }

            .admin-units-table,
            .admin-units-table thead,
            .admin-units-table tbody,
            .admin-units-table tr,
            .admin-units-table th,
            .admin-units-table td {
                display: block;
                width: 100%;
            }

            .admin-units-table thead {
                display: none;
            }

            .admin-units-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e8eaed;
            }

            .admin-units-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .admin-units-table td:last-child {
                padding-bottom: 0;
            }

            .admin-units-table td::before {
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

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1>Unit Kerja</h1>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.units.index') }}" class="admin-units-filter">
                <div class="row">
                    <div class="col-md-10 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama, kode, atau keterangan unit">
                    </div>
                    <div class="col-md-2 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.units.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-units-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode</th>
                            <th>Keterangan</th>
                            <th>User</th>
                            <th>Jabatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($units as $unit)
                            <tr>
                                <td data-label="Nama">{{ $unit->nama }}</td>
                                <td data-label="Kode">{{ $unit->kode }}</td>
                                <td data-label="Keterangan">{{ $unit->keterangan ?: '-' }}</td>
                                <td data-label="User">{{ $unit->users_count }}</td>
                                <td data-label="Jabatan">{{ $unit->jabatans_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Belum ada data unit.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $units->links() }}
        </div>
    </div>
@endsection
