@extends('layouts.app')

@section('title', 'Input NFC')

@section('header-actions')
<div class="nfc-top-stats">
    <div class="nfc-stat-pill">
        <span class="nfc-stat-label">Terdaftar</span>
        <strong class="nfc-stat-value" id="statTerdaftar">{{ $totalTerdaftar }}</strong>
    </div>
    <div class="nfc-stat-pill nfc-stat-pill-warn">
        <span class="nfc-stat-label">Belum Ada Kartu</span>
        <strong class="nfc-stat-value" id="statBelum">{{ $totalBelum }}</strong>
    </div>
</div>
@endsection

@section('content')
<div class="nfc-page-wrapper">

    {{-- Notifikasi Sukses / Error Standar --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="margin-bottom: 16px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Form Utama Pairing Kartu (Filament Style Card) --}}
    <div class="fi-section-card">
        <form id="nfcAssignForm" action="{{ route('nfc.assign', [], false) }}" method="POST" autocomplete="off">
            @csrf

            <div class="nfc-form-grid">
                {{-- 1. Input Nomor Serial Kartu --}}
                <div class="nfc-field-wrap">
                    <div class="nfc-field-head">
                        <label for="rfid_serial" class="nfc-field-label">
                            Nomor Serial Kartu (NFC)
                        </label>
                        <span id="serialStatusBadge" class="nfc-live-badge standby">
                            <span class="pulse-indicator"></span>
                            <span>Siap Tap Kartu</span>
                        </span>
                    </div>

                    <div class="nfc-serial-input-box">
                        <div class="nfc-input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                <line x1="2" y1="10" x2="22" y2="10"></line>
                            </svg>
                        </div>
                        <input type="text"
                               name="rfid_uid"
                               id="rfid_serial"
                               class="form-control nfc-serial-input"
                               placeholder="Tempelkan kartu pada alat USB reader..."
                               maxlength="32"
                               autofocus
                               required>
                        <button type="button" class="nfc-input-clear-btn" id="btnClearSerial" title="Reset nomor serial" style="display:none;">
                            &times;
                        </button>
                    </div>

                    {{-- Peringatan jika kartu sudah milik santri lain --}}
                    <div id="cardOwnerAlert" class="nfc-warning-banner" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div id="cardOwnerText" class="nfc-warning-text"></div>
                    </div>
                </div>

                {{-- 2. Pilih Nama Santri --}}
                <div class="nfc-field-wrap">
                    <div class="nfc-field-head">
                        <label for="siswaSearchInput" class="nfc-field-label">
                            Pilih Nama Santri
                        </label>
                        <span id="selectedSiswaBadge" class="nfc-selected-tag" style="display:none;">
                            Santri Terpilih
                        </span>
                    </div>

                    <div class="nfc-combobox-wrap" id="siswaCombobox">
                        <input type="hidden" name="siswa_id" id="selectedSiswaId" required>

                        <div class="nfc-combobox-input-box">
                            <div class="nfc-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <input type="text"
                                   id="siswaSearchInput"
                                   class="form-control nfc-combobox-input"
                                   placeholder="Ketik 2-3 huruf nama santri..."
                                   autocomplete="off">
                            <button type="button" class="nfc-input-clear-btn" id="btnClearSiswa" title="Batalkan pilihan" style="display:none;">
                                &times;
                            </button>
                        </div>

                        {{-- Dropdown Hasil Pencarian Santri --}}
                        <div class="nfc-dropdown-list" id="siswaDropdownList" style="display:none;">
                            <div class="nfc-dropdown-filter-tags">
                                <span class="nfc-filter-hint">Pilih santri untuk memasangkan kartu:</span>
                            </div>
                            <div class="nfc-dropdown-items" id="siswaDropdownItems"></div>
                        </div>

                        {{-- Preview Santri Terpilih --}}
                        <div id="selectedSiswaCard" class="nfc-preview-card" style="display:none;">
                            <div class="nfc-preview-avatar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="nfc-preview-info">
                                <div class="nfc-preview-nama" id="prevNama">-</div>
                                <div class="nfc-preview-meta" id="prevMeta">-</div>
                            </div>
                            <button type="button" class="nfc-preview-change-btn" id="btnChangeSiswa" title="Ganti Santri">
                                Ubah
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. Tombol Aksi Simpan --}}
            <div class="nfc-form-actions">
                <button type="submit" class="btn btn-primary nfc-btn-save" id="btnSubmitForm" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Simpan Kartu</span>
                </button>
                <button type="button" class="btn btn-secondary nfc-btn-reset" id="btnResetAll">
                    <span>Batal / Reset</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Riwayat Input Terkini (Clean Filament Table) --}}
    <div class="table-card nfc-table-card">
        <div class="nfc-card-header">
            <div class="nfc-card-header-left">
                <h3 class="nfc-card-title">Riwayat Input Terkini</h3>
            </div>
            <div class="nfc-card-header-right">
                <span class="nfc-card-subtext">Menampilkan 10 kartu terakhir yang berhasil dipasangkan</span>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="modern-table" id="tableRecent">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nomor Serial Kartu</th>
                        <th>Nama Santri</th>
                        <th>Jenjang / Kelas</th>
                        <th>Kamar</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="recentListBody">
                    @forelse($recentPairings as $idx => $r)
                    <tr id="row-siswa-{{ $r->id }}" data-id="{{ $r->id }}" data-serial="{{ $r->rfid_uid }}">
                        <td style="color:var(--text-muted); font-size:12px;">{{ $idx + 1 }}</td>
                        <td>
                            <span class="nfc-table-serial-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;">
                                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                                <code>{{ $r->rfid_uid }}</code>
                            </span>
                        </td>
                        <td class="cell-nama">
                            <span class="nama-text" style="font-weight:600;">{{ $r->nama_lengkap }}</span>
                        </td>
                        <td>
                            @php
                                $j = $r->jenjang ?? ($r->pendaftaran?->jenjang ?? 'Reguler');
                                $badgeColor = match($j) {
                                    'SMP' => 'smp',
                                    'SMA' => 'sma',
                                    default => 'pondok',
                                };
                            @endphp
                            <span class="badge-lembaga badge-lembaga-{{ $badgeColor }}">{{ $j }}</span>
                            @if($r->kelas)
                                <span style="font-size:12px; color:var(--text-muted); margin-left:4px;">{{ $r->kelas }}</span>
                            @endif
                        </td>
                        <td style="color:var(--text-muted);">{{ $r->kamar ?: '-' }}</td>
                        <td style="text-align: center;">
                            <button type="button"
                                    class="action-btn action-btn-koreksi btn-unpair-card"
                                    data-id="{{ $r->id }}"
                                    data-nama="{{ $r->nama_lengkap }}"
                                    data-serial="{{ $r->rfid_uid }}"
                                    title="Lepas Kartu dari Santri Ini">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                <span>Lepas</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="rowEmptyRecent">
                        <td colspan="6" class="cell-empty" style="text-align:center; padding:32px 16px; color:var(--text-muted);">
                            Belum ada riwayat pairing kartu pada sesi ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Data Santri untuk Client-Side Autocomplete Cepat --}}
<script>
    window.DATA_SISWAS = @json($siswas);
</script>

<style>
/* ==================== TAMPILAN FILAMENT CLEAN ==================== */
.nfc-page-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1000px;
    margin: 0 auto;
    padding-bottom: 40px;
}

/* Header Top Stats */
.nfc-top-stats {
    display: flex;
    gap: 8px;
    align-items: center;
}

.nfc-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-light, #e5e7eb);
    border-radius: 8px;
    font-size: 12.5px;
    color: var(--text-main, #1e293b);
}

.nfc-stat-label {
    color: var(--text-muted, #64748b);
    font-size: 11.5px;
}

.nfc-stat-value {
    color: var(--primary-color, #0052ff);
    font-weight: 700;
}

.nfc-stat-pill-warn .nfc-stat-value {
    color: #f59e0b;
}

/* Section Card (Filament Styled) */
.fi-section-card {
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-light, #e5e7eb);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.nfc-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .nfc-form-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }
}

.nfc-field-wrap {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.nfc-field-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 22px;
}

.nfc-field-label {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-main, #1e293b);
    margin: 0;
}

/* Live Badge Status */
.nfc-live-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.nfc-live-badge.standby {
    background: rgba(100, 116, 139, 0.1);
    color: #64748b;
    border: 1px solid rgba(100, 116, 139, 0.2);
}

.nfc-live-badge.ready {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.pulse-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #64748b;
}

.nfc-live-badge.ready .pulse-indicator {
    background: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
}

.nfc-selected-tag {
    font-size: 11px;
    font-weight: 600;
    color: #0052ff;
    background: rgba(0, 82, 255, 0.08);
    padding: 2px 7px;
    border-radius: 6px;
}

/* Input Box Styling */
.nfc-serial-input-box,
.nfc-combobox-input-box {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.nfc-input-icon {
    position: absolute;
    left: 14px;
    width: 18px;
    height: 18px;
    color: var(--text-muted, #94a3b8);
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nfc-input-icon svg {
    width: 16px;
    height: 16px;
}

.nfc-serial-input,
.nfc-combobox-input {
    width: 100%;
    height: 44px;
    padding: 0 36px 0 42px;
    border-radius: 8px;
    border: 1px solid var(--border-light, #d1d5db);
    background: var(--card-bg, #ffffff);
    color: var(--text-main, #1e293b);
    font-size: 14.5px;
    transition: all 0.15s ease-in-out;
}

.nfc-serial-input {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.nfc-serial-input:focus,
.nfc-combobox-input:focus {
    border-color: var(--primary-color, #0052ff);
    box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.15);
    outline: none;
}

.nfc-serial-input.is-scanned {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.04);
}

.nfc-input-clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    border: none;
    background: transparent;
    color: var(--text-muted, #94a3b8);
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nfc-input-clear-btn:hover {
    color: #ef4444;
}

/* Warning Banner Saat Kartu Sudah Dipakai */
.nfc-warning-banner {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 9px 12px;
    border-radius: 8px;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #b45309;
    font-size: 12.5px;
    line-height: 1.4;
    margin-top: 4px;
}

.dark-mode .nfc-warning-banner {
    background: rgba(245, 158, 11, 0.15);
    color: #fcd34d;
}

.nfc-warning-banner svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}

/* Combobox Dropdown */
.nfc-combobox-wrap {
    position: relative;
    width: 100%;
}

.nfc-dropdown-list {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 50;
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-light, #e5e7eb);
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    max-height: 260px;
    overflow-y: auto;
}

.nfc-dropdown-filter-tags {
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-light, #f1f5f9);
    background: var(--bg-surface, #f8fafc);
}

.nfc-filter-hint {
    font-size: 11.5px;
    color: var(--text-muted, #64748b);
}

.nfc-dropdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-light, #f8fafc);
    transition: background 0.12s ease;
}

.nfc-dropdown-item:hover,
.nfc-dropdown-item.active {
    background: rgba(0, 82, 255, 0.06);
}

.nfc-item-name {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--text-main, #1e293b);
}

.nfc-item-sub {
    font-size: 11.5px;
    color: var(--text-muted, #64748b);
}

.nfc-item-badge {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
}

.nfc-badge-empty {
    background: #f1f5f9;
    color: #64748b;
}

.nfc-badge-has {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.nfc-dropdown-empty {
    padding: 16px;
    text-align: center;
    font-size: 13px;
    color: var(--text-muted, #94a3b8);
}

/* Preview Card Santri Terpilih */
.nfc-preview-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    background: rgba(0, 82, 255, 0.05);
    border: 1px solid rgba(0, 82, 255, 0.2);
}

.nfc-preview-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #0052ff;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.nfc-preview-avatar svg {
    width: 18px;
    height: 18px;
}

.nfc-preview-info {
    flex: 1;
    min-width: 0;
}

.nfc-preview-nama {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-main, #1e293b);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.nfc-preview-meta {
    font-size: 11.5px;
    color: var(--text-muted, #64748b);
}

.nfc-preview-change-btn {
    border: none;
    background: transparent;
    color: #0052ff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
}

.nfc-preview-change-btn:hover {
    background: rgba(0, 82, 255, 0.1);
}

/* Actions Footer */
.nfc-form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    padding-top: 6px;
    border-top: 1px solid var(--border-light, #f1f5f9);
}

.nfc-btn-save {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-weight: 600;
    font-size: 13.5px;
}

.nfc-btn-save:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.nfc-btn-reset {
    padding: 10px 18px;
    font-size: 13.5px;
}

/* Table Card */
.nfc-table-card {
    background: var(--card-bg, #ffffff);
    border: 1px solid var(--border-light, #e5e7eb);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.nfc-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border-light, #e5e7eb);
}

.nfc-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-main, #1e293b);
    margin: 0;
}

.nfc-card-subtext {
    font-size: 12px;
    color: var(--text-muted, #64748b);
}

.nfc-table-serial-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(0, 82, 255, 0.08);
    color: #0052ff;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 600;
    font-size: 12px;
}

.btn-unpair-card {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #dc2626;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.25);
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-unpair-card:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputSerial = document.getElementById('rfid_serial');
    const btnClearSerial = document.getElementById('btnClearSerial');
    const badgeStatus = document.getElementById('serialStatusBadge');
    const cardOwnerAlert = document.getElementById('cardOwnerAlert');
    const cardOwnerText = document.getElementById('cardOwnerText');

    const inputSiswaSearch = document.getElementById('siswaSearchInput');
    const inputSiswaId = document.getElementById('selectedSiswaId');
    const btnClearSiswa = document.getElementById('btnClearSiswa');
    const siswaDropdown = document.getElementById('siswaDropdownList');
    const siswaDropdownItems = document.getElementById('siswaDropdownItems');
    const selectedSiswaCard = document.getElementById('selectedSiswaCard');
    const prevNama = document.getElementById('prevNama');
    const prevMeta = document.getElementById('prevMeta');
    const btnChangeSiswa = document.getElementById('btnChangeSiswa');
    const selectedSiswaBadge = document.getElementById('selectedSiswaBadge');

    const btnSubmitForm = document.getElementById('btnSubmitForm');
    const btnResetAll = document.getElementById('btnResetAll');
    const nfcForm = document.getElementById('nfcAssignForm');
    const recentTbody = document.getElementById('recentListBody');

    const siswasData = window.DATA_SISWAS || [];

    // Focus awal pada input nomor serial
    setTimeout(() => inputSerial.focus(), 100);

    // 1. Logika Input Serial dari USB Reader
    inputSerial.addEventListener('input', function () {
        const val = this.value.trim();
        btnClearSerial.style.display = val ? 'flex' : 'none';
        checkInputCompleteness();
    });

    // Menangkap tombol ENTER otomatis yang dikirimkan oleh USB Reader
    inputSerial.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            if (val.length >= 4) {
                onCardDetected(val);
            }
        }
    });

    btnClearSerial.addEventListener('click', function () {
        resetSerialField();
        inputSerial.focus();
    });

    function resetSerialField() {
        inputSerial.value = '';
        inputSerial.classList.remove('is-scanned');
        btnClearSerial.style.display = 'none';
        badgeStatus.className = 'nfc-live-badge standby';
        badgeStatus.innerHTML = '<span class="pulse-indicator"></span><span>Siap Tap Kartu</span>';
        cardOwnerAlert.style.display = 'none';
        cardOwnerText.textContent = '';
        checkInputCompleteness();
    }

    function onCardDetected(serial) {
        inputSerial.classList.add('is-scanned');
        badgeStatus.className = 'nfc-live-badge ready';
        badgeStatus.innerHTML = '<span class="pulse-indicator"></span><span>Kartu Terbaca</span>';

        // Cek ke server apakah kartu sudah terdaftar (gunakan relative URL agar tidak bentrok dengan APP_URL localhost)
        fetch(`{{ route('nfc.check-card', [], false) }}?serial=${encodeURIComponent(serial)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'assigned') {
                cardOwnerAlert.style.display = 'flex';
                cardOwnerText.innerHTML = `<strong>Perhatian:</strong> ${data.message} Jika Anda menyimpan, kartu akan dipindahkan ke santri baru.`;
            } else {
                cardOwnerAlert.style.display = 'none';
                cardOwnerText.textContent = '';
            }

            // Langkah 2: Kursor otomatis berpindah ke kotak 'Pilih Nama Santri'
            if (!inputSiswaId.value) {
                inputSiswaSearch.focus();
                showSiswaDropdown('');
            }
            checkInputCompleteness();
        })
        .catch(err => {
            console.error('Check card error:', err);
            checkInputCompleteness();
        });
    }

    // 2. Logika Pemilihan Nama Santri (Combobox)
    inputSiswaSearch.addEventListener('focus', function () {
        showSiswaDropdown(this.value.trim());
    });

    inputSiswaSearch.addEventListener('input', function () {
        const query = this.value.trim();
        btnClearSiswa.style.display = query ? 'flex' : 'none';
        showSiswaDropdown(query);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#siswaCombobox')) {
            siswaDropdown.style.display = 'none';
        }
    });

    btnClearSiswa.addEventListener('click', function () {
        inputSiswaSearch.value = '';
        btnClearSiswa.style.display = 'none';
        showSiswaDropdown('');
        inputSiswaSearch.focus();
    });

    btnChangeSiswa.addEventListener('click', function () {
        selectedSiswaCard.style.display = 'none';
        inputSiswaSearch.parentElement.style.display = 'flex';
        selectedSiswaBadge.style.display = 'none';
        inputSiswaId.value = '';
        inputSiswaSearch.value = '';
        inputSiswaSearch.focus();
        showSiswaDropdown('');
        checkInputCompleteness();
    });

    function formatKamar(kamar) {
        if (!kamar) return '';
        kamar = String(kamar).trim();
        return /^kamar\b/i.test(kamar) ? kamar : 'Kamar ' + kamar;
    }

    function formatKelas(kelas) {
        if (!kelas) return '';
        kelas = String(kelas).trim();
        return /^kelas\b/i.test(kelas) ? kelas : 'Kelas ' + kelas;
    }

    function showSiswaDropdown(query) {
        const q = query.toLowerCase();
        let matches = siswasData;
        if (q) {
            matches = siswasData.filter(s => {
                const nama = (s.nama_lengkap || '').toLowerCase();
                const jenjang = (s.jenjang || '').toLowerCase();
                const kamar = (s.kamar || '').toLowerCase();
                return nama.includes(q) || jenjang.includes(q) || kamar.includes(q);
            });
        }

        siswaDropdownItems.innerHTML = '';

        if (matches.length === 0) {
            siswaDropdownItems.innerHTML = '<div class="nfc-dropdown-empty">Tidak ada santri yang cocok.</div>';
            siswaDropdown.style.display = 'block';
            return;
        }

        // Tampilkan maksimal 20 rekomendasi untuk performa cepat
        matches.slice(0, 20).forEach(s => {
            const item = document.createElement('div');
            item.className = 'nfc-dropdown-item';
            
            const metaInfo = [
                s.jenjang || 'Pondok', 
                s.kelas ? formatKelas(s.kelas) : null, 
                s.kamar ? formatKamar(s.kamar) : null
            ].filter(Boolean).join(' • ');

            const badgeHtml = s.rfid_uid
                ? `<span class="nfc-item-badge nfc-badge-has">Kartu: ${s.rfid_uid}</span>`
                : `<span class="nfc-item-badge nfc-badge-empty">Belum Ber-kartu</span>`;

            item.innerHTML = `
                <div>
                    <div class="nfc-item-name">${escapeHtml(s.nama_lengkap)}</div>
                    <div class="nfc-item-sub">${escapeHtml(metaInfo)}</div>
                </div>
                <div>${badgeHtml}</div>
            `;

            item.addEventListener('click', () => selectSiswa(s));
            siswaDropdownItems.appendChild(item);
        });

        siswaDropdown.style.display = 'block';
    }

    function selectSiswa(s) {
        inputSiswaId.value = s.id;
        prevNama.textContent = s.nama_lengkap;
        
        const metaInfo = [
            s.jenjang || 'Pondok', 
            s.kelas ? formatKelas(s.kelas) : null, 
            s.kamar ? formatKamar(s.kamar) : null
        ].filter(Boolean).join(' • ');
        prevMeta.textContent = metaInfo;

        selectedSiswaCard.style.display = 'flex';
        inputSiswaSearch.parentElement.style.display = 'none';
        selectedSiswaBadge.style.display = 'inline-block';
        siswaDropdown.style.display = 'none';

        checkInputCompleteness();

        // Jika nomor serial sudah ada, fokus langsung ke tombol Simpan
        if (inputSerial.value.trim().length >= 4) {
            btnSubmitForm.focus();
        }
    }

    // 3. Validasi Kelengkapan Input
    function checkInputCompleteness() {
        const hasSerial = inputSerial.value.trim().length >= 4;
        const hasSiswa = inputSiswaId.value !== '';
        btnSubmitForm.disabled = !(hasSerial && hasSiswa);
    }

    // 4. Tombol Reset / Batal
    btnResetAll.addEventListener('click', function () {
        resetSerialField();
        btnChangeSiswa.click();
        inputSerial.focus();
    });

    // 5. Submit Form via AJAX
    nfcForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const serial = inputSerial.value.trim();
        const siswaId = inputSiswaId.value;

        if (!serial || !siswaId) return;

        btnSubmitForm.disabled = true;
        btnSubmitForm.innerHTML = '<span>Menyimpan...</span>';

        fetch('{{ route("nfc.assign", [], false) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ rfid_uid: serial, siswa_id: siswaId })
        })
        .then(async res => {
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'HTTP error ' + res.status);
                throw new Error(errMsg);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                // Notifikasi Sukses
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Dipasangkan!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    alert(data.message);
                }

                // Tambahkan baris baru ke tabel riwayat
                prependRecentRow(data.siswa);

                // Update data memori lokal
                const found = siswasData.find(s => s.id == siswaId);
                if (found) found.rfid_uid = (data.siswa && data.siswa.rfid_uid) ? data.siswa.rfid_uid : serial;

                // Update indikator statistik
                const statTerdaftar = document.getElementById('statTerdaftar');
                const statBelum = document.getElementById('statBelum');
                if (statTerdaftar && statBelum) {
                    statTerdaftar.textContent = parseInt(statTerdaftar.textContent || '0', 10) + 1;
                    const b = Math.max(0, parseInt(statBelum.textContent || '0', 10) - 1);
                    statBelum.textContent = b;
                }

                // Reset form dan langsung siap untuk kartu santri berikutnya!
                resetSerialField();
                btnChangeSiswa.click();
                inputSerial.focus();
            } else {
                alert(data.message || 'Gagal menyimpan data.');
            }
        })
        .catch(err => {
            console.error('Submit error:', err);
            if (confirm('Gagal menyimpan via AJAX: ' + err.message + '\n\nApakah Anda ingin mencoba simpan langsung formulir?')) {
                nfcForm.submit();
            }
        })
        .finally(() => {
            btnSubmitForm.disabled = false;
            btnSubmitForm.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span>Simpan Kartu</span>
            `;
            checkInputCompleteness();
        });
    });

    // 6. Tombol Lepas Kartu (Unpair)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-unpair-card');
        if (!btn) return;

        const id = btn.dataset.id;
        const nama = btn.dataset.nama;
        const serial = btn.dataset.serial;

        const confirmAction = () => {
            fetch('{{ route("nfc.unpair", [], false) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ siswa_id: id })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`row-siswa-${id}`);
                    if (row) row.remove();

                    const found = siswasData.find(s => s.id == id);
                    if (found) found.rfid_uid = null;

                    // Update statistik
                    const statTerdaftar = document.getElementById('statTerdaftar');
                    const statBelum = document.getElementById('statBelum');
                    if (statTerdaftar && statBelum) {
                        statTerdaftar.textContent = Math.max(0, parseInt(statTerdaftar.textContent || '0', 10) - 1);
                        statBelum.textContent = parseInt(statBelum.textContent || '0', 10) + 1;
                    }

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kartu Dilepas',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }
                }
            })
            .catch(err => console.error('Unpair error:', err));
        };

        if (window.Swal) {
            Swal.fire({
                title: 'Lepas Kartu Santri?',
                html: `Apakah Anda yakin ingin melepas kartu <strong>${escapeHtml(serial)}</strong> dari santri <strong>${escapeHtml(nama)}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Ya, Lepas Kartu',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) confirmAction();
            });
        } else {
            if (confirm(`Lepas kartu ${serial} dari ${nama}?`)) confirmAction();
        }
    });

    function prependRecentRow(siswa) {
        const emptyRow = document.getElementById('rowEmptyRecent');
        if (emptyRow) emptyRow.remove();

        // Hapus baris lama jika santri ini sudah ada di tabel sebelumnya
        const oldRow = document.getElementById(`row-siswa-${siswa.id}`);
        if (oldRow) oldRow.remove();

        const tr = document.createElement('tr');
        tr.id = `row-siswa-${siswa.id}`;
        tr.dataset.id = siswa.id;
        tr.dataset.serial = siswa.rfid_uid;

        const badgeColor = (siswa.jenjang || '').toUpperCase() === 'SMP' ? 'smp' : ((siswa.jenjang || '').toUpperCase() === 'SMA' ? 'sma' : 'pondok');

        tr.innerHTML = `
            <td style="color:var(--text-muted); font-size:12px;">Baru</td>
            <td>
                <span class="nfc-table-serial-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <code>${escapeHtml(siswa.rfid_uid)}</code>
                </span>
            </td>
            <td class="cell-nama">
                <span class="nama-text" style="font-weight:600;">${escapeHtml(siswa.nama_lengkap)}</span>
            </td>
            <td>
                <span class="badge-lembaga badge-lembaga-${badgeColor}">${escapeHtml(siswa.jenjang || 'Pondok')}</span>
                ${siswa.kelas ? `<span style="font-size:12px; color:var(--text-muted); margin-left:4px;">${escapeHtml(siswa.kelas)}</span>` : ''}
            </td>
            <td style="color:var(--text-muted);">${escapeHtml(siswa.kamar || '-')}</td>
            <td style="text-align: center;">
                <button type="button"
                        class="action-btn action-btn-koreksi btn-unpair-card"
                        data-id="${siswa.id}"
                        data-nama="${escapeHtml(siswa.nama_lengkap)}"
                        data-serial="${escapeHtml(siswa.rfid_uid)}"
                        title="Lepas Kartu dari Santri Ini">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Lepas</span>
                </button>
            </td>
        `;

        recentTbody.insertBefore(tr, recentTbody.firstChild);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection

