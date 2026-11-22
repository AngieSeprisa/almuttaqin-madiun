<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>@yield('title', 'SPMB Al Muttaqin')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <style>
        body.spmb-body {
            background: #F1F5F9;
            color: #0F172A;
            min-height: 100vh;
            margin: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Topbar ===== */
        .spmb-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E2E8F0;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
        }
        .spmb-topbar-inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }
        .spmb-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #0F172A;
            min-width: 0;
        }
        .spmb-brand-logo {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            padding: 4px;
        }
        .spmb-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .spmb-brand-text {
            min-width: 0;
        }
        .spmb-brand-title {
            font-size: 15.5px;
            font-weight: 800;
            line-height: 1.25;
            color: #0F172A;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .spmb-brand-meta {
            font-size: 12px;
            color: #64748B;
            font-weight: 600;
            margin-top: 1px;
        }

        /* Segmented Tab Navigation */
        .spmb-nav {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px;
            border-radius: 12px;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            flex-shrink: 0;
        }
        .spmb-nav-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            text-decoration: none;
            transition: all 0.16s ease;
            white-space: nowrap;
        }
        .spmb-nav-tab svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }
        .spmb-nav-tab:hover {
            color: #0F172A;
        }
        .spmb-nav-tab.is-active {
            background: #0052FF;
            color: #FFFFFF;
            box-shadow: 0 2px 8px rgba(0, 82, 255, 0.25);
        }

        /* ===== Main Container ===== */
        .spmb-container {
            max-width: 960px;
            margin: 0 auto;
            padding: 20px 20px 48px;
        }

        /* ===== Info Summary Card ===== */
        .spmb-info-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }
        .spmb-info-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        .spmb-wave-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1D4ED8;
            font-size: 12.5px;
            font-weight: 700;
        }
        .spmb-wave-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #2563EB;
            flex-shrink: 0;
        }
        .spmb-info-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .spmb-info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .spmb-info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748B;
        }
        .spmb-info-value {
            font-size: 13.5px;
            font-weight: 700;
            color: #0F172A;
            line-height: 1.35;
            word-break: break-word;
        }

        /* ===== Standard Card ===== */
        .spmb-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        }

        /* ===== Wizard Refinements for Public SPMB ===== */
        .spmb-body .wizard-card {
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
            margin-bottom: 0;
        }
        .spmb-progress-track {
            height: 3px;
            width: 100%;
            background: #EEF2F7;
            overflow: hidden;
        }
        .spmb-progress-bar {
            height: 100%;
            width: 25%;
            background: linear-gradient(90deg, #0052FF, #3B82F6);
            transition: width 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .spmb-body .wizard-body {
            padding: 26px 28px;
        }
        .spmb-body .wizard-label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }
        .spmb-body .wizard-input,
        .spmb-body .wizard-select,
        .spmb-body .wizard-textarea {
            border: 1.5px solid #DCE3EE;
            border-radius: 11px;
            font-size: 14.5px;
            padding: 11px 14px;
            color: #0F172A;
            background-color: #FFFFFF;
            min-height: 46px;
        }
        .spmb-body .wizard-textarea {
            min-height: 82px;
        }
        .spmb-body .wizard-select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 38px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 13px center;
            background-size: 16px;
        }
        .spmb-body .wizard-input::placeholder,
        .spmb-body .wizard-textarea::placeholder {
            color: #94A3B8;
        }
        .spmb-body .wizard-input:focus,
        .spmb-body .wizard-select:focus,
        .spmb-body .wizard-textarea:focus {
            border-color: #0052FF;
            box-shadow: 0 0 0 3.5px rgba(0, 82, 255, 0.12);
        }
        .spmb-body .wizard-subcard {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 16px;
        }
        .spmb-body .wizard-subcard-title {
            font-size: 14px;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 14px;
            padding-left: 10px;
            border-left: 3px solid #0052FF;
        }
        .spmb-body .wizard-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #F1F5F9;
        }
        .spmb-body .btn-wizard-next,
        .spmb-body .btn-wizard-prev {
            min-height: 46px;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        .spmb-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .spmb-span-full {
            grid-column: 1 / -1;
        }
        .spmb-step-short {
            display: none;
        }

        /* ===== Responsive Mobile Optimization ===== */
        @media (max-width: 768px) {
            .spmb-topbar-inner {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 14px;
                gap: 10px;
            }
            .spmb-brand {
                gap: 10px;
            }
            .spmb-brand-logo {
                width: 38px;
                height: 38px;
            }
            .spmb-brand-title {
                font-size: 15px;
            }
            .spmb-nav {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
                box-sizing: border-box;
            }
            .spmb-nav-tab {
                padding: 9px 10px;
                font-size: 13px;
            }
            .spmb-container {
                padding: 14px 12px 36px;
            }
            .spmb-info-card {
                padding: 13px 14px;
                border-radius: 14px;
                margin-bottom: 14px;
            }
            .spmb-info-top {
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            .spmb-wave-badge {
                font-size: 12px;
                padding: 4px 10px;
            }
            .spmb-info-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px 12px;
            }
            .spmb-info-item.spmb-info-wide {
                grid-column: 1 / -1;
                padding-top: 8px;
                border-top: 1px dashed #E2E8F0;
            }
            .spmb-card {
                padding: 18px 15px;
                border-radius: 14px;
            }

            /* 4-column compact mobile stepper (zero clipping!) */
            .spmb-body .wizard-card {
                border-radius: 16px;
            }
            .spmb-body .wizard-stepper {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                overflow: visible;
                border-bottom: none;
                padding: 12px 6px 10px;
                gap: 4px;
                background: #FAFBFD;
            }
            .spmb-body .wizard-divider {
                display: none !important;
            }
            .spmb-body .wizard-step {
                min-width: 0 !important;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                gap: 5px;
                padding: 4px 2px !important;
                background: transparent !important;
                text-align: center;
            }
            .spmb-body .wizard-step-circle {
                width: 30px;
                height: 30px;
                font-size: 12px;
                border-width: 1.5px;
            }
            .spmb-body .wizard-step.is-active .wizard-step-circle {
                background: #EFF6FF;
                border-color: #0052FF;
                color: #0052FF;
                box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
            }
            .spmb-body .wizard-step-title {
                font-size: 11px !important;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
            }
            .spmb-step-full {
                display: none;
            }
            .spmb-step-short {
                display: inline;
            }

            .spmb-body .wizard-body {
                padding: 18px 15px 20px;
            }
            .spmb-form-grid {
                grid-template-columns: 1fr;
                gap: 14px;
            }
            /* Prevent iOS Safari auto-zoom on focus */
            .spmb-body .wizard-input,
            .spmb-body .wizard-select,
            .spmb-body .wizard-textarea {
                font-size: 16px;
                min-height: 48px;
                padding: 11px 14px;
            }
            .spmb-body .wizard-select {
                padding-right: 38px;
            }
            .spmb-body .wizard-subcard {
                padding: 14px;
                border-radius: 12px;
                margin-bottom: 14px;
            }
            .spmb-body .wizard-footer {
                margin-top: 20px;
                padding-top: 16px;
                gap: 10px;
            }
            .spmb-body .wizard-footer > button {
                flex: 1;
                padding-left: 14px !important;
                padding-right: 14px !important;
            }
            .spmb-body .gform-grid {
                grid-template-columns: 1fr !important;
            }

            /* Mobile Bukti & Cek Status */
            .spmb-actions-stack {
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
            }
            .spmb-actions-stack .btn {
                width: 100%;
                min-height: 46px;
                justify-content: center;
                border-radius: 11px;
                font-size: 14px;
            }
            .spmb-body .detail-grid {
                grid-template-columns: 1fr !important;
                gap: 12px !important;
            }
            .spmb-body .detail-item {
                padding-bottom: 10px;
                border-bottom: 1px solid #F1F5F9;
            }
            .spmb-body .detail-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="spmb-body">
    <header class="spmb-topbar">
        <div class="spmb-topbar-inner">
            <a href="{{ route('spmb.show', $penerimaan->slug) }}" class="spmb-brand">
                <div class="spmb-brand-logo">
                    <img src="{{ asset('images/logo.png') }}" alt="Al Muttaqin">
                </div>
                <div class="spmb-brand-text">
                    <div class="spmb-brand-title">{{ $penerimaan->judul }}</div>
                    <div class="spmb-brand-meta">Tahun {{ $penerimaan->tahun_ajaran }}</div>
                </div>
            </a>

            <nav class="spmb-nav" aria-label="Navigasi SPMB">
                <a href="{{ route('spmb.show', $penerimaan->slug) }}"
                   class="spmb-nav-tab {{ request()->routeIs('spmb.show') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Formulir</span>
                </a>
                <a href="{{ route('spmb.cek-status', $penerimaan->slug) }}"
                   class="spmb-nav-tab {{ request()->routeIs('spmb.cek-status') ? 'is-active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span>Cek Status</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="spmb-container">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
