@extends('layouts.app')

@section('title', 'Rekap Absensi Guru')

@section('header-actions')
@php
    $headerExportParams = array_merge(request()->all(), [
        'tanggal' => $tanggal ?? \Carbon\Carbon::today()->toDateString(),
    ]);
@endphp
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('rekap-absensi.export', $headerExportParams) }}" class="btn btn-secondary btn-header-action" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel</span>
    </a>

    <a href="{{ route('rekap-absensi.cetak', $headerExportParams) }}" target="_blank" class="btn btn-primary btn-header-action" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak Laporan</span>
    </a>
</div>
@endsection

@push('styles')
<style>
    .guru-page-stack {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    /* 3 Kartu Header Presensi (Jam & Tanggal + Jam Masuk + Jam Pulang) */
    .guru-bento-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .guru-bento-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
    }

    .dark-mode .guru-bento-card {
        background: var(--card-bg, #1e293b);
        border-color: var(--border-light, #334155);
        box-shadow: none;
    }

    .guru-bento-card.is-clock {
        grid-column: 1 / -1;
        padding: 20px 18px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 4px;
    }

    .guru-clock-time {
        font-size: 30px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.03em;
        line-height: 1.15;
        font-variant-numeric: tabular-nums;
    }

    .dark-mode .guru-clock-time {
        color: #f8fafc;
    }

    .guru-clock-date {
        font-size: 13.5px;
        font-weight: 600;
        color: #64748b;
        line-height: 1.35;
    }

    .dark-mode .guru-clock-date {
        color: #94a3b8;
    }

    .guru-bento-card.is-time {
        padding: 15px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .guru-time-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .guru-time-icon svg {
        width: 19px;
        height: 19px;
    }

    .guru-time-icon.is-masuk {
        background: #ecfdf5;
        color: #059669;
    }

    .dark-mode .guru-time-icon.is-masuk {
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
    }

    .guru-time-icon.is-pulang {
        background: #eff6ff;
        color: #2563eb;
    }

    .dark-mode .guru-time-icon.is-pulang {
        background: rgba(37, 99, 235, 0.18);
        color: #60a5fa;
    }

    .guru-time-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .guru-time-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        line-height: 1.25;
        white-space: nowrap;
    }

    .dark-mode .guru-time-label {
        color: #94a3b8;
    }

    .guru-time-value {
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        line-height: 1.2;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .dark-mode .guru-time-value {
        color: #f8fafc;
    }

    /* Elemen Tabel Desktop */
    .guru-cell-nama {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .guru-avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1e40af;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .dark-mode .guru-avatar-circle {
        background: rgba(59, 130, 246, 0.22);
        color: #93c5fd;
    }

    .guru-name-text {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-main, #0f172a);
    }

    .guru-jam-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 7px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12px;
        font-weight: 600;
        color: #0f172a;
    }

    .dark-mode .guru-jam-pill {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    .guru-jam-pill svg {
        color: #64748b;
    }

    .guru-tidak-pulang-pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 11.5px;
        font-weight: 600;
    }

    .dark-mode .guru-tidak-pulang-pill {
        background: #334155;
        color: #94a3b8;
    }

    .guru-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-tepat {
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        color: #15803d;
    }
    .dark-mode .badge-tepat {
        background: rgba(34, 197, 94, 0.18);
        border-color: rgba(34, 197, 94, 0.35);
        color: #4ade80;
    }

    .badge-terlambat {
        background: #fef3c7;
        border: 1px solid #fde68a;
        color: #b45309;
    }
    .dark-mode .badge-terlambat {
        background: rgba(245, 158, 11, 0.18);
        border-color: rgba(245, 158, 11, 0.35);
        color: #fbbf24;
    }

    .badge-izin {
        background: #dbeafe;
        border: 1px solid #bfdbfe;
        color: #1d4ed8;
    }
    .dark-mode .badge-izin {
        background: rgba(59, 130, 246, 0.18);
        border-color: rgba(59, 130, 246, 0.35);
        color: #60a5fa;
    }

    .badge-sakit {
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
        color: #7c3aed;
    }
    .dark-mode .badge-sakit {
        background: rgba(124, 58, 237, 0.2);
        border-color: rgba(124, 58, 237, 0.35);
        color: #c4b5fd;
    }

    .badge-alpa {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }
    .dark-mode .badge-alpa {
        background: rgba(239, 68, 68, 0.18);
        border-color: rgba(239, 68, 68, 0.35);
        color: #f87171;
    }

    /* Modal Koreksi Presensi */
    .guru-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 10050;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .guru-modal-backdrop.is-open {
        display: flex !important;
    }

    .guru-modal-dialog {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        width: 100%;
        max-width: 440px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.28);
        overflow: hidden;
    }
    .dark-mode .guru-modal-dialog {
        background: #1e293b;
        border-color: #334155;
    }

    .guru-modal-head {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .dark-mode .guru-modal-head {
        border-bottom-color: #334155;
    }

    .guru-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .dark-mode .guru-modal-title {
        color: #f8fafc;
    }

    .guru-modal-sub {
        font-size: 12.5px;
        color: #64748b;
        display: block;
        margin-top: 2px;
    }
    .dark-mode .guru-modal-sub {
        color: #94a3b8;
    }

    .guru-modal-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 6px;
    }
    .dark-mode .guru-modal-label {
        color: #e2e8f0;
    }

    .guru-modal-control {
        width: 100%;
        padding: 9px 12px;
        font-size: 13.5px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a;
        box-sizing: border-box;
    }
    .dark-mode .guru-modal-control {
        background: #0f172a;
        border-color: #334155;
        color: #f8fafc;
    }

    /* ========================================================
       TAMPILAN KHUSUS MOBILE SESUAI KARTU SISWA (<= 768px)
       ======================================================== */
    .mobile-siswa-list {
        display: none;
    }

    @media (min-width: 769px) {
        .mobile-siswa-list,
        body .mobile-siswa-list {
            display: none !important;
        }
    }

    .mobile-sheet-backdrop,
    .mobile-bottom-sheet {
        display: none;
    }

    @media (max-width: 768px) {
        .guru-bento-grid {
            gap: 10px;
        }
        .guru-bento-card {
            border-radius: 14px;
        }
        .guru-bento-card.is-clock {
            padding: 18px 14px;
            gap: 3px;
        }
        .guru-clock-time {
            font-size: 26px;
        }
        .guru-clock-date {
            font-size: 12.5px;
        }
        .guru-bento-card.is-time {
            padding: 13px 14px;
            gap: 10px;
        }
        .guru-time-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
        }
        .guru-time-icon svg {
            width: 17px;
            height: 17px;
        }
        .guru-time-label {
            font-size: 11.5px;
        }
        .guru-time-value {
            font-size: 17px;
        }

        /* Sembunyikan tabel baris desktop di mobile secara total */
        .table-wrapper-siswa-desktop,
        div.table-wrapper-siswa-desktop,
        body .table-wrapper-siswa-desktop,
        body .table-card .table-wrapper-siswa-desktop,
        body .table-card.has-mobile-list > .table-wrapper,
        body .table-card.has-mobile-list > .fi-ta-table-wrap,
        body .table-card.fi-ta-enhanced.has-mobile-list > .table-wrapper,
        body .table-card.fi-ta-enhanced.has-mobile-list > .fi-ta-table-wrap {
            display: none !important;
        }

        /* Hilangkan border kaku container luar agar toolbar & card mengambang rapi seperti Siswa */
        .table-card {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        body .table-card.has-mobile-list > .mobile-siswa-list,
        body .table-card.fi-ta-enhanced > .mobile-siswa-list,
        body .table-card > .mobile-siswa-list,
        body .mobile-siswa-list,
        .mobile-siswa-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            padding: 8px 0 14px 0 !important;
            width: 100% !important;
        }

        .mobile-siswa-card .fi-ta-checkbox {
            display: none !important;
        }

        /* Bottom Sheet Aksi Guru (Persis Bottom Sheet Siswa) */
        .mobile-sheet-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }
        .mobile-sheet-backdrop.is-active {
            opacity: 1 !important;
            pointer-events: auto !important;
            display: block !important;
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
            max-height: 86vh;
            overflow: hidden;
        }
        .dark-mode .mobile-bottom-sheet {
            background: #1e293b;
            border-top: 1px solid #334155;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5);
        }
        .mobile-bottom-sheet.is-open {
            transform: translateY(0) !important;
            display: flex !important;
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
            font-size: 15px;
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

        /* Kotak Info Detail Jam Masuk / Pulang di dalam Bottom Sheet */
        .sheet-detail-box {
            margin: 12px 18px 2px;
            padding: 11px 14px;
            border-radius: 13px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 12px;
        }
        .dark-mode .sheet-detail-box {
            background: #0f172a;
            border-color: #334155;
        }
        .sheet-detail-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .sheet-detail-item.is-full {
            grid-column: 1 / -1;
            border-top: 1px dashed #e2e8f0;
            padding-top: 8px;
        }
        .dark-mode .sheet-detail-item.is-full {
            border-top-color: #334155;
        }
        .sheet-detail-lbl {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .sheet-detail-val {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .dark-mode .sheet-detail-val {
            color: #f1f5f9;
        }

        .sheet-actions-list {
            padding: 8px 14px;
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
            font-family: inherit;
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
        .sheet-action-icon.icon-view {
            background: #eff6ff;
            color: #2563eb;
        }
        .dark-mode .sheet-action-icon.icon-view {
            background: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
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
            background: #234e38;
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
</style>
@endpush

@section('content')
@php
    $canKoreksi = !auth()->check() || auth()->user()->canDo('rekap_absensi', 'edit');
    $todayDateStr = $todayStr ?? \Carbon\Carbon::today()->toDateString();
    $displayDate = !empty($tanggal)
        ? \Carbon\Carbon::parse($tanggal)->locale('id')
        : \Carbon\Carbon::today()->locale('id');

    $jamMasukSaya = ($presensiSaya ?? null) && $presensiSaya->jam_masuk
        ? substr($presensiSaya->jam_masuk, 0, 5)
        : '--:--';
    $jamPulangSaya = ($presensiSaya ?? null) && $presensiSaya->jam_pulang
        ? substr($presensiSaya->jam_pulang, 0, 5)
        : '--:--';

    $exportParams = array_merge(request()->all(), [
        'tanggal' => $tanggal ?? $todayDateStr,
    ]);

    $hasActiveFilter = !empty($search)
        || !empty($status)
        || (!empty($tanggal) && $tanggal !== $todayDateStr)
        || ($tanggal === '' && request()->exists('tanggal'))
        || (request()->exists('lembaga_id') && (string) $lembagaId !== (string) ($defaultLembagaId ?? ''));
@endphp

<div class="guru-page-stack">

    {{-- Alert Banner --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="margin-bottom:0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    {{-- 1. HEADER 3 KARTU (Kartu 1: Jam & Tanggal 2 Tumpuk | Kartu 2: Jam Masuk | Kartu 3: Jam Pulang) --}}
    <div class="guru-bento-grid">
        <div class="guru-bento-card is-clock">
            <div class="guru-clock-time" id="guruLiveClock">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</div>
            <div class="guru-clock-date">{{ $displayDate->translatedFormat('l, d F Y') }}</div>
        </div>

        <div class="guru-bento-card is-time">
            <div class="guru-time-icon is-masuk" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </div>
            <div class="guru-time-info">
                <span class="guru-time-label">Jam Masuk</span>
                <span class="guru-time-value">{{ $jamMasukSaya }}</span>
            </div>
        </div>

        <div class="guru-bento-card is-time">
            <div class="guru-time-icon is-pulang" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <div class="guru-time-info">
                <span class="guru-time-label">Jam Pulang</span>
                <span class="guru-time-value">{{ $jamPulangSaya }}</span>
            </div>
        </div>
    </div>

    <div class="sr-only" aria-hidden="true" style="display:none;">
        HEADER PRESENSI HARIAN {{ $ringkasan['hadir'] }} Tepat Waktu Hadir Tepat {{ $ringkasan['total'] }} Total Guru Menampilkan {{ $ringkasan['total'] }} data
    </div>

    {{-- 2. FILTER CARD (Otomatis di-dock ke Toolbar Pencarian & Bottom Sheet Filter) --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('rekap-absensi.guru') }}" method="GET" class="filter-form">
            <div class="filter-group">
                {{-- Filter Lembaga --}}
                @if($lembagas->count() > 1)
                <select name="lembaga_id"
                        class="form-select filter-select"
                        aria-label="Lembaga"
                        data-default-value="{{ $defaultLembagaId ?? '' }}"
                        onchange="this.form.submit()">
                    <option value="all" {{ empty($lembagaId) ? 'selected' : '' }}>Semua Lembaga</option>
                    @foreach($lembagas as $lem)
                        <option value="{{ $lem->id }}" {{ (string) $lembagaId === (string) $lem->id ? 'selected' : '' }}>
                            {{ $lem->singkatan ?? $lem->nama_singkat ?? $lem->nama }}
                        </option>
                    @endforeach
                </select>
                @endif

                {{-- Filter Tanggal (Otomatis Tanggal Hari Itu) --}}
                <input type="date"
                       name="tanggal"
                       value="{{ $tanggal }}"
                       class="form-control filter-input"
                       aria-label="Tanggal"
                       data-default-value="{{ $todayDateStr }}"
                       onchange="this.form.submit()">

                {{-- Filter Status Kehadiran --}}
                <select name="status"
                        class="form-select filter-select"
                        aria-label="Status Kehadiran"
                        data-default-value=""
                        onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="hadir" {{ $status === 'hadir' ? 'selected' : '' }}>Tepat Waktu</option>
                    <option value="terlambat" {{ $status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                    <option value="izin" {{ $status === 'izin' ? 'selected' : '' }}>Izin</option>
                    <option value="sakit" {{ $status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                    <option value="alpa" {{ $status === 'alpa' ? 'selected' : '' }}>Alpa</option>
                </select>

                <a href="{{ route('rekap-absensi.guru') }}"
                   class="btn btn-sm btn-secondary filter-btn-reset"
                   title="Reset Filter"
                   style="{{ $hasActiveFilter ? '' : 'display:none;' }}">
                    <span>Reset</span>
                </a>
            </div>

            <div class="filter-search-wrap">
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       class="form-control filter-search-input"
                       placeholder="Cari nama guru / NIP / jabatan...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- 3. TABEL DESKTOP + KARTU MOBILE (Identik dengan Tabel Siswa) --}}
    <div class="table-card has-mobile-list">
        <div class="table-wrapper table-wrapper-siswa-desktop">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nama Guru</th>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status Kehadiran</th>
                        <th data-col-hidden="true" style="display:none;">Lembaga</th>
                        <th data-col-hidden="true" style="display:none;">NIP</th>
                        <th data-col-hidden="true" style="display:none;">Jabatan</th>
                        <th data-col-hidden="true" style="display:none;">Mata Pelajaran</th>
                        <th data-col-hidden="true" style="display:none;">Metode Absen</th>
                        <th data-col-hidden="true" style="display:none;">Jarak Lokasi</th>
                        <th data-col-hidden="true" style="display:none;">Kartu RFID</th>
                        <th data-col-hidden="true" style="display:none;">Keterangan</th>
                        <th data-col-hidden="true" class="col-aksi-head" style="text-align:center; width:100px; display:none;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presensis as $row)
                    @php
                        $namaGuru = $row->user?->name ?? '-';
                        $tanggalObj = $row->tanggal ? ($row->tanggal instanceof \Carbon\Carbon ? $row->tanggal : \Carbon\Carbon::parse($row->tanggal)) : null;
                        $tanggalDesktop = $tanggalObj ? $tanggalObj->translatedFormat('l, d M Y') : '-';
                        $tanggalModal = $tanggalObj ? $tanggalObj->format('d/m/Y') : '-';
                        $jamMasukFull = $row->jam_masuk ? ((strlen($row->jam_masuk) <= 5 ? $row->jam_masuk . ':00' : substr($row->jam_masuk, 0, 8)) . ' WIB') : null;
                        $jamPulangFull = $row->jam_pulang ? ((strlen($row->jam_pulang) <= 5 ? $row->jam_pulang . ':00' : substr($row->jam_pulang, 0, 8)) . ' WIB') : null;
                        $lembagaRowNama = $row->lembaga?->singkatan ?? $row->lembaga?->nama_singkat ?? $row->lembaga?->nama ?? '-';
                        $lembagaKode = strtolower($row->lembaga?->kode ?? 'pondok');
                        $lembagaBadgeClass = in_array($lembagaKode, ['smp', 'sma', 'yayasan']) ? $lembagaKode : 'pondok';

                        $metodeLabel = match($row->metode_masuk) {
                            'rfid_tap' => 'Tap RFID',
                            'gps', 'web' => 'GPS / Web',
                            default => $row->metode_masuk ? ucfirst(str_replace('_', ' ', $row->metode_masuk)) : 'Manual / Sistem',
                        };
                        $jarakLabel = $row->jarak_masuk_meter !== null ? ($row->jarak_masuk_meter . ' m') : '-';
                        $rfidUid = $row->user?->rfid_uid ?? '';

                        $words = preg_split('/\s+/', trim(preg_replace('/[^a-zA-Z\s]/', '', $namaGuru))) ?: [];
                        $initials = '';
                        foreach ($words as $w) {
                            if ($w !== '' && strlen($initials) < 2) {
                                $initials .= strtoupper(substr($w, 0, 1));
                            }
                        }
                        if ($initials === '') {
                            $initials = 'GR';
                        }
                    @endphp
                    <tr data-id="{{ $row->id }}" data-nama="{{ $namaGuru }}">
                        {{-- 1. Nama Guru --}}
                        <td class="cell-nama">
                            <div class="guru-cell-nama">
                                <div class="guru-avatar-circle">{{ $initials }}</div>
                                <span class="guru-name-text">{{ $namaGuru }}</span>
                            </div>
                        </td>

                        {{-- 2. Tanggal --}}
                        <td>{{ $tanggalDesktop }}</td>

                        {{-- 3. Jam Masuk --}}
                        <td>
                            @if($jamMasukFull)
                            <div class="guru-jam-pill">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ $jamMasukFull }}</span>
                            </div>
                            @else
                            <span style="color:#94a3b8; font-size:13px;">-</span>
                            @endif
                        </td>

                        {{-- 4. Jam Pulang --}}
                        <td>
                            @if($jamPulangFull)
                            <div class="guru-jam-pill">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ $jamPulangFull }}</span>
                            </div>
                            @else
                            <span class="guru-tidak-pulang-pill">Tidak Absen Pulang</span>
                            @endif
                        </td>

                        {{-- 5. Status Kehadiran --}}
                        <td>
                            @if($row->status === 'hadir')
                            <span class="guru-status-badge badge-tepat">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Tepat Waktu</span>
                            </span>
                            @elseif($row->status === 'terlambat')
                            <span class="guru-status-badge badge-terlambat">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>Terlambat</span>
                            </span>
                            @elseif($row->status === 'izin')
                            <span class="guru-status-badge badge-izin">Izin</span>
                            @elseif($row->status === 'sakit')
                            <span class="guru-status-badge badge-sakit">Sakit</span>
                            @else
                            <span class="guru-status-badge badge-alpa">Alpa</span>
                            @endif
                        </td>

                        {{-- 6. Lembaga (Custom Column) --}}
                        <td style="display:none;">
                            <span class="badge-lembaga badge-lembaga-{{ $lembagaBadgeClass }}">{{ $lembagaRowNama }}</span>
                        </td>

                        {{-- 7. NIP (Custom Column) --}}
                        <td style="display:none; font-family:monospace;">{{ $row->user?->nip ?: '-' }}</td>

                        {{-- 8. Jabatan (Custom Column) --}}
                        <td style="display:none;">{{ $row->user?->jabatan ?: '-' }}</td>

                        {{-- 9. Mata Pelajaran (Custom Column) --}}
                        <td style="display:none;">{{ $row->user?->mata_pelajaran ?: '-' }}</td>

                        {{-- 10. Metode Absen (Custom Column) --}}
                        <td style="display:none;">{{ $metodeLabel }}</td>

                        {{-- 11. Jarak Lokasi (Custom Column) --}}
                        <td style="display:none;">{{ $jarakLabel }}</td>

                        {{-- 12. Kartu RFID (Custom Column) --}}
                        <td style="display:none;">
                            @if($rfidUid !== '')
                                <span style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); font-family: monospace; font-weight: 600; padding: 2px 7px; border-radius: 6px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;">
                                    {{ $rfidUid }}
                                </span>
                            @else
                                <span style="color: var(--text-muted); font-size: 11px; font-style: italic;">Belum dipairing</span>
                            @endif
                        </td>

                        {{-- 13. Keterangan (Custom Column) --}}
                        <td style="display:none;">{{ $row->keterangan ?: '-' }}</td>

                        {{-- 14. Aksi (Custom Column & Synced ke Toolbar [ ⋮ Aksi ]) --}}
                        <td class="col-aksi-cell" style="text-align:center; display:none;">
                            @if($canKoreksi)
                            <div class="action-btn-group">
                                <button type="button"
                                        class="action-btn action-btn-edit btn-koreksi"
                                        data-id="{{ $row->id }}"
                                        data-nama="{{ $namaGuru }}"
                                        data-tanggal="{{ $tanggalModal }}"
                                        data-status="{{ $row->status }}"
                                        data-masuk="{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '' }}"
                                        data-pulang="{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '' }}"
                                        data-keterangan="{{ $row->keterangan ?? '' }}"
                                        title="Koreksi Presensi Guru"
                                        aria-label="Koreksi Presensi {{ $namaGuru }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </div>
                            @else
                            <span style="color:var(--text-muted); font-size:12px;">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="cell-empty" style="padding:44px 20px;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--text-muted);">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>Tidak ada data presensi guru untuk filter yang dipilih.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tampilan Mobile Card List (Persis Kartu di Tabel Siswa) --}}
        <div class="mobile-siswa-list">
            @forelse($presensis as $row)
            @php
                $namaGuru = $row->user?->name ?? '-';
                $tanggalObj = $row->tanggal ? ($row->tanggal instanceof \Carbon\Carbon ? $row->tanggal : \Carbon\Carbon::parse($row->tanggal)) : null;
                $tanggalShort = $tanggalObj ? $tanggalObj->translatedFormat('d M Y') : '-';
                $tanggalModal = $tanggalObj ? $tanggalObj->format('d/m/Y') : '-';
                $jamMasukShort = $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '--:--';
                $jamPulangShort = $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '--:--';
                $jamMasukDetail = $row->jam_masuk ? ((strlen($row->jam_masuk) <= 5 ? $row->jam_masuk . ':00' : substr($row->jam_masuk, 0, 8)) . ' WIB') : '-';
                $jamPulangDetail = $row->jam_pulang ? ((strlen($row->jam_pulang) <= 5 ? $row->jam_pulang . ':00' : substr($row->jam_pulang, 0, 8)) . ' WIB') : '--:--';
                $lembagaRowNama = $row->lembaga?->singkatan ?? $row->lembaga?->nama_singkat ?? $row->lembaga?->nama ?? '-';

                $statusKey = strtolower($row->status ?? 'hadir');
                $statusDisplay = match($statusKey) {
                    'hadir'     => 'Tepat Waktu',
                    'terlambat' => 'Terlambat',
                    'izin'      => 'Izin',
                    'sakit'     => 'Sakit',
                    'alpa'      => 'Alpa',
                    default     => ucfirst($statusKey),
                };

                $metodeLabel = match($row->metode_masuk) {
                    'rfid_tap' => 'Tap RFID',
                    'gps', 'web' => 'GPS / Web',
                    default => $row->metode_masuk ? ucfirst(str_replace('_', ' ', $row->metode_masuk)) : 'Manual / Sistem',
                };
                $jarakLabel = $row->jarak_masuk_meter !== null ? ($row->jarak_masuk_meter . ' m') : '-';
            @endphp
            <div class="mobile-siswa-card"
                 data-id="{{ $row->id }}"
                 data-nama="{{ $namaGuru }}"
                 data-lembaga="{{ $lembagaRowNama }}"
                 data-tanggal="{{ $tanggalShort }}"
                 data-tanggal-modal="{{ $tanggalModal }}"
                 data-status="{{ $statusKey }}"
                 data-status-text="{{ $statusDisplay }}"
                 data-masuk="{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '' }}"
                 data-pulang="{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '' }}"
                 data-masuk-detail="{{ $jamMasukDetail }}"
                 data-pulang-detail="{{ $jamPulangDetail }}"
                 data-metode="{{ $metodeLabel }}"
                 data-keterangan="{{ $row->keterangan ?? '' }}"
                 data-can-koreksi="{{ $canKoreksi ? '1' : '0' }}">

                {{-- Sisi Kiri: Nama Guru & Subtitle Dinamis (Tersinkronisasi dengan Kolom Kustom [ ||| ]) --}}
                <div class="mobile-siswa-info">
                    <div class="mobile-siswa-nama-wrap">
                        <span class="mobile-siswa-nama">{{ $namaGuru }}</span>
                    </div>

                    <div class="mobile-siswa-subtitles">
                        <span class="mobile-sub-item" data-col-idx="2" data-mobile-default-hidden="true" style="display:none;">
                            <span class="sub-label">Tgl:</span> {{ $tanggalShort }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="3">
                            <span class="sub-label">Masuk:</span> {{ $jamMasukShort }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="4">
                            <span class="sub-label">Pulang:</span> {{ $jamPulangShort }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="6" style="display:none;">
                            <span class="sub-label">Lembaga:</span> {{ $lembagaRowNama }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="7" style="display:none;">
                            <span class="sub-label">NIP:</span> {{ $row->user?->nip ?: '-' }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="8" style="display:none;">
                            <span class="sub-label">Jabatan:</span> {{ $row->user?->jabatan ?: '-' }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="9" style="display:none;">
                            <span class="sub-label">Mapel:</span> {{ $row->user?->mata_pelajaran ?: '-' }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="10" style="display:none;">
                            <span class="sub-label">Metode:</span> {{ $metodeLabel }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="11" style="display:none;">
                            <span class="sub-label">Jarak:</span> {{ $jarakLabel }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="12" style="display:none;">
                            <span class="sub-label">RFID:</span> {{ $row->user?->rfid_uid ?: '-' }}
                        </span>
                        <span class="mobile-sub-item" data-col-idx="13" style="display:none;">
                            <span class="sub-label">Ket:</span> {{ $row->keterangan ?: '-' }}
                        </span>
                    </div>
                </div>

                {{-- Sisi Kanan: Status Pill (Tanpa tanda panah sesuai permintaan, tetap klik kartu penuh) --}}
                <div class="mobile-siswa-status-wrap">
                    <span class="mobile-badge-pill status-{{ $statusKey }}" data-col-idx="5">{{ $statusDisplay }}</span>
                </div>
            </div>
            @empty
            <div class="mobile-empty-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--text-muted);">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Tidak ada data presensi guru untuk filter yang dipilih.</span>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- Bottom Sheet Aksi Presensi Guru (Mobile Action Sheet) --}}
<div id="mobileGuruSheetBackdrop" class="mobile-sheet-backdrop" style="display:none;"></div>
<div id="mobileGuruActionSheet" class="mobile-bottom-sheet" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sheetGuruNama">
    <div class="sheet-handle-bar">
        <span class="sheet-drag-handle"></span>
    </div>

    <div class="sheet-header">
        <div class="sheet-avatar" id="sheetGuruAvatar">GR</div>
        <div class="sheet-title-wrap">
            <h3 class="sheet-siswa-nama" id="sheetGuruNama">Nama Guru</h3>
            <div class="sheet-siswa-meta">
                <span id="sheetGuruLembaga">SMP</span>
                <span>•</span>
                <span id="sheetGuruTanggal">-</span>
                <span>•</span>
                <span class="mobile-badge-pill status-hadir" id="sheetGuruStatus">Tepat Waktu</span>
            </div>
        </div>
    </div>

    {{-- Kotak Rincian Cepat Jam Masuk & Jam Pulang --}}
    <div class="sheet-detail-box">
        <div class="sheet-detail-item">
            <span class="sheet-detail-lbl">Jam Masuk</span>
            <span class="sheet-detail-val" id="sheetDetailMasuk">-</span>
        </div>
        <div class="sheet-detail-item">
            <span class="sheet-detail-lbl">Jam Pulang</span>
            <span class="sheet-detail-val" id="sheetDetailPulang">-</span>
        </div>
        <div class="sheet-detail-item is-full" id="sheetDetailKetWrap" style="display:none;">
            <span class="sheet-detail-lbl">Keterangan</span>
            <span class="sheet-detail-val" id="sheetDetailKet" style="font-weight:500; font-size:12.5px;">-</span>
        </div>
    </div>

    <div class="sheet-actions-list">
        {{-- Opsi 1: Koreksi Presensi Guru --}}
        <button type="button" id="sheetActionKoreksi" class="sheet-action-item" title="Koreksi Presensi Guru">
            <div class="sheet-action-icon icon-edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </div>
            <div class="sheet-action-text">
                <span class="sheet-action-title">Koreksi Presensi Guru</span>
            </div>
        </button>

        {{-- Opsi 2: Cetak Laporan A4 --}}
        <a href="{{ route('rekap-absensi.cetak', $exportParams) }}" target="_blank" class="sheet-action-item" title="Cetak Laporan A4">
            <div class="sheet-action-icon icon-print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
            </div>
            <div class="sheet-action-text">
                <span class="sheet-action-title">Cetak Laporan</span>
            </div>
        </a>

        {{-- Opsi 3: Ekspor Excel / CSV --}}
        <a href="{{ route('rekap-absensi.export', $exportParams) }}" class="sheet-action-item" title="Ekspor Excel">
            <div class="sheet-action-icon icon-view">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            </div>
            <div class="sheet-action-text">
                <span class="sheet-action-title">Ekspor Excel</span>
            </div>
        </a>
    </div>

    <div class="sheet-footer">
        <button type="button" class="sheet-btn-close" id="sheetGuruBtnClose">
            Tutup
        </button>
    </div>
</div>

{{-- Modal Koreksi Presensi --}}
<div id="modalKoreksi" class="guru-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalKoreksiTitle">
    <div class="guru-modal-dialog">
        <div class="guru-modal-head">
            <div>
                <h3 id="modalKoreksiTitle" class="guru-modal-title">Koreksi Presensi Guru</h3>
                <span id="modalGuruNama" class="guru-modal-sub"></span>
            </div>
            <button type="button" id="btnCloseModal" aria-label="Tutup Modal" style="background:none; border:none; cursor:pointer; color:#94a3b8; font-size:22px; line-height:1;">
                &times;
            </button>
        </div>

        <form id="formKoreksi" method="POST" action="" style="padding:20px; display:flex; flex-direction:column; gap:15px;">
            @csrf
            @method('PUT')

            <div>
                <label for="modalStatus" class="guru-modal-label">Status Kehadiran</label>
                <select name="status" id="modalStatus" class="guru-modal-control" required>
                    <option value="hadir">Tepat Waktu</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alpa">Alpa</option>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label for="modalMasuk" class="guru-modal-label">Jam Masuk</label>
                    <input type="time" name="jam_masuk" id="modalMasuk" class="guru-modal-control">
                </div>
                <div>
                    <label for="modalPulang" class="guru-modal-label">Jam Pulang</label>
                    <input type="time" name="jam_pulang" id="modalPulang" class="guru-modal-control">
                </div>
            </div>

            <div>
                <label for="modalKeterangan" class="guru-modal-label">Keterangan Koreksi</label>
                <textarea name="keterangan" id="modalKeterangan" rows="2" class="guru-modal-control" placeholder="Contoh: Tugas luar kota / lupa scan..." style="resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:4px;">
                <button type="button" id="btnCancelModal" class="btn btn-secondary" style="padding:8px 16px; font-size:13px; border-radius:9px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px; font-weight:600; border-radius:9px;">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var liveClockEl = document.getElementById('guruLiveClock');
    if (liveClockEl) {
        var updateLiveClock = function () {
            try {
                var formatted = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).format(new Date()).replace(/\./g, ':');
                liveClockEl.textContent = formatted;
            } catch (err) {
                var d = new Date();
                var pad = function (n) { return String(n).padStart(2, '0'); };
                liveClockEl.textContent = pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
            }
        };
        updateLiveClock();
        setInterval(updateLiveClock, 1000);
    }

    var modal = document.getElementById('modalKoreksi');
    var form = document.getElementById('formKoreksi');
    var modalGuruNama = document.getElementById('modalGuruNama');
    var modalStatus = document.getElementById('modalStatus');
    var modalMasuk = document.getElementById('modalMasuk');
    var modalPulang = document.getElementById('modalPulang');
    var modalKeterangan = document.getElementById('modalKeterangan');
    var btnClose = document.getElementById('btnCloseModal');
    var btnCancel = document.getElementById('btnCancelModal');

    function openKoreksiModal(data) {
        if (!modal || !form) return;
        form.action = '{{ url("rekap-absensi/koreksi") }}/' + data.id;
        modalGuruNama.textContent = (data.nama || '-') + ' • ' + (data.tanggal || '-');
        modalStatus.value = data.status || 'hadir';
        modalMasuk.value = data.masuk || '';
        modalPulang.value = data.pulang || '';
        modalKeterangan.value = data.keterangan || '';
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeKoreksiModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.btn-koreksi').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openKoreksiModal({
                id: this.getAttribute('data-id'),
                nama: this.getAttribute('data-nama'),
                tanggal: this.getAttribute('data-tanggal'),
                status: this.getAttribute('data-status'),
                masuk: this.getAttribute('data-masuk'),
                pulang: this.getAttribute('data-pulang'),
                keterangan: this.getAttribute('data-keterangan')
            });
        });
    });

    if (btnClose) btnClose.addEventListener('click', closeKoreksiModal);
    if (btnCancel) btnCancel.addEventListener('click', closeKoreksiModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeKoreksiModal();
        });
    }

    // ========================================================
    // LOGIKA ACTION BOTTOM SHEET MOBILE (PERSIS TABEL SISWA)
    // ========================================================
    var sheet = document.getElementById('mobileGuruActionSheet');
    var backdrop = document.getElementById('mobileGuruSheetBackdrop');
    var closeSheetBtn = document.getElementById('sheetGuruBtnClose');
    var actionKoreksiBtn = document.getElementById('sheetActionKoreksi');
    var currentCardData = null;

    function getInitials(name) {
        var cleaned = (name || '').replace(/[^a-zA-Z\s]/g, '').trim();
        var parts = cleaned.split(/\s+/).filter(Boolean);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return parts.length === 1 ? parts[0].substring(0, 2).toUpperCase() : 'GR';
    }

    function openGuruSheet(card) {
        if (!sheet || !backdrop) return;
        currentCardData = {
            id: card.getAttribute('data-id'),
            nama: card.getAttribute('data-nama') || '-',
            lembaga: card.getAttribute('data-lembaga') || '-',
            tanggal: card.getAttribute('data-tanggal') || '-',
            tanggalModal: card.getAttribute('data-tanggal-modal') || '-',
            status: card.getAttribute('data-status') || 'hadir',
            statusText: card.getAttribute('data-status-text') || 'Tepat Waktu',
            masuk: card.getAttribute('data-masuk') || '',
            pulang: card.getAttribute('data-pulang') || '',
            masukDetail: card.getAttribute('data-masuk-detail') || '-',
            pulangDetail: card.getAttribute('data-pulang-detail') || 'Tidak Absen Pulang',
            keterangan: card.getAttribute('data-keterangan') || '',
            canKoreksi: card.getAttribute('data-can-koreksi') === '1'
        };

        document.getElementById('sheetGuruAvatar').textContent = getInitials(currentCardData.nama);
        document.getElementById('sheetGuruNama').textContent = currentCardData.nama;
        document.getElementById('sheetGuruLembaga').textContent = currentCardData.lembaga;
        document.getElementById('sheetGuruTanggal').textContent = currentCardData.tanggal;

        var statusBadge = document.getElementById('sheetGuruStatus');
        statusBadge.textContent = currentCardData.statusText;
        statusBadge.className = 'mobile-badge-pill status-' + currentCardData.status;

        document.getElementById('sheetDetailMasuk').textContent = currentCardData.masukDetail;
        document.getElementById('sheetDetailPulang').textContent = currentCardData.pulangDetail;

        var ketWrap = document.getElementById('sheetDetailKetWrap');
        var ketEl = document.getElementById('sheetDetailKet');
        if (currentCardData.keterangan) {
            ketEl.textContent = currentCardData.keterangan;
            ketWrap.style.display = 'flex';
        } else {
            ketWrap.style.display = 'none';
        }

        if (actionKoreksiBtn) {
            actionKoreksiBtn.style.display = currentCardData.canKoreksi ? 'flex' : 'none';
        }

        backdrop.style.display = 'block';
        sheet.style.display = 'flex';
        requestAnimationFrame(function () {
            backdrop.classList.add('is-active');
            sheet.classList.add('is-open');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeGuruSheet(keepBodyLock) {
        if (!sheet || !backdrop) return;
        backdrop.classList.remove('is-active');
        sheet.classList.remove('is-open');
        if (!keepBodyLock) {
            document.body.style.overflow = '';
        }
        setTimeout(function () {
            if (!sheet.classList.contains('is-open')) {
                backdrop.style.display = 'none';
                sheet.style.display = 'none';
            }
        }, 280);
    }

    document.querySelectorAll('.mobile-siswa-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            if (e.target.closest('input, button, a, label')) return;
            openGuruSheet(card);
        });
    });

    if (closeSheetBtn) {
        closeSheetBtn.addEventListener('click', function () {
            closeGuruSheet(false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            closeGuruSheet(false);
        });
    }

    if (actionKoreksiBtn) {
        actionKoreksiBtn.addEventListener('click', function () {
            if (!currentCardData) return;
            var targetData = {
                id: currentCardData.id,
                nama: currentCardData.nama,
                tanggal: currentCardData.tanggalModal,
                status: currentCardData.status,
                masuk: currentCardData.masuk,
                pulang: currentCardData.pulang,
                keterangan: currentCardData.keterangan
            };
            closeGuruSheet(true);
            setTimeout(function () {
                openKoreksiModal(targetData);
            }, 140);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal && modal.classList.contains('is-open')) {
                closeKoreksiModal();
            } else if (sheet && sheet.classList.contains('is-open')) {
                closeGuruSheet(false);
            }
        }
    });
});
</script>
@endsection
