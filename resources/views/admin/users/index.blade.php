@extends('layouts.app')

@section('title', 'Kelola User')

@push('styles')
    <style>
        .admin-users-card {
            border: 0;
            border-radius: 0;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .admin-users-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e5edf5;
        }

        .admin-users-table {
            color: #14213d;
            table-layout: auto;
            width: 100%;
        }

        .admin-users-table thead th {
            padding: 16px 15px;
            border-top: 0;
            border-bottom: 2px solid #064e3b;
            background: #f1f6fb;
            color: #064236;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .admin-users-table tbody tr:nth-child(odd) {
            background: #fbfcfe;
        }

        .admin-users-table tbody tr:nth-child(even) {
            background: #ffffff;
        }

        .admin-users-table td {
            padding: 12px 10px;
            border-top: 1px solid #edf2f7;
            vertical-align: middle;
            font-size: 0.82rem;
        }

        .admin-users-table .col-no {
            width: 46px;
            text-align: center;
        }

        .admin-users-table .col-name {
            width: 21%;
        }

        .admin-users-table .col-wa {
            width: 126px;
        }

        .admin-users-table .col-role {
            width: 18%;
        }

        .admin-users-table .col-jabatan {
            width: 20%;
        }

        .admin-users-table .col-atasan {
            width: 18%;
        }

        .admin-users-table .col-status {
            width: 94px;
        }

        .admin-users-table .col-aksi {
            width: 166px;
        }

        .user-name-main {
            color: #1f2a44;
            font-size: 0.88rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .user-name-sub {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 500;
        }

        .user-role-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-width: 100%;
        }

        .user-role-badge,
        .user-status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 22px;
            padding: 4px 8px;
            border-radius: 5px;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .user-role-badge.pegawai,
        .user-status-badge.active {
            background: #059669;
        }

        .user-role-badge.atasan {
            background: #2563eb;
        }

        .user-role-badge.admin {
            background: #6d28d9;
        }

        .user-role-badge.operator,
        .user-role-badge.notulis,
        .user-role-badge.protokoler {
            background: #0891b2;
        }

        .user-role-badge.approval,
        .user-role-badge.ppk {
            background: #0f766e;
        }

        .user-status-badge.inactive {
            background: #64748b;
        }

        .admin-users-table .app-action-group {
            flex-wrap: nowrap;
            gap: 5px;
        }

        .admin-users-table .app-icon-btn {
            width: 32px;
            height: 32px;
            min-width: 32px;
            border-radius: 8px;
            font-size: 0.82rem;
        }

        .admin-users-ajax-loading {
            opacity: .62;
            pointer-events: none;
            transition: opacity .18s ease;
        }

        .admin-users-table .app-icon-btn.send {
            background: #059669;
        }

        .admin-users-table .app-icon-btn.edit {
            background: #eab308;
            color: #061126;
        }

        .admin-users-table .app-icon-btn.toggle {
            background: #6b7280;
        }

        .admin-users-table .app-icon-btn.delete {
            background: #dc2626;
        }

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
                border: 1px solid #e8eaed;
                border-radius: 8px;
                margin: 10px;
                background: #ffffff;
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
                flex-wrap: nowrap;
                overflow-x: auto;
                max-width: 100%;
                padding-bottom: 2px;
            }

            .admin-users-table .col-no,
            .admin-users-table .col-name,
            .admin-users-table .col-wa,
            .admin-users-table .col-role,
            .admin-users-table .col-jabatan,
            .admin-users-table .col-atasan,
            .admin-users-table .col-status,
            .admin-users-table .col-aksi {
                width: 100%;
            }

            .admin-users-table .app-icon-btn[data-mobile-label] {
                width: 42px;
                min-width: 42px;
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

@push('scripts')
    <script>
        (function () {
            const filterForm = document.getElementById('adminUsersFilter');
            const resultsSelector = '#adminUsersResults';
            const results = () => document.querySelector(resultsSelector);
            let debounceTimer = null;
            let activeController = null;

            if (!filterForm || !results()) {
                return;
            }

            function buildUrl(resetPage) {
                const url = new URL(filterForm.action, window.location.origin);
                const formData = new FormData(filterForm);

                formData.forEach(function (value, key) {
                    if (String(value).trim() !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                if (!resetPage) {
                    const currentPage = new URL(window.location.href).searchParams.get('page');
                    if (currentPage) {
                        url.searchParams.set('page', currentPage);
                    }
                }

                return url;
            }

            function setLoading(isLoading) {
                const target = results();
                if (target) {
                    target.classList.toggle('admin-users-ajax-loading', isLoading);
                }
            }

            function syncFormFromUrl(url) {
                const params = new URL(url, window.location.origin).searchParams;
                filterForm.querySelectorAll('input[name], select[name]').forEach(function (field) {
                    field.value = params.get(field.name) || '';
                    if (window.jQuery && jQuery.fn.select2 && jQuery(field).hasClass('select2-hidden-accessible')) {
                        jQuery(field).trigger('change.select2');
                    }
                });
            }

            function afterResultsUpdated() {
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(resultsSelector + ' .select2').select2({ theme: 'bootstrap4', width: '100%' });
                }

                if (typeof registerActionTooltips === 'function') {
                    registerActionTooltips(resultsSelector + ' .app-icon-btn');
                }

                if (typeof syncMobileActionLabels === 'function') {
                    syncMobileActionLabels();
                }
            }

            function loadUsers(url, pushState) {
                if (activeController) {
                    activeController.abort();
                }

                activeController = new AbortController();
                setLoading(true);

                fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    signal: activeController.signal
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Gagal memuat data user.');
                        }
                        return response.text();
                    })
                    .then(function (html) {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const nextResults = doc.querySelector(resultsSelector);
                        const currentResults = results();

                        if (!nextResults || !currentResults) {
                            window.location.href = url.toString();
                            return;
                        }

                        currentResults.innerHTML = nextResults.innerHTML;
                        if (pushState) {
                            window.history.pushState({}, '', url.toString());
                        }
                        afterResultsUpdated();
                    })
                    .catch(function (error) {
                        if (error.name === 'AbortError') {
                            return;
                        }
                        window.location.href = url.toString();
                    })
                    .finally(function () {
                        setLoading(false);
                        activeController = null;
                    });
            }

            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                loadUsers(buildUrl(true), true);
            });

            filterForm.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    loadUsers(buildUrl(true), true);
                });
            });

            const searchInput = filterForm.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = window.setTimeout(function () {
                        loadUsers(buildUrl(true), true);
                    }, 360);
                });
            }

            filterForm.addEventListener('click', function (event) {
                const resetLink = event.target.closest('a.admin-users-reset[href]');
                if (!resetLink) {
                    return;
                }
                event.preventDefault();
                filterForm.querySelectorAll('input[name], select[name]').forEach(function (field) {
                    field.value = '';
                    if (window.jQuery && jQuery.fn.select2 && jQuery(field).hasClass('select2-hidden-accessible')) {
                        jQuery(field).trigger('change.select2');
                    }
                });
                loadUsers(new URL(filterForm.action, window.location.origin), true);
            });

            document.addEventListener('click', function (event) {
                const paginationLink = event.target.closest(resultsSelector + ' .pagination a[href]');
                if (!paginationLink) {
                    return;
                }
                event.preventDefault();
                loadUsers(new URL(paginationLink.href, window.location.origin), true);
            });

            window.addEventListener('popstate', function () {
                const url = new URL(window.location.href);
                syncFormFromUrl(url);
                loadUsers(url, false);
            });
        })();
    </script>
@endpush

@section('content')
    @include('admin._alerts')

    <div class="card admin-users-card">
        <div class="card-header">
            <form method="GET" action="{{ route('admin.users.index') }}" class="admin-users-filter" id="adminUsersFilter">
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
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary admin-users-reset">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div id="adminUsersResults">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 admin-users-table">
                    <thead>
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-name">Nama / NIP</th>
                            <th class="col-wa">WhatsApp</th>
                            <th class="col-role">Role</th>
                            <th class="col-jabatan">Jabatan</th>
                            <th class="col-atasan">Atasan</th>
                            <th class="col-status">Status</th>
                            <th class="col-aksi text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $selectedRoles = $user->roles->pluck('id')->map(fn ($id) => (string) $id)->all();
                                $roleBadges = $user->roles->sortBy('display_name')->map(function ($role) {
                                    $name = $role->name;
                                    $class = 'pegawai';
                                    if (in_array($name, ['super_admin', 'admin', 'admin_surat', 'admin_kepegawaian'], true)) {
                                        $class = 'admin';
                                    } elseif (in_array($name, ['ketua', 'wakil_ketua', 'sekretaris', 'panitera', 'kabag', 'kasubag', 'panmud', 'atasan_langsung'], true)) {
                                        $class = 'atasan';
                                    } elseif (in_array($name, ['operator', 'operator_surat_masuk', 'operator_persediaan', 'notulis', 'protokoler'], true)) {
                                        $class = 'operator';
                                    } elseif (in_array($name, ['approval', 'ppk', 'verifikator_dokumen'], true)) {
                                        $class = 'approval';
                                    }

                                    return [
                                        'label' => $role->display_name,
                                        'class' => $class,
                                    ];
                                });
                                $jabatanText = $user->jabatan_keterangan ?: optional($user->jabatan)->nama ?: '-';
                                $rowNumber = ($users->firstItem() ?: 1) + $loop->index;
                            @endphp
                            <tr>
                                <td class="col-no" data-label="No">{{ $rowNumber }}</td>
                                <td class="col-name" data-label="Nama / NIP">
                                    <div class="user-name-main">{{ $user->name }}</div>
                                    <span class="user-name-sub">{{ $user->nip ?? '-' }}</span>
                                </td>
                                <td class="col-wa" data-label="WhatsApp">{{ $user->no_hp ?? '-' }}</td>
                                <td class="col-role" data-label="Role">
                                    <div class="user-role-list">
                                        @forelse($roleBadges as $roleBadge)
                                            <span class="user-role-badge {{ $roleBadge['class'] }}">{{ $roleBadge['label'] }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="col-jabatan" data-label="Jabatan">{{ $jabatanText }}</td>
                                <td class="col-atasan" data-label="Atasan">{{ optional($user->atasanLangsung)->name ?: '-' }}</td>
                                <td class="col-status" data-label="Status">
                                    <span class="user-status-badge {{ $user->status_aktif_pegawai ? 'active' : 'inactive' }}">
                                        {{ $user->status_aktif_pegawai ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="app-action-cell col-aksi" data-label="Aksi">
                                    <div class="app-action-group">
                                    <form action="{{ route('admin.users.send-login-info', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="app-icon-btn send" data-mobile-label="Kirim"
                                            onclick="return confirm('Kirim informasi login ke WhatsApp user ini?')">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    </form>
                                    <button class="app-icon-btn edit" data-mobile-label="Edit" data-toggle="modal"
                                        data-target="#editUserModal{{ $user->id }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="app-icon-btn toggle" data-mobile-label="{{ $user->status_aktif_pegawai ? 'Nonaktif' : 'Aktif' }}"
                                            onclick="return confirm('{{ $user->status_aktif_pegawai ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')">
                                            <i class="fas {{ $user->status_aktif_pegawai ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
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
                                                    <div class="col-md-6 form-group">
                                                        <label>Atasan Langsung</label>
                                                        <select name="atasan_langsung_id" class="form-control select2">
                                                            <option value="">-- Pilih Atasan Langsung --</option>
                                                            @foreach($supervisorOptions as $option)
                                                                @if((int) $option->id !== (int) $user->id)
                                                                    <option value="{{ $option->id }}" {{ (string) $user->atasan_langsung_id === (string) $option->id ? 'selected' : '' }}>
                                                                        {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 form-group">
                                                        <label>Pejabat Berwenang Cuti</label>
                                                        <select name="pejabat_berwenang_id" class="form-control select2">
                                                            <option value="">-- Pilih Pejabat Berwenang --</option>
                                                            @foreach($supervisorOptions as $option)
                                                                @if((int) $option->id !== (int) $user->id)
                                                                    <option value="{{ $option->id }}" {{ (string) $user->pejabat_berwenang_id === (string) $option->id ? 'selected' : '' }}>
                                                                        {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
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
                            <div class="col-md-6 form-group">
                                <label>Atasan Langsung</label>
                                <select name="atasan_langsung_id" class="form-control select2">
                                    <option value="">-- Pilih Atasan Langsung --</option>
                                    @foreach($supervisorOptions as $option)
                                        <option value="{{ $option->id }}">
                                            {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Pejabat Berwenang Cuti</label>
                                <select name="pejabat_berwenang_id" class="form-control select2">
                                    <option value="">-- Pilih Pejabat Berwenang --</option>
                                    @foreach($supervisorOptions as $option)
                                        <option value="{{ $option->id }}">
                                            {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                                        </option>
                                    @endforeach
                                </select>
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
