<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Memproses Login</title>
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
            width: min(520px, calc(100% - 32px));
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
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <main class="panel">
        <h1>Memproses login</h1>
        <p>Mohon tunggu sebentar.</p>
        <form id="autologin-form" method="POST" action="{{ route('autologin.login') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="redirect" value="{{ $redirect }}">
        </form>
    </main>
    <script>
        document.getElementById('autologin-form').submit();
    </script>
</body>
</html>
