<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | PTA Papua Barat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --navy-950: #091829;
            --navy-900: #0f2640;
            --navy-800: #163654;
            --blue-500: #2c6bed;
            --gold-500: #d9a441;
            --surface: rgba(255, 255, 255, 0.98);
            --line: rgba(15, 38, 64, 0.12);
            --text: #102132;
            --muted: #64748b;
            --danger: #dc2626;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: #ffffff;
            background:
                radial-gradient(circle at top left, rgba(217, 164, 65, 0.14), transparent 26%),
                radial-gradient(circle at top right, rgba(44, 107, 237, 0.14), transparent 28%),
                linear-gradient(180deg, var(--navy-950) 0%, var(--navy-900) 48%, var(--navy-800) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .login-loader {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(6, 17, 29, 0.48);
            backdrop-filter: blur(4px);
        }

        .login-loader.is-visible {
            display: flex;
        }

        .login-loader-card {
            width: 250px;
            padding: 22px;
            border-radius: 20px;
            background: rgba(255,255,255,0.98);
            text-align: center;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.24);
        }

        .login-loader-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            border-radius: 999px;
            border: 4px solid rgba(44, 107, 237, 0.16);
            border-top-color: var(--navy-900);
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-shell {
            width: min(100%, 560px);
            text-align: center;
        }

        .brand-block {
            margin-bottom: 28px;
        }

        .brand-logo {
            width: 86px;
            height: 86px;
            margin: 0 auto 18px;
            overflow: hidden;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 12px;
            display: block;
        }

        .brand-title {
            margin: 0 0 8px;
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: -0.05em;
        }

        .brand-subtitle {
            margin: 0;
            color: rgba(255,255,255,0.72);
            font-size: 1rem;
            line-height: 1.6;
        }

        .login-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 28px;
            padding: 28px 26px 24px;
            box-shadow: 0 28px 70px rgba(5, 16, 30, 0.24);
            text-align: left;
            max-width: 500px;
            margin: 0 auto;
        }

        .login-card h1 {
            margin: 0 0 24px;
            text-align: center;
            font-size: 1.72rem;
            line-height: 1;
            letter-spacing: -0.05em;
            color: var(--navy-900);
        }

        .alert-danger {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: .86rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: .9rem;
            font-weight: 700;
            color: var(--text);
        }

        .input-shell {
            position: relative;
        }

        .input-shell i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: .95rem;
        }

        .form-control {
            width: 100%;
            min-height: 54px;
            padding: 14px 16px 14px 46px;
            border-radius: 14px;
            border: 1.5px solid #dbe4f0;
            background: #fff;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .98rem;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .form-control:focus {
            border-color: var(--blue-500);
            box-shadow: 0 0 0 4px rgba(44, 107, 237, 0.12);
        }

        .error-msg {
            margin-top: 6px;
            color: var(--danger);
            font-size: .82rem;
        }

        .remember-row {
            margin: 4px 0 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: .84rem;
            color: var(--muted);
        }

        .remember-check {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #475569;
            font-weight: 600;
        }

        .remember-check input {
            width: 16px;
            height: 16px;
        }

        .btn-login {
            width: 100%;
            min-height: 56px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--navy-900), var(--navy-800));
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            box-shadow: 0 18px 34px rgba(15, 38, 64, 0.2);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 22px 40px rgba(15, 38, 64, 0.26);
        }

        .login-footer {
            margin-top: 18px;
            text-align: center;
            color: rgba(255,255,255,0.68);
            font-size: .84rem;
        }

        @media (max-width: 640px) {
            body {
                padding: 16px;
            }

            .brand-title {
                font-size: 1.85rem;
            }

            .brand-subtitle {
                font-size: .94rem;
            }

            .login-card {
                padding: 24px 18px 20px;
                border-radius: 24px;
            }

            .login-card h1 {
                font-size: 1.7rem;
            }

            .remember-row {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-loader" id="loginLoader" aria-hidden="true">
        <div class="login-loader-card">
            <div class="login-loader-spinner"></div>
            <div style="font-weight:800;color:#0c2136;margin-bottom:4px;">Memproses login...</div>
            <div style="color:#64748b;font-size:.84rem;">Mohon tunggu sebentar.</div>
        </div>
    </div>

    <div class="login-shell">
        <div class="brand-block">
            <div class="brand-logo">
                <img src="{{ asset('logo_qr.png') }}" alt="Logo PTA Papua Barat">
            </div>
            <h1 class="brand-title">PTA Papua Barat</h1>
            <p class="brand-subtitle">Sistem Informasi Internal</p>
        </div>

        <div class="login-card">
            <h1>Masuk ke Akun Anda</h1>

            @if($errors->any())
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-shell">
                        <i class="fas fa-envelope"></i>
                        <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Masukkan email Anda" required autofocus>
                    </div>
                    @error('email')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-shell">
                        <i class="fas fa-lock"></i>
                        <input id="password" type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                    </div>
                    @error('password')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>

                <div class="remember-row">
                    <label class="remember-check" for="remember">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Masuk</span>
                </button>
            </form>
        </div>

        <div class="login-footer">Hak Cipta &copy; PTA Papua Barat - {{ now()->format('Y') }}</div>
    </div>

    <script>
        function toggleLoginLoader(show) {
            const loader = document.getElementById('loginLoader');
            if (!loader) return;
            loader.classList.toggle('is-visible', show);
            loader.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        document.querySelector('form').addEventListener('submit', function () {
            toggleLoginLoader(true);
        });

        window.addEventListener('pageshow', function () {
            toggleLoginLoader(false);
        });
    </script>
</body>
</html>
