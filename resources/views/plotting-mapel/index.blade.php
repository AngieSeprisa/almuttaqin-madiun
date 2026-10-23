@extends('layouts.app')

@section('title', 'Plotting Mata Pelajaran')

@section('header-actions')
<div class="mapel-header-actions-wrap">
    @if(!$isYayasan)
    {{-- Badge sekolah: tampil di desktop, disembunyikan di mobile agar topbar tidak sesak --}}
    <div class="mapel-header-badge-sekolah">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:#2563EB;">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Sekolah: <strong>{{ $currentLembaga?->nama_singkat ?? ($currentLembaga?->nama ?? 'Sekolah') }}</strong></span>
    </div>
    @endif

    {{-- Tombol Tambah Mata Pelajaran (Responsif Desktop & Mobile) --}}
    <button type="button" class="btn btn-primary mapel-btn-tambah" onclick="bukaModalTambahMapel()" title="Tambah Mata Pelajaran">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        <span class="btn-text-full">Tambah Mata Pelajaran</span>
        <span class="btn-text-short">Tambah</span>
    </button>
</div>
@endsection

@section('content')
<style>
    /* ========================================================
       RESPONSIVE & OVERFLOW PREVENTION (FIT ON MOBILE)
       ======================================================== */
    .mapel-page-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    /* Header Actions */
    .mapel-header-actions-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .mapel-header-badge-sekolah {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(37, 99, 235, 0.08);
        border: 1px solid rgba(37, 99, 235, 0.2);
        border-radius: 10px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
    }

    .mapel-btn-tambah {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        font-size: 13px;
        border-radius: 10px;
        font-weight: 600;
        white-space: nowrap;
        height: 38px;
        box-sizing: border-box;
    }

    .mapel-btn-tambah .btn-text-short {
        display: none;
    }

    /* Keterangan Hak Akses Card */
    .mapel-info-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 18px;
        border-radius: 12px;
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        width: 100%;
        box-sizing: border-box;
    }

    /* Statistik Cards Grid */
    .mapel-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
        width: 100%;
        box-sizing: border-box;
    }

    .mapel-stat-box {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        box-sizing: border-box;
        min-width: 0;
    }

    .mapel-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Main Table & Filter Card */
    .mapel-table-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .mapel-toolbar-wrap {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-light);
        background: var(--bg-surface);
        width: 100%;
        box-sizing: border-box;
    }

    .mapel-filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        margin: 0;
        box-sizing: border-box;
    }

    .mapel-search-box {
        position: relative;
        flex: 1 1 auto;
        min-width: 160px;
        box-sizing: border-box;
    }

    .mapel-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        color: var(--text-muted);
        pointer-events: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mapel-search-input {
        width: 100%;
        height: 38px;
        padding: 7px 12px 7px 36px;
        font-size: 13px;
        border-radius: 10px;
        border: 1px solid var(--border-light);
        background: var(--bg-surface);
        color: var(--text-primary);
        outline: none;
        transition: all 0.15s ease;
        box-sizing: border-box;
    }

    .mapel-search-input:focus {
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .mapel-controls-group {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        box-sizing: border-box;
    }

    .mapel-select-sekolah {
        height: 38px !important;
        padding: 6px 32px 6px 12px !important;
        font-size: 12.5px !important;
        font-weight: 500 !important;
        border-radius: 10px !important;
        border: 1px solid var(--border-light) !important;
        background-color: var(--bg-surface) !important;
        color: var(--text-primary) !important;
        width: auto !important;
        min-width: 145px !important;
        max-width: 200px !important;
        cursor: pointer !important;
        outline: none !important;
        box-sizing: border-box !important;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 10px center !important;
        background-size: 13px 13px !important;
    }

    .mapel-btn-cari {
        height: 38px;
        padding: 0 15px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        white-space: nowrap;
        box-sizing: border-box;
        flex-shrink: 0;
    }

    .mapel-btn-reset {
        height: 38px;
        width: 38px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        text-decoration: none;
        box-sizing: border-box;
        flex-shrink: 0;
    }

    /* Responsive Table Container */
    .mapel-table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        box-sizing: border-box;
    }

    .mapel-data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 13px;
    }

    /* ========================================================
       MOBILE SPECIFIC STYLES (FIT ON SCREENS <= 768px)
       ======================================================== */
    @media (max-width: 768px) {
        .mapel-page-container {
            gap: 16px;
        }

        /* Topbar Header Actions */
        .mapel-header-badge-sekolah {
            display: none !important; /* Sembunyikan badge panjang di topbar mobile */
        }

        .mapel-btn-tambah {
            padding: 7px 12px;
            font-size: 12.5px;
            height: 36px;
        }

        .mapel-btn-tambah .btn-text-full {
            display: none;
        }

        .mapel-btn-tambah .btn-text-short {
            display: inline;
        }

        /* Info Card */
        .mapel-info-card {
            padding: 12px 14px;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        /* Stats Grid: 1 Kolom Full Width */
        .mapel-stats-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .mapel-stat-box {
            padding: 14px;
        }

        /* Toolbar Filter: Bersih & Fit */
        .mapel-toolbar-wrap {
            padding: 12px 14px;
        }

        /* Jika Yayasan: Baris 1 Search, Baris 2 Dropdown + Cari + Reset */
        .mapel-filter-form.has-school-filter {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
        }

        .mapel-filter-form.has-school-filter .mapel-search-box {
            width: 100%;
        }

        .mapel-filter-form.has-school-filter .mapel-controls-group {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mapel-filter-form.has-school-filter .mapel-select-sekolah {
            flex: 1 1 auto !important;
            width: 100% !important;
            min-width: 0 !important;
            max-width: none !important;
        }

        /* Jika Non-Yayasan (SMP/SMA): 1 Baris Sejajar Rapi! */
        .mapel-filter-form.no-school-filter {
            flex-direction: row;
            align-items: center;
            gap: 8px;
        }

        .mapel-filter-form.no-school-filter .mapel-search-box {
            flex: 1 1 auto;
        }

        .mapel-filter-form.no-school-filter .mapel-controls-group {
            display: inline-flex;
            gap: 6px;
        }

        /* Tabel cell padding di mobile */
        .mapel-data-table th,
        .mapel-data-table td {
            padding: 10px 12px !important;
        }
    }
</style>

{{-- Kontainer Full Layar yang 100% Fit --}}
<div class="mapel-page-container">

    {{-- Alert Banner --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46; box-sizing:border-box;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-banner alert-error" style="display:flex; align-items:flex-start; gap:12px; padding:12px 16px; border-radius:12px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); color:#991B1B; box-sizing:border-box;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; margin-top:2px; color:#EF4444;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="font-size:13px;">
            <div style="font-weight:700; margin-bottom:4px;">Gagal memproses data:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Keterangan Cakupan Hak Akses --}}
    <div class="mapel-info-card">
        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
            <div style="width:38px; height:38px; border-radius:10px; background:rgba(37, 99, 235, 0.1); display:flex; align-items:center; justify-content:center; color:#2563EB; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div style="min-width:0;">
                <h4 style="margin:0 0 2px 0; font-size:13.5px; font-weight:700; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    @if($isYayasan)
                        @if($currentLembaga)
                            Plotting Mata Pelajaran &bull; {{ $currentLembaga->nama }}
                        @else
                            Plotting Mata Pelajaran &bull; Seluruh Sekolah (SMP & SMA)
                        @endif
                    @else
                        Plotting Mata Pelajaran &bull; {{ $currentLembaga?->nama ?? 'Sekolah' }}
                    @endif
                </h4>
                <p style="margin:0; font-size:12px; color:var(--text-secondary); line-height:1.4;">
                    @if($isYayasan)
                        Yayasan dapat mengatur mata pelajaran unit sekolah (SMP & SMA).
                    @else
                        Mengatur mata pelajaran yang diajarkan pada {{ $currentLembaga?->nama ?? 'sekolah' }}.
                    @endif
                </p>
            </div>
        </div>

        @if($isYayasan && $currentLembaga)
        <a href="{{ route('plotting-mapel.index') }}?lembaga_id=all" class="btn btn-secondary" style="font-size:12px; padding:6px 12px; border-radius:8px; text-decoration:none; white-space:nowrap;">
            Semua Sekolah
        </a>
        @endif
    </div>

    {{-- Kartu Ringkasan Sederhana (Responsif 100% Fit) --}}
    <div class="mapel-stats-grid">
        <div class="mapel-stat-box">
            <div class="mapel-stat-icon" style="background:rgba(37, 99, 235, 0.1); color:#2563EB;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px;">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                </svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Total Mata Pelajaran</div>
                <div style="font-size:22px; font-weight:800; color:var(--text-primary); margin-top:2px; line-height:1.2;">{{ $stats['total_mapel'] }}</div>
                <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">Mata pelajaran terdaftar</div>
            </div>
        </div>

        <div class="mapel-stat-box">
            <div class="mapel-stat-icon" style="background:rgba(16, 185, 129, 0.1); color:#10B981;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px; height:22px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#059669;">Kode Mata Pelajaran</div>
                <div style="font-size:22px; font-weight:800; color:#059669; margin-top:2px; line-height:1.2;">{{ $stats['total_kode'] }}</div>
                <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">Kode unik tersimpan</div>
            </div>
        </div>
    </div>

    {{-- Filter, Search, dan Tabel Data --}}
    <div class="mapel-table-card">
        
        {{-- Toolbar Pencarian & Filter Sekolah --}}
        <div class="mapel-toolbar-wrap">
            <form action="{{ route('plotting-mapel.index') }}" method="GET" class="mapel-filter-form {{ $isYayasan ? 'has-school-filter' : 'no-school-filter' }}">
                
                {{-- Input Pencarian --}}
                <div class="mapel-search-box">
                    <span class="mapel-search-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari mata pelajaran atau kode..."
                           class="mapel-search-input">
                </div>

                {{-- Kontrol Aksi --}}
                <div class="mapel-controls-group">
                    {{-- Filter Sekolah (Khusus Yayasan) --}}
                    @if($isYayasan)
                    <select name="lembaga_id" class="mapel-select-sekolah" onchange="this.form.submit()" title="Filter Sekolah">
                        <option value="all" {{ empty($selectedLembagaId) ? 'selected' : '' }}>Semua Sekolah</option>
                        @foreach($accessibleLembagas as $lem)
                        <option value="{{ $lem->id }}" {{ $selectedLembagaId == $lem->id ? 'selected' : '' }}>
                            {{ $lem->nama_singkat ?? $lem->nama }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <button type="submit" class="btn btn-secondary mapel-btn-cari" title="Cari data">
                        <span>Cari</span>
                    </button>

                    @if(request()->anyFilled(['search']) || (request('lembaga_id') && request('lembaga_id') !== 'all'))
                    <a href="{{ route('plotting-mapel.index') }}" class="btn btn-secondary mapel-btn-reset" title="Reset Pencarian & Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                            <path d="M3 3v5h5"></path>
                        </svg>
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel Mata Pelajaran Responsif (Scroll internal tanpa merusak layar) --}}
        <div class="mapel-table-responsive">
            <table class="table mapel-data-table">
                <thead>
                    <tr style="background:var(--bg-primary); border-bottom:1px solid var(--border-light); color:var(--text-secondary); text-transform:uppercase; font-size:11px; letter-spacing:0.5px;">
                        <th style="padding:12px 16px; width:40px; text-align:center;">#</th>
                        @if($isYayasan && empty($selectedLembagaId))
                        <th style="padding:12px 16px; width:120px;">Sekolah</th>
                        @endif
                        <th style="padding:12px 16px; width:120px;">Kode</th>
                        <th style="padding:12px 16px;">Mata Pelajaran</th>
                        <th style="padding:12px 16px; width:120px; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mataPelajarans as $index => $item)
                    <tr style="border-bottom:1px solid var(--border-light); transition:background 0.15s ease;" onmouseover="this.style.background='var(--bg-primary)'" onmouseout="this.style.background='transparent'">
                        {{-- Nomor --}}
                        <td style="padding:12px 16px; text-align:center; color:var(--text-muted); font-size:12px;">
                            {{ $mataPelajarans->firstItem() + $index }}
                        </td>

                        {{-- Sekolah (Jika Yayasan melihat semua sekolah) --}}
                        @if($isYayasan && empty($selectedLembagaId))
                        <td style="padding:12px 16px;">
                            @php
                                $lemKode = strtolower($item->lembaga->kode ?? '');
                                $badgeColor = match($lemKode) {
                                    'smp' => ['bg' => 'rgba(37, 99, 235, 0.1)', 'text' => '#1D4ED8', 'border' => 'rgba(37, 99, 235, 0.25)'],
                                    'sma' => ['bg' => 'rgba(139, 92, 246, 0.1)', 'text' => '#6D28D9', 'border' => 'rgba(139, 92, 246, 0.25)'],
                                    default => ['bg' => 'rgba(107, 114, 128, 0.1)', 'text' => '#374151', 'border' => 'rgba(107, 114, 128, 0.25)'],
                                };
                            @endphp
                            <span style="display:inline-block; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; background:{{ $badgeColor['bg'] }}; color:{{ $badgeColor['text'] }}; border:1px solid {{ $badgeColor['border'] }};">
                                {{ $item->lembaga->nama_singkat ?? $item->lembaga->singkatan }}
                            </span>
                        </td>
                        @endif

                        {{-- Kode Mata Pelajaran --}}
                        <td style="padding:12px 16px;">
                            <span style="display:inline-block; font-family:monospace; font-size:12px; font-weight:700; padding:2px 7px; border-radius:6px; background:var(--bg-primary); border:1px solid var(--border-light); color:var(--text-primary); letter-spacing:0.5px; white-space:nowrap;">
                                {{ $item->kode ?: ($item->kode_mapel ?: '-') }}
                            </span>
                        </td>

                        {{-- Nama Mata Pelajaran --}}
                        <td style="padding:12px 16px;">
                            <span style="font-weight:700; color:var(--text-primary); font-size:13.5px;">
                                {{ $item->mata_pelajaran ?: $item->nama_pelajaran }}
                            </span>
                        </td>

                        {{-- Aksi Edit & Hapus --}}
                        <td style="padding:12px 16px; text-align:right;">
                            <div style="display:inline-flex; align-items:center; gap:5px;">
                                <button type="button" class="btn btn-secondary" onclick="bukaModalEditMapel({{ json_encode($item) }})"
                                        style="padding:5px 9px; font-size:12px; border-radius:7px; display:inline-flex; align-items:center; gap:3px;" title="Edit Mata Pelajaran">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </button>

                                <form action="{{ route('plotting-mapel.destroy', $item->id) }}" method="POST" style="margin:0; display:inline-block;"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran {{ addslashes($item->mata_pelajaran ?: $item->nama_pelajaran) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="padding:5px 8px; font-size:12px; border-radius:7px; color:#EF4444;" title="Hapus Mata Pelajaran">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px;">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ ($isYayasan && empty($selectedLembagaId)) ? 5 : 4 }}" style="padding:40px 16px; text-align:center; color:var(--text-muted);">
                            <div style="width:48px; height:48px; border-radius:12px; background:rgba(107, 114, 128, 0.08); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; color:var(--text-muted);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:24px; height:24px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <div style="font-weight:700; font-size:14px; color:var(--text-primary); margin-bottom:4px;">
                                Belum Ada Mata Pelajaran
                            </div>
                            <p style="font-size:12px; color:var(--text-secondary); max-width:360px; margin:0 auto 14px;">
                                Belum ada mata pelajaran yang diatur untuk sekolah ini.
                            </p>
                            <button type="button" class="btn btn-primary" onclick="bukaModalTambahMapel()" style="font-size:12.5px; padding:7px 14px; border-radius:8px;">
                                Tambah Mata Pelajaran Baru
                            </button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($mataPelajarans->hasPages())
        <div style="padding:12px 18px; border-top:1px solid var(--border-light); background:var(--bg-surface); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; box-sizing:border-box;">
            <div style="font-size:12px; color:var(--text-secondary);">
                Menampilkan {{ $mataPelajarans->firstItem() }} - {{ $mataPelajarans->lastItem() }} dari {{ $mataPelajarans->total() }} mata pelajaran
            </div>
            <div>
                {{ $mataPelajarans->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

{{-- MODAL TAMBAH MATA PELAJARAN --}}
<div id="modalTambahMapel" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.5); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px); box-sizing:border-box;">
    <div style="background:var(--bg-surface); width:100%; max-width:440px; border-radius:16px; box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border:1px solid var(--border-light); overflow:hidden; box-sizing:border-box;">
        
        {{-- Header Modal --}}
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(37, 99, 235, 0.1); color:#2563EB; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:14.5px; font-weight:700; color:var(--text-primary);">Tambah Mata Pelajaran</h3>
                    <p style="margin:0; font-size:11.5px; color:var(--text-secondary);">Masukkan mata pelajaran dan kodenya</p>
                </div>
            </div>
            <button type="button" onclick="tutupModalTambahMapel()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:6px; border-radius:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- Form Tambah: HANYA MATA PELAJARAN DAN KODE --}}
        <form action="{{ route('plotting-mapel.store') }}" method="POST" style="padding:18px 20px; box-sizing:border-box;">
            @csrf

            <div style="display:flex; flex-direction:column; gap:14px;">
                
                {{-- Sekolah (Jika Yayasan) --}}
                @if($isYayasan)
                <div>
                    <label for="tambah_lembaga_id" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Sekolah <span style="color:#EF4444;">*</span>
                    </label>
                    <select name="lembaga_id" id="tambah_lembaga_id" class="form-select" required style="width:100%; border-radius:10px; padding:8px 12px; font-size:13px;">
                        @foreach($accessibleLembagas as $lem)
                        <option value="{{ $lem->id }}" {{ ($selectedLembagaId == $lem->id || $currentLembaga?->id == $lem->id) ? 'selected' : '' }}>
                            {{ $lem->nama }} ({{ $lem->nama_singkat ?? $lem->kode }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" name="lembaga_id" value="{{ $currentLembaga?->id }}">
                @endif

                {{-- 1. Nama Mata Pelajaran --}}
                <div>
                    <label for="tambah_mata_pelajaran" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Mata Pelajaran <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="mata_pelajaran" id="tambah_mata_pelajaran" class="form-control" required
                           placeholder="Contoh: Matematika" style="width:100%; border-radius:10px; padding:9px 12px; font-size:13px;">
                </div>

                {{-- 2. Kode Mata Pelajaran --}}
                <div>
                    <label for="tambah_kode" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Kode <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="kode" id="tambah_kode" class="form-control" required
                           placeholder="Contoh: SMP-MAT" style="width:100%; border-radius:10px; padding:9px 12px; font-size:13px; text-transform:uppercase;">
                </div>

            </div>

            {{-- Footer Modal --}}
            <div style="margin-top:20px; padding-top:14px; border-top:1px solid var(--border-light); display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="tutupModalTambahMapel()" class="btn btn-secondary" style="padding:8px 14px; border-radius:8px; font-size:12.5px;">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" style="padding:8px 18px; border-radius:8px; font-size:12.5px; font-weight:600;">
                    Simpan
                </button>
            </div>
        </form>

    </div>
</div>

{{-- MODAL EDIT MATA PELAJARAN --}}
<div id="modalEditMapel" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.5); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px); box-sizing:border-box;">
    <div style="background:var(--bg-surface); width:100%; max-width:440px; border-radius:16px; box-shadow:0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border:1px solid var(--border-light); overflow:hidden; box-sizing:border-box;">
        
        {{-- Header Modal --}}
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:10px; background:rgba(16, 185, 129, 0.1); color:#10B981; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:14.5px; font-weight:700; color:var(--text-primary);">Edit Mata Pelajaran</h3>
                    <p style="margin:0; font-size:11.5px; color:var(--text-secondary);">Perbarui mata pelajaran dan kodenya</p>
                </div>
            </div>
            <button type="button" onclick="tutupModalEditMapel()" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:6px; border-radius:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- Form Edit: HANYA MATA PELAJARAN DAN KODE --}}
        <form id="formEditMapel" action="" method="POST" style="padding:18px 20px; box-sizing:border-box;">
            @csrf
            @method('PUT')

            <div style="display:flex; flex-direction:column; gap:14px;">
                
                {{-- Sekolah (Jika Yayasan) --}}
                @if($isYayasan)
                <div>
                    <label for="edit_lembaga_id" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Sekolah <span style="color:#EF4444;">*</span>
                    </label>
                    <select name="lembaga_id" id="edit_lembaga_id" class="form-select" required style="width:100%; border-radius:10px; padding:8px 12px; font-size:13px;">
                        @foreach($accessibleLembagas as $lem)
                        <option value="{{ $lem->id }}">{{ $lem->nama }} ({{ $lem->nama_singkat ?? $lem->kode }})</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- 1. Nama Mata Pelajaran --}}
                <div>
                    <label for="edit_mata_pelajaran" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Mata Pelajaran <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="mata_pelajaran" id="edit_mata_pelajaran" class="form-control" required
                           style="width:100%; border-radius:10px; padding:9px 12px; font-size:13px;">
                </div>

                {{-- 2. Kode Mata Pelajaran --}}
                <div>
                    <label for="edit_kode" class="form-label" style="font-weight:600; font-size:12.5px; margin-bottom:5px; display:block;">
                        Kode <span style="color:#EF4444;">*</span>
                    </label>
                    <input type="text" name="kode" id="edit_kode" class="form-control" required
                           style="width:100%; border-radius:10px; padding:9px 12px; font-size:13px; text-transform:uppercase;">
                </div>

            </div>

            {{-- Footer Modal --}}
            <div style="margin-top:20px; padding-top:14px; border-top:1px solid var(--border-light); display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="tutupModalEditMapel()" class="btn btn-secondary" style="padding:8px 14px; border-radius:8px; font-size:12.5px;">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" style="padding:8px 18px; border-radius:8px; font-size:12.5px; font-weight:600;">
                    Simpan Perubahan
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function bukaModalTambahMapel() {
        const modal = document.getElementById('modalTambahMapel');
        modal.style.display = 'flex';
        const inputMapel = document.getElementById('tambah_mata_pelajaran');
        if (inputMapel) {
            setTimeout(() => inputMapel.focus(), 50);
        }
    }

    function tutupModalTambahMapel() {
        document.getElementById('modalTambahMapel').style.display = 'none';
    }

    function bukaModalEditMapel(mapel) {
        const modal = document.getElementById('modalEditMapel');
        const form = document.getElementById('formEditMapel');

        form.action = "{{ url('plotting-mapel') }}/" + mapel.id;

        const editLembaga = document.getElementById('edit_lembaga_id');
        if (editLembaga) {
            editLembaga.value = mapel.lembaga_id;
        }

        const inputMapel = document.getElementById('edit_mata_pelajaran');
        const inputKode = document.getElementById('edit_kode');
        
        if (inputMapel) {
            inputMapel.value = mapel.mata_pelajaran || mapel.nama_pelajaran || '';
        }
        if (inputKode) {
            inputKode.value = mapel.kode || mapel.kode_mapel || '';
        }

        modal.style.display = 'flex';
        if (inputMapel) {
            setTimeout(() => inputMapel.focus(), 50);
        }
    }

    function tutupModalEditMapel() {
        document.getElementById('modalEditMapel').style.display = 'none';
    }

    // Tutup modal jika klik di luar box
    window.addEventListener('click', function(e) {
        const modalTambah = document.getElementById('modalTambahMapel');
        const modalEdit = document.getElementById('modalEditMapel');
        if (e.target === modalTambah) tutupModalTambahMapel();
        if (e.target === modalEdit) tutupModalEditMapel();
    });

    // Tutup modal dengan tombol Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalTambahMapel();
            tutupModalEditMapel();
        }
    });
</script>
@endsection
