@extends('layouts.app')

@section('title', 'Plotting Kelas')

@section('header-actions')
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
@endsection

@section('content')
<div class="pk-container">

    {{-- Alert Flash Message --}}
    @if(session('success'))
    <div class="pk-alert pk-alert-success" id="pkFlashAlert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pk-alert-icon">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
        <button type="button" class="pk-alert-close" onclick="document.getElementById('pkFlashAlert').remove()">&times;</button>
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
                <span>Kelas</span>
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
                   placeholder="Cari..."
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
                            <th class="pk-th-aksi">AKSI</th>
                            <th class="pk-th-kelas">KELAS</th>
                            <th class="pk-th-jumlah">JUMLAH SISWA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dataKelas as $item)
                        <tr class="pk-row-kelas" data-kelas="{{ $item->nama_kelas }}">
                            {{-- Kolom Aksi di sebelah kiri: Logo Mata --}}
                            <td class="pk-td-aksi">
                                <button type="button"
                                        class="pk-btn-eye"
                                        title="Lihat Anggota Kelas {{ $item->nama_kelas }}"
                                        onclick="openAnggotaModal('{{ $item->nama_kelas }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </td>
                            <td class="pk-td-kelas">
                                <span class="pk-kelas-name">Kelas {{ $item->nama_kelas }}</span>
                            </td>
                            <td class="pk-td-jumlah">
                                <span class="pk-siswa-count">{{ $item->jumlah_siswa }} Siswa</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="pk-td-empty">Tidak ada kelas yang terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 2: SISWA BELUM TERDAFTAR --}}
    <div class="pk-tab-pane {{ $activeTab === 'belum_terdaftar' ? 'active' : '' }}" id="paneBelum">
        <div class="pk-table-card">
            <div class="pk-table-wrapper">
                <table class="pk-table" id="tableBelum">
                    <thead>
                        <tr>
                            <th class="pk-th-aksi-belum">AKSI</th>
                            <th class="pk-th-nama">NAMA SISWA</th>
                            <th class="pk-th-nisn">NISN</th>
                            <th class="pk-th-status">STATUS</th>
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
                            </td>
                            <td class="pk-td-nisn">
                                <span class="pk-nisn-text">{{ $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-' }}</span>
                            </td>
                            <td class="pk-td-status">
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
    </div>

</div>

{{-- MODAL 1: LIHAT ANGGOTA KELAS --}}
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
                                <th style="width:110px; text-align:right;">AKSI</th>
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
                        <h3 class="pk-modal-title">Mutasi Kelas</h3>
                        <span class="pk-modal-subtitle" id="mutasiSiswaNama">-</span>
                    </div>
                    <button type="button" class="pk-modal-close" onclick="closeMutasiModal()">&times;</button>
                </div>

                <div class="pk-modal-body">
                    <label for="selectKelasTujuan" class="pk-form-label">Pilih Kelas Tujuan:</label>
                    <select name="kelas" id="selectKelasTujuan" class="pk-form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($allDaftarKelas as $k)
                            <option value="{{ $k }}">Kelas {{ $k }}</option>
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
    background: rgba(22, 101, 52, 0.2);
    border-color: rgba(34, 197, 94, 0.3);
    color: #86EFAC;
}
.pk-alert-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}
.pk-alert-close {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 18px;
    color: inherit;
    cursor: pointer;
    line-height: 1;
}

/* Topbar & Segmented Tabs */
.pk-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.pk-tabs-nav {
    display: inline-flex;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}
.dark-mode .pk-tabs-nav {
    background: #1E293B;
    border-color: #334155;
}
.pk-tab-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border: none;
    background: transparent;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pk-tab-item:hover {
    color: #0F172A;
}
.dark-mode .pk-tab-item:hover {
    color: #F8FAFC;
}
.pk-tab-item.active {
    background: #FFFFFF;
    color: #0F172A;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
.dark-mode .pk-tab-item.active {
    background: #0F172A;
    color: #F8FAFC;
}
.pk-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 1px 7px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    background: #F1F5F9;
    color: #475569;
}
.dark-mode .pk-tab-badge {
    background: #334155;
    color: #CBD5E1;
}
.pk-tab-item.active .pk-tab-badge {
    background: #E2E8F0;
    color: #0F172A;
}
.dark-mode .pk-tab-item.active .pk-tab-badge {
    background: #1E293B;
    color: #F8FAFC;
}
.pk-badge-notice {
    background: #FEF3C7 !important;
    color: #D97706 !important;
}

/* Search Box */
.pk-search-box {
    display: flex;
    align-items: center;
    position: relative;
    width: 240px;
    max-width: 100%;
}
.pk-search-icon {
    position: absolute;
    left: 12px;
    width: 15px;
    height: 15px;
    color: #94A3B8;
    pointer-events: none;
}
.pk-search-input {
    width: 100%;
    padding: 8px 12px 8px 36px;
    font-size: 13px;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    background: #FFFFFF;
    color: #0F172A;
    outline: none;
    transition: border-color 0.15s ease;
}
.dark-mode .pk-search-input {
    background: #1E293B;
    border-color: #334155;
    color: #F8FAFC;
}
.pk-search-input:focus {
    border-color: #94A3B8;
}

/* Tab Panes */
.pk-tab-pane {
    display: none;
}
.pk-tab-pane.active {
    display: block;
}

/* Table Card & Clean Minimalist Table */
.pk-table-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    overflow: hidden;
}
.dark-mode .pk-table-card {
    background: #0F172A;
    border-color: #1E293B;
}
.pk-table-wrapper {
    overflow-x: auto;
    width: 100%;
}
.pk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    text-align: left;
}
.pk-table thead tr th {
    background: #F8FAFC;
    color: #64748B;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 12px 18px;
    border-bottom: 1px solid #E2E8F0;
}
.dark-mode .pk-table thead tr th {
    background: #1E293B;
    color: #94A3B8;
    border-bottom-color: #334155;
}
.pk-table tbody tr {
    transition: background-color 0.1s ease;
}
.pk-table tbody tr:hover {
    background-color: #F8FAFC;
}
.dark-mode .pk-table tbody tr:hover {
    background-color: #1E293B;
}
.pk-table tbody tr td {
    padding: 13px 18px;
    border-bottom: 1px solid #F1F5F9;
    color: #0F172A;
    vertical-align: middle;
}
.dark-mode .pk-table tbody tr td {
    border-bottom-color: #1E293B;
    color: #F8FAFC;
}

/* Table Columns Specific */
.pk-th-aksi {
    width: 75px;
    text-align: center !important;
}
.pk-td-aksi {
    text-align: center;
    width: 75px;
}
.pk-btn-eye {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 7px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
}
.dark-mode .pk-btn-eye {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
}
.pk-btn-eye:hover {
    border-color: #0F172A;
    color: #0F172A;
    background: #F8FAFC;
}
.dark-mode .pk-btn-eye:hover {
    border-color: #38BDF8;
    color: #38BDF8;
}
.pk-btn-eye svg {
    width: 16px;
    height: 16px;
}

.pk-kelas-name {
    font-weight: 700;
    color: #0F172A;
    font-size: 13.5px;
}
.dark-mode .pk-kelas-name {
    color: #F8FAFC;
}
.pk-siswa-count {
    font-size: 13px;
    color: #64748B;
    font-weight: 500;
}
.pk-th-jumlah {
    width: 140px;
}

/* Belum Terdaftar Table */
.pk-th-aksi-belum {
    width: 130px;
}
.pk-td-aksi-belum {
    width: 130px;
}
.pk-btn-mutasi-row {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
}
.dark-mode .pk-btn-mutasi-row {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
}
.pk-btn-mutasi-row:hover {
    border-color: #0F172A;
    color: #0F172A;
}
.dark-mode .pk-btn-mutasi-row:hover {
    border-color: #38BDF8;
    color: #38BDF8;
}
.pk-btn-mutasi-row svg {
    width: 13px;
    height: 13px;
}

.pk-nama-text {
    font-weight: 600;
    font-size: 13.5px;
}
.pk-nisn-text {
    font-size: 12px;
    color: #64748B;
    font-family: monospace;
}
.pk-status-pill {
    display: inline-flex;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #F1F5F9;
    color: #64748B;
}
.dark-mode .pk-status-pill {
    background: #1E293B;
    color: #94A3B8;
}

.pk-td-empty {
    text-align: center;
    padding: 40px 16px;
    color: #94A3B8;
    font-size: 13px;
}

/* Modals */
.pk-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.pk-modal-dialog {
    width: 100%;
    max-width: 640px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}
.pk-modal-dialog-sm {
    max-width: 440px;
}
.pk-modal-content {
    background: #FFFFFF;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 16px 24px -4px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    max-height: 85vh;
    overflow: hidden;
}
.dark-mode .pk-modal-content {
    background: #0F172A;
    border-color: #334155;
}
.pk-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #E2E8F0;
}
.dark-mode .pk-modal-header {
    border-bottom-color: #1E293B;
}
.pk-modal-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0F172A;
}
.dark-mode .pk-modal-title {
    color: #F8FAFC;
}
.pk-modal-subtitle {
    font-size: 12px;
    color: #64748B;
    margin-top: 2px;
    display: block;
}
.pk-modal-close {
    background: none;
    border: none;
    font-size: 22px;
    color: #94A3B8;
    cursor: pointer;
    line-height: 1;
}
.pk-modal-close:hover {
    color: #0F172A;
}
.dark-mode .pk-modal-close:hover {
    color: #F8FAFC;
}
.pk-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
}
.pk-modal-table-wrap {
    max-height: 380px;
    overflow-y: auto;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
}
.dark-mode .pk-modal-table-wrap {
    border-color: #334155;
}
.pk-modal-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}
.pk-modal-table thead tr th {
    position: sticky;
    top: 0;
    background: #F8FAFC;
    color: #64748B;
    font-weight: 700;
    padding: 8px 12px;
    border-bottom: 1px solid #E2E8F0;
    font-size: 11px;
    letter-spacing: 0.4px;
}
.dark-mode .pk-modal-table thead tr th {
    background: #1E293B;
    color: #94A3B8;
    border-bottom-color: #334155;
}
.pk-modal-table tbody tr td {
    padding: 9px 12px;
    border-bottom: 1px solid #F1F5F9;
    color: #0F172A;
    vertical-align: middle;
}
.dark-mode .pk-modal-table tbody tr td {
    border-bottom-color: #1E293B;
    color: #F8FAFC;
}
.pk-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 12px 20px;
    border-top: 1px solid #E2E8F0;
    background: #F8FAFC;
}
.dark-mode .pk-modal-footer {
    border-top-color: #1E293B;
    background: #0F172A;
}

/* Form Controls */
.pk-form-label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.dark-mode .pk-form-label {
    color: #CBD5E1;
}
.pk-form-select {
    width: 100%;
    padding: 9px 12px;
    font-size: 13px;
    border: 1px solid #CBD5E1;
    border-radius: 7px;
    background: #FFFFFF;
    color: #0F172A;
    outline: none;
}
.dark-mode .pk-form-select {
    background: #1E293B;
    border-color: #334155;
    color: #F8FAFC;
}

/* Action Buttons */
.pk-btn-clean {
    padding: 6px 14px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    background: #FFFFFF;
    color: #334155;
    cursor: pointer;
}
.dark-mode .pk-btn-clean {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
}
.pk-btn-primary {
    padding: 6px 16px;
    font-size: 12.5px;
    font-weight: 600;
    border: 1px solid transparent;
    border-radius: 6px;
    background: #0F172A;
    color: #FFFFFF;
    cursor: pointer;
}
.dark-mode .pk-btn-primary {
    background: #38BDF8;
    color: #0F172A;
}

/* Micro Action Buttons in Modal */
.pk-btn-action-sm {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 5px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
}
.dark-mode .pk-btn-action-sm {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
}
.pk-btn-action-sm:hover {
    border-color: #0F172A;
    color: #0F172A;
}
.pk-btn-unassign {
    color: #DC2626 !important;
    border-color: #FECACA !important;
}
.dark-mode .pk-btn-unassign {
    color: #F87171 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}
</style>

<script>
// Tab Switching
function switchTab(tabName) {
    document.querySelectorAll('.pk-tab-pane').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.pk-tab-item').forEach(el => el.classList.remove('active'));

    if (tabName === 'kelas') {
        document.getElementById('paneKelas').classList.add('active');
        document.getElementById('tabBtnKelas').classList.add('active');
    } else {
        document.getElementById('paneBelum').classList.add('active');
        document.getElementById('tabBtnBelum').classList.add('active');
    }

    // Update query param without reloading page
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Client-side quick filter
function filterTableRows() {
    const q = document.getElementById('pkSearchInput').value.toLowerCase();
    
    // Filter rows in active pane
    const activePane = document.querySelector('.pk-tab-pane.active');
    if (!activePane) return;

    const rows = activePane.querySelectorAll('tbody tr');
    rows.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        if (text.includes(q)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

// Modal Anggota Kelas
const jenjangAktif = "{{ $jenjang }}";

function openAnggotaModal(namaKelas) {
    const modal = document.getElementById('modalAnggota');
    const title = document.getElementById('modalAnggotaTitle');
    const subtitle = document.getElementById('modalAnggotaSubtitle');
    const tbody = document.getElementById('modalAnggotaBody');

    title.textContent = `Kelas ${namaKelas}`;
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

// Modal Mutasi
function openMutasiSingleModal(siswaId, namaSiswa) {
    document.getElementById('mutasiSiswaId').value = siswaId;
    document.getElementById('mutasiSiswaNama').textContent = namaSiswa;
    document.getElementById('modalMutasi').style.display = 'flex';
}

function closeMutasiModal() {
    document.getElementById('modalMutasi').style.display = 'none';
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
    if (e.target === mAnggota) closeAnggotaModal();
    if (e.target === mMutasi) closeMutasiModal();
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAnggotaModal();
        closeMutasiModal();
    }
});
</script>
@endsection
