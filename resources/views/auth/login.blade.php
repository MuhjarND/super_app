<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | PTA Papua Barat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #0f2640 0%, #1a3a5c 40%, #2c5282 100%);
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 80%;
            height: 200%;
            background: radial-gradient(ellipse, rgba(232, 168, 56, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(44, 82, 130, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .login-left .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e8a838, #f0c060);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f2640;
            margin-bottom: 24px;
            box-shadow: 0 12px 40px rgba(232, 168, 56, 0.3);
        }

        .login-left h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 8px;
            text-align: center;
        }

        .login-left h1 span {
            color: #e8a838;
        }

        .login-left p {
            font-size: 1rem;
            opacity: 0.7;
            text-align: center;
            max-width: 400px;
        }

        .features {
            margin-top: 40px;
            display: flex;
            gap: 30px;
        }

        .feature-item {
            text-align: center;
        }

        .feature-item .icon-wrap {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            color: #e8a838;
        }

        .feature-item span {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .login-right {
            width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            z-index: 1;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            padding: 48px 40px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
        }

        .login-card h2 {
            color: #1a3a5c;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .login-card .subtitle {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.82rem;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 2;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: #2c5282;
            box-shadow: 0 0 0 3px rgba(44, 82, 130, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a3a5c, #2c5282);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(26, 58, 92, 0.4);
        }

        .error-msg {
            color: #e53e3e;
            font-size: 0.82rem;
            margin-top: 4px;
        }

        .alert-danger {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .login-left {
                padding: 40px 20px;
            }

            .login-right {
                width: 100%;
                padding: 20px;
            }

            .features {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="login-left">
        <div class="logo-icon">P</div>
        <h1>Sistem Informasi <span>Persuratan</span></h1>
        <p>Pengadilan Tinggi Agama Papua Barat</p>
        <div class="features">
            <div class="feature-item">
                <div class="icon-wrap"><i class="fas fa-envelope"></i></div>
                <span>Persuratan</span>
            </div>
            <div class="feature-item">
                <div class="icon-wrap"><i class="fas fa-calendar"></i></div>
                <span>Cuti</span>
            </div>
            <div class="feature-item">
                <div class="icon-wrap"><i class="fas fa-users"></i></div>
                <span>Rapat</span>
            </div>
            <div class="feature-item">
                <div class="icon-wrap"><i class="fas fa-boxes"></i></div>
                <span>Persediaan</span>
            </div>
        </div>
    </div>
    <div class="login-right">
        <div class="login-card">
            <h2>Selamat Datang</h2>
            <p class="subtitle">Masuk ke akun Anda untuk melanjutkan</p>

            @if($errors->any())
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}"
                            placeholder="nama@pta-papuabarat.go.id" required autofocus>
                    </div>
                    @error('email')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" name="password" placeholder="Masukkan password"
                            required>
                    </div>
                    @error('password')
                        <div class="error-msg">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group" style="display: flex; align-items: center;">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}
                        style="margin-right: 8px;">
                    <label for="remember"
                        style="margin: 0; text-transform: none; font-weight: 400; font-size: 0.875rem;">Ingat
                        saya</label>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                </button>
            </form>
        </div>
    </div>
</body>

</html>