<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $rapat->judul }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.7cm 1.5cm 1.5cm 1.5cm;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10.5pt;
            line-height: 1.28;
        }

        .kop {
            margin-bottom: 12pt;
        }

        .kop img {
            width: 100%;
            display: block;
        }

        .info-table,
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 12pt;
        }

        .info-table td {
            padding: 2.5pt 0;
            vertical-align: top;
        }

        .info-table td:nth-child(1) {
            width: 122pt;
        }

        .info-table td:nth-child(2) {
            width: 12pt;
        }

        .section-title {
            margin: 12pt 0 6pt;
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #64748b;
            padding: 5pt 6pt;
            vertical-align: middle;
        }

        .attendance-table th {
            background: #e2e8f0;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
        }

        .attendance-table td {
            font-size: 9.5pt;
        }

        .text-center {
            text-align: center;
        }

        .signature-image {
            width: 82pt;
            height: 34pt;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .empty {
            text-align: center;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
    @if($kopImage)
        <div class="kop">
            <img src="{{ $kopImage }}" alt="Kop Absensi">
        </div>
    @endif

    <table class="info-table">
        <tr>
            <td>Nama kegiatan</td>
            <td>:</td>
            <td>{{ $rapat->judul ?: '-' }}</td>
        </tr>
        <tr>
            <td>Hari dan Tanggal</td>
            <td>:</td>
            <td>{{ optional($rapat->tanggal)->translatedFormat('l, d F Y') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>:</td>
            <td>{{ $rapat->waktu_mulai_formatted }} WIT s/d Selesai</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{{ $rapat->tempat ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Absensi Peserta Internal</div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width: 28pt;">No</th>
                <th>Nama</th>
                <th>Jabatan / Keterangan</th>
                <th style="width: 74pt;">Status</th>
                <th style="width: 92pt;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($internalParticipants as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item['user']->name }}</td>
                    <td>{{ $item['user']->jabatan_keterangan ?: optional($item['user']->jabatan)->nama ?: '-' }}</td>
                    <td class="text-center">{{ $item['attendance'] ? 'Hadir' : 'Belum Hadir' }}</td>
                    <td class="text-center">
                        @if($item['signature_data_uri'])
                            <img src="{{ $item['signature_data_uri'] }}" alt="Tanda Tangan" class="signature-image">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">Belum ada peserta internal yang terdaftar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($guestAttendances->count() > 0)
        <div class="section-title">Absensi Peserta External</div>
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 28pt;">No</th>
                    <th>Nama</th>
                    <th>Instansi / Keterangan</th>
                    <th style="width: 92pt;">Tanda Tangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($guestAttendances as $index => $attendance)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $attendance->participant_name_snapshot }}</td>
                        <td>{{ $attendance->guest_instansi ?: ($attendance->participant_jabatan_snapshot ?: '-') }}</td>
                        <td class="text-center">
                            @if($attendance->signature_data_uri)
                                <img src="{{ $attendance->signature_data_uri }}" alt="Tanda Tangan" class="signature-image">
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
