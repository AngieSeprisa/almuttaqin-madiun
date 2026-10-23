@extends('layouts.app')

@section('title', 'Pendaftaran Santri')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'create'))
<a href="{{ route('pendaftaran.create') }}" class="btn btn-primary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    Formulir Pendaftaran Baru
</a>
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

    {{-- Mini Stats Grid --}}
    <div class="stats-grid" style="margin-bottom:0;">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Pendaftar</span>
                <div class="stat-icon-wrapper stat-icon-total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['total'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Menunggu Verifikasi</span>
                <div class="stat-icon-wrapper stat-icon-pulang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['pending'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Diterima</span>
                <div class="stat-icon-wrapper stat-icon-aktif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['diterima'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Ditolak</span>
                <div class="stat-icon-wrapper stat-icon-sakit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['ditolak'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Filter & Search Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('pendaftaran.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Status Pendaftaran --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <select name="status" id="filter_status_pendaftaran" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending ({{ $stats['pending'] }})</option>
                        <option value="Diterima" {{ request('status') === 'Diterima' ? 'selected' : '' }}>Diterima ({{ $stats['diterima'] }})</option>
                        <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak ({{ $stats['ditolak'] }})</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Jenjang (Khusus ruang kerja yayasan/pondok) --}}
                @if(!$activeRoom || in_array($activeRoom, ['yayasan', 'pondok']))
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="jenjang" id="filter_jenjang_pendaftaran" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Jenjang</option>
                        <option value="Reguler" {{ request('jenjang') === 'Reguler' ? 'selected' : '' }}>Reguler</option>
                        <option value="SMP" {{ request('jenjang') === 'SMP' ? 'selected' : '' }}>SMP</option>
                        <option value="SMA" {{ request('jenjang') === 'SMA' ? 'selected' : '' }}>SMA</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
                @endif

                @if(request('status') || request('jenjang') || request('search'))
                    <a href="{{ route('pendaftaran.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari NIK, NISN, Nama, Jenjang, Asal Sekolah...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Pendaftaran --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>No. Induk / NIK</th>
                        <th>Nama Calon Santri</th>
                        <th>Jenjang</th>
                        <th>JK & Usia</th>
                        <th>Sambung Asal</th>
                        <th>Status Pendaftaran</th>
                        <th>Tanggal Daftar</th>
                        <th style="text-align:center; width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pendaftarans as $pendaftar)
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $pendaftar->no_induk ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-muted); font-family:monospace;">{{ $pendaftar->nik }}</div>
                        </td>
                        <td class="cell-nama">
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <span>{{ $pendaftar->nama_lengkap }}</span>
                                @if($pendaftar->penerima_kip)
                                    <span class="badge-kip" title="Penerima KIP / PIP / PKH">KIP</span>
                                @endif
                            </div>
                            <div style="font-size:12px; color:var(--text-secondary); font-weight:normal;">
                                Panggilan: {{ $pendaftar->nama_panggilan }}
                                @if($pendaftar->nisn)
                                    • <span style="font-family:monospace; font-weight:600;">NISN: {{ $pendaftar->nisn }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php
                                $jenjangClass = match($pendaftar->jenjang ?? 'Reguler') {
                                    'SMP' => 'jenjang-pill-smp',
                                    'SMA' => 'jenjang-pill-sma',
                                    default => 'jenjang-pill-reguler',
                                };
                            @endphp
                            <span class="jenjang-pill {{ $jenjangClass }}">{{ $pendaftar->jenjang ?? 'Reguler' }}</span>
                            @if($pendaftar->nama_sekolah_asal)
                                <div style="font-size:11.5px; color:var(--text-muted); max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-top:3px;" title="{{ $pendaftar->nama_sekolah_asal }}">
                                    {{ $pendaftar->nama_sekolah_asal }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $pendaftar->jenis_kelamin }}</div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                {{ $pendaftar->tanggal_lahir ? $pendaftar->tanggal_lahir->age . ' Thn' : '-' }}
                            </div>
                        </td>
                        <td>
                            <div>{{ $pendaftar->daerah }}</div>
                            <div style="font-size:12px; color:var(--text-muted);">
                                {{ $pendaftar->desa }} • {{ $pendaftar->kelompok }}
                            </div>
                        </td>
                        <td>
                            <span class="status-pill status-{{ strtolower($pendaftar->status_pendaftaran) }}">
                                {{ $pendaftar->status_pendaftaran }}
                            </span>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $pendaftar->created_at ? $pendaftar->created_at->format('d M Y') : '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                {{-- Tombol Detail / Verifikasi (Hanya Icon Mata) --}}
                                <a href="{{ route('pendaftaran.show', $pendaftar->id) }}" class="action-btn action-btn-view" title="Detail & Verifikasi" aria-label="Detail {{ $pendaftar->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                {{-- Tombol Cetak Formulir (Hanya Icon Printer) --}}
                                <a href="{{ route('pendaftaran.cetak', $pendaftar->id) }}" class="action-btn action-btn-print" title="Cetak Formulir A4" aria-label="Cetak Formulir {{ $pendaftar->nama_lengkap }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="cell-empty">
                            Belum ada calon santri yang mendaftar. Silakan klik tombol <strong>"Formulir Pendaftaran Baru"</strong> untuk menginput data formulir.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pendaftarans->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $pendaftarans->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
