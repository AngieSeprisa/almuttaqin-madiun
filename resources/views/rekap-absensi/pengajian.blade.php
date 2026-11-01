@extends('layouts.app')

@section('title', 'Rekap Presensi Pengajian')

@section('content')
<style>
    .pj-container {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    /* Top Filter Bar */
    .pj-filter-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 16px 22px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .pj-filter-grid {
        display: flex;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
    }
    .pj-filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
        min-width: 220px;
    }
    .pj-filter-label {
        font-size: 12.5px;
        font-weight: 600;
        color: #374151;
    }
    .pj-select, .pj-input {
        width: 100%;
        height: 42px;
        padding: 8px 14px;
        font-size: 13.5px;
        color: #111827;
        background: #ffffff;
        border: 1.5px solid #111827;
        border-radius: 10px;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }
    .pj-select:focus, .pj-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .pj-btn-wa {
        height: 42px;
        padding: 0 24px;
        border-radius: 10px;
        background: #00875a;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.15s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .pj-btn-wa:hover {
        background: #006644;
        color: #ffffff;
    }

    /* Matrix Table Card */
    .pj-table-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .pj-table-scroll {
        overflow-x: auto;
        width: 100%;
    }
    .pj-matrix-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
        white-space: nowrap;
    }
    .pj-matrix-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 10px 8px;
        border: 1px solid #e2e8f0;
        text-align: center;
    }
    .pj-matrix-table th.th-fixed-no {
        width: 44px;
        min-width: 44px;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .pj-matrix-table th.th-fixed-nama {
        text-align: left;
        padding-left: 16px;
        min-width: 220px;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .pj-matrix-table th.th-date {
        font-size: 12.5px;
        color: #0f172a;
        padding: 8px 10px;
    }
    .pj-matrix-table th.th-session {
        font-size: 9.5px;
        color: #64748b;
        font-weight: 700;
        letter-spacing: 0.3px;
        padding: 6px 8px;
        min-width: 42px;
    }
    .pj-matrix-table td {
        padding: 10px 8px;
        border: 1px solid #f1f5f9;
        text-align: center;
        color: #334155;
    }
    .pj-matrix-table td.td-nama {
        text-align: left;
        padding-left: 16px;
        font-weight: 600;
        color: #1e293b;
    }
    .pj-matrix-table tr:hover td {
        background-color: #f8fafc;
    }

    /* Badges Status (Sesuai Gambar 1) */
    .status-badge-h {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: #dcfce7;
        color: #16a34a;
        font-weight: 700;
        font-size: 11px;
        min-width: 14px;
        text-align: center;
    }
    .status-badge-i {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: #e0e7ff;
        color: #4f46e5;
        font-weight: 700;
        font-size: 11px;
        min-width: 14px;
        text-align: center;
    }
    .status-badge-t {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: #fef3c7;
        color: #b45309;
        font-weight: 700;
        font-size: 11px;
        min-width: 14px;
        text-align: center;
    }
    .status-badge-a {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        background: #fee2e2;
        color: #dc2626;
        font-weight: 700;
        font-size: 11px;
        min-width: 14px;
        text-align: center;
    }
    .status-badge-empty {
        color: #cbd5e1;
        font-weight: 600;
    }

    /* Modal Backdrop */
    .rp-modal-backdrop {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(3px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .rp-modal-dialog {
        background: #ffffff;
        border-radius: 16px;
        width: 100%;
        max-width: 580px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .rp-modal-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .rp-modal-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .rp-modal-close-btn {
        background: transparent;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #94a3b8;
    }
    .rp-modal-body {
        padding: 18px 20px;
    }
    .rp-wa-textarea {
        width: 100%;
        height: 260px;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12.5px;
        padding: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        box-sizing: border-box;
        outline: none;
        white-space: pre-wrap;
    }
    .rp-modal-footer {
        padding: 14px 20px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }
    .btn-secondary {
        padding: 8px 16px;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
    }
</style>

<div class="pj-container">

    {{-- 1. TOP FILTER BAR (PERSIS GAMBAR 1) --}}
    <div class="pj-filter-card">
        <form action="{{ route('rekap.pengajian.index') }}" method="GET" id="formPengajianFilter">
            <div class="pj-filter-grid">
                {{-- Dropdown Kelas Pengajian --}}
                <div class="pj-filter-group">
                    <label class="pj-filter-label">Kelas Pengajian</label>
                    <select name="kelas_pengajian_id" class="pj-select" onchange="this.form.submit()">
                        @foreach($kelasList as $kp)
                            <option value="{{ $kp->id }}" {{ (string) $selectedKelasId === (string) $kp->id ? 'selected' : '' }}>
                                {{ strtolower($kp->nama_kelas) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Datepicker Bulan --}}
                <div class="pj-filter-group" style="max-width: 320px;">
                    <label class="pj-filter-label">Bulan</label>
                    <div style="position: relative;">
                        <input type="month"
                               name="bulan_tahun"
                               value="{{ sprintf('%04d-%02d', $tahun, $bulan) }}"
                               class="pj-input"
                               onchange="handleMonthChange(this.value)">
                        <input type="hidden" name="bulan" id="hiddenBulan" value="{{ $bulan }}">
                        <input type="hidden" name="tahun" id="hiddenTahun" value="{{ $tahun }}">
                    </div>
                </div>

                {{-- Tombol Laporan WA (Hijau Sesuai Gambar 1) --}}
                <div style="margin-left: auto;">
                    <button type="button" class="pj-btn-wa" onclick="openWaModal()">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                        </svg>
                        <span>Laporan WA</span>
                    </button>

                    <a href="{{ route('rekap.pengajian.export', request()->query()) }}" class="btn-secondary" style="margin-left: 8px; height: 42px; display: inline-flex; align-items: center; box-sizing: border-box;" title="Unduh CSV">
                        <span>Unduh CSV</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 2. TABEL MATRIKS BULANAN (PERSIS GAMBAR 1) --}}
    <div class="pj-table-card">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-bottom:1px solid #e5e7eb; background:#ffffff;">
            <div style="display:flex; align-items:center; gap:10px;">
                <h2 style="font-size:14.5px; font-weight:700; color:#111827; margin:0;">Data Presensi Pengajian</h2>
                <span style="display:inline-flex; align-items:center; padding:2px 10px; border-radius:9999px; font-size:12px; font-weight:600; background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb;">
                    {{ $totalSantri }} Santri
                </span>
            </div>
            <div style="font-size:12px; color:#6b7280; font-weight:500;">
                Sesi Pengajian: Bada Subuh, Pagi 1, Pagi 2, Siang, Bada Maghrib, Malam
            </div>
        </div>
        <div class="pj-table-scroll">
            <table class="pj-matrix-table">
                <thead>
                    {{-- Baris 1: Angka Tanggal --}}
                    <tr>
                        <th rowspan="2" class="th-fixed-no">NO</th>
                        <th rowspan="2" class="th-fixed-nama">NAMA SANTRI</th>
                        @foreach($dateColumns as $col)
                            <th colspan="{{ count($col['sessions']) }}" class="th-date">
                                {{ $col['day_num'] }}
                            </th>
                        @endforeach
                    </tr>

                    {{-- Baris 2: Sub-sesi di bawah tiap tanggal --}}
                    <tr>
                        @foreach($dateColumns as $col)
                            @foreach($col['sessions'] as $sess)
                                <th class="th-session" title="{{ $sess['label'] }}">
                                    {{ $sess['short'] }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($matrixRows as $row)
                    <tr>
                        <td class="th-fixed-no">{{ $row['no'] }}</td>
                        <td class="td-nama">{{ $row['nama_lengkap'] }}</td>
                        @foreach($dateColumns as $dStr => $col)
                            @foreach($col['sessions'] as $sess)
                                @php
                                    $cellKey = "{$dStr}_{$sess['key']}";
                                    $cell = $row['cells'][$cellKey] ?? ['code' => '-', 'badge' => 'status-badge-empty'];
                                @endphp
                                <td>
                                    <span class="{{ $cell['badge'] }}">{{ $cell['code'] }}</span>
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 2 + max(1, count($dateColumns)) }}" style="padding: 40px; color: #94a3b8; text-align: center;">
                            Tidak ada data santri pada kelas pengajian ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Modal Laporan WhatsApp (Persis Gambar 3 dengan Filter Kelas & Kamar) --}}
<div id="modalLaporanWA" class="rp-modal-backdrop" style="display:none;">
    <div class="rp-modal-dialog" style="max-width: 620px;">
        <div class="rp-modal-header" style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:10px; background:#dcfce7; display:flex; align-items:center; justify-content:center; color:#16a34a; flex-shrink:0;">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="rp-modal-title" style="font-size:16px; font-weight:700; color:#0f172a; margin:0;">Preview Laporan WhatsApp</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">
                        Laporan absensi harian kelas {{ strtolower($selectedKelas ? $selectedKelas->nama_kelas : 'pengajian') }}
                    </div>
                </div>
            </div>
            <button type="button" class="rp-modal-close-btn" onclick="closeWaModal()">&times;</button>
        </div>

        <div class="rp-modal-body" style="padding:18px 20px;">
            {{-- Filter Grid (Tanggal & Filter Kamar Sesuai Gambar Mockup) --}}
            <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:14px; margin-bottom:14px;">
                <div>
                    <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">PILIH TANGGAL LAPORAN</label>
                    <select id="waDateSelect" class="pj-select" style="height:38px; font-size:12px; padding:6px 10px;" onchange="renderPengajianWaPreview()">
                        @foreach($dateList as $dItem)
                            <option value="{{ $dItem['date'] }}" {{ $loop->last ? 'selected' : '' }}>
                                {{ $dItem['formatted'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">FILTER KAMAR</label>
                    <select id="waKamarSelect" class="pj-select" style="height:38px; font-size:12px; padding:6px 10px;" onchange="renderPengajianWaPreview()">
                        <option value="semua">-- Semua Kamar --</option>
                        @foreach($kamarList as $kmr)
                            <option value="{{ $kmr }}">{{ $kmr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Textarea Preview Teks --}}
            <div>
                <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:5px;">TEKS PESAN WHATSAPP</label>
                <textarea id="waTextarea" class="rp-wa-textarea" spellcheck="false" style="height:270px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12.5px; line-height:1.6; border-radius:10px;"></textarea>
            </div>

            <div id="copyNotif" style="display:none;margin-top:8px;padding:8px;background:#dcfce7;color:#15803d;font-size:12px;font-weight:600;border-radius:6px;text-align:center;">
                Format WhatsApp berhasil disalin ke clipboard!
            </div>
        </div>

        <div class="rp-modal-footer" style="display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid #e5e7eb; background:#f8fafc; border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
            <div style="font-size:11.5px; color:#64748b;">
                Gunakan tombol di kanan untuk menyalin teks pesan absensi.
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="btn-secondary" onclick="closeWaModal()" style="height:38px; padding:0 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer;">
                    Tutup
                </button>
                <button type="button" class="pj-btn-wa" onclick="copyWaText()" id="btnSalinWa" style="height:38px; padding:0 18px; border-radius:8px; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span id="btnSalinWaText">Salin Teks</span>
                </button>
                <button type="button" class="btn-secondary" onclick="openWaWeb()" title="Buka WhatsApp Web" style="height:38px; padding:0 12px; border-radius:8px; display:inline-flex; align-items:center; color:#16a34a; border-color:#86efac; background:#f0fdf4;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.PENGAJIAN_MATRIX_ROWS = {!! json_encode($matrixRows) !!};
    window.PENGAJIAN_DATE_LIST = {!! json_encode($dateList) !!};
    window.PENGAJIAN_DATE_COLS = {!! json_encode($dateColumns) !!};
    window.PENGAJIAN_KELAS_NAME = {!! json_encode($selectedKelas ? strtoupper($selectedKelas->nama_kelas) : 'PENGAJIAN') !!};

    function handleMonthChange(val) {
        if (!val) return;
        var parts = val.split('-');
        if (parts.length === 2) {
            document.getElementById('hiddenTahun').value = parts[0];
            document.getElementById('hiddenBulan').value = parseInt(parts[1], 10);
            document.getElementById('formPengajianFilter').submit();
        }
    }

    function renderPengajianWaPreview() {
        var dateEl = document.getElementById('waDateSelect');
        var kamarEl = document.getElementById('waKamarSelect');
        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;

        var selDate = dateEl ? dateEl.value : '';
        var selKamar = kamarEl ? kamarEl.value : 'semua';

        var dateObj = null;
        if (window.PENGAJIAN_DATE_LIST) {
            for (var i = 0; i < window.PENGAJIAN_DATE_LIST.length; i++) {
                if (window.PENGAJIAN_DATE_LIST[i].date === selDate) {
                    dateObj = window.PENGAJIAN_DATE_LIST[i];
                    break;
                }
            }
            if (!dateObj && window.PENGAJIAN_DATE_LIST.length > 0) {
                dateObj = window.PENGAJIAN_DATE_LIST[window.PENGAJIAN_DATE_LIST.length - 1];
            }
        }

        var dayName = dateObj ? dateObj.day_name : 'Hari';
        var formattedDate = dateObj ? dateObj.formatted : selDate;
        var sessions = (dateObj && dateObj.sessions) ? dateObj.sessions : [];

        var targetKelas = window.PENGAJIAN_KELAS_NAME;
        if (selKamar !== 'semua') {
            targetKelas += ' (KAMAR ' + selKamar.toUpperCase() + ')';
        }

        var header = "🗓️ *LAPORAN ABSENSI KELAS " + targetKelas + "*\n" +
                     "_" + dayName + ", " + formattedDate + "_\n\n" +
                     "*Daftar Kehadiran Siswa:*";

        var filtered = (window.PENGAJIAN_MATRIX_ROWS || []).filter(function(r) {
            if (selKamar !== 'semua' && r.kamar && r.kamar.toLowerCase().indexOf(selKamar.toLowerCase()) === -1) {
                return false;
            }
            return true;
        });

        var lines = [];
        var totalHadir = 0;
        var totalTelatIzin = 0;
        var totalAlfa = 0;

        filtered.forEach(function(r, idx) {
            var emojis = [];
            if (sessions.length === 0) {
                emojis.push('✅');
            } else {
                sessions.forEach(function(sess) {
                    var cellKey = (dateObj ? dateObj.date : selDate) + '_' + sess.key;
                    var cell = (r.cells && r.cells[cellKey]) ? r.cells[cellKey] : null;
                    var code = cell ? cell.code : 'H';
                    if (code === 'H') {
                        emojis.push('✅');
                        totalHadir++;
                    } else if (code === 'T') {
                        emojis.push('⚠️');
                        totalTelatIzin++;
                    } else if (code === 'I' || code === 'S') {
                        emojis.push('✉️');
                        totalTelatIzin++;
                    } else if (code === 'A') {
                        emojis.push('❌');
                        totalAlfa++;
                    } else {
                        emojis.push('✅');
                        totalHadir++;
                    }
                });
            }
            lines.push((idx + 1) + '. ' + (r.nama_bersih || r.nama_lengkap) + ' ' + emojis.join(' '));
        });

        var body = lines.length > 0 ? lines.join("\n") : "Tidak ada siswa pada kelompok ini.";
        var summary = "*Ringkasan Kehadiran:*\n" +
                      "• Total Santri: " + filtered.length + " Siswa\n" +
                      "• Total Hadir: " + totalHadir + "\n" +
                      "• Total Izin/Sakit/Telat: " + totalTelatIzin + "\n" +
                      "• Total Alfa: " + totalAlfa;

        textarea.value = header + "\n\n" + body + "\n\n" + summary;
    }

    function setupModalDom() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupModalDom);
    } else {
        setupModalDom();
    }

    function openWaModal() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal) {
            setupModalDom();
            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
            renderPengajianWaPreview();
        }
    }

    function closeWaModal() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            document.body.style.overflow = '';
        }
    }

    function copyWaText() {
        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(textarea.value).then(function() {
            var btnText = document.getElementById('btnSalinWaText');
            if (btnText) btnText.textContent = 'Tersalin!';
            var notif = document.getElementById('copyNotif');
            if (notif) {
                notif.style.display = 'block';
                setTimeout(function() {
                    notif.style.display = 'none';
                    if (btnText) btnText.textContent = 'Salin Teks';
                }, 3000);
            }
        });
    }

    function openWaWeb() {
        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;
        var encoded = encodeURIComponent(textarea.value);
        window.open('https://web.whatsapp.com/send?text=' + encoded, '_blank');
    }

    // Expose fungsi ke window untuk kemudahan akses
    window.openWaModal = openWaModal;
    window.closeWaModal = closeWaModal;
    window.copyWaText = copyWaText;
    window.openWaWeb = openWaWeb;
    window.renderPengajianWaPreview = renderPengajianWaPreview;

    // Klik di luar dialog untuk menutup & Tombol Escape
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('modalLaporanWA');
        if (modal && e.target === modal) {
            closeWaModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeWaModal();
        }
    });

    // Inisialisasi awal saat load
    document.addEventListener('DOMContentLoaded', function() {
        renderPengajianWaPreview();
    });
</script>
@endsection
