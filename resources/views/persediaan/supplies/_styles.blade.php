@include('persediaan.partials.module-styles')

<style>
    :root {
        --supply-primary: #6d4aff;
        --supply-primary-dark: #5532e6;
        --supply-ink: #111827;
        --supply-muted: #64748b;
        --supply-line: #e5edf7;
        --supply-soft: #f6f8fc;
        --supply-soft-primary: #f1edff;
        --supply-success: #0f9f6e;
        --supply-danger: #ef4444;
        --supply-shadow: 0 14px 34px rgba(17, 24, 39, 0.07);
    }

    .inventory-module-hero {
        padding: 4px 0 14px;
        margin-bottom: 4px;
    }

    .inventory-module-title {
        color: var(--supply-ink);
        font-size: 1.22rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .inventory-module-subtitle {
        color: var(--supply-muted);
        font-size: 0.88rem;
    }

    .inventory-module-shell {
        overflow: hidden;
        margin-top: 0;
        border: 1px solid var(--supply-line);
        border-radius: 22px;
        background: #fff;
        box-shadow: var(--supply-shadow);
    }

    .inventory-module-board-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--supply-line);
        background: #fff;
    }

    .inventory-module-board-body {
        padding: 18px;
        background: linear-gradient(180deg, #fff, #fbfcff);
    }

    .inventory-module-board-title,
    .inventory-module-panel-title {
        color: var(--supply-ink);
        font-size: 0.94rem;
        font-weight: 800;
        letter-spacing: -0.01em;
    }

    .inventory-module-board-title i,
    .inventory-module-panel-title i {
        color: #94a3b8 !important;
    }

    .inventory-module-muted {
        color: var(--supply-muted);
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .inventory-module-chip {
        display: inline-flex;
        align-items: center;
        min-height: 30px;
        padding: 6px 12px;
        border: 1px solid #e3ddff;
        border-radius: 999px;
        background: var(--supply-soft-primary);
        color: var(--supply-primary-dark);
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
    }

    .inventory-module-panel {
        overflow: hidden;
        border: 1px solid var(--supply-line);
        border-radius: 18px;
        background: #fff;
        box-shadow: none;
    }

    .inventory-module-panel-header {
        padding: 13px 16px;
        border-bottom: 1px solid var(--supply-line);
        background: #fff;
    }

    .inventory-module-panel-body {
        padding: 16px;
    }

    .inventory-module-stat {
        height: 100%;
        padding: 15px;
        border: 1px solid var(--supply-line);
        border-radius: 18px;
        background: #fff;
        box-shadow: none;
    }

    .inventory-module-stat-label {
        color: var(--supply-muted);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
    }

    .inventory-module-stat-value,
    .supply-stat-value {
        margin-top: 5px;
        color: var(--supply-ink);
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .inventory-module-table {
        color: var(--supply-ink);
        table-layout: auto;
    }

    .inventory-module-table thead th {
        padding: 12px 14px;
        border-top: 0;
        border-bottom: 1px solid var(--supply-line);
        background: #f8fafc;
        color: #475569;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .inventory-module-table td {
        padding: 13px 14px;
        border-color: var(--supply-line);
        font-size: 0.86rem;
        vertical-align: middle;
    }

    .inventory-module-table tbody tr:hover {
        background: #fafcff;
    }

    .inventory-module-empty {
        padding: 18px;
        border: 1px dashed #cbd5e1;
        border-radius: 16px;
        background: #fff;
        color: var(--supply-muted);
        font-size: 0.86rem;
        text-align: center;
    }

    .inventory-module-empty i {
        display: block;
        margin-bottom: 8px;
        color: #94a3b8;
        font-size: 1.25rem;
    }

    .supply-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .supply-action-row .btn,
    .inventory-module-panel .btn,
    .modal .btn {
        min-height: 36px;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 800;
        padding: 7px 14px;
        box-shadow: none;
    }

    .supply-action-row .app-create-btn,
    .modal .app-create-btn {
        border-color: var(--supply-primary);
        background: linear-gradient(135deg, var(--supply-primary), #8b5cf6);
        color: #fff;
    }

    .supply-action-row .btn-outline-primary,
    .inventory-module-panel .btn-outline-primary {
        border-color: #ded7ff;
        color: var(--supply-primary-dark);
        background: #fff;
    }

    .supply-action-row .btn-outline-secondary,
    .inventory-module-panel .btn-outline-secondary,
    .modal .btn-outline-secondary {
        border-color: var(--supply-line);
        color: #475569;
        background: #fff;
    }

    .supply-search-bar {
        position: relative;
    }

    .supply-search-bar i {
        position: absolute;
        top: 50%;
        left: 15px;
        z-index: 2;
        color: #94a3b8;
        transform: translateY(-50%);
    }

    .supply-search-bar .form-control {
        height: 42px;
        padding-left: 40px;
        border: 1px solid var(--supply-line);
        border-radius: 14px;
        color: var(--supply-ink);
        font-size: 0.88rem;
        font-weight: 700;
        box-shadow: none;
    }

    .supply-search-bar .form-control:focus,
    .supply-purpose-card .form-control:focus,
    .modal .form-control:focus {
        border-color: #b9a9ff;
        box-shadow: 0 0 0 3px rgba(109, 74, 255, 0.12);
    }

    .supply-catalog-grid,
    .supply-shop-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(178px, 1fr));
        gap: 14px;
    }

    .supply-request-shop .inventory-module-panel {
        border: 0;
        background: transparent;
    }

    .supply-request-shop .inventory-module-panel-body {
        padding: 0;
    }

    .supply-item-card,
    .supply-shop-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 100%;
        overflow: hidden;
        border: 1px solid var(--supply-line);
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 10px 24px rgba(17, 24, 39, 0.045);
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
    }

    .supply-item-card {
        padding: 14px;
    }

    .supply-item-card:hover,
    .supply-shop-card:hover {
        border-color: #d6ccff;
        box-shadow: 0 14px 30px rgba(17, 24, 39, 0.075);
        transform: translateY(-1px);
    }

    .supply-shop-card.is-selected {
        border-color: #b9a9ff;
        box-shadow: inset 0 0 0 2px rgba(109, 74, 255, 0.16), 0 16px 30px rgba(109, 74, 255, 0.12);
    }

    .supply-shop-image {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 118px;
        background: linear-gradient(135deg, #f6f8fc, #f1edff);
        color: var(--supply-primary);
        font-size: 1.8rem;
    }

    .supply-shop-image img,
    .supply-image-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .supply-shop-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: 10px;
        padding: 12px;
    }

    .supply-shop-name,
    .supply-item-name {
        color: var(--supply-ink);
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.28;
    }

    .supply-shop-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .supply-item-meta {
        color: var(--supply-muted);
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .supply-stock-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 5px 9px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.72rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .supply-stock-pill.low {
        background: #fff1f2;
        color: #e11d48;
    }

    .supply-qty-control {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) 34px;
        gap: 6px;
        align-items: center;
        margin-top: auto;
    }

    .supply-qty-btn {
        width: 34px;
        height: 34px;
        border: 1px solid #e3ddff;
        border-radius: 11px;
        background: var(--supply-soft-primary);
        color: var(--supply-primary-dark);
        font-size: 1rem;
        font-weight: 900;
        line-height: 1;
    }

    .supply-qty-btn.plus {
        border-color: var(--supply-primary);
        background: var(--supply-primary);
        color: #fff;
    }

    .supply-qty-input {
        width: 100%;
        height: 34px;
        border: 1px solid var(--supply-line);
        border-radius: 11px;
        color: var(--supply-ink);
        font-size: 0.86rem;
        font-weight: 900;
        text-align: center;
        box-shadow: none;
    }

    .supply-image-thumb {
        width: 64px;
        height: 50px;
        border: 1px solid var(--supply-line);
        border-radius: 13px;
        background: var(--supply-soft);
    }

    .supply-signature-thumb {
        width: 116px;
        height: 46px;
        object-fit: contain;
        padding: 4px;
        border: 1px solid var(--supply-line);
        border-radius: 12px;
        background: #fff;
    }

    .supply-purpose-card {
        padding: 16px;
        border: 1px solid var(--supply-line);
        border-radius: 18px;
        background: #fff;
    }

    .supply-purpose-card label,
    .modal label {
        color: var(--supply-ink);
        font-size: 0.78rem;
        font-weight: 800;
    }

    .supply-purpose-card .form-control,
    .modal .form-control {
        border-color: var(--supply-line);
        border-radius: 14px;
        font-size: 0.88rem;
        box-shadow: none;
    }

    .supply-custom-summary {
        margin-top: 10px;
        padding: 9px 11px;
        border-radius: 13px;
        background: var(--supply-soft-primary);
        color: var(--supply-primary-dark);
        font-size: 0.8rem;
        font-weight: 800;
    }

    .supply-request-item {
        margin-bottom: 10px;
        padding: 12px;
        border: 1px solid var(--supply-line);
        border-radius: 14px;
        background: #fff;
    }

    .supply-filter-form .form-control {
        min-height: 36px;
        border-color: var(--supply-line);
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        box-shadow: none;
    }

    .supply-search-compact {
        max-width: 190px;
    }

    .supply-readable-text,
    .supply-note-alert {
        font-size: 0.88rem;
        line-height: 1.55;
    }

    .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 22px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, 0.22);
    }

    .modal-header,
    .modal-footer {
        border-color: var(--supply-line);
        background: #fff;
    }

    .modal-title {
        color: var(--supply-ink);
        font-size: 1rem;
        font-weight: 800;
    }

    .modal .custom-control-label {
        color: var(--supply-ink);
        font-size: 0.84rem;
        font-weight: 800;
    }

    @media (max-width: 991.98px) {
        .supply-catalog-grid,
        .supply-shop-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .inventory-module-hero {
            padding: 0 0 12px;
        }

        .inventory-module-title {
            font-size: 1.08rem;
        }

        .inventory-module-shell {
            border-radius: 20px;
        }

        .inventory-module-board-header,
        .inventory-module-board-body {
            padding: 14px;
        }

        .inventory-module-board-title,
        .inventory-module-panel-title {
            font-size: 0.9rem;
        }

        .inventory-module-board-subtitle,
        .inventory-module-panel-subtitle {
            display: none;
        }

        .inventory-module-hero > .supply-action-row {
            display: grid;
            width: 100%;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .inventory-module-hero > .supply-action-row .btn {
            width: 100%;
        }

        .supply-filter-form {
            width: 100%;
            margin-top: 8px;
        }

        .supply-filter-form .form-control,
        .supply-filter-form .btn {
            width: 100%;
            margin: 0 0 8px !important;
        }

        .supply-catalog-grid,
        .supply-shop-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .supply-shop-image {
            height: 98px;
            font-size: 1.45rem;
        }

        .supply-shop-body {
            padding: 10px;
            gap: 8px;
        }

        .supply-shop-name,
        .supply-item-name {
            font-size: 0.82rem;
        }

        .supply-stock-pill {
            min-height: 22px;
            padding: 4px 7px;
            font-size: 0.68rem;
        }

        .supply-qty-control {
            grid-template-columns: 32px minmax(0, 1fr) 32px;
            gap: 5px;
        }

        .supply-qty-btn,
        .supply-qty-input {
            height: 32px;
            border-radius: 10px;
        }

        .supply-qty-btn {
            width: 32px;
        }

        .supply-purpose-card {
            padding: 14px;
            border-radius: 16px;
        }

        .supply-table,
        .supply-table thead,
        .supply-table tbody,
        .supply-table tr,
        .supply-table th,
        .supply-table td {
            display: block;
            width: 100%;
        }

        .supply-table thead {
            display: none;
        }

        .supply-table tbody tr {
            margin: 0;
            padding: 12px 14px;
            border-bottom: 1px solid var(--supply-line);
            background: #fff;
        }

        .supply-table tbody tr:last-child {
            border-bottom: 0;
        }

        .supply-table tbody td {
            display: grid;
            grid-template-columns: 108px minmax(0, 1fr);
            gap: 10px;
            padding: 7px 0;
            border: 0;
            font-size: 0.84rem;
        }

        .supply-table tbody td::before {
            content: attr(data-label);
            color: var(--supply-muted);
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
    }

    @media (max-width: 420px) {
        .supply-catalog-grid,
        .supply-shop-grid {
            gap: 10px;
        }

        .supply-shop-body {
            padding: 9px;
        }

        .supply-table tbody td {
            grid-template-columns: 96px minmax(0, 1fr);
        }
    }
</style>
