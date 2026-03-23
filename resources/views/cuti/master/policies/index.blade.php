@extends('layouts.app')

@section('title', 'Kebijakan Cuti')

@section('content')
@include('admin._alerts')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h3 class="mb-1">Kebijakan Cuti</h3><p class="text-muted mb-0">Parameter configurable untuk tiap jenis cuti.</p></div>
    <button class="btn app-create-btn" data-toggle="modal" data-target="#createLeavePolicyModal"><i class="fas fa-plus"></i> Tambah Kebijakan</button>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header">
        <form method="GET" action="{{ route('cuti.master.policies.index') }}">
            <div class="row">
                <div class="col-md-5 form-group mb-md-0"><input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Cari key atau jenis cuti"></div>
                <div class="col-md-5 form-group mb-md-0"><select name="leave_type_id" class="form-control"><option value="">Semua Jenis</option>@foreach($leaveTypes as $leaveType)<option value="{{ $leaveType->id }}" {{ (string) ($filters['leave_type_id'] ?? '') === (string) $leaveType->id ? 'selected' : '' }}>{{ $leaveType->name }}</option>@endforeach</select></div>
                <div class="col-md-2 d-flex" style="gap:6px;"><button type="submit" class="btn btn-primary btn-block">Filter</button><a href="{{ route('cuti.master.policies.index') }}" class="btn btn-outline-secondary">Reset</a></div>
            </div>
        </form>
    </div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Jenis Cuti</th><th>Key</th><th>Nilai</th><th>Periode</th><th>Status</th><th class="text-right">Aksi</th></tr></thead><tbody>
    @forelse($policies as $policy)
    <tr>
        <td>{{ optional($policy->leaveType)->name }}</td>
        <td><code>{{ $policy->key }}</code></td>
        <td>{{ $policy->value_text ?: '-' }}</td>
        <td>{{ optional($policy->effective_start)->translatedFormat('d M Y') ?: '-' }} - {{ optional($policy->effective_end)->translatedFormat('d M Y') ?: '-' }}</td>
        <td><span class="badge badge-{{ $policy->is_active ? 'success' : 'secondary' }}">{{ $policy->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
        <td class="app-action-cell"><div class="app-action-group"><button class="app-icon-btn edit" data-toggle="modal" data-target="#editLeavePolicyModal{{ $policy->id }}"><i class="fas fa-pen"></i></button><form method="POST" action="{{ route('cuti.master.policies.destroy', $policy) }}" class="d-inline">@csrf @method('DELETE')<button type="submit" class="app-icon-btn delete" onclick="return confirm('Hapus kebijakan ini?')"><i class="fas fa-trash"></i></button></form></div></td>
    </tr>
    <div class="modal fade" id="editLeavePolicyModal{{ $policy->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Kebijakan</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><form method="POST" action="{{ route('cuti.master.policies.update', $policy) }}">@csrf @method('PUT')<div class="modal-body">@include('cuti.master.policies.partials.form', ['item' => $policy, 'leaveTypes' => $leaveTypes])</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div>
    @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada kebijakan cuti.</td></tr>@endforelse
    </tbody></table></div></div>
    <div class="card-footer clearfix">{{ $policies->links() }}</div>
</div>
<div class="modal fade" id="createLeavePolicyModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Tambah Kebijakan</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><form method="POST" action="{{ route('cuti.master.policies.store') }}">@csrf <div class="modal-body">@include('cuti.master.policies.partials.form', ['item' => null, 'leaveTypes' => $leaveTypes])</div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div>
@endsection
