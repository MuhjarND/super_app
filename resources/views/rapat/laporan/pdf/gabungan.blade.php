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
        table.meta, table.grid { width: 100%; border-collapse: collapse; }
        table.meta td { padding: 3px 0; vertical-align: top; }
        table.meta td:first-child { width: 130px; }
        table.grid th, table.grid td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        table.grid th { background: #f3f4f6; }
        .paragraph { white-space: pre-line; text-align: justify; }
        .note { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">LAPORAN GABUNGAN RAPAT</div>
        <div class="sub">{{ $rapat->judul }}</div>
    </div>

    <div class="section">
        <div class="section-title">I. Informasi Rapat</div>
        <table class="meta">
            <tr><td>Nomor Undangan</td><td>: {{ $rapat->nomor_undangan }}</td></tr>
            <tr><td>Tanggal</td><td>: {{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Waktu</td><td>: {{ $rapat->waktu_mulai_formatted }} WIT</td></tr>
            <tr><td>Tempat</td><td>: {{ $rapat->tempat }}</td></tr>
            <tr><td>Kategori Surat</td><td>: {{ $rapat->kategori_surat_label }}</td></tr>
            <tr><td>Pembuat</td><td>: {{ optional($rapat->creator)->name ?: '-' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">II. Undangan / Peserta</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Nama Peserta</th>
                    <th>Jabatan / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rapat->pesertas as $index => $peserta)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $peserta->name }}</td>
                        <td>{{ $peserta->jabatan_keterangan ?: optional($peserta->jabatan)->nama ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">III. Rekap Absensi</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width:30px;">No</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Waktu Absen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $attendance)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $attendance->participant_name_snapshot }}</td>
                        <td>{{ ucfirst($attendance->attendance_type) }}</td>
                        <td>{{ $attendance->attended_at ? $attendance->attended_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Belum ada data absensi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">IV. Notulensi</div>
        @if($notulensi && !$notulensi->tidak_membuat_notulen)
            <div class="paragraph"><strong>A. Uraian Kegiatan Rapat</strong><br>{{ $notulensi->uraian_kegiatan }}</div>
            <div class="paragraph" style="margin-top:8px;"><strong>B. Agenda Rapat</strong><br>{{ $notulensi->agenda_rapat }}</div>
            <div class="paragraph" style="margin-top:8px;"><strong>C. Hasil Rapat</strong><br>{{ $notulensi->hasil_rapat }}</div>
            <div class="paragraph" style="margin-top:8px;"><strong>D. Rekomendasi</strong><br>{{ $notulensi->rekomendasi ?: '-' }}</div>
            @if($notulensi->mode === 'upload' && $notulensi->file_nama)
                <div class="note" style="margin-top:8px;">Notulensi final menggunakan file upload: {{ $notulensi->file_nama }}</div>
            @endif
        @else
            <div class="paragraph">Laporan gabungan belum siap karena notulensi final belum tersedia atau rapat ditandai tanpa notulen.</div>
        @endif
    </div>
</body>
</html>
