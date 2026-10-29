@extends('layouts.app')

@section('title', 'Plotting Kelas')

@section('header-actions')
<div class="pk-header-actions-wrap">
    {{-- Tombol Muat Standar --}}
    <form action="{{ route('plotting-kelas.muat-standar') }}" method="POST" class="d-inline" id="formMuatStandarKelas">
        @csrf
        <input type="hidden" name="lembaga_id" value="{{ $selectedLembaga->id }}">
        <input type="hidden" name="jenjang" value="{{ $jenjang }}">
        <button type="button" class="pk-btn-secondary" title="Muat kelas standar SMP (VII A-IX B) atau SMA (X A-XII B)"
                onclick="openFiConfirm({
                    variant: 'primary',
                    title: 'Muat Kelas Standar {{ $jenjang }}',
                    desc: 'Sistem akan memeriksa dan menambahkan daftar ruang kelas standar untuk jenjang {{ $jenjang }} jika belum terdaftar.',
                    confirmText: 'Muat Standar',
                    onConfirm: function() { document.getElementById('formMuatStandarKelas').submit(); }
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

    {{-- Switcher Jenjang untuk Yayasan --}}
    @if($isYayasan && $sekolahLembagas->count() > 1)
    <div class="yayasan-switch-wrap">
        @foreach($sekolahLembagas as $lem)
            <a href="{{ route('plotting-kelas.index', ['lembaga' => strtolower($lem->kode), 'tab' => $activeTab]) }}"
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

    {{-- Segmented Tabs Navigation --}}
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
    {{-- TAB 1: DAFTAR KELAS (FILAMENT V4 TABLE CARD)                            --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'kelas' ? 'active' : '' }}" id="paneKelas">
        <div class="fi-ta-ctn" id="fiCardKelas">

            {{-- Top Toolbar: [ ⋮ Aksi ] on Left | [ 🔍 Cari... ] + [ Funnel⁰ ] on Right --}}
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
                               placeholder="Cari nama ruang kelas..."
                               oninput="onSearchInput('kelas')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchKelas" onclick="clearSearch('kelas')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button + Floating Popover (Right Next to Search - Gambar 4) --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapKelas">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterKelas"
                                title="Filter Ruang Kelas"
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
                                    <span>Hanya kelas terisi siswa</span>
                                </label>

                                <div class="fi-ta-filter-field">
                                    <label for="fltKelasTingkat" class="fi-ta-filter-label">Tingkat Kelas</label>
                                    <select id="fltKelasTingkat" class="fi-ta-filter-select">
                                        <option value="">Semua Tingkat</option>
                                        @if($jenjang === 'SMP')
                                            <option value="7">Tingkat 7 (Kelas VII)</option>
                                            <option value="8">Tingkat 8 (Kelas VIII)</option>
                                            <option value="9">Tingkat 9 (Kelas IX)</option>
                                        @else
                                            <option value="10">Tingkat 10 (Kelas X)</option>
                                            <option value="11">Tingkat 11 (Kelas XI)</option>
                                            <option value="12">Tingkat 12 (Kelas XII)</option>
                                        @endif
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
                </div>
            </div>

            {{-- Selection Strip (Gambar 5) --}}
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
                                    <span>Nama Kelas</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th class="pk-th-tingkat">Tingkat</th>
                            <th style="text-align: center;">
                                <button type="button" class="fi-ta-th-sort" onclick="sortTableBy('kelas', 'jumlah')">
                                    <span>Jumlah Siswa</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th style="text-align: right; padding-right: 20px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyKelas">
                        @forelse($dataKelas as $item)
                        <tr class="pk-row-kelas js-row-kelas"
                            data-id="{{ $item->id }}"
                            data-nama="{{ strtolower('Kelas ' . ($item->nama_kelas_display ?? $item->nama_kelas)) }}"
                            data-kelas="{{ $item->nama_kelas }}"
                            data-tingkat="{{ $item->tingkat ?? 7 }}"
                            data-jumlah="{{ $item->jumlah_siswa }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox js-cb-kelas"
                                       value="{{ $item->id }}"
                                       data-label="Kelas {{ $item->nama_kelas_display ?? $item->nama_kelas }}"
                                       data-jumlah="{{ $item->jumlah_siswa }}"
                                       onchange="onRowCheckboxChange('kelas')">
                            </td>
                            <td>
                                <div class="pk-kelas-cell-content">
                                    <div class="pk-tingkat-badge" title="Tingkat {{ $item->tingkat ?? 7 }}">{{ $item->tingkat ?? 7 }}</div>
                                    <div class="pk-kelas-name-wrap">
                                        <span class="pk-kelas-name">Kelas {{ $item->nama_kelas_display ?? $item->nama_kelas }}</span>
                                        <div class="pk-kelas-sub-mobile">Tingkat {{ $item->tingkat ?? 7 }} • Jenjang {{ $jenjang }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pk-td-tingkat">
                                <span class="pk-tingkat-text">Tingkat {{ $item->tingkat ?? 7 }}</span>
                            </td>
                            <td style="text-align: center;">
                                <span class="pk-siswa-count"
                                      onclick="openAnggotaModal('{{ addslashes($item->nama_kelas) }}')"
                                      title="Klik untuk lihat & kelola anggota kelas">
                                    {{ $item->jumlah_siswa }} Siswa
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 16px;">
                                <div class="fi-ta-row-actions">
                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-view pk-btn-eye"
                                            title="Lihat & Plotting Siswa Kelas {{ $item->nama_kelas }}"
                                            onclick="openAnggotaModal('{{ addslashes($item->nama_kelas) }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <span class="pk-act-label">Lihat</span>
                                    </button>

                                    @if(isset($item->id))
                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-edit"
                                            title="Edit Ruang Kelas {{ $item->nama_kelas }}"
                                            onclick="openEditKelasModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->tingkat ?? 7 }}, {{ $item->urutan ?? 1 }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                        <span class="pk-act-label">Edit</span>
                                    </button>

                                    <button type="button"
                                            class="fi-ta-act-link fi-ta-act-delete"
                                            title="Hapus Ruang Kelas {{ $item->nama_kelas }}"
                                            onclick="confirmDeleteKelas({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->jumlah_siswa }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                        <span class="pk-act-label">Hapus</span>
                                    </button>

                                    <form id="formDeleteKelas_{{ $item->id }}" action="{{ route('plotting-kelas.destroy-kelas', $item->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="5" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                <div style="margin-bottom: 10px;">Tidak ada ruang kelas yang terdaftar untuk jenjang {{ $jenjang }}.</div>
                                <button type="button" class="pk-btn-add-kelas" onclick="openTambahKelasModal()" style="display:inline-flex;">
                                    + Tambah Kelas Baru
                                </button>
                            </td>
                        </tr>
                        @endforelse
                        <tr id="emptyFilterRowKelas" style="display:none;">
                            <td colspan="5" style="padding: 32px 16px; text-align: center; color: #6B7280;">
                                Tidak ada data kelas yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Filament v4 Footer --}}
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
    {{-- TAB 2: SISWA BELUM TERDAFTAR KELAS (FILAMENT V4 TABLE CARD)             --}}
    {{-- ======================================================================= --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">
        <div class="fi-ta-ctn" id="fiCardBelum">

            {{-- Top Toolbar: [ ⋮ Aksi ] on Left | [ 🔍 Cari... ] + [ Funnel⁰ ] on Right --}}
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
                                <span>Mutasi ke Kelas</span>
                            </button>
                            <div class="fi-ta-bulk-divider"></div>
                            <button type="button" class="fi-ta-bulk-item is-danger" onclick="clearSelection('belum')">
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
                               placeholder="Cari nama siswa atau NISN..."
                               oninput="onSearchInput('belum')">
                        <button type="button" class="fi-ta-search-clear" id="clearSearchBelum" onclick="clearSearch('belum')" title="Bersihkan pencarian">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>

                    {{-- Filter Funnel Button + Floating Popover (Right Next to Search - Gambar 4) --}}
                    <div class="fi-ta-filter-wrap" id="filterWrapBelum">
                        <button type="button"
                                class="fi-ta-filter-btn"
                                id="btnFilterBelum"
                                title="Filter Siswa Belum Terdaftar"
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
                                <label class="fi-ta-filter-check-row">
                                    <input type="checkbox" id="fltBelumHasNisn" class="fi-ta-checkbox">
                                    <span>Hanya siswa dengan NISN terisi</span>
                                </label>
                            </div>
                            <div class="fi-ta-filter-actions">
                                <button type="button" class="fi-ta-filter-apply" onclick="applyFilter('belum')">
                                    Terapkan filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Selection Strip (Gambar 5) --}}
            <div class="fi-ta-selection-bar" id="selBarBelum">
                <span class="fi-ta-selection-count" id="selCountTextBelum">0 siswa dipilih</span>
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
                                    <span>Nama Siswa</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                            </th>
                            <th class="pk-th-nisn">NISN</th>
                            <th>Status</th>
                            <th style="text-align: right; padding-right: 20px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBelum">
                        @forelse($belumTerdaftar as $siswa)
                        @php
                            $nisnVal = $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-';
                        @endphp
                        <tr class="pk-row-siswa js-row-belum"
                            data-id="{{ $siswa->id }}"
                            data-nama="{{ strtolower($siswa->nama_lengkap) }}"
                            data-siswa-id="{{ $siswa->id }}"
                            data-siswa-nama="{{ $siswa->nama_lengkap }}"
                            data-has-nisn="{{ ($nisnVal && $nisnVal !== '-') ? '1' : '0' }}">
                            <td class="fi-ta-td-check">
                                <input type="checkbox"
                                       class="fi-ta-checkbox js-cb-belum"
                                       value="{{ $siswa->id }}"
                                       data-label="{{ $siswa->nama_lengkap }}"
                                       onchange="onRowCheckboxChange('belum')">
                            </td>
                            <td>
                                <span class="pk-nama-text">{{ $siswa->nama_lengkap }}</span>
                                <div class="pk-nisn-sub-mobile">NISN: {{ $nisnVal }}</div>
                            </td>
                            <td class="pk-td-nisn">
                                <span class="pk-nisn-text">{{ $nisnVal }}</span>
                            </td>
                            <td>
                                <span class="pk-status-pill">Belum Terdaftar</span>
                            </td>
                            <td style="text-align: right; padding-right: 16px;">
                                <button type="button"
                                        class="pk-btn-mutasi-row"
                                        onclick="openMutasiSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')"
                                        title="Mutasikan ke Kelas">
                                    <span>Pilih Kelas</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr class="js-empty-initial">
                            <td colspan="5" style="padding: 36px 16px; text-align: center; color: #6B7280;">
                                Semua siswa jenjang {{ $jenjang }} telah terdaftar ke kelas.
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

{{-- FORM HIDDEN BULK DELETE KELAS & UNASSIGN --}}
<form action="{{ route('plotting-kelas.destroy-bulk-kelas') }}" method="POST" id="formBulkDeleteKelas" style="display:none;">
    @csrf
    <input type="hidden" name="kelas_ids" id="bulkDeleteKelasIds">
</form>

<form action="{{ route('plotting-kelas.unassign') }}" method="POST" id="formUnassign" style="display:none;">
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

{{-- ======================================================================= --}}
{{-- MODAL 1: LIHAT & KELOLA ANGGOTA KELAS (WITH FILAMENT V4 TOOLBAR)        --}}
{{-- ======================================================================= --}}
<div class="pk-modal-backdrop" id="modalAnggota" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <div class="pk-modal-header">
                <div>
                    <h3 class="pk-modal-title" id="modalAnggotaTitle">Anggota Kelas</h3>
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
                                    <button type="button" class="fi-ta-bulk-item is-primary" onclick="openMutasiBulkModal()">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="9 18 15 12 9 6"></polyline>
                                        </svg>
                                        <span>Mutasi Kelas Terpilih</span>
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
                                       placeholder="Cari siswa di kelas ini..."
                                       oninput="filterModalAnggotaRows()">
                            </div>
                        </div>
                    </div>

                    <div class="pk-modal-table-wrap" style="border:none; border-radius: 0 0 12px 12px;">
                        <table class="fi-ta-table">
                            <thead>
                                <tr>
                                    <th class="fi-ta-th-check">
                                        <input type="checkbox" id="checkAllModalAnggota" class="fi-ta-checkbox" onchange="toggleSelectAllModal(this.checked)" title="Pilih Semua">
                                    </th>
                                    <th>Nama Siswa</th>
                                    <th class="pk-th-nisn">NISN</th>
                                    <th style="text-align:right; padding-right:16px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="modalAnggotaBody">
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:24px; color:#64748B;">Memuat data...</td>
                                </tr>
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

{{-- MODAL 2: MUTASI SISWA KE KELAS --}}
<div class="pk-modal-backdrop" id="modalMutasi" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-kelas.mutasi') }}" method="POST" id="formMutasi">
                @csrf
                <input type="hidden" name="siswa_id" id="mutasiSiswaId">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Mutasi Kelas Siswa</h3>
                        <span class="pk-modal-subtitle" id="mutasiSiswaNama">-</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeMutasiModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="selectKelasTujuan" class="pk-form-label">Pilih Kelas Tujuan <span style="color:#EF4444;">*</span></label>
                        <select name="kelas" id="selectKelasTujuan" class="pk-form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($dataKelas as $kItem)
                                <option value="{{ $kItem->nama_kelas }}">Kelas {{ $kItem->nama_kelas_display ?? $kItem->nama_kelas }} ({{ $kItem->jumlah_siswa }} Siswa)</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeMutasiModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Mutasi</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- MODAL 3: TAMBAH RUANG KELAS BARU --}}
<div class="pk-modal-backdrop" id="modalTambahKelas" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-kelas.store-kelas') }}" method="POST" id="formTambahKelas">
                @csrf
                <input type="hidden" name="lembaga_id" value="{{ $selectedLembaga->id }}">
                <input type="hidden" name="jenjang" value="{{ $jenjang }}">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Ruang Kelas Baru</h3>
                        <span class="pk-modal-subtitle">Jenjang {{ $jenjang }} • {{ $selectedLembaga->nama }}</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeTambahKelasModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="inputNamaKelas" class="pk-form-label">Nama Kelas <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kelas"
                               id="inputNamaKelas"
                               class="pk-form-input"
                               placeholder="Contoh: VII C, 10 MIPA 1, atau IX B"
                               required
                               autocomplete="off">
                        <span class="pk-form-hint">Nama kelas akan tampil di daftar plotting, matriks jadwal, dan data siswa.</span>
                    </div>

                    <div class="pk-form-group">
                        <label for="selectTingkat" class="pk-form-label">Tingkat Kelas <span style="color:#EF4444;">*</span></label>
                        <select name="tingkat" id="selectTingkat" class="pk-form-select" required>
                            @if($jenjang === 'SMP')
                                <option value="7">Tingkat 7 (Kelas VII)</option>
                                <option value="8">Tingkat 8 (Kelas VIII)</option>
                                <option value="9">Tingkat 9 (Kelas IX)</option>
                            @else
                                <option value="10">Tingkat 10 (Kelas X)</option>
                                <option value="11">Tingkat 11 (Kelas XI)</option>
                                <option value="12">Tingkat 12 (Kelas XII)</option>
                            @endif
                        </select>
                    </div>

                    <div class="pk-form-group">
                        <label for="inputUrutan" class="pk-form-label">Nomor Urutan Tampilan (Opsional)</label>
                        <input type="number"
                               name="urutan"
                               id="inputUrutan"
                               class="pk-form-input"
                               placeholder="Otomatis di akhir jika dikosongkan">
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

{{-- MODAL 4: EDIT RUANG KELAS --}}
<div class="pk-modal-backdrop" id="modalEditKelas" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="" method="POST" id="formEditKelas">
                @csrf
                @method('PUT')

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Edit Ruang Kelas</h3>
                        <span class="pk-modal-subtitle">Perbarui data nama atau tingkat kelas</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeEditKelasModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="editNamaKelas" class="pk-form-label">Nama Kelas <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kelas"
                               id="editNamaKelas"
                               class="pk-form-input"
                               required
                               autocomplete="off">
                    </div>

                    <div class="pk-form-group">
                        <label for="editTingkat" class="pk-form-label">Tingkat Kelas <span style="color:#EF4444;">*</span></label>
                        <select name="tingkat" id="editTingkat" class="pk-form-select" required>
                            @if($jenjang === 'SMP')
                                <option value="7">Tingkat 7 (Kelas VII)</option>
                                <option value="8">Tingkat 8 (Kelas VIII)</option>
                                <option value="9">Tingkat 9 (Kelas IX)</option>
                            @else
                                <option value="10">Tingkat 10 (Kelas X)</option>
                                <option value="11">Tingkat 11 (Kelas XI)</option>
                                <option value="12">Tingkat 12 (Kelas XII)</option>
                            @endif
                        </select>
                    </div>

                    <div class="pk-form-group">
                        <label for="editUrutan" class="pk-form-label">Nomor Urutan Tampilan</label>
                        <input type="number"
                               name="urutan"
                               id="editUrutan"
                               class="pk-form-input">
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
    width: 30px; height: 30px; border-radius: 8px; display: inline-flex;
    align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;
    background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0,82,255,0.16);
}
.pk-kelas-name { font-weight: 600; color: #111827; font-size: 13.5px; }
.dark-mode .pk-kelas-name { color: #F8FAFC; }
.pk-tingkat-text { color: #4B5563; font-size: 13px; }
.dark-mode .pk-tingkat-text { color: #94A3B8; }

.pk-siswa-count {
    display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px;
    font-size: 12px; font-weight: 600; background: #ECFDF5; color: #059669;
    border: 1px solid rgba(16,185,129,0.22); cursor: pointer; transition: all 0.14s ease;
}
.pk-siswa-count:hover { background: #D1FAE5; }

.pk-nama-text { font-weight: 600; color: #111827; }
.dark-mode .pk-nama-text { color: #F8FAFC; }
.pk-nisn-text { font-family: monospace; font-size: 12.5px; color: #6B7280; }

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
.pk-modal-dialog { width: 100%; max-width: 700px; max-height: 88vh; display: flex; flex-direction: column; }
.pk-modal-dialog-sm { max-width: 460px; }
.pk-modal-content {
    background: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 14px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.18); display: flex; flex-direction: column; overflow: hidden;
}
.dark-mode .pk-modal-content { background: #141C28; border-color: #263346; }
.pk-modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid #E5E7EB;
}
.dark-mode .pk-modal-header { border-color: #263346; }
.pk-modal-title { margin: 0; font-size: 16px; font-weight: 700; color: #111827; }
.dark-mode .pk-modal-title { color: #F8FAFC; }
.pk-modal-subtitle { display: block; font-size: 12px; color: #6B7280; margin-top: 2px; }
.pk-modal-close { background: transparent; border: none; font-size: 22px; color: #9CA3AF; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
.pk-modal-body { padding: 18px 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 14px; }
.pk-modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    padding: 14px 20px; background: #F9FAFB; border-top: 1px solid #E5E7EB;
}
.dark-mode .pk-modal-footer { background: #101722; border-color: #263346; }
.pk-form-group { display: flex; flex-direction: column; gap: 5px; }
.pk-form-label { font-size: 13px; font-weight: 600; color: #374151; }
.dark-mode .pk-form-label { color: #E2E8F0; }
.pk-form-hint { font-size: 11.5px; color: #6B7280; line-height: 1.4; }
.pk-form-input, .pk-form-select {
    width: 100%; height: 38px; padding: 0 12px; border-radius: 8px;
    border: 1px solid #D1D5DB; background: #FFFFFF; color: #111827; font-size: 13.5px; outline: none; box-sizing: border-box;
}
.pk-form-input:focus, .pk-form-select:focus { border-color: #0052FF; box-shadow: 0 0 0 3px rgba(0,82,255,0.12); }
.dark-mode .pk-form-input, .dark-mode .pk-form-select { background: #0F172A; border-color: #334155; color: #F8FAFC; }
.pk-btn-clean {
    background: #FFFFFF; border: 1px solid #D1D5DB; color: #374151;
    padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.pk-btn-primary {
    background: #0052FF; color: #FFFFFF; border: 1px solid #0052FF;
    padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
}
.pk-modal-table-wrap { max-height: 360px; overflow-y: auto; }

.pk-kelas-sub-mobile, .pk-nisn-sub-mobile { display: none; }

@media (max-width: 768px) {
    .pk-tabs-nav { width: 100%; display: flex; }
    .pk-tab-item { flex: 1; justify-content: center; }
    .pk-th-tingkat, .pk-td-tingkat, .pk-th-nisn, .pk-td-nisn { display: none !important; }
    .pk-kelas-sub-mobile, .pk-nisn-sub-mobile {
        display: block !important; font-size: 11.5px; color: #6B7280; margin-top: 2px;
    }
    .pk-act-label { display: none; }
    .fi-ta-row-actions { gap: 6px; }
}
</style>

<script>
const jenjangAktif = '{{ $jenjang }}';

const tableState = {
    kelas: {
        page: 1,
        perPage: 10,
        search: '',
        onlyFilled: false,
        tingkat: '',
        sortKey: '',
        sortAsc: true,
        rowSelector: '#tbodyKelas tr.js-row-kelas',
        cbSelector: '.js-cb-kelas',
        tbodyId: 'tbodyKelas',
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
        hasNisn: false,
        sortKey: '',
        sortAsc: true,
        rowSelector: '#tbodyBelum tr.js-row-belum',
        cbSelector: '.js-cb-belum',
        tbodyId: 'tbodyBelum',
        emptyRowId: 'emptyFilterRowBelum',
        checkAllPageId: 'checkAllPageBelum',
        btnBulkId: 'btnBulkBelum',
        bulkCountBadgeId: 'bulkCountBadgeBelum',
        selBarId: 'selBarBelum',
        selCountTextId: 'selCountTextBelum',
        btnSelectAllMatchingId: 'btnSelectAllMatchingBelum',
        footerInfoId: 'footerInfoBelum',
        pagesId: 'pagesBelum',
        unitLabel: 'siswa'
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
    document.querySelectorAll('.fi-ta-bulk-menu.is-open, .fi-ta-filter-popover.is-open').forEach(el => {
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
            if (st.tingkat && String(tr.dataset.tingkat || '') !== String(st.tingkat)) return false;
        } else if (key === 'belum') {
            if (st.hasNisn && tr.dataset.hasNisn !== '1') return false;
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
        const tingkat = document.getElementById('fltKelasTingkat')?.value || '';
        tableState.kelas.onlyFilled = onlyFilled;
        tableState.kelas.tingkat = tingkat;
        const activeCount = (onlyFilled ? 1 : 0) + (tingkat ? 1 : 0);
        updateFilterBadge('badgeFilterKelas', 'btnFilterKelas', activeCount);
    } else {
        const hasNisn = document.getElementById('fltBelumHasNisn')?.checked || false;
        tableState.belum.hasNisn = hasNisn;
        updateFilterBadge('badgeFilterBelum', 'btnFilterBelum', hasNisn ? 1 : 0);
    }
    tableState[key].page = 1;
    closeAllPopovers();
    renderTable(key);
}

function resetFilter(key) {
    if (key === 'kelas') {
        const cb = document.getElementById('fltKelasTerisi');
        const sel = document.getElementById('fltKelasTingkat');
        if (cb) cb.checked = false;
        if (sel) sel.value = '';
    } else {
        const cb = document.getElementById('fltBelumHasNisn');
        if (cb) cb.checked = false;
    }
    applyFilter(key);
}

function updateFilterBadge(badgeId, btnId, count) {
    const badge = document.getElementById(badgeId);
    const btn = document.getElementById(btnId);
    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('has-active', count > 0);
    }
    if (btn) {
        btn.classList.toggle('has-filters', count > 0);
    }
}

function sortTableBy(key, prop) {
    const st = tableState[key];
    if (st.sortKey === prop) {
        st.sortAsc = !st.sortAsc;
    } else {
        st.sortKey = prop;
        st.sortAsc = true;
    }
    const tbody = document.getElementById(st.tbodyId);
    const rows = Array.from(document.querySelectorAll(st.rowSelector));
    rows.sort((a, b) => {
        if (prop === 'jumlah') {
            const va = parseInt(a.dataset.jumlah || '0', 10);
            const vb = parseInt(b.dataset.jumlah || '0', 10);
            return st.sortAsc ? va - vb : vb - va;
        }
        const sa = a.dataset.nama || '';
        const sb = b.dataset.nama || '';
        return st.sortAsc ? sa.localeCompare(sb, 'id') : sb.localeCompare(sa, 'id');
    });
    const emptyRow = document.getElementById(st.emptyRowId);
    rows.forEach(r => tbody.insertBefore(r, emptyRow));
    renderTable(key);
}

function togglePageSelection(key, checked) {
    const st = tableState[key];
    document.querySelectorAll(st.rowSelector).forEach(tr => {
        if (tr.style.display === 'none') return;
        const cb = tr.querySelector(st.cbSelector);
        if (cb) cb.checked = checked;
    });
    onRowCheckboxChange(key);
}

function selectAllMatching(key) {
    const st = tableState[key];
    getMatchingRows(key).forEach(tr => {
        const cb = tr.querySelector(st.cbSelector);
        if (cb) cb.checked = true;
    });
    onRowCheckboxChange(key);
}

function clearSelection(key) {
    const st = tableState[key];
    document.querySelectorAll(st.cbSelector).forEach(cb => cb.checked = false);
    closeAllPopovers();
    onRowCheckboxChange(key);
}

function onRowCheckboxChange(key) {
    const matching = getMatchingRows(key);
    const visible = matching.filter(tr => tr.style.display !== 'none');
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
// GAMBAR 5 CENTERED CONFIRMATION MODAL
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

function confirmBulkDeleteKelas() {
    const selected = Array.from(document.querySelectorAll('.js-cb-kelas:checked'));
    if (selected.length === 0) return;

    openFiConfirm({
        variant: 'danger',
        title: `Hapus ${selected.length} ruang kelas terpilih`,
        desc: 'Apakah Anda yakin ingin menghapus ruang kelas yang dipilih? Kelas yang masih memiliki siswa atau jadwal pelajaran akan otomatis dilindungi.',
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
        : `${checkedBoxes.length} Siswa Terpilih`;

    document.getElementById('mutasiSiswaId').value = ids;
    document.getElementById('mutasiSiswaNama').textContent = label;
    document.getElementById('modalMutasi').style.display = 'flex';
}

// ==========================================
// MODAL ANGGOTA KELAS
// ==========================================
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openAnggotaModal(namaKelas) {
    const modal = document.getElementById('modalAnggota');
    const title = document.getElementById('modalAnggotaTitle');
    const subtitle = document.getElementById('modalAnggotaSubtitle');
    const tbody = document.getElementById('modalAnggotaBody');
    const searchInput = document.getElementById('modalAnggotaSearch');
    const checkAll = document.getElementById('checkAllModalAnggota');

    title.textContent = `Anggota Kelas ${namaKelas}`;
    subtitle.textContent = 'Memuat daftar anggota...';
    if (searchInput) searchInput.value = '';
    if (checkAll) checkAll.checked = false;
    updateModalSelectionState();
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:24px; color:#64748B;">Memuat data...</td></tr>`;

    modal.style.display = 'flex';

    fetch(`/plotting-kelas/anggota/${encodeURIComponent(namaKelas)}?jenjang=${jenjangAktif}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) throw new Error('Gagal memuat');

        subtitle.textContent = `${res.total} Siswa Terdaftar`;
        tbody.innerHTML = '';

        if (res.anggota.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:28px; color:#94A3B8;">Belum ada siswa di kelas ini.</td></tr>`;
            return;
        }

        res.anggota.forEach((item) => {
            const tr = document.createElement('tr');
            tr.className = 'js-modal-anggota-row';
            const safeNamaJs = String(item.nama_lengkap || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");

            tr.innerHTML = `
                <td class="fi-ta-td-check">
                    <input type="checkbox" class="fi-ta-checkbox js-check-modal" value="${item.id}" data-nama="${escapeHtml(item.nama_lengkap)}" onchange="updateModalSelectionState()">
                </td>
                <td style="font-weight:600;">${escapeHtml(item.nama_lengkap)}</td>
                <td class="pk-td-nisn" style="font-family:monospace; font-size:12px; color:#64748B;">${escapeHtml(item.nisn)}</td>
                <td style="text-align:right; padding-right:16px;">
                    <div class="fi-ta-row-actions">
                        <button type="button" class="fi-ta-act-link fi-ta-act-edit" onclick="openMutasiSingleModal(${item.id}, '${safeNamaJs}')">
                            Mutasi
                        </button>
                        <button type="button" class="fi-ta-act-link fi-ta-act-delete" onclick="unassignSiswa(${item.id}, '${safeNamaJs}')">
                            Keluarkan
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(() => {
        subtitle.textContent = 'Gagal memuat data';
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:24px; color:#EF4444;">Gagal memuat data anggota kelas.</td></tr>`;
    });
}

function filterModalAnggotaRows() {
    const q = (document.getElementById('modalAnggotaSearch')?.value || '').toLowerCase().trim();
    document.querySelectorAll('#modalAnggotaBody tr.js-modal-anggota-row').forEach(tr => {
        tr.style.display = !q || tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

function toggleSelectAllModal(checked) {
    document.querySelectorAll('#modalAnggotaBody tr.js-modal-anggota-row').forEach(tr => {
        if (tr.style.display === 'none') return;
        const cb = tr.querySelector('.js-check-modal');
        if (cb) cb.checked = checked;
    });
    updateModalSelectionState();
}

function updateModalSelectionState() {
    const allModalCbs = Array.from(document.querySelectorAll('.js-check-modal'));
    allModalCbs.forEach(cb => cb.closest('tr')?.classList.toggle('is-selected', cb.checked));
    const checkedBoxes = allModalCbs.filter(cb => cb.checked);
    const btn = document.getElementById('btnBulkModal');
    const countEl = document.getElementById('bulkCountModal');
    if (btn) {
        btn.disabled = checkedBoxes.length === 0;
        btn.classList.toggle('is-active-selection', checkedBoxes.length > 0);
    }
    if (countEl) {
        countEl.textContent = checkedBoxes.length;
        countEl.style.display = checkedBoxes.length > 0 ? 'inline-flex' : 'none';
    }
}

function openMutasiBulkModal() {
    closeAllPopovers();
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-modal:checked'));
    if (checkedBoxes.length === 0) return;

    const ids = checkedBoxes.map(cb => cb.value).join(',');
    const label = checkedBoxes.length === 1
        ? checkedBoxes[0].dataset.nama
        : `${checkedBoxes.length} Siswa Terpilih`;

    document.getElementById('mutasiSiswaId').value = ids;
    document.getElementById('mutasiSiswaNama').textContent = label;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function unassignBulkModal() {
    closeAllPopovers();
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-modal:checked'));
    if (checkedBoxes.length === 0) return;

    openFiConfirm({
        variant: 'danger',
        title: `Keluarkan ${checkedBoxes.length} siswa terpilih`,
        desc: 'Siswa yang dikeluarkan akan dipindahkan ke daftar Belum Terdaftar.',
        confirmText: 'Keluarkan',
        onConfirm: function() {
            document.getElementById('unassignSiswaId').value = checkedBoxes.map(cb => cb.value).join(',');
            document.getElementById('formUnassign').submit();
        }
    });
}

function closeAnggotaModal() {
    document.getElementById('modalAnggota').style.display = 'none';
}

function openMutasiSingleModal(siswaId, namaSiswa) {
    document.getElementById('mutasiSiswaId').value = siswaId;
    document.getElementById('mutasiSiswaNama').textContent = namaSiswa;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function closeMutasiModal() {
    document.getElementById('modalMutasi').style.display = 'none';
}

function openTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'flex';
    setTimeout(() => document.getElementById('inputNamaKelas')?.focus(), 100);
}

function closeTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'none';
}

function openEditKelasModal(id, namaKelas, tingkat, urutan) {
    const form = document.getElementById('formEditKelas');
    form.action = `/plotting-kelas/kelas/${id}`;
    document.getElementById('editNamaKelas').value = namaKelas;
    document.getElementById('editTingkat').value = tingkat;
    document.getElementById('editUrutan').value = urutan || '';
    document.getElementById('modalEditKelas').style.display = 'flex';
    setTimeout(() => document.getElementById('editNamaKelas')?.focus(), 100);
}

function closeEditKelasModal() {
    document.getElementById('modalEditKelas').style.display = 'none';
}

function confirmDeleteKelas(id, namaKelas, jumlahSiswa) {
    if (jumlahSiswa > 0) {
        openFiConfirm({
            variant: 'danger',
            infoOnly: true,
            title: `Kelas ${namaKelas} Masih Terisi`,
            desc: `Ruang kelas ini masih memiliki ${jumlahSiswa} siswa aktif. Silakan mutasikan atau keluarkan siswa terlebih dahulu sebelum menghapus ruang kelas.`
        });
        return;
    }

    openFiConfirm({
        variant: 'danger',
        title: `Hapus Ruang Kelas ${namaKelas}`,
        desc: 'Apakah Anda yakin ingin menghapus ruang kelas ini?',
        confirmText: 'Hapus',
        onConfirm: function() {
            document.getElementById(`formDeleteKelas_${id}`)?.submit();
        }
    });
}

function unassignSiswa(siswaId, namaSiswa) {
    openFiConfirm({
        variant: 'danger',
        title: `Keluarkan ${namaSiswa}`,
        desc: 'Siswa akan dikeluarkan dari kelas ini dan masuk ke daftar Belum Terdaftar.',
        confirmText: 'Keluarkan',
        onConfirm: function() {
            document.getElementById('unassignSiswaId').value = siswaId;
            document.getElementById('formUnassign').submit();
        }
    });
}

document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('modalAnggota')) closeAnggotaModal();
    if (e.target === document.getElementById('modalMutasi')) closeMutasiModal();
    if (e.target === document.getElementById('modalTambahKelas')) closeTambahKelasModal();
    if (e.target === document.getElementById('modalEditKelas')) closeEditKelasModal();
    if (e.target === document.getElementById('fiConfirmModal')) closeFiConfirm();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllPopovers();
        closeFiConfirm();
        closeAnggotaModal();
        closeMutasiModal();
        closeTambahKelasModal();
        closeEditKelasModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    renderTable('kelas');
    renderTable('belum');
});
</script>
@endsection
