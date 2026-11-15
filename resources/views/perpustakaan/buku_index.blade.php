@extends('layouts.app')

@section('title', 'Daftar Buku')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('perpustakaan.buku.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Buku</span>
    </a>
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- Alert Notifikasi --}}
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

    {{-- Kartu Ringkasan Statistik --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:var(--text-secondary);">Total Judul Buku</div>
            <div style="font-size:26px; font-weight:800; color:var(--text-primary); margin-top:4px;">{{ number_format($totalJudul) }}</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#047857;">Stok Tersedia</div>
            <div style="font-size:26px; font-weight:800; color:#047857; margin-top:4px;">{{ number_format($totalTersedia) }}</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#2563eb;">Sedang Dipinjam</div>
            <div style="font-size:26px; font-weight:800; color:#2563eb; margin-top:4px;">{{ number_format($totalDipinjam) }}</div>
        </div>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('perpustakaan.buku.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Ketersediaan --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="ketersediaan" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Ketersediaan</option>
                        <option value="tersedia" {{ request('ketersediaan') === 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                        <option value="habis" {{ request('ketersediaan') === 'habis' ? 'selected' : '' }}>Stok Habis / Dipinjam Semua</option>
                    </select>
                </div>

                @if(isset($kategoriList) && $kategoriList->count() > 0)
                <div class="filter-select-wrap">
                    <select name="kategori" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        @foreach($kategoriList as $kat)
                            <option value="{{ $kat }}" {{ request('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(request('search') || request('kategori') || request('ketersediaan'))
                <a href="{{ route('perpustakaan.buku.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari nama buku, kode buku, atau barcode...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Daftar Buku --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px;">No</th>
                        <th>Kode Buku</th>
                        <th>Nama Buku</th>
                        <th>Kategori / Rak</th>
                        <th>Stok</th>
                        <th style="text-align:center; width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bukus as $idx => $item)
                    <tr>
                        <td>{{ $bukus->firstItem() + $idx }}</td>
                        <td>
                            <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-family:monospace; font-size:12.5px; font-weight:700;">
                                {{ $item->kode_buku ?: $item->barcode }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $item->judul_buku }}</div>
                            @if($item->penulis || $item->penerbit)
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                    {{ implode(' • ', array_filter([$item->penulis, $item->penerbit])) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($item->kategori || $item->rak)
                                <div style="font-size:12.5px; font-weight:600; color:var(--text-primary);">{{ $item->kategori ?: '-' }}</div>
                                @if($item->rak)
                                    <div style="font-size:11.5px; color:var(--text-muted);">Rak: {{ $item->rak }}</div>
                                @endif
                            @else
                                <span style="color:var(--text-muted); font-size:12px;">-</span>
                            @endif
                        </td>
                        <td>
                            @if($item->stok_tersedia > 0)
                                <span class="status-pill status-sudah_kembali" style="font-size:11.5px;">
                                    Tersedia {{ $item->stok_tersedia }} / {{ $item->stok_total }}
                                </span>
                            @else
                                <span class="status-pill status-melewati_batas" style="font-size:11.5px;">
                                    Habis 0 / {{ $item->stok_total }}
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                @if($item->stok_tersedia > 0)
                                <a href="{{ route('perpustakaan.pinjam.create', ['buku_id' => $item->id]) }}"
                                   class="action-btn action-btn-success"
                                   title="Pinjam Buku Ini"
                                   aria-label="Pinjam Buku {{ $item->judul_buku }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                                    </svg>
                                </a>
                                @endif

                                <button type="button"
                                        class="action-btn action-btn-edit"
                                        data-modal-target="modalEditBuku{{ $item->id }}"
                                        title="Edit Buku"
                                        aria-label="Edit Buku {{ $item->judul_buku }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>

                                <form action="{{ route('perpustakaan.buku.destroy', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="action-btn action-btn-delete js-swal-confirm"
                                            data-title="Hapus Buku?"
                                            data-html="Apakah Anda yakin ingin menghapus buku <strong>{{ $item->judul_buku }}</strong> - {{ $item->kode_buku }}?"
                                            data-icon="warning"
                                            data-confirm-text="Ya, Hapus"
                                            data-confirm-color="#DC2626"
                                            data-cancel-text="Batal"
                                            title="Hapus Buku"
                                            aria-label="Hapus Buku {{ $item->judul_buku }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Modal Edit Buku --}}
                            <div class="app-modal" id="modalEditBuku{{ $item->id }}" role="dialog" aria-modal="true">
                                <div class="modal-dialog" style="max-width:520px;">
                                    <form action="{{ route('perpustakaan.buku.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h3 class="modal-title">Edit Data Buku</h3>
                                            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
                                        </div>
                                        <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:12px;">
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Kode Buku <span class="required-mark">*</span></label>
                                                <input type="text" name="kode_buku" value="{{ $item->kode_buku ?: $item->barcode }}" class="form-control" style="font-family:monospace; font-weight:700;" required>
                                            </div>
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Nama Buku <span class="required-mark">*</span></label>
                                                <input type="text" name="judul_buku" value="{{ $item->judul_buku }}" class="form-control" required>
                                            </div>
                                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Penulis / Pengarang</label>
                                                    <input type="text" name="penulis" value="{{ $item->penulis }}" class="form-control">
                                                </div>
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Penerbit</label>
                                                    <input type="text" name="penerbit" value="{{ $item->penerbit }}" class="form-control">
                                                </div>
                                            </div>
                                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Kategori</label>
                                                    <input type="text" name="kategori" value="{{ $item->kategori }}" class="form-control" placeholder="Umum / Kitab">
                                                </div>
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Jumlah Stok <span class="required-mark">*</span></label>
                                                    <input type="number" name="stok_total" min="1" value="{{ $item->stok_total }}" class="form-control" required>
                                                </div>
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Rak / Lokasi</label>
                                                    <input type="text" name="rak" value="{{ $item->rak }}" class="form-control" placeholder="Rak A1">
                                                </div>
                                            </div>
                                            <div class="form-group" style="margin:0;">
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:36px 16px; text-align:center;">
                            <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:14px;">Belum ada data buku</div>
                            <a href="{{ route('perpustakaan.buku.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:15px;height:15px;">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Tambah Buku</span>
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bukus->hasPages())
        <div style="padding:12px 16px; border-top:1px solid var(--border-subtle);">
            {{ $bukus->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

