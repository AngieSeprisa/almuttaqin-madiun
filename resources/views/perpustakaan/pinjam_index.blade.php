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
                       class="form-control filter-search-input" placeholder="Cari peminjam, buku, atau kode pinjam...">
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
    <div class="form-section-card" style="padding:0; overflow:hidden;">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:52px; text-align:center;">No</th>
                        <th>Kode Pinjam</th>
                        <th>Nama Peminjam</th>
                        <th>Buku yang Dipinjam</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas / Tanggal Kembali</th>
                        <th style="text-align:center;">Status</th>
                        <th style="width:160px; text-align:right; padding-right:20px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($peminjamans as $index => $item)
                        @php
                            $effStatus = $item->effective_status;
                        @endphp
                        <tr>
                            <td style="text-align:center; color:var(--text-muted); font-size:12.5px;">
                                {{ $peminjamans->firstItem() + $index }}
                            </td>
                            <td>
                                <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; font-weight:700; font-size:12px; font-family:monospace;">
                                    {{ $item->kode_pinjam }}
                                </span>
                            </td>
                            <td>
                                <div class="font-medium text-primary" style="font-weight:700; font-size:14px;">{{ $item->nama_peminjam }}</div>
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
                                            {{ $item->buku->kode_buku }}
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
                            <td style="text-align:center;">
                                @if ($effStatus === 'dikembalikan')
                                    <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#ecfdf5; color:#047857; font-size:11.5px; font-weight:700;">
                                        Sudah Dikembalikan
                                    </span>
                                @elseif ($effStatus === 'terlambat')
                                    <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#fef2f2; color:#dc2626; font-size:11.5px; font-weight:700;">
                                        Terlambat
                                    </span>
                                @else
                                    <span style="display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11.5px; font-weight:700;">
                                        Sedang Dipinjam
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:right; padding-right:20px;">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                                    {{-- Tandai Sudah Dikembalikan --}}
                                    @if ($item->status !== 'dikembalikan')
                                        <form action="{{ route('perpustakaan.pinjam.kembali', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Tandai buku ini sudah dikembalikan oleh {{ addslashes($item->nama_peminjam) }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Tandai Sudah Dikembalikan" style="padding:5px 8px; color:#059669; border-color:#a7f3d0; background:#ecfdf5;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit --}}
                                    <button type="button"
                                            class="btn btn-sm btn-secondary"
                                            title="Edit Peminjaman"
                                            style="padding:5px 8px; color:#d97706; border-color:#fde68a; background:#fffbeb;"
                                            onclick='openEditPinjamModal(@json($item))'>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>

                                    {{-- Hapus --}}
                                    <form action="{{ route('perpustakaan.pinjam.destroy', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus data peminjaman {{ addslashes($item->kode_pinjam) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-secondary" title="Hapus Data" style="padding:5px 8px; color:#dc2626; border-color:#fecaca; background:#fef2f2;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding: 48px 20px;">
                                <div style="color: var(--text-muted); display:flex; flex-direction:column; align-items:center; gap:10px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:42px;height:42px; opacity:0.5;">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    </svg>
                                    <div style="font-weight:600; font-size:14.5px; color:var(--text-primary);">Belum ada data peminjaman buku</div>
                                    <div style="font-size:13px;">Klik tombol <strong>Pinjam Buku</strong> untuk mencatat peminjaman baru.</div>
                                    <a href="{{ route('perpustakaan.pinjam.create') }}" class="btn btn-primary" style="margin-top:6px;">
                                        + Pinjam Buku
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($peminjamans->hasPages())
        <div style="padding: 16px 20px; border-top: 1px solid var(--border-color);">
            {{ $peminjamans->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal Edit Peminjaman --}}
<div id="modalEditPinjam" class="modal-backdrop" style="display:none;">
    <div class="modal-content" style="max-width: 520px;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Data Peminjaman</h3>
            <button type="button" class="btn-close" onclick="closeEditPinjamModal()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form id="formEditPinjam" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body" style="display:flex; flex-direction:column; gap:14px;">
                <div class="form-group">
                    <label class="form-label required">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" id="edit_nama_peminjam" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kelas / Kamar</label>
                    <input type="text" name="kelas_kamar" id="edit_kelas_kamar" class="form-control" placeholder="Contoh: Kelas 8A • Kamar Al-Fatih">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label required">Tanggal Pinjam</label>
                        <input type="date" name="tanggal_pinjam" id="edit_tanggal_pinjam" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estimasi / Batas Kembali</label>
                        <input type="date" name="tanggal_jatuh_tempo" id="edit_tanggal_jatuh_tempo" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label required">Status Peminjaman</label>
                    <select name="status" id="edit_status" class="form-select" required>
                        <option value="dipinjam">Sedang Dipinjam</option>
                        <option value="dikembalikan">Sudah Dikembalikan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" id="edit_catatan" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditPinjamModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openEditPinjamModal(item) {
        const modal = document.getElementById('modalEditPinjam');
        const form = document.getElementById('formEditPinjam');

        form.action = `{{ url('perpustakaan/pinjam') }}/${item.id}`;
        document.getElementById('edit_nama_peminjam').value = item.nama_peminjam || '';
        document.getElementById('edit_kelas_kamar').value = item.kelas_kamar || '';
        document.getElementById('edit_tanggal_pinjam').value = item.tanggal_pinjam ? item.tanggal_pinjam.substring(0, 10) : '';
        document.getElementById('edit_tanggal_jatuh_tempo').value = item.tanggal_jatuh_tempo ? item.tanggal_jatuh_tempo.substring(0, 10) : '';
        document.getElementById('edit_status').value = item.status || 'dipinjam';
        document.getElementById('edit_catatan').value = item.catatan || '';

        modal.style.display = 'flex';
    }

    function closeEditPinjamModal() {
        document.getElementById('modalEditPinjam').style.display = 'none';
    }
</script>
@endpush
@endsection

