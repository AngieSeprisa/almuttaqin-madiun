@extends('layouts.app')

@section('title', 'Plotting Kelas')

@section('header-actions')
<div class="pk-header-actions-wrap">
    {{-- Tombol Muat Standar --}}
    <form action="{{ route('plotting-kelas.muat-standar') }}" method="POST" class="d-inline" onsubmit="return confirm('Muat kelas standar untuk jenjang {{ $jenjang }}?');">
        @csrf
        <input type="hidden" name="lembaga_id" value="{{ $selectedLembaga->id }}">
        <input type="hidden" name="jenjang" value="{{ $jenjang }}">
        <button type="submit" class="pk-btn-secondary" title="Muat kelas standar SMP (VII A-IX B) atau SMA (X A-XII B)">
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

    {{-- Bar Kontrol & Tab Navigasi --}}
    <div class="pk-topbar">
        {{-- Segmented Tabs --}}
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

        {{-- Pencarian Cepat --}}
        <div class="pk-search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-search-icon">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text"
                   id="pkSearchInput"
                   class="pk-search-input"
                   placeholder="Cari kelas atau siswa..."
                   onkeyup="filterTableRows()">
        </div>
    </div>

    {{-- TAB 1: DAFTAR KELAS --}}
    <div class="pk-tab-pane {{ $activeTab === 'kelas' ? 'active' : '' }}" id="paneKelas">
        <div class="pk-table-card">
            <div class="pk-table-wrapper">
                <table class="pk-table" id="tableKelas">
                    <thead>
                        <tr>
                            <th class="pk-th-aksi" style="width: 140px;">AKSI</th>
                            <th class="pk-th-kelas">KELAS</th>
                            <th class="pk-th-tingkat" style="width: 130px;">TINGKAT</th>
                            <th class="pk-th-jumlah" style="width: 160px; text-align: right;">JUMLAH SISWA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataKelas as $item)
                        <tr class="pk-row-kelas" data-kelas="{{ $item->nama_kelas }}">
                            {{-- Kolom Aksi di sebelah kiri: Logo Mata, Edit Kelas, Hapus Kelas --}}
                            <td class="pk-td-aksi">
                                <div class="pk-actions-group">
                                    <button type="button"
                                            class="pk-btn-eye"
                                            title="Lihat & Plotting Siswa Kelas {{ $item->nama_kelas }}"
                                            onclick="openAnggotaModal('{{ $item->nama_kelas }}')">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>

                                    @if(isset($item->id))
                                    <button type="button"
                                            class="pk-btn-edit-action"
                                            title="Edit Ruang Kelas {{ $item->nama_kelas }}"
                                            onclick="openEditKelasModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->tingkat ?? 7 }}, {{ $item->urutan ?? 1 }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    <button type="button"
                                            class="pk-btn-delete-action"
                                            title="Hapus Ruang Kelas {{ $item->nama_kelas }}"
                                            onclick="confirmDeleteKelas({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->jumlah_siswa }})">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>

                                    <form id="formDeleteKelas_{{ $item->id }}" action="{{ route('plotting-kelas.destroy-kelas', $item->id) }}" method="POST" style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endif
                                </div>
                            </td>
                            <td class="pk-td-kelas">
                                <div class="pk-kelas-cell-content" style="display:flex; align-items:center; gap:10px;">
                                    <div class="pk-tingkat-badge" title="Tingkat {{ $item->tingkat ?? 7 }}">{{ $item->tingkat ?? 7 }}</div>
                                    <div class="pk-kelas-name-wrap">
                                        <span class="pk-kelas-name">Kelas {{ $item->nama_kelas_display ?? $item->nama_kelas }}</span>
                                        <div class="pk-kelas-sub-mobile">Tingkat {{ $item->tingkat ?? 7 }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pk-td-tingkat">
                                <span class="pk-tingkat-text">Tingkat {{ $item->tingkat ?? 7 }}</span>
                            </td>
                            <td class="pk-td-jumlah" style="text-align: right;">
                                <span class="pk-siswa-count" onclick="openAnggotaModal('{{ $item->nama_kelas }}')" style="cursor:pointer;" title="Klik untuk lihat anggota">{{ $item->jumlah_siswa }} Siswa</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="pk-td-empty">
                                <div style="padding: 24px 12px; text-align: center;">
                                    <p style="margin: 0 0 12px 0; color: var(--text-muted);">Tidak ada kelas yang terdaftar untuk jenjang {{ $jenjang }}.</p>
                                    <button type="button" class="pk-btn-add-kelas" onclick="openTambahKelasModal()" style="display:inline-flex;">
                                        + Tambah Kelas Baru
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card List: Kelas --}}
        <div class="mobile-card-list">
            @forelse($dataKelas as $item)
            <div class="mobile-card-item">
                <div class="mobile-card-info">
                    <div class="mobile-card-title">Kelas {{ $item->nama_kelas_display ?? $item->nama_kelas }}</div>
                    <div class="mobile-card-subtitle">Tingkat {{ $item->tingkat ?? 7 }} &bull; Jenjang {{ $jenjang }}</div>

                    <div class="mobile-card-meta-grid">
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Tingkat</span>
                            <span class="mobile-card-pill mobile-card-pill-blue">Tingkat {{ $item->tingkat ?? 7 }}</span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Jumlah</span>
                            <span class="mobile-card-pill mobile-card-pill-green">{{ $item->jumlah_siswa }} Siswa</span>
                        </div>
                    </div>
                </div>

                <div class="mobile-card-actions">
                    <span class="mobile-card-action-title">Aksi</span>
                    <div class="mobile-card-action-buttons">
                        <button type="button" class="mobile-btn-pastel mobile-btn-view"
                                title="Lihat & Plotting Siswa"
                                onclick="openAnggotaModal('{{ $item->nama_kelas }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        @if(isset($item->id))
                        <button type="button" class="mobile-btn-pastel mobile-btn-edit"
                                title="Edit Ruang Kelas"
                                onclick="openEditKelasModal({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->tingkat ?? 7 }}, {{ $item->urutan ?? 1 }})">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>

                        <button type="button" class="mobile-btn-pastel mobile-btn-delete"
                                title="Hapus Ruang Kelas"
                                onclick="confirmDeleteKelas({{ $item->id }}, '{{ addslashes($item->nama_kelas) }}', {{ $item->jumlah_siswa }})">
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
                Tidak ada kelas yang terdaftar untuk jenjang {{ $jenjang }}.
            </div>
            @endforelse
        </div>
    </div>

    {{-- TAB 2: SISWA BELUM TERDAFTAR --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">
        <div class="pk-table-card">
            <div class="pk-table-wrapper">
                <table class="pk-table" id="tableBelum">
                    <thead>
                        <tr>
                            <th class="pk-th-aksi-belum" style="width: 140px;">AKSI</th>
                            <th class="pk-th-nama">NAMA SISWA</th>
                            <th class="pk-th-nisn">NISN</th>
                            <th class="pk-th-status" style="width: 140px; text-align: right;">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($belumTerdaftar as $siswa)
                        <tr class="pk-row-siswa" data-siswa-id="{{ $siswa->id }}" data-siswa-nama="{{ $siswa->nama_lengkap }}">
                            {{-- Kolom Aksi di sebelah kiri: Mutasi ke Kelas --}}
                            <td class="pk-td-aksi-belum">
                                <button type="button"
                                        class="pk-btn-mutasi-row"
                                        onclick="openMutasiSingleModal({{ $siswa->id }}, '{{ addslashes($siswa->nama_lengkap) }}')"
                                        title="Mutasikan ke Kelas">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                    <span>Pilih Kelas</span>
                                </button>
                            </td>
                            <td class="pk-td-nama">
                                <span class="pk-nama-text">{{ $siswa->nama_lengkap }}</span>
                                <div class="pk-nisn-sub-mobile">NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</div>
                            </td>
                            <td class="pk-td-nisn">
                                <span class="pk-nisn-text">{{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</span>
                            </td>
                            <td class="pk-td-status" style="text-align: right;">
                                <span class="pk-status-pill">Belum Terdaftar</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="pk-td-empty">Semua siswa jenjang {{ $jenjang }} telah terdaftar ke kelas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Card List: Siswa Belum Terdaftar --}}
        <div class="mobile-card-list">
            @forelse($belumTerdaftar as $siswa)
            <div class="mobile-card-item">
                <div class="mobile-card-info">
                    <div class="mobile-card-title">{{ $siswa->nama_lengkap }}</div>
                    <div class="mobile-card-subtitle">NISN: {{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</div>

                    <div class="mobile-card-meta-grid">
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Jenjang</span>
                            <span class="mobile-card-pill mobile-card-pill-blue">{{ $jenjang }}</span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">&nbsp;</span>
                            <span class="status-pill status-pill-warning">Belum Terdaftar</span>
                        </div>
                    </div>
                </div>

                <div class="mobile-card-actions">
                    <span class="mobile-card-action-title">Aksi</span>
                    <div class="mobile-card-action-buttons">
                        <button type="button" class="mobile-btn-pastel mobile-btn-view"
                                title="Mutasikan ke Kelas"
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
                Semua siswa jenjang {{ $jenjang }} telah terdaftar ke kelas.
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- MODAL 1: LIHAT & KELOLA ANGGOTA KELAS --}}
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

            <div class="pk-modal-body">
                <div class="pk-modal-table-wrap">
                    <table class="pk-modal-table">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;">NO</th>
                                <th>NAMA SISWA</th>
                                <th style="width:140px;">NISN</th>
                                <th style="width:140px; text-align:right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="modalAnggotaBody">
                            <tr>
                                <td colspan="4" style="text-align:center; padding:20px; color:#64748B;">Memuat data...</td>
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
                    <label for="selectKelasTujuan" class="pk-form-label">Pilih Kelas Tujuan:</label>
                    <select name="kelas" id="selectKelasTujuan" class="pk-form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($dataKelas as $kItem)
                            <option value="{{ $kItem->nama_kelas }}">Kelas {{ $kItem->nama_kelas_display ?? $kItem->nama_kelas }}</option>
                        @endforeach
                    </select>
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

{{-- FORM HIDDEN UNTUK UNASSIGN / KELUARKAN SISWA DARI KELAS --}}
<form action="{{ route('plotting-kelas.unassign') }}" method="POST" id="formUnassign" style="display:none;">
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

/* Yayasan Switcher */
.yayasan-switch-wrap {
    display: inline-flex;
    background: #F1F5F9;
    border-radius: 8px;
    padding: 3px;
    border: 1px solid #E2E8F0;
}
.dark-mode .yayasan-switch-wrap {
    background: #1E293B;
    border-color: #334155;
}
.yayasan-tab-btn {
    padding: 5px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #64748B;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.yayasan-tab-btn.active {
    background: #0F172A;
    color: #FFFFFF;
}
.dark-mode .yayasan-tab-btn.active {
    background: #38BDF8;
    color: #0F172A;
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

/* Search Box */
.pk-search-box {
    position: relative;
    width: 260px;
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

/* Tingkat Badge */
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
    font-size: 11.5px;
    flex-shrink: 0;
}
.dark-mode .pk-tingkat-badge {
    background: rgba(0, 82, 255, 0.2);
    color: #60A5FA;
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
    max-width: 680px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.pk-modal-dialog-sm {
    max-width: 440px;
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
    padding: 22px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 14px;
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
    max-height: 400px;
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
    padding: 10px 14px;
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
    padding: 10px 14px;
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
    /* Header Actions Wrap */
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
    .yayasan-switch-wrap {
        padding: 2px !important;
    }
    .yayasan-tab-btn {
        padding: 4px 10px !important;
        font-size: 11.5px !important;
    }

    /* Topbar: Tabs & Search */
    .pk-topbar {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
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
    .pk-search-box {
        width: 100% !important;
    }
    .pk-search-input {
        width: 100% !important;
    }

    /* Table General */
    .pk-table th,
    .pk-table td {
        padding: 10px 8px !important;
        font-size: 12.5px !important;
    }

    /* Tab 1: Table Kelas */
    .pk-th-tingkat,
    .pk-td-tingkat {
        display: none !important;
    }
    .pk-kelas-sub-mobile {
        display: block !important;
        font-size: 11px !important;
        color: var(--text-secondary, #64748B) !important;
        font-weight: 500 !important;
        margin-top: 2px !important;
    }
    .pk-th-aksi,
    .pk-td-aksi {
        width: 104px !important;
        min-width: 104px !important;
        max-width: 104px !important;
        padding-left: 12px !important;
        padding-right: 4px !important;
    }
    .pk-actions-group {
        gap: 4px !important;
    }
    .pk-btn-eye,
    .pk-btn-edit-action,
    .pk-btn-delete-action {
        width: 28px !important;
        height: 28px !important;
        border-radius: 6px !important;
    }
    .pk-btn-eye svg,
    .pk-btn-edit-action svg,
    .pk-btn-delete-action svg {
        width: 14px !important;
        height: 14px !important;
    }
    .pk-tingkat-badge {
        width: 24px !important;
        height: 24px !important;
        font-size: 11px !important;
        border-radius: 6px !important;
    }
    .pk-kelas-name {
        font-size: 13px !important;
        white-space: nowrap !important;
    }
    .pk-th-jumlah,
    .pk-td-jumlah {
        width: 80px !important;
        min-width: 80px !important;
        padding-right: 12px !important;
        padding-left: 4px !important;
    }
    .pk-siswa-count {
        padding: 3px 6px !important;
        font-size: 11px !important;
        white-space: nowrap !important;
    }

    /* Tab 2: Table Belum Terdaftar */
    .pk-th-nisn,
    .pk-td-nisn {
        display: none !important;
    }
    .pk-nisn-sub-mobile {
        display: block !important;
        font-size: 11px !important;
        font-family: monospace !important;
        color: var(--text-secondary, #64748B) !important;
        margin-top: 2px !important;
    }
    .pk-th-aksi-belum,
    .pk-td-aksi-belum {
        width: 96px !important;
        min-width: 96px !important;
        padding-left: 12px !important;
        padding-right: 4px !important;
    }
    .pk-btn-mutasi-row {
        padding: 5px 8px !important;
        font-size: 11.5px !important;
    }
    .pk-btn-mutasi-row svg {
        width: 13px !important;
        height: 13px !important;
    }
    .pk-th-status,
    .pk-td-status {
        width: 90px !important;
        min-width: 90px !important;
        padding-right: 12px !important;
        padding-left: 4px !important;
    }
    .pk-status-pill {
        padding: 3px 6px !important;
        font-size: 10.5px !important;
        white-space: nowrap !important;
    }

    /* Modals Mobile */
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
    .pk-modal-title {
        font-size: 15px !important;
    }
    .pk-modal-table th,
    .pk-modal-table td {
        padding: 8px 10px !important;
        font-size: 12px !important;
    }
}
</style>

<script>
const jenjangAktif = '{{ $jenjang }}';

// Tab Switching
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

    // Update query string tanpa reload halaman
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);

    // Re-filter tabel setelah tab berubah
    filterTableRows();
}

// Client-side quick filter
function filterTableRows() {
    const q = document.getElementById('pkSearchInput').value.toLowerCase().trim();

    // Filter baris di Tab Kelas
    document.querySelectorAll('#tableKelas tbody tr.pk-row-kelas').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        tr.style.display = text.includes(q) ? '' : 'none';
    });

    // Filter baris di Tab Belum Terdaftar
    document.querySelectorAll('#tableBelum tbody tr.pk-row-siswa').forEach(tr => {
        const text = tr.innerText.toLowerCase();
        tr.style.display = text.includes(q) ? '' : 'none';
    });

    // Filter kartu mobile di kedua tab
    document.querySelectorAll('.pk-tab-pane .mobile-card-item').forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(q) ? '' : 'none';
    });
}

// Modal Anggota Kelas
function openAnggotaModal(namaKelas) {
    const modal = document.getElementById('modalAnggota');
    const title = document.getElementById('modalAnggotaTitle');
    const subtitle = document.getElementById('modalAnggotaSubtitle');
    const tbody = document.getElementById('modalAnggotaBody');

    title.textContent = `Anggota Kelas ${namaKelas}`;
    subtitle.textContent = `Memuat daftar anggota...`;
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:#64748B;">Memuat data...</td></tr>`;

    modal.style.display = 'flex';

    // Fetch anggota data via JSON endpoint
    fetch(`/plotting-kelas/anggota/${encodeURIComponent(namaKelas)}?jenjang=${jenjangAktif}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) throw new Error('Gagal memuat');

        subtitle.textContent = `${res.total} Siswa Terdaftar`;
        tbody.innerHTML = '';

        if (res.anggota.length === 0) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:24px; color:#94A3B8;">Belum ada siswa di kelas ini.</td></tr>`;
            return;
        }

        res.anggota.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:center; font-weight:600; color:#64748B;">${idx + 1}</td>
                <td style="font-weight:600;">${item.nama_lengkap}</td>
                <td style="font-family:monospace; font-size:12px; color:#64748B;">${item.nisn}</td>
                <td style="text-align:right;">
                    <div style="display:inline-flex; gap:6px;">
                        <button type="button" class="pk-btn-action-sm" onclick="openMutasiSingleModal(${item.id}, '${item.nama_lengkap.replace(/'/g, "\\'")}')" title="Pindah Kelas">
                            Mutasi
                        </button>
                        <button type="button" class="pk-btn-action-sm pk-btn-unassign" onclick="unassignSiswa(${item.id}, '${item.nama_lengkap.replace(/'/g, "\\'")}')" title="Keluarkan dari Kelas">
                            Keluarkan
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(err => {
        subtitle.textContent = 'Gagal memuat data';
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:#EF4444;">Gagal memuat data anggota kelas.</td></tr>`;
    });
}

function closeAnggotaModal() {
    document.getElementById('modalAnggota').style.display = 'none';
}

// Modal Mutasi Siswa
function openMutasiSingleModal(siswaId, namaSiswa) {
    document.getElementById('mutasiSiswaId').value = siswaId;
    document.getElementById('mutasiSiswaNama').textContent = namaSiswa;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function closeMutasiModal() {
    document.getElementById('modalMutasi').style.display = 'none';
}

// Modal Tambah Kelas
function openTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('inputNamaKelas').focus();
    }, 100);
}

function closeTambahKelasModal() {
    document.getElementById('modalTambahKelas').style.display = 'none';
}

// Modal Edit Kelas
function openEditKelasModal(id, namaKelas, tingkat, urutan) {
    const form = document.getElementById('formEditKelas');
    form.action = `/plotting-kelas/kelas/${id}`;
    document.getElementById('editNamaKelas').value = namaKelas;
    document.getElementById('editTingkat').value = tingkat;
    document.getElementById('editUrutan').value = urutan || '';
    document.getElementById('modalEditKelas').style.display = 'flex';
    setTimeout(() => {
        document.getElementById('editNamaKelas').focus();
    }, 100);
}

function closeEditKelasModal() {
    document.getElementById('modalEditKelas').style.display = 'none';
}

// Hapus Kelas dengan Konfirmasi
function confirmDeleteKelas(id, namaKelas, jumlahSiswa) {
    if (jumlahSiswa > 0) {
        alert(`Kelas ${namaKelas} tidak dapat dihapus karena masih memiliki ${jumlahSiswa} siswa terdaftar. Silakan mutasikan atau keluarkan siswa terlebih dahulu.`);
        return;
    }

    if (confirm(`Apakah Anda yakin ingin menghapus ruang kelas ${namaKelas}?`)) {
        const form = document.getElementById(`formDeleteKelas_${id}`);
        if (form) form.submit();
    }
}

// Unassign Siswa
function unassignSiswa(siswaId, namaSiswa) {
    if (confirm(`Keluarkan ${namaSiswa} dari kelas ini? Siswa akan masuk ke tab Belum Terdaftar.`)) {
        document.getElementById('unassignSiswaId').value = siswaId;
        document.getElementById('formUnassign').submit();
    }
}

// Close modals when clicking backdrop
document.addEventListener('click', function(e) {
    const mAnggota = document.getElementById('modalAnggota');
    const mMutasi = document.getElementById('modalMutasi');
    const mTambah = document.getElementById('modalTambahKelas');
    const mEdit = document.getElementById('modalEditKelas');

    if (e.target === mAnggota) closeAnggotaModal();
    if (e.target === mMutasi) closeMutasiModal();
    if (e.target === mTambah) closeTambahKelasModal();
    if (e.target === mEdit) closeEditKelasModal();
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnggotaModal();
        closeMutasiModal();
        closeTambahKelasModal();
        closeEditKelasModal();
    }
});
</script>
@endsection
