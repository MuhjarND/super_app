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

        .theme-toggle-btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #374151;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s ease;
        }

        .theme-toggle-btn:hover {
            background: #f9fafb;
            border-color: #9ca3af;
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

        /* ======================== GLOBAL LOADER ======================== */
        .global-loader {
            position: fixed;
            inset: 0;
            z-index: 20000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(3px);
        }

        .global-loader.is-visible {
            display: flex;
        }

        .global-loader-card {
            min-width: 220px;
            max-width: 320px;
            padding: 22px 24px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(229, 231, 235, 0.9);
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.16);
            text-align: center;
        }

        .global-loader-spinner {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px;
            border-radius: 50%;
            border: 4px solid rgba(59, 130, 246, 0.18);
            border-top-color: #2563eb;
            animation: globalLoaderSpin 0.8s linear infinite;
        }

        .global-loader-title {
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .global-loader-text {
            color: var(--text-secondary);
            font-size: 0.82rem;
            margin: 0;
        }

        @keyframes globalLoaderSpin {
            to {
                transform: rotate(360deg);
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

        /* ======================== DARK THEME ======================== */
        body.theme-dark {
            --sidebar-bg: #0f172a;
            --sidebar-border: #1f2937;
            --body-bg: #0b1120;
            --text-primary: #e5e7eb;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --card-shadow: 0 1px 3px rgba(15, 23, 42, 0.35), 0 1px 2px rgba(15, 23, 42, 0.25);
            --card-hover-shadow: 0 16px 36px rgba(2, 6, 23, 0.42);
        }

        body.theme-dark .main-header,
        body.theme-dark .main-footer,
        body.theme-dark .dropdown-menu,
        body.theme-dark .card,
        body.theme-dark .modal-content,
        body.theme-dark .page-header-card,
        body.theme-dark .quick-action,
        body.theme-dark .agenda-preview,
        body.theme-dark .attendance-info-box,
        body.theme-dark .attendance-detail-card,
        body.theme-dark .voting-stat-card,
        body.theme-dark .result-item-card {
            background: #111827 !important;
            color: #e5e7eb !important;
            border-color: #1f2937 !important;
        }

        body.theme-dark .main-sidebar .brand-link,
        body.theme-dark .sidebar-user,
        body.theme-dark .card-header,
        body.theme-dark .main-footer,
        body.theme-dark .modal-footer,
        body.theme-dark .table thead th,
        body.theme-dark .table td,
        body.theme-dark .dropdown-divider {
            border-color: #1f2937 !important;
        }

        body.theme-dark .main-header .nav-link,
        body.theme-dark .sidebar .nav-link,
        body.theme-dark .sidebar-user-name,
        body.theme-dark .dropdown-item,
        body.theme-dark .content-header h1,
        body.theme-dark .card-header .card-title,
        body.theme-dark .table td,
        body.theme-dark .form-group label,
        body.theme-dark .main-footer,
        body.theme-dark .text-dark {
            color: #e5e7eb !important;
        }

        body.theme-dark .main-header .nav-link:hover,
        body.theme-dark .sidebar .nav-link:hover,
        body.theme-dark .dropdown-item:hover,
        body.theme-dark .table tbody tr:hover,
        body.theme-dark .btn-outline-secondary:hover,
        body.theme-dark .theme-toggle-btn:hover {
            background: #172033 !important;
            color: #f8fafc !important;
        }

        body.theme-dark .sidebar .nav-link.active,
        body.theme-dark .theme-toggle-btn {
            background: #172554;
            color: #dbeafe;
            border-color: #1d4ed8;
        }

        body.theme-dark .table thead th,
        body.theme-dark .text-muted,
        body.theme-dark .sidebar-user-role,
        body.theme-dark .dataTables_wrapper .dataTables_length,
        body.theme-dark .dataTables_wrapper .dataTables_filter,
        body.theme-dark .dataTables_wrapper .dataTables_info,
        body.theme-dark small,
        body.theme-dark .form-hint {
            color: #94a3b8 !important;
        }

        body.theme-dark .form-control,
        body.theme-dark .dataTables_wrapper .dataTables_filter input,
        body.theme-dark .dataTables_wrapper .dataTables_length select,
        body.theme-dark .page-link,
        body.theme-dark .btn-outline-secondary {
            background: #0f172a !important;
            border-color: #334155 !important;
            color: #e5e7eb !important;
        }

        body.theme-dark .form-control:focus {
            border-color: #60a5fa !important;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.16) !important;
        }

        body.theme-dark .modal-header {
            background: linear-gradient(135deg, #0f3a7c 0%, #1d4ed8 100%);
        }

        body.theme-dark .main-header .navbar-nav .nav-item .dropdown-toggle:hover {
            background: #172033;
        }

        body.theme-dark .quick-action {
            color: #cbd5e1;
        }

        body.theme-dark .quick-action:hover {
            color: #dbeafe;
            border-color: #3b82f6;
        }

        body.theme-dark .toast-container .custom-toast {
            background: #111827;
            color: #e5e7eb;
        }

        body.theme-dark .global-loader {
            background: rgba(2, 6, 23, 0.74);
        }

        body.theme-dark .global-loader-card {
            background: rgba(15, 23, 42, 0.96);
            border-color: #1f2937;
            box-shadow: 0 22px 50px rgba(2, 6, 23, 0.45);
        }

        @media (max-width: 768px) {
            .content-header .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }

            .card-header,
            .card-body,
            .modal-body,
            .modal-footer {
                padding: 16px;
            }

            .page-header-card {
                padding: 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .theme-toggle-btn {
                padding: 8px 12px;
            }
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
                <li class="nav-item mr-2 d-flex align-items-center">
                    <button type="button" class="theme-toggle-btn" id="themeToggle">
                        <i class="fas fa-moon" id="themeToggleIcon"></i>
                        <span id="themeToggleLabel">Dark</span>
                    </button>
                </li>
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

                        @if(Auth::user()->canAccessPersuratanMenu())
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
                        @endif

                        @if(Auth::user()->canAccessMeetingMasterData())
                            <li class="nav-header">MASTER DATA</li>

                            <li
                                class="nav-item has-treeview {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.jabatans.*') || request()->routeIs('admin.units.*') || request()->routeIs('admin.bidangs.*') || request()->routeIs('admin.kategori-surats.*') || request()->routeIs('admin.kategori-rapats.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                class="nav-link {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.jabatans.*') || request()->routeIs('admin.units.*') || request()->routeIs('admin.bidangs.*') || request()->routeIs('admin.kategori-surats.*') || request()->routeIs('admin.kategori-rapats.*') ? 'active' : '' }}">
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
                                            <p>Unit Kerja</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.bidangs.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.bidangs.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-sitemap"></i>
                                            <p>Bidang</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.kategori-surats.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.kategori-surats.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-folder"></i>
                                            <p>Kategori Surat</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admin.kategori-rapats.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.kategori-rapats.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-comments"></i>
                                            <p>Kategori Rapat</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if(Auth::user()->canAccessMeetingModule())
                            <li class="nav-header">RAPAT</li>

                            <li
                                class="nav-item has-treeview {{ request()->routeIs('rapat.*') ? 'menu-open' : '' }}">
                                <a href="#"
                                    class="nav-link {{ request()->routeIs('rapat.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Smart Rapat
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('rapat.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.index') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-calendar-alt"></i>
                                            <p>Rapat</p>
                                        </a>
                                    </li>
                                    @if(Auth::user()->canAccessMeetingMinutes())
                                        <li class="nav-item">
                                            <a href="{{ route('rapat.notulensi.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.notulensi.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-file-alt"></i>
                                                <p>Notulensi</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if(Auth::user()->canAccessMeetingApproval())
                                        <li class="nav-item">
                                            <a href="{{ route('rapat.approval.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.approval.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-check-double"></i>
                                                <p>Approval</p>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item">
                                        <a href="{{ route('rapat.absensi.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.absensi.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-clipboard-check"></i>
                                            <p>Absensi</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('rapat.laporan.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.laporan.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-file-pdf"></i>
                                            <p>Laporan</p>
                                        </a>
                                    </li>
                                    @if(Auth::user()->canAccessAgendaPimpinan())
                                        <li class="nav-item">
                                            <a href="{{ route('rapat.agenda.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.agenda.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-calendar-day"></i>
                                                <p>Agenda Pimpinan</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if(Auth::user()->canManageVoting())
                                        <li class="nav-item">
                                            <a href="{{ route('rapat.voting.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.voting.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-poll"></i>
                                                <p>E-Voting</p>
                                            </a>
                                        </li>
                                    @endif
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

    <div class="global-loader" id="globalLoader" aria-hidden="true">
        <div class="global-loader-card">
            <div class="global-loader-spinner"></div>
            <div class="global-loader-title">Memproses...</div>
            <p class="global-loader-text" id="globalLoaderText">Mohon tunggu sebentar.</p>
        </div>
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
        const THEME_KEY = 'smart-theme';
        const SIDEBAR_KEY = 'smart-sidebar-collapse';
        const LOADER_DEFAULT_TEXT = 'Mohon tunggu sebentar.';

        function applyTheme(theme) {
            const isDark = theme === 'dark';
            document.body.classList.toggle('theme-dark', isDark);
            document.body.classList.toggle('theme-light', !isDark);

            const icon = document.getElementById('themeToggleIcon');
            const label = document.getElementById('themeToggleLabel');
            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
            if (label) {
                label.textContent = isDark ? 'Light' : 'Dark';
            }
        }

        window.AppLoader = (function () {
            let activeRequests = 0;

            function getElements() {
                return {
                    loader: document.getElementById('globalLoader'),
                    text: document.getElementById('globalLoaderText'),
                };
            }

            function show(message = LOADER_DEFAULT_TEXT) {
                const { loader, text } = getElements();
                if (!loader || !text) {
                    return;
                }

                text.textContent = message;
                loader.classList.add('is-visible');
                loader.setAttribute('aria-hidden', 'false');
            }

            function hide(force = false) {
                const { loader, text } = getElements();
                if (!loader || !text) {
                    return;
                }

                if (force) {
                    activeRequests = 0;
                }

                if (activeRequests > 0 && !force) {
                    return;
                }

                loader.classList.remove('is-visible');
                loader.setAttribute('aria-hidden', 'true');
                text.textContent = LOADER_DEFAULT_TEXT;
            }

            function requestStarted(message = LOADER_DEFAULT_TEXT) {
                activeRequests += 1;
                show(message);
            }

            function requestFinished() {
                activeRequests = Math.max(0, activeRequests - 1);
                if (activeRequests === 0) {
                    hide(true);
                }
            }

            return {
                show,
                hide,
                requestStarted,
                requestFinished,
            };
        })();

        function shouldShowLoaderForLink(element) {
            if (!element) {
                return false;
            }

            const href = element.getAttribute('href') || '';
            if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) {
                return false;
            }

            if (element.hasAttribute('download') || element.dataset.skipLoader !== undefined) {
                return false;
            }

            if (element.getAttribute('target') === '_blank' || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return false;
            }

            if (element.hasAttribute('data-toggle') || element.hasAttribute('data-bs-toggle')) {
                return false;
            }

            if (element.classList.contains('dropdown-toggle') || element.getAttribute('role') === 'button') {
                return false;
            }

            return true;
        }

        function shouldShowLoaderForForm(form) {
            if (!form || form.dataset.skipLoader !== undefined) {
                return false;
            }

            if (form.target === '_blank') {
                return false;
            }

            return true;
        }

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

            const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
            applyTheme(savedTheme);

            if (localStorage.getItem(SIDEBAR_KEY) === '1') {
                $('body').addClass('sidebar-collapse');
            }

            $('#themeToggle').on('click', function () {
                const nextTheme = $('body').hasClass('theme-dark') ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, nextTheme);
                applyTheme(nextTheme);
            });

            $('[data-widget="pushmenu"]').on('click', function () {
                setTimeout(function () {
                    localStorage.setItem(SIDEBAR_KEY, $('body').hasClass('sidebar-collapse') ? '1' : '0');
                }, 180);
            });

            $(document).on('click', 'a[href]', function (event) {
                if (event.isDefaultPrevented() || !shouldShowLoaderForLink(this)) {
                    return;
                }

                const message = this.dataset.loadingText || 'Memuat halaman...';
                setTimeout(function () {
                    window.AppLoader.show(message);
                }, 0);
            });

            $(document).on('submit', 'form', function () {
                if (!shouldShowLoaderForForm(this)) {
                    return;
                }

                const message = this.dataset.loadingText || 'Memproses permintaan...';
                window.AppLoader.show(message);
            });

            $(document).ajaxSend(function (_event, _xhr, settings) {
                if (settings && settings.showLoader === false) {
                    return;
                }

                const message = settings && settings.loadingMessage ? settings.loadingMessage : 'Memproses data...';
                window.AppLoader.requestStarted(message);
            });

            $(document).ajaxComplete(function (_event, _xhr, settings) {
                if (settings && settings.showLoader === false) {
                    return;
                }

                window.AppLoader.requestFinished();
            });

            if (typeof window.fetch === 'function') {
                const nativeFetch = window.fetch.bind(window);
                window.fetch = function (input, init = {}) {
                    if (init && init.showLoader === false) {
                        return nativeFetch(input, init);
                    }

                    const message = init && init.loadingMessage ? init.loadingMessage : 'Memproses data...';
                    window.AppLoader.requestStarted(message);

                    return nativeFetch(input, init).finally(function () {
                        window.AppLoader.requestFinished();
                    });
                };
            }
        });

        window.addEventListener('pageshow', function () {
            window.AppLoader.hide(true);
        });
    </script>

    @stack('scripts')
</body>

</html>
