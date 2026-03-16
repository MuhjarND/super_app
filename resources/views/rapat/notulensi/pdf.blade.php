<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulensi Rapat</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.55;
            margin: 34px 38px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .meta-table td:first-child {
            width: 120px;
        }

        .section {
            margin-top: 14px;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .paragraph {
            white-space: pre-line;
            text-align: justify;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">NOTULENSI RAPAT</div>
        <div>{{ $notulensi->judul ?: $rapat->judul }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td>Nomor Undangan</td>
            <td>: {{ $rapat->nomor_undangan }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>: {{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td>Waktu</td>
            <td>: {{ $rapat->waktu_mulai_formatted }} WIT</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>: {{ $rapat->tempat }}</td>
        </tr>
        <tr>
            <td>Kategori</td>
            <td>: {{ $rapat->kategori_surat_label }}</td>
        </tr>
        <tr>
            <td>Notulis</td>
            <td>: {{ optional($notulensi->notulis)->name ?: '-' }}</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">A. Uraian Kegiatan Rapat</div>
        <div class="paragraph">{{ $notulensi->uraian_kegiatan }}</div>
    </div>

    <div class="section">
        <div class="section-title">B. Agenda Rapat</div>
        <div class="paragraph">{{ $notulensi->agenda_rapat }}</div>
    </div>

    <div class="section">
        <div class="section-title">C. Susunan Agenda Rapat</div>
        <div class="paragraph">{{ $notulensi->susunan_agenda ?: '-' }}</div>
    </div>

    <div class="section">
        <div class="section-title">D. Hasil Rapat</div>
        <div class="paragraph">{{ $notulensi->hasil_rapat }}</div>
    </div>

    <div class="section">
        <div class="section-title">E. Rekomendasi</div>
        <div class="paragraph">{{ $notulensi->rekomendasi ?: '-' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Dokumentasi Rapat</div>
        <div class="paragraph">{{ $notulensi->dokumentasi_rapat ?: '-' }}</div>
    </div>

    @if($notulensi->catatan)
        <div class="section">
            <div class="section-title">Catatan</div>
            <div class="paragraph">{{ $notulensi->catatan }}</div>
        </div>
    @endif
</body>
</html>
