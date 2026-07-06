<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi 2 Faktor | PAPEDA</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_app_new.png') }}">
    <link rel="shortcut icon" href="{{ asset('logo_app_new.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/logo-app-192.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#5b21b6">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100vh;font-family:'Inter',sans-serif;background:linear-gradient(180deg,#f8fafc 0%,#eef2ff 55%,#f5f3ff 100%);display:flex;align-items:center;justify-content:center;padding:20px;color:#0f172a}
        .shell{width:min(100%,440px);background:#fff;border:1px solid #e0e7ff;border-radius:26px;padding:28px;box-shadow:0 20px 50px rgba(99,102,241,.08)}
        h1{margin:0 0 10px;font-size:1.6rem;font-weight:800}
        p{margin:0 0 22px;color:#64748b;line-height:1.6}
        .form-control{width:100%;min-height:52px;border-radius:14px;border:1.5px solid #dbe4f0;padding:12px 14px;font-size:1rem;box-sizing:border-box}
        .btn{width:100%;min-height:54px;border:none;border-radius:14px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;font-weight:800;font-size:1rem;cursor:pointer;margin-top:16px}
        .error{color:#dc2626;font-size:.86rem;margin-top:8px}
        .back{display:block;text-align:center;margin-top:16px;color:#64748b;font-size:.9rem}
    </style>
</head>
<body>
    <div class="shell">
        <h1>Verifikasi 2 Faktor</h1>
        <p>Masukkan kode 6 digit dari aplikasi authenticator atau salah satu backup recovery code untuk melanjutkan login.</p>
        <form method="POST" action="{{ route('two-factor.challenge.store') }}">
            @csrf
            <input type="text" name="code" class="form-control" maxlength="32" placeholder="000000 atau ABCD-EFGH" required autofocus>
            @error('code')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn">Verifikasi</button>
        </form>
        <a href="{{ route('login') }}" class="back">Kembali ke login</a>
    </div>
</body>
</html>
