<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Pondok Pesantren Al Muttaqin</title>

    <!-- Web App & PWA Meta (iOS & Android) -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Al-Muttaqin">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Al-Muttaqin">
    <meta name="theme-color" content="#5b4ee5">

    <!-- Icons & Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('apple-touch-icon-precomposed.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v=2">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v=2">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="manifest" href="{{ asset('manifest.json') }}?v=2">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #5b4ee5 0%, #764ba2 50%, #9b51e0 100%);
            --hero-gradient: linear-gradient(135deg, #5c4fe6 0%, #7853de 45%, #b451c8 80%, #db4f8c 100%);
            --btn-gradient: linear-gradient(90deg, #6250ea 0%, #8b4fda 55%, #bd4ec8 100%);
            --btn-hover-gradient: linear-gradient(90deg, #523fd9 0%, #7c3ecb 55%, #ac3db7 100%);
            --accent-warm: linear-gradient(90deg, #ff9800 0%, #f43f5e 100%);
            --text-dark: #1e1b4b;
            --text-muted: #64748b;
            --input-bg: #f0f2f9;
            --input-focus-border: #6366f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body.login-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px 24px;
            background: radial-gradient(circle at 15% 20%, #7a63ec 0%, #6350db 35%, #5943c8 65%, #6a42b9 85%, #7641bc 100%);
            background-attachment: fixed;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient Glowing Background Elements */
        .ambient-glow-1 {
            position: fixed;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: rgba(236, 72, 153, 0.22);
            filter: blur(100px);
            bottom: -100px;
            right: -100px;
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: fixed;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.3);
            filter: blur(110px);
            top: -120px;
            left: -80px;
            pointer-events: none;
            z-index: 0;
        }

        /* Main Container Card */
        .login-wrapper {
            width: 100%;
            max-width: 960px;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-card {
            width: 100%;
            display: flex;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 65px -12px rgba(30, 20, 80, 0.42), 0 10px 25px -8px rgba(0, 0, 0, 0.15);
            min-height: 530px;
            position: relative;
        }

        /* -------------------------------------------------------------
           LEFT SIDE (DESKTOP WELCOME & ARTWORK BANNER)
           ------------------------------------------------------------- */
        .login-hero-side {
            flex: 1.15;
            background: var(--hero-gradient);
            padding: 55px 45px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #ffffff;
        }

        /* SVG Vector Artwork (Angled Capsules & Glow Orbs) */
        .hero-artwork-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .hero-content-top {
            position: relative;
            z-index: 2;
            max-width: 420px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .hero-badge img {
            width: 22px;
            height: 22px;
            object-fit: contain;
            border-radius: 50%;
            background: #ffffff;
            padding: 2px;
        }

        .hero-title {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.22;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 16px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        }

        .hero-title-highlight {
            display: block;
            color: #ffffff;
            font-weight: 800;
        }

        .hero-description {
            font-size: 14px;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.88);
            margin: 0;
            max-width: 380px;
            font-weight: 400;
        }

        .hero-content-bottom {
            position: relative;
            z-index: 2;
            margin-top: 30px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-content-bottom svg {
            width: 15px;
            height: 15px;
            opacity: 0.85;
        }

        /* -------------------------------------------------------------
           RIGHT SIDE (LOGIN FORM)
           ------------------------------------------------------------- */
        .login-form-side {
            flex: 0.95;
            background: #ffffff;
            padding: 50px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        /* Mobile-only Top Brand Header */
        .mobile-brand-header {
            display: none;
            text-align: center;
            margin-bottom: 24px;
        }

        .mobile-brand-logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            margin-bottom: 8px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.08));
        }

        .mobile-brand-title {
            font-size: 17px;
            font-weight: 800;
            color: #1e1b4b;
            letter-spacing: -0.01em;
            line-height: 1.25;
        }

        .mobile-brand-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Form Heading */
        .form-heading {
            text-align: center;
            margin-bottom: 28px;
        }

        .form-title {
            font-size: 18px;
            font-weight: 800;
            color: #5c4ee5;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }

        .form-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Alert Box */
        .login-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .login-alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* Input Controls (Pill Shaped per reference image) */
        .input-group-pill {
            position: relative;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
        }

        .input-icon-pill {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #8b80b6;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-input-pill {
            width: 100%;
            height: 48px;
            border-radius: 9999px;
            border: 1.5px solid transparent;
            background-color: var(--input-bg);
            color: #1e293b;
            font-size: 13.5px;
            font-weight: 500;
            padding: 0 46px 0 48px;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input-pill::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-input-pill:focus {
            background-color: #ffffff;
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.14);
        }

        .form-input-pill:focus + .input-icon-pill,
        .input-group-pill:focus-within .input-icon-pill {
            color: #5c4ee5;
        }

        /* Password Visibility Toggle */
        .btn-toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 6px;
            color: #94a3b8;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: color 0.2s, background-color 0.2s;
        }

        .btn-toggle-password:hover {
            color: #5c4ee5;
            background-color: rgba(99, 102, 241, 0.08);
        }

        .btn-toggle-password svg {
            width: 17px;
            height: 17px;
        }

        /* Options Row (Remember me & Forgot Password) */
        .login-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            margin-bottom: 24px;
            padding: 0 6px;
            font-size: 12.5px;
        }

        .remember-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
            user-select: none;
            font-weight: 500;
        }

        .custom-checkbox {
            width: 17px;
            height: 17px;
            border-radius: 50%;
            border: 1.5px solid #cbd5e1;
            appearance: none;
            -webkit-appearance: none;
            outline: none;
            cursor: pointer;
            display: grid;
            place-content: center;
            transition: all 0.2s ease;
            background: #ffffff;
            margin: 0;
        }

        .custom-checkbox:checked {
            background-color: #5c4ee5;
            border-color: #5c4ee5;
        }

        .custom-checkbox:checked::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #ffffff;
        }

        .forgot-password-link {
            color: #8b80b6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            cursor: pointer;
        }

        .forgot-password-link:hover {
            color: #5c4ee5;
            text-decoration: underline;
        }

        /* Submit Button (Pill with Purple-Magenta Gradient) */
        .btn-login-submit {
            width: 100%;
            height: 48px;
            border-radius: 9999px;
            background: var(--btn-gradient);
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 24px rgba(115, 80, 231, 0.38);
            transition: all 0.25s ease;
        }

        .btn-login-submit:hover {
            background: var(--btn-hover-gradient);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(115, 80, 231, 0.48);
        }

        .btn-login-submit:active {
            transform: translateY(0);
            box-shadow: 0 4px 14px rgba(115, 80, 231, 0.35);
        }

        /* -------------------------------------------------------------
           FOOTER ATTRIBUTION (Instagram logo & account almuttaqin.madiun)
           ------------------------------------------------------------- */
        .login-footer {
            margin-top: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }

        .ig-attribution-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 18px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .ig-attribution-pill:hover {
            background: rgba(255, 255, 255, 0.26);
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.18);
            color: #ffffff;
        }

        .ig-icon-container {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .ig-icon-svg {
            width: 14px;
            height: 14px;
            stroke: #ffffff;
            stroke-width: 2.2;
            fill: none;
        }

        /* -------------------------------------------------------------
           RESPONSIVE MOBILE VIEW (Hide left hero side, show only form)
           ------------------------------------------------------------- */
        @media (max-width: 860px) {
            body.login-page {
                padding: 20px 16px;
            }

            .login-card {
                flex-direction: column;
                max-width: 440px;
                min-height: auto;
                border-radius: 22px;
            }

            /* 1. Sembunyikan bagian kiri pada tampilan mobile */
            .login-hero-side {
                display: none !important;
            }

            /* Tampilkan header brand di bagian form saat mobile */
            .mobile-brand-header {
                display: block;
            }

            .login-form-side {
                flex: 1;
                padding: 38px 26px 36px;
            }

            .form-heading {
                margin-bottom: 22px;
            }

            .form-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body class="login-page">

    <!-- Ambient Glowing Orbs -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- ========================================================
                 BAGIAN KIRI (DESKTOP): Ucapan Selamat Datang & Artwork
                 ======================================================== -->
            <div class="login-hero-side">
                <!-- Artwork Vektor Abstrak (Diagonal Rounded Capsules & Glow Orbs) -->
                <svg class="hero-artwork-svg" viewBox="0 0 520 540" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                    <defs>
                        <!-- Warm Gradient 1 (Orange to Magenta) -->
                        <linearGradient id="warmGrad1" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFA41B" />
                            <stop offset="100%" stop-color="#FF5151" />
                        </linearGradient>

                        <!-- Warm Gradient 2 (Yellow to Orange) -->
                        <linearGradient id="warmGrad2" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFDD00" />
                            <stop offset="100%" stop-color="#FF7A00" />
                        </linearGradient>

                        <!-- Soft Glow Orb -->
                        <radialGradient id="orbGlow" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#FF5784" stop-opacity="0.65" />
                            <stop offset="100%" stop-color="#FF5784" stop-opacity="0" />
                        </radialGradient>
                    </defs>

                    <!-- Glowing Orb di bagian kanan tengah -->
                    <circle cx="430" cy="200" r="110" fill="url(#orbGlow)" />
                    <circle cx="340" cy="420" r="80" fill="url(#orbGlow)" opacity="0.4" />

                    <!-- Diagonal Capsules (Rotated -38deg to match the illustration) -->
                    <g transform="rotate(-38 260 270)">
                        <!-- Capsule 1 (Thick, Warm Gradient) -->
                        <rect x="20" y="340" width="160" height="42" rx="21" fill="url(#warmGrad1)" opacity="0.9" />

                        <!-- Capsule 2 (Large Hero Capsule) -->
                        <rect x="110" y="270" width="220" height="52" rx="26" fill="url(#warmGrad2)" opacity="0.95" />

                        <!-- Capsule 3 (Right Lower Capsule) -->
                        <rect x="260" y="360" width="180" height="46" rx="23" fill="url(#warmGrad1)" opacity="0.88" />

                        <!-- Capsule 4 (Back Subtle Layer) -->
                        <rect x="50" y="210" width="120" height="36" rx="18" fill="#FFA41B" opacity="0.4" />

                        <!-- Accent Streak Lines -->
                        <rect x="180" y="195" width="85" height="7" rx="3.5" fill="#FFDD00" opacity="0.8" />
                        <rect x="130" y="235" width="115" height="8" rx="4" fill="#FFA41B" opacity="0.7" />
                        <rect x="230" y="250" width="70" height="6" rx="3" fill="#FF5151" opacity="0.75" />
                        <rect x="80" y="405" width="95" height="7" rx="3.5" fill="#FF7A00" opacity="0.6" />
                        <rect x="310" y="335" width="80" height="6" rx="3" fill="#FFDD00" opacity="0.65" />
                        <rect x="360" y="430" width="60" height="5" rx="2.5" fill="#FF5151" opacity="0.5" />
                    </g>
                </svg>

                <div class="hero-content-top">
                    <div class="hero-badge">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo Al Muttaqin">
                        <span>Pondok Pesantren Al Muttaqin</span>
                    </div>

                    <h1 class="hero-title">
                        SELAMAT DATANG<br>
                        <span class="hero-title-highlight">di AL MUTTAQIN TERPADU</span>
                    </h1>

                    <p class="hero-description">
                        Sistem Informasi Manajemen Terpadu Pondok Pesantren, SMP, dan SMA Al Muttaqin Madiun. Layanan akademik, administrasi santri, dan kepegawaian dalam satu pintu.
                    </p>
                </div>

                <div class="hero-content-bottom">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Koneksi aman terenkripsi &bull; Portal Resmi Yayasan</span>
                </div>
            </div>

            <!-- ========================================================
                 BAGIAN KANAN: Formulir Login
                 ======================================================== -->
            <div class="login-form-side">

                <!-- Header Brand khusus saat tampilan mobile -->
                <div class="mobile-brand-header">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo Al Muttaqin" class="mobile-brand-logo">
                    <h2 class="mobile-brand-title">AL MUTTAQIN TERPADU</h2>
                    <p class="mobile-brand-sub">Sistem Informasi Manajemen Yayasan</p>
                </div>

                <div class="form-heading">
                    <h3 class="form-title">USER LOGIN</h3>
                    <p class="form-subtitle">Masukkan akun Anda untuk melanjutkan</p>
                </div>

                @if($errors->any())
                <div class="login-alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div>{{ $errors->first() }}</div>
                </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf

                    <!-- Input Alamat Email / Username -->
                    <div class="input-group-pill">
                        <input type="email"
                               name="email"
                               id="email"
                               class="form-input-pill"
                               value="{{ old('email') }}"
                               placeholder="Email Pengguna"
                               required
                               autofocus
                               autocomplete="username">
                        <svg class="input-icon-pill" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>

                    <!-- Input Kata Sandi -->
                    <div class="input-group-pill">
                        <input type="password"
                               name="password"
                               id="passwordInput"
                               class="form-input-pill"
                               placeholder="Kata Sandi"
                               required
                               autocomplete="current-password">
                        <svg class="input-icon-pill" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <button type="button" class="btn-toggle-password" id="btnTogglePassword" onclick="togglePasswordVisibility()" aria-label="Lihat kata sandi">
                            <svg id="iconEyeOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg id="iconEyeClosed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>

                    <!-- Pilihan Ingat Saya & Lupa Kata Sandi -->
                    <div class="login-options-row">
                        <label class="remember-checkbox-label">
                            <input type="checkbox" name="remember" value="1" class="custom-checkbox" {{ old('remember') ? 'checked' : '' }}>
                            <span>Remember</span>
                        </label>
                        <a href="javascript:void(0)" class="forgot-password-link" onclick="handleForgotPassword()">Forgot password?</a>
                    </div>

                    <!-- Tombol Masuk / Login Gradient Pill -->
                    <button type="submit" class="btn-login-submit">
                        <span>LOGIN</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- ========================================================
             FOOTER: Logo Instagram & Akun almuttaqin.madiun
             ======================================================== -->
        <div class="login-footer">
            <a href="https://www.instagram.com/almuttaqin.madiun" target="_blank" rel="noopener noreferrer" class="ig-attribution-pill" title="Kunjungi Instagram Resmi Al Muttaqin Madiun">
                <span class="ig-icon-container">
                    <svg class="ig-icon-svg" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </span>
                <span>almuttaqin.madiun</span>
            </a>
        </div>
    </div>

    <!-- Script Interaksi -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const iconEyeOpen = document.getElementById('iconEyeOpen');
            const iconEyeClosed = document.getElementById('iconEyeClosed');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                iconEyeOpen.style.display = 'none';
                iconEyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                iconEyeOpen.style.display = 'block';
                iconEyeClosed.style.display = 'none';
            }
        }

        function handleForgotPassword() {
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Lupa Kata Sandi?',
                    text: 'Silakan hubungi administrator yayasan atau pengurus sistem Al Muttaqin untuk melakukan reset kata sandi akun Anda.',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#5c4ee5'
                });
            } else {
                alert('Silakan hubungi administrator yayasan atau pengurus sistem Al Muttaqin untuk melakukan reset kata sandi akun Anda.');
            }
        }
    </script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>