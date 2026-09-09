<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $jenis }} - PAPEDA</title>
    @include('partials.app-icons')
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px; font-family:Arial,sans-serif; background:#f3f6fb; color:#0f172a; }
        .card { width:min(100%,620px); background:#fff; border:1px solid #dbe3ef; border-radius:20px; box-shadow:0 18px 44px rgba(15,23,42,.09); overflow:hidden; }
        .head { padding:22px 24px; color:#fff; background:linear-gradient(135deg,#4f46e5,#7c3aed); }
        .head h1 { margin:0 0 6px; font-size:22px; }
        .head p { margin:0; opacity:.88; }
        .body { padding:22px 24px; }
        .row { padding:12px 0; border-bottom:1px solid #eef2f7; }
        .row:last-child { border-bottom:0; }
        .label { margin-bottom:5px; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .value { line-height:1.5; }
        .note { margin-top:18px; padding:13px 15px; border-radius:12px; background:#fff7ed; color:#9a3412; font-size:14px; line-height:1.5; }
    </style>
</head>
<body>
    <main class="card">
        <header class="head"><h1>{{ $jenis }}</h1><p>Tautan publik PAPEDA</p></header>
        <section class="body">
            <div class="row"><div class="label">Nomor</div><div class="value">{{ $nomor ?: '-' }}</div></div>
            <div class="row"><div class="label">Perihal</div><div class="value">{{ $perihal ?: '-' }}</div></div>
            <div class="row"><div class="label">Tanggal</div><div class="value">{{ $tanggal ?: '-' }}</div></div>
            <div class="note">Berkas dokumen belum tersedia pada surat ini.</div>
        </section>
    </main>
</body>
</html>
