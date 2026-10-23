@extends('layouts.app')

@section('title', 'Siswa')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('siswa', 'create'))
<button type="button" class="btn btn-primary" data-modal-target="modalAddSiswa">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    Tambah Siswa
</button>
@endif
@endsection

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

                @if(request('jenjang') || request('kamar') || request('status') || request('search'))
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
                       class="form-control filter-search-input" placeholder="Cari nama santri / kamar...">
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
                        <th style="text-align:center; width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswa as $item)
                    <tr>
                        <td class="cell-nama">
                            <div>{{ $item->nama_lengkap }}</div>
                        </td>
                        <td>
                            @php
                                $j = $item->jenjang ?? ($item->pendaftaran?->jenjang ?? 'Reguler');
                                $badgeColor = match($j) {
                                    'SMP' => 'smp',
                                    'SMA' => 'sma',
                                    default => 'pondok',
                                };
                            @endphp
                            <span class="badge-lembaga badge-lembaga-{{ $badgeColor }}">{{ $j }}</span>
                        </td>
                        <td class="cell-kamar">{{ $item->kamar }}</td>
                        <td class="cell-status">
                            <span class="status-badge status-{{ strtolower($item->status ?? 'aktif') }}">
                                {{ ucfirst($item->status ?? 'aktif') }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                {{-- Tombol Lihat (Hanya Icon Mata -> Menampilkan Formulir Cetak Seperti Gambar 2) --}}
                                @php
                                    $targetCetakUrl = $item->pendaftaran_id
                                        ? route('pendaftaran.cetak', ['id' => $item->pendaftaran_id, 'from' => 'siswa'])
                                        : route('siswa.show', $item->id);
                                @endphp
                                <a href="{{ $targetCetakUrl }}" class="action-btn action-btn-view" title="Lihat Formulir Siswa" aria-label="Lihat Formulir {{ $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                {{-- Tombol Edit (Hanya Icon Pensil) --}}
                                @if(!auth()->check() || auth()->user()->canDo('siswa', 'edit'))
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEdit{{ $item->id }}" title="Edit Data Siswa" aria-label="Edit Data {{ $item->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                @endif

                                {{-- Tombol Hapus via SweetAlert2 --}}
                                @if(!auth()->check() || auth()->user()->canDo('siswa', 'delete'))
                                <form action="{{ route('siswa.destroy', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-delete js-swal-confirm"
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

                            {{-- MODAL EDIT SISWA --}}
                            <div class="app-modal" id="modalEdit{{ $item->id }}" role="dialog" aria-modal="true" aria-labelledby="modalEditTitle{{ $item->id }}">
                                <div class="modal-dialog">
                                    <form action="{{ route('siswa.update', $item->id) }}" method="POST">
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
                                                <h3 class="modal-title" id="modalEditTitle{{ $item->id }}">Edit Data Siswa</h3>
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
                                                <label for="edit_nama_{{ $item->id }}" class="form-label">Nama Lengkap <span class="required-mark">*</span></label>
                                                <input type="text" name="nama_lengkap" id="edit_nama_{{ $item->id }}" value="{{ old('nama_lengkap', $item->nama_lengkap) }}"
                                                       class="form-control" placeholder="" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_kamar_{{ $item->id }}" class="form-label">Kamar / Asrama <span class="required-mark">*</span></label>
                                                <input type="text" name="kamar" id="edit_kamar_{{ $item->id }}" value="{{ old('kamar', $item->kamar) }}"
                                                       class="form-control" placeholder="" required>
                                            </div>

                                            <div class="form-group">
                                                <label for="edit_status_{{ $item->id }}" class="form-label">Status Santri <span class="required-mark">*</span></label>
                                                <select name="status" id="edit_status_{{ $item->id }}" class="form-select" required>
                                                    <option value="aktif" {{ old('status', $item->status) === 'aktif' ? 'selected' : '' }}>Aktif (Di Pondok)</option>
                                                    <option value="pulang" {{ old('status', $item->status) === 'pulang' ? 'selected' : '' }}>Pulang (Izin / Libur)</option>
                                                    <option value="sakit" {{ old('status', $item->status) === 'sakit' ? 'selected' : '' }}>Sakit (Perawatan)</option>
                                                </select>
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
                        <td colspan="5" class="cell-empty" style="padding:40px 20px;">
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
</div>

{{-- MODAL TAMBAH SISWA BARU --}}
<div class="app-modal" id="modalAddSiswa" role="dialog" aria-modal="true" aria-labelledby="modalAddSiswaTitle">
    <div class="modal-dialog">
        <form action="{{ route('siswa.store') }}" method="POST">
            @csrf

            <div class="modal-header">
                <div class="modal-header-title">
                    <div class="modal-icon-badge view-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </div>
                    <h3 class="modal-title" id="modalAddSiswaTitle">Tambah Siswa Baru</h3>
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
                    <label for="add_nama" class="form-label">Nama Lengkap <span class="required-mark">*</span></label>
                    <input type="text" name="nama_lengkap" id="add_nama" value="{{ old('nama_lengkap') }}"
                           class="form-control" placeholder="" required>
                </div>

                <div class="form-group">
                    <label for="add_jenjang" class="form-label">Jenjang Pendidikan <span class="required-mark">*</span></label>
                    <select name="jenjang" id="add_jenjang" class="form-select" required>
                        <option value="SMP" {{ old('jenjang', ($activeRoom === 'smp' ? 'SMP' : '')) === 'SMP' ? 'selected' : '' }}>SMP Al-Muttaqin</option>
                        <option value="SMA" {{ old('jenjang', ($activeRoom === 'sma' ? 'SMA' : '')) === 'SMA' ? 'selected' : '' }}>SMA Al-Muttaqin</option>
                        <option value="Reguler" {{ old('jenjang', in_array($activeRoom, ['pondok', 'yayasan']) ? 'Reguler' : '') === 'Reguler' ? 'selected' : '' }}>Pondok Pesantren (Reguler)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_kamar" class="form-label">Kamar / Asrama <span class="required-mark">*</span></label>
                    <input type="text" name="kamar" id="add_kamar" value="{{ old('kamar') }}"
                           class="form-control" placeholder="" required>
                </div>

                <div class="form-group">
                    <label for="add_status" class="form-label">Status Santri <span class="required-mark">*</span></label>
                    <select name="status" id="add_status" class="form-select" required>
                        <option value="aktif" {{ old('status') === 'aktif' || !old('status') ? 'selected' : '' }}>Aktif (Di Pondok)</option>
                        <option value="pulang" {{ old('status') === 'pulang' ? 'selected' : '' }}>Pulang (Izin / Libur)</option>
                        <option value="sakit" {{ old('status') === 'sakit' ? 'selected' : '' }}>Sakit (Perawatan)</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Siswa</button>
            </div>
        </form>
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
});
</script>
@endsection
