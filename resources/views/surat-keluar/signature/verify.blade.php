<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Tanda Tangan Surat Keluar</title>
    @include('partials.app-icons')
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; background:#f8fafc; color:#0f172a; margin:0; }
        .page { max-width: 820px; margin: 0 auto; padding: 28px 16px 36px; }
        .card { background:#fff; border:1px solid #e2e8f0; border-radius:20px; box-shadow:0 12px 28px rgba(15,23,42,.06); overflow:hidden; }
        .head { padding:22px 22px 18px; border-bottom:1px solid #e2e8f0; background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%); }
        .title { font-size:1.2rem; font-weight:800; margin-bottom:6px; }
        .subtitle { color:#475569; font-size:0.92rem; }
        .badge { display:inline-flex; align-items:center; padding:8px 14px; border-radius:999px; font-size:.84rem; font-weight:800; margin-top:14px; }
        .badge.ok { background:#dcfce7; color:#166534; }
        .badge.no { background:#fee2e2; color:#991b1b; }
        .body { padding:20px 22px 22px; }
        .row { display:flex; padding:12px 0; border-bottom:1px solid #f1f5f9; gap:14px; }
        .row:last-child { border-bottom:none; }
        .label { width:220px; color:#64748b; font-weight:700; flex-shrink:0; font-size:0.8rem; text-transform:uppercase; letter-spacing:.04em; }
        .value { flex:1; line-height:1.5; word-break:break-word; }
        .note { margin-top:18px; padding:16px 18px; border-radius:14px; background:#eef2ff; border:1px solid #c7d2fe; color:#312e81; font-size:0.92rem; line-height:1.55; }
        @media (max-width: 768px) {
            .page { padding: 18px 12px 26px; }
            .row { display:block; }
            .label { width:auto; display:block; margin-bottom:5px; font-size:0.74rem; }
            .title { font-size:1.05rem; }
            .subtitle { font-size:0.86rem; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="head">
                <div class="title">Verifikasi Tanda Tangan Surat Keluar</div>
                <div class="subtitle">PAPEDA</div>
                <span class="badge {{ $data['valid'] ? 'ok' : 'no' }}">{{ $data['valid'] ? 'Valid dan terverifikasi' : 'Tidak valid / belum final' }}</span>
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
                <div class="note">
                    @if($data['valid'])
                        Tanda tangan digital pada surat keluar ini sah dan tercatat pada data approval final yang tersimpan di sistem. Halaman publik ini hanya menampilkan informasi verifikasi dokumen dan penanda tangan.
                    @else
                        Dokumen ini belum berada pada status final yang sah untuk tanda tangan elektronik, atau tautan verifikasinya tidak memenuhi syarat validasi.
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
