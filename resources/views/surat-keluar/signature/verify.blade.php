<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Tanda Tangan Surat Keluar</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; color:#0f172a; margin:0; padding:24px; }
        .card { max-width: 760px; margin: 0 auto; background:#fff; border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 12px 28px rgba(15,23,42,.06); overflow:hidden; }
        .head { padding:20px 22px; border-bottom:1px solid #e2e8f0; }
        .title { font-size:1.1rem; font-weight:800; margin-bottom:6px; }
        .badge { display:inline-block; padding:7px 12px; border-radius:999px; font-size:.82rem; font-weight:700; }
        .badge.ok { background:#dcfce7; color:#166534; }
        .badge.no { background:#fee2e2; color:#991b1b; }
        .body { padding:20px 22px; }
        .row { display:flex; padding:10px 0; border-bottom:1px solid #f1f5f9; gap:12px; }
        .row:last-child { border-bottom:none; }
        .label { width:210px; color:#64748b; font-weight:700; flex-shrink:0; }
        .value { flex:1; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <div class="title">Verifikasi Tanda Tangan Surat Keluar</div>
            <span class="badge {{ $data['valid'] ? 'ok' : 'no' }}">{{ $data['valid'] ? 'Valid' : 'Tidak Valid' }}</span>
        </div>
        <div class="body">
            <div class="row"><div class="label">Nomor Dokumen</div><div class="value">{{ $data['document_number'] }}</div></div>
            <div class="row"><div class="label">Jenis Dokumen</div><div class="value">{{ $data['document_type'] }}</div></div>
            <div class="row"><div class="label">Template</div><div class="value">{{ $data['document_title'] }}</div></div>
            <div class="row"><div class="label">Kategori</div><div class="value">{{ $data['category'] }}</div></div>
            <div class="row"><div class="label">Status Approval</div><div class="value">{{ $data['status'] }}</div></div>
            <div class="row"><div class="label">Penanda Tangan</div><div class="value">{{ $data['signer_name'] }}</div></div>
            <div class="row"><div class="label">Jabatan Penanda Tangan</div><div class="value">{{ $data['signer_title'] }}</div></div>
            <div class="row"><div class="label">Waktu Tanda Tangan</div><div class="value">{{ $data['acted_at'] }}</div></div>
        </div>
    </div>
</body>
</html>
