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
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --primary-50: #eef2ff;
            --primary-100: #e0e7ff;
            --accent: #8b5cf6;
            --accent-light: #a78bfa;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --sidebar-width: 260px;
            --sidebar-bg: #ffffff;
            --sidebar-border: #e8eaed;
            --body-bg: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #9ca3af;
            --card-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            --card-hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            font-size: 12.5px;
            -webkit-font-smoothing: antialiased;
        }


        /* ======================== GLOBAL FONT SIZE ENFORCEMENT ======================== */
        h1, .h1 { font-size: 1.35rem !important; }
        h2, .h2 { font-size: 1.15rem !important; }
        h3, .h3 { font-size: 1rem !important; }
        h4, .h4 { font-size: 0.92rem !important; }
        h5, .h5 { font-size: 0.85rem !important; }
        h6, .h6 { font-size: 0.78rem !important; }
        .content-header h1 { font-size: 1.1rem !important; font-weight: 700; }
        .content-header .breadcrumb { font-size: 0.72rem; }
        .text-sm, small, .small { font-size: 0.76rem !important; }
        .lead { font-size: 0.92rem !important; }
        .table, .table th, .table td { font-size: 0.78rem !important; }
        .table thead th { font-size: 0.72rem !important; text-transform: uppercase; letter-spacing: 0.03em; }
        .dataTable, .dataTable th, .dataTable td { font-size: 0.78rem !important; }
        .dataTables_info, .dataTables_length, .dataTables_filter, .dataTables_paginate { font-size: 0.74rem !important; }
        .dataTables_wrapper label { font-size: 0.74rem !important; }
        .form-control { font-size: 0.8rem !important; padding: 0.35rem 0.65rem; }
        .form-control-sm { font-size: 0.74rem !important; }
        .form-control-lg { font-size: 0.88rem !important; }
        select.form-control { font-size: 0.8rem !important; }
        textarea.form-control { font-size: 0.8rem !important; }
        .form-group label, .form-label { font-size: 0.76rem !important; font-weight: 600; }
        .form-text, .help-block { font-size: 0.72rem !important; }
        .custom-control-label { font-size: 0.78rem !important; }
        .input-group-text { font-size: 0.78rem !important; }
        .custom-file-label { font-size: 0.78rem !important; }
        .btn { font-size: 0.78rem !important; padding: 0.35rem 0.75rem; }
        .btn-sm { font-size: 0.72rem !important; padding: 0.25rem 0.55rem; }
        .btn-lg { font-size: 0.85rem !important; }
        .btn-xs { font-size: 0.65rem !important; }
        .badge { font-size: 0.68rem !important; padding: 0.25em 0.55em; }
        .card-title { font-size: 0.88rem !important; font-weight: 700; }
        .card-header { font-size: 0.82rem; }
        .card-body { font-size: 0.8rem; }
        .card-footer { font-size: 0.76rem; }
        .alert { font-size: 0.78rem !important; padding: 0.6rem 0.9rem; }
        .modal-title { font-size: 0.92rem !important; font-weight: 700; }
        .modal-body { font-size: 0.8rem; }
        .modal-footer { font-size: 0.78rem; }
        .pagination .page-link { font-size: 0.74rem !important; padding: 0.3rem 0.6rem; }
        .dropdown-item { font-size: 0.78rem !important; padding: 0.35rem 0.8rem; }
        .dropdown-header { font-size: 0.72rem !important; }
        .nav-tabs .nav-link, .nav-pills .nav-link { font-size: 0.78rem !important; }
        .tab-content { font-size: 0.8rem; }
        .tooltip-inner { font-size: 0.72rem !important; }
        .popover { font-size: 0.78rem; }
        .popover-header { font-size: 0.82rem !important; }
        .select2-container--bootstrap4 .select2-selection { font-size: 0.8rem !important; }
        .select2-results__option { font-size: 0.78rem !important; }
        .select2-search__field { font-size: 0.78rem !important; }
        .info-box-text { font-size: 0.74rem !important; }
        .info-box-number { font-size: 1.1rem !important; }
        .small-box h3 { font-size: 1.3rem !important; }
        .small-box p { font-size: 0.76rem !important; }
        .list-group-item { font-size: 0.78rem; padding: 0.5rem 0.8rem; }
        .breadcrumb-item, .breadcrumb-item a { font-size: 0.72rem !important; }
        .swal2-title { font-size: 1rem !important; }
        .swal2-content, .swal2-html-container { font-size: 0.82rem !important; }
        .toast-body { font-size: 0.78rem; }
        .fc .fc-toolbar-title { font-size: 1rem !important; }
        .fc .fc-button { font-size: 0.74rem !important; }
        .fc .fc-daygrid-event { font-size: 0.7rem !important; }
        .fc td, .fc th { font-size: 0.74rem !important; }
        .progress { height: 0.6rem; }
        .table-responsive .table { font-size: 0.78rem !important; }


        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }

        /* ======================== SIDEBAR ======================== */
        .main-sidebar {
            background: var(--sidebar-bg) !important;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: none !important;
            width: var(--sidebar-width) !important;
            transition: width 0.2s ease, transform 0.2s ease;
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
            font-size: 0.85rem;
            letter-spacing: -0.01em;
        }

        .logo-mark {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
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
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.8rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .sidebar-user-avatar img,
        .topbar-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar-user-name {
            font-weight: 600;
            font-size: 0.78rem;
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
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.56rem;
            letter-spacing: 1.2px;
            padding: 20px 20px 6px;
            font-weight: 700;
        }

        .sidebar .nav-section {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sidebar .nav-section-toggle {
            width: calc(100% - 24px);
            margin: 10px 12px 3px;
            padding: 6px 10px;
            border: 0;
            background: transparent;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 0.56rem;
            letter-spacing: 1.2px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 10px;
            transition: all 0.15s ease;
        }

        .sidebar .nav-section-toggle:hover {
            background: #f8fafc;
            color: #64748b;
        }

        .sidebar .nav-section-toggle.has-alert {
            background: linear-gradient(135deg, #fef2f2, #fff1f2);
            color: #dc2626;
            box-shadow: inset 0 0 0 1px rgba(239, 68, 68, 0.12);
        }

        .sidebar .nav-section-toggle.has-alert:hover {
            background: linear-gradient(135deg, #fee2e2, #ffe4e6);
            color: #b91c1c;
        }

        .sidebar .nav-section-toggle.has-alert .section-chevron {
            color: #dc2626;
        }

        .sidebar .nav-section-toggle .section-chevron {
            font-size: 0.6rem;
            transition: transform 0.18s ease;
        }

        .sidebar .nav-section.is-collapsed .section-chevron {
            transform: rotate(-90deg);
        }

        .sidebar .nav-section-menu {
            padding: 0;
            margin: 0;
            overflow: hidden;
            transition: max-height 0.18s ease, opacity 0.18s ease;
        }

        .sidebar .nav-section.is-collapsed .nav-section-menu {
            max-height: 0 !important;
            opacity: 0;
            pointer-events: none;
        }

        .sidebar .nav-link {
            color: #1e293b !important;
            border-radius: 10px;
            margin: 1px 12px;
            padding: 6px 11px !important;
            transition: all 0.15s ease;
            font-size: 0.72rem !important;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .sidebar .nav-link:hover {
            background: #f5f3ff !important;
            color: #4f46e5 !important;
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #eef2ff, #e8e5ff) !important;
            color: #4f46e5 !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
            border-left: 3px solid #4f46e5;
        }

        .sidebar .nav-link .nav-icon {
            width: 18px;
            text-align: center;
            margin-right: 8px;
            font-size: 0.72rem !important;
            opacity: 0.5;
        }

        .sidebar .nav-link > p {
            display: flex;
            align-items: center;
            flex: 1 1 auto;
            min-width: 0;
            gap: 8px;
            margin: 0;
            line-height: 1.3;
        }

        .sidebar .nav-link > p > .right.badge {
            margin-left: auto;
            margin-right: 0;
            position: static;
            top: auto;
            right: auto;
            flex-shrink: 0;
            align-self: center;
        }

        .sidebar .nav-link > p > .badge:not(.right) {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: center;
            flex-shrink: 0;
        }

        .sidebar .nav-link.active .nav-icon {
            color: #3b82f6;
            opacity: 1;
        }

        .sidebar .nav-item-sub .nav-link {
            margin-left: 24px;
            padding: 5px 10px !important;
            font-size: 0.68rem !important;
            border-radius: 8px;
        }

        .sidebar .nav-item-sub .nav-link .nav-icon {
            width: 16px;
            margin-right: 7px;
            font-size: 0.68rem !important;
        }

        /* Smaller badges in sidebar */
        .sidebar .badge {
            padding: 1px 5px;
            font-size: 0.55rem !important;
            min-width: 16px;
            height: 16px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
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
            font-size: 0.78rem;
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
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.75rem;
            overflow: hidden;
        }

        

        .dropdown-menu {
            border: 1px solid #e8eaed;
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

        .notification-toggle {
            position: relative;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid #e8eaed;
            background: #ffffff;
            color: #475569 !important;
            transition: all 0.15s ease;
        }

        .notification-toggle:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a !important;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.35);
        }

        .notification-menu {
            width: 360px;
            max-width: calc(100vw - 24px);
            padding: 0;
            overflow: hidden;
        }

        .notification-menu-header {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .notification-menu-title {
            font-size: 0.86rem;
            font-weight: 800;
            color: #0f172a;
        }

        .notification-menu-subtitle {
            font-size: 0.74rem;
            color: #64748b;
            margin-top: 2px;
        }

        .notification-list {
            max-height: 420px;
            overflow-y: auto;
        }

        .notification-item {
            display: flex;
            gap: 12px;
            padding: 14px 16px;
            text-decoration: none !important;
            color: inherit !important;
            border-bottom: 1px solid #f1f5f9;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background: #f8fafc;
        }

        .notification-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.95rem;
        }

        .notification-item-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
        }

        .notification-item-subtitle {
            font-size: 0.76rem;
            color: #334155;
            margin-top: 2px;
        }

        .notification-item-description {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.4;
        }

        .notification-item-time {
            font-size: 0.71rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        .notification-empty {
            padding: 24px 18px;
            text-align: center;
            color: #64748b;
            font-size: 0.82rem;
        }

        /* ======================== CONTENT ======================== */
        .content-wrapper {
            background: var(--body-bg);
        }

        .content-wrapper,
        .main-footer,
        .main-header {
            margin-left: var(--sidebar-width) !important;
            transition: margin-left 0.2s ease;
        }

        body.sidebar-collapse .main-sidebar,
        body.sidebar-closed .main-sidebar {
            width: 76px !important;
        }

        body.sidebar-collapse .content-wrapper,
        body.sidebar-collapse .main-footer,
        body.sidebar-collapse .main-header,
        body.sidebar-closed .content-wrapper,
        body.sidebar-closed .main-footer,
        body.sidebar-closed .main-header {
            margin-left: 76px !important;
        }

        body.sidebar-collapse .main-sidebar .brand-link,
        body.sidebar-closed .main-sidebar .brand-link {
            justify-content: center;
            padding-left: 10px;
            padding-right: 10px;
        }

        body.sidebar-collapse .main-sidebar .brand-text,
        body.sidebar-collapse .sidebar-user > .sidebar-user-inner > div:last-child,
        body.sidebar-collapse .sidebar .nav-section-toggle span,
        body.sidebar-collapse .sidebar .nav-link > p,
        body.sidebar-closed .main-sidebar .brand-text,
        body.sidebar-closed .sidebar-user > .sidebar-user-inner > div:last-child,
        body.sidebar-closed .sidebar .nav-section-toggle span,
        body.sidebar-closed .sidebar .nav-link > p {
            display: none !important;
        }

        body.sidebar-collapse .sidebar-user,
        body.sidebar-closed .sidebar-user {
            padding: 14px 10px;
        }

        body.sidebar-collapse .sidebar-user-inner,
        body.sidebar-collapse .sidebar .nav-section-toggle,
        body.sidebar-collapse .sidebar .nav-link,
        body.sidebar-closed .sidebar-user-inner,
        body.sidebar-closed .sidebar .nav-section-toggle,
        body.sidebar-closed .sidebar .nav-link {
            justify-content: center;
        }

        body.sidebar-collapse .sidebar .nav-link,
        body.sidebar-closed .sidebar .nav-link {
            margin-left: 10px;
            margin-right: 10px;
            padding-left: 0 !important;
            padding-right: 0 !important;
            min-height: 36px;
        }

        body.sidebar-collapse .sidebar .nav-link .nav-icon,
        body.sidebar-closed .sidebar .nav-link .nav-icon {
            width: auto;
            margin-right: 0;
            opacity: 0.85;
        }

        body.sidebar-collapse .sidebar .nav-section-toggle,
        body.sidebar-closed .sidebar .nav-section-toggle {
            width: calc(100% - 20px);
            margin-left: 10px;
            margin-right: 10px;
            padding-left: 0;
            padding-right: 0;
        }

        body.sidebar-collapse .sidebar .nav-item-sub .nav-link,
        body.sidebar-closed .sidebar .nav-item-sub .nav-link {
            margin-left: 10px;
        }

        @media (min-width: 992px) {
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover {
                width: var(--sidebar-width) !important;
                z-index: 1040;
                box-shadow: 18px 0 42px rgba(15, 23, 42, 0.12);
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .brand-link,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .brand-link {
                justify-content: flex-start;
                padding-left: 20px;
                padding-right: 20px;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .brand-text,
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar-user > .sidebar-user-inner > div:last-child,
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-section-toggle span,
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-link > p,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .brand-text,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar-user > .sidebar-user-inner > div:last-child,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-section-toggle span,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-link > p {
                display: flex !important;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar-user,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar-user {
                padding: 20px;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar-user-inner,
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-section-toggle,
            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-link,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar-user-inner,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-section-toggle,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-link {
                justify-content: flex-start;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-link,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-link {
                margin-left: 12px;
                margin-right: 12px;
                padding-left: 11px !important;
                padding-right: 11px !important;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-link .nav-icon,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-link .nav-icon {
                width: 18px;
                margin-right: 8px;
                opacity: 0.5;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-section-toggle,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-section-toggle {
                width: calc(100% - 24px);
                margin-left: 12px;
                margin-right: 12px;
                padding-left: 10px;
                padding-right: 10px;
            }

            body.sidebar-mini.sidebar-collapse .main-sidebar:hover .sidebar .nav-item-sub .nav-link,
            body.sidebar-mini.sidebar-closed .main-sidebar:hover .sidebar .nav-item-sub .nav-link {
                margin-left: 24px;
            }
        }

        .content-header h1 {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 1.15rem;
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
            border: 1px solid #e8eaef;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);
            background: linear-gradient(180deg, #ffffff 0%, #fdfcff 100%);
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(99,102,241,0.06);
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
            font-size: 0.88rem;
        }

        .card-body {
            padding: 24px;
        }

        /* ======================== TABLES ======================== */
        .table thead th {
            background: #f9fafb;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
            border-top: none;
            padding: 12px 16px;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.78rem;
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
            padding: 2px 7px;
            font-weight: 700;
            font-size: 0.6rem;
            border-radius: 999px;
            border: 1px solid transparent;
            letter-spacing: 0.01em;
            line-height: 1.2;
        }

        .app-status-badge,
        .badge.badge-warning,
        .badge.badge-success,
        .badge.badge-danger,
        .badge.badge-info,
        .badge.badge-primary,
        .badge.badge-secondary,
        .badge.badge-light,
        .badge.badge-dark {
            padding: 3px 8px;
            font-weight: 700;
            box-shadow: none;
            border-color: transparent;
            color: #fff !important;
        }

        .badge.badge-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .badge.badge-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .badge.badge-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .badge.badge-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .badge.badge-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .badge.badge-secondary {
            background: linear-gradient(135deg, #64748b, #475569);
        }

        .badge.badge-light {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }

        .badge.badge-dark {
            background: linear-gradient(135deg, #334155, #1f2937);
        }

        /* ======================== BUTTONS ======================== */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.76rem;
            padding: 6px 14px;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
        }

        .app-create-btn,
        .btn-add-surat {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            color: #fff !important;
            padding: 8px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.76rem;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);
        }

        .app-create-btn:hover,
        .btn-add-surat:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2);
        }

        .app-create-btn i,
        .btn-add-surat i {
            margin: 0 !important;
            font-size: 0.95em;
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
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #64748b, #475569);
            box-shadow: none;
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #475569, #334155);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            box-shadow: none;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-success {
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: none;
        }

        .btn-outline-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-danger {
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            box-shadow: none;
        }

        .btn-outline-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-info {
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            box-shadow: none;
        }

        .btn-outline-info:hover {
            background: linear-gradient(135deg, #0891b2, #0e7490);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-outline-warning {
            border: 1px solid transparent;
            color: #fff;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: none;
        }

        .btn-outline-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #fff;
            transform: translateY(-1px);
        }

        .app-action-cell {
            white-space: nowrap;
            text-align: right;
        }

        .app-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 6px;
            row-gap: 6px;
        }

        .app-action-group > * {
            margin: 0 !important;
        }

        .app-icon-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 9px;
            border: 1px solid transparent;
            background: linear-gradient(135deg, #64748b, #475569);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.84rem;
            line-height: 1;
            transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease, border-color .15s ease, color .15s ease;
            box-shadow: none;
        }

        .app-icon-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #fff;
        }

        .app-icon-btn i {
            margin: 0 !important;
        }

        .app-icon-btn::after {
            content: none;
        }

        .app-icon-btn.view,
        .app-icon-btn.preview,
        .app-icon-btn.detail {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .app-icon-btn.edit,
        .app-icon-btn.update {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        }

        .app-icon-btn.delete,
        .app-icon-btn.reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .app-icon-btn.upload,
        .app-icon-btn.send,
        .app-icon-btn.process,
        .app-icon-btn.approve {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .app-icon-btn.download,
        .app-icon-btn.pdf,
        .app-icon-btn.file {
            background: linear-gradient(135deg, #f97316, #ea580c);
        }

        .app-icon-btn.link,
        .app-icon-btn.copy,
        .app-icon-btn.history {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        .app-icon-btn.archive,
        .app-icon-btn.restore,
        .app-icon-btn.cancel {
            background: linear-gradient(135deg, #64748b, #475569);
        }

        .app-action-group .btn.app-icon-btn,
        .app-action-group .app-icon-btn {
            min-width: 32px;
        }

        .action-btn {
            min-height: 32px;
            border-radius: 9px;
            font-weight: 700;
        }

        .approval-list-item .btn.btn-sm,
        .app-iconify {
            width: 32px;
            height: 32px;
            padding: 0;
            gap: 0;
            font-size: 0;
            line-height: 1;
            justify-content: center;
            border-radius: 9px;
            border: 1px solid transparent;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff !important;
            box-shadow: none;
        }

        .approval-list-item .btn.btn-sm i,
        .app-iconify i {
            font-size: 0.92rem;
            margin: 0 !important;
        }

        .action-btn.primary,
        .approval-list-item .btn.btn-sm.btn-primary,
        .app-iconify.primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }

        .action-btn.success,
        .approval-list-item .btn.btn-sm.btn-success,
        .app-iconify.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .action-btn.danger,
        .approval-list-item .btn.btn-sm.btn-danger,
        .app-iconify.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .action-btn.secondary,
        .approval-list-item .btn.btn-sm.btn-secondary,
        .approval-list-item .btn.btn-sm.btn-outline-secondary,
        .app-iconify.secondary {
            background: linear-gradient(135deg, #64748b, #475569);
        }

        .action-btn.warning,
        .approval-list-item .btn.btn-sm.btn-warning,
        .app-iconify.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .action-btn.info,
        .approval-list-item .btn.btn-sm.btn-info,
        .app-iconify.info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
        }

        /* ======================== MODALS ======================== */
        .modal-content {
            border: none;
            border-radius: var(--radius);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background: #ffffff;
            color: var(--text-primary);
            padding: 20px 24px;
            border-bottom: 1px solid #e8eaed;
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--primary);
        }

        .modal-header .close {
            color: var(--text-secondary);
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
            padding: 8px 12px;
            font-size: 0.78rem;
            color: var(--text-primary);
            transition: all 0.15s ease;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.75rem;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-control-file {
            font-size: 0.76rem;
        }

        /* ======================== FOOTER ======================== */
        .main-footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            font-size: 0.7rem;
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
            font-size: 0.78rem;
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
            border: 4px solid rgba(99, 102, 241, 0.18);
            border-top-color: #4f46e5;
            animation: globalLoaderSpin 0.8s linear infinite;
        }

        .global-loader-title {
            color: var(--text-primary);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .global-loader-text {
            color: var(--text-secondary);
            font-size: 0.74rem;
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
            border: 1px solid #e8eaed;
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header-card h3 {
            font-weight: 700;
            font-size: 0.95rem;
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
            font-size: 0.8rem;
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
            border: 1px solid #e8eaed;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.74rem;
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
            font-size: 0.76rem;
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
            font-size: 0.76rem;
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

        

        @media (max-width: 991.98px) {
            .main-sidebar {
                width: min(82vw, 280px) !important;
                margin-left: 0 !important;
                left: 0;
                transform: translateX(-108%);
                opacity: 0;
                pointer-events: none;
                transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
            }

            body.sidebar-collapse .main-sidebar,
            body.sidebar-closed .main-sidebar {
                transform: translateX(-108%);
                opacity: 0;
                pointer-events: none;
            }

            body.sidebar-open .main-sidebar {
                transform: translateX(0);
                opacity: 1;
                pointer-events: auto;
            }

            body.sidebar-open .main-sidebar {
                box-shadow: 18px 0 42px rgba(15, 23, 42, 0.18);
            }

            .content-wrapper,
            .main-footer,
            .main-header,
            body.sidebar-collapse .content-wrapper,
            body.sidebar-collapse .main-footer,
            body.sidebar-collapse .main-header,
            body.sidebar-closed .content-wrapper,
            body.sidebar-closed .main-footer,
            body.sidebar-closed .main-header,
            body.sidebar-open .content-wrapper,
            body.sidebar-open .main-footer,
            body.sidebar-open .main-header {
                margin-left: 0 !important;
            }

            body.sidebar-collapse .main-sidebar,
            body.sidebar-closed .main-sidebar {
                width: min(82vw, 280px) !important;
            }

            body.sidebar-collapse .main-sidebar .brand-text,
            body.sidebar-collapse .sidebar-user > .sidebar-user-inner > div:last-child,
            body.sidebar-collapse .sidebar .nav-section-toggle span,
            body.sidebar-collapse .sidebar .nav-link > p,
            body.sidebar-closed .main-sidebar .brand-text,
            body.sidebar-closed .sidebar-user > .sidebar-user-inner > div:last-child,
            body.sidebar-closed .sidebar .nav-section-toggle span,
            body.sidebar-closed .sidebar .nav-link > p {
                display: flex !important;
            }

            body.sidebar-collapse .sidebar-user-inner,
            body.sidebar-collapse .sidebar .nav-section-toggle,
            body.sidebar-collapse .sidebar .nav-link,
            body.sidebar-closed .sidebar-user-inner,
            body.sidebar-closed .sidebar .nav-section-toggle,
            body.sidebar-closed .sidebar .nav-link {
                justify-content: flex-start;
            }

            body.sidebar-collapse .sidebar .nav-link .nav-icon,
            body.sidebar-closed .sidebar .nav-link .nav-icon {
                width: 18px;
                margin-right: 8px;
            }

            .content-header {
                padding: 12px 0 0;
            }

            .content-header .container-fluid,
            .content > .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }

            .main-header {
                min-height: auto;
                padding: 6px 8px;
            }

            .main-header .navbar-nav {
                flex-direction: row;
                align-items: center;
                flex-wrap: nowrap;
                gap: 6px;
            }

            .main-header .navbar-nav .nav-item .dropdown-toggle {
                padding: 7px 9px;
                border-radius: 10px;
            }

            .main-header .navbar-nav.ml-auto {
                justify-content: flex-end;
                gap: 8px;
            }

            .main-header .navbar-nav.ml-auto .nav-item {
                margin-right: 0 !important;
            }

            .main-header .navbar-nav.ml-auto .dropdown-toggle span,
            .theme-toggle-btn #themeToggleLabel {
                display: none;
            }

            .theme-toggle-btn {
                padding: 7px 9px;
                min-width: 38px;
                justify-content: center;
            }

            .notification-menu {
                width: min(360px, calc(100vw - 20px));
                right: 0 !important;
                left: auto !important;
            }

            .sidebar-user {
                padding: 14px 16px;
            }

            .main-sidebar .brand-link {
                padding: 14px 16px;
            }

            .sidebar .nav-section-toggle {
                width: calc(100% - 18px);
                margin: 12px 9px 3px;
                padding: 8px 10px;
            }

            .sidebar .nav-link {
                margin: 1px 9px;
                padding: 9px 11px !important;
                font-size: 0.8rem;
            }

            .sidebar .nav-item-sub .nav-link {
                margin-left: 18px;
            }

            .card-header,
            .card-body,
            .modal-body,
            .modal-footer {
                padding: 14px;
            }

            .page-header-card {
                padding: 16px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .page-header-card h3,
            .content-header h1 {
                font-size: 1.08rem;
            }

            .btn,
            .app-create-btn,
            .btn-add-surat {
                min-height: 40px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 12px;
            }

            .table-responsive > .table {
                min-width: 640px;
            }

            .modal-dialog {
                margin: 0.75rem;
            }

            .modal-dialog.modal-lg,
            .modal-dialog.modal-xl {
                max-width: calc(100vw - 1.5rem);
            }

            .modal-content {
                border-radius: var(--radius);
            }

            .select2-container {
                width: 100% !important;
            }
        }

        @media (max-width: 767.98px) {
            .main-header {
                padding: 4px 6px;
            }

            .main-header .navbar {
                padding-left: 0;
                padding-right: 0;
            }

            .main-header .navbar-nav:first-child {
                margin-right: auto;
            }

            .main-header .navbar-nav.ml-auto {
                gap: 4px;
            }

            .main-header .navbar-nav.ml-auto .nav-item.mr-2 {
                margin-right: 0 !important;
            }

            .main-header .nav-link {
                padding-left: 0.45rem;
                padding-right: 0.45rem;
            }

            .topbar-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.68rem;
            }

            .notification-toggle {
                width: 34px;
                height: 34px;
                border-radius: 9px;
            }

            .notification-badge {
                min-width: 18px;
                height: 18px;
                font-size: 0.62rem;
                top: -4px;
                right: -4px;
            }

            .notification-menu {
                width: calc(100vw - 16px);
                max-width: calc(100vw - 16px);
                margin-top: 10px;
            }

            .notification-menu-header {
                padding: 12px 14px;
            }

            .notification-menu-title {
                font-size: 0.82rem;
            }

            .notification-menu-subtitle {
                font-size: 0.72rem;
            }

            .notification-item {
                padding: 12px 14px;
                gap: 10px;
            }

            .notification-item-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                font-size: 0.85rem;
            }

            .notification-item-title {
                font-size: 0.8rem;
            }

            .notification-item-subtitle,
            .notification-item-description,
            .notification-item-time {
                font-size: 0.72rem;
            }

            .app-icon-btn[data-mobile-label] {
                width: auto;
                min-width: 38px;
                min-height: 36px;
                padding: 0 12px;
                gap: 7px;
                font-size: 0.78rem;
                justify-content: center;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
            }

            .app-icon-btn[data-mobile-label]::after {
                content: attr(data-mobile-label);
                font-weight: 700;
                line-height: 1;
            }

            .app-action-cell {
                white-space: normal;
                text-align: left;
            }

            .app-action-cell .app-action-group,
            .table.table-mobile-stack .app-action-group,
            .approval-list-item .app-action-group {
                display: flex;
                width: 100%;
                justify-content: flex-start !important;
                align-items: stretch;
                gap: 8px;
            }

            .app-action-cell .app-action-group .app-icon-btn[data-mobile-label],
            .table.table-mobile-stack .app-action-group .app-icon-btn[data-mobile-label],
            .approval-list-item .app-action-group .app-icon-btn[data-mobile-label],
            .approval-list-item .app-action-group .btn.btn-sm[data-mobile-label] {
                flex: 0 1 auto;
                max-width: 100%;
            }

            .table.table-mobile-stack td[data-label="Aksi"] .app-action-group {
                margin-top: 2px;
            }

            .table.table-mobile-stack td[data-label="Aksi"] .app-icon-btn[data-mobile-label] {
                min-width: 0;
            }

            .content {
                padding-bottom: 18px;
            }

            .content-header {
                padding-top: 8px;
            }

            .content-header .container-fluid,
            .content > .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            .card,
            .page-header-card {
                border-radius: 14px;
            }

            .dropdown-menu {
                max-width: calc(100vw - 20px);
            }

            .modal-dialog {
                margin: 0.55rem;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 14px;
            }

            .modal-footer {
                gap: 8px;
            }

            .modal-footer .btn {
                width: 100%;
            }

            .main-footer {
                padding: 14px 12px;
            }

            .dataTables_wrapper .row:first-child > [class*="col-"],
            .dataTables_wrapper .row:last-child > [class*="col-"] {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                text-align: left !important;
            }

            .dataTables_wrapper .dataTables_filter {
                margin-top: 10px;
            }

            .dataTables_wrapper .dataTables_filter label,
            .dataTables_wrapper .dataTables_length label {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                margin-bottom: 0;
                font-size: 0.82rem;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                max-width: 100%;
                margin-left: 0 !important;
            }

            .dataTables_wrapper .dataTables_paginate {
                margin-top: 10px;
            }

            .dataTables_wrapper .pagination {
                flex-wrap: wrap;
                justify-content: flex-start !important;
                gap: 6px;
            }

            .table-responsive > .table.table-mobile-stack,
            .table.table-mobile-stack {
                min-width: 0 !important;
                width: 100% !important;
                border-collapse: separate;
                border-spacing: 0;
            }

            .table.table-mobile-stack,
            .table.table-mobile-stack thead,
            .table.table-mobile-stack tbody,
            .table.table-mobile-stack tr,
            .table.table-mobile-stack th,
            .table.table-mobile-stack td {
                display: block;
                width: 100%;
            }

            .table.table-mobile-stack thead {
                display: none;
            }

            .table.table-mobile-stack tbody tr {
                background: #ffffff;
                border: 1px solid #dbe3f0;
                border-radius: 14px;
                box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
                margin-bottom: 14px;
                overflow: hidden;
            }

            .table.table-mobile-stack tbody tr:last-child {
                margin-bottom: 0;
            }

            .table.table-mobile-stack tbody td {
                position: relative;
                padding: 12px 12px 12px 132px !important;
                min-height: 48px;
                border-top: none !important;
                border-bottom: 1px solid #eef2f7 !important;
                text-align: left !important;
                white-space: normal !important;
                vertical-align: top !important;
            }

            .table.table-mobile-stack tbody td:last-child {
                border-bottom: none !important;
            }

            .table.table-mobile-stack tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 12px;
                top: 12px;
                width: 104px;
                color: #64748b;
                font-size: 0.68rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                line-height: 1.35;
            }

            .table.table-mobile-stack tbody td.table-mobile-no-label,
            .table.table-mobile-stack tbody tr.table-mobile-fullrow-auto td {
                padding-left: 12px !important;
            }

            .table.table-mobile-stack tbody td.table-mobile-no-label::before,
            .table.table-mobile-stack tbody tr.table-mobile-fullrow-auto td::before {
                display: none;
            }

            .table.table-mobile-stack .app-action-group,
            .table.table-mobile-stack .d-flex,
            .table.table-mobile-stack .btn-group {
                justify-content: flex-start !important;
                flex-wrap: wrap;
                gap: 8px;
            }

            .table.table-mobile-stack .badge,
            .table.table-mobile-stack .rapat-chip,
            .table.table-mobile-stack .action-btn,
            .table.table-mobile-stack .action-chip-btn {
                white-space: normal;
            }

            .table.table-mobile-stack .row-toggle-col {
                width: 100%;
            }

            .table.table-mobile-stack .row-toggle-btn,
            .table.table-mobile-stack .btn-expand {
                width: 34px;
                height: 34px;
            }
        }

        .page-emoji {
            display: inline-block;
            margin-right: 0.45rem;
            font-style: normal;
            font-size: 0.92em;
            line-height: 1;
            transform: translateY(-1px);
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
                <li class="nav-item dropdown mr-2 d-flex align-items-center">
                    <a class="nav-link notification-toggle" data-toggle="dropdown" href="#" title="Tindak lanjut">
                        <i class="fas fa-bell"></i>
                        @if(($topbarActionCount ?? 0) > 0)
                            <span class="notification-badge">{{ ($topbarActionCount ?? 0) > 99 ? '99+' : ($topbarActionCount ?? 0) }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-right notification-menu">
                        <div class="notification-menu-header">
                            <div class="notification-menu-title">Tindak Lanjut</div>
                            <div class="notification-menu-subtitle">{{ $topbarActionCount ?? 0 }} tugas memerlukan tindakan Anda</div>
                        </div>
                        <div class="notification-list">
                            @forelse(($topbarActionItems ?? collect()) as $item)
                                <a href="{{ $item['url'] }}" class="notification-item">
                                    <div class="notification-item-icon" style="background: {{ $item['icon_bg'] }}; color: {{ $item['icon_color'] }};">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="notification-item-title">{{ $item['title'] }}</div>
                                        <div class="notification-item-subtitle">{{ $item['subtitle'] }}</div>
                                        <div class="notification-item-description">{{ \Illuminate\Support\Str::limit($item['description'], 90) }}</div>
                                        <div class="notification-item-time">{{ $item['time'] ?: 'Baru saja' }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="notification-empty">
                                    Tidak ada tindak lanjut yang perlu Anda proses saat ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </li>
                <li class="nav-item mr-2 d-flex align-items-center">
                    <a class="nav-link notification-toggle" href="{{ route('two-factor.edit') }}" title="Authenticator 2 Faktor">
                        <i class="fas fa-shield-alt" style="color: {{ Auth::user()->hasTwoFactorEnabled() ? '#10b981' : '#64748b' }};"></i>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                        <div class="topbar-avatar">
                            @if(Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}">
                            @else
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                        <span style="font-weight: 600; color: #374151;">{{ Auth::user()->name ?? 'User' }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="px-3 py-2">
                            <div style="font-weight: 600; font-size: 0.85rem; color: #111827;">
                                {{ Auth::user()->name ?? '-' }}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">{{ Auth::user()->display_jabatan }}
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="fas fa-user-cog mr-2 text-primary"></i> Profil Saya
                        </a>
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
                            @if(Auth::user()->profile_photo_path)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="{{ Auth::user()->name }}">
                            @else
                                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
                            @endif
                        </div>
                        <div>
                            <div class="sidebar-user-name">{{ Auth::user()->name ?? 'User' }}</div>
                            <div class="sidebar-user-role">{{ Auth::user()->roles->first()->display_name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Menu -->
                <nav class="mt-1">
                    @php($sidebarUser = Auth::user())
                    @php($isSidebarSuperAdmin = $sidebarUser && $sidebarUser->isSuperAdmin())
                    <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-th-large"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        @if($sidebarUser->canAccessLeadershipDashboard())
                            <li class="nav-item">
                                <a href="{{ route('dashboard.leadership') }}"
                                    class="nav-link {{ request()->routeIs('dashboard.leadership') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-tie"></i>
                                    <p>Dashboard Pimpinan</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('audit-trail.index') }}"
                                    class="nav-link {{ request()->routeIs('audit-trail.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>Audit Trail</p>
                                </a>
                            </li>
                        @endif

                        @if($sidebarUser->canAccessIntegratedCalendar())
                            <li class="nav-item">
                                <a href="{{ route('calendar.integrated.index') }}"
                                    class="nav-link {{ request()->routeIs('calendar.integrated.*') ? 'active' : '' }}">
                                    <i class="nav-icon far fa-calendar-alt"></i>
                                    <p>Kalender Terpadu</p>
                                </a>
                            </li>
                        @endif

                        @if($sidebarUser->canAccessUnifiedActionCenter())
                            <li class="nav-item">
                                <a href="{{ route('action-center.index') }}"
                                    class="nav-link {{ request()->routeIs('action-center.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-tasks"></i>
                                    <p>
                                        Tindak Lanjut Terpadu
                                        @if(($sidebarActionCenterCount ?? 0) > 0)
                                            <span class="right badge badge-danger">{{ ($sidebarActionCenterCount ?? 0) > 99 ? '99+' : $sidebarActionCenterCount }}</span>
                                        @endif
                                    </p>
                                </a>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessApprovalCenter())
                            <li class="nav-section " data-section="approval">
                                <button type="button" class="nav-section-toggle {{ ($sidebarApprovalTotalCount ?? 0) > 0 ? 'has-alert' : '' }}">
                                    <span>Approval</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('approval.index') }}"
                                            class="nav-link {{ request()->routeIs('approval.index') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-tasks"></i>
                                            <p>
                                                Tindaklanjuti
                                                @if(($sidebarApprovalTotalCount ?? 0) > 0)
                                                    <span class="right badge badge-danger">{{ $sidebarApprovalTotalCount }}</span>
                                                @endif
                                            </p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('approval.history') }}"
                                            class="nav-link {{ request()->routeIs('approval.history') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-history"></i>
                                            <p>Riwayat Approval</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessPersuratanMenu())
                            <li class="nav-section " data-section="persuratan">
                                <button type="button" class="nav-section-toggle {{ (($sidebarSuratMasukOpenCount ?? 0) > 0 || ($sidebarSuratKeluarDraftCount ?? 0) > 0) ? 'has-alert' : '' }}">
                                    <span>Persuratan</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    @if($isSidebarSuperAdmin || $sidebarUser->canAccessSuratMasukMenu())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('surat-masuk.index') }}"
                                                class="nav-link {{ request()->routeIs('surat-masuk.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-envelope"></i>
                                                <p>
                                                    Surat Masuk
                                                    @if(($sidebarSuratMasukOpenCount ?? 0) > 0)
                                                        <span class="right badge badge-danger">{{ $sidebarSuratMasukOpenCount }}</span>
                                                    @endif
                                                </p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($isSidebarSuperAdmin || $sidebarUser->canAccessSuratKeluarMenu())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('surat-keluar.index') }}"
                                                class="nav-link {{ request()->routeIs('surat-keluar.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-paper-plane"></i>
                                                <p>
                                                    Surat Keluar
                                                    @if(($sidebarSuratKeluarDraftCount ?? 0) > 0)
                                                        <span class="right badge badge-danger">{{ $sidebarSuratKeluarDraftCount }}</span>
                                                    @endif
                                                </p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($isSidebarSuperAdmin || $sidebarUser->canAccessSuratTemplateMenu())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('surat-template.index') }}"
                                                class="nav-link {{ request()->routeIs('surat-template.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-file-word"></i>
                                                <p>Template Surat</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessMeetingModule())
                            <li class="nav-section " data-section="rapat">
                                <button type="button" class="nav-section-toggle {{ ($sidebarNotulensiFollowUpCount ?? 0) > 0 ? 'has-alert' : '' }}">
                                    <span>Rapat / Agenda</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('rapat.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.index') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-calendar-alt"></i>
                                            <p>Rapat/Agenda</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canAccessMeetingMinutes())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('rapat.notulensi.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.notulensi.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-file-alt"></i>
                                                <p>Notulensi</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($isSidebarSuperAdmin || (!$sidebarUser->canAccessMeetingMinutes()) || (($sidebarNotulensiFollowUpCount ?? 0) > 0))
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('rapat.notulensi.follow-ups') }}"
                                                class="nav-link {{ request()->routeIs('rapat.notulensi.follow-ups') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-tasks"></i>
                                                <p>
                                                    Tindak Lanjut
                                                    @if(($sidebarNotulensiFollowUpCount ?? 0) > 0)
                                                        <span class="right badge badge-danger">{{ $sidebarNotulensiFollowUpCount }}</span>
                                                    @endif
                                                </p>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('rapat.absensi.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.absensi.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-clipboard-check"></i>
                                            <p>Absensi</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('rapat.laporan.index') }}"
                                            class="nav-link {{ request()->routeIs('rapat.laporan.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-file-pdf"></i>
                                            <p>Laporan</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canAccessAgendaPimpinan())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('rapat.agenda.index') }}"
                                                class="nav-link {{ request()->routeIs('rapat.agenda.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-calendar-day"></i>
                                                <p>Agenda Pimpinan</p>
                                            </a>
                                        </li>
                                    @endif
                                    @if($isSidebarSuperAdmin || $sidebarUser->canManageVoting())
                                        <li class="nav-item nav-item-sub">
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

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessLeaveModule())
                            <li class="nav-section " data-section="cuti">
                                <button type="button" class="nav-section-toggle">
                                    <span>Cuti</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('cuti.index') }}"
                                            class="nav-link {{ request()->routeIs('cuti.index') || request()->routeIs('cuti.create') || request()->routeIs('cuti.show') || request()->routeIs('cuti.edit') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-calendar-alt"></i>
                                            <p>Pengajuan Cuti</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canApproveLeave())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('cuti.approval.index') }}"
                                                class="nav-link {{ request()->routeIs('cuti.approval.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-user-check"></i>
                                                <p>Approval Cuti</p>
                                            </a>
                                        </li>
                                    @endif
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('cuti.balances.index') }}"
                                            class="nav-link {{ request()->routeIs('cuti.balances.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-wallet"></i>
                                            <p>Rekap Saldo</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('cuti.reports.index') }}"
                                            class="nav-link {{ request()->routeIs('cuti.reports.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-chart-bar"></i>
                                            <p>Laporan Cuti</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canManageLeaveMasterData())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('cuti.master.types.index') }}"
                                                class="nav-link {{ request()->routeIs('cuti.master.types.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-list-alt"></i>
                                                <p>Jenis Cuti</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('cuti.master.policies.index') }}"
                                                class="nav-link {{ request()->routeIs('cuti.master.policies.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-sliders-h"></i>
                                                <p>Kebijakan Cuti</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('cuti.master.holidays.index') }}"
                                                class="nav-link {{ request()->routeIs('cuti.master.holidays.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-calendar-check"></i>
                                                <p>Cuti Bersama</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('cuti.master.delegations.index') }}"
                                                class="nav-link {{ request()->routeIs('cuti.master.delegations.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-people-arrows"></i>
                                                <p>Delegasi Approval</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessInventoryModule())
                            <li class="nav-section " data-section="perawatan-alat-mesin">
                                <button type="button" class="nav-section-toggle">
                                    <span>Perawatan Alat dan Mesin</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('perawatan-alat-mesin.index') }}"
                                            class="nav-link {{ request()->routeIs('perawatan-alat-mesin.index') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-tachometer-alt"></i>
                                            <p>Dashboard</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('perawatan-alat-mesin.items.index') }}"
                                            class="nav-link {{ request()->routeIs('perawatan-alat-mesin.items.*') || request()->routeIs('perawatan-alat-mesin.details.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-boxes"></i>
                                            <p>Master Barang</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}"
                                            class="nav-link {{ request()->routeIs('perawatan-alat-mesin.maintenance.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-tools"></i>
                                            <p>Transaksi Perawatan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('perawatan-alat-mesin.reports.index') }}"
                                            class="nav-link {{ request()->routeIs('perawatan-alat-mesin.reports.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                            <p>Laporan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('perawatan-alat-mesin.qrcode.index') }}"
                                            class="nav-link {{ request()->routeIs('perawatan-alat-mesin.qrcode.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-qrcode"></i>
                                            <p>Cetak QR Code</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canManageInventoryMasterData())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('perawatan-alat-mesin.master.index', 'units') }}"
                                                class="nav-link {{ request()->is('perawatan-alat-dan-mesin/master/units*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-ruler-combined"></i>
                                                <p>Satuan Barang</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('perawatan-alat-mesin.master.index', 'conditions') }}"
                                                class="nav-link {{ request()->is('perawatan-alat-dan-mesin/master/conditions*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-clipboard-check"></i>
                                                <p>Kondisi Barang</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('perawatan-alat-mesin.master.index', 'rooms') }}"
                                                class="nav-link {{ request()->is('perawatan-alat-dan-mesin/master/rooms*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-door-open"></i>
                                                <p>Ruang</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('perawatan-alat-mesin.master.index', 'brands') }}"
                                                class="nav-link {{ request()->is('perawatan-alat-dan-mesin/master/brands*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-tags"></i>
                                                <p>Brand / Merk</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('perawatan-alat-mesin.authority.index') }}"
                                                class="nav-link {{ request()->routeIs('perawatan-alat-mesin.authority.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-user-tie"></i>
                                                <p>Kuasa Pengguna</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>

                            <li class="nav-section " data-section="persediaan-dev">
                                <button type="button" class="nav-section-toggle">
                                    <span>Persediaan</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('persediaan.index') }}"
                                            class="nav-link {{ request()->routeIs('persediaan.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-warehouse"></i>
                                            <p>Persediaan <span class="badge-dev ml-2">DEV</span></p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessProgressZiModule())
                            <li class="nav-section " data-section="progress-zi">
                                <button type="button" class="nav-section-toggle {{ ($sidebarProgressZiAttentionCount ?? 0) > 0 ? 'has-alert' : '' }}">
                                    <span>Progress ZI</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('progress-zi.dashboard') }}"
                                            class="nav-link {{ request()->routeIs('progress-zi.dashboard') || request()->routeIs('progress-zi.index') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-chart-line"></i>
                                            <p>Rekapan ZI</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('progress-zi.activities.index') }}"
                                            class="nav-link {{ request()->routeIs('progress-zi.activities.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-tasks"></i>
                                            <p>
                                                Monitoring Kegiatan
                                                @if(($sidebarProgressZiAttentionCount ?? 0) > 0)
                                                    <span class="right badge badge-danger">{{ $sidebarProgressZiAttentionCount }}</span>
                                                @endif
                                            </p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('progress-zi.guidelines.index') }}"
                                            class="nav-link {{ request()->routeIs('progress-zi.guidelines.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-book-open"></i>
                                            <p>Pedoman ZI</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessMeetingMasterData() || $sidebarUser->canManageProgressZiMasterData())
                            <li class="nav-section " data-section="master-data">
                                <button type="button" class="nav-section-toggle">
                                    <span>Master Data</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.users.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-user"></i>
                                            <p>User</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.jabatans.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.jabatans.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-id-badge"></i>
                                            <p>Jabatan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.units.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.units.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-building"></i>
                                            <p>Unit Kerja</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.bidangs.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.bidangs.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-sitemap"></i>
                                            <p>Bidang</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.kategori-surats.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.kategori-surats.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-folder"></i>
                                            <p>Kategori Surat</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.kategori-rapats.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.kategori-rapats.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-comments"></i>
                                            <p>Kategori Rapat</p>
                                        </a>
                                    </li>
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('admin.dasar-hukums.index') }}"
                                            class="nav-link {{ request()->routeIs('admin.dasar-hukums.*') ? 'active' : '' }}">
                                            <i class="nav-icon fas fa-balance-scale"></i>
                                            <p>Dasar Hukum</p>
                                        </a>
                                    </li>
                                    @if($isSidebarSuperAdmin || $sidebarUser->canManageProgressZiMasterData())
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('progress-zi.periods.index') }}"
                                                class="nav-link {{ request()->routeIs('progress-zi.periods.*') ? 'active' : '' }}">
                                                <i class="nav-icon far fa-calendar-alt"></i>
                                                <p>Periode ZI</p>
                                            </a>
                                        </li>
                                        <li class="nav-item nav-item-sub">
                                            <a href="{{ route('progress-zi.areas.index') }}"
                                                class="nav-link {{ request()->routeIs('progress-zi.areas.*') ? 'active' : '' }}">
                                                <i class="nav-icon fas fa-layer-group"></i>
                                                <p>Area ZI</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if($isSidebarSuperAdmin || $sidebarUser->canAccessArchiveMenu())
                            <li class="nav-section " data-section="arsip">
                                <button type="button" class="nav-section-toggle">
                                    <span>Arsip</span>
                                    <i class="fas fa-chevron-down section-chevron"></i>
                                </button>
                                <ul class="nav nav-pills flex-column nav-section-menu">
                                    <li class="nav-item nav-item-sub">
                                        <a href="{{ route('arsip.index') }}"
                                            class="nav-link {{ request()->routeIs('arsip.*') ? 'active' : '' }}">
                                            <i class="nav-icon far fa-folder-open"></i>
                                            <p>Arsip</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
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
        
        const SIDEBAR_KEY = 'smart-sidebar-collapse';
        const SIDEBAR_SECTION_KEY = 'smart-sidebar-sections';
        const LOADER_DEFAULT_TEXT = 'Mohon tunggu sebentar.';

        function decoratePageHeaderEmoji() {
            const routeName = @json(optional(request()->route())->getName());
            let emoji = '📌';

            if (!routeName) {
                emoji = '📌';
            } else if (routeName.startsWith('dashboard')) {
                emoji = '🏠';
            } else if (routeName.startsWith('action-center')) {
                emoji = '📥';
            } else if (routeName.startsWith('calendar.')) {
                emoji = '🗓️';
            } else if (routeName.startsWith('surat-masuk') || routeName.startsWith('surat-keluar') || routeName.startsWith('surat-template')) {
                emoji = '📄';
            } else if (routeName.startsWith('rapat') || routeName.startsWith('agenda-pimpinan')) {
                emoji = '📝';
            } else if (routeName.startsWith('cuti')) {
                emoji = '🌴';
            } else if (routeName.startsWith('progress-zi')) {
                emoji = '📈';
            } else if (routeName.startsWith('perawatan-alat-mesin') || routeName.startsWith('persediaan')) {
                emoji = '🛠️';
            } else if (routeName.startsWith('approval')) {
                emoji = '✅';
            } else if (routeName.startsWith('arsip')) {
                emoji = '🗂️';
            } else if (routeName.startsWith('admin.')) {
                emoji = '⚙️';
            }

            document.querySelectorAll('.content-header h1').forEach(function (heading) {
                if (heading.querySelector('.page-emoji')) {
                    return;
                }

                const emojiEl = document.createElement('span');
                emojiEl.className = 'page-emoji';
                emojiEl.textContent = emoji;
                heading.prepend(emojiEl);
            });
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

        function syncMobileSidebarState() {
            if (window.innerWidth >= 992) {
                return;
            }

            if (!$('body').hasClass('sidebar-collapse') && !$('body').hasClass('sidebar-open')) {
                $('body').addClass('sidebar-collapse');
            }
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

        function registerActionTooltips(selector) {
            const fallbackLabels = {
                view: 'Lihat',
                preview: 'Preview',
                detail: 'Detail',
                open: 'Buka',
                show: 'Buka',
                edit: 'Edit',
                update: 'Perbarui',
                delete: 'Hapus',
                reject: 'Tolak',
                upload: 'Upload',
                send: 'Kirim',
                process: 'Proses',
                approve: 'Setujui',
                disposisi: 'Disposisi',
                teruskan: 'Teruskan',
                naikan: 'Naikkan',
                followup: 'Tindaklanjuti',
                download: 'Download',
                pdf: 'Export PDF',
                file: 'Buka Berkas',
                link: 'Salin Tautan',
                copy: 'Salin',
                history: 'Lihat Riwayat',
                archive: 'Arsip',
                restore: 'Pulihkan',
                cancel: 'Batalkan'
            };

            $(selector).each(function () {
                const $el = $(this);
                let label = ($el.attr('aria-label') || $el.attr('title') || $el.text() || '').replace(/\s+/g, ' ').trim();

                if (!label) {
                    Object.keys(fallbackLabels).some(function (key) {
                        if ($el.hasClass(key)) {
                            label = fallbackLabels[key];
                            return true;
                        }
                        return false;
                    });
                }

                if (label && !$el.attr('title')) {
                    $el.attr('title', label);
                }

                if (label && !$el.attr('aria-label')) {
                    $el.attr('aria-label', label);
                }
            });

            $(selector).tooltip('dispose');
            $(selector).tooltip({ container: 'body', trigger: 'hover', placement: 'top' });
        }

        function syncMobileActionLabels() {
            const isMobileViewport = window.innerWidth < 768;

            document.querySelectorAll('.app-icon-btn, .approval-list-item .btn.btn-sm, .app-iconify').forEach(function (button) {
                if (!button) {
                    return;
                }

                const explicitLabel = button.getAttribute('data-mobile-label');
                const autoLabel = button.getAttribute('data-mobile-auto-label') === '1';
                const label = (button.getAttribute('aria-label') || button.getAttribute('title') || button.textContent || '').replace(/\s+/g, ' ').trim();

                if (isMobileViewport) {
                    if (!explicitLabel && label) {
                        button.setAttribute('data-mobile-label', label);
                        button.setAttribute('data-mobile-auto-label', '1');
                    }
                } else if (autoLabel) {
                    button.removeAttribute('data-mobile-label');
                    button.removeAttribute('data-mobile-auto-label');
                }
            });
        }

        function shouldSkipMobileTable(table) {
            if (!table || !table.tHead || !table.tBodies.length || !table.querySelector('tbody tr')) {
                return true;
            }

            if (table.closest('.fc, .dataTables_scrollHead, .dataTables_scrollBody') || table.dataset.mobileStackIgnore === '1') {
                return true;
            }

            if (
                table.classList.contains('archive-mobile-table') ||
                table.classList.contains('laporan-mobile-table') ||
                table.classList.contains('leave-show-table') ||
                table.classList.contains('inventory-module-table') ||
                table.classList.contains('inventory-detail-table') ||
                table.classList.contains('history-table') ||
                table.classList.contains('zi-area-table') ||
                table.classList.contains('zi-period-table') ||
                table.classList.contains('surat-arsip-table') ||
                table.classList.contains('surat-keluar-history-table') ||
                table.classList.contains('laporan-arsip-table') ||
                table.classList.contains('admin-users-table') ||
                table.classList.contains('admin-units-table') ||
                table.classList.contains('admin-bidangs-table') ||
                table.classList.contains('admin-kategori-table') ||
                table.classList.contains('leave-type-table')
            ) {
                return true;
            }

            return false;
        }

        function resetMobileTableStack(table) {
            if (!table) {
                return;
            }

            table.classList.remove('table-mobile-stack');

            table.querySelectorAll('td[data-mobile-auto-label="1"]').forEach(function (cell) {
                cell.classList.remove('table-mobile-no-label');
                cell.removeAttribute('data-label');
                cell.removeAttribute('data-mobile-auto-label');
            });

            table.querySelectorAll('tr.table-mobile-fullrow-auto').forEach(function (row) {
                row.classList.remove('table-mobile-fullrow-auto');
            });
        }

        function applyMobileTableStack(table) {
            if (!table || shouldSkipMobileTable(table)) {
                return;
            }

            const headerCells = Array.from(table.tHead.querySelectorAll('tr:first-child th')).map(function (th) {
                return (th.textContent || '').replace(/\s+/g, ' ').trim();
            });

            if (!headerCells.length) {
                return;
            }

            table.classList.add('table-mobile-stack');

            table.querySelectorAll('tbody tr').forEach(function (row) {
                const cells = Array.from(row.children).filter(function (cell) {
                    return cell.tagName === 'TD' || cell.tagName === 'TH';
                });

                const isSingleFullRow = cells.length === 1 && (cells[0].colSpan || 1) > 1;
                row.classList.toggle('table-mobile-fullrow-auto', isSingleFullRow);

                cells.forEach(function (cell, index) {
                    const label = isSingleFullRow ? '' : (headerCells[index] || '');
                    cell.setAttribute('data-mobile-auto-label', '1');

                    if (label) {
                        cell.setAttribute('data-label', label);
                        cell.classList.remove('table-mobile-no-label');
                    } else {
                        cell.setAttribute('data-label', '');
                        cell.classList.add('table-mobile-no-label');
                    }
                });
            });
        }

        function syncMobileTableStacks() {
            const isMobileViewport = window.innerWidth < 768;
            document.querySelectorAll('.content-wrapper table.table').forEach(function (table) {
                if (isMobileViewport) {
                    applyMobileTableStack(table);
                } else {
                    resetMobileTableStack(table);
                }
            });
        }

        $(document).ready(function () {
            $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
            registerActionTooltips('.app-icon-btn, .approval-list-item .btn.btn-sm');
            syncMobileActionLabels();

            $(document).on('mouseenter', '.app-icon-btn, .approval-list-item .btn.btn-sm', function () {
                if (!$(this).data('bs.tooltip')) {
                    registerActionTooltips(this);
                }
            });
            syncMobileSidebarState();

            if (localStorage.getItem(SIDEBAR_KEY) === '1') {
                $('body').addClass('sidebar-collapse');
            }

            let savedSections = {};
            try {
                savedSections = JSON.parse(localStorage.getItem(SIDEBAR_SECTION_KEY) || '{}');
            } catch (error) {
                savedSections = {};
            }

            $('.nav-section').each(function () {
                const $section = $(this);
                const sectionKey = $section.data('section');
                if (!sectionKey) {
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(savedSections, sectionKey)) {
                    $section.toggleClass('is-collapsed', !!savedSections[sectionKey]);
                }
            });

            $(document).on('click', '.nav-section-toggle', function () {
                const $section = $(this).closest('.nav-section');
                const sectionKey = $section.data('section');
                if (!sectionKey) {
                    return;
                }

                $section.toggleClass('is-collapsed');
                savedSections[sectionKey] = $section.hasClass('is-collapsed');
                localStorage.setItem(SIDEBAR_SECTION_KEY, JSON.stringify(savedSections));
            });

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

            $(window).on('resize', function () {
                syncMobileSidebarState();
            });

            $(document).on('click', '.content-wrapper, .main-footer', function () {
                if (window.innerWidth < 992 && $('body').hasClass('sidebar-open')) {
                    $('body').removeClass('sidebar-open').addClass('sidebar-collapse');
                }
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

            decoratePageHeaderEmoji();
            syncMobileTableStacks();
            syncMobileActionLabels();

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

            $(document).on('draw.dt', function () {
                syncMobileTableStacks();
                syncMobileActionLabels();
            });

            let mobileTableSyncTimer = null;
            $(window).on('resize orientationchange', function () {
                clearTimeout(mobileTableSyncTimer);
                mobileTableSyncTimer = setTimeout(function () {
                    syncMobileTableStacks();
                    syncMobileActionLabels();
                }, 120);
            });
        });

        window.addEventListener('pageshow', function () {
            window.AppLoader.hide(true);
        });
    </script>

    @stack('scripts')
</body>

</html>
