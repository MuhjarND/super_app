@if(!empty($pdfVerification['qr']))
    @php
        $verificationQrSize = max(32, min(72, (int) ($pdfVerificationQrSize ?? 48)));
        $verificationQrColumnSize = $verificationQrSize + 4;
    @endphp
    <style>
        .pdf-verification-badge {
            position: fixed;
            left: 0.6cm;
            right: 0.6cm;
            bottom: 0.38cm;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            z-index: 999;
        }
        .pdf-verification-badge table {
            border-collapse: collapse;
            width: 100%;
        }
        .pdf-verification-badge td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .pdf-verification-qr {
            width: {{ $verificationQrColumnSize }}px;
            text-align: center;
        }
        .pdf-verification-badge img {
            width: {{ $verificationQrSize }}px;
            height: {{ $verificationQrSize }}px;
            display: block;
            margin: 0 auto 1px;
        }
        .pdf-verification-label {
            font-size: 5.6px;
            line-height: 1.05;
            text-align: center;
        }
        .pdf-verification-note {
            padding-left: 8px !important;
            font-size: 6.8px;
            line-height: 1.28;
            color: #1f2937;
        }
    </style>
    <div class="pdf-verification-badge">
        <table>
            <tr>
                <td class="pdf-verification-qr">
                    <img src="{{ $pdfVerification['qr'] }}" alt="Validasi PDF">
                    <div class="pdf-verification-label">Validasi PDF</div>
                </td>
                <td class="pdf-verification-note">
                    <strong>Dokumen ini telah ditandatangani secara elektronik dan dinyatakan valid melalui aplikasi PAPEDA.</strong><br>
                    Pindai QR berlogo PTA Papua Barat untuk melihat identitas penandatangan, waktu penandatanganan, dan dokumen asli.
                </td>
            </tr>
        </table>
    </div>
@endif
