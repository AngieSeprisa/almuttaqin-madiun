@extends('layouts.app')

@section('title', 'Guru Sekolah')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    @if(isset($lembagas) && $lembagas->count() > 1)
    <div style="display:flex; align-items:center; gap:8px;">
        <select class="form-select" onchange="window.location.href='{{ route('guru-sekolah.index') }}?lembaga_id=' + this.value"
                style="padding:8px 14px; font-size:13px; font-weight:600; border-radius:10px; background:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border-light);">
            <option value="">Semua Lembaga</option>
            @foreach($lembagas as $lem)
            <option value="{{ $lem->id }}" {{ request('lembaga_id') == $lem->id ? 'selected' : '' }}>
                {{ $lem->nama_singkat ?? $lem->singkatan }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    <button type="button" class="btn btn-primary" onclick="bukaModalTetapkanMapel()" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        <span>Tetapkan Mapel Guru</span>
    </button>
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; max-width:1300px; margin:0 auto;">

    {{-- Banner Notifikasi --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-banner alert-error" style="display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:12px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); color:#991B1B;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; margin-top:2px; color:#EF4444;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="font-size:13.5px;">
            <div style="font-weight:700; margin-bottom:4px;">Gagal memproses tindakan:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Highlight Pengingat Persetujuan Yayasan (Filament Clean) --}}
    @if($stats['menunggu'] > 0)
    <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; padding:12px 18px; border-radius:12px; background:rgba(245, 158, 11, 0.08); border:1px solid rgba(245, 158, 11, 0.3); flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="width:34px; height:34px; border-radius:8px; background:#F59E0B; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div style="font-size:14px; font-weight:700; color:#B45309;">
                Terdapat {{ $stats['menunggu'] }} Pengajuan Mata Pelajaran Menunggu Persetujuan Yayasan
            </div>
        </div>
        <a href="{{ route('guru-sekolah.index', ['status' => 'menunggu']) }}" class="btn btn-warning" style="padding:7px 14px; font-size:12.5px; font-weight:700; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#F59E0B; color:#ffffff; border:none;">
            <span>Lihat Pengajuan</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
    </div>
    @endif

    {{-- Kartu Statistik Metrik (Filament Style: Bersih Tanpa Deskripsi) --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
        {{-- Total Guru --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(59, 130, 246, 0.1); display:flex; align-items:center; justify-content:center; color:#3B82F6; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Total Guru</div>
                <div style="font-size:22px; font-weight:800; color:var(--text-primary); margin-top:2px; line-height:1.2;">{{ $stats['total_guru'] }}</div>
            </div>
        </div>

        {{-- Menunggu Approval --}}
        <div style="background:var(--bg-surface); border:1px solid {{ $stats['menunggu'] > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--border-light)' }}; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(245, 158, 11, 0.12); display:flex; align-items:center; justify-content:center; color:#F59E0B; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#D97706;">Menunggu Persetujuan</div>
                <div style="font-size:22px; font-weight:800; color:#D97706; margin-top:2px; line-height:1.2;">{{ $stats['menunggu'] }}</div>
            </div>
        </div>

        {{-- Disetujui --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(16, 185, 129, 0.1); display:flex; align-items:center; justify-content:center; color:#10B981; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Mapel Disetujui</div>
                <div style="font-size:22px; font-weight:800; color:var(--text-primary); margin-top:2px; line-height:1.2;">{{ $stats['disetujui'] }}</div>
            </div>
        </div>

        {{-- Belum Mengajukan --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px;">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(148, 163, 184, 0.15); display:flex; align-items:center; justify-content:center; color:#64748B; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted);">Belum Mengajukan</div>
                <div style="font-size:22px; font-weight:800; color:var(--text-primary); margin-top:2px; line-height:1.2;">{{ $stats['belum_mengajukan'] }}</div>
            </div>
        </div>
    </div>

    {{-- Filter Navigasi Cepat & Pencarian --}}
    <div style="background:var(--bg-surface); border:1px solid var(--border-light); border-radius:12px; padding:14px 18px; display:flex; flex-direction:column; gap:12px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
            {{-- Tab Status Filter --}}
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                @php
                    $curStatus = request('status', '');
                @endphp
                <a href="{{ route('guru-sekolah.index', array_merge(request()->except('status', 'page'), ['status' => ''])) }}"
                   class="btn" style="padding:6px 12px; font-size:12.5px; font-weight:600; border-radius:8px; text-decoration:none; {{ $curStatus === '' ? 'background:var(--brand-primary, #0284c7); color:#fff;' : 'background:rgba(0,0,0,0.04); color:var(--text-secondary);' }}">
                    Semua ({{ $stats['total_guru'] }})
                </a>
                <a href="{{ route('guru-sekolah.index', array_merge(request()->except('status', 'page'), ['status' => 'menunggu'])) }}"
                   class="btn" style="padding:6px 12px; font-size:12.5px; font-weight:600; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; {{ $curStatus === 'menunggu' ? 'background:#F59E0B; color:#fff;' : 'background:rgba(245, 158, 11, 0.12); color:#B45309;' }}">
                    <span>Menunggu</span>
                    @if($stats['menunggu'] > 0)
                    <span style="background:{{ $curStatus === 'menunggu' ? '#fff' : '#EF4444' }}; color:{{ $curStatus === 'menunggu' ? '#B45309' : '#fff' }}; font-size:10px; font-weight:800; padding:1px 6px; border-radius:10px;">{{ $stats['menunggu'] }}</span>
                    @endif
                </a>
                <a href="{{ route('guru-sekolah.index', array_merge(request()->except('status', 'page'), ['status' => 'disetujui'])) }}"
                   class="btn" style="padding:6px 12px; font-size:12.5px; font-weight:600; border-radius:8px; text-decoration:none; {{ $curStatus === 'disetujui' ? 'background:#10B981; color:#fff;' : 'background:rgba(16, 185, 129, 0.1); color:#047857;' }}">
                    Disetujui ({{ $stats['disetujui'] }})
                </a>
                <a href="{{ route('guru-sekolah.index', array_merge(request()->except('status', 'page'), ['status' => 'ditolak'])) }}"
                   class="btn" style="padding:6px 12px; font-size:12.5px; font-weight:600; border-radius:8px; text-decoration:none; {{ $curStatus === 'ditolak' ? 'background:#EF4444; color:#fff;' : 'background:rgba(239, 68, 68, 0.08); color:#B91C1C;' }}">
                    Ditolak ({{ $stats['ditolak'] }})
                </a>
                <a href="{{ route('guru-sekolah.index', array_merge(request()->except('status', 'page'), ['status' => 'belum_mengajukan'])) }}"
                   class="btn" style="padding:6px 12px; font-size:12.5px; font-weight:600; border-radius:8px; text-decoration:none; {{ $curStatus === 'belum_mengajukan' ? 'background:#64748B; color:#fff;' : 'background:rgba(100, 116, 139, 0.08); color:#475569;' }}">
                    Belum Mengajukan ({{ $stats['belum_mengajukan'] }})
                </a>
            </div>

            {{-- Form Pencarian --}}
            <form action="{{ route('guru-sekolah.index') }}" method="GET" style="display:flex; align-items:center; gap:8px; margin:0; flex-grow:1; max-width:320px;">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('lembaga_id'))
                    <input type="hidden" name="lembaga_id" value="{{ request('lembaga_id') }}">
                @endif
                <div style="position:relative; width:100%;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari guru, NIP, mapel..."
                           style="width:100%; padding:7px 32px 7px 12px; border-radius:8px; font-size:13px; border:1px solid var(--border-light); background:var(--bg-main, #F8FAFC); color:var(--text-primary);">
                    <button type="submit" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; padding:4px; color:var(--text-muted); cursor:pointer;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px; height:15px;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </div>
                @if(request('search'))
                <a href="{{ route('guru-sekolah.index', request()->except('search', 'page')) }}" class="btn btn-secondary" style="padding:7px 10px; font-size:12px; border-radius:8px;" title="Reset Pencarian">
                    Reset
                </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabel Utama Data Guru (Filament Table Style) --}}
    <div class="table-card" style="background:var(--bg-surface); border:1px solid var(--border-light); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:var(--bg-main, #F8FAFC); border-bottom:1px solid var(--border-light); color:var(--text-secondary); font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 14px; width:45px; text-align:center;">#</th>
                        <th style="padding:12px 14px;">Guru</th>
                        <th style="padding:12px 14px;">Sekolah</th>
                        <th style="padding:12px 14px;">Mata Pelajaran (Diampu)</th>
                        <th style="padding:12px 14px;">Status Pengajuan</th>
                        <th style="padding:12px 14px; text-align:center; width:200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $guru)
                    <tr style="border-bottom:1px solid var(--border-subtle, #F1F5F9); transition:background 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.015)'" onmouseout="this.style.background='transparent'">
                        {{-- No --}}
                        <td style="padding:12px 14px; text-align:center; color:var(--text-muted); font-size:12px;">
                            {{ $users->firstItem() + $index }}
                        </td>

                        {{-- Identitas Guru --}}
                        <td style="padding:12px 14px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($guru->foto_url)
                                    <img src="{{ $guru->foto_url }}" alt="{{ $guru->name }}" style="width:34px; height:34px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid var(--border-light);">
                                @else
                                    <div style="width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg, #3B82F6, #1D4ED8); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:13px; flex-shrink:0;">
                                        {{ $guru->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary); font-size:13.5px; display:flex; align-items:center; gap:6px;">
                                        <span>{{ $guru->name }}</span>
                                        @if($guru->mapel_status === 'disetujui')
                                        <svg viewBox="0 0 24 24" fill="#10B981" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;" title="Terverifikasi Yayasan">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="9 12 11 14 15 10"></polyline>
                                        </svg>
                                        @endif
                                    </div>
                                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                                        @if($guru->nip)
                                            <span>NIP: {{ $guru->nip }}</span>
                                        @else
                                            <span>{{ $guru->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Lembaga --}}
                        <td style="padding:12px 14px;">
                            @if($guru->primaryLembaga)
                                <span class="badge-lembaga badge-lembaga-{{ $guru->primaryLembaga->kode }}" style="font-size:11px; padding:3px 8px; border-radius:6px;">
                                    {{ $guru->primaryLembaga->singkatan }}
                                </span>
                            @elseif($guru->lembagas->isNotEmpty())
                                @foreach($guru->lembagas as $l)
                                    <span class="badge-lembaga badge-lembaga-{{ $l->kode }}" style="font-size:11px; padding:3px 8px; border-radius:6px; margin-right:4px;">
                                        {{ $l->singkatan }}
                                    </span>
                                @endforeach
                            @else
                                <span style="font-size:12px; color:var(--text-muted);">-</span>
                            @endif
                        </td>

                        {{-- Mata Pelajaran Aktif (Filament Pills: Mendukung 2 atau Lebih Mapel) --}}
                        <td style="padding:12px 14px;">
                            @php
                                $mapels = $guru->daftar_mata_pelajaran;
                            @endphp
                            @if(!empty($mapels))
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    @foreach($mapels as $m)
                                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:600; background:rgba(2, 132, 199, 0.08); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">
                                            {{ $m }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="font-size:12px; color:var(--text-muted);">-</span>
                            @endif
                        </td>

                        {{-- Status Pengajuan Mapel --}}
                        <td style="padding:12px 14px;">
                            @if($guru->mapel_status === 'menunggu')
                                <div style="display:inline-flex; flex-direction:column; gap:4px;">
                                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#d97706;"></span>
                                        <span>Menunggu Persetujuan</span>
                                    </span>
                                    @php
                                        $pendingMapels = $guru->daftar_mapel_diajukan;
                                    @endphp
                                    @if(!empty($pendingMapels))
                                        <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                            @foreach($pendingMapels as $pm)
                                                <span style="font-size:11px; font-weight:600; color:#92400e; background:rgba(245, 158, 11, 0.15); padding:1px 6px; border-radius:4px;">
                                                    {{ $pm }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @elseif($guru->mapel_status === 'disetujui')
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px; height:12px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>Disetujui</span>
                                </span>
                            @elseif($guru->mapel_status === 'ditolak')
                                <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">
                                    <span>Ditolak</span>
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:600; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">
                                    Belum Mengajukan
                                </span>
                            @endif
                        </td>

                        {{-- Aksi Yayasan --}}
                        <td style="padding:12px 14px; text-align:center;">
                            <div style="display:inline-flex; align-items:center; gap:6px; justify-content:center;">
                                @if($guru->mapel_status === 'menunggu')
                                    <button type="button" class="btn"
                                            data-guru-id="{{ $guru->id }}"
                                            data-guru-name="{{ $guru->name }}"
                                            data-guru-mapel="{{ $guru->mapel_diajukan }}"
                                            data-guru-catatan="{{ $guru->mapel_catatan ?? '' }}"
                                            onclick="bukaModalApproveBtn(this)"
                                            style="background:#10B981; color:#fff; padding:5px 10px; font-size:12px; font-weight:700; border-radius:6px; border:none; display:inline-flex; align-items:center; gap:4px; cursor:pointer;"
                                            title="Setujui">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px; height:13px;">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        <span>Approve</span>
                                    </button>

                                    <button type="button" class="btn"
                                            data-guru-id="{{ $guru->id }}"
                                            data-guru-name="{{ $guru->name }}"
                                            data-guru-mapel="{{ $guru->mapel_diajukan }}"
                                            onclick="bukaModalTolakBtn(this)"
                                            style="background:rgba(239, 68, 68, 0.1); color:#EF4444; border:1px solid rgba(239, 68, 68, 0.25); padding:5px 8px; font-size:12px; font-weight:600; border-radius:6px; display:inline-flex; align-items:center; gap:4px; cursor:pointer;"
                                            title="Tolak">
                                        <span>Tolak</span>
                                    </button>
                                @endif

                                {{-- Tombol Tetapkan / Ubah Mapel --}}
                                <button type="button" class="btn btn-secondary"
                                        data-guru-id="{{ $guru->id }}"
                                        data-guru-name="{{ $guru->name }}"
                                        data-guru-mapel="{{ $guru->mata_pelajaran ?? '' }}"
                                        data-guru-lembaga="{{ $guru->primary_lembaga_id }}"
                                        onclick="bukaModalTetapkanMapelSpesifikBtn(this)"
                                        style="padding:5px 9px; font-size:12px; border-radius:6px; display:inline-flex; align-items:center; gap:4px; cursor:pointer;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px; height:13px;">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                    <span>{{ $guru->mata_pelajaran ? 'Ubah' : 'Tetapkan' }}</span>
                                </button>

                                {{-- Tombol Detail --}}
                                <button type="button" class="btn btn-secondary"
                                        data-guru="{{ htmlspecialchars(json_encode([
                                            'id' => $guru->id,
                                            'name' => $guru->name,
                                            'jabatan' => $guru->jabatan,
                                            'nip' => $guru->nip,
                                            'jenis_kelamin' => $guru->jenis_kelamin,
                                            'email' => $guru->email,
                                            'telepon' => $guru->telepon,
                                            'mata_pelajaran' => $guru->mata_pelajaran,
                                            'alamat' => $guru->alamat,
                                            'mapel_status' => $guru->mapel_status,
                                            'mapel_diajukan' => $guru->mapel_diajukan,
                                            'mapel_catatan' => $guru->mapel_catatan,
                                        ]), ENT_QUOTES, 'UTF-8') }}"
                                        onclick="bukaModalDetail(this)"
                                        style="padding:5px 7px; font-size:12px; border-radius:6px; cursor:pointer;" title="Detail">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:36px 16px; text-align:center; color:var(--text-muted);">
                            Tidak ada data guru yang sesuai filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div style="padding:14px 18px; border-top:1px solid var(--border-light); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="font-size:12.5px; color:var(--text-muted);">
                Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} guru
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
        @endif
    </div>

    {{-- Card List untuk Tampilan Mobile (<= 768px) --}}
    <div class="mobile-card-list">
        @forelse($users as $index => $guru)
        <div class="mobile-card-item">
            <div class="mobile-card-info">
                <div class="mobile-card-title">
                    <span>{{ $guru->name }}</span>
                    @if($guru->mapel_status === 'disetujui')
                    <svg viewBox="0 0 24 24" fill="#10B981" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; vertical-align:middle; display:inline-block;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="9 12 11 14 15 10"></polyline>
                    </svg>
                    @endif
                </div>
                <div class="mobile-card-subtitle">
                    @if($guru->nip)
                        <span>NIP: {{ $guru->nip }}</span>
                    @else
                        <span>{{ $guru->email }}</span>
                    @endif
                </div>

                <div class="mobile-card-meta-grid">
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Lembaga</span>
                        @if($guru->primaryLembaga)
                            <span class="mobile-card-pill mobile-card-pill-blue">{{ $guru->primaryLembaga->singkatan }}</span>
                        @elseif($guru->lembagas->isNotEmpty())
                            <span class="mobile-card-pill mobile-card-pill-blue">{{ $guru->lembagas->first()->singkatan }}</span>
                        @else
                            <span class="mobile-card-pill mobile-card-pill-gray">-</span>
                        @endif
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Mapel</span>
                        <div style="display:flex; flex-wrap:wrap; gap:3px;">
                            @forelse($guru->daftar_mata_pelajaran as $m)
                                <span class="mobile-card-pill mobile-card-pill-purple">{{ $m }}</span>
                            @empty
                                <span class="mobile-card-pill mobile-card-pill-gray">-</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Status</span>
                        @if($guru->mapel_status === 'menunggu')
                            <span class="status-pill status-pill-warning">Menunggu</span>
                        @elseif($guru->mapel_status === 'disetujui')
                            <span class="status-pill status-pill-active">Disetujui</span>
                        @elseif($guru->mapel_status === 'ditolak')
                            <span class="status-pill status-pill-inactive">Ditolak</span>
                        @else
                            <span class="status-pill" style="background:#f1f5f9; color:#64748b;">Belum Ajukan</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mobile-card-actions">
                <span class="mobile-card-action-title">Aksi</span>
                <div class="mobile-card-action-buttons">
                    <button type="button" class="mobile-btn-pastel mobile-btn-view"
                            data-guru="{{ htmlspecialchars(json_encode([
                                'id' => $guru->id,
                                'name' => $guru->name,
                                'jabatan' => $guru->jabatan,
                                'nip' => $guru->nip,
                                'jenis_kelamin' => $guru->jenis_kelamin,
                                'email' => $guru->email,
                                'telepon' => $guru->telepon,
                                'mata_pelajaran' => $guru->mata_pelajaran,
                                'alamat' => $guru->alamat,
                                'mapel_status' => $guru->mapel_status,
                                'mapel_diajukan' => $guru->mapel_diajukan,
                                'mapel_catatan' => $guru->mapel_catatan,
                            ]), ENT_QUOTES, 'UTF-8') }}"
                            onclick="bukaModalDetail(this)" title="Detail">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>

                    @if($guru->mapel_status === 'menunggu')
                    <button type="button" class="mobile-btn-pastel mobile-btn-success"
                            data-guru-id="{{ $guru->id }}"
                            data-guru-name="{{ $guru->name }}"
                            data-guru-mapel="{{ $guru->mapel_diajukan }}"
                            data-guru-catatan="{{ $guru->mapel_catatan ?? '' }}"
                            onclick="bukaModalApproveBtn(this)" title="Setujui">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>

                    <button type="button" class="mobile-btn-pastel mobile-btn-delete"
                            data-guru-id="{{ $guru->id }}"
                            data-guru-name="{{ $guru->name }}"
                            data-guru-mapel="{{ $guru->mapel_diajukan }}"
                            onclick="bukaModalTolakBtn(this)" title="Tolak">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    @endif

                    <button type="button" class="mobile-btn-pastel mobile-btn-edit"
                            data-guru-id="{{ $guru->id }}"
                            data-guru-name="{{ $guru->name }}"
                            data-guru-mapel="{{ $guru->mata_pelajaran ?? '' }}"
                            data-guru-lembaga="{{ $guru->primary_lembaga_id }}"
                            onclick="bukaModalTetapkanMapelSpesifikBtn(this)"
                            title="Ubah Mapel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:10px; padding:24px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
            Tidak ada data guru yang sesuai filter.
        </div>
        @endforelse
    </div>

</div>

{{-- ========================================================================= --}}
{{-- MODAL 1: APPROVE PENGAJUAN MATA PELAJARAN (FILAMENT CLEAN) --}}
{{-- ========================================================================= --}}
<div id="modalApprove" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface); border-radius:14px; max-width:480px; width:100%; border:1px solid var(--border-light); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden;">
        <form id="formApprove" method="POST" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary);">Persetujuan Mata Pelajaran</h3>
                <button type="button" onclick="tutupModal('modalApprove')" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div style="background:var(--bg-main); border:1px solid var(--border-light); border-radius:10px; padding:12px 14px;">
                    <div style="font-size:11.5px; color:var(--text-muted);">Nama Guru:</div>
                    <div id="approveGuruNama" style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:2px;"></div>
                    <div style="margin-top:8px; font-size:11.5px; color:var(--text-muted);">Mata Pelajaran Diajukan:</div>
                    <div id="approveMapelNama" style="font-size:13.5px; font-weight:700; color:#047857; margin-top:2px;"></div>
                    <div id="approveGuruCatatanWrap" style="margin-top:8px; font-size:11.5px; color:var(--text-secondary); display:none;">
                        Catatan: <em id="approveGuruCatatan"></em>
                    </div>
                </div>

                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Mata Pelajaran yang Disetujui (Bisa Lebih Dari Satu) <span style="color:#EF4444;">*</span>
                    </label>

                    {{-- Tag Container --}}
                    <div id="approveTagsContainer" style="display:flex; flex-wrap:wrap; gap:6px; min-height:38px; padding:6px 10px; border:1px solid var(--border-light); border-radius:8px; background:var(--bg-main); align-items:center;">
                        <!-- Tag pills injected by JS -->
                    </div>
                    <input type="hidden" name="mata_pelajaran" id="approveMataPelajaranInput" required>

                    {{-- Pilihan Mapel dari Plotting Mata Pelajaran --}}
                    <div style="margin-top:8px;">
                        <div style="display:flex; flex-wrap:wrap; gap:5px;" id="approveAvailablePills">
                            @foreach($daftarMapel as $mapelItem)
                            <button type="button" class="btn-toggle-mapel-approve"
                                    data-mapel="{{ $mapelItem }}"
                                    onclick="toggleMapelApprove('{{ addslashes($mapelItem) }}')"
                                    style="padding:3px 9px; font-size:11.5px; font-weight:600; border-radius:6px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-secondary); cursor:pointer;">
                                + {{ $mapelItem }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <label for="approveCatatan" class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Catatan Yayasan (Opsional)
                    </label>
                    <textarea name="catatan" id="approveCatatan" rows="2" class="form-control"
                              style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; background:var(--bg-main);"></textarea>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main);">
                <button type="button" class="btn btn-secondary" onclick="tutupModal('modalApprove')" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; background:#10B981; border:none; color:#fff; font-weight:700;">
                    Setujui
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 2: TOLAK PENGAJUAN MATA PELAJARAN (FILAMENT CLEAN) --}}
{{-- ========================================================================= --}}
<div id="modalTolak" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface); border-radius:14px; max-width:460px; width:100%; border:1px solid var(--border-light); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden;">
        <form id="formTolak" method="POST" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary);">Tolak Pengajuan Mapel</h3>
                <button type="button" onclick="tutupModal('modalTolak')" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div style="background:var(--bg-main); border:1px solid var(--border-light); border-radius:10px; padding:12px 14px;">
                    <div style="font-size:11.5px; color:var(--text-muted);">Nama Guru:</div>
                    <div id="tolakGuruNama" style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:2px;"></div>
                    <div style="margin-top:8px; font-size:11.5px; color:var(--text-muted);">Mata Pelajaran Diajukan:</div>
                    <div id="tolakMapelNama" style="font-size:13.5px; font-weight:700; color:#B91C1C; margin-top:2px;"></div>
                </div>

                <div>
                    <label for="tolakCatatan" class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Alasan Penolakan <span style="color:#EF4444;">*</span>
                    </label>
                    <textarea name="catatan" id="tolakCatatan" rows="3" class="form-control" required
                              style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; background:var(--bg-main);"></textarea>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main);">
                <button type="button" class="btn btn-secondary" onclick="tutupModal('modalTolak')" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-danger" style="padding:7px 18px; border-radius:8px; font-size:12.5px; background:#EF4444; border:none; color:#fff; font-weight:700;">
                    Tolak Pengajuan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 3: TETAPKAN / UBAH MATA PELAJARAN (FILAMENT MULTI-SELECT CLEAN) --}}
{{-- ========================================================================= --}}
<div id="modalTetapkan" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface); border-radius:14px; max-width:500px; width:100%; border:1px solid var(--border-light); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden;">
        <form id="formTetapkan" method="POST" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
                <h3 id="modalTetapkanJudul" style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary);">Tetapkan Mata Pelajaran</h3>
                <button type="button" onclick="tutupModal('modalTetapkan')" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                {{-- Pilih Guru (jika modal dipanggil dari tombol header) --}}
                <div id="tetapkanPilihGuruWrap">
                    <label for="tetapkanPilihGuruSelect" class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Guru Sekolah <span style="color:#EF4444;">*</span>
                    </label>
                    <select id="tetapkanPilihGuruSelect" class="form-select" onchange="onGuruSelectedForAssign(this)"
                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; background:var(--bg-main);">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($users as $g)
                        <option value="{{ $g->id }}" data-mapel="{{ $g->mata_pelajaran }}" data-lembaga="{{ $g->primary_lembaga_id }}">
                            {{ $g->name }} ({{ $g->primaryLembaga?->singkatan ?? 'Yayasan' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Guru terpilih display (jika dipanggil dari row) --}}
                <div id="tetapkanGuruRowInfo" style="display:none; background:var(--bg-main); border:1px solid var(--border-light); border-radius:8px; padding:10px 12px;">
                    <div style="font-size:11.5px; color:var(--text-muted);">Nama Guru:</div>
                    <div id="tetapkanGuruRowNama" style="font-size:14px; font-weight:700; color:var(--text-primary); margin-top:2px;"></div>
                </div>

                {{-- Multi-Select Mata Pelajaran yang Diampu --}}
                <div>
                    <label class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Mata Pelajaran yang Diampu (Bisa Lebih Dari Satu) <span style="color:#EF4444;">*</span>
                    </label>

                    {{-- Tag Container (Pills of Selected Subjects) --}}
                    <div id="tetapkanTagsContainer" style="display:flex; flex-wrap:wrap; gap:6px; min-height:40px; padding:6px 10px; border:1px solid var(--border-light); border-radius:8px; background:var(--bg-main); align-items:center;">
                        <!-- Tag pills injected by JS -->
                    </div>
                    <input type="hidden" name="mata_pelajaran" id="tetapkanMapelInput" required>

                    {{-- Quick Select Dropdown & Clickable Badges (Strictly from Plotting Mata Pelajaran) --}}
                    <div style="margin-top:8px;">
                        <div style="display:flex; flex-wrap:wrap; gap:5px;" id="tetapkanAvailablePills">
                            @foreach($daftarMapel as $mapelItem)
                            <button type="button" class="btn-toggle-mapel-assign"
                                    data-mapel="{{ $mapelItem }}"
                                    onclick="toggleMapelAssign('{{ addslashes($mapelItem) }}')"
                                    style="padding:3px 9px; font-size:11.5px; font-weight:600; border-radius:6px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-secondary); cursor:pointer;">
                                + {{ $mapelItem }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Lembaga Penempatan (Opsional) --}}
                @if(isset($lembagas) && $lembagas->isNotEmpty())
                <div>
                    <label for="tetapkanLembagaSelect" class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Unit Lembaga
                    </label>
                    <select name="primary_lembaga_id" id="tetapkanLembagaSelect" class="form-select"
                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; background:var(--bg-main);">
                        <option value="">-- Tetap Sesuai Lembaga Saat Ini --</option>
                        @foreach($lembagas as $lem)
                        <option value="{{ $lem->id }}">{{ $lem->nama }} ({{ $lem->singkatan }})</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Catatan Otoritas --}}
                <div>
                    <label for="tetapkanCatatanInput" class="form-label" style="font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px; display:block;">
                        Catatan (Opsional)
                    </label>
                    <textarea name="catatan" id="tetapkanCatatanInput" rows="2" class="form-control"
                              style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light); font-size:13px; background:var(--bg-main);"></textarea>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main);">
                <button type="button" class="btn btn-secondary" onclick="tutupModal('modalTetapkan')" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700;">
                    Simpan &amp; Tetapkan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- MODAL 4: DETAIL LENGKAP PROFIL GURU (FILAMENT CLEAN) --}}
{{-- ========================================================================= --}}
<div id="modalDetail" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface); border-radius:14px; max-width:500px; width:100%; border:1px solid var(--border-light); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
            <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary);">Detail Guru</h3>
            <button type="button" onclick="tutupModal('modalDetail')" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
        </div>

        <div style="padding:20px; display:flex; flex-direction:column; gap:14px; max-height:75vh; overflow-y:auto;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div id="detailAvatar" style="width:44px; height:44px; border-radius:10px; background:#3B82F6; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800;"></div>
                <div>
                    <h4 id="detailNama" style="margin:0 0 2px 0; font-size:15px; font-weight:700; color:var(--text-primary);"></h4>
                    <div id="detailJabatan" style="font-size:12px; color:var(--text-secondary);"></div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; background:var(--bg-main); padding:12px; border-radius:10px; border:1px solid var(--border-light);">
                <div>
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">NIP / NUPTK</div>
                    <div id="detailNip" style="font-weight:700; font-size:12.5px; color:var(--text-primary); margin-top:2px;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Jenis Kelamin</div>
                    <div id="detailGender" style="font-weight:700; font-size:12.5px; color:var(--text-primary); margin-top:2px;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Email</div>
                    <div id="detailEmail" style="font-weight:600; font-size:12.5px; color:var(--text-primary); margin-top:2px; word-break:break-all;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Telepon / WA</div>
                    <div id="detailTelepon" style="font-weight:600; font-size:12.5px; color:#10B981; margin-top:2px;">-</div>
                </div>
            </div>

            <div style="background:var(--bg-main); padding:12px; border-radius:10px; border:1px solid var(--border-light);">
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase; margin-bottom:4px;">Mata Pelajaran Diampu</div>
                <div id="detailMapelAktif" style="display:flex; flex-wrap:wrap; gap:5px;">-</div>
            </div>

            <div id="detailPengajuanBox" style="background:rgba(245, 158, 11, 0.08); border:1px solid rgba(245, 158, 11, 0.25); padding:12px; border-radius:10px;">
                <div style="font-size:11px; color:#B45309; text-transform:uppercase; font-weight:700;">Status Pengajuan</div>
                <div id="detailPengajuanStatus" style="font-weight:700; font-size:13px; margin-top:2px;">-</div>
                <div id="detailPengajuanCatatan" style="font-size:11.5px; color:var(--text-secondary); margin-top:3px;"></div>
            </div>

            <div style="background:var(--bg-main); padding:12px; border-radius:10px; border:1px solid var(--border-light);">
                <div style="font-size:11px; color:var(--text-muted); text-transform:uppercase;">Alamat</div>
                <div id="detailAlamat" style="font-size:12.5px; color:var(--text-primary); margin-top:2px;">-</div>
            </div>
        </div>

        <div style="padding:12px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; background:var(--bg-main);">
            <button type="button" class="btn btn-secondary" onclick="tutupModal('modalDetail')" style="padding:7px 16px; border-radius:8px; font-size:12.5px;">Tutup</button>
        </div>
    </div>
</div>

<script>
    // State management for Multi-Select Mapel
    let selectedAssignMapels = [];
    let selectedApproveMapels = [];

    function tutupModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) el.style.display = 'none';
    }

    // Helper: parse comma-separated string to clean array
    function parseMapelString(str) {
        if (!str) return [];
        return str.split(/[,|;]/)
            .map(s => s.trim())
            .filter(s => s.length > 0);
    }

    // Helper: update Assign Tags UI
    function renderAssignTags() {
        const container = document.getElementById('tetapkanTagsContainer');
        const hiddenInput = document.getElementById('tetapkanMapelInput');
        if (!container || !hiddenInput) return;

        hiddenInput.value = selectedAssignMapels.join(', ');
        container.innerHTML = '';

        if (selectedAssignMapels.length === 0) {
            container.innerHTML = '<span style="color:var(--text-muted); font-size:12px; font-style:italic;">Belum ada mata pelajaran dipilih</span>';
        } else {
            selectedAssignMapels.forEach((mapel, idx) => {
                const tag = document.createElement('span');
                tag.style.cssText = 'display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:600; background:#f0f9ff; color:#0284c7; border:1px solid #bae6fd;';
                tag.innerHTML = `<span>${mapel}</span><button type="button" onclick="removeAssignMapel(${idx})" style="background:none; border:none; color:#0284c7; cursor:pointer; padding:0; line-height:1; font-size:13px; font-weight:700;">&times;</button>`;
                container.appendChild(tag);
            });
        }

        // Update button states
        document.querySelectorAll('#tetapkanAvailablePills .btn-toggle-mapel-assign').forEach(btn => {
            const m = btn.getAttribute('data-mapel');
            if (selectedAssignMapels.includes(m)) {
                btn.style.background = '#0284c7';
                btn.style.color = '#ffffff';
                btn.style.borderColor = '#0284c7';
                btn.innerHTML = `✓ ${m}`;
            } else {
                btn.style.background = 'var(--bg-surface)';
                btn.style.color = 'var(--text-secondary)';
                btn.style.borderColor = 'var(--border-light)';
                btn.innerHTML = `+ ${m}`;
            }
        });
    }

    function toggleMapelAssign(mapel) {
        const index = selectedAssignMapels.indexOf(mapel);
        if (index > -1) {
            selectedAssignMapels.splice(index, 1);
        } else {
            selectedAssignMapels.push(mapel);
        }
        renderAssignTags();
    }

    function removeAssignMapel(index) {
        selectedAssignMapels.splice(index, 1);
        renderAssignTags();
    }

    // Helper: update Approve Tags UI
    function renderApproveTags() {
        const container = document.getElementById('approveTagsContainer');
        const hiddenInput = document.getElementById('approveMataPelajaranInput');
        if (!container || !hiddenInput) return;

        hiddenInput.value = selectedApproveMapels.join(', ');
        container.innerHTML = '';

        if (selectedApproveMapels.length === 0) {
            container.innerHTML = '<span style="color:var(--text-muted); font-size:12px; font-style:italic;">Belum ada mata pelajaran dipilih</span>';
        } else {
            selectedApproveMapels.forEach((mapel, idx) => {
                const tag = document.createElement('span');
                tag.style.cssText = 'display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:600; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;';
                tag.innerHTML = `<span>${mapel}</span><button type="button" onclick="removeApproveMapel(${idx})" style="background:none; border:none; color:#047857; cursor:pointer; padding:0; line-height:1; font-size:13px; font-weight:700;">&times;</button>`;
                container.appendChild(tag);
            });
        }

        // Update button states
        document.querySelectorAll('#approveAvailablePills .btn-toggle-mapel-approve').forEach(btn => {
            const m = btn.getAttribute('data-mapel');
            if (selectedApproveMapels.includes(m)) {
                btn.style.background = '#10B981';
                btn.style.color = '#ffffff';
                btn.style.borderColor = '#10B981';
                btn.innerHTML = `✓ ${m}`;
            } else {
                btn.style.background = 'var(--bg-surface)';
                btn.style.color = 'var(--text-secondary)';
                btn.style.borderColor = 'var(--border-light)';
                btn.innerHTML = `+ ${m}`;
            }
        });
    }

    function toggleMapelApprove(mapel) {
        const index = selectedApproveMapels.indexOf(mapel);
        if (index > -1) {
            selectedApproveMapels.splice(index, 1);
        } else {
            selectedApproveMapels.push(mapel);
        }
        renderApproveTags();
    }

    function removeApproveMapel(index) {
        selectedApproveMapels.splice(index, 1);
        renderApproveTags();
    }

    // Modal Approve
    function bukaModalApproveBtn(btn) {
        const guruId = btn.getAttribute('data-guru-id');
        const guruNama = btn.getAttribute('data-guru-name');
        const mapelNama = btn.getAttribute('data-guru-mapel');
        const catatanGuru = btn.getAttribute('data-guru-catatan') || '';
        bukaModalApprove(guruId, guruNama, mapelNama, catatanGuru);
    }

    function bukaModalApprove(guruId, guruNama, mapelNama, catatanGuru) {
        const form = document.getElementById('formApprove');
        if (form) form.action = '{{ url("/guru-sekolah") }}/' + guruId + '/approve';
        const namaEl = document.getElementById('approveGuruNama');
        if (namaEl) namaEl.innerText = guruNama || '-';
        const mapelEl = document.getElementById('approveMapelNama');
        if (mapelEl) mapelEl.innerText = mapelNama || '-';

        selectedApproveMapels = parseMapelString(mapelNama);
        renderApproveTags();

        const catatanWrap = document.getElementById('approveGuruCatatanWrap');
        const catatanEl = document.getElementById('approveGuruCatatan');
        if (catatanGuru && catatanGuru.trim() !== '') {
            if (catatanEl) catatanEl.innerText = catatanGuru;
            if (catatanWrap) catatanWrap.style.display = 'block';
        } else {
            if (catatanWrap) catatanWrap.style.display = 'none';
        }

        const catatanYayasan = document.getElementById('approveCatatan');
        if (catatanYayasan) catatanYayasan.value = 'Disetujui oleh Yayasan';
        const m = document.getElementById('modalApprove');
        if (m) m.style.display = 'flex';
    }

    // Modal Tolak
    function bukaModalTolakBtn(btn) {
        const guruId = btn.getAttribute('data-guru-id');
        const guruNama = btn.getAttribute('data-guru-name');
        const mapelNama = btn.getAttribute('data-guru-mapel');
        bukaModalTolak(guruId, guruNama, mapelNama);
    }

    function bukaModalTolak(guruId, guruNama, mapelNama) {
        const form = document.getElementById('formTolak');
        if (form) form.action = '{{ url("/guru-sekolah") }}/' + guruId + '/reject';
        const namaEl = document.getElementById('tolakGuruNama');
        if (namaEl) namaEl.innerText = guruNama || '-';
        const mapelEl = document.getElementById('tolakMapelNama');
        if (mapelEl) mapelEl.innerText = mapelNama || '-';
        const catatanEl = document.getElementById('tolakCatatan');
        if (catatanEl) catatanEl.value = '';
        const m = document.getElementById('modalTolak');
        if (m) m.style.display = 'flex';
    }

    // Modal Tetapkan Mapel (Umum / Header Button)
    function bukaModalTetapkanMapel() {
        const judul = document.getElementById('modalTetapkanJudul');
        if (judul) judul.innerText = 'Tetapkan Mata Pelajaran Guru';
        const wrapPilih = document.getElementById('tetapkanPilihGuruWrap');
        if (wrapPilih) wrapPilih.style.display = 'block';
        const rowInfo = document.getElementById('tetapkanGuruRowInfo');
        if (rowInfo) rowInfo.style.display = 'none';
        const selectGuru = document.getElementById('tetapkanPilihGuruSelect');
        if (selectGuru) selectGuru.value = '';

        selectedAssignMapels = [];
        renderAssignTags();

        const catatanInput = document.getElementById('tetapkanCatatanInput');
        if (catatanInput) catatanInput.value = 'Ditetapkan langsung oleh Yayasan';
        const form = document.getElementById('formTetapkan');
        if (form) form.action = '{{ url("/guru-sekolah") }}/0/assign';
        const m = document.getElementById('modalTetapkan');
        if (m) m.style.display = 'flex';
    }

    function onGuruSelectedForAssign(selectEl) {
        const guruId = selectEl.value;
        if (!guruId) return;
        const form = document.getElementById('formTetapkan');
        if (form) form.action = '{{ url("/guru-sekolah") }}/' + guruId + '/assign';
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        const existingMapel = selectedOption.dataset.mapel || '';
        const existingLembaga = selectedOption.dataset.lembaga || '';

        selectedAssignMapels = parseMapelString(existingMapel);
        renderAssignTags();

        const lemSelect = document.getElementById('tetapkanLembagaSelect');
        if (lemSelect && existingLembaga) {
            lemSelect.value = existingLembaga;
        }
    }

    // Modal Tetapkan Mapel (Spesifik Row)
    function bukaModalTetapkanMapelSpesifikBtn(btn) {
        const guruId = btn.getAttribute('data-guru-id');
        const guruNama = btn.getAttribute('data-guru-name');
        const mapelSaatIni = btn.getAttribute('data-guru-mapel') || '';
        const lembagaId = btn.getAttribute('data-guru-lembaga') || '';
        bukaModalTetapkanMapelSpesifik(guruId, guruNama, mapelSaatIni, lembagaId);
    }

    function bukaModalTetapkanMapelSpesifik(guruId, guruNama, mapelSaatIni, lembagaId) {
        const judul = document.getElementById('modalTetapkanJudul');
        if (judul) judul.innerText = 'Ubah / Tetapkan Mata Pelajaran';
        const wrapPilih = document.getElementById('tetapkanPilihGuruWrap');
        if (wrapPilih) wrapPilih.style.display = 'none';
        const rowInfo = document.getElementById('tetapkanGuruRowInfo');
        if (rowInfo) rowInfo.style.display = 'block';
        const rowNama = document.getElementById('tetapkanGuruRowNama');
        if (rowNama) rowNama.innerText = guruNama || '-';

        selectedAssignMapels = parseMapelString(mapelSaatIni);
        renderAssignTags();

        const lemSelect = document.getElementById('tetapkanLembagaSelect');
        if (lemSelect && lembagaId) {
            lemSelect.value = lembagaId;
        }
        const catatanInput = document.getElementById('tetapkanCatatanInput');
        if (catatanInput) catatanInput.value = 'Ditetapkan langsung oleh Yayasan';
        const form = document.getElementById('formTetapkan');
        if (form) form.action = '{{ url("/guru-sekolah") }}/' + guruId + '/assign';
        const m = document.getElementById('modalTetapkan');
        if (m) m.style.display = 'flex';
    }

    // Modal Detail Profil Guru
    function bukaModalDetail(target) {
        let guru = target;
        if (target && target instanceof HTMLElement) {
            try {
                guru = JSON.parse(target.getAttribute('data-guru'));
            } catch (e) {
                console.error('Error parsing data-guru', e);
                return;
            }
        }
        if (!guru) return;

        const avatar = document.getElementById('detailAvatar');
        if (avatar) avatar.innerText = (guru.name || 'G').substring(0, 1).toUpperCase();
        const nama = document.getElementById('detailNama');
        if (nama) nama.innerText = guru.name || '-';
        const jabatan = document.getElementById('detailJabatan');
        if (jabatan) jabatan.innerText = guru.jabatan || 'Guru Pengajar';
        const nip = document.getElementById('detailNip');
        if (nip) nip.innerText = guru.nip || '-';
        const gender = document.getElementById('detailGender');
        if (gender) gender.innerText = guru.jenis_kelamin || '-';
        const email = document.getElementById('detailEmail');
        if (email) email.innerText = guru.email || '-';
        const telepon = document.getElementById('detailTelepon');
        if (telepon) telepon.innerText = guru.telepon || '-';

        // Render Mata Pelajaran Aktif sebagai pills
        const mapelBox = document.getElementById('detailMapelAktif');
        if (mapelBox) {
            const list = parseMapelString(guru.mata_pelajaran);
            if (list.length === 0) {
                mapelBox.innerHTML = '<span style="color:var(--text-muted); font-size:12px;">-</span>';
            } else {
                mapelBox.innerHTML = list.map(m => `<span style="padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:600; background:rgba(2, 132, 199, 0.08); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">${m}</span>`).join('');
            }
        }

        const alamat = document.getElementById('detailAlamat');
        if (alamat) alamat.innerText = guru.alamat || '-';

        const statusBox = document.getElementById('detailPengajuanBox');
        const statusText = document.getElementById('detailPengajuanStatus');
        const catatanText = document.getElementById('detailPengajuanCatatan');

        if (statusBox && statusText && catatanText) {
            if (guru.mapel_status === 'menunggu') {
                statusBox.style.background = 'rgba(245, 158, 11, 0.1)';
                statusBox.style.borderColor = 'rgba(245, 158, 11, 0.3)';
                statusText.style.color = '#B45309';
                statusText.innerText = 'Menunggu: ' + (guru.mapel_diajukan || '-');
                catatanText.innerText = guru.mapel_catatan ? 'Catatan: "' + guru.mapel_catatan + '"' : '';
            } else if (guru.mapel_status === 'disetujui') {
                statusBox.style.background = 'rgba(16, 185, 129, 0.1)';
                statusBox.style.borderColor = 'rgba(16, 185, 129, 0.3)';
                statusText.style.color = '#047857';
                statusText.innerText = 'Disetujui: ' + (guru.mata_pelajaran || '-');
                catatanText.innerText = guru.mapel_catatan ? 'Catatan: "' + guru.mapel_catatan + '"' : '';
            } else if (guru.mapel_status === 'ditolak') {
                statusBox.style.background = 'rgba(239, 68, 68, 0.1)';
                statusBox.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                statusText.style.color = '#B91C1C';
                statusText.innerText = 'Ditolak: ' + (guru.mapel_diajukan || '-');
                catatanText.innerText = guru.mapel_catatan ? 'Alasan: "' + guru.mapel_catatan + '"' : '';
            } else {
                statusBox.style.background = 'rgba(0,0,0,0.04)';
                statusBox.style.borderColor = 'var(--border-light)';
                statusText.style.color = 'var(--text-muted)';
                statusText.innerText = 'Belum Ada Pengajuan';
                catatanText.innerText = '';
            }
        }

        const m = document.getElementById('modalDetail');
        if (m) m.style.display = 'flex';
    }

    // Tutup modal jika klik backdrop atau tombol Escape
    document.addEventListener('click', function(event) {
        ['modalApprove', 'modalTolak', 'modalTetapkan', 'modalDetail'].forEach(id => {
            const m = document.getElementById(id);
            if (m && event.target === m) {
                m.style.display = 'none';
            }
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            ['modalApprove', 'modalTolak', 'modalTetapkan', 'modalDetail'].forEach(id => {
                const m = document.getElementById(id);
                if (m) m.style.display = 'none';
            });
        }
    });
</script>

@endsection
