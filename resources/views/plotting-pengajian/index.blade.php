@extends('layouts.app')

@section('title', 'Plotting Kelas Pengajian')

@section('header-actions')
<div class="pk-header-actions-wrap" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    {{-- Tombol Muat Standar --}}
    <form action="{{ route('plotting-pengajian.muat-standar') }}" method="POST" class="d-inline" id="formMuatStandarPengajian">
        @csrf
        <button type="button" class="pk-btn-secondary" title="Muat daftar kelas pengajian standar pondok"
                onclick="openFiConfirm({
                    variant: 'primary',
                    title: 'Muat Kelas Standar Pengajian',
                    desc: 'Sistem akan memeriksa dan menambahkan 4 kelas pengajian standar (Kelas Lambatan SMP, Kelas Cepatan SMP, Kelas Pegon Bacaan SMP, Kelas Reguler) jika belum ada di database.',
                    confirmText: 'Muat Standar',
                    onConfirm: function() { document.getElementById('formMuatStandarPengajian').submit(); }
                })">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            <span>Muat Standar</span>
        </button>
    </form>

    {{-- Tombol Kelola Master Kelas --}}
    <button type="button" class="pk-btn-secondary" onclick="openKelolaKelasModal()" title="Lihat dan kelola master kelas pengajian">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <span>Master Kelas ({{ $totalKelas }})</span>
    </button>

    {{-- Tombol Tambah Kelas Baru --}}
    <button type="button" class="pk-btn-add-kelas" onclick="openTambahKelasModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Kelas</span>
    </button>
</div>
@endsection

@section('content')
<div class="pk-container">

    {{-- Alert Flash Messages --}}
    @if(session('success'))
    <div class="pk-alert pk-alert-success" id="pkFlashAlert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-alert-icon">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
        <button type="button" class="pk-alert-close" onclick="document.getElementById('pkFlashAlert').remove()">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="pk-alert pk-alert-danger" id="pkFlashAlertErr">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-alert-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>{{ session('error') }}</span>
        <button type="button" class="pk-alert-close" onclick="document.getElementById('pkFlashAlertErr').remove()">&times;</button>
    </div>
    @endif

    @if($errors->any())
    <div class="pk-alert pk-alert-danger" id="pkFlashAlertVal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-alert-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>{{ $errors->first() }}</span>
        <button type="button" class="pk-alert-close" onclick="document.getElementById('pkFlashAlertVal').remove()">&times;</button>
    </div>
    @endif

    {{-- Mini Stats Grid --}}
    <div class="stats-grid" style="margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <div class="stat-card" style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div class="stat-card-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span class="stat-card-title" style="font-size:12.5px; font-weight:600; color:#6B7280;">Total Santri Aktif</span>
                <div class="stat-icon-wrapper" style="width:34px; height:34px; border-radius:8px; background:#EFF6FF; color:#0052FF; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number" style="font-size:24px; font-weight:700; color:#111827;">{{ number_format($totalSantriOverall, 0, ',', '.') }}</div>
                <div style="font-size:11.5px; color:#9CA3AF; margin-top:2px;">Santri mukim pondok</div>
            </div>
        </div>

        <div class="stat-card" style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div class="stat-card-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span class="stat-card-title" style="font-size:12.5px; font-weight:600; color:#059669;">Sudah Terplotting</span>
                <div class="stat-icon-wrapper" style="width:34px; height:34px; border-radius:8px; background:#ECFDF5; color:#059669; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number" style="font-size:24px; font-weight:700; color:#059669;">{{ number_format($totalTerplotting, 0, ',', '.') }}</div>
                <div style="font-size:11.5px; color:#059669; font-weight:600; margin-top:2px;">{{ $persenTerplotting }}% dari total santri</div>
            </div>
        </div>

        <div class="stat-card" style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div class="stat-card-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span class="stat-card-title" style="font-size:12.5px; font-weight:600; color: {{ $totalBelumTerplotting > 0 ? '#DC2626' : '#6B7280' }};">Belum Terplotting</span>
                <div class="stat-icon-wrapper" style="width:34px; height:34px; border-radius:8px; background: {{ $totalBelumTerplotting > 0 ? '#FEF2F2' : '#F3F4F6' }}; color: {{ $totalBelumTerplotting > 0 ? '#DC2626' : '#9CA3AF' }}; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number" style="font-size:24px; font-weight:700; color: {{ $totalBelumTerplotting > 0 ? '#DC2626' : '#111827' }};">{{ number_format($totalBelumTerplotting, 0, ',', '.') }}</div>
                <div style="font-size:11.5px; color: {{ $totalBelumTerplotting > 0 ? '#DC2626' : '#9CA3AF' }}; font-weight:500; margin-top:2px;">
                    {{ $totalBelumTerplotting > 0 ? 'Perlu penempatan kelas' : 'Seluruh santri telah diplot' }}
                </div>
            </div>
        </div>

        <div class="stat-card" style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div class="stat-card-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span class="stat-card-title" style="font-size:12.5px; font-weight:600; color:#6B7280;">Master Kelas Pengajian</span>
                <div class="stat-icon-wrapper" style="width:34px; height:34px; border-radius:8px; background:#F5F3FF; color:#7C3AED; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number" style="font-size:24px; font-weight:700; color:#111827;">{{ $totalKelas }}</div>
                <div style="font-size:11.5px; color:#7C3AED; font-weight:500; margin-top:2px; cursor:pointer;" onclick="openKelolaKelasModal()">
                    Kelola daftar kelas &rarr;
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- FILAMENT V4 TABLE CARD: DAFTAR SANTRI PLOTTING PENGAJIAN                --}}
    {{-- ======================================================================= --}}
    <div class="fi-ta-ctn" id="fiCardPengajian">

        {{-- Top Toolbar: [ ⋮ Aksi ] on Left | [ 🔍 Cari... ] + [ Funnel Filter ] + [ Col Customizer ] on Right --}}
        <div class="fi-ta-header-toolbar">
            <div class="fi-ta-toolbar-left">
                <div class="fi-ta-bulk-wrap" id="bulkWrapPengajian">
                    <button type="button"
                            class="fi-ta-bulk-btn"
                            id="btnBulkPengajian"
                            disabled
                            onclick="toggleDropdownMenu('menuBulkPengajian', event)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                            <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                            <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                        </svg>
                        <span>Aksi</span>
                        <span class="fi-ta-bulk-count" id="bulkCountBadgePengajian" style="display:none;">0</span>
                    </button>

                    <div class="fi-ta-bulk-menu" id="menuBulkPengajian">
                        <button type="button" class="fi-ta-bulk-item is-primary" onclick="focusBulkTargetSelect()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                            </svg>
                            <span>Tetapkan Kelas Terpilih</span>
                        </button>
                        <button type="button" class="fi-ta-bulk-item is-danger" onclick="confirmBulkUnassignPengajian()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            <span>Keluarkan dari Kelas (Unassign)</span>
                        </button>
                        <div class="fi-ta-bulk-divider"></div>
                        <button type="button" class="fi-ta-bulk-item" onclick="clearSelection('pengajian')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            <span>Batalkan pilihan</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="fi-ta-toolbar-right">
                {{-- Search Box --}}
                <div class="fi-ta-search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fi-ta-search-icon">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text"
                           id="searchPengajian"
                           class="fi-ta-search-input"
                           placeholder="Cari santri, NISN, kamar..."
                           oninput="onSearchInput('pengajian')">
                    <button type="button" class="fi-ta-search-clear" id="clearSearchPengajian" onclick="clearSearch('pengajian')" title="Bersihkan pencarian">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                {{-- Funnel Filter Button + Popover --}}
                <div class="fi-ta-filter-wrap" id="filterWrapPengajian">
                    <button type="button"
                            class="fi-ta-filter-btn"
                            id="btnFilterPengajian"
                            title="Filter Data Santri"
                            onclick="toggleDropdownMenu('popoverFilterPengajian', event)">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                        </svg>
                        <span class="fi-ta-filter-badge" id="badgeFilterPengajian">0</span>
                    </button>

                    <div class="fi-ta-filter-popover" id="popoverFilterPengajian" onclick="event.stopPropagation()">
                        <div class="fi-ta-filter-head">
                            <span class="fi-ta-filter-title">Filter Santri</span>
                            <button type="button" class="fi-ta-filter-reset" onclick="resetFilter('pengajian')">Reset</button>
                        </div>
                        <div class="fi-ta-filter-body">
                            {{-- Filter Kelas Pengajian --}}
                            <div class="fi-ta-filter-field">
                                <label for="fltPengajianKelas" class="fi-ta-filter-label">Kelas Pengajian</label>
                                <select id="fltPengajianKelas" class="fi-ta-filter-select">
                                    <option value="">Semua Kelas Pengajian</option>
                                    <option value="unassigned">-- Belum Diplot ({{ $totalBelumTerplotting }}) --</option>
                                    <option value="assigned">-- Sudah Diplot ({{ $totalTerplotting }}) --</option>
                                    @foreach($masterKelasList as $mk)
                                        <option value="{{ $mk->id }}">{{ $mk->nama_kelas }} ({{ $mk->siswas_count }} Santri)</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filter Gender --}}
                            <div class="fi-ta-filter-field">
                                <label for="fltPengajianGender" class="fi-ta-filter-label">Jenis Kelamin (Gender)</label>
                                <select id="fltPengajianGender" class="fi-ta-filter-select">
                                    <option value="">Semua Gender</option>
                                    <option value="laki-laki">Putra (Laki-laki)</option>
                                    <option value="perempuan">Putri (Perempuan)</option>
                                </select>
                            </div>

                            {{-- Filter Jenjang Sekolah --}}
                            <div class="fi-ta-filter-field">
                                <label for="fltPengajianJenjang" class="fi-ta-filter-label">Jenjang Sekolah</label>
                                <select id="fltPengajianJenjang" class="fi-ta-filter-select">
                                    <option value="">Semua Jenjang</option>
                                    <option value="smp">SMP</option>
                                    <option value="sma">SMA</option>
                                    <option value="reguler">Reguler / Non-formal</option>
                                </select>
                            </div>
                        </div>
                        <div class="fi-ta-filter-actions">
                            <button type="button" class="fi-ta-filter-apply" onclick="applyFilter('pengajian')">
                                Terapkan filter
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Custom Kolom Toggle Button + Popover --}}
                <div class="fi-ta-col-wrap" id="colWrapPengajian">
                    <button type="button"
                            class="fi-ta-col-btn"
                            id="btnColsPengajian"
                            title="Sesuaikan Kolom"
                            onclick="toggleDropdownMenu('popoverColsPengajian', event)">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <rect x="3" y="3" width="3.2" height="14" rx="1.6" />
                            <rect x="8.4" y="3" width="3.2" height="14" rx="1.6" />
                            <rect x="13.8" y="3" width="3.2" height="14" rx="1.6" />
                        </svg>
                    </button>

                    <div class="fi-ta-col-popover" id="popoverColsPengajian" onclick="event.stopPropagation()">
                        <div class="fi-ta-col-head">
                            <span class="fi-ta-col-title">Kolom</span>
                            <button type="button" class="fi-ta-col-reset" onclick="resetColumns('pengajian')">Reset</button>
                        </div>
                        <div class="fi-ta-col-list" id="colListPengajian"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Selection Strip (Bulk Action Bar) --}}
        <div class="fi-ta-selection-bar" id="selBarPengajian" style="display:none; background:#EFF6FF; border-bottom:1px solid #BFDBFE; padding:10px 16px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <span class="fi-ta-selection-count" id="selCountTextPengajian" style="font-weight:700; color:#1E40AF; font-size:13px;">0 santri dipilih</span>
                <div class="fi-ta-selection-links" style="display:inline-flex; align-items:center; gap:8px;">
                    <button type="button" class="fi-ta-link-select-all" id="btnSelectAllMatchingPengajian" onclick="selectAllMatching('pengajian')" style="color:#0052FF; font-weight:600; font-size:12px; background:none; border:none; cursor:pointer;">
                        Pilih semua {{ $allSantri->count() }} santri
                    </button>
                    <span style="color:#93C5FD;">•</span>
                    <button type="button" class="fi-ta-link-deselect" onclick="clearSelection('pengajian')" style="color:#6B7280; font-size:12px; background:none; border:none; cursor:pointer;">
                        Batalkan pilihan
                    </button>
                </div>
            </div>

            {{-- Bulk Assignment Quick Dropdown --}}
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <label for="bulkTargetKelas" style="font-size:12px; font-weight:600; color:#1E3A8A;">Target Kelas:</label>
                <select id="bulkTargetKelas" class="fi-ta-filter-select" style="min-width: 220px; font-size:12.5px; padding:6px 10px; border-radius:8px; border:1px solid #93C5FD; background:#fff;">
                    <option value="">-- Pilih Kelas Pengajian Tujuan --</option>
                    @foreach($masterKelasList as $mk)
                        <option value="{{ $mk->id }}">{{ $mk->nama_kelas }} ({{ strtoupper($mk->jenjang) }})</option>
                    @endforeach
                    <option value="unassign">-- Kosongkan / Keluarkan dari Kelas --</option>
                </select>

                <button type="button"
                        id="btnBulkApply"
                        onclick="applyBulkTargetPengajian()"
                        style="padding:6px 14px; font-size:12.5px; font-weight:600; color:#fff; background:#0052FF; border:none; border-radius:8px; cursor:pointer; box-shadow:0 1px 2px rgba(0,82,255,0.2);">
                    Terapkan Plotting
                </button>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="fi-ta-table-wrap">
            <table class="fi-ta-table" id="tablePengajian">
                <thead>
                    <tr>
                        <th class="fi-ta-th-check" style="width: 44px; text-align:center;">
                            <input type="checkbox" id="checkAllPagePengajian" class="fi-ta-checkbox" onchange="togglePageSelection('pengajian', this.checked)" title="Pilih semua di halaman ini">
                        </th>
                        <th>
                            <button type="button" class="fi-ta-th-sort" onclick="sortTableBy('pengajian', 'nama')">
                                <span>Nama Santri</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                        </th>
                        <th class="pk-col-nisn">NISN</th>
                        <th class="pk-col-gender" style="text-align:center;">Gender</th>
                        <th class="pk-col-kamar">Kamar</th>
                        <th class="pk-col-kelas-sekolah">Kelas Sekolah</th>
                        <th class="pk-col-kelas-pengajian">Kelas Pengajian Saat Ini</th>
                        <th class="pk-col-aksi" style="text-align:right; padding-right:16px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbodyPengajian">
                    @forelse($allSantri as $s)
                    @php
                        $gender = strtolower((string)($s->pendaftaran?->jenis_kelamin ?? ''));
                        $isPutra = in_array($gender, ['l', 'laki-laki', 'putra']);
                        $isPutri = in_array($gender, ['p', 'perempuan', 'putri']);
                        $nisnVal = $s->pendaftaran?->nisn ?: ($s->pendaftaran?->no_induk ?: '-');
                        $kelasPengajianNama = $s->kelasPengajian?->nama_kelas;
                        $kelasPengajianId = $s->kelas_pengajian_id;
                    @endphp
                    <tr class="fi-ta-row"
                        id="rowPengajian_{{ $s->id }}"
                        data-id="{{ $s->id }}"
                        data-nama="{{ mb_strtolower($s->nama_lengkap) }}"
                        data-nisn="{{ mb_strtolower($nisnVal) }}"
                        data-gender="{{ $isPutra ? 'laki-laki' : ($isPutri ? 'perempuan' : 'umum') }}"
                        data-jenjang="{{ strtolower($s->jenjang ?? 'reguler') }}"
                        data-kamar="{{ mb_strtolower($s->kamar ?? '') }}"
                        data-kelas-sekolah="{{ mb_strtolower($s->kelas ?? '') }}"
                        data-kelas-pengajian-id="{{ $kelasPengajianId ?: 'unassigned' }}"
                        data-kelas-pengajian-nama="{{ mb_strtolower($kelasPengajianNama ?? '') }}">

                        <td class="fi-ta-td-check" style="text-align:center;">
                            <input type="checkbox"
                                   class="fi-ta-checkbox js-check-pengajian"
                                   value="{{ $s->id }}"
                                   onchange="onRowSelectionChange('pengajian', {{ $s->id }}, this.checked)">
                        </td>

                        {{-- Nama Santri --}}
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px; height:34px; border-radius:50%; background: {{ $isPutri ? '#FDF2F8' : '#EFF6FF' }}; color: {{ $isPutri ? '#DB2777' : '#0052FF' }}; font-weight:700; font-size:12.5px; display:flex; align-items:center; justify-content:center; shrink:0; border:1px solid {{ $isPutri ? '#FCE7F3' : '#DBEAFE' }};">
                                    {{ mb_strtoupper(mb_substr($s->nama_lengkap, 0, 1)) }}
                                </div>
                                <div style="min-width:0;">
                                    <div style="font-weight:600; color:#111827; font-size:13.5px; line-height:1.3;">
                                        {{ $s->nama_lengkap }}
                                    </div>
                                    <div style="font-size:11.5px; color:#6B7280; margin-top:1px;">
                                        @if($s->pendaftaran?->nama_panggilan)
                                            <span style="color:#0052FF; font-weight:500;">({{ $s->pendaftaran->nama_panggilan }})</span> &bull;
                                        @endif
                                        <span>Jenjang {{ strtoupper($s->jenjang ?? 'Reguler') }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- NISN --}}
                        <td class="pk-col-nisn">
                            <span style="font-family:monospace; font-size:12px; color:#4B5563;">{{ $nisnVal }}</span>
                        </td>

                        {{-- Gender --}}
                        <td class="pk-col-gender" style="text-align:center;">
                            @if($isPutra)
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; background:#EFF6FF; color:#1D4ED8; border:1px solid #BFDBFE;">
                                <span>Putra (L)</span>
                            </span>
                            @elseif($isPutri)
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; background:#FDF2F8; color:#BE185D; border:1px solid #FBCFE8;">
                                <span>Putri (P)</span>
                            </span>
                            @else
                            <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:500; background:#F3F4F6; color:#6B7280;">
                                -
                            </span>
                            @endif
                        </td>

                        {{-- Kamar --}}
                        <td class="pk-col-kamar">
                            @if($s->kamar && $s->kamar !== '-')
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:500; background:#F8FAFC; border:1px solid #E2E8F0; color:#334155;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;color:#64748B;">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                </svg>
                                <span>{{ $s->kamar }}</span>
                            </span>
                            @else
                            <span style="color:#9CA3AF; font-size:12px; font-style:italic;">Belum berkamar</span>
                            @endif
                        </td>

                        {{-- Kelas Sekolah --}}
                        <td class="pk-col-kelas-sekolah">
                            @if($s->kelas && $s->kelas !== '-')
                            <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:600; background:#F0FDF4; border:1px solid #BBF7D0; color:#166534;">
                                {{ $s->kelas }}
                            </span>
                            @else
                            <span style="display:inline-flex; align-items:center; padding:2px 7px; border-radius:6px; font-size:11px; font-weight:500; background:#F3F4F6; color:#6B7280;">
                                Non-formal / Reguler
                            </span>
                            @endif
                        </td>

                        {{-- Kelas Pengajian Saat Ini --}}
                        <td class="pk-col-kelas-pengajian">
                            @if($s->kelasPengajian)
                            <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:7px; font-size:12px; font-weight:600; background:#EFF6FF; border:1px solid #BFDBFE; color:#0052FF;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                </svg>
                                <span>{{ $s->kelasPengajian->nama_kelas }}</span>
                            </span>
                            @else
                            <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:7px; font-size:11.5px; font-weight:600; background:#FFFBEB; border:1px solid #FDE68A; color:#B45309;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <span>Belum Diplot</span>
                            </span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="pk-col-aksi" style="text-align:right; padding-right:16px;">
                            <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:6px;">
                                {{-- Quick Assign Button / Modal --}}
                                <button type="button"
                                        class="fi-ta-act-link fi-ta-act-edit"
                                        title="Ubah Penempatan Kelas Pengajian"
                                        onclick="openSingleAssignModal({{ $s->id }}, '{{ addslashes($s->nama_lengkap) }}', '{{ $s->kelas_pengajian_id ?? '' }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Plot</span>
                                </button>

                                @if($s->kelas_pengajian_id)
                                <button type="button"
                                        class="fi-ta-act-link fi-ta-act-delete"
                                        title="Keluarkan dari Kelas Pengajian"
                                        onclick="confirmSingleUnassign({{ $s->id }}, '{{ addslashes($s->nama_lengkap) }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="js-empty-initial">
                        <td colspan="8" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                            Belum ada data santri aktif di sistem.
                        </td>
                    </tr>
                    @endforelse

                    <tr id="emptyFilterRowPengajian" style="display:none;">
                        <td colspan="8" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                            Tidak ada data santri yang cocok dengan filter atau pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Filament v4 Footer: Info + Per Halaman + Pagination --}}
        <div class="fi-ta-footer" id="footerPengajian">
            <div class="fi-ta-footer-info" id="footerInfoPengajian">Menampilkan 0 data</div>

            <div class="fi-ta-perpage">
                <span class="fi-ta-perpage-label">Per halaman</span>
                <select class="fi-ta-perpage-select" id="perPagePengajian" onchange="changePerPage('pengajian', this.value)">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="9999">Semua</option>
                </select>
            </div>

            <div class="fi-ta-pages" id="pagesPengajian"></div>
        </div>

    </div>

</div>

{{-- ======================================================================= --}}
{{-- MODAL 1: SINGLE PLOTTING KELAS PENGAJIAN                                --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalSingleAssign" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">
            <form action="{{ route('plotting-pengajian.mutasi') }}" method="POST" id="formSingleAssign">
                @csrf
                <input type="hidden" name="siswa_id" id="singleAssignSiswaId">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Plotting Kelas Pengajian</h3>
                        <span class="pk-modal-subtitle" id="singleAssignSiswaNama">-</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeSingleAssignModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="selectSingleKelas" class="pk-form-label">Pilih Kelas Pengajian Tujuan <span style="color:#EF4444;">*</span></label>
                        <select name="kelas_pengajian_id" id="selectSingleKelas" class="pk-form-select" required>
                            <option value="">-- Pilih Kelas Pengajian --</option>
                            @foreach($masterKelasList as $mk)
                                <option value="{{ $mk->id }}">
                                    {{ $mk->nama_kelas }} ({{ strtoupper($mk->jenjang) }} • {{ $mk->siswas_count }} Santri)
                                </option>
                            @endforeach
                            <option value="unassign">-- Kosongkan / Belum Diplot --</option>
                        </select>
                        <span class="pk-form-hint">Pilih kelas pengajian yang sesuai dengan capaian mengaji santri.</span>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeSingleAssignModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Penempatan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 2: TAMBAH KELAS PENGAJIAN BARU                                   --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalTambahKelas" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">
            <form action="{{ route('plotting-pengajian.store-kelas') }}" method="POST" id="formTambahKelas">
                @csrf
                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Kelas Pengajian</h3>
                        <span class="pk-modal-subtitle">Pondok Pesantren Al Muttaqin</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeTambahKelasModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="inputNamaKelas" class="pk-form-label">Nama Kelas Pengajian <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kelas"
                               id="inputNamaKelas"
                               class="pk-form-input"
                               placeholder="Contoh: Kelas Lambatan SMP, Kelas Reguler Sore"
                               required
                               autocomplete="off">
                        <span class="pk-form-hint">Nama kelompok pengajian (taklim/bacaan) yang diikuti santri.</span>
                    </div>

                    <div class="pk-form-row-2">
                        <div class="pk-form-group">
                            <label for="selectJenjangKelas" class="pk-form-label">Jenjang Target <span style="color:#EF4444;">*</span></label>
                            <select name="jenjang" id="selectJenjangKelas" class="pk-form-select" required>
                                <option value="smp">SMP</option>
                                <option value="sma">SMA</option>
                                <option value="reguler">Reguler</option>
                                <option value="umum">Umum</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="inputKodeKelas" class="pk-form-label">Kode Kelas (Opsional)</label>
                            <input type="text"
                                   name="kode_kelas"
                                   id="inputKodeKelas"
                                   class="pk-form-input"
                                   placeholder="Contoh: KLS_LAMBATAN_SMP"
                                   autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeTambahKelasModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Kelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 3: KELOLA MASTER KELAS PENGAJIAN                                  --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalKelolaKelas" style="display:none;">
    <div class="pk-modal-dialog" style="max-width: 680px;">
        <div class="pk-modal-content">
            <div class="pk-modal-header">
                <div>
                    <h3 class="pk-modal-title">Master Kelas Pengajian</h3>
                    <span class="pk-modal-subtitle">Daftar kelas pengajian yang terdaftar</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="pk-btn-add-kelas" onclick="closeKelolaKelasModal(); openTambahKelasModal();" style="font-size:12px; padding:5px 10px;">
                        + Tambah Baru
                    </button>
                    <button type="button" class="pk-modal-close" onclick="closeKelolaKelasModal()">&times;</button>
                </div>
            </div>

            <div class="pk-modal-body" style="padding: 16px;">
                <div class="fi-ta-table-wrap" style="border: 1px solid #E5E7EB; border-radius: 10px;">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">No</th>
                                <th>Nama Kelas</th>
                                <th>Kode</th>
                                <th>Jenjang</th>
                                <th style="text-align: center;">Jumlah Santri</th>
                                <th style="text-align: right; padding-right:14px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($masterKelasList as $idx => $mk)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><strong>{{ $mk->nama_kelas }}</strong></td>
                                <td><span style="font-family:monospace; font-size:11.5px; color:#4B5563;">{{ $mk->kode_kelas }}</span></td>
                                <td><span style="display:inline-flex; padding:2px 7px; border-radius:5px; font-size:11px; font-weight:600; background:#F3F4F6;">{{ strtoupper($mk->jenjang) }}</span></td>
                                <td style="text-align: center;">
                                    <span style="font-weight:700; color: {{ $mk->siswas_count > 0 ? '#0052FF' : '#9CA3AF' }};">
                                        {{ $mk->siswas_count }} Santri
                                    </span>
                                </td>
                                <td style="text-align: right; padding-right:14px;">
                                    <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:6px;">
                                        <button type="button"
                                                class="fi-ta-act-link fi-ta-act-edit"
                                                title="Edit Kelas"
                                                onclick="openEditKelasModal({{ $mk->id }}, '{{ addslashes($mk->nama_kelas) }}', '{{ addslashes($mk->kode_kelas) }}', '{{ $mk->jenjang }}')">
                                            Edit
                                        </button>

                                        @if($mk->siswas_count == 0)
                                        <button type="button"
                                                class="fi-ta-act-link fi-ta-act-delete"
                                                title="Hapus Kelas"
                                                onclick="confirmDeleteKelas({{ $mk->id }}, '{{ addslashes($mk->nama_kelas) }}')">
                                            Hapus
                                        </button>
                                        <form id="formDeleteKelas_{{ $mk->id }}" action="{{ route('plotting-pengajian.destroy-kelas', $mk->id) }}" method="POST" style="display:none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align:center; padding:24px; color:#6B7280;">Belum ada master kelas pengajian.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pk-modal-footer">
                <button type="button" class="pk-btn-clean" onclick="closeKelolaKelasModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 4: EDIT KELAS PENGAJIAN                                           --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalEditKelas" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">
            <form action="" method="POST" id="formEditKelas">
                @csrf
                @method('PUT')
                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Edit Kelas Pengajian</h3>
                        <span class="pk-modal-subtitle" id="editKelasSubtitle">Perbarui data kelas</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeEditKelasModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="editNamaKelas" class="pk-form-label">Nama Kelas Pengajian <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kelas"
                               id="editNamaKelas"
                               class="pk-form-input"
                               required
                               autocomplete="off">
                    </div>

                    <div class="pk-form-row-2">
                        <div class="pk-form-group">
                            <label for="editJenjangKelas" class="pk-form-label">Jenjang Target <span style="color:#EF4444;">*</span></label>
                            <select name="jenjang" id="editJenjangKelas" class="pk-form-select" required>
                                <option value="smp">SMP</option>
                                <option value="sma">SMA</option>
                                <option value="reguler">Reguler</option>
                                <option value="umum">Umum</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="editKodeKelas" class="pk-form-label">Kode Kelas <span style="color:#EF4444;">*</span></label>
                            <input type="text"
                                   name="kode_kelas"
                                   id="editKodeKelas"
                                   class="pk-form-input"
                                   required
                                   autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeEditKelasModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- HIDDEN BULK MUTASI & UNASSIGN FORMS                                     --}}
{{-- ======================================================================= --}}
<form action="{{ route('plotting-pengajian.mutasi') }}" method="POST" id="formBulkMutasi" style="display:none;">
    @csrf
    <input type="hidden" name="siswa_id" id="bulkMutasiSiswaIds">
    <input type="hidden" name="kelas_pengajian_id" id="bulkMutasiKelasId">
</form>

<form action="{{ route('plotting-pengajian.unassign') }}" method="POST" id="formSingleUnassign" style="display:none;">
    @csrf
    <input type="hidden" name="siswa_id" id="singleUnassignSiswaId">
</form>

{{-- ======================================================================= --}}
{{-- CENTERED CONFIRMATION MODAL                                             --}}
{{-- ======================================================================= --}}
<div class="fi-confirm-backdrop" id="fiConfirmModal">
    <div class="fi-confirm-dialog" role="dialog" aria-modal="true">
        <button type="button" class="fi-confirm-close" onclick="closeFiConfirm()" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="fi-confirm-icon is-danger" id="fiConfirmIcon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
        </div>

        <h3 class="fi-confirm-title" id="fiConfirmTitle">Konfirmasi Tindakan</h3>
        <p class="fi-confirm-desc" id="fiConfirmDesc">Apakah Anda yakin ingin melakukan tindakan ini?</p>

        <div class="fi-confirm-actions" id="fiConfirmActions">
            <button type="button" class="fi-confirm-btn-cancel" id="fiConfirmCancelBtn" onclick="closeFiConfirm()">Batal</button>
            <button type="button" class="fi-confirm-btn-submit" id="fiConfirmSubmitBtn">Lanjutkan</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // State Pengajian Table
    var state = {
        selectedIds: new Set(),
        searchQuery: '',
        filterKelas: '',
        filterGender: '',
        filterJenjang: '',
        sortField: 'nama',
        sortAsc: true,
        perPage: 25,
        currentPage: 1,
        columns: {
            nisn: true,
            gender: true,
            kamar: true,
            'kelas-sekolah': true,
            'kelas-pengajian': true,
            aksi: true
        }
    };

    var colLabels = {
        nisn: 'NISN',
        gender: 'Gender',
        kamar: 'Kamar',
        'kelas-sekolah': 'Kelas Sekolah',
        'kelas-pengajian': 'Kelas Pengajian',
        aksi: 'Aksi'
    };

    function getAllRows() {
        return Array.from(document.querySelectorAll('#tbodyPengajian tr.fi-ta-row'));
    }

    function rowMatches(tr) {
        var q = state.searchQuery.toLowerCase();
        if (q) {
            var nama = tr.getAttribute('data-nama') || '';
            var nisn = tr.getAttribute('data-nisn') || '';
            var kamar = tr.getAttribute('data-kamar') || '';
            var ks = tr.getAttribute('data-kelas-sekolah') || '';
            var kp = tr.getAttribute('data-kelas-pengajian-nama') || '';
            if (!nama.includes(q) && !nisn.includes(q) && !kamar.includes(q) && !ks.includes(q) && !kp.includes(q)) {
                return false;
            }
        }

        if (state.filterKelas) {
            var rowKpId = tr.getAttribute('data-kelas-pengajian-id');
            if (state.filterKelas === 'unassigned') {
                if (rowKpId !== 'unassigned') return false;
            } else if (state.filterKelas === 'assigned') {
                if (rowKpId === 'unassigned') return false;
            } else {
                if (String(rowKpId) !== String(state.filterKelas)) return false;
            }
        }

        if (state.filterGender) {
            var rowGender = tr.getAttribute('data-gender');
            if (rowGender !== state.filterGender) return false;
        }

        if (state.filterJenjang) {
            var rowJenjang = tr.getAttribute('data-jenjang');
            if (rowJenjang !== state.filterJenjang) return false;
        }

        return true;
    }

    function getFilteredRows() {
        var rows = getAllRows().filter(rowMatches);

        rows.sort(function(a, b) {
            var valA = a.getAttribute('data-' + state.sortField) || '';
            var valB = b.getAttribute('data-' + state.sortField) || '';
            return state.sortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        });

        return rows;
    }

    function renderTable() {
        var all = getAllRows();
        var filtered = getFilteredRows();
        var emptyRow = document.getElementById('emptyFilterRowPengajian');

        all.forEach(function(r) { r.style.display = 'none'; });

        if (filtered.length === 0) {
            if (emptyRow) emptyRow.style.display = '';
        } else {
            if (emptyRow) emptyRow.style.display = 'none';
        }

        var total = filtered.length;
        var perPage = parseInt(state.perPage, 10);
        var totalPages = Math.ceil(total / perPage) || 1;
        if (state.currentPage > totalPages) state.currentPage = totalPages;
        if (state.currentPage < 1) state.currentPage = 1;

        var start = (state.currentPage - 1) * perPage;
        var end = Math.min(start + perPage, total);
        var pageRows = filtered.slice(start, end);

        pageRows.forEach(function(r) {
            r.style.display = '';
            var id = parseInt(r.getAttribute('data-id'), 10);
            var cb = r.querySelector('.js-check-pengajian');
            if (cb) cb.checked = state.selectedIds.has(id);
            if (state.selectedIds.has(id)) {
                r.classList.add('is-selected');
            } else {
                r.classList.remove('is-selected');
            }
        });

        // Update footer
        var footerInfo = document.getElementById('footerInfoPengajian');
        if (footerInfo) {
            if (total === 0) {
                footerInfo.textContent = 'Menampilkan 0 data';
            } else {
                footerInfo.textContent = 'Menampilkan ' + (start + 1) + ' sampai ' + end + ' dari ' + total + ' santri';
            }
        }

        renderPagination(totalPages);
        updateSelectionUI();
        updateFilterBadge();
    }

    function renderPagination(totalPages) {
        var pagesEl = document.getElementById('pagesPengajian');
        if (!pagesEl) return;
        pagesEl.innerHTML = '';

        if (totalPages <= 1) return;

        // Prev
        var prevBtn = document.createElement('button');
        prevBtn.type = 'button';
        prevBtn.className = 'fi-ta-page-btn' + (state.currentPage === 1 ? ' is-disabled' : '');
        prevBtn.innerHTML = '&lsaquo;';
        prevBtn.onclick = function() {
            if (state.currentPage > 1) {
                state.currentPage--;
                renderTable();
            }
        };
        pagesEl.appendChild(prevBtn);

        // Page numbers
        for (var i = 1; i <= totalPages; i++) {
            if (totalPages > 7) {
                if (i !== 1 && i !== totalPages && Math.abs(i - state.currentPage) > 2) {
                    if (i === 2 || i === totalPages - 1) {
                        var ellipsis = document.createElement('span');
                        ellipsis.className = 'fi-ta-page-dots';
                        ellipsis.textContent = '...';
                        pagesEl.appendChild(ellipsis);
                    }
                    continue;
                }
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'fi-ta-page-btn' + (i === state.currentPage ? ' is-active' : '');
            btn.textContent = i;
            (function(p) {
                btn.onclick = function() {
                    state.currentPage = p;
                    renderTable();
                };
            })(i);
            pagesEl.appendChild(btn);
        }

        // Next
        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'fi-ta-page-btn' + (state.currentPage === totalPages ? ' is-disabled' : '');
        nextBtn.innerHTML = '&rsaquo;';
        nextBtn.onclick = function() {
            if (state.currentPage < totalPages) {
                state.currentPage++;
                renderTable();
            }
        };
        pagesEl.appendChild(nextBtn);
    }

    function updateSelectionUI() {
        var count = state.selectedIds.size;
        var bulkBtn = document.getElementById('btnBulkPengajian');
        var badge = document.getElementById('bulkCountBadgePengajian');
        var selBar = document.getElementById('selBarPengajian');
        var selText = document.getElementById('selCountTextPengajian');

        if (bulkBtn && badge) {
            if (count > 0) {
                bulkBtn.removeAttribute('disabled');
                bulkBtn.classList.add('is-active-selection');
                badge.textContent = count;
                badge.style.display = 'inline-flex';
            } else {
                bulkBtn.setAttribute('disabled', 'disabled');
                bulkBtn.classList.remove('is-active-selection');
                badge.style.display = 'none';
            }
        }

        if (selBar && selText) {
            if (count > 0) {
                selBar.style.display = 'flex';
                selText.textContent = count + ' santri dipilih';
            } else {
                selBar.style.display = 'none';
            }
        }

        // Header checkbox state
        var checkAllPage = document.getElementById('checkAllPagePengajian');
        if (checkAllPage) {
            var visibleRows = getAllRows().filter(function(r) { return r.style.display !== 'none'; });
            if (visibleRows.length > 0) {
                var allChecked = visibleRows.every(function(r) {
                    var id = parseInt(r.getAttribute('data-id'), 10);
                    return state.selectedIds.has(id);
                });
                checkAllPage.checked = allChecked;
            } else {
                checkAllPage.checked = false;
            }
        }
    }

    function updateFilterBadge() {
        var count = 0;
        if (state.filterKelas) count++;
        if (state.filterGender) count++;
        if (state.filterJenjang) count++;

        var badge = document.getElementById('badgeFilterPengajian');
        var btn = document.getElementById('btnFilterPengajian');
        if (badge) {
            badge.textContent = count;
            if (count > 0) {
                badge.classList.add('is-active');
                if (btn) btn.classList.add('is-active');
            } else {
                badge.classList.remove('is-active');
                if (btn) btn.classList.remove('is-active');
            }
        }
    }

    // Exported Window functions
    window.onSearchInput = function(tab) {
        var input = document.getElementById('searchPengajian');
        state.searchQuery = input ? input.value : '';
        state.currentPage = 1;
        renderTable();
    };

    window.clearSearch = function(tab) {
        var input = document.getElementById('searchPengajian');
        if (input) input.value = '';
        state.searchQuery = '';
        state.currentPage = 1;
        renderTable();
    };

    window.applyFilter = function(tab) {
        var fltKelas = document.getElementById('fltPengajianKelas');
        var fltGender = document.getElementById('fltPengajianGender');
        var fltJenjang = document.getElementById('fltPengajianJenjang');

        state.filterKelas = fltKelas ? fltKelas.value : '';
        state.filterGender = fltGender ? fltGender.value : '';
        state.filterJenjang = fltJenjang ? fltJenjang.value : '';
        state.currentPage = 1;

        var pop = document.getElementById('popoverFilterPengajian');
        if (pop) pop.classList.remove('is-open');

        renderTable();
    };

    window.resetFilter = function(tab) {
        var fltKelas = document.getElementById('fltPengajianKelas');
        var fltGender = document.getElementById('fltPengajianGender');
        var fltJenjang = document.getElementById('fltPengajianJenjang');

        if (fltKelas) fltKelas.value = '';
        if (fltGender) fltGender.value = '';
        if (fltJenjang) fltJenjang.value = '';

        state.filterKelas = '';
        state.filterGender = '';
        state.filterJenjang = '';
        state.currentPage = 1;

        var pop = document.getElementById('popoverFilterPengajian');
        if (pop) pop.classList.remove('is-open');

        renderTable();
    };

    window.onRowSelectionChange = function(tab, id, checked) {
        if (checked) {
            state.selectedIds.add(id);
        } else {
            state.selectedIds.delete(id);
        }
        updateSelectionUI();
    };

    window.togglePageSelection = function(tab, checked) {
        var visibleRows = getAllRows().filter(function(r) { return r.style.display !== 'none'; });
        visibleRows.forEach(function(r) {
            var id = parseInt(r.getAttribute('data-id'), 10);
            var cb = r.querySelector('.js-check-pengajian');
            if (cb) cb.checked = checked;
            if (checked) {
                state.selectedIds.add(id);
                r.classList.add('is-selected');
            } else {
                state.selectedIds.delete(id);
                r.classList.remove('is-selected');
            }
        });
        updateSelectionUI();
    };

    window.selectAllMatching = function(tab) {
        var filtered = getFilteredRows();
        filtered.forEach(function(r) {
            var id = parseInt(r.getAttribute('data-id'), 10);
            state.selectedIds.add(id);
        });
        renderTable();
    };

    window.clearSelection = function(tab) {
        state.selectedIds.clear();
        renderTable();
        closeAllDropdowns();
    };

    window.changePerPage = function(tab, val) {
        state.perPage = parseInt(val, 10);
        state.currentPage = 1;
        renderTable();
    };

    window.sortTableBy = function(tab, field) {
        if (state.sortField === field) {
            state.sortAsc = !state.sortAsc;
        } else {
            state.sortField = field;
            state.sortAsc = true;
        }
        renderTable();
    };

    window.focusBulkTargetSelect = function() {
        closeAllDropdowns();
        var sel = document.getElementById('bulkTargetKelas');
        if (sel) {
            sel.focus();
            sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    window.applyBulkTargetPengajian = function() {
        if (state.selectedIds.size === 0) {
            alert('Silakan pilih minimal 1 santri terlebih dahulu.');
            return;
        }

        var sel = document.getElementById('bulkTargetKelas');
        var targetVal = sel ? sel.value : '';
        if (!targetVal) {
            alert('Silakan pilih kelas pengajian tujuan.');
            return;
        }

        var targetText = sel.options[sel.selectedIndex].text;
        var count = state.selectedIds.size;

        var isUnassign = targetVal === 'unassign';
        var desc = isUnassign
            ? 'Apakah Anda yakin ingin mengeluarkan ' + count + ' santri terpilih dari kelas pengajian mereka?'
            : 'Apakah Anda yakin ingin menetapkan ' + count + ' santri terpilih ke ' + targetText + '?';

        openFiConfirm({
            variant: isUnassign ? 'danger' : 'primary',
            title: isUnassign ? 'Konfirmasi Unassign Santri' : 'Konfirmasi Plotting Massal',
            desc: desc,
            confirmText: isUnassign ? 'Keluarkan' : 'Terapkan',
            onConfirm: function() {
                var form = document.getElementById('formBulkMutasi');
                document.getElementById('bulkMutasiSiswaIds').value = Array.from(state.selectedIds).join(',');
                document.getElementById('bulkMutasiKelasId').value = targetVal;
                form.submit();
            }
        });
    };

    window.confirmBulkUnassignPengajian = function() {
        if (state.selectedIds.size === 0) return;
        var count = state.selectedIds.size;
        openFiConfirm({
            variant: 'danger',
            title: 'Keluarkan dari Kelas Pengajian',
            desc: 'Apakah Anda yakin ingin mengeluarkan ' + count + ' santri terpilih dari kelas pengajian?',
            confirmText: 'Keluarkan',
            onConfirm: function() {
                var form = document.getElementById('formBulkMutasi');
                document.getElementById('bulkMutasiSiswaIds').value = Array.from(state.selectedIds).join(',');
                document.getElementById('bulkMutasiKelasId').value = 'unassign';
                form.submit();
            }
        });
    };

    window.openSingleAssignModal = function(id, nama, currentKelasId) {
        document.getElementById('singleAssignSiswaId').value = id;
        document.getElementById('singleAssignSiswaNama').textContent = nama;
        var sel = document.getElementById('selectSingleKelas');
        if (sel) {
            sel.value = currentKelasId || '';
        }
        document.getElementById('modalSingleAssign').style.display = 'flex';
    };

    window.closeSingleAssignModal = function() {
        document.getElementById('modalSingleAssign').style.display = 'none';
    };

    window.confirmSingleUnassign = function(id, nama) {
        openFiConfirm({
            variant: 'danger',
            title: 'Keluarkan dari Kelas Pengajian',
            desc: 'Apakah Anda yakin ingin mengeluarkan ' + nama + ' dari kelas pengajian?',
            confirmText: 'Keluarkan',
            onConfirm: function() {
                var form = document.getElementById('formSingleUnassign');
                document.getElementById('singleUnassignSiswaId').value = id;
                form.submit();
            }
        });
    };

    window.openTambahKelasModal = function() {
        document.getElementById('modalTambahKelas').style.display = 'flex';
    };

    window.closeTambahKelasModal = function() {
        document.getElementById('modalTambahKelas').style.display = 'none';
    };

    window.openKelolaKelasModal = function() {
        document.getElementById('modalKelolaKelas').style.display = 'flex';
    };

    window.closeKelolaKelasModal = function() {
        document.getElementById('modalKelolaKelas').style.display = 'none';
    };

    window.openEditKelasModal = function(id, nama, kode, jenjang) {
        var form = document.getElementById('formEditKelas');
        form.action = '/plotting-pengajian/kelas/' + id;
        document.getElementById('editNamaKelas').value = nama;
        document.getElementById('editKodeKelas').value = kode;
        document.getElementById('editJenjangKelas').value = jenjang;
        document.getElementById('editKelasSubtitle').textContent = nama;
        document.getElementById('modalEditKelas').style.display = 'flex';
    };

    window.closeEditKelasModal = function() {
        document.getElementById('modalEditKelas').style.display = 'none';
    };

    window.confirmDeleteKelas = function(id, nama) {
        openFiConfirm({
            variant: 'danger',
            title: 'Hapus Kelas Pengajian',
            desc: 'Apakah Anda yakin ingin menghapus master kelas "' + nama + '"? Tindakan ini tidak dapat dibatalkan.',
            confirmText: 'Hapus',
            onConfirm: function() {
                var f = document.getElementById('formDeleteKelas_' + id);
                if (f) f.submit();
            }
        });
    };

    // Global Dropdown helper
    function closeAllDropdowns() {
        document.querySelectorAll('.fi-ta-bulk-menu, .fi-ta-filter-popover, .fi-ta-col-popover').forEach(function(el) {
            el.classList.remove('is-open');
        });
    }

    window.toggleDropdownMenu = function(id, e) {
        if (e) e.stopPropagation();
        var target = document.getElementById(id);
        if (!target) return;
        var isOpen = target.classList.contains('is-open');
        closeAllDropdowns();
        if (!isOpen) {
            target.classList.add('is-open');
        }
    };

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.fi-ta-bulk-wrap, .fi-ta-filter-wrap, .fi-ta-col-wrap')) {
            closeAllDropdowns();
        }
    });

    // Column customizer generator
    function initColCustomizer() {
        var list = document.getElementById('colListPengajian');
        if (!list) return;
        list.innerHTML = '';

        Object.keys(state.columns).forEach(function(colKey) {
            var label = document.createElement('label');
            label.className = 'fi-ta-col-item';

            var chk = document.createElement('input');
            chk.type = 'checkbox';
            chk.className = 'fi-ta-checkbox';
            chk.checked = state.columns[colKey];
            chk.onchange = function() {
                state.columns[colKey] = this.checked;
                applyColVisibility();
            };

            var span = document.createElement('span');
            span.textContent = colLabels[colKey] || colKey;

            label.appendChild(chk);
            label.appendChild(span);
            list.appendChild(label);
        });
    }

    function applyColVisibility() {
        Object.keys(state.columns).forEach(function(colKey) {
            var isVis = state.columns[colKey];
            var ths = document.querySelectorAll('#tablePengajian th.pk-col-' + colKey);
            var tds = document.querySelectorAll('#tablePengajian td.pk-col-' + colKey);
            ths.forEach(function(el) { el.style.display = isVis ? '' : 'none'; });
            tds.forEach(function(el) { el.style.display = isVis ? '' : 'none'; });
        });
    }

    window.resetColumns = function(tab) {
        Object.keys(state.columns).forEach(function(k) { state.columns[k] = true; });
        initColCustomizer();
        applyColVisibility();
    };

    // Init on load
    document.addEventListener('DOMContentLoaded', function() {
        initColCustomizer();
        renderTable();
    });

})();
</script>
@endpush
