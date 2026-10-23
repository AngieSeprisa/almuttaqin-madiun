@extends('layouts.app')

@section('title', 'Rekap Jurnal Pembelajaran')

@section('header-actions')
<a href="{{ route('sekolah.jurnal') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    <span>Input Jurnal Baru</span>
</a>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%;">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- Unified Filter Bar --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('sekolah.rekap-jurnal') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Kelas --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="kelas" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}" {{ request('kelas') === $k ? 'selected' : '' }}>
                                Kelas {{ $k }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Semester --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <select name="semester" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Semester</option>
                        <option value="Semester Ganjil" {{ request('semester') === 'Semester Ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                        <option value="Semester Genap" {{ request('semester') === 'Semester Genap' ? 'selected' : '' }}>Semester Genap</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Tanggal --}}
                <div class="filter-select-wrap">
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}"
                           class="form-control" style="font-size:13px; padding:6px 12px; border-radius:10px; width:auto;"
                           onchange="this.form.submit()">
                </div>

                {{-- Reset Button jika ada filter aktif --}}
                @if(request()->hasAny(['kelas', 'semester', 'tanggal', 'search']))
                <a href="{{ route('sekolah.rekap-jurnal') }}" class="btn btn-secondary filter-btn-reset">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reset
                </a>
                @endif
            </div>

            {{-- Filter Pencarian --}}
            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input"
                       placeholder="Cari materi, guru, mapel...">
                <button type="submit" class="btn btn-primary filter-search-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Rekap Jurnal --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th>Hari & Tanggal</th>
                        <th>Kelas</th>
                        <th>Guru Pengajar</th>
                        <th>Mata Pelajaran</th>
                        <th style="width:90px; text-align:center;">Jam Ke</th>
                        <th style="min-width:180px;">Materi Pembelajaran</th>
                        <th>Catatan</th>
                        <th style="min-width:160px; text-align:center;">Ringkasan Absensi</th>
                        <th style="width:100px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnals as $index => $item)
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">
                            {{ $jurnals->firstItem() + $index }}
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">
                                {{ $item->hari }}, {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                            </div>
                            <span style="font-size:11.5px; color:var(--text-secondary);">
                                {{ $item->semester }} ({{ $item->tahun_pelajaran }})
                            </span>
                        </td>
                        <td>
                            <span class="badge-lembaga" style="background:rgba(0, 82, 255, 0.1); color:#0052FF; font-weight:700;">
                                {{ $item->kelas }}
                            </span>
                        </td>
                        <td style="font-weight:600; color:var(--text-primary);">
                            {{ $item->nama_guru }}
                        </td>
                        <td>
                            <span style="font-weight:500;">{{ $item->mata_pelajaran }}</span>
                        </td>
                        <td style="text-align:center; font-size:12.5px; font-weight:600; color:var(--text-secondary);">
                            {{ $item->jam_ke }}
                        </td>
                        <td>
                            <div style="font-size:13px; line-height:1.4; color:var(--text-primary); max-width:260px; overflow:hidden; text-overflow:ellipsis;">
                                {{ $item->materi }}
                            </div>
                        </td>
                        <td>
                            @if($item->catatan)
                                <div style="font-size:12px; color:var(--text-secondary); max-width:200px; overflow:hidden; text-overflow:ellipsis;">
                                    {{ $item->catatan }}
                                </div>
                            @else
                                <span style="color:var(--text-secondary); opacity:0.6; font-size:12px;">-</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:center;">
                                <span title="Hadir: {{ $item->total_hadir }}" style="font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; background:rgba(16, 185, 129, 0.15); color:#059669;">
                                    H: {{ $item->total_hadir }}
                                </span>
                                @if($item->total_sakit > 0)
                                <span title="Sakit: {{ $item->total_sakit }}" style="font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; background:rgba(245, 158, 11, 0.15); color:#D97706;">
                                    S: {{ $item->total_sakit }}
                                </span>
                                @endif
                                @if($item->total_izin > 0)
                                <span title="Izin: {{ $item->total_izin }}" style="font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; background:rgba(37, 99, 235, 0.15); color:#2563EB;">
                                    I: {{ $item->total_izin }}
                                </span>
                                @endif
                                @if($item->total_alpa > 0)
                                <span title="Alpa: {{ $item->total_alpa }}" style="font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; background:rgba(239, 68, 68, 0.15); color:#DC2626;">
                                    A: {{ $item->total_alpa }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                {{-- Tombol Lihat Detail --}}
                                <button type="button" class="action-btn action-btn-view" data-modal-target="modalDetailJurnal{{ $item->id }}" title="Lihat Detail Jurnal">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>

                                {{-- Tombol Hapus --}}
                                <button type="button" class="action-btn action-btn-delete" data-modal-target="modalDeleteJurnal{{ $item->id }}" title="Hapus Jurnal">
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
                        <td colspan="10" style="text-align:center; padding:48px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:10px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:40px;height:40px;opacity:0.4;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span style="font-size:14px; font-weight:500;">Belum ada data jurnal pembelajaran yang diinput</span>
                                <a href="{{ route('sekolah.jurnal') }}" class="btn btn-sm btn-primary" style="margin-top:4px;">
                                    Mulai Input Jurnal
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jurnals->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $jurnals->links() }}
        </div>
        @endif
    </div>

</div>

{{-- MODAL DETAIL JURNAL & HAPUS JURNAL UNTUK SETIAP ITEM --}}
@foreach($jurnals as $item)

{{-- MODAL DETAIL --}}
<div class="app-modal" id="modalDetailJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:700px;">
        <div class="modal-header">
            <div class="modal-header-title">
                <div class="modal-icon-badge view-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <h3 class="modal-title">Detail Jurnal Pembelajaran</h3>
            </div>
            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:16px;">
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:12px; background:var(--bg-card-header); padding:14px; border-radius:10px;">
                <div>
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Kelas & Waktu</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">Kelas {{ $item->kelas }}</strong>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $item->hari }}, {{ \Carbon\Carbon::parse($item->tanggal)->format('d F Y') }}</div>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $item->jam_ke }}</div>
                </div>
                <div>
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Guru & Mata Pelajaran</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">{{ $item->nama_guru }}</strong>
                    <div style="font-size:12px; color:var(--electric-blue); font-weight:600;">{{ $item->mata_pelajaran }}</div>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $item->semester }} - {{ $item->tahun_pelajaran }}</div>
                </div>
            </div>

            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Materi Pembelajaran:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; line-height:1.5;">
                    {{ $item->materi }}
                </div>
            </div>

            @if($item->catatan)
            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Catatan:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:12.5px; color:var(--text-secondary); line-height:1.5;">
                    {{ $item->catatan }}
                </div>
            </div>
            @endif

            {{-- Detail Absensi Siswa --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">Daftar Kehadiran Siswa ({{ $item->total_siswa }} Siswa)</span>
                    <div style="display:flex; gap:6px;">
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(16, 185, 129, 0.15); color:#059669;">H: {{ $item->total_hadir }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(245, 158, 11, 0.15); color:#D97706;">S: {{ $item->total_sakit }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(37, 99, 235, 0.15); color:#2563EB;">I: {{ $item->total_izin }}</span>
                        <span style="font-size:11px; font-weight:700; padding:2px 6px; border-radius:8px; background:rgba(239, 68, 68, 0.15); color:#DC2626;">A: {{ $item->total_alpa }}</span>
                    </div>
                </div>

                <div style="max-height:220px; overflow-y:auto; border:1px solid var(--border-light); border-radius:8px;">
                    <table class="modern-table" style="font-size:12.5px;">
                        <thead>
                            <tr>
                                <th style="width:35px; text-align:center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:110px; text-align:center;">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->absensis as $absIndex => $abs)
                            <tr>
                                <td style="text-align:center;">{{ $absIndex + 1 }}</td>
                                <td style="font-weight:600;">{{ $abs->nama_siswa }}</td>
                                <td style="text-align:center;">
                                    @php
                                        $badgeColor = match($abs->status) {
                                            'hadir' => 'background:rgba(16, 185, 129, 0.15); color:#059669;',
                                            'sakit' => 'background:rgba(245, 158, 11, 0.15); color:#D97706;',
                                            'izin'  => 'background:rgba(37, 99, 235, 0.15); color:#2563EB;',
                                            'alpa'  => 'background:rgba(239, 68, 68, 0.15); color:#DC2626;',
                                            default => 'background:rgba(16, 185, 129, 0.15); color:#059669;',
                                        };
                                    @endphp
                                    <span style="padding:2px 8px; border-radius:12px; font-weight:700; font-size:11px; {{ $badgeColor }}">
                                        {{ ucfirst($abs->status) }}
                                    </span>
                                </td>
                                <td style="color:var(--text-secondary); font-size:11.5px;">
                                    {{ $abs->keterangan ?: '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align:center; padding:12px; color:var(--text-secondary);">Tidak ada data detail absensi</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Tutup</button>
        </div>
    </div>
</div>

{{-- MODAL KONFIRMASI HAPUS --}}
<div class="app-modal" id="modalDeleteJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:440px;">
        <form action="{{ route('sekolah.jurnal.destroy', $item->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <div class="modal-header-title">
                    <div class="modal-icon-badge delete-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Hapus Jurnal Pembelajaran</h3>
                </div>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="modal-body" style="text-align:left;">
                <p style="margin:0 0 10px; font-size:13.5px; color:var(--text-primary);">
                    Apakah Anda yakin ingin menghapus data jurnal pembelajaran <strong>Kelas {{ $item->kelas }}</strong> tanggal <strong>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</strong>?
                </p>
                <p style="margin:0; font-size:12px; color:var(--text-secondary);">
                    Seluruh rekap kehadiran siswa pada sesi ini juga akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus Jurnal</button>
            </div>
        </form>
    </div>
</div>

@endforeach

{{-- SCRIPT PENGENDALI MODAL --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('is-active');
            if (!document.querySelector('.app-modal.is-active')) {
                document.body.style.overflow = '';
            }
        }
    }

    document.querySelectorAll('[data-modal-target]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = this.getAttribute('data-modal-target');
            openModal(targetId);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal(this.closest('.app-modal'));
        });
    });

    document.querySelectorAll('.app-modal').forEach(function (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });

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
