@extends('layouts.app')

@section('title', 'Kelola User')

@push('styles')
    <style>
        .admin-users-card {
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .admin-users-card .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e5edf5;
            padding: 14px;
        }

        .admin-users-top-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .admin-users-table {
            color: #14213d;
            table-layout: auto;
            width: 100%;
            font-size: 0.72rem !important;
        }

        .admin-users-table thead th {
            padding: 11px 10px;
            border-top: 0;
            border-bottom: 1px solid #c7d2fe;
            background: linear-gradient(180deg, #f8fafc, #eef2ff);
            color: #312e81;
            font-size: 0.66rem !important;
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
            padding: 9px 8px;
            border-top: 1px solid #edf2f7;
            vertical-align: middle;
            font-size: 0.72rem !important;
        }

        .admin-users-table .col-no {
            width: 46px;
            text-align: center;
        }

        .admin-users-table .col-name {
            width: 24%;
        }

        .admin-users-table .col-wa {
            width: 126px;
        }

        .admin-users-table .col-role {
            width: 16%;
        }

        .admin-users-table .col-jabatan {
            width: 19%;
        }

        .admin-users-table .col-atasan {
            width: 18%;
        }

        .admin-users-table .col-status {
            width: 94px;
        }

        .admin-users-table .col-aksi {
            width: 156px;
        }

        .user-identity {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 210px;
        }

        .user-list-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            font-size: 0.72rem;
            font-weight: 800;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.18);
        }

        .user-list-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name-main {
            color: #1f2a44;
            font-size: 0.78rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .user-name-sub {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 0.66rem;
            font-weight: 500;
            line-height: 1.28;
        }

        .user-name-email {
            display: block;
            margin-top: 2px;
            color: #4f46e5;
            font-size: 0.64rem;
            font-weight: 700;
            line-height: 1.25;
            word-break: break-all;
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
            padding: 3px 7px;
            border-radius: 999px;
            color: #ffffff;
            font-size: 0.62rem !important;
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

        .user-delegation-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
            padding: 3px 7px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.62rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .admin-users-table .app-action-group {
            flex-wrap: nowrap;
            gap: 5px;
        }

        .admin-users-table .app-icon-btn {
            width: 30px;
            height: 30px;
            min-width: 30px;
            border-radius: 8px;
            font-size: 0.76rem;
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

        .wa-global-toggle {
            border: 1px solid {{ $whatsAppEnabled ? '#fecaca' : '#bbf7d0' }};
            color: {{ $whatsAppEnabled ? '#dc2626' : '#047857' }} !important;
            background: {{ $whatsAppEnabled ? '#fff1f2' : '#ecfdf5' }};
            font-weight: 800;
            border-radius: 999px;
        }

        .wa-global-toggle:hover {
            color: #ffffff !important;
            background: {{ $whatsAppEnabled ? '#dc2626' : '#059669' }};
        }

        .user-form-modal {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
        }

        .user-form-header {
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            border-bottom: 1px solid #dbe4ff;
            align-items: center;
        }

        .user-form-subtitle {
            margin-top: 2px;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .user-form-grid {
            display: grid;
            gap: 12px;
        }

        .user-form-section {
            padding: 14px;
            border: 1px solid #e0e7ff;
            border-radius: 12px;
            background: #ffffff;
        }

        .user-form-section-title {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 12px;
            color: #3730a3;
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.02em;
        }

        .user-form-section-title i {
            color: #4f46e5;
        }

        .user-photo-field {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px dashed #c7d2fe;
        }

        .user-photo-preview {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            font-size: 1.15rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .user-photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-photo-control {
            min-width: 0;
            flex: 1;
        }

        .user-form-footer {
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
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

            .admin-users-top-actions {
                width: 100%;
            }

            .admin-users-top-actions form,
            .admin-users-top-actions button {
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

            .user-identity {
                min-width: 0;
            }

            .user-photo-field {
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center admin-users-top">
                <h1>Kelola User</h1>
                <div class="admin-users-top-actions">
                    <form method="POST" action="{{ route('admin.whatsapp-notifications.toggle') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn wa-global-toggle"
                            onclick="return confirm('{{ $whatsAppEnabled ? 'Nonaktifkan seluruh notifikasi WhatsApp?' : 'Aktifkan kembali notifikasi WhatsApp?' }}')">
                            <i class="fab fa-whatsapp mr-1"></i>
                            {{ $whatsAppEnabled ? 'Nonaktifkan WA' : 'Aktifkan WA' }}
                        </button>
                    </form>
                    <button class="btn app-create-btn" data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-plus mr-1"></i> Tambah User
                    </button>
                </div>
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
                    <div class="col-md-4 form-group mb-md-0">
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
                    <div class="col-md-2 d-flex" style="gap: 6px;">
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
                                $unitText = optional($user->unit)->nama ?: optional(optional($user->jabatan)->unit)->nama;
                                $activeDelegation = $user->activeJabatanDelegations->first();
                                $rowNumber = ($users->firstItem() ?: 1) + $loop->index;
                            @endphp
                            <tr>
                                <td class="col-no" data-label="No">{{ $rowNumber }}</td>
                                <td class="col-name" data-label="Nama / NIP">
                                    <div class="user-identity">
                                        <div class="user-list-avatar">
                                            @if($user->profile_photo_path)
                                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}">
                                            @else
                                                {{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="user-name-main">{{ $user->name }}</div>
                                            <span class="user-name-sub">NIP. {{ $user->nip ?? '-' }}</span>
                                            <span class="user-name-email">{{ $user->email }}</span>
                                        </div>
                                    </div>
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
                                <td class="col-jabatan" data-label="Jabatan">
                                    <div style="font-weight: 800; color: #1f2937;">{{ $jabatanText }}</div>
                                    @if($unitText)
                                        <div class="user-name-sub">{{ $unitText }}</div>
                                    @endif
                                    @if($activeDelegation && $activeDelegation->jabatan)
                                        <div class="user-delegation-badge">
                                            {{ $activeDelegation->type_label }} {{ $activeDelegation->jabatan->nama }}
                                        </div>
                                    @endif
                                </td>
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
        <div id="adminUsersEditModals">
            @foreach($users as $user)
                @include('admin.users._edit-modal', [
                    'user' => $user,
                    'roles' => $roles,
                    'jabatans' => $jabatans,
                    'units' => $units,
                    'supervisorOptions' => $supervisorOptions,
                ])
            @endforeach
        </div>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content user-form-modal">
                <div class="modal-header user-form-header">
                    <div>
                        <h5 class="modal-title">Tambah User</h5>
                        <div class="user-form-subtitle">Lengkapi identitas, role, jabatan, dan relasi approval.</div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        @include('admin.users._form-fields', [
                            'mode' => 'create',
                            'user' => new \App\User(),
                        ])
                    </div>
                    <div class="modal-footer user-form-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
