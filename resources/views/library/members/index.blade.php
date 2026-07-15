@extends('layouts.app')
@section('title', 'Data Anggota')
@section('page-title', 'Data Anggota')
@section('page-subtitle', 'Manajemen anggota perpustakaan')

@section('content')
<div class="page-header">
    <div>
        <h1>Data Anggota</h1>
        <p>Total {{ $members->total() }} anggota terdaftar</p>
    </div>
    @if($canManageLibrary)<a href="{{ route('library.members.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus-fill me-1"></i> Tambah Anggota
    </a>@endif
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari nama, nomor anggota, HP..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status')=='nonaktif'?'selected':'' }}>Non-Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="gender" class="form-select">
                    <option value="">Semua</option>
                    <option value="L" {{ request('gender')=='L'?'selected':'' }}>Laki-laki</option>
                    <option value="P" {{ request('gender')=='P'?'selected':'' }}>Perempuan</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <a href="{{ route('library.members.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>No. Anggota</th>
                        <th>Kelas/Jabatan</th>
                        <th>Kontak</th>
                        <th>Pinjam Aktif</th>
                        <th>Denda Belum Bayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text-sm
                                    {{ $member->gender == 'L' ? 'bg-primary bg-opacity-10 text-primary' : 'bg-pink bg-opacity-10' }}"
                                    style="{{ $member->gender == 'P' ? 'background:#fce7f3;color:#be185d;' : '' }}">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13.5px;">{{ $member->name }}</div>
                                    <small class="text-muted">{{ $member->gender == 'L' ? '♂ Laki-laki' : '♀ Perempuan' }}</small>
                                </div>
                            </div>
                        </td>
                        <td><code style="font-size:12px;">{{ $member->member_number }}</code></td>
                        <td style="font-size:13px;">{{ $member->class_position ?? '—' }}</td>
                        <td style="font-size:13px;">
                            @if($member->phone)<div><i class="bi bi-phone me-1 text-muted"></i>{{ $member->phone }}</div>@endif
                            @if($member->email)<div><i class="bi bi-envelope me-1 text-muted"></i>{{ $member->email }}</div>@endif
                        </td>
                        <td>
                            @if($member->active_loans_count > 0)
                            <span class="badge bg-warning text-dark">{{ $member->active_loans_count }} buku</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($member->unpaid_fines_count > 0)
                            <span class="badge bg-danger">{{ $member->unpaid_fines_count }} denda</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $member->status == 'aktif' ? 'success' : 'secondary' }} badge-status">
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('library.members.show', $member) }}" class="btn btn-sm btn-icon btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($canManageLibrary)<a href="{{ route('library.members.edit', $member) }}" class="btn btn-sm btn-icon btn-outline-warning">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('library.members.destroy', $member) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus anggota ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>@endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="empty-state"><i class="bi bi-people d-block"></i>Belum ada anggota terdaftar</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($members->hasPages())
    <div class="card-footer bg-transparent">{{ $members->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
