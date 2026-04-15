@extends('layouts.app')

@section('title', 'Kelola User')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .admin-users-top {
                flex-direction: column;
                align-items: stretch !important;
                gap: 10px;
            }

            .admin-users-top .btn {
                width: 100%;
            }

            .admin-users-filter .row {
                display: block;
            }

            .admin-users-filter .form-group,
            .admin-users-filter .d-flex {
                margin-bottom: 10px;
            }

            .admin-users-table,
            .admin-users-table thead,
            .admin-users-table tbody,
            .admin-users-table tr,
            .admin-users-table th,
            .admin-users-table td {
                display: block;
                width: 100%;
            }

            .admin-users-table thead {
                display: none;
            }

            .admin-users-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e5e7eb;
            }

            .admin-users-table td {
                padding: 0 0 10px;
                border: 0;
            }

            .admin-users-table td:last-child {
                padding-bottom: 0;
            }

            .admin-users-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.74rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .admin-users-table .app-action-group {
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center admin-users-top">
                <h1>Kelola User</h1>
                <button class="btn app-create-btn" data-toggle="modal" data-target="#createUserModal">
                    <i class="fas fa-plus mr-1"></i> Tambah User
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin._alerts')

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.users.index') }}" class="admin-users-filter">
                <div class="row">
                    <div class="col-md-3 form-group mb-md-0">
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari nama, email, NIP, HP">
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="role_id" class="form-control">
                            <option value="">Semua Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ (string) ($filters['role_id'] ?? '') === (string) $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="jabatan_id" class="form-control">
                            <option value="">Semua Jabatan</option>
                            @foreach($jabatans as $jabatan)
                                <option value="{{ $jabatan->id }}" {{ (string) ($filters['jabatan_id'] ?? '') === (string) $jabatan->id ? 'selected' : '' }}>
                                    {{ $jabatan->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="unit_id" class="form-control">
                            <option value="">Semua Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ (string) ($filters['unit_id'] ?? '') === (string) $unit->id ? 'selected' : '' }}>
                                    {{ $unit->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group mb-md-0">
                        <select name="bidang_id" class="form-control">
                            <option value="">Semua Bidang</option>
                            @foreach($bidangs as $bidang)
                                <option value="{{ $bidang->id }}" {{ (string) ($filters['bidang_id'] ?? '') === (string) $bidang->id ? 'selected' : '' }}>
                                    {{ $bidang->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1 d-flex" style="gap: 6px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-users-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Unit / Bidang</th>
                            <th>Jabatan</th>
                            <th>Hirarki</th>
                            <th>No. HP / WA</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php $selectedRoles = $user->roles->pluck('id')->map(fn ($id) => (string) $id)->all(); @endphp
                            <tr>
                                <td data-label="Nama">
                                    <div>{{ $user->name }}</div>
                                    <small class="text-muted">{{ $user->nip ?? '-' }}</small>
                                </td>
                                <td data-label="Email">{{ $user->email }}</td>
                                <td data-label="Role">{{ $user->roles->pluck('display_name')->implode(', ') ?: '-' }}</td>
                                <td data-label="Unit / Bidang">
                                    <div>{{ optional($user->unit)->nama ?? '-' }}</div>
                                    <small class="text-muted">{{ optional($user->bidang)->nama ?? '-' }}</small>
                                </td>
                                <td data-label="Jabatan">
                                    <div>{{ optional($user->jabatan)->nama ?? '-' }}</div>
                                    <small class="text-muted">{{ $user->jabatan_keterangan ?: '-' }}</small>
                                </td>
                                <td data-label="Hirarki">{{ $user->hirarki ?? '-' }}</td>
                                <td data-label="No. HP / WA">{{ $user->no_hp ?? '-' }}</td>
                                <td class="app-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                    <form action="{{ route('admin.users.send-login-info', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="app-icon-btn send" data-mobile-label="Kirim"
                                            onclick="return confirm('Kirim informasi login ke WhatsApp user ini?')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                    <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal"
                                        data-target="#editUserModal{{ $user->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus"
                                            onclick="return confirm('Hapus user ini?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 form-group">
                                                        <label>Nama</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Email</label>
                                                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Password Baru (opsional)</label>
                                                        <input type="password" name="password" class="form-control">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Role</label>
                                                        <select name="role_ids[]" class="form-control select2" multiple required>
                                                            @foreach($roles as $role)
                                                                <option value="{{ $role->id }}" {{ in_array((string) $role->id, $selectedRoles, true) ? 'selected' : '' }}>
                                                                    {{ $role->display_name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Unit Kerja</label>
                                                        <select name="unit_id" class="form-control">
                                                            <option value="">-- Pilih Unit --</option>
                                                            @foreach($units as $unit)
                                                                <option value="{{ $unit->id }}" {{ (string) $user->unit_id === (string) $unit->id ? 'selected' : '' }}>
                                                                    {{ $unit->nama }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Bidang</label>
                                                        <select name="bidang_id" class="form-control">
                                                            <option value="">-- Pilih Bidang --</option>
                                                            @foreach($bidangs as $bidang)
                                                                <option value="{{ $bidang->id }}" {{ (string) $user->bidang_id === (string) $bidang->id ? 'selected' : '' }}>
                                                                    {{ $bidang->nama }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 form-group">
                                                        <label>Hirarki</label>
                                                        <input type="number" name="hirarki" class="form-control" value="{{ $user->hirarki ?? 999 }}" min="1">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Jabatan</label>
                                                        <select name="jabatan_id" class="form-control">
                                                            <option value="">-- Pilih Jabatan --</option>
                                                            @foreach($jabatans as $jabatan)
                                                                <option value="{{ $jabatan->id }}" {{ (string) $user->jabatan_id === (string) $jabatan->id ? 'selected' : '' }}>
                                                                    {{ $jabatan->nama }}{{ $jabatan->unit ? ' - ' . $jabatan->unit->nama : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Jabatan Keterangan</label>
                                                        <input type="text" name="jabatan_keterangan" class="form-control" value="{{ $user->jabatan_keterangan }}">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>NIP</label>
                                                        <input type="text" name="nip" class="form-control" value="{{ $user->nip }}">
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>No. HP / WA</label>
                                                        <input type="text" name="no_hp" class="form-control" value="{{ $user->no_hp }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada data user.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $users->links() }}
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Nama</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Role</label>
                                <select name="role_ids[]" class="form-control select2" multiple required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Unit Kerja</label>
                                <select name="unit_id" class="form-control">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Bidang</label>
                                <select name="bidang_id" class="form-control">
                                    <option value="">-- Pilih Bidang --</option>
                                    @foreach($bidangs as $bidang)
                                        <option value="{{ $bidang->id }}">{{ $bidang->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Hirarki</label>
                                <input type="number" name="hirarki" class="form-control" value="999" min="1">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jabatan</label>
                                <select name="jabatan_id" class="form-control">
                                    <option value="">-- Pilih Jabatan --</option>
                                    @foreach($jabatans as $jabatan)
                                        <option value="{{ $jabatan->id }}">{{ $jabatan->nama }}{{ $jabatan->unit ? ' - ' . $jabatan->unit->nama : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Jabatan Keterangan</label>
                                <input type="text" name="jabatan_keterangan" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>NIP</label>
                                <input type="text" name="nip" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>No. HP / WA</label>
                                <input type="text" name="no_hp" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
