@extends('layouts.app')

@section('title', 'Plotting Kamar')

@section('header-actions')
<div class="pk-header-actions-wrap">
    {{-- Tombol Muat Standar --}}
    <form action="{{ route('plotting-kamar.muat-standar') }}" method="POST" class="d-inline" onsubmit="return confirm('Muat daftar kamar standar pondok (Reguler, Abu Bakar, Umar, Utsman, Ali, Khadijah, Aisyah, Fathimah)?');">
        @csrf
        <button type="submit" class="pk-btn-secondary" title="Muat daftar kamar standar pondok pesantren">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
            </svg>
            <span>Muat Standar</span>
        </button>
    </form>

    {{-- Tombol Buat Kamar Baru --}}
    <button type="button" class="pk-btn-add-kelas" onclick="openTambahKamarModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pk-btn-icon">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Kamar</span>
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

    {{-- Bar Kontrol & Tab Navigasi --}}
    <div class="pk-topbar">
        {{-- Segmented Tabs --}}
        <div class="pk-tabs-nav" role="tablist">
            <button type="button"
                    class="pk-tab-item {{ $activeTab === 'kamar' ? 'active' : '' }}"
                    id="tabBtnKamar"
                    onclick="switchTab('kamar')">
                <span>Daftar Kamar</span>
                <span class="pk-tab-badge">{{ count($dataKamar) }}</span>
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

        {{-- Filter & Pencarian Cepat --}}
        <div class="pk-topbar-right">
            <select id="pkFilterSelect" class="pk-filter-select" onchange="filterTableRows()" aria-label="Filter Asrama atau Jenjang">
                <option value="">Semua Kategori / Jenjang</option>
                <optgroup label="Kategori Asrama (Tab Kamar)">
                    <option value="asrama:putra">Asrama Putra</option>
                    <option value="asrama:putri">Asrama Putri</option>
                    <option value="asrama:umum">Asrama Umum</option>
                </optgroup>
                <optgroup label="Jenjang Santri (Tab Belum Terdaftar)">
                    <option value="jenjang:reguler">Reguler (Pondok)</option>
                    <option value="jenjang:smp">SMP</option>
                    <option value="jenjang:sma">SMA</option>
                </optgroup>
            </select>

            <div class="pk-search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-search-icon">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text"
                       id="pkSearchInput"
                       class="pk-search-input"
                       placeholder="Cari kamar, musyrif, atau santri..."
                       onkeyup="filterTableRows()">
            </div>
        </div>
    </div>

    {{-- TAB 1: DAFTAR KAMAR --}}
    <div class="pk-tab-pane {{ $activeTab === 'kamar' ? 'active' : '' }}" id="paneKamar">
        <div class="pk-table-card">
            <div class="pk-table-wrapper">
                <table class="pk-table" id="tableKamar">
                    <thead>
                        <tr>
                            <th class="pk-th-aksi" style="width: 140px;">AKSI</th>
                            <th class="pk-th-kamar">NAMA KAMAR</th>
                            <th class="pk-th-asrama" style="width: 155px;">ASRAMA</th>
                            <th class="pk-th-wali" style="width: 210px;">MUSYRIF / PAMONG</th>
                            <th class="pk-th-jumlah" style="width: 170px; text-align: right;">JUMLAH SANTRI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataKamar as $item)
                        @php
                            $katLower = strtolower($item->kategori_asrama ?? 'Putra');
                            $badgeCode = $katLower === 'putri' ? 'Pi' : ($katLower === 'umum' ? 'Um' : 'Pa');
                            $isFull = $item->kapasitas && $item->jumlah_siswa >= $item->kapasitas;
                        @endphp
                        <tr class="pk-row-kamar"
                            data-kamar="{{ $item->nama_kamar }}"
                            data-asrama="{{ $katLower }}">
                            {{-- Kolom Aksi di sebelah kiri: Logo Mata, Edit Kamar, Hapus Kamar --}}
                            <td class="pk-td-aksi">
                                <div class="pk-actions-group">
                                    <button type="button"
                                            class="pk-btn-eye"
                                            title="Lihat & Plotting Santri {{ $item->nama_kamar_display }}"
                                            onclick="openAnggotaModal('{{ addslashes($item->nama_kamar) }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>

                                    @if(isset($item->id))
                                    <button type="button"
                                            class="pk-btn-edit-action"
                                            title="Edit {{ $item->nama_kamar_display }}"
                                            onclick="openEditKamarModal({{ $item->id }}, '{{ addslashes($item->nama_kamar) }}', '{{ addslashes($item->kategori_asrama) }}', '{{ $item->kapasitas ?? '' }}', '{{ addslashes($item->wali_kamar ?? '') }}', '{{ $item->urutan ?? 1 }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    <button type="button"
                                            class="pk-btn-delete-action"
                                            title="Hapus {{ $item->nama_kamar_display }}"
                                            onclick="confirmDeleteKamar({{ $item->id }}, '{{ addslashes($item->nama_kamar) }}', {{ $item->jumlah_siswa }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>

                                    <form id="formDeleteKamar_{{ $item->id }}" action="{{ route('plotting-kamar.destroy-kamar', $item->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                            <td class="pk-td-kamar">
                                <div class="pk-kelas-cell-content" style="display:flex; align-items:center; gap:10px;">
                                    <div class="pk-tingkat-badge pk-asrama-icon-{{ $katLower }}" title="Asrama {{ $item->kategori_asrama }}">{{ $badgeCode }}</div>
                                    <div class="pk-kelas-name-wrap">
                                        <span class="pk-kelas-name">{{ $item->nama_kamar_display }}</span>
                                        <div class="pk-kelas-sub-mobile">
                                            Asrama {{ $item->kategori_asrama }}{{ $item->wali_kamar ? ' • ' . $item->wali_kamar : '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="pk-td-asrama">
                                <span class="pk-asrama-pill pk-asrama-pill-{{ $katLower }}">
                                    Asrama {{ $item->kategori_asrama }}
                                </span>
                            </td>
                            <td class="pk-td-wali">
                                <span class="pk-tingkat-text">{{ $item->wali_kamar ?: '-' }}</span>
                            </td>
                            <td class="pk-td-jumlah" style="text-align: right;">
                                <span class="pk-siswa-count {{ $isFull ? 'pk-siswa-count-full' : '' }}"
                                      onclick="openAnggotaModal('{{ addslashes($item->nama_kamar) }}')"
                                      style="cursor:pointer;"
                                      title="Klik untuk lihat & kelola penghuni kamar">
                                    {{ $item->jumlah_siswa }}{{ $item->kapasitas ? ' / ' . $item->kapasitas : '' }} Santri
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="pk-td-empty">
                                <div style="padding: 24px 12px; text-align: center;">
                                    <p style="margin: 0 0 12px 0; color: var(--text-muted);">Belum ada kamar asrama yang terdaftar di sistem.</p>
                                    <button type="button" class="pk-btn-add-kelas" onclick="openTambahKamarModal()" style="display:inline-flex;">
                                        + Tambah Kamar Baru
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card List: Kamar --}}
        <div class="mobile-card-list">
            @forelse($dataKamar as $item)
            @php
                $katLower = strtolower($item->kategori_asrama ?? 'Putra');
            @endphp
            <div class="mobile-card-item" data-asrama="{{ $katLower }}">
                <div class="mobile-card-info">
                    <div class="mobile-card-title">{{ $item->nama_kamar_display }}</div>
                    <div class="mobile-card-subtitle">
                        Asrama {{ $item->kategori_asrama }}
                        @if($item->wali_kamar)
                            &bull; Musyrif: {{ $item->wali_kamar }}
                        @endif
                    </div>

                    <div class="mobile-card-meta-grid">
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Asrama</span>
                            <span class="mobile-card-pill mobile-card-pill-blue">{{ $item->kategori_asrama }}</span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Penghuni</span>
                            <span class="mobile-card-pill mobile-card-pill-green">{{ $item->jumlah_siswa }}{{ $item->kapasitas ? '/' . $item->kapasitas : '' }} Santri</span>
                        </div>
                    </div>
                </div>

                <div class="mobile-card-actions">
                    <span class="mobile-card-action-title">Aksi</span>
                    <div class="mobile-card-action-buttons">
                        <button type="button" class="mobile-btn-pastel mobile-btn-view"
                                title="Lihat & Plotting Santri"
                                onclick="openAnggotaModal('{{ addslashes($item->nama_kamar) }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        @if(isset($item->id))
                        <button type="button" class="mobile-btn-pastel mobile-btn-edit"
                                title="Edit Kamar"
                                onclick="openEditKamarModal({{ $item->id }}, '{{ addslashes($item->nama_kamar) }}', '{{ addslashes($item->kategori_asrama) }}', '{{ $item->kapasitas ?? '' }}', '{{ addslashes($item->wali_kamar ?? '') }}', '{{ $item->urutan ?? 1 }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>

                        <button type="button" class="mobile-btn-pastel mobile-btn-delete"
                                title="Hapus Kamar"
                                onclick="confirmDeleteKamar({{ $item->id }}, '{{ addslashes($item->nama_kamar) }}', {{ $item->jumlah_siswa }})">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div style="background:#fff; border-radius:12px; padding:30px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
                Belum ada kamar asrama yang terdaftar di sistem.
            </div>
            @endforelse
        </div>
    </div>

    {{-- TAB 2: SANTRI BELUM TERDAFTAR KAMAR --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">

        {{-- Bar Penempatan Massal (Muncul saat checkbox santri dipilih) --}}
        <div class="pk-bulk-bar" id="bulkBarBelum" style="display:none;">
            <div class="pk-bulk-info">
                <span class="pk-bulk-count-badge" id="bulkCountBelum">0</span>
                <span>santri dipilih untuk ditempatkan ke kamar</span>
            </div>
            <div class="pk-bulk-actions">
                <button type="button" class="pk-btn-clean pk-btn-sm" onclick="clearBelumSelection()">Batal Pilih</button>
                <button type="button" class="pk-btn-primary pk-btn-sm" onclick="openMutasiBulkBelum()">
                    Tempatkan ke Kamar &rarr;
                </button>
            </div>
        </div>

        <div class="pk-table-card">
            <div class="pk-table-wrapper">
                <table class="pk-table" id="tableBelum">
                    <thead>
                        <tr>
                            <th style="width: 42px; text-align:center;">
                                <input type="checkbox" id="checkAllBelum" class="pk-checkbox" onchange="toggleSelectAllBelum(this.checked)" title="Pilih Semua">
                            </th>
                            <th class="pk-th-aksi-belum" style="width: 145px;">AKSI</th>
                            <th class="pk-th-nama">NAMA SANTRI</th>
                            <th class="pk-th-jenjang" style="width: 160px;">JENJANG / KELAS</th>
                            <th class="pk-th-nisn" style="width: 150px;">NISN</th>
                            <th class="pk-th-status" style="width: 145px; text-align: right;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($belumTerdaftar as $siswa)
                        @php
                            $jenjangSiswa = $siswa->jenjang ?? ($siswa->pendaftaran?->jenjang ?? 'Reguler');
                            $jenjangLower = strtolower($jenjangSiswa);
                        @endphp
                        <tr class="pk-row-siswa"
                            data-siswa-id="{{ $siswa->id }}"
                            data-siswa-nama="{{ $siswa->nama_lengkap }}"
                            data-jenjang="{{ $jenjangLower }}">
                            <td style="text-align:center;">
                                <input type="checkbox"
                                       class="pk-checkbox js-check-belum"
                                       value="{{ $siswa->id }}"
                                       data-nama="{{ $siswa->nama_lengkap }}"
                                       onchange="updateBelumSelectionState()">
                            </td>
                            <td class="pk-td-aksi-belum">
                                <button type="button"
                                        class="pk-btn-mutasi-row"
                                        onclick="openMutasiSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')"
                                        title="Tempatkan ke Kamar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                    <span>Pilih Kamar</span>
                                </button>
                            </td>
                            <td class="pk-td-nama">
                                <span class="pk-nama-text">{{ $siswa->nama_lengkap }}</span>
                                <div class="pk-nisn-sub-mobile">
                                    {{ $jenjangSiswa }}{{ $siswa->kelas ? ' (' . $siswa->kelas . ')' : '' }} &bull; NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}
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
                            <td class="pk-td-status" style="text-align: right;">
                                <span class="pk-status-pill">Belum Berkamar</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="pk-td-empty">Seluruh santri aktif telah ditempatkan ke kamar masing-masing.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card List: Santri Belum Terdaftar --}}
        <div class="mobile-card-list">
            @forelse($belumTerdaftar as $siswa)
            @php
                $jenjangSiswa = $siswa->jenjang ?? ($siswa->pendaftaran?->jenjang ?? 'Reguler');
                $jenjangLower = strtolower($jenjangSiswa);
            @endphp
            <div class="mobile-card-item" data-jenjang="{{ $jenjangLower }}">
                <div class="mobile-card-info">
                    <div class="mobile-card-title">{{ $siswa->nama_lengkap }}</div>
                    <div class="mobile-card-subtitle">NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</div>

                    <div class="mobile-card-meta-grid">
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Jenjang</span>
                            <span class="mobile-card-pill mobile-card-pill-blue">{{ $jenjangSiswa }}{{ $siswa->kelas ? ' • ' . $siswa->kelas : '' }}</span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">&nbsp;</span>
                            <span class="status-pill status-pill-warning">Belum Berkamar</span>
                        </div>
                    </div>
                </div>

                <div class="mobile-card-actions">
                    <span class="mobile-card-action-title">Aksi</span>
                    <div class="mobile-card-action-buttons">
                        <button type="button" class="mobile-btn-pastel mobile-btn-view"
                                title="Tempatkan ke Kamar"
                                onclick="openMutasiSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div style="background:#fff; border-radius:12px; padding:30px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
                Seluruh santri aktif telah ditempatkan ke kamar masing-masing.
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- MODAL 1: LIHAT & KELOLA PENGHUNI KAMAR --}}
<div class="pk-modal-backdrop" id="modalAnggota" style="display:none;">
    <div class="pk-modal-dialog">
        <div class="pk-modal-content">

            <div class="pk-modal-header">
                <div>
                    <h3 class="pk-modal-title" id="modalAnggotaTitle">Penghuni Kamar</h3>
                    <span class="pk-modal-subtitle" id="modalAnggotaSubtitle">Daftar santri terdaftar</span>
                </div>
                <button type="button" class="pk-modal-close" onclick="closeAnggotaModal()">&times;</button>
            </div>

            <div class="pk-modal-body">
                {{-- Pencarian Cepat & Bulk Action di dalam Modal Penghuni Kamar --}}
                <div class="pk-modal-toolbar">
                    <div class="pk-search-box" style="width:100%;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-search-icon">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text"
                               id="modalAnggotaSearch"
                               class="pk-search-input"
                               placeholder="Cari nama santri atau jenjang di kamar ini..."
                               onkeyup="filterModalAnggotaRows()">
                    </div>

                    <div class="pk-bulk-bar" id="bulkBarModal" style="display:none; margin-top:10px;">
                        <div class="pk-bulk-info">
                            <span class="pk-bulk-count-badge" id="bulkCountModal">0</span>
                            <span>santri dipilih</span>
                        </div>
                        <div class="pk-bulk-actions">
                            <button type="button" class="pk-btn-action-sm" onclick="openMutasiBulkModal()">Pindah Kamar</button>
                            <button type="button" class="pk-btn-action-sm pk-btn-unassign" onclick="unassignBulkModal()">Keluarkan</button>
                        </div>
                    </div>
                </div>

                <div class="pk-modal-table-wrap">
                    <table class="pk-modal-table">
                        <thead>
                            <tr>
                                <th style="width:36px; text-align:center;">
                                    <input type="checkbox" id="checkAllModalAnggota" class="pk-checkbox" onchange="toggleSelectAllModal(this.checked)" title="Pilih Semua">
                                </th>
                                <th style="width:38px; text-align:center;">NO</th>
                                <th>NAMA SANTRI</th>
                                <th style="width:110px;">JENJANG</th>
                                <th style="width:120px;">NISN</th>
                                <th style="width:150px; text-align:right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="modalAnggotaBody">
                            <tr>
                                <td colspan="6" style="text-align:center; padding:20px; color:#64748B;">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pk-modal-footer">
                <button type="button" class="pk-btn-clean" onclick="closeAnggotaModal()">Tutup</button>
            </div>

        </div>
    </div>
</div>

{{-- MODAL 2: TEMPATKAN / PINDAH KAMAR SANTRI (TUNGGAL & MASSAL) --}}
<div class="pk-modal-backdrop" id="modalMutasi" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-kamar.mutasi') }}" method="POST" id="formMutasi">
                @csrf
                <input type="hidden" name="siswa_id" id="mutasiSiswaId">

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Penempatan Kamar Santri</h3>
                        <span class="pk-modal-subtitle" id="mutasiSiswaNama">-</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeMutasiModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="selectKamarTujuan" class="pk-form-label">Pilih Kamar Tujuan <span style="color:#EF4444;">*</span></label>
                        <select name="kamar" id="selectKamarTujuan" class="pk-form-select" required>
                            <option value="">-- Pilih Kamar Asrama --</option>
                            @foreach($dataKamar as $kItem)
                                <option value="{{ $kItem->nama_kamar }}">
                                    {{ $kItem->nama_kamar_display }} ({{ $kItem->kategori_asrama }} • {{ $kItem->jumlah_siswa }}{{ $kItem->kapasitas ? '/' . $kItem->kapasitas : '' }} Santri)
                                </option>
                            @endforeach
                        </select>
                        <span class="pk-form-hint">Jika kamar tujuan belum ada di daftar, silakan tambahkan terlebih dahulu melalui tombol <strong>+ Tambah Kamar</strong>.</span>
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

{{-- MODAL 3: TAMBAH KAMAR ASRAMA BARU --}}
<div class="pk-modal-backdrop" id="modalTambahKamar" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="{{ route('plotting-kamar.store-kamar') }}" method="POST" id="formTambahKamar">
                @csrf

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Tambah Kamar Baru</h3>
                        <span class="pk-modal-subtitle">{{ $pondokLembaga->nama ?? 'Pondok Pesantren Al Muttaqin' }}</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeTambahKamarModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="inputNamaKamar" class="pk-form-label">Nama Kamar <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kamar"
                               id="inputNamaKamar"
                               class="pk-form-input"
                               placeholder="Contoh: Abu Bakar, Umar 1, Aisyah, atau Reguler"
                               required
                               autocomplete="off">
                        <span class="pk-form-hint">Nama kamar otomatis terhubung ke Data Santri dan Filter Sesi Presensi NFC/RFID.</span>
                    </div>

                    <div class="pk-form-row-2">
                        <div class="pk-form-group">
                            <label for="selectKategoriAsrama" class="pk-form-label">Kategori Asrama <span style="color:#EF4444;">*</span></label>
                            <select name="kategori_asrama" id="selectKategoriAsrama" class="pk-form-select" required>
                                <option value="Putra">Asrama Putra</option>
                                <option value="Putri">Asrama Putri</option>
                                <option value="Umum">Umum / Gabungan</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="inputKapasitas" class="pk-form-label">Kapasitas (Opsional)</label>
                            <input type="number"
                                   name="kapasitas"
                                   id="inputKapasitas"
                                   class="pk-form-input"
                                   min="1"
                                   max="1000"
                                   placeholder="Misal: 20">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="inputWaliKamar" class="pk-form-label">Musyrif / Pamong Kamar (Opsional)</label>
                        <input type="text"
                               name="wali_kamar"
                               id="inputWaliKamar"
                               class="pk-form-input"
                               placeholder="Contoh: Ustadz Ahmad Fauzi"
                               autocomplete="off">
                    </div>

                    <div class="pk-form-group">
                        <label for="inputUrutan" class="pk-form-label">Nomor Urutan Tampilan (Opsional)</label>
                        <input type="number"
                               name="urutan"
                               id="inputUrutan"
                               class="pk-form-input"
                               min="0"
                               placeholder="Otomatis di akhir jika dikosongkan">
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeTambahKamarModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Kamar</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- MODAL 4: EDIT KAMAR ASRAMA --}}
<div class="pk-modal-backdrop" id="modalEditKamar" style="display:none;">
    <div class="pk-modal-dialog pk-modal-dialog-sm">
        <div class="pk-modal-content">

            <form action="" method="POST" id="formEditKamar">
                @csrf
                @method('PUT')

                <div class="pk-modal-header">
                    <div>
                        <h3 class="pk-modal-title">Edit Data Kamar</h3>
                        <span class="pk-modal-subtitle">Perubahan nama otomatis disinkronkan ke data santri</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeEditKamarModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <div class="pk-form-group">
                        <label for="editNamaKamar" class="pk-form-label">Nama Kamar <span style="color:#EF4444;">*</span></label>
                        <input type="text"
                               name="nama_kamar"
                               id="editNamaKamar"
                               class="pk-form-input"
                               required
                               autocomplete="off">
                    </div>

                    <div class="pk-form-row-2">
                        <div class="pk-form-group">
                            <label for="editKategoriAsrama" class="pk-form-label">Kategori Asrama <span style="color:#EF4444;">*</span></label>
                            <select name="kategori_asrama" id="editKategoriAsrama" class="pk-form-select" required>
                                <option value="Putra">Asrama Putra</option>
                                <option value="Putri">Asrama Putri</option>
                                <option value="Umum">Umum / Gabungan</option>
                            </select>
                        </div>

                        <div class="pk-form-group">
                            <label for="editKapasitas" class="pk-form-label">Kapasitas (Opsional)</label>
                            <input type="number"
                                   name="kapasitas"
                                   id="editKapasitas"
                                   class="pk-form-input"
                                   min="1"
                                   max="1000"
                                   placeholder="Kosongkan jika bebas">
                        </div>
                    </div>

                    <div class="pk-form-group">
                        <label for="editWaliKamar" class="pk-form-label">Musyrif / Pamong Kamar</label>
                        <input type="text"
                               name="wali_kamar"
                               id="editWaliKamar"
                               class="pk-form-input"
                               placeholder="Contoh: Ustadz Ahmad Fauzi"
                               autocomplete="off">
                    </div>

                    <div class="pk-form-group">
                        <label for="editUrutan" class="pk-form-label">Nomor Urutan Tampilan</label>
                        <input type="number"
                               name="urutan"
                               id="editUrutan"
                               min="0"
                               class="pk-form-input">
                    </div>
                </div>

                <div class="pk-modal-footer">
                    <button type="button" class="pk-btn-clean" onclick="closeEditKamarModal()">Batal</button>
                    <button type="submit" class="pk-btn-primary">Simpan Perubahan</button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- FORM HIDDEN UNTUK UNASSIGN / KELUARKAN SANTRI DARI KAMAR --}}
<form action="{{ route('plotting-kamar.unassign') }}" method="POST" id="formUnassign" style="display:none;">
    @csrf
    <input type="hidden" name="siswa_id" id="unassignSiswaId">
</form>

<style>
/* Main Container */
.pk-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
}

/* Header Action Buttons */
.pk-header-actions-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.pk-btn-icon {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
}

.pk-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #FFFFFF;
    color: var(--text-primary, #0F172A);
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.pk-btn-secondary:hover {
    background: #F8FAFC;
    border-color: #CBD5E1;
}
.dark-mode .pk-btn-secondary {
    background: #1E293B;
    border-color: #334155;
    color: #F8FAFC;
}
.dark-mode .pk-btn-secondary:hover {
    background: #2D3D54;
}

.pk-btn-add-kelas {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 15px;
    background: #0052FF;
    color: #FFFFFF;
    border: 1px solid #0052FF;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0, 82, 255, 0.25);
}
.pk-btn-add-kelas:hover {
    background: #0043D4;
    border-color: #0043D4;
}

/* Alert Notification */
.pk-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
}
.pk-alert-success {
    background: #F0FDF4;
    border: 1px solid #BBF7D0;
    color: #166534;
}
.dark-mode .pk-alert-success {
    background: rgba(34, 197, 94, 0.15);
    border-color: rgba(34, 197, 94, 0.3);
    color: #86EFAC;
}
.pk-alert-danger {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    color: #991B1B;
}
.dark-mode .pk-alert-danger {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.3);
    color: #FCA5A5;
}
.pk-alert-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}
.pk-alert-close {
    margin-left: auto;
    background: transparent;
    border: none;
    font-size: 18px;
    color: inherit;
    cursor: pointer;
    padding: 0 4px;
}

/* Topbar: Segmented Tabs & Search */
.pk-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.pk-topbar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.pk-tabs-nav {
    display: inline-flex;
    background: #FFFFFF;
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.dark-mode .pk-tabs-nav {
    background: #141C28;
    border-color: #232E40;
}

.pk-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary, #64748B);
    cursor: pointer;
    transition: all 0.2s ease;
}
.pk-tab-item:hover {
    color: var(--text-primary, #0F172A);
    background: rgba(0,0,0,0.03);
}
.dark-mode .pk-tab-item:hover {
    background: rgba(255,255,255,0.05);
}
.pk-tab-item.active {
    background: #0052FF;
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(0, 82, 255, 0.25);
}

.pk-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #F1F5F9;
    color: #475569;
}
.pk-tab-item.active .pk-tab-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #FFFFFF;
}
.dark-mode .pk-tab-badge {
    background: #1E293B;
    color: #94A3B8;
}
.pk-tab-badge.pk-badge-notice {
    background: #FEE2E2;
    color: #DC2626;
}
.pk-tab-item.active .pk-tab-badge.pk-badge-notice {
    background: #EF4444;
    color: #FFFFFF;
}

/* Filter Select & Search Box */
.pk-filter-select {
    height: 38px;
    padding: 0 12px;
    background: #FFFFFF;
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 10px;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--text-primary, #0F172A);
    outline: none;
    cursor: pointer;
}
.dark-mode .pk-filter-select {
    background: #141C28;
    border-color: #232E40;
    color: #F8FAFC;
}
.pk-filter-select:focus {
    border-color: #0052FF;
}

.pk-search-box {
    position: relative;
    width: 265px;
}
.pk-search-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    width: 15px;
    height: 15px;
    color: #94A3B8;
    pointer-events: none;
}
.pk-search-input {
    width: 100%;
    height: 38px;
    padding: 0 12px 0 34px;
    background: #FFFFFF;
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 10px;
    font-size: 13px;
    color: var(--text-primary, #0F172A);
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.dark-mode .pk-search-input {
    background: #141C28;
    border-color: #232E40;
    color: #F8FAFC;
}
.pk-search-input:focus {
    border-color: #0052FF;
    box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
}

/* Bulk Action Bar */
.pk-bulk-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px;
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-radius: 10px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.dark-mode .pk-bulk-bar {
    background: rgba(0, 82, 255, 0.14);
    border-color: rgba(59, 130, 246, 0.35);
}
.pk-bulk-info {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #1E3A8A;
}
.dark-mode .pk-bulk-info {
    color: #BFDBFE;
}
.pk-bulk-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    background: #0052FF;
    color: #FFFFFF;
    font-size: 11.5px;
    font-weight: 700;
}
.pk-bulk-actions {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.pk-btn-sm {
    padding: 6px 12px !important;
    font-size: 12px !important;
}
.pk-checkbox {
    width: 16px;
    height: 16px;
    accent-color: #0052FF;
    cursor: pointer;
}

/* Table Card */
.pk-tab-pane {
    display: none;
}
.pk-tab-pane.active {
    display: block;
}

.pk-table-card {
    background: #FFFFFF;
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.dark-mode .pk-table-card {
    background: #141C28;
    border-color: #232E40;
}

.pk-table-wrapper {
    overflow-x: auto;
    width: 100%;
}

.pk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    text-align: left;
}

.pk-table th {
    background: #F8FAFC;
    color: #64748B;
    font-weight: 700;
    font-size: 11.5px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 13px 18px;
    border-bottom: 1px solid var(--border-light, #E2E8F0);
    white-space: nowrap;
}
.dark-mode .pk-table th {
    background: #101722;
    color: #94A3B8;
    border-color: #232E40;
}

.pk-table td {
    padding: 13px 18px;
    border-bottom: 1px solid var(--border-light, #E2E8F0);
    color: var(--text-primary, #0F172A);
    vertical-align: middle;
}
.dark-mode .pk-table td {
    border-color: #1E293B;
    color: #F8FAFC;
}

.pk-table tbody tr:hover {
    background: #F8FAFD;
}
.dark-mode .pk-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.pk-td-empty {
    text-align: center;
    padding: 36px 16px;
    color: #94A3B8;
    font-style: italic;
}

/* Kolom Aksi Group */
.pk-actions-group {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Action Button: Eye Icon */
.pk-btn-eye {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(0, 82, 255, 0.08);
    border: 1px solid rgba(0, 82, 255, 0.15);
    color: #0052FF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pk-btn-eye:hover {
    background: #0052FF;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0, 82, 255, 0.25);
}
.pk-btn-eye svg {
    width: 16px;
    height: 16px;
}

/* Action Button: Edit Icon */
.pk-btn-edit-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.2);
    color: #D97706;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pk-btn-edit-action:hover {
    background: #F59E0B;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(245, 158, 11, 0.25);
}
.pk-btn-edit-action svg {
    width: 15px;
    height: 15px;
}

/* Action Button: Delete Icon */
.pk-btn-delete-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #DC2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pk-btn-delete-action:hover {
    background: #EF4444;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(239, 68, 68, 0.25);
}
.pk-btn-delete-action svg {
    width: 15px;
    height: 15px;
}

/* Asrama Badges */
.pk-tingkat-badge {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: rgba(0, 82, 255, 0.08);
    color: #0052FF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 11px;
    flex-shrink: 0;
}
.pk-asrama-icon-putri {
    background: rgba(236, 72, 153, 0.12);
    color: #DB2777;
}
.pk-asrama-icon-umum {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

.pk-asrama-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 600;
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
}
.pk-asrama-pill-putri {
    background: #FDF2F8;
    color: #BE185D;
    border-color: #FBCFE8;
}
.pk-asrama-pill-umum {
    background: #ECFDF5;
    color: #047857;
    border-color: #A7F3D0;
}
.dark-mode .pk-asrama-pill {
    background: rgba(59, 130, 246, 0.15);
    color: #93C5FD;
    border-color: rgba(59, 130, 246, 0.3);
}
.dark-mode .pk-asrama-pill-putri {
    background: rgba(236, 72, 153, 0.15);
    color: #F9A8D4;
    border-color: rgba(236, 72, 153, 0.3);
}
.dark-mode .pk-asrama-pill-umum {
    background: rgba(16, 185, 129, 0.15);
    color: #6EE7B7;
    border-color: rgba(16, 185, 129, 0.3);
}

.pk-jenjang-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    background: #F1F5F9;
    color: #334155;
}
.pk-jenjang-smp {
    background: #EFF6FF;
    color: #1D4ED8;
}
.pk-jenjang-sma {
    background: #F5F3FF;
    color: #6D28D9;
}
.pk-jenjang-reguler {
    background: #ECFDF5;
    color: #047857;
}
.dark-mode .pk-jenjang-badge {
    background: #1E293B;
    color: #CBD5E1;
}

.pk-kelas-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-primary, #0F172A);
}

.pk-tingkat-text {
    font-size: 12.5px;
    color: var(--text-secondary, #64748B);
    font-weight: 500;
}

.pk-siswa-count {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    background: #F1F5F9;
    color: #475569;
    transition: all 0.15s ease;
}
.pk-siswa-count:hover {
    background: rgba(0, 82, 255, 0.1);
    color: #0052FF;
}
.pk-siswa-count-full {
    background: #FEF3C7;
    color: #B45309;
}
.dark-mode .pk-siswa-count {
    background: #1E293B;
    color: #94A3B8;
}

/* Tab 2: Belum Terdaftar Elements */
.pk-btn-mutasi-row {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #0052FF;
    border: none;
    border-radius: 7px;
    color: #FFFFFF;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pk-btn-mutasi-row:hover {
    background: #0043D4;
    transform: translateY(-1px);
}
.pk-btn-mutasi-row svg {
    width: 14px;
    height: 14px;
}

.pk-nama-text {
    font-weight: 600;
}
.pk-nisn-text {
    font-family: monospace;
    font-size: 12px;
    color: #64748B;
}
.dark-mode .pk-nisn-text {
    color: #94A3B8;
}

.pk-status-pill {
    display: inline-flex;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #FEF2F2;
    color: #DC2626;
    border: 1px solid #FCA5A5;
}
.dark-mode .pk-status-pill {
    background: rgba(239, 68, 68, 0.15);
    color: #FCA5A5;
    border-color: rgba(239, 68, 68, 0.3);
}

/* Modals System */
.pk-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 16px;
}

.pk-modal-dialog {
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.pk-modal-dialog-sm {
    max-width: 460px;
}

.pk-modal-content {
    background: #FFFFFF;
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    border: 1px solid var(--border-light, #E2E8F0);
    animation: pkModalScale 0.2s ease-out;
}
.dark-mode .pk-modal-content {
    background: #141C28;
    border-color: #232E40;
}

@keyframes pkModalScale {
    from { transform: scale(0.96); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.pk-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-light, #E2E8F0);
}
.dark-mode .pk-modal-header {
    border-color: #232E40;
}

.pk-modal-title {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary, #0F172A);
}

.pk-modal-subtitle {
    display: block;
    font-size: 12px;
    color: var(--text-secondary, #64748B);
    margin-top: 2px;
}

.pk-modal-close {
    background: transparent;
    border: none;
    font-size: 22px;
    line-height: 1;
    color: #94A3B8;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
}
.pk-modal-close:hover {
    background: rgba(0,0,0,0.05);
    color: #0F172A;
}

.pk-modal-body {
    padding: 20px 22px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.pk-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.pk-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.pk-form-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary, #0F172A);
}

.pk-form-hint {
    font-size: 11.5px;
    color: var(--text-secondary, #64748B);
    line-height: 1.35;
}

.pk-form-input,
.pk-form-select {
    width: 100%;
    height: 40px;
    padding: 0 12px;
    border-radius: 8px;
    border: 1px solid var(--border-light, #E2E8F0);
    background: #FFFFFF;
    color: var(--text-primary, #0F172A);
    font-size: 13.5px;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}
.dark-mode .pk-form-input,
.dark-mode .pk-form-select {
    background: #101722;
    border-color: #232E40;
    color: #F8FAFC;
}
.pk-form-input:focus,
.pk-form-select:focus {
    border-color: #0052FF;
    box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
}

.pk-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 22px;
    background: #F8FAFC;
    border-top: 1px solid var(--border-light, #E2E8F0);
}
.dark-mode .pk-modal-footer {
    background: #101722;
    border-color: #232E40;
}

.pk-btn-clean {
    background: transparent;
    border: 1px solid var(--border-light, #CBD5E1);
    color: var(--text-primary, #475569);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.dark-mode .pk-btn-clean {
    border-color: #334155;
    color: #CBD5E1;
}

.pk-btn-primary {
    background: #0052FF;
    color: #FFFFFF;
    border: none;
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 82, 255, 0.25);
    transition: all 0.15s ease;
}
.pk-btn-primary:hover {
    background: #0043D4;
}

/* Modal Inner Table */
.pk-modal-table-wrap {
    max-height: 380px;
    overflow-y: auto;
    border: 1px solid var(--border-light, #E2E8F0);
    border-radius: 10px;
}
.dark-mode .pk-modal-table-wrap {
    border-color: #232E40;
}

.pk-modal-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.pk-modal-table th {
    background: #F8FAFC;
    padding: 10px 12px;
    font-weight: 700;
    font-size: 11px;
    color: #64748B;
    border-bottom: 1px solid #E2E8F0;
    position: sticky;
    top: 0;
    z-index: 2;
}
.dark-mode .pk-modal-table th {
    background: #101722;
    color: #94A3B8;
    border-color: #232E40;
}

.pk-modal-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #F1F5F9;
    color: var(--text-primary, #0F172A);
}
.dark-mode .pk-modal-table td {
    border-color: #1E293B;
    color: #F8FAFC;
}

.pk-btn-action-sm {
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #BFDBFE;
}
.pk-btn-action-sm:hover {
    background: #DBEAFE;
}
.pk-btn-unassign {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #FECACA;
}
.pk-btn-unassign:hover {
    background: #FEE2E2;
}

/* Responsive Mobile (max-width: 768px) */
.pk-kelas-sub-mobile,
.pk-nisn-sub-mobile {
    display: none;
}

@media (max-width: 768px) {
    .pk-header-actions-wrap {
        width: 100% !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    .pk-header-actions-wrap form {
        display: inline-flex !important;
    }
    .pk-btn-secondary,
    .pk-btn-add-kelas {
        padding: 6px 12px !important;
        font-size: 12px !important;
    }

    .pk-topbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .pk-topbar-right {
        flex-direction: column !important;
        align-items: stretch !important;
        width: 100% !important;
    }
    .pk-tabs-nav {
        width: 100% !important;
        display: flex !important;
    }
    .pk-tab-item {
        flex: 1 !important;
        justify-content: center !important;
        padding: 8px 10px !important;
        font-size: 12.5px !important;
    }
    .pk-filter-select,
    .pk-search-box,
    .pk-search-input {
        width: 100% !important;
    }

    .pk-form-row-2 {
        grid-template-columns: 1fr !important;
    }

    .pk-table th,
    .pk-table td {
        padding: 10px 8px !important;
        font-size: 12.5px !important;
    }

    .pk-th-asrama,
    .pk-td-asrama,
    .pk-th-wali,
    .pk-td-wali,
    .pk-th-jenjang,
    .pk-td-jenjang,
    .pk-th-nisn,
    .pk-td-nisn {
        display: none !important;
    }

    .pk-kelas-sub-mobile,
    .pk-nisn-sub-mobile {
        display: block !important;
        font-size: 11px !important;
        color: var(--text-secondary, #64748B) !important;
        margin-top: 2px !important;
    }

    .pk-modal-backdrop {
        padding: 10px !important;
    }
    .pk-modal-dialog {
        max-height: 94vh !important;
        margin: auto !important;
    }
    .pk-modal-header {
        padding: 12px 16px !important;
    }
    .pk-modal-body {
        padding: 14px 16px !important;
        gap: 12px !important;
    }
    .pk-modal-footer {
        padding: 12px 16px !important;
    }
}
</style>

<script>
// Tab Switching
function switchTab(tab) {
    document.querySelectorAll('.pk-tab-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.pk-tab-pane').forEach(p => p.classList.remove('active'));

    if (tab === 'kamar') {
        document.getElementById('tabBtnKamar').classList.add('active');
        document.getElementById('paneKamar').classList.add('active');
    } else {
        document.getElementById('tabBtnBelum').classList.add('active');
        document.getElementById('paneBelum').classList.add('active');
    }

    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);

    filterTableRows();
}

// Client-side quick filter (Search + Kategori Asrama / Jenjang)
function filterTableRows() {
    const q = (document.getElementById('pkSearchInput')?.value || '').toLowerCase().trim();
    const filterVal = (document.getElementById('pkFilterSelect')?.value || '').toLowerCase().trim();

    const asramaFilter = filterVal.startsWith('asrama:') ? filterVal.replace('asrama:', '') : '';
    const jenjangFilter = filterVal.startsWith('jenjang:') ? filterVal.replace('jenjang:', '') : '';

    // Filter baris di Tab Kamar
    document.querySelectorAll('#tableKamar tbody tr.pk-row-kamar').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        const rowAsrama = (tr.dataset.asrama || '').toLowerCase();
        const matchSearch = !q || text.includes(q);
        const matchAsrama = !asramaFilter || rowAsrama === asramaFilter;
        tr.style.display = (matchSearch && matchAsrama) ? '' : 'none';
    });

    // Filter baris di Tab Belum Terdaftar
    document.querySelectorAll('#tableBelum tbody tr.pk-row-siswa').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        const rowJenjang = (tr.dataset.jenjang || '').toLowerCase();
        const matchSearch = !q || text.includes(q);
        const matchJenjang = !jenjangFilter || rowJenjang === jenjangFilter;
        tr.style.display = (matchSearch && matchJenjang) ? '' : 'none';
    });

    // Filter kartu mobile di kedua tab
    document.querySelectorAll('#paneKamar .mobile-card-item').forEach(card => {
        const text = card.innerText.toLowerCase();
        const rowAsrama = (card.dataset.asrama || '').toLowerCase();
        const matchSearch = !q || text.includes(q);
        const matchAsrama = !asramaFilter || rowAsrama === asramaFilter;
        card.style.display = (matchSearch && matchAsrama) ? '' : 'none';
    });

    document.querySelectorAll('#paneBelum .mobile-card-item').forEach(card => {
        const text = card.innerText.toLowerCase();
        const rowJenjang = (card.dataset.jenjang || '').toLowerCase();
        const matchSearch = !q || text.includes(q);
        const matchJenjang = !jenjangFilter || rowJenjang === jenjangFilter;
        card.style.display = (matchSearch && matchJenjang) ? '' : 'none';
    });
}

// ==========================================
// BULK SELECTION: TAB BELUM TERDAFTAR
// ==========================================
function toggleSelectAllBelum(checked) {
    document.querySelectorAll('#tableBelum tbody tr.pk-row-siswa').forEach(tr => {
        if (tr.style.display === 'none') return;
        const cb = tr.querySelector('.js-check-belum');
        if (cb) cb.checked = checked;
    });
    updateBelumSelectionState();
}

function updateBelumSelectionState() {
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-belum:checked'));
    const bar = document.getElementById('bulkBarBelum');
    const countEl = document.getElementById('bulkCountBelum');
    if (countEl) countEl.textContent = checkedBoxes.length;
    if (bar) bar.style.display = checkedBoxes.length > 0 ? 'flex' : 'none';
}

function clearBelumSelection() {
    document.querySelectorAll('.js-check-belum').forEach(cb => cb.checked = false);
    const checkAll = document.getElementById('checkAllBelum');
    if (checkAll) checkAll.checked = false;
    updateBelumSelectionState();
}

function openMutasiBulkBelum() {
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-belum:checked'));
    if (checkedBoxes.length === 0) return;

    const ids = checkedBoxes.map(cb => cb.value).join(',');
    const label = checkedBoxes.length === 1
        ? checkedBoxes[0].dataset.nama
        : `${checkedBoxes.length} Santri Terpilih`;

    document.getElementById('mutasiSiswaId').value = ids;
    document.getElementById('mutasiSiswaNama').textContent = label;
    document.getElementById('modalMutasi').style.display = 'flex';
}

// ==========================================
// MODAL ANGGOTA / PENGHUNI KAMAR
// ==========================================
function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openAnggotaModal(namaKamar) {
    const modal = document.getElementById('modalAnggota');
    const title = document.getElementById('modalAnggotaTitle');
    const subtitle = document.getElementById('modalAnggotaSubtitle');
    const tbody = document.getElementById('modalAnggotaBody');
    const searchInput = document.getElementById('modalAnggotaSearch');
    const checkAll = document.getElementById('checkAllModalAnggota');

    const displayTitle = namaKamar.toLowerCase().startsWith('kamar ') ? namaKamar : `Kamar ${namaKamar}`;
    title.textContent = `Penghuni ${displayTitle}`;
    subtitle.textContent = 'Memuat daftar santri...';
    if (searchInput) searchInput.value = '';
    if (checkAll) checkAll.checked = false;
    document.getElementById('bulkBarModal').style.display = 'none';
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#64748B;">Memuat data...</td></tr>`;

    modal.style.display = 'flex';

    fetch(`/plotting-kamar/anggota/${encodeURIComponent(namaKamar)}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) throw new Error('Gagal memuat');

        subtitle.textContent = `${res.total} Santri Terdaftar`;
        tbody.innerHTML = '';

        if (res.anggota.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:24px; color:#94A3B8;">Belum ada santri yang ditempatkan di kamar ini.</td></tr>`;
            return;
        }

        res.anggota.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'js-modal-anggota-row';
            const jenjangLabel = item.kelas && item.kelas !== '-' ? `${item.jenjang} • ${item.kelas}` : item.jenjang;
            const safeNamaJs = String(item.nama_lengkap || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");

            tr.innerHTML = `
                <td style="text-align:center;">
                    <input type="checkbox" class="pk-checkbox js-check-modal" value="${item.id}" data-nama="${escapeHtml(item.nama_lengkap)}" onchange="updateModalSelectionState()">
                </td>
                <td style="text-align:center; font-weight:600; color:#64748B;">${idx + 1}</td>
                <td style="font-weight:600;">${escapeHtml(item.nama_lengkap)}</td>
                <td><span class="pk-jenjang-badge pk-jenjang-${escapeHtml(String(item.jenjang || 'reguler').toLowerCase())}">${escapeHtml(jenjangLabel)}</span></td>
                <td style="font-family:monospace; font-size:12px; color:#64748B;">${escapeHtml(item.nisn)}</td>
                <td style="text-align:right;">
                    <div style="display:inline-flex; gap:6px;">
                        <button type="button" class="pk-btn-action-sm" onclick="openMutasiSingleModal(${item.id}, '${safeNamaJs}')" title="Pindah Kamar">
                            Mutasi
                        </button>
                        <button type="button" class="pk-btn-action-sm pk-btn-unassign" onclick="unassignSiswa(${item.id}, '${safeNamaJs}')" title="Keluarkan dari Kamar">
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
        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:#EF4444;">Gagal memuat data penghuni kamar.</td></tr>`;
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
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-modal:checked'));
    const bar = document.getElementById('bulkBarModal');
    const countEl = document.getElementById('bulkCountModal');
    if (countEl) countEl.textContent = checkedBoxes.length;
    if (bar) bar.style.display = checkedBoxes.length > 0 ? 'flex' : 'none';
}

function openMutasiBulkModal() {
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-modal:checked'));
    if (checkedBoxes.length === 0) return;

    const ids = checkedBoxes.map(cb => cb.value).join(',');
    const label = checkedBoxes.length === 1
        ? checkedBoxes[0].dataset.nama
        : `${checkedBoxes.length} Santri Terpilih`;

    document.getElementById('mutasiSiswaId').value = ids;
    document.getElementById('mutasiSiswaNama').textContent = label;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function unassignBulkModal() {
    const checkedBoxes = Array.from(document.querySelectorAll('.js-check-modal:checked'));
    if (checkedBoxes.length === 0) return;

    if (confirm(`Keluarkan ${checkedBoxes.length} santri terpilih dari kamar ini? Santri akan dipindahkan ke tab Belum Terdaftar.`)) {
        document.getElementById('unassignSiswaId').value = checkedBoxes.map(cb => cb.value).join(',');
        document.getElementById('formUnassign').submit();
    }
}

function closeAnggotaModal() {
    document.getElementById('modalAnggota').style.display = 'none';
}

// Modal Mutasi Tunggal
function openMutasiSingleModal(siswaId, namaSiswa) {
    document.getElementById('mutasiSiswaId').value = siswaId;
    document.getElementById('mutasiSiswaNama').textContent = namaSiswa;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function closeMutasiModal() {
    document.getElementById('modalMutasi').style.display = 'none';
}

// Modal Tambah Kamar
function openTambahKamarModal() {
    document.getElementById('modalTambahKamar').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('inputNamaKamar').focus();
    }, 100);
}

function closeTambahKamarModal() {
    document.getElementById('modalTambahKamar').style.display = 'none';
}

// Modal Edit Kamar
function openEditKamarModal(id, namaKamar, kategoriAsrama, kapasitas, waliKamar, urutan) {
    const form = document.getElementById('formEditKamar');
    form.action = `/plotting-kamar/kamar/${id}`;
    document.getElementById('editNamaKamar').value = namaKamar;
    document.getElementById('editKategoriAsrama').value = kategoriAsrama || 'Putra';
    document.getElementById('editKapasitas').value = kapasitas || '';
    document.getElementById('editWaliKamar').value = waliKamar || '';
    document.getElementById('editUrutan').value = urutan || '';
    document.getElementById('modalEditKamar').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('editNamaKamar').focus();
    }, 100);
}

function closeEditKamarModal() {
    document.getElementById('modalEditKamar').style.display = 'none';
}

// Hapus Kamar dengan Konfirmasi
function confirmDeleteKamar(id, namaKamar, jumlahSiswa) {
    if (jumlahSiswa > 0) {
        alert(`Kamar ${namaKamar} tidak dapat dihapus karena masih ditempati oleh ${jumlahSiswa} santri aktif. Silakan pindahkan atau keluarkan santri terlebih dahulu.`);
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus kamar ${namaKamar}?`)) {
        const form = document.getElementById(`formDeleteKamar_${id}`);
        if (form) form.submit();
    }
}

// Unassign Santri Tunggal
function unassignSiswa(siswaId, namaSiswa) {
    if (confirm(`Keluarkan ${namaSiswa} dari kamar ini? Santri akan masuk ke tab Belum Terdaftar.`)) {
        document.getElementById('unassignSiswaId').value = siswaId;
        document.getElementById('formUnassign').submit();
    }
}

// Close modals when clicking backdrop
document.addEventListener('click', function(e) {
    const mAnggota = document.getElementById('modalAnggota');
    const mMutasi = document.getElementById('modalMutasi');
    const mTambah = document.getElementById('modalTambahKamar');
    const mEdit = document.getElementById('modalEditKamar');

    if (e.target === mAnggota) closeAnggotaModal();
    if (e.target === mMutasi) closeMutasiModal();
    if (e.target === mTambah) closeTambahKamarModal();
    if (e.target === mEdit) closeEditKamarModal();
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnggotaModal();
        closeMutasiModal();
        closeTambahKamarModal();
        closeEditKamarModal();
    }
});
</script>
@endsection

