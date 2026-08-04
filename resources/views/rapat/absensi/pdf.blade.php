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

        .attendance-table {
            table-layout: fixed;
        }

        .attendance-table tr {
            page-break-inside: avoid;
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
            padding: 4.5pt 5pt;
            vertical-align: middle;
        }

        .attendance-table th {
            background: #e2e8f0;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
        }

        .attendance-table td {
            font-size: 9pt;
        }

        .text-center {
            text-align: center;
        }

        .empty {
            text-align: center;
            color: #64748b;
            font-style: italic;
        }

        .participant-signature {
            width: 70pt;
            height: 34pt;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }

        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16pt;
            page-break-inside: avoid;
        }

        .ttd-table td {
            vertical-align: top;
        }

        .ttd-box {
            width: 240pt;
            margin-left: auto;
            text-align: center;
            page-break-inside: avoid;
        }

        .official-attendance-signature {
            width: 112pt;
            height: 50pt;
            margin: 5pt auto 3pt;
            text-align: center;
        }

        .official-attendance-signature img {
            width: 112pt;
            height: 50pt;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }

        .signature-pending {
            height: 42pt;
            padding-top: 16pt;
            color: #64748b;
            font-size: 8pt;
            font-style: italic;
        }

        .nama-ttd {
            font-weight: bold;
            font-size: 10.5pt;
            position: relative;
            z-index: 1;
        }

        .waktu-approval {
            margin-top: 2pt;
            color: #475569;
            font-size: 8pt;
        }
    </style>
</head>
<body>
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

    <div class="section-title">Daftar Hadir Peserta</div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width: 28pt;">No</th>
                <th style="width: 34%;">Nama</th>
                <th>Jabatan / Keterangan</th>
                <th style="width: 84pt;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceRows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-center">
                        @if(!empty($row['signature']))
                            <img src="{{ $row['signature'] }}" alt="Tanda tangan {{ $row['name'] }}" class="participant-signature">
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">Belum ada data peserta absensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($hasAttendanceSigner)
        <table class="ttd-table">
            <tr>
                <td style="width: 52%;"></td>
                <td>
                    <div class="ttd-box">
                        <div>{{ $attendanceSignature['line1'] ?? 'Pejabat Penanda Tangan,' }}</div>
                        <div><strong>{{ $attendanceSignature['line2'] ?? 'Pengadilan Tinggi Agama Papua Barat' }}</strong></div>
                        @if($hasAttendanceSignature)
                            <div class="official-attendance-signature">
                                <img src="{{ $attendanceSignature['image'] }}" alt="Tanda tangan pejabat absensi">
                            </div>
                        @else
                            <div class="signature-pending">Belum melakukan absensi</div>
                        @endif
                        <div class="nama-ttd">{{ $attendanceSignature['name'] ?? '-' }}</div>
                        @if(!empty($attendanceSignature['nip']))
                            <div>NIP. {{ $attendanceSignature['nip'] }}</div>
                        @endif
                        @if(!empty($attendanceSignature['signed_at']))
                            <div class="waktu-approval">
                                Tanda tangan kehadiran pada
                                {{ $attendanceSignature['signed_at']->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') }} WIT
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    @endif
    @include('partials.pdf-verification-badge', [
        'pdfVerification' => $pdfVerification ?? null,
        'pdfVerificationInFlow' => true,
    ])
</body>
</html>
