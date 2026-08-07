<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Disposisi Surat Masuk</title>
    <style>
        @page { size: A3 landscape; margin: 12mm 14mm 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
        .header td { border: 0; padding: 0; vertical-align: middle; }
        .logo-cell { width: 82pt; text-align: center; }
        .logo { width: 62pt; height: 62pt; object-fit: contain; }
        .institution { text-align: center; padding-right: 82pt !important; }
        .institution-name { font-size: 15pt; font-weight: bold; }
        .document-title { margin-top: 4pt; font-size: 18pt; font-weight: bold; }
        .document-subtitle { margin-top: 2pt; color: #4b5563; }
        .letter-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
            table-layout: fixed;
        }
        .letter-info td { border: .7pt solid #94a3b8; padding: 5pt 7pt; vertical-align: top; }
        .info-label { display: block; color: #64748b; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; }
        .info-value { display: block; margin-top: 2pt; font-weight: bold; }
        .history-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .history-table th {
            border: .75pt solid #475569;
            padding: 6pt 5pt;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 8pt;
            text-align: left;
            vertical-align: middle;
        }
        .history-table td {
            border: .65pt solid #94a3b8;
            padding: 6pt 5pt;
            vertical-align: top;
            word-wrap: break-word;
        }
        .history-table tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .muted { color: #64748b; font-size: 7.5pt; }
        .status { font-weight: bold; }
        .summary { margin-top: 7pt; color: #475569; font-size: 8pt; }
        .verification-wrap { margin-top: 8pt; page-break-inside: avoid; }
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
            'pending' => 'Pending',
            'dibaca' => 'Dibaca',
            'diproses' => 'Diproses',
            'ditindaklanjuti' => 'Ditindaklanjuti',
        ];
        $priorityLabels = [
            'high' => 'Sangat segera',
            'normal' => 'Normal',
            'low' => 'Rendah',
        ];
    @endphp

    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logoData)
                    <img src="{{ $logoData }}" class="logo" alt="Logo">
                @endif
            </td>
            <td class="institution">
                <div class="institution-name">PENGADILAN TINGGI AGAMA PAPUA BARAT</div>
                <div class="document-title">RIWAYAT DISPOSISI SURAT MASUK</div>
                <div class="document-subtitle">Dicetak {{ now('Asia/Jayapura')->format('d-m-Y H:i') }} WIT</div>
            </td>
        </tr>
    </table>

    <table class="letter-info">
        <tr>
            <td style="width: 26%;">
                <span class="info-label">Nomor Surat</span>
                <span class="info-value">{{ $suratMasuk->nomor_surat ?: '-' }}</span>
            </td>
            <td style="width: 19%;">
                <span class="info-label">Tanggal Surat</span>
                <span class="info-value">{{ optional($suratMasuk->tanggal_surat)->format('d-m-Y') ?: '-' }}</span>
            </td>
            <td style="width: 25%;">
                <span class="info-label">Pengirim</span>
                <span class="info-value">{{ $suratMasuk->pengirim ?: '-' }}</span>
            </td>
            <td style="width: 20%;">
                <span class="info-label">Jenis / Klasifikasi</span>
                <span class="info-value">{{ optional($suratMasuk->kategoriSurat)->nama ?: optional($suratMasuk->klasifikasiKode)->nama ?: '-' }}</span>
            </td>
            <td style="width: 10%;">
                <span class="info-label">Sifat</span>
                <span class="info-value">{{ $sifatLabels[$suratMasuk->sifat] ?? ucfirst((string) $suratMasuk->sifat) }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <span class="info-label">Perihal</span>
                <span class="info-value">{{ $suratMasuk->perihal ?: '-' }}</span>
            </td>
        </tr>
    </table>

    <table class="history-table">
        <thead>
            <tr>
                <th class="center" style="width: 3%;">No.</th>
                <th style="width: 9%;">Tanggal</th>
                <th style="width: 13%;">Dari</th>
                <th style="width: 13%;">Kepada</th>
                <th style="width: 8%;">Jenis</th>
                <th style="width: 8%;">Prioritas</th>
                <th style="width: 13%;">Petunjuk</th>
                <th style="width: 15%;">Catatan</th>
                <th style="width: 12%;">Tindak Lanjut</th>
                <th style="width: 6%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($disposisis as $index => $disposisi)
                @php
                    $sourcePosition = optional($disposisi->dariJabatan)->nama
                        ?: optional(optional($disposisi->dariUser)->jabatan)->nama;
                    $targetPosition = optional($disposisi->kepadaJabatan)->nama
                        ?: optional(optional($disposisi->kepadaUser)->jabatan)->nama;
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>
                        {{ optional($disposisi->created_at)->format('d-m-Y') ?: '-' }}<br>
                        <span class="muted">{{ optional($disposisi->created_at)->format('H:i') ?: '-' }} WIT</span>
                    </td>
                    <td>
                        <strong>{{ optional($disposisi->dariUser)->name ?: '-' }}</strong><br>
                        <span class="muted">{{ $sourcePosition ?: '-' }}</span>
                    </td>
                    <td>
                        <strong>{{ optional($disposisi->kepadaUser)->name ?: '-' }}</strong><br>
                        <span class="muted">{{ $targetPosition ?: '-' }}</span>
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $disposisi->tipe)) }}</td>
                    <td>{{ $priorityLabels[$disposisi->priority_level] ?? ucfirst((string) $disposisi->priority_level) }}</td>
                    <td>{{ $disposisi->petunjuk ?: '-' }}</td>
                    <td>{{ $disposisi->catatan ?: '-' }}</td>
                    <td>
                        {{ $disposisi->catatan_tindak_lanjut ?: '-' }}
                        @if($disposisi->tautan_tindak_lanjut)
                            <br><span class="muted">{{ $disposisi->tautan_tindak_lanjut }}</span>
                        @endif
                    </td>
                    <td class="status">{{ $statusLabels[$disposisi->status] ?? ucfirst((string) $disposisi->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">Jumlah riwayat disposisi: {{ $disposisis->count() }}</div>

    <div class="verification-wrap">
        @include('partials.pdf-verification-badge')
    </div>
</body>
</html>
