@extends('layouts.app')

@section('title', 'Rekap Jurnal Pembelajaran')

@section('page_header')
<x-page-header class="rj-page-header" title="Rekap Jurnal Pembelajaran">
    <x-slot:actions>
        <a href="{{ route('sekolah.jurnal') }}" class="btn btn-primary rj-btn-add">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Input Jurnal Baru</span>
        </a>
    </x-slot:actions>
</x-page-header>
@endsection

@push('styles')
<style>
    /* ======================================================================
       HEADER SEJAJAR & TOMBOL KOMPAK (Desktop & Mobile)
       ====================================================================== */
    .rj-page-header {
        margin-bottom: 12px !important;
    }

    .rj-page-header .page-header-row {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 10px !important;
    }

    .rj-page-header .page-header-left {
        flex: 1 1 auto !important;
        width: auto !important;
        min-width: 0 !important;
    }

    .rj-page-header .page-header-title {
        margin: 0 !important;
        line-height: 1.25 !important;
        font-size: 20px !important;
    }

    .rj-page-header .page-header-actions {
        width: auto !important;
        flex: 0 0 auto !important;
        margin-left: auto !important;
        justify-content: flex-end !important;
    }

    .rj-page-header .page-header-actions a.rj-btn-add,
    a.rj-btn-add {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        height: 34px !important;
        padding: 0 12px !important;
        border-radius: 10px !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        white-space: nowrap !important;
        text-decoration: none !important;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.18);
    }

    a.rj-btn-add svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }

    @media (max-width: 640px) {
        .rj-page-header .page-header-title {
            font-size: 16.5px !important;
            letter-spacing: -0.02em !important;
        }

        .rj-page-header .page-header-actions a.rj-btn-add,
        a.rj-btn-add {
            height: 32px !important;
            padding: 0 10px !important;
            font-size: 12px !important;
            border-radius: 9px !important;
        }
    }

    @media (max-width: 370px) {
        .rj-page-header .page-header-title {
            font-size: 15px !important;
        }

        .rj-page-header .page-header-actions a.rj-btn-add,
        a.rj-btn-add {
            padding: 0 9px !important;
            font-size: 11.5px !important;
        }
    }

    /* ======================================================================
       BARIS FILTER RINGKAS (Total Data + Tombol Icon Filter)
       ====================================================================== */
    .rj-compact-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 2px 2px 4px;
    }

    .rj-toolbar-left {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        min-width: 0;
    }

    .rj-total-count {
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
    }

    .rj-total-count strong {
        font-weight: 700;
        color: #334155;
    }

    .dark-mode .rj-total-count {
        color: #94a3b8;
    }

    .dark-mode .rj-total-count strong {
        color: #f1f5f9;
    }

    .rj-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
    }

    .dark-mode .rj-filter-chip {
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
        border-color: rgba(59, 130, 246, 0.35);
    }

    .rj-reset-inline {
        font-size: 12px;
        font-weight: 600;
        color: #dc2626;
        text-decoration: none;
        padding: 2px 4px;
    }

    .rj-reset-inline:hover {
        text-decoration: underline;
    }

    .dark-mode .rj-reset-inline {
        color: #f87171;
    }

    .rj-filter-icon-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: 34px;
        padding: 0 11px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12.5px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        transition: border-color 0.15s ease, background-color 0.15s ease, color 0.15s ease;
        flex-shrink: 0;
    }

    .rj-filter-icon-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .rj-filter-icon-btn.has-active-filter {
        border-color: #93c5fd;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .dark-mode .rj-filter-icon-btn {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

    .dark-mode .rj-filter-icon-btn:hover {
        background: #27354a;
        color: #f8fafc;
    }

    .dark-mode .rj-filter-icon-btn.has-active-filter {
        background: rgba(37, 99, 235, 0.2);
        border-color: #3b82f6;
        color: #93c5fd;
    }

    .rj-filter-icon-btn svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .rj-filter-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #2563eb;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1;
    }

    /* ======================================================================
       DAFTAR KARTU JURNAL (bg-white, rounded-2xl, border-slate-100, shadow lembut)
       ====================================================================== */
    .rj-card-list.mobile-siswa-list,
    body .rj-card-list.mobile-siswa-list {
        display: flex !important;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    .rj-card.mobile-siswa-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.02);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }

    .rj-card.mobile-siswa-card:hover {
        border-color: #e2e8f0;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06), 0 2px 4px rgba(15, 23, 42, 0.03);
    }

    .rj-card.mobile-siswa-card:active {
        transform: scale(0.992);
        background-color: #f8fafc;
    }

    .dark-mode .rj-card.mobile-siswa-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .dark-mode .rj-card.mobile-siswa-card:hover {
        border-color: #475569;
        background: #233044;
    }

    .rj-card-left {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .rj-card-title {
        font-size: 14.5px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.32;
        margin: 0;
        word-break: break-word;
    }

    .dark-mode .rj-card-title {
        color: #f8fafc;
    }

    .rj-card-line2 {
        font-size: 12.5px;
        font-weight: 500;
        color: #475569;
        line-height: 1.35;
    }

    .dark-mode .rj-card-line2 {
        color: #cbd5e1;
    }

    .rj-card-line3 {
        font-size: 12px;
        font-weight: 400;
        color: #64748b;
        line-height: 1.35;
    }

    .dark-mode .rj-card-line3 {
        color: #94a3b8;
    }

    .rj-card-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .rj-attendance-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        letter-spacing: -0.01em;
    }

    /* bg-emerald-50 text-emerald-700 */
    .rj-attendance-badge.is-complete {
        background-color: #ecfdf5;
        color: #047857;
        border: 1px solid #d1fae5;
    }

    /* bg-amber-50 text-amber-700 */
    .rj-attendance-badge.is-partial {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fef3c7;
    }

    .dark-mode .rj-attendance-badge.is-complete {
        background-color: rgba(16, 185, 129, 0.16);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }

    .dark-mode .rj-attendance-badge.is-partial {
        background-color: rgba(245, 158, 11, 0.16);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.3);
    }

    .rj-card-chevron {
        width: 16px;
        height: 16px;
        color: #94a3b8;
        flex-shrink: 0;
        transition: transform 0.15s ease, color 0.15s ease;
    }

    .rj-card.mobile-siswa-card:hover .rj-card-chevron {
        transform: translateX(2px);
        color: #64748b;
    }

    .dark-mode .rj-card-chevron {
        color: #64748b;
    }

    .rj-empty-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px dashed #e2e8f0;
        padding: 40px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        color: #64748b;
    }

    .dark-mode .rj-empty-card {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    /* ======================================================================
       PAGINATION RINGKAS & STATUS ABU-ABU TIPIS DI TENGAH
       ====================================================================== */
    .rj-pagination-footer {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 0 6px;
    }

    .rj-pagination-status {
        font-size: 12px;
        font-weight: 400;
        color: #94a3b8;
        text-align: center;
        margin: 0;
    }

    .dark-mode .rj-pagination-status {
        color: #64748b;
    }

    /* ======================================================================
       BOTTOM SHEET / MODAL SHEET (Berfungsi di Mobile & Desktop)
       ====================================================================== */
    .mobile-sheet-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 9998;
        opacity: 0;
        transition: opacity 0.24s cubic-bezier(0.16, 1, 0.3, 1);
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
        max-width: 500px;
        margin: 0 auto;
        background: #ffffff;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transform: translateY(100%);
        transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
        display: flex;
        flex-direction: column;
        max-height: 88vh;
        overflow: hidden;
    }

    @media (min-width: 769px) {
        .mobile-bottom-sheet {
            top: 50%;
            bottom: auto;
            border-radius: 20px;
            max-width: 460px;
            transform: translateY(-44%) scale(0.96);
            opacity: 0;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
        }

        .mobile-bottom-sheet.is-open {
            transform: translateY(-50%) scale(1) !important;
            opacity: 1 !important;
            display: flex !important;
        }

        .mobile-bottom-sheet .sheet-handle-bar {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .mobile-bottom-sheet.is-open {
            transform: translateY(0) !important;
            display: flex !important;
        }
    }

    .dark-mode .mobile-bottom-sheet {
        background: #1e293b;
        border: 1px solid #334155;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5);
    }

    .sheet-handle-bar {
        width: 100%;
        padding: 12px 0 8px;
        display: flex;
        justify-content: center;
    }

    .sheet-drag-handle {
        width: 46px;
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
        gap: 12px;
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    @media (max-width: 768px) {
        .sheet-header {
            padding: 6px 20px 14px;
        }
    }

    .dark-mode .sheet-header {
        border-bottom-color: #334155;
    }

    .sheet-avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sheet-title-wrap {
        flex: 1;
        min-width: 0;
    }

    .sheet-siswa-nama {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }

    .dark-mode .sheet-siswa-nama {
        color: #f8fafc;
    }

    .sheet-siswa-meta {
        font-size: 12px;
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

    .sheet-detail-box {
        margin: 12px 18px 4px;
        padding: 11px 14px;
        border-radius: 13px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .dark-mode .sheet-detail-box {
        background: #0f172a;
        border-color: #334155;
    }

    .sheet-detail-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 12.5px;
    }

    .sheet-detail-lbl {
        font-size: 11px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .sheet-detail-val {
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .dark-mode .sheet-detail-val {
        color: #f1f5f9;
    }

    .sheet-materi-text {
        font-size: 12.5px;
        color: #334155;
        line-height: 1.45;
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
    }

    .dark-mode .sheet-materi-text {
        color: #cbd5e1;
        border-top-color: #334155;
    }

    .sheet-actions-list {
        padding: 8px 14px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sheet-action-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 11px 14px;
        border-radius: 14px;
        background: transparent;
        border: none;
        color: #1e293b;
        cursor: pointer;
        width: 100%;
        text-align: left;
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

    .sheet-action-item.is-danger {
        color: #dc2626;
    }

    .dark-mode .sheet-action-item.is-danger {
        color: #f87171;
    }

    .sheet-action-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sheet-action-icon svg {
        width: 18px;
        height: 18px;
    }

    .sheet-action-icon.icon-view {
        background: #eff6ff;
        color: #2563eb;
    }

    .dark-mode .sheet-action-icon.icon-view {
        background: rgba(37, 99, 235, 0.2);
        color: #60a5fa;
    }

    .sheet-action-icon.icon-delete {
        background: #fef2f2;
        color: #dc2626;
    }

    .dark-mode .sheet-action-icon.icon-delete {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .sheet-action-title {
        font-size: 14px;
        font-weight: 600;
    }

    .sheet-footer {
        padding: 12px 18px 18px;
        border-top: 1px solid #f1f5f9;
    }

    .dark-mode .sheet-footer {
        border-top-color: #334155;
    }

    .sheet-btn-close {
        width: 100%;
        height: 44px;
        border-radius: 12px;
        background: #234e38;
        color: #ffffff;
        font-size: 14.5px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark-mode .sheet-btn-close {
        background: #2d5a44;
    }

    /* Form Filter Sheet */
    .rj-filter-sheet-body {
        padding: 16px 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        overflow-y: auto;
    }

    .rj-filter-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .rj-filter-field label {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
    }

    .dark-mode .rj-filter-field label {
        color: #cbd5e1;
    }

    .rj-filter-sheet-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px 18px;
        border-top: 1px solid #f1f5f9;
    }

    .dark-mode .rj-filter-sheet-actions {
        border-top-color: #334155;
    }
</style>
@endpush

@section('content')
@php
    $activeFiltersCount = collect(['kelas', 'semester', 'tanggal'])
        ->filter(fn ($key) => request()->filled($key))
        ->count();
    $hasAnyFilter = $activeFiltersCount > 0 || request()->filled('search');
@endphp

<div style="display:flex; flex-direction:column; gap:10px; width:100%;">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- Baris Filter Ringkas (Total Data + Tombol Icon Filter) --}}
    <div class="rj-compact-toolbar">
        <div class="rj-toolbar-left">
            <span class="rj-total-count">Total <strong>{{ $jurnals->total() }}</strong> jurnal</span>

            @if(request()->filled('kelas'))
                <span class="rj-filter-chip">Kelas {{ \App\Http\Controllers\PlottingKelasController::toRomanDisplay(request('kelas')) }}</span>
            @endif
            @if(request()->filled('semester'))
                <span class="rj-filter-chip">{{ request('semester') }}</span>
            @endif
            @if(request()->filled('tanggal'))
                <span class="rj-filter-chip">{{ \Carbon\Carbon::parse(request('tanggal'))->format('d/m/Y') }}</span>
            @endif
            @if(request()->filled('search'))
                <span class="rj-filter-chip">"{{ request('search') }}"</span>
            @endif

            @if($hasAnyFilter)
                <a href="{{ route('sekolah.rekap-jurnal') }}" class="rj-reset-inline">Reset</a>
            @endif
        </div>

        <button type="button"
                id="btnOpenRjFilter"
                class="rj-filter-icon-btn {{ $activeFiltersCount > 0 ? 'has-active-filter' : '' }}"
                aria-label="Filter Jurnal"
                title="Filter Jurnal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <span>Filter</span>
            @if($activeFiltersCount > 0)
                <span class="rj-filter-badge">{{ $activeFiltersCount }}</span>
            @endif
        </button>
    </div>

    {{-- Daftar Kartu Jurnal --}}
    <div class="rj-card-list mobile-siswa-list">
        @forelse($jurnals as $item)
            @php
                $meta = \App\Http\Controllers\SekolahController::formatJurnalCardMeta($item, $timeSlots ?? null);
                $absensiSummary = 'H: ' . $item->total_hadir . '/' . $item->total_siswa
                    . ($item->total_sakit > 0 ? ' • S: ' . $item->total_sakit : '')
                    . ($item->total_izin > 0 ? ' • I: ' . $item->total_izin : '')
                    . ($item->total_alpa > 0 ? ' • A: ' . $item->total_alpa : '');
            @endphp
            <div class="rj-card mobile-siswa-card bg-white rounded-2xl border border-slate-100"
                 data-id="{{ $item->id }}"
                 data-mapel="{{ $meta['mapel_title'] }}"
                 data-kelas="{{ $meta['kelas_label'] }}"
                 data-tanggal="{{ $meta['hari_tanggal_label'] }}"
                 data-jam="{{ $meta['jam_ke_label'] ?: $item->jam_ke }}"
                 data-guru="{{ $item->nama_guru }}"
                 data-materi="{{ $item->materi }}"
                 data-absensi="{{ $absensiSummary }}">
                <div class="rj-card-left">
                    <div class="rj-card-title font-bold text-slate-800">{{ $meta['mapel_title'] }}</div>
                    <div class="rj-card-line2">{{ $meta['baris_2'] }}</div>
                    <div class="rj-card-line3">{{ $meta['baris_3'] }}</div>
                </div>

                <div class="rj-card-right">
                    @if($meta['is_hadir_lengkap'])
                        <span class="rj-attendance-badge is-complete bg-emerald-50 text-emerald-700">{{ $meta['badge_text'] }}</span>
                    @else
                        <span class="rj-attendance-badge is-partial bg-amber-50 text-amber-700">{{ $meta['badge_text'] }}</span>
                    @endif

                    <svg class="rj-card-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </div>
            </div>
        @empty
            <div class="rj-empty-card">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:38px;height:38px;opacity:0.45;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <span style="font-size:13.5px; font-weight:500;">Belum ada data jurnal pembelajaran yang diinput.</span>
                <a href="{{ route('sekolah.jurnal') }}" class="btn btn-sm btn-primary" style="margin-top:2px;">
                    Mulai Input Jurnal
                </a>
            </div>
        @endforelse
    </div>

    {{-- Pagination Ringkas / Status Teks Abu-abu Tipis di Tengah --}}
    @if($jurnals->total() > 0)
        <div class="rj-pagination-footer">
            @if($jurnals->hasPages())
                @php
                    $currentPage = $jurnals->currentPage();
                    $lastPage = $jurnals->lastPage();
                    $startPage = max(1, $currentPage - 1);
                    $endPage = min($lastPage, $currentPage + 1);
                @endphp
                <nav aria-label="Navigasi Halaman Jurnal">
                    <ul class="pagination-list">
                        <li class="pagination-item {{ $jurnals->onFirstPage() ? 'disabled' : '' }}">
                            @if($jurnals->onFirstPage())
                                <span class="pagination-link" aria-disabled="true">&lsaquo;</span>
                            @else
                                <a href="{{ $jurnals->previousPageUrl() }}" class="pagination-link" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                            @endif
                        </li>

                        @if($startPage > 1)
                            <li class="pagination-item">
                                <a href="{{ $jurnals->url(1) }}" class="pagination-link">1</a>
                            </li>
                            @if($startPage > 2)
                                <li class="pagination-item"><span class="pagination-link dots">&hellip;</span></li>
                            @endif
                        @endif

                        @for($p = $startPage; $p <= $endPage; $p++)
                            <li class="pagination-item {{ $p === $currentPage ? 'active' : '' }}">
                                @if($p === $currentPage)
                                    <span class="pagination-link" aria-current="page">{{ $p }}</span>
                                @else
                                    <a href="{{ $jurnals->url($p) }}" class="pagination-link">{{ $p }}</a>
                                @endif
                            </li>
                        @endfor

                        @if($endPage < $lastPage)
                            @if($endPage < $lastPage - 1)
                                <li class="pagination-item"><span class="pagination-link dots">&hellip;</span></li>
                            @endif
                            <li class="pagination-item">
                                <a href="{{ $jurnals->url($lastPage) }}" class="pagination-link">{{ $lastPage }}</a>
                            </li>
                        @endif

                        <li class="pagination-item {{ !$jurnals->hasMorePages() ? 'disabled' : '' }}">
                            @if($jurnals->hasMorePages())
                                <a href="{{ $jurnals->nextPageUrl() }}" class="pagination-link" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                            @else
                                <span class="pagination-link" aria-disabled="true">&rsaquo;</span>
                            @endif
                        </li>
                    </ul>
                </nav>
                <p class="rj-pagination-status">
                    Menampilkan {{ $jurnals->firstItem() }}–{{ $jurnals->lastItem() }} dari {{ $jurnals->total() }} jurnal pembelajaran
                </p>
            @else
                <p class="rj-pagination-status">
                    Menampilkan {{ $jurnals->count() }} dari {{ $jurnals->total() }} jurnal pembelajaran
                </p>
            @endif
        </div>
    @endif

</div>

{{-- Sheet Filter Jurnal --}}
<div id="rjFilterBackdrop" class="mobile-sheet-backdrop" style="display:none;"></div>
<div id="rjFilterSheet" class="mobile-bottom-sheet" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="rjFilterSheetTitle">
    <div class="sheet-handle-bar">
        <span class="sheet-drag-handle"></span>
    </div>

    <div class="sheet-header" style="justify-content:space-between;">
        <h3 class="sheet-siswa-nama" id="rjFilterSheetTitle">Filter Jurnal Pembelajaran</h3>
        @if($hasAnyFilter)
            <a href="{{ route('sekolah.rekap-jurnal') }}" class="rj-reset-inline">Reset</a>
        @endif
    </div>

    <form action="{{ route('sekolah.rekap-jurnal') }}" method="GET">
        @if(request()->filled('search'))
            <input type="hidden" name="search" value="{{ request('search') }}">
        @endif

        <div class="rj-filter-sheet-body">
            <div class="rj-filter-field">
                <label for="rj_filter_kelas">Kelas</label>
                <select id="rj_filter_kelas" name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        @php
                            $isKSelected = request('kelas') === $k || (request('kelas') && \App\Http\Controllers\PlottingKelasController::isSameClass(request('kelas'), $k));
                            $dispK = \App\Http\Controllers\PlottingKelasController::toRomanDisplay($k);
                        @endphp
                        <option value="{{ $k }}" {{ $isKSelected ? 'selected' : '' }}>
                            Kelas {{ $dispK }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="rj-filter-field">
                <label for="rj_filter_semester">Semester</label>
                <select id="rj_filter_semester" name="semester" class="form-select">
                    <option value="">Semua Semester</option>
                    <option value="Semester Ganjil" {{ request('semester') === 'Semester Ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                    <option value="Semester Genap" {{ request('semester') === 'Semester Genap' ? 'selected' : '' }}>Semester Genap</option>
                </select>
            </div>

            <div class="rj-filter-field">
                <label for="rj_filter_tanggal">Tanggal</label>
                <input type="date" id="rj_filter_tanggal" name="tanggal" value="{{ request('tanggal') }}" class="form-control">
            </div>
        </div>

        <div class="rj-filter-sheet-actions">
            <button type="button" id="btnCloseRjFilter" class="btn btn-secondary" style="flex:1; justify-content:center;">Batal</button>
            <button type="submit" class="btn btn-primary" style="flex:1.4; justify-content:center;">Terapkan Filter</button>
        </div>
    </form>
</div>

{{-- Sheet Aksi Jurnal --}}
<div id="mobileJurnalSheetBackdrop" class="mobile-sheet-backdrop" style="display:none;"></div>
<div id="mobileJurnalActionSheet" class="mobile-bottom-sheet" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="sheetJurnalMapel">
    <div class="sheet-handle-bar">
        <span class="sheet-drag-handle"></span>
    </div>

    <div class="sheet-header">
        <div class="sheet-avatar">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
        </div>
        <div class="sheet-title-wrap">
            <h3 class="sheet-siswa-nama" id="sheetJurnalMapel">Mata Pelajaran</h3>
            <div class="sheet-siswa-meta">
                <span id="sheetJurnalKelas">Kelas</span>
                <span>•</span>
                <span id="sheetJurnalTanggal">-</span>
                <span>•</span>
                <span id="sheetJurnalJam">-</span>
            </div>
        </div>
    </div>

    <div class="sheet-detail-box">
        <div class="sheet-detail-row">
            <span class="sheet-detail-lbl">Guru</span>
            <span class="sheet-detail-val" id="sheetJurnalGuru">-</span>
        </div>
        <div class="sheet-detail-row">
            <span class="sheet-detail-lbl">Kehadiran</span>
            <span class="sheet-detail-val" id="sheetJurnalAbsensi">-</span>
        </div>
        <div class="sheet-materi-text" id="sheetJurnalMateri">-</div>
    </div>

    <div class="sheet-actions-list">
        <button type="button" id="sheetBtnViewJurnal" class="sheet-action-item">
            <div class="sheet-action-icon icon-view">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </div>
            <span class="sheet-action-title">Lihat Detail Jurnal</span>
        </button>

        <button type="button" id="sheetBtnDeleteJurnal" class="sheet-action-item is-danger">
            <div class="sheet-action-icon icon-delete">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </div>
            <span class="sheet-action-title">Hapus Jurnal</span>
        </button>
    </div>

    <div class="sheet-footer">
        <button type="button" class="sheet-btn-close" id="sheetJurnalBtnClose">Tutup</button>
    </div>
</div>

{{-- MODAL DETAIL JURNAL & HAPUS JURNAL UNTUK SETIAP ITEM --}}
@foreach($jurnals as $item)
@php
    $modalMeta = \App\Http\Controllers\SekolahController::formatJurnalCardMeta($item, $timeSlots ?? null);
@endphp

{{-- MODAL DETAIL --}}
<div class="app-modal" id="modalDetailJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:700px;">
        <div class="modal-header">
            <div class="modal-header-title">
                <div class="modal-icon-badge view-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <h3 class="modal-title">Detail Jurnal Pembelajaran</h3>
            </div>
            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:16px;">
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; background:var(--bg-card-header); padding:14px; border-radius:10px;">
                <div>
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Kelas & Waktu</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">{{ $modalMeta['baris_2'] }}</strong>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $modalMeta['baris_3'] }}</div>
                </div>
                <div>
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Guru & Mata Pelajaran</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">{{ $item->nama_guru }}</strong>
                    <div style="font-size:12px; color:var(--electric-blue); font-weight:600;">{{ $modalMeta['mapel_title'] }}</div>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $item->semester }} - {{ $item->tahun_pelajaran }}</div>
                </div>
            </div>

            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Materi Pembelajaran:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; line-height:1.5;">
                    {{ $item->materi }}
                </div>
            </div>

            @if($item->catatan)
            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Catatan:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:12.5px; color:var(--text-secondary); line-height:1.5;">
                    {{ $item->catatan }}
                </div>
            </div>
            @endif

            {{-- Detail Absensi Siswa --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:6px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">Daftar Kehadiran Siswa ({{ $item->total_siswa }} Siswa)</span>
                    <div style="display:flex; gap:6px;">
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(16, 185, 129, 0.15); color:#059669;">H: {{ $item->total_hadir }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(245, 158, 11, 0.15); color:#D97706;">S: {{ $item->total_sakit }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(37, 99, 235, 0.15); color:#2563EB;">I: {{ $item->total_izin }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(239, 68, 68, 0.15); color:#DC2626;">A: {{ $item->total_alpa }}</span>
                    </div>
                </div>

                <div style="max-height:220px; overflow-y:auto; border:1px solid var(--border-light); border-radius:8px;">
                    <table class="modern-table" style="font-size:12.5px;">
                        <thead>
                            <tr>
                                <th style="width:35px; text-align:center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:110px; text-align:center;">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->absensis as $absIndex => $abs)
                            <tr>
                                <td style="text-align:center;">{{ $absIndex + 1 }}</td>
                                <td style="font-weight:600;">{{ $abs->nama_siswa }}</td>
                                <td style="text-align:center;">
                                    @php
                                        $badgeColor = match($abs->status) {
                                            'hadir' => 'background:rgba(16, 185, 129, 0.15); color:#059669;',
                                            'sakit' => 'background:rgba(245, 158, 11, 0.15); color:#D97706;',
                                            'izin'  => 'background:rgba(37, 99, 235, 0.15); color:#2563EB;',
                                            'alpa'  => 'background:rgba(239, 68, 68, 0.15); color:#DC2626;',
                                            default => 'background:rgba(16, 185, 129, 0.15); color:#059669;',
                                        };
                                    @endphp
                                    <span style="padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px; {{ $badgeColor }}">
                                        {{ ucfirst($abs->status) }}
                                    </span>
                                </td>
                                <td style="color:var(--text-secondary); font-size:11.5px;">
                                    {{ $abs->keterangan ?: '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:12px; color:var(--text-secondary);">Tidak ada data detail absensi</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Tutup</button>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI HAPUS --}}
<div class="app-modal" id="modalDeleteJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:440px;">
        <form action="{{ route('sekolah.jurnal.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <div class="modal-header-title">
                    <div class="modal-icon-badge delete-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Hapus Jurnal Pembelajaran</h3>
                </div>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body" style="text-align:left;">
                <p style="margin:0 0 10px; font-size:13.5px; color:var(--text-primary);">
                    Apakah Anda yakin ingin menghapus data jurnal pembelajaran <strong>{{ $modalMeta['kelas_label'] }}</strong> tanggal <strong>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</strong>?
                </p>
                <p style="margin:0; font-size:12px; color:var(--text-secondary);">
                    Seluruh rekap kehadiran siswa pada sesi ini juga akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus Jurnal</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- SCRIPT PENGENDALI FILTER SHEET, ACTION SHEET & MODAL --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('is-active');
            if (!document.querySelector('.app-modal.is-active')) {
                document.body.style.overflow = '';
            }
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal(this.getAttribute('data-modal-target'));
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal(this.closest('.app-modal'));
        });
    });

    document.querySelectorAll('.app-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });

    function openSheetEl(sheetEl, backdropEl) {
        if (!sheetEl || !backdropEl) return;
        backdropEl.style.display = 'block';
        sheetEl.style.display = 'flex';
        requestAnimationFrame(function () {
            backdropEl.classList.add('is-active');
            sheetEl.classList.add('is-open');
        });
        document.body.style.overflow = 'hidden';
    }

    function closeSheetEl(sheetEl, backdropEl, keepBodyLock) {
        if (!sheetEl || !backdropEl) return;
        backdropEl.classList.remove('is-active');
        sheetEl.classList.remove('is-open');
        if (!keepBodyLock && !document.querySelector('.app-modal.is-active')) {
            document.body.style.overflow = '';
        }
        setTimeout(function () {
            if (!sheetEl.classList.contains('is-open')) {
                sheetEl.style.display = 'none';
                backdropEl.style.display = 'none';
            }
        }, 280);
    }

    // 1. Filter Sheet
    var filterSheet = document.getElementById('rjFilterSheet');
    var filterBackdrop = document.getElementById('rjFilterBackdrop');
    var btnOpenFilter = document.getElementById('btnOpenRjFilter');
    var btnCloseFilter = document.getElementById('btnCloseRjFilter');

    if (btnOpenFilter) {
        btnOpenFilter.addEventListener('click', function () {
            openSheetEl(filterSheet, filterBackdrop);
        });
    }
    if (btnCloseFilter) {
        btnCloseFilter.addEventListener('click', function () {
            closeSheetEl(filterSheet, filterBackdrop, false);
        });
    }
    if (filterBackdrop) {
        filterBackdrop.addEventListener('click', function () {
            closeSheetEl(filterSheet, filterBackdrop, false);
        });
    }

    // 2. Action Sheet Kartu Jurnal
    var sheet = document.getElementById('mobileJurnalActionSheet');
    var backdrop = document.getElementById('mobileJurnalSheetBackdrop');
    var btnCloseSheet = document.getElementById('sheetJurnalBtnClose');
    var btnViewJurnal = document.getElementById('sheetBtnViewJurnal');
    var btnDeleteJurnal = document.getElementById('sheetBtnDeleteJurnal');
    var currentJurnalId = null;

    function openJurnalSheet(card) {
        if (!sheet || !backdrop) return;
        currentJurnalId = card.getAttribute('data-id');

        var elMapel = document.getElementById('sheetJurnalMapel');
        var elKelas = document.getElementById('sheetJurnalKelas');
        var elTanggal = document.getElementById('sheetJurnalTanggal');
        var elJam = document.getElementById('sheetJurnalJam');
        var elGuru = document.getElementById('sheetJurnalGuru');
        var elAbsensi = document.getElementById('sheetJurnalAbsensi');
        var elMateri = document.getElementById('sheetJurnalMateri');

        if (elMapel) elMapel.textContent = card.getAttribute('data-mapel') || '-';
        if (elKelas) elKelas.textContent = card.getAttribute('data-kelas') || '-';
        if (elTanggal) elTanggal.textContent = card.getAttribute('data-tanggal') || '-';
        if (elJam) elJam.textContent = card.getAttribute('data-jam') || '-';
        if (elGuru) elGuru.textContent = card.getAttribute('data-guru') || '-';
        if (elAbsensi) elAbsensi.textContent = card.getAttribute('data-absensi') || '-';
        if (elMateri) elMateri.textContent = card.getAttribute('data-materi') || '-';

        openSheetEl(sheet, backdrop);
    }

    document.querySelectorAll('.rj-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            if (e.target.closest('input, button, a, label')) return;
            openJurnalSheet(card);
        });
    });

    if (btnCloseSheet) {
        btnCloseSheet.addEventListener('click', function () {
            closeSheetEl(sheet, backdrop, false);
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            closeSheetEl(sheet, backdrop, false);
        });
    }
    if (btnViewJurnal) {
        btnViewJurnal.addEventListener('click', function () {
            var id = currentJurnalId;
            closeSheetEl(sheet, backdrop, true);
            if (id) openModal('modalDetailJurnal' + id);
        });
    }
    if (btnDeleteJurnal) {
        btnDeleteJurnal.addEventListener('click', function () {
            var id = currentJurnalId;
            closeSheetEl(sheet, backdrop, true);
            if (id) openModal('modalDeleteJurnal' + id);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (filterSheet && filterSheet.classList.contains('is-open')) {
                closeSheetEl(filterSheet, filterBackdrop, false);
                return;
            }
            if (sheet && sheet.classList.contains('is-open')) {
                closeSheetEl(sheet, backdrop, false);
                return;
            }
            var activeModal = document.querySelector('.app-modal.is-active');
            if (activeModal) {
                closeModal(activeModal);
            }
        }
    });
});
</script>
@endsection
