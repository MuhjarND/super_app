@extends('layouts.app')

@section('title', 'Rekap Absensi Kegiatan')

@push('styles')
    <style>
        .attendance-detail-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .attendance-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .attendance-info-box {
            border: 1px solid #e8eaed;
            border-radius: 14px;
            padding: 12px 14px;
            background: #fff;
        }

        .attendance-signature {
            width: 96px;
            height: 44px;
            object-fit: contain;
            display: block;
        }

    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">Rekap Absensi</h1>
                <div class="text-muted" style="font-size: 0.82rem;">{{ $rapat->judul }} | {{ $rapat->nomor_undangan }}</div>
                @if($rapat->is_attendance_only)
                    <span class="badge badge-light mt-2">
                        <i class="fas fa-envelope-open-text mr-1"></i> Berdasarkan Surat Keluar
                    </span>
                @endif
            </div>
            <div class="text-right">
                @if($canManageAttendance)
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#attendanceSignerModal">
                        <i class="fas fa-user-edit mr-1"></i> Edit Penanda Tangan
                    </button>
                @endif
                <a href="{{ route('rapat.absensi.pdf', $rapat) }}" target="_blank" class="btn btn-outline-danger btn-sm">PDF Absensi</a>
                <a href="{{ route('rapat.absensi.public.show', $rapat->public_code) }}" target="_blank" class="btn btn-outline-primary btn-sm">Buka Link Publik</a>
                <div class="text-muted mt-2" style="font-size: 0.75rem;">{{ $publicAttendanceUrl }}</div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="attendance-info-grid mb-3">
        @if(auth()->user()->canManageRapat() || auth()->user()->canManageMeetingMinutes())
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Tanggal</div>
            <div class="font-weight-bold">{{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</div>
        </div>
        @endif
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Waktu</div>
            <div class="font-weight-bold">{{ $rapat->waktu_mulai_formatted }} WIT</div>
        </div>
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Tempat</div>
            <div class="font-weight-bold">{{ $rapat->tempat }}</div>
        </div>
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Kehadiran Internal</div>
            <div class="font-weight-bold">{{ $rapat->internalAttendances->count() }} / {{ $rapat->pesertas->count() }}</div>
        </div>
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Satker/External</div>
            <div class="font-weight-bold">{{ $guestAttendances->count() }}</div>
        </div>
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Penanda Tangan Absensi</div>
            <div class="font-weight-bold">{{ $attendanceSignature['name'] ?? '-' }}</div>
            <div class="mt-1" style="font-size: 0.75rem;">
                @if(!empty($attendanceSignature['available']))
                    <span class="badge badge-success">Sudah tanda tangan</span>
                @else
                    <span class="badge badge-warning">Belum melakukan absensi</span>
                @endif
            </div>
        </div>
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Reminder WA</div>
            <form action="{{ route('rapat.absensi.remind', $rapat) }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Kirim pengingat absensi ke peserta yang belum absen?')">Kirim Pengingat</button>
            </form>
        </div>
    </div>

    <div class="card attendance-detail-card mb-3">
        <div class="card-header bg-white">
            <strong>Peserta Undangan</strong>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jabatan / Keterangan</th>
                        <th>Status</th>
                        <th>Waktu Absen</th>
                        <th>Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internalParticipants as $item)
                        <tr>
                            <td>{{ $item['user']->name }}</td>
                            <td>{{ $item['user']->jabatan_keterangan ?: optional($item['user']->jabatan)->nama ?: '-' }}</td>
                            <td>
                                @if($item['attendance'])
                                    <span class="badge badge-success">Hadir</span>
                                @else
                                    <span class="badge badge-danger">Belum Absen</span>
                                @endif
                            </td>
                            <td>{{ $item['attendance'] ? $item['attendance']->attended_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</td>
                            <td>
                                @if($item['attendance'] && $item['attendance']->signature_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['attendance']->signature_path) }}" target="_blank">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['attendance']->signature_path) }}"
                                            alt="Tanda tangan {{ $item['user']->name }}"
                                            class="attendance-signature"
                                        >
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card attendance-detail-card">
        <div class="card-header bg-white">
            <strong>Peserta Satker/External</strong>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Instansi / Jabatan</th>
                        <th>Waktu Absen</th>
                        <th>Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($guestAttendances as $attendance)
                        <tr>
                            <td>{{ $attendance->participant_name_snapshot }}</td>
                            <td>{{ $attendance->guest_instansi ?: '-' }}</td>
                            <td>{{ $attendance->attended_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') }} WIT</td>
                            <td>
                                @if($attendance->signature_path)
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attendance->signature_path) }}" target="_blank">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attendance->signature_path) }}"
                                            alt="Tanda tangan {{ $attendance->participant_name_snapshot }}"
                                            class="attendance-signature"
                                        >
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada peserta external yang mengisi absensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManageAttendance)
        <div class="modal fade" id="attendanceSignerModal" tabindex="-1" role="dialog" aria-labelledby="attendanceSignerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form action="{{ route('rapat.absensi.update-signer', $rapat) }}" method="POST" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="attendanceSignerModalLabel">Penanda Tangan Absensi</h5>
                            <div class="text-muted" style="font-size: 0.78rem;">{{ $rapat->judul }}</div>
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
                                @foreach($attendanceOfficials as $attendanceOfficial)
                                    <option
                                        value="{{ $attendanceOfficial->id }}"
                                        {{ (string) $rapat->attendance_signer_id === (string) $attendanceOfficial->id ? 'selected' : '' }}
                                    >
                                        {{ $attendanceOfficial->name }}{{ $attendanceOfficial->jabatan_keterangan ? ' - ' . $attendanceOfficial->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Pejabat otomatis menjadi peserta. PDF memakai tanda tangan yang dibuat saat pejabat melakukan absensi.
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
        $(function () {
            const modal = $('#attendanceSignerModal');
            const signerSelect = $('#attendanceSignerSelect');

            modal.on('shown.bs.modal', function () {
                if ($.fn.select2 && !signerSelect.hasClass('select2-hidden-accessible')) {
                    signerSelect.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: modal,
                        placeholder: '-- Pilih Pejabat --'
                    });
                }
            });
        });
    </script>
@endpush
