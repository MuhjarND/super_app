@extends('layouts.app')

@section('title', 'Rapat')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .rapat-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .rapat-card .card-body {
            padding: 16px;
        }

        .rapat-card .table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
        }

        .rapat-card .table tbody td {
            vertical-align: top;
            font-size: 0.86rem;
            color: #0f172a;
        }

        .rapat-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .rapat-meta {
            color: #64748b;
            font-size: 0.76rem;
        }

        .rapat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 600;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .rapat-chip.virtual {
            background: #ede9fe;
            color: #6d28d9;
        }

        .rapat-chip.pakaian {
            background: #fff7ed;
            color: #c2410c;
        }

        .rapat-chip.recurring {
            background: #ecfeff;
            color: #0f766e;
        }

        .form-hint {
            font-size: 0.74rem;
            color: #64748b;
        }

        .rapat-form-modal .rapat-modal-dialog {
            margin-top: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .rapat-form-modal .modal-content {
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 2.5rem);
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.22);
        }

        .rapat-form-modal .modal-header,
        .rapat-form-modal .modal-footer {
            flex: 0 0 auto;
        }

        .rapat-form-modal .modal-header,
        .rapat-form-modal .modal-footer {
            border-color: #e5eaf3;
            background: #fff;
            padding: 16px 20px;
        }

        .rapat-form-modal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            max-height: calc(100vh - 12rem);
            padding: 18px 20px;
            background: #fbfcff;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        .rapat-form-modal .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .rapat-form-modal .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        .rapat-form-modal .modal-body::-webkit-scrollbar-thumb {
            background: #c4b5fd;
            border-radius: 999px;
        }

        .rapat-form-modal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a78bfa;
        }

        .rapat-form-modal .modal-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }

        .rapat-modal-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.35;
        }

        .rapat-form-section {
            padding: 16px;
            border: 1px solid #e5eaf3;
            border-radius: 18px;
            background: #fff;
            margin-bottom: 14px;
        }

        .rapat-form-section-title {
            margin-bottom: 12px;
            color: #111827;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .rapat-form-modal label {
            color: #1f2937;
            font-size: 0.78rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .rapat-form-modal .form-control {
            min-height: 42px;
            border-color: #dbe4f0;
            border-radius: 13px;
            color: #111827;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: none;
        }

        .rapat-form-modal textarea.form-control {
            min-height: auto;
            line-height: 1.45;
            font-weight: 500;
        }

        .rapat-form-modal .form-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.13);
        }

        .rapat-form-modal .select2-container {
            width: 100% !important;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection {
            min-height: 42px;
            border-color: #dbe4f0;
            border-radius: 13px;
            box-shadow: none;
        }

        .rapat-participant-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .rapat-participant-toolbar label {
            margin-bottom: 0;
        }

        .rapat-participant-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .rapat-select-all-participants,
        .rapat-select-unit-trigger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1;
            padding: 8px 12px;
            white-space: nowrap;
        }

        .rapat-select-all-participants:hover,
        .rapat-select-all-participants:focus,
        .rapat-select-unit-trigger:hover,
        .rapat-select-unit-trigger:focus {
            background: #e0e7ff;
            color: #4338ca;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        .rapat-unit-menu {
            min-width: 250px;
            padding: 6px;
            border: 1px solid #dbe4f0;
            border-radius: 13px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, .12);
        }

        .rapat-select-unit-participants {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-height: 40px;
            padding: 8px 10px;
            border-radius: 9px;
            color: #334155;
            font-size: .78rem;
            font-weight: 700;
        }

        .rapat-select-unit-participants.is-unit-selected {
            background: #eef2ff;
            color: #4338ca;
        }

        .rapat-unit-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 22px;
            padding: 0 7px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: .68rem;
        }

        .rapat-select-unit-participants.is-unit-selected .rapat-unit-count {
            background: #fff;
            color: #4338ca;
        }

        .rapat-select-all-participants.is-all-selected {
            border-color: #fecaca;
            background: #fff1f2;
            color: #dc2626;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple {
            max-height: 156px;
            overflow-y: auto;
            padding: 7px 9px;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 7px;
            width: 100%;
            min-width: 0;
            margin: 0;
            padding: 0;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            position: relative;
            display: inline-flex;
            align-items: center;
            border: 0;
            border-radius: 999px;
            background: #ede9fe;
            color: #5b21b6;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.25;
            max-width: 100%;
            margin: 0;
            padding: 4px 10px 4px 28px;
            white-space: normal;
            word-break: break-word;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.95rem;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-search--inline {
            flex: 1 1 160px;
            min-width: 160px;
        }

        .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-search__field {
            width: 100% !important;
            min-width: 140px;
            height: 26px;
            margin: 0;
            font-weight: 600;
        }

        .rapat-advanced {
            border: 1px solid #e5eaf3;
            border-radius: 18px;
            background: #fff;
        }

        .rapat-advanced summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            list-style: none;
            color: #111827;
            font-size: 0.86rem;
            font-weight: 800;
        }

        .rapat-advanced summary::-webkit-details-marker {
            display: none;
        }

        .rapat-advanced summary::after {
            content: '+';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #ede9fe;
            color: #5b21b6;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .rapat-advanced[open] summary::after {
            content: '-';
            background: #f1f5f9;
            color: #475569;
        }

        .rapat-advanced summary small {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 600;
        }

        .rapat-advanced-body {
            padding: 0 16px 16px;
        }

        .rapat-option-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin: 2px 0 14px;
        }

        .rapat-option-card {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            min-height: 82px;
            padding: 11px;
            border: 1px solid #e5eaf3;
            border-radius: 16px;
            background: #fff;
            cursor: pointer;
            margin: 0;
            transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
        }

        .rapat-option-card:hover {
            border-color: #c4b5fd;
            background: #fbfaff;
            transform: translateY(-1px);
        }

        .rapat-option-card input {
            width: 16px;
            height: 16px;
            margin-top: 2px;
            accent-color: #6d4aff;
            flex: 0 0 auto;
        }

        .rapat-option-card strong {
            display: block;
            color: #111827;
            font-size: 0.8rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .rapat-option-card small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.7rem;
            line-height: 1.25;
            font-weight: 600;
        }

        .rapat-option-card:has(input:checked) {
            border-color: #a78bfa;
            background: #f5f3ff;
        }

        .rapat-conditional-field {
            padding: 14px;
            border: 1px solid #e5eaf3;
            border-radius: 16px;
            background: #f8fafc;
            margin-top: 10px;
        }

        .rapat-form-modal .modal-footer .btn {
            min-height: 38px;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 800;
            padding: 8px 16px;
        }

        .rapat-form-modal .modal-footer .app-create-btn {
            border-color: #6d4aff;
            background: linear-gradient(135deg, #6d4aff, #8b5cf6);
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .rapat-option-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .rapat-form-modal .modal-dialog {
                margin: 10px;
            }

            .rapat-form-modal .modal-content {
                max-height: calc(100dvh - 20px);
            }

            .rapat-form-modal .modal-body {
                max-height: calc(100dvh - 150px);
            }

            .rapat-form-modal .modal-header,
            .rapat-form-modal .modal-footer,
            .rapat-form-modal .modal-body {
                padding: 14px;
            }

            .rapat-form-section {
                padding: 14px;
                border-radius: 16px;
            }

            .rapat-participant-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .rapat-select-all-participants {
                width: 100%;
            }

            .rapat-participant-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
            }

            .rapat-participant-actions .dropdown,
            .rapat-select-unit-trigger {
                width: 100%;
            }

            .rapat-unit-menu {
                width: min(300px, calc(100vw - 48px));
            }

            .rapat-form-modal .select2-container--bootstrap4 .select2-selection--multiple {
                max-height: 190px;
            }

            .rapat-advanced summary {
                align-items: flex-start;
            }

            .rapat-option-grid {
                grid-template-columns: 1fr;
            }
        }

        .row-toggle-col {
            width: 46px;
        }

        .row-toggle-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.18);
        }

        .row-toggle-btn.is-open {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: none;
        }

        .rapat-action-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .rapat-action-meta {
            color: #64748b;
            font-size: 0.82rem;
            margin-right: 10px;
        }

        .action-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid transparent;
            background: #fff;
            color: #1f2937;
        }

        .action-chip-btn.action-lampiran {
            background: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }

        .action-chip-btn.action-edit {
            background: #eef2ff;
            color: #4338ca;
            border-color: #c7d2fe;
        }

        .action-chip-btn.action-delete {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .status-trigger-btn {
            border: none;
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .status-trigger-btn .badge {
            cursor: pointer;
        }

        .status-trigger-btn small {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            margin-top: 4px;
        }

        .status-modal-section {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            background: #f8fafc;
            margin-bottom: 14px;
        }

        .status-modal-title {
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #334155;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .status-step {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-step:last-child {
            border-bottom: none;
        }

        .status-step-main {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }

        .status-step-sub {
            font-size: 0.77rem;
            color: #64748b;
            margin-top: 2px;
        }

        .status-history-item {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-history-item:last-child {
            border-bottom: none;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Rapat</h1>
                    <div class="text-muted" style="font-size: 0.82rem;">Jadwal rapat, undangan, peserta, dan lampiran tambahan.</div>
                </div>
                @if(auth()->user()->canManageRapat())
                    <button class="btn app-create-btn" data-toggle="modal" data-target="#createRapatModal">
                        <i class="fas fa-plus mr-1"></i> Tambah Rapat
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card rapat-card">
        <div class="card-body">
            <table id="rapatTable" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="row-toggle-col"></th>
                        <th>Nomor / Judul</th>
                        <th>Kategori Surat</th>
                        <th>Waktu WIT</th>
                        <th>Tempat</th>
                        <th>Peserta</th>
                        <th>Approver</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapats as $rapat)
                        <tr
                            data-rapat-id="{{ $rapat->id }}"
                            data-update-url="{{ route('rapat.update', $rapat) }}"
                            data-delete-url="{{ route('rapat.destroy', $rapat) }}"
                            data-lampiran-url="{{ $rapat->lampiran_tambahan_path ? route('rapat.lampiran', $rapat) : '' }}"
                            data-undangan-url="{{ route('rapat.undangan.preview', $rapat) }}"
                            data-nomor-undangan="{{ $rapat->nomor_undangan }}"
                            data-judul="{{ $rapat->judul }}"
                            data-deskripsi="{{ $rapat->deskripsi }}"
                            data-kategori-surat-kode="{{ $rapat->kategori_surat_kode_id }}"
                            data-nomenklatur-jabatan="{{ $rapat->nomenklatur_jabatan }}"
                            data-tanggal="{{ optional($rapat->tanggal)->format('Y-m-d') }}"
                            data-waktu-mulai="{{ $rapat->waktu_mulai_formatted }}"
                            data-tempat="{{ $rapat->tempat }}"
                            data-peserta-ids="{{ $rapat->pesertas->pluck('id')->implode(',') }}"
                            data-approver-1="{{ $rapat->approver_1_id }}"
                            data-approver-2="{{ $rapat->approver_2_id }}"
                            data-approval1-jabatan-manual="{{ $rapat->approval1_jabatan_manual }}"
                            data-detail-tambahan="{{ $rapat->detail_tambahan }}"
                            data-include-detail-tambahan="{{ $rapat->detail_tambahan ? 1 : 0 }}"
                            data-tujuan-surat="{{ $rapat->tujuan_surat }}"
                            data-bersama-satker="{{ $rapat->bersama_satker ? 1 : 0 }}"
                            data-jenis-pakaian="{{ $rapat->jenis_pakaian }}"
                            data-include-pakaian="{{ $rapat->jenis_pakaian ? 1 : 0 }}"
                            data-is-virtual="{{ $rapat->is_virtual ? 1 : 0 }}"
                            data-meeting-id="{{ $rapat->meeting_id }}"
                            data-meeting-passcode="{{ $rapat->meeting_passcode }}"
                            data-status="{{ $rapat->status }}"
                            data-is-recurring="{{ $rapat->is_recurring ? 1 : 0 }}"
                            data-recurring-pattern="{{ $rapat->recurring_pattern }}"
                            data-recurring-until="{{ optional($rapat->recurring_until)->format('Y-m-d') }}"
                            data-has-lampiran="{{ $rapat->lampiran_tambahan_path ? 1 : 0 }}"
                        >
                            <td class="row-toggle-col">
                                <button type="button" class="row-toggle-btn rapat-row-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="rapat-title">{{ $rapat->judul }}</div>
                                <div class="rapat-meta">{{ $rapat->nomor_undangan }}</div>
                                @if($rapat->deskripsi)
                                    <div class="rapat-meta mt-1">{{ \Illuminate\Support\Str::limit($rapat->deskripsi, 80) }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $rapat->kategori_surat_label }}</div>
                                <div class="rapat-meta">{{ $rapat->kategori_surat_kode_label }}</div>
                            </td>
                            <td>
                                <div>{{ $rapat->tanggal_wit_formatted }}</div>
                                <div class="rapat-meta">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                            </td>
                            <td>{{ $rapat->tempat }}</td>
                            <td>
                                <div>{{ $rapat->pesertas->count() }} peserta</div>
                                <div class="rapat-meta">{{ $rapat->pesertas->take(2)->pluck('name')->implode(', ') }}{{ $rapat->pesertas->count() > 2 ? '...' : '' }}</div>
                            </td>
                            <td>
                                <div class="rapat-meta">{{ optional($rapat->approver1)->name ?? '-' }}</div>
                                <div class="rapat-meta">{{ optional($rapat->approver2)->name ?? '-' }}</div>
                            </td>
                            <td>
                                <button type="button" class="status-trigger-btn" onclick="openStatusModal({{ $rapat->id }})">
                                    {!! $rapat->status_badge !!}
                                    <small>Lihat riwayat approval</small>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="rapatActionTemplates" class="d-none">
        @foreach($rapats as $rapat)
            <div data-template-id="{{ $rapat->id }}">
                <div class="rapat-action-panel">
                    @if($rapat->is_virtual)
                        <span class="rapat-chip virtual"><i class="fas fa-video"></i> Virtual</span>
                    @endif
                    @if($rapat->jenis_pakaian)
                        <span class="rapat-chip pakaian"><i class="fas fa-tshirt"></i> {{ $rapat->jenis_pakaian }}</span>
                    @endif
                    @if($rapat->is_recurring)
                        <span class="rapat-chip recurring"><i class="fas fa-sync-alt"></i> {{ ucfirst($rapat->recurring_pattern) }}</span>
                    @endif
                    @if($rapat->bersama_satker)
                        <span class="rapat-chip"><i class="fas fa-building"></i> Bersama Satker</span>
                    @endif
                    <span class="rapat-action-meta">Tindakan rapat</span>
                    @if($rapat->lampiran_tambahan_path)
                        <button type="button" class="action-chip-btn action-lampiran" onclick="previewLampiran('{{ route('rapat.lampiran', $rapat) }}')">
                            <i class="fas fa-paperclip"></i> Lampiran
                        </button>
                    @endif
                    <button type="button" class="action-chip-btn action-lampiran" onclick="previewLampiran('{{ route('rapat.undangan.preview', $rapat) }}')">
                        <i class="fas fa-file-pdf"></i> Undangan
                    </button>
                    @if($rapat->bersama_satker)
                        <button type="button" class="action-chip-btn action-lampiran" onclick="previewLampiran('{{ route('rapat.undangan-satker.preview', $rapat) }}')">
                            <i class="fas fa-building"></i> Undangan Satker
                        </button>
                    @endif
                    @if(auth()->user()->canManageRapat())
                        <button type="button" class="action-chip-btn action-edit" onclick="openEditModal({{ $rapat->id }})">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button type="button" class="action-chip-btn action-delete" onclick="deleteRapat({{ $rapat->id }})">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div id="rapatStatusTemplates" class="d-none">
        @foreach($rapats as $rapat)
            <div data-status-template-id="{{ $rapat->id }}">
                <div class="status-modal-section">
                    <div class="status-modal-title">Informasi Status</div>
                    <div><strong>{{ $rapat->status_label }}</strong></div>
                    <div class="rapat-meta mt-1">{{ $rapat->nomor_undangan }}</div>
                    <div class="rapat-meta">{{ $rapat->judul }}</div>
                </div>

                <div class="status-modal-section">
                    <div class="status-modal-title">Urutan Approval</div>
                    @forelse($rapat->approvals->sortBy('step_order') as $step)
                        @php
                            $stepBadge = [
                                'pending' => ['warning', 'Pending'],
                                'waiting' => ['secondary', 'Waiting'],
                                'approved' => ['success', 'Approved'],
                                'rejected' => ['danger', 'Rejected'],
                            ][$step->status] ?? ['secondary', ucfirst($step->status)];
                        @endphp
                        <div class="status-step">
                            <div>
                                <div class="status-step-main">{{ $step->stage_label }} - {{ $step->approver_name_snapshot }}</div>
                                <div class="status-step-sub">{{ $step->approver_jabatan_snapshot ?: 'Tanpa jabatan' }}</div>
                                @if($step->acted_at)
                                    <div class="status-step-sub">{{ $step->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') }} WIT</div>
                                @endif
                                @if($step->catatan)
                                    <div class="status-step-sub">Catatan: {{ $step->catatan }}</div>
                                @endif
                            </div>
                            <div><span class="badge badge-{{ $stepBadge[0] }}">{{ $stepBadge[1] }}</span></div>
                        </div>
                    @empty
                        <div class="rapat-meta">Belum ada workflow approval untuk rapat ini.</div>
                    @endforelse
                </div>

                <div class="status-modal-section">
                    <div class="status-modal-title">Riwayat Approval</div>
                    @forelse($rapat->approvalHistories->sortByDesc('acted_at') as $entry)
                        <div class="status-history-item">
                            <div class="status-step-main">{{ ucfirst($entry->action) }} - {{ $entry->approver_name_snapshot }}</div>
                            <div class="status-step-sub">{{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                            @if($entry->catatan)
                                <div class="status-step-sub">Catatan: {{ $entry->catatan }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="rapat-meta">Belum ada riwayat approval.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @if(auth()->user()->canManageRapat())
        @include('rapat.partials.form-modal', ['modalId' => 'createRapatModal', 'formId' => 'createRapatForm', 'title' => 'Tambah Rapat', 'submitLabel' => 'Simpan', 'action' => route('rapat.store'), 'method' => 'POST'])
        @include('rapat.partials.form-modal', ['modalId' => 'editRapatModal', 'formId' => 'editRapatForm', 'title' => 'Edit Rapat', 'submitLabel' => 'Perbarui', 'action' => '#', 'method' => 'PUT'])
    @endif

    <div class="modal fade" id="lampiranModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Lampiran</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="lampiranViewer" style="width: 100%; height: 75vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Status dan Riwayat Approval</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" id="statusModalBody"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(function () {
            const rapatTable = $('#rapatTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries found',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                }
            });

            $('#rapatTable tbody').on('click', '.rapat-row-toggle', function () {
                const tr = $(this).closest('tr');
                const row = rapatTable.row(tr);
                const rapatId = tr.data('rapatId');
                const $btn = $(this);
                const template = $('#rapatActionTemplates').find('[data-template-id="' + rapatId + '"]').html() || '';

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $btn.removeClass('is-open').text('+');
                    return;
                }

                rapatTable.rows().every(function () {
                    const currentNode = $(this.node());
                    currentNode.find('.rapat-row-toggle').removeClass('is-open').text('+');
                    if (this.child.isShown()) {
                        this.child.hide();
                        currentNode.removeClass('shown');
                    }
                });

                row.child(template).show();
                tr.addClass('shown');
                $btn.addClass('is-open').text('-');
            });

            function toggleKategoriDependentFields(prefix) {}

            function togglePakaianFields(prefix) {
                const checked = $('#' + prefix + 'IncludePakaian').is(':checked');
                $('#' + prefix + 'PakaianGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'JenisPakaian').val('');
                }
            }

            function toggleDetailTambahan(prefix) {
                const checked = $('#' + prefix + 'IncludeDetailTambahan').is(':checked');
                $('#' + prefix + 'DetailTambahanGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'DetailTambahan').val('');
                }
            }

            function toggleVirtualFields(prefix) {
                const checked = $('#' + prefix + 'IsVirtual').is(':checked');
                $('#' + prefix + 'VirtualGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'MeetingId').val('');
                    $('#' + prefix + 'MeetingPasscode').val('');
                }
            }

            function toggleRecurringFields(prefix) {
                const checked = $('#' + prefix + 'IsRecurring').is(':checked');
                $('#' + prefix + 'RecurringGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'RecurringPattern').val('');
                    $('#' + prefix + 'RecurringUntil').val('');
                }
            }

            function toggleLampiranFields(prefix) {
                const checked = $('#' + prefix + 'GunakanLampiran').is(':checked');
                $('#' + prefix + 'LampiranGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'Lampiran').val('');
                }
            }

            function toggleSatkerFields(prefix) {
                const checked = $('#' + prefix + 'BersamaSatker').is(':checked');
                const $participantSelect = $('#' + prefix + 'PesertaIds');
                const $satkerOptions = $participantSelect.find('option[data-is-satker="1"]');

                $('#' + prefix + 'SatkerGroup').toggle(checked);
                $('#' + prefix + 'TujuanSurat').prop('required', checked);
                $('#' + prefix + 'Approver1Id').prop('required', checked);
                $('#' + prefix + 'SatkerApproverRequired').toggle(checked);
                $satkerOptions.prop('disabled', !checked);

                if (!checked) {
                    $('#' + prefix + 'TujuanSurat').val('');
                    const satkerValues = $satkerOptions.map(function () { return String(this.value); }).get();
                    const selected = ($participantSelect.val() || []).map(String).filter(function (value) {
                        return satkerValues.indexOf(value) === -1;
                    });
                    $participantSelect.val(selected).trigger('change');
                } else {
                    $participantSelect.trigger('change.select2');
                }
            }

            function bindFormBehavior(prefix) {
                $('#' + prefix + 'KategoriSuratKode').on('change', function () {
                    toggleKategoriDependentFields(prefix);
                    updateNomorPreview(prefix);
                });
                $('#' + prefix + 'IsVirtual').on('change', function () { toggleVirtualFields(prefix); });
                $('#' + prefix + 'IsRecurring').on('change', function () { toggleRecurringFields(prefix); });
                $('#' + prefix + 'GunakanLampiran').on('change', function () { toggleLampiranFields(prefix); });
                $('#' + prefix + 'BersamaSatker').on('change', function () { toggleSatkerFields(prefix); });
                $('#' + prefix + 'IncludeDetailTambahan').on('change', function () { toggleDetailTambahan(prefix); });
                $('#' + prefix + 'IncludePakaian').on('change', function () { togglePakaianFields(prefix); });
                $('#' + prefix + 'Tanggal, #' + prefix + 'NomenklaturJabatan').on('change', function () {
                    updateNomorPreview(prefix);
                });
                toggleKategoriDependentFields(prefix);
                togglePakaianFields(prefix);
                toggleDetailTambahan(prefix);
                toggleVirtualFields(prefix);
                toggleRecurringFields(prefix);
                toggleLampiranFields(prefix);
                toggleSatkerFields(prefix);
                updateNomorPreview(prefix);
            }

            function updateNomorPreview(prefix) {
                if (!$('#' + prefix + 'NomorUndangan').length) {
                    return;
                }

                const kategoriSuratKodeId = $('#' + prefix + 'KategoriSuratKode').val();
                const tanggal = $('#' + prefix + 'Tanggal').val();
                const nomenklatur = $('#' + prefix + 'NomenklaturJabatan').val();

                if (!kategoriSuratKodeId) {
                    $('#' + prefix + 'NomorUndangan').val('');
                    return;
                }

                $.get('{{ route('rapat.preview-nomor') }}', {
                    kategori_surat_kode_id: kategoriSuratKodeId,
                    tanggal: tanggal,
                    nomenklatur_jabatan: nomenklatur
                }).done(function (response) {
                    $('#' + prefix + 'NomorUndangan').val(response.nomor || '');
                }).fail(function () {
                    $('#' + prefix + 'NomorUndangan').val('');
                });
            }

            @if(auth()->user()->canManageRapat())
                bindFormBehavior('create');
                bindFormBehavior('edit');

                function refreshRapatParticipantSelectAll($scope) {
                    const $selects = $scope && $scope.is('select[data-participant-select="1"]')
                        ? $scope
                        : ($scope || $(document)).find('select[data-participant-select="1"]');

                    $selects.each(function () {
                        const $select = $(this);
                        const id = $select.attr('id');
                        const $button = id ? $('.rapat-select-all-participants[data-target="#' + id + '"]') : $();
                        const values = $select.find('option:not(:disabled)').map(function () {
                            return String(this.value);
                        }).get();
                        const selectedValues = ($select.val() || []).map(String);
                        const isAllSelected = values.length > 0 && selectedValues.length >= values.length;

                        $button
                            .toggleClass('is-all-selected', isAllSelected)
                            .html(isAllSelected
                                ? '<i class="fas fa-times mr-1"></i> Hapus Semua'
                                : '<i class="fas fa-check-double mr-1"></i> Pilih Semua');

                        $('.rapat-select-unit-participants[data-target="#' + id + '"]').each(function () {
                            const $unitButton = $(this);
                            const unitId = String($unitButton.data('unit-id'));
                            const unitValues = $select.find('option:not(:disabled)').filter(function () {
                                return String($(this).data('unit-id')) === unitId;
                            }).map(function () {
                                return String(this.value);
                            }).get();
                            const isUnitSelected = unitValues.length > 0 && unitValues.every(function (value) {
                                return selectedValues.includes(value);
                            });

                            $unitButton.toggleClass('is-unit-selected', isUnitSelected);
                            $unitButton.find('i')
                                .toggleClass('far fa-square', !isUnitSelected)
                                .toggleClass('fas fa-check-square', isUnitSelected);
                        });
                    });
                }

                function initRapatSelect2($modal) {
                    $modal.find('.select2').each(function () {
                        const $select = $(this);
                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }
                        $select.select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownParent: $modal
                        });
                    });

                    refreshRapatParticipantSelectAll($modal);
                }

                $('#createRapatModal').on('shown.bs.modal', function () {
                    initRapatSelect2($(this));
                });

                $('#editRapatModal').on('shown.bs.modal', function () {
                    initRapatSelect2($(this));
                });

                $(document).on('click', '.rapat-select-all-participants', function () {
                    const $button = $(this);
                    const $select = $($button.data('target'));
                    const values = $select.find('option:not(:disabled)').map(function () {
                        return String(this.value);
                    }).get();
                    const selectedValues = ($select.val() || []).map(String);
                    const isAllSelected = values.length > 0 && selectedValues.length >= values.length;

                    $select.val(isAllSelected ? [] : values).trigger('change');
                    refreshRapatParticipantSelectAll($select);
                });

                $(document).on('click', '.rapat-select-unit-participants', function () {
                    const $button = $(this);
                    const $select = $($button.data('target'));
                    const unitId = String($button.data('unit-id'));
                    const unitValues = $select.find('option:not(:disabled)').filter(function () {
                        return String($(this).data('unit-id')) === unitId;
                    }).map(function () {
                        return String(this.value);
                    }).get();
                    const selectedValues = ($select.val() || []).map(String);
                    const selectedSet = new Set(selectedValues);
                    const isUnitSelected = unitValues.length > 0 && unitValues.every(function (value) {
                        return selectedSet.has(value);
                    });

                    unitValues.forEach(function (value) {
                        if (isUnitSelected) {
                            selectedSet.delete(value);
                        } else {
                            selectedSet.add(value);
                        }
                    });

                    $select.val(Array.from(selectedSet)).trigger('change');
                    refreshRapatParticipantSelectAll($select);
                });

                $(document).on('change', 'select[data-participant-select="1"]', function () {
                    refreshRapatParticipantSelectAll($(this));
                });

                $('#createRapatForm').on('submit', function (e) {
                    e.preventDefault();
                    submitRapatForm($(this), '{{ route('rapat.store') }}');
                });

                $('#editRapatForm').on('submit', function (e) {
                    e.preventDefault();
                    const action = $('#editRapatForm').data('action');
                    submitRapatForm($(this), action, true);
                });
            @endif

            window.previewLampiran = function (url) {
                $('#lampiranViewer').attr('src', url);
                $('#lampiranModal').modal('show');
            };

            window.openStatusModal = function (rapatId) {
                const html = $('#rapatStatusTemplates').find('[data-status-template-id="' + rapatId + '"]').html() || '<div class="text-muted">Status belum tersedia.</div>';
                $('#statusModalBody').html(html);
                $('#statusModal').modal('show');
            };

            window.deleteRapat = function (rapatId) {
                const row = $('tr[data-rapat-id="' + rapatId + '"]');
                const url = row.data('deleteUrl');

                if (!confirm('Hapus rapat ini?')) {
                    return;
                }

                $.ajax({
                    url: url,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        showToast(res.message, 'success');
                        location.reload();
                    },
                    error: function (xhr) {
                        showToast(xhr.responseJSON?.message || 'Gagal menghapus rapat.', 'error');
                    }
                });
            };

            @if(auth()->user()->canManageRapat())
                window.openEditModal = function (rapatId) {
                    const row = $('tr[data-rapat-id="' + rapatId + '"]');
                    const pesertaIds = String(row.data('pesertaIds') || '').split(',').filter(Boolean);

                    $('#editRapatForm').data('action', row.data('updateUrl'));
                    $('#editJudul').val(row.data('judul'));
                    $('#editDeskripsi').val(row.data('deskripsi'));
                    $('#editKategoriSuratKode').val(row.data('kategoriSuratKode')).trigger('change');
                    $('#editNomenklaturJabatan').val(row.data('nomenklaturJabatan'));
                    $('#editTanggal').val(row.data('tanggal'));
                    $('#editWaktuMulai').val(row.data('waktuMulai'));
                    $('#editTempat').val(row.data('tempat'));
                    $('#editBersamaSatker').prop('checked', Number(row.data('bersamaSatker')) === 1).trigger('change');
                    $('#editPesertaIds').val(pesertaIds).trigger('change');
                    refreshRapatParticipantSelectAll($('#editPesertaIds'));
                    $('#editApprover1Id').val(row.data('approver1')).trigger('change');
                    $('#editApprover2Id').val(row.data('approver2')).trigger('change');
                    $('#editApproval1JabatanManual').val(row.data('approval1JabatanManual'));
                    $('#editIncludeDetailTambahan').prop('checked', Number(row.data('includeDetailTambahan')) === 1).trigger('change');
                    $('#editDetailTambahan').val(row.data('detailTambahan'));
                    $('#editTujuanSurat').val(row.data('tujuanSurat'));
                    $('#editIncludePakaian').prop('checked', Number(row.data('includePakaian')) === 1).trigger('change');
                    $('#editJenisPakaian').val(row.data('jenisPakaian'));
                    $('#editIsVirtual').prop('checked', Number(row.data('isVirtual')) === 1).trigger('change');
                    $('#editMeetingId').val(row.data('meetingId'));
                    $('#editMeetingPasscode').val(row.data('meetingPasscode'));
                    $('#editIsRecurring').prop('checked', Number(row.data('isRecurring')) === 1).trigger('change');
                    $('#editRecurringPattern').val(row.data('recurringPattern'));
                    $('#editRecurringUntil').val(row.data('recurringUntil'));
                    $('#editGunakanLampiran').prop('checked', Number(row.data('hasLampiran')) === 1).trigger('change');
                    $('#editHapusLampiranTambahan').prop('checked', false);
                    $('#editLampiranInfo').toggle(Number(row.data('hasLampiran')) === 1);

                    $('#editRapatModal').modal('show');
                };

                function submitRapatForm($form, url, usePostOverride) {
                    const formData = new FormData($form[0]);
                    if (usePostOverride) {
                        formData.append('_method', 'PUT');
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (res) {
                            showToast(res.message, 'success');
                            location.reload();
                        },
                        error: function (xhr) {
                            const errors = xhr.responseJSON?.errors;
                            let message = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                            if (errors) {
                                message = Object.values(errors).flat().join('<br>');
                            }
                            showToast(message, 'error');
                        }
                    });
                }

                const directRapatId = Number(@json((int) request('focus')));
                if (directRapatId && @json(request('action')) === 'edit') {
                    const directRow = $('tr[data-rapat-id="' + directRapatId + '"]');
                    if (directRow.length) {
                        directRow.css({ backgroundColor: '#eef2ff', outline: '2px solid #8b5cf6' });
                        setTimeout(function () { window.openEditModal(directRapatId); }, 250);
                    }
                }
            @endif
        });
    </script>
@endpush
