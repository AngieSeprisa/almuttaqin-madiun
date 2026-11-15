@extends('layouts.app')

@section('title', 'Pinjam Buku')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('perpustakaan.buku.index') }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <span>Daftar Buku</span>
    </a>
    <a href="{{ route('perpustakaan.pinjam.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Pinjam Buku</span>
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
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:14px;">
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:var(--text-secondary);">Total Peminjaman</div>
            <div style="font-size:26px; font-weight:800; color:var(--text-primary); margin-top:4px;">{{ number_format($totalTransaksi) }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Seluruh riwayat pinjam buku</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#2563eb;">Sedang Dipinjam</div>
            <div style="font-size:26px; font-weight:800; color:#2563eb; margin-top:4px;">{{ number_format($sedangDipinjam) }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Belum dikembalikan</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#047857;">Sudah Dikembalikan</div>
            <div style="font-size:26px; font-weight:800; color:#047857; margin-top:4px;">{{ number_format($sudahKembali) }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Selesai dikembalikan</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#dc2626;">Melewati Jatuh Tempo</div>
            <div style="font-size:26px; font-weight:800; color:#dc2626; margin-top:4px;">{{ number_format($terlambatCount) }}</div>
            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Perlu tindak lanjut pengembalian</div>
        </div>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('perpustakaan.pinjam.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Status --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="status" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="dipinjam" {{ request('status') === 'dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                        <option value="terlambat" {{ request('status') === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="dikembalikan" {{ request('status') === 'dikembalikan' ? 'selected' : '' }}>Sudah Dikembalikan</option>
                    </select>
                </div>

                @if(request('search') || request('status'))
                <a href="{{ route('perpustakaan.pinjam.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari peminjam, buku, atau kode buku...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Data Peminjaman --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px;">No</th>
                        <th>Kode Pinjam</th>
                        <th>Nama Peminjam</th>
                        <th>Buku yang Dipinjam</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas / Tanggal Kembali</th>
                        <th>Status</th>
                        <th style="text-align:center; width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $index => $item)
                        @php
                            $effStatus = $item->effective_status;
                        @endphp
                        <tr>
                            <td>{{ $peminjamans->firstItem() + $index }}</td>
                            <td>
                                <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-weight:700; font-size:12px; font-family:monospace;">
                                    {{ $item->kode_pinjam }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $item->nama_peminjam }}</div>
                                @if ($item->kelas_kamar)
                                    <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;">{{ $item->kelas_kamar }}</div>
                                @endif
                                @if ($item->catatan)
                                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Catatan: {{ $item->catatan }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($item->buku)
                                    <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">
                                        {{ $item->buku->judul_buku }}
                                    </div>
                                    <div style="display:flex; align-items:center; gap:6px; margin-top:3px; flex-wrap:wrap;">
                                        <span style="font-size:11.5px; font-family:monospace; color:#475569; background:#f1f5f9; padding:1px 6px; border-radius:4px;">
                                            {{ $item->buku->kode_buku ?: $item->buku->barcode }}
                                        </span>
                                        @if ($item->jumlah > 1)
                                            <span style="font-size:11.5px; font-weight:600; color:#1d4ed8;">
                                                • {{ $item->jumlah }} eks
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:var(--text-muted); font-style:italic;">Buku telah dihapus</span>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                {{ $item->tanggal_pinjam ? $item->tanggal_pinjam->translatedFormat('d M Y') : '-' }}
                            </td>
                            <td style="white-space:nowrap;">
                                @if ($item->status === 'dikembalikan' && $item->tanggal_kembali)
                                    <div style="font-size:12.5px; font-weight:600; color:#047857;">
                                        Kembali: {{ $item->tanggal_kembali->translatedFormat('d M Y') }}
                                    </div>
                                @elseif ($item->tanggal_jatuh_tempo)
                                    <div style="font-size:12.5px; font-weight:600; color:{{ $effStatus === 'terlambat' ? '#dc2626' : 'var(--text-secondary)' }};">
                                        Batas: {{ $item->tanggal_jatuh_tempo->translatedFormat('d M Y') }}
                                    </div>
                                @else
                                    <span style="color:var(--text-muted); font-size:12.5px;">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($effStatus === 'dikembalikan')
                                    <span class="status-pill status-sudah_kembali" style="font-size:11.5px;">
                                        Sudah Dikembalikan
                                    </span>
                                @elseif ($effStatus === 'terlambat')
                                    <span class="status-pill status-melewati_batas" style="font-size:11.5px;">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="status-pill status-sambang" style="font-size:11.5px;">
                                        Sedang Dipinjam
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <div class="action-btn-group">
                                    {{-- Tandai Sudah Dikembalikan --}}
                                    @if ($item->status !== 'dikembalikan')
                                        <form action="{{ route('perpustakaan.pinjam.kembali', $item->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button"
                                                    class="action-btn action-btn-success js-swal-confirm"
                                                    data-title="Tandai Buku Sudah Dikembalikan?"
                                                    data-html="Konfirmasi bahwa <strong>{{ $item->nama_peminjam }}</strong> telah mengembalikan buku <strong>{{ $item->buku?->judul_buku ?? '-' }}</strong>?"
                                                    data-icon="question"
                                                    data-confirm-text="Ya, Sudah Kembali"
                                                    data-confirm-color="#10B981"
                                                    data-cancel-text="Batal"
                                                    title="Tandai Sudah Dikembalikan"
                                                    aria-label="Tandai Sudah Dikembalikan">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit --}}
                                    <button type="button"
                                            class="action-btn action-btn-edit"
                                            data-modal-target="modalEditPinjam{{ $item->id }}"
                                            title="Edit Peminjaman"
                                            aria-label="Edit Peminjaman {{ $item->nama_peminjam }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    {{-- Hapus --}}
                                    <form action="{{ route('perpustakaan.pinjam.destroy', $item->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="action-btn action-btn-delete js-swal-confirm"
                                                data-title="Hapus Data Peminjaman?"
                                                data-html="Apakah Anda yakin ingin menghapus data peminjaman <strong>{{ $item->kode_pinjam }}</strong> atas nama <strong>{{ $item->nama_peminjam }}</strong>?"
                                                data-icon="warning"
                                                data-confirm-text="Ya, Hapus"
                                                data-confirm-color="#DC2626"
                                                data-cancel-text="Batal"
                                                title="Hapus Data"
                                                aria-label="Hapus Data {{ $item->kode_pinjam }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>

                                {{-- Modal Edit Peminjaman --}}
                                <div class="app-modal" id="modalEditPinjam{{ $item->id }}" role="dialog" aria-modal="true">
                                    <div class="modal-dialog" style="max-width:520px;">
                                        <form action="{{ route('perpustakaan.pinjam.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h3 class="modal-title">Edit Data Peminjaman</h3>
                                                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
                                            </div>
                                            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:12px;">
                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Nama Peminjam <span class="required-mark">*</span></label>
                                                    <input type="text" name="nama_peminjam" value="{{ $item->nama_peminjam }}" class="form-control" required>
                                                </div>

                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Kelas / Kamar</label>
                                                    <input type="text" name="kelas_kamar" value="{{ $item->kelas_kamar }}" class="form-control" placeholder="Contoh: Kelas 8A • Kamar Al-Fatih">
                                                </div>

                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                                    <div class="form-group" style="margin:0;">
                                                        <label class="form-label">Tanggal Pinjam <span class="required-mark">*</span></label>
                                                        <input type="date" name="tanggal_pinjam" value="{{ $item->tanggal_pinjam ? $item->tanggal_pinjam->format('Y-m-d') : '' }}" class="form-control" required>
                                                    </div>
                                                    <div class="form-group" style="margin:0;">
                                                        <label class="form-label">Estimasi / Batas Kembali</label>
                                                        <input type="date" name="tanggal_jatuh_tempo" value="{{ $item->tanggal_jatuh_tempo ? $item->tanggal_jatuh_tempo->format('Y-m-d') : '' }}" class="form-control">
                                                    </div>
                                                </div>

                                                <div class="form-group" style="margin:0;">
                                                    <label class="form-label">Status Peminjaman <span class="required-mark">*</span></label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="dipinjam" {{ $item->status === 'dipinjam' ? 'selected' : '' }}>Sedang Dipinjam</option>
                                                        <option value="dikembalikan" {{ $item->status === 'dikembalikan' ? 'selected' : '' }}>Sudah Dikembalikan</option>
                                                    </select>
                                                </div>

                                                <div class="form-group" style="margin:0;">
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:36px 16px; text-align:center;">
                                <div style="font-size:14px; font-weight:700; color:var(--text-primary); margin-bottom:4px;">Belum ada data peminjaman buku</div>
                                <div style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">Klik tombol <strong>Pinjam Buku</strong> untuk mencatat peminjaman buku baru.</div>
                                <a href="{{ route('perpustakaan.pinjam.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:15px;height:15px;">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>Pinjam Buku</span>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($peminjamans->hasPages())
        <div style="padding:12px 16px; border-top:1px solid var(--border-subtle);">
            {{ $peminjamans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
