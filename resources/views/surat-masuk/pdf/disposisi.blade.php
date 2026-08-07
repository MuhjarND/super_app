<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Disposisi</title>
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7pt;
            line-height: 1.16;
        }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { vertical-align: top; }
        .sheet { border: .85pt solid #111; }
        .official-letterhead {
            width: auto;
            height: 27mm;
            padding: 1.1mm 2mm;
            overflow: hidden;
            border-bottom: .75pt solid #111;
            text-align: center;
        }
        .official-letterhead img {
            display: block;
            width: 67%;
            height: auto;
            margin: 0 auto;
        }
        .letterhead-fallback {
            height: 24mm;
            padding: 2mm 8mm 0;
            text-align: center;
            font-family: DejaVu Serif, serif;
            font-weight: bold;
            line-height: 1.1;
        }
        .letterhead-fallback .line-1 { font-size: 8.5pt; }
        .letterhead-fallback .line-2 { font-size: 8.5pt; }
        .letterhead-fallback .line-3 { font-size: 10pt; }
        .letterhead-fallback .address { margin-top: 1mm; font-size: 6.4pt; font-weight: normal; }
        .title {
            height: 6mm;
            padding: .8mm 2mm .4mm;
            border-bottom: .75pt solid #111;
            text-align: center;
            font-family: DejaVu Serif, serif;
            font-size: 11.5pt;
            font-weight: bold;
            line-height: 1;
        }
        .notice {
            height: 5.5mm;
            padding: 1.2mm 2mm .7mm;
            border-bottom: .75pt solid #111;
            text-align: center;
            font-size: 6.6pt;
            font-weight: bold;
        }
        .meta > tbody > tr > td {
            height: 20mm;
            padding: 2.4mm 2mm 1.5mm;
            border-right: .75pt solid #111;
            border-bottom: .75pt solid #111;
        }
        .meta > tbody > tr > td:last-child { border-right: 0; }
        .detail-lines { border: 0; table-layout: auto; }
        .detail-lines td {
            height: auto;
            padding: 0 0 1.2mm;
            border: 0;
            vertical-align: top;
        }
        .detail-label { white-space: nowrap; }
        .detail-colon { width: 3.5mm; text-align: center; }
        .detail-value { padding-left: .5mm !important; }
        .letter-number { font-size: 6.4pt; line-height: 1.2; word-break: break-all; }
        .sender-cell {
            height: 13mm;
            padding: 2mm;
            border-bottom: .75pt solid #111;
        }
        .sender-label { width: 17mm; }
        .sender-value { font-size: 7pt; }
        .priority td {
            height: 8mm;
            padding: 2.2mm 1mm 1mm;
            border-right: .75pt solid #111;
            border-bottom: .75pt solid #111;
            text-align: center;
            font-size: 8pt;
        }
        .priority td:last-child { border-right: 0; }
        .checkbox {
            display: inline-block;
            width: 3.2mm;
            height: 3.2mm;
            margin-right: 1.7mm;
            border: .7pt solid #111;
            text-align: center;
            font-size: 6.7pt;
            font-weight: bold;
            line-height: 2.8mm;
            vertical-align: -.4mm;
        }
        .work > tbody > tr > td {
            height: 122mm;
            padding: 3mm 2.5mm 2mm;
            border-right: .75pt solid #111;
            border-bottom: .75pt solid #111;
        }
        .work > tbody > tr > td:last-child { border-right: 0; }
        .section-title {
            display: inline-block;
            margin-bottom: 2.5mm;
            font-size: 7.5pt;
            font-weight: bold;
            text-decoration: underline;
        }
        .recipient-list { min-height: 43mm; }
        .recipient-entry { margin: 0 0 2.2mm 1.5mm; }
        .recipient-position { font-weight: normal; }
        .note-title { margin-top: 0; }
        .note {
            min-height: 41mm;
            margin-top: .5mm;
            padding: 1mm;
            white-space: pre-wrap;
            line-height: 1.3;
        }
        .instruction { border: 0; table-layout: auto; }
        .instruction td { height: 5.4mm; padding: .4mm 0; border: 0; vertical-align: top; }
        .instruction .check-cell { width: 6mm; }
        .instruction .instruction-label { padding-top: .7mm; }
        .process td {
            height: 12mm;
            padding: 1.6mm 2mm;
            border-right: .75pt solid #111;
            border-bottom: .75pt solid #111;
            font-size: 6.8pt;
            line-height: 1.25;
        }
        .process tr:last-child td { border-bottom: 0; }
        .process td:last-child { border-right: 0; }
        .process-line { margin-top: 2.2mm; }
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
        <div class="notice">PERHATIAN: Dilarang memisahkan sehelai Naskah Dinas pun yang tergabung dalam berkas ini.</div>

        <table class="meta">
            <tr>
                <td style="width: 38%;">
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
                <td style="width: 25%;">
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
                <td style="width: 37%;">
                    <table class="detail-lines">
                        <colgroup>
                            <col style="width: 48%;">
                            <col style="width: 9pt;">
                            <col>
                        </colgroup>
                        <tr><td class="detail-label">Diterima Tanggal</td><td class="detail-colon">:</td><td class="detail-value">{{ optional($suratMasuk->created_at)->format('d-m-Y') ?: '-' }}</td></tr>
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
                        <tr><td class="sender-label">Perihal</td><td class="detail-colon">:</td><td class="detail-value sender-value">{{ $suratMasuk->perihal ?: '-' }}</td></tr>
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
                    <div class="recipient-list">
                        <div class="recipient-entry">
                            <span class="checkbox">&#10003;</span>
                            <span class="recipient-position">{{ $targetJabatan }}</span>
                        </div>
                    </div>

                    <div class="section-title note-title">CATATAN:</div>
                    <div class="note">{{ $disposisi->catatan ?: '-' }}</div>
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

</body>
</html>
