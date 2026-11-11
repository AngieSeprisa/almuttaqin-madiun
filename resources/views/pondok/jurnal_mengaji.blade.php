@extends('layouts.app')

@section('title', !empty($isRekapPage) ? 'Rekap Jurnal Pengajian' : 'Jurnal Mengaji Pondok')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('pondok.rekap-absensi') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
        <span>Rekap Absensi</span>
    </a>
    <a href="{{ route('pondok.target.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
        <span>Target Pengajian</span>
    </a>
    <button type="button" class="btn btn-primary" data-modal-target="modalTambahJurnalMengaji" style="display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Input Jurnal Mengaji</span>
    </button>
</div>
@endsection

@push('styles')
<style>
    .jm-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    @media (max-width: 768px) {
        .jm-stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
    }

    .jm-stat-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 13px 16px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .jm-stat-lbl {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .jm-stat-val {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.2;
        color: var(--text-primary, #0f172a);
    }

    .jm-stat-sub {
        font-size: 11px;
        color: #94a3b8;
    }

    .jm-indikator-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .jm-indikator-badge.status-tercapai {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .jm-indikator-badge.status-kurang {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .jm-indikator-badge.status-terlampaui {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }

    .dark-mode .jm-indikator-badge.status-tercapai {
        background: rgba(16, 185, 129, 0.18);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.35);
    }

    .dark-mode .jm-indikator-badge.status-kurang {
        background: rgba(245, 158, 11, 0.18);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.35);
    }

    .dark-mode .jm-indikator-badge.status-terlampaui {
        background: rgba(99, 102, 241, 0.2);
        color: #a5b4fc;
        border-color: rgba(99, 102, 241, 0.35);
    }

    .jm-live-preview {
        padding: 11px 14px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .dark-mode .jm-live-preview {
        background: #0f172a;
        border-color: #334155;
    }

    /* Absensi Santri di dalam Modal Jurnal */
    .jm-absensi-box {
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        background: var(--card-bg, #ffffff);
        overflow: hidden;
    }

    .jm-absensi-header {
        padding: 10px 14px;
        background: var(--bg-card-header, #f8fafc);
        border-bottom: 1px solid var(--border-light, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .jm-absensi-list {
        max-height: 250px;
        overflow-y: auto;
        divide-y: 1px solid var(--border-light, #f1f5f9);
    }

    .jm-absensi-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 14px;
        border-bottom: 1px solid var(--border-light, #f1f5f9);
    }

    .jm-absensi-row:last-child {
        border-bottom: none;
    }

    .jm-status-pills {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    .jm-pill-label {
        position: relative;
        cursor: pointer;
        user-select: none;
    }

    .jm-pill-label input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .jm-pill-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 26px;
        padding: 0 7px;
        border-radius: 7px;
        font-size: 11.5px;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        transition: all 0.15s ease;
    }

    .dark-mode .jm-pill-btn {
        background: #1e293b;
        border-color: #334155;
        color: #cbd5e1;
    }

    .jm-pill-label input[value="hadir"]:checked + .jm-pill-btn {
        background: #10b981;
        border-color: #059669;
        color: #ffffff;
    }

    .jm-pill-label input[value="terlambat"]:checked + .jm-pill-btn {
        background: #0ea5e9;
        border-color: #0284c7;
        color: #ffffff;
    }

    .jm-pill-label input[value="izin"]:checked + .jm-pill-btn {
        background: #3b82f6;
        border-color: #2563eb;
        color: #ffffff;
    }

    .jm-pill-label input[value="sakit"]:checked + .jm-pill-btn {
        background: #f59e0b;
        border-color: #d97706;
        color: #ffffff;
    }

    .jm-pill-label input[value="alpa"]:checked + .jm-pill-btn {
        background: #ef4444;
        border-color: #dc2626;
        color: #ffffff;
    }
</style>
@endpush

@section('content')
@php
    $hariIndo = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];
    $currentRoute = !empty($isRekapPage) ? route('pondok.rekap-jurnal') : route('pondok.jurnal.index');
@endphp

<div style="display:flex; flex-direction:column; gap:16px; width:100%;">

    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    {{-- Kartu Ringkasan Indikator Ketercapaian --}}
    <div class="jm-stats-grid">
        <div class="jm-stat-card">
            <span class="jm-stat-lbl">Total Jurnal Kelas</span>
            <span class="jm-stat-val">{{ $stats['total'] }}</span>
            <span class="jm-stat-sub">Pencapaian & absensi tercatat</span>
        </div>
        <div class="jm-stat-card">
            <span class="jm-stat-lbl" style="color:#047857;">Indikator: Tercapai</span>
            <span class="jm-stat-val" style="color:#047857;">{{ $stats['tercapai'] }}</span>
            <span class="jm-stat-sub">Sesuai target materi</span>
        </div>
        <div class="jm-stat-card">
            <span class="jm-stat-lbl" style="color:#b45309;">Indikator: Kurang</span>
            <span class="jm-stat-val" style="color:#b45309;">{{ $stats['kurang'] }}</span>
            <span class="jm-stat-sub">Di bawah target materi</span>
        </div>
        <div class="jm-stat-card">
            <span class="jm-stat-lbl" style="color:#4338ca;">Indikator: Terlampaui</span>
            <span class="jm-stat-val" style="color:#4338ca;">{{ $stats['terlampaui'] }}</span>
            <span class="jm-stat-sub">Melebihi target materi</span>
        </div>
    </div>

    {{-- Filter Docked untuk Untitled UI Table Enhancer --}}
    <div class="filter-card" style="padding:14px 16px;">
        <form action="{{ $currentRoute }}" method="GET" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" style="width:auto; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Tanggal">

                <select name="kelas_pengajian_id" class="form-select" style="width:auto; min-width:175px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Kelas Pengajian">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kls)
                        <option value="{{ $kls->id }}" {{ (string) request('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                            {{ $kls->nama_kelas }}
                        </option>
                    @endforeach
                </select>

                <select name="status_ketercapaian" class="form-select" style="width:auto; min-width:165px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Indikator Ketercapaian">
                    <option value="">Semua Indikator</option>
                    <option value="tercapai" {{ request('status_ketercapaian') === 'tercapai' ? 'selected' : '' }}>Tercapai</option>
                    <option value="kurang" {{ request('status_ketercapaian') === 'kurang' ? 'selected' : '' }}>Kurang</option>
                    <option value="terlampaui" {{ request('status_ketercapaian') === 'terlampaui' ? 'selected' : '' }}>Terlampaui</option>
                </select>

                <select name="kategori" class="form-select" style="width:auto; min-width:140px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Kategori Materi">
                    <option value="">Semua Kategori</option>
                    <option value="al_quran" {{ request('kategori') === 'al_quran' ? 'selected' : '' }}>Al-Qur'an</option>
                    <option value="al_hadist" {{ request('kategori') === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
                </select>

                @if(request()->hasAny(['tanggal', 'kelas_pengajian_id', 'status_ketercapaian', 'kategori', 'search']))
                    <a href="{{ $currentRoute }}" class="btn btn-secondary" style="height:36px; padding:0 12px; display:inline-flex; align-items:center;">Reset</a>
                @endif
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kelas, materi, pengajar..." style="height:36px; font-size:13px; width:220px;">
                <button type="submit" class="btn btn-primary" style="height:36px; padding:0 12px;">Cari</button>
            </div>
        </form>
    </div>

    {{-- Tabel Pencapaian & Absensi Pengajian Per Kelas (Custom Blade Tailwind Table - Untitled UI Style) --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th>Tanggal & Pengajar</th>
                        <th>Kelas Pengajian</th>
                        <th>Materi Pondok</th>
                        <th>Target Materi</th>
                        <th>Pencapaian Kelas</th>
                        <th style="text-align:center;">Indikator Ketercapaian</th>
                        <th style="text-align:center;">Kehadiran Santri</th>
                        <th>Catatan</th>
                        <th style="width:105px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnals as $idx => $item)
                    @php
                        $dt = \Carbon\Carbon::parse($item->tanggal);
                        $hari = $hariIndo[$dt->format('l')] ?? $dt->format('l');
                        $hadirTotal = (int) $item->total_hadir + (int) $item->total_terlambat;
                        $santriTotal = (int) $item->total_santri;
                        $isLengkap = $santriTotal > 0 && $hadirTotal >= $santriTotal;
                    @endphp
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $hari }}, {{ $dt->format('d/m/Y') }}</div>
                            <div style="display:flex; align-items:center; gap:6px; margin-top:2px; flex-wrap:wrap;">
                                <span style="font-size:11px; font-weight:600; color:#2563eb; background:#eff6ff; padding:1px 7px; border-radius:999px;">
                                    {{ $item->sesi_label }}
                                </span>
                                <span style="font-size:12px; color:var(--text-secondary);">{{ $item->nama_pengajar ?: '-' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge-lembaga" style="background:rgba(37, 99, 235, 0.1); color:#1d4ed8; font-weight:700;">
                                {{ $item->kelasPengajian?->nama_kelas ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $item->materi?->nama_materi ?? '-' }}</div>
                            <span style="font-size:11.5px; font-weight:600; color:{{ $item->materi?->isAlHadist() ? '#1d4ed8' : '#047857' }};">
                                {{ $item->materi?->kategori_label ?? "Al-Qur'an" }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">
                                {{ $item->target_label }}
                            </span>
                        </td>
                        <td>
                            <span style="font-size:13px; font-weight:700; color:var(--text-primary);">
                                {{ $item->capaian_label }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                                <span class="jm-indikator-badge status-{{ $item->status_ketercapaian }}">
                                    @if($item->status_ketercapaian === 'tercapai')
                                        ✓ Tercapai
                                    @elseif($item->status_ketercapaian === 'terlampaui')
                                        ↑ Terlampaui
                                    @else
                                        ! Kurang
                                    @endif
                                </span>
                                <span style="font-size:11px; color:var(--text-secondary); font-weight:600;">
                                    {{ $item->selisih_label }}
                                </span>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            @if($santriTotal > 0)
                                <div style="display:flex; flex-direction:column; align-items:center; gap:3px;">
                                    <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; {{ $isLengkap ? 'background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;' : 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;' }}">
                                        {{ $isLengkap ? '✓ ' . $hadirTotal . '/' . $santriTotal : 'H: ' . $hadirTotal . '/' . $santriTotal }}
                                    </span>
                                    @if(!$isLengkap)
                                        <span style="font-size:10.5px; color:var(--text-secondary); font-weight:600;">
                                            I:{{ (int) $item->total_izin }} S:{{ (int) $item->total_sakit }} A:{{ (int) $item->total_alpa }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span style="font-size:12px; color:var(--text-secondary);">-</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:var(--text-secondary);">
                            {{ $item->catatan ?: '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditJurnal{{ $item->id }}" title="Edit Jurnal & Absensi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button type="button" class="action-btn action-btn-delete" data-modal-target="modalHapusJurnal{{ $item->id }}" title="Hapus Jurnal">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:44px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <span style="font-size:14px; font-weight:600;">Belum ada data jurnal mengaji yang dicatat</span>
                                <span style="font-size:12.5px;">Catat pencapaian pengajian dan absensi santri per kelas untuk memantau ketercapaian materi.</span>
                                <button type="button" class="btn btn-sm btn-primary" data-modal-target="modalTambahJurnalMengaji" style="margin-top:4px;">
                                    + Input Jurnal Mengaji Pertama
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL INPUT JURNAL MENGAJI + ABSENSI SANTRI --}}
<div class="app-modal" id="modalTambahJurnalMengaji" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:640px;">
        <form action="{{ route('pondok.jurnal.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Input Jurnal & Absensi Pengajian</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px; max-height:76vh; overflow-y:auto;">
                <div class="form-group">
                    <label class="form-label">Acuan Target Pengajian</label>
                    <select name="target_pengajian_id" id="jm_add_target_ref" class="form-select" onchange="applySelectedTargetRef('add')">
                        <option value="">Pilih Target Pengajian</option>
                        @foreach($targetList as $t)
                            <option value="{{ $t->id }}"
                                    data-tanggal="{{ \Carbon\Carbon::parse($t->tanggal)->format('Y-m-d') }}"
                                    data-sesi="{{ $t->sesi_pengajian ?: 'bada_maghrib' }}"
                                    data-kelas="{{ $t->kelas_pengajian_id }}"
                                    data-materi="{{ $t->pondok_materi_id }}"
                                    data-kategori="{{ $t->materi?->kategori ?? 'al_quran' }}"
                                    data-surat="{{ (int) ($t->surat_nomor ?: 1) }}"
                                    data-ayat="{{ (int) ($t->ayat ?: 1) }}"
                                    data-target-hal="{{ (int) $t->target_halaman }}"
                                    data-target-baris="{{ (int) $t->target_baris }}"
                                    {{ (string) request('target_id') === (string) $t->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }} — {{ $t->kelasPengajian?->nama_kelas }} — {{ $t->materi?->nama_materi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="required-mark">*</span></label>
                        <input type="date" name="tanggal" id="jm_add_tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Sesi Pengajian</label>
                        <select name="sesi_pengajian" id="jm_add_sesi" class="form-select">
                            @foreach($sesiList as $sKey => $sLbl)
                                <option value="{{ $sKey }}" {{ old('sesi_pengajian', 'bada_maghrib') === $sKey ? 'selected' : '' }}>
                                    {{ $sLbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kelas Pengajian <span class="required-mark">*</span></label>
                        <select name="kelas_pengajian_id" id="jm_add_kelas" class="form-select" required onchange="renderAbsensiList('add', this.value)">
                            <option value="">Pilih Kelas</option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}" {{ (string) old('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                                    {{ $kls->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Materi Pondok <span class="required-mark">*</span></label>
                        <select name="pondok_materi_id" id="jm_add_materi" class="form-select" required onchange="syncJurnalMateriMode('add')">
                            <option value="" data-kategori="al_quran" data-halaman="604" data-baris="15" data-hal-awal="1" data-hal-akhir="604">Pilih Materi</option>
                            @foreach($materiList as $m)
                                <option value="{{ $m->id }}"
                                        data-kategori="{{ $m->kategori }}"
                                        data-halaman="{{ (int) $m->jumlah_halaman }}"
                                        data-baris="{{ (int) $m->jumlah_baris }}"
                                        data-hal-awal="{{ (int) $m->effective_halaman_awal }}"
                                        data-hal-akhir="{{ (int) $m->effective_halaman_akhir }}"
                                        {{ (string) old('pondok_materi_id') === (string) $m->id ? 'selected' : '' }}>
                                    {{ $m->nama_materi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Pengajar</label>
                        <input type="text" name="nama_pengajar" value="{{ old('nama_pengajar', auth()->user()?->name) }}" class="form-control" placeholder="Nama pengajar...">
                    </div>
                </div>

                {{-- SEKSI AL-QUR'AN: TARGET & CAPAIAN SURAT + AYAT --}}
                <div id="jm_add_quran_wrap" style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Nama Surat</label>
                            <select name="target_surat_nomor" id="jm_add_target_surat" class="form-select" onchange="updateLiveIndikator('add')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}" data-ayat="{{ $srt['jumlah_ayat'] }}" data-hal-awal="{{ $srt['halaman_awal'] }}" data-hal-akhir="{{ $srt['halaman_akhir'] }}">
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Ayat</label>
                            <input type="number" name="target_ayat" id="jm_add_target_ayat" min="1" value="{{ old('target_ayat', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Nama Surat <span class="required-mark">*</span></label>
                            <select name="capaian_surat_nomor" id="jm_add_capaian_surat" class="form-select" onchange="updateLiveIndikator('add')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}" data-ayat="{{ $srt['jumlah_ayat'] }}" data-hal-awal="{{ $srt['halaman_awal'] }}" data-hal-akhir="{{ $srt['halaman_akhir'] }}">
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_ayat" id="jm_add_capaian_ayat" min="1" value="{{ old('capaian_ayat', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>
                </div>

                {{-- SEKSI AL-HADIST: TARGET & CAPAIAN HALAMAN + BARIS --}}
                <div id="jm_add_hadist_wrap" style="display:none; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Halaman</label>
                            <input type="number" name="target_halaman" id="jm_add_target_hal" min="0" value="{{ old('target_halaman', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Baris</label>
                            <input type="number" name="target_baris" id="jm_add_target_baris" min="0" value="{{ old('target_baris', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_halaman" id="jm_add_capaian_hal" min="0" value="{{ old('capaian_halaman', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Baris <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_baris" id="jm_add_capaian_baris" min="0" value="{{ old('capaian_baris', 1) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>
                </div>

                {{-- Live Preview Indikator Ketercapaian --}}
                <div class="jm-live-preview">
                    <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">Indikator Ketercapaian Materi</span>
                    <span id="jm_add_live_badge" class="jm-indikator-badge status-tercapai">✓ Tercapai</span>
                </div>

                {{-- SEKSI ABSENSI SANTRI PENGAJIAN --}}
                <div class="jm-absensi-box">
                    <div class="jm-absensi-header">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong style="font-size:12.5px; color:var(--text-primary);">Absensi Santri Pengajian</strong>
                            <span id="jm_add_absensi_count" style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8;">0 Santri</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="markAllHadir('add')" style="height:26px; padding:0 10px; font-size:11.5px;">
                            ✓ Hadirkan Semua
                        </button>
                    </div>
                    <div id="jm_add_absensi_container" class="jm-absensi-list">
                        <div style="padding:20px 14px; text-align:center; font-size:12.5px; color:var(--text-secondary);">
                            Pilih Kelas Pengajian terlebih dahulu untuk menampilkan daftar absensi santri.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" rows="2" class="form-control" placeholder="Catatan evaluasi pengajian kelas...">{{ old('catatan') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Jurnal & Absensi</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT & HAPUS JURNAL MENGAJI --}}
@foreach($jurnals as $item)
@php
    $existingAbsensiMap = $item->absensis->pluck('status', 'siswa_id')->toArray();
@endphp
<div class="app-modal" id="modalEditJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:640px;">
        <form action="{{ route('pondok.jurnal.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Edit Jurnal & Absensi Pengajian</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px; max-height:76vh; overflow-y:auto;">
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="required-mark">*</span></label>
                        <input type="date" name="tanggal" value="{{ \Carbon\Carbon::parse($item->tanggal)->format('Y-m-d') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sesi Pengajian</label>
                        <select name="sesi_pengajian" class="form-select">
                            @foreach($sesiList as $sKey => $sLbl)
                                <option value="{{ $sKey }}" {{ ($item->sesi_pengajian ?: 'bada_maghrib') === $sKey ? 'selected' : '' }}>
                                    {{ $sLbl }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas Pengajian <span class="required-mark">*</span></label>
                        <select name="kelas_pengajian_id" id="jm_edit_{{ $item->id }}_kelas" class="form-select" required onchange="renderAbsensiList('edit_{{ $item->id }}', this.value)">
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}" {{ (int) $item->kelas_pengajian_id === (int) $kls->id ? 'selected' : '' }}>
                                    {{ $kls->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Materi Pondok <span class="required-mark">*</span></label>
                        <select name="pondok_materi_id" id="jm_edit_{{ $item->id }}_materi" class="form-select" required onchange="syncJurnalMateriMode('edit_{{ $item->id }}')">
                            @foreach($materiList as $m)
                                <option value="{{ $m->id }}"
                                        data-kategori="{{ $m->kategori }}"
                                        data-halaman="{{ (int) $m->jumlah_halaman }}"
                                        data-baris="{{ (int) $m->jumlah_baris }}"
                                        data-hal-awal="{{ (int) $m->effective_halaman_awal }}"
                                        data-hal-akhir="{{ (int) $m->effective_halaman_akhir }}"
                                        {{ (int) $item->pondok_materi_id === (int) $m->id ? 'selected' : '' }}>
                                    {{ $m->nama_materi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pengajar</label>
                        <input type="text" name="nama_pengajar" value="{{ $item->nama_pengajar }}" class="form-control">
                    </div>
                </div>

                <div id="jm_edit_{{ $item->id }}_quran_wrap" style="display:{{ $item->materi?->isAlHadist() ? 'none' : 'grid' }}; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Nama Surat</label>
                            <select name="target_surat_nomor" id="jm_edit_{{ $item->id }}_target_surat" class="form-select" onchange="updateLiveIndikator('edit_{{ $item->id }}')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}" data-ayat="{{ $srt['jumlah_ayat'] }}" data-hal-awal="{{ $srt['halaman_awal'] }}" data-hal-akhir="{{ $srt['halaman_akhir'] }}" {{ (int) ($item->target_surat_nomor ?: 1) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Ayat</label>
                            <input type="number" name="target_ayat" id="jm_edit_{{ $item->id }}_target_ayat" min="1" value="{{ (int) ($item->target_ayat ?: 1) }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Nama Surat <span class="required-mark">*</span></label>
                            <select name="capaian_surat_nomor" id="jm_edit_{{ $item->id }}_capaian_surat" class="form-select" onchange="updateLiveIndikator('edit_{{ $item->id }}')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}" data-ayat="{{ $srt['jumlah_ayat'] }}" data-hal-awal="{{ $srt['halaman_awal'] }}" data-hal-akhir="{{ $srt['halaman_akhir'] }}" {{ (int) ($item->capaian_surat_nomor ?: 1) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_ayat" id="jm_edit_{{ $item->id }}_capaian_ayat" min="1" value="{{ (int) ($item->capaian_ayat ?: 1) }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>
                </div>

                <div id="jm_edit_{{ $item->id }}_hadist_wrap" style="display:{{ $item->materi?->isAlHadist() ? 'grid' : 'none' }}; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Halaman</label>
                            <input type="number" name="target_halaman" id="jm_edit_{{ $item->id }}_target_hal" min="0" value="{{ (int) $item->target_halaman }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Baris</label>
                            <input type="number" name="target_baris" id="jm_edit_{{ $item->id }}_target_baris" min="0" value="{{ (int) $item->target_baris }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_halaman" id="jm_edit_{{ $item->id }}_capaian_hal" min="0" value="{{ (int) $item->capaian_halaman }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Baris <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_baris" id="jm_edit_{{ $item->id }}_capaian_baris" min="0" value="{{ (int) $item->capaian_baris }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>
                </div>

                <div class="jm-live-preview">
                    <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">Indikator Ketercapaian Materi</span>
                    <span id="jm_edit_{{ $item->id }}_live_badge" class="jm-indikator-badge status-{{ $item->status_ketercapaian }}">
                        {{ $item->status_label }}
                    </span>
                </div>

                {{-- SEKSI ABSENSI SANTRI PENGAJIAN (EDIT) --}}
                <div class="jm-absensi-box">
                    <div class="jm-absensi-header">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <strong style="font-size:12.5px; color:var(--text-primary);">Absensi Santri Pengajian</strong>
                            <span id="jm_edit_{{ $item->id }}_absensi_count" style="font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8;">0 Santri</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="markAllHadir('edit_{{ $item->id }}')" style="height:26px; padding:0 10px; font-size:11.5px;">
                            ✓ Hadirkan Semua
                        </button>
                    </div>
                    <div id="jm_edit_{{ $item->id }}_absensi_container" class="jm-absensi-list" data-existing='@json($existingAbsensiMap)'></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" rows="2" class="form-control">{{ $item->catatan }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="app-modal" id="modalHapusJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:420px;">
        <form action="{{ route('pondok.jurnal.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h3 class="modal-title">Hapus Jurnal Mengaji</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body" style="text-align:left;">
                <p style="margin:0; font-size:13.5px;">
                    Yakin ingin menghapus jurnal mengaji kelas <strong>{{ $item->kelasPengajian?->nama_kelas }}</strong> tanggal <strong>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</strong>?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
var SANTRI_BY_KELAS = @json($santriByKelas ?? []);

function getMateriKategori(prefix) {
    var sel = document.getElementById('jm_' + prefix + '_materi');
    if (!sel) return 'al_quran';
    var opt = sel.options[sel.selectedIndex];
    return (opt && opt.getAttribute('data-kategori')) || 'al_quran';
}

function filterJurnalSuratOptions(selectEl, halAwal, halAkhir) {
    if (!selectEl) return;
    var firstValid = null;
    var currentValid = false;

    for (var i = 0; i < selectEl.options.length; i++) {
        var o = selectEl.options[i];
        var sAwal = parseInt(o.getAttribute('data-hal-awal') || '1', 10) || 1;
        var sAkhir = parseInt(o.getAttribute('data-hal-akhir') || '604', 10) || 604;
        var inRange = (sAkhir >= halAwal && sAwal <= halAkhir);
        o.hidden = !inRange;
        o.disabled = !inRange;
        if (inRange) {
            if (firstValid === null) firstValid = o.value;
            if (o.value === selectEl.value) currentValid = true;
        }
    }

    if (!currentValid && firstValid !== null) {
        selectEl.value = firstValid;
    }
}

function syncJurnalMateriMode(prefix) {
    var sel = document.getElementById('jm_' + prefix + '_materi');
    var opt = sel && sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex] : null;
    var kat = (opt && opt.getAttribute('data-kategori')) || 'al_quran';
    var halAwal = parseInt((opt && opt.getAttribute('data-hal-awal')) || '1', 10) || 1;
    var halAkhir = parseInt((opt && opt.getAttribute('data-hal-akhir')) || '604', 10) || 604;

    var qWrap = document.getElementById('jm_' + prefix + '_quran_wrap');
    var hWrap = document.getElementById('jm_' + prefix + '_hadist_wrap');
    if (qWrap) qWrap.style.display = (kat === 'al_hadist') ? 'none' : 'grid';
    if (hWrap) hWrap.style.display = (kat === 'al_hadist') ? 'grid' : 'none';

    if (kat !== 'al_hadist') {
        filterJurnalSuratOptions(document.getElementById('jm_' + prefix + '_target_surat'), halAwal, halAkhir);
        filterJurnalSuratOptions(document.getElementById('jm_' + prefix + '_capaian_surat'), halAwal, halAkhir);
    }

    updateLiveIndikator(prefix);
}

function updateLiveIndikator(prefix) {
    var kat = getMateriKategori(prefix);
    var cmp = 0;

    if (kat === 'al_hadist') {
        var tHal = parseInt((document.getElementById('jm_' + prefix + '_target_hal') || {}).value || '0', 10) || 0;
        var cHal = parseInt((document.getElementById('jm_' + prefix + '_capaian_hal') || {}).value || '0', 10) || 0;
        var tBaris = parseInt((document.getElementById('jm_' + prefix + '_target_baris') || {}).value || '0', 10) || 0;
        var cBaris = parseInt((document.getElementById('jm_' + prefix + '_capaian_baris') || {}).value || '0', 10) || 0;

        if (cHal !== tHal) {
            cmp = cHal > tHal ? 1 : -1;
        } else if (cBaris !== tBaris) {
            cmp = cBaris > tBaris ? 1 : -1;
        }
    } else {
        var tSurat = parseInt((document.getElementById('jm_' + prefix + '_target_surat') || {}).value || '1', 10) || 1;
        var cSurat = parseInt((document.getElementById('jm_' + prefix + '_capaian_surat') || {}).value || '1', 10) || 1;
        var tAyat = parseInt((document.getElementById('jm_' + prefix + '_target_ayat') || {}).value || '1', 10) || 1;
        var cAyat = parseInt((document.getElementById('jm_' + prefix + '_capaian_ayat') || {}).value || '1', 10) || 1;

        if (cSurat !== tSurat) {
            cmp = cSurat > tSurat ? 1 : -1;
        } else if (cAyat !== tAyat) {
            cmp = cAyat > tAyat ? 1 : -1;
        }
    }

    var badge = document.getElementById('jm_' + prefix + '_live_badge');
    if (!badge) return;

    badge.className = 'jm-indikator-badge ';
    if (cmp > 0) {
        badge.className += 'status-terlampaui';
        badge.textContent = '↑ Terlampaui';
    } else if (cmp < 0) {
        badge.className += 'status-kurang';
        badge.textContent = '! Kurang';
    } else {
        badge.className += 'status-tercapai';
        badge.textContent = '✓ Tercapai';
    }
}

function renderAbsensiList(prefix, kelasId, overrideMap) {
    var container = document.getElementById('jm_' + prefix + '_absensi_container');
    var countBadge = document.getElementById('jm_' + prefix + '_absensi_count');
    if (!container) return;

    var existingMap = overrideMap || {};
    if (!overrideMap && container.getAttribute('data-existing')) {
        try {
            existingMap = JSON.parse(container.getAttribute('data-existing')) || {};
        } catch (e) {
            existingMap = {};
        }
    }

    var list = (kelasId && SANTRI_BY_KELAS[kelasId]) ? SANTRI_BY_KELAS[kelasId] : [];
    if (countBadge) {
        countBadge.textContent = list.length + ' Santri';
    }

    if (!kelasId) {
        container.innerHTML = '<div style="padding:20px 14px; text-align:center; font-size:12.5px; color:var(--text-secondary);">Pilih Kelas Pengajian terlebih dahulu untuk menampilkan daftar absensi santri.</div>';
        return;
    }

    if (list.length === 0) {
        container.innerHTML = '<div style="padding:20px 14px; text-align:center; font-size:12.5px; color:var(--text-secondary);">Belum ada santri yang di-plot pada kelas pengajian ini.</div>';
        return;
    }

    var statuses = [
        { val: 'hadir',     lbl: 'H', title: 'Hadir' },
        { val: 'terlambat', lbl: 'T', title: 'Terlambat' },
        { val: 'izin',      lbl: 'I', title: 'Izin' },
        { val: 'sakit',     lbl: 'S', title: 'Sakit' },
        { val: 'alpa',      lbl: 'A', title: 'Alpa' }
    ];

    var html = '';
    for (var i = 0; i < list.length; i++) {
        var sw = list[i];
        var chosen = existingMap[sw.id] || sw.default_status || 'hadir';
        var infoBadge = '';
        if (sw.info_izin) {
            infoBadge = '<span style="font-size:10.5px; font-weight:600; color:#b45309; background:#fef3c7; padding:1px 6px; border-radius:999px; margin-left:6px;">' + sw.info_izin + '</span>';
        }

        html += '<div class="jm-absensi-row">';
        html += '  <div style="min-width:0; flex:1;">';
        html += '    <div style="font-size:13px; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + (i + 1) + '. ' + sw.nama_lengkap + infoBadge + '</div>';
        html += '  </div>';
        html += '  <div class="jm-status-pills">';
        for (var j = 0; j < statuses.length; j++) {
            var st = statuses[j];
            var checked = (chosen === st.val) ? 'checked' : '';
            html += '<label class="jm-pill-label" title="' + st.title + '">';
            html += '  <input type="radio" name="absensi[' + sw.id + ']" value="' + st.val + '" ' + checked + '>';
            html += '  <span class="jm-pill-btn">' + st.lbl + '</span>';
            html += '</label>';
        }
        html += '  </div>';
        html += '</div>';
    }

    container.innerHTML = html;
}

function markAllHadir(prefix) {
    var container = document.getElementById('jm_' + prefix + '_absensi_container');
    if (!container) return;
    var radios = container.querySelectorAll('input[type="radio"][value="hadir"]');
    for (var i = 0; i < radios.length; i++) {
        radios[i].checked = true;
    }
}

function applySelectedTargetRef(prefix) {
    var refSel = document.getElementById('jm_' + prefix + '_target_ref');
    if (!refSel || !refSel.value) return;
    var opt = refSel.options[refSel.selectedIndex];
    if (!opt) return;

    var elTgl = document.getElementById('jm_' + prefix + '_tanggal');
    var elSesi = document.getElementById('jm_' + prefix + '_sesi');
    var elKls = document.getElementById('jm_' + prefix + '_kelas');
    var elMat = document.getElementById('jm_' + prefix + '_materi');
    var elTSurat = document.getElementById('jm_' + prefix + '_target_surat');
    var elTAyat = document.getElementById('jm_' + prefix + '_target_ayat');
    var elCSurat = document.getElementById('jm_' + prefix + '_capaian_surat');
    var elCAyat = document.getElementById('jm_' + prefix + '_capaian_ayat');
    var elTHal = document.getElementById('jm_' + prefix + '_target_hal');
    var elTBar = document.getElementById('jm_' + prefix + '_target_baris');
    var elCHal = document.getElementById('jm_' + prefix + '_capaian_hal');
    var elCBar = document.getElementById('jm_' + prefix + '_capaian_baris');

    if (elTgl && opt.getAttribute('data-tanggal')) elTgl.value = opt.getAttribute('data-tanggal');
    if (elSesi && opt.getAttribute('data-sesi')) elSesi.value = opt.getAttribute('data-sesi');
    if (elKls && opt.getAttribute('data-kelas')) {
        elKls.value = opt.getAttribute('data-kelas');
        renderAbsensiList(prefix, elKls.value);
    }
    if (elMat && opt.getAttribute('data-materi')) elMat.value = opt.getAttribute('data-materi');

    syncJurnalMateriMode(prefix);

    var srt = opt.getAttribute('data-surat') || '1';
    var ayt = opt.getAttribute('data-ayat') || '1';
    if (elTSurat) elTSurat.value = srt;
    if (elTAyat) elTAyat.value = ayt;
    if (elCSurat) elCSurat.value = srt;
    if (elCAyat) elCAyat.value = ayt;

    if (elTHal) elTHal.value = opt.getAttribute('data-target-hal') || 1;
    if (elTBar) elTBar.value = opt.getAttribute('data-target-baris') || 1;
    if (elCHal) elCHal.value = opt.getAttribute('data-target-hal') || 1;
    if (elCBar) elCBar.value = opt.getAttribute('data-target-baris') || 1;

    updateLiveIndikator(prefix);
}

document.addEventListener('DOMContentLoaded', function () {
    syncJurnalMateriMode('add');
    var addKls = document.getElementById('jm_add_kelas');
    if (addKls && addKls.value) {
        renderAbsensiList('add', addKls.value);
    }

    @foreach($jurnals as $item)
        syncJurnalMateriMode('edit_{{ $item->id }}');
        renderAbsensiList('edit_{{ $item->id }}', '{{ $item->kelas_pengajian_id }}');
    @endforeach

    @if(request()->filled('target_id'))
        applySelectedTargetRef('add');
        var modal = document.getElementById('modalTambahJurnalMengaji');
        if (modal) modal.classList.add('active');
    @endif
});
</script>
@endsection
