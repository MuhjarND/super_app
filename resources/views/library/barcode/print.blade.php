<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Barcode</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #fff; }

        .print-controls {
            background: #f1f5f9;
            padding: 12px 20px;
            display: flex; gap: 10px; align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .print-controls button {
            padding: 8px 20px;
            background: #4f46e5; color: white;
            border: none; border-radius: 8px; cursor: pointer; font-size: 14px;
        }

        .print-controls a {
            padding: 8px 16px;
            background: #e2e8f0; color: #475569;
            border-radius: 8px; text-decoration: none; font-size: 14px;
        }

        .labels-grid {
            display: flex;
            flex-wrap: wrap;
            padding: 10mm;
            gap: 4mm;
        }

        .label {
            width: 62mm;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4mm;
            text-align: center;
            page-break-inside: avoid;
            background: white;
        }

        .label .book-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2mm;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            min-height: 2.6em;
        }

        .label .barcode-wrapper {
            margin: 2mm 0;
        }

        .label .barcode-wrapper svg {
            max-width: 100%;
            height: auto;
        }

        .label .copy-code {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            font-weight: bold;
            background: #f8f8f8;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }

        @media print {
            .print-controls { display: none !important; }
            body { background: white; }
            .labels-grid { padding: 5mm; }
        }
    </style>
</head>
<body>
<div class="print-controls">
    <button onclick="window.print()">🖨️ Cetak Sekarang</button>
    <a href="javascript:history.back()">← Kembali</a>
    <span style="font-size:13px;color:#64748b;">{{ $copies->count() }} label siap cetak</span>
</div>

<div class="labels-grid">
    @foreach($copies as $copy)
    <div class="label">
        <div class="book-title">{{ $copy->book->title }}</div>
        <div class="barcode-wrapper">
            {!! $barcodes[$copy->id] !!}
        </div>
        <div class="copy-code">{{ $copy->copy_code }}</div>
    </div>
    @endforeach
</div>

<script>
// Auto print setelah load
window.onload = function() {
    setTimeout(() => {
        // window.print(); // Uncomment untuk auto-print
    }, 500);
};
</script>
</body>
</html>
