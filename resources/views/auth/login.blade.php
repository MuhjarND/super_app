<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | SIMANTAP</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_app.png') }}">
    <link rel="shortcut icon" href="{{ asset('logo_app.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --brand: #5b21b6;
            --brand-dark: #3b0764;
            --brand-mid: #6d28d9;
            --brand-soft: #ede9fe;
            --line: #dbe4f0;
            --text: #111827;
            --muted: #64748b;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: #ffffff;
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
            background: rgba(255, 255, 255, 0.76);
            backdrop-filter: blur(4px);
        }

        .login-loader.is-visible {
            display: flex;
        }

        .login-loader-card {
            width: 250px;
            padding: 22px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.98);
            text-align: center;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.18);
        }

        .login-loader-spinner {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            border-radius: 999px;
            border: 4px solid rgba(91, 33, 182, 0.16);
            border-top-color: var(--brand);
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 58vw) minmax(560px, 42vw);
            background: #ffffff;
        }

        .brand-side {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            color: #ffffff;
            text-align: center;
            background:
                radial-gradient(circle at 44% 48%, rgba(167, 139, 250, 0.34), transparent 0 170px),
                radial-gradient(circle at 50% 60%, rgba(255, 255, 255, 0.08), transparent 0 360px),
                linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 52%, var(--brand-mid) 100%);
        }

        .brand-content {
            width: min(100%, 560px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .brand-logo {
            width: 126px;
            height: 126px;
            margin-bottom: 28px;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-title {
            margin: 0;
            color: #ffffff;
            font-size: 2.3rem;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: 0;
        }

        .brand-subtitle {
            margin: 14px 0 0;
            color: rgba(255, 255, 255, 0.82);
            font-size: 1.05rem;
            line-height: 1.55;
            font-weight: 500;
        }

        .brand-divider {
            width: 76px;
            height: 4px;
            margin: 28px auto 24px;
            border-radius: 999px;
            background: #ffffff;
            opacity: .72;
        }

        .brand-description {
            margin: 0;
            max-width: 430px;
            color: rgba(255, 255, 255, 0.72);
            font-size: .98rem;
            line-height: 1.65;
            font-weight: 500;
        }

        .form-side {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 72px;
            background: #ffffff;
        }

        .form-wrap {
            width: min(100%, 600px);
        }

        .form-heading {
            margin-bottom: 34px;
        }

        .form-heading h1 {
            margin: 0 0 8px;
            color: var(--brand-dark);
            font-size: 1.72rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: 0;
        }

        .form-heading p {
            margin: 0;
            color: var(--muted);
            font-size: .96rem;
            line-height: 1.55;
        }

        .alert-danger {
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 8px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: .86rem;
            line-height: 1.55;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #1f2937;
            font-size: .9rem;
            font-weight: 800;
        }

        .input-group {
            display: grid;
            grid-template-columns: 58px 1fr;
            min-height: 58px;
            border: 1.5px solid var(--line);
            border-radius: 8px;
            background: #ffffff;
            overflow: hidden;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .input-group:focus-within {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(91, 33, 182, 0.1);
        }

        .input-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-right: 1px solid var(--line);
            color: #9ca3af;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: none;
            padding: 14px 16px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            background: transparent;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .error-msg {
            margin-top: 7px;
            color: var(--danger);
            font-size: .82rem;
        }

        .remember-row {
            margin: 4px 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: #4b5563;
            font-size: .9rem;
        }

        .remember-check {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .remember-check input {
            width: 18px;
            height: 18px;
            accent-color: var(--brand);
        }

        .forgot-link {
            color: var(--brand);
            font-weight: 800;
        }

        .btn-login {
            width: 100%;
            min-height: 56px;
            border: 0;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--brand), var(--brand-mid));
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            cursor: pointer;
            box-shadow: 0 14px 26px rgba(91, 33, 182, 0.24);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(91, 33, 182, 0.3);
        }

        .login-footer {
            margin-top: 22px;
            color: #94a3b8;
            text-align: center;
            font-size: .82rem;
            line-height: 1.55;
        }

        @media (max-width: 980px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .brand-side,
            .form-side {
                min-height: auto;
            }

            .brand-side {
                padding: 34px 22px 30px;
            }

            .brand-logo {
                width: 92px;
                height: 92px;
                margin-bottom: 18px;
            }

            .brand-title {
                font-size: 1.9rem;
            }

            .brand-divider {
                margin: 20px auto 16px;
            }

            .form-side {
                padding: 34px 22px 38px;
            }
        }

        @media (max-width: 520px) {
            .brand-description {
                display: none;
            }

            .remember-row {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="login-loader" id="loginLoader" aria-hidden="true">
        <div class="login-loader-card">
            <div class="login-loader-spinner"></div>
            <div style="font-weight:800;color:#0f172a;margin-bottom:4px;">Memproses login...</div>
            <div style="color:#64748b;font-size:.84rem;">Mohon tunggu sebentar.</div>
        </div>
    </div>

    <main class="login-page">
        <section class="brand-side">
            <div class="brand-content">
                <div class="brand-logo">
                    <img src="{{ asset('logo_app.png') }}" alt="Logo SIMANTAP">
                </div>
                <h1 class="brand-title">SIMANTAP</h1>
                <p class="brand-subtitle">Sistem Manajemen Terpadu PTA Papua Barat</p>
                <div class="brand-divider"></div>
                <p class="brand-description">Portal kerja terpadu untuk persuratan, rapat, cuti, Zona Integritas, perawatan aset, dan tindak lanjut internal.</p>
            </div>
        </section>

        <section class="form-side">
            <div class="form-wrap">
                <div class="form-heading">
                    <h1>Selamat Datang</h1>
                    <p>Masuk ke akun Anda untuk melanjutkan.</p>
                </div>

                @if($errors->any())
                    <div class="alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="login">NIP</label>
                        <div class="input-group">
                            <div class="input-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <input id="login" type="text" inputmode="numeric" autocomplete="username" class="form-control" name="login" value="{{ old('login') }}" placeholder="Masukkan NIP Anda" required autofocus>
                        </div>
                        @error('login')
                            <div class="error-msg">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input id="password" type="password" class="form-control" name="password" placeholder="Masukkan password" required>
                        </div>
                        @error('password')
                            <div class="error-msg">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="remember-row">
                        <label class="remember-check" for="remember">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Ingat Saya</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">Lupa Password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Masuk</span>
                    </button>
                </form>

                <div class="login-footer">Akses dilindungi untuk kebutuhan kerja internal PTA Papua Barat.</div>
            </div>
        </section>
    </main>

    <script>
        function toggleLoginLoader(show) {
            const loader = document.getElementById('loginLoader');
            if (!loader) return;
            loader.classList.toggle('is-visible', show);
            loader.setAttribute('aria-hidden', show ? 'false' : 'true');
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || form.tagName !== 'FORM') return;
            toggleLoginLoader(true);
        });

        window.addEventListener('pageshow', function () {
            toggleLoginLoader(false);
        });
    </script>
</body>
</html>
