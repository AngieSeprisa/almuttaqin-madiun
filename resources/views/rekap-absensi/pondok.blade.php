@extends('layouts.app')

@section('title', 'Rekap Presensi Pondok (Sholat & Pengajian)')

@section('header-actions')
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <a id="btnExportExcel"
           href="{{ route('rekap.pondok.export', array_merge(request()->query(), ['format' => 'excel', 'mode' => $mode])) }}"
           class="btn btn-secondary"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Ekspor Excel (CSV)
        </a>
        <a id="btnExportPdf"
           href="{{ route('rekap.pondok.export', array_merge(request()->query(), ['format' => 'pdf', 'mode' => $mode])) }}"
           target="_blank"
           class="btn btn-primary"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak / PDF
        </a>
    </div>
@endsection

@section('content')
<style>
    .rp-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    @media (max-width: 1180px) {
        .rp-stats-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 720px) {
        .rp-stats-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    }
    .rp-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .rp-stat-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
    }
    .rp-stat-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
        line-height: 1.3;
    }
    .rp-stat-value {
        font-size: 1.65rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .rp-stat-sub {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-top: 6px;
    }
    .rp-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .rp-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 18px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .rp-filter-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        align-items: end;
    }
    @media (max-width: 1100px) {
        .rp-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .rp-filter-grid { grid-template-columns: 1fr; }
    }
    .rp-field-label {
        display: block;
        font-size: 0.76rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 6px;
    }
    .rp-tabs-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .rp-tab-group {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        gap: 4px;
    }
    .rp-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #475569;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .rp-tab-btn.active {
        background: #ffffff;
        color: #2563eb;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }
    .rp-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .rp-badge-hadir {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .rp-badge-terlambat {
        background: #fef9c3;
        color: #a16207;
        border: 1px solid #fde047;
    }
    .rp-badge-izin {
        background: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .rp-badge-alfa {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    /* Matriks Bulanan */
    .rp-matrix-cell {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.69rem;
        font-weight: 700;
        line-height: 1;
        user-select: none;
    }
    .rp-matrix-H {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
    }
    .rp-matrix-T {
        background: #fef3c7;
        color: #b45309;
        border: 1px solid #fcd34d;
    }
    .rp-matrix-I, .rp-matrix-S {
        background: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #93c5fd;
    }
    .rp-matrix-A {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }
    .rp-matrix-empty {
        background: #f8fafc;
        color: #cbd5e1;
        border: 1px dashed #e2e8f0;
    }
</style>

{{-- 1. KARTU STATISTIK RINGKASAN (STATS CARDS) --}}
<div class="rp-stats-grid">
    {{-- Total Target Santri --}}
    <div class="rp-stat-card">
        <div>
            <div class="rp-stat-label">Total Target Santri</div>
            <div class="rp-stat-value" style="color:#0f172a;">{{ $stats['total_target'] }}</div>
            <div class="rp-stat-sub">
                @if($selectedSesi)
                    Wajib hadir sesi {{ $selectedSesi->nama_sesi }}
                @else
                    Santri aktif sesuai filter
                @endif
            </div>
        </div>
        <div class="rp-stat-icon" style="background:#eff6ff;color:#2563eb;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
    </div>

    {{-- Hadir Tepat Waktu --}}
    <div class="rp-stat-card">
        <div>
            <div class="rp-stat-label">Hadir Tepat Waktu</div>
            <div class="rp-stat-value" style="color:#16a34a;">{{ $stats['hadir'] }}</div>
            <div class="rp-stat-sub">Tap sebelum batas toleransi</div>
        </div>
        <div class="rp-stat-icon" style="background:#f0fdf4;color:#16a34a;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
    </div>

    {{-- Terlambat --}}
    <div class="rp-stat-card">
        <div>
            <div class="rp-stat-label">Terlambat</div>
            <div class="rp-stat-value" style="color:#d97706;">{{ $stats['terlambat'] }}</div>
            <div class="rp-stat-sub">Melewati batas toleransi sesi</div>
        </div>
        <div class="rp-stat-icon" style="background:#fffbeb;color:#d97706;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>

    {{-- Tidak Hadir / Alfa --}}
    <div class="rp-stat-card">
        <div>
            <div class="rp-stat-label">Tidak Hadir / Alfa</div>
            <div class="rp-stat-value" style="color:#dc2626;">{{ $stats['alfa'] }}</div>
            <div class="rp-stat-sub">Belum tap & tanpa surat izin</div>
        </div>
        <div class="rp-stat-icon" style="background:#fef2f2;color:#dc2626;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
    </div>

    {{-- Izin / Sakit --}}
    <div class="rp-stat-card">
        <div>
            <div class="rp-stat-label">Izin / Sakit</div>
            <div class="rp-stat-value" style="color:#2563eb;">{{ $stats['izin_sakit'] }}</div>
            <div class="rp-stat-sub">Izin Sambang: {{ $stats['izin'] }} &bull; Sakit: {{ $stats['sakit'] }}</div>
        </div>
        <div class="rp-stat-icon" style="background:#eff6ff;color:#2563eb;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
    </div>
</div>

{{-- 2. FILTER & KONTROL HALAMAN (FILAMENT 3.X) --}}
<div class="rp-filter-card">
    <form method="GET" action="{{ route('rekap.pondok.index') }}" id="formFilterRekapPondok">
        <input type="hidden" name="mode" id="inputMode" value="{{ $mode }}">

        <div class="rp-filter-grid">
            {{-- Filter Kategori Sesi --}}
            <div>
                <label class="rp-field-label" for="selectKategoriSesi">Kategori Sesi</label>
                <select name="kategori" id="selectKategoriSesi" class="form-select filter-select" style="width:100%;">
                    <option value="semua" {{ $kategori === 'semua' ? 'selected' : '' }}>Semua Kategori (Sholat & Pengajian)</option>
                    <option value="sholat" {{ $kategori === 'sholat' ? 'selected' : '' }}>Sholat Berjamaah</option>
                    <option value="pengajian" {{ $kategori === 'pengajian' ? 'selected' : '' }}>Pengajian / Diniyah</option>
                </select>
            </div>

            {{-- Filter Sesi Spesifik --}}
            <div>
                <label class="rp-field-label" for="selectSesiSpesifik">Sesi Presensi Spesifik</label>
                <select name="sesi_id" id="selectSesiSpesifik" class="form-select filter-select" style="width:100%;">
                    <option value="">Semua Sesi Pondok</option>
                    @foreach($allSesiPondok as $sesiItem)
                        <option value="{{ $sesiItem->id }}"
                                data-kategori="{{ strtolower($sesiItem->kategori) }}"
                                {{ (string) $sesiId === (string) $sesiItem->id ? 'selected' : '' }}>
                            {{ $sesiItem->nama_sesi }} ({{ ucfirst($sesiItem->kategori) }} &bull; {{ substr((string) $sesiItem->jam_mulai, 0, 5) }}-{{ substr((string) $sesiItem->jam_selesai, 0, 5) }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Kamar Santri --}}
            <div>
                <label class="rp-field-label" for="selectKamarSantri">Berdasarkan Kamar Santri</label>
                <select name="kamar" id="selectKamarSantri" class="form-select filter-select" style="width:100%;">
                    <option value="">Semua Kamar Asrama</option>
                    @foreach($kamarList as $kmr)
                        <option value="{{ $kmr }}" {{ $filterKamar === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Kelas Pengajian --}}
            <div>
                <label class="rp-field-label" for="selectKelasPengajian">Berdasarkan Kelas Pengajian</label>
                <select name="kelas_pengajian_id" id="selectKelasPengajian" class="form-select filter-select" style="width:100%;">
                    <option value="">Semua Kelas Pengajian</option>
                    @foreach($kelasPengajianList as $kp)
                        <option value="{{ $kp->id }}" {{ (string) $filterKelasPengajianId === (string) $kp->id ? 'selected' : '' }}>
                            {{ $kp->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Gender --}}
            <div>
                <label class="rp-field-label" for="selectGenderSantri">Filter Gender</label>
                <select name="gender" id="selectGenderSantri" class="form-select filter-select" style="width:100%;">
                    <option value="" {{ $filterGender === '' ? 'selected' : '' }}>Semua (Putra & Putri)</option>
                    <option value="L" {{ $filterGender === 'L' ? 'selected' : '' }}>Putra (L)</option>
                    <option value="P" {{ $filterGender === 'P' ? 'selected' : '' }}>Putri (P)</option>
                </select>
            </div>

            {{-- Filter Waktu: Mode Harian (Pemilihan Tanggal) --}}
            <div id="filterGroupHarian" style="{{ $mode === 'bulanan' ? 'display:none;' : '' }}">
                <label class="rp-field-label" for="inputTanggalRekap">Tanggal Presensi (Mode Harian)</label>
                <input type="date" name="tanggal" id="inputTanggalRekap" value="{{ $tanggal }}" class="form-input" style="width:100%;">
            </div>

            {{-- Filter Waktu: Mode Bulanan (Pilihan Bulan & Tahun) --}}
            <div id="filterGroupBulanan" style="{{ $mode === 'harian' ? 'display:none;' : 'display:grid;grid-template-columns:1.2fr 0.8fr;gap:8px;' }}">
                <div>
                    <label class="rp-field-label" for="selectBulanRekap">Bulan</label>
                    <select name="bulan" id="selectBulanRekap" class="form-select filter-select" style="width:100%;">
                        @foreach($namaBulanList as $num => $lbl)
                            <option value="{{ $num }}" {{ (int) $bulan === (int) $num ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="rp-field-label" for="selectTahunRekap">Tahun</label>
                    <select name="tahun" id="selectTahunRekap" class="form-select filter-select" style="width:100%;">
                        @for($yr = now()->year + 1; $yr >= 2023; $yr--)
                            <option value="{{ $yr }}" {{ (int) $tahun === (int) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Search Bar Live --}}
            <div>
                <label class="rp-field-label" for="liveSearchSantri">Cari Nama Santri / NISN</label>
                <div style="position:relative;">
                    <input type="text"
                           name="search"
                           id="liveSearchSantri"
                           value="{{ $search }}"
                           placeholder="Ketik nama santri atau NISN..."
                           class="form-input"
                           style="width:100%;padding-left:34px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none;">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
            </div>

            {{-- Tombol Terapkan & Reset --}}
            <div style="display:flex;align-items:center;gap:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    Terapkan Filter
                </button>
                <a href="{{ route('rekap.pondok.index', ['mode' => $mode]) }}" class="btn btn-secondary" title="Reset Filter">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

{{-- 3. TOGGLE TAB TAMPILAN DATA (REKAP HARIAN VS MATRIKS BULANAN) --}}
<div class="rp-tabs-bar">
    <div class="rp-tab-group" role="tablist">
        <button type="button"
                id="tabBtnHarian"
                class="rp-tab-btn {{ $mode === 'harian' ? 'active' : '' }}"
                onclick="switchRekapPondokTab('harian')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Rekap Harian (Detail Log Sesi)
        </button>
        <button type="button"
                id="tabBtnBulanan"
                class="rp-tab-btn {{ $mode === 'bulanan' ? 'active' : '' }}"
                onclick="switchRekapPondokTab('bulanan')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            Matriks Bulanan (1 s/d {{ $daysInMonth }})
        </button>
    </div>

    {{-- Keterangan Badge Warna --}}
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:0.76rem;color:#475569;">
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <span class="rp-matrix-cell rp-matrix-H">H</span> Hadir
        </span>
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <span class="rp-matrix-cell rp-matrix-T">T</span> Terlambat
        </span>
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <span class="rp-matrix-cell rp-matrix-I">I/S</span> Izin / Sakit
        </span>
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <span class="rp-matrix-cell rp-matrix-A">A</span> Alfa / Tidak Hadir
        </span>
    </div>
</div>

{{-- =========================================================================
     TAB 1: REKAP HARIAN (DETAIL LOG SESI)
     ========================================================================= --}}
<div id="panelRekapHarian" class="card" style="{{ $mode === 'bulanan' ? 'display:none;' : '' }}">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;font-size:0.98rem;font-weight:700;color:#0f172a;">
                Detail Log Presensi Pondok &mdash; {{ $tanggalCarbon->translatedFormat('d F Y') }}
            </h3>
            <p style="margin:3px 0 0;font-size:0.78rem;color:#64748b;">
                @if($selectedSesi)
                    Sesi: <strong>{{ $selectedSesi->nama_sesi }}</strong> ({{ ucfirst($selectedSesi->kategori) }} &bull; Jam {{ substr((string) $selectedSesi->jam_mulai, 0, 5) }} - {{ substr((string) $selectedSesi->jam_selesai, 0, 5) }}, Toleransi {{ (int) $selectedSesi->toleransi_menit }} menit)
                @else
                    Kategori: <strong>{{ $kategori === 'semua' ? 'Semua Sesi Pondok (Sholat & Pengajian)' : ucfirst($kategori) }}</strong>
                @endif
            </p>
        </div>

        <div style="display:flex;align-items:center;gap:8px;">
            <span style="font-size:0.78rem;color:#64748b;">
                Menampilkan <strong id="dailyVisibleCount">{{ count($dailyRows) }}</strong> santri
            </span>
        </div>
    </div>

    <div class="table-wrapper" style="overflow-x:auto;">
        <table id="tableRekapHarian" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="width:48px;text-align:center;">No</th>
                    <th>NISN</th>
                    <th>Nama Santri</th>
                    <th style="text-align:center;">Gender</th>
                    <th>Kamar / Kelas Pengajian</th>
                    <th style="text-align:center;">Jam Tap</th>
                    <th>Titik Mesin ESP32</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyRows as $idx => $row)
                    <tr class="rp-searchable-row"
                        data-search="{{ mb_strtolower($row['nama_lengkap'] . ' ' . $row['nisn'] . ' ' . $row['kamar'] . ' ' . $row['kelas_pengajian']) }}">
                        <td style="text-align:center;color:#64748b;font-weight:600;">{{ $idx + 1 }}</td>
                        <td style="font-family:monospace;font-size:0.82rem;color:#334155;font-weight:600;">
                            {{ $row['nisn'] }}
                        </td>
                        <td>
                            <div style="font-weight:700;color:#0f172a;">{{ $row['nama_lengkap'] }}</div>
                            @if(!empty($row['sesi_nama']) && !$selectedSesi)
                                <div style="font-size:0.72rem;color:#64748b;">Sesi: {{ $row['sesi_nama'] }}</div>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($row['gender_code'] === 'L')
                                <span style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:0.73rem;font-weight:700;background:#eff6ff;color:#1d4ed8;">Putra (L)</span>
                            @elseif($row['gender_code'] === 'P')
                                <span style="display:inline-block;padding:2px 8px;border-radius:6px;font-size:0.73rem;font-weight:700;background:#fdf2f8;color:#be185d;">Putri (P)</span>
                            @else
                                <span style="color:#94a3b8;">-</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:600;color:#1e293b;font-size:0.83rem;">{{ $row['kamar'] }}</div>
                            <div style="font-size:0.74rem;color:#64748b;">Pengajian: {{ $row['kelas_pengajian'] }}</div>
                        </td>
                        <td style="text-align:center;font-family:monospace;font-weight:700;font-size:0.84rem;">
                            @if($row['jam_tap'])
                                <span style="color:#0f172a;background:#f1f5f9;padding:3px 8px;border-radius:6px;">{{ $row['jam_tap'] }}</span>
                            @else
                                <span style="color:#cbd5e1;">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @if($row['device_id'])
                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.8rem;color:#334155;font-weight:600;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                                    {{ $row['device_label'] }}
                                </span>
                            @else
                                <span style="color:#94a3b8;font-size:0.8rem;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($row['status'] === 'hadir')
                                <span class="rp-badge rp-badge-hadir">Hadir</span>
                            @elseif($row['status'] === 'terlambat')
                                <span class="rp-badge rp-badge-terlambat">Terlambat</span>
                            @elseif($row['status'] === 'izin')
                                <span class="rp-badge rp-badge-izin" title="{{ $row['keterangan'] }}">Izin</span>
                            @elseif($row['status'] === 'sakit')
                                <span class="rp-badge rp-badge-izin" title="{{ $row['keterangan'] }}">Sakit</span>
                            @else
                                <span class="rp-badge rp-badge-alfa">Alfa</span>
                            @endif
                            @if(!empty($row['keterangan']) && in_array($row['status'], ['izin', 'sakit']))
                                <div style="font-size:0.69rem;color:#64748b;margin-top:3px;">{{ $row['keterangan'] }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:36px 16px;color:#64748b;">
                            Tidak ada data santri yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- =========================================================================
     TAB 2: MATRIKS BULANAN (TANGGAL 1 S/D 31)
     ========================================================================= --}}
<div id="panelMatriksBulanan" class="card" style="{{ $mode === 'harian' ? 'display:none;' : '' }}">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h3 style="margin:0;font-size:0.98rem;font-weight:700;color:#0f172a;">
                Matriks Kehadiran Bulanan Pondok &mdash; {{ $namaBulan }} {{ $tahun }}
            </h3>
            <p style="margin:3px 0 0;font-size:0.78rem;color:#64748b;">
                @if($selectedSesi)
                    Sesi: <strong>{{ $selectedSesi->nama_sesi }}</strong> ({{ ucfirst($selectedSesi->kategori) }})
                @else
                    Kategori: <strong>{{ $kategori === 'semua' ? 'Semua Sesi Pondok' : ucfirst($kategori) }}</strong>
                @endif
                &bull; Menampilkan tanggal 1 s/d {{ $daysInMonth }} {{ $namaBulan }} {{ $tahun }}
            </p>
        </div>

        <div style="font-size:0.78rem;color:#64748b;">
            Total: <strong id="matrixVisibleCount">{{ count($matriksRows) }}</strong> santri
        </div>
    </div>

    <div class="table-wrapper" style="overflow-x:auto;">
        <table id="tableMatriksBulanan" style="width:100%;border-collapse:collapse;font-size:0.78rem;">
            <thead>
                <tr>
                    <th style="width:38px;text-align:center;position:sticky;left:0;background:#f8fafc;z-index:2;">No</th>
                    <th style="min-width:190px;position:sticky;left:38px;background:#f8fafc;z-index:2;">Nama Santri &amp; NISN</th>
                    <th style="min-width:140px;">Kamar / Kelas</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        <th style="text-align:center;padding:6px 3px;min-width:28px;">{{ $d }}</th>
                    @endfor
                    <th style="text-align:center;background:#f0fdf4;color:#15803d;font-weight:700;" title="Total Hadir Tepat Waktu">Total H</th>
                    <th style="text-align:center;background:#fffbeb;color:#b45309;font-weight:700;" title="Total Terlambat">Total T</th>
                    <th style="text-align:center;background:#eff6ff;color:#1d4ed8;font-weight:700;" title="Total Izin / Sakit">Total I/S</th>
                    <th style="text-align:center;background:#fef2f2;color:#b91c1c;font-weight:700;" title="Total Alfa">Total A</th>
                    <th style="text-align:center;font-weight:700;min-width:85px;">% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matriksRows as $idx => $mRow)
                    <tr class="rp-searchable-matrix-row"
                        data-search="{{ mb_strtolower($mRow['nama_lengkap'] . ' ' . $mRow['nisn'] . ' ' . $mRow['kamar'] . ' ' . $mRow['kelas_pengajian']) }}">
                        <td style="text-align:center;color:#64748b;font-weight:600;position:sticky;left:0;background:#ffffff;z-index:1;">
                            {{ $idx + 1 }}
                        </td>
                        <td style="position:sticky;left:38px;background:#ffffff;z-index:1;">
                            <div style="font-weight:700;color:#0f172a;">{{ $mRow['nama_lengkap'] }}</div>
                            <div style="font-size:0.71rem;color:#64748b;font-family:monospace;">
                                {{ $mRow['nisn'] }} &bull; {{ $mRow['gender_code'] }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;color:#334155;font-size:0.76rem;">{{ $mRow['kamar'] }}</div>
                            <div style="font-size:0.7rem;color:#64748b;">{{ $mRow['kelas_pengajian'] }}</div>
                        </td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $cell = $mRow['days'][$d] ?? ['code' => '-', 'label' => '-', 'jam' => null];
                                $code = $cell['code'];
                                $cellClass = 'rp-matrix-empty';
                                if ($code === 'H') $cellClass = 'rp-matrix-H';
                                elseif ($code === 'T') $cellClass = 'rp-matrix-T';
                                elseif ($code === 'I' || $code === 'S') $cellClass = 'rp-matrix-I';
                                elseif ($code === 'A') $cellClass = 'rp-matrix-A';
                            @endphp
                            <td style="text-align:center;padding:4px 2px;">
                                <span class="rp-matrix-cell {{ $cellClass }}"
                                      title="Tgl {{ $d }}: {{ $cell['label'] }}{{ $cell['jam'] ? ' (' . $cell['jam'] . ')' : '' }}">
                                    {{ $code }}
                                </span>
                            </td>
                        @endfor
                        <td style="text-align:center;font-weight:700;color:#15803d;background:#f0fdf4;">{{ $mRow['total_h'] }}</td>
                        <td style="text-align:center;font-weight:700;color:#b45309;background:#fffbeb;">{{ $mRow['total_t'] }}</td>
                        <td style="text-align:center;font-weight:700;color:#1d4ed8;background:#eff6ff;">{{ $mRow['total_is'] }}</td>
                        <td style="text-align:center;font-weight:700;color:#b91c1c;background:#fef2f2;">{{ $mRow['total_a'] }}</td>
                        <td style="text-align:center;font-weight:800;color:{{ $mRow['persentase'] >= 80 ? '#15803d' : ($mRow['persentase'] >= 50 ? '#b45309' : '#b91c1c') }};">
                            {{ $mRow['persentase'] }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $daysInMonth + 8 }}" style="text-align:center;padding:36px 16px;color:#64748b;">
                            Tidak ada data santri yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    function switchRekapPondokTab(mode) {
        var inputMode = document.getElementById('inputMode');
        var panelHarian = document.getElementById('panelRekapHarian');
        var panelBulanan = document.getElementById('panelMatriksBulanan');
        var tabHarian = document.getElementById('tabBtnHarian');
        var tabBulanan = document.getElementById('tabBtnBulanan');
        var filterHarian = document.getElementById('filterGroupHarian');
        var filterBulanan = document.getElementById('filterGroupBulanan');

        if (inputMode) inputMode.value = mode;

        if (mode === 'bulanan') {
            if (panelHarian) panelHarian.style.display = 'none';
            if (panelBulanan) panelBulanan.style.display = '';
            if (tabHarian) tabHarian.classList.remove('active');
            if (tabBulanan) tabBulanan.classList.add('active');
            if (filterHarian) filterHarian.style.display = 'none';
            if (filterBulanan) filterBulanan.style.display = 'grid';
        } else {
            if (panelHarian) panelHarian.style.display = '';
            if (panelBulanan) panelBulanan.style.display = 'none';
            if (tabHarian) tabHarian.classList.add('active');
            if (tabBulanan) tabBulanan.classList.remove('active');
            if (filterHarian) filterHarian.style.display = '';
            if (filterBulanan) filterBulanan.style.display = 'none';
        }

        // Update link ekspor Excel & PDF agar mengikuti mode aktif
        ['btnExportExcel', 'btnExportPdf'].forEach(function (btnId) {
            var el = document.getElementById(btnId);
            if (!el) return;
            try {
                var url = new URL(el.href, window.location.origin);
                url.searchParams.set('mode', mode);
                el.href = url.toString();
            } catch (e) {}
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('liveSearchSantri');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = (this.value || '').toLowerCase().trim();

                // Filter baris Harian
                var dailyRows = document.querySelectorAll('#tableRekapHarian tbody tr.rp-searchable-row');
                var dailyVisible = 0;
                dailyRows.forEach(function (tr) {
                    var hay = tr.getAttribute('data-search') || '';
                    var match = !q || hay.indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) dailyVisible++;
                });
                var dailyCountEl = document.getElementById('dailyVisibleCount');
                if (dailyCountEl && dailyRows.length > 0) {
                    dailyCountEl.textContent = dailyVisible;
                }

                // Filter baris Matriks Bulanan
                var matrixRows = document.querySelectorAll('#tableMatriksBulanan tbody tr.rp-searchable-matrix-row');
                var matrixVisible = 0;
                matrixRows.forEach(function (tr) {
                    var hay = tr.getAttribute('data-search') || '';
                    var match = !q || hay.indexOf(q) !== -1;
                    tr.style.display = match ? '' : 'none';
                    if (match) matrixVisible++;
                });
                var matrixCountEl = document.getElementById('matrixVisibleCount');
                if (matrixCountEl && matrixRows.length > 0) {
                    matrixCountEl.textContent = matrixVisible;
                }
            });
        }
    });
</script>
@endpush
@endsection

