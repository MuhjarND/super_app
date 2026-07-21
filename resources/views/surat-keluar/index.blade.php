

<?php $__env->startSection('title', 'Surat Keluar'); ?>

<?php $__env->startPush('styles'); ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .surat-keluar-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .surat-keluar-filter-bar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
            padding: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05);
        }

        .surat-filter-btn {
            min-height: 36px;
            padding: 7px 16px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 700;
            transition: background 0.18s ease, color 0.18s ease, box-shadow 0.18s ease;
        }

        .surat-filter-btn:hover {
            color: #4f46e5;
            background: #f5f3ff;
        }

        .surat-filter-btn.active {
            color: #ffffff;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            box-shadow: 0 5px 12px rgba(79, 70, 229, 0.22);
        }

        #suratKeluarList.is-loading {
            min-height: 220px;
            opacity: 0.55;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        .surat-keluar-card .card-header {
            background: white;
            border-bottom: 1px solid #f3f4f6;
            padding: 20px 24px;
            border-radius: 16px 16px 0 0;
        }

        .surat-keluar-card .card-header h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .btn-add-surat {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border: none;
            color: white;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-add-surat:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
        }

        .recipient-type-group {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .recipient-type-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 11px 14px;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            background: #ffffff;
            color: #334155;
            font-weight: 700;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        }

        .recipient-type-option input {
            width: 16px;
            height: 16px;
            accent-color: #5b4df5;
        }

        .recipient-type-option:has(input:checked) {
            border-color: #6957f5;
            background: #f1efff;
            color: #4f46e5;
            box-shadow: 0 8px 18px rgba(99, 102, 241, 0.12);
        }

        .recipient-select-all {
            display: flex;
            align-items: center;
            gap: 8px;
            width: max-content;
            border: 1px solid #dbe3ef;
            border-radius: 999px;
            padding: 7px 12px;
            margin: 0 0 10px;
            background: #ffffff;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .recipient-select-all input {
            accent-color: #5b4df5;
        }

        #createInternalGroup > label:first-child,
        #editInternalGroup > label:first-child {
            display: block;
            margin-bottom: 8px;
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: 46px;
            height: auto !important;
            border-color: #dbe3ef;
            border-radius: 10px;
            padding: 7px 8px;
            background: #fbfcff;
            overflow: visible;
        }

        .surat-recipient-select + .select2-container--bootstrap4.select2-container--focus .select2-selection--multiple {
            border-color: #6957f5;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.12);
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-selection__choice {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            float: none;
            max-width: 100%;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            padding: 4px 10px 4px 7px;
            margin: 0;
            background: #eef2ff;
            color: #4338ca;
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1.25;
            white-space: normal;
            word-break: break-word;
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-selection__choice__remove {
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            margin: 0;
            background: #ffffff;
            color: #4f46e5;
            line-height: 17px;
            text-align: center;
            opacity: 1;
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-search--inline {
            flex: 1 0 160px;
            min-width: 160px;
        }

        .surat-recipient-select + .select2-container--bootstrap4 .select2-search__field {
            width: 100% !important;
            margin: 0;
            line-height: 28px;
        }

        #createModal .form-control:focus,
        #editModal .form-control:focus {
            border-color: #6957f5;
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.12);
        }

        #createModal select.form-control option:checked,
        #editModal select.form-control option:checked {
            background: #6957f5;
            color: #ffffff;
        }

        #createModal select.form-control option:hover,
        #editModal select.form-control option:hover {
            background: #eef2ff;
            color: #4338ca;
        }

        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected],
        .select2-container--bootstrap4 .select2-results__option--highlighted[aria-selected="true"] {
            background-color: #6957f5;
            color: #ffffff;
        }

        .select2-container--bootstrap4 .select2-results__option[aria-selected="true"] {
            background-color: #eef2ff;
            color: #4338ca;
            font-weight: 700;
        }

        /* Warning banner */
        .notice-banner {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notice-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #22c55e;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .notice-text {
            color: #166534;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .notice-text strong {
            font-weight: 700;
        }

        .surat-keluar-style {
            font-family: 'Inter', sans-serif;
            table-layout: fixed;
            width: 100% !important;
        }

        #suratKeluarTable {
            width: 100% !important;
        }

        .surat-keluar-card .card-body {
            padding: 14px 14px;
        }

        .surat-keluar-card .dataTables_wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .surat-keluar-style thead th {
            background: #f6f7f9;
            color: #8d98ad;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.45px;
            text-transform: uppercase;
            border-bottom: 1px dashed #d7dde7;
            padding: 9px 8px;
            white-space: normal;
            line-height: 1.2;
        }

        .surat-keluar-style tbody td {
            padding: 10px 8px;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.8rem;
            color: #0f172a;
            vertical-align: top;
            line-height: 1.25;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .btn-expand {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 2px solid #d1d5db;
            background: #ffffff;
            color: #9ca3af;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-top: 1px;
            padding: 0;
        }

        .btn-expand:hover,
        .btn-expand.expanded {
            border-color: #6366f1;
            background: #6366f1;
            color: #ffffff;
        }

        .nomor-surat-text {
            color: #0f172a;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.22;
            word-break: break-all;
        }

        .nomor-kode {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1.25;
        }

        .perihal-text {
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.3;
            word-break: break-word;
        }

        .recipient-pill {
            display: inline-block;
            border-radius: 6px;
            padding: 3px 9px;
            font-size: 0.68rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .recipient-pill.internal {
            background: #d9f9e7;
            color: #16a34a;
        }

        .recipient-pill.external {
            background: #e2e8f0;
            color: #475569;
        }

        .recipient-name {
            margin-top: 7px;
            display: inline-block;
            max-width: 100%;
            border-radius: 6px;
            padding: 4px 9px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .recipient-count-btn {
            border: 0;
            cursor: pointer;
            text-align: left;
        }

        .recipient-count-btn:hover,
        .recipient-count-btn:focus {
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.22);
            outline: none;
        }

        .recipient-modal-summary {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #64748b;
            font-size: 0.86rem;
        }

        .recipient-modal-list {
            display: grid;
            gap: 10px;
            max-height: 420px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .recipient-modal-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fbfcff;
        }

        .recipient-modal-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 800;
        }

        .recipient-modal-name {
            color: #0f172a;
            font-weight: 800;
            line-height: 1.25;
        }

        .recipient-modal-position {
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.3;
            margin-top: 2px;
        }

        .lampiran-badge {
            display: inline-block;
            border-radius: 8px;
            padding: 3px 10px;
            font-size: 0.68rem;
            font-weight: 700;
            line-height: 1.1;
            text-decoration: none !important;
            cursor: pointer;
        }

        .lampiran-badge.exists {
            background: #edf1f6;
            color: #0f172a;
        }

        .lampiran-badge.empty {
            background: #ffe4eb;
            color: #e11d48;
            cursor: default;
        }

        .creator-text {
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.25;
            word-break: break-word;
        }

        .status-badge {
            display: inline-block;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.68rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .status-badge.complete {
            background: #eef2ff;
            color: #4f46e5;
        }

        .status-badge.draft {
            background: #fee2e2;
            color: #b91c1c;
        }

        .detail-row td {
            background: #f3f5f8 !important;
            border-bottom: 1px solid #d8dee6 !important;
            padding: 12px 14px !important;
        }

        .detail-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .detail-meta {
            color: #8a94a3;
            font-size: 0.95rem;
        }

        .detail-meta strong {
            color: #3730a3;
            font-weight: 700;
        }

        .detail-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 6px 12px;
            line-height: 1.1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.15s ease;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .action-btn.detail {
            background: #eef2f7;
            color: #334155;
        }

        .action-btn.upload {
            background: #e8f5e9;
            color: #166534;
        }

        .action-btn.delete {
            background: #ffe9e9;
            color: #dc2626;
        }

        /* Fixed header/footer for Surat Keluar modal */
        #createModal .modal-content,
        #editModal .modal-content {
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }

        #createModal .modal-header,
        #editModal .modal-header {
            position: sticky;
            top: 0;
            z-index: 2;
            flex-shrink: 0;
        }

        #createModal #createForm,
        #editModal #editForm {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }

        #createModal .modal-body,
        #editModal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 0;
        }

        #createModal .modal-footer,
        #editModal .modal-footer {
            position: sticky;
            bottom: 0;
            z-index: 2;
            flex-shrink: 0;
            background: #ffffff;
            border-top: 1px solid #f3f4f6;
        }

        @media (max-width: 992px) {
            .surat-keluar-style thead th {
                font-size: 0.64rem;
                padding: 8px 6px;
            }

            .surat-keluar-style tbody td {
                font-size: 0.74rem;
                padding: 8px 6px;
            }
        }

        .surat-keluar-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #suratKeluarTable {
            min-width: 1120px;
        }

        @media (max-width: 767.98px) {
            .notice-banner {
                padding: 12px 14px;
                align-items: flex-start;
            }

            .surat-keluar-card {
                border-radius: 14px;
            }

            .surat-keluar-card .card-body {
                padding: 14px;
            }

            .content-header .row.mb-2 {
                gap: 12px;
            }

            .content-header .col-sm-6,
            .content-header .col-sm-6.text-right {
                flex: 0 0 100%;
                max-width: 100%;
                text-align: left !important;
            }

            .content-header h1 {
                font-size: 1.08rem;
                line-height: 1.3;
            }

            .btn-add-surat {
                width: 100%;
                justify-content: center;
            }

            .surat-keluar-filter-bar {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }

            .surat-filter-btn {
                min-width: 0;
                padding-inline: 8px;
            }
        }

    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content-header'); ?>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-paper-plane" style="font-size: 0.9rem; color: #16a34a;"></i>
                        </div>
                        Surat Keluar
                    </h1>
                </div>
                <div class="col-sm-6 text-right">
                    @include('persuratan._legacy-sync-button')
                    <?php if($canCreateSuratKeluar): ?>
                        <button class="btn btn-add-surat" data-toggle="modal" data-target="#createModal">
                            <i class="fas fa-plus mr-1"></i> Add Surat Keluar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    @if(!empty($templatePrefill))
        <div class="alert alert-info border-0 shadow-sm">
            Draft surat keluar dari template <strong>{{ $templatePrefill['template_name'] ?? 'Template Surat' }}</strong> siap dilengkapi. Form pembuatan akan dibuka otomatis dengan data awal dari template tersebut.
        </div>
    @endif

    <div class="surat-keluar-filter-bar" role="group" aria-label="Filter surat keluar">
        @foreach(['semua' => 'Semua', 'dibuat' => 'Dibuat', 'terkait' => 'Terkait'] as $filterValue => $filterLabel)
            <button type="button"
                class="surat-filter-btn {{ $activeFilter === $filterValue ? 'active' : '' }}"
                data-filter="{{ $filterValue }}">
                {{ $filterLabel }}
            </button>
        @endforeach
    </div>

    <div id="suratKeluarList">
        @include('surat-keluar._list')
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Buat Surat Keluar</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="createForm">
                    <?php echo csrf_field(); ?>
                    @if(!empty($templatePrefill))
                        <input type="hidden" name="template_source" value="{{ $templatePrefill['source'] ?? 'template_surat' }}">
                        <input type="hidden" name="template_name" value="{{ $templatePrefill['template_name'] ?? '' }}">
                        <input type="hidden" name="template_slug" value="{{ $templatePrefill['template_slug'] ?? '' }}">
                        <textarea name="template_rendered_body" hidden>{{ $templatePrefill['rendered_body'] ?? '' }}</textarea>
                        <textarea name="template_field_values" hidden>@json($templatePrefill['field_values'] ?? [])</textarea>
                    @endif
                    <div class="modal-body">
                        @if(!empty($templatePrefill))
                            <div class="alert alert-light border small">
                                Draft ini dibawa dari template surat <strong>{{ $templatePrefill['template_name'] ?? 'Template Surat' }}</strong>. Lengkapi metadata persuratan berikut sebelum disimpan.
                            </div>
                        @endif
                        <div class="form-group">
                            <label>Tahun Surat <span class="text-danger">*</span></label>
                            <select class="form-control nomor-input" name="tahun_surat" required>
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo e($y); ?>" <?php echo e($y == date('Y') ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nomenklatur Jabatan <span class="text-danger">*</span></label>
                            <select class="form-control nomor-input" name="nomenklatur_jabatan" required>
                                <option value="">-- Pilih --</option>
                                <option value="ketua">Ketua PTA (KPTA)</option>
                                <option value="sekretaris">Sekretaris (SEK)</option>
                                <option value="panitera">Panitera (PAN)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Klasifikasi <span class="text-danger">*</span></label>
                            <select class="form-control nomor-input" name="klasifikasi_kode_id" id="createKlasifikasiKode" required>
                                <option value="">-- Pilih --</option>
                                <?php $__currentLoopData = $klasifikasiKodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" data-kode="<?php echo e($k->kode); ?>"><?php echo e($k->kode); ?> -
                                        <?php echo e($k->nama); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <input type="hidden" name="kategori_surat_id" id="createKategoriSurat">

                        <div class="form-group">
                            <label>Kode Fungsi</label>
                            <select class="form-control nomor-input" name="kode_fungsi_id" id="createKodeFungsi">
                                <option value="">-- Pilih --</option>
                                <?php $__currentLoopData = $kodeFungsi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" data-kode="<?php echo e($k->kode); ?>" data-parent="<?php echo e($k->parent_id); ?>">
                                        <?php echo e($k->kode); ?> - <?php echo e($k->nama); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Kegiatan</label>
                            <select class="form-control nomor-input" name="kode_kegiatan_id" id="createKodeKegiatan">
                                <option value="">-- Pilih --</option>
                                <?php $__currentLoopData = $kodeKegiatan; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" data-kode="<?php echo e($k->kode); ?>" data-parent="<?php echo e($k->parent_id); ?>">
                                        <?php echo e($k->kode); ?> - <?php echo e($k->nama); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Transaksi</label>
                            <select class="form-control nomor-input" name="kode_transaksi_id" id="createKodeTransaksi">
                                <option value="">-- Pilih --</option>
                                <?php $__currentLoopData = $kodeTransaksi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" data-kode="<?php echo e($k->kode); ?>" data-parent="<?php echo e($k->parent_id); ?>">
                                        <?php echo e($k->kode); ?> - <?php echo e($k->nama); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Opsi Penerima <span class="text-danger">*</span></label>
                            <div class="recipient-type-group" id="createOpsiPenerima">
                                <label class="recipient-type-option">
                                    <input type="radio" name="opsi_penerima" value="internal" required>
                                    <span>Internal</span>
                                </label>
                                <label class="recipient-type-option">
                                    <input type="radio" name="opsi_penerima" value="external" required>
                                    <span>External</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" id="createInternalGroup" style="display: none;">
                            <label>Penerima Internal <span class="text-danger">*</span></label>
                            <label class="recipient-select-all">
                                <input type="checkbox" id="selectAll"> <span>Pilih Semua</span>
                            </label>
                            <select class="form-control select2 surat-recipient-select" name="penerima_internal[]" id="penerimaInternal" multiple>
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?>

                                        <?php echo e($u->jabatan ? '(' . $u->jabatan->nama . ')' : ''); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-group" id="createExternalGroup" style="display: none;">
                            <label>Penerima External <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="penerima_external"
                                placeholder="Nama penerima surat">
                        </div>

                        <div class="form-group">
                            <label>Perihal / Isi Ringkas <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="perihal" rows="3" required
                                placeholder="Perihal atau isi ringkas surat"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_surat" required
                                value="<?php echo e(date('Y-m-d')); ?>">
                        </div>

                        <input type="hidden" name="has_lampiran" value="tidak">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Surat Keluar</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="editForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" id="editSuratId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tahun Surat <span class="text-danger">*</span></label>
                            <select class="form-control" name="tahun_surat" id="editTahunSurat" required>
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nomenklatur Jabatan <span class="text-danger">*</span></label>
                            <select class="form-control" name="nomenklatur_jabatan" id="editNomenklaturJabatan" required>
                                <option value="">-- Pilih --</option>
                                <option value="ketua">Ketua PTA (KPTA)</option>
                                <option value="sekretaris">Sekretaris (SEK)</option>
                                <option value="panitera">Panitera (PAN)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Klasifikasi <span class="text-danger">*</span></label>
                            <select class="form-control" name="klasifikasi_kode_id" id="editKlasifikasiKode" required>
                                <option value="">-- Pilih --</option>
                                <?php $__currentLoopData = $klasifikasiKodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k->id); ?>" data-kode="<?php echo e($k->kode); ?>"><?php echo e($k->kode); ?> - <?php echo e($k->nama); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <input type="hidden" name="kategori_surat_id" id="editKategoriSurat">

                        <div class="form-group">
                            <label>Kode Fungsi</label>
                            <select class="form-control" name="kode_fungsi_id" id="editKodeFungsi">
                                <option value="">-- Pilih --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Kegiatan</label>
                            <select class="form-control" name="kode_kegiatan_id" id="editKodeKegiatan">
                                <option value="">-- Pilih --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kode Transaksi</label>
                            <select class="form-control" name="kode_transaksi_id" id="editKodeTransaksi">
                                <option value="">-- Pilih --</option>
                            </select>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Opsi Penerima <span class="text-danger">*</span></label>
                            <div class="recipient-type-group" id="editOpsiPenerima">
                                <label class="recipient-type-option">
                                    <input type="radio" name="opsi_penerima" value="internal" required>
                                    <span>Internal</span>
                                </label>
                                <label class="recipient-type-option">
                                    <input type="radio" name="opsi_penerima" value="external" required>
                                    <span>External</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" id="editInternalGroup" style="display: none;">
                            <label>Penerima Internal <span class="text-danger">*</span></label>
                            <label class="recipient-select-all">
                                <input type="checkbox" id="selectAllEdit"> <span>Pilih Semua</span>
                            </label>
                            <select class="form-control select2 surat-recipient-select" name="penerima_internal[]" id="editPenerimaInternal" multiple>
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?><?php echo e($u->jabatan ? ' (' . $u->jabatan->nama . ')' : ''); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="form-group" id="editExternalGroup" style="display: none;">
                            <label>Penerima External <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="penerima_external" id="editPenerimaExternal"
                                placeholder="Nama penerima surat">
                        </div>

                        <div class="form-group">
                            <label>Perihal / Isi Ringkas <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="perihal" id="editPerihal" rows="3" required
                                placeholder="Perihal atau isi ringkas surat"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_surat" id="editTanggalSurat" required>
                        </div>

                        <input type="hidden" name="has_lampiran" id="editHasLampiran" value="tidak">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnEditSubmit">
                            <i class="fas fa-save mr-1"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload mr-2"></i>Upload Berkas Surat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <input type="hidden" name="surat_id" id="uploadSuratId">
                        <div class="form-group">
                            <label>Berkas Surat <span class="text-danger">*</span></label>
                            <input type="file" class="form-control-file" name="file" required
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.webp,.txt,.csv,.zip,.rar">
                            <small class="text-muted d-block mt-1">
                                Dokumen, gambar, spreadsheet, presentasi, teks, atau arsip ZIP/RAR (maks. 10 MB).
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnUpload">
                            <i class="fas fa-upload mr-1"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View File Modal -->
    <div class="modal fade" id="viewFileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file mr-2"></i>Lihat Berkas</h5>
                    <div class="d-flex align-items-center">
                        <a href="#" id="openSuratKeluarFile" target="_blank" rel="noopener"
                            class="btn btn-sm btn-outline-primary mr-2">
                            <i class="fas fa-external-link-alt mr-1"></i>Buka / Unduh
                        </a>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                </div>
                <div class="modal-body text-center">
                    <iframe id="fileViewer"
                        style="width: 100%; height: 500px; border: 1px solid #e8eaed; border-radius: 8px;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recipientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Penerima Internal</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="recipient-modal-summary">
                        <span class="recipient-pill internal">Internal</span>
                        <span id="recipientModalMeta">-</span>
                    </div>
                    <div class="recipient-modal-list" id="recipientModalList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            const canCreateSuratKeluar = <?php echo json_encode($canCreateSuratKeluar, 15, 512) ?>;
            let currentSearch = @json($search);
            let searchTimer = null;
            let recipientMap = <?php echo json_encode($suratKeluar->getCollection()->mapWithKeys(function ($surat) {
                return [
                    $surat->id => $surat->penerimaInternal->map(function ($user) {
                        return [
                            'name' => $user->name,
                            'jabatan' => optional($user->jabatan)->nama ?: ($user->jabatan_keterangan ?: '-'),
                        ];
                    })->values(),
                ];
            })->all(), 15, 512) ?>;

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, function (char) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    }[char];
                });
            }

            function initialsFromName(name) {
                const words = String(name || '').trim().split(/\s+/).filter(Boolean);
                if (!words.length) {
                    return '-';
                }

                return words.slice(0, 2).map(function (word) {
                    return word.charAt(0).toUpperCase();
                }).join('');
            }

            function initializeSuratKeluarTable() {
                const instance = $('#suratKeluarTable').DataTable({
                    order: [],
                    paging: false,
                    info: false,
                    lengthChange: false,
                    autoWidth: false,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries found",
                        emptyTable: '<div class="text-center py-4"><i class="fas fa-paper-plane fa-3x mb-3 d-block" style="opacity:0.2;color:#9ca3af;"></i><span style="color: #9ca3af;">Tidak ada surat keluar</span></div>',
                        paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
                    },
                    columnDefs: [
                        { orderable: false, targets: [0] }
                    ]
                });

                $('#suratKeluarTable_filter input')
                    .val(currentSearch)
                    .off('.DT')
                    .off('.suratAjax')
                    .on('input.suratAjax', function () {
                        currentSearch = $(this).val();
                        clearTimeout(searchTimer);
                        searchTimer = setTimeout(function () {
                            loadSuratKeluarList(@json(route('surat-keluar.index')), null, currentSearch);
                        }, 350);
                    });

                return instance;
            }

            let table = initializeSuratKeluarTable();
            let listRequest = null;

            function loadSuratKeluarList(url, filter, search) {
                const list = $('#suratKeluarList');
                filter = filter || $('.surat-filter-btn.active').data('filter') || 'semua';
                currentSearch = typeof search === 'string' ? search : currentSearch;
                const requestUrl = new URL(url, window.location.origin);

                requestUrl.searchParams.set('filter', filter);
                if (currentSearch.trim() === '') {
                    requestUrl.searchParams.delete('search');
                } else {
                    requestUrl.searchParams.set('search', currentSearch.trim());
                }

                if (listRequest) {
                    listRequest.abort();
                }

                list.addClass('is-loading');
                $('.surat-filter-btn').prop('disabled', true);

                listRequest = $.ajax({
                    url: requestUrl.toString(),
                    method: 'GET',
                    success: function (response) {
                        if (table) {
                            table.destroy();
                        }

                        list.html(response.html);
                        recipientMap = response.recipient_map || {};
                        currentSearch = response.search || '';
                        table = initializeSuratKeluarTable();

                        $('.surat-filter-btn')
                            .removeClass('active')
                            .filter('[data-filter="' + response.filter + '"]')
                            .addClass('active');

                        requestUrl.searchParams.set('filter', response.filter);
                        window.history.replaceState({}, '', requestUrl.pathname + requestUrl.search);
                    },
                    error: function (xhr, status) {
                        if (status === 'abort') {
                            return;
                        }
                        showToast(xhr.responseJSON?.message || 'Daftar surat keluar gagal dimuat.', 'error');
                    },
                    complete: function () {
                        listRequest = null;
                        list.removeClass('is-loading');
                        $('.surat-filter-btn').prop('disabled', false);
                    }
                });
            }

            $(document).on('click', '.surat-filter-btn', function () {
                const filter = $(this).data('filter');
                loadSuratKeluarList('<?php echo e(route('surat-keluar.index')); ?>', filter);
            });

            $(document).on('click', '#suratKeluarList .pagination a', function (event) {
                event.preventDefault();
                loadSuratKeluarList($(this).attr('href'));
            });

            function formatDetailRow(data) {
                const canManageRow = Number(data.canManage) === 1;
                let actions = '';
                if (data.fileUrl) {
                    actions += '<button type="button" class="action-btn detail" onclick="viewFile(\'' + data.fileUrl + '\')"><i class="fas fa-eye"></i> Preview</button>';
                }
                if (canManageRow) {
                    actions += '<button type="button" class="action-btn detail" onclick="openEdit(' + data.suratId + ')"><i class="fas fa-edit"></i> Edit</button>';
                    actions += '<button type="button" class="action-btn upload" onclick="openUpload(' + data.suratId + ')"><i class="fas fa-upload"></i> Upload</button>';
                    actions += '<button type="button" class="action-btn delete" onclick="deleteSurat(' + data.suratId + ', \'' + data.deleteUrl + '\')"><i class="fas fa-trash"></i> Hapus</button>';
                }

                return '<div class="detail-content">' +
                    '<div class="detail-meta">Dibuat Oleh: <strong>' + data.creator + '</strong></div>' +
                    '<div class="detail-actions">' + actions + '</div>' +
                    '</div>';
            }

            $(document).on('click', '#suratKeluarTable tbody .dt-expand', function () {
                const tr = $(this).closest('tr');
                const btn = $(this);
                const icon = btn.find('i');
                const row = table.row(tr);

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    btn.removeClass('expanded');
                    icon.removeClass('fa-minus').addClass('fa-plus');
                } else {
                    row.child(formatDetailRow(tr.data()), 'detail-row').show();
                    tr.addClass('shown');
                    btn.addClass('expanded');
                    icon.removeClass('fa-plus').addClass('fa-minus');
                }
            });

            $(document).on('click', '#suratKeluarTable tbody .js-recipient-modal', function () {
                const suratId = $(this).data('suratId');
                const nomorSurat = $(this).data('nomorSurat') || '-';
                const recipients = recipientMap[suratId] || [];
                const listHtml = recipients.length
                    ? recipients.map(function (recipient) {
                        return '<div class="recipient-modal-item">' +
                            '<div class="recipient-modal-avatar">' + escapeHtml(initialsFromName(recipient.name)) + '</div>' +
                            '<div>' +
                                '<div class="recipient-modal-name">' + escapeHtml(recipient.name || '-') + '</div>' +
                                '<div class="recipient-modal-position">' + escapeHtml(recipient.jabatan || '-') + '</div>' +
                            '</div>' +
                        '</div>';
                    }).join('')
                    : '<div class="text-muted text-center py-3">Belum ada penerima internal.</div>';

                $('#recipientModalMeta').text(recipients.length + ' orang ditag pada ' + nomorSurat);
                $('#recipientModalList').html(listHtml);
                $('#recipientModal').modal('show');
            });

            const fungsiOptions = <?php echo json_encode($kodeFungsiOptions, 15, 512) ?>;
            const kegiatanOptions = <?php echo json_encode($kodeKegiatanOptions, 15, 512) ?>;
            const transaksiOptions = <?php echo json_encode($kodeTransaksiOptions, 15, 512) ?>;
            const klasifikasiMap = <?php echo json_encode($klasifikasiKodes->map(function($item){ return ['id' => $item->id, 'kode' => strtoupper($item->kode)]; })->values(), 15, 512) ?>;
            const kategoriMap = <?php echo json_encode($kategoriSurats->map(function($item){ return ['id' => $item->id, 'kode' => strtoupper($item->kode)]; })->values(), 15, 512) ?>;
            const suratTemplatePrefill = <?php echo json_encode($templatePrefill ?? null, 15, 512) ?>;
            const klasifikasiByKode = {};
            const kategoriByKode = {};

            klasifikasiMap.forEach(function (item) {
                klasifikasiByKode[item.kode] = item.id;
            });

            kategoriMap.forEach(function (item) {
                kategoriByKode[item.kode] = item.id;
            });

            function rebuildKodeOptions($select, options, placeholder, emptyText, selectedValue) {
                let html = '<option value="">' + (options.length ? placeholder : (emptyText || placeholder)) + '</option>';
                options.forEach(function (item) {
                    const selected = String(selectedValue || '') === String(item.id) ? ' selected' : '';
                    html += '<option value="' + item.id + '" data-kode="' + item.kode + '" data-parent="' + item.parent_id + '"' + selected + '>' +
                        item.kode + ' - ' + item.nama +
                        '</option>';
                });
                $select.html(html);
                $select.prop('disabled', options.length === 0);
                $select.val(selectedValue || '');
            }

            function syncKategoriFromKlasifikasi($klasifikasiSelect, $kategoriSelect) {
                const selected = klasifikasiMap.find(function (item) {
                    return String(item.id) === String($klasifikasiSelect.val());
                });
                $kategoriSelect.val(selected ? (kategoriByKode[selected.kode] || '') : '');
            }

            function syncKlasifikasiFromKategori($kategoriSelect, $klasifikasiSelect) {
                const selected = kategoriMap.find(function (item) {
                    return String(item.id) === String($kategoriSelect.val());
                });
                $klasifikasiSelect.val(selected ? (klasifikasiByKode[selected.kode] || '') : '');
            }

            function applyKodeHierarchy(prefix, selectedValues) {
                const $klasifikasi = $('#' + prefix + 'KlasifikasiKode');
                const $fungsi = $('#' + prefix + 'KodeFungsi');
                const $kegiatan = $('#' + prefix + 'KodeKegiatan');
                const $transaksi = $('#' + prefix + 'KodeTransaksi');
                const values = selectedValues || {};
                const klasifikasiId = values.klasifikasi || $klasifikasi.val();
                const fungsiId = values.fungsi || '';
                const kegiatanId = values.kegiatan || '';
                const transaksiId = values.transaksi || '';

                if (!klasifikasiId) {
                    rebuildKodeOptions($fungsi, [], '-- Pilih --', '-- Pilih kode klasifikasi dulu --', '');
                    rebuildKodeOptions($kegiatan, [], '-- Pilih --', '-- Pilih kode fungsi dulu --', '');
                    rebuildKodeOptions($transaksi, [], '-- Pilih --', '-- Pilih kode kegiatan dulu --', '');
                    return;
                }

                const filteredFungsi = fungsiOptions.filter(function (item) {
                    return String(item.parent_id) === String(klasifikasiId);
                });
                rebuildKodeOptions($fungsi, filteredFungsi, '-- Pilih --', '-- Tidak ada kode fungsi --', fungsiId);

                if (!fungsiId) {
                    rebuildKodeOptions($kegiatan, [], '-- Pilih --', '-- Pilih kode fungsi dulu --', '');
                    rebuildKodeOptions($transaksi, [], '-- Pilih --', '-- Pilih kode kegiatan dulu --', '');
                    return;
                }

                const filteredKegiatan = kegiatanOptions.filter(function (item) {
                    return String(item.parent_id) === String(fungsiId);
                });
                rebuildKodeOptions($kegiatan, filteredKegiatan, '-- Pilih --', '-- Tidak ada kode kegiatan --', kegiatanId);

                if (!kegiatanId) {
                    rebuildKodeOptions($transaksi, [], '-- Pilih --', '-- Pilih kode kegiatan dulu --', '');
                    return;
                }

                const filteredTransaksi = transaksiOptions.filter(function (item) {
                    return String(item.parent_id) === String(kegiatanId);
                });
                rebuildKodeOptions($transaksi, filteredTransaksi, '-- Pilih --', '-- Tidak ada kode transaksi --', transaksiId);
            }

            function initInternalSelect(selectId, modalId) {
                const $internalSelect = $('#' + selectId);
                if ($internalSelect.hasClass('select2-hidden-accessible')) {
                    $internalSelect.select2('destroy');
                }
                $internalSelect.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $('#' + modalId)
                });
            }

            function togglePenerimaFields(prefix) {
                const formSelector = prefix === 'create' ? '#createForm' : '#editForm';
                const opsi = $('input[name="opsi_penerima"]:checked', formSelector).val();
                const $internalGroup = $('#' + prefix + 'InternalGroup');
                const $externalGroup = $('#' + prefix + 'ExternalGroup');

                if (opsi === 'internal') {
                    initInternalSelect(prefix === 'create' ? 'penerimaInternal' : 'editPenerimaInternal', prefix === 'create' ? 'createModal' : 'editModal');
                    $internalGroup.slideDown();
                    $externalGroup.slideUp();
                } else if (opsi === 'external') {
                    $externalGroup.slideDown();
                    $internalGroup.slideUp();
                } else {
                    $internalGroup.hide();
                    $externalGroup.hide();
                }
            }

            function bindKategoriSync(prefix) {
                const $klasifikasi = $('#' + prefix + 'KlasifikasiKode');
                const $kategori = $('#' + prefix + 'KategoriSurat');

                $klasifikasi.on('change', function () {
                    syncKategoriFromKlasifikasi($klasifikasi, $kategori);
                    applyKodeHierarchy(prefix, {});
                });

                $kategori.on('change', function () {
                    syncKlasifikasiFromKategori($kategori, $klasifikasi);
                    applyKodeHierarchy(prefix, {});
                });

                $('#' + prefix + 'KodeFungsi').on('change', function () {
                    applyKodeHierarchy(prefix, {
                        klasifikasi: $klasifikasi.val(),
                        fungsi: $('#' + prefix + 'KodeFungsi').val()
                    });
                });

                $('#' + prefix + 'KodeKegiatan').on('change', function () {
                    applyKodeHierarchy(prefix, {
                        klasifikasi: $klasifikasi.val(),
                        fungsi: $('#' + prefix + 'KodeFungsi').val(),
                        kegiatan: $('#' + prefix + 'KodeKegiatan').val()
                    });
                });
            }

            bindKategoriSync('create');
            bindKategoriSync('edit');
            applyKodeHierarchy('create', {});
            applyKodeHierarchy('edit', {});

            $('input[name="opsi_penerima"]', '#createForm, #editForm').on('change', function () {
                togglePenerimaFields($(this).closest('form').attr('id') === 'createForm' ? 'create' : 'edit');
            });

            $('#selectAll').on('change', function () {
                $('#penerimaInternal option').prop('selected', $(this).is(':checked'));
                $('#penerimaInternal').trigger('change');
            });

            $('#selectAllEdit').on('change', function () {
                $('#editPenerimaInternal option').prop('selected', $(this).is(':checked'));
                $('#editPenerimaInternal').trigger('change');
            });

            $('#createForm').on('submit', function (e) {
                e.preventDefault();
                if (!canCreateSuratKeluar) {
                    showToast('Anda tidak memiliki akses untuk membuat surat keluar.', 'warning');
                    return;
                }
                let btn = $('#btnSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

                $.ajax({
                    url: '<?php echo e(route("surat-keluar.store")); ?>',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#createModal').modal('hide');
                        $('#createForm')[0].reset();
                        $('input[name="opsi_penerima"]', '#createForm').prop('checked', false);
                        $('#selectAll').prop('checked', false);
                        $('#createInternalGroup, #createExternalGroup').hide();
                        $('#penerimaInternal').val(null).trigger('change');
                        applyKodeHierarchy('create', {});
                        location.reload();
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let msg = 'Terjadi kesalahan.';
                        if (errors) {
                            msg = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
                    }
                });
            });

            $('#editForm').on('submit', function (e) {
                e.preventDefault();
                const suratId = $('#editSuratId').val();
                const row = $('tr[data-surat-id="' + suratId + '"]');
                if (Number(row.data('canManage')) !== 1) {
                    showToast('Anda tidak memiliki akses untuk mengubah surat keluar.', 'warning');
                    return;
                }
                let btn = $('#btnEditSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memperbarui...');

                $.ajax({
                    url: '/surat-keluar/' + suratId,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#editModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let msg = 'Terjadi kesalahan.';
                        if (errors) {
                            msg = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui');
                    }
                });
            });

            window.openEdit = function (suratId) {
                const row = $('tr[data-surat-id="' + suratId + '"]');
                if (Number(row.data('canManage')) !== 1) {
                    showToast('Anda tidak memiliki akses untuk mengubah surat keluar.', 'warning');
                    return;
                }

                const data = row.data();
                const penerimaInternal = String(data.penerimaInternal || '')
                    .split(',')
                    .map(function (item) { return item.trim(); })
                    .filter(Boolean);

                $('#editSuratId').val(suratId);
                $('#editTahunSurat').val(data.tahunSurat);
                $('#editNomenklaturJabatan').val(data.nomenklaturJabatan);
                $('#editKlasifikasiKode').val(data.klasifikasiKode);
                $('#editKategoriSurat').val(data.kategoriSurat);
                $('#editPerihal').val(data.perihal);
                $('#editTanggalSurat').val(data.tanggalSurat);
                $('#editHasLampiran').val('tidak');
                $('input[name="opsi_penerima"]', '#editForm').prop('checked', false);
                $('input[name="opsi_penerima"][value="' + data.opsiPenerima + '"]', '#editForm').prop('checked', true);
                $('#editPenerimaExternal').val(data.penerimaExternal || '');

                if (!data.klasifikasiKode && data.kategoriSurat) {
                    syncKlasifikasiFromKategori($('#editKategoriSurat'), $('#editKlasifikasiKode'));
                }

                applyKodeHierarchy('edit', {
                    klasifikasi: $('#editKlasifikasiKode').val(),
                    fungsi: data.kodeFungsi,
                    kegiatan: data.kodeKegiatan,
                    transaksi: data.kodeTransaksi
                });

                $('#editPenerimaInternal').val(penerimaInternal).trigger('change');
                togglePenerimaFields('edit');
                $('#editModal').modal('show');
            };

            // Upload form
            $('#uploadForm').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let suratId = $('#uploadSuratId').val();
                let btn = $('#btnUpload');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...');

                $.ajax({
                    url: '/surat-keluar/' + suratId + '/upload',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#uploadModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr) {
                        let msg = xhr.responseJSON?.message || 'Gagal upload file.';
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Upload');
                    }
                });
            });

            // Re-init select2 in modal
            $('#createModal').on('shown.bs.modal', function () {
                initInternalSelect('penerimaInternal', 'createModal');
                applyKodeHierarchy('create', {});
            });

            if (suratTemplatePrefill && canCreateSuratKeluar) {
                $('input[name="tahun_surat"]', '#createForm').val(suratTemplatePrefill.tahun_surat || new Date().getFullYear());
                $('textarea[name="perihal"]', '#createForm').val(suratTemplatePrefill.perihal || suratTemplatePrefill.template_name || 'Template Surat');
                $('input[name="tanggal_surat"]', '#createForm').val(suratTemplatePrefill.tanggal_surat || '');
                $('input[name="has_lampiran"]', '#createForm').val('tidak');
                $('select[name="nomenklatur_jabatan"]', '#createForm').val(suratTemplatePrefill.nomenklatur_jabatan || 'sekretaris');
                $('input[name="opsi_penerima"][value="' + (suratTemplatePrefill.opsi_penerima || 'internal') + '"]', '#createForm').prop('checked', true).trigger('change');

                if (suratTemplatePrefill.klasifikasi_kode_id) {
                    $('#createKlasifikasiKode').val(String(suratTemplatePrefill.klasifikasi_kode_id));
                    applyKodeHierarchy('create', {
                        klasifikasi: suratTemplatePrefill.klasifikasi_kode_id,
                        fungsi: suratTemplatePrefill.kode_fungsi_id || '',
                        kegiatan: suratTemplatePrefill.kode_kegiatan_id || '',
                        transaksi: ''
                    });
                }

                if (suratTemplatePrefill.kategori_surat_id) {
                    $('#createKategoriSurat').val(String(suratTemplatePrefill.kategori_surat_id));
                }

                setTimeout(function () {
                    $('#createModal').modal('show');
                }, 250);
            }

            $('#editModal').on('shown.bs.modal', function () {
                initInternalSelect('editPenerimaInternal', 'editModal');
            });

            $('#viewFileModal').on('hidden.bs.modal', function () {
                $('#fileViewer').attr('src', 'about:blank');
                $('#openSuratKeluarFile').attr('href', '#');
            });
        });

        function openUpload(suratId) {
            const row = $('tr[data-surat-id="' + suratId + '"]');
            if (Number(row.data('canManage')) !== 1) {
                showToast('Anda tidak memiliki akses untuk mengupload lampiran.', 'warning');
                return;
            }
            $('#uploadSuratId').val(suratId);
            $('#uploadModal').modal('show');
        }

        function viewFile(url) {
            if (!url) {
                showToast('Berkas belum tersedia.', 'warning');
                return;
            }
            $('#fileViewer').attr('src', url);
            $('#openSuratKeluarFile').attr('href', url);
            $('#viewFileModal').modal('show');
        }

        function deleteSurat(id, url) {
            const row = $('tr[data-surat-id="' + id + '"]');
            if (Number(row.data('canManage')) !== 1) {
                showToast('Anda tidak memiliki akses untuk menghapus surat.', 'warning');
                return;
            }
            if (!confirm('Apakah Anda yakin ingin menghapus surat ini?')) return;
            $.ajax({
                url: url,
                method: 'DELETE',
                data: { _token: '<?php echo e(csrf_token()); ?>' },
                success: function (res) {
                    showToast(res.message, 'success');
                    location.reload();
                },
                error: function () {
                    showToast('Gagal menghapus surat.', 'error');
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\super3\resources\views\surat-keluar\index.blade.php ENDPATH**/ ?>
