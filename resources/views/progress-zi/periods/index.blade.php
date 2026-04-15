@extends('layouts.app')

@section('title', 'Master Periode ZI')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .zi-period-top {
            flex-direction: column;
            align-items: stretch !important;
        }

        .zi-period-top .app-create-btn {
            width: 100%;
        }

        .zi-period-table,
        .zi-period-table thead,
        .zi-period-table tbody,
        .zi-period-table tr,
        .zi-period-table th,
        .zi-period-table td {
            display: block;
            width: 100%;
        }

        .zi-period-table thead {
            display: none;
        }

        .zi-period-table tbody tr {
            padding: 14px 14px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .zi-period-table td {
            padding: 0 0 10px;
            border: 0;
        }

        .zi-period-table td:last-child {
            padding-bottom: 0;
        }

        .zi-period-table td::before {
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
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap zi-period-top" style="gap:12px;">
        <div>
            <h4 class="mb-1">Master Periode ZI</h4>
            <div class="text-muted">Kelola periode evaluasi dan periode aktif Progress Zona Integritas.</div>
        </div>
        @if($canManage)
            <button class="app-create-btn" data-toggle="modal" data-target="#createPeriodModal"><i class="fas fa-plus"></i>Tambah Periode</button>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0 zi-period-table">
            <thead>
                <tr>
                    <th>Periode</th>
                    <th>Target Evaluasi</th>
                    <th>Status</th>
                    <th>Deskripsi</th>
                    @if($canManage)<th style="width:90px;">Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $period)
                    <tr>
                        <td data-label="Periode"><strong>{{ $period->name }}</strong><div class="text-muted small">{{ $period->year }}</div></td>
                        <td data-label="Target Evaluasi">{{ optional($period->target_evaluation_date)->translatedFormat('d F Y') ?: '-' }}</td>
                        <td data-label="Status">{!! $period->status_badge !!}</td>
                        <td data-label="Deskripsi">{{ $period->description ?: '-' }}</td>
                        @if($canManage)
                            <td class="app-action-cell" data-label="Aksi"><div class="app-action-group"><button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal" data-target="#editPeriodModal{{ $period->id }}" title="Edit"><i class="fas fa-pen"></i></button></div></td>
                        @endif
                    </tr>
                    @if($canManage)
                    <div class="modal fade" id="editPeriodModal{{ $period->id }}" tabindex="-1">
                        <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Periode</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                            <form method="POST" action="{{ route('progress-zi.periods.update', $period) }}">@csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="form-group"><label>Nama</label><input class="form-control" name="name" value="{{ $period->name }}" required></div>
                                    <div class="form-group"><label>Tahun</label><input type="number" class="form-control" name="year" value="{{ $period->year }}" required></div>
                                    <div class="form-group"><label>Target Evaluasi</label><input type="date" class="form-control" name="target_evaluation_date" value="{{ optional($period->target_evaluation_date)->format('Y-m-d') }}"></div>
                                    <div class="form-group"><label>Deskripsi</label><textarea class="form-control" name="description" rows="3">{{ $period->description }}</textarea></div>
                                    <div class="custom-control custom-switch"><input class="custom-control-input" type="checkbox" id="periodActive{{ $period->id }}" name="is_active" value="1" {{ $period->is_active ? 'checked' : '' }}><label class="custom-control-label" for="periodActive{{ $period->id }}">Jadikan periode aktif</label></div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div></div>
                    </div>
                    @endif
                @empty
                    <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="text-center text-muted py-4">Belum ada periode ZI.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($canManage)
<div class="modal fade" id="createPeriodModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Periode</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form method="POST" action="{{ route('progress-zi.periods.store') }}">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Nama</label><input class="form-control" name="name" placeholder="Periode Evaluasi ZI 2026" required></div>
                <div class="form-group"><label>Tahun</label><input type="number" class="form-control" name="year" value="{{ now()->year }}" required></div>
                <div class="form-group"><label>Target Evaluasi</label><input type="date" class="form-control" name="target_evaluation_date"></div>
                <div class="form-group"><label>Deskripsi</label><textarea class="form-control" name="description" rows="3"></textarea></div>
                <div class="custom-control custom-switch"><input class="custom-control-input" type="checkbox" id="periodActiveCreate" name="is_active" value="1"><label class="custom-control-label" for="periodActiveCreate">Jadikan periode aktif</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>
@endif
@endsection
