@extends('layouts.app')

@section('title', 'Rekap Jurnal Ekstrakurikuler')

@section('header-actions')
<a href="{{ route('sekolah.ekskul.index') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
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

    {{-- Filter Bar --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('sekolah.ekskul.rekap-jurnal') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Ekskul --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="ekskul_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Ekstrakurikuler</option>
                        @foreach($ekskuls as $ek)
                            <option value="{{ $ek->id }}" {{ request('ekskul_id') == $ek->id ? 'selected' : '' }}>
                                {{ $ek->nama_ekskul }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Bulan --}}
                @php
                    $bulanList = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <select name="bulan" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList as $num => $namaB)
                            <option value="{{ $num }}" {{ (int) request('bulan', $bulan) === $num ? 'selected' : '' }}>
                                {{ $namaB }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tahun --}}
                <div class="filter-select-wrap">
                    <select name="tahun" class="form-select filter-select" style="min-width:110px;" onchange="this.form.submit()">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ (int) request('tahun', $tahun) === $y ? 'selected' : '' }}>
                                Tahun {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Filter Tanggal Spesifik --}}
                <div class="filter-select-wrap">
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}"
                           class="form-control" style="font-size:13px; padding:6px 12px; border-radius:10px; width:auto;"
                           onchange="this.form.submit()">
                </div>

                {{-- Reset Button --}}
                @if(request()->hasAny(['ekskul_id', 'bulan', 'tanggal']))
                <a href="{{ route('sekolah.ekskul.rekap-jurnal') }}" class="btn btn-secondary filter-btn-reset">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Stat Cards --}}
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
        <div style="background:var(--bg-card); border:1px solid var(--border-light); border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(0, 82, 255, 0.1); color:#0052FF; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <span style="font-size:12px; color:var(--text-secondary); display:block;">Total Pertemuan</span>
                <strong style="font-size:22px; color:var(--text-primary); line-height:1.2;">{{ $totalPertemuan }}</strong>
            </div>
        </div>

        <div style="background:var(--bg-card); border:1px solid var(--border-light); border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(16, 185, 129, 0.1); color:#10B981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <polyline points="16 11 18 13 22 9"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size:12px; color:var(--text-secondary); display:block;">Total Presensi Hadir</span>
                <strong style="font-size:22px; color:var(--text-primary); line-height:1.2;">{{ $totalHadirAll }}</strong>
                <span style="font-size:11.5px; color:var(--text-secondary);">/ {{ $totalSiswaAll }} Siswa</span>
            </div>
        </div>

        <div style="background:var(--bg-card); border:1px solid var(--border-light); border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(245, 158, 11, 0.1); color:#F59E0B; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <span style="font-size:12px; color:var(--text-secondary); display:block;">Tingkat Kehadiran</span>
                <strong style="font-size:22px; color:#059669; line-height:1.2;">{{ $rataPersenHadir }}%</strong>
            </div>
        </div>
    </div>

    {{-- Tabel Rekap Jurnal --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:45px; text-align:center;">No</th>
                        <th>Hari & Tanggal</th>
                        <th>Ekstrakurikuler</th>
                        <th>Pembina</th>
                        <th>Waktu & Tempat</th>
                        <th style="min-width:200px;">Materi / Aktivitas</th>
                        <th style="min-width:160px; text-align:center;">Presensi Siswa</th>
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
                                {{ \Carbon\Carbon::parse($item->tanggal)->diffForHumans() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-lembaga" style="background:rgba(0, 82, 255, 0.1); color:#0052FF; font-weight:700;">
                                {{ $item->nama_ekskul }}
                            </span>
                        </td>
                        <td style="font-weight:600; color:var(--text-primary);">
                            {{ $item->nama_pembina ?: '-' }}
                        </td>
                        <td style="font-size:12px;">
                            @if($item->jam_mulai && $item->jam_selesai)
                                <div>{{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }} WIB</div>
                            @endif
                            @if($item->tempat)
                                <div style="color:var(--text-secondary);">{{ $item->tempat }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-size:13px; line-height:1.4; color:var(--text-primary); max-width:260px; overflow:hidden; text-overflow:ellipsis;">
                                {{ $item->materi }}
                            </div>
                            @if($item->catatan)
                                <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">
                                    Catatan: {{ \Illuminate\Support\Str::limit($item->catatan, 40) }}
                                </div>
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
                                <button type="button" class="action-btn action-btn-view" data-modal-target="modalDetailJurnal{{ $item->id }}" title="Lihat Detail Jurnal">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
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
                        <td colspan="8" style="text-align:center; padding:48px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:10px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:40px;height:40px;opacity:0.4;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                </svg>
                                <span style="font-size:14px; font-weight:500;">Belum ada data jurnal ekstrakurikuler yang diinput</span>
                                <a href="{{ route('sekolah.ekskul.index') }}" class="btn btn-sm btn-primary" style="margin-top:4px;">
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

{{-- MODALS DETAIL & HAPUS --}}
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
                <h3 class="modal-title">Detail Jurnal: {{ $item->nama_ekskul }}</h3>
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
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Kegiatan & Waktu</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">{{ $item->nama_ekskul }}</strong>
                    <div style="font-size:12px; color:var(--text-secondary);">{{ $item->hari }}, {{ \Carbon\Carbon::parse($item->tanggal)->format('d F Y') }}</div>
                    @if($item->jam_mulai && $item->jam_selesai)
                        <div style="font-size:12px; color:var(--text-secondary);">{{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }} WIB</div>
                    @endif
                </div>
                <div>
                    <span style="font-size:11.5px; color:var(--text-secondary); display:block;">Pembina & Lokasi</span>
                    <strong style="font-size:13.5px; color:var(--text-primary);">{{ $item->nama_pembina ?: '-' }}</strong>
                    <div style="font-size:12px; color:var(--text-secondary);">Tempat: {{ $item->tempat ?: '-' }}</div>
                </div>
            </div>

            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Materi / Aktivitas Latihan:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; line-height:1.5;">
                    {{ $item->materi }}
                </div>
            </div>

            @if($item->catatan)
            <div>
                <span style="font-size:12px; font-weight:700; color:var(--text-secondary); display:block; margin-bottom:4px;">Catatan & Evaluasi:</span>
                <div style="padding:10px 14px; border-radius:8px; border:1px solid var(--border-light); font-size:12.5px; color:var(--text-secondary); line-height:1.4;">
                    {{ $item->catatan }}
                </div>
            </div>
            @endif

            {{-- Daftar Kehadiran Siswa --}}
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <span style="font-size:12.5px; font-weight:700; color:var(--text-primary);">Presensi Peserta ({{ $item->total_siswa }} Siswa)</span>
                    <div style="display:flex; gap:6px; font-size:11.5px; font-weight:600;">
                        <span style="color:#059669;">H: {{ $item->total_hadir }}</span> &bull;
                        <span style="color:#D97706;">S: {{ $item->total_sakit }}</span> &bull;
                        <span style="color:#2563EB;">I: {{ $item->total_izin }}</span> &bull;
                        <span style="color:#DC2626;">A: {{ $item->total_alpa }}</span>
                    </div>
                </div>
                <div style="max-height:240px; overflow-y:auto; border:1px solid var(--border-light); border-radius:8px;">
                    <table class="modern-table" style="font-size:12px;">
                        <thead>
                            <tr>
                                <th style="width:35px; text-align:center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:80px;">Kelas</th>
                                <th style="width:100px; text-align:center;">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->absensis as $idx => $ab)
                            <tr>
                                <td style="text-align:center;">{{ $idx + 1 }}</td>
                                <td style="font-weight:600;">{{ $ab->nama_siswa }}</td>
                                <td>{{ $ab->kelas ?: '-' }}</td>
                                <td style="text-align:center;">
                                    @if($ab->status === 'hadir')
                                        <span style="font-weight:700; color:#059669; background:#ECFDF5; padding:2px 6px; border-radius:4px;">Hadir</span>
                                    @elseif($ab->status === 'sakit')
                                        <span style="font-weight:700; color:#D97706; background:#FEF3C7; padding:2px 6px; border-radius:4px;">Sakit</span>
                                    @elseif($ab->status === 'izin')
                                        <span style="font-weight:700; color:#2563EB; background:#EFF6FF; padding:2px 6px; border-radius:4px;">Izin</span>
                                    @else
                                        <span style="font-weight:700; color:#DC2626; background:#FEE2E2; padding:2px 6px; border-radius:4px;">Alpa</span>
                                    @endif
                                </td>
                                <td style="color:var(--text-secondary);">{{ $ab->keterangan ?: '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="text-align:center; padding:16px; color:var(--text-secondary);">Tidak ada data presensi siswa.</td>
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

{{-- MODAL HAPUS --}}
<div class="app-modal" id="modalDeleteJurnal{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="modal-header-title">
                <div class="modal-icon-badge delete-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </div>
                <h3 class="modal-title">Hapus Jurnal Kegiatan</h3>
            </div>
            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="modal-body" style="text-align:left;">
            <p>Apakah Anda yakin ingin menghapus jurnal <strong>{{ $item->nama_ekskul }}</strong> tanggal <strong>{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</strong>?</p>
            <p style="font-size:12.5px; color:var(--text-secondary); margin-top:6px;">Seluruh data absensi peserta pada tanggal ini juga akan dihapus.</p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
            <form action="{{ route('sekolah.ekskul.jurnal.destroy', $item->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Hapus Jurnal</button>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal toggle handler
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-modal-target');
            const targetModal = document.getElementById(targetId);
            if (targetModal) targetModal.classList.add('active');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.app-modal')?.classList.remove('active');
        });
    });

    document.querySelectorAll('.app-modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
});
</script>
@endsection