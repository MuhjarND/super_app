@if(!empty($pdfVerification['qr']))
    <style>
        .pdf-verification-badge {
            position: fixed;
            left: 0.55cm;
            bottom: 0.45cm;
            width: 50px;
            text-align: center;
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            z-index: 999;
        }
        .pdf-verification-badge img {
            width: 42px;
            height: 42px;
            display: block;
            margin: 0 auto 1px;
        }
        .pdf-verification-badge div {
            font-size: 5.6px;
            line-height: 1.05;
        }
    </style>
    <div class="pdf-verification-badge">
        <img src="{{ $pdfVerification['qr'] }}" alt="Validasi PDF">
        <div>Validasi PDF</div>
    </div>
@endif
