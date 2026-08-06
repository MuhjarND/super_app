@extends('layouts.app')

@section('title', 'Absensi Rapat')

@push('styles')
    <style>
        .attendance-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
        }

        .attendance-stat {
            border: 1px solid #e8eaed;
            border-radius: 14px;
            padding: 14px 16px;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }

        .attendance-stat__label {
            font-size: 0.76rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .attendance-stat__value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 6px;
        }

        .attendance-table thead th {
            font-size: 0.73rem;
            text-transform: uppercase;
            color: #64748b;
            border-top: none;
        }

        .attendance-table tbody td {
            vertical-align: top;
            font-size: 0.85rem;
        }

        .attendance-action-cell {
            width: 172px;
            vertical-align: middle !important;
        }

        .attendance-action-cell .app-action-group {
            flex-wrap: nowrap;
            justify-content: flex-end;
        }

        .attendance-progress {
            margin-top: 6px;
        }

        .attendance-progress-track {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .attendance-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #4f46e5, #818cf8);
        }

        .attendance-progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 5px;
            font-size: 0.76rem;
            color: #64748b;
        }

        .direct-attendance-modal .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .direct-attendance-modal .modal-header,
        .direct-attendance-modal .modal-footer {
            border-color: #eef2f7;
        }

        .direct-attendance-modal .modal-body {
            max-height: calc(100vh - 190px);
            overflow-y: auto;
        }

        .direct-attendance-participant-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 7px;
        }

        .direct-attendance-participant-head label {
            margin-bottom: 0;
        }

        .direct-attendance-select-all {
            border: 1px solid #c7d2fe;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            padding: 6px 11px;
            font-size: .72rem;
            font-weight: 800;
        }

        .direct-attendance-select-all:hover,
        .direct-attendance-select-all:focus {
            background: #e0e7ff;
            color: #4338ca;
        }

        .direct-attendance-modal .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: 44px;
            max-height: 145px;
            overflow-y: auto;
            padding: 7px 9px;
            border-color: #dbe4f0;
            border-radius: 13px;
        }

        .direct-attendance-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 7px;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .direct-attendance-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice {
            position: relative;
            display: inline-flex;
            align-items: center;
            max-width: 100%;
            margin: 0;
            padding: 5px 11px 5px 29px;
            border: 0;
            border-radius: 999px;
            background: linear-gradient(135deg, #ede9fe, #e0e7ff);
            color: #4f46e5;
            font-size: .75rem;
            font-weight: 800;
            white-space: normal;
        }

        .direct-attendance-modal .select2-container--bootstrap4 .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: #6366f1;
            font-size: .95rem;
        }

        .attendance-source-note {
            border: 1px solid #ddd6fe;
            border-radius: 12px;
            padding: 11px 13px;
            background: #f5f3ff;
            color: #4c1d95;
            font-size: 0.78rem;
        }

        @media (max-width: 767.98px) {
            .attendance-page-heading {
                align-items: stretch !important;
                flex-direction: column;
                gap: 12px;
            }

            .attendance-page-heading .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center attendance-page-heading">
            <div>
                <h1 class="mb-1">Absensi Kegiatan</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Rekap kehadiran dan tautan absensi publik.</div>
            </div>
            @if($canCreateDirectAttendance)
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#directAttendanceModal">
                    <i class="fas fa-plus mr-1"></i> Buat dari Surat Keluar
                </button>
            @endif
        </div>
    </div>
@endsection

@section('content')
    @php
        $totalRapat = $rapats->count();
        $totalPeserta = $rapats->sum(function ($rapat) { return $rapat->pesertas->count(); });
        $totalHadir = $rapats->sum(function ($rapat) { return $rapat->internalAttendances->count(); });
        $totalGuest = $rapats->sum(function ($rapat) { return $rapat->guestAttendances->count(); });
    @endphp

    <div class="attendance-summary mb-3">
        <div class="attendance-stat">
            <div class="attendance-stat__label">Total Kegiatan</div>
            <div class="attendance-stat__value">{{ $totalRapat }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">Peserta PTA</div>
            <div class="attendance-stat__value">{{ $totalPeserta }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">Sudah Absen</div>
            <div class="attendance-stat__value">{{ $totalHadir }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">Satker/External</div>
            <div class="attendance-stat__value">{{ $totalGuest }}</div>
        </div>
    </div>

    <div class="card attendance-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 attendance-table">
                    <thead>
                        <tr>
                            <th>Kegiatan / Dasar</th>
                            <th>Waktu WIT</th>
                            <th>Peserta</th>
                            <th>Hadir</th>
                            <th>External</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rapats as $rapat)
                            @php
                                $participantCount = $rapat->pesertas->count();
                                $attendedCount = $rapat->internalAttendances->count();
                                $guestCount = $rapat->guestAttendances->count();
                                $remainingCount = max($participantCount - $attendedCount, 0);
                                $attendancePercent = $participantCount > 0 ? round(($attendedCount / $participantCount) * 100) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">{{ $rapat->judul }}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">{{ $rapat->nomor_undangan }}</div>
                                    @if($rapat->is_attendance_only)
                                        <span class="badge badge-light mt-1">
                                            <i class="fas fa-envelope-open-text mr-1"></i> Surat Keluar
                                        </span>
                                    @endif
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        Penanda tangan: {{ optional($rapat->attendanceSigner)->name ?: 'Mengikuti penanda tangan rapat' }}
                                    </div>
                                </td>
                                <td>
                                    <div>{{ optional($rapat->tanggal)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                                </td>
                                <td>{{ $participantCount }} peserta</td>
                                <td>
                                    <div class="font-weight-bold">{{ $attendedCount }} / {{ $participantCount }}</div>
                                    <div class="attendance-progress">
                                        <div class="attendance-progress-track">
                                            <div class="attendance-progress-fill" style="width: {{ $attendancePercent }}%;"></div>
                                        </div>
                                        <div class="attendance-progress-meta">
                                            <span>{{ $attendancePercent }}% hadir</span>
                                            <span>{{ $remainingCount }} belum hadir</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $guestCount }}</td>
                                <td>
                                    @if($rapat->is_attendance_only)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        {!! $rapat->status_badge !!}
                                    @endif
                                </td>
                                <td class="app-action-cell attendance-action-cell" data-label="Aksi">
                                    <div class="app-action-group">
                                        <a href="{{ route('rapat.absensi.show', $rapat) }}" class="app-icon-btn detail" data-mobile-label="Rekap" title="Rekap absensi">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                        <a href="{{ route('rapat.absensi.pdf', $rapat) }}" target="_blank" class="app-icon-btn pdf" data-mobile-label="PDF" title="Unduh PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <button type="button" class="app-icon-btn link" data-mobile-label="Link" title="Salin link publik" onclick="copyPublicLink('{{ route('rapat.absensi.public.show', $rapat->public_code) }}')">
                                            <i class="fas fa-link"></i>
                                        </button>
                                        @if($canCreateDirectAttendance && $rapat->is_attendance_only)
                                            <button
                                                type="button"
                                                class="app-icon-btn edit manual-attendance-edit"
                                                data-mobile-label="Edit"
                                                title="Edit absensi manual"
                                                data-toggle="modal"
                                                data-target="#manualAttendanceEditModal"
                                                data-rapat-id="{{ $rapat->id }}"
                                                data-action="{{ route('rapat.absensi.update-direct', $rapat) }}"
                                                data-title="{{ $rapat->judul }}"
                                                data-participant-ids='@json($rapat->pesertas->pluck('id')->map(function ($id) { return (string) $id; })->values())'
                                                data-date="{{ optional($rapat->tanggal)->format('Y-m-d') }}"
                                                data-time="{{ substr((string) $rapat->waktu_mulai, 0, 5) }}"
                                                data-place="{{ $rapat->tempat }}"
                                                data-signer-id="{{ $rapat->attendance_signer_id }}"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @elseif($canCreateDirectAttendance)
                                            <button
                                                type="button"
                                                class="app-icon-btn edit attendance-signer-edit"
                                                data-mobile-label="Edit"
                                                title="Atur penanda tangan absensi"
                                                data-toggle="modal"
                                                data-target="#attendanceSignerModal"
                                                data-action="{{ route('rapat.absensi.update-signer', $rapat) }}"
                                                data-signer-id="{{ $rapat->attendance_signer_id }}"
                                                data-title="{{ $rapat->judul }}"
                                            >
                                                <i class="fas fa-user-edit"></i>
                                            </button>
                                        @endif
                                        @if($canCreateDirectAttendance && $rapat->is_attendance_only)
                                            <form method="POST" action="{{ route('rapat.absensi.destroy-direct', $rapat) }}" class="d-inline" onsubmit="return confirm('Hapus absensi langsung ini beserta seluruh data kehadirannya?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="app-icon-btn delete" data-mobile-label="Hapus" title="Hapus absensi">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada kegiatan untuk absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($canCreateDirectAttendance)
        <div class="modal fade direct-attendance-modal" id="directAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="directAttendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <form action="{{ route('rapat.absensi.store-from-surat-keluar') }}" method="POST" class="modal-content">
                    @csrf
                    <input type="hidden" name="attendance_form" value="create">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="directAttendanceModalLabel">Buat Absensi</h5>
                            <div class="text-muted" style="font-size: 0.78rem;">Gunakan Surat Keluar sebagai dasar kegiatan.</div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any() && old('attendance_form') === 'create')
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="directAttendanceSuratKeluar">Surat Keluar</label>
                            <select
                                name="surat_keluar_id"
                                id="directAttendanceSuratKeluar"
                                class="form-control"
                                required
                            >
                                <option value="">-- Pilih Surat Keluar --</option>
                                @foreach($availableSuratKeluar as $suratKeluar)
                                    <option
                                        value="{{ $suratKeluar->id }}"
                                        data-date="{{ optional($suratKeluar->tanggal_surat)->format('Y-m-d') }}"
                                        data-title="{{ $suratKeluar->perihal }}"
                                        data-participant-ids="{{ $suratKeluar->penerimaInternal->pluck('id')->implode(',') }}"
                                        {{ (string) old('surat_keluar_id') === (string) $suratKeluar->id ? 'selected' : '' }}
                                    >
                                        {{ $suratKeluar->nomor_surat_formatted }} | {{ \Illuminate\Support\Str::limit($suratKeluar->perihal, 90) }}
                                    </option>
                                @endforeach
                            </select>
                            @if($availableSuratKeluar->isEmpty())
                                <small class="form-text text-muted">Tidak ada Surat Keluar yang tersedia.</small>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="directAttendanceTitle">Judul Absensi</label>
                            <input
                                type="text"
                                name="judul"
                                id="directAttendanceTitle"
                                class="form-control"
                                value="{{ old('judul') }}"
                                maxlength="255"
                                placeholder="Contoh: Pembinaan dan Evaluasi Kinerja"
                                required
                            >
                            <small class="form-text text-muted">Judul ini ditampilkan pada halaman absensi publik.</small>
                        </div>

                        @php
                            $oldParticipantIds = collect(old('participant_ids', []))
                                ->map(function ($id) {
                                    return (string) $id;
                                })
                                ->all();
                        @endphp
                        <div class="form-group">
                            <div class="direct-attendance-participant-head">
                                <label for="directAttendanceParticipants">Peserta</label>
                                <button type="button" class="direct-attendance-select-all" id="directAttendanceSelectAll">
                                    Pilih semua
                                </button>
                            </div>
                            <select
                                name="participant_ids[]"
                                id="directAttendanceParticipants"
                                class="form-control"
                                multiple
                                required
                            >
                                @foreach($attendanceParticipants as $attendanceParticipant)
                                    <option
                                        value="{{ $attendanceParticipant->id }}"
                                        {{ in_array((string) $attendanceParticipant->id, $oldParticipantIds, true) ? 'selected' : '' }}
                                    >
                                        {{ $attendanceParticipant->name }}
                                        @if($attendanceParticipant->jabatan_keterangan)
                                            - {{ $attendanceParticipant->jabatan_keterangan }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Penerima internal surat dipilih otomatis dan masih dapat disesuaikan.</small>
                        </div>

                        <div class="form-group">
                            <label for="directAttendanceSigner">Pejabat Penanda Tangan Absensi</label>
                            <select name="attendance_signer_id" id="directAttendanceSigner" class="form-control" required>
                                <option value="">-- Pilih Pejabat --</option>
                                @foreach($attendanceParticipants as $attendanceOfficial)
                                    <option
                                        value="{{ $attendanceOfficial->id }}"
                                        {{ (string) old('attendance_signer_id') === (string) $attendanceOfficial->id ? 'selected' : '' }}
                                    >
                                        {{ $attendanceOfficial->name }}{{ $attendanceOfficial->jabatan_keterangan ? ' - ' . $attendanceOfficial->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Tanda tangan yang dipakai adalah tanda tangan pejabat saat mengisi absensi.</small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="directAttendanceDate">Tanggal Kegiatan</label>
                                <input
                                    type="date"
                                    name="tanggal"
                                    id="directAttendanceDate"
                                    class="form-control"
                                    value="{{ old('tanggal') }}"
                                    required
                                >
                            </div>
                            <div class="form-group col-md-4">
                                <label for="directAttendanceTime">Waktu</label>
                                <input
                                    type="time"
                                    name="waktu_mulai"
                                    id="directAttendanceTime"
                                    class="form-control"
                                    value="{{ old('waktu_mulai') }}"
                                    required
                                >
                            </div>
                            <div class="form-group col-md-4">
                                <label for="directAttendancePlace">Tempat</label>
                                <input
                                    type="text"
                                    name="tempat"
                                    id="directAttendancePlace"
                                    class="form-control"
                                    value="{{ old('tempat') }}"
                                    maxlength="255"
                                    required
                                >
                            </div>
                        </div>

                        <div class="attendance-source-note">
                            Peserta terpilih masuk ke daftar PTA Papua Barat. Peserta Satker/External dapat mengisi melalui tautan absensi publik.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" {{ $availableSuratKeluar->isEmpty() ? 'disabled' : '' }}>
                            <i class="fas fa-check mr-1"></i> Buat Absensi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade direct-attendance-modal" id="manualAttendanceEditModal" tabindex="-1" role="dialog" aria-labelledby="manualAttendanceEditModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <form action="#" method="POST" class="modal-content" id="manualAttendanceEditForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="attendance_form" value="manual">
                    <input type="hidden" name="rapat_id" id="manualAttendanceRapatId" value="">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="manualAttendanceEditModalLabel">Edit Absensi Manual</h5>
                            <div class="text-muted" style="font-size: 0.78rem;">Perbarui kegiatan, peserta, dan penanda tangan.</div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if($errors->any() && old('attendance_form') === 'manual')
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <div class="form-group">
                            <label for="manualAttendanceTitle">Judul Absensi</label>
                            <input type="text" name="judul" id="manualAttendanceTitle" class="form-control" maxlength="255" required>
                        </div>

                        <div class="form-group">
                            <div class="direct-attendance-participant-head">
                                <label for="manualAttendanceParticipants">Peserta</label>
                                <button type="button" class="direct-attendance-select-all" id="manualAttendanceSelectAll">Pilih semua</button>
                            </div>
                            <select name="participant_ids[]" id="manualAttendanceParticipants" class="form-control" multiple required>
                                @foreach($attendanceParticipants as $attendanceParticipant)
                                    <option value="{{ $attendanceParticipant->id }}">
                                        {{ $attendanceParticipant->name }}{{ $attendanceParticipant->jabatan_keterangan ? ' - ' . $attendanceParticipant->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Peserta yang sudah melakukan absensi tetap dipertahankan otomatis.</small>
                        </div>

                        <div class="form-group">
                            <label for="manualAttendanceSigner">Pejabat Penanda Tangan</label>
                            <select name="attendance_signer_id" id="manualAttendanceSigner" class="form-control" required>
                                <option value="">-- Pilih Pejabat --</option>
                                @foreach($attendanceParticipants as $attendanceOfficial)
                                    <option value="{{ $attendanceOfficial->id }}">
                                        {{ $attendanceOfficial->name }}{{ $attendanceOfficial->jabatan_keterangan ? ' - ' . $attendanceOfficial->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="manualAttendanceDate">Tanggal</label>
                                <input type="date" name="tanggal" id="manualAttendanceDate" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="manualAttendanceTime">Waktu</label>
                                <input type="time" name="waktu_mulai" id="manualAttendanceTime" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="manualAttendancePlace">Tempat</label>
                                <input type="text" name="tempat" id="manualAttendancePlace" class="form-control" maxlength="255" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade direct-attendance-modal" id="attendanceSignerModal" tabindex="-1" role="dialog" aria-labelledby="attendanceSignerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form action="#" method="POST" class="modal-content" id="attendanceSignerForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="attendance_form" value="signer">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="attendanceSignerModalLabel">Penanda Tangan Absensi</h5>
                            <div class="text-muted" id="attendanceSignerMeetingTitle" style="font-size: 0.78rem;"></div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="attendanceSignerSelect">Pejabat</label>
                            <select name="attendance_signer_id" id="attendanceSignerSelect" class="form-control" required>
                                <option value="">-- Pilih Pejabat --</option>
                                @foreach($attendanceParticipants as $attendanceOfficial)
                                    <option value="{{ $attendanceOfficial->id }}">
                                        {{ $attendanceOfficial->name }}{{ $attendanceOfficial->jabatan_keterangan ? ' - ' . $attendanceOfficial->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Pejabat otomatis ditambahkan sebagai peserta. Tanda tangannya muncul di PDF setelah melakukan absensi.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        function copyPublicLink(url) {
            navigator.clipboard.writeText(url).then(function () {
                showToast('Tautan absensi publik berhasil disalin.', 'success');
            }).catch(function () {
                showToast('Gagal menyalin tautan absensi publik.', 'error');
            });
        }

        $(function () {
            const modal = $('#directAttendanceModal');
            const suratSelect = $('#directAttendanceSuratKeluar');
            const participantSelect = $('#directAttendanceParticipants');
            const participantSelectAll = $('#directAttendanceSelectAll');
            const directSignerSelect = $('#directAttendanceSigner');
            const manualModal = $('#manualAttendanceEditModal');
            const manualParticipants = $('#manualAttendanceParticipants');
            const manualSigner = $('#manualAttendanceSigner');
            const manualSelectAll = $('#manualAttendanceSelectAll');
            const signerModal = $('#attendanceSignerModal');
            const signerSelect = $('#attendanceSignerSelect');
            let preserveOldParticipants = {{ old('participant_ids') !== null ? 'true' : 'false' }};

            modal.on('shown.bs.modal', function () {
                if ($.fn.select2 && !suratSelect.hasClass('select2-hidden-accessible')) {
                    suratSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: modal,
                        placeholder: '-- Pilih Surat Keluar --'
                    });
                }
                if ($.fn.select2 && !participantSelect.hasClass('select2-hidden-accessible')) {
                    participantSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: modal,
                        placeholder: 'Cari dan pilih peserta',
                        closeOnSelect: false
                    });
                }
                if ($.fn.select2 && !directSignerSelect.hasClass('select2-hidden-accessible')) {
                    directSignerSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: modal,
                        placeholder: '-- Pilih Pejabat --'
                    });
                }
                suratSelect.trigger('change');
            });

            signerModal.on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                $('#attendanceSignerForm').attr('action', button.data('action'));
                $('#attendanceSignerMeetingTitle').text(button.data('title') || '');
                signerSelect.val(String(button.data('signer-id') || '')).trigger('change');
            });

            signerModal.on('shown.bs.modal', function () {
                if ($.fn.select2 && !signerSelect.hasClass('select2-hidden-accessible')) {
                    signerSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: signerModal,
                        placeholder: '-- Pilih Pejabat --'
                    });
                }
            });

            function manualParticipantIds(button) {
                const raw = button.attr('data-participant-ids') || '[]';
                try {
                    return JSON.parse(raw).map(String);
                } catch (error) {
                    return [];
                }
            }

            function refreshManualParticipantToggle() {
                const selectedCount = (manualParticipants.val() || []).length;
                const optionCount = manualParticipants.find('option:not(:disabled)').length;
                manualSelectAll.text(optionCount > 0 && selectedCount === optionCount ? 'Kosongkan' : 'Pilih semua');
            }

            manualModal.on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                $('#manualAttendanceEditForm').attr('action', button.data('action'));
                $('#manualAttendanceRapatId').val(button.data('rapat-id'));
                $('#manualAttendanceTitle').val(button.data('title') || '');
                $('#manualAttendanceDate').val(button.data('date') || '');
                $('#manualAttendanceTime').val(button.data('time') || '');
                $('#manualAttendancePlace').val(button.data('place') || '');
                manualParticipants.val(manualParticipantIds(button)).trigger('change');
                manualSigner.val(String(button.data('signer-id') || '')).trigger('change');
            });

            manualModal.on('shown.bs.modal', function () {
                if ($.fn.select2 && !manualParticipants.hasClass('select2-hidden-accessible')) {
                    manualParticipants.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: manualModal,
                        placeholder: 'Cari dan pilih peserta',
                        closeOnSelect: false
                    });
                }
                if ($.fn.select2 && !manualSigner.hasClass('select2-hidden-accessible')) {
                    manualSigner.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: manualModal,
                        placeholder: '-- Pilih Pejabat --'
                    });
                }
                refreshManualParticipantToggle();
            });

            manualParticipants.on('change', refreshManualParticipantToggle);
            manualSelectAll.on('click', function () {
                const allValues = manualParticipants.find('option:not(:disabled)').map(function () {
                    return String(this.value);
                }).get();
                const selectedValues = (manualParticipants.val() || []).map(String);
                manualParticipants
                    .val(allValues.length > 0 && selectedValues.length === allValues.length ? [] : allValues)
                    .trigger('change');
            });
            manualSigner.on('change', function () {
                const signerId = String($(this).val() || '');
                if (!signerId) {
                    return;
                }
                const participantIds = (manualParticipants.val() || []).map(String);
                if (!participantIds.includes(signerId)) {
                    participantIds.push(signerId);
                    manualParticipants.val(participantIds).trigger('change');
                }
            });

            function refreshParticipantToggle() {
                const selectedCount = (participantSelect.val() || []).length;
                const optionCount = participantSelect.find('option:not(:disabled)').length;
                participantSelectAll.text(optionCount > 0 && selectedCount === optionCount ? 'Kosongkan' : 'Pilih semua');
            }

            suratSelect.on('change', function () {
                const selected = this.options[this.selectedIndex];
                const dateInput = document.getElementById('directAttendanceDate');
                const titleInput = document.getElementById('directAttendanceTitle');
                if (selected && selected.dataset.date && !dateInput.value) {
                    dateInput.value = selected.dataset.date;
                }
                if (selected && selected.dataset.title && !titleInput.value) {
                    titleInput.value = selected.dataset.title;
                }
                if (preserveOldParticipants) {
                    preserveOldParticipants = false;
                    refreshParticipantToggle();
                    return;
                }

                const participantIds = selected && selected.dataset.participantIds
                    ? selected.dataset.participantIds.split(',').filter(Boolean)
                    : [];
                participantSelect.val(participantIds).trigger('change');
            });

            participantSelect.on('change', refreshParticipantToggle);
            participantSelectAll.on('click', function () {
                const allValues = participantSelect.find('option:not(:disabled)').map(function () {
                    return String(this.value);
                }).get();
                const selectedValues = (participantSelect.val() || []).map(String);
                participantSelect
                    .val(allValues.length > 0 && selectedValues.length === allValues.length ? [] : allValues)
                    .trigger('change');
            });
            refreshParticipantToggle();

            @if($errors->any() && old('attendance_form') === 'create')
                modal.modal('show');
            @endif

            @if($errors->any() && old('attendance_form') === 'manual' && old('rapat_id'))
                const failedManualButton = $('.manual-attendance-edit[data-rapat-id="{{ old('rapat_id') }}"]').first();
                if (failedManualButton.length) {
                    manualModal.one('shown.bs.modal', function () {
                        $('#manualAttendanceTitle').val(@json(old('judul')));
                        $('#manualAttendanceDate').val(@json(old('tanggal')));
                        $('#manualAttendanceTime').val(@json(old('waktu_mulai')));
                        $('#manualAttendancePlace').val(@json(old('tempat')));
                        manualParticipants.val(@json(collect(old('participant_ids', []))->map(function ($id) { return (string) $id; })->values())).trigger('change');
                        manualSigner.val(String(@json(old('attendance_signer_id')) || '')).trigger('change');
                    });
                    failedManualButton.trigger('click');
                }
            @endif
        });
    </script>
@endpush
