<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Pondok Pesantren Al Muttaqin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-color: var(--bg-page);
            transition: background-color var(--transition-default);
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.05);
            padding: 40px 36px;
            position: relative;
        }

        html.dark-mode .login-card {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            width: 58px;
            height: 58px;
            object-fit: contain;
            margin-bottom: 14px;
        }

        .login-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 13.5px;
            color: var(--text-secondary);
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .login-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .login-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border-radius: 10px;
            border: 1px solid var(--border-light);
            background-color: var(--bg-surface);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }

        .login-input:focus {
            outline: none;
            border-color: var(--electric-blue);
            box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.15);
        }

        .login-input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .login-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .login-btn {
            width: 100%;
            padding: 12px 18px;
            border-radius: 10px;
            background-color: var(--electric-blue);
            color: #FFFFFF;
            font-size: 14.5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(0, 82, 255, 0.28);
            transition: all 0.2s ease;
            margin-top: 6px;
        }

        .login-btn:hover {
            background-color: var(--electric-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0, 82, 255, 0.35);
        }

        .login-theme-pos {
            position: absolute;
            top: 24px;
            right: 24px;
        }
    </style>
</head>
<body class="login-page">

    <div class="login-theme-pos">
        <button type="button" class="theme-toggle" id="themeToggleBtn" aria-label="Ganti Tema">
            <span class="theme-toggle-icon theme-icon-moon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </span>
            <span class="theme-toggle-knob"></span>
            <span class="theme-toggle-icon theme-icon-sun">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor"></circle>
                    <line x1="12" y1="2" x2="12" y2="4.5"></line>
                    <line x1="12" y1="19.5" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="6.7" y2="6.7"></line>
                    <line x1="17.3" y1="17.3" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="4.5" y2="12"></line>
                    <line x1="19.5" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="6.7" y2="17.3"></line>
                    <line x1="17.3" y1="6.7" x2="19.07" y2="4.93"></line>
                </svg>
            </span>
        </button>
    </div>

    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Al Muttaqin" class="login-logo">
            <h1 class="login-title">Sistem Informasi Yayasan</h1>
            <p class="login-subtitle">Pondok Pesantren, SMP, & SMA Al Muttaqin</p>
        </div>

        @if($errors->any())
        <div class="alert-banner alert-error" style="margin-bottom: 20px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                {{ $errors->first() }}
            </div>
        </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="login-form">
            @csrf

            <div class="login-field">
                <label for="email" class="login-label">Alamat Email</label>
                <div class="login-input-wrap">
                    <svg class="login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" name="email" id="email" class="login-input" value="{{ old('email') }}" placeholder="nama@almuttaqin.id" required autofocus>
                </div>
            </div>

            <div class="login-field">
                <label for="password" class="login-label">Kata Sandi</label>
                <div class="login-input-wrap">
                    <svg class="login-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" name="password" id="password" class="login-input" placeholder="••••••••" required>
                </div>
            </div>

            <div class="login-options">
                <label class="login-checkbox-label">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--electric-blue);">
                    Ingat sesi saya
                </label>
            </div>

            <button type="submit" class="login-btn">
                <span>Masuk ke Sistem</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>
    </div>

    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
