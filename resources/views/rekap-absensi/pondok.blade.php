@extends('layouts.app')

@section('title', 'Rekap Presensi Sholat')

@section('content')
<style>
    /* Filament 3.x Table Container */
    .fi-ta-ctn {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    /* Filters Section Above Content (Filament Style - Gambar 1) */
    .fi-ta-filters {
        padding: 18px 24px 20px;
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

    /* Grid Filter Fields */
    .fi-ta-filters-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 16px;
        align-items: end;
    }
    @media (max-width: 1200px) {
        .fi-ta-filters-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .fi-ta-filters-grid {
            grid-template-columns: 1fr;
        }
    }

    .fi-filter-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .fi-filter-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
    }
    .fi-select, .fi-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px;
        font-size: 13.5px;
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
        padding: 14px 24px;
        gap: 16px;
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
        width: 250px;
    }
    .fi-ta-search-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px 7px 36px;
        font-size: 13.5px;
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
    }
    .fi-btn-icon:hover {
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
        padding: 12px 24px;
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
        padding: 14px 24px;
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

    /* Filament Card Footer & Pagination */
    .fi-ta-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 24px;
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

    /* Modal Pop-up Rincian Kehadiran Siswa (Gambar 2) */
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
        max-width: 620px;
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
        padding: 18px 24px 14px;
        border-bottom: 1px solid #f3f4f6;
    }
    .rp-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }
    .rp-modal-sub {
        font-size: 12.5px;
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
        padding: 18px 24px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
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
    .status-badge-alpa {
        background: #fee2e2;
        color: #dc2626;
    }
    .status-badge-hadir {
        background: #dcfce7;
        color: #15803d;
    }
    .status-badge-terlambat {
        background: #fef3c7;
        color: #b45309;
    }
    .status-badge-izin {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .status-badge-sakit {
        background: #f3e8ff;
        color: #7e22ce;
    }
    .status-badge-belum {
        background: #f3f4f6;
        color: #9ca3af;
    }
    .rp-modal-footer {
        padding: 12px 24px 16px;
        border-top: 1px solid #f3f4f6;
        display: flex;
        justify-content: flex-end;
    }
    .rp-btn-tutup {
        padding: 7px 20px;
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
</style>

<div class="fi-ta-ctn">

    {{-- 1. FILAMENT TABLE FILTERS (ABOVE CONTENT - GAMBAR 1 RESMI) --}}
    <form action="{{ route('rekap.sholat.index') }}" method="GET" id="formFilamentFilters">
        <input type="hidden" name="search" id="filterHiddenSearch" value="{{ $search }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">

        <div class="fi-ta-filters">
            <div class="fi-ta-filters-header">
                <h4 class="fi-ta-filters-heading">Filters</h4>
                <a href="{{ route('rekap.sholat.index') }}" class="fi-ta-filters-reset-btn">Reset</a>
            </div>

            <div class="fi-ta-filters-grid">
                {{-- Filter 1: Rentang Waktu --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Rentang Waktu</label>
                    <select name="rentang" class="fi-select" onchange="this.form.submit()">
                        <option value="harian" {{ $rentang === 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="mingguan" {{ $rentang === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ $rentang === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>

                {{-- Filter 2: Tanggal / Bulan --}}
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

                {{-- Filter 3: Sesi Sholat --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Sesi Sholat</label>
                    <select name="sesi_sholat" class="fi-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterSesiSholat === 'semua' ? 'selected' : '' }}>Semua Sholat</option>
                        <option value="subuh" {{ $filterSesiSholat === 'subuh' ? 'selected' : '' }}>Subuh</option>
                        <option value="dzuhur" {{ $filterSesiSholat === 'dzuhur' ? 'selected' : '' }}>Dzuhur</option>
                        <option value="ashar" {{ $filterSesiSholat === 'ashar' ? 'selected' : '' }}>Asar</option>
                        <option value="maghrib" {{ $filterSesiSholat === 'maghrib' ? 'selected' : '' }}>Maghrib</option>
                        <option value="isya" {{ $filterSesiSholat === 'isya' ? 'selected' : '' }}>Isya</option>
                    </select>
                </div>

                {{-- Filter 4: Kamar Santri --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Kamar Santri</label>
                    <select name="kamar" class="fi-select" onchange="this.form.submit()">
                        <option value="">Semua Kamar</option>
                        @foreach($kamarList as $kmr)
                            <option value="{{ $kmr }}" {{ $filterKamar === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter 5: Jenjang --}}
                <div class="fi-filter-item">
                    <label class="fi-filter-label">Jenjang</label>
                    <select name="jenjang" class="fi-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterJenjang === 'semua' ? 'selected' : '' }}>Semua Jenjang</option>
                        <option value="smp" {{ $filterJenjang === 'smp' ? 'selected' : '' }}>SMP</option>
                        <option value="sma" {{ $filterJenjang === 'sma' ? 'selected' : '' }}>SMA</option>
                        <option value="reguler" {{ $filterJenjang === 'reguler' ? 'selected' : '' }}>Reguler</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="fi-ta-divider"></div>

    {{-- 2. TOOLBAR SEARCH & ACTIONS --}}
    <div class="fi-ta-toolbar">
        <div class="fi-ta-title-wrap">
            <h3 class="fi-ta-title">Data Presensi Sholat</h3>
            <span class="fi-ta-badge">{{ $stats['total_target'] }} Santri</span>
        </div>

        <div class="fi-ta-actions">
            {{-- Search Bar Filament --}}
            <div class="fi-ta-search-wrap">
                <svg class="fi-ta-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text"
                       id="filamentLiveSearch"
                       value="{{ $search }}"
                       placeholder="Search"
                       class="fi-ta-search-input">
            </div>

            {{-- Refresh Button --}}
            <a href="{{ route('rekap.sholat.index') }}" class="fi-btn-icon" title="Reset / Reload">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
            </a>

            {{-- Unduh Rekap Button --}}
            <a href="{{ route('rekap.sholat.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
               class="fi-btn-primary"
               title="Unduh Rekap Excel / CSV">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                <span>Unduh Rekap</span>
            </a>
        </div>
    </div>

    {{-- 3. FILAMENT TABLE (HANYA NAMA, HADIR, TELAT, IZIN, ALFA) --}}
    <div style="overflow-x:auto;">
        <table class="fi-table" id="tablePresensiSholatFilament">
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
                <tr class="fi-row-santri" data-search="{{ mb_strtolower($row['nama_lengkap'] . ' ' . $row['nisn'] . ' ' . $row['kamar']) }}">
                    {{-- Nama Santri Clickable (Memicu Modal Pop-up Gambar 2) --}}
                    <td>
                        <a href="javascript:void(0)"
                           class="fi-nama-link js-trigger-detail"
                           onclick="showRincianSantri({{ $row['siswa_id'] }})"
                           data-siswa-id="{{ $row['siswa_id'] }}">
                            {{ $row['nama_lengkap'] }}
                        </a>
                    </td>

                    {{-- Hadir --}}
                    <td style="text-align:center;font-weight:600;color:#111827;">
                        {{ $row['total_h'] }}
                    </td>

                    {{-- Telat --}}
                    <td style="text-align:center;font-weight:600;color:#111827;">
                        {{ $row['total_t'] }}
                    </td>

                    {{-- Izin (Izin + Sakit) --}}
                    <td style="text-align:center;font-weight:600;color:#111827;">
                        {{ $row['total_i'] + $row['total_s'] }}
                    </td>

                    {{-- Alfa --}}
                    <td style="text-align:center;font-weight:600;color:#111827;">
                        {{ $row['total_a'] }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:48px 16px;color:#9ca3af;">
                        Tidak ada data presensi sholat santri yang ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 4. FOOTER PAGINATION (GAMBAR 1) --}}
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

{{-- MODAL POP-UP RINCIAN KEHADIRAN SISWA (GAMBAR 2) --}}
<div id="modalRincianSiswa" class="rp-modal-backdrop" style="display:none;">
    <div class="rp-modal-dialog">

        {{-- Modal Header --}}
        <div class="rp-modal-header">
            <div>
                <h3 class="rp-modal-title">Rincian Kehadiran Siswa</h3>
                <div class="rp-modal-sub" id="modalSubtitleText">Nama Siswa &bull; NISN: -</div>
            </div>
            <button type="button" class="rp-modal-close-btn" onclick="closeRincianModal()">&times;</button>
        </div>

        {{-- Modal Body --}}
        <div class="rp-modal-body">

            {{-- Kotak Metrik 5 Kolom Rounded --}}
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

            {{-- Tabel Riwayat Sesi Sholat --}}
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
                    <tbody id="modalRiwayatTableBody">
                        {{-- Diisi secara dinamis oleh JavaScript --}}
                    </tbody>
                </table>
            </div>

        </div>

        {{-- Modal Footer --}}
        <div class="rp-modal-footer">
            <button type="button" class="rp-btn-tutup" onclick="closeRincianModal()">Tutup</button>
        </div>

    </div>
</div>

@php
    $santriMap = collect($rows)->keyBy('siswa_id')->map(function ($item) {
        return [
            'nama'      => $item['nama_lengkap'],
            'nisn'      => $item['nisn'],
            'kamar'     => $item['kamar'],
            'jenjang'   => $item['jenjang'],
            'hadir'     => $item['total_h'],
            'terlambat' => $item['total_t'],
            'izin'      => $item['total_i'],
            'sakit'     => $item['total_s'],
            'alpa'      => $item['total_a'],
            'riwayat'   => $item['riwayat'],
        ];
    });
@endphp

{{-- SCRIPT LANGSUNG DI DALAM VIEW --}}
<script>
    window.REKAP_SHOLAT_DATA = {!! json_encode($santriMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

    function changePerPage(val) {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', val);
        url.searchParams.set('page', 1);
        window.location.href = url.toString();
    }

    function showRincianSantri(id) {
        if (!window.REKAP_SHOLAT_DATA || !window.REKAP_SHOLAT_DATA[id]) {
            console.warn('Data santri tidak ditemukan untuk ID:', id);
            return;
        }
        openRincianModal(window.REKAP_SHOLAT_DATA[id]);
    }

    function openRincianModal(data) {
        var modal = document.getElementById('modalRincianSiswa');
        if (!modal) {
            console.error('Modal #modalRincianSiswa tidak ditemukan');
            return;
        }

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
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px;color:#9ca3af;">Tidak ada data riwayat sholat.</td></tr>';
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

    // Pindahkan modal ke root <body>
    function setupModalDom() {
        var modal = document.getElementById('modalRincianSiswa');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupModalDom);
    } else {
        setupModalDom();
    }

    // Event delegation cadangan jika elemen di-klik
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.js-trigger-detail');
        if (trigger) {
            e.preventDefault();
            var id = trigger.getAttribute('data-siswa-id');
            if (id && window.REKAP_SHOLAT_DATA && window.REKAP_SHOLAT_DATA[id]) {
                openRincianModal(window.REKAP_SHOLAT_DATA[id]);
            }
        }

        var modal = document.getElementById('modalRincianSiswa');
        if (modal && e.target === modal) {
            closeRincianModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeRincianModal();
        }
    });

    // Client-side search & Enter server-side
    var searchInput = document.getElementById('filamentLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var q = (this.value || '').toLowerCase().trim();
            var rows = document.querySelectorAll('#tablePresensiSholatFilament tbody tr.fi-row-santri');
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
