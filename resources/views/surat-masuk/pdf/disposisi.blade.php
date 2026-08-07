<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi</title>
    <style>
        @page { size: A4 portrait; margin: 4mm 5mm 4mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.7pt;
            line-height: 1.2;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { vertical-align: top; }
        .sheet { border: 1pt solid #111; }
        .official-letterhead {
            width: 100%;
            overflow: hidden;
            border-bottom: .9pt solid #111;
        }
        .official-letterhead img {
            display: block;
            width: 100%;
            height: auto;
        }
        .letterhead-fallback {
            height: 102pt;
            padding: 16pt 12pt 8pt;
            text-align: center;
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            line-height: 1.2;
        }
        .letterhead-fallback .line-1 { font-size: 11pt; }
        .letterhead-fallback .line-2 { font-size: 11pt; }
        .letterhead-fallback .line-3 { font-size: 13pt; }
        .letterhead-fallback .address { margin-top: 4pt; font-size: 7.2pt; font-weight: normal; }
        .title {
            height: 22pt;
            padding: 2.5pt 4pt;
            border-bottom: .9pt solid #111;
            text-align: center;
            font-family: DejaVu Serif, serif;
            font-size: 14.5pt;
            line-height: 1;
        }
        .notice {
            height: 17pt;
            padding: 3pt 5pt;
            border-bottom: .9pt solid #111;
            text-align: center;
            font-size: 7.7pt;
            font-weight: bold;
        }
        .meta > tbody > tr > td {
            height: 54pt;
            padding: 7pt 7pt 4pt;
            border-right: .9pt solid #111;
            border-bottom: .9pt solid #111;
        }
        .meta > tbody > tr > td:last-child { border-right: 0; }
        .detail-lines { border: 0; table-layout: auto; }
        .detail-lines td {
            height: auto;
            padding: 0 0 2.7pt;
            border: 0;
            vertical-align: top;
        }
        .detail-label { white-space: nowrap; }
        .detail-colon { width: 9pt; text-align: center; }
        .detail-value { padding-left: 2pt !important; }
        .letter-number { font-size: 6.5pt; line-height: 1.25; word-break: break-all; }
        .sender-cell {
            height: 44pt;
            padding: 7pt;
            border-bottom: .9pt solid #111;
        }
        .sender-label { width: 46pt; }
        .sender-value { font-size: 7.6pt; }
        .priority td {
            height: 25pt;
            padding: 6pt 4pt 4pt;
            border-right: .9pt solid #111;
            border-bottom: .9pt solid #111;
            text-align: center;
            font-size: 9pt;
        }
        .priority td:last-child { border-right: 0; }
        .checkbox {
            display: inline-block;
            width: 9pt;
            height: 9pt;
            margin-right: 5pt;
            border: .8pt solid #111;
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            line-height: 8pt;
            vertical-align: -1pt;
        }
        .work > tbody > tr > td {
            height: 250pt;
            padding: 10pt 8pt 6pt;
            border-right: .9pt solid #111;
            border-bottom: .9pt solid #111;
        }
        .work > tbody > tr > td:last-child { border-right: 0; }
        .section-title {
            display: inline-block;
            margin-bottom: 8pt;
            font-size: 8.5pt;
            font-weight: bold;
            text-decoration: underline;
        }
        .recipient-entry { margin: 0 0 7pt 6pt; }
        .recipient-position { font-weight: bold; }
        .recipient-name { margin: 3pt 0 0 16pt; color: #333; font-size: 7.2pt; }
        .note-title { margin-top: 28pt; }
        .note {
            min-height: 56pt;
            margin-top: 2pt;
            padding: 3pt 4pt;
            white-space: pre-wrap;
            line-height: 1.35;
        }
        .source-info { margin: 11pt 4pt 0; color: #333; font-size: 7.1pt; line-height: 1.4; }
        .instruction { border: 0; table-layout: auto; }
        .instruction td { height: 13.5pt; padding: .8pt 0; border: 0; vertical-align: top; }
        .instruction .check-cell { width: 17pt; }
        .instruction .instruction-label { padding-top: 1pt; }
        .process td {
            height: 35pt;
            padding: 4.5pt 7pt;
            border-right: .9pt solid #111;
            border-bottom: .9pt solid #111;
            font-size: 7.2pt;
            line-height: 1.3;
        }
        .process tr:last-child td { border-bottom: 0; }
        .process td:last-child { border-right: 0; }
        .process-line { margin-top: 7pt; }
        .verification-wrap { margin-top: 0; }
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
        $petunjukLabels = [
            'Sesuai dengan ketentuan yang berlaku' => 'Setuju sesuai ketentuan yang berlaku',
            'Tidak sesuai dengan ketentuan yang berlaku' => 'Tolak sesuai ketentuan yang berlaku',
            'Sesuaikan dengan ketentuan yang berlaku' => 'Selesaikan sesuai ketentuan yang berlaku',
            'Jawab sesuai dengan ketentuan yang berlaku' => 'Jawab sesuai ketentuan yang berlaku',
            'Teliti dan pendapat' => 'Teliti & pendapat',
        ];
        $formPetunjukOptions = collect([
            'Sesuai dengan ketentuan yang berlaku',
            'Tidak sesuai dengan ketentuan yang berlaku',
            'Sesuaikan dengan ketentuan yang berlaku',
            'Jawab sesuai dengan ketentuan yang berlaku',
            'Perbaiki',
            'Teliti dan pendapat',
            'Sesuai catatan',
            'Untuk perhatian',
            'Untuk diketahui',
            'Edarkan',
            'Bicarakan dengan saya',
            'Bicarakan bersama dan laporkan hasilnya',
            'Dijadwalkan',
            'Simpan',
            'Disiapkan',
            'Ingatkan',
            'Harap dihadiri/diwakili',
            'Asli kepada',
        ])->map(function ($value) use ($petunjukLabels) {
            return [
                'value' => $value,
                'label' => $value === 'Asli kepada'
                    ? 'Asli kepada ........................'
                    : ($petunjukLabels[$value] ?? $value),
            ];
        });
        $nomorSurat = trim((string) $suratMasuk->nomor_surat);
        $nomorParts = explode('/', $nomorSurat);
        if (strlen($nomorSurat) > 22 && count($nomorParts) > 3) {
            $tailParts = array_splice($nomorParts, -2);
            $nomorSuratPdf = e(implode('/', $nomorParts) . '/') . '<br>' . e(implode('/', $tailParts));
        } else {
            $nomorSuratPdf = e($nomorSurat);
        }
    @endphp

    <div class="sheet">
        <div class="official-letterhead" data-institution="MAHKAMAH AGUNG REPUBLIK INDONESIA">
            @if($kopImage)
                <img src="{{ $kopImage }}" alt="Kop Surat Pengadilan Tinggi Agama Papua Barat">
            @else
                <div class="letterhead-fallback">
                    <div class="line-1">MAHKAMAH AGUNG REPUBLIK INDONESIA</div>
                    <div class="line-2">DIREKTORAT JENDERAL BADAN PERADILAN AGAMA</div>
                    <div class="line-3">PENGADILAN TINGGI AGAMA PAPUA BARAT</div>
                    <div class="address">Jalan Brawijaya, Manokwari, Papua Barat</div>
                </div>
            @endif
        </div>

        <div class="title">LEMBAR DISPOSISI</div>
        <div class="notice">PERHATIAN: Dilarang memisahkan sehelai Naskah Dinas yang tergabung dalam berkas ini.</div>

        <table class="meta">
            <tr>
                <td style="width: 40%;">
                    <table class="detail-lines">
                        <colgroup>
                            <col style="width: 44%;">
                            <col style="width: 9pt;">
                            <col>
                        </colgroup>
                        <tr><td class="detail-label">Nomor Naskah Dinas</td><td class="detail-colon">:</td><td class="detail-value letter-number">{!! $nomorSuratPdf !!}</td></tr>
                        <tr><td class="detail-label">Tanggal Naskah Dinas</td><td class="detail-colon">:</td><td class="detail-value">{{ optional($suratMasuk->tanggal_surat)->format('d-m-Y') ?: '-' }}</td></tr>
                        <tr><td class="detail-label">Lampiran</td><td class="detail-colon">:</td><td class="detail-value">{{ $suratMasuk->file_path ? '1 berkas' : '-' }}</td></tr>
                    </table>
                </td>
                <td style="width: 24%;">
                    <table class="detail-lines">
                        <colgroup>
                            <col style="width: 35%;">
                            <col style="width: 9pt;">
                            <col>
                        </colgroup>
                        <tr><td class="detail-label">Status</td><td class="detail-colon">:</td><td class="detail-value">{{ $statusLabels[$disposisi->status] ?? ucfirst((string) $disposisi->status) }}</td></tr>
                        <tr><td class="detail-label">Sifat</td><td class="detail-colon">:</td><td class="detail-value">{{ $sifatLabels[$suratMasuk->sifat] ?? ucfirst((string) $suratMasuk->sifat) }}</td></tr>
                        <tr><td class="detail-label">Jenis</td><td class="detail-colon">:</td><td class="detail-value">{{ $jenis }}</td></tr>
                    </table>
                </td>
                <td style="width: 36%;">
                    <table class="detail-lines">
                        <colgroup>
                            <col style="width: 48%;">
                            <col style="width: 9pt;">
                            <col>
                        </colgroup>
                        <tr><td class="detail-label">Diterima Tanggal</td><td class="detail-colon">:</td><td class="detail-value">{{ optional($suratMasuk->created_at)->format('d-m-Y H:i') ?: '-' }}</td></tr>
                        <tr><td class="detail-label">Nomor Agenda</td><td class="detail-colon">:</td><td class="detail-value">{{ $suratMasuk->id ?: '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="sender">
            <tr>
                <td class="sender-cell">
                    <table class="detail-lines">
                        <colgroup>
                            <col style="width: 8%;">
                            <col style="width: 2%;">
                            <col style="width: 90%;">
                        </colgroup>
                        <tr><td class="sender-label">Dari</td><td class="detail-colon">:</td><td class="detail-value sender-value">{{ $suratMasuk->pengirim ?: '-' }}</td></tr>
                        <tr><td class="sender-label">Hal</td><td class="detail-colon">:</td><td class="detail-value sender-value">{{ $suratMasuk->perihal ?: '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="priority">
            <tr>
                <td>
                    <span class="checkbox">{!! $disposisi->priority_level === 'high' ? '&#10003;' : '&nbsp;' !!}</span>
                    SANGAT SEGERA
                </td>
                <td>
                    <span class="checkbox">{!! $disposisi->priority_level !== 'high' ? '&#10003;' : '&nbsp;' !!}</span>
                    SEGERA
                </td>
            </tr>
        </table>

        <table class="work">
            <tr>
                <td style="width: 51%;">
                    <div class="section-title">DISPOSISI KEPADA:</div>
                    <div class="recipient-entry">
                        <span class="checkbox">&#10003;</span>
                        <span class="recipient-position">{{ $targetJabatan }}</span>
                        <div class="recipient-name">{{ optional($disposisi->kepadaUser)->name ?: '-' }}</div>
                    </div>

                    <div class="section-title note-title">CATATAN:</div>
                    <div class="note">{{ $disposisi->catatan ?: '-' }}</div>
                    <div class="source-info">
                        Disposisi oleh: {{ optional($disposisi->dariUser)->name ?: '-' }}<br>
                        Jabatan: {{ $sourceJabatan }}
                    </div>
                </td>
                <td style="width: 49%;">
                    <div class="section-title">PETUNJUK:</div>
                    <table class="instruction">
                        <colgroup>
                            <col style="width: 8%;">
                            <col style="width: 92%;">
                        </colgroup>
                        @foreach($formPetunjukOptions as $option)
                            <tr>
                                <td class="check-cell">
                                    <span class="checkbox">{!! $selectedPetunjuk === $option['value'] ? '&#10003;' : '&nbsp;' !!}</span>
                                </td>
                                <td class="instruction-label">{{ $option['label'] }}</td>
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
