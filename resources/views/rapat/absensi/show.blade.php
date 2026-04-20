@extends('layouts.app')

@section('title', 'Rekap Absensi Rapat')

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
            width: 180px;
            max-width: 100%;
            border: 1px solid #e8eaed;
            border-radius: 10px;
            background: #fff;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">Rekap Absensi</h1>
                <div class="text-muted" style="font-size: 0.82rem;">{{ $rapat->judul }} | {{ $rapat->nomor_undangan }}</div>
            </div>
            <div class="text-right">
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
        <div class="attendance-info-box">
            <div class="text-muted" style="font-size: 0.75rem;">Tanggal</div>
            <div class="font-weight-bold">{{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</div>
        </div>
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
            <div class="text-muted" style="font-size: 0.75rem;">External</div>
            <div class="font-weight-bold">{{ $guestAttendances->count() }}</div>
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
                                @if($item['attendance'])
                                    <a href="{{ route('rapat.absensi.signature', $item['attendance']) }}" target="_blank">
                                        <img src="{{ route('rapat.absensi.signature', $item['attendance']) }}" alt="Signature" class="attendance-signature">
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
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
            <strong>Peserta External</strong>
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
                                <a href="{{ route('rapat.absensi.signature', $attendance) }}" target="_blank">
                                    <img src="{{ route('rapat.absensi.signature', $attendance) }}" alt="Signature" class="attendance-signature">
                                </a>
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
@endsection
