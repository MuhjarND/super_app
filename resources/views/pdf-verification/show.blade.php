<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validasi PDF - SIMANTAP</title>
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <style>
        :root { --primary:#4f46e5; --accent:#7c3aed; --ink:#0f172a; --muted:#64748b; --line:#dbe3ef; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Arial, sans-serif; background:#f6f8fc; color:var(--ink); }
        .page { min-height:100vh; padding:24px; }
        .wrap { max-width:1180px; margin:0 auto; display:grid; grid-template-columns:360px minmax(0,1fr); gap:18px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:22px; box-shadow:0 18px 45px rgba(15,23,42,.08); overflow:hidden; }
        .head { padding:22px; background:linear-gradient(135deg,var(--primary),var(--accent)); color:#fff; }
        .head h1 { margin:0 0 6px; font-size:24px; }
        .head p { margin:0; opacity:.88; line-height:1.45; }
        .body { padding:20px 22px; }
        .status { display:inline-flex; align-items:center; gap:8px; padding:9px 12px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:800; margin-bottom:16px; }
        .row { padding:12px 0; border-bottom:1px solid #eef2f7; }
        .row:last-child { border-bottom:0; }
        .label { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); font-weight:800; margin-bottom:4px; }
        .value { font-size:14px; line-height:1.45; word-break:break-word; }
        .signer { border:1px solid #eef2f7; border-radius:14px; padding:12px; margin-top:8px; background:#f8fafc; }
        .preview { height:calc(100vh - 48px); min-height:620px; }
        .preview iframe { width:100%; height:100%; border:0; display:block; background:#fff; }
        .hash { font-size:11px; font-family: Consolas, monospace; color:#475569; }
        @media (max-width: 900px) {
            .page { padding:12px; }
            .wrap { grid-template-columns:1fr; }
            .preview { height:72vh; min-height:520px; }
            .head h1 { font-size:20px; }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="wrap">
            <section class="card">
                <div class="head">
                    <h1>Validasi PDF</h1>
                    <p>Informasi ini digunakan untuk memastikan dokumen berasal dari SIMANTAP dan belum diganti dari salinan yang tersimpan.</p>
                </div>
                <div class="body">
                    <div class="status"><i class="fas fa-check-circle"></i> Dokumen Terdaftar</div>
                    <div class="row"><div class="label">Judul</div><div class="value">{{ $verification->title }}</div></div>
                    <div class="row"><div class="label">Modul</div><div class="value">{{ ucwords(str_replace(['_', '-'], ' ', $verification->module)) }}</div></div>
                    <div class="row"><div class="label">Jenis Dokumen</div><div class="value">{{ ucwords(str_replace(['_', '-'], ' ', $verification->document_type)) }}</div></div>
                    <div class="row"><div class="label">Dibuat</div><div class="value">{{ optional($verification->finalized_at ?: $verification->created_at)->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') }} WIT</div></div>
                    <div class="row"><div class="label">Hash SHA-256</div><div class="value hash">{{ $verification->file_hash ?: '-' }}</div></div>

                    <div class="row">
                        <div class="label">Penandatangan / Validasi</div>
                        @forelse(($verification->signers ?: []) as $signer)
                            <div class="signer">
                                <strong>{{ $signer['name'] ?? '-' }}</strong><br>
                                <span>{{ $signer['role'] ?? ($signer['title'] ?? '-') }}</span><br>
                                <small>{{ $signer['signed_at'] ?? '-' }}</small>
                            </div>
                        @empty
                            <div class="value">Tidak ada data tanda tangan khusus pada dokumen ini.</div>
                        @endforelse
                    </div>
                </div>
            </section>
            <section class="card preview">
                @if($verification->file_path)
                    <iframe src="{{ route('pdf-verification.preview', $verification->token) }}"></iframe>
                @else
                    <div class="body">Preview PDF belum tersedia.</div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
