@if(!empty($pdfVerification['qr']))
    <style>
        .pdf-verification-badge {
            position: fixed;
            left: 0.55cm;
            bottom: 0.45cm;
            width: 205px;
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
            width: 48px;
            text-align: center;
        }
        .pdf-verification-badge img {
            width: 42px;
            height: 42px;
            display: block;
            margin: 0 auto 1px;
        }
        .pdf-verification-label {
            font-size: 5.6px;
            line-height: 1.05;
            text-align: center;
        }
        .pdf-verification-note {
            padding-left: 6px !important;
            font-size: 6.4px;
            line-height: 1.22;
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
                    Dokumen ini telah ditandatangani secara elektronik melalui aplikasi SIMANTAP.
                    Pindai QR untuk verifikasi keaslian dokumen.
                </td>
            </tr>
        </table>
    </div>
@endif
