@extends('layouts.app')

@section('title', 'Rekap Presensi Pondok (Sholat & Pengajian)')

@section('content')
<style>
    /* Root styling */
    .rp-page-container {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    /* Top Stats Grid (Filament 3.x Style) */
    .rp-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
    }
    @media (max-width: 1200px) {
        .rp-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .rp-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 480px) {
        .rp-stats-grid {
            grid-template-columns: 1fr;
        }
    }

    .rp-stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .rp-stat-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
    }
    .rp-stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .rp-stat-label {
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .rp-stat-icon-wrap {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .rp-stat-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: 2px;
    }
    .rp-stat-desc {
        font-size: 11px;
        color: #94a3b8;
    }

    /* Filament 3.x Table Container */
    .fi-ta-ctn {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    /* Filters Section Above Content (Filament Style) */
    .fi-ta-filters {
        padding: 16px 22px 18px;
        background: #ffffff;
    }
    .fi-ta-filters-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .fi-ta-filters-heading {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .fi-ta-filters-reset-btn {
        font-size: 13px;
        font-weight: 600;
        color: #e11d48;
        background: transparent;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: color 0.15s ease;
        padding: 0;
    }
    .fi-ta-filters-reset-btn:hover {
        color: #be123c;
        text-decoration: underline;
    }

    /* Grid Filter Fields (Responsive 4 columns) */
    .fi-ta-filters-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
    }
    @media (max-width: 1200px) {
        .fi-ta-filters-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .fi-ta-filters-grid {
            grid-template-columns: 1fr;
        }
    }

    .fi-filter-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .fi-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }
    .fi-select, .fi-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px;
        font-size: 13px;
        line-height: 1.4;
        color: #111827;
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }
    .fi-select {
        padding-right: 32px;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 10px center;
        background-repeat: no-repeat;
        background-size: 16px 16px;
        appearance: none;
        -webkit-appearance: none;
    }
    .fi-select:focus, .fi-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Divider */
    .fi-ta-divider {
        border-top: 1px solid #e5e7eb;
    }

    /* Toolbar Search & Actions */
    .fi-ta-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 22px;
        gap: 14px;
        flex-wrap: wrap;
        background: #ffffff;
    }
    .fi-ta-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .fi-ta-title {
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .fi-ta-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f3f4f6;
        color: #4b5563;
        border: 1px solid #e5e7eb;
    }
    .fi-ta-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .fi-ta-search-wrap {
        position: relative;
        width: 240px;
    }
    .fi-ta-search-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px 7px 36px;
        font-size: 13px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }
    .fi-ta-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }
    .fi-ta-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: #9ca3af;
        pointer-events: none;
    }
    .fi-btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #ffffff;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .fi-btn-icon:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #111827;
    }

    /* Buttons */
    .fi-btn-wa {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 14px;
        border-radius: 8px;
        background: #16a34a;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }
    .fi-btn-wa:hover {
        background: #15803d;
        color: #ffffff;
    }
    .fi-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 12px;
        border-radius: 8px;
        background: #ffffff;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #d1d5db;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .fi-btn-secondary:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #111827;
    }
    .fi-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 38px;
        padding: 0 14px;
        border-radius: 8px;
        background: #4f46e5;
        color: #ffffff;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }
    .fi-btn-primary:hover {
        background: #4338ca;
        color: #ffffff;
    }

    /* Filament Table */
    .fi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13.5px;
    }
    .fi-table thead th {
        background: #fafafa;
        color: #6b7280;
        font-size: 12.5px;
        font-weight: 600;
        padding: 12px 22px;
        border-bottom: 1px solid #e5e7eb;
        text-align: left;
    }
    .fi-table tbody tr {
        transition: background-color 0.1s ease;
    }
    .fi-table tbody tr:hover {
        background-color: #f9fafb;
    }
    .fi-table tbody td {
        padding: 13px 22px;
        border-bottom: 1px solid #f3f4f6;
        color: #1f2937;
        vertical-align: middle;
    }
    .fi-table tbody tr:last-child td {
        border-bottom: none;
    }
    .fi-nama-link {
        font-weight: 600;
        color: #111827;
        cursor: pointer;
        display: inline-block;
        transition: color 0.15s ease;
        text-decoration: none;
    }
    .fi-nama-link:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    /* Filament Footer & Pagination */
    .fi-ta-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 22px;
        border-top: 1px solid #e5e7eb;
        background: #ffffff;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6b7280;
    }
    .fi-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .fi-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 30px;
        height: 30px;
        padding: 0 8px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #4b5563;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.15s ease;
    }
    .fi-page-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }
    .fi-page-btn.active {
        background: #f3f4f6;
        color: #ea580c;
        border-color: #fed7aa;
    }
    .fi-page-ellipsis {
        padding: 0 4px;
        color: #9ca3af;
    }

    /* Modal Backdrop & Dialog */
    .rp-modal-backdrop {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(15, 23, 42, 0.48) !important;
        backdrop-filter: blur(4px) !important;
        -webkit-backdrop-filter: blur(4px) !important;
        z-index: 999999 !important;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        box-sizing: border-box;
    }
    .rp-modal-dialog {
        width: 100%;
        max-width: 640px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        max-height: 88vh;
    }
    .rp-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 16px 22px 14px;
        border-bottom: 1px solid #f3f4f6;
    }
    .rp-modal-title {
        font-size: 15.5px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .rp-modal-sub {
        font-size: 12px;
        color: #6b7280;
        margin-top: 3px;
    }
    .rp-modal-close-btn {
        background: transparent;
        border: none;
        color: #9ca3af;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: color 0.15s ease;
    }
    .rp-modal-close-btn:hover {
        color: #111827;
    }
    .rp-modal-body {
        padding: 16px 22px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .rp-metrics-box {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        padding: 12px 6px;
        background: #ffffff;
    }
    .rp-metric-item {
        text-align: center;
        border-right: 1px solid #f3f4f6;
        padding: 0 4px;
    }
    .rp-metric-item:last-child {
        border-right: none;
    }
    .rp-metric-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .rp-metric-value {
        font-size: 18px;
        font-weight: 800;
        color: #111827;
        line-height: 1.1;
    }
    .rp-modal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }
    .rp-modal-table thead th {
        text-align: left;
        padding: 8px 12px;
        background: #f9fafb;
        color: #4b5563;
        font-size: 11.5px;
        font-weight: 700;
        border-bottom: 1px solid #e5e7eb;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .rp-modal-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        vertical-align: middle;
    }
    .rp-modal-table tbody tr:last-child td {
        border-bottom: none;
    }
    .rp-badge-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .status-badge-alpa { background: #fee2e2; color: #dc2626; }
    .status-badge-hadir { background: #dcfce7; color: #15803d; }
    .status-badge-terlambat { background: #fef3c7; color: #b45309; }
    .status-badge-izin { background: #dbeafe; color: #1d4ed8; }
    .status-badge-sakit { background: #f3e8ff; color: #7e22ce; }
    .status-badge-belum { background: #f3f4f6; color: #9ca3af; }

    .rp-modal-footer {
        padding: 12px 22px 16px;
        border-top: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }
    .rp-btn-tutup {
        padding: 7px 18px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #374151;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .rp-btn-tutup:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    /* WhatsApp Textarea */
    .rp-wa-textarea {
        width: 100%;
        height: 250px;
        padding: 12px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 12.5px;
        line-height: 1.5;
        color: #111827;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        outline: none;
        resize: vertical;
        box-sizing: border-box;
        white-space: pre-wrap;
    }
    .rp-wa-textarea:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
        background: #ffffff;
    }
</style>

<div class="rp-page-container">

    {{-- 1. KARTU STATISTIK RINGKASAN (STATS CARDS) --}}
    <div class="rp-stats-grid">
        {{-- Card 1: Total Target Santri --}}
        <div class="rp-stat-card">
            <div class="rp-stat-header">
                <span class="rp-stat-label">Total Target Santri</span>
                <div class="rp-stat-icon-wrap" style="background:#eff6ff;color:#2563eb;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>
            <div class="rp-stat-value">{{ $stats['total_target'] }}</div>
        </div>

        {{-- Card 2: Hadir Tepat Waktu --}}
        <div class="rp-stat-card">
            <div class="rp-stat-header">
                <span class="rp-stat-label">Hadir Tepat Waktu</span>
                <div class="rp-stat-icon-wrap" style="background:#f0fdf4;color:#16a34a;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
            </div>
            <div class="rp-stat-value" style="color:#15803d;">{{ $stats['hadir'] }}</div>
        </div>

        {{-- Card 3: Terlambat --}}
        <div class="rp-stat-card">
            <div class="rp-stat-header">
                <span class="rp-stat-label">Terlambat</span>
                <div class="rp-stat-icon-wrap" style="background:#fffbeb;color:#d97706;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
            </div>
            <div class="rp-stat-value" style="color:#b45309;">{{ $stats['terlambat'] }}</div>
        </div>

        {{-- Card 4: Izin / Sakit --}}
        <div class="rp-stat-card">
            <div class="rp-stat-header">
                <span class="rp-stat-label">Sakit / Izin</span>
                <div class="rp-stat-icon-wrap" style="background:#f5f3ff;color:#7c3aed;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                </div>
            </div>
            <div class="rp-stat-value" style="color:#6d28d9;">{{ $stats['izin_sakit'] }}</div>
        </div>

        {{-- Card 5: Tidak Hadir / Alfa --}}
        <div class="rp-stat-card">
            <div class="rp-stat-header">
                <span class="rp-stat-label">Alpha / Tidak Hadir</span>
                <div class="rp-stat-icon-wrap" style="background:#fef2f2;color:#dc2626;">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </div>
            </div>
            <div class="rp-stat-value" style="color:#b91c1c;">{{ $stats['alfa'] }}</div>
        </div>
    </div>

    {{-- 2. FILAMENT TABLE CONTAINER (FILTERS + TOOLBAR + TABLE) --}}
    <div class="fi-ta-ctn">

        {{-- FILAMENT TABLE FILTERS (ABOVE CONTENT) --}}
        <form action="{{ route('rekap.pondok.index') }}" method="GET" id="formFilamentFilters">
            <input type="hidden" name="search" id="filterHiddenSearch" value="{{ $search }}">
            <input type="hidden" name="per_page" value="{{ $perPage }}">

            <div class="fi-ta-filters">
                <div class="fi-ta-filters-header">
                    <h4 class="fi-ta-filters-heading">Filters</h4>
                    <a href="{{ route('rekap.pondok.index') }}" class="fi-ta-filters-reset-btn">Reset</a>
                </div>

                <div class="fi-ta-filters-grid">
                    {{-- Filter 1: Kategori Kegiatan --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Kategori Kegiatan</label>
                        <select name="kategori" class="fi-select" onchange="this.form.submit()">
                            @foreach($kategoriList as $kKey => $kLabel)
                                <option value="{{ $kKey }}" {{ $kategori === $kKey ? 'selected' : '' }}>{{ $kLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 2: Sesi Spesifik --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Sesi Presensi Spesifik</label>
                        <select name="sesi_id" class="fi-select" onchange="this.form.submit()">
                            @foreach($sesiOptions as $sId => $sLabel)
                                <option value="{{ $sId }}" {{ (string) $sesi_id === (string) $sId ? 'selected' : '' }}>{{ $sLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 3: Rentang Waktu --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Rentang Waktu</label>
                        <select name="rentang" class="fi-select" onchange="this.form.submit()">
                            <option value="harian" {{ $rentang === 'harian' ? 'selected' : '' }}>Harian</option>
                            <option value="mingguan" {{ $rentang === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                            <option value="bulanan" {{ $rentang === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>

                    {{-- Filter 4: Tanggal / Bulan --}}
                    <div class="fi-filter-item">
                        @if($rentang !== 'bulanan')
                            <label class="fi-filter-label">Tanggal</label>
                            <input type="date" name="tanggal" value="{{ $tanggal }}" class="fi-input" onchange="this.form.submit()">
                        @else
                            <label class="fi-filter-label">Bulan &amp; Tahun</label>
                            <div style="display:flex;gap:6px;">
                                <select name="bulan" class="fi-select" style="flex:1.3;" onchange="this.form.submit()">
                                    @foreach($namaBulanList as $num => $lbl)
                                        <option value="{{ $num }}" {{ (int) $bulan === (int) $num ? 'selected' : '' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <select name="tahun" class="fi-select" style="flex:1;" onchange="this.form.submit()">
                                    @for($y = date('Y') + 1; $y >= 2023; $y--)
                                        <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        @endif
                    </div>

                    {{-- Filter 5: Kelas Sekolah --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Kelas Sekolah</label>
                        <select name="kelas" class="fi-select" onchange="this.form.submit()">
                            <option value="semua">Semua Kelas</option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls }}" {{ $kelas === $kls ? 'selected' : '' }}>Kelas {{ $kls }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 6: Kamar Asrama --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Kamar Santri</label>
                        <select name="kamar" class="fi-select" onchange="this.form.submit()">
                            <option value="">Semua Kamar</option>
                            @foreach($kamarList as $kmr)
                                <option value="{{ $kmr }}" {{ $kamar === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 7: Kelas Pengajian --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Kelas Pengajian</label>
                        <select name="kelas_pengajian_id" class="fi-select" onchange="this.form.submit()">
                            <option value="semua">Semua Kelas Pengajian</option>
                            @foreach($kelasPengajianList as $kp)
                                <option value="{{ $kp->id }}" {{ (string) $kelas_pengajian_id === (string) $kp->id ? 'selected' : '' }}>{{ $kp->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter 8: Gender --}}
                    <div class="fi-filter-item">
                        <label class="fi-filter-label">Gender (Jenis Kelamin)</label>
                        <select name="gender" class="fi-select" onchange="this.form.submit()">
                            @foreach($genderList as $gKey => $gLabel)
                                <option value="{{ $gKey }}" {{ $gender === $gKey ? 'selected' : '' }}>{{ $gLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <div class="fi-ta-divider"></div>

        {{-- TOOLBAR SEARCH & ACTIONS --}}
        <div class="fi-ta-toolbar">
            <div class="fi-ta-title-wrap">
                <h3 class="fi-ta-title">Data Presensi Pondok</h3>
                <span class="fi-ta-badge">{{ $stats['total_target'] }} Santri</span>
            </div>

            <div class="fi-ta-actions">
                {{-- Live Search --}}
                <div class="fi-ta-search-wrap">
                    <svg class="fi-ta-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                           id="filamentLiveSearch"
                           value="{{ $search }}"
                           placeholder="Cari nama atau NISN"
                           class="fi-ta-search-input">
                </div>

                {{-- Refresh Button --}}
                <a href="{{ route('rekap.pondok.index') }}" class="fi-btn-icon" title="Reset / Reload">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </a>

                {{-- Tombol Laporan WhatsApp --}}
                <button type="button" class="fi-btn-wa" onclick="openWaModal()" title="Buka &amp; Salin Format Laporan WhatsApp">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                    </svg>
                    <span>Laporan WhatsApp</span>
                </button>

                {{-- Unduh Rekap Excel / CSV --}}
                <a href="{{ route('rekap.pondok.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                   class="fi-btn-secondary"
                   title="Unduh Rekap Excel / CSV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh Rekap</span>
                </a>

                {{-- Cetak / PDF --}}
                <a href="{{ route('rekap.pondok.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                   target="_blank"
                   class="fi-btn-secondary"
                   title="Cetak Laporan / Simpan PDF">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Cetak PDF</span>
                </a>
            </div>
        </div>

        {{-- FILAMENT TABLE (HANYA NAMA, HADIR, TELAT, IZIN, ALFA) --}}
        <div style="overflow-x:auto;">
            <table class="fi-table" id="tablePresensiPondokFilament">
                <thead>
                    <tr>
                        <th style="width:40%;">Nama</th>
                        <th style="text-align:center;width:15%;">Hadir</th>
                        <th style="text-align:center;width:15%;">Telat</th>
                        <th style="text-align:center;width:15%;">Izin</th>
                        <th style="text-align:center;width:15%;">Alfa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedRows as $row)
                    <tr class="fi-row-santri" data-search="{{ mb_strtolower($row['nama_lengkap'] . ' ' . $row['nisn'] . ' ' . $row['kamar'] . ' ' . $row['kelas']) }}">
                        {{-- Nama Santri Clickable (Memicu Modal Pop-up) --}}
                        <td>
                            <a href="javascript:void(0)"
                               class="fi-nama-link js-trigger-detail"
                               onclick="showRincianSantri({{ $row['siswa_id'] }})"
                               data-siswa-id="{{ $row['siswa_id'] }}">
                                {{ $row['nama_lengkap'] }}
                            </a>
                        </td>

                        {{-- Hadir --}}
                        <td style="text-align:center;font-weight:600;color:#15803d;">
                            {{ $row['total_h'] }}
                        </td>

                        {{-- Telat --}}
                        <td style="text-align:center;font-weight:600;color:#b45309;">
                            {{ $row['total_t'] }}
                        </td>

                        {{-- Izin (Izin + Sakit) --}}
                        <td style="text-align:center;font-weight:600;color:#4338ca;">
                            {{ $row['total_i'] + $row['total_s'] }}
                        </td>

                        {{-- Alfa --}}
                        <td style="text-align:center;font-weight:600;color:#dc2626;">
                            {{ $row['total_a'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:48px 16px;color:#9ca3af;">
                            Tidak ada data presensi pondok santri yang ditemukan sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- FOOTER PAGINATION --}}
        <div class="fi-ta-footer">
            <div>
                @php
                    $from = $paginatedRows->total() > 0 ? ($paginatedRows->currentPage() - 1) * $paginatedRows->perPage() + 1 : 0;
                    $to = min($paginatedRows->currentPage() * $paginatedRows->perPage(), $paginatedRows->total());
                @endphp
                Showing {{ $from }} to {{ $to }} of {{ $paginatedRows->total() }} results
            </div>

            <div style="display:flex;align-items:center;gap:16px;">
                {{-- Per Page Dropdown --}}
                <div style="display:flex;align-items:center;gap:6px;">
                    <span>Per page</span>
                    <select class="fi-select"
                            style="width:auto;height:32px;padding:4px 28px 4px 8px;font-size:12.5px;"
                            onchange="changePerPage(this.value)">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                {{-- Pagination Links --}}
                <div class="fi-pagination">
                    @if($paginatedRows->onFirstPage())
                        <span class="fi-page-btn" style="opacity:0.35;cursor:default;">&lt;</span>
                    @else
                        <a href="{{ $paginatedRows->previousPageUrl() }}" class="fi-page-btn">&lt;</a>
                    @endif

                    @php
                        $last = $paginatedRows->lastPage();
                        $curr = $paginatedRows->currentPage();
                        $pages = [];
                        if ($last <= 7) {
                            $pages = range(1, $last);
                        } else {
                            if ($curr <= 3) {
                                $pages = [1, 2, 3, 4, '...', $last];
                            } elseif ($curr >= $last - 2) {
                                $pages = [1, '...', $last - 3, $last - 2, $last - 1, $last];
                            } else {
                                $pages = [1, '...', $curr - 1, $curr, $curr + 1, '...', $last];
                            }
                        }
                    @endphp

                    @foreach($pages as $p)
                        @if($p === '...')
                            <span class="fi-page-ellipsis">&hellip;</span>
                        @elseif((int) $p === (int) $curr)
                            <span class="fi-page-btn active">{{ $p }}</span>
                        @else
                            <a href="{{ $paginatedRows->url($p) }}" class="fi-page-btn">{{ $p }}</a>
                        @endif
                    @endforeach

                    @if($paginatedRows->hasMorePages())
                        <a href="{{ $paginatedRows->nextPageUrl() }}" class="fi-page-btn">&gt;</a>
                    @else
                        <span class="fi-page-btn" style="opacity:0.35;cursor:default;">&gt;</span>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

{{-- MODAL 1: RINCIAN KEHADIRAN SISWA --}}
<div id="modalRincianSiswa" class="rp-modal-backdrop" style="display:none;">
    <div class="rp-modal-dialog">
        <div class="rp-modal-header">
            <div>
                <h3 class="rp-modal-title">Rincian Kehadiran Siswa</h3>
                <div class="rp-modal-sub" id="modalSubtitleText">Nama Siswa &bull; NISN: -</div>
            </div>
            <button type="button" class="rp-modal-close-btn" onclick="closeRincianModal()">&times;</button>
        </div>
        <div class="rp-modal-body">
            <div class="rp-metrics-box">
                <div class="rp-metric-item">
                    <span class="rp-metric-label">HADIR</span>
                    <span class="rp-metric-value" id="modalMetricHadir">0</span>
                </div>
                <div class="rp-metric-item">
                    <span class="rp-metric-label">TERLAMBAT</span>
                    <span class="rp-metric-value" id="modalMetricTerlambat">0</span>
                </div>
                <div class="rp-metric-item">
                    <span class="rp-metric-label">IZIN</span>
                    <span class="rp-metric-value" id="modalMetricIzin">0</span>
                </div>
                <div class="rp-metric-item">
                    <span class="rp-metric-label">SAKIT</span>
                    <span class="rp-metric-value" id="modalMetricSakit">0</span>
                </div>
                <div class="rp-metric-item">
                    <span class="rp-metric-label">ALPA</span>
                    <span class="rp-metric-value" id="modalMetricAlpa">0</span>
                </div>
            </div>

            <div style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <table class="rp-modal-table">
                    <thead>
                        <tr>
                            <th style="width:25%;">TANGGAL</th>
                            <th style="width:25%;">MATA PELAJARAN / SESI</th>
                            <th style="width:18%;">STATUS</th>
                            <th style="width:32%;">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody id="modalRiwayatTableBody"></tbody>
                </table>
            </div>
        </div>
        <div class="rp-modal-footer">
            <button type="button" class="rp-btn-tutup" onclick="closeRincianModal()">Tutup</button>
        </div>
    </div>
</div>

{{-- MODAL 2: LAPORAN WHATSAPP (FORMAT BERSIH SESUAI STANDAR) --}}
<div id="modalLaporanWA" class="rp-modal-backdrop" style="display:none;">
    <div class="rp-modal-dialog">
        <div class="rp-modal-header">
            <div>
                <h3 class="rp-modal-title" style="display:flex;align-items:center;gap:8px;">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" style="color:#16a34a;">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                    </svg>
                    Laporan WhatsApp Presensi
                </h3>
            </div>
            <button type="button" class="rp-modal-close-btn" onclick="closeWaModal()">&times;</button>
        </div>
        <div class="rp-modal-body">
            <div style="display:grid;grid-template-columns:repeat(3, minmax(0, 1fr));gap:10px;">
                {{-- Dropdown Jenjang --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Jenjang</label>
                    <select id="waJenjangSelect" class="fi-select" onchange="renderWaReportPreview()">
                        <option value="semua">Semua Jenjang</option>
                        <option value="smp" {{ strtolower($jenjang ?? '') === 'smp' ? 'selected' : '' }}>SMP</option>
                        <option value="sma" {{ strtolower($jenjang ?? '') === 'sma' ? 'selected' : '' }}>SMA</option>
                        <option value="reguler" {{ strtolower($jenjang ?? '') === 'reguler' ? 'selected' : '' }}>Reguler</option>
                    </select>
                </div>

                {{-- Dropdown Kamar --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Kamar</label>
                    <select id="waKamarSelect" class="fi-select" onchange="renderWaReportPreview()">
                        <option value="semua">Semua Kamar</option>
                        @foreach($kamarList as $kmr)
                            <option value="{{ $kmr }}" {{ ($kamar ?? '') === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Dropdown Sesi --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Sesi</label>
                    <select id="waSessionSelect" class="fi-select" onchange="renderWaReportPreview()">
                        @foreach($waSessions as $sKey => $sMeta)
                            <option value="{{ $sKey }}" {{ $sKey === $waPrimarySlot ? 'selected' : '' }}>
                                {{ $sMeta['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Textarea Pratinjau Teks WhatsApp --}}
            <div>
                <label class="fi-filter-label" style="margin-bottom:6px;display:block;">Teks Pesan WhatsApp:</label>
                <textarea id="waMessageTextarea" class="rp-wa-textarea" spellcheck="false">{{ $waText }}</textarea>
            </div>

            <div id="waCopyNotification" style="display:none;padding:8px 12px;background:#dcfce7;color:#15803d;border:1px solid #86efac;border-radius:8px;font-size:12.5px;font-weight:600;text-align:center;">
                Format WhatsApp berhasil disalin ke clipboard! Siap dipaste ke grup WhatsApp.
            </div>
        </div>
        <div class="rp-modal-footer">
            <button type="button" class="rp-btn-tutup" onclick="closeWaModal()">Tutup</button>
            <button type="button" class="fi-btn-secondary" onclick="openWhatsAppWeb()" title="Buka WhatsApp Web di tab baru">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                </svg>
                <span>Buka WhatsApp Web</span>
            </button>
            <button type="button" class="fi-btn-wa" id="btnSalinWa" onclick="copyWaText()">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span id="btnSalinWaText">Salin Format WA</span>
            </button>
        </div>
    </div>
</div>

@php
    $santriMap = collect($rows)->keyBy('siswa_id')->map(function ($item) {
        return [
            'nama'            => $item['nama_lengkap'],
            'nama_bersih'     => $item['nama_bersih'] ?? \App\Http\Controllers\RekapPondokController::cleanStudentName($item['nama_lengkap']),
            'nisn'            => $item['nisn'],
            'kamar'           => $item['kamar'],
            'kelas'           => $item['kelas'],
            'jenjang'         => $item['jenjang'],
            'hadir'           => $item['total_h'],
            'terlambat'       => $item['total_t'],
            'izin'            => $item['total_i'],
            'sakit'           => $item['total_s'],
            'alpa'            => $item['total_a'],
            'riwayat'         => $item['riwayat'],
            'status_per_sesi' => $item['status_per_sesi'] ?? [],
        ];
    });
@endphp

<script>
    window.REKAP_PONDOK_DATA = {!! json_encode($santriMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    window.REKAP_SHOLAT_DATA = window.REKAP_PONDOK_DATA; // Kompatibilitas alias
    window.WA_SESSIONS_DATA = {!! json_encode($waSessions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
    window.WA_DATE_FORMATTED = {!! json_encode($waDateFormatted) !!};

    function changePerPage(val) {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', val);
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }

    // Modal 1: Rincian Kehadiran Siswa
    function showRincianSantri(id) {
        if (!window.REKAP_PONDOK_DATA || !window.REKAP_PONDOK_DATA[id]) {
            console.warn('Data santri tidak ditemukan untuk ID:', id);
            return;
        }
        openRincianModal(window.REKAP_PONDOK_DATA[id]);
    }

    function openRincianModal(data) {
        var modal = document.getElementById('modalRincianSiswa');
        if (!modal) return;

        document.getElementById('modalSubtitleText').textContent = (data.nama || '-') + ' • NISN: ' + (data.nisn || '-');
        document.getElementById('modalMetricHadir').textContent = data.hadir || 0;
        document.getElementById('modalMetricTerlambat').textContent = data.terlambat || 0;
        document.getElementById('modalMetricIzin').textContent = data.izin || 0;
        document.getElementById('modalMetricSakit').textContent = data.sakit || 0;
        document.getElementById('modalMetricAlpa').textContent = data.alpa || 0;

        var tbody = document.getElementById('modalRiwayatTableBody');
        tbody.innerHTML = '';

        var riwayat = data.riwayat || [];
        if (riwayat.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px;color:#9ca3af;">Tidak ada data riwayat presensi sesi.</td></tr>';
        } else {
            riwayat.forEach(function (item) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (item.tanggal || '-') + '</td>' +
                               '<td><strong style="color:#111827;">' + (item.sesi || '-') + '</strong></td>' +
                               '<td><span class="rp-badge-status ' + (item.badge || 'status-badge-alpa') + '">' + (item.status || 'ALPA') + '</span></td>' +
                               '<td style="color:#6b7280;font-size:12px;">' + (item.keterangan || '-') + '</td>';
                tbody.appendChild(tr);
            });
        }

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeRincianModal() {
        var modal = document.getElementById('modalRincianSiswa');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    // Modal 2: Laporan WhatsApp
    function openWaModal() {
        var modal = document.getElementById('modalLaporanWA');
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        var notif = document.getElementById('waCopyNotification');
        if (notif) notif.style.display = 'none';

        var btnText = document.getElementById('btnSalinWaText');
        if (btnText) btnText.textContent = 'Salin Format WA';

        // Segarkan pratinjau teks WA sesuai filter aktif
        renderWaReportPreview();
    }

    function closeWaModal() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function renderWaReportPreview() {
        var jenjangEl = document.getElementById('waJenjangSelect');
        var kamarEl = document.getElementById('waKamarSelect');
        var sessionEl = document.getElementById('waSessionSelect');
        var textarea = document.getElementById('waMessageTextarea');
        if (!textarea) return;

        var selJenjang = jenjangEl ? (jenjangEl.value || 'semua').toLowerCase() : 'semua';
        var selKamar = kamarEl ? (kamarEl.value || 'semua').trim() : 'semua';
        var selSession = sessionEl ? sessionEl.value : 'dzuhur';

        var sessionMeta = (window.WA_SESSIONS_DATA && window.WA_SESSIONS_DATA[selSession]) ? window.WA_SESSIONS_DATA[selSession] : {};
        var sessionLabel = sessionMeta.label || selSession.toUpperCase();
        var headerKegiatan = sessionMeta.header_kegiatan || 'SHOLAT';
        var headerWaktuLabel = (headerKegiatan === 'PENGAJIAN') ? 'Waktu Pengajian' : 'Waktu Sholat';

        // Filter santri berdasarkan Jenjang & Kamar
        var matchedStudents = [];
        var allSantri = Object.values(window.REKAP_PONDOK_DATA || {});

        allSantri.forEach(function (s) {
            // Filter Jenjang
            if (selJenjang !== 'semua' && selJenjang !== '') {
                var j = (s.jenjang || '').toLowerCase();
                var kmr = (s.kamar || '').toLowerCase();
                if (selJenjang === 'smp' && j.indexOf('smp') === -1) {
                    return;
                }
                if (selJenjang === 'sma' && j.indexOf('sma') === -1) {
                    return;
                }
                if (selJenjang === 'reguler') {
                    var isReg = j.indexOf('reguler') !== -1 || kmr.indexOf('reguler') !== -1 || (j.indexOf('smp') === -1 && j.indexOf('sma') === -1);
                    if (!isReg) return;
                }
            }

            // Filter Kamar
            if (selKamar !== 'semua' && selKamar !== '') {
                var k = (s.kamar || '').toLowerCase();
                if (k.indexOf(selKamar.toLowerCase()) === -1) {
                    return;
                }
            }

            matchedStudents.push(s);
        });

        // Tentukan Sasaran Header
        var sasaran = 'SEMUA SANTRI';
        if (selJenjang !== 'semua' && selKamar !== 'semua' && selKamar !== '') {
            sasaran = selJenjang.toUpperCase() + ' - ' + selKamar.toUpperCase();
        } else if (selJenjang !== 'semua') {
            sasaran = selJenjang.toUpperCase();
        } else if (selKamar !== 'semua' && selKamar !== '') {
            sasaran = selKamar.toUpperCase();
        }

        var dateLine = window.WA_DATE_FORMATTED ? (window.WA_DATE_FORMATTED + "\n\n") : '';
        var header = "📅 *LAPORAN ABSENSI " + headerKegiatan + " SISWA KELAS " + sasaran + "*\n" +
                     dateLine +
                     "*" + headerWaktuLabel + ":* " + sessionLabel + "\n\n" +
                     "*Daftar Kehadiran Siswa:*";

        var lines = [];
        var hadirCount = 0;
        var izinTerlambatCount = 0;
        var alpaCount = 0;

        matchedStudents.forEach(function (s, idx) {
            var sesiStatus = (s.status_per_sesi && s.status_per_sesi[selSession]) ? s.status_per_sesi[selSession] : null;
            var tag = '❌ - ALPA';
            if (sesiStatus) {
                tag = sesiStatus.wa_emoji || '❌ - ALPA';
                var st = sesiStatus.status;
                if (st === 'hadir') {
                    hadirCount++;
                } else if (st === 'terlambat' || st === 'izin' || st === 'sakit') {
                    izinTerlambatCount++;
                } else {
                    alpaCount++;
                }
            } else {
                alpaCount++;
            }
            var cleanName = s.nama_bersih || s.nama || '';
            lines.push((idx + 1) + '. ' + cleanName + ' ' + tag);
        });

        var body = lines.length > 0 ? lines.join("\n") : "Tidak ada siswa dalam kelompok ini.";
        var summary = "*Ringkasan Kehadiran:*\n" +
                      "• Hadir: " + hadirCount + " Siswa\n" +
                      "• Sakit/Izin/Terlambat: " + izinTerlambatCount + " Siswa\n" +
                      "• Alpha: " + alpaCount + " Siswa";

        textarea.value = header + "\n\n" + body + "\n\n" + summary;
    }

    function switchWaSession(slotKey) {
        renderWaReportPreview();
    }

    function copyWaText() {
        var textarea = document.getElementById('waMessageTextarea');
        if (!textarea) return;

        textarea.select();
        textarea.setSelectionRange(0, 99999);

        var text = textarea.value;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                showCopyFeedback();
            }).catch(function () {
                document.execCommand('copy');
                showCopyFeedback();
            });
        } else {
            document.execCommand('copy');
            showCopyFeedback();
        }
    }

    function showCopyFeedback() {
        var notif = document.getElementById('waCopyNotification');
        if (notif) notif.style.display = 'block';

        var btnText = document.getElementById('btnSalinWaText');
        if (btnText) btnText.textContent = 'Tersalin! ✓';

        setTimeout(function () {
            if (btnText) btnText.textContent = 'Salin Format WA';
        }, 3000);
    }

    function openWhatsAppWeb() {
        var textarea = document.getElementById('waMessageTextarea');
        var text = textarea ? textarea.value : '';
        var url = 'https://web.whatsapp.com/send?text=' + encodeURIComponent(text);
        window.open(url, '_blank');
    }

    // Pindahkan modals ke root <body>
    function setupModalDom() {
        var modal1 = document.getElementById('modalRincianSiswa');
        if (modal1 && modal1.parentElement !== document.body) {
            document.body.appendChild(modal1);
        }
        var modal2 = document.getElementById('modalLaporanWA');
        if (modal2 && modal2.parentElement !== document.body) {
            document.body.appendChild(modal2);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupModalDom);
    } else {
        setupModalDom();
    }

    // Event delegation klik
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-trigger-detail');
        if (trigger) {
            e.preventDefault();
            var id = trigger.getAttribute('data-siswa-id');
            if (id && window.REKAP_PONDOK_DATA && window.REKAP_PONDOK_DATA[id]) {
                openRincianModal(window.REKAP_PONDOK_DATA[id]);
            }
        }

        var modal1 = document.getElementById('modalRincianSiswa');
        if (modal1 && e.target === modal1) {
            closeRincianModal();
        }

        var modal2 = document.getElementById('modalLaporanWA');
        if (modal2 && e.target === modal2) {
            closeWaModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeRincianModal();
            closeWaModal();
        }
    });

    // Client-side search & Enter server-side
    var searchInput = document.getElementById('filamentLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = (this.value || '').toLowerCase().trim();
            var rows = document.querySelectorAll('#tablePresensiPondokFilament tbody tr.fi-row-santri');
            rows.forEach(function (tr) {
                var hay = tr.getAttribute('data-search') || '';
                tr.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var url = new URL(window.location.href);
                url.searchParams.set('search', this.value.trim());
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            }
        });
    }
</script>
@endsection
