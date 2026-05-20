@extends('layouts.app')

@section('title', 'Surat Masuk')

@push('styles')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .surat-masuk-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .surat-masuk-card .card-header {
            background: white;
            border-bottom: 1px solid #f3f4f6;
            padding: 20px 24px;
            border-radius: 16px 16px 0 0;
        }

        .surat-masuk-card .card-header h3 {
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
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }

        /* Table */
        #suratMasukTable thead th {
            background: #f9fafb;
            color: #6b7280;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e8eaed;
            padding: 12px 14px;
            white-space: nowrap;
        }

        #suratMasukTable tbody td {
            padding: 14px;
            vertical-align: top;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.85rem;
            color: #374151;
        }

        #suratMasukTable tbody tr:hover {
            background: #f9fafb;
        }

        /* Badges */
        .badge-ma {
            background: #22c55e;
            color: white;
            font-size: 0.68rem;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-non-ma {
            background: #f97316;
            color: white;
            font-size: 0.68rem;
            padding: 3px 10px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-sifat-biasa {
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-sifat-rahasia {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-sifat-sangat-rahasia {
            background: #fef2f2;
            color: #dc2626;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-new-status {
            background: #fee2e2;
            color: #b91c1c;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-on-process {
            background: #fef3c7;
            color: #92400e;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .badge-done {
            background: #dcfce7;
            color: #166534;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        .status-text {
            font-size: 0.85rem;
            color: #374151;
            font-weight: 500;
        }

        .nomor-surat-text {
            font-weight: 600;
            color: #111827;
            font-size: 0.85rem;
        }

        .klasifikasi-prefix {
            color: #9ca3af;
            font-size: 0.78rem;
        }

        .pengirim-nama {
            color: #4b5563;
            font-size: 0.8rem;
            margin-top: 4px;
            font-weight: 500;
        }

        /* Detail row */
        .detail-row {
            background: #fafbfc;
        }

        .detail-row td {
            padding: 12px 14px !important;
            border-bottom: 2px solid #e5e7eb;
        }

        .detail-content {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            padding-left: 36px;
        }

        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
        }

        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .surat-actions-cell {
            min-width: 120px;
        }

        .surat-action-dropdown .dropdown-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 7px 13px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(79, 70, 229, 0.18);
        }

        .surat-action-dropdown .dropdown-toggle:hover,
        .surat-action-dropdown.show .dropdown-toggle {
            background: linear-gradient(135deg, #4338ca, #4f46e5);
            color: #ffffff;
        }

        .surat-action-menu {
            min-width: 210px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 8px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
        }

        .surat-action-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 9px;
            border-radius: 10px;
            padding: 9px 11px;
            color: #1f2937;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .surat-action-menu .dropdown-item i {
            width: 16px;
            text-align: center;
            color: #6366f1;
        }

        .surat-action-menu .dropdown-item:hover {
            background: #eef2ff;
            color: #4338ca;
        }

        .surat-action-menu .dropdown-item.text-danger i {
            color: #dc2626;
        }

        .detail-label {
            color: #9ca3af;
            font-size: 0.8rem;
        }

        .detail-value {
            color: #374151;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Action buttons in detail row */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 34px;
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.77rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
            text-decoration: none;
            line-height: 1.2;
            white-space: nowrap;
        }

        .action-btn:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }

        .action-btn-disposisi {
            background: #eef2ff;
            color: #4f46e5;
        }

        .action-btn-disposisi:hover {
            background: #e0e7ff;
            color: #4f46e5;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);
        }

        .action-btn-naikan {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btn-naikan:hover {
            background: #fde68a;
            color: #92400e;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
        }

        .action-btn-detail {
            background: #f3f4f6;
            color: #374151;
        }

        .action-btn-detail:hover {
            background: #e5e7eb;
            color: #111827;
        }

        .action-btn-edit {
            background: #f0fdf4;
            color: #166534;
        }

        .action-btn-edit:hover {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn-delete {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn-delete:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn-follow-up {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn-follow-up:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn-disabled {
            background: #f3f4f6 !important;
            color: #9ca3af !important;
            cursor: not-allowed !important;
            box-shadow: none !important;
            pointer-events: none;
            transform: none !important;
        }

        .agenda-pimpinan-box {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: linear-gradient(180deg, #f8fbff, #ffffff);
            padding: 14px 16px;
            margin-top: 8px;
        }

        .agenda-pimpinan-fields {
            display: none;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px dashed #bfdbfe;
        }

        .agenda-pimpinan-note {
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.45;
            margin-top: 4px;
        }

        .history-panel {
            margin-top: 16px;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
        }

        .history-panel-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }

        .history-list {
            max-height: 220px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .history-item {
            border: 1px solid #e8eaed;
            border-radius: 10px;
            padding: 10px 12px;
            background: #ffffff;
            margin-bottom: 8px;
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-flow {
            font-size: 0.8rem;
            color: #111827;
            font-weight: 600;
            margin-top: 6px;
        }

        .history-meta,
        .history-note {
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.4;
            margin-top: 6px;
        }

        .history-empty {
            font-size: 0.78rem;
            color: #9ca3af;
            padding: 10px 0;
        }

        /* Detail Modal */
        .detail-info-table td {
            padding: 8px 12px;
            font-size: 0.875rem;
            border: none;
        }

        .detail-info-label {
            font-weight: 600;
            color: #6b7280;
            width: 160px;
        }

        .detail-info-value {
            color: #111827;
        }

        .surat-masuk-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #suratMasukTable {
            min-width: 1120px;
        }

        @media (max-width: 767.98px) {
            .surat-masuk-card {
                border-radius: 14px;
            }

            .surat-masuk-card .card-body {
                padding-left: 14px !important;
                padding-right: 14px !important;
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

            .detail-content {
                padding-left: 0;
                gap: 10px;
            }

            .detail-meta {
                gap: 6px 12px;
            }

            .detail-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                gap: 8px;
            }

            .detail-actions .action-btn {
                width: 100%;
                min-width: 0;
                padding: 8px 10px;
                font-size: 0.76rem;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-inbox" style="font-size: 0.9rem; color: #6366f1;"></i>
                        </div>
                        Surat Masuk
                    </h1>
                </div>
                <div class="col-sm-6 text-right">
                    @if(auth()->user()->canCreateSuratMasuk())
                        <button class="btn btn-add-surat" data-toggle="modal" data-target="#createModal">
                            <i class="fas fa-plus mr-1"></i> Add Surat Masuk
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card surat-masuk-card">
        <div class="card-body" style="padding-top: 20px;">
            <div class="table-responsive surat-masuk-table-wrap">
            <table id="suratMasukTable" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Pengirim</th>
                        <th>Perihal / Isi Ringkas</th>
                        <th>Tanggal Surat</th>
                        <th>Diinput Pada</th>
                        <th>Dibuat Oleh</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suratMasuk as $surat)
                        @php
                            $latestDisposisi = $surat->disposisis->sortByDesc('created_at')->first();
                            $pendingForMe = $surat->disposisis
                                ->where('kepada_user_id', auth()->id())
                                ->where('status', 'pending')
                                ->sortByDesc('created_at')
                                ->first();
                        @endphp
                        <tr class="main-row" data-surat-id="{{ $surat->id }}" data-creator="{{ optional($surat->creator)->name ?: '-' }}"
                            data-show-url="{{ route('surat-masuk.show', $surat) }}"
                            data-download-url="{{ route('surat-masuk.download', $surat) }}"
                            data-preview-url="{{ route('surat-masuk.preview', $surat) }}"
                            data-update-url="{{ route('surat-masuk.update', $surat) }}"
                            data-delete-url="{{ route('surat-masuk.destroy', $surat) }}" data-nomor="{{ $surat->nomor_surat }}"
                            data-opsi-pengirim="{{ $surat->opsi_pengirim }}"
                            data-kategori-surat="{{ $surat->kategori_surat_id }}"
                            data-kategori-surat-label="{{ optional($surat->kategoriSurat)->kode ? optional($surat->kategoriSurat)->kode . ' - ' . optional($surat->kategoriSurat)->nama : '' }}"
                            data-klasifikasi="{{ $surat->klasifikasi_kode_id }}" data-pengirim="{{ $surat->pengirim }}"
                            data-perihal="{{ $surat->perihal }}" data-tanggal="{{ $surat->tanggal_surat->format('Y-m-d') }}"
                            data-sifat="{{ $surat->sifat }}" data-status="{{ $surat->status }}"
                            data-file-path="{{ $surat->file_path }}"
                            data-agenda-title="{{ optional($surat->agendaPimpinan)->judul_agenda }}"
                            data-agenda-date="{{ optional(optional($surat->agendaPimpinan)->tanggal_kegiatan)->format('Y-m-d') }}"
                            data-agenda-time="{{ optional($surat->agendaPimpinan)->waktu_formatted }}"
                            data-agenda-place="{{ optional($surat->agendaPimpinan)->tempat }}"
                            data-agenda-clothing="{{ optional($surat->agendaPimpinan)->seragam_pakaian }}"
                            data-can-forward="{{ auth()->user()->canForwardSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-edit="{{ auth()->user()->canEditSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-delete="{{ auth()->user()->canDeleteSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-follow-up="{{ auth()->user()->canOpenTindakLanjutSuratMasuk($surat) ? 1 : 0 }}"
                            data-pending-disposisi-id="{{ $pendingForMe ? $pendingForMe->id : '' }}">
                            <td>
                                @if($surat->klasifikasiKode)
                                    <span class="klasifikasi-prefix">{{ $surat->klasifikasiKode->kode }} -
                                        {{ $surat->klasifikasiKode->nama }}</span><br>
                                @endif
                                <span class="nomor-surat-text">{{ $surat->nomor_surat }}</span><br>
                                @php
                                    $sifatClass = ['biasa' => 'badge-sifat-biasa', 'rahasia' => 'badge-sifat-rahasia', 'sangat_rahasia' => 'badge-sifat-sangat-rahasia'];
                                    $sifatLabel = ['biasa' => 'Biasa', 'rahasia' => 'Rahasia', 'sangat_rahasia' => 'Sangat Rahasia'];
                                @endphp
                                <span
                                    class="{{ $sifatClass[$surat->sifat] ?? 'badge-sifat-biasa' }}">{{ $sifatLabel[$surat->sifat] ?? $surat->sifat }}</span>
                            </td>
                            <td>
                                <span class="{{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'badge-ma' : 'badge-non-ma' }}">
                                    {{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung' }}
                                </span>
                                <div class="pengirim-nama">{{ $surat->pengirim }}</div>
                            </td>
                            <td style="max-width: 220px;">
                                {{ Str::limit($surat->perihal, 80) }}
                            </td>
                            <td>{{ $surat->tanggal_surat->format('Y-m-d') }}</td>
                            <td>{{ $surat->created_at->format('Y-m-d') }}</td>
                            <td>{{ optional($surat->creator)->name ?: '-' }}</td>
                            <td>
                                @if($surat->status == 'baru')
                                    <span class="status-text">Baru</span><br>
                                    <span class="badge-new-status">New</span>
                                @elseif($surat->status == 'didisposisi')
                                    @if($latestDisposisi)
                                        <span
                                            class="status-text">{{ $latestDisposisi->tipe == 'naikan' ? 'Dinaikan' : 'Diteruskan' }}</span><br>
                                    @else
                                        <span class="status-text">Didisposisi</span><br>
                                    @endif
                                    <span class="badge-on-process">On-Process</span>
                                @else
                                    <span class="status-text">Selesai</span><br>
                                    <span class="badge-done">Done</span>
                                @endif
                            </td>
                            <td class="surat-actions-cell">
                                <div class="dropdown surat-action-dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="suratMasukAction{{ $surat->id }}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h"></i> Aksi
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right surat-action-menu" aria-labelledby="suratMasukAction{{ $surat->id }}">
                                        @if(auth()->user()->canForwardSuratMasuk($surat))
                                            @if(auth()->user()->isKasubagTurt())
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'teruskan')">
                                                    <i class="fas fa-share"></i> Teruskan
                                                </button>
                                            @else
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'disposisi')">
                                                    <i class="fas fa-share"></i> Disposisi
                                                </button>
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'naikan')">
                                                    <i class="fas fa-level-up-alt"></i> Naikan
                                                </button>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                        @endif

                                        @if(auth()->user()->canOpenTindakLanjutSuratMasuk($surat))
                                            <button type="button" class="dropdown-item" onclick="openTindakLanjut({{ $surat->id }})">
                                                <i class="fas fa-flag"></i> Tindaklanjuti
                                            </button>
                                        @endif

                                        <button type="button" class="dropdown-item" onclick="openDetail({{ $surat->id }})">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>

                                        @if(auth()->user()->canEditSuratMasuk($surat))
                                            <button type="button" class="dropdown-item" onclick="openEdit({{ $surat->id }})">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->canDeleteSuratMasuk($surat))
                                            <div class="dropdown-divider"></div>
                                            <button type="button" class="dropdown-item text-danger" data-delete-url="{{ route('surat-masuk.destroy', $surat) }}" onclick="deleteSurat({{ $surat->id }}, this.dataset.deleteUrl)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Surat Masuk</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="createForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nomor Surat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nomor_surat" required
                                        placeholder="Masukkan nomor surat">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Opsi Pengirim <span class="text-danger">*</span></label>
                                    <select class="form-control" name="opsi_pengirim" id="opsiPengirim" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="mahkamah_agung">Mahkamah Agung</option>
                                        <option value="non_mahkamah_agung">Non Mahkamah Agung</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="klasifikasiGroup" style="display: none;">
                            <label>Kode Klasifikasi Surat <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="klasifikasi_kode_id" id="klasifikasiKode">
                                <option value="">-- Pilih Kode Klasifikasi --</option>
                                @foreach($klasifikasiKodes as $kode)
                                    <option value="{{ $kode->id }}">{{ $kode->kode }} - {{ $kode->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Kategori Surat</label>
                            <select class="form-control" name="kategori_surat_id" id="createKategoriSurat">
                                <option value="">-- Pilih Kategori Surat --</option>
                                @foreach($kategoriSurats as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->kode }} - {{ $kategori->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Pengirim <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pengirim" required
                                placeholder="Nama pengirim surat">
                        </div>

                        <div class="form-group">
                            <label>Perihal / Isi Ringkas <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="perihal" rows="3" required
                                placeholder="Perihal atau isi ringkas surat"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tanggal Surat <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_surat" required
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sifat <span class="text-danger">*</span></label>
                                    <select class="form-control" name="sifat" required>
                                        <option value="biasa">Biasa</option>
                                        <option value="rahasia">Rahasia</option>
                                        <option value="sangat_rahasia">Sangat Rahasia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>File Lampiran <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control-file" name="file" required
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <small class="text-muted">PDF, DOC, DOCX, JPG, PNG (maks. 10MB)</small>
                                </div>
                            </div>
                        </div>

                        <div class="agenda-pimpinan-box">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="createAgendaPimpinan" name="agenda_pimpinan" value="1">
                                <label class="custom-control-label font-weight-bold" for="createAgendaPimpinan">
                                    Jadikan sebagai agenda pimpinan
                                </label>
                            </div>
                            <div class="agenda-pimpinan-note">
                                Jika dicentang, sistem akan otomatis membuat agenda pimpinan dari surat masuk ini dan mengirim notifikasi ke role Protokoler untuk mengatur peserta kegiatan.
                            </div>
                            <div class="agenda-pimpinan-fields" id="createAgendaPimpinanFields">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Tanggal Kegiatan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control agenda-required" name="agenda_tanggal_kegiatan" id="createAgendaTanggal" value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Waktu Kegiatan (WIT) <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control agenda-required" name="agenda_waktu" id="createAgendaWaktu" step="60" value="{{ now()->timezone('Asia/Jayapura')->format('H:i') }}">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label>Tempat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control agenda-required" name="agenda_tempat" id="createAgendaTempat" placeholder="Tempat kegiatan">
                                </div>
                            </div>
                        </div>
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

    <!-- Detail / Preview Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope-open-text mr-2"></i>Detail Surat Masuk</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <div class="row no-gutters">
                        <!-- File Preview -->
                        <div class="col-lg-7" style="border-right: 1px solid #f3f4f6;">
                            <div style="padding: 16px;">
                                <h6 style="font-weight: 700; color: #374151; margin-bottom: 12px;">
                                    <i class="fas fa-file-pdf mr-1 text-danger"></i> Preview Lampiran
                                </h6>
                                <iframe id="detailFileViewer"
                                    style="width: 100%; height: 500px; border: 1px solid #e8eaed; border-radius: 10px; background: #f9fafb;"></iframe>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="col-lg-5">
                            <div style="padding: 20px;">
                                <h6 style="font-weight: 700; color: #374151; margin-bottom: 16px;">
                                    <i class="fas fa-info-circle mr-1 text-primary"></i> Informasi Surat
                                </h6>
                                <table class="table table-borderless detail-info-table">
                                    <tr>
                                        <td class="detail-info-label">Nomor Surat</td>
                                        <td class="detail-info-value" id="detailNomor">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Kategori Surat</td>
                                        <td class="detail-info-value" id="detailKategoriSurat">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Pengirim</td>
                                        <td class="detail-info-value" id="detailPengirim">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Opsi Pengirim</td>
                                        <td class="detail-info-value" id="detailOpsi">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Perihal</td>
                                        <td class="detail-info-value" id="detailPerihal">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Tanggal Surat</td>
                                        <td class="detail-info-value" id="detailTanggal">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Sifat</td>
                                        <td class="detail-info-value" id="detailSifat">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Status</td>
                                        <td class="detail-info-value" id="detailStatus">-</td>
                                    </tr>
                                    <tr>
                                        <td class="detail-info-label">Di-input Oleh</td>
                                        <td class="detail-info-value" id="detailCreator">-</td>
                                    </tr>
                                    <tr id="detailAgendaRow" style="display: none;">
                                        <td class="detail-info-label">Agenda Pimpinan</td>
                                        <td class="detail-info-value" id="detailAgendaInfo">-</td>
                                    </tr>
                                </table>
                                <div class="mt-3 d-flex" style="gap: 8px;">
                                    <a href="#" id="detailDownloadBtn" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download mr-1"></i> Download
                                    </a>
                                    <a href="#" id="detailShowBtn" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-external-link-alt mr-1"></i> Halaman Detail
                                    </a>
                                </div>
                                <div class="history-panel">
                                    <div class="history-panel-title">
                                        <i class="fas fa-history mr-1 text-primary"></i> Riwayat Surat
                                    </div>
                                    <div id="detailHistory" class="history-list"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Surat Masuk</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="editForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" id="editSuratId">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nomor Surat <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nomor_surat" id="editNomor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Opsi Pengirim <span class="text-danger">*</span></label>
                                    <select class="form-control" name="opsi_pengirim" id="editOpsiPengirim" required>
                                        <option value="mahkamah_agung">Mahkamah Agung</option>
                                        <option value="non_mahkamah_agung">Non Mahkamah Agung</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" id="editKlasifikasiGroup" style="display: none;">
                            <label>Kode Klasifikasi Surat</label>
                            <select class="form-control" name="klasifikasi_kode_id" id="editKlasifikasi">
                                <option value="">-- Pilih --</option>
                                @foreach($klasifikasiKodes as $kode)
                                    <option value="{{ $kode->id }}">{{ $kode->kode }} - {{ $kode->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Kategori Surat</label>
                            <select class="form-control" name="kategori_surat_id" id="editKategoriSurat">
                                <option value="">-- Pilih Kategori Surat --</option>
                                @foreach($kategoriSurats as $kategori)
                                    <option value="{{ $kategori->id }}">{{ $kategori->kode }} - {{ $kategori->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pengirim <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pengirim" id="editPengirim" required>
                        </div>
                        <div class="form-group">
                            <label>Perihal <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="perihal" id="editPerihal" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tanggal Surat <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="tanggal_surat" id="editTanggal" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Sifat <span class="text-danger">*</span></label>
                                    <select class="form-control" name="sifat" id="editSifat" required>
                                        <option value="biasa">Biasa</option>
                                        <option value="rahasia">Rahasia</option>
                                        <option value="sangat_rahasia">Sangat Rahasia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>File Baru (Opsional)</label>
                                    <input type="file" class="form-control-file" name="file"
                                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    <small class="text-muted">Kosongkan jika tidak ganti file</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success" id="btnEditSubmit">
                            <i class="fas fa-save mr-1"></i> Perbarui
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Disposisi Modal -->
    <div class="modal fade" id="disposisiModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="disposisiModalTitle"><i class="fas fa-share mr-2"></i>Disposisi Surat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="disposisiForm">
                    @csrf
                    <input type="hidden" name="surat_masuk_id" id="disposisiSuratId">
                    <input type="hidden" name="tipe" id="disposisiTipe" value="disposisi">
                    <div class="modal-body p-0">
                        <div class="row no-gutters">
                            <!-- Preview Surat -->
                            <div class="col-lg-6" style="border-right: 1px solid #f3f4f6;">
                                <div style="padding: 16px;">
                                    <h6 style="font-weight: 700; color: #374151; margin-bottom: 12px;">
                                        <i class="fas fa-file-pdf mr-1 text-danger"></i> Preview Surat
                                    </h6>
                                    <iframe id="disposisiFileViewer"
                                        style="width: 100%; height: 400px; border: 1px solid #e8eaed; border-radius: 10px; background: #f9fafb;"></iframe>
                                </div>
                            </div>
                            <!-- Form -->
                            <div class="col-lg-6">
                                <div style="padding: 20px;">
                                    <h6 style="font-weight: 700; color: #374151; margin-bottom: 16px;">
                                        <i class="fas fa-share mr-1 text-primary" id="disposisiFormIcon"></i>
                                        <span id="disposisiFormLabel">Form Disposisi</span>
                                    </h6>
                                    <div class="form-group">
                                        <label>Tujuan <span class="text-danger">*</span></label>
                                        <select class="form-control" name="kepada_user_id" id="disposisiTarget" required>
                                            <option value="">-- Memuat... --</option>
                                        </select>
                                    </div>
                                    <div class="form-group" id="disposisiPetunjukGroup">
                                        <label>Petunjuk <span class="text-danger">*</span></label>
                                        <select class="form-control" name="petunjuk" id="disposisiPetunjuk" required>
                                            <option value="">-- Pilih Petunjuk --</option>
                                            @foreach($petunjukOptions as $petunjuk)
                                                <option value="{{ $petunjuk }}">{{ $petunjuk }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Catatan</label>
                                        <textarea class="form-control" name="catatan" rows="3"
                                            placeholder="Catatan disposisi (opsional)"></textarea>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label>Prioritas <span class="text-danger">*</span></label>
                                            <select class="form-control" name="priority_level" required>
                                                <option value="normal">Normal</option>
                                                <option value="high">Tinggi</option>
                                                <option value="low">Rendah</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Target Tindak Lanjut</label>
                                            <input type="datetime-local" class="form-control" name="target_tindak_lanjut_at">
                                        </div>
                                    </div>
                                    <div class="history-panel">
                                        <div class="history-panel-title">
                                            <i class="fas fa-history mr-1 text-primary"></i> Riwayat Surat
                                        </div>
                                        <div id="disposisiHistory" class="history-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnDisposisiSubmit">
                            <i class="fas fa-paper-plane mr-1"></i> Kirim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tindak Lanjut Modal -->
    <div class="modal fade" id="tindakLanjutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-flag mr-2"></i>Tindak Lanjuti Surat</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="tindakLanjutForm">
                    @csrf
                    <input type="hidden" id="tindakLanjutDisposisiId">
                    <div class="modal-body p-0">
                        <div class="row no-gutters">
                            <div class="col-lg-6" style="border-right: 1px solid #f3f4f6;">
                                <div style="padding: 16px;">
                                    <h6 style="font-weight: 700; color: #374151; margin-bottom: 12px;">
                                        <i class="fas fa-file-pdf mr-1 text-danger"></i> Preview Surat
                                    </h6>
                                    <iframe id="tindakLanjutFileViewer"
                                        style="width: 100%; height: 400px; border: 1px solid #e8eaed; border-radius: 10px; background: #f9fafb;"></iframe>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div style="padding: 20px;">
                                    <h6 style="font-weight: 700; color: #374151; margin-bottom: 16px;">
                                        <i class="fas fa-flag mr-1 text-danger"></i> Form Tindak Lanjut
                                    </h6>
                                    <div class="form-group">
                                        <label>Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="tindakLanjutCatatan" rows="4"
                                            placeholder="Isi catatan tindak lanjut surat" required></textarea>
                                    </div>
                                    <div class="history-panel">
                                        <div class="history-panel-title">
                                            <i class="fas fa-history mr-1 text-primary"></i> Riwayat Surat
                                        </div>
                                        <div id="tindakLanjutHistory" class="history-list"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="btnTindakLanjutSubmit">
                            <i class="fas fa-check mr-1"></i> Tindaklanjuti
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            const canCreateSuratMasuk = @json(auth()->user()->canCreateSuratMasuk());
            const requiresPetunjuk = @json(auth()->user()->requiresPetunjukDisposisi());
            const isKasubagTurt = @json(auth()->user()->isKasubagTurt());
            const suratHistories = @json($suratHistories);
            const klasifikasiKodeMap = @json($klasifikasiKodes->map(function ($kode) {
                return ['id' => $kode->id, 'kode' => strtoupper($kode->kode)];
            })->values());
            const kategoriSuratMap = @json($kategoriSurats->map(function ($kategori) {
                return ['id' => $kategori->id, 'kode' => strtoupper($kategori->kode)];
            })->values());
            window.suratMasukConfig = {
                canCreateSuratMasuk: canCreateSuratMasuk,
                requiresPetunjuk: requiresPetunjuk,
                isKasubagTurt: isKasubagTurt
            };

            const klasifikasiByKode = {};
            const kategoriByKode = {};

            klasifikasiKodeMap.forEach(function (item) {
                klasifikasiByKode[item.kode] = item.id;
            });

            kategoriSuratMap.forEach(function (item) {
                kategoriByKode[item.kode] = item.id;
            });

            function syncKategoriFromKlasifikasi($klasifikasiSelect, $kategoriSelect) {
                const selected = klasifikasiKodeMap.find(function (item) {
                    return String(item.id) === String($klasifikasiSelect.val());
                });

                $kategoriSelect.val(selected ? (kategoriByKode[selected.kode] || '') : '');
            }

            function syncKlasifikasiFromKategori($kategoriSelect, $klasifikasiSelect) {
                const selected = kategoriSuratMap.find(function (item) {
                    return String(item.id) === String($kategoriSelect.val());
                });

                $klasifikasiSelect.val(selected ? (klasifikasiByKode[selected.kode] || '') : '').trigger('change.select2');
            }

            $('#disposisiPetunjukGroup').toggle(requiresPetunjuk);
            $('#disposisiPetunjuk').prop('required', requiresPetunjuk);

            function renderHistory(suratId, targetSelector) {
                const items = suratHistories[suratId] || [];
                if (!items.length) {
                    $(targetSelector).html('<div class="history-empty">Belum ada riwayat surat.</div>');
                    return;
                }

                let html = '';
                items.forEach(function (item) {
                    html += '<div class="history-item">';
                    html += '<div class="d-flex justify-content-between align-items-center flex-wrap">' + item.tipe_badge + item.status_badge + '</div>';
                    if (item.priority_badge || item.target_label) {
                        html += '<div class="history-meta mt-1">' + (item.priority_badge || '') + ' <span class="ml-1">Target: ' + (item.target_label || '-') + '</span></div>';
                    }
                    html += '<div class="history-flow">' + (item.dari || '-') + ' <i class="fas fa-arrow-right mx-1 text-muted"></i> ' + (item.kepada || '-') + '</div>';
                    if (item.jabatan) {
                        html += '<div class="history-meta">' + item.jabatan + '</div>';
                    }
                    if (item.petunjuk) {
                        html += '<div class="history-note"><strong>Petunjuk:</strong> ' + item.petunjuk + '</div>';
                    }
                    if (item.catatan) {
                        html += '<div class="history-note"><strong>Catatan:</strong> ' + item.catatan + '</div>';
                    }
                    if (item.catatan_tindak_lanjut) {
                        html += '<div class="history-note"><strong>Tindak Lanjut:</strong> ' + item.catatan_tindak_lanjut + '</div>';
                    }
                    html += '<div class="history-meta"><i class="fas fa-clock mr-1"></i>' + item.waktu + ' (' + item.waktu_human + ')</div>';
                    html += '</div>';
                });

                $(targetSelector).html(html);
            }

            window.renderSuratHistory = renderHistory;

            var table = $('#suratMasukTable').DataTable({
                order: [],
                pageLength: 10,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries found",
                    emptyTable: '<div class="text-center py-4"><i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity:0.2;color:#9ca3af;"></i><span style="color:#9ca3af;">Tidak ada surat masuk</span></div>',
                    paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
                },
                columnDefs: [
                    { orderable: false, targets: -1 }
                ]
            });

            // Toggle klasifikasi
            $('#opsiPengirim').on('change', function () {
                if ($(this).val() === 'mahkamah_agung') {
                    $('#klasifikasiGroup').slideDown();
                    if (!$('#klasifikasiKode').val() && $('#createKategoriSurat').val()) {
                        syncKlasifikasiFromKategori($('#createKategoriSurat'), $('#klasifikasiKode'));
                    }
                } else {
                    $('#klasifikasiGroup').slideUp();
                    $('#klasifikasiKode').val('').trigger('change');
                }
            });

            $('#klasifikasiKode').on('change', function () {
                syncKategoriFromKlasifikasi($('#klasifikasiKode'), $('#createKategoriSurat'));
            });

            $('#createKategoriSurat').on('change', function () {
                if ($('#opsiPengirim').val() === 'mahkamah_agung') {
                    syncKlasifikasiFromKategori($('#createKategoriSurat'), $('#klasifikasiKode'));
                }
            });

            function toggleCreateAgendaFields() {
                const checked = $('#createAgendaPimpinan').is(':checked');
                if (checked) {
                    $('#createAgendaPimpinanFields').stop(true, true).slideDown();
                } else {
                    $('#createAgendaPimpinanFields').stop(true, true).slideUp();
                }
                $('#createAgendaPimpinanFields .agenda-required').prop('required', checked);
                if (!checked) {
                    $('#createAgendaTempat').val('');
                }
            }

            $('#createAgendaPimpinan').on('change', toggleCreateAgendaFields);
            toggleCreateAgendaFields();

            // Create form
            $('#createForm').on('submit', function (e) {
                e.preventDefault();
                if (!canCreateSuratMasuk) {
                    showToast('Anda tidak memiliki akses untuk menambahkan surat masuk.', 'warning');
                    return;
                }
                let formData = new FormData(this);
                let btn = $('#btnSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

                $.ajax({
                    url: '{{ route("surat-masuk.store") }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#createModal').modal('hide');
                        $('#createForm')[0].reset();
                        $('#klasifikasiGroup').hide();
                        $('#createKategoriSurat').val('');
                        $('#klasifikasiKode').val('').trigger('change');
                        $('#createAgendaPimpinan').prop('checked', false);
                        toggleCreateAgendaFields();
                        location.reload();
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let msg = 'Terjadi kesalahan.';
                        if (errors) msg = Object.values(errors).flat().join('<br>');
                        else if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan');
                    }
                });
            });

            // Edit form
            $('#editForm').on('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);
                let suratId = $('#editSuratId').val();
                let btn = $('#btnEditSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Memperbarui...');

                $.ajax({
                    url: '/surat-masuk/' + suratId,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#editModal').modal('hide');
                        location.reload();
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let msg = 'Terjadi kesalahan.';
                        if (errors) msg = Object.values(errors).flat().join('<br>');
                        else if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui');
                    }
                });
            });

            // Edit opsi pengirim toggle
            $('#editOpsiPengirim').on('change', function () {
                if ($(this).val() === 'mahkamah_agung') {
                    $('#editKlasifikasiGroup').slideDown();
                    if (!$('#editKlasifikasi').val() && $('#editKategoriSurat').val()) {
                        syncKlasifikasiFromKategori($('#editKategoriSurat'), $('#editKlasifikasi'));
                    }
                } else {
                    $('#editKlasifikasiGroup').slideUp();
                    $('#editKlasifikasi').val('');
                }
            });

            $('#editKlasifikasi').on('change', function () {
                syncKategoriFromKlasifikasi($('#editKlasifikasi'), $('#editKategoriSurat'));
            });

            $('#editKategoriSurat').on('change', function () {
                if ($('#editOpsiPengirim').val() === 'mahkamah_agung') {
                    syncKlasifikasiFromKategori($('#editKategoriSurat'), $('#editKlasifikasi'));
                }
            });

            // Disposisi form
            $('#disposisiForm').on('submit', function (e) {
                e.preventDefault();
                let btn = $('#btnDisposisiSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...');

                $.ajax({
                    url: '{{ route("disposisi.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#disposisiModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function (xhr) {
                        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Kirim');
                    }
                });
            });

            $('#tindakLanjutForm').on('submit', function (e) {
                e.preventDefault();
                let disposisiId = $('#tindakLanjutDisposisiId').val();
                let btn = $('#btnTindakLanjutSubmit');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

                $.ajax({
                    url: '/disposisi/' + disposisiId + '/status',
                    method: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: 'ditindaklanjuti',
                        catatan_tindak_lanjut: $('#tindakLanjutCatatan').val()
                    },
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#tindakLanjutModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function (xhr) {
                        let errors = xhr.responseJSON?.errors;
                        let msg = 'Terjadi kesalahan.';
                        if (errors) msg = Object.values(errors).flat().join('<br>');
                        else if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Tindaklanjuti');
                    }
                });
            });

            // Re-init select2 in modal
            $('#createModal').on('shown.bs.modal', function () {
                $(this).find('.select2').select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownParent: $(this)
                });
            });
        });

        function escapeHtml(value) {
            return $('<div>').text(value || '-').html();
        }

        // Open Detail Modal
        function openDetail(suratId) {
            var row = $('tr[data-surat-id="' + suratId + '"]');
            var d = row.data();

            $('#detailNomor').text(d.nomor);
            $('#detailKategoriSurat').text(d.kategoriSuratLabel || '-');
            $('#detailPengirim').text(d.pengirim);
            $('#detailOpsi').text(d.opsiPengirim === 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung');
            $('#detailPerihal').text(d.perihal);
            $('#detailTanggal').text(d.tanggal);
            $('#detailSifat').text(d.sifat);
            $('#detailStatus').text(d.status);
            $('#detailCreator').text(d.creator);
            if (d.agendaTitle) {
                var agendaInfo = '<div class="font-weight-bold">' + escapeHtml(d.agendaTitle) + '</div>';
                agendaInfo += '<div class="text-muted small">' + escapeHtml(d.agendaDate) + ' ' + escapeHtml(d.agendaTime) + ' WIT</div>';
                agendaInfo += '<div class="text-muted small">Tempat: ' + escapeHtml(d.agendaPlace) + '</div>';
                if (d.agendaClothing) {
                    agendaInfo += '<div class="text-muted small">Pakaian: ' + escapeHtml(d.agendaClothing) + '</div>';
                }
                $('#detailAgendaInfo').html(agendaInfo);
                $('#detailAgendaRow').show();
            } else {
                $('#detailAgendaInfo').text('-');
                $('#detailAgendaRow').hide();
            }
            $('#detailDownloadBtn').attr('href', d.downloadUrl);
            $('#detailShowBtn').attr('href', d.showUrl);
            window.renderSuratHistory(String(suratId), '#detailHistory');

            if (d.filePath) {
                $('#detailFileViewer').attr('src', d.previewUrl);
            } else {
                $('#detailFileViewer').attr('src', '');
            }

            $('#detailModal').modal('show');
        }

        // Open Edit Modal
        function openEdit(suratId) {
            var row = $('tr[data-surat-id="' + suratId + '"]');
            var d = row.data();

            if (Number(d.canEdit) !== 1) {
                showToast('Anda tidak memiliki akses untuk mengedit surat ini.', 'warning');
                return;
            }

            $('#editSuratId').val(suratId);
            $('#editNomor').val(d.nomor);
            $('#editOpsiPengirim').val(d.opsiPengirim);
            $('#editKlasifikasi').val(d.klasifikasi);
            $('#editKategoriSurat').val(d.kategoriSurat);
            $('#editPengirim').val(d.pengirim);
            $('#editPerihal').val(d.perihal);
            $('#editTanggal').val(d.tanggal);
            $('#editSifat').val(d.sifat);

            if (d.opsiPengirim === 'mahkamah_agung') {
                $('#editKlasifikasiGroup').show();
                if (!d.klasifikasi && d.kategoriSurat) {
                    $('#editKategoriSurat').trigger('change');
                }
            } else {
                $('#editKlasifikasiGroup').hide();
            }

            $('#editModal').modal('show');
        }

        // Open Disposisi Modal
        function openDisposisi(suratId, tipe) {
            var row = $('tr[data-surat-id="' + suratId + '"]');
            var d = row.data();

            if (Number(d.canForward) !== 1) {
                showToast('Anda tidak memiliki akses untuk memproses surat ini.', 'warning');
                return;
            }

            $('#disposisiForm')[0].reset();
            $('#disposisiSuratId').val(suratId);
            $('#disposisiTipe').val(tipe === 'teruskan' ? 'disposisi' : tipe);
            const requiresPetunjuk = window.suratMasukConfig ? window.suratMasukConfig.requiresPetunjuk : false;
            $('#disposisiPetunjukGroup').toggle(requiresPetunjuk);
            $('#disposisiPetunjuk').prop('required', requiresPetunjuk);

            if (tipe === 'teruskan') {
                $('#disposisiModalTitle').html('<i class="fas fa-share mr-2"></i>Teruskan Surat');
                $('#disposisiFormIcon').removeClass('fa-level-up-alt').addClass('fa-share');
                $('#disposisiFormLabel').text('Form Teruskan');
            } else if (tipe === 'naikan') {
                $('#disposisiModalTitle').html('<i class="fas fa-level-up-alt mr-2"></i>Naikkan Surat');
                $('#disposisiFormIcon').removeClass('fa-share').addClass('fa-level-up-alt');
                $('#disposisiFormLabel').text('Form Naikkan');
            } else {
                $('#disposisiModalTitle').html('<i class="fas fa-share mr-2"></i>Disposisi Surat');
                $('#disposisiFormIcon').removeClass('fa-level-up-alt').addClass('fa-share');
                $('#disposisiFormLabel').text('Form Disposisi');
            }

            // Load file preview
            if (d.filePath && d.previewUrl) {
                $('#disposisiFileViewer').attr('src', d.previewUrl);
            } else {
                $('#disposisiFileViewer').attr('src', '');
            }
            window.renderSuratHistory(String(suratId), '#disposisiHistory');

            // Load targets via API
            $.get('{{ route("api.disposisi.targets") }}', function (res) {
                var options = '<option value="">-- Pilih Tujuan --</option>';
                if (res && res.length) {
                    res.forEach(function (item) {
                        options += '<option value="' + item.id + '">' + item.name + ' (' + (item.jabatan || '-') + ')</option>';
                    });
                }
                $('#disposisiTarget').html(options);
            }).fail(function () {
                $('#disposisiTarget').html('<option value="">Gagal memuat data</option>');
            });

            $('#disposisiModal').modal('show');
        }

        function openTindakLanjut(suratId) {
            var row = $('tr[data-surat-id="' + suratId + '"]');
            var d = row.data();

            if (Number(d.canFollowUp) !== 1 || !d.pendingDisposisiId) {
                showToast('Surat ini belum bisa ditindaklanjuti oleh akun Anda.', 'warning');
                return;
            }

            $('#tindakLanjutForm')[0].reset();
            $('#tindakLanjutDisposisiId').val(d.pendingDisposisiId);
            window.renderSuratHistory(String(suratId), '#tindakLanjutHistory');

            if (d.filePath && d.previewUrl) {
                $('#tindakLanjutFileViewer').attr('src', d.previewUrl);
            } else {
                $('#tindakLanjutFileViewer').attr('src', '');
            }

            $('#tindakLanjutModal').modal('show');
        }

        // Delete Surat
        function deleteSurat(id, url) {
            if (!confirm('Apakah Anda yakin ingin menghapus surat ini? Semua data disposisi juga akan terhapus.')) return;
            $.ajax({
                url: url,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
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
@endpush
