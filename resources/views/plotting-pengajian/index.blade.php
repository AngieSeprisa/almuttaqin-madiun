@extends('layouts.app')

@section('title', 'Plotting Kelas Pengajian')

@section('header-actions')
<div class="pk-header-actions-wrap">
    {{-- Tombol Muat Standar --}}
    <form action="{{ route('plotting-pengajian.muat-standar') }}" method="POST" class="d-inline" id="formMuatStandarPengajian">
        @csrf
        <button type="button" class="pk-btn-secondary" title="Muat daftar kelas pengajian standar pondok pesantren"
                onclick="openFiConfirm({
                    variant: 'primary',
                    title: 'Muat Kelas Standar Pengajian',
                    desc: 'Sistem akan memeriksa dan menambahkan 4 kelas pengajian standar (Kelas Lambatan SMP, Kelas Cepatan SMP, Kelas Pegon Bacaan SMP, Kelas Reguler) jika belum ada di sistem.',
                    confirmText: 'Muat Standar',
                    onConfirm: function() { document.getElementById('formMuatStandarPengajian').submit(); }
                })">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            <span>Muat Standar</span>
        </button>
    </form>

    {{-- Tombol Buat Kelas Baru --}}
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

    {{-- Alert Flash Message Success --}}
    @if(session('success'))
    <div class="pk-alert pk-alert-success" id="pkFlashAlert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-alert-icon">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
        <button type="button" class="pk-alert-close" onclick="document.getElementById('pkFlashAlert').remove()">&times;</button>
    </div>
    @endif

    {{-- Alert Flash Message Error --}}
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

    {{-- Segmented Tabs Navigation (Persis Plotting Kamar) --}}
    <div class="pk-topbar">
        <div class="pk-tabs-nav" role="tablist">
            <button type="button"
                    class="pk-tab-item {{ $activeTab === 'kelas' ? 'active' : '' }}"
                    id="tabBtnKelas"
                    onclick="switchTab('kelas')">
                <span>Daftar Kelas</span>
                <span class="pk-tab-badge">{{ count($dataKelas) }}</span>
            </button>
            <button type="button"
                    class="pk-tab-item {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}"
                    id="tabBtnBelum"
                    onclick="switchTab('belum_terdaftar')">
                <span>Belum Terdaftar</span>
                <span class="pk-tab-badge {{ $belumTerdaftar->count() > 0 ? 'pk-badge-notice' : '' }}">
                    {{ $belumTerdaftar->count() }}
                </span>
            </button>
        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- TAB 1: DAFTAR KELAS PENGAJIAN (FILAMENT V4 TABLE CARD)                 --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'kelas' ? 'active' : '' }}" id="paneKelas">
        <div class="fi-ta-ctn" id="fiCardKelas">

            {{-- Top Toolbar: [ ⋮ Aksi ] on Left | [ 🔍 Cari... ] + [ Funnel Filter ] on Right --}}
            <div class="fi-ta-header-toolbar">
                <div class="fi-ta-toolbar-left">
                    <div class="fi-ta-bulk-wrap" id="bulkWrapKelas">
                        <button type="button"
                                class="fi-ta-bulk-btn"
                                id="btnBulkKelas"
                                disabled
                                onclick="toggleDropdownMenu('menuBulkKelas', event)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="5" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="12" r="1.5" fill="currentColor"></circle>
                                <circle cx="12" cy="19" r="1.5" fill="currentColor"></circle>
                            </svg>
                            <span>Aksi</span>
                            <span class="fi-ta-bulk-count" id="bulkCountBadgeKelas" style="display:none;">0</span>
                        </button>

                        <div class="fi-ta-bulk-menu" id="menuBulkKelas">
                            <button type="button" class="fi-ta-bulk-item is-danger" onclick="confirmBulkDeleteKelas()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                                <span>Hapus terpilih</span>
                            </button>
                            <div class="fi-ta-bulk-divider"></div>
                            <button type="button" class="fi-ta-bulk-item" onclick="clearSelection('kelas')">
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
                               id="searchKelas"
                               class="fi-ta-search-input"
                               placeholder="Cari kelas pengajian..."
                               oninput="onSearchInput('kelas')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchKelas" onclick="clearSearch('kelas')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button + Popover --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapKelas">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterKelas"
                                title="Filter Kelas Pengajian"
                                onclick="toggleDropdownMenu('popoverFilterKelas', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                            </svg>
                            <span class="fi-ta-filter-badge" id="badgeFilterKelas">0</span>
                        </button>

                        <div class="fi-ta-filter-popover" id="popoverFilterKelas" onclick="event.stopPropagation()">
                            <div class="fi-ta-filter-head">
                                <span class="fi-ta-filter-title">Filter</span>
                                <button type="button" class="fi-ta-filter-reset" onclick="resetFilter('kelas')">Reset</button>
                            </div>
                            <div class="fi-ta-filter-body">
                                <label class="fi-ta-filter-check-row">
                                    <input type="checkbox" id="fltKelasTerisi" class="fi-ta-checkbox">
                                    <span>Hanya kelas terisi santri</span>
                                </label>

                                <div class="fi-ta-filter-field">
                                    <label for="fltKelasJenjang" class="fi-ta-filter-label">Jenjang Target</label>
                                    <select id="fltKelasJenjang" class="fi-ta-filter-select">
                                        <option value="">Semua Jenjang</option>
                                        <option value="smp">SMP</option>
                                        <option value="sma">SMA</option>
                                        <option value="reguler">Reguler</option>
                                        <option value="umum">Umum</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fi-ta-filter-actions">
                                <button type="button" class="fi-ta-filter-apply" onclick="applyFilter('kelas')">
                                    Terapkan filter
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Kolom Toggle Button + Popover --}}
                    <div class="fi-ta-col-wrap" id="colWrapKelas">
                        <button type="button"
                                class="fi-ta-col-btn"
                                id="btnColsKelas"
                                title="Sesuaikan Kolom"
                                onclick="toggleDropdownMenu('popoverColsKelas', event)">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <rect x="3" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="8.4" y="3" width="3.2" height="14" rx="1.6" />
                                <rect x="13.8" y="3" width="3.2" height="14" rx="1.6" />
                            </svg>
                        </button>

                        <div class="fi-ta-col-popover" id="popoverColsKelas" onclick="event.stopPropagation()">
                            <div class="fi-ta-col-head">
                                <span class="fi-ta-col-title">Kolom</span>
                                <button type="button" class="fi-ta-col-reset" onclick="resetColumns('kelas')">Reset</button>
                            </div>
                            <div class="fi-ta-col-list" id="colListKelas"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selection Strip --}}
            <div class="fi-ta-selection-bar" id="selBarKelas">
                <span class="fi-ta-selection-count" id="selCountTextKelas">0 kelas dipilih</span>
                <div class="fi-ta-selection-links">
                    <button type="button" class="fi-ta-link-select-all" id="btnSelectAllMatchingKelas" onclick="selectAllMatching('kelas')">
                        Pilih semua {{ count($dataKelas) }}
                    </button>
                    <button type="button" class="fi-ta-link-deselect" onclick="clearSelection('kelas')">
                        Batalkan pilihan
                    </button>
                </div>
            </div>

            {{-- Table Content --}}
            <div class="fi-ta-table-wrap">
                <table class="fi-ta-table" id="tableKelas">
                    <thead>
                        <tr>
                            <th class="fi-ta-th-check">
                                <input type="checkbox" id="checkAllPageKelas" class="fi-ta-checkbox" onchange="togglePageSelection('kelas', this.checked)" title="Pilih halaman ini">
                            </th>
                            <th>
                                <button type="button" class="fi-ta-th-sort" onclick="sortTableBy('kelas', 'nama')">
                                    <span>Nama Kelas Pengajian</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th class="pk-th-jenjang">Jenjang</th>
                            <th class="pk-th-kode">Kode Kelas</th>
                            <th style="text-align: center;">
                                <button type="button" class="fi-ta-th-sort" onclick="sortTableBy('kelas', 'jumlah')">
                                    <span>Santri Terdaftar</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th style="text-align: right; padding-right: 20px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyKelas">
                        @forelse($dataKelas as $item)
                        @php
                            $jenjangLower = strtolower($item->jenjang ?? 'smp');
                            $badgeCode = strtoupper(substr($jenjangLower, 0, 3));
                        @endphp
                        <tr class="pk-row-kamar js-row-kelas"
                            data-id="{{ $item->id }}"
                            data-nama="{{ strtolower($item->nama_kelas) }}"
                            data-jenjang="{{ $jenjangLower }}"
                            data-kode="{{ strtolower($item->kode_kelas) }}"
                            data-jumlah="{{ $item->jumlah_siswa }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox js-cb-kelas"
                                       value="{{ $item->id }}"
                                       data-label="{{ $item->nama_kelas }}"
                                       data-jumlah="{{ $item->jumlah_siswa }}"
                                       onchange="onRowCheckboxChange('kelas')">
                            </td>
                            <td>
                                <div class="pk-kelas-cell-content">
                                    <div class="pk-tingkat-badge pk-jenjang-icon-{{ $jenjangLower }}" title="Jenjang {{ strtoupper($item->jenjang) }}">{{ $badgeCode }}</div>
                                    <div class="pk-kelas-name-wrap">
                                        <span class="pk-kelas-name">{{ $item->nama_kelas }}</span>
                                        <div class="pk-kelas-sub-mobile">
                                            Jenjang {{ strtoupper($item->jenjang) }} &bull; {{ $item->kode_kelas }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="pk-td-jenjang">
                                <span class="pk-jenjang-pill pk-jenjang-pill-{{ $jenjangLower }}">
                                    {{ strtoupper($item->jenjang) }}
                                </span>
                            </td>
                            <td class="pk-td-kode">
                                <span class="pk-kode-text">{{ $item->kode_kelas }}</span>
                            </td>
                            <td style="text-align: center;">
                                <span class="pk-siswa-count"
                                      onclick="openAnggotaModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}')"
                                      title="Klik untuk lihat & kelola santri di kelas ini">
                                    {{ $item->jumlah_siswa }} Santri
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 16px;">
                                <div class="fi-ta-row-actions">
                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-view pk-btn-eye"
                                            title="Lihat & Plotting Santri {{ $item->nama_kelas }}"
                                            onclick="openAnggotaModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span class="pk-act-label">Lihat</span>
                                    </button>

                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-edit"
                                            title="Edit {{ $item->nama_kelas }}"
                                            onclick="openEditKelasModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', '{{ addslashes($item->kode_kelas) }}', '{{ $item->jenjang }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span class="pk-act-label">Edit</span>
                                    </button>

                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-delete"
                                            title="Hapus {{ $item->nama_kelas }}"
                                            onclick="confirmDeleteKelas({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->jumlah_siswa }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        <span class="pk-act-label">Hapus</span>
                                    </button>

                                    <form id="formDeleteKelas_{{ $item->id }}" action="{{ route('plotting-pengajian.destroy-kelas', $item->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="6" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                <div style="margin-bottom: 10px;">Belum ada master kelas pengajian yang terdaftar di sistem.</div>
                                <button type="button" class="pk-btn-add-kelas" onclick="openTambahKelasModal()" style="display:inline-flex;">
                                    + Tambah Kelas Baru
                                </button>
                            </td>
                        </tr>
                        @endforelse
                        <tr id="emptyFilterRowKelas" style="display:none;">
                            <td colspan="6" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                Tidak ada data kelas yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Filament v4 Footer: Info + Per Halaman + Pagination --}}
            <div class="fi-ta-footer" id="footerKelas">
                <div class="fi-ta-footer-info" id="footerInfoKelas">Menampilkan 0 data</div>

                <div class="fi-ta-perpage">
                    <span class="fi-ta-perpage-label">Per halaman</span>
                    <select class="fi-ta-perpage-select" id="perPageKelas" onchange="changePerPage('kelas', this.value)">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="9999">Semua</option>
                    </select>
                </div>

                <div class="fi-ta-pages" id="pagesKelas"></div>
            </div>

        </div>
    </div>

    {{-- ======================================================================= --}}
    {{-- TAB 2: SANTRI BELUM TERDAFTAR (FILAMENT V4 TABLE CARD)                  --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">
        <div class="fi-ta-ctn" id="fiCardBelum">

            {{-- Top Toolbar --}}
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
                            <button type="button" class="fi-ta-bulk-item is-primary" onclick="openMutasiBulkBelum()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                                <span>Tetapkan Kelas Terpilih</span>
                            </button>
                            <div class="fi-ta-bulk-divider"></div>
                            <button type="button" class="fi-ta-bulk-item" onclick="clearSelection('belum')">
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
                               id="searchBelum"
                               class="fi-ta-search-input"
                               placeholder="Cari nama santri, NISN, atau kamar..."
                               oninput="onSearchInput('belum')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchBelum" onclick="clearSearch('belum')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button + Popover --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapBelum">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterBelum"
                                title="Filter Santri Belum Terdaftar"
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
                                    <label for="fltBelumGender" class="fi-ta-filter-label">Jenis Kelamin (Gender)</label>
                                    <select id="fltBelumGender" class="fi-ta-filter-select">
                                        <option value="">Semua Gender</option>
                                        <option value="putra">Putra (Laki-laki)</option>
                                        <option value="putri">Putri (Perempuan)</option>
                                    </select>
                                </div>

                                <div class="fi-ta-filter-field">
                                    <label for="fltBelumJenjang" class="fi-ta-filter-label">Jenjang Sekolah</label>
                                    <select id="fltBelumJenjang" class="fi-ta-filter-select">
                                        <option value="">Semua Jenjang</option>
                                        <option value="smp">SMP</option>
                                        <option value="sma">SMA</option>
                                        <option value="reguler">Reguler (Pondok)</option>
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

                    {{-- Custom Kolom Toggle Button + Popover --}}
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

            {{-- Selection Strip (Bulk Action Bar) --}}
            <div class="fi-ta-selection-bar" id="selBarBelum">
                <span class="fi-ta-selection-count" id="selCountTextBelum">0 santri dipilih</span>
                <div class="fi-ta-selection-links">
                    <button type="button" class="fi-ta-link-select-all" id="btnSelectAllMatchingBelum" onclick="selectAllMatching('belum')">
                        Pilih semua {{ $belumTerdaftar->count() }}
                    </button>
                    <button type="button" class="fi-ta-link-deselect" onclick="clearSelection('belum')">
                        Batalkan pilihan
                    </button>
                </div>
            </div>

            {{-- Table Content --}}
            <div class="fi-ta-table-wrap">
                <table class="fi-ta-table" id="tableBelum">
                    <thead>
                        <tr>
                            <th class="fi-ta-th-check">
                                <input type="checkbox" id="checkAllPageBelum" class="fi-ta-checkbox" onchange="togglePageSelection('belum', this.checked)" title="Pilih halaman ini">
                            </th>
                            <th>
                                <button type="button" class="fi-ta-th-sort" onclick="sortTableBy('belum', 'nama')">
                                    <span>Nama Santri</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th class="pk-th-jenjang">Jenjang / Kelas</th>
                            <th class="pk-th-nisn">NISN</th>
                            <th class="pk-th-gender">Gender</th>
                            <th class="pk-th-kamar">Kamar</th>
                            <th>Status</th>
                            <th style="text-align: right; padding-right: 20px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBelum">
                        @forelse($belumTerdaftar as $siswa)
                        @php
                            $jenjangSiswa = $siswa->jenjang ?? ($siswa->pendaftaran?->jenjang ?? 'Reguler');
                            $jenjangLower = strtolower($jenjangSiswa);
                            $gender = strtolower((string)($siswa->pendaftaran?->jenis_kelamin ?? ''));
                            $isPutri = in_array($gender, ['p', 'perempuan', 'putri']);
                            $genderLabel = $isPutri ? 'putri' : 'putra';
                        @endphp
                        <tr class="pk-row-siswa js-row-belum"
                            data-id="{{ $siswa->id }}"
                            data-nama="{{ strtolower($siswa->nama_lengkap) }}"
                            data-siswa-id="{{ $siswa->id }}"
                            data-siswa-nama="{{ $siswa->nama_lengkap }}"
                            data-gender="{{ $genderLabel }}"
                            data-jenjang="{{ $jenjangLower }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox js-cb-belum"
                                       value="{{ $siswa->id }}"
                                       data-label="{{ $siswa->nama_lengkap }}"
                                       onchange="onRowCheckboxChange('belum')">
                            </td>
                            <td>
                                <span class="pk-nama-text">{{ $siswa->nama_lengkap }}</span>
                                <div class="pk-nisn-sub-mobile">
                                    {{ $jenjangSiswa }}{{ $siswa->kelas ? ' (' . $siswa->kelas . ')' : '' }} &bull; NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }} &bull; {{ $siswa->kamar ?: 'Belum berkamar' }}
                                </div>
                            </td>
                            <td class="pk-td-jenjang">
                                <span class="pk-jenjang-badge pk-jenjang-{{ $jenjangLower }}">
                                    {{ $jenjangSiswa }}{{ $siswa->kelas && $siswa->kelas !== '-' ? ' • ' . $siswa->kelas : '' }}
                                </span>
                            </td>
                            <td class="pk-td-nisn">
                                <span class="pk-nisn-text">{{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</span>
                            </td>
                            <td class="pk-td-gender">
                                @if($isPutri)
                                    <span class="pk-asrama-pill pk-asrama-pill-putri" style="font-size:11px; padding:2px 7px;">Putri (P)</span>
                                @else
                                    <span class="pk-asrama-pill pk-asrama-pill-putra" style="font-size:11px; padding:2px 7px;">Putra (L)</span>
                                @endif
                            </td>
                            <td class="pk-td-kamar">
                                <span style="font-size:12.5px; color:#4B5563;">{{ $siswa->kamar ?: '-' }}</span>
                            </td>
                            <td>
                                <span class="pk-status-pill">Belum Diplot</span>
                            </td>
                            <td style="text-align: right; padding-right: 16px;">
                                <button type="button"
                                        class="pk-btn-mutasi-row"
                                        onclick="openMutasiSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')"
                                        title="Tempatkan ke Kelas Pengajian">
                                    <span>Pilih Kelas</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="8" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                Seluruh santri aktif telah ditempatkan ke kelas pengajian masing-masing.
                            </td>
                        </tr>
                        @endforelse
                        <tr id="emptyFilterRowBelum" style="display:none;">
                            <td colspan="8" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                Tidak ada data santri yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Filament v4 Footer --}}
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

{{-- ======================================================================= --}}
{{-- MODAL 1: LIHAT & KELOLA ANGGOTA KELAS PENGAJIAN (WITH FILAMENT V4 TOOLBAR) --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalAnggota" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <div class="pk-modal-header">
                <div>
                    <h3 class="pk-modal-title" id="modalAnggotaTitle">Santri Kelas Pengajian</h3>
                    <span class="pk-modal-subtitle" id="modalAnggotaSubtitle">Daftar santri terdaftar</span>
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
                                    <button type="button" class="fi-ta-bulk-item is-primary" onclick="openMutasiBulkModal()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                        <span>Pindah Kelas Terpilih</span>
                                    </button>
                                    <button type="button" class="fi-ta-bulk-item is-danger" onclick="unassignBulkModal()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        <span>Keluarkan dari Kelas</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="fi-ta-toolbar-right">
                            <div class="fi-ta-search-wrap" style="width: 220px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="fi-ta-search-icon">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <input type="text"
                                       id="modalAnggotaSearch"
                                       class="fi-ta-search-input"
                                       placeholder="Cari santri di kelas ini..."
                                       oninput="filterModalAnggotaRows()">
                            </div>
                        </div>
                    </div>

                    <div class="pk-modal-table-wrap" style="border:none; border-radius: 0 0 12px 12px; max-height: 48vh; overflow-y:auto;">
                        <table class="fi-ta-table">
                            <thead>
                                <tr>
                                    <th class="fi-ta-th-check" style="width:40px;">
                                        <input type="checkbox" id="checkAllModalAnggota" class="fi-ta-checkbox" onchange="toggleModalPageSelection(this.checked)">
                                    </th>
                                    <th>Nama Santri</th>
                                    <th>NISN</th>
                                    <th>Gender</th>
                                    <th>Kamar</th>
                                    <th style="text-align: right; padding-right:16px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="modalAnggotaBody">
                                <tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data...</td></tr>
                            </tbody>
                        </table>
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
{{-- MODAL 2: PILIH KELAS PENGAJIAN TUJUAN (MUTASI TUNGGAL / MASSAL)         --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalMutasi" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-pengajian.mutasi') }}" method="POST" id="formMutasi">
                @csrf
                <input type="hidden" name="siswa_id" id="mutasiSiswaId">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tempatkan ke Kelas Pengajian</h3>
                        <span class="pk-modal-subtitle" id="mutasiSiswaNama">-</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeMutasiModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="selectKelasTujuan" class="pk-form-label">Pilih Kelas Pengajian Tujuan <span style="color:#EF4444;">*</span></label>
                        <select name="kelas_pengajian_id" id="selectKelasTujuan" class="pk-form-select" required>
                            <option value="">-- Pilih Kelas Pengajian --</option>
                            @foreach($dataKelas as $kItem)
                                <option value="{{ $kItem->id }}">
                                    {{ $kItem->nama_kelas }} ({{ strtoupper($kItem->jenjang) }} • {{ $kItem->jumlah_siswa }} Santri)
                                </option>
                            @endforeach
                            <option value="unassign">-- Keluarkan / Kosongkan Kelas --</option>
                        </select>
                        <span class="pk-form-hint">Jika kelas pengajian belum ada, silakan tambahkan terlebih dahulu melalui tombol <strong>+ Tambah Kelas</strong>.</span>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeMutasiModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Penempatan</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- ======================================================================= --}}
{{-- MODAL 3: TAMBAH MASTER KELAS PENGAJIAN BARU                            --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalTambahKelas" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-pengajian.store-kelas') }}" method="POST" id="formTambahKelas">
                @csrf

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Kelas Pengajian Baru</h3>
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
                               placeholder="Contoh: Kelas Lambatan SMP, Kelas Cepatan SMP"
                               required
                               autocomplete="off">
                        <span class="pk-form-hint">Nama kelas pengajian otomatis terhubung ke data santri.</span>
                    </div>

                    <div class="pk-form-row-2">
                        <div class="pk-form-group">
                            <label for="selectJenjang" class="pk-form-label">Jenjang Target <span style="color:#EF4444;">*</span></label>
                            <select name="jenjang" id="selectJenjang" class="pk-form-select" required>
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
                                   placeholder="Misal: KLS_LAMBATAN_SMP">
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
{{-- MODAL 4: EDIT MASTER KELAS PENGAJIAN                                    --}}
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
                        <span class="pk-modal-subtitle">Perbarui identitas kelas</span>
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
                            <label for="editJenjang" class="pk-form-label">Jenjang Target <span style="color:#EF4444;">*</span></label>
                            <select name="jenjang" id="editJenjang" class="pk-form-select" required>
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
                                   required>
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
{{-- HIDDEN FORMS: BULK DELETE KELAS & UNASSIGN                              --}}
{{-- ======================================================================= --}}
<form action="{{ route('plotting-pengajian.destroy-bulk-kelas') }}" method="POST" id="formBulkDeleteKelas" style="display:none;">
    @csrf
    <input type="hidden" name="kelas_ids" id="bulkDeleteKelasIds">
</form>

<form action="{{ route('plotting-pengajian.unassign') }}" method="POST" id="formUnassign" style="display:none;">
    @csrf
    <input type="hidden" name="siswa_id" id="unassignSiswaId">
</form>

{{-- ======================================================================= --}}
{{-- GAMBAR 5 CENTERED CONFIRMATION MODAL                                    --}}
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
.pk-tab-item:hover { color: #111827; background: #F9FAFB; }
.dark-mode .pk-tab-item:hover { color: #F8FAFC; background: rgba(255,255,255,0.05); }
.pk-tab-item.active { background: #0052FF; color: #FFFFFF; box-shadow: 0 1px 3px rgba(0, 82, 255, 0.25); }
.pk-tab-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px;
    font-size: 11px; font-weight: 700; background: #F3F4F6; color: #4B5563;
}
.pk-tab-item.active .pk-tab-badge { background: rgba(255,255,255,0.22); color: #FFFFFF; }
.pk-tab-badge.pk-badge-notice { background: #FEE2E2; color: #DC2626; }
.pk-tab-item.active .pk-tab-badge.pk-badge-notice { background: #EF4444; color: #FFFFFF; }

.pk-tab-pane { display: none; }
.pk-tab-pane.active { display: block; }

.pk-kelas-cell-content { display: flex; align-items: center; gap: 11px; }
.pk-tingkat-badge {
    width: 32px; height: 32px; border-radius: 8px; display: inline-flex;
    align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; flex-shrink: 0;
    background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0,82,255,0.16);
}
.pk-jenjang-icon-sma { background: #F5F3FF; color: #7C3AED; border-color: #DDD6FE; }
.pk-jenjang-icon-reguler { background: #ECFDF5; color: #059669; border-color: #A7F3D0; }
.pk-jenjang-icon-umum { background: #FFFBEB; color: #D97706; border-color: #FDE68A; }

.pk-kelas-name { font-weight: 600; color: #111827; font-size: 13.5px; }
.dark-mode .pk-kelas-name { color: #F8FAFC; }

.pk-jenjang-pill {
    display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 6px;
    font-size: 11.5px; font-weight: 600; background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0,82,255,0.16);
}
.pk-jenjang-pill-sma { background: #F5F3FF; color: #7C3AED; border-color: #DDD6FE; }
.pk-jenjang-pill-reguler { background: #ECFDF5; color: #047857; border-color: #A7F3D0; }
.pk-jenjang-pill-umum { background: #FFFBEB; color: #B45309; border-color: #FDE68A; }

.pk-kode-text { font-family: monospace; font-size: 12.5px; color: #4B5563; }
.dark-mode .pk-kode-text { color: #94A3B8; }

.pk-siswa-count {
    display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px;
    font-size: 12px; font-weight: 600; background: #ECFDF5; color: #059669;
    border: 1px solid rgba(16,185,129,0.22); cursor: pointer; transition: all 0.14s ease;
}
.pk-siswa-count:hover { background: #D1FAE5; }

.pk-nama-text { font-weight: 600; color: #111827; }
.dark-mode .pk-nama-text { color: #F8FAFC; }
.pk-nisn-text { font-family: monospace; font-size: 12.5px; color: #6B7280; }

.pk-jenjang-badge {
    display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 6px;
    font-size: 12px; font-weight: 600; background: #F3F4F6; color: #374151;
}
.pk-jenjang-smp { background: #EFF6FF; color: #1D4ED8; }
.pk-jenjang-sma { background: #F5F3FF; color: #6D28D9; }
.pk-jenjang-reguler { background: #ECFDF5; color: #047857; }

.pk-status-pill {
    display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 6px;
    font-size: 12px; font-weight: 600; background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A;
}

.pk-btn-mutasi-row {
    display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px;
    border-radius: 7px; background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0,82,255,0.2);
    font-size: 12.5px; font-weight: 600; cursor: pointer; transition: all 0.14s ease;
}
.pk-btn-mutasi-row:hover { background: #0052FF; color: #FFFFFF; }
.pk-btn-mutasi-row svg { width: 14px; height: 14px; }

/* Modals */
.pk-modal-backdrop {
    position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px);
    z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px;
}
.pk-modal-dialog { width: 100%; max-width: 720px; max-height: 88vh; display: flex; flex-direction: column; }
.pk-modal-dialog-sm { max-width: 480px; }
.pk-modal-content {
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18); display: flex; flex-direction: column;
}
#modalAnggota .pk-modal-content { overflow: hidden; }
.pk-modal-dialog-sm .pk-modal-content { overflow: visible; }
.pk-modal-dialog-sm .pk-modal-body { overflow: visible; min-height: 140px; }
.dark-mode .pk-modal-content { background: #141C28; border-color: #263346; }
.pk-modal-header {
    display: flex; align-items: center; justify-content: space-between; padding: 16px 20px;
    border-bottom: 1px solid #F3F4F6; border-top-left-radius: 14px; border-top-right-radius: 14px;
}
.dark-mode .pk-modal-header { border-bottom-color: #1F2937; }
.pk-modal-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
.dark-mode .pk-modal-title { color: #F8FAFC; }
.pk-modal-subtitle { font-size: 12px; color: #6B7280; }
.pk-modal-close {
    background: transparent; border: none; font-size: 22px; color: #9CA3AF; cursor: pointer; line-height: 1;
}
.pk-modal-close:hover { color: #111827; }
.pk-modal-body { padding: 18px 20px; display: flex; flex-direction: column; gap: 14px; }
.pk-modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 20px;
    background: #F9FAFB; border-top: 1px solid #F3F4F6; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;
}
.dark-mode .pk-modal-footer { background: #101622; border-top-color: #1F2937; }
.pk-btn-clean {
    padding: 7px 14px; background: transparent; border: 1px solid #E5E7EB; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #4B5563; cursor: pointer;
}
.pk-btn-clean:hover { background: #F3F4F6; }
.pk-btn-primary {
    padding: 7px 16px; background: #0052FF; border: 1px solid #0052FF; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: #FFFFFF; cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 82, 255, 0.25);
}
.pk-btn-primary:hover { background: #0043D4; }

.pk-form-group { display: flex; flex-direction: column; gap: 5px; }
.pk-form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.pk-form-label { font-size: 12.5px; font-weight: 600; color: #374151; }
.dark-mode .pk-form-label { color: #D1D5DB; }
.pk-form-input, .pk-form-select {
    width: 100%; padding: 8px 12px; border: 1px solid #D1D5DB; border-radius: 8px;
    font-size: 13.5px; background: #FFFFFF; color: #111827; outline: none; transition: border-color 0.15s ease;
}
.pk-form-input:focus, .pk-form-select:focus { border-color: #0052FF; }
.dark-mode .pk-form-input, .dark-mode .pk-form-select {
    background: #1E293B; border-color: #334155; color: #F8FAFC;
}
.pk-form-hint { font-size: 11.5px; color: #6B7280; line-height: 1.4; }

.pk-kelas-sub-mobile, .pk-nisn-sub-mobile { display: none; }
@media (max-width: 768px) {
    .pk-th-jenjang, .pk-td-jenjang,
    .pk-th-kode, .pk-td-kode,
    .pk-th-nisn, .pk-td-nisn,
    .pk-th-gender, .pk-td-gender,
    .pk-th-kamar, .pk-td-kamar {
        display: none !important;
    }
    .pk-kelas-sub-mobile, .pk-nisn-sub-mobile {
        display: block !important;
        font-size: 11.5px; color: #6B7280; margin-top: 2px;
    }
}
</style>

@endsection

@push('scripts')
<script>
// ==========================================
// STATE MANAGEMENT & TABLE CONTROLLER
// ==========================================
const tableState = {
    kelas: {
        page: 1,
        perPage: 10,
        search: '',
        onlyFilled: false,
        jenjang: '',
        sortBy: 'nama',
        sortAsc: true,
        rowSelector: '.js-row-kelas',
        cbSelector: '.js-cb-kelas',
        emptyRowId: 'emptyFilterRowKelas',
        checkAllPageId: 'checkAllPageKelas',
        btnBulkId: 'btnBulkKelas',
        bulkCountBadgeId: 'bulkCountBadgeKelas',
        selBarId: 'selBarKelas',
        selCountTextId: 'selCountTextKelas',
        btnSelectAllMatchingId: 'btnSelectAllMatchingKelas',
        footerInfoId: 'footerInfoKelas',
        pagesId: 'pagesKelas',
        unitLabel: 'kelas'
    },
    belum: {
        page: 1,
        perPage: 10,
        search: '',
        gender: '',
        jenjang: '',
        sortBy: 'nama',
        sortAsc: true,
        rowSelector: '.js-row-belum',
        cbSelector: '.js-cb-belum',
        emptyRowId: 'emptyFilterRowBelum',
        checkAllPageId: 'checkAllPageBelum',
        btnBulkId: 'btnBulkBelum',
        bulkCountBadgeId: 'bulkCountBadgeBelum',
        selBarId: 'selBarBelum',
        selCountTextId: 'selCountTextBelum',
        btnSelectAllMatchingId: 'btnSelectAllMatchingBelum',
        footerInfoId: 'footerInfoBelum',
        pagesId: 'pagesBelum',
        unitLabel: 'santri'
    }
};

function switchTab(tab) {
    document.querySelectorAll('.pk-tab-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pk-tab-pane').forEach(p => p.classList.remove('active'));

    if (tab === 'kelas') {
        document.getElementById('tabBtnKelas').classList.add('active');
        document.getElementById('paneKelas').classList.add('active');
    } else {
        document.getElementById('tabBtnBelum').classList.add('active');
        document.getElementById('paneBelum').classList.add('active');
    }

    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}

function closeAllPopovers() {
    document.querySelectorAll('.fi-ta-bulk-menu.is-open, .fi-ta-filter-popover.is-open, .fi-ta-col-popover.is-open').forEach(el => {
        el.classList.remove('is-open');
    });
}

function toggleDropdownMenu(menuId, event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById(menuId);
    if (!menu) return;
    const wasOpen = menu.classList.contains('is-open');
    closeAllPopovers();
    if (!wasOpen) menu.classList.add('is-open');
}

document.addEventListener('click', function() {
    closeAllPopovers();
});

function getMatchingRows(key) {
    const st = tableState[key];
    const allRows = Array.from(document.querySelectorAll(st.rowSelector));
    return allRows.filter(tr => {
        const text = tr.innerText.toLowerCase();
        const matchSearch = !st.search || text.includes(st.search);
        if (!matchSearch) return false;

        if (key === 'kelas') {
            if (st.onlyFilled && parseInt(tr.dataset.jumlah || '0', 10) <= 0) return false;
            if (st.jenjang && (tr.dataset.jenjang || '') !== st.jenjang) return false;
        } else if (key === 'belum') {
            if (st.gender && (tr.dataset.gender || '') !== st.gender) return false;
            if (st.jenjang && (tr.dataset.jenjang || '') !== st.jenjang) return false;
        }
        return true;
    });
}

function renderTable(key) {
    const st = tableState[key];
    const allRows = Array.from(document.querySelectorAll(st.rowSelector));
    const matching = getMatchingRows(key);
    const total = matching.length;
    const totalPages = Math.max(1, Math.ceil(total / st.perPage));
    if (st.page > totalPages) st.page = totalPages;

    const startIdx = (st.page - 1) * st.perPage;
    const endIdx = Math.min(startIdx + st.perPage, total);
    const visibleSet = new Set(matching.slice(startIdx, endIdx));

    allRows.forEach(tr => {
        tr.style.display = visibleSet.has(tr) ? '' : 'none';
    });

    const emptyRow = document.getElementById(st.emptyRowId);
    if (emptyRow) {
        emptyRow.style.display = (allRows.length > 0 && total === 0) ? '' : 'none';
    }

    const infoEl = document.getElementById(st.footerInfoId);
    if (infoEl) {
        if (total === 0) {
            infoEl.textContent = 'Menampilkan 0 data';
        } else {
            infoEl.textContent = `Menampilkan ${startIdx + 1} sampai ${endIdx} dari ${total} data`;
        }
    }

    renderPaginationButtons(key, totalPages);
    syncSelectionUI(key, matching, Array.from(visibleSet));
}

function renderPaginationButtons(key, totalPages) {
    const st = tableState[key];
    const container = document.getElementById(st.pagesId);
    if (!container) return;

    if (totalPages <= 1) {
        container.style.display = 'none';
        container.innerHTML = '';
        return;
    }

    container.style.display = 'inline-flex';
    let html = `<button type="button" class="fi-ta-page-btn" ${st.page <= 1 ? 'disabled' : ''} onclick="goToPage('${key}', ${st.page - 1})">&lsaquo;</button>`;
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - st.page) <= 1) {
            html += `<button type="button" class="fi-ta-page-btn ${p === st.page ? 'is-active' : ''}" onclick="goToPage('${key}', ${p})">${p}</button>`;
        } else if (Math.abs(p - st.page) === 2) {
            html += `<button type="button" class="fi-ta-page-btn" disabled>&hellip;</button>`;
        }
    }
    html += `<button type="button" class="fi-ta-page-btn" ${st.page >= totalPages ? 'disabled' : ''} onclick="goToPage('${key}', ${st.page + 1})">&rsaquo;</button>`;
    container.innerHTML = html;
}

function goToPage(key, page) {
    tableState[key].page = page;
    renderTable(key);
}

function changePerPage(key, val) {
    tableState[key].perPage = parseInt(val, 10) || 10;
    tableState[key].page = 1;
    renderTable(key);
}

function onSearchInput(key) {
    const input = document.getElementById(key === 'kelas' ? 'searchKelas' : 'searchBelum');
    const clearBtn = document.getElementById(key === 'kelas' ? 'clearSearchKelas' : 'clearSearchBelum');
    const val = (input?.value || '').toLowerCase().trim();
    if (clearBtn) clearBtn.classList.toggle('is-visible', val.length > 0);
    tableState[key].search = val;
    tableState[key].page = 1;
    renderTable(key);
}

function clearSearch(key) {
    const input = document.getElementById(key === 'kelas' ? 'searchKelas' : 'searchBelum');
    if (input) input.value = '';
    onSearchInput(key);
}

function applyFilter(key) {
    if (key === 'kelas') {
        const onlyFilled = document.getElementById('fltKelasTerisi')?.checked || false;
        const jenjang = (document.getElementById('fltKelasJenjang')?.value || '').toLowerCase();
        tableState.kelas.onlyFilled = onlyFilled;
        tableState.kelas.jenjang = jenjang;

        let activeCount = 0;
        if (onlyFilled) activeCount++;
        if (jenjang) activeCount++;
        const badge = document.getElementById('badgeFilterKelas');
        if (badge) {
            badge.textContent = activeCount;
            badge.classList.toggle('is-active', activeCount > 0);
        }
    } else {
        const gender = (document.getElementById('fltBelumGender')?.value || '').toLowerCase();
        const jenjang = (document.getElementById('fltBelumJenjang')?.value || '').toLowerCase();
        tableState.belum.gender = gender;
        tableState.belum.jenjang = jenjang;

        let activeCount = 0;
        if (gender) activeCount++;
        if (jenjang) activeCount++;
        const badge = document.getElementById('badgeFilterBelum');
        if (badge) {
            badge.textContent = activeCount;
            badge.classList.toggle('is-active', activeCount > 0);
        }
    }

    tableState[key].page = 1;
    closeAllPopovers();
    renderTable(key);
}

function resetFilter(key) {
    if (key === 'kelas') {
        if (document.getElementById('fltKelasTerisi')) document.getElementById('fltKelasTerisi').checked = false;
        if (document.getElementById('fltKelasJenjang')) document.getElementById('fltKelasJenjang').value = '';
        tableState.kelas.onlyFilled = false;
        tableState.kelas.jenjang = '';
        const badge = document.getElementById('badgeFilterKelas');
        if (badge) { badge.textContent = '0'; badge.classList.remove('is-active'); }
    } else {
        if (document.getElementById('fltBelumGender')) document.getElementById('fltBelumGender').value = '';
        if (document.getElementById('fltBelumJenjang')) document.getElementById('fltBelumJenjang').value = '';
        tableState.belum.gender = '';
        tableState.belum.jenjang = '';
        const badge = document.getElementById('badgeFilterBelum');
        if (badge) { badge.textContent = '0'; badge.classList.remove('is-active'); }
    }

    tableState[key].page = 1;
    closeAllPopovers();
    renderTable(key);
}

function sortTableBy(key, field) {
    const st = tableState[key];
    if (st.sortBy === field) {
        st.sortAsc = !st.sortAsc;
    } else {
        st.sortBy = field;
        st.sortAsc = true;
    }

    const tbody = document.getElementById(key === 'kelas' ? 'tbodyKelas' : 'tbodyBelum');
    const rows = Array.from(tbody.querySelectorAll(st.rowSelector));

    rows.sort((a, b) => {
        let valA, valB;
        if (field === 'jumlah') {
            valA = parseInt(a.dataset.jumlah || '0', 10);
            valB = parseInt(b.dataset.jumlah || '0', 10);
            return st.sortAsc ? (valA - valB) : (valB - valA);
        } else {
            valA = (a.dataset.nama || '').toLowerCase();
            valB = (b.dataset.nama || '').toLowerCase();
            return st.sortAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
        }
    });

    rows.forEach(r => tbody.appendChild(r));
    renderTable(key);
}

// ==========================================
// SELECTION & BULK ACTIONS
// ==========================================
function togglePageSelection(key, checked) {
    const st = tableState[key];
    const visibleRows = Array.from(document.querySelectorAll(st.rowSelector)).filter(r => r.style.display !== 'none');
    visibleRows.forEach(r => {
        const cb = r.querySelector(st.cbSelector);
        if (cb) cb.checked = checked;
    });
    syncSelectionUI(key, getMatchingRows(key), visibleRows);
}

function selectAllMatching(key) {
    const st = tableState[key];
    const matching = getMatchingRows(key);
    matching.forEach(r => {
        const cb = r.querySelector(st.cbSelector);
        if (cb) cb.checked = true;
    });
    const visible = matching.filter(r => r.style.display !== 'none');
    syncSelectionUI(key, matching, visible);
}

function clearSelection(key) {
    const st = tableState[key];
    document.querySelectorAll(st.cbSelector).forEach(cb => { cb.checked = false; });
    const matching = getMatchingRows(key);
    const visible = matching.filter(r => r.style.display !== 'none');
    syncSelectionUI(key, matching, visible);
    closeAllPopovers();
}

function onRowCheckboxChange(key) {
    const st = tableState[key];
    const matching = getMatchingRows(key);
    const visible = matching.filter(r => r.style.display !== 'none');
    syncSelectionUI(key, matching, visible);
}

function syncSelectionUI(key, matchingRows, visibleRows) {
    const st = tableState[key];
    const allCbs = Array.from(document.querySelectorAll(st.cbSelector));
    allCbs.forEach(cb => {
        const tr = cb.closest('tr');
        if (tr) tr.classList.toggle('is-selected', cb.checked);
    });

    const selectedCbs = allCbs.filter(cb => cb.checked);
    const count = selectedCbs.length;

    const btnBulk = document.getElementById(st.btnBulkId);
    const badge = document.getElementById(st.bulkCountBadgeId);
    if (btnBulk) {
        btnBulk.disabled = count === 0;
        btnBulk.classList.toggle('is-active-selection', count > 0);
    }
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    const selBar = document.getElementById(st.selBarId);
    const selText = document.getElementById(st.selCountTextId);
    const btnSelectAll = document.getElementById(st.btnSelectAllMatchingId);
    if (selBar) selBar.classList.toggle('is-visible', count > 0);
    if (selText) selText.textContent = `${count} ${st.unitLabel} dipilih`;
    if (btnSelectAll) {
        btnSelectAll.textContent = `Pilih semua ${matchingRows.length}`;
        btnSelectAll.style.display = count < matchingRows.length ? 'inline-block' : 'none';
    }

    const checkAllPage = document.getElementById(st.checkAllPageId);
    if (checkAllPage) {
        const visibleCbs = visibleRows.map(r => r.querySelector(st.cbSelector)).filter(Boolean);
        checkAllPage.checked = visibleCbs.length > 0 && visibleCbs.every(cb => cb.checked);
    }
}

// ==========================================
// CENTERED CONFIRMATION MODAL
// ==========================================
let currentFiConfirmCallback = null;

function openFiConfirm(opts) {
    closeAllPopovers();
    const modal = document.getElementById('fiConfirmModal');
    const icon = document.getElementById('fiConfirmIcon');
    const title = document.getElementById('fiConfirmTitle');
    const desc = document.getElementById('fiConfirmDesc');
    const cancelBtn = document.getElementById('fiConfirmCancelBtn');
    const submitBtn = document.getElementById('fiConfirmSubmitBtn');
    const actions = document.getElementById('fiConfirmActions');

    const isPrimary = opts.variant === 'primary';
    icon.className = 'fi-confirm-icon ' + (isPrimary ? 'is-primary' : 'is-danger');
    icon.innerHTML = isPrimary
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4m0 4h.01"></path></svg>`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>`;

    title.textContent = opts.title || 'Konfirmasi';
    desc.textContent = opts.desc || 'Apakah Anda yakin ingin melakukan tindakan ini?';

    if (opts.infoOnly) {
        actions.style.gridTemplateColumns = '1fr';
        cancelBtn.style.display = 'none';
        submitBtn.textContent = 'Mengerti';
        submitBtn.className = 'fi-confirm-btn-submit is-primary';
        currentFiConfirmCallback = function() { closeFiConfirm(); };
    } else {
        actions.style.gridTemplateColumns = '1fr 1fr';
        cancelBtn.style.display = '';
        submitBtn.textContent = opts.confirmText || 'Hapus';
        submitBtn.className = 'fi-confirm-btn-submit' + (isPrimary ? ' is-primary' : '');
        currentFiConfirmCallback = opts.onConfirm || null;
    }

    submitBtn.onclick = function() {
        const cb = currentFiConfirmCallback;
        closeFiConfirm();
        if (typeof cb === 'function') cb();
    };

    modal.classList.add('is-open');
}

function closeFiConfirm() {
    document.getElementById('fiConfirmModal')?.classList.remove('is-open');
}

function confirmDeleteKelas(id, nama, santriCount) {
    if (santriCount > 0) {
        openFiConfirm({
            variant: 'danger',
            infoOnly: true,
            title: 'Tidak Dapat Menghapus Kelas',
            desc: `Kelas "${nama}" saat ini masih memiliki ${santriCount} santri aktif. Silakan pindahkan santri ke kelas lain terlebih dahulu sebelum menghapus kelas ini.`
        });
        return;
    }

    openFiConfirm({
        variant: 'danger',
        title: `Hapus Kelas "${nama}"`,
        desc: `Apakah Anda yakin ingin menghapus kelas "${nama}"? Tindakan ini tidak dapat dibatalkan.`,
        confirmText: 'Hapus',
        onConfirm: function() {
            document.getElementById('formDeleteKelas_' + id).submit();
        }
    });
}

function confirmBulkDeleteKelas() {
    const selected = Array.from(document.querySelectorAll('.js-cb-kelas:checked'));
    if (selected.length === 0) return;

    openFiConfirm({
        variant: 'danger',
        title: `Hapus ${selected.length} kelas terpilih`,
        desc: 'Apakah Anda yakin ingin menghapus kelas yang dipilih? Kelas yang masih memiliki santri aktif akan otomatis dilewati.',
        confirmText: 'Hapus',
        onConfirm: function() {
            document.getElementById('bulkDeleteKelasIds').value = selected.map(cb => cb.value).join(',');
            document.getElementById('formBulkDeleteKelas').submit();
        }
    });
}

function openMutasiBulkBelum() {
    closeAllPopovers();
    const checkedBoxes = Array.from(document.querySelectorAll('.js-cb-belum:checked'));
    if (checkedBoxes.length === 0) return;

    const ids = checkedBoxes.map(cb => cb.value).join(',');
    const label = checkedBoxes.length === 1
        ? checkedBoxes[0].dataset.label
        : `${checkedBoxes.length} Santri Terpilih`;

    document.getElementById('mutasiSiswaId').value = ids;
    document.getElementById('mutasiSiswaNama').textContent = label;

    const sel = document.getElementById('selectKelasTujuan');
    if (sel) {
        sel.value = '';
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (window.closeAllFiSelects) window.closeAllFiSelects();

    document.getElementById('modalMutasi').style.display = 'flex';
}

function openMutasiSingleModal(siswaId, namaSantri) {
    document.getElementById('mutasiSiswaId').value = siswaId;
    document.getElementById('mutasiSiswaNama').textContent = namaSantri;

    const sel = document.getElementById('selectKelasTujuan');
    if (sel) {
        sel.value = '';
        sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (window.closeAllFiSelects) window.closeAllFiSelects();

    document.getElementById('modalMutasi').style.display = 'flex';
}

function closeMutasiModal() {
    document.getElementById('modalMutasi').style.display = 'none';
    if (window.closeAllFiSelects) window.closeAllFiSelects();
}

function openTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'flex';
}

function closeTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'none';
    if (window.closeAllFiSelects) window.closeAllFiSelects();
}

function openEditKelasModal(id, nama, kode, jenjang) {
    const form = document.getElementById('formEditKelas');
    form.action = `/plotting-pengajian/kelas/${id}`;
    document.getElementById('editNamaKelas').value = nama;
    document.getElementById('editKodeKelas').value = kode;
    document.getElementById('editJenjang').value = jenjang;
    document.getElementById('editJenjang').dispatchEvent(new Event('change', { bubbles: true }));
    if (window.closeAllFiSelects) window.closeAllFiSelects();
    document.getElementById('modalEditKelas').style.display = 'flex';
}

function closeEditKelasModal() {
    document.getElementById('modalEditKelas').style.display = 'none';
    if (window.closeAllFiSelects) window.closeAllFiSelects();
}

// ==========================================
// MODAL ANGGOTA KELAS PENGAJIAN
// ==========================================
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openAnggotaModal(kelasId, namaKelas) {
    const modal = document.getElementById('modalAnggota');
    const title = document.getElementById('modalAnggotaTitle');
    const subtitle = document.getElementById('modalAnggotaSubtitle');
    const tbody = document.getElementById('modalAnggotaBody');
    const searchInput = document.getElementById('modalAnggotaSearch');
    const checkAll = document.getElementById('checkAllModalAnggota');

    title.textContent = `Santri ${namaKelas}`;
    subtitle.textContent = 'Memuat daftar santri...';
    if (searchInput) searchInput.value = '';
    if (checkAll) checkAll.checked = false;
    updateModalSelectionState();
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#64748B;">Memuat data...</td></tr>`;

    modal.style.display = 'flex';

    fetch(`/plotting-pengajian/anggota/${kelasId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        subtitle.textContent = `${data.anggota.length} santri terdaftar`;
        if (data.anggota.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:28px; color:#64748B;">Belum ada santri yang ditempatkan di kelas ini.</td></tr>`;
            return;
        }

        let html = '';
        data.anggota.forEach(s => {
            html += `
                <tr class="js-modal-row" data-id="${s.id}" data-nama="${escapeHtml(s.nama_lengkap.toLowerCase())}" data-nisn="${escapeHtml(s.nisn.toLowerCase())}">
                    <td class="fi-ta-td-check" style="text-align:center;">
                        <input type="checkbox" class="fi-ta-checkbox js-cb-modal" value="${s.id}" data-nama="${escapeHtml(s.nama_lengkap)}" onchange="updateModalSelectionState()">
                    </td>
                    <td>
                        <span class="pk-nama-text">${escapeHtml(s.nama_lengkap)}</span>
                    </td>
                    <td><span class="pk-nisn-text">${escapeHtml(s.nisn)}</span></td>
                    <td><span style="font-size:12px; color:#4B5563;">${escapeHtml(s.jenis_kelamin)}</span></td>
                    <td><span style="font-size:12px; color:#4B5563;">${escapeHtml(s.kamar)}</span></td>
                    <td style="text-align: right; padding-right:16px;">
                        <div style="display:inline-flex; align-items:center; gap:6px;">
                            <button type="button" class="fi-ta-act-link fi-ta-act-edit" onclick="openMutasiSingleModal(${s.id}, '${escapeHtml(s.nama_lengkap)}')">
                                Pindah
                            </button>
                            <button type="button" class="fi-ta-act-link fi-ta-act-delete" onclick="unassignSingleModal(${s.id}, '${escapeHtml(s.nama_lengkap)}')">
                                Keluarkan
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        updateModalSelectionState();
    })
    .catch(() => {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#DC2626;">Gagal memuat data santri. Silakan coba lagi.</td></tr>`;
    });
}

function closeAnggotaModal() {
    document.getElementById('modalAnggota').style.display = 'none';
}

function filterModalAnggotaRows() {
    const q = (document.getElementById('modalAnggotaSearch')?.value || '').toLowerCase();
    document.querySelectorAll('#modalAnggotaBody .js-modal-row').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        tr.style.display = !q || text.includes(q) ? '' : 'none';
    });
}

function toggleModalPageSelection(checked) {
    document.querySelectorAll('#modalAnggotaBody .js-modal-row').forEach(tr => {
        if (tr.style.display !== 'none') {
            const cb = tr.querySelector('.js-cb-modal');
            if (cb) cb.checked = checked;
        }
    });
    updateModalSelectionState();
}

function updateModalSelectionState() {
    const allCbs = Array.from(document.querySelectorAll('#modalAnggotaBody .js-cb-modal'));
    const selected = allCbs.filter(cb => cb.checked);
    const count = selected.length;

    const btnBulk = document.getElementById('btnBulkModal');
    const badge = document.getElementById('bulkCountModal');
    if (btnBulk) {
        btnBulk.disabled = count === 0;
        btnBulk.classList.toggle('is-active-selection', count > 0);
    }
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }
}

function openMutasiBulkModal() {
    closeAllPopovers();
    const selected = Array.from(document.querySelectorAll('#modalAnggotaBody .js-cb-modal:checked'));
    if (selected.length === 0) return;

    const ids = selected.map(cb => cb.value).join(',');
    const label = `${selected.length} Santri Terpilih`;
    openMutasiSingleModal(ids, label);
}

function unassignSingleModal(siswaId, namaSantri) {
    openFiConfirm({
        variant: 'danger',
        title: `Keluarkan "${namaSantri}"`,
        desc: `Santri ini akan dikeluarkan dari kelas pengajian dan kembali ke tab Belum Terdaftar. Lanjutkan?`,
        confirmText: 'Keluarkan',
        onConfirm: function() {
            document.getElementById('unassignSiswaId').value = siswaId;
            document.getElementById('formUnassign').submit();
        }
    });
}

function unassignBulkModal() {
    closeAllPopovers();
    const selected = Array.from(document.querySelectorAll('#modalAnggotaBody .js-cb-modal:checked'));
    if (selected.length === 0) return;

    const ids = selected.map(cb => cb.value).join(',');
    openFiConfirm({
        variant: 'danger',
        title: `Keluarkan ${selected.length} santri terpilih`,
        desc: 'Santri yang dipilih akan dikeluarkan dari kelas pengajian dan kembali ke tab Belum Terdaftar. Lanjutkan?',
        confirmText: 'Keluarkan',
        onConfirm: function() {
            document.getElementById('unassignSiswaId').value = ids;
            document.getElementById('formUnassign').submit();
        }
    });
}

// ==========================================
// COLUMN VISIBILITY SYSTEM
// ==========================================
const colDefs = {
    kelas: [
        { key: 'jenjang', label: 'Jenjang' },
        { key: 'kode', label: 'Kode Kelas' }
    ],
    belum: [
        { key: 'jenjang', label: 'Jenjang / Kelas' },
        { key: 'nisn', label: 'NISN' },
        { key: 'gender', label: 'Gender' },
        { key: 'kamar', label: 'Kamar' }
    ]
};

function initColumnCustomizers() {
    ['kelas', 'belum'].forEach(tab => {
        const list = document.getElementById(tab === 'kelas' ? 'colListKelas' : 'colListBelum');
        if (!list) return;
        list.innerHTML = '';
        colDefs[tab].forEach(col => {
            const label = document.createElement('label');
            label.className = 'fi-ta-col-item';
            label.innerHTML = `
                <input type="checkbox" class="fi-ta-checkbox" checked onchange="toggleColumn('${tab}', '${col.key}', this.checked)">
                <span>${col.label}</span>
            `;
            list.appendChild(label);
        });
    });
}

function toggleColumn(tab, colKey, isVisible) {
    const table = document.getElementById(tab === 'kelas' ? 'tableKelas' : 'tableBelum');
    if (!table) return;
    table.querySelectorAll(`.pk-th-${colKey}, .pk-td-${colKey}`).forEach(el => {
        el.style.display = isVisible ? '' : 'none';
    });
}

function resetColumns(tab) {
    const list = document.getElementById(tab === 'kelas' ? 'colListKelas' : 'colListBelum');
    if (list) {
        list.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            cb.checked = true;
        });
    }
    colDefs[tab].forEach(col => {
        toggleColumn(tab, col.key, true);
    });
}

// Initial Run on Load
document.addEventListener('DOMContentLoaded', function() {
    initColumnCustomizers();
    renderTable('kelas');
    renderTable('belum');
});
</script>
@endpush
