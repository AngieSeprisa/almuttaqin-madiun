@extends('layouts.app')

@section('title', 'Rekap Absensi Guru')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('rekap-absensi.export', request()->all()) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel</span>
    </a>

    <a href="{{ route('rekap-absensi.cetak', request()->all()) }}" target="_blank" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak Laporan</span>
    </a>
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- Alert Banner --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    {{-- 5 Kartu Ringkasan Statistik --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
        <div class="stat-card" style="background:var(--bg-surface); padding:16px 20px; border-radius:14px; border:1px solid var(--border-light); box-shadow:var(--card-shadow); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Hadir Tepat</span>
                <div style="font-size:24px; font-weight:800; color:#059669; line-height:1.2; margin-top:2px;">{{ $ringkasan['hadir'] }}</div>
            </div>
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(16, 185, 129, 0.1); display:flex; align-items:center; justify-content:center; color:#10B981;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card" style="background:var(--bg-surface); padding:16px 20px; border-radius:14px; border:1px solid var(--border-light); box-shadow:var(--card-shadow); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Terlambat</span>
                <div style="font-size:24px; font-weight:800; color:#D97706; line-height:1.2; margin-top:2px;">{{ $ringkasan['terlambat'] }}</div>
            </div>
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(245, 158, 11, 0.1); display:flex; align-items:center; justify-content:center; color:#F59E0B;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card" style="background:var(--bg-surface); padding:16px 20px; border-radius:14px; border:1px solid var(--border-light); box-shadow:var(--card-shadow); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Izin</span>
                <div style="font-size:24px; font-weight:800; color:#2563EB; line-height:1.2; margin-top:2px;">{{ $ringkasan['izin'] }}</div>
            </div>
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(37, 99, 235, 0.1); display:flex; align-items:center; justify-content:center; color:#2563EB;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card" style="background:var(--bg-surface); padding:16px 20px; border-radius:14px; border:1px solid var(--border-light); box-shadow:var(--card-shadow); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Sakit</span>
                <div style="font-size:24px; font-weight:800; color:#7C3AED; line-height:1.2; margin-top:2px;">{{ $ringkasan['sakit'] }}</div>
            </div>
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(124, 58, 237, 0.1); display:flex; align-items:center; justify-content:center; color:#7C3AED;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card" style="background:var(--bg-surface); padding:16px 20px; border-radius:14px; border:1px solid var(--border-light); box-shadow:var(--card-shadow); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <span style="font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Alpa</span>
                <div style="font-size:24px; font-weight:800; color:#DC2626; line-height:1.2; margin-top:2px;">{{ $ringkasan['alpa'] }}</div>
            </div>
            <div style="width:40px; height:40px; border-radius:10px; background:rgba(220, 38, 38, 0.1); display:flex; align-items:center; justify-content:center; color:#DC2626;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('rekap-absensi.guru') }}" method="GET" class="filter-form">
            <div class="filter-group-left">

                {{-- Filter Lembaga --}}
                @if($lembagas->count() > 1)
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="lembaga_id" id="filter_rekap_lembaga" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Lembaga</option>
                        @foreach($lembagas as $lem)
                        <option value="{{ $lem->id }}" {{ $lembagaId == $lem->id ? 'selected' : '' }}>
                            {{ $lem->nama_singkat }}
                        </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
                @endif

                {{-- Filter Bulan --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <select name="bulan" id="filter_rekap_bulan" class="form-select filter-select" onchange="this.form.submit()">
                        @foreach([1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April', 5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus', 9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'] as $num => $namaBulan)
                        <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>
                            {{ $namaBulan }}
                        </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Tahun --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <select name="tahun" id="filter_rekap_tahun" class="form-select filter-select" onchange="this.form.submit()">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
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
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="status" id="filter_rekap_status" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="hadir" {{ $status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="terlambat" {{ $status === 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="izin" {{ $status === 'izin' ? 'selected' : '' }}>Izin</option>
                        <option value="sakit" {{ $status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                        <option value="alpa" {{ $status === 'alpa' ? 'selected' : '' }}>Alpa</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                @if($search || $status || request('tanggal'))
                <a href="{{ route('rekap-absensi.guru', ['lembaga_id' => $lembagaId, 'bulan' => $bulan, 'tahun' => $tahun]) }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Reset</span>
                </a>
                @endif
            </div>

            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau NIP..."
                       class="form-control filter-search-input">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Rekap Data Presensi --}}
    <div class="table-container table-card" style="background:var(--bg-surface); border-radius:14px; border:1px solid var(--border-light); overflow:hidden; box-shadow:var(--card-shadow);">
        <table class="table" style="width:100%; border-collapse:collapse; text-align:left;">
            <thead>
                <tr style="background:var(--table-th-bg); border-bottom:1px solid var(--border-light);">
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary); width:50px;">No</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Tanggal</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Guru</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Lembaga</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Masuk</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Pulang</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary);">Status</th>
                    <th style="padding:14px 18px; font-size:12.5px; font-weight:700; color:var(--text-secondary); text-align:right; width:90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($presensis as $index => $row)
                <tr style="border-bottom:1px solid var(--border-subtle); transition:background-color 0.15s ease;">
                    <td style="padding:14px 18px; font-size:13px; color:var(--text-muted);">
                        {{ $presensis->firstItem() + $index }}
                    </td>
                    <td style="padding:14px 18px; font-size:13.5px; font-weight:600; color:var(--text-primary); white-space:nowrap;">
                        {{ $row->tanggal ? $row->tanggal->translatedFormat('d M Y') : '-' }}
                    </td>
                    <td style="padding:14px 18px;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div style="width:34px; height:34px; border-radius:50%; background:rgba(0, 82, 255, 0.1); color:var(--electric-blue); font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                {{ strtoupper(substr($row->user->name ?? 'G', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:13.5px; font-weight:700; color:var(--text-primary);">
                                    {{ $row->user->name ?? '-' }}
                                </div>
                                <div style="font-size:12px; color:var(--text-muted);">
                                    {{ $row->user->nip ?? ($row->user->jabatan ?? '-') }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 18px; font-size:13px; color:var(--text-secondary); white-space:nowrap;">
                        {{ $row->lembaga->singkatan ?? ($row->lembaga->nama ?? '-') }}
                    </td>
                    <td style="padding:14px 18px;">
                        @if($row->jam_masuk)
                        <div style="font-size:13.5px; font-weight:700; color:var(--text-primary); font-family:monospace;">
                            {{ substr($row->jam_masuk, 0, 5) }}
                        </div>
                        @if($row->jarak_masuk_meter)
                        <span style="font-size:11px; color:var(--text-muted);">{{ $row->jarak_masuk_meter }}m</span>
                        @endif
                        @else
                        <span style="color:var(--text-muted); font-size:13px;">-</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px;">
                        @if($row->jam_pulang)
                        <div style="font-size:13.5px; font-weight:700; color:var(--text-primary); font-family:monospace;">
                            {{ substr($row->jam_pulang, 0, 5) }}
                        </div>
                        @else
                        <span style="color:var(--text-muted); font-size:13px;">-</span>
                        @endif
                    </td>
                    <td style="padding:14px 18px;">
                        @php
                            $badgeStyles = match($row->status) {
                                'hadir'     => 'background:rgba(16, 185, 129, 0.12); color:#059669;',
                                'terlambat' => 'background:rgba(245, 158, 11, 0.12); color:#D97706;',
                                'izin'      => 'background:rgba(37, 99, 235, 0.12); color:#2563EB;',
                                'sakit'     => 'background:rgba(124, 58, 237, 0.12); color:#7C3AED;',
                                default     => 'background:rgba(239, 68, 68, 0.12); color:#DC2626;',
                            };
                        @endphp
                        <span style="display:inline-block; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700; {{ $badgeStyles }}">
                            {{ $row->status_label }}
                        </span>
                    </td>
                    <td style="padding:14px 18px; text-align:right;">
                        <button type="button" class="btn btn-secondary btn-koreksi"
                                data-id="{{ $row->id }}"
                                data-nama="{{ $row->user->name ?? '-' }}"
                                data-tanggal="{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '' }}"
                                data-status="{{ $row->status }}"
                                data-masuk="{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '' }}"
                                data-pulang="{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '' }}"
                                data-keterangan="{{ $row->keterangan ?? '' }}"
                                style="padding:6px 12px; font-size:12px;">
                            Koreksi
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="padding:40px 20px; text-align:center; color:var(--text-muted); font-size:14px;">
                        Tidak ada data presensi guru untuk filter yang dipilih.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($presensis->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $presensis->links() }}
        </div>
        @endif
    </div>

    {{-- Card List untuk Tampilan Mobile (<= 768px) --}}
    <div class="mobile-card-list">
        @forelse($presensis as $index => $row)
        <div class="mobile-card-item">
            <div class="mobile-card-info">
                <div class="mobile-card-title">{{ $row->user->name ?? '-' }}</div>
                <div class="mobile-card-subtitle">
                    {{ $row->tanggal ? $row->tanggal->translatedFormat('d M Y') : '-' }} &bull; {{ $row->user->nip ?? ($row->user->jabatan ?? '-') }}
                </div>

                <div class="mobile-card-meta-grid">
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Lembaga</span>
                        <span class="mobile-card-pill mobile-card-pill-blue">{{ $row->lembaga->singkatan ?? ($row->lembaga->nama ?? '-') }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Jam</span>
                        <span class="mobile-card-pill mobile-card-pill-gray" style="font-family:monospace;">{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }} - {{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">&nbsp;</span>
                        <span class="status-pill {{ $row->status === 'hadir' ? 'status-pill-active' : ($row->status === 'terlambat' ? 'status-pill-warning' : 'status-pill-inactive') }}">
                            {{ $row->status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mobile-card-actions">
                <span class="mobile-card-action-title">Aksi</span>
                <div class="mobile-card-action-buttons">
                    <button type="button" class="mobile-btn-pastel mobile-btn-edit btn-koreksi"
                            data-id="{{ $row->id }}"
                            data-nama="{{ $row->user->name ?? '-' }}"
                            data-tanggal="{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '' }}"
                            data-status="{{ $row->status }}"
                            data-masuk="{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '' }}"
                            data-pulang="{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '' }}"
                            data-keterangan="{{ $row->keterangan ?? '' }}"
                            title="Koreksi Presensi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:30px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
            Tidak ada data presensi guru untuk filter yang dipilih.
        </div>
        @endforelse

        @if($presensis->hasPages())
        <div style="margin-top:16px; display:flex; justify-content:center;">
            {{ $presensis->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal Koreksi Presensi --}}
<div id="modalKoreksi" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-surface); border-radius:16px; border:1px solid var(--border-light); width:100%; max-width:440px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Koreksi Presensi</h3>
                <span id="modalGuruNama" style="font-size:12.5px; color:var(--text-muted); display:block; margin-top:2px;"></span>
            </div>
            <button type="button" id="btnCloseModal" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="formKoreksi" method="POST" action="" style="padding:22px; display:flex; flex-direction:column; gap:16px;">
            @csrf
            @method('PUT')

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Status</label>
                <select name="status" id="modalStatus" class="form-select" required
                        style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                    <option value="hadir">Hadir</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="alpa">Alpa</option>
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Jam Masuk</label>
                    <input type="time" name="jam_masuk" id="modalMasuk" class="form-control"
                           style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                </div>
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Jam Pulang</label>
                    <input type="time" name="jam_pulang" id="modalPulang" class="form-control"
                           style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                </div>
            </div>

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Keterangan Koreksi</label>
                <textarea name="keterangan" id="modalKeterangan" rows="2" class="form-control" placeholder="Contoh: Tugas luar kota / lupa scan..."
                          style="width:100%; padding:9px 12px; font-size:13px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary); resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                <button type="button" id="btnCancelModal" class="btn btn-secondary" style="padding:8px 16px; font-size:13px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px; font-weight:600;">Simpan Koreksi</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modalKoreksi');
    var form = document.getElementById('formKoreksi');
    var modalGuruNama = document.getElementById('modalGuruNama');
    var modalStatus = document.getElementById('modalStatus');
    var modalMasuk = document.getElementById('modalMasuk');
    var modalPulang = document.getElementById('modalPulang');
    var modalKeterangan = document.getElementById('modalKeterangan');
    var btnClose = document.getElementById('btnCloseModal');
    var btnCancel = document.getElementById('btnCancelModal');

    document.querySelectorAll('.btn-koreksi').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            var tanggal = this.getAttribute('data-tanggal');
            var status = this.getAttribute('data-status');
            var masuk = this.getAttribute('data-masuk');
            var pulang = this.getAttribute('data-pulang');
            var keterangan = this.getAttribute('data-keterangan');

            form.action = '{{ url("rekap-absensi/koreksi") }}/' + id;
            modalGuruNama.textContent = nama + ' • ' + tanggal;
            modalStatus.value = status;
            modalMasuk.value = masuk;
            modalPulang.value = pulang;
            modalKeterangan.value = keterangan;

            modal.style.display = 'flex';
        });
    });

    function closeModal() {
        modal.style.display = 'none';
    }

    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
});
</script>
@endsection
