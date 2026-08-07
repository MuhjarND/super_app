<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi</title>
    <style>
        @page { size: A4 portrait; margin: 8mm 9mm 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.2pt;
            line-height: 1.18;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: .75pt solid #111; padding: 3.2pt 4pt; vertical-align: top; }
        .sheet { border: .9pt solid #111; }
        .header td { height: 62pt; vertical-align: middle; }
        .logo-cell { text-align: center; }
        .logo { width: 52pt; height: 52pt; object-fit: contain; }
        .institution { text-align: center; font-size: 13pt; font-weight: bold; line-height: 1.28; }
        .title { padding: 3pt; text-align: center; font-family: DejaVu Serif, serif; font-size: 15pt; }
        .notice { padding: 3pt; text-align: center; font-size: 7.7pt; font-weight: bold; }
        .detail-lines { border: 0; table-layout: fixed; }
        .detail-lines td { border: 0; height: auto; padding: 0 0 2pt; vertical-align: top; }
        .detail-lines .detail-label { width: 43%; }
        .detail-lines .detail-colon { width: 9pt; text-align: center; }
        .detail-lines .letter-number { font-size: 7.4pt; word-break: break-all; }
        .meta-cell { height: 51pt; }
        .sender-cell { height: 38pt; }
        .priority td { padding: 5pt; text-align: center; font-size: 9.5pt; }
        .box { font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
        .section-title { margin-bottom: 4pt; font-weight: bold; text-decoration: underline; }
        .work-cell { height: 278pt; }
        .recipient-list { margin: 0; padding: 0; list-style: none; }
        .recipient-list li { margin: 0 0 6pt; }
        .recipient-name { margin-left: 16pt; color: #333; font-size: 7.4pt; }
        .instruction { width: 100%; border: 0; }
        .instruction td { border: 0; padding: 0 0 3.5pt; vertical-align: top; }
        .instruction .check { width: 16pt; font-size: 9pt; }
        .note { margin-top: 12pt; white-space: pre-wrap; }
        .process td { height: 43pt; font-size: 7.5pt; }
        .process-line { margin-top: 10pt; }
        .muted { color: #444; }
        .verification-wrap { margin-top: 3pt; }
    </style>
</head>
<body>
    @php
        $sifatLabels = [
            'biasa' => 'Biasa',
            'rahasia' => 'Rahasia',
            'sangat_rahasia' => 'Sangat Rahasia',
        ];
        $statusLabels = [
            'pending' => 'Belum diproses',
            'dibaca' => 'Sudah dibaca',
            'diproses' => 'Sedang diproses',
            'ditindaklanjuti' => 'Selesai ditindaklanjuti',
        ];
        $targetJabatan = optional($disposisi->kepadaJabatan)->nama
            ?: optional(optional($disposisi->kepadaUser)->jabatan)->nama
            ?: '-';
        $sourceJabatan = optional($disposisi->dariJabatan)->nama
            ?: optional(optional($disposisi->dariUser)->jabatan)->nama
            ?: '-';
        $jenis = optional($suratMasuk->kategoriSurat)->nama
            ?: optional($suratMasuk->klasifikasiKode)->nama
            ?: '-';
        $selectedPetunjuk = trim((string) $disposisi->petunjuk);
        $nomorSuratPdf = str_replace(
            ['/', '.', '-'],
            ['/&#8203;', '.&#8203;', '-&#8203;'],
            e((string) $suratMasuk->nomor_surat)
        );
    @endphp

    <div class="sheet">
        <table class="header">
            <tr>
                <td class="logo-cell" style="width: 22%;">
                    @if($logoData)
                        <img src="{{ $logoData }}" class="logo" alt="Logo">
                    @endif
                </td>
                <td class="institution" style="width: 78%;">PENGADILAN TINGGI AGAMA<br>PAPUA BARAT</td>
            </tr>
        </table>

        <div class="title">LEMBAR DISPOSISI</div>
        <div class="notice">PERHATIAN: Dilarang memisahkan sehelai Naskah Dinas yang tergabung dalam berkas ini.</div>

        <table class="meta">
            <tr>
                <td class="meta-cell" style="width: 42%;">
                    <table class="detail-lines">
                        <tr><td class="detail-label">Nomor Naskah Dinas</td><td class="detail-colon">:</td><td class="letter-number">{!! $nomorSuratPdf !!}</td></tr>
                        <tr><td class="detail-label">Tanggal Naskah Dinas</td><td class="detail-colon">:</td><td>{{ optional($suratMasuk->tanggal_surat)->format('d-m-Y') ?: '-' }}</td></tr>
                        <tr><td class="detail-label">Lampiran</td><td class="detail-colon">:</td><td>{{ $suratMasuk->file_path ? '1 berkas' : '-' }}</td></tr>
                    </table>
                </td>
                <td class="meta-cell" style="width: 23%;">
                    <table class="detail-lines">
                        <tr><td class="detail-label">Status</td><td class="detail-colon">:</td><td>{{ $statusLabels[$disposisi->status] ?? ucfirst((string) $disposisi->status) }}</td></tr>
                        <tr><td class="detail-label">Sifat</td><td class="detail-colon">:</td><td>{{ $sifatLabels[$suratMasuk->sifat] ?? ucfirst((string) $suratMasuk->sifat) }}</td></tr>
                        <tr><td class="detail-label">Jenis</td><td class="detail-colon">:</td><td>{{ $jenis }}</td></tr>
                    </table>
                </td>
                <td class="meta-cell" style="width: 35%;">
                    <table class="detail-lines">
                        <tr><td class="detail-label">Diterima Tanggal</td><td class="detail-colon">:</td><td>{{ optional($suratMasuk->created_at)->format('d-m-Y H:i') ?: '-' }}</td></tr>
                        <tr><td class="detail-label">Nomor Agenda</td><td class="detail-colon">:</td><td>-</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="sender">
            <tr>
                <td class="sender-cell">
                    <table class="detail-lines">
                        <tr><td style="width: 48pt;">Dari</td><td class="detail-colon">:</td><td>{{ $suratMasuk->pengirim ?: '-' }}</td></tr>
                        <tr><td>Hal</td><td class="detail-colon">:</td><td>{{ $suratMasuk->perihal ?: '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="priority">
            <tr>
                <td><span class="box">{{ $disposisi->priority_level === 'high' ? '☑' : '☐' }}</span> SANGAT SEGERA</td>
                <td><span class="box">{{ $disposisi->priority_level !== 'high' ? '☑' : '☐' }}</span> SEGERA</td>
            </tr>
        </table>

        <table class="work">
            <tr>
                <td class="work-cell" style="width: 51%;">
                    <div class="section-title">DISPOSISI KEPADA:</div>
                    <ul class="recipient-list">
                        <li>
                            <span class="box">☑</span> {{ $targetJabatan }}
                            <div class="recipient-name">{{ optional($disposisi->kepadaUser)->name ?: '-' }}</div>
                        </li>
                    </ul>

                    <div class="section-title" style="margin-top: 22pt;">CATATAN:</div>
                    <div class="note">{{ $disposisi->catatan ?: '-' }}</div>
                    <div class="muted" style="margin-top: 18pt; font-size: 7.2pt;">
                        Disposisi oleh: {{ optional($disposisi->dariUser)->name ?: '-' }}<br>
                        Jabatan: {{ $sourceJabatan }}
                    </div>
                </td>
                <td class="work-cell" style="width: 49%;">
                    <div class="section-title">PETUNJUK:</div>
                    <table class="instruction">
                        @foreach($petunjukOptions as $option)
                            <tr>
                                <td class="check">{{ $selectedPetunjuk === $option ? '☑' : '☐' }}</td>
                                <td>{{ $option }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        <table class="process">
            <tr>
                <td>
                    Tanggal Kirim untuk Proses: {{ optional($disposisi->created_at)->format('d-m-Y H:i') ?: '-' }}
                    <div class="process-line">Diterima Oleh: {{ optional($disposisi->kepadaUser)->name ?: '-' }}</div>
                </td>
                <td>
                    Diajukan Kembali Tanggal: {{ optional($disposisi->completed_at)->format('d-m-Y H:i') ?: '-' }}
                    <div class="process-line">Diterima Tanggal: {{ optional($disposisi->read_at)->format('d-m-Y H:i') ?: '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    Tanggal Kembali untuk Proses: {{ optional($disposisi->completed_at)->format('d-m-Y H:i') ?: '-' }}
                    <div class="process-line">Diterima Oleh: {{ optional($disposisi->dariUser)->name ?: '-' }}</div>
                </td>
                <td>
                    Tanggal selesai dari Pejabat yang memberi disposisi:
                    <div class="process-line">{{ optional($disposisi->completed_at)->format('d-m-Y H:i') ?: '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="verification-wrap">
        @include('partials.pdf-verification-badge')
    </div>
</body>
</html>
