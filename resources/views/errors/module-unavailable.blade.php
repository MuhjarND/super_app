<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $module['label'] }} | SIMANTAP</title>
    <link rel="icon" href="{{ asset('logo_app.png') }}">
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(145deg,#f5f3ff,#eef2ff 55%,#fff);font-family:"Segoe UI",sans-serif;color:#172033}.box{width:min(520px,100%);padding:36px;background:#fff;border:1px solid #ddd6fe;border-radius:24px;box-shadow:0 24px 70px rgba(76,29,149,.12);text-align:center}.icon{width:72px;height:72px;margin:0 auto 20px;display:grid;place-items:center;border-radius:22px;background:#ede9fe;color:#6d28d9;font-size:30px}h1{margin:0 0 10px;font-size:26px}p{margin:0;color:#64748b;line-height:1.7}.badge{display:inline-block;margin-bottom:14px;padding:6px 12px;border-radius:999px;background:#fff7ed;color:#c2410c;font-size:12px;font-weight:800;text-transform:uppercase}.actions{display:flex;gap:10px;justify-content:center;margin-top:26px}.btn{padding:11px 18px;border-radius:11px;text-decoration:none;font-weight:700;color:#fff;background:#6d28d9}.btn.alt{color:#475569;background:#f1f5f9}@media(max-width:480px){.box{padding:28px 20px}.actions{flex-direction:column}}
    </style>
</head>
<body>
    <main class="box">
        <div class="icon">&#9881;</div>
        <span class="badge">{{ $module['status'] === 'maintenance' ? 'Sedang Maintenance' : 'Belum Dipublikasikan' }}</span>
        <h1>{{ $module['label'] }}</h1>
        <p>{{ $module['status'] === 'maintenance' ? $module['maintenance_message'] : 'Modul ini belum tersedia untuk pengguna.' }}</p>
        <div class="actions">
            <a class="btn alt" href="javascript:history.back()">Kembali</a>
            <a class="btn" href="{{ route('dashboard') }}">Dashboard</a>
        </div>
    </main>
</body>
</html>
