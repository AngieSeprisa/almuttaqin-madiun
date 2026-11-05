@extends('layouts.app')

@section('title', 'Siswa')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'create'))
    <a href="{{ route('pendaftaran.create') }}" class="btn btn-primary btn-header-action" style="display:inline-flex; align-items:center; gap:8px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Pendaftaran Baru</span>
    </a>
    @endif
</div>
@endsection

@push('styles')
<style>
/* Baris santri belum lengkap (merah tipis dengan opacity 90% merah) */
.modern-table tbody tr.row-incomplete > td {
    background-color: rgba(239, 68, 68, 0.08) !important;
    transition: background-color 0.15s ease;
}

.modern-table tbody tr.row-incomplete:hover > td {
    background-color: rgba(239, 68, 68, 0.14) !important;
}

.modern-table tbody tr.row-incomplete > td:first-child {
    border-left: 3px solid #ef4444 !important;
}

.dark-mode .modern-table tbody tr.row-incomplete > td {
    background-color: rgba(239, 68, 68, 0.12) !important;
}

.dark-mode .modern-table tbody tr.row-incomplete:hover > td {
    background-color: rgba(239, 68, 68, 0.20) !important;
}

/* Badge belum lengkap & info teks */
.nama-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.nama-text {
    font-weight: 600;
    color: var(--text-main);
}

.badge-incomplete {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.25);
    color: #dc2626;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.2;
    cursor: default;
}

.dark-mode .badge-incomplete {
    background: rgba(239, 68, 68, 0.22);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

.text-missing-fields {
    font-size: 11.5px;
    color: #b91c1c;
    margin-top: 3px;
    line-height: 1.3;
}

.dark-mode .text-missing-fields {
    color: #fca5a5;
}

.missing-label {
    font-weight: 600;
    color: #dc2626;
}

.dark-mode .missing-label {
    color: #f87171;
}

/* Action button koreksi */
.action-btn.action-btn-koreksi {
    color: #dc2626;
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
}

.action-btn.action-btn-koreksi:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    transform: translateY(-1px);
}

/* Action button print */
.action-btn.action-btn-print {
    color: #0284c7;
    background: rgba(2, 132, 199, 0.1);
    border-color: rgba(2, 132, 199, 0.3);
}

.action-btn.action-btn-print:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    transform: translateY(-1px);
}

/* Banner Notifikasi Kelengkapan Data */
.alert-incomplete-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 12px 18px;
    background: rgba(239, 68, 68, 0.07);
    border: 1px solid rgba(239, 68, 68, 0.22);
    border-left: 4px solid #ef4444;
    border-radius: 12px;
    font-size: 13.5px;
    color: var(--text-main);
    flex-wrap: wrap;
}

.alert-incomplete-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    flex-shrink: 0;
}

.alert-incomplete-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 240px;
}

.alert-incomplete-body strong {
    color: #b91c1c;
}

.dark-mode .alert-incomplete-body strong {
    color: #fca5a5;
}

.alert-incomplete-body span {
    font-size: 12.5px;
    color: var(--text-muted);
}

.btn-filter-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #dc2626;
    font-weight: 600;
    font-size: 12.5px;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn-filter-tag:hover,
.btn-filter-tag.active {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

.dark-mode .btn-filter-tag {
    background: var(--card-bg, #1e293b);
    border-color: rgba(239, 68, 68, 0.4);
    color: #f87171;
}

.dark-mode .btn-filter-tag:hover,
.dark-mode .btn-filter-tag.active {
    background: #dc2626;
    color: #ffffff;
}

/* ========================================================
   TAMPILAN KHUSUS MOBILE SESUAI GAMBAR (<= 768px)
   ======================================================== */
.mobile-siswa-list {
    display: none;
}

/* Sembunyikan bottom sheet di desktop */
.mobile-sheet-backdrop,
.mobile-bottom-sheet {
    display: none;
}

@media (max-width: 768px) {
    /* Sembunyikan hanya tabel baris desktop di mobile, container card & toolbar tetap tampil */
    .table-wrapper,
    .fi-ta-table-wrap {
        display: none !important;
    }

    /* Hilangkan border & padding kaku table-card di mobile agar card list mengalir rapi */
    .table-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Tampilkan daftar card mobile */
    .mobile-siswa-list {
        display: flex !important;
        flex-direction: column;
        gap: 10px;
        padding: 0 0 16px 0;
    }

    /* Container Card Siswa Mobile Sesuai Gambar Referensi */
    .mobile-siswa-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 13px 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        transition: transform 0.12s ease, box-shadow 0.12s ease, background-color 0.12s ease;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }

    .mobile-siswa-card:active {
        transform: scale(0.99);
        background-color: #f8fafc;
    }

    .dark-mode .mobile-siswa-card {
        background: var(--card-bg, #1e293b);
        border-color: var(--border-light, #334155);
        box-shadow: none;
    }

    .dark-mode .mobile-siswa-card:active {
        background-color: #27354a;
    }

    /* Highlight merah tipis untuk santri data belum lengkap */
    .mobile-siswa-card.row-incomplete {
        background-color: rgba(239, 68, 68, 0.06) !important;
        border-color: rgba(239, 68, 68, 0.3) !important;
        border-left: 4px solid #ef4444 !important;
    }

    .dark-mode .mobile-siswa-card.row-incomplete {
        background-color: rgba(239, 68, 68, 0.12) !important;
        border-color: rgba(239, 68, 68, 0.45) !important;
    }

    /* Sisi Kiri: Info Santri */
    .mobile-siswa-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .mobile-siswa-nama-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .mobile-siswa-nama {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        word-break: break-word;
    }

    .dark-mode .mobile-siswa-nama {
        color: #f8fafc;
    }

    /* Subtitle (Kamar / Kolom Kustom) */
    .mobile-siswa-subtitles {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.3;
    }

    .dark-mode .mobile-siswa-subtitles {
        color: #94a3b8;
    }

    .mobile-sub-item {
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    .mobile-sub-item .sub-label {
        color: #94a3b8;
        font-size: 11.5px;
    }

    .dark-mode .mobile-sub-item .sub-label {
        color: #64748b;
    }

    /* Sisi Kanan: Status Badge & Chevron */
    .mobile-siswa-status-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .mobile-card-chevron {
        width: 16px;
        height: 16px;
        color: #94a3b8;
        opacity: 0.7;
    }

    .dark-mode .mobile-card-chevron {
        color: #64748b;
    }

    .mobile-badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 600;
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        line-height: 1.25;
        white-space: nowrap;
    }

    .dark-mode .mobile-badge-pill {
        background-color: #334155;
        color: #f1f5f9;
        border-color: #475569;
    }

    /* Status badge (Aktif / Pulang / Sakit) */
    .mobile-badge-pill.status-aktif {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .dark-mode .mobile-badge-pill.status-aktif {
        background-color: rgba(34, 197, 94, 0.18);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.35);
    }

    .mobile-badge-pill.status-pulang {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .dark-mode .mobile-badge-pill.status-pulang {
        background-color: rgba(245, 158, 11, 0.18);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.35);
    }

    .mobile-badge-pill.status-sakit {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .dark-mode .mobile-badge-pill.status-sakit {
        background-color: rgba(239, 68, 68, 0.18);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.35);
    }

    /* ========================================================
       BOTTOM SHEET STYLING (Sesuai Gambar Referensi)
       ======================================================== */
    .mobile-sheet-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 9998;
        opacity: 0;
        transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }
    .mobile-sheet-backdrop.is-active {
        opacity: 1;
        pointer-events: auto;
    }

    .mobile-bottom-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        max-width: 540px;
        margin: 0 auto;
        background: #ffffff;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        max-height: 85vh;
        overflow: hidden;
    }

    .dark-mode .mobile-bottom-sheet {
        background: #1e293b;
        border-top: 1px solid #334155;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5);
    }

    .mobile-bottom-sheet.is-open {
        transform: translateY(0);
    }

    .sheet-handle-bar {
        width: 100%;
        padding: 12px 0 8px;
        display: flex;
        justify-content: center;
        cursor: grab;
    }

    .sheet-drag-handle {
        width: 48px;
        height: 5px;
        border-radius: 999px;
        background: #cbd5e1;
    }

    .dark-mode .sheet-drag-handle {
        background: #475569;
    }

    .sheet-header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 6px 20px 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .dark-mode .sheet-header {
        border-bottom-color: #334155;
    }

    .sheet-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #ffffff;
        font-size: 16px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .sheet-title-wrap {
        flex: 1;
        min-width: 0;
    }

    .sheet-siswa-nama {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark-mode .sheet-siswa-nama {
        color: #f8fafc;
    }

    .sheet-siswa-meta {
        font-size: 12.5px;
        color: #64748b;
        margin-top: 3px;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .dark-mode .sheet-siswa-meta {
        color: #94a3b8;
    }

    .sheet-actions-list {
        padding: 10px 14px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .sheet-action-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        border-radius: 14px;
        background: transparent;
        border: none;
        text-decoration: none;
        color: #1e293b;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.1s ease;
        width: 100%;
        text-align: left;
        box-sizing: border-box;
    }

    .dark-mode .sheet-action-item {
        color: #f1f5f9;
    }

    .sheet-action-item:active,
    .sheet-action-item:hover {
        background: #f8fafc;
    }

    .dark-mode .sheet-action-item:active,
    .dark-mode .sheet-action-item:hover {
        background: #334155;
    }

    .sheet-action-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sheet-action-icon svg {
        width: 20px;
        height: 20px;
    }

    .sheet-action-icon.icon-view {
        background: #eff6ff;
        color: #2563eb;
    }
    .dark-mode .sheet-action-icon.icon-view {
        background: rgba(37, 99, 235, 0.2);
        color: #60a5fa;
    }

    .sheet-action-icon.icon-edit {
        background: #fefce8;
        color: #ca8a04;
    }
    .dark-mode .sheet-action-icon.icon-edit {
        background: rgba(202, 138, 4, 0.2);
        color: #facc15;
    }

    .sheet-action-icon.icon-print {
        background: #f0fdf4;
        color: #16a34a;
    }
    .dark-mode .sheet-action-icon.icon-print {
        background: rgba(22, 163, 74, 0.2);
        color: #4ade80;
    }

    .sheet-action-icon.icon-rfid {
        background: #f5f3ff;
        color: #7c3aed;
    }
    .dark-mode .sheet-action-icon.icon-rfid {
        background: rgba(124, 58, 237, 0.2);
        color: #a78bfa;
    }

    .sheet-action-icon.icon-delete {
        background: #fef2f2;
        color: #ef4444;
    }
    .dark-mode .sheet-action-icon.icon-delete {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .sheet-action-text {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .sheet-action-title {
        font-size: 14.5px;
        font-weight: 600;
        line-height: 1.3;
    }

    .sheet-action-desc {
        font-size: 12px;
        color: #64748b;
        margin-top: 1px;
    }

    .dark-mode .sheet-action-desc {
        color: #94a3b8;
    }

    .sheet-action-item.is-danger .sheet-action-title {
        color: #ef4444;
    }
    .dark-mode .sheet-action-item.is-danger .sheet-action-title {
        color: #f87171;
    }

    .sheet-chevron {
        width: 16px;
        height: 16px;
        color: #cbd5e1;
        flex-shrink: 0;
    }
    .dark-mode .sheet-chevron {
        color: #64748b;
    }

    .sheet-footer {
        padding: 12px 18px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .dark-mode .sheet-footer {
        border-top-color: #334155;
    }

    .sheet-btn-close {
        width: 100%;
        height: 48px;
        border-radius: 14px;
        background: #234e38; /* Sage/forest green sesuai gambar contoh */
        color: #ffffff;
        font-size: 15px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.15s ease, transform 0.1s ease;
    }

    .sheet-btn-close:active {
        transform: scale(0.98);
        opacity: 0.9;
    }

    .dark-mode .sheet-btn-close {
        background: #2d5a44;
    }
}

    /* Filter Card & Search Bar Mobile Responsive Sesuai Gambar */
    .filter-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin-bottom: 2px !important;
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .filter-search-wrap {
        order: 1;
        width: 100%;
        position: relative;
        margin: 0;
        height: 42px;
    }

    .filter-search-input {
        width: 100%;
        height: 42px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #d1d5db;
        padding: 0 40px 0 14px;
        font-size: 13.5px;
        color: var(--text-main);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .dark-mode .filter-search-input {
        background: var(--card-bg, #1e293b);
        border-color: var(--border-light, #334155);
        color: #f8fafc;
    }

    .filter-search-btn {
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        width: 34px;
        height: 34px;
        background: transparent !important;
        border: none !important;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    /* Horizontal scrollable pills filter row */
    .filter-group-left {
        order: 2;
        display: flex !important;
        flex-direction: row !important;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-bottom: 4px;
        width: 100%;
    }
    .filter-group-left::-webkit-scrollbar {
        display: none;
    }

    .filter-select-wrap {
        flex: 0 0 auto;
        width: auto;
        min-width: 0;
        position: relative;
    }

    .filter-select-icon-left {
        display: none !important;
    }

    .filter-select {
        height: 34px !important;
        line-height: 34px !important;
        padding: 0 26px 0 14px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
        border-radius: 9999px !important;
        cursor: pointer;
        white-space: nowrap;
        appearance: none;
        -webkit-appearance: none;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .filter-select option {
        background-color: #ffffff;
        color: #1e293b;
    }
    .dark-mode .filter-select option {
        background-color: #1e293b;
        color: #f8fafc;
    }

    /* Pill 1: Jenjang (Background Slate/Gray sesuai gambar) */
    .filter-select-wrap:nth-child(1) .filter-select {
        background-color: #64748b !important;
        color: #ffffff !important;
        border: 1px solid #64748b !important;
    }
    .filter-select-wrap:nth-child(1) .filter-select-icon-right {
        color: #ffffff !important;
    }

    /* Pill 2, 3, 4: Kamar, Status, Kelengkapan (Background putih dengan border sesuai gambar) */
    .filter-select-wrap:nth-child(2) .filter-select,
    .filter-select-wrap:nth-child(3) .filter-select,
    .filter-select-wrap:nth-child(4) .filter-select {
        background-color: #ffffff !important;
        color: #334155 !important;
        border: 1px solid #cbd5e1 !important;
    }
    .filter-select-wrap:nth-child(2) .filter-select-icon-right,
    .filter-select-wrap:nth-child(3) .filter-select-icon-right,
    .filter-select-wrap:nth-child(4) .filter-select-icon-right {
        color: #64748b !important;
    }

    .dark-mode .filter-select-wrap:nth-child(2) .filter-select,
    .dark-mode .filter-select-wrap:nth-child(3) .filter-select,
    .dark-mode .filter-select-wrap:nth-child(4) .filter-select {
        background-color: var(--card-bg, #1e293b) !important;
        color: #f1f5f9 !important;
        border-color: var(--border-light, #334155) !important;
    }

    .filter-select-icon-right {
        right: 9px;
        pointer-events: none;
    }
    .filter-select-icon-right svg {
        width: 12px !important;
        height: 12px !important;
    }

    .filter-btn-reset {
        height: 34px;
        padding: 0 12px;
        border-radius: 9999px;
        font-size: 11.5px;
        flex: 0 0 auto;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .mobile-empty-card {
        background: #ffffff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 32px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-size: 13px;
        text-align: center;
    }
    .dark-mode .mobile-empty-card {
        background: var(--card-bg);
        border-color: var(--border-light);
    }
}
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:100%; min-width:0;">

    {{-- Feedback Alerts --}}
    @if (session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Terjadi kesalahan saat memproses data:</strong>
            <ul style="margin: 4px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Banner Notifikasi Kelengkapan Data Diri Santri --}}
    @if(isset($countBelumLengkap) && $countBelumLengkap > 0)
    <div class="alert-incomplete-info">
        <div class="alert-incomplete-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
        </div>
        <div class="alert-incomplete-body">
            <strong>Terdapat {{ $countBelumLengkap }} santri dengan data diri belum lengkap</strong>
            <span>Baris santri ditandai merah tipis untuk memudahkan identifikasi. Klik icon pensil (<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>) pada baris tersebut untuk mengoreksi / melengkapi data.</span>
        </div>
        <div class="alert-incomplete-action">
            @if(request('kelengkapan') === 'belum_lengkap')
                <a href="{{ route('siswa', request()->except('kelengkapan')) }}" class="btn-filter-tag active" title="Tampilkan semua santri">
                    Tampilkan Semua
                </a>
            @else
                <a href="{{ route('siswa', array_merge(request()->query(), ['kelengkapan' => 'belum_lengkap'])) }}" class="btn-filter-tag" title="Saring hanya santri yang belum lengkap">
                    Saring yang Perlu Koreksi ({{ $countBelumLengkap }})
                </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Filter & Search Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('siswa') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Jenjang --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="jenjang" id="filter_jenjang" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Jenjang</option>
                        @foreach($jenjangList as $itemJenjang)
                            <option value="{{ $itemJenjang }}" {{ request('jenjang') === $itemJenjang ? 'selected' : '' }}>
                                {{ $itemJenjang }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Kamar --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <select name="kamar" id="filter_kamar" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kamar</option>
                        @foreach($kamars as $k)
                            <option value="{{ $k }}" {{ request('kamar') === $k ? 'selected' : '' }}>
                                {{ $k }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Status --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <select name="status" id="filter_status" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="pulang" {{ request('status') === 'pulang' ? 'selected' : '' }}>Pulang</option>
                        <option value="sakit" {{ request('status') === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Kelengkapan Data Diri --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <select name="kelengkapan" id="filter_kelengkapan" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status Data</option>
                        <option value="belum_lengkap" {{ request('kelengkapan') === 'belum_lengkap' ? 'selected' : '' }}>
                            Perlu Koreksi {{ isset($countBelumLengkap) && $countBelumLengkap > 0 ? "($countBelumLengkap)" : '' }}
                        </option>
                        <option value="lengkap" {{ request('kelengkapan') === 'lengkap' ? 'selected' : '' }}>
                            Data Lengkap
                        </option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                @if(request('jenjang') || request('kamar') || request('status') || request('kelengkapan') || request('search'))
                    <a href="{{ route('siswa') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input" placeholder="Cari nama santri/kamar...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Siswa --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Jenjang</th>
                        <th>Kamar</th>
                        <th>Status</th>
                        <th>Kartu RFID</th>
                        <th data-col-hidden="true" style="display:none;">Tinggi Badan</th>
                        <th data-col-hidden="true" style="display:none;">Berat Badan</th>
                        <th data-col-hidden="true" style="display:none;">Gol. Darah</th>
                        <th data-col-hidden="true" style="display:none;">NIK</th>
                        <th data-col-hidden="true" style="display:none;">NISN</th>
                        <th data-col-hidden="true" style="display:none;">No. Induk</th>
                        <th data-col-hidden="true" style="display:none;">Nama Panggilan</th>
                        <th data-col-hidden="true" style="display:none;">Tempat Lahir</th>
                        <th data-col-hidden="true" style="display:none;">Tanggal Lahir</th>
                        <th data-col-hidden="true" style="display:none;">Jenis Kelamin</th>
                        <th data-col-hidden="true" style="display:none;">Anak Ke</th>
                        <th data-col-hidden="true" style="display:none;">Tinggal Bersama</th>
                        <th data-col-hidden="true" style="display:none;">Alamat Jalan</th>
                        <th data-col-hidden="true" style="display:none;">RT / RW</th>
                        <th data-col-hidden="true" style="display:none;">Desa / Kelurahan</th>
                        <th data-col-hidden="true" style="display:none;">Kecamatan</th>
                        <th data-col-hidden="true" style="display:none;">Kabupaten / Kota</th>
                        <th data-col-hidden="true" style="display:none;">Provinsi</th>
                        <th data-col-hidden="true" style="display:none;">No. HP / WA (PJ)</th>
                        <th data-col-hidden="true" style="display:none;">Nama PJ Biaya</th>
                        <th data-col-hidden="true" style="display:none;">Pendidikan Terakhir</th>
                        <th data-col-hidden="true" style="display:none;">Asal Sekolah</th>
                        <th data-col-hidden="true" style="display:none;">Tahun Lulus</th>
                        <th data-col-hidden="true" style="display:none;">Status Mondok</th>
                        <th data-col-hidden="true" style="display:none;">Riwayat Penyakit</th>
                        <th data-col-hidden="true" style="display:none;">Alergi</th>
                        <th data-col-hidden="true" style="display:none;">Keahlian / Prestasi</th>
                        <th data-col-hidden="true" style="display:none;">Nama Ayah</th>
                        <th data-col-hidden="true" style="display:none;">Pekerjaan Ayah</th>
                        <th data-col-hidden="true" style="display:none;">No. HP Ayah</th>
                        <th data-col-hidden="true" style="display:none;">Nama Ibu</th>
                        <th data-col-hidden="true" style="display:none;">Pekerjaan Ibu</th>
                        <th data-col-hidden="true" style="display:none;">No. HP Ibu</th>
                        <th data-col-hidden="true" style="display:none;">Penerima KIP</th>
                        <th data-col-hidden="true" class="col-aksi-head" style="text-align:center; width:150px; display:none;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswa as $item)
                    @php
                        $p = $item->pendaftaran;
                        $isLengkap = $item->isDataLengkap();
                        $missingFields = !$isLengkap ? $item->field_belum_lengkap : [];
                        $targetViewUrl = route('siswa.show', $item->id);
                        $targetEditUrl = route('siswa.edit', $item->id);
                        $targetCetakUrl = route('siswa.cetak', $item->id);
                    @endphp
                    <tr class="{{ !$isLengkap ? 'row-incomplete' : '' }}"
                        data-id="{{ $item->id }}"
                        data-nama="{{ $item->nama_lengkap }}"
                        data-view-url="{{ $targetViewUrl }}"
                        data-edit-url="{{ $targetEditUrl }}"
                        data-cetak-url="{{ $targetCetakUrl }}">
                        <td class="cell-nama">
                            <div class="nama-wrapper">
                                <div class="nama-text">{{ $item->nama_lengkap }}</div>
                                @if(!$isLengkap)
                                    <span class="badge-incomplete" title="Data belum lengkap: {{ implode(', ', $missingFields) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        Perlu Dilengkapi
                                    </span>
                                @endif
                            </div>
                            @if(!$isLengkap && count($missingFields) > 0)
                                <div class="text-missing-fields" title="Field yang belum diisi: {{ implode(', ', $missingFields) }}">
                                    <span class="missing-label">Kurang:</span> {{ implode(', ', array_slice($missingFields, 0, 3)) }}{{ count($missingFields) > 3 ? ', +' . (count($missingFields) - 3) . ' lainnya' : '' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @php
                                $j = $item->jenjang ?? ($p?->jenjang ?? 'Reguler');
                                $badgeColor = match($j) {
                                    'SMP' => 'smp',
                                    'SMA' => 'sma',
                                    default => 'pondok',
                                };
                            @endphp
                            <span class="badge-lembaga badge-lembaga-{{ $badgeColor }}">{{ $j }}</span>
                        </td>
                        <td class="cell-kamar">{{ $item->kamar ?: '-' }}</td>
                        <td class="cell-status">
                            <span class="status-badge status-{{ strtolower($item->status ?? 'aktif') }}">
                                {{ ucfirst($item->status ?? 'aktif') }}
                            </span>
                        </td>
                        <td class="cell-rfid">
                            @if(!empty($item->rfid_uid))
                                <span class="badge-rfid-active" style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); font-family: monospace; font-weight: 600; padding: 2px 7px; border-radius: 6px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                                    {{ $item->rfid_uid }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 11px; font-style: italic;">Belum dipairing</span>
                            @endif
                        </td>

                        {{-- Seluruh Kolom Data Diri Siswa (Tinggi Badan, Berat Badan, Gol Darah, NIK, dll) --}}
                        <td style="display:none;">{{ $p?->tinggi_badan ? $p->tinggi_badan . ' cm' : '-' }}</td>
                        <td style="display:none;">{{ $p?->berat_badan ? $p->berat_badan . ' kg' : '-' }}</td>
                        <td style="display:none;">{{ $p?->gol_darah ?: '-' }}</td>
                        <td style="display:none; font-family:monospace;">{{ $p?->nik ?: '-' }}</td>
                        <td style="display:none; font-family:monospace;">{{ $p?->nisn ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->no_induk ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->nama_panggilan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->tempat_lahir ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->tanggal_lahir ? date('d/m/Y', strtotime($p->tanggal_lahir)) : '-' }}</td>
                        <td style="display:none;">{{ $p?->jenis_kelamin ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->anak_ke ? 'Ke-' . $p->anak_ke . ($p->jumlah_saudara ? ' (dari ' . $p->jumlah_saudara . ')' : '') : '-' }}</td>
                        <td style="display:none;">{{ $p?->tinggal_bersama ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->alamat_jalan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->rt_rw ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->desa_kelurahan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->kecamatan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->kab_kota ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->provinsi ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->pj_biaya_hp ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->pj_biaya_nama ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->pendidikan_terakhir ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->nama_sekolah_asal ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->tahun_lulus ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->status_mondok ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->penyakit_diderita ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->alergi ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->keahlian_prestasi ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->ayah_nama ? $p->ayah_nama . ($p->ayah_status === 'Meninggal' ? ' (Alm.)' : '') : '-' }}</td>
                        <td style="display:none;">{{ $p?->ayah_pekerjaan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->ayah_hp ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->ibu_nama ? $p->ibu_nama . ($p->ibu_status === 'Meninggal' ? ' (Almh.)' : '') : '-' }}</td>
                        <td style="display:none;">{{ $p?->ibu_pekerjaan ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->ibu_hp ?: '-' }}</td>
                        <td style="display:none;">{{ $p?->penerima_kip ? 'Ya' . ($p->nomor_kip ? ' (' . $p->nomor_kip . ')' : '') : 'Tidak' }}</td>

                        {{-- Kolom Aksi (Dipindahkan ke Tombol Aksi di Kiri Atas Toolbar) --}}
                        <td class="col-aksi-cell" style="text-align:center; display:none;">
                            <div class="action-btn-group">
                                {{-- Tombol Pairing RFID --}}
                                @if(!auth()->check() || auth()->user()->canDo('siswa', 'edit'))
                                <button type="button" class="action-btn btn-rfid-pair" 
                                        data-id="{{ $item->id }}" 
                                        data-nama="{{ $item->nama_lengkap }}" 
                                        data-uid="{{ $item->rfid_uid ?? '' }}"
                                        title="Pairing / Ubah Kartu RFID Santri" 
                                        style="color: #2563eb; background: rgba(37, 99, 235, 0.1);">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                                        <line x1="2" y1="10" x2="22" y2="10"></line>
                                    </svg>
                                </button>
                                @endif

                                {{-- Tombol Lihat (Halaman View Penuh / Full Page ViewRecord) --}}
                                <a href="{{ $targetViewUrl }}" class="action-btn action-btn-view btn-view" title="Lihat Formulir Siswa" aria-label="Lihat Formulir {{ $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span class="sr-only" style="display:none;">Lihat</span>
                                </a>

                                {{-- Tombol Cetak (Format Formulir Cetak A4) --}}
                                <a href="{{ $targetCetakUrl }}" target="_blank" class="action-btn action-btn-print btn-print" title="Cetak Formulir / Biodata" aria-label="Cetak Formulir {{ $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                    <span class="sr-only" style="display:none;">Cetak</span>
                                </a>

                                {{-- Tombol Edit / Koreksi (Halaman Edit Penuh / Full Page EditRecord) --}}
                                @if(!auth()->check() || auth()->user()->canDo('siswa', 'edit'))
                                <a href="{{ $targetEditUrl }}"
                                   class="action-btn btn-edit {{ !$isLengkap ? 'action-btn-koreksi' : 'action-btn-edit' }}"
                                   title="{{ !$isLengkap ? 'Edit & Koreksi Kelengkapan Data Santri (' . implode(', ', $missingFields) . ')' : 'Edit Formulir Pendaftaran Siswa' }}"
                                   aria-label="{{ !$isLengkap ? 'Edit & Koreksi Data ' . $item->nama_lengkap : 'Edit Formulir ' . $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span class="sr-only" style="display:none;">Edit</span>
                                </a>
                                @endif

                                {{-- Tombol Hapus via SweetAlert2 --}}
                                @if(!auth()->check() || auth()->user()->canDo('siswa', 'delete'))
                                <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-delete btn-delete js-swal-confirm"
                                        data-title="Konfirmasi Hapus Siswa?"
                                        data-html="Apakah Anda yakin ingin menghapus data santri <strong>{{ $item->nama_lengkap }}</strong>?<br><div style='padding:12px 14px; background:var(--nav-hover-bg); border-radius:10px; border:1px solid var(--border-light); margin-top:12px; font-size:13px; text-align:left; line-height:1.6;'><div>Kamar: <strong>{{ $item->kamar }}</strong></div><div>Status: <strong>{{ ucfirst($item->status ?? 'aktif') }}</strong></div></div><div style='margin-top:10px; font-size:12.5px; color:#DC2626; font-weight:600;'>⚠️ Data yang sudah dihapus tidak dapat dipulihkan kembali.</div>"
                                        data-icon="warning"
                                        data-confirm-text="Ya, Hapus Data"
                                        data-confirm-color="#DC2626"
                                        data-cancel-text="Batal"
                                        title="Hapus Data Siswa" aria-label="Hapus Data {{ $item->nama_lengkap }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        <span class="sr-only" style="display:none;">Hapus</span>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="41" class="cell-empty" style="padding:40px 20px;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--text-muted);">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                </svg>
                                <span>Tidak ada data siswa yang ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tampilan Mobile Card List (Khusus Tampilan Layar HP Sesuai Gambar Referensi) --}}
    <div class="mobile-siswa-list">
        @forelse($siswa as $item)
        @php
            $isLengkap = $item->isDataLengkap();
            $missingFields = !$isLengkap ? $item->field_belum_lengkap : [];
            $targetViewUrl = route('siswa.show', $item->id);
            $targetEditUrl = route('siswa.edit', $item->id);
            $targetCetakUrl = route('siswa.cetak', $item->id);
            $jenjangText = $item->jenjang ?? ($item->pendaftaran?->jenjang ?? 'Reguler');
            $kamarText = $item->kamar ?? '-';
            $statusText = ucfirst($item->status ?? 'aktif');
            $statusKey = strtolower($item->status ?? 'aktif');
        @endphp
        <div class="mobile-siswa-card {{ !$isLengkap ? 'row-incomplete' : '' }}"
             data-id="{{ $item->id }}"
             data-nama="{{ $item->nama_lengkap }}"
             data-kamar="{{ $kamarText }}"
             data-jenjang="{{ $jenjangText }}"
             data-status="{{ $statusKey }}"
             data-status-text="{{ $statusText }}"
             data-rfid="{{ $item->rfid_uid ?? '' }}"
             data-view-url="{{ $targetViewUrl }}"
             data-edit-url="{{ $targetEditUrl }}"
             data-cetak-url="{{ $targetCetakUrl }}"
             data-delete-url="{{ route('siswa.destroy', $item->id) }}"
             data-can-edit="{{ (!auth()->check() || auth()->user()->canDo('siswa', 'edit')) ? '1' : '0' }}"
             data-can-delete="{{ (!auth()->check() || auth()->user()->canDo('siswa', 'delete')) ? '1' : '0' }}">
            
            {{-- Sisi Kiri: Nama Lengkap & Subtitle (Kamar / Kolom Kustom) --}}
            <div class="mobile-siswa-info">
                <div class="mobile-siswa-nama-wrap">
                    <span class="mobile-siswa-nama">{{ $item->nama_lengkap }}</span>
                    @if(!$isLengkap)
                        <span class="badge-incomplete" title="Data belum lengkap: {{ implode(', ', $missingFields) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Perlu Dilengkapi
                        </span>
                    @endif
                </div>

                @if(!$isLengkap && count($missingFields) > 0)
                    <div class="text-missing-fields" style="margin-bottom: 2px;" title="Field yang belum diisi: {{ implode(', ', $missingFields) }}">
                        <span class="missing-label">Kurang:</span> {{ implode(', ', array_slice($missingFields, 0, 3)) }}{{ count($missingFields) > 3 ? ', +' . (count($missingFields) - 3) . ' lainnya' : '' }}
                    </div>
                @endif

                {{-- Default: Kamar. Custom columns (Jenjang, RFID) synced via data-col-idx --}}
                <div class="mobile-siswa-subtitles">
                    <span class="mobile-sub-item" data-col-idx="3">
                        <span class="sub-label">Kamar:</span> {{ $kamarText }}
                    </span>
                    <span class="mobile-sub-item" data-col-idx="2" style="display:none;">
                        • <span class="sub-label">Jenjang:</span> {{ $jenjangText }}
                    </span>
                    <span class="mobile-sub-item" data-col-idx="5" style="display:none;">
                        • <span class="sub-label">RFID:</span> {{ $item->rfid_uid ?? '-' }}
                    </span>
                </div>
            </div>

            {{-- Sisi Kanan: Status Badge & Chevron --}}
            <div class="mobile-siswa-status-wrap">
                <span class="mobile-badge-pill status-{{ $statusKey }}">{{ $statusText }}</span>
                <svg class="mobile-card-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </div>
        @empty
        <div class="mobile-empty-card">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--text-muted);">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
            <span>Tidak ada data siswa yang ditemukan.</span>
        </div>
        @endforelse
    </div>

    {{-- Bottom Sheet Aksi Santri (Mobile Action Sheet sesuai contoh gambar) --}}
    <div id="mobileActionSheetBackdrop" class="mobile-sheet-backdrop" style="display:none;"></div>
    <div id="mobileActionSheet" class="mobile-bottom-sheet" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sheetSiswaNama">
        <div class="sheet-handle-bar">
            <span class="sheet-drag-handle"></span>
        </div>
        
        <div class="sheet-header">
            <div class="sheet-avatar" id="sheetSiswaAvatar">ST</div>
            <div class="sheet-title-wrap">
                <h3 class="sheet-siswa-nama" id="sheetSiswaNama">Nama Santri</h3>
                <div class="sheet-siswa-meta">
                    <span id="sheetSiswaKamar">Kamar -</span>
                    <span>•</span>
                    <span id="sheetSiswaJenjang">Reguler</span>
                    <span>•</span>
                    <span class="mobile-badge-pill status-aktif" id="sheetSiswaStatus">Aktif</span>
                </div>
            </div>
        </div>

        <div class="sheet-actions-list">
            <span class="sheet-section-title" style="display:none;">Aksi</span>

            {{-- Opsi 1: Lihat Formulir Siswa --}}
            <a href="#" id="sheetActionView" class="sheet-action-item btn-view" title="Lihat Formulir Siswa">
                <div class="sheet-action-icon icon-view">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
                <div class="sheet-action-text">
                    <span class="sheet-action-title">Lihat Formulir Siswa</span>
                    <span class="sheet-action-desc">Buka biodata lengkap santri</span>
                </div>
                <svg class="sheet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            {{-- Opsi 2: Edit & Koreksi Data Siswa --}}
            <a href="#" id="sheetActionEdit" class="sheet-action-item btn-edit" title="Edit Formulir Siswa">
                <div class="sheet-action-icon icon-edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <div class="sheet-action-text">
                    <span class="sheet-action-title">Edit Data Siswa</span>
                    <span class="sheet-action-desc">Ubah data identitas & kelengkapan</span>
                </div>
                <svg class="sheet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            {{-- Opsi 3: Cetak Formulir A4 --}}
            <a href="#" id="sheetActionPrint" target="_blank" class="sheet-action-item btn-print" title="Cetak Formulir A4">
                <div class="sheet-action-icon icon-print">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                </div>
                <div class="sheet-action-text">
                    <span class="sheet-action-title">Cetak Formulir A4</span>
                    <span class="sheet-action-desc">Cetak format kertas resmi santri</span>
                </div>
                <svg class="sheet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>

            {{-- Opsi 4: Pairing Kartu RFID --}}
            <button type="button" id="sheetActionRfid" class="sheet-action-item btn-rfid-pair" title="Pairing Kartu RFID">
                <div class="sheet-action-icon icon-rfid">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                </div>
                <div class="sheet-action-text">
                    <span class="sheet-action-title">Pairing Kartu RFID</span>
                    <span class="sheet-action-desc" id="sheetRfidDesc">Hubungkan kartu fisik santri</span>
                </div>
                <svg class="sheet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>

            {{-- Opsi 5: Hapus Data Siswa --}}
            <button type="button" id="sheetActionDelete" class="sheet-action-item is-danger btn-delete" title="Hapus Data Siswa">
                <div class="sheet-action-icon icon-delete">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </div>
                <div class="sheet-action-text">
                    <span class="sheet-action-title">Hapus Data Siswa</span>
                    <span class="sheet-action-desc">Hapus data santri dari aplikasi</span>
                </div>
                <svg class="sheet-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>

        {{-- Footer: Tombol Tutup Sesuai Gambar Referensi --}}
        <div class="sheet-footer">
            <button type="button" class="sheet-btn-close" id="sheetBtnClose">
                Tutup
            </button>
        </div>
    </div>

    {{-- Hidden form untuk eksekusi hapus siswa dari bottom sheet --}}
    <form id="sheetDeleteForm" action="" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>



{{-- SCRIPT PENGENDALI MODAL --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Fungsi buka modal
    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';
            var firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(function() { firstInput.focus(); }, 100);
            }
        }
    }

    // Fungsi tutup modal
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('is-active');
            // Jika tidak ada modal lain yang aktif, kembalikan scroll body
            if (!document.querySelector('.app-modal.is-active')) {
                document.body.style.overflow = '';
            }
        }
    }

    // Event listener untuk tombol buka modal
    document.querySelectorAll('[data-modal-target]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    // Event listener untuk tombol switch modal (misal dari view langsung ke edit)
    document.querySelectorAll('[data-modal-switch-to]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var currentModal = this.closest('.app-modal');
            var nextModalId = this.getAttribute('data-modal-switch-to');
            closeModal(currentModal);
            setTimeout(function() {
                openModal(nextModalId);
            }, 150);
        });
    });

    // Event listener untuk tombol tutup modal
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal(this.closest('.app-modal'));
        });
    });

    // Klik di area backdrop luar modal dialog untuk menutup
    document.querySelectorAll('.app-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });

    // Tekan tombol Escape untuk menutup modal yang sedang aktif
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var activeModal = document.querySelector('.app-modal.is-active');
            if (activeModal) {
                closeModal(activeModal);
            }
        }
    });

    // Handler Pairing Kartu RFID Santri (Reusable)
    function triggerRfidPairing(siswaId, nama, currentUid) {
        currentUid = currentUid || '';
        if (typeof Swal === 'undefined') {
            var manualUid = prompt('Masukkan UID Kartu RFID untuk santri ' + nama + ':', currentUid);
            if (manualUid !== null) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '/siswa/' + siswaId + '/rfid';
                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);
                var inputUid = document.createElement('input');
                inputUid.type = 'hidden';
                inputUid.name = 'rfid_uid';
                inputUid.value = manualUid.trim().toUpperCase();
                form.appendChild(inputUid);
                document.body.appendChild(form);
                form.submit();
            }
            return;
        }

        Swal.fire({
            title: 'Pairing Kartu RFID',
            html: `
                <div style="text-align:left; font-size:13px; line-height:1.6; color:var(--text-main);">
                    <p style="margin-bottom:10px;">Santri: <strong>${nama}</strong></p>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">UID Kartu RFID (Hexadecimal):</label>
                    <div style="display:flex; gap:8px;">
                        <input id="swal_rfid_uid" class="swal2-input" style="margin:0; flex:1; text-transform:uppercase; font-family:monospace; font-size:14px; font-weight:600;" placeholder="Contoh: C1F8EFA7" value="${currentUid}">
                        <button type="button" id="btn_detect_rfid" class="btn btn-secondary" style="font-size:12px; padding:0 12px; white-space:nowrap; border-radius:8px;" title="Ambil tap kartu terakhir di alat">
                            📡 Cek Tap Terakhir
                        </button>
                    </div>
                    <div id="detect_rfid_status" style="font-size:11.5px; color:#64748b; margin-top:8px;">
                        💡 <em>Tip: Tap kartu baru di sensor RFID alat ESP32, lalu klik tombol "Cek Tap Terakhir".</em>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Simpan Kartu',
            confirmButtonColor: '#2563eb',
            cancelButtonText: 'Batal',
            focusConfirm: false,
            didOpen: () => {
                const detectBtn = document.getElementById('btn_detect_rfid');
                const uidInput = document.getElementById('swal_rfid_uid');
                const statusText = document.getElementById('detect_rfid_status');
                
                detectBtn.addEventListener('click', function() {
                    statusText.innerHTML = '<span style="color:#2563eb;">⏳ Memeriksa data tap kartu terakhir...</span>';
                    fetch('{{ route("siswa.lastRfidTap") }}')
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.uid) {
                                uidInput.value = data.uid.toUpperCase();
                                statusText.innerHTML = `<span style="color:#059669; font-weight:600;">✓ Terdeteksi UID: ${data.uid} (${data.timestamp || 'Baru saja'})</span>`;
                            } else {
                                statusText.innerHTML = '<span style="color:#dc2626;">⚠️ Belum ada kartu baru yang di-tap di alat. Silakan tap kartu di sensor RFID terlebih dahulu.</span>';
                            }
                        })
                        .catch(err => {
                            statusText.innerHTML = '<span style="color:#dc2626;">Gagal menghubungi server: ' + err.message + '</span>';
                        });
                });
            },
            preConfirm: () => {
                const uid = document.getElementById('swal_rfid_uid').value.trim().toUpperCase();
                return fetch(`/siswa/${siswaId}/rfid`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ rfid_uid: uid })
                })
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(errData => {
                            throw new Error(errData.message || (errData.errors && errData.errors.rfid_uid ? errData.errors.rfid_uid[0] : 'Gagal menyimpan pairing kartu'));
                        });
                    }
                    return res.json();
                })
                .catch(error => {
                    Swal.showValidationMessage(error.message);
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Dipasangkan!',
                    text: result.value.message || 'Kartu RFID berhasil disimpan.',
                    timer: 1600,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        });
    }
    window.triggerRfidPairing = triggerRfidPairing;

    document.querySelectorAll('.btn-rfid-pair').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var siswaId = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            var currentUid = this.getAttribute('data-uid') || '';
            triggerRfidPairing(siswaId, nama, currentUid);
        });
    });

    // ========================================================
    // LOGIKA ACTION BOTTOM SHEET (MOBILE)
    // ========================================================
    var sheet = document.getElementById('mobileActionSheet');
    var backdrop = document.getElementById('mobileActionSheetBackdrop');
    var closeBtn = document.getElementById('sheetBtnClose');
    var currentCardData = null;

    function openActionSheet(card) {
        if (!sheet || !backdrop) return;
        currentCardData = {
            id: card.getAttribute('data-id'),
            nama: card.getAttribute('data-nama'),
            kamar: card.getAttribute('data-kamar'),
            jenjang: card.getAttribute('data-jenjang'),
            status: card.getAttribute('data-status'),
            statusText: card.getAttribute('data-status-text'),
            rfid: card.getAttribute('data-rfid'),
            viewUrl: card.getAttribute('data-view-url'),
            editUrl: card.getAttribute('data-edit-url'),
            cetakUrl: card.getAttribute('data-cetak-url'),
            deleteUrl: card.getAttribute('data-delete-url'),
            canEdit: card.getAttribute('data-can-edit') === '1',
            canDelete: card.getAttribute('data-can-delete') === '1'
        };

        // Populate Header Info
        var elNama = document.getElementById('sheetSiswaNama');
        var elAvatar = document.getElementById('sheetSiswaAvatar');
        var elKamar = document.getElementById('sheetSiswaKamar');
        var elJenjang = document.getElementById('sheetSiswaJenjang');
        var elStatus = document.getElementById('sheetSiswaStatus');
        var elRfidDesc = document.getElementById('sheetRfidDesc');

        if (elNama) elNama.textContent = currentCardData.nama || 'Santri';
        if (elAvatar) {
            var words = (currentCardData.nama || 'S').trim().split(/\s+/);
            var initials = words.slice(0, 2).map(function(w){ return w.charAt(0); }).join('').toUpperCase();
            elAvatar.textContent = initials || 'ST';
        }
        if (elKamar) elKamar.textContent = currentCardData.kamar ? ('Kamar: ' + currentCardData.kamar) : 'Kamar: -';
        if (elJenjang) elJenjang.textContent = currentCardData.jenjang || 'Reguler';
        if (elStatus) {
            elStatus.textContent = currentCardData.statusText || 'Aktif';
            elStatus.className = 'mobile-badge-pill status-' + (currentCardData.status || 'aktif');
        }
        if (elRfidDesc) {
            elRfidDesc.textContent = currentCardData.rfid ? ('UID Terpasang: ' + currentCardData.rfid) : 'Belum dipairing dengan kartu RFID';
        }

        // Action URLs & Visibility
        var btnView = document.getElementById('sheetActionView');
        var btnEdit = document.getElementById('sheetActionEdit');
        var btnPrint = document.getElementById('sheetActionPrint');
        var btnRfid = document.getElementById('sheetActionRfid');
        var btnDelete = document.getElementById('sheetActionDelete');

        if (btnView) btnView.href = currentCardData.viewUrl || '#';
        if (btnPrint) btnPrint.href = currentCardData.cetakUrl || '#';
        if (btnEdit) {
            btnEdit.href = currentCardData.editUrl || '#';
            btnEdit.style.display = currentCardData.canEdit ? 'flex' : 'none';
        }
        if (btnRfid) {
            btnRfid.style.display = currentCardData.canEdit ? 'flex' : 'none';
        }
        if (btnDelete) {
            btnDelete.style.display = currentCardData.canDelete ? 'flex' : 'none';
        }

        // Animate in
        backdrop.style.display = 'block';
        sheet.style.display = 'flex';
        void sheet.offsetHeight; // trigger reflow
        backdrop.classList.add('is-active');
        sheet.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeActionSheet() {
        if (!sheet || !backdrop) return;
        backdrop.classList.remove('is-active');
        sheet.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(function () {
            backdrop.style.display = 'none';
            sheet.style.display = 'none';
            currentCardData = null;
        }, 280);
    }

    // Klik pada kartu santri mobile untuk membuka bottom sheet
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.mobile-siswa-card');
        if (!card) return;
        // Jangan buka bottom sheet jika klik checkbox, link, atau form
        if (e.target.closest('.fi-ta-checkbox, input[type="checkbox"], a, button, form')) return;
        e.preventDefault();
        openActionSheet(card);
    });

    if (closeBtn) closeBtn.addEventListener('click', closeActionSheet);
    if (backdrop) backdrop.addEventListener('click', closeActionSheet);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sheet && sheet.classList.contains('is-open')) {
            closeActionSheet();
        }
    });

    // Action RFID dari Bottom Sheet
    var sheetRfidBtn = document.getElementById('sheetActionRfid');
    if (sheetRfidBtn) {
        sheetRfidBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!currentCardData) return;
            var sid = currentCardData.id;
            var snama = currentCardData.nama;
            var suid = currentCardData.rfid;
            closeActionSheet();
            setTimeout(function () {
                triggerRfidPairing(sid, snama, suid);
            }, 300);
        });
    }

    // Action Hapus dari Bottom Sheet
    var sheetDeleteBtn = document.getElementById('sheetActionDelete');
    if (sheetDeleteBtn) {
        sheetDeleteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!currentCardData || !currentCardData.deleteUrl) return;
            var delUrl = currentCardData.deleteUrl;
            var delNama = currentCardData.nama;
            var delKamar = currentCardData.kamar;
            var delStatus = currentCardData.statusText;
            var delForm = document.getElementById('sheetDeleteForm');
            if (!delForm) return;
            delForm.action = delUrl;

            closeActionSheet();

            setTimeout(function() {
                if (typeof Swal === 'undefined') {
                    if (confirm('Konfirmasi Hapus Siswa?\nApakah Anda yakin ingin menghapus data santri ' + delNama + '? Tindakan ini tidak dapat dibatalkan.')) {
                        delForm.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: 'Konfirmasi Hapus Siswa?',
                    html: `Apakah Anda yakin ingin menghapus data santri <strong>${delNama}</strong>?<br><div style='padding:12px 14px; background:var(--nav-hover-bg, #f1f5f9); border-radius:10px; border:1px solid var(--border-light, #e2e8f0); margin-top:12px; font-size:13px; text-align:left; line-height:1.6;'><div>Kamar: <strong>${delKamar}</strong></div><div>Status: <strong>${delStatus}</strong></div></div><div style='margin-top:10px; font-size:12.5px; color:#DC2626; font-weight:600;'>⚠️ Data yang sudah dihapus tidak dapat dipulihkan kembali.</div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus Data',
                    confirmButtonColor: '#DC2626',
                    cancelButtonText: 'Batal',
                    cancelButtonColor: '#64748b'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        delForm.submit();
                    }
                });
            }, 250);
        });
    }
});
</script>
@endsection
