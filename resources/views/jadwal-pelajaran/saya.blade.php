@extends('layouts.app')

@section('title', 'Jadwal Mengajar Saya')

@section('header-actions')
<a href="{{ route('jadwal-pelajaran.index') }}" class="btn btn-secondary jms-header-btn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="3" y1="9" x2="21" y2="9"></line>
        <line x1="9" y1="21" x2="9" y2="9"></line>
    </svg>
    <span>Master Jadwal Grid</span>
</a>
@endsection

@push('styles')
<style>
    .jms-page {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }
    .jms-header-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        padding: 7px 12px !important;
        font-size: 12.5px !important;
    }

    /* Compact 4-Card Stats Row (Desktop 4-col, Mobile 2x2) */
    .jms-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .jms-stat-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--card-border, #e5e7eb);
        border-radius: 10px;
        padding: 10px 12px !important;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: var(--card-shadow, 0 2px 8px rgba(0, 0, 0, 0.02));
        min-width: 0;
    }
    .jms-stat-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .jms-stat-icon svg {
        width: 16px;
        height: 16px;
    }
    .jms-stat-body {
        min-width: 0;
        flex: 1;
    }
    .jms-stat-label {
        font-size: 11px;
        color: var(--text-secondary, #64748b);
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .jms-stat-val {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary, #111827);
        line-height: 1.25;
        margin-top: 2px;
    }
    .jms-stat-unit {
        font-size: 11px;
        font-weight: 500;
        color: var(--text-muted, #94a3b8);
    }
    .jms-stat-sub {
        font-size: 10px;
        color: var(--text-muted, #94a3b8);
        line-height: 1.2;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Main 2-Column Layout */
    .jadwal-grid-layout {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }
    .jms-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--card-border, #e5e7eb);
        border-radius: 12px;
        padding: 13px 15px !important;
        box-shadow: var(--card-shadow, 0 2px 10px rgba(0, 0, 0, 0.02));
    }
    .jms-card-head {
        border-bottom: 1px solid var(--border-light, #e5e7eb);
        padding-bottom: 9px;
        margin-bottom: 11px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }
    .jms-card-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary, #111827);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .jms-card-desc {
        font-size: 11.5px;
        color: var(--text-secondary, #64748b);
        margin: 2px 0 0 0;
        line-height: 1.35;
    }

    /* Info Box Mapel Diampu */
    .jms-mapel-box {
        background: var(--nav-hover-bg, #f8fafc);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 8px;
        padding: 8px 10px;
        margin-bottom: 11px;
    }
    .dark-mode .jms-mapel-box {
        background: rgba(255, 255, 255, 0.03);
    }
    .jms-mapel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 5px;
    }
    .jms-mapel-title {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--text-secondary, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .jms-mapel-link {
        font-size: 11px;
        color: #059669;
        text-decoration: none;
        font-weight: 600;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Compact Form Controls */
    .jms-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .jms-form .form-group {
        margin: 0;
    }
    .jms-label {
        font-weight: 600;
        font-size: 12px;
        color: var(--text-primary, #1e293b);
        margin-bottom: 4px;
        display: block;
    }
    .jms-form .form-control {
        height: 36px;
        padding: 5px 10px;
        font-size: 12.5px;
        border-radius: 8px;
    }
    .jms-jenjang-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .jms-jenjang-opt {
        border: 1.5px solid var(--border-light, #e2e8f0);
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #1e293b);
        border-radius: 8px;
        padding: 7px 9px;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 12px;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .jms-jenjang-opt input[type="radio"] {
        margin: 0;
        width: 14px;
        height: 14px;
        accent-color: #10B981;
        flex-shrink: 0;
    }
    .jms-jenjang-opt.is-selected {
        border-color: #10B981;
        background: rgba(16, 185, 129, 0.08);
    }
    .jms-submit-btn {
        width: 100%;
        justify-content: center;
        height: 38px;
        padding: 8px 12px !important;
        font-weight: 700;
        font-size: 13px !important;
        border-radius: 8px !important;
        margin-top: 2px;
    }

    /* Compact Weekly Schedule Cards */
    .jms-days-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .jms-day-card {
        background: var(--nav-hover-bg, #f8fafc);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 9px;
        overflow: hidden;
    }
    .dark-mode .jms-day-card {
        background: rgba(255, 255, 255, 0.02);
    }
    .jms-day-head {
        padding: 7px 11px;
        background: rgba(148, 163, 184, 0.1);
        border-bottom: 1px solid var(--border-light, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .jms-day-title {
        font-weight: 700;
        font-size: 12.5px;
        color: var(--text-primary, #1e293b);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .jms-day-count {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #cbd5e1);
        color: var(--text-secondary, #334155);
        font-weight: 600;
        font-size: 10.5px;
        padding: 1px 7px;
        border-radius: 999px;
    }
    .jms-slots-wrap {
        padding: 7px 9px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .jms-slot-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 8px;
        padding: 7px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .jms-slot-left {
        display: flex;
        align-items: center;
        gap: 9px;
        min-width: 0;
        flex: 1;
    }
    .jms-slot-time {
        min-width: 72px;
        padding-right: 9px;
        border-right: 1px solid var(--border-light, #e2e8f0);
        flex-shrink: 0;
    }
    .jms-slot-jam {
        font-weight: 700;
        font-size: 11.5px;
        color: var(--text-primary, #1e293b);
        line-height: 1.2;
    }
    .jms-slot-clock {
        font-size: 10.5px;
        color: var(--text-secondary, #64748b);
        line-height: 1.2;
        margin-top: 2px;
    }
    .jms-slot-info {
        min-width: 0;
        flex: 1;
    }
    .jms-slot-mapel {
        font-weight: 700;
        font-size: 12.5px;
        color: var(--text-primary, #1e293b);
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .jms-slot-badges {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        margin-top: 3px;
    }
    .jms-pill {
        font-size: 10.5px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 4px;
        line-height: 1.35;
    }
    .jms-pill-smp {
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
    }
    .jms-pill-sma {
        background: rgba(139, 92, 246, 0.12);
        color: #7c3aed;
    }
    .jms-pill-kelas {
        background: rgba(148, 163, 184, 0.14);
        color: var(--text-primary, #1e293b);
    }
    .jms-pill-kode {
        background: rgba(16, 185, 129, 0.12);
        color: #059669;
    }
    .jms-slot-note {
        font-size: 10.5px;
        color: var(--text-secondary, #64748b);
        margin-top: 2px;
    }
    .jms-btn-del {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #ef4444;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        flex-shrink: 0;
        transition: all 0.15s ease;
    }
    .jms-btn-del:hover {
        background: #fee2e2;
        border-color: #fca5a5;
    }
    .dark-mode .jms-btn-del {
        background: rgba(239, 68, 68, 0.14);
        border-color: rgba(239, 68, 68, 0.3);
        color: #f87171;
    }

    @media (max-width: 900px) {
        .jadwal-grid-layout {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 768px) {
        .jms-page {
            gap: 10px;
        }
        .jms-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .jms-stat-card {
            padding: 8px 10px !important;
            gap: 8px;
            border-radius: 9px;
        }
        .jms-stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 7px;
        }
        .jms-stat-icon svg {
            width: 14px;
            height: 14px;
        }
        .jms-stat-label {
            font-size: 10.5px;
        }
        .jms-stat-val {
            font-size: 14.5px;
        }
        .jms-stat-unit {
            font-size: 10px;
        }
        .jms-card {
            padding: 11px 12px !important;
            border-radius: 10px;
        }
        .jms-jenjang-opt {
            padding: 6px 8px;
            font-size: 11.5px;
            gap: 5px;
        }
        .jms-slot-time {
            min-width: 66px;
            padding-right: 7px;
        }
    }
</style>
@endpush

@section('content')
<div class="jms-page">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Validasi Penginputan Jadwal Gagal:</strong>
            <ul style="margin:4px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Compact Stats Cards --}}
    <div class="jms-stats-grid">
        <div class="form-section-card jms-stat-card">
            <div class="jms-stat-icon" style="background:rgba(16, 185, 129, 0.12); color:#10B981;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="jms-stat-body">
                <div class="jms-stat-label">Total Jam</div>
                <div class="jms-stat-val">
                    {{ $totalJam }} <span class="jms-stat-unit">JP/Mgg</span>
                </div>
            </div>
        </div>

        <div class="form-section-card jms-stat-card">
            <div class="jms-stat-icon" style="background:rgba(59, 130, 246, 0.12); color:#3B82F6;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div class="jms-stat-body">
                <div class="jms-stat-label">Mengajar SMP</div>
                <div class="jms-stat-val" style="color:#3B82F6;">
                    {{ $totalJamSMP }} <span class="jms-stat-unit">JP</span>
                </div>
            </div>
        </div>

        <div class="form-section-card jms-stat-card">
            <div class="jms-stat-icon" style="background:rgba(139, 92, 246, 0.12); color:#8B5CF6;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div class="jms-stat-body">
                <div class="jms-stat-label">Mengajar SMA</div>
                <div class="jms-stat-val" style="color:#8B5CF6;">
                    {{ $totalJamSMA }} <span class="jms-stat-unit">JP</span>
                </div>
            </div>
        </div>

        <div class="form-section-card jms-stat-card">
            <div class="jms-stat-icon" style="background:{{ $hasLocked ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.12)' }}; color:{{ $hasLocked ? '#EF4444' : '#10B981' }};">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div class="jms-stat-body">
                <div class="jms-stat-label">Status Input</div>
                <div class="jms-stat-val" style="font-size:12.5px; color:{{ $hasLocked ? '#EF4444' : '#10B981' }};">
                    {{ $hasLocked ? 'Terkunci Final' : 'Bebas Diedit' }}
                </div>
                <div class="jms-stat-sub">{{ $tahunAjaran }} ({{ $semester }})</div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Content --}}
    <div class="jadwal-grid-layout">

        {{-- Left Column: Input Form Mandiri --}}
        <div class="form-section-card jms-card">
            <div class="jms-card-head">
                <div>
                    <h3 class="jms-card-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px; color:#10B981;">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        <span>Input Jadwal Mengajar</span>
                    </h3>
                    <p class="jms-card-desc">
                        Pilih jenjang, kelas, mapel, hari, dan jam pelajaran.
                    </p>
                </div>
            </div>

            {{-- Info Sinkronisasi Guru Sekolah --}}
            <div class="jms-mapel-box">
                <div class="jms-mapel-head">
                    <span class="jms-mapel-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px; color:#10B981;">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        <span>Mapel Diampu</span>
                    </span>
                    <a href="{{ route('guru-sekolah.index') }}" class="jms-mapel-link" title="Buka Menu Guru Sekolah">
                        Menu Guru &rarr;
                    </a>
                </div>
                @if($hasAssignedMapel)
                    <div style="display:flex; align-items:center; gap:5px; flex-wrap:wrap;">
                        @foreach($teacherMapels as $tmp)
                        <span class="badge" style="background:rgba(16, 185, 129, 0.12); color:#059669; font-weight:700; font-size:11.5px; padding:2px 7px; border-radius:5px; border:1px solid rgba(16, 185, 129, 0.25);">
                            ★ {{ $tmp }}
                        </span>
                        @endforeach
                        <span class="badge" style="background:rgba(148, 163, 184, 0.16); color:var(--text-secondary, #475569); font-size:10.5px; padding:2px 6px; border-radius:5px;">
                            {{ ucfirst($mapelStatus ?? 'Aktif') }}
                        </span>
                    </div>
                @else
                    <div style="font-size:11.5px; color:#d97706; display:flex; align-items:center; gap:5px; line-height:1.35;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px; flex-shrink:0;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>Belum ada mapel di menu Guru Sekolah.</span>
                    </div>
                @endif
            </div>

            <form action="{{ route('jadwal-pelajaran.saya.store') }}" method="POST" id="formInputJadwal" class="jms-form">
                @csrf

                {{-- Pilihan Jenjang --}}
                <div class="form-group">
                    <label class="form-label jms-label">
                        1. Jenjang Sekolah <span style="color:#ef4444;">*</span>
                    </label>
                    <div class="jms-jenjang-grid">
                        <label class="jms-jenjang-opt is-selected" id="labelJenjangSMP">
                            <input type="radio" name="jenjang_select" value="SMP" checked onchange="switchJenjang('SMP')">
                            <span>SMP Al-Muttaqin</span>
                        </label>
                        <label class="jms-jenjang-opt" id="labelJenjangSMA">
                            <input type="radio" name="jenjang_select" value="SMA" onchange="switchJenjang('SMA')">
                            <span>SMA Al-Muttaqin</span>
                        </label>
                    </div>
                </div>

                {{-- Rombel Kelas --}}
                <div class="form-group">
                    <label for="classroom_id" class="form-label jms-label">
                        2. Kelas / Rombel Tujuan <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="classroom_id" id="classroom_id" class="form-control" required style="width:100%;">
                        <option value="">-- Pilih Kelas --</option>
                    </select>
                </div>

                {{-- Mata Pelajaran (Tersinkron dengan Plotting Mapel & Guru Sekolah) --}}
                <div class="form-group">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                        <label for="subject_id" class="form-label jms-label" style="margin:0;">
                            3. Mata Pelajaran <span style="color:#ef4444;">*</span>
                        </label>
                        <span style="font-size:10.5px; color:var(--text-secondary, #64748b);">
                            Plotting Mapel
                        </span>
                    </div>
                    <select name="subject_id" id="subject_id" class="form-control" required style="width:100%;">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                    </select>

                    {{-- Notice bila belum ada mapel yang di-plot untuk jenjang ini --}}
                    <div id="noMapelAlert" style="display:none; margin-top:6px; padding:8px 10px; border-radius:7px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.25); color:#dc2626; font-size:11.5px; line-height:1.4;">
                        <div>Mata pelajaran Anda belum terdaftar pada jenjang ini di <strong>Plotting Mata Pelajaran</strong>.</div>
                        @if($isPrivileged)
                        <div style="margin-top:5px;">
                            <a href="{{ route('plotting-mapel.index') }}" class="btn btn-secondary" style="font-size:11px; padding:3px 8px; text-decoration:none; display:inline-block;">
                                Buka Plotting Mapel &rarr;
                            </a>
                        </div>
                        @endif
                    </div>

                    @if($isPrivileged)
                    <div style="margin-top:5px;">
                        <label style="font-size:11px; color:var(--text-secondary, #64748b); display:flex; align-items:center; gap:5px; cursor:pointer;">
                            <input type="checkbox" id="toggleShowAllMapel" onchange="toggleAllMapelMode(this.checked)" style="accent-color:#10B981;">
                            <span>Tampilkan seluruh mapel (Mode Pengurus)</span>
                        </label>
                    </div>
                    @endif
                </div>

                {{-- Hari Mengajar --}}
                <div class="form-group">
                    <label for="hari" class="form-label jms-label">
                        4. Hari Mengajar <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="hari" id="hari" class="form-control" required style="width:100%;">
                        @foreach($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rentang Jam (Jam Mulai s/d Jam Selesai) --}}
                <div class="form-group">
                    <label class="form-label jms-label">
                        5. Rentang Jam Pelajaran (JP) <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:8px; align-items:center;">
                        <div>
                            <select name="jam_mulai" id="jam_mulai" class="form-control" required style="width:100%;">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div style="font-size:11.5px; font-weight:600; color:var(--text-secondary, #64748b);">s/d</div>
                        <div>
                            <select name="jam_selesai" id="jam_selesai" class="form-control" required style="width:100%;">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div style="font-size:11px; color:var(--text-secondary, #64748b); margin-top:4px;" id="jamPreviewText">
                        Total alokasi: <strong>1 Jam Pelajaran (JP)</strong>
                    </div>
                </div>

                {{-- Catatan Opsional --}}
                <div class="form-group">
                    <label for="catatan" class="form-label jms-label">
                        Catatan Khusus (Opsional)
                    </label>
                    <input type="text" name="catatan" id="catatan" class="form-control" placeholder="Contoh: Lab Komputer / Perpustakaan" maxlength="255" style="width:100%;">
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary jms-submit-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Jadwal Mengajar</span>
                </button>
            </form>
        </div>

        {{-- Right Column: Unified Weekly Schedule --}}
        <div class="form-section-card jms-card">
            <div class="jms-card-head">
                <div>
                    <h3 class="jms-card-title">Kalender Mengajar Mingguan Saya</h3>
                    <p class="jms-card-desc">
                        Penggabungan jadwal mengajar SMP &amp; SMA Al-Muttaqin
                    </p>
                </div>
                <span class="badge" style="background:rgba(16, 185, 129, 0.12); color:#059669; font-weight:700; padding:3px 9px; border-radius:999px; font-size:11px;">
                    {{ $mySchedules->count() }} Slot
                </span>
            </div>

            @if($mySchedules->isEmpty())
            <div style="padding:24px 14px; text-align:center; color:var(--text-secondary, #64748b); background:var(--nav-hover-bg, #f8fafc); border-radius:8px; border:1px dashed var(--border-light, #cbd5e1);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:34px;height:34px; margin-bottom:8px; color:#94a3b8;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <div style="font-weight:700; font-size:13.5px; color:var(--text-primary, #1e293b); margin-bottom:3px;">
                    Belum Ada Jadwal Mengajar
                </div>
                <p style="font-size:12px; max-width:360px; margin:0 auto; line-height:1.4;">
                    Gunakan formulir Input Jadwal Mengajar untuk menambahkan jadwal mengajar Anda.
                </p>
            </div>
            @else
            <div class="jms-days-list">
                @php
                    $groupedByDay = $mySchedules->groupBy(fn($s) => $s->timeSlot->hari);
                @endphp

                @foreach($days as $day)
                    @php
                        $daySchedules = $groupedByDay->get($day, collect());
                    @endphp
                    @if($daySchedules->isNotEmpty())
                    <div class="jms-day-card">
                        {{-- Day Header --}}
                        <div class="jms-day-head">
                            <div class="jms-day-title">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px; color:#10B981;">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <span>{{ $day }}</span>
                            </div>
                            <span class="jms-day-count">{{ $daySchedules->count() }} JP</span>
                        </div>

                        {{-- Schedule items for this day --}}
                        <div class="jms-slots-wrap">
                            @foreach($daySchedules as $sched)
                            @php
                                $rawKelas = trim((string) ($sched->classroom->nama_kelas ?? ''));
                                $namaKelasBersih = stripos($rawKelas, 'kelas') === 0 ? $rawKelas : 'Kelas ' . $rawKelas;
                            @endphp
                            <div class="jms-slot-card">
                                <div class="jms-slot-left">
                                    {{-- Jam & Waktu --}}
                                    <div class="jms-slot-time">
                                        <div class="jms-slot-jam">Jam Ke-{{ $sched->timeSlot->jam_ke }}</div>
                                        <div class="jms-slot-clock">
                                            {{ substr($sched->timeSlot->waktu_mulai, 0, 5) }} - {{ substr($sched->timeSlot->waktu_selesai, 0, 5) }}
                                        </div>
                                    </div>

                                    {{-- Mapel & Kelas --}}
                                    <div class="jms-slot-info">
                                        <div class="jms-slot-mapel">{{ $sched->subject->nama_pelajaran }}</div>
                                        <div class="jms-slot-badges">
                                            @if($sched->classroom->jenjang === 'SMP')
                                                <span class="jms-pill jms-pill-smp">SMP</span>
                                            @else
                                                <span class="jms-pill jms-pill-sma">SMA</span>
                                            @endif
                                            <span class="jms-pill jms-pill-kelas">{{ $namaKelasBersih }}</span>
                                            @if(!empty($sched->subject->kode))
                                                <span class="jms-pill jms-pill-kode">{{ $sched->subject->kode }}</span>
                                            @endif
                                        </div>
                                        @if($sched->catatan)
                                        <div class="jms-slot-note">Catatan: {{ $sched->catatan }}</div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Action: Delete --}}
                                <div>
                                    @if($sched->is_locked)
                                        <span class="jms-pill" style="background:#fef2f2; color:#ef4444; display:inline-flex; align-items:center; gap:3px;" title="Terkunci oleh Admin/Yayasan">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px;">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                            <span>Terkunci</span>
                                        </span>
                                    @else
                                        <form action="{{ route('jadwal-pelajaran.destroy', $sched->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus jadwal ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="jms-btn-del" title="Hapus Jadwal" aria-label="Hapus Jadwal">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>

{{-- Embedded Master Data for Instant Dynamic Dropdown Switching --}}
<script>
    const masterData = {
        SMP: {
            classrooms: {!! json_encode($smpClassrooms->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama_kelas])) !!},
            assignedSubjects: {!! json_encode($smpSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!},
            allSubjects: {!! json_encode($allSmpSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!}
        },
        SMA: {
            classrooms: {!! json_encode($smaClassrooms->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama_kelas])) !!},
            assignedSubjects: {!! json_encode($smaSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!},
            allSubjects: {!! json_encode($allSmaSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!}
        }
    };

    let currentJenjang = 'SMP';
    let showAllMapelMode = false;

    function toggleAllMapelMode(showAll) {
        showAllMapelMode = !!showAll;
        populateSubjects(currentJenjang);
    }

    function populateSubjects(jenjang) {
        const subSelect = document.getElementById('subject_id');
        const alertBox = document.getElementById('noMapelAlert');
        const submitBtn = document.querySelector('#formInputJadwal button[type="submit"]');

        const assignedList = masterData[jenjang].assignedSubjects || [];
        const allList = masterData[jenjang].allSubjects || [];
        const list = showAllMapelMode ? allList : assignedList;

        subSelect.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';

        if (!list || list.length === 0) {
            if (alertBox) alertBox.style.display = 'block';
            subSelect.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            return;
        }

        if (alertBox) alertBox.style.display = 'none';
        subSelect.disabled = false;
        if (submitBtn) submitBtn.disabled = false;

        list.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            const badge = sub.is_assigned ? ' ★ [Diampu]' : '';
            opt.textContent = `${sub.kode ? sub.kode + ' - ' : ''}${sub.nama}${badge}`;
            subSelect.appendChild(opt);
        });

        // Auto-select if only 1 subject available
        if (list.length === 1) {
            subSelect.value = list[0].id;
        }
    }

    function switchJenjang(jenjang) {
        currentJenjang = jenjang;
        const clsSelect = document.getElementById('classroom_id');
        const labelSMP = document.getElementById('labelJenjangSMP');
        const labelSMA = document.getElementById('labelJenjangSMA');

        if (jenjang === 'SMP') {
            labelSMP.classList.add('is-selected');
            labelSMA.classList.remove('is-selected');
        } else {
            labelSMA.classList.add('is-selected');
            labelSMP.classList.remove('is-selected');
        }

        // Update Classrooms (tampilkan nama kelas saja tanpa Tingkat)
        clsSelect.innerHTML = '<option value="">-- Pilih Kelas --</option>';
        masterData[jenjang].classrooms.forEach(cls => {
            const opt = document.createElement('option');
            opt.value = cls.id;
            const rawNama = String(cls.nama || '').trim();
            opt.textContent = /^kelas\b/i.test(rawNama) ? rawNama : `Kelas ${rawNama}`;
            clsSelect.appendChild(opt);
        });

        // Update Subjects
        populateSubjects(jenjang);
    }

    // Rentang Jam sync
    const jamMulaiSelect = document.getElementById('jam_mulai');
    const jamSelesaiSelect = document.getElementById('jam_selesai');
    const jamPreviewText = document.getElementById('jamPreviewText');

    function updateJamPreview() {
        const start = parseInt(jamMulaiSelect.value, 10);
        let end = parseInt(jamSelesaiSelect.value, 10);

        if (end < start) {
            jamSelesaiSelect.value = start;
            end = start;
        }

        const count = (end - start) + 1;
        jamPreviewText.innerHTML = `Total alokasi: <strong>${count} Jam Pelajaran (JP)</strong> (${start === end ? 'Jam Ke-' + start : 'Jam Ke-' + start + ' s/d Jam Ke-' + end})`;
    }

    jamMulaiSelect.addEventListener('change', function() {
        if (parseInt(jamSelesaiSelect.value, 10) < parseInt(this.value, 10)) {
            jamSelesaiSelect.value = this.value;
        }
        updateJamPreview();
    });
    jamSelesaiSelect.addEventListener('change', updateJamPreview);

    // Initial render
    document.addEventListener('DOMContentLoaded', function() {
        switchJenjang('SMP');
        updateJamPreview();
    });
</script>
@endsection
