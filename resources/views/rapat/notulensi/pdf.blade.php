<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Notulen Rapat</title>
    <style>
        @page {
            size: A4;
            margin: 1.4cm 1.2cm 2.8cm 1.2cm;
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

        .section-table > tbody > tr > td,
        .section-table > tbody > tr > th {
            border: 1px solid #666;
            padding: 6px 8px;
            vertical-align: top;
        }

        .section-block {
            width: 100%;
            margin-bottom: 12px;
        }

        .section-block-header {
            border: 1px solid #666;
            padding: 6px 8px;
            font-size: 12pt;
            font-weight: bold;
        }

        .section-block-body {
            border: 1px solid #666;
            border-top: 0;
            padding: 9px 8px;
        }

        .recommendation-section {
            page-break-inside: avoid;
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

        .minutes-rich-content {
            line-height: 1.3;
        }

        .minutes-rich-content p {
            margin: 0 0 7px;
            text-align: justify;
        }

        .minutes-rich-content table {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 7px 0 4px !important;
            font-size: 9.3pt;
            line-height: 1.22;
        }

        .minutes-rich-content table thead {
            display: table-header-group;
        }

        .minutes-rich-content table tbody {
            display: table-row-group;
        }

        .minutes-rich-content table tr {
            page-break-inside: avoid;
        }

        .minutes-rich-content table th,
        .minutes-rich-content table td {
            border: 1px solid #666 !important;
            padding: 5px 6px !important;
            vertical-align: top !important;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .minutes-rich-content table th {
            background: #eef2f5;
            font-weight: bold;
            text-align: center;
            vertical-align: middle !important;
        }

        .minutes-rich-content table th:nth-child(1),
        .minutes-rich-content table td:nth-child(1) {
            width: 6% !important;
            text-align: center;
        }

        .minutes-rich-content table th:nth-child(2),
        .minutes-rich-content table td:nth-child(2) {
            width: 18% !important;
        }

        .minutes-rich-content table th:nth-child(3),
        .minutes-rich-content table td:nth-child(3) {
            width: 32% !important;
        }

        .minutes-rich-content table th:nth-child(4),
        .minutes-rich-content table td:nth-child(4) {
            width: 21% !important;
        }

        .minutes-rich-content table th:nth-child(5),
        .minutes-rich-content table td:nth-child(5) {
            width: 23% !important;
        }

        .documentation-section {
            page-break-before: always;
        }

        .documentation-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0;
        }

        .documentation-table td {
            width: 50%;
            border: 0;
            padding: 0;
            vertical-align: middle;
            text-align: center;
            page-break-inside: avoid;
        }

        .documentation-table img {
            max-width: 100%;
            max-height: 225px;
            object-fit: contain;
            border: 1px solid #888;
            padding: 3px;
        }

        .signature-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            page-break-inside: avoid;
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

        .signature-pad-image {
            margin: 4px auto 3px;
            width: 68px;
            height: 68px;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-pad-image img {
            width: 100%;
            height: 100%;
            display: block;
            margin: 0 auto;
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
            margin-top: 0;
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', [
    'pdfVerification' => $pdfVerification ?? null,
    'pdfVerificationQrSize' => 44,
    'pdfVerificationBottom' => '-2cm',
])
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

    <div class="section-block">
        <div class="section-block-header">D.&nbsp;&nbsp; HASIL AGENDA</div>
        <div class="section-block-body minutes-rich-content">{!! $notulensi->hasil_rapat !!}</div>
    </div>

    <div class="section-block recommendation-section">
        <div class="section-block-header">E.&nbsp;&nbsp; REKOMENDASI</div>
        <div class="section-block-body minutes-rich-content">
            {!! $notulensi->rekomendasi ?: '<p>-</p>' !!}
        </div>
    </div>

    @if($dokumentasiImages->count() > 0)
        <div class="section-block documentation-section">
            <div class="section-block-header">DOKUMENTASI AGENDA</div>
            <div class="section-block-body">
                <table class="documentation-table">
                    @foreach($dokumentasiImages->chunk(2) as $imageRow)
                        <tr>
                            @foreach($imageRow as $image)
                                <td><img src="{{ $image['data_uri'] }}" alt="{{ $image['nama'] }}"></td>
                            @endforeach
                            @if($imageRow->count() === 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-line-1">{{ $notulisSignature['line1'] ?? 'Notulis,' }}</div>
                @if(!empty($notulisSignature['line2']))
                    <div class="signature-line-2">{{ $notulisSignature['line2'] }}</div>
                @endif
                @if(!empty($notulisSignature['image']))
                    <div class="signature-pad-image">
                        <img src="{{ $notulisSignature['image'] }}" alt="QR tanda tangan notulis">
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
                @if(!empty($approvalSignature['image']))
                    <div class="signature-pad-image">
                        <img src="{{ $approvalSignature['image'] }}" alt="QR tanda tangan approval">
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
