@extends('layouts.app')

@section('title', 'Rekap Presensi Sholat')

@section('content')
<style>
    /* Filament 3.x Clean Card & Table */
    .filament-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }
    .filament-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
        flex-wrap: wrap;
    }
    .filament-card-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .filament-card-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .filament-badge-count {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 9999px;
        font-size: 0.73rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .filament-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .filament-search-wrap {
        position: relative;
        min-width: 220px;
    }
    .filament-search-input {
        width: 100%;
        padding: 7px 12px 7px 34px;
        font-size: 0.83rem;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        outline: none;
        background: #ffffff;
        color: #1e293b;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .filament-search-input:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .filament-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        color: #94a3b8;
        pointer-events: none;
    }
    .filament-btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 35px;
        padding: 0 11px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: #ffffff;
        color: #475569;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .filament-btn-icon:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .filament-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 35px;
        padding: 0 14px;
        border-radius: 9px;
        background: #4f46e5;
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease, transform 0.05s ease;
    }
    .filament-btn-primary:hover {
        background: #4338ca;
        color: #ffffff;
    }
    .filament-filter-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0e7ff;
        color: #4338ca;
        font-size: 0.69rem;
        font-weight: 700;
        border-radius: 9999px;
        width: 17px;
        height: 17px;
        margin-left: 2px;
    }

    /* Panel Filter Dropdown / Collapsible */
    .filament-filter-panel {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 14px 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }
    .filament-filter-field label {
        display: block;
        font-size: 0.74rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 5px;
    }
    .filament-filter-select, .filament-filter-input {
        width: 100%;
        padding: 6px 10px;
        font-size: 0.81rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #1e293b;
        outline: none;
    }

    /* Clean Filament Table */
    .filament-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.84rem;
    }
    .filament-table thead th {
        background: #fafafa;
        color: #64748b;
        font-size: 0.77rem;
        font-weight: 600;
        text-transform: none;
        padding: 12px 18px;
        border-bottom: 1px solid #f1f5f9;
        letter-spacing: 0.2px;
    }
    .filament-table tbody tr {
        transition: background-color 0.12s ease;
    }
    .filament-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .filament-table tbody td {
        padding: 13px 18px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .filament-table tbody tr:last-child td {
        border-bottom: none;
    }
    .col-nama-link {
        font-weight: 600;
        color: #0f172a;
        cursor: pointer;
        display: inline-block;
        transition: color 0.15s ease;
        text-decoration: none;
    }
    .col-nama-link:hover {
        color: #4f46e5;
        text-decoration: underline;
    }

    /* Filament Card Footer & Pagination */
    .filament-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-top: 1px solid #f1f5f9;
        background: #ffffff;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 0.79rem;
        color: #64748b;
    }
    .filament-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .filament-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 28px;
        padding: 0 7px;
        border-radius: 7px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.15s ease;
    }
    .filament-page-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .filament-page-btn.active {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
    }
    .filament-page-ellipsis {
        padding: 0 4px;
        color: #94a3b8;
    }

    /* Modal Rincian Kehadiran Siswa (Gambar 2) */
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
        animation: rpFadeIn 0.18s ease-out;
    }
    @keyframes rpFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    .rp-modal-dialog {
        width: 100%;
        max-width: 620px;
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        max-height: 88vh;
    }
    .rp-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 18px 24px 14px;
        border-bottom: 1px solid #f1f5f9;
    }
    .rp-modal-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .rp-modal-sub {
        font-size: 0.77rem;
        color: #64748b;
        margin-top: 3px;
    }
    .rp-modal-close-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: color 0.15s ease;
    }
    .rp-modal-close-btn:hover {
        color: #0f172a;
    }
    .rp-modal-body {
        padding: 18px 24px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    /* Metrik Box Bulat Rounded 5 Kolom */
    .rp-metrics-box {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        padding: 12px 6px;
        background: #ffffff;
    }
    .rp-metric-item {
        text-align: center;
        border-right: 1px solid #f1f5f9;
        padding: 0 4px;
    }
    .rp-metric-item:last-child {
        border-right: none;
    }
    .rp-metric-label {
        display: block;
        font-size: 0.69rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .rp-metric-value {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    /* Tabel Rincian Modal */
    .rp-modal-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.79rem;
    }
    .rp-modal-table thead th {
        text-align: left;
        padding: 8px 12px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.73rem;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .rp-modal-table tbody td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
    }
    .rp-modal-table tbody tr:last-child td {
        border-bottom: none;
    }
    /* Status Badge Rincian */
    .rp-badge-status {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
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
        background: #f1f5f9;
        color: #94a3b8;
    }
    .rp-modal-footer {
        padding: 12px 24px 16px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
    }
    .rp-btn-tutup {
        padding: 7px 20px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .rp-btn-tutup:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
</style>

<div style="display:flex; flex-direction:column; gap:16px;">

    {{-- CARD TABEL UTAMA (FILAMENT 3.X CLEAN TABLE) --}}
    <div class="filament-card">

        {{-- Header Card: Data Presensi Sholat, Pencarian, Filter Icon, Refresh & Unduh Rekap --}}
        <div class="filament-card-header">
            <div class="filament-card-title-wrap">
                <h3 class="filament-card-title">Data Presensi Sholat</h3>
                <span class="filament-badge-count">{{ $stats['total_target'] }} Santri</span>
            </div>

            <div class="filament-header-actions">
                {{-- Search Bar Client-Side Live + Server Fallback --}}
                <div class="filament-search-wrap">
                    <svg class="filament-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                           id="filamentLiveSearch"
                           value="{{ $search }}"
                           placeholder="Cari..."
                           class="filament-search-input">
                </div>

                {{-- Tombol Filter Icon dengan Badge Angka --}}
                <button type="button"
                        id="btnToggleFilterPanel"
                        class="filament-btn-icon"
                        title="Filter Data Presensi"
                        onclick="toggleFilterPanel()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span class="filament-filter-badge" id="badgeActiveFilterCount">{{ $activeFiltersCount }}</span>
                </button>

                {{-- Tombol Refresh --}}
                <a href="{{ route('rekap.sholat.index') }}" class="filament-btn-icon" title="Muat Ulang / Reset Data">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </a>

                {{-- Tombol Unduh Rekap (Ungu Filament) --}}
                <a href="{{ route('rekap.sholat.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                   class="filament-btn-primary"
                   title="Unduh Rekap Excel / CSV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh Rekap</span>
                </a>
            </div>
        </div>

        {{-- Panel Filter Dropdown (Toggled by Filter Icon) --}}
        <div id="filamentFilterPanel" class="filament-filter-panel" style="{{ $activeFiltersCount > 0 ? '' : 'display:none;' }}">
            <form action="{{ route('rekap.sholat.index') }}" method="GET" id="formFilterSholat" style="display:contents;">
                <input type="hidden" name="search" id="formHiddenSearch" value="{{ $search }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                {{-- Rentang Waktu --}}
                <div class="filament-filter-field">
                    <label>Rentang Waktu</label>
                    <select name="rentang" class="filament-filter-select" onchange="this.form.submit()">
                        <option value="harian" {{ $rentang === 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="mingguan" {{ $rentang === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ $rentang === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>

                {{-- Tanggal / Bulan --}}
                @if($rentang !== 'bulanan')
                <div class="filament-filter-field">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" class="filament-filter-input" onchange="this.form.submit()">
                </div>
                @else
                <div class="filament-filter-field">
                    <label>Bulan & Tahun</label>
                    <div style="display:flex;gap:4px;">
                        <select name="bulan" class="filament-filter-select" onchange="this.form.submit()">
                            @foreach($namaBulanList as $num => $lbl)
                                <option value="{{ $num }}" {{ (int) $bulan === (int) $num ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <select name="tahun" class="filament-filter-select" style="min-width:75px;" onchange="this.form.submit()">
                            @for($y = date('Y') + 1; $y >= 2023; $y--)
                                <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                @endif

                {{-- Filter Sesi Sholat --}}
                <div class="filament-filter-field">
                    <label>Sesi Sholat</label>
                    <select name="sesi_sholat" class="filament-filter-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterSesiSholat === 'semua' ? 'selected' : '' }}>Semua Sholat</option>
                        <option value="subuh" {{ $filterSesiSholat === 'subuh' ? 'selected' : '' }}>Subuh</option>
                        <option value="dzuhur" {{ $filterSesiSholat === 'dzuhur' ? 'selected' : '' }}>Dzuhur</option>
                        <option value="ashar" {{ $filterSesiSholat === 'ashar' ? 'selected' : '' }}>Asar</option>
                        <option value="maghrib" {{ $filterSesiSholat === 'maghrib' ? 'selected' : '' }}>Maghrib</option>
                        <option value="isya" {{ $filterSesiSholat === 'isya' ? 'selected' : '' }}>Isya</option>
                    </select>
                </div>

                {{-- Filter Kamar --}}
                <div class="filament-filter-field">
                    <label>Kamar Santri</label>
                    <select name="kamar" class="filament-filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kamar</option>
                        @foreach($kamarList as $kmr)
                            <option value="{{ $kmr }}" {{ $filterKamar === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Jenjang --}}
                <div class="filament-filter-field">
                    <label>Jenjang</label>
                    <select name="jenjang" class="filament-filter-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterJenjang === 'semua' ? 'selected' : '' }}>Semua Jenjang</option>
                        <option value="smp" {{ $filterJenjang === 'smp' ? 'selected' : '' }}>SMP</option>
                        <option value="sma" {{ $filterJenjang === 'sma' ? 'selected' : '' }}>SMA</option>
                        <option value="reguler" {{ $filterJenjang === 'reguler' ? 'selected' : '' }}>Reguler</option>
                    </select>
                </div>

                {{-- Reset Button --}}
                <div style="display:flex;gap:6px;">
                    <a href="{{ route('rekap.sholat.index') }}" class="filament-btn-icon" style="height:32px;font-size:0.78rem;">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel Presensi Sholat (Hanya Nama, Hadir, Telat, Izin, Alfa) --}}
        <div style="overflow-x:auto;">
            <table class="filament-table" id="tablePresensiSholatFilament">
                <thead>
                    <tr>
                        <th style="width:38%;">Nama</th>
                        <th style="text-align:center;width:15%;">Hadir</th>
                        <th style="text-align:center;width:15%;">Telat</th>
                        <th style="text-align:center;width:15%;">Izin</th>
                        <th style="text-align:center;width:15%;">Alfa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paginatedRows as $row)
                    <tr class="filament-row-santri" data-search="{{ mb_strtolower($row['nama_lengkap'] . ' ' . $row['nisn'] . ' ' . $row['kamar']) }}">
                        {{-- Nama Santri Clickable (Memicu Pop-up Modal) --}}
                        <td>
                            <a href="javascript:void(0)"
                               class="col-nama-link js-trigger-detail"
                               onclick="showRincianSantri({{ $row['siswa_id'] }})"
                               data-siswa-id="{{ $row['siswa_id'] }}">
                                {{ $row['nama_lengkap'] }}
                            </a>
                        </td>

                        {{-- Hadir --}}
                        <td style="text-align:center;font-weight:600;color:#0f172a;">
                            {{ $row['total_h'] }}
                        </td>

                        {{-- Telat --}}
                        <td style="text-align:center;font-weight:600;color:#0f172a;">
                            {{ $row['total_t'] }}
                        </td>

                        {{-- Izin (Izin + Sakit) --}}
                        <td style="text-align:center;font-weight:600;color:#0f172a;">
                            {{ $row['total_i'] + $row['total_s'] }}
                        </td>

                        {{-- Alfa --}}
                        <td style="text-align:center;font-weight:600;color:#0f172a;">
                            {{ $row['total_a'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px 16px;color:#94a3b8;">
                            Tidak ada data presensi sholat santri yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Card: "Menampilkan 1 sampai 10 dari 238 hasil" & Pagination --}}
        <div class="filament-card-footer">
            <div>
                @php
                    $from = $paginatedRows->total() > 0 ? ($paginatedRows->currentPage() - 1) * $paginatedRows->perPage() + 1 : 0;
                    $to = min($paginatedRows->currentPage() * $paginatedRows->perPage(), $paginatedRows->total());
                @endphp
                Menampilkan <strong>{{ $from }}</strong> sampai <strong>{{ $to }}</strong> dari <strong>{{ $paginatedRows->total() }}</strong> hasil
            </div>

            {{-- Pagination Links --}}
            <div class="filament-pagination">
                {{-- Previous --}}
                @if($paginatedRows->onFirstPage())
                    <span class="filament-page-btn" style="opacity:0.35;cursor:default;">&lt;</span>
                @else
                    <a href="{{ $paginatedRows->previousPageUrl() }}" class="filament-page-btn">&lt;</a>
                @endif

                {{-- Page Numbers (Compact Sliding Window) --}}
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
                        <span class="filament-page-ellipsis">&hellip;</span>
                    @elseif((int) $p === (int) $curr)
                        <span class="filament-page-btn active">{{ $p }}</span>
                    @else
                        <a href="{{ $paginatedRows->url($p) }}" class="filament-page-btn">{{ $p }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($paginatedRows->hasMorePages())
                    <a href="{{ $paginatedRows->nextPageUrl() }}" class="filament-page-btn">&gt;</a>
                @else
                    <span class="filament-page-btn" style="opacity:0.35;cursor:default;">&gt;</span>
                @endif
            </div>

            {{-- Per Halaman Selector --}}
            <div style="display:flex;align-items:center;gap:6px;">
                <span>per halaman</span>
                <select class="filament-filter-select"
                        style="width:auto;padding:3px 8px;height:28px;"
                        onchange="changePerPage(this.value)">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
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
            <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
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

{{-- SCRIPT LANGSUNG DI DALAM VIEW (TIDAK BERGANTUNG PADA STACK ATAU DOM LOAD TIMING) --}}
<script>
    window.REKAP_SHOLAT_DATA = {!! json_encode($santriMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

    function toggleFilterPanel() {
        var p = document.getElementById('filamentFilterPanel');
        if (p) {
            p.style.display = (p.style.display === 'none' || p.style.display === '') ? 'grid' : 'none';
        }
    }

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
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px;color:#94a3b8;">Tidak ada data riwayat sholat.</td></tr>';
        } else {
            riwayat.forEach(function (item) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (item.tanggal || '-') + '</td>' +
                               '<td><strong style="color:#0f172a;">' + (item.sesi || '-') + '</strong></td>' +
                               '<td><span class="rp-badge-status ' + (item.badge || 'status-badge-alpa') + '">' + (item.status || 'ALPA') + '</span></td>' +
                               '<td style="color:#64748b;font-size:0.75rem;">' + (item.keterangan || '-') + '</td>';
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

    // Pindahkan modal ke root <body> agar bebas dari stacking context parent
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
            var rows = document.querySelectorAll('#tablePresensiSholatFilament tbody tr.filament-row-santri');
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
