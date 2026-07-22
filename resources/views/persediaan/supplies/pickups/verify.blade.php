<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Penerimaan Persediaan - PAPEDA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7ff; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; }
        .page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 620px; background: #fff; border: 1px solid #dfe5f4; border-radius: 22px; box-shadow: 0 18px 50px rgba(58, 49, 128, .12); overflow: hidden; }
        .head { padding: 24px 28px; color: #fff; background: linear-gradient(135deg, #4f46e5, #7c3aed); }
        .head h1 { margin: 0 0 6px; font-size: 22px; }
        .head p { margin: 0; opacity: .86; font-size: 14px; }
        .body { padding: 26px 28px; }
        .valid { display: inline-block; margin-bottom: 20px; padding: 8px 13px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; font-size: 13px; }
        dl { margin: 0; display: grid; grid-template-columns: 165px 1fr; gap: 12px 18px; }
        dt { color: #64748b; font-size: 13px; }
        dd { margin: 0; font-weight: 700; font-size: 14px; overflow-wrap: anywhere; }
        @media (max-width: 560px) { .page { padding: 12px; } .head, .body { padding: 20px; } dl { grid-template-columns: 1fr; gap: 4px; } dd { margin-bottom: 10px; } }
    </style>
</head>
<body>
<main class="page">
    <section class="card">
        <header class="head"><h1>Validasi Penerimaan Persediaan</h1><p>PAPEDA - PTA Papua Barat</p></header>
        <div class="body">
            <div class="valid">Data valid dan tercatat</div>
            <dl>
                <dt>Penerima</dt><dd>{{ optional($pickup->user)->name ?: '-' }}</dd>
                <dt>Barang</dt><dd>{{ $pickup->item_name_snapshot ?: optional($pickup->item)->name ?: '-' }}</dd>
                <dt>Jumlah</dt><dd>{{ $pickup->quantity_label }}</dd>
                <dt>Keperluan</dt><dd>{{ $pickup->purpose ?: '-' }}</dd>
                <dt>Tanggal diterima</dt><dd>{{ $pickup->pickup_date ? $pickup->pickup_date->translatedFormat('d F Y') : '-' }}</dd>
                <dt>Dicatat oleh</dt><dd>{{ optional($pickup->creator)->name ?: '-' }}</dd>
            </dl>
        </div>
    </section>
</main>
</body>
</html>
