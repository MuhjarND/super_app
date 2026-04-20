<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Code Alat dan Mesin</title>
    <style>
        @page {
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
        }

        .page-title {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            text-align: center;
            font-size: 10px;
            color: #475569;
            margin-bottom: 16px;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -6px;
        }

        .item {
            width: 33.33%;
            padding: 6px;
            box-sizing: border-box;
        }

        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 10px;
            text-align: center;
            min-height: 182px;
        }

        .qr {
            width: 110px;
            height: 110px;
            margin: 0 auto 8px;
            display: block;
        }

        .code {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #4338ca;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .item-name {
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .sub-name {
            display: block;
            font-size: 9px;
            color: #475569;
            line-height: 1.35;
        }
    </style>
</head>
<body>
    <div class="page-title">QR Code Alat dan Mesin</div>
    <div class="page-subtitle">Daftar QR sub barang untuk kebutuhan identifikasi dan pelacakan inventaris.</div>

    <div class="grid">
        @foreach($details as $detail)
            <div class="item">
                <div class="card">
                    <img class="qr" src="data:image/svg+xml;base64,{{ $detail->qr_svg }}" alt="QR">
                    <span class="code">{{ $detail->sub_code }}</span>
                    <span class="item-name">{{ optional($detail->item)->name }}</span>
                    <span class="sub-name">{{ $detail->name }}</span>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
