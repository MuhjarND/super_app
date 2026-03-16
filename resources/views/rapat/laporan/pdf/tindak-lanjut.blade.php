<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $laporan->judul }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; margin: 30px 34px; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 18px; }
        .header-title { font-size: 16px; font-weight: bold; }
        .sub { font-size: 11px; margin-top: 4px; }
        .section { margin-top: 16px; }
        .section-title { font-size: 12px; font-weight: bold; border-bottom: 1px solid #111827; padding-bottom: 4px; margin-bottom: 8px; }
        .paragraph { white-space: pre-line; text-align: justify; }
        table.meta { width: 100%; border-collapse: collapse; }
        table.meta td { padding: 3px 0; vertical-align: top; }
        table.meta td:first-child { width: 130px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">LAPORAN TINDAK LANJUT RAPAT</div>
        <div class="sub">{{ $rapat->judul }}</div>
    </div>

    <table class="meta">
        <tr><td>Nomor Undangan</td><td>: {{ $rapat->nomor_undangan }}</td></tr>
        <tr><td>Tanggal Rapat</td><td>: {{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Tempat</td><td>: {{ $rapat->tempat }}</td></tr>
        <tr><td>Notulis</td><td>: {{ optional(optional($notulensi)->notulis)->name ?: '-' }}</td></tr>
    </table>

    <div class="section">
        <div class="section-title">Ringkasan Hasil Rapat</div>
        <div class="paragraph">{{ optional($notulensi)->hasil_rapat ?: 'Belum ada ringkasan hasil rapat.' }}</div>
    </div>

    <div class="section">
        <div class="section-title">Rekomendasi / Tindak Lanjut</div>
        <div class="paragraph">{{ optional($notulensi)->rekomendasi ?: 'Belum ada rekomendasi tindak lanjut yang dicatat.' }}</div>
    </div>

    @if(optional($notulensi)->catatan)
        <div class="section">
            <div class="section-title">Catatan Tambahan</div>
            <div class="paragraph">{{ $notulensi->catatan }}</div>
        </div>
    @endif
</body>
</html>
