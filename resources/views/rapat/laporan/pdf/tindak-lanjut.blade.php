<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $laporan->judul }}</title>
    <style>
        @page {
            size: A4;
            margin: 2.2cm 2.2cm 2.4cm 2.2cm;
        }

        body {
            font-family: DejaVu Serif, serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.6;
        }

        .cover-page {
            page-break-after: always;
            text-align: center;
            padding-top: 120px;
        }

        .cover-logo {
            width: 120px;
            height: auto;
            margin: 0 auto 26px;
        }

        .cover-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.45;
            margin-bottom: 16px;
        }

        .cover-subtitle {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.55;
        }

        .chapter-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin: 0 0 14px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 14px 0 8px;
        }

        .paragraph,
        .html-content {
            text-align: justify;
            margin: 0 0 8px;
        }

        .html-content p {
            margin: 0 0 8px;
        }

        .html-content ol,
        .html-content ul {
            margin: 0 0 8px 18px;
            padding: 0;
        }

        .html-content li {
            margin: 0 0 4px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .meta-table td:first-child {
            width: 140px;
            font-weight: bold;
        }

        a {
            color: #1d4ed8;
            text-decoration: underline;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="cover-page">
        @if($coverLogo)
            <img src="{{ $coverLogo }}" alt="Logo" class="cover-logo">
        @endif
        <div class="cover-title">Laporan Tindak Lanjut</div>
        <div class="cover-subtitle">{{ $rapat->judul }}</div>
    </div>

    <div class="chapter-title">Bab I Pendahuluan</div>

    <table class="meta-table">
        <tr>
            <td>Judul Kegiatan</td>
            <td>: {{ $rapat->judul }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ optional($rapat->tanggal)->translatedFormat('d F Y') ?: '-' }}</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>: {{ $rapat->tempat ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">I. Latar Belakang</div>
    <div class="html-content">
        {!! $bab1LatarBelakang ?: '<p>-</p>' !!}
    </div>

    <div class="section-title">II. Dasar</div>
    <div class="html-content">
        {!! $bab1Dasar ?: '<p>-</p>' !!}
    </div>

    <div class="section-title">III. Tujuan</div>
    <div class="html-content">
        {!! $bab1Tujuan ?: '<p>-</p>' !!}
    </div>

    <div style="page-break-before: always;"></div>
    <div class="chapter-title">Bab II Hasil Monitoring dan Evaluasi</div>
    <div class="html-content">
        {!! $bab2HasilMonitoring ?: '<p>-</p>' !!}
    </div>

    <div style="page-break-before: always;"></div>
    <div class="chapter-title">Bab III Tindak Lanjut dan Rekomendasi</div>
    <div class="html-content">
        {!! $bab3TindakLanjut ?: '<p>-</p>' !!}
    </div>
</body>
</html>
