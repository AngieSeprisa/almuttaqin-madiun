@extends('layouts.app')

@section('title', 'Plotting Ekstrakurikuler')

@section('header-actions')
<div class="pk-header-actions-wrap">
    {{-- Tombol Tambah Ekskul --}}
    <button type="button" class="pk-btn-add-kelas" onclick="openTambahEkskulModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Ekskul</span>
    </button>

    {{-- Switcher Jenjang untuk Yayasan --}}
    @if($isYayasan && $sekolahLembagas->count() > 1)
    <div class="yayasan-switch-wrap">
        @foreach($sekolahLembagas as $lem)
            <a href="{{ route('plotting-ekskul.index', ['lembaga' => strtolower($lem->kode), 'tab' => $activeTab]) }}"
               class="yayasan-tab-btn {{ $kodeLembaga === strtolower($lem->kode) ? 'active' : '' }}">
                {{ strtoupper($lem->singkatan ?: $lem->kode) }}
            </a>
        @endforeach
    </div>
    @endif
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

    {{-- Segmented Tabs Navigation --}}
    <div class="pk-topbar">
        <div class="pk-tabs-nav" role="tablist">
            <button type="button"
                    class="pk-tab-item {{ $activeTab === 'ekskul' ? 'active' : '' }}"
                    id="tabBtnEkskul"
                    onclick="switchTab('ekskul')">
                <span>Daftar Ekstrakurikuler</span>
                <span class="pk-tab-badge">{{ $ekskuls->count() }}</span>
            </button>
            <button type="button"
                    class="pk-tab-item {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}"
                    id="tabBtnBelum"
                    onclick="switchTab('belum_terdaftar')">
                <span>Belum Ikut Ekskul</span>
                <span class="pk-tab-badge {{ $belumTerdaftar->count() > 0 ? 'pk-badge-notice' : '' }}">
                    {{ $belumTerdaftar->count() }}
                </span>
            </button>
        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- TAB 1: DAFTAR EKSTRAKURIKULER (FILAMENT V4 TABLE CARD)                 --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'ekskul' ? 'active' : '' }}" id="paneEkskul">
        <div class="fi-ta-ctn" id="fiCardEkskul">

            {{-- Toolbar: [ ⋮ Aksi ] on Left | [ 🔍 Cari ] + [ Filter ] + [ Kolom ] on Right --}}
            <div class="fi-ta-header-toolbar">
                <div class="fi-ta-toolbar-left">
                    <div class="fi-ta-bulk-wrap" id="bulkWrapEkskul">
                        <button type="button"
                                class="fi-ta-bulk-btn"
                                id="btnBulkEkskul"
                                disabled
                                onclick="toggleDropdownMenu('menuBulkEkskul', event)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                            </svg>
                            <span>Aksi</span>
                            <span class="fi-ta-bulk-count" id="bulkCountBadgeEkskul" style="display:none;">0</span>
                        </button>

                        <div class="fi-ta-bulk-menu" id="menuBulkEkskul">
                            <button type="button" class="fi-ta-bulk-item is-danger" onclick="confirmBulkDeleteEkskul()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                <span>Hapus terpilih</span>
                            </button>
                            <div class="fi-ta-bulk-divider"></div>
                            <button type="button" class="fi-ta-bulk-item" onclick="clearSelection('ekskul')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                               id="searchEkskul"
                               class="fi-ta-search-input"
                               placeholder="Cari ekstrakurikuler atau pembina..."
                               oninput="onSearchInput('ekskul')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchEkskul" onclick="clearSearch('ekskul')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapEkskul">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterEkskul"
                                title="Filter Ekstrakurikuler"
                                onclick="toggleDropdownMenu('popoverFilterEkskul', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                            </svg>
                            <span class="fi-ta-filter-badge" id="badgeFilterEkskul">0</span>
                        </button>

                        <div class="fi-ta-filter-popover" id="popoverFilterEkskul" onclick="event.stopPropagation()">
                            <div class="fi-ta-filter-head">
                                <span class="fi-ta-filter-title">Filter</span>
                                <button type="button" class="fi-ta-filter-reset" onclick="resetFilter('ekskul')">Reset</button>
                            </div>
                            <div class="fi-ta-filter-body">
                                <label class="fi-ta-filter-check-row">
                                    <input type="checkbox" id="fltEkskulTerisi" class="fi-ta-checkbox">
                                    <span>Hanya ekskul yang memiliki anggota</span>
                                </label>

                                <div class="fi-ta-filter-field">
                                    <label for="fltEkskulHari" class="fi-ta-filter-label">Hari Latihan</label>
                                    <select id="fltEkskulHari" class="fi-ta-filter-select">
                                        <option value="">Semua Hari</option>
                                        <option value="Senin">Senin</option>
                                        <option value="Selasa">Selasa</option>
                                        <option value="Rabu">Rabu</option>
                                        <option value="Kamis">Kamis</option>
                                        <option value="Jumat">Jumat</option>
                                        <option value="Sabtu">Sabtu</option>
                                        <option value="Minggu">Minggu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fi-ta-filter-actions">
                                <button type="button" class="fi-ta-filter-apply" onclick="applyFilter('ekskul')">
                                    Terapkan filter
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Kolom Toggle Button --}}
                    <div class="fi-ta-col-wrap" id="colWrapEkskul">
                        <button type="button"
                                class="fi-ta-col-btn"
                                id="btnColsEkskul"
                                title="Sesuaikan Kolom"
                                onclick="toggleDropdownMenu('popoverColsEkskul', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <rect x="3" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="8.4" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="13.8" y="3" width="3.2" height="14" rx="1.6" />
                            </svg>
                        </button>

                        <div class="fi-ta-col-popover" id="popoverColsEkskul" onclick="event.stopPropagation()">
                            <div class="fi-ta-col-head">
                                <span class="fi-ta-col-title">Kolom</span>
                                <button type="button" class="fi-ta-col-reset" onclick="resetColumns('ekskul')">Reset</button>
                            </div>
                            <div class="fi-ta-col-list" id="colListEkskul"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selection Strip --}}
            <div class="fi-ta-selection-bar" id="selBarEkskul">
                <span class="fi-ta-selection-count" id="selCountTextEkskul">0 ekskul dipilih</span>
                <button type="button" class="fi-ta-selection-clear-btn" onclick="clearSelection('ekskul')">Batal</button>
            </div>

            {{-- Table Element --}}
            <div class="fi-ta-table-scroll">
                <table class="fi-ta-table" id="tableEkskul">
                    <thead>
                        <tr>
                            <th class="fi-ta-th-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox"
                                       id="masterCheckEkskul"
                                       onclick="toggleMasterCheck('ekskul', this.checked)">
                            </th>
                            <th class="fi-ta-th fi-ta-th-sortable" onclick="sortTable('ekskul', 1)">
                                <span>Ekstrakurikuler</span>
                                <span class="fi-ta-sort-icon">↕</span>
                            </th>
                            <th class="fi-ta-th">
                                <span>Pembina</span>
                            </th>
                            <th class="fi-ta-th">
                                <span>Jadwal & Tempat</span>
                            </th>
                            <th class="fi-ta-th fi-ta-th-center fi-ta-th-sortable" onclick="sortTable('ekskul', 4)">
                                <span>Anggota</span>
                                <span class="fi-ta-sort-icon">↕</span>
                            </th>
                            <th class="fi-ta-th fi-ta-th-right" style="width: 140px;">
                                <span>Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEkskul">
                        @forelse($ekskuls as $item)
                        <tr class="fi-ta-row js-row-ekskul"
                            data-id="{{ $item->id }}"
                            data-nama="{{ strtolower($item->nama_ekskul) }}"
                            data-kode="{{ strtolower($item->kode ?? '') }}"
                            data-pembina="{{ strtolower($item->nama_pembina ?? '') }}"
                            data-hari="{{ $item->hari ?? '' }}"
                            data-tempat="{{ strtolower($item->tempat ?? '') }}"
                            data-count="{{ $item->anggota_count }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox row-check-ekskul"
                                       value="{{ $item->id }}"
                                       onchange="onRowCheckChange('ekskul')">
                            </td>
                            <td class="fi-ta-td">
                                <div class="pk-cell-kelas">
                                    <div class="pk-kelas-main">
                                        <span class="pk-kelas-name">{{ $item->nama_ekskul }}</span>
                                        @if($item->kode)
                                            <span class="pk-badge-kode">{{ $item->kode }}</span>
                                        @endif
                                    </div>
                                    <div class="pk-kelas-sub">
                                        <span class="pk-badge-jenjang {{ strtolower($item->jenjang ?? $jenjang) }}">
                                            {{ $item->jenjang === 'ALL' ? 'SMP & SMA' : ($item->jenjang ?? $jenjang) }}
                                        </span>
                                        @if($item->deskripsi)
                                            <span class="pk-text-muted" title="{{ $item->deskripsi }}">
                                                &bull; {{ \Illuminate\Support\Str::limit($item->deskripsi, 35) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-td">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:28px; height:28px; border-radius:50%; background:#EFF6FF; color:#0052FF; font-weight:700; font-size:11px; display:flex; align-items:center; justify-content:center;">
                                        {{ strtoupper(substr($item->nama_pembina ?: 'P', 0, 2)) }}
                                    </div>
                                    <span style="font-weight:600; font-size:13px; color:var(--text-primary, #0F172A);">
                                        {{ $item->nama_pembina ?: '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="fi-ta-td">
                                <div style="display:flex; flex-direction:column; gap:2px; font-size:12.5px;">
                                    <div style="font-weight:600; color:var(--text-primary, #0F172A); display:flex; align-items:center; gap:5px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13" style="color:#0052FF;">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span>{{ $item->hari ?: 'Belum diatur' }}</span>
                                        @if($item->jam_mulai && $item->jam_selesai)
                                            <span style="color:#6B7280; font-weight:500;">({{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }})</span>
                                        @endif
                                    </div>
                                    @if($item->tempat)
                                    <div style="color:#6B7280; font-size:11.5px; display:flex; align-items:center; gap:4px;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        <span>{{ $item->tempat }}</span>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="fi-ta-td fi-ta-td-center">
                                <button type="button"
                                        class="pk-badge-count-btn"
                                        onclick="openAnggotaModal({{ $item->id }}, '{{ addslashes($item->nama_ekskul) }}')"
                                        title="Klik untuk melihat dan kelola siswa anggota">
                                    <span class="pk-badge-count-num">{{ $item->anggota_count }}</span>
                                    <span class="pk-badge-count-label">Siswa</span>
                                </button>
                            </td>
                            <td class="fi-ta-td fi-ta-td-right">
                                <div class="pk-row-actions">
                                    {{-- Lihat/Kelola Anggota --}}
                                    <button type="button"
                                            class="pk-action-btn is-view"
                                            onclick="openAnggotaModal({{ $item->id }}, '{{ addslashes($item->nama_ekskul) }}')"
                                            title="Kelola Anggota">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </button>

                                    {{-- Edit Ekskul --}}
                                    <button type="button"
                                            class="pk-action-btn is-edit"
                                            onclick="openEditEkskulModal({
                                                id: {{ $item->id }},
                                                nama_ekskul: '{{ addslashes($item->nama_ekskul) }}',
                                                kode: '{{ addslashes($item->kode ?? '') }}',
                                                pembina_id: '{{ $item->pembina_id ?? '' }}',
                                                nama_pembina: '{{ addslashes($item->nama_pembina ?? '') }}',
                                                hari: '{{ addslashes($item->hari ?? '') }}',
                                                jam_mulai: '{{ $item->jam_mulai ? substr($item->jam_mulai, 0, 5) : '' }}',
                                                jam_selesai: '{{ $item->jam_selesai ? substr($item->jam_selesai, 0, 5) : '' }}',
                                                tempat: '{{ addslashes($item->tempat ?? '') }}',
                                                deskripsi: '{{ addslashes($item->deskripsi ?? '') }}'
                                            })"
                                            title="Edit Ekstrakurikuler">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    {{-- Hapus Ekskul --}}
                                    <button type="button"
                                            class="pk-action-btn is-delete"
                                            onclick="confirmDeleteSingleEkskul({{ $item->id }}, '{{ addslashes($item->nama_ekskul) }}')"
                                            title="Hapus Ekstrakurikuler">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="6" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                Belum ada data ekstrakurikuler untuk jenjang {{ $jenjang }}. Klik tombol "Tambah Ekskul" di atas.
                            </td>
                        </tr>
                        @endforelse
                        <tr id="emptyFilterRowEkskul" style="display:none;">
                            <td colspan="6" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                Tidak ada ekstrakurikuler yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Filament v4 Footer --}}
            <div class="fi-ta-footer" id="footerEkskul">
                <div class="fi-ta-footer-info" id="footerInfoEkskul">Menampilkan 0 data</div>

                <div class="fi-ta-perpage">
                    <span class="fi-ta-perpage-label">Per halaman</span>
                    <select class="fi-ta-perpage-select" id="perPageEkskul" onchange="changePerPage('ekskul', this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="9999">Semua</option>
                    </select>
                </div>

                <div class="fi-ta-pages" id="pagesEkskul"></div>
            </div>

        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- TAB 2: SISWA BELUM IKUT EKSKUL (FILAMENT V4 TABLE CARD)                --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">
        <div class="fi-ta-ctn" id="fiCardBelum">

            <div class="fi-ta-header-toolbar">
                <div class="fi-ta-toolbar-left">
                    <div class="fi-ta-bulk-wrap" id="bulkWrapBelum">
                        <button type="button"
                                class="fi-ta-bulk-btn"
                                id="btnBulkBelum"
                                disabled
                                onclick="toggleDropdownMenu('menuBulkBelum', event)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                            </svg>
                            <span>Aksi</span>
                            <span class="fi-ta-bulk-count" id="bulkCountBadgeBelum" style="display:none;">0</span>
                        </button>

                        <div class="fi-ta-bulk-menu" id="menuBulkBelum">
                            <button type="button" class="fi-ta-bulk-item is-primary" onclick="openAssignBulkModal()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                <span>Masukkan ke Ekskul...</span>
                            </button>
                            <div class="fi-ta-bulk-divider"></div>
                            <button type="button" class="fi-ta-bulk-item" onclick="clearSelection('belum')">
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
                    <div class="fi-ta-search-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fi-ta-search-icon">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text"
                               id="searchBelum"
                               class="fi-ta-search-input"
                               placeholder="Cari siswa atau NISN..."
                               oninput="onSearchInput('belum')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchBelum" onclick="clearSearch('belum')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapBelum">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterBelum"
                                title="Filter Siswa"
                                onclick="toggleDropdownMenu('popoverFilterBelum', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                            </svg>
                            <span class="fi-ta-filter-badge" id="badgeFilterBelum">0</span>
                        </button>

                        <div class="fi-ta-filter-popover" id="popoverFilterBelum" onclick="event.stopPropagation()">
                            <div class="fi-ta-filter-head">
                                <span class="fi-ta-filter-title">Filter</span>
                                <button type="button" class="fi-ta-filter-reset" onclick="resetFilter('belum')">Reset</button>
                            </div>
                            <div class="fi-ta-filter-body">
                                <div class="fi-ta-filter-field">
                                    <label for="fltBelumKelas" class="fi-ta-filter-label">Filter Kelas Formal</label>
                                    <select id="fltBelumKelas" class="fi-ta-filter-select">
                                        <option value="">Semua Kelas</option>
                                        @php
                                            $uniqueKelas = $belumTerdaftar->pluck('kelas')->filter()->unique()->sort()->values();
                                        @endphp
                                        @foreach($uniqueKelas as $uk)
                                            <option value="{{ $uk }}">Kelas {{ $uk }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="fi-ta-filter-actions">
                                <button type="button" class="fi-ta-filter-apply" onclick="applyFilter('belum')">
                                    Terapkan filter
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Kolom Toggle Button --}}
                    <div class="fi-ta-col-wrap" id="colWrapBelum">
                        <button type="button"
                                class="fi-ta-col-btn"
                                id="btnColsBelum"
                                title="Sesuaikan Kolom"
                                onclick="toggleDropdownMenu('popoverColsBelum', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <rect x="3" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="8.4" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="13.8" y="3" width="3.2" height="14" rx="1.6" />
                            </svg>
                        </button>

                        <div class="fi-ta-col-popover" id="popoverColsBelum" onclick="event.stopPropagation()">
                            <div class="fi-ta-col-head">
                                <span class="fi-ta-col-title">Kolom</span>
                                <button type="button" class="fi-ta-col-reset" onclick="resetColumns('belum')">Reset</button>
                            </div>
                            <div class="fi-ta-col-list" id="colListBelum"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fi-ta-selection-bar" id="selBarBelum">
                <span class="fi-ta-selection-count" id="selCountTextBelum">0 siswa dipilih</span>
                <button type="button" class="fi-ta-selection-clear-btn" onclick="clearSelection('belum')">Batal</button>
            </div>

            <div class="fi-ta-table-scroll">
                <table class="fi-ta-table" id="tableBelum">
                    <thead>
                        <tr>
                            <th class="fi-ta-th-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox"
                                       id="masterCheckBelum"
                                       onclick="toggleMasterCheck('belum', this.checked)">
                            </th>
                            <th class="fi-ta-th fi-ta-th-sortable" onclick="sortTable('belum', 1)">
                                <span>Nama Siswa</span>
                                <span class="fi-ta-sort-icon">↕</span>
                            </th>
                            <th class="fi-ta-th">
                                <span>Kelas Formal</span>
                            </th>
                            <th class="fi-ta-th fi-ta-th-center">
                                <span>Status</span>
                            </th>
                            <th class="fi-ta-th fi-ta-th-right" style="width: 140px;">
                                <span>Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBelum">
                        @forelse($belumTerdaftar as $siswa)
                        <tr class="fi-ta-row js-row-belum"
                            data-id="{{ $siswa->id }}"
                            data-nama="{{ strtolower($siswa->nama_lengkap) }}"
                            data-nisn="{{ strtolower($siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '') }}"
                            data-kelas="{{ $siswa->kelas ?? '' }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox row-check-belum"
                                       value="{{ $siswa->id }}"
                                       onchange="onRowCheckChange('belum')">
                            </td>
                            <td class="fi-ta-td">
                                <div class="pk-cell-siswa">
                                    <div class="pk-siswa-avatar">
                                        {{ strtoupper(substr($siswa->nama_lengkap, 0, 2)) }}
                                    </div>
                                    <div class="pk-siswa-info">
                                        <span class="pk-siswa-name">{{ $siswa->nama_lengkap }}</span>
                                        <span class="pk-siswa-nisn">
                                            NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-td">
                                <span class="pk-badge-jenjang {{ strtolower($jenjang) }}">
                                    Kelas {{ $siswa->kelas ?: '-' }} ({{ $jenjang }})
                                </span>
                            </td>
                            <td class="fi-ta-td fi-ta-td-center">
                                @php
                                    $st = strtolower($siswa->status ?? 'aktif');
                                @endphp
                                @if($st === 'aktif')
                                    <span class="pk-badge-status is-aktif">Aktif</span>
                                @elseif($st === 'sakit')
                                    <span class="pk-badge-status is-sakit">Sakit</span>
                                @elseif($st === 'pulang')
                                    <span class="pk-badge-status is-pulang">Pulang</span>
                                @else
                                    <span class="pk-badge-status is-aktif">{{ ucfirst($st) }}</span>
                                @endif
                            </td>
                            <td class="fi-ta-td fi-ta-td-right">
                                <button type="button"
                                        class="pk-btn-mutasi-row"
                                        onclick="openAssignSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')"
                                        title="Masukkan ke Ekstrakurikuler">
                                    <span>Pilih Ekskul</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="5" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                Seluruh siswa jenjang {{ $jenjang }} telah mengikuti kegiatan ekstrakurikuler.
                            </td>
                        </tr>
                        @endforelse
                        <tr id="emptyFilterRowBelum" style="display:none;">
                            <td colspan="5" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                Tidak ada data siswa yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="fi-ta-footer" id="footerBelum">
                <div class="fi-ta-footer-info" id="footerInfoBelum">Menampilkan 0 data</div>

                <div class="fi-ta-perpage">
                    <span class="fi-ta-perpage-label">Per halaman</span>
                    <select class="fi-ta-perpage-select" id="perPageBelum" onchange="changePerPage('belum', this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="9999">Semua</option>
                    </select>
                </div>

                <div class="fi-ta-pages" id="pagesBelum"></div>
            </div>

        </div>
    </div>

</div>

{{-- FORM HIDDEN BULK DELETE --}}
<form action="{{ route('plotting-ekskul.destroy-bulk-ekskul') }}" method="POST" id="formBulkDeleteEkskul" style="display:none;">
    @csrf
    <input type="hidden" name="ekskul_ids" id="bulkDeleteEkskulIds">
</form>

<form action="" method="POST" id="formDeleteSingleEkskul" style="display:none;">
    @csrf
    @method('DELETE')
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
            <button type="button" class="fi-confirm-btn-submit" id="fiConfirmSubmitBtn">Hapus</button>
        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 1: LIHAT & KELOLA ANGGOTA EKSKUL                                   --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalAnggota" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <div class="pk-modal-header">
                <div>
                    <h3 class="pk-modal-title" id="modalAnggotaTitle">Anggota Ekstrakurikuler</h3>
                    <span class="pk-modal-subtitle" id="modalAnggotaSubtitle">Daftar siswa terdaftar</span>
                </div>
                <button type="button" class="pk-modal-close" onclick="closeAnggotaModal()">&times;</button>
            </div>

            <div class="pk-modal-body" style="padding: 16px 20px;">
                <div class="fi-ta-ctn" style="box-shadow:none;">
                    <div class="fi-ta-header-toolbar" style="padding: 10px 12px;">
                        <div class="fi-ta-toolbar-left">
                            <div class="fi-ta-bulk-wrap">
                                <button type="button"
                                        class="fi-ta-bulk-btn"
                                        id="btnBulkModal"
                                        disabled
                                        onclick="toggleDropdownMenu('menuBulkModal', event)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                                        <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                        <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                                    </svg>
                                    <span>Aksi</span>
                                    <span class="fi-ta-bulk-count" id="bulkCountModal" style="display:none;">0</span>
                                </button>
                                <div class="fi-ta-bulk-menu" id="menuBulkModal">
                                    <button type="button" class="fi-ta-bulk-item is-danger" onclick="keluarkanBulkModal()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        <span>Keluarkan dari Ekskul</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="fi-ta-toolbar-right">
                            {{-- Tombol Tambah Siswa Baru ke Ekskul ini --}}
                            <button type="button" class="pk-btn-secondary" style="padding: 5px 12px; font-size: 12px;" onclick="openTambahAnggotaModal()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Tambah Siswa</span>
                            </button>

                            <div class="fi-ta-search-wrap" style="width: 200px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="fi-ta-search-icon">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text"
                                       id="modalAnggotaSearch"
                                       class="fi-ta-search-input"
                                       placeholder="Cari siswa..."
                                       oninput="filterModalAnggota(this.value)">
                            </div>
                        </div>
                    </div>

                    <div class="fi-ta-table-scroll" style="max-height: 380px;">
                        <table class="fi-ta-table" id="tableModalAnggota">
                            <thead>
                                <tr>
                                    <th class="fi-ta-th-check">
                                        <input type="checkbox"
                                               class="fi-ta-checkbox"
                                               id="masterCheckModal"
                                               onclick="toggleMasterCheckModal(this.checked)">
                                    </th>
                                    <th class="fi-ta-th">Nama Siswa</th>
                                    <th class="fi-ta-th">Kelas</th>
                                    <th class="fi-ta-th">Peran</th>
                                    <th class="fi-ta-th fi-ta-th-right" style="width: 80px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyModalAnggota">
                                <tr>
                                    <td colspan="5" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                        Memuat data siswa...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="fi-ta-footer" style="padding: 10px 14px;">
                        <div class="fi-ta-footer-info" id="footerInfoModal">Total 0 anggota</div>
                    </div>
                </div>
            </div>

            <div class="pk-modal-footer">
                <button type="button" class="pk-btn-clean" onclick="closeAnggotaModal()">Tutup</button>
            </div>

        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 2: ASSIGN SISWA KE EKSKUL (SINGLE / BULK)                         --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalAssignEkskul" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-ekskul.tambah-anggota') }}" method="POST" id="formAssignEkskul">
                @csrf
                <input type="hidden" name="siswa_id" id="assignSiswaIds">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title" id="assignModalTitle">Pilih Ekstrakurikuler</h3>
                        <span class="pk-modal-subtitle" id="assignModalSubtitle">Masukkan siswa ke kegiatan ekstrakurikuler</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeAssignModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="selectEkskulTarget" class="pk-form-label">Pilih Ekstrakurikuler <span style="color:#EF4444;">*</span></label>
                        <select name="ekstrakurikuler_id" id="selectEkskulTarget" class="pk-form-select" required>
                            <option value="">-- Pilih Ekstrakurikuler --</option>
                            @foreach($ekskuls as $ek)
                                <option value="{{ $ek->id }}">{{ $ek->nama_ekskul }} ({{ $ek->hari ?: 'Jadwal belum diatur' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pk-form-group">
                        <label for="selectPeranSiswa" class="pk-form-label">Peran dalam Ekskul</label>
                        <select name="peran" id="selectPeranSiswa" class="pk-form-select">
                            <option value="anggota" selected>Anggota</option>
                            <option value="ketua">Ketua</option>
                            <option value="wakil">Wakil Ketua</option>
                            <option value="sekretaris">Sekretaris</option>
                            <option value="bendahara">Bendahara</option>
                        </select>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeAssignModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Masukkan ke Ekskul</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 3: TAMBAH EKSTRAKURIKULER BARU                                    --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalTambahEkskul" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-ekskul.store-ekskul') }}" method="POST" id="formTambahEkskul">
                @csrf
                <input type="hidden" name="lembaga_id" value="{{ $selectedLembaga->id }}">
                <input type="hidden" name="jenjang" value="{{ $jenjang }}">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Ekstrakurikuler</h3>
                        <span class="pk-modal-subtitle">Daftarkan kegiatan ekstrakurikuler baru untuk {{ $jenjang }}</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeTambahEkskulModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:14px;">
                        <div class="pk-form-group">
                            <label for="inputNamaEkskul" class="pk-form-label">Nama Ekstrakurikuler <span style="color:#EF4444;">*</span></label>
                            <input type="text"
                                   name="nama_ekskul"
                                   id="inputNamaEkskul"
                                   class="pk-form-input"
                                   placeholder="Contoh: Futsal, Pramuka, Hadroh / Banjari"
                                   required
                                   autocomplete="off">
                        </div>

                        <div class="pk-form-group">
                            <label for="inputKodeEkskul" class="pk-form-label">Kode (Opsional)</label>
                            <input type="text"
                                   name="kode"
                                   id="inputKodeEkskul"
                                   class="pk-form-input"
                                   placeholder="Contoh: FTSL, PRMK"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="selectPembina" class="pk-form-label">Guru Pembina / Pelatih</label>
                        <select name="pembina_id" id="selectPembina" class="pk-form-select">
                            <option value="">-- Pilih Guru Pembina --</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                        <div class="pk-form-group">
                            <label for="selectHari" class="pk-form-label">Hari Latihan</label>
                            <select name="hari" id="selectHari" class="pk-form-select">
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="inputJamMulai" class="pk-form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" id="inputJamMulai" class="pk-form-input">
                        </div>

                        <div class="pk-form-group">
                            <label for="inputJamSelesai" class="pk-form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="inputJamSelesai" class="pk-form-input">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="inputTempat" class="pk-form-label">Tempat / Lokasi Latihan</label>
                        <input type="text"
                               name="tempat"
                               id="inputTempat"
                               class="pk-form-input"
                               placeholder="Contoh: Lapangan Utama, Aula Asrama, Lab Komputer">
                    </div>

                    <div class="pk-form-group">
                        <label for="inputDeskripsi" class="pk-form-label">Keterangan / Deskripsi Singkat</label>
                        <textarea name="deskripsi" id="inputDeskripsi" class="pk-form-input" rows="2" placeholder="Catatan kegiatan ekskul..."></textarea>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeTambahEkskulModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Ekstrakurikuler</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 4: EDIT EKSTRAKURIKULER                                           --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalEditEkskul" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <form action="" method="POST" id="formEditEkskul">
                @csrf
                @method('PUT')

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Edit Ekstrakurikuler</h3>
                        <span class="pk-modal-subtitle">Perbarui data kegiatan ekstrakurikuler</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeEditEkskulModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:14px;">
                        <div class="pk-form-group">
                            <label for="editNamaEkskul" class="pk-form-label">Nama Ekstrakurikuler <span style="color:#EF4444;">*</span></label>
                            <input type="text"
                                   name="nama_ekskul"
                                   id="editNamaEkskul"
                                   class="pk-form-input"
                                   required
                                   autocomplete="off">
                        </div>

                        <div class="pk-form-group">
                            <label for="editKodeEkskul" class="pk-form-label">Kode (Opsional)</label>
                            <input type="text"
                                   name="kode"
                                   id="editKodeEkskul"
                                   class="pk-form-input"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="editPembina" class="pk-form-label">Guru Pembina / Pelatih</label>
                        <select name="pembina_id" id="editPembina" class="pk-form-select">
                            <option value="">-- Pilih Guru Pembina --</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                        <div class="pk-form-group">
                            <label for="editHari" class="pk-form-label">Hari Latihan</label>
                            <select name="hari" id="editHari" class="pk-form-select">
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="editJamMulai" class="pk-form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" id="editJamMulai" class="pk-form-input">
                        </div>

                        <div class="pk-form-group">
                            <label for="editJamSelesai" class="pk-form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" id="editJamSelesai" class="pk-form-input">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="editTempat" class="pk-form-label">Tempat / Lokasi Latihan</label>
                        <input type="text"
                               name="tempat"
                               id="editTempat"
                               class="pk-form-input">
                    </div>

                    <div class="pk-form-group">
                        <label for="editDeskripsi" class="pk-form-label">Keterangan / Deskripsi Singkat</label>
                        <textarea name="deskripsi" id="editDeskripsi" class="pk-form-input" rows="2"></textarea>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeEditEkskulModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Perubahan</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- MODAL TAMBAH ANGGOTA LANGSUNG DI MODAL LIHAT ANGGOTA --}}
<div class="pk-modal-backdrop" id="modalTambahAnggotaDirect" style="display:none; z-index:10050;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">
            <form action="{{ route('plotting-ekskul.tambah-anggota') }}" method="POST" id="formDirectTambahAnggota">
                @csrf
                <input type="hidden" name="ekstrakurikuler_id" id="directEkskulId">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Siswa ke Ekskul</h3>
                        <span class="pk-modal-subtitle" id="directModalSubtitle">Pilih siswa jenjang {{ $jenjang }}</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeTambahAnggotaDirectModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="directSelectSiswa" class="pk-form-label">Pilih Siswa <span style="color:#EF4444;">*</span></label>
                        <select name="siswa_id" id="directSelectSiswa" class="pk-form-select" required>
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($allSiswa as $s)
                                <option value="{{ $s->id }}">{{ $s->nama_lengkap }} (Kelas {{ $s->kelas ?: '-' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pk-form-group">
                        <label for="directSelectPeran" class="pk-form-label">Peran</label>
                        <select name="peran" id="directSelectPeran" class="pk-form-select">
                            <option value="anggota" selected>Anggota</option>
                            <option value="ketua">Ketua</option>
                            <option value="wakil">Wakil Ketua</option>
                            <option value="sekretaris">Sekretaris</option>
                            <option value="bendahara">Bendahara</option>
                        </select>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeTambahAnggotaDirectModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pk-container { display: flex; flex-direction: column; gap: 16px; width: 100%; }
.pk-header-actions-wrap { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pk-btn-icon { width: 15px; height: 15px; flex-shrink: 0; }

.pk-btn-secondary {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
    background: #FFFFFF; color: var(--text-primary, #0F172A);
    border: 1px solid #E5E7EB; border-radius: 8px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.15s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.pk-btn-secondary:hover { background: #F9FAFB; border-color: #D1D5DB; }
.dark-mode .pk-btn-secondary { background: #1E293B; border-color: #334155; color: #F8FAFC; }

.pk-btn-add-kelas {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 15px;
    background: #0052FF; color: #FFFFFF; border: 1px solid #0052FF; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(0, 82, 255, 0.25);
}
.pk-btn-add-kelas:hover { background: #0043D4; border-color: #0043D4; }

.yayasan-switch-wrap {
    display: inline-flex; background: #F3F4F6; border-radius: 8px; padding: 3px; border: 1px solid #E5E7EB;
}
.dark-mode .yayasan-switch-wrap { background: #1E293B; border-color: #334155; }
.yayasan-tab-btn {
    padding: 5px 13px; font-size: 12px; font-weight: 700; color: #6B7280;
    text-decoration: none; border-radius: 6px; transition: all 0.15s ease;
}
.yayasan-tab-btn.active { background: #0052FF; color: #FFFFFF; }

.pk-alert { display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }
.pk-alert-success { background: #ECFDF5; border: 1px solid #A7F3D0; color: #065F46; }
.pk-alert-danger { background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; }
.pk-alert-icon { width: 17px; height: 17px; flex-shrink: 0; }
.pk-alert-close { margin-left: auto; background: transparent; border: none; font-size: 18px; color: inherit; cursor: pointer; }

.pk-topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.pk-tabs-nav {
    display: inline-flex; background: #FFFFFF; border: 1px solid #E5E7EB;
    border-radius: 10px; padding: 4px; gap: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.dark-mode .pk-tabs-nav { background: #141C28; border-color: #263346; }
.pk-tab-item {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 15px;
    background: transparent; border: none; border-radius: 7px; font-size: 13px;
    font-weight: 600; color: #6B7280; cursor: pointer; transition: all 0.15s ease;
}
.pk-tab-item:hover { color: #0052FF; }
.pk-tab-item.active { background: #0052FF; color: #FFFFFF; }
.pk-tab-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px;
    font-size: 11px; font-weight: 700; background: #F3F4F6; color: #4B5563;
}
.pk-tab-item.active .pk-tab-badge { background: rgba(255, 255, 255, 0.25); color: #FFFFFF; }
.pk-tab-badge.pk-badge-notice { background: #FEE2E2; color: #DC2626; }
.pk-tab-item.active .pk-tab-badge.pk-badge-notice { background: #FFFFFF; color: #DC2626; }

.pk-tab-pane { display: none; }
.pk-tab-pane.active { display: block; }

.fi-ta-ctn {
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;
}
.dark-mode .fi-ta-ctn { background: #141C28; border-color: #263346; }

.fi-ta-header-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-bottom: 1px solid #F1F5F9; gap: 12px; flex-wrap: wrap;
}
.dark-mode .fi-ta-header-toolbar { border-bottom-color: #1E293B; }
.fi-ta-toolbar-left, .fi-ta-toolbar-right { display: flex; align-items: center; gap: 8px; }

.fi-ta-bulk-wrap, .fi-ta-filter-wrap, .fi-ta-col-wrap { position: relative; }
.fi-ta-bulk-btn, .fi-ta-filter-btn, .fi-ta-col-btn {
    display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 11px;
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #4B5563; cursor: pointer; transition: all 0.15s ease;
}
.dark-mode .fi-ta-bulk-btn, .dark-mode .fi-ta-filter-btn, .dark-mode .fi-ta-col-btn {
    background: #1E293B; border-color: #334155; color: #CBD5E1;
}
.fi-ta-bulk-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.fi-ta-bulk-btn:not(:disabled):hover, .fi-ta-filter-btn:hover, .fi-ta-col-btn:hover {
    background: #F9FAFB; border-color: #D1D5DB; color: #0052FF;
}

.fi-ta-bulk-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px;
    font-size: 10.5px; font-weight: 700; background: #0052FF; color: #FFFFFF;
}

.fi-ta-bulk-menu, .fi-ta-filter-popover, .fi-ta-col-popover {
    position: absolute; top: calc(100% + 6px); z-index: 50;
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08); display: none; min-width: 180px;
}
.dark-mode .fi-ta-bulk-menu, .dark-mode .fi-ta-filter-popover, .dark-mode .fi-ta-col-popover {
    background: #1E293B; border-color: #334155; color: #F1F5F9;
}
.fi-ta-bulk-menu { left: 0; padding: 6px; }
.fi-ta-bulk-item {
    display: flex; align-items: center; gap: 8px; width: 100%; padding: 7px 10px;
    background: transparent; border: none; border-radius: 6px; font-size: 12.5px;
    font-weight: 500; color: #374151; cursor: pointer; text-align: left; transition: all 0.15s ease;
}
.dark-mode .fi-ta-bulk-item { color: #E2E8F0; }
.fi-ta-bulk-item:hover { background: #F3F4F6; }
.dark-mode .fi-ta-bulk-item:hover { background: #334155; }
.fi-ta-bulk-item.is-danger { color: #DC2626; }
.fi-ta-bulk-item.is-danger:hover { background: #FEF2F2; }
.fi-ta-bulk-item.is-primary { color: #0052FF; }
.fi-ta-bulk-item.is-primary:hover { background: #EFF6FF; }
.fi-ta-bulk-divider { height: 1px; background: #E5E7EB; margin: 4px 0; }
.dark-mode .fi-ta-bulk-divider { background: #334155; }

.fi-ta-search-wrap {
    position: relative; display: flex; align-items: center; width: 260px;
}
.fi-ta-search-icon {
    position: absolute; left: 10px; width: 14px; height: 14px; color: #9CA3AF; pointer-events: none;
}
.fi-ta-search-input {
    width: 100%; height: 34px; padding: 0 28px 0 32px; font-size: 12.5px;
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 8px;
    color: var(--text-primary, #0F172A); outline: none; transition: all 0.15s ease;
}
.dark-mode .fi-ta-search-input { background: #1E293B; border-color: #334155; color: #F8FAFC; }
.fi-ta-search-input:focus { border-color: #0052FF; box-shadow: 0 0 0 2px rgba(0, 82, 255, 0.15); }
.fi-ta-search-clear {
    position: absolute; right: 8px; background: transparent; border: none;
    color: #9CA3AF; cursor: pointer; display: none; padding: 2px;
}

.fi-ta-filter-popover, .fi-ta-col-popover { right: 0; width: 260px; padding: 12px; }
.fi-ta-filter-head, .fi-ta-col-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #E5E7EB;
}
.dark-mode .fi-ta-filter-head, .dark-mode .fi-ta-col-head { border-bottom-color: #334155; }
.fi-ta-filter-title, .fi-ta-col-title { font-size: 12.5px; font-weight: 700; color: #111827; }
.dark-mode .fi-ta-filter-title, .dark-mode .fi-ta-col-title { color: #F1F5F9; }
.fi-ta-filter-reset, .fi-ta-col-reset {
    background: transparent; border: none; font-size: 11.5px; font-weight: 600;
    color: #0052FF; cursor: pointer;
}
.fi-ta-filter-body { display: flex; flex-direction: column; gap: 10px; }
.fi-ta-filter-check-row {
    display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #374151; cursor: pointer;
}
.dark-mode .fi-ta-filter-check-row { color: #CBD5E1; }
.fi-ta-filter-field { display: flex; flex-direction: column; gap: 4px; }
.fi-ta-filter-label { font-size: 11.5px; font-weight: 600; color: #4B5563; }
.dark-mode .fi-ta-filter-label { color: #94A3B8; }
.fi-ta-filter-select {
    height: 32px; padding: 0 8px; font-size: 12.5px; border-radius: 6px;
    border: 1px solid #E5E7EB; background: #FFFFFF; color: #111827;
}
.dark-mode .fi-ta-filter-select { background: #0F172A; border-color: #334155; color: #F1F5F9; }
.fi-ta-filter-actions { margin-top: 12px; padding-top: 8px; border-top: 1px solid #E5E7EB; }
.dark-mode .fi-ta-filter-actions { border-top-color: #334155; }
.fi-ta-filter-apply {
    width: 100%; height: 32px; background: #0052FF; color: #FFFFFF;
    border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;
}

.fi-ta-col-list { display: flex; flex-direction: column; gap: 6px; max-height: 200px; overflow-y: auto; }
.fi-ta-col-item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #374151; cursor: pointer; }
.dark-mode .fi-ta-col-item { color: #CBD5E1; }

.fi-ta-selection-bar {
    display: none; align-items: center; justify-content: space-between;
    padding: 8px 16px; background: #EFF6FF; border-bottom: 1px solid #DBEAFE;
    font-size: 12.5px; font-weight: 600; color: #1E40AF;
}
.dark-mode .fi-ta-selection-bar { background: rgba(0, 82, 255, 0.15); border-bottom-color: rgba(0, 82, 255, 0.3); color: #93C5FD; }
.fi-ta-selection-clear-btn { background: transparent; border: none; color: inherit; text-decoration: underline; cursor: pointer; font-size: 12px; }

.fi-ta-table-scroll { width: 100%; overflow-x: auto; }
.fi-ta-table { width: 100%; border-collapse: collapse; text-align: left; }
.fi-ta-table th {
    padding: 10px 14px; background: #F8FAFC; border-bottom: 1px solid #E5E7EB;
    font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.03em;
}
.dark-mode .fi-ta-table th { background: #0F172A; border-bottom-color: #1E293B; color: #94A3B8; }
.fi-ta-th-check, .fi-ta-td-check { width: 36px; text-align: center; padding: 10px 8px !important; }
.fi-ta-checkbox { width: 15px; height: 15px; border-radius: 4px; accent-color: #0052FF; cursor: pointer; }
.fi-ta-th-sortable { cursor: pointer; user-select: none; }
.fi-ta-sort-icon { font-size: 10px; color: #9CA3AF; margin-left: 4px; }
.fi-ta-th-center, .fi-ta-td-center { text-align: center; }
.fi-ta-th-right, .fi-ta-td-right { text-align: right; }

.fi-ta-table td {
    padding: 12px 14px; border-bottom: 1px solid #F1F5F9; font-size: 13px; color: var(--text-primary, #0F172A); vertical-align: middle;
}
.dark-mode .fi-ta-table td { border-bottom-color: #1E293B; }
.fi-ta-row:hover td { background: #F8FAFC; }
.dark-mode .fi-ta-row:hover td { background: #1E293B; }
.fi-ta-row.is-selected td { background: #F0F7FF; }
.dark-mode .fi-ta-row.is-selected td { background: rgba(0, 82, 255, 0.12); }

.pk-cell-kelas, .pk-cell-siswa { display: flex; flex-direction: column; gap: 3px; }
.pk-kelas-main, .pk-siswa-main { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pk-kelas-name, .pk-siswa-name { font-weight: 700; font-size: 13.5px; color: var(--text-primary, #0F172A); }
.pk-badge-kode { font-size: 11px; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: #F3F4F6; color: #4B5563; }
.pk-badge-jenjang {
    display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 5px;
    background: #EFF6FF; color: #0052FF; width: fit-content;
}
.pk-badge-jenjang.sma { background: #FEF3C7; color: #B45309; }

.pk-cell-siswa { display: flex; flex-direction: row; align-items: center; gap: 10px; }
.pk-siswa-avatar {
    width: 32px; height: 32px; border-radius: 50%; background: #EFF6FF; color: #0052FF;
    font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.pk-siswa-info { display: flex; flex-direction: column; gap: 1px; }
.pk-siswa-nisn { font-size: 11.5px; color: #6B7280; }

.pk-badge-count-btn {
    display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px;
    background: #F1F5F9; border: 1px solid #E2E8F0; border-radius: 20px;
    cursor: pointer; transition: all 0.15s ease;
}
.pk-badge-count-btn:hover { background: #E0E7FF; border-color: #C7D2FE; }
.pk-badge-count-num { font-weight: 700; font-size: 12.5px; color: #0052FF; }
.pk-badge-count-label { font-size: 11px; color: #64748B; }

.pk-row-actions { display: inline-flex; align-items: center; gap: 4px; justify-content: flex-end; }
.pk-action-btn {
    width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #E5E7EB; background: #FFFFFF; border-radius: 6px; color: #6B7280;
    cursor: pointer; transition: all 0.15s ease;
}
.dark-mode .pk-action-btn { background: #1E293B; border-color: #334155; color: #94A3B8; }
.pk-action-btn svg { width: 14px; height: 14px; }
.pk-action-btn.is-view:hover { color: #0052FF; border-color: #93C5FD; background: #EFF6FF; }
.pk-action-btn.is-edit:hover { color: #F59E0B; border-color: #FDE68A; background: #FFFBEB; }
.pk-action-btn.is-delete:hover { color: #DC2626; border-color: #FECACA; background: #FEF2F2; }

.pk-btn-mutasi-row {
    display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px;
    background: #0052FF; color: #FFFFFF; border: none; border-radius: 6px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;
}
.pk-btn-mutasi-row:hover { background: #0043D4; }
.pk-btn-mutasi-row svg { width: 12px; height: 12px; }

.pk-badge-status {
    display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;
}
.pk-badge-status.is-aktif { background: #ECFDF5; color: #059669; }
.pk-badge-status.is-sakit { background: #FEF3C7; color: #D97706; }
.pk-badge-status.is-pulang { background: #F1F5F9; color: #475569; }

.fi-ta-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid #E5E7EB; gap: 12px; flex-wrap: wrap;
}
.dark-mode .fi-ta-footer { border-top-color: #1E293B; }
.fi-ta-footer-info { font-size: 12px; color: #6B7280; }
.fi-ta-perpage { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #6B7280; }
.fi-ta-perpage-select {
    height: 28px; padding: 0 6px; font-size: 12px; border: 1px solid #E5E7EB;
    border-radius: 6px; background: #FFFFFF; color: #374151;
}
.dark-mode .fi-ta-perpage-select { background: #1E293B; border-color: #334155; color: #F1F5F9; }
.fi-ta-pages { display: flex; align-items: center; gap: 4px; }
.fi-ta-page-btn {
    min-width: 28px; height: 28px; padding: 0 6px; display: inline-flex;
    align-items: center; justify-content: center; border: 1px solid #E5E7EB;
    background: #FFFFFF; border-radius: 6px; font-size: 12px; font-weight: 600;
    color: #4B5563; cursor: pointer; transition: all 0.15s ease;
}
.fi-ta-page-btn:hover { background: #F3F4F6; }
.fi-ta-page-btn.active { background: #0052FF; border-color: #0052FF; color: #FFFFFF; }

.pk-modal-backdrop, .fi-confirm-backdrop {
    position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; padding: 16px;
}
.pk-modal-dialog { width: 100%; max-width: 640px; }
.pk-modal-dialog-sm { max-width: 440px; }
.pk-modal-content {
    background: #FFFFFF; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    overflow: hidden; display: flex; flex-direction: column;
}
.dark-mode .pk-modal-content { background: #1E293B; color: #F8FAFC; }
.pk-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px; border-bottom: 1px solid #E5E7EB;
}
.dark-mode .pk-modal-header { border-bottom-color: #334155; }
.pk-modal-title { font-size: 15px; font-weight: 700; color: var(--text-primary, #0F172A); margin: 0; }
.pk-modal-subtitle { font-size: 12px; color: #6B7280; margin-top: 2px; }
.pk-modal-close { background: transparent; border: none; font-size: 20px; color: #9CA3AF; cursor: pointer; line-height: 1; }
.pk-modal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 14px; }
.pk-form-group { display: flex; flex-direction: column; gap: 5px; }
.pk-form-label { font-size: 12.5px; font-weight: 600; color: #374151; }
.dark-mode .pk-form-label { color: #CBD5E1; }
.pk-form-input, .pk-form-select {
    width: 100%; height: 36px; padding: 0 12px; font-size: 13px;
    border: 1px solid #D1D5DB; border-radius: 8px; background: #FFFFFF;
    color: var(--text-primary, #0F172A); outline: none; transition: border-color 0.15s ease;
}
textarea.pk-form-input { height: auto; padding: 8px 12px; }
.dark-mode .pk-form-input, .dark-mode .pk-form-select { background: #0F172A; border-color: #334155; color: #F8FAFC; }
.pk-form-input:focus, .pk-form-select:focus { border-color: #0052FF; }
.pk-modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 8px;
    padding: 12px 20px; border-top: 1px solid #E5E7EB; background: #F8FAFC;
}
.dark-mode .pk-modal-footer { background: #0F172A; border-top-color: #334155; }
.pk-btn-clean {
    padding: 7px 14px; background: transparent; border: 1px solid #D1D5DB;
    border-radius: 7px; font-size: 12.5px; font-weight: 600; color: #4B5563; cursor: pointer;
}
.dark-mode .pk-btn-clean { border-color: #334155; color: #CBD5E1; }
.pk-btn-primary {
    padding: 7px 16px; background: #0052FF; border: 1px solid #0052FF;
    border-radius: 7px; font-size: 12.5px; font-weight: 600; color: #FFFFFF; cursor: pointer;
}

.fi-confirm-backdrop { display: none; }
.fi-confirm-dialog {
    background: #FFFFFF; border-radius: 12px; width: 100%; max-width: 400px;
    padding: 24px 20px 20px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
    text-align: center; position: relative;
}
.dark-mode .fi-confirm-dialog { background: #1E293B; }
.fi-confirm-close { position: absolute; top: 12px; right: 12px; background: transparent; border: none; color: #9CA3AF; cursor: pointer; }
.fi-confirm-icon {
    width: 44px; height: 44px; border-radius: 50%; margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
}
.fi-confirm-icon.is-danger { background: #FEE2E2; color: #DC2626; }
.fi-confirm-icon.is-primary { background: #EFF6FF; color: #0052FF; }
.fi-confirm-icon svg { width: 22px; height: 22px; }
.fi-confirm-title { font-size: 15px; font-weight: 700; color: var(--text-primary, #0F172A); margin: 0 0 6px; }
.fi-confirm-desc { font-size: 12.5px; color: #6B7280; margin: 0 0 20px; }
.fi-confirm-actions { display: flex; align-items: center; justify-content: center; gap: 8px; }
.fi-confirm-btn-cancel {
    padding: 8px 16px; background: #FFFFFF; border: 1px solid #D1D5DB;
    border-radius: 7px; font-size: 12.5px; font-weight: 600; color: #4B5563; cursor: pointer;
}
.fi-confirm-btn-submit {
    padding: 8px 18px; background: #DC2626; border: none; border-radius: 7px;
    font-size: 12.5px; font-weight: 600; color: #FFFFFF; cursor: pointer;
}
</style>

<script>
let currentActiveEkskulId = null;
let currentModalAnggotaData = [];

// Tab switcher
function switchTab(tab) {
    document.querySelectorAll('.pk-tab-item').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.pk-tab-pane').forEach(p => p.classList.remove('active'));

    if (tab === 'ekskul') {
        document.getElementById('tabBtnEkskul').classList.add('active');
        document.getElementById('paneEkskul').classList.add('active');
    } else {
        document.getElementById('tabBtnBelum').classList.add('active');
        document.getElementById('paneBelum').classList.add('active');
    }
}

// Popovers
function toggleDropdownMenu(menuId, event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById(menuId);
    if (!menu) return;
    const isShown = menu.style.display === 'block';
    closeAllPopovers();
    if (!isShown) menu.style.display = 'block';
}

function closeAllPopovers() {
    document.querySelectorAll('.fi-ta-bulk-menu, .fi-ta-filter-popover, .fi-ta-col-popover').forEach(el => {
        el.style.display = 'none';
    });
}
document.addEventListener('click', closeAllPopovers);

// Selection Checkbox
function toggleMasterCheck(key, isChecked) {
    const selector = key === 'ekskul' ? '.row-check-ekskul' : '.row-check-belum';
    document.querySelectorAll(selector).forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') {
            cb.checked = isChecked;
        }
    });
    onRowCheckChange(key);
}

function onRowCheckChange(key) {
    const selector = key === 'ekskul' ? '.row-check-ekskul' : '.row-check-belum';
    const checked = Array.from(document.querySelectorAll(selector + ':checked'));
    const count = checked.length;

    const btnBulk = document.getElementById(key === 'ekskul' ? 'btnBulkEkskul' : 'btnBulkBelum');
    const badgeCount = document.getElementById(key === 'ekskul' ? 'bulkCountBadgeEkskul' : 'bulkCountBadgeBelum');
    const selBar = document.getElementById(key === 'ekskul' ? 'selBarEkskul' : 'selBarBelum');
    const selCountText = document.getElementById(key === 'ekskul' ? 'selCountTextEkskul' : 'selCountTextBelum');

    if (btnBulk) btnBulk.disabled = count === 0;
    if (badgeCount) {
        badgeCount.textContent = count;
        badgeCount.style.display = count > 0 ? 'inline-flex' : 'none';
    }
    if (selBar) selBar.style.display = count > 0 ? 'flex' : 'none';
    if (selCountText) {
        selCountText.textContent = `${count} ${key === 'ekskul' ? 'ekskul' : 'siswa'} dipilih`;
    }

    document.querySelectorAll(selector).forEach(cb => {
        cb.closest('tr')?.classList.toggle('is-selected', cb.checked);
    });
}

function clearSelection(key) {
    const selector = key === 'ekskul' ? '.row-check-ekskul' : '.row-check-belum';
    document.querySelectorAll(selector).forEach(cb => cb.checked = false);
    const master = document.getElementById(key === 'ekskul' ? 'masterCheckEkskul' : 'masterCheckBelum');
    if (master) master.checked = false;
    onRowCheckChange(key);
}

// Client search & filter
function onSearchInput(key) {
    const val = (document.getElementById(key === 'ekskul' ? 'searchEkskul' : 'searchBelum')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById(key === 'ekskul' ? 'clearSearchEkskul' : 'clearSearchBelum');
    if (clearBtn) clearBtn.style.display = val.length > 0 ? 'block' : 'none';
    applyClientFilter(key);
}

function clearSearch(key) {
    const input = document.getElementById(key === 'ekskul' ? 'searchEkskul' : 'searchBelum');
    if (input) input.value = '';
    const clearBtn = document.getElementById(key === 'ekskul' ? 'clearSearchEkskul' : 'clearSearchBelum');
    if (clearBtn) clearBtn.style.display = 'none';
    applyClientFilter(key);
}

function applyFilter(key) {
    closeAllPopovers();
    applyClientFilter(key);
}

function resetFilter(key) {
    if (key === 'ekskul') {
        const c1 = document.getElementById('fltEkskulTerisi');
        const s1 = document.getElementById('fltEkskulHari');
        if (c1) c1.checked = false;
        if (s1) s1.value = '';
    } else {
        const s2 = document.getElementById('fltBelumKelas');
        if (s2) s2.value = '';
    }
    closeAllPopovers();
    applyClientFilter(key);
}

function applyClientFilter(key) {
    const isEkskul = key === 'ekskul';
    const rows = document.querySelectorAll(isEkskul ? '.js-row-ekskul' : '.js-row-belum');
    const emptyRow = document.getElementById(isEkskul ? 'emptyFilterRowEkskul' : 'emptyFilterRowBelum');
    const searchVal = (document.getElementById(isEkskul ? 'searchEkskul' : 'searchBelum')?.value || '').toLowerCase().trim();

    let filterBadgeCount = 0;
    let visibleCount = 0;

    let fltTerisi = false;
    let fltHari = '';
    let fltKelas = '';

    if (isEkskul) {
        fltTerisi = document.getElementById('fltEkskulTerisi')?.checked || false;
        fltHari = (document.getElementById('fltEkskulHari')?.value || '').trim();
        if (fltTerisi) filterBadgeCount++;
        if (fltHari) filterBadgeCount++;
        const badgeEl = document.getElementById('badgeFilterEkskul');
        if (badgeEl) badgeEl.textContent = filterBadgeCount;
    } else {
        fltKelas = (document.getElementById('fltBelumKelas')?.value || '').trim();
        if (fltKelas) filterBadgeCount++;
        const badgeEl = document.getElementById('badgeFilterBelum');
        if (badgeEl) badgeEl.textContent = filterBadgeCount;
    }

    rows.forEach(row => {
        let match = true;
        if (isEkskul) {
            const nama = row.getAttribute('data-nama') || '';
            const kode = row.getAttribute('data-kode') || '';
            const pembina = row.getAttribute('data-pembina') || '';
            const tempat = row.getAttribute('data-tempat') || '';
            const hari = row.getAttribute('data-hari') || '';
            const count = parseInt(row.getAttribute('data-count') || '0', 10);

            if (searchVal) {
                match = nama.includes(searchVal) || kode.includes(searchVal) || pembina.includes(searchVal) || tempat.includes(searchVal);
            }
            if (match && fltTerisi && count === 0) match = false;
            if (match && fltHari && hari !== fltHari) match = false;
        } else {
            const nama = row.getAttribute('data-nama') || '';
            const nisn = row.getAttribute('data-nisn') || '';
            const kelas = row.getAttribute('data-kelas') || '';

            if (searchVal) {
                match = nama.includes(searchVal) || nisn.includes(searchVal);
            }
            if (match && fltKelas && kelas !== fltKelas) match = false;
        }

        row.style.display = match ? '' : 'none';
        if (match) visibleCount++;
    });

    if (emptyRow) emptyRow.style.display = visibleCount === 0 ? '' : 'none';

    const footerInfo = document.getElementById(isEkskul ? 'footerInfoEkskul' : 'footerInfoBelum');
    if (footerInfo) footerInfo.textContent = `Menampilkan ${visibleCount} data`;
}

// Confirmation Dialog Modal
function openFiConfirm({ title, desc, confirmText, isDanger, onConfirm }) {
    const modal = document.getElementById('fiConfirmModal');
    document.getElementById('fiConfirmTitle').textContent = title || 'Konfirmasi Tindakan';
    document.getElementById('fiConfirmDesc').textContent = desc || 'Apakah Anda yakin ingin melanjutkan?';
    const subBtn = document.getElementById('fiConfirmSubmitBtn');
    subBtn.textContent = confirmText || 'Ya, Lanjutkan';
    subBtn.className = isDanger ? 'fi-confirm-btn-submit' : 'pk-btn-primary';
    subBtn.onclick = function() {
        closeFiConfirm();
        if (typeof onConfirm === 'function') onConfirm();
    };
    modal.style.display = 'flex';
}

function closeFiConfirm() {
    document.getElementById('fiConfirmModal').style.display = 'none';
}

// Delete Single / Bulk Ekskul
function confirmDeleteSingleEkskul(id, nama) {
    openFiConfirm({
        title: `Hapus Ekstrakurikuler ${nama}`,
        desc: `Apakah Anda yakin ingin menghapus ekstrakurikuler "${nama}"? Seluruh data riwayat keanggotaan akan terhapus.`,
        confirmText: 'Hapus Ekskul',
        isDanger: true,
        onConfirm: function() {
            const form = document.getElementById('formDeleteSingleEkskul');
            form.action = `/plotting-ekskul/ekskul/${id}`;
            form.submit();
        }
    });
}

function confirmBulkDeleteEkskul() {
    const checked = Array.from(document.querySelectorAll('.row-check-ekskul:checked')).map(cb => cb.value);
    if (checked.length === 0) return;

    openFiConfirm({
        title: `Hapus ${checked.length} Ekstrakurikuler`,
        desc: `Apakah Anda yakin ingin menghapus ${checked.length} ekstrakurikuler terpilih secara massal? Tindakan ini tidak dapat dibatalkan.`,
        confirmText: 'Hapus Terpilih',
        isDanger: true,
        onConfirm: function() {
            document.getElementById('bulkDeleteEkskulIds').value = checked.join(',');
            document.getElementById('formBulkDeleteEkskul').submit();
        }
    });
}

// Modal Tambah & Edit Ekskul
function openTambahEkskulModal() {
    document.getElementById('modalTambahEkskul').style.display = 'flex';
}
function closeTambahEkskulModal() {
    document.getElementById('modalTambahEkskul').style.display = 'none';
}

function openEditEkskulModal(data) {
    document.getElementById('formEditEkskul').action = `/plotting-ekskul/ekskul/${data.id}`;
    document.getElementById('editNamaEkskul').value = data.nama_ekskul || '';
    document.getElementById('editKodeEkskul').value = data.kode || '';
    document.getElementById('editPembina').value = data.pembina_id || '';
    document.getElementById('editHari').value = data.hari || '';
    document.getElementById('editJamMulai').value = data.jam_mulai || '';
    document.getElementById('editJamSelesai').value = data.jam_selesai || '';
    document.getElementById('editTempat').value = data.tempat || '';
    document.getElementById('editDeskripsi').value = data.deskripsi || '';
    document.getElementById('modalEditEkskul').style.display = 'flex';
}
function closeEditEkskulModal() {
    document.getElementById('modalEditEkskul').style.display = 'none';
}

// Modal Anggota Ekskul
function openAnggotaModal(ekskulId, namaEkskul) {
    currentActiveEkskulId = ekskulId;
    document.getElementById('modalAnggotaTitle').textContent = `Anggota: ${namaEkskul}`;
    document.getElementById('modalAnggotaSubtitle').textContent = `Memuat daftar siswa...`;
    document.getElementById('modalAnggota').style.display = 'flex';
    document.getElementById('modalAnggotaSearch').value = '';

    fetch(`/plotting-ekskul/anggota/${ekskulId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                currentModalAnggotaData = res.anggota;
                document.getElementById('modalAnggotaSubtitle').textContent = `Pembina: ${res.pembina} • ${res.total} Anggota`;
                renderModalAnggotaTable(res.anggota);
            }
        })
        .catch(err => {
            document.getElementById('tbodyModalAnggota').innerHTML = `
                <tr><td colspan="5" style="padding:24px; text-align:center; color:#DC2626;">Gagal memuat data anggota.</td></tr>
            `;
        });
}

function closeAnggotaModal() {
    document.getElementById('modalAnggota').style.display = 'none';
    currentActiveEkskulId = null;
}

function renderModalAnggotaTable(list) {
    const tbody = document.getElementById('tbodyModalAnggota');
    if (!list || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="padding:28px; text-align:center; color:#6B7280;">Belum ada anggota di ekstrakurikuler ini.</td></tr>`;
        document.getElementById('footerInfoModal').textContent = `Total 0 anggota`;
        return;
    }

    let html = '';
    list.forEach(a => {
        html += `
            <tr class="fi-ta-row modal-anggota-row" data-siswa-id="${a.siswa_id}" data-nama="${(a.nama_lengkap || '').toLowerCase()}">
                <td class="fi-ta-td-check">
                    <input type="checkbox" class="fi-ta-checkbox row-check-modal" value="${a.siswa_id}" onchange="onModalCheckChange()">
                </td>
                <td class="fi-ta-td">
                    <div style="font-weight:600; color:var(--text-primary, #0F172A);">${a.nama_lengkap}</div>
                    <div style="font-size:11.5px; color:#6B7280;">NISN: ${a.nisn}</div>
                </td>
                <td class="fi-ta-td">
                    <span class="pk-badge-jenjang">${a.kelas || '-'}</span>
                </td>
                <td class="fi-ta-td">
                    <span style="font-size:11.5px; font-weight:700; text-transform:capitalize; padding:2px 8px; border-radius:12px; background:#F1F5F9; color:#475569;">
                        ${a.peran || 'anggota'}
                    </span>
                </td>
                <td class="fi-ta-td fi-ta-td-right">
                    <button type="button" class="pk-action-btn is-delete" onclick="keluarkanSingleModal(${a.siswa_id}, '${escapeJs(a.nama_lengkap)}')" title="Keluarkan dari ekskul">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
    document.getElementById('footerInfoModal').textContent = `Total ${list.length} anggota`;
    onModalCheckChange();
}

function filterModalAnggota(q) {
    const search = q.toLowerCase().trim();
    const rows = document.querySelectorAll('.modal-anggota-row');
    rows.forEach(r => {
        const nama = r.getAttribute('data-nama') || '';
        r.style.display = nama.includes(search) ? '' : 'none';
    });
}

function toggleMasterCheckModal(isChecked) {
    document.querySelectorAll('.row-check-modal').forEach(cb => {
        const r = cb.closest('tr');
        if (r && r.style.display !== 'none') cb.checked = isChecked;
    });
    onModalCheckChange();
}

function onModalCheckChange() {
    const checked = Array.from(document.querySelectorAll('.row-check-modal:checked'));
    const btn = document.getElementById('btnBulkModal');
    const badge = document.getElementById('bulkCountModal');
    if (btn) btn.disabled = checked.length === 0;
    if (badge) {
        badge.textContent = checked.length;
        badge.style.display = checked.length > 0 ? 'inline-flex' : 'none';
    }
}

function keluarkanSingleModal(siswaId, nama) {
    openFiConfirm({
        title: 'Keluarkan Siswa',
        desc: `Keluarkan ${nama} dari ekstrakurikuler ini?`,
        confirmText: 'Keluarkan',
        isDanger: true,
        onConfirm: function() {
            postKeluarkanAnggota([siswaId]);
        }
    });
}

function keluarkanBulkModal() {
    const checked = Array.from(document.querySelectorAll('.row-check-modal:checked')).map(cb => cb.value);
    if (checked.length === 0) return;

    openFiConfirm({
        title: `Keluarkan ${checked.length} Siswa`,
        desc: `Apakah Anda yakin ingin mengeluarkan ${checked.length} siswa terpilih dari ekskul ini?`,
        confirmText: 'Keluarkan',
        isDanger: true,
        onConfirm: function() {
            postKeluarkanAnggota(checked);
        }
    });
}

function postKeluarkanAnggota(siswaIds) {
    if (!currentActiveEkskulId) return;

    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('ekstrakurikuler_id', currentActiveEkskulId);
    siswaIds.forEach(id => fd.append('siswa_id[]', id));

    fetch('{{ route("plotting-ekskul.hapus-anggota") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            // Refresh modal list
            openAnggotaModal(currentActiveEkskulId, document.getElementById('modalAnggotaTitle').textContent.replace('Anggota: ', ''));
        }
    });
}

// Tambah Siswa Langsung dari dalam Modal Anggota
function openTambahAnggotaModal() {
    document.getElementById('directEkskulId').value = currentActiveEkskulId;
    document.getElementById('modalTambahAnggotaDirect').style.display = 'flex';
}
function closeTambahAnggotaDirectModal() {
    document.getElementById('modalTambahAnggotaDirect').style.display = 'none';
}

// Assign Siswa (Tab 2)
function openAssignSingleModal(siswaId, nama) {
    document.getElementById('assignSiswaIds').value = siswaId;
    document.getElementById('assignModalTitle').textContent = `Pilih Ekskul: ${nama}`;
    document.getElementById('modalAssignEkskul').style.display = 'flex';
}

function openAssignBulkModal() {
    const checked = Array.from(document.querySelectorAll('.row-check-belum:checked')).map(cb => cb.value);
    if (checked.length === 0) return;
    document.getElementById('assignSiswaIds').value = checked.join(',');
    document.getElementById('assignModalTitle').textContent = `Masukkan ${checked.length} Siswa ke Ekskul`;
    document.getElementById('modalAssignEkskul').style.display = 'flex';
}

function closeAssignModal() {
    document.getElementById('modalAssignEkskul').style.display = 'none';
}

function escapeJs(str) {
    return (str || '').replace(/'/g, "\\'");
}

// Init client filter on page load
document.addEventListener('DOMContentLoaded', function() {
    applyClientFilter('ekskul');
    applyClientFilter('belum');
});
</script>
@endsection