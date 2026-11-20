@extends('layouts.app')

@section('title', 'Sistem Ujian & Koreksi LJK')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <a href="{{ route('ujian.download-template') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Unduh Template Word</span>
    </a>

    @if(!auth()->check() || auth()->user()->canDo('ujian', 'create'))
    <a href="{{ route('ujian.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>+ Buat Ujian Baru</span>
    </a>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:100%; min-width:0;">

    {{-- STATS CARDS --}}
    <div class="stats-grid" style="margin-bottom:0;">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Ujian</span>
                <div class="stat-icon-wrapper stat-icon-total">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ $totalUjian ?? $ujians->total() }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Ujian Aktif</span>
                <div class="stat-icon-wrapper stat-icon-aktif">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ $ujianAktif ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">LJK Terkoreksi</span>
                <div class="stat-icon-wrapper stat-icon-pulang">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ $totalKoreksi ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Ujian Selesai</span>
                <div class="stat-icon-wrapper stat-icon-sakit">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ $ujianSelesai ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- FILTER BAR CARD --}}
    <div class="form-section-card filter-card">
        <form method="GET" action="{{ route('ujian.index') }}" class="filter-form">
            <div class="filter-group-left">
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                    </div>
                    <select name="kelas_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        @foreach($kelases as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    </div>
                    <select name="mapel_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Mapel</option>
                        @foreach($mapels as $m)
                        <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->mata_pelajaran }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                @if(request()->anyFilled(['search', 'kelas_id', 'mapel_id', 'status']))
                <a href="{{ route('ujian.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    <span>Reset</span>
                </a>
                @endif
            </div>

            <div class="filter-search-wrap">
                <input type="text" name="search" class="form-control filter-search-input" placeholder="Cari nama atau kode ujian..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            </div>
        </form>
    </div>

    {{-- TABEL UJIAN MODERN --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="width:130px;">Kode Ujian</th>
                        <th style="min-width:180px;">Nama Ujian</th>
                        <th style="min-width:140px;">Mapel & Kelas</th>
                        <th style="min-width:140px;">Guru Pengampu</th>
                        <th style="width:110px; text-align:center;">Soal & Timer</th>
                        <th style="width:120px; text-align:center;">Status</th>
                        <th style="width:125px; text-align:center;">Koreksi</th>
                        <th style="text-align:center; width:150px; white-space:nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ujians as $idx => $ujian)
                    <tr>
                        <td style="text-align:center; color:var(--text-muted); font-weight:600;">
                            {{ $ujians->firstItem() ? ($ujians->firstItem() + $idx) : ($idx + 1) }}
                        </td>
                        <td>
                            <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-family:monospace; font-size:12px; font-weight:700;">
                                {{ $ujian->kode_ujian }}
                            </span>
                        </td>
                        <td class="cell-nama">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $ujian->nama_ujian }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                Tanggal: {{ $ujian->tanggal_ujian?->format('d/m/Y') ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary); font-size:13px;">{{ $ujian->mapel?->mata_pelajaran ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                Kelas: <span style="font-weight:600; color:var(--text-secondary);">{{ $ujian->kelas?->nama_kelas ?? '-' }}</span>
                            </div>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $ujian->guru?->name ?? '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13px;">{{ $ujian->jumlah_soal }} Butir</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">{{ $ujian->timer_default }} dtk/soal</div>
                        </td>
                        <td style="text-align:center;">
                            @if($ujian->status === 'aktif')
                                <span class="status-pill status-sudah_kembali" style="font-size:11px; padding:3px 8px;">Aktif</span>
                            @elseif($ujian->status === 'selesai')
                                <span class="status-pill status-melewati_batas" style="font-size:11px; padding:3px 8px;">Selesai</span>
                            @else
                                <span class="status-pill status-pending" style="font-size:11px; padding:3px 8px;">Draft</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if(isset($ujian->hasils_count) && $ujian->hasils_count > 0)
                                <a href="{{ route('ujian.hasil', $ujian->id) }}" class="status-pill status-sudah_kembali" style="font-size:11px; padding:3px 8px; text-decoration:none;">
                                    {{ $ujian->hasils_count }} LJK
                                </a>
                            @else
                                <span class="status-pill status-melewati_batas" style="font-size:11px; padding:3px 8px;">
                                    0 LJK
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <a href="{{ route('ujian.kunci', $ujian->id) }}" class="action-btn action-btn-view" title="Kunci Jawaban" aria-label="Kunci Jawaban {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="7.5" cy="15.5" r="5.5"></circle>
                                        <path d="M21 2l-9.6 9.6"></path>
                                        <path d="M15.5 7.5l3 3L22 7l-3-3"></path>
                                    </svg>
                                </a>

                                <a href="{{ route('ujian.upload-scan', $ujian->id) }}" class="action-btn action-btn-print" title="Koreksi Scan LJK" aria-label="Koreksi Scan {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                </a>

                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'edit'))
                                <a href="{{ route('ujian.edit', $ujian->id) }}" class="action-btn action-btn-edit" title="Ubah Ujian" aria-label="Ubah Ujian {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'delete'))
                                <form action="{{ route('ujian.destroy', $ujian->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ujian {{ addslashes($ujian->nama_ujian) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-delete" title="Hapus Ujian" aria-label="Hapus Ujian {{ $ujian->nama_ujian }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="cell-empty">
                            Belum ada data ujian yang dibuat. Silakan klik tombol <strong>"+ Buat Ujian Baru"</strong> di pojok kanan atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ujians->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $ujians->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
