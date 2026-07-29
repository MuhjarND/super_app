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
                        @if($errors->any())
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
                                        {{ (string) old('surat_keluar_id') === (string) $suratKeluar->id ? 'selected' : '' }}
                                    >
                                        {{ $suratKeluar->nomor_surat_formatted }} | {{ \Illuminate\Support\Str::limit($suratKeluar->perihal, 90) }}
                                    </option>
                                @endforeach
                            </select>
                            @if($availableSuratKeluar->isEmpty())
                                <small class="form-text text-muted">Tidak ada Surat Keluar yang tersedia atau seluruhnya sudah digunakan.</small>
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
                            Penerima internal Surat Keluar otomatis menjadi peserta PTA Papua Barat. Peserta Satker/External dapat mengisi melalui tautan absensi publik.
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

            modal.on('shown.bs.modal', function () {
                if ($.fn.select2 && !suratSelect.hasClass('select2-hidden-accessible')) {
                    suratSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: modal,
                        placeholder: '-- Pilih Surat Keluar --'
                    });
                }
                suratSelect.trigger('change');
            });

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
            });

            @if($errors->any())
                modal.modal('show');
            @endif
        });
    </script>
@endpush
