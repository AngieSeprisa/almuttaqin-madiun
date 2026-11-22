<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SPMB Al Muttaqin')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    <style>
        body.spmb-body {
            background: #F4F7FB;
            color: #111827;
            min-height: 100vh;
            margin: 0;
        }
        .spmb-topbar {
            background: #FFFFFF;
            border-bottom: 1px solid #E5E7EB;
            position: sticky;
            top: 0;
            z-index: 30;
        }
        .spmb-topbar-inner {
            max-width: 1040px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .spmb-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #111827;
            min-width: 0;
        }
        .spmb-brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            flex-shrink: 0;
        }
        .spmb-brand-title {
            font-size: 15px;
            font-weight: 800;
            line-height: 1.25;
            color: #0F172A;
        }
        .spmb-brand-meta {
            font-size: 12px;
            color: #64748B;
            font-weight: 600;
        }
        .spmb-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .spmb-container {
            max-width: 1040px;
            margin: 0 auto;
            padding: 24px 20px 48px;
        }
        .spmb-meta-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }
        .spmb-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12.5px;
            font-weight: 600;
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            color: #334155;
        }
        .spmb-chip-primary {
            background: #EFF6FF;
            border-color: #BFDBFE;
            color: #1D4ED8;
        }
        .spmb-card {
            background: #FFFFFF;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }
        @media (max-width: 640px) {
            .spmb-container { padding: 16px 12px 36px; }
            .spmb-topbar-inner { padding: 12px 14px; }
            .spmb-card { padding: 18px 14px; }
        }
    </style>
    @stack('styles')
</head>
<body class="spmb-body">
    <header class="spmb-topbar">
        <div class="spmb-topbar-inner">
            <a href="{{ route('spmb.show', $penerimaan->slug) }}" class="spmb-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Al Muttaqin">
                <div>
                    <div class="spmb-brand-title">{{ $penerimaan->judul }}</div>
                    <div class="spmb-brand-meta">Tahun {{ $penerimaan->tahun_ajaran }}</div>
                </div>
            </a>

            <div class="spmb-nav">
                <a href="{{ route('spmb.show', $penerimaan->slug) }}"
                   class="btn btn-sm {{ request()->routeIs('spmb.show') ? 'btn-primary' : 'btn-secondary' }}">
                    Formulir
                </a>
                <a href="{{ route('spmb.cek-status', $penerimaan->slug) }}"
                   class="btn btn-sm {{ request()->routeIs('spmb.cek-status') ? 'btn-primary' : 'btn-secondary' }}">
                    Cek Status
                </a>
            </div>
        </div>
    </header>

    <main class="spmb-container">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
