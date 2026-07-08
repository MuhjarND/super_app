<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Link Login Tidak Valid</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            color: #111827;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .panel {
            width: min(560px, calc(100% - 32px));
            padding: 32px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, .12);
        }
        h1 {
            margin: 0 0 12px;
            font-size: 24px;
            line-height: 1.25;
        }
        p {
            margin: 0 0 24px;
            color: #374151;
            line-height: 1.6;
        }
        a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 6px;
            color: #fff;
            background: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Link login tidak valid</h1>
        <p>{{ $message }}</p>
        <a href="{{ route('login') }}">Kembali ke login</a>
    </main>
</body>
</html>
