@extends('layouts.app')

@section('title', 'Rekap Presensi Guru')

@section('page_header')
{{-- Dikosongkan agar Banner Header Presensi Harian menjadi tampilan utama sesuai Gambar 3 --}}
<span class="sr-only" style="display:none;">Rekap Absensi Guru</span>
@endsection

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('rekap-absensi.export', request()->all()) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel</span>
    </a>

    <a href="{{ route('rekap-absensi.cetak', request()->all()) }}" target="_blank" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak Laporan</span>
    </a>
</div>
@endsection

@section('content')
<style>
    .guru-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* 1. Header Banner Card (Persis Gambar 3) */
    .guru-header-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        flex-wrap: wrap;
        gap: 16px;
    }
    .guru-header-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .guru-header-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #2563eb;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
    }
    .guru-header-titles {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .guru-header-meta-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .guru-badge-header {
        background: #2563eb;
        color: #ffffff;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.6px;
        padding: 2.5px 8px;
        border-radius: 5px;
        text-transform: uppercase;
        display: inline-block;
    }
    .guru-sub-institution {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 600;
    }
    .guru-title-date {
        font-size: 21px;
        font-weight: 800;
        color: #0f172a;
        margin: 2px 0 0 0;
        line-height: 1.25;
    }

    .guru-header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .guru-pill-stat {
        padding: 7px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        background: #ffffff;
        transition: all 0.15s ease;
    }
    .pill-stat-tepat {
        border: 1.5px solid #10b981;
        color: #059669;
    }
    .pill-stat-total {
        border: 1.5px solid #3b82f6;
        color: #2563eb;
    }

    /* 2. Filter Bar Card */
    .guru-filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }
    .guru-filter-grid {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .guru-filter-select, .guru-filter-input {
        height: 38px;
        padding: 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 13px;
        color: #111827;
        background: #ffffff;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .guru-filter-select:focus, .guru-filter-input:focus {
        border-color: #2563eb;
    }

    /* 3. Table Card (Persis Gambar 3) */
    .guru-table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .guru-table-scroll {
        overflow-x: auto;
        width: 100%;
    }
    .guru-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        white-space: nowrap;
    }
    .guru-table th {
        background: #ffffff;
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.6px;
        text-transform: uppercase;
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
    }
    .guru-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
    }
    .guru-table tbody tr:hover td {
        background-color: #f8fafc;
    }

    .guru-avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1e40af;
        font-size: 12.5px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .guru-name-text {
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
    }

    .guru-jam-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 11px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
    }
    .guru-jam-pill svg {
        color: #64748b;
    }

    .guru-tidak-pulang-pill {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 600;
    }

    /* Badges Status Kehadiran (Gambar 3) */
    .guru-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-tepat {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #16a34a;
    }
    .badge-terlambat {
        background: #fffbeb;
        border: 1px solid #fef08a;
        color: #d97706;
    }
    .badge-izin {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #2563eb;
    }
    .badge-sakit {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
        color: #7c3aed;
    }
    .badge-alpa {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }

    .guru-action-eye-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .guru-action-eye-btn:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #2563eb;
    }

    /* Footer Pagination */
    .guru-table-footer {
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #e2e8f0;
        font-size: 12.5px;
        color: #64748b;
        flex-wrap: wrap;
        gap: 12px;
        background: #ffffff;
    }
    .guru-footer-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .guru-perpage-select {
        height: 32px;
        padding: 2px 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #111827;
        background: #ffffff;
        outline: none;
    }
</style>

<div class="guru-container">

    {{-- Alert Banner --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    {{-- 1. HEADER PRESENSI HARIAN (PERSIS GAMBAR 3) --}}
    @php
        $selectedLembaga = $lembagaId ? $lembagas->firstWhere('id', $lembagaId) : null;
        $lembagaText = $selectedLembaga ? ($selectedLembaga->nama_singkat ?? $selectedLembaga->nama) : 'Seluruh Lembaga';
        $subHeader = $selectedLembaga ? ($lembagaText . ' • Yayasan Muttaqin Kota Madiun') : 'Seluruh Lembaga • Yayasan Muttaqin Kota Madiun';
        $displayDate = $tanggal ? \Carbon\Carbon::parse($tanggal) : \Carbon\Carbon::now();
        $dateFormatted = $displayDate->translatedFormat('l, d M Y');
    @endphp

    <div class="guru-header-card">
        <div class="guru-header-left">
            <div class="guru-header-icon-box">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="guru-header-titles">
                <div class="guru-header-meta-row">
                    <span class="guru-badge-header">HEADER PRESENSI HARIAN</span>
                    <span class="guru-sub-institution">{{ $subHeader }}</span>
                </div>
                <h1 class="guru-title-date">{{ $dateFormatted }}</h1>
            </div>
        </div>

        <div class="guru-header-right">
            <div class="guru-pill-stat pill-stat-tepat">
                <span>{{ $ringkasan['hadir'] }} Tepat Waktu</span>
                <span class="sr-only" style="display:none;">Hadir Tepat</span>
            </div>
            <div class="guru-pill-stat pill-stat-total">
                <span>{{ $ringkasan['total'] }} Total Guru</span>
            </div>
        </div>
    </div>

    {{-- 2. FILTER KONTROL --}}
    <div class="guru-filter-card">
        <form action="{{ route('rekap-absensi.guru') }}" method="GET" class="guru-filter-grid">
            {{-- Filter Lembaga --}}
            @if($lembagas->count() > 1)
            <select name="lembaga_id" class="guru-filter-select" onchange="this.form.submit()">
                <option value="" {{ empty($lembagaId) ? 'selected' : '' }}>Semua Lembaga</option>
                @foreach($lembagas as $lem)
                <option value="{{ $lem->id }}" {{ (string)$lembagaId === (string)$lem->id ? 'selected' : '' }}>
                    {{ $lem->nama_singkat ?? $lem->nama }}
                </option>
                @endforeach
            </select>
            @endif

            {{-- Filter Tanggal Harian --}}
            <input type="date" name="tanggal" value="{{ $tanggal ?: date('Y-m-d') }}" class="guru-filter-input" onchange="this.form.submit()" title="Pilih Tanggal Presensi">

            {{-- Filter Status --}}
            <select name="status" class="guru-filter-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="hadir" {{ $status === 'hadir' ? 'selected' : '' }}>Tepat Waktu</option>
                <option value="terlambat" {{ $status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                <option value="izin" {{ $status === 'izin' ? 'selected' : '' }}>Izin</option>
                <option value="sakit" {{ $status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                <option value="alpa" {{ $status === 'alpa' ? 'selected' : '' }}>Alpa</option>
            </select>

            {{-- Pencarian --}}
            <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama guru..." class="guru-filter-input" style="width:220px;">
                <button type="submit" class="btn btn-secondary" style="height:38px; padding:0 14px; border-radius:8px;">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
                @if($search || $status || request('tanggal'))
                <a href="{{ route('rekap-absensi.guru') }}" class="btn btn-secondary" style="height:38px; display:inline-flex; align-items:center; border-radius:8px;" title="Reset Filter">
                    ✕
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- 3. TABEL PRESENSI GURU (PERSIS GAMBAR 3) --}}
    <div class="guru-table-card">
        <div class="guru-table-scroll">
            <table class="guru-table">
                <thead>
                    <tr>
                        <th style="width:50px;">NO</th>
                        <th style="min-width:240px;">NAMA GURU</th>
                        <th style="min-width:160px;">TANGGAL</th>
                        <th style="min-width:160px;">JAM MASUK</th>
                        <th style="min-width:160px;">JAM PULANG</th>
                        <th style="min-width:160px;">STATUS KEHADIRAN</th>
                        <th style="width:80px; text-align:center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presensis as $index => $row)
                    <tr>
                        <td>
                            {{ $presensis->firstItem() + $index }}
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div class="guru-avatar-circle">
                                    {{ strtoupper(substr($row->user->name ?? 'G', 0, 1)) }}
                                </div>
                                <div>
                                    <span class="guru-name-text">{{ $row->user->name ?? '-' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $row->tanggal ? $row->tanggal->translatedFormat('l, d M Y') : '-' }}
                        </td>
                        <td>
                            @if($row->jam_masuk)
                            <div class="guru-jam-pill">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ strlen($row->jam_masuk) <= 5 ? $row->jam_masuk . ':00' : substr($row->jam_masuk, 0, 8) }} WIB</span>
                            </div>
                            @else
                            <span style="color:#94a3b8; font-size:13px;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($row->jam_pulang)
                            <div class="guru-jam-pill">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ strlen($row->jam_pulang) <= 5 ? $row->jam_pulang . ':00' : substr($row->jam_pulang, 0, 8) }} WIB</span>
                            </div>
                            @else
                            <span class="guru-tidak-pulang-pill">Tidak Absen Pulang</span>
                            @endif
                        </td>
                        <td>
                            @if($row->status === 'hadir')
                            <span class="guru-status-badge badge-tepat">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Tepat Waktu</span>
                            </span>
                            @elseif($row->status === 'terlambat')
                            <span class="guru-status-badge badge-terlambat">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>Terlambat</span>
                            </span>
                            @elseif($row->status === 'izin')
                            <span class="guru-status-badge badge-izin">Izin</span>
                            @elseif($row->status === 'sakit')
                            <span class="guru-status-badge badge-sakit">Sakit</span>
                            @else
                            <span class="guru-status-badge badge-alpa">Alpa</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="guru-action-eye-btn btn-koreksi"
                                    data-id="{{ $row->id }}"
                                    data-nama="{{ $row->user->name ?? '-' }}"
                                    data-tanggal="{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '' }}"
                                    data-status="{{ $row->status }}"
                                    data-masuk="{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '' }}"
                                    data-pulang="{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '' }}"
                                    data-keterangan="{{ $row->keterangan ?? '' }}"
                                    title="Lihat / Koreksi Presensi">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="padding:48px 20px; text-align:center; color:#94a3b8; font-size:14px;">
                            Tidak ada data presensi guru untuk filter yang dipilih.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Paginasi (Persis Gambar 3) --}}
        <div class="guru-table-footer">
            <div class="guru-footer-left">
                <span>Menampilkan</span>
                <select name="per_page" class="guru-perpage-select" onchange="changeGuruPerPage(this.value)">
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span>dari {{ $presensis->total() }} total baris rekapitulasi</span>
            </div>
            <div class="guru-footer-right">
                @if($presensis->hasPages())
                    <div style="display:flex; align-items:center; gap:8px;">
                        @if($presensis->onFirstPage())
                            <span style="color:#cbd5e1; cursor:not-allowed;">&lsaquo;</span>
                        @else
                            <a href="{{ $presensis->previousPageUrl() }}" style="color:#2563eb; text-decoration:none;">&lsaquo;</a>
                        @endif
                        <span style="font-weight:600; color:#1e293b;">Halaman {{ $presensis->currentPage() }} dari {{ $presensis->lastPage() }}</span>
                        @if($presensis->hasMorePages())
                            <a href="{{ $presensis->nextPageUrl() }}" style="color:#2563eb; text-decoration:none;">&rsaquo;</a>
                        @else
                            <span style="color:#cbd5e1; cursor:not-allowed;">&rsaquo;</span>
                        @endif
                    </div>
                @else
                    <span style="font-weight:600; color:#1e293b;">Halaman 1 dari 1</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Koreksi Presensi --}}
<div id="modalKoreksi" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.45); backdrop-filter:blur(3px); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#ffffff; border-radius:16px; border:1px solid #e2e8f0; width:100%; max-width:440px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:16px; font-weight:700; color:#0f172a; margin:0;">Koreksi Presensi Guru</h3>
                <span id="modalGuruNama" style="font-size:12.5px; color:#64748b; display:block; margin-top:2px;"></span>
            </div>
            <button type="button" id="btnCloseModal" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:20px;">
                &times;
            </button>
        </div>

        <form id="formKoreksi" method="POST" action="" style="padding:22px; display:flex; flex-direction:column; gap:16px;">
            @csrf
            @method('PUT')

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#0f172a; margin-bottom:6px;">Status</label>
                <select name="status" id="modalStatus" class="form-select" required
                        style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a;">
                    <option value="hadir">Tepat Waktu</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alpa">Alpa</option>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#0f172a; margin-bottom:6px;">Jam Masuk</label>
                    <input type="time" name="jam_masuk" id="modalMasuk" class="form-control"
                           style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a;">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#0f172a; margin-bottom:6px;">Jam Pulang</label>
                    <input type="time" name="jam_pulang" id="modalPulang" class="form-control"
                           style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a;">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#0f172a; margin-bottom:6px;">Keterangan Koreksi</label>
                <textarea name="keterangan" id="modalKeterangan" rows="2" class="form-control" placeholder="Contoh: Tugas luar kota / lupa scan..."
                          style="width:100%; padding:9px 12px; font-size:13px; border-radius:10px; border:1px solid #cbd5e1; background:#ffffff; color:#0f172a; resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                <button type="button" id="btnCancelModal" class="btn btn-secondary" style="padding:8px 16px; font-size:13px; border-radius:8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px; font-weight:600; border-radius:8px;">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

<script>
function changeGuruPerPage(val) {
    var url = new URL(window.location.href);
    url.searchParams.set('per_page', val);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalKoreksi');
    var form = document.getElementById('formKoreksi');
    var modalGuruNama = document.getElementById('modalGuruNama');
    var modalStatus = document.getElementById('modalStatus');
    var modalMasuk = document.getElementById('modalMasuk');
    var modalPulang = document.getElementById('modalPulang');
    var modalKeterangan = document.getElementById('modalKeterangan');
    var btnClose = document.getElementById('btnCloseModal');
    var btnCancel = document.getElementById('btnCancelModal');

    document.querySelectorAll('.btn-koreksi').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            var tanggal = this.getAttribute('data-tanggal');
            var status = this.getAttribute('data-status');
            var masuk = this.getAttribute('data-masuk');
            var pulang = this.getAttribute('data-pulang');
            var keterangan = this.getAttribute('data-keterangan');

            form.action = '{{ url("rekap-absensi/koreksi") }}/' + id;
            modalGuruNama.textContent = nama + ' • ' + tanggal;
            modalStatus.value = status;
            modalMasuk.value = masuk;
            modalPulang.value = pulang;
            modalKeterangan.value = keterangan;

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
    }

    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
});
</script>
@endsection
