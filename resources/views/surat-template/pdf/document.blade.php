<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 2.2cm 2cm 2cm 2.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.5; }
        .kop { margin-bottom: 14px; }
        .kop img { width: 100%; height: auto; display: block; }
        .meta-top { text-align: right; font-size: 10px; margin-bottom: 18px; }
        .letter-wrap { width: 100%; }
        .recipient { margin-bottom: 20px; }
        .title { text-align: center; font-size: 15px; font-weight: 700; margin-bottom: 4px; text-transform: uppercase; }
        .number { text-align: center; font-size: 11px; margin-bottom: 18px; }
        .body { font-size: 11px; }
        .body p { margin: 0 0 10px; }
        .body ul, .body ol { margin: 0 0 10px 18px; }
        .footer { margin-top: 28px; width: 100%; }
        .footer .sign { width: 42%; margin-left: auto; text-align: center; page-break-inside: avoid; }
        .footer .sign-qr { width: 68px; height: 68px; display: block; margin: 5px auto 3px; object-fit: contain; }
        .small-meta { margin-top: 26px; font-size: 10px; color: #4b5563; }
        table.info { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.info td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        table.info td.label { width: 26%; background: #f9fafb; font-weight: 600; }
        .approval-box { width: 100%; border-collapse: collapse; margin-top: 20px; page-break-inside: avoid; }
        .approval-box th, .approval-box td { border: 1px solid #111827; padding: 5px 8px; vertical-align: top; }
        .approval-box th { text-align: center; font-size: 12px; }
        .approval-options { width: 58%; line-height: 1.8; }
        .approval-sign { width: 42%; text-align: center; line-height: 1.35; }
        .stamp-signature { width: 82px; height: 82px; object-fit: contain; display: block; margin: 3px auto; }
        .stamp-placeholder { height: 88px; }
        .suket-presensi { font-size: 10px; line-height: 1.35; }
        .suket-presensi .body { font-size: 10px; }
        .suket-presensi .body p { margin-bottom: 6px; }
        .suket-presensi .footer { margin-top: 10px; }
        .suket-presensi .approval-box { margin-top: 10px; }
        .suket-presensi .stamp-signature { width: 66px; height: 66px; }
        .suket-presensi .stamp-placeholder { height: 68px; }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
    <div class="letter-wrap">
        @if($templateSlug === 'surat-tugas')
            @include('surat-template.pdf.surat-tugas', [
                'suratKeluar' => $suratKeluar,
                'fieldValues' => $fieldValues,
                'kopImage' => $kopImage,
                'signatoryTitle' => $signatoryTitle,
            ])
        @elseif($templateSlug === 'surat-keterangan-perbaikan-presensi')
            @include('surat-template.pdf.suket-perbaikan-presensi', [
                'suratKeluar' => $suratKeluar,
                'fieldValues' => $fieldValues,
                'kopImage' => $kopImage,
                'approvalSignature' => $approvalSignature ?? null,
                'approvalNote' => $approvalNote ?? null,
            ])
        @else
            @if($kopImage)
                <div class="kop">
                    <img src="{{ $kopImage }}" alt="Kop Surat">
                </div>
            @endif

            <div class="meta-top">{{ optional($suratKeluar->created_at)->translatedFormat('d F Y') }}</div>

            <div class="recipient">
                Yth. {{ $suratKeluar->opsi_penerima === 'internal' ? 'Penerima Internal' : ($suratKeluar->penerima_external ?: 'Penerima') }}<br>
                di -<br>
                Tempat
            </div>

            <div class="title">{{ $templateName }}</div>
            <div class="number">Nomor: {{ $suratKeluar->nomor_surat_formatted }}</div>

            <div class="body">{!! $renderedBody !!}</div>

            @if(!empty($fieldValues))
                <table class="info">
                    @foreach($fieldValues as $label => $value)
                        <tr>
                            <td class="label">{{ ucwords(str_replace('_', ' ', $label)) }}</td>
                            <td>{{ $value ?: '-' }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <div class="footer">
                <div class="sign">
                    Manokwari, {{ optional($suratKeluar->tanggal_surat)->translatedFormat('d F Y') }}<br>
                    {{ $signatoryTitle['line1'] ?? 'Pejabat Penandatangan,' }}<br>
                    {{ $signatoryTitle['line2'] ?? 'Pengadilan Tinggi Agama Papua Barat' }}
                    @if(!empty($approvalSignature['image']))
                        <img class="sign-qr" src="{{ $approvalSignature['image'] }}" alt="QR tanda tangan elektronik">
                    @else
                        <div style="height:76px;"></div>
                    @endif
                    <strong>{{ $approvalSignature['name'] ?? (optional($suratKeluar->creator)->name ?: 'Pejabat Penandatangan') }}</strong><br>
                    @if(!empty($approvalSignature['nip']))
                        NIP. {{ $approvalSignature['nip'] }}
                    @endif
                </div>
            </div>

            <div class="small-meta">
                Dokumen ini dihasilkan dari modul Template Surat dan tersimpan pada Surat Keluar dengan nomor {{ $suratKeluar->nomor_surat_formatted }}.
            </div>
        @endif
    </div>
</body>
</html>
