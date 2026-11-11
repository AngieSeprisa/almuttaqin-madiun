@extends('layouts.app')

@section('title', 'Target Pengajian Pondok')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('pondok.jurnal.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; text-decoration:none;">
        <span>Jurnal Mengaji</span>
    </a>
    <button type="button" class="btn btn-primary" data-modal-target="modalTambahTarget" style="display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Target</span>
    </button>
</div>
@endsection

@push('styles')
<style>
    .tp-badge-quran {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #d1fae5;
    }

    .tp-badge-hadist {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
    }

    .tp-target-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 12.5px;
        font-weight: 700;
        color: #0f172a;
    }

    .dark-mode .tp-target-pill {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    .indikator-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
    }

    .indikator-pill.is-tercapai {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }

    .indikator-pill.is-kurang {
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }

    .indikator-pill.is-terlampaui {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
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

    {{-- Filter Card (Otomatis diintegrasikan ke dalam Toolbar Tabel Untitled UI oleh app.js) --}}
    <div class="filter-card" style="display:none;">
        <form action="{{ route('pondok.target.index') }}" method="GET">
            <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" aria-label="Tanggal" onchange="this.form.submit()">

            <select name="kelas_pengajian_id" class="form-select" aria-label="Kelas Pengajian" onchange="this.form.submit()">
                <option value="">Semua Kelas Pengajian</option>
                @foreach($kelasList as $kls)
                    <option value="{{ $kls->id }}" {{ (string) request('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                        {{ $kls->nama_kelas }}
                    </option>
                @endforeach
            </select>

            <select name="kategori" class="form-select" aria-label="Kategori Materi" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <option value="al_quran" {{ request('kategori') === 'al_quran' ? 'selected' : '' }}>Al-Qur'an</option>
                <option value="al_hadist" {{ request('kategori') === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
            </select>

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari materi atau kelas...">
            <a href="{{ route('pondok.target.index') }}" class="filter-btn-reset" title="Reset Filter">Reset</a>
        </form>
    </div>

    {{-- Tabel Target Pengajian (Custom Blade Tailwind Table - Untitled UI Style) --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th>Tanggal Target</th>
                        <th>Kelas Pengajian</th>
                        <th>Materi</th>
                        <th>Target Materi</th>
                        <th>Status Jurnal</th>
                        <th>Keterangan</th>
                        <th style="width:110px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $idx => $item)
                    @php
                        $dt = \Carbon\Carbon::parse($item->tanggal);
                        $hari = $hariIndo[$dt->format('l')] ?? $dt->format('l');
                        $lastJurnal = $item->jurnals->sortByDesc('id')->first();
                    @endphp
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $hari }}, {{ $dt->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <span class="badge-lembaga" style="background:rgba(37, 99, 235, 0.1); color:#1d4ed8; font-weight:700;">
                                {{ $item->kelasPengajian?->nama_kelas ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; flex-direction:column; gap:4px; align-items:flex-start;">
                                <span style="font-weight:700; color:var(--text-primary);">{{ $item->materi?->nama_materi ?? '-' }}</span>
                                @if($item->materi?->isAlHadist())
                                    <span class="tp-badge-hadist">Al-Hadist</span>
                                @else
                                    <span class="tp-badge-quran">Al-Qur'an</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="tp-target-pill">{{ $item->target_label }}</span>
                        </td>
                        <td>
                            @if($lastJurnal)
                                <span class="indikator-pill is-{{ $lastJurnal->status_ketercapaian }}">
                                    @if($lastJurnal->status_ketercapaian === 'tercapai')
                                        ✓ Tercapai
                                    @elseif($lastJurnal->status_ketercapaian === 'terlampaui')
                                        ↑ Terlampaui
                                    @else
                                        ! Kurang
                                    @endif
                                </span>
                            @else
                                <a href="{{ route('pondok.jurnal.index', ['target_id' => $item->id]) }}"
                                   class="btn btn-sm btn-secondary"
                                   style="font-size:11.5px; padding:4px 10px; text-decoration:none;">
                                    + Catat Jurnal
                                </a>
                            @endif
                        </td>
                        <td style="font-size:12.5px; color:var(--text-secondary);">
                            {{ $item->keterangan ?: '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditTarget{{ $item->id }}" title="Edit Target">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button type="button" class="action-btn action-btn-delete" data-modal-target="modalHapusTarget{{ $item->id }}" title="Hapus Target">
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
                        <td colspan="8" style="text-align:center; padding:44px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <span style="font-size:14px; font-weight:600;">Belum ada target pengajian yang dijadwalkan</span>
                                <span style="font-size:12.5px;">Tetapkan target pengajian per tanggal untuk masing-masing kelas.</span>
                                <button type="button" class="btn btn-sm btn-primary" data-modal-target="modalTambahTarget" style="margin-top:4px;">
                                    + Tambah Target Pengajian
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

{{-- MODAL TAMBAH TARGET PENGAJIAN --}}
<div class="app-modal" id="modalTambahTarget" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:540px;">
        <form action="{{ route('pondok.target.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Tambah Target Pengajian Per Kelas</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal Target <span class="required-mark">*</span></label>
                        <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kelas Pengajian <span class="required-mark">*</span></label>
                        <select name="kelas_pengajian_id" class="form-select" required>
                            <option value="">Pilih Kelas</option>
                            @foreach($kelasList as $kls)
                                <option value="{{ $kls->id }}" {{ (string) old('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                                    {{ $kls->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Materi Pondok <span class="required-mark">*</span></label>
                    <select name="pondok_materi_id" id="target_add_materi" class="form-select" required onchange="syncTargetForm('add')">
                        <option value="" data-kategori="al_quran" data-hal-awal="1" data-hal-akhir="604" data-halaman="604" data-baris="16">Pilih Materi</option>
                        @foreach($materiList as $m)
                            <option value="{{ $m->id }}"
                                    data-kategori="{{ $m->kategori }}"
                                    data-hal-awal="{{ $m->effective_halaman_awal }}"
                                    data-hal-akhir="{{ $m->effective_halaman_akhir }}"
                                    data-halaman="{{ (int) $m->jumlah_halaman }}"
                                    data-baris="{{ (int) $m->jumlah_baris }}"
                                    {{ (string) old('pondok_materi_id') === (string) $m->id ? 'selected' : '' }}>
                                {{ $m->nama_materi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SEKSI AL-QUR'AN: AWAL SURAT & AKHIR SURAT --}}
                <div id="target_add_quran_section" style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Awal Surat <span class="required-mark">*</span></label>
                            <select name="surat_nomor" id="target_add_surat" class="form-select" onchange="onAwalSuratChanged('add')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}"
                                            data-ayat="{{ $srt['jumlah_ayat'] }}"
                                            data-hal-awal="{{ $srt['halaman_awal'] }}"
                                            data-hal-akhir="{{ $srt['halaman_akhir'] }}"
                                            {{ (int) old('surat_nomor', 1) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="ayat" id="target_add_ayat" min="1" max="7" value="{{ old('ayat', 1) }}" class="form-control">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Akhir Surat <span class="required-mark">*</span></label>
                            <select name="surat_akhir_nomor" id="target_add_surat_akhir" class="form-select" onchange="updateAyatLimit('add')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}"
                                            data-ayat="{{ $srt['jumlah_ayat'] }}"
                                            data-hal-awal="{{ $srt['halaman_awal'] }}"
                                            data-hal-akhir="{{ $srt['halaman_akhir'] }}"
                                            {{ (int) old('surat_akhir_nomor', old('surat_nomor', 1)) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="ayat_akhir" id="target_add_ayat_akhir" min="1" max="7" value="{{ old('ayat_akhir', old('ayat', 1)) }}" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- SEKSI AL-HADIST: HALAMAN & BARIS SESUAI MATERI PONDOK --}}
                <div id="target_add_hadist_section" style="display:none; flex-direction:column; gap:12px;">
                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="halaman" id="target_add_halaman" min="1" value="{{ old('halaman', 1) }}" class="form-control">
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Baris <span class="required-mark">*</span></label>
                            <input type="number" name="baris" id="target_add_baris" min="1" value="{{ old('baris', 1) }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-control" placeholder="Catatan target pengajian kelas...">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Target</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT & HAPUS TARGET --}}
@foreach($targets as $item)
<div class="app-modal" id="modalEditTarget{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:540px;">
        <form action="{{ route('pondok.target.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h3 class="modal-title">Edit Target Pengajian</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Tanggal Target <span class="required-mark">*</span></label>
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

                <div class="form-group">
                    <label class="form-label">Materi Pondok <span class="required-mark">*</span></label>
                    <select name="pondok_materi_id" id="target_edit_{{ $item->id }}_materi" class="form-select" required onchange="syncTargetForm('edit_{{ $item->id }}')">
                        @foreach($materiList as $m)
                            <option value="{{ $m->id }}"
                                    data-kategori="{{ $m->kategori }}"
                                    data-hal-awal="{{ $m->effective_halaman_awal }}"
                                    data-hal-akhir="{{ $m->effective_halaman_akhir }}"
                                    data-halaman="{{ (int) $m->jumlah_halaman }}"
                                    data-baris="{{ (int) $m->jumlah_baris }}"
                                    {{ (int) $item->pondok_materi_id === (int) $m->id ? 'selected' : '' }}>
                                {{ $m->nama_materi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SEKSI AL-QUR'AN --}}
                <div id="target_edit_{{ $item->id }}_quran_section" style="display:{{ $item->materi?->isAlHadist() ? 'none' : 'flex' }}; flex-direction:column; gap:12px;">
                    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Awal Surat <span class="required-mark">*</span></label>
                            <select name="surat_nomor" id="target_edit_{{ $item->id }}_surat" class="form-select" onchange="onAwalSuratChanged('edit_{{ $item->id }}')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}"
                                            data-ayat="{{ $srt['jumlah_ayat'] }}"
                                            data-hal-awal="{{ $srt['halaman_awal'] }}"
                                            data-hal-akhir="{{ $srt['halaman_akhir'] }}"
                                            {{ (int) ($item->surat_nomor ?: 1) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="ayat" id="target_edit_{{ $item->id }}_ayat" min="1" value="{{ (int) ($item->ayat ?: 1) }}" class="form-control">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Akhir Surat <span class="required-mark">*</span></label>
                            <select name="surat_akhir_nomor" id="target_edit_{{ $item->id }}_surat_akhir" class="form-select" onchange="updateAyatLimit('edit_{{ $item->id }}')">
                                @foreach($daftarSurat as $srt)
                                    <option value="{{ $srt['nomor'] }}"
                                            data-ayat="{{ $srt['jumlah_ayat'] }}"
                                            data-hal-awal="{{ $srt['halaman_awal'] }}"
                                            data-hal-akhir="{{ $srt['halaman_akhir'] }}"
                                            {{ (int) ($item->surat_akhir_nomor ?: ($item->surat_nomor ?: 1)) === (int) $srt['nomor'] ? 'selected' : '' }}>
                                        {{ $srt['nomor'] }}. {{ $srt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Ayat <span class="required-mark">*</span></label>
                            <input type="number" name="ayat_akhir" id="target_edit_{{ $item->id }}_ayat_akhir" min="1" value="{{ (int) ($item->ayat_akhir ?: ($item->ayat ?: 1)) }}" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- SEKSI AL-HADIST --}}
                <div id="target_edit_{{ $item->id }}_hadist_section" style="display:{{ $item->materi?->isAlHadist() ? 'flex' : 'none' }}; flex-direction:column; gap:12px;">
                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Halaman <span class="required-mark">*</span></label>
                            <input type="number" name="halaman" id="target_edit_{{ $item->id }}_halaman" min="1" max="{{ (int) ($item->materi?->jumlah_halaman ?: 999) }}" value="{{ (int) $item->target_halaman }}" class="form-control">
                        </div>

                        <div class="form-group" style="margin:0;">
                            <label class="form-label">Baris <span class="required-mark">*</span></label>
                            <input type="number" name="baris" id="target_edit_{{ $item->id }}_baris" min="1" max="{{ (int) ($item->materi?->jumlah_baris ?: 999) }}" value="{{ (int) $item->target_baris }}" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-control">{{ $item->keterangan }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="app-modal" id="modalHapusTarget{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:420px;">
        <form action="{{ route('pondok.target.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h3 class="modal-title">Hapus Target Pengajian</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body" style="text-align:left;">
                <p style="margin:0; font-size:13.5px;">
                    Yakin ingin menghapus target pengajian kelas <strong>{{ $item->kelasPengajian?->nama_kelas }}</strong> tanggal <strong>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</strong>?
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
function syncTargetForm(prefix) {
    var sel = document.getElementById('target_' + prefix + '_materi');
    var qSec = document.getElementById('target_' + prefix + '_quran_section');
    var hSec = document.getElementById('target_' + prefix + '_hadist_section');
    if (!sel || !qSec || !hSec) return;

    var opt = sel.options[sel.selectedIndex];
    var kat = opt ? opt.getAttribute('data-kategori') : 'al_quran';
    var maxHal = parseInt((opt && opt.getAttribute('data-halaman')) || '1', 10) || 1;
    var maxBar = parseInt((opt && opt.getAttribute('data-baris')) || '1', 10) || 1;
    var halAwal = parseInt((opt && opt.getAttribute('data-hal-awal')) || '1', 10) || 1;
    var halAkhir = parseInt((opt && opt.getAttribute('data-hal-akhir')) || '604', 10) || 604;

    var inHal = document.getElementById('target_' + prefix + '_halaman');
    var inBar = document.getElementById('target_' + prefix + '_baris');

    if (kat === 'al_hadist') {
        qSec.style.display = 'none';
        hSec.style.display = 'flex';
        if (inHal) {
            inHal.max = maxHal;
            if (parseInt(inHal.value || '0', 10) > maxHal) inHal.value = maxHal;
        }
        if (inBar) {
            inBar.max = maxBar;
            if (parseInt(inBar.value || '0', 10) > maxBar) inBar.value = maxBar;
        }
    } else {
        qSec.style.display = 'flex';
        hSec.style.display = 'none';
        filterSuratSelectByRange(document.getElementById('target_' + prefix + '_surat'), halAwal, halAkhir);
        filterSuratSelectByRange(document.getElementById('target_' + prefix + '_surat_akhir'), halAwal, halAkhir);
        updateAyatLimit(prefix);
    }
}

function filterSuratSelectByRange(selSurat, halAwal, halAkhir) {
    if (!selSurat) return;

    var firstValidVal = null;
    var currentValid = false;

    for (var i = 0; i < selSurat.options.length; i++) {
        var o = selSurat.options[i];
        var sAwal = parseInt(o.getAttribute('data-hal-awal') || '1', 10) || 1;
        var sAkhir = parseInt(o.getAttribute('data-hal-akhir') || '604', 10) || 604;
        var inRange = (sAkhir >= halAwal && sAwal <= halAkhir);
        o.hidden = !inRange;
        o.disabled = !inRange;
        if (inRange) {
            if (!firstValidVal) firstValidVal = o.value;
            if (o.value === selSurat.value) currentValid = true;
        }
    }

    if (!currentValid && firstValidVal) {
        selSurat.value = firstValidVal;
    }
    selSurat.dispatchEvent(new Event('fi-select:sync'));
}

function onAwalSuratChanged(prefix) {
    var selAwal = document.getElementById('target_' + prefix + '_surat');
    var selAkhir = document.getElementById('target_' + prefix + '_surat_akhir');
    if (selAwal && selAkhir) {
        selAkhir.value = selAwal.value;
        selAkhir.dispatchEvent(new Event('fi-select:sync'));
    }
    updateAyatLimit(prefix);
}

function updateAyatLimit(prefix) {
    var selSurat = document.getElementById('target_' + prefix + '_surat');
    var inAyat = document.getElementById('target_' + prefix + '_ayat');
    if (selSurat && inAyat) {
        var opt = selSurat.options[selSurat.selectedIndex];
        var maxAyat = parseInt((opt && opt.getAttribute('data-ayat')) || '286', 10) || 286;
        inAyat.max = maxAyat;
        if (parseInt(inAyat.value || '1', 10) > maxAyat) {
            inAyat.value = maxAyat;
        }
    }

    var selSuratAkhir = document.getElementById('target_' + prefix + '_surat_akhir');
    var inAyatAkhir = document.getElementById('target_' + prefix + '_ayat_akhir');
    if (selSuratAkhir && inAyatAkhir) {
        var optAkhir = selSuratAkhir.options[selSuratAkhir.selectedIndex];
        var maxAyatAkhir = parseInt((optAkhir && optAkhir.getAttribute('data-ayat')) || '286', 10) || 286;
        inAyatAkhir.max = maxAyatAkhir;
        if (parseInt(inAyatAkhir.value || '1', 10) > maxAyatAkhir) {
            inAyatAkhir.value = maxAyatAkhir;
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    syncTargetForm('add');
});
</script>
@endsection
