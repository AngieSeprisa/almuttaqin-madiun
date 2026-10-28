@php
    $authUser = auth()->user();
    $accessibleLembagas = $authUser ? $authUser->accessibleLembagas() : collect();
    $currentLembaga = $authUser ? $authUser->currentLembaga() : null;

    $brandLogo = $currentLembaga ? $currentLembaga->logo_url : asset('images/logo.png');
    $brandTitle = $currentLembaga ? $currentLembaga->brand_title : 'PONDOK PESANTREN AL MUTTAQIN';
    $brandAlt = $brandTitle;

    $nfcPage = $nfcPage ?? (request()->routeIs('pengaturan.sesi*') ? 'pengaturan' : 'dashboard');
    $activeSidebarKey = $activeSidebarKey ?? ($nfcPage === 'pengaturan'
        ? (request('tab') === 'devices' ? 'devices' : 'sesi')
        : (in_array(request('tab'), ['pondok', 'sekolah', 'pegawai'], true) ? request('tab') : 'pondok'));

    $sesiBadgeCount = $sesiCount ?? null;
    $deviceBadgeCount = $deviceCount ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Presensi NFC') - {{ $brandTitle }}</title>
    <link rel="icon" type="image/png" href="{{ $brandLogo }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', '"Segoe UI"', 'Roboto', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        electric: {
                            DEFAULT: '#0052FF',
                            50: '#EFF4FF',
                            100: '#DBE6FE',
                            500: '#0052FF',
                            600: '#0043D4',
                            700: '#0038B3',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

            /* Electric Blue Accent */
            --electric-blue: #0052FF;
            --electric-blue-hover: #0043D4;
            --electric-blue-active: #0038B3;
            --electric-blue-light: #EFF4FF;
            --electric-blue-glow: 0 4px 14px rgba(0, 82, 255, 0.24);

            /* Filament v4 Clean Light Tokens + Halaman Utama Sidebar Tokens */
            --bg-page: #F9FAFB;
            --bg-surface: #FFFFFF;
            --border-light: #E5E7EB;
            --border-subtle: #F3F4F6;

            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #9CA3AF;

            --card-bg: #FFFFFF;
            --card-border: #E5E7EB;

            --nav-hover-bg: #F8FAFC;
            --nav-active-bg: #FFFFFF;
            --nav-active-border: #E5E7EB;
            --nav-active-color: #111827;
            --nav-active-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);

            --sidebar-expanded-width: 260px;
            --sidebar-collapsed-width: 68px;
            --transition-default: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [x-cloak] { display: none !important; }

        * { box-sizing: border-box; }

        body {
            font-family: var(--font-main);
            background-color: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            overflow-x: clip;
            margin: 0;
        }

        .app-layout {
            min-height: 100vh;
            position: relative;
            display: flex;
        }

        /* ========================================================
           SIDEBAR PERSIS GAYA HALAMAN UTAMA (layouts/app.blade.php)
           ======================================================== */
        .app-sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: var(--sidebar-expanded-width);
            background: var(--bg-surface);
            border: none;
            border-right: 1px solid var(--border-light);
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: width var(--transition-default), transform var(--transition-default);
            overflow: hidden;
        }

        .sidebar-header {
            height: 60px;
            min-height: 60px;
            padding: 0 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
            transition: padding var(--transition-default);
            flex-shrink: 0;
        }

        .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            cursor: pointer;
            flex: 1;
            min-width: 0;
        }

        .brand-logo-wrap {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .brand-logo-img {
            width: 28px;
            height: 28px;
            object-fit: contain;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }

        .brand-wrapper:hover .brand-logo-img {
            transform: scale(1.08);
        }

        .brand-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            flex: 1;
        }

        .brand-title {
            font-size: 12.5px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            white-space: normal;
            word-break: break-word;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-transform: uppercase;
        }

        /* Lembaga Switcher (Halaman Utama) */
        .sidebar-room-area {
            padding: 10px 12px 4px 12px;
            flex-shrink: 0;
        }
        .sidebar-room-wrap { position: relative; width: 100%; }
        .sidebar-room-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background-color: var(--nav-hover-bg);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: left;
        }
        .sidebar-room-btn:hover {
            background-color: var(--bg-surface);
            border-color: var(--electric-blue);
            box-shadow: 0 0 0 3px var(--electric-blue-light);
        }
        .sidebar-room-current-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        .sidebar-room-chevron {
            width: 14px;
            height: 14px;
            color: var(--text-muted);
            flex-shrink: 0;
            margin-left: 6px;
        }
        .sidebar-room-dropdown {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            padding: 5px;
            display: none;
            flex-direction: column;
            gap: 2px;
            z-index: 1000;
        }
        .sidebar-room-dropdown.show { display: flex; }
        .sidebar-room-list { display: flex; flex-direction: column; gap: 2px; max-height: 220px; overflow-y: auto; }
        .sidebar-room-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            transition: all 0.12s ease;
        }
        .sidebar-room-item:hover { background-color: var(--nav-hover-bg); color: var(--text-primary); }
        .sidebar-room-item.active {
            background-color: var(--electric-blue-light);
            color: var(--electric-blue);
            font-weight: 600;
        }
        .sidebar-room-check { width: 14px; height: 14px; color: var(--electric-blue); flex-shrink: 0; }

        /* Collapse Button */
        .sidebar-collapse-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }
        .sidebar-collapse-btn:hover {
            background: var(--nav-hover-bg);
            color: var(--electric-blue);
        }
        .sidebar-collapse-btn svg { width: 18px; height: 18px; }
        .sidebar-icon-desktop { display: block; }
        .sidebar-icon-mobile { display: none; }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 16px 12px 24px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
        }
        .sidebar-nav::-webkit-scrollbar { width: 5px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 10px; }

        .sidebar-section-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            padding: 10px 12px 8px 12px;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sidebar-section-label:first-child {
            padding-top: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .nav-item:hover {
            background-color: var(--nav-hover-bg);
            color: var(--text-primary);
        }
        .nav-item-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nav-item-icon svg {
            width: 20px;
            height: 20px;
            color: var(--text-muted);
            transition: color 0.15s ease;
        }
        .nav-item:hover .nav-item-icon svg {
            color: var(--text-primary);
        }

        /* Active Nav Item — Persis Halaman Utama + Aksen Electric Blue */
        .nav-item.active {
            background-color: var(--nav-active-bg);
            border: 1px solid var(--nav-active-border);
            box-shadow: var(--nav-active-shadow);
            color: var(--nav-active-color);
            font-weight: 700;
        }
        .nav-item.active .nav-item-icon svg {
            color: var(--electric-blue);
        }

        .nav-item-badge {
            margin-left: auto;
            font-size: 11.5px;
            font-weight: 700;
            line-height: 1.2;
            padding: 2px 8px;
            border-radius: 6px;
            background-color: var(--electric-blue-light);
            color: var(--electric-blue);
            border: 1px solid rgba(0, 82, 255, 0.14);
            flex-shrink: 0;
        }

        /* Collapsed Sidebar State (Persis Halaman Utama) */
        body.sidebar-collapsed .app-sidebar { width: var(--sidebar-collapsed-width); }
        body.sidebar-collapsed .sidebar-header { padding: 0; justify-content: center; }
        body.sidebar-collapsed .brand-wrapper { justify-content: center; }
        body.sidebar-collapsed .brand-wrapper:hover .brand-logo-wrap {
            transform: scale(1.08);
            border-color: var(--electric-blue);
            box-shadow: 0 2px 10px rgba(0, 82, 255, 0.22);
        }
        body.sidebar-collapsed .brand-info,
        body.sidebar-collapsed .brand-title,
        body.sidebar-collapsed .sidebar-collapse-btn,
        body.sidebar-collapsed .sidebar-room-area,
        body.sidebar-collapsed .sidebar-section-label,
        body.sidebar-collapsed .nav-item-text,
        body.sidebar-collapsed .nav-item-badge {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-nav { padding: 16px 8px; align-items: center; }
        body.sidebar-collapsed .nav-item {
            width: 44px;
            height: 44px;
            padding: 0;
            justify-content: center;
            border-radius: 10px;
        }

        /* Main Wrapper & Flush White Topbar */
        .main-wrapper {
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-expanded-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left var(--transition-default);
            background-color: var(--bg-page);
        }
        body.sidebar-collapsed .main-wrapper {
            margin-left: var(--sidebar-collapsed-width);
        }

        .app-header {
            height: 60px;
            min-height: 60px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-light);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .topbar-toggle-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 8px;
            padding: 0;
            transition: all 0.15s ease;
        }
        .topbar-toggle-btn:hover {
            color: var(--text-primary);
            background-color: var(--nav-hover-bg);
        }
        .topbar-toggle-btn svg { width: 22px; height: 22px; }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            margin-left: auto;
        }

        /* Topbar Search Box (Persis Gambar Referensi & Halaman Utama) */
        .topbar-search-wrap { position: relative; }
        .topbar-search-input-box { position: relative; display: flex; align-items: center; }
        .topbar-search-icon {
            position: absolute;
            left: 12px;
            width: 16px;
            height: 16px;
            color: #9CA3AF;
            pointer-events: none;
        }
        .topbar-search-input {
            width: 240px;
            height: 38px;
            padding: 8px 34px 8px 36px;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-primary);
            background-color: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            outline: none;
            transition: all 0.2s ease;
        }
        .topbar-search-input::placeholder { color: #9CA3AF; }
        .topbar-search-input:focus {
            border-color: var(--electric-blue);
            box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
            width: 290px;
        }
        .topbar-search-spinner {
            position: absolute;
            right: 12px;
            width: 14px;
            height: 14px;
            border: 2px solid var(--border-light);
            border-top-color: var(--electric-blue);
            border-radius: 50%;
            animation: topbarSpin 0.7s linear infinite;
        }
        @keyframes topbarSpin { to { transform: rotate(360deg); } }

        .topbar-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 350px;
            max-height: 400px;
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.10);
            overflow-y: auto;
            z-index: 1000;
        }
        .topbar-search-category {
            padding: 8px 14px 4px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            background: var(--nav-hover-bg);
            border-bottom: 1px solid var(--border-subtle);
        }
        .topbar-search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            text-decoration: none;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-subtle);
            transition: background-color 0.15s ease;
        }
        .topbar-search-item:hover { background-color: var(--nav-hover-bg); }
        .topbar-search-item-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            background: var(--electric-blue-light);
            color: var(--electric-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .topbar-search-item-icon svg { width: 15px; height: 15px; }
        .topbar-search-item-content { flex: 1; min-width: 0; }
        .topbar-search-item-title { font-size: 13px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topbar-search-item-meta { font-size: 11.5px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topbar-search-item-badge {
            font-size: 10.5px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 6px;
            background: var(--bg-page);
            color: var(--text-secondary);
            border: 1px solid var(--border-light);
            flex-shrink: 0;
        }
        .topbar-search-empty { padding: 20px 16px; text-align: center; color: var(--text-muted); font-size: 13px; }

        /* User Avatar Circle & Dropdown */
        .user-menu-wrap { position: relative; }
        .user-avatar-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: transform 0.15s ease;
        }
        .user-avatar-btn:hover { transform: scale(1.05); }
        .topbar-avatar-initials {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #111827;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.4px;
        }
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 240px;
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.10);
            z-index: 1000;
            overflow: hidden;
        }
        .user-dropdown-info {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-light);
            background: var(--nav-hover-bg);
        }
        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .user-dropdown-item:hover {
            background-color: var(--nav-hover-bg);
            color: var(--text-primary);
        }
        .user-dropdown-logout-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #DC2626;
            background: transparent;
            border: none;
            border-top: 1px solid var(--border-subtle);
            cursor: pointer;
            text-align: left;
            transition: background-color 0.15s ease;
        }
        .user-dropdown-logout-btn:hover { background-color: #FEF2F2; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.45);
            backdrop-filter: blur(2px);
            z-index: 90;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #F9FAFB; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #D1D5DB; border-radius: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #9CA3AF; }

        @media (max-width: 768px) {
            .sidebar-icon-desktop { display: none !important; }
            .sidebar-icon-mobile { display: block !important; }
            .app-sidebar {
                transform: translateX(calc(-100% - 20px));
                width: var(--sidebar-expanded-width) !important;
                z-index: 1000;
            }
            body.sidebar-mobile-open .app-sidebar {
                transform: translateX(0);
                box-shadow: 0 12px 36px rgba(0, 0, 0, 0.18);
            }
            body.sidebar-mobile-open .sidebar-overlay {
                display: block;
                opacity: 1;
                z-index: 999;
            }
            .main-wrapper,
            body.sidebar-collapsed .main-wrapper {
                margin-left: 0 !important;
            }
            .app-header {
                padding: 0 16px;
                height: 56px;
                min-height: 56px;
            }
            .topbar-toggle-btn { display: inline-flex; }
            .topbar-search-input { width: 170px; }
            .topbar-search-input:focus { width: 200px; }
        }
    </style>
    @stack('styles')
</head>
<body @yield('body-attrs')>
    <script>
        (function() {
            if (window.innerWidth > 768 && localStorage.getItem('sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    <div class="sidebar-overlay" id="nfcSidebarOverlay"></div>

    <div class="app-layout">
        <!-- ======================================================= -->
        <!-- SIDEBAR: GAYA HALAMAN UTAMA + TAB MENU NFC TAP & SESI   -->
        <!-- ======================================================= -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-header">
                <a href="{{ route('beranda') }}" class="brand-wrapper" id="nfcBrandLink" title="{{ $brandAlt }}">
                    <div class="brand-logo-wrap">
                        <img src="{{ $brandLogo }}" alt="Logo {{ $brandAlt }}" class="brand-logo-img">
                    </div>
                    <div class="brand-info">
                        <span class="brand-title">{{ $brandTitle }}</span>
                    </div>
                </a>

                <button type="button" class="sidebar-collapse-btn js-nfc-sidebar-toggle" aria-label="Buka/Tutup Menu" title="Buka/Tutup Menu">
                    <svg class="sidebar-icon-desktop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2.5"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    <svg class="sidebar-icon-mobile" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            @if($authUser && $currentLembaga && $accessibleLembagas->count() > 1)
            <div class="sidebar-room-area">
                <div class="sidebar-room-wrap" id="sidebarRoomWrap">
                    <button type="button" class="sidebar-room-btn" id="sidebarRoomToggle" aria-label="Pilih Lembaga" title="{{ $currentLembaga->brand_title }}">
                        <span class="sidebar-room-current-name">{{ $currentLembaga->brand_title }}</span>
                        <svg class="sidebar-room-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sidebar-room-dropdown" id="sidebarRoomMenu">
                        <div class="sidebar-room-list">
                            @foreach($accessibleLembagas as $lem)
                            <a href="{{ route('switch-lembaga', ['kode' => $lem->kode]) }}" class="sidebar-room-item {{ $currentLembaga->kode === $lem->kode ? 'active' : '' }}">
                                <span>{{ $lem->brand_title }}</span>
                                @if($currentLembaga->kode === $lem->kode)
                                <svg class="sidebar-room-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <nav class="sidebar-nav">
                <!-- Grup 1: Tab Monitoring Live NFC Tap -->
                <div class="sidebar-section-label">
                    <span>Monitoring NFC Tap</span>
                </div>

                <a href="{{ route('dashboard-presensi') }}"
                   data-nfc-page="dashboard"
                   data-nfc-tab="pondok"
                   class="nav-item js-nfc-nav {{ $nfcPage === 'dashboard' && $activeSidebarKey === 'pondok' ? 'active' : '' }}"
                   title="Pondok / Asrama">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </span>
                    <span class="nav-item-text">Pondok / Asrama</span>
                </a>

                <a href="{{ route('dashboard-presensi', ['tab' => 'sekolah']) }}"
                   data-nfc-page="dashboard"
                   data-nfc-tab="sekolah"
                   class="nav-item js-nfc-nav {{ $nfcPage === 'dashboard' && $activeSidebarKey === 'sekolah' ? 'active' : '' }}"
                   title="Sekolah (SMP/SMA)">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                    </span>
                    <span class="nav-item-text">Sekolah (SMP/SMA)</span>
                </a>

                <a href="{{ route('dashboard-presensi', ['tab' => 'pegawai']) }}"
                   data-nfc-page="dashboard"
                   data-nfc-tab="pegawai"
                   class="nav-item js-nfc-nav {{ $nfcPage === 'dashboard' && $activeSidebarKey === 'pegawai' ? 'active' : '' }}"
                   title="Pegawai & Guru">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <span class="nav-item-text">Pegawai &amp; Guru</span>
                </a>

                <!-- Grup 2: Tab Pengaturan Sesi & Perangkat NFC -->
                <div class="sidebar-section-label" style="margin-top: 8px;">
                    <span>Pengaturan Sesi NFC</span>
                </div>

                <a href="{{ route('pengaturan.sesi') }}"
                   data-nfc-page="pengaturan"
                   data-nfc-tab="sesi"
                   class="nav-item js-nfc-nav {{ $nfcPage === 'pengaturan' && $activeSidebarKey === 'sesi' ? 'active' : '' }}"
                   title="Jadwal Sesi Presensi">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </span>
                    <span class="nav-item-text">Jadwal Sesi</span>
                    @if($sesiBadgeCount !== null)
                        <span class="nav-item-badge">{{ $sesiBadgeCount }}</span>
                    @endif
                </a>

                <a href="{{ route('pengaturan.sesi', ['tab' => 'devices']) }}"
                   data-nfc-page="pengaturan"
                   data-nfc-tab="devices"
                   class="nav-item js-nfc-nav {{ $nfcPage === 'pengaturan' && $activeSidebarKey === 'devices' ? 'active' : '' }}"
                   title="Perangkat Mesin ESP32">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                            <rect x="9" y="9" width="6" height="6"></rect>
                            <line x1="9" y1="1" x2="9" y2="4"></line>
                            <line x1="15" y1="1" x2="15" y2="4"></line>
                            <line x1="9" y1="20" x2="9" y2="23"></line>
                            <line x1="15" y1="20" x2="15" y2="23"></line>
                            <line x1="20" y1="9" x2="23" y2="9"></line>
                            <line x1="20" y1="14" x2="23" y2="14"></line>
                            <line x1="1" y1="9" x2="4" y2="9"></line>
                            <line x1="1" y1="14" x2="4" y2="14"></line>
                        </svg>
                    </span>
                    <span class="nav-item-text">Perangkat ESP32</span>
                    @if($deviceBadgeCount !== null)
                        <span class="nav-item-badge">{{ $deviceBadgeCount }}</span>
                    @endif
                </a>

                <!-- Grup 3: Navigasi Sistem -->
                <div class="sidebar-section-label" style="margin-top: 8px;">
                    <span>Sistem</span>
                </div>

                <a href="{{ route('beranda') }}" class="nav-item" title="Kembali ke Halaman Utama">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </span>
                    <span class="nav-item-text">Halaman Utama</span>
                </a>
            </nav>
        </aside>

        <!-- ======================================================= -->
        <!-- MAIN AREA: FLUSH WHITE TOPBAR + FILAMENT V4 CANVAS      -->
        <!-- ======================================================= -->
        <div class="main-wrapper">
            <header class="app-header">
                <div class="header-left">
                    <button type="button" class="topbar-toggle-btn js-nfc-sidebar-toggle" aria-label="Buka/Tutup Menu" title="Buka/Tutup Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>

                    <!-- Live WIB System Clock Pill -->
                    <div class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-600">
                        <span class="w-2 h-2 rounded-full bg-[#0052FF] animate-pulse"></span>
                        <span class="font-medium text-gray-500">Waktu Sistem:</span>
                        <span class="font-mono font-bold text-gray-900" x-text="clock">00:00:00</span>
                        <span class="text-[11px] font-semibold text-[#0052FF]">WIB</span>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Global Quick Search (Persis Gambar Referensi) -->
                    <div class="topbar-search-wrap" id="topbarSearchWrap">
                        <div class="topbar-search-input-box">
                            <svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text"
                                   id="topbarSearchInput"
                                   class="topbar-search-input"
                                   placeholder="Cari menu atau santri..."
                                   autocomplete="off"
                                   aria-label="Cari data atau halaman">
                            <span class="topbar-search-spinner" id="topbarSearchSpinner" style="display:none;"></span>
                        </div>
                        <div class="topbar-search-dropdown" id="topbarSearchDropdown" style="display:none;">
                            <div id="topbarSearchResults"></div>
                        </div>
                    </div>

                    @if($authUser)
                    <div class="user-menu-wrap" id="userMenuWrap">
                        <button type="button" class="user-avatar-btn" id="userDropdownToggle" aria-label="Menu Akun: {{ $authUser->name }}" title="{{ $authUser->name }}">
                            @if($authUser->foto_url)
                                <img src="{{ $authUser->foto_url }}" alt="{{ $authUser->name }}" class="w-9 h-9 rounded-full object-cover">
                            @else
                                <div class="topbar-avatar-initials">{{ $authUser->initials }}</div>
                            @endif
                        </button>
                        <div class="user-dropdown" id="userDropdownMenu" style="display:none;">
                            <div class="user-dropdown-info">
                                <div class="text-[13.5px] font-bold text-gray-900 truncate">{{ $authUser->name }}</div>
                                <div class="text-xs text-gray-500 truncate mt-0.5">{{ $authUser->email }}</div>
                            </div>
                            <a href="{{ route('profile') }}" class="user-dropdown-item">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>Profil Pengguna</span>
                            </a>
                            <a href="{{ route('beranda') }}" class="user-dropdown-item">
                                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                </svg>
                                <span>Halaman Utama</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="user-dropdown-logout-btn">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </header>

            <main class="flex-1 w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var currentPage = @json($nfcPage);
            var overlay = document.getElementById('nfcSidebarOverlay');
            var brandLink = document.getElementById('nfcBrandLink');
            var toggleBtns = document.querySelectorAll('.js-nfc-sidebar-toggle');

            function toggleSidebar() {
                if (window.innerWidth <= 768) {
                    document.body.classList.toggle('sidebar-mobile-open');
                } else {
                    var collapsed = document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
                }
            }

            toggleBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleSidebar();
                });
            });

            if (brandLink) {
                brandLink.addEventListener('click', function(e) {
                    if (window.innerWidth > 768 && document.body.classList.contains('sidebar-collapsed')) {
                        e.preventDefault();
                        document.body.classList.remove('sidebar-collapsed');
                        localStorage.setItem('sidebar_collapsed', '0');
                    }
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }

            // Instant Client-Side Tab Switching for Same-Page Sidebar Items
            var navLinks = document.querySelectorAll('.js-nfc-nav');
            function setActiveSidebarTab(tabKey) {
                navLinks.forEach(function(el) {
                    var isMatch = el.getAttribute('data-nfc-page') === currentPage && el.getAttribute('data-nfc-tab') === tabKey;
                    el.classList.toggle('active', isMatch);
                });
            }

            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var targetPage = link.getAttribute('data-nfc-page');
                    var targetTab = link.getAttribute('data-nfc-tab');
                    if (targetPage === currentPage && targetTab) {
                        e.preventDefault();
                        setActiveSidebarTab(targetTab);
                        document.body.classList.remove('sidebar-mobile-open');

                        var nextUrl = new URL(link.href, window.location.origin);
                        window.history.replaceState({}, '', nextUrl.pathname + nextUrl.search);

                        window.dispatchEvent(new CustomEvent('nfc-tab-switch', {
                            detail: { tab: targetTab }
                        }));
                    }
                });
            });

            window.addEventListener('nfc-sync-sidebar', function(e) {
                if (e.detail && e.detail.tab) {
                    setActiveSidebarTab(e.detail.tab);
                }
            });

            // Dropdowns & Global Quick Search
            var roomBtn = document.getElementById('sidebarRoomToggle');
            var roomMenu = document.getElementById('sidebarRoomMenu');
            var userBtn = document.getElementById('userDropdownToggle');
            var userMenu = document.getElementById('userDropdownMenu');
            var searchWrap = document.getElementById('topbarSearchWrap');
            var searchInput = document.getElementById('topbarSearchInput');
            var searchDropdown = document.getElementById('topbarSearchDropdown');
            var searchResults = document.getElementById('topbarSearchResults');
            var searchSpinner = document.getElementById('topbarSearchSpinner');
            var searchTimer = null;
            var searchCache = {};

            function closeAll(except) {
                if (roomMenu && except !== roomMenu) roomMenu.classList.remove('show');
                if (userMenu && except !== userMenu) userMenu.style.display = 'none';
                if (searchDropdown && except !== searchDropdown) searchDropdown.style.display = 'none';
            }

            if (roomBtn && roomMenu) {
                roomBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var open = !roomMenu.classList.contains('show');
                    closeAll();
                    if (open) roomMenu.classList.add('show');
                });
            }

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var open = userMenu.style.display === 'none' || !userMenu.style.display;
                    closeAll();
                    if (open) userMenu.style.display = 'block';
                });
            }

            function esc(str) {
                if (!str) return '';
                var m = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return String(str).replace(/[&<>"']/g, function(c) { return m[c]; });
            }

            function renderQuickSearch(categories, q) {
                if (!searchResults) return;
                var count = 0;
                var html = '';
                categories.forEach(function(cat) {
                    if (cat.items && cat.items.length > 0) {
                        count += cat.items.length;
                        html += '<div class="topbar-search-category">' + esc(cat.category) + '</div>';
                        cat.items.forEach(function(item) {
                            html += '<a href="' + esc(item.url) + '" class="topbar-search-item">' +
                                '<div class="topbar-search-item-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></div>' +
                                '<div class="topbar-search-item-content">' +
                                    '<div class="topbar-search-item-title">' + esc(item.title) + '</div>' +
                                    (item.subtitle ? '<div class="topbar-search-item-meta">' + esc(item.subtitle) + '</div>' : '') +
                                '</div>' +
                                (item.badge ? '<span class="topbar-search-item-badge">' + esc(item.badge) + '</span>' : '') +
                            '</a>';
                        });
                    }
                });
                if (count === 0) {
                    html = '<div class="topbar-search-empty">Tidak ada hasil untuk "' + esc(q) + '"</div>';
                }
                searchResults.innerHTML = html;
                searchDropdown.style.display = 'block';
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var q = searchInput.value.trim();
                    if (searchTimer) clearTimeout(searchTimer);
                    if (!q) {
                        if (searchSpinner) searchSpinner.style.display = 'none';
                        if (searchDropdown) searchDropdown.style.display = 'none';
                        return;
                    }
                    if (searchCache[q]) {
                        renderQuickSearch(searchCache[q], q);
                        return;
                    }
                    if (searchSpinner) searchSpinner.style.display = 'block';
                    searchTimer = setTimeout(function() {
                        fetch('{{ route("quick-search") }}?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (searchSpinner) searchSpinner.style.display = 'none';
                            var cats = data.results || [];
                            searchCache[q] = cats;
                            renderQuickSearch(cats, q);
                        })
                        .catch(function() {
                            if (searchSpinner) searchSpinner.style.display = 'none';
                        });
                    }, 220);
                });
            }

            document.addEventListener('click', function(e) {
                if (roomMenu && !roomMenu.contains(e.target) && e.target !== roomBtn && !roomBtn?.contains(e.target)) {
                    roomMenu.classList.remove('show');
                }
                if (userMenu && !userMenu.contains(e.target) && e.target !== userBtn && !userBtn?.contains(e.target)) {
                    userMenu.style.display = 'none';
                }
                if (searchWrap && !searchWrap.contains(e.target) && searchDropdown) {
                    searchDropdown.style.display = 'none';
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>

