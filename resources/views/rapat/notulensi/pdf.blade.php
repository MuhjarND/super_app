<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulen Rapat</title>
    <style>
        @page {
            size: A4;
            margin: 1.4cm 1.2cm 1.5cm 1.2cm;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 11pt;
            color: #111;
            margin: 0;
        }

        .kop {
            width: 100%;
            margin-bottom: 10px;
        }

        .kop img {
            width: 100%;
            height: auto;
        }

        .title-band {
            background: #7bc043;
            text-align: center;
            font-size: 17pt;
            padding: 6px 8px;
            margin: 8px 0 14px;
        }

        .section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .section-table td,
        .section-table th {
            border: 1px solid #666;
            padding: 6px 8px;
            vertical-align: top;
        }

        .section-header {
            font-size: 12pt;
            font-weight: bold;
            background: #fff;
        }

        .section-body {
            padding: 10px 8px;
        }

        .section-body.justify-body,
        .section-body.justify-body p,
        .section-body.justify-body li {
            text-align: justify;
        }

        .section-label {
            width: 34%;
            white-space: nowrap;
            vertical-align: top;
        }

        .section-value {
            vertical-align: top;
        }

        .section-body p {
            margin: 0 0 6px;
        }

        .section-body ol,
        .section-body ul {
            margin: 0 0 0 18px;
            padding: 0;
        }

        .section-body ol li,
        .section-body ul li {
            margin: 0 0 2px;
            padding: 0;
        }

        .section-body li p {
            margin: 0;
        }

        .notulen-auto-list p {
            margin: 0 0 4px;
            line-height: 1.35;
        }

        .documentation-grid {
            margin-top: 8px;
        }

        .documentation-item {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            margin: 0 1% 12px;
            text-align: center;
        }

        .documentation-item img {
            width: 100%;
            max-height: 260px;
            object-fit: contain;
            border: 1px solid #888;
            padding: 3px;
        }

        .signature-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 12px;
        }

        .signature-line-1,
        .signature-line-2 {
            font-weight: bold;
            font-size: 11pt;
        }

        .signature-line-2 {
            margin-bottom: 8px;
        }

        .signature-qr {
            margin: 8px auto 6px;
            width: 110px;
            height: 110px;
        }

        .signature-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .signature-meta {
            font-size: 9.5pt;
            color: #444;
            min-height: 14px;
        }

        .signature-name {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    @if($kopImage)
        <div class="kop">
            <img src="{{ $kopImage }}" alt="Kop Notulen">
        </div>
    @endif

    <div class="title-band">NOTULEN AGENDA</div>

    <table class="section-table">
        <tr>
            <td class="section-header" colspan="2">A.&nbsp;&nbsp; URAIAN KEGIATAN</td>
        </tr>
        @if(!empty($uraianKegiatanRows))
            @foreach($uraianKegiatanRows as $row)
                <tr>
                    <td class="section-label">{{ $row['label'] }}</td>
                    <td class="section-value">{{ $row['value'] }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="section-body" colspan="2">{!! $notulensi->uraian_kegiatan ?: '<p>-</p>' !!}</td>
            </tr>
        @endif
    </table>

    <table class="section-table">
        <tr>
            <td class="section-header">B.&nbsp;&nbsp; AGENDA</td>
        </tr>
        <tr>
            <td class="section-body justify-body">{!! $notulensi->agenda_rapat !!}</td>
        </tr>
    </table>

    <table class="section-table">
        <tr>
            <td class="section-header">C.&nbsp;&nbsp; SUSUNAN AGENDA</td>
        </tr>
        <tr>
            <td class="section-body justify-body">{!! $notulensi->susunan_agenda ?: '<p>-</p>' !!}</td>
        </tr>
    </table>

    <table class="section-table">
        <tr>
            <td class="section-header">D.&nbsp;&nbsp; HASIL AGENDA</td>
        </tr>
        <tr>
            <td class="section-body justify-body">{!! $notulensi->hasil_rapat !!}</td>
        </tr>
    </table>

    <table class="section-table">
        <tr>
            <td class="section-header">E.&nbsp;&nbsp; REKOMENDASI</td>
        </tr>
        <tr>
            <td class="section-body justify-body">
                {!! $notulensi->rekomendasi ?: '<p>-</p>' !!}
            </td>
        </tr>
    </table>

    @if($dokumentasiImages->count() > 0)
        <table class="section-table">
            <tr>
                <td class="section-header">DOKUMENTASI AGENDA</td>
            </tr>
            <tr>
                <td class="section-body">
                    <div class="documentation-grid" style="text-align:center;">
                        @foreach($dokumentasiImages as $image)
                            <div class="documentation-item">
                                <img src="{{ $image['data_uri'] }}" alt="{{ $image['nama'] }}">
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
        </table>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line-1">{{ $notulisSignature['line1'] ?? 'Notulis,' }}</div>
                @if(!empty($notulisSignature['line2']))
                    <div class="signature-line-2">{{ $notulisSignature['line2'] }}</div>
                @endif
                @if(!empty($notulisSignature['barcode']))
                    <div class="signature-qr">
                        <img src="{{ $notulisSignature['barcode'] }}" alt="Barcode TTD Notulis">
                    </div>
                @endif
                <div class="signature-meta">
                    {{ !empty($notulisSignature['signed_at']) ? $notulisSignature['signed_at']->translatedFormat('d F Y H:i') . ' WIT' : '' }}
                </div>
                <div class="signature-name">{{ $notulisSignature['name'] ?? '-' }}</div>
            </td>
            <td>
                <div class="signature-line-1">{{ $approvalSignature['line1'] ?? 'Pejabat Approval,' }}</div>
                @if(!empty($approvalSignature['line2']))
                    <div class="signature-line-2">{{ $approvalSignature['line2'] }}</div>
                @endif
                @if(!empty($approvalSignature['barcode']))
                    <div class="signature-qr">
                        <img src="{{ $approvalSignature['barcode'] }}" alt="Barcode TTD Approval">
                    </div>
                @endif
                <div class="signature-meta">
                    {{ !empty($approvalSignature['signed_at']) ? $approvalSignature['signed_at']->translatedFormat('d F Y H:i') . ' WIT' : '' }}
                </div>
                <div class="signature-name">{{ $approvalSignature['name'] ?? '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
