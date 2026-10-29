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

@media (max-width: 768px) {
    /* Sembunyikan tabel desktop di mobile */
    .table-card {
        display: none !important;
    }

    /* Tampilkan daftar card mobile */
    .mobile-siswa-list {
        display: flex !important;
        flex-direction: column;
        gap: 12px;
    }

    /* Container Card Siswa Mobile Sesuai Gambar */
    .mobile-siswa-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 13px 15px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .dark-mode .mobile-siswa-card {
        background: var(--card-bg, #1e293b);
        border-color: var(--border-light, #334155);
        box-shadow: none;
    }

    /* Highlight merah tipis (opacity 90%) untuk santri data belum lengkap */
    .mobile-siswa-card.row-incomplete {
        background-color: rgba(239, 68, 68, 0.08) !important;
        border-color: rgba(239, 68, 68, 0.3) !important;
        border-left: 4px solid #ef4444 !important;
    }

    .dark-mode .mobile-siswa-card.row-incomplete {
        background-color: rgba(239, 68, 68, 0.14) !important;
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

    /* Grid Label & Badges di bawah Nama Sesuai Gambar */
    .mobile-siswa-meta-grid {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-top: 4px;
        flex-wrap: wrap;
    }

    .mobile-meta-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .mobile-meta-label {
        font-size: 11px;
        font-weight: 500;
        color: #64748b;
        line-height: 1;
    }

    .dark-mode .mobile-meta-label {
        color: #94a3b8;
    }

    .mobile-badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 3px 9px;
        border-radius: 6px;
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

    .mobile-badge-pill.status-pulang {
        background-color: #fef3c7;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .mobile-badge-pill.status-sakit {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .dark-mode .mobile-badge-pill.status-aktif {
        background-color: rgba(34, 197, 94, 0.18);
        color: #4ade80;
        border-color: rgba(34, 197, 94, 0.35);
    }

    /* Sisi Kanan: Aksi Sesuai Gambar */
    .mobile-siswa-action-col {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
        flex-shrink: 0;
    }

    .mobile-action-title {
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        line-height: 1;
        text-align: right;
        padding-right: 2px;
    }

    .dark-mode .mobile-action-title {
        color: #94a3b8;
    }

    .mobile-action-buttons {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .mobile-action-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        min-height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        text-decoration: none;
        padding: 0;
        transition: transform 0.1s ease, filter 0.15s ease;
    }

    .mobile-action-btn:active {
        transform: scale(0.92);
    }

    .mobile-action-btn svg {
        width: 15px;
        height: 15px;
    }

    /* Tombol Lihat (Mata - Biru) */
    .mobile-action-btn.btn-view {
        background-color: #dbeafe;
        color: #2563eb;
    }
    .dark-mode .mobile-action-btn.btn-view {
        background-color: rgba(37, 99, 235, 0.22);
        color: #60a5fa;
    }

    /* Tombol Edit (Pensil - Kuning) */
    .mobile-action-btn.btn-edit {
        background-color: #fef3c7;
        color: #d97706;
    }
    .dark-mode .mobile-action-btn.btn-edit {
        background-color: rgba(217, 119, 6, 0.22);
        color: #fbbf24;
    }

    /* Tombol Hapus (Sampah - Merah) */
    .mobile-action-btn.btn-delete {
        background-color: #fee2e2;
        color: #ef4444;
    }
    .dark-mode .mobile-action-btn.btn-delete {
        background-color: rgba(239, 68, 68, 0.22);
        color: #f87171;
    }

    /* ---- Penyempurnaan tampilan card mobile ---- */
    .mobile-siswa-list { gap: 14px; }

    .mobile-siswa-card {
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        padding: 0;
        overflow: hidden;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }

    .mobile-siswa-info {
        padding: 14px 16px 12px;
        gap: 8px;
    }

    .mobile-siswa-nama { font-size: 16px; letter-spacing: -0.01em; }

    .mobile-siswa-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, auto);
        justify-content: start;
        gap: 6px 14px;
        margin-top: 2px;
    }

    .mobile-meta-label {
        font-size: 10.5px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
    }

    .mobile-badge-pill { padding: 4px 10px; font-size: 12px; border-radius: 8px; }

    .mobile-siswa-action-col {
        flex-direction: row;
        align-items: center;
        justify-content: flex-end;
        border-top: 1px solid #f1f5f9;
        background: transparent;
        padding: 6px 10px;
        width: 100%;
        box-sizing: border-box;
    }

    .dark-mode .mobile-siswa-action-col {
        border-top-color: var(--border-light, #334155);
    }

    .mobile-action-title { display: none; }

    .mobile-action-buttons {
        width: auto;
        gap: 4px;
    }

    .mobile-action-buttons form { display: flex !important; margin: 0; }

    .mobile-action-btn {
        flex: none;
        width: 30px;
        height: 30px;
        min-width: 30px;
        min-height: 30px;
        border-radius: 8px;
        background: transparent !important;
        opacity: 0.75;
    }

    .mobile-action-btn:active,
    .mobile-action-btn:hover { opacity: 1; background: #f1f5f9 !important; }

    .mobile-action-btn svg { width: 15px; height: 15px; }
    .mobile-action-btn.btn-view { color: #64748b; }
    .mobile-action-btn.btn-edit { color: #64748b; }
    .mobile-action-btn.btn-delete { color: #ef4444; }

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
<div style="display:flex; flex-direction:column; gap:20px;">

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
                        $targetCetakUrl = $item->pendaftaran_id
                            ? route('pendaftaran.cetak', ['id' => $item->pendaftaran_id, 'from' => 'siswa'])
                            : route('siswa.show', $item->id);
                        $targetEditUrl = route('siswa.edit', $item->id);
                    @endphp
                    <tr class="{{ !$isLengkap ? 'row-incomplete' : '' }}"
                        data-id="{{ $item->id }}"
                        data-nama="{{ $item->nama_lengkap }}"
                        data-view-url="{{ $targetCetakUrl }}"
                        data-edit-url="{{ $targetEditUrl }}">
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

                                {{-- Tombol Lihat (Hanya Icon Mata -> Menampilkan Formulir Cetak Seperti Gambar 2) --}}
                                <a href="{{ $targetCetakUrl }}" class="action-btn action-btn-view btn-view" title="Lihat Formulir Siswa" aria-label="Lihat Formulir {{ $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span class="sr-only" style="display:none;">Lihat</span>
                                </a>

                                {{-- Tombol Edit / Koreksi (Icon Pensil -> Menuju Formulir Pendaftaran) --}}
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

    {{-- Tampilan Mobile Card List (Khusus Tampilan Layar HP Sesuai Gambar) --}}
    <div class="mobile-siswa-list">
        @forelse($siswa as $item)
        @php
            $isLengkap = $item->isDataLengkap();
            $missingFields = !$isLengkap ? $item->field_belum_lengkap : [];
            $targetCetakUrl = $item->pendaftaran_id
                ? route('pendaftaran.cetak', ['id' => $item->pendaftaran_id, 'from' => 'siswa'])
                : route('siswa.show', $item->id);
            $jenjangText = $item->jenjang ?? ($item->pendaftaran?->jenjang ?? 'Reguler');
            $kamarText = $item->kamar ?? '-';
            $statusText = ucfirst($item->status ?? 'aktif');
            $statusKey = strtolower($item->status ?? 'aktif');
        @endphp
        <div class="mobile-siswa-card {{ !$isLengkap ? 'row-incomplete' : '' }}">
            {{-- Sisi Kiri: Nama & Kolom Jenjang, Kamar, Status --}}
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
                    <div class="text-missing-fields" style="margin-bottom: 4px;" title="Field yang belum diisi: {{ implode(', ', $missingFields) }}">
                        <span class="missing-label">Kurang:</span> {{ implode(', ', array_slice($missingFields, 0, 3)) }}{{ count($missingFields) > 3 ? ', +' . (count($missingFields) - 3) . ' lainnya' : '' }}
                    </div>
                @endif

                <div class="mobile-siswa-meta-grid">
                    <div class="mobile-meta-item">
                        <span class="mobile-meta-label">Jenjang</span>
                        <span class="mobile-badge-pill">{{ $jenjangText }}</span>
                    </div>
                    <div class="mobile-meta-item">
                        <span class="mobile-meta-label">Kamar</span>
                        <span class="mobile-badge-pill">{{ $kamarText }}</span>
                    </div>
                    <div class="mobile-meta-item">
                        <span class="mobile-meta-label">RFID</span>
                        <span class="mobile-badge-pill" style="font-family: monospace; font-size: 11px;">{{ $item->rfid_uid ?? '-' }}</span>
                    </div>
                    <div class="mobile-meta-item mobile-meta-status">
                        <span class="mobile-meta-label">&nbsp;</span>
                        <span class="mobile-badge-pill status-{{ $statusKey }}">{{ $statusText }}</span>
                    </div>
                </div>
            </div>

            {{-- Sisi Kanan: Kolom Aksi dengan Tombol Lihat, Edit, Hapus, RFID --}}
            <div class="mobile-siswa-action-col">
                <span class="mobile-action-title">Aksi</span>
                <div class="mobile-action-buttons">
                    {{-- Tombol Pairing RFID (Mobile) --}}
                    @if(!auth()->check() || auth()->user()->canDo('siswa', 'edit'))
                    <button type="button" class="mobile-action-btn btn-rfid-pair"
                       data-id="{{ $item->id }}" 
                       data-nama="{{ $item->nama_lengkap }}" 
                       data-uid="{{ $item->rfid_uid ?? '' }}"
                       title="Pairing Kartu RFID Santri"
                       style="color: #2563eb;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </button>
                    @endif

                    {{-- Tombol Lihat (Mata - Biru) --}}
                    <a href="{{ $targetCetakUrl }}" class="mobile-action-btn btn-view" title="Lihat Formulir Siswa" aria-label="Lihat Formulir {{ $item->nama_lengkap }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </a>

                    {{-- Tombol Edit / Koreksi (Pensil - Kuning) --}}
                    @if(!auth()->check() || auth()->user()->canDo('siswa', 'edit'))
                    <a href="{{ route('siswa.edit', $item->id) }}"
                       class="mobile-action-btn btn-edit {{ !$isLengkap ? 'is-koreksi' : '' }}"
                       title="{{ !$isLengkap ? 'Edit & Koreksi Kelengkapan Data Santri (' . implode(', ', $missingFields) . ')' : 'Edit Formulir Pendaftaran Siswa' }}"
                       aria-label="{{ !$isLengkap ? 'Edit & Koreksi Data ' . $item->nama_lengkap : 'Edit Formulir ' . $item->nama_lengkap }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    @endif

                    {{-- Tombol Hapus (Sampah - Merah) --}}
                    @if(!auth()->check() || auth()->user()->canDo('siswa', 'delete'))
                    <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" style="display:inline; margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="mobile-action-btn btn-delete js-swal-confirm"
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
                        </button>
                    </form>
                    @endif
                </div>
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

    // Handler Pairing Kartu RFID Santri
    document.querySelectorAll('.btn-rfid-pair').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var siswaId = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            var currentUid = this.getAttribute('data-uid') || '';

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
        });
    });
});
</script>
@endsection
