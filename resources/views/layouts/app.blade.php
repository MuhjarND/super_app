<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Persuratan') | PTA Papua Barat</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">

    <style>
        /* ======================== MODERN DESIGN SYSTEM ======================== */
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a5f;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-width: 260px;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e5e7eb;
            --body-bg: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --card-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --card-hover-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* ======================== SIDEBAR ======================== */
        .main-sidebar {
            background: var(--sidebar-bg) !important;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: none !important;
            width: var(--sidebar-width) !important;
        }

        .main-sidebar .brand-link {
            background: transparent;
            border-bottom: 1px solid var(--sidebar-border);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .main-sidebar .brand-link .brand-text {
            color: var(--text-primary) !important;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        /* User panel in sidebar */
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .sidebar-user-inner {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e40af;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .sidebar-user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .sidebar-user-role {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Sidebar nav */
        .sidebar {
            padding: 0 !important;
            overflow-y: auto;
        }

        .sidebar .nav-header {
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 1.2px;
            padding: 20px 20px 6px;
            font-weight: 700;
        }

        .sidebar .nav-link {
            color: var(--text-secondary) !important;
            border-radius: 10px;
            margin: 1px 12px;
            padding: 10px 12px !important;
            transition: all 0.15s ease;
            font-size: 0.84rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background: #f3f4f6 !important;
            color: var(--text-primary) !important;
        }

        .sidebar .nav-link.active {
            background: #eff6ff !important;
            color: #1e40af !important;
            font-weight: 600;
            box-shadow: none;
        }

        .sidebar .nav-link .nav-icon {
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 0.9rem;
            opacity: 0.65;
        }

        .sidebar .nav-link.active .nav-icon {
            color: #3b82f6;
            opacity: 1;
        }

        .sidebar .nav-treeview {
            padding-left: 0;
        }

        .sidebar .nav-treeview .nav-link {
            padding-left: 44px !important;
            font-size: 0.82rem;
            margin: 0 12px;
        }

        .sidebar .nav-treeview .nav-link .nav-icon {
            font-size: 0.78rem;
        }

        /* Badge styles */
        .badge-dev {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            font-size: 0.6rem;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* ======================== TOPBAR ======================== */
        .main-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: none;
            min-height: 56px;
        }

        .main-header .nav-link {
            color: var(--text-secondary) !important;
            font-size: 0.875rem;
        }

        .main-header .navbar-nav .nav-item .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 10px;
            transition: all 0.15s;
        }

        .main-header .navbar-nav .nav-item .dropdown-toggle:hover {
            background: #f3f4f6;
        }

        .topbar-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.75rem;
        }

        .dropdown-menu {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
            padding: 8px;
            margin-top: 8px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 0.85rem;
            transition: all 0.1s;
        }

        .dropdown-item:hover {
            background: #f3f4f6;
        }

        /* ======================== CONTENT ======================== */
        .content-wrapper {
            background: var(--body-bg);
        }

        .content-header h1 {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.4rem;
            letter-spacing: -0.02em;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .breadcrumb-item a {
            color: var(--primary-light);
            font-weight: 500;
        }

        .breadcrumb-item.active {
            color: var(--text-muted);
        }

        /* ======================== CARDS ======================== */
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: none;
            transition: all 0.2s ease;
            background: white;
        }

        .card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #f3f4f6;
            padding: 20px 24px;
        }

        .card-header .card-title {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .card-body {
            padding: 24px;
        }

        /* ======================== TABLES ======================== */
        .table thead th {
            background: #f9fafb;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            border-top: none;
            padding: 12px 16px;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.875rem;
            color: var(--text-primary);
            padding: 14px 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .table tbody tr {
            transition: background 0.1s ease;
        }

        .table tbody tr:hover {
            background: #f9fafb;
        }

        /* ======================== BADGES ======================== */
        .badge {
            padding: 4px 10px;
            font-weight: 600;
            font-size: 0.72rem;
            border-radius: 6px;
        }

        /* ======================== BUTTONS ======================== */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 8px 18px;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }

        .btn-success {
            background: linear-gradient(135deg, #059669, #10b981);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #047857, #059669);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
        }

        .btn-outline-secondary {
            border: 1px solid #d1d5db;
            color: var(--text-secondary);
            background: white;
        }

        .btn-outline-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: var(--text-primary);
        }

        /* ======================== MODALS ======================== */
        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 20px 24px;
            border: none;
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 1rem;
        }

        .modal-header .close {
            color: white;
            opacity: 0.7;
            text-shadow: none;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #f3f4f6;
        }

        /* ======================== FORMS ======================== */
        .form-control {
            border-radius: 10px;
            border: 1px solid #d1d5db;
            padding: 10px 14px;
            font-size: 0.875rem;
            color: var(--text-primary);
            transition: all 0.15s ease;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.82rem;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-control-file {
            font-size: 0.85rem;
        }

        /* ======================== FOOTER ======================== */
        .main-footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ======================== SCROLLBAR ======================== */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        /* ======================== TOAST ======================== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .custom-toast {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            padding: 14px 20px;
            margin-bottom: 10px;
            border-left: 4px solid var(--success);
            animation: toastSlide 0.3s ease;
            font-size: 0.875rem;
        }

        @keyframes toastSlide {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ======================== PAGE HEADER ======================== */
        .page-header-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-card h3 {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-primary);
            margin: 0;
        }

        /* ======================== EMPTY STATE ======================== */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* ======================== UTILITIES ======================== */
        .text-primary-custom {
            color: var(--primary) !important;
        }

        .gap-2 {
            gap: 8px;
        }

        .gap-3 {
            gap: 12px;
        }

        /* Pagination modern */
        .page-link {
            border-radius: 8px !important;
            border: 1px solid #e5e7eb;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.82rem;
            margin: 0 2px;
        }

        .page-item.active .page-link {
            background: var(--primary);
            border-color: var(--primary);
        }

        /* DataTables modern override */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            padding: 16px 0;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 4px 8px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 6px 14px;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.8rem;
            color: var(--text-muted);
            padding: 16px 0;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            margin: 0 2px;
        }
    </style>

    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#"
                        style="font-size: 1.1rem; color: #374151 !important;">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                        <div class="topbar-avatar">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <span style="font-weight: 600; color: #374151;">{{ Auth::user()->name ?? 'User' }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="px-3 py-2">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #111827;">
                                {{ Auth::user()->name ?? '-' }}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">{{ Auth::user()->jabatan->nama ?? '-' }}
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt mr-2 text-danger"></i> Keluar
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar">
            <a href="{{ route('dashboard') }}" class="brand-link" style="text-decoration: none;">
                <div class="logo-mark">P</div>
                <span class="brand-text">PTA Papua Barat</span>
            </a>

            <div class="sidebar">
                <!-- User Panel -->
                <div class="sidebar-user">
                    <div class="sidebar-user-inner">
                        <div class="sidebar-user-avatar">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                        </div>
                        <div>
                            <div class="sidebar-user-name">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="sidebar-user-role">{{ Auth::user()->roles->first()->display_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Menu -->
                <nav class="mt-1">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview"
                        role="menu">

                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-th-large"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-header">PERSURATAN</li>

                        <li
                            class="nav-item has-treeview {{ request()->routeIs('surat-masuk.*') || request()->routeIs('surat-keluar.*') || request()->routeIs('arsip.*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->routeIs('surat-masuk.*') || request()->routeIs('surat-keluar.*') || request()->routeIs('arsip.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-mail-bulk"></i>
                                <p>
                                    Persuratan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('surat-masuk.index') }}"
                                        class="nav-link {{ request()->routeIs('surat-masuk.*') ? 'active' : '' }}">
                                        <i class="nav-icon far fa-envelope"></i>
                                        <p>
                                            Surat Masuk
                                            <span class="right badge badge-warning">{{ $sidebarSuratMasukOpenCount ?? 0 }}</span>
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('surat-keluar.index') }}"
                                        class="nav-link {{ request()->routeIs('surat-keluar.*') ? 'active' : '' }}">
                                        <i class="nav-icon far fa-paper-plane"></i>
                                        <p>
                                            Surat Keluar
                                            <span class="right badge badge-info">{{ $sidebarSuratKeluarDraftCount ?? 0 }}</span>
                                        </p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('arsip.index') }}"
                                        class="nav-link {{ request()->routeIs('arsip.*') ? 'active' : '' }}">
                                        <i class="nav-icon far fa-folder-open"></i>
                                        <p>
                                            Arsip
                                            <span class="right badge badge-success">{{ $sidebarArsipCount ?? 0 }}</span>
                                        </p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        @if(Auth::user()->isSuperAdmin())
                            <li class="nav-header">MASTER DATA</li>

                            <li
                                class="nav-item has-treeview {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.jabatans.*') || request()->routeIs('admin.units.*') || request()->routeIs('admin.kategori-surats.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.jabatans.*') || request()->routeIs('admin.units.*') || request()->routeIs('admin.kategori-surats.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-database"></i>
                                    <p>
                                        Master Data
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('admin.users.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-user"></i>
                                            <p>User</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.jabatans.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.jabatans.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-id-badge"></i>
                                            <p>Jabatan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.units.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-building"></i>
                                            <p>Unit</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.kategori-surats.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.kategori-surats.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-folder"></i>
                                            <p>Kategori Surat</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        <li class="nav-header">MODUL LAINNYA</li>

                        <li class="nav-item">
                            <a href="{{ route('cuti.index') }}"
                                class="nav-link {{ request()->routeIs('cuti.*') ? 'active' : '' }}">
                                <i class="nav-icon far fa-calendar-check"></i>
                                <p>Cuti <span class="badge badge-dev ml-auto">DEV</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('rapat.index') }}"
                                class="nav-link {{ request()->routeIs('rapat.*') ? 'active' : '' }}">
                                <i class="nav-icon far fa-comments"></i>
                                <p>Rapat <span class="badge badge-dev ml-auto">DEV</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('persediaan.index') }}"
                                class="nav-link {{ request()->routeIs('persediaan.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cube"></i>
                                <p>Persediaan <span class="badge badge-dev ml-auto">DEV</span></p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            @yield('content-header')

            <section class="content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </section>
        </div>

        <!-- Footer -->
        <footer class="main-footer text-center">
            <small>&copy; {{ date('Y') }} <strong>Pengadilan Tinggi Agama Papua Barat</strong>. Sistem Informasi
                Persuratan.</small>
        </footer>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        function showToast(message, type = 'success') {
            const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#06b6d4' };
            const icons = { success: 'check-circle', error: 'times-circle', warning: 'exclamation-triangle', info: 'info-circle' };
            const toast = $(`
                <div class="custom-toast" style="border-left-color: ${colors[type]}">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${icons[type]} mr-2" style="color: ${colors[type]}; font-size: 1.1rem;"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `);
            $('#toastContainer').append(toast);
            setTimeout(() => toast.fadeOut(300, function () { $(this).remove(); }), 4000);
        }

        $(document).ready(function () {
            $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
        });
    </script>

    @stack('scripts')
</body>

</html>
