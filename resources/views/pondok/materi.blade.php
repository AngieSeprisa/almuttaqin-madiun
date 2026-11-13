@extends('layouts.app')

@section('title', 'Daftar Materi Pondok')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <form action="{{ route('pondok.materi.muat-standar') }}" method="POST" style="margin:0;">
        @csrf
        <button type="submit" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            <span>Muat Materi Standar</span>
        </button>
    </form>
    <button type="button" class="btn btn-primary" data-modal-target="modalTambahMateri" style="display:inline-flex; align-items:center; gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Materi</span>
    </button>
</div>
@endsection

@push('styles')
<style>
    .pm-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    @media (max-width: 768px) {
        .pm-stats-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
    }

    .pm-stat-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .pm-stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .pm-stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
        line-height: 1.2;
    }

    .pm-stat-sub {
        font-size: 11px;
        color: #94a3b8;
    }

    .badge-kategori-quran {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #d1fae5;
    }

    .badge-kategori-hadist {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #dbeafe;
    }

    .dark-mode .badge-kategori-quran {
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }

    .dark-mode .badge-kategori-hadist {
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
        border-color: rgba(59, 130, 246, 0.3);
    }

    .pm-volume-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 12.5px;
        font-weight: 700;
        color: #1e293b;
    }

    .dark-mode .pm-volume-pill {
        background: #0f172a;
        border-color: #334155;
        color: #f1f5f9;
    }

    .pm-range-tag {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 700;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .dark-mode .pm-range-tag {
        background: rgba(22, 163, 74, 0.16);
        color: #4ade80;
        border-color: rgba(22, 163, 74, 0.3);
    }
</style>
@endpush

@section('content')
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

    {{-- Ringkasan Statistik Materi --}}
    <div class="pm-stats-grid">
        <div class="pm-stat-card">
            <span class="pm-stat-label">Total Materi</span>
            <span class="pm-stat-value">{{ $totalMateri }}</span>
            <span class="pm-stat-sub">Al-Qur'an & Al-Hadist</span>
        </div>
        <div class="pm-stat-card">
            <span class="pm-stat-label" style="color:#047857;">Materi Al-Qur'an</span>
            <span class="pm-stat-value" style="color:#047857;">{{ $totalQuran }}</span>
            <span class="pm-stat-sub">Cepatan • Lambatan • Reguler</span>
        </div>
        <div class="pm-stat-card">
            <span class="pm-stat-label" style="color:#1d4ed8;">Materi Al-Hadist</span>
            <span class="pm-stat-value" style="color:#1d4ed8;">{{ $totalHadist }}</span>
            <span class="pm-stat-sub">Satuan: Halaman & Baris</span>
        </div>
    </div>

    {{-- Filter Card (Otomatis diintegrasikan ke dalam Toolbar Tabel Untitled UI oleh app.js) --}}
    <div class="filter-card" style="display:none;">
        <form action="{{ route('pondok.materi.index') }}" method="GET">
            <select name="kategori" class="form-select" aria-label="Kategori Materi" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                <option value="al_quran" {{ request('kategori') === 'al_quran' ? 'selected' : '' }}>Al-Qur'an</option>
                <option value="al_hadist" {{ request('kategori') === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode materi...">
            <a href="{{ route('pondok.materi.index') }}" class="filter-btn-reset" title="Reset Filter">Reset</a>
        </form>
    </div>

    {{-- Tabel Daftar Materi (Custom Blade Tailwind Table - Untitled UI Style) --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="width:110px;">Kode</th>
                        <th>Nama Materi</th>
                        <th style="width:150px;">Kategori</th>
                        <th>Detail Materi</th>
                        <th>Keterangan</th>
                        <th style="width:110px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materis as $idx => $item)
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">{{ $idx + 1 }}</td>
                        <td>
                            <span style="font-family:monospace; font-size:12px; font-weight:700; padding:3px 8px; border-radius:6px; background:var(--bg-page); border:1px solid var(--border-light);">
                                {{ $item->kode_materi ?: '-' }}
                            </span>
                        </td>
                        <td style="font-weight:700; color:var(--text-primary);">{{ $item->nama_materi }}</td>
                        <td>
                            @if($item->isAlHadist())
                                <span class="badge-kategori-hadist">Al-Hadist</span>
                            @else
                                <span class="badge-kategori-quran">Al-Qur'an</span>
                            @endif
                        </td>
                        <td>
                            @if($item->isAlHadist())
                                <span class="pm-volume-pill">
                                    <span>{{ $item->jumlah_halaman }} Halaman</span>
                                    <span style="color:#94a3b8;">•</span>
                                    <span style="color:#1d4ed8;">{{ (int) $item->jumlah_baris }} Baris</span>
                                </span>
                            @else
                                <div style="display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <span class="pm-volume-pill">
                                        <span>{{ $item->jumlah_halaman }} Halaman</span>
                                    </span>
                                    <span class="pm-range-tag">{{ $item->rentang_halaman_label }}</span>
                                </div>
                            @endif
                        </td>
                        <td style="font-size:12.5px; color:var(--text-secondary);">
                            {{ $item->keterangan ?: '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditMateri{{ $item->id }}" title="Edit Materi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                <button type="button" class="action-btn action-btn-delete" data-modal-target="modalHapusMateri{{ $item->id }}" title="Hapus Materi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:44px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <span style="font-size:14px; font-weight:600;">Belum ada daftar materi pondok</span>
                                <span style="font-size:12.5px;">Tambahkan materi Al-Qur'an atau Al-Hadist, atau muat daftar kitab standar.</span>
                                <div style="display:flex; gap:8px; margin-top:4px;">
                                    <button type="button" class="btn btn-sm btn-primary" data-modal-target="modalTambahMateri">
                                        + Tambah Materi
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL TAMBAH MATERI --}}
<div class="app-modal" id="modalTambahMateri" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:520px;">
        <form action="{{ route('pondok.materi.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <div class="modal-header-title">
                    <h3 class="modal-title">Tambah Materi Pondok</h3>
                </div>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div class="form-group">
                    <label class="form-label">Kategori Materi <span class="required-mark">*</span></label>
                    <select name="kategori" id="add_kategori" class="form-select" required onchange="toggleBarisField('add')">
                        <option value="al_quran" {{ old('kategori') === 'al_hadist' ? '' : 'selected' }}>Al-Qur'an</option>
                        <option value="al_hadist" {{ old('kategori') === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
                    </select>
                </div>

                {{-- Pilihan Paket Kurikulum Al-Qur'an (Cepatan 1-341, Lambatan 342-604, Reguler 1-604) --}}
                <div class="form-group" id="add_paket_quran_wrap">
                    <label class="form-label">Jenis Kelas Al-Qur'an</label>
                    <select name="paket_quran" id="add_paket_quran" class="form-select" onchange="applyPaketQuran('add')">
                        <option value="reguler" data-nama="Al-Qur'an Reguler" data-kode="QUR-REG" data-awal="1" data-akhir="604" data-jml="604">Reguler — Halaman 1 s/d 604</option>
                        <option value="cepatan" data-nama="Al-Qur'an Cepatan" data-kode="QUR-CPT" data-awal="1" data-akhir="341" data-jml="341">Cepatan — Halaman 1 s/d 341</option>
                        <option value="lambatan" data-nama="Al-Qur'an Lambatan" data-kode="QUR-LMB" data-awal="342" data-akhir="604" data-jml="263">Lambatan — Halaman 342 s/d 604</option>
                    </select>
                    <input type="hidden" name="halaman_awal" id="add_halaman_awal" value="1">
                    <input type="hidden" name="halaman_akhir" id="add_halaman_akhir" value="604">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Kode Materi</label>
                        <input type="text" name="kode_materi" id="add_kode_materi" value="{{ old('kode_materi') }}" class="form-control" placeholder="Otomatis">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Materi <span class="required-mark">*</span></label>
                        <input type="text" name="nama_materi" id="add_nama_materi" value="{{ old('nama_materi') }}" class="form-control" placeholder="Contoh: Al-Qur'an Cepatan / K. Sholah" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Jumlah Halaman <span class="required-mark">*</span></label>
                        <input type="number" name="jumlah_halaman" id="add_jumlah_halaman" min="1" value="{{ old('jumlah_halaman', 604) }}" class="form-control" required>
                    </div>

                    <div class="form-group" id="add_baris_wrap" style="{{ old('kategori') === 'al_hadist' ? '' : 'display:none;' }}">
                        <label class="form-label">Jumlah Baris per Halaman <span class="required-mark">*</span></label>
                        <input type="number" name="jumlah_baris" id="add_jumlah_baris" min="0" value="{{ old('jumlah_baris', 16) }}" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="form-control" placeholder="Catatan materi...">{{ old('keterangan') }}</textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Materi</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT & HAPUS PER ITEM --}}
@foreach($materis as $item)
<div class="app-modal" id="modalEditMateri{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:520px;">
        <form action="{{ route('pondok.materi.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div class="modal-header-title">
                    <h3 class="modal-title">Edit Materi Pondok</h3>
                </div>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div class="form-group">
                    <label class="form-label">Kategori Materi <span class="required-mark">*</span></label>
                    <select name="kategori" id="edit_kategori_{{ $item->id }}" class="form-select" required onchange="toggleBarisField('edit_{{ $item->id }}')">
                        <option value="al_quran" {{ $item->kategori === 'al_quran' ? 'selected' : '' }}>Al-Qur'an</option>
                        <option value="al_hadist" {{ $item->kategori === 'al_hadist' ? 'selected' : '' }}>Al-Hadist</option>
                    </select>
                </div>

                <div class="form-group" id="edit_{{ $item->id }}_paket_quran_wrap" style="{{ $item->isAlHadist() ? 'display:none;' : '' }}">
                    <label class="form-label">Jenis Kelas Al-Qur'an</label>
                    <select name="paket_quran" id="edit_{{ $item->id }}_paket_quran" class="form-select" onchange="applyPaketQuran('edit_{{ $item->id }}')">
                        <option value="reguler" data-nama="Al-Qur'an Reguler" data-kode="QUR-REG" data-awal="1" data-akhir="604" data-jml="604" {{ $item->paket_quran === 'reguler' ? 'selected' : '' }}>Reguler — Halaman 1 s/d 604</option>
                        <option value="cepatan" data-nama="Al-Qur'an Cepatan" data-kode="QUR-CPT" data-awal="1" data-akhir="341" data-jml="341" {{ ($item->paket_quran === 'cepatan' || $item->effective_halaman_akhir === 341) ? 'selected' : '' }}>Cepatan — Halaman 1 s/d 341</option>
                        <option value="lambatan" data-nama="Al-Qur'an Lambatan" data-kode="QUR-LMB" data-awal="342" data-akhir="604" data-jml="263" {{ ($item->paket_quran === 'lambatan' || $item->effective_halaman_awal === 342) ? 'selected' : '' }}>Lambatan — Halaman 342 s/d 604</option>
                    </select>
                    <input type="hidden" name="halaman_awal" id="edit_{{ $item->id }}_halaman_awal" value="{{ $item->effective_halaman_awal }}">
                    <input type="hidden" name="halaman_akhir" id="edit_{{ $item->id }}_halaman_akhir" value="{{ $item->effective_halaman_akhir }}">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Kode Materi</label>
                        <input type="text" name="kode_materi" id="edit_{{ $item->id }}_kode_materi" value="{{ $item->kode_materi }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Materi <span class="required-mark">*</span></label>
                        <input type="text" name="nama_materi" id="edit_{{ $item->id }}_nama_materi" value="{{ $item->nama_materi }}" class="form-control" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Jumlah Halaman <span class="required-mark">*</span></label>
                        <input type="number" name="jumlah_halaman" id="edit_{{ $item->id }}_jumlah_halaman" min="1" value="{{ $item->jumlah_halaman }}" class="form-control" required>
                    </div>

                    <div class="form-group" id="edit_{{ $item->id }}_baris_wrap" style="{{ $item->isAlHadist() ? '' : 'display:none;' }}">
                        <label class="form-label">Jumlah Baris per Halaman <span class="required-mark">*</span></label>
                        <input type="number" name="jumlah_baris" id="edit_{{ $item->id }}_jumlah_baris" min="0" value="{{ (int) $item->jumlah_baris }}" class="form-control">
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

<div class="app-modal" id="modalHapusMateri{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:420px;">
        <form action="{{ route('pondok.materi.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h3 class="modal-title">Hapus Materi</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body" style="text-align:left;">
                <p style="margin:0; font-size:13.5px;">
                    Yakin ingin menghapus materi <strong>{{ $item->nama_materi }}</strong>?
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
function toggleBarisField(prefix) {
    var sel = document.getElementById(prefix === 'add' ? 'add_kategori' : prefix + '_kategori');
    if (!sel && prefix.indexOf('edit_') === 0) {
        sel = document.getElementById('edit_kategori_' + prefix.replace('edit_', ''));
    }
    var wrap = document.getElementById(prefix + '_baris_wrap');
    var paketWrap = document.getElementById(prefix + '_paket_quran_wrap');
    var input = document.getElementById(prefix + '_jumlah_baris');
    if (!sel || !wrap) return;

    if (sel.value === 'al_hadist') {
        wrap.style.display = '';
        if (paketWrap) paketWrap.style.display = 'none';
        if (input) input.setAttribute('required', 'required');
    } else {
        wrap.style.display = 'none';
        if (paketWrap) paketWrap.style.display = '';
        if (input) input.removeAttribute('required');
    }
}

function applyPaketQuran(prefix) {
    var sel = document.getElementById(prefix + '_paket_quran');
    if (!sel) return;
    var opt = sel.options[sel.selectedIndex];
    if (!opt) return;

    var inHal = document.getElementById(prefix + '_jumlah_halaman');
    var inAwal = document.getElementById(prefix + '_halaman_awal');
    var inAkhir = document.getElementById(prefix + '_halaman_akhir');
    var inNama = document.getElementById(prefix + '_nama_materi');
    var inKode = document.getElementById(prefix + '_kode_materi');

    if (inHal) inHal.value = opt.getAttribute('data-jml') || 604;
    if (inAwal) inAwal.value = opt.getAttribute('data-awal') || 1;
    if (inAkhir) inAkhir.value = opt.getAttribute('data-akhir') || 604;
    if (prefix === 'add') {
        if (inNama && (!inNama.value || inNama.value.indexOf("Al-Qur'an") === 0)) {
            inNama.value = opt.getAttribute('data-nama') || '';
        }
        if (inKode && (!inKode.value || inKode.value.indexOf('QUR-') === 0)) {
            inKode.value = opt.getAttribute('data-kode') || '';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    toggleBarisField('add');
});
</script>
@endsection
