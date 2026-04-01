@extends('layouts.app')

@section('title', 'Master Area ZI')

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap:12px;">
        <div>
            <h4 class="mb-1">Master Area Perubahan</h4>
            <div class="text-muted">Kelola area perubahan, PIC area, dan deskripsi monitoring.</div>
        </div>
        @if($canManage)
            <button class="app-create-btn" data-toggle="modal" data-target="#createAreaModal"><i class="fas fa-plus"></i>Tambah Area</button>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Kode</th><th>Nama Area</th><th>PIC Area</th><th>Status</th><th>Deskripsi</th>@if($canManage)<th style="width:90px;">Aksi</th>@endif</tr></thead>
            <tbody>
                @forelse($areas as $area)
                    <tr>
                        <td><strong>{{ $area->code }}</strong></td>
                        <td>{{ $area->name }}</td>
                        <td>{{ $area->pic_names }}</td>
                        <td>{!! $area->status_badge !!}</td>
                        <td>{{ $area->description ?: '-' }}</td>
                        @if($canManage)
                            <td class="app-action-cell"><div class="app-action-group"><button class="app-icon-btn edit" data-toggle="modal" data-target="#editAreaModal{{ $area->id }}" title="Edit"><i class="fas fa-pen"></i></button></div></td>
                        @endif
                    </tr>
                    @if($canManage)
                    <div class="modal fade" id="editAreaModal{{ $area->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Area</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
                        <form method="POST" action="{{ route('progress-zi.areas.update', $area) }}">@csrf @method('PUT')<div class="modal-body">
                            <div class="form-group"><label>Kode</label><input class="form-control" name="code" value="{{ $area->code }}" required></div>
                            <div class="form-group"><label>Nama Area</label><input class="form-control" name="name" value="{{ $area->name }}" required></div>
                            <div class="form-group"><label>PIC Area</label><select class="form-control select2" name="pic_user_ids[]" multiple data-dropdown-parent="#editAreaModal{{ $area->id }}">@foreach($users as $user)<option value="{{ $user->id }}" {{ $area->pics->contains('id', $user->id) || ((int) $area->pic_user_id === (int) $user->id && $area->pics->isEmpty()) ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select><small class="text-muted">Bisa memilih lebih dari satu PIC area.</small></div>
                            <div class="form-group"><label>Deskripsi</label><textarea class="form-control" name="description" rows="3">{{ $area->description }}</textarea></div>
                            <div class="custom-control custom-switch"><input class="custom-control-input" type="checkbox" id="areaActive{{ $area->id }}" name="is_active" value="1" {{ $area->is_active ? 'checked' : '' }}><label class="custom-control-label" for="areaActive{{ $area->id }}">Aktif</label></div>
                        </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form>
                    </div></div></div>
                    @endif
                @empty
                    <tr><td colspan="{{ $canManage ? 6 : 5 }}" class="text-center text-muted py-4">Belum ada area perubahan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($canManage)
<div class="modal fade" id="createAreaModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Area</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form method="POST" action="{{ route('progress-zi.areas.store') }}">@csrf<div class="modal-body">
        <div class="form-group"><label>Kode</label><input class="form-control" name="code" placeholder="AP-I" required></div>
        <div class="form-group"><label>Nama Area</label><input class="form-control" name="name" required></div>
        <div class="form-group"><label>PIC Area</label><select class="form-control select2" name="pic_user_ids[]" multiple data-dropdown-parent="#createAreaModal">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><small class="text-muted">Bisa memilih lebih dari satu PIC area.</small></div>
        <div class="form-group"><label>Deskripsi</label><textarea class="form-control" name="description" rows="3"></textarea></div>
        <div class="custom-control custom-switch"><input class="custom-control-input" type="checkbox" id="areaActiveCreate" name="is_active" value="1" checked><label class="custom-control-label" for="areaActiveCreate">Aktif</label></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form>
</div></div></div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        $('.modal').on('shown.bs.modal', function () {
            $(this).find('select.select2').each(function () {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $select.data('dropdown-parent') ? $($select.data('dropdown-parent')) : $select.closest('.modal')
                });
            });
        });
    })();
</script>
@endpush
