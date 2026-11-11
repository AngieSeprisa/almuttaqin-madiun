@extends('layouts.app')

@section('title', 'Jurnal Mengaji Pondok')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
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
            <span class="jm-stat-sub">Pencapaian tercatat</span>
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

    {{-- Filter Jurnal Mengaji --}}
    <div class="form-section-card" style="padding:14px 16px;">
        <form action="{{ route('pondok.jurnal.index') }}" method="GET" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" style="width:auto; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Filter Tanggal">

                <select name="kelas_pengajian_id" class="form-select" style="width:auto; min-width:175px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Filter Kelas">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $kls)
                        <option value="{{ $kls->id }}" {{ (string) request('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                            {{ $kls->nama_kelas }}
                        </option>
                    @endforeach
                </select>

                <select name="status_ketercapaian" class="form-select" style="width:auto; min-width:165px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Filter Indikator">
                    <option value="">Semua Indikator</option>
                    <option value="tercapai" {{ request('status_ketercapaian') === 'tercapai' ? 'selected' : '' }}>Tercapai</option>
                    <option value="kurang" {{ request('status_ketercapaian') === 'kurang' ? 'selected' : '' }}>Kurang</option>
                    <option value="terlampaui" {{ request('status_ketercapaian') === 'terlampaui' ? 'selected' : '' }}>Terlampaui</option>
                </select>

                <select name="kategori" class="form-select" style="width:auto; min-width:140px; height:36px; font-size:13px;" onchange="this.form.submit()" aria-label="Filter Kategori">
                    <option value="">Semua Kategori</option>
                    <option value="al_quran" {{ request('kategori') === 'al_quran' ? 'selected' : '' }}>Al-Qur'an</option>
                    <option value="al_hadist" {{ request('kategori') === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
                </select>

                @if(request()->hasAny(['tanggal', 'kelas_pengajian_id', 'status_ketercapaian', 'kategori', 'search']))
                    <a href="{{ route('pondok.jurnal.index') }}" class="btn btn-secondary" style="height:36px; padding:0 12px; display:inline-flex; align-items:center;">Reset</a>
                @endif
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kelas / materi / ustadz..." style="height:36px; font-size:13px; width:200px;">
                <button type="submit" class="btn btn-primary" style="height:36px; padding:0 12px;">Cari</button>
            </div>
        </form>
    </div>

    {{-- Tabel Pencapaian Pengajian Per Kelas --}}
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
                        <th>Catatan</th>
                        <th style="width:105px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnals as $idx => $item)
                    @php
                        $dt = \Carbon\Carbon::parse($item->tanggal);
                        $hari = $hariIndo[$dt->format('l')] ?? $dt->format('l');
                    @endphp
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $hari }}, {{ $dt->format('d/m/Y') }}</div>
                            <span style="font-size:12px; color:var(--text-secondary);">{{ $item->nama_pengajar ?: '-' }}</span>
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
                        <td style="font-size:12px; color:var(--text-secondary);">
                            {{ $item->catatan ?: '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditJurnal{{ $item->id }}" title="Edit Jurnal">
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
                        <td colspan="9" style="text-align:center; padding:44px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <span style="font-size:14px; font-weight:600;">Belum ada data jurnal mengaji yang dicatat</span>
                                <span style="font-size:12.5px;">Catat pencapaian pengajian per kelas untuk melihat indikator Tercapai, Kurang, atau Terlampaui.</span>
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

{{-- MODAL INPUT JURNAL MENGAJI --}}
<div class="app-modal" id="modalTambahJurnalMengaji" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:580px;">
        <form action="{{ route('pondok.jurnal.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Input Jurnal Mengaji & Pencapaian Kelas</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                {{-- Pilih dari Target Pengajian yang sudah dijadwalkan --}}
                <div class="form-group">
                    <label class="form-label">Acuan Target Pengajian (Opsional - Otomatis Isi Kelas & Target)</label>
                    <select name="target_pengajian_id" id="jm_add_target_ref" class="form-select" onchange="applySelectedTargetRef('add')">
                        <option value="">-- Pilih Target Pengajian Terjadwal / Isi Manual di Bawah --</option>
                        @foreach($targetList as $t)
                            <option value="{{ $t->id }}"
                                    data-tanggal="{{ \Carbon\Carbon::parse($t->tanggal)->format('Y-m-d') }}"
                                    data-kelas="{{ $t->kelas_pengajian_id }}"
                                    data-materi="{{ $t->pondok_materi_id }}"
                                    data-kategori="{{ $t->materi?->kategori ?? 'al_quran' }}"
                                    data-target-hal="{{ (int) $t->target_halaman }}"
                                    data-target-baris="{{ (int) $t->target_baris }}"
                                    {{ (string) request('target_id') === (string) $t->id ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::parse($t->tanggal)->format('d/m/Y') }} — {{ $t->kelasPengajian?->nama_kelas }} — {{ $t->materi?->nama_materi }} (Target: {{ $t->target_label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal Pengajian <span class="required-mark">*</span></label>
                        <input type="date" name="tanggal" id="jm_add_tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kelas Pengajian <span class="required-mark">*</span></label>
                        <select name="kelas_pengajian_id" id="jm_add_kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
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
                            <option value="" data-kategori="al_quran">-- Pilih Materi --</option>
                            @foreach($materiList as $m)
                                <option value="{{ $m->id }}"
                                        data-kategori="{{ $m->kategori }}"
                                        {{ (string) old('pondok_materi_id') === (string) $m->id ? 'selected' : '' }}>
                                    [{{ $m->kategori_label }}] {{ $m->nama_materi }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ustadz / Pengajar</label>
                        <input type="text" name="nama_pengajar" value="{{ old('nama_pengajar', auth()->user()?->name) }}" class="form-control" placeholder="Nama pengajar...">
                    </div>
                </div>

                {{-- Perbandingan Target vs Pencapaian --}}
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Target Halaman</label>
                            <input type="number" name="target_halaman" id="jm_add_target_hal" min="0" value="{{ old('target_halaman', 2) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                        <div class="form-group" id="jm_add_target_baris_wrap" style="margin:0; display:none;">
                            <label class="form-label" style="font-size:12px;">Target Baris (Al-Hadist)</label>
                            <input type="number" name="target_baris" id="jm_add_target_baris" min="0" value="{{ old('target_baris', 0) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Capaian Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_halaman" id="jm_add_capaian_hal" min="0" value="{{ old('capaian_halaman', 2) }}" class="form-control" required oninput="updateLiveIndikator('add')">
                        </div>
                        <div class="form-group" id="jm_add_capaian_baris_wrap" style="margin:0; display:none;">
                            <label class="form-label" style="font-size:12px;">Capaian Baris (Al-Hadist)</label>
                            <input type="number" name="capaian_baris" id="jm_add_capaian_baris" min="0" value="{{ old('capaian_baris', 0) }}" class="form-control" oninput="updateLiveIndikator('add')">
                        </div>
                    </div>
                </div>

                {{-- Live Preview Indikator Ketercapaian --}}
                <div class="jm-live-preview">
                    <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">Indikator Ketercapaian Materi:</span>
                    <span id="jm_add_live_badge" class="jm-indikator-badge status-tercapai">✓ Tercapai</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Evaluasi Pengajian (Opsional)</label>
                    <textarea name="catatan" rows="2" class="form-control" placeholder="Catatan kelancaran bacaan / kendala kelas...">{{ old('catatan') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Jurnal Mengaji</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT & HAPUS JURNAL MENGAJI --}}
@foreach($jurnals as $item)
<div class="app-modal" id="modalEditJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:560px;">
        <form action="{{ route('pondok.jurnal.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Edit Jurnal Mengaji</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal Pengajian <span class="required-mark">*</span></label>
                        <input type="date" name="tanggal" value="{{ \Carbon\Carbon::parse($item->tanggal)->format('Y-m-d') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas Pengajian <span class="required-mark">*</span></label>
                        <select name="kelas_pengajian_id" class="form-select" required>
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
                                        {{ (int) $item->pondok_materi_id === (int) $m->id ? 'selected' : '' }}>
                                    [{{ $m->kategori_label }}] {{ $m->nama_materi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ustadz / Pengajar</label>
                        <input type="text" name="nama_pengajar" value="{{ $item->nama_pengajar }}" class="form-control">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; padding:12px; border-radius:12px; background:var(--bg-card-header); border:1px solid var(--border-light);">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#64748b; text-transform:uppercase;">Target Materi</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Target Halaman</label>
                            <input type="number" name="target_halaman" id="jm_edit_{{ $item->id }}_target_hal" min="0" value="{{ (int) $item->target_halaman }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                        <div class="form-group" id="jm_edit_{{ $item->id }}_target_baris_wrap" style="margin:0; {{ $item->materi?->isAlHadist() ? '' : 'display:none;' }}">
                            <label class="form-label" style="font-size:12px;">Target Baris (Al-Hadist)</label>
                            <input type="number" name="target_baris" id="jm_edit_{{ $item->id }}_target_baris" min="0" value="{{ (int) $item->target_baris }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <strong style="font-size:12px; color:#2563eb; text-transform:uppercase;">Pencapaian Kelas</strong>
                        <div class="form-group" style="margin:0;">
                            <label class="form-label" style="font-size:12px;">Capaian Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="capaian_halaman" id="jm_edit_{{ $item->id }}_capaian_hal" min="0" value="{{ (int) $item->capaian_halaman }}" class="form-control" required oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                        <div class="form-group" id="jm_edit_{{ $item->id }}_capaian_baris_wrap" style="margin:0; {{ $item->materi?->isAlHadist() ? '' : 'display:none;' }}">
                            <label class="form-label" style="font-size:12px;">Capaian Baris (Al-Hadist)</label>
                            <input type="number" name="capaian_baris" id="jm_edit_{{ $item->id }}_capaian_baris" min="0" value="{{ (int) $item->capaian_baris }}" class="form-control" oninput="updateLiveIndikator('edit_{{ $item->id }}')">
                        </div>
                    </div>
                </div>

                <div class="jm-live-preview">
                    <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">Indikator Ketercapaian Materi:</span>
                    <span id="jm_edit_{{ $item->id }}_live_badge" class="jm-indikator-badge status-{{ $item->status_ketercapaian }}">
                        {{ $item->status_label }}
                    </span>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan Evaluasi Pengajian (Opsional)</label>
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
function getMateriKategori(prefix) {
    var sel = document.getElementById('jm_' + prefix + '_materi');
    if (!sel) return 'al_quran';
    var opt = sel.options[sel.selectedIndex];
    return (opt && opt.getAttribute('data-kategori')) || 'al_quran';
}

function syncJurnalMateriMode(prefix) {
    var kat = getMateriKategori(prefix);
    var tWrap = document.getElementById('jm_' + prefix + '_target_baris_wrap');
    var cWrap = document.getElementById('jm_' + prefix + '_capaian_baris_wrap');
    if (tWrap) tWrap.style.display = (kat === 'al_hadist') ? '' : 'none';
    if (cWrap) cWrap.style.display = (kat === 'al_hadist') ? '' : 'none';
    updateLiveIndikator(prefix);
}

function updateLiveIndikator(prefix) {
    var kat = getMateriKategori(prefix);
    var tHal = parseInt((document.getElementById('jm_' + prefix + '_target_hal') || {}).value || '0', 10) || 0;
    var cHal = parseInt((document.getElementById('jm_' + prefix + '_capaian_hal') || {}).value || '0', 10) || 0;
    var tBaris = parseInt((document.getElementById('jm_' + prefix + '_target_baris') || {}).value || '0', 10) || 0;
    var cBaris = parseInt((document.getElementById('jm_' + prefix + '_capaian_baris') || {}).value || '0', 10) || 0;

    var cmp = 0;
    if (kat === 'al_hadist') {
        if (cHal !== tHal) {
            cmp = cHal > tHal ? 1 : -1;
        } else if (cBaris !== tBaris) {
            cmp = cBaris > tBaris ? 1 : -1;
        }
    } else {
        if (cHal !== tHal) {
            cmp = cHal > tHal ? 1 : -1;
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

function applySelectedTargetRef(prefix) {
    var refSel = document.getElementById('jm_' + prefix + '_target_ref');
    if (!refSel || !refSel.value) return;
    var opt = refSel.options[refSel.selectedIndex];
    if (!opt) return;

    var elTgl = document.getElementById('jm_' + prefix + '_tanggal');
    var elKls = document.getElementById('jm_' + prefix + '_kelas');
    var elMat = document.getElementById('jm_' + prefix + '_materi');
    var elTHal = document.getElementById('jm_' + prefix + '_target_hal');
    var elTBar = document.getElementById('jm_' + prefix + '_target_baris');
    var elCHal = document.getElementById('jm_' + prefix + '_capaian_hal');
    var elCBar = document.getElementById('jm_' + prefix + '_capaian_baris');

    if (elTgl && opt.getAttribute('data-tanggal')) elTgl.value = opt.getAttribute('data-tanggal');
    if (elKls && opt.getAttribute('data-kelas')) elKls.value = opt.getAttribute('data-kelas');
    if (elMat && opt.getAttribute('data-materi')) elMat.value = opt.getAttribute('data-materi');
    if (elTHal) elTHal.value = opt.getAttribute('data-target-hal') || 0;
    if (elTBar) elTBar.value = opt.getAttribute('data-target-baris') || 0;
    if (elCHal) elCHal.value = opt.getAttribute('data-target-hal') || 0;
    if (elCBar) elCBar.value = opt.getAttribute('data-target-baris') || 0;

    syncJurnalMateriMode(prefix);
}

document.addEventListener('DOMContentLoaded', function () {
    syncJurnalMateriMode('add');

    var refSel = document.getElementById('jm_add_target_ref');
    if (refSel && refSel.value) {
        applySelectedTargetRef('add');
        var modalAdd = document.getElementById('modalTambahJurnalMengaji');
        if (modalAdd) {
            modalAdd.classList.add('is-active');
            document.body.style.overflow = 'hidden';
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var m = document.getElementById(this.getAttribute('data-modal-target'));
            if (m) {
                m.classList.add('is-active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var m = this.closest('.app-modal');
            if (m) {
                m.classList.remove('is-active');
                document.body.style.overflow = '';
            }
        });
    });

    document.querySelectorAll('.app-modal').forEach(function (m) {
        m.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('is-active');
                document.body.style.overflow = '';
            }
        });
    });
});
</script>
@endsection

