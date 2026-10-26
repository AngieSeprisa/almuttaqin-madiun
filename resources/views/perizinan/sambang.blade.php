@extends('layouts.app')

@section('title', 'Perizinan Sambang')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('perizinan_sambang', 'create'))
<a href="{{ route('perizinan.sambang.create') }}" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    Input Perizinan
</a>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px;">

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


    {{-- Filter Dropdown & Search Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('perizinan.sambang') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Status --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="status" id="filter_status" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="sambang" {{ request('status') === 'sambang' ? 'selected' : '' }}>Sambang</option>
                        <option value="sudah_kembali" {{ request('status') === 'sudah_kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                        <option value="melewati_batas" {{ request('status') === 'melewati_batas' ? 'selected' : '' }}>Melewati Batas</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Kamar --}}
                @if(isset($kamars) && count($kamars) > 0)
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
                            <option value="{{ $k }}" {{ request('kamar') === $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
                @endif

                @if(request('status') || request('kamar') || request('search'))
                    <a href="{{ route('perizinan.sambang') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari nama santri / kamar / keterangan...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Perizinan Sambang --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nama Santri</th>
                        <th>Kamar</th>
                        <th>Tanggal Mulai Sambang</th>
                        <th>Tanggal Estimasi Kembali</th>
                        <th>Status</th>
                        <th style="text-align:center; width:140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perizinan as $item)
                    @php
                        $effectiveStatus = $item->effective_status;
                    @endphp
                    <tr>
                        <td class="cell-nama">
                            <div style="font-weight:600; color:var(--text-primary);">{{ $item->nama }}</div>
                            @if($item->keterangan)
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">{{ $item->keterangan }}</div>
                            @endif
                        </td>
                        <td class="cell-kamar">{{ $item->kamar }}</td>
                        <td>{{ $item->tanggal_mulai ? $item->tanggal_mulai->translatedFormat('d F Y') : '-' }}</td>
                        <td>{{ $item->tanggal_kembali ? $item->tanggal_kembali->translatedFormat('d F Y') : '-' }}</td>
                        <td>
                            @if($effectiveStatus === 'sudah_kembali')
                                <span class="status-pill status-sudah_kembali">Sudah Kembali</span>
                            @elseif($effectiveStatus === 'melewati_batas')
                                <span class="status-pill status-melewati_batas">Melewati Batas</span>
                            @else
                                <span class="status-pill status-sambang">Sambang</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                {{-- Tombol Tandai Kembali jika masih sambang / melewati batas --}}
                                @if($item->status !== 'sudah_kembali')
                                <form action="{{ route('perizinan.sambang.kembali', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" class="action-btn action-btn-success js-swal-confirm"
                                        data-title="Tandai Sudah Kembali?"
                                        data-html="Konfirmasi santri <strong>{{ $item->nama }}</strong> telah kembali ke pondok.<br><span style='font-size:13px; color:var(--text-muted);'>Status santri akan otomatis diperbarui menjadi <strong>Aktif</strong>.</span>"
                                        data-icon="question"
                                        data-confirm-text="Ya, Sudah Kembali"
                                        data-confirm-color="#10B981"
                                        data-cancel-text="Batal"
                                        title="Tandai Sudah Kembali" aria-label="Tandai Sudah Kembali">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </button>
                                </form>
                                @endif

                                {{-- Tombol Edit --}}
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditSambang{{ $item->id }}" title="Edit Perizinan" aria-label="Edit Perizinan {{ $item->nama }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>

                                {{-- Tombol Hapus via SweetAlert2 --}}
                                <form action="{{ route('perizinan.sambang.destroy', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-delete js-swal-confirm"
                                        data-title="Hapus Perizinan Sambang?"
                                        data-html="Apakah Anda yakin ingin menghapus perizinan sambang santri <strong>{{ $item->nama }}</strong>?<br><div style='padding:12px 14px; background:var(--nav-hover-bg); border-radius:10px; border:1px solid var(--border-light); margin-top:12px; font-size:13px; text-align:left; line-height:1.6;'><div>Kamar: <strong>{{ $item->kamar }}</strong></div><div>Mulai: <strong>{{ $item->tanggal_mulai ? $item->tanggal_mulai->format('d/m/Y') : '-' }}</strong></div><div>Status: <strong>{{ ucfirst(str_replace('_', ' ', $effectiveStatus)) }}</strong></div></div><div style='margin-top:10px; font-size:12.5px; color:#DC2626; font-weight:600;'>⚠️ Data yang dihapus tidak dapat dipulihkan.</div>"
                                        data-icon="warning"
                                        data-confirm-text="Ya, Hapus Data"
                                        data-confirm-color="#DC2626"
                                        data-cancel-text="Batal"
                                        title="Hapus Perizinan" aria-label="Hapus Perizinan {{ $item->nama }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {{-- MODAL EDIT PERIZINAN SAMBANG --}}
                            <div class="app-modal" id="modalEditSambang{{ $item->id }}" role="dialog" aria-modal="true" aria-labelledby="modalEditSambangTitle{{ $item->id }}">
                                <div class="modal-dialog">
                                    <form action="{{ route('perizinan.sambang.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="modal-header">
                                            <div class="modal-header-title">
                                                <div class="modal-icon-badge edit-badge">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="modal-title" id="modalEditSambangTitle{{ $item->id }}">Edit Perizinan Sambang</h3>
                                            </div>
                                            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup Modal">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="modal-body" style="text-align:left;">
                                            <div class="form-group">
                                                <label class="form-label">Nama Santri</label>
                                                <input type="text" class="form-control" value="{{ $item->nama }} (Kamar: {{ $item->kamar }})" readonly style="background-color:var(--nav-hover-bg);">
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_tgl_mulai_{{ $item->id }}" class="form-label">Tanggal Mulai Sambang <span class="required-mark">*</span></label>
                                                <input type="date" name="tanggal_mulai" id="edit_tgl_mulai_{{ $item->id }}" value="{{ $item->tanggal_mulai ? $item->tanggal_mulai->format('Y-m-d') : '' }}" class="form-control" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_tgl_kembali_{{ $item->id }}" class="form-label">Tanggal Estimasi Kembali <span class="required-mark">*</span></label>
                                                <input type="date" name="tanggal_kembali" id="edit_tgl_kembali_{{ $item->id }}" value="{{ $item->tanggal_kembali ? $item->tanggal_kembali->format('Y-m-d') : '' }}" class="form-control" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_status_{{ $item->id }}" class="form-label">Status Sambang <span class="required-mark">*</span></label>
                                                <select name="status" id="edit_status_{{ $item->id }}" class="form-select" required>
                                                    <option value="sambang" {{ $item->status === 'sambang' ? 'selected' : '' }}>Sambang</option>
                                                    <option value="sudah_kembali" {{ $item->status === 'sudah_kembali' ? 'selected' : '' }}>Sudah Kembali</option>
                                                    <option value="melewati_batas" {{ $item->status === 'melewati_batas' ? 'selected' : '' }}>Melewati Batas</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_ket_{{ $item->id }}" class="form-label">Keterangan</label>
                                                <input type="text" name="keterangan" id="edit_ket_{{ $item->id }}" value="{{ $item->keterangan }}" class="form-control">
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
                        <td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">
                            Belum ada data perizinan sambang.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tampilan Mobile Card List (Khusus Tampilan Layar HP Sesuai Gambar) --}}
    <div class="mobile-card-list">
        @forelse($perizinan as $item)
        @php
            $effectiveStatus = $item->effective_status;
            $statusKey = match($effectiveStatus) {
                'sudah_kembali' => 'sudah_kembali',
                'melewati_batas' => 'melewati_batas',
                default => 'sambang',
            };
            $statusLabel = match($effectiveStatus) {
                'sudah_kembali' => 'Sudah Kembali',
                'melewati_batas' => 'Melewati Batas',
                default => 'Sambang',
            };
            $mulaiText = $item->tanggal_mulai ? $item->tanggal_mulai->format('d M Y') : '-';
            $kembaliText = $item->tanggal_kembali ? $item->tanggal_kembali->format('d M Y') : '-';
        @endphp
        <div class="mobile-card-item">
            <div class="mobile-card-info">
                <div class="mobile-card-title-wrap">
                    <span class="mobile-card-title">{{ $item->nama }}</span>
                </div>

                <div class="mobile-card-subtitle">
                    Kamar: {{ $item->kamar }}{{ $item->keterangan ? ' • ' . $item->keterangan : '' }}
                </div>

                <div class="mobile-card-meta-grid">
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Mulai</span>
                        <span class="mobile-card-pill">{{ $mulaiText }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Estimasi Kembali</span>
                        <span class="mobile-card-pill">{{ $kembaliText }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">&nbsp;</span>
                        <span class="mobile-card-pill status-{{ $statusKey }}">{{ $statusLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="mobile-card-actions">
                <span class="mobile-card-action-title">Aksi</span>
                <div class="mobile-card-action-buttons">
                    {{-- Tombol Tandai Kembali --}}
                    @if($effectiveStatus !== 'sudah_kembali')
                    <form action="{{ route('perizinan.sambang.kembali', $item->id) }}" method="POST" style="display:inline; margin:0;">
                        @csrf
                        <button type="submit" class="mobile-btn-pastel mobile-btn-success" title="Tandai Sudah Kembali" aria-label="Tandai Kembali {{ $item->nama }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    @endif

                    {{-- Tombol Edit --}}
                    <button type="button" class="mobile-btn-pastel mobile-btn-edit" data-modal-target="modalEditSambang{{ $item->id }}" title="Edit Perizinan" aria-label="Edit Perizinan {{ $item->nama }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>

                    {{-- Tombol Hapus --}}
                    <form action="{{ route('perizinan.sambang.destroy', $item->id) }}" method="POST" style="display:inline; margin:0;">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="mobile-btn-pastel mobile-btn-delete js-swal-confirm"
                            data-title="Konfirmasi Hapus Perizinan?"
                            data-html="Apakah Anda yakin ingin menghapus perizinan sambang untuk santri <strong>{{ $item->nama }}</strong>?"
                            data-icon="warning"
                            data-confirm-text="Ya, Hapus"
                            data-confirm-color="#DC2626"
                            data-cancel-text="Batal"
                            title="Hapus Perizinan" aria-label="Hapus Perizinan {{ $item->nama }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="mobile-empty-card">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--text-muted);">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
            </svg>
            <span>Belum ada data perizinan sambang.</span>
        </div>
        @endforelse
    </div>
</div>
@endsection
