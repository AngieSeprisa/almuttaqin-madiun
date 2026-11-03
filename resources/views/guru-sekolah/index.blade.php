@extends('layouts.app')

@section('title', 'Guru Sekolah')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    @if(isset($sekolahLembagas) && $sekolahLembagas->count() > 1)
    <div style="display:flex; align-items:center; gap:8px;">
        <select class="form-select" onchange="window.location.href='{{ route('guru-sekolah.index') }}?lembaga_id=' + this.value"
                style="padding:8px 14px; font-size:13px; font-weight:600; border-radius:10px; background:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border-light, #e2e8f0);">
            <option value="">Semua Sekolah</option>
            @foreach($sekolahLembagas as $lem)
            <option value="{{ $lem->id }}" {{ request('lembaga_id') == $lem->id ? 'selected' : '' }}>
                {{ $lem->nama_singkat ?? $lem->singkatan }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- Tombol Tambah Guru (Filament Primary Button) --}}
    @if(!auth()->check() || auth()->user()->isYayasan() || auth()->user()->canDo('guru_sekolah', 'create'))
    <button type="button" class="btn btn-primary" onclick="bukaModalTambahGuru()" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Guru</span>
    </button>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:1300px; margin:0 auto;">

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
            <div style="font-weight:700; margin-bottom:4px;">Gagal memproses data:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Highlight Pengingat Persetujuan Yayasan (Filament Clean) --}}
    @if(isset($stats['menunggu']) && $stats['menunggu'] > 0)
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
        <a href="{{ route('guru-sekolah.index', ['status' => 'menunggu']) }}" class="btn" style="padding:7px 14px; font-size:12.5px; font-weight:700; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; background:#F59E0B; color:#ffffff; border:none;">
            <span>Lihat Pengajuan</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
    </div>
    @endif

    {{-- Kartu Statistik Ringkas (Filament Style: Bersih Tanpa Deskripsi) --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
        {{-- Total Guru Sekolah --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(59, 130, 246, 0.08); display:flex; align-items:center; justify-content:center; color:#2563eb; flex-shrink:0; border:1px solid rgba(59, 130, 246, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Total Guru Sekolah</div>
                <div style="font-size:24px; font-weight:800; color:var(--text-primary, #0f172a); margin-top:2px; line-height:1.2;">{{ $stats['total_guru'] }}</div>
            </div>
        </div>

        {{-- Guru SMP --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(2, 132, 199, 0.08); display:flex; align-items:center; justify-content:center; color:#0284c7; flex-shrink:0; border:1px solid rgba(2, 132, 199, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Guru SMP</div>
                <div style="font-size:24px; font-weight:800; color:#0284c7; margin-top:2px; line-height:1.2;">{{ $stats['smp'] }}</div>
            </div>
        </div>

        {{-- Guru SMA --}}
        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(16, 185, 129, 0.08); display:flex; align-items:center; justify-content:center; color:#059669; flex-shrink:0; border:1px solid rgba(16, 185, 129, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <circle cx="12" cy="8" r="7"></circle>
                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Guru SMA</div>
                <div style="font-size:24px; font-weight:800; color:#059669; margin-top:2px; line-height:1.2;">{{ $stats['sma'] }}</div>
            </div>
        </div>

        {{-- Menunggu Persetujuan --}}
        <div style="background:var(--bg-surface); border:1px solid {{ $stats['menunggu'] > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--border-light, #e2e8f0)' }}; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(245, 158, 11, 0.1); display:flex; align-items:center; justify-content:center; color:#d97706; flex-shrink:0; border:1px solid rgba(245, 158, 11, 0.2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#d97706;">Menunggu Persetujuan</div>
                <div style="font-size:24px; font-weight:800; color:#d97706; margin-top:2px; line-height:1.2;">{{ $stats['menunggu'] }}</div>
            </div>
        </div>
    </div>

    {{-- Kontainer Tabel Filament PHP (Persis Gaya Menu Siswa) --}}
    <div class="fi-ta-ctn" style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        
        {{-- Filament Table Toolbar (Search & Filter Sesuai Menu Siswa) --}}
        <div style="padding:14px 18px; border-bottom:1px solid var(--border-subtle, #f1f5f9); background:var(--bg-surface);">
            <form action="{{ route('guru-sekolah.index') }}" method="GET" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin:0;">
                
                {{-- Group Filter Sisi Kiri --}}
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    {{-- Tombol Aksi Dropdown Filament Style --}}
                    <div style="position:relative; display:inline-block;">
                        <button type="button" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:12.5px; font-weight:600; border-radius:8px; background:var(--bg-main, #f8fafc); border:1px solid var(--border-light, #cbd5e1); color:var(--text-secondary, #475569); cursor:default;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-muted, #94a3b8);">
                                <circle cx="12" cy="12" r="1"></circle>
                                <circle cx="12" cy="5" r="1"></circle>
                                <circle cx="12" cy="19" r="1"></circle>
                            </svg>
                            <span>Aksi</span>
                        </button>
                    </div>

                    {{-- Filter Status Pengajuan --}}
                    <div style="position:relative; display:inline-block;">
                        <select name="status" class="form-select" onchange="this.form.submit()"
                                style="padding:7px 28px 7px 12px; font-size:12.5px; font-weight:600; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a); cursor:pointer;">
                            <option value="">Semua Status</option>
                            <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                            <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                            <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                        </select>
                    </div>

                    {{-- Filter Lembaga Sekolah --}}
                    @if(isset($sekolahLembagas) && $sekolahLembagas->count() > 1)
                    <div style="position:relative; display:inline-block;">
                        <select name="lembaga_id" class="form-select" onchange="this.form.submit()"
                                style="padding:7px 28px 7px 12px; font-size:12.5px; font-weight:600; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a); cursor:pointer;">
                            <option value="">Semua Sekolah</option>
                            @foreach($sekolahLembagas as $lem)
                                <option value="{{ $lem->id }}" {{ request('lembaga_id') == $lem->id ? 'selected' : '' }}>
                                    {{ $lem->nama_singkat ?? $lem->singkatan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Tombol Reset --}}
                    @if(request('status') || request('lembaga_id') || request('search'))
                    <a href="{{ route('guru-sekolah.index') }}" class="btn btn-secondary" style="padding:7px 12px; font-size:12px; font-weight:600; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                    @endif
                </div>

                {{-- Kolom Pencarian & Icon Tools Sisi Kanan --}}
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="position:relative; width:100%; min-width:260px; max-width:320px;">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari guru, NIP, mapel..." autocomplete="off"
                               style="width:100%; padding:7px 32px 7px 34px; border-radius:8px; font-size:13px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a);">
                        <div style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted, #94a3b8); pointer-events:none; display:flex; align-items:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                        @if(request('search'))
                        <a href="{{ route('guru-sekolah.index', request()->except('search')) }}" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); color:var(--text-muted, #94a3b8); text-decoration:none; font-size:16px;">&times;</a>
                        @endif
                    </div>

                    {{-- Icon Filter Visual (Sesuai Tampilan Filament Siswa) --}}
                    <div style="position:relative; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-muted, #64748b);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        @if(request('status') || request('lembaga_id'))
                        <span style="position:absolute; top:-4px; right:-4px; width:14px; height:14px; background:#0284c7; color:#fff; border-radius:50%; font-size:9px; font-weight:800; display:flex; align-items:center; justify-content:center;">1</span>
                        @endif
                    </div>

                    {{-- Icon Kolom Visual --}}
                    <div style="display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-muted, #64748b);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                            <line x1="15" y1="3" x2="15" y2="21"></line>
                        </svg>
                    </div>
                </div>

            </form>
        </div>

        {{-- Tabel Data Filament PHP --}}
        <div style="overflow-x:auto;">
            <table class="fi-ta-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:var(--bg-main, #f8fafc); border-bottom:1px solid var(--border-light, #e2e8f0); color:var(--text-secondary, #64748b); font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 14px; width:36px; text-align:center;">
                            <input type="checkbox" disabled style="border-radius:4px; border:1px solid var(--border-light, #cbd5e1);">
                        </th>
                        <th style="padding:12px 14px; width:45px; text-align:center;">#</th>
                        <th style="padding:12px 16px;">Guru</th>
                        <th style="padding:12px 16px;">Sekolah</th>
                        <th style="padding:12px 16px;">Mata Pelajaran (Diampu)</th>
                        <th style="padding:12px 16px;">Status Pengajuan</th>
                        <th style="padding:12px 16px; text-align:center; width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $guru)
                    @php
                        $rowNum = method_exists($users, 'firstItem') ? ($users->firstItem() + $index) : ($index + 1);
                        $mapels = $guru->daftar_mata_pelajaran;
                    @endphp
                    <tr style="border-bottom:1px solid var(--border-subtle, #f1f5f9); transition:background 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.012)'" onmouseout="this.style.background='transparent'">
                        {{-- Checkbox --}}
                        <td style="padding:12px 14px; text-align:center;">
                            <input type="checkbox" style="border-radius:4px; border:1px solid var(--border-light, #cbd5e1); cursor:pointer;">
                        </td>

                        {{-- No --}}
                        <td style="padding:12px 14px; text-align:center; color:var(--text-muted, #94a3b8); font-size:12px;">
                            {{ $rowNum }}
                        </td>

                        {{-- Identitas Guru --}}
                        <td style="padding:12px 16px;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($guru->foto_url)
                                    <img src="{{ $guru->foto_url }}" alt="{{ $guru->name }}" style="width:34px; height:34px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid var(--border-light, #e2e8f0);">
                                @else
                                    <div style="width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg, #0284c7, #0369a1); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:12.5px; flex-shrink:0;">
                                        {{ $guru->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary, #0f172a); font-size:13.5px; display:flex; align-items:center; gap:6px;">
                                        <span>{{ $guru->name }}</span>
                                        @if($guru->mapel_status === 'disetujui')
                                        <svg viewBox="0 0 24 24" fill="#10B981" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;" title="Terverifikasi">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="9 12 11 14 15 10"></polyline>
                                        </svg>
                                        @endif
                                    </div>
                                    <div style="font-size:11.5px; color:var(--text-muted, #94a3b8); margin-top:1px;">
                                        @if($guru->nip)
                                            <span>NIP: {{ $guru->nip }}</span>
                                        @else
                                            <span>{{ $guru->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Lembaga Sekolah --}}
                        <td style="padding:12px 16px;">
                            @php
                                $lembagaKode = strtolower($guru->primaryLembaga?->kode ?? '');
                                $lembagaSingkat = $guru->primaryLembaga?->singkatan ?? ($guru->lembagas->first()?->singkatan ?? '-');
                            @endphp
                            @if(in_array($lembagaKode, ['smp']) || strtoupper($lembagaSingkat) === 'SMP')
                                <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">
                                    SMP
                                </span>
                            @elseif(in_array($lembagaKode, ['sma']) || strtoupper($lembagaSingkat) === 'SMA')
                                <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                                    SMA
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:600; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;">
                                    {{ $lembagaSingkat }}
                                </span>
                            @endif
                        </td>

                        {{-- Mata Pelajaran (Diampu) - Multi Mapel Didukung --}}
                        <td style="padding:12px 16px;">
                            @if(!empty($mapels))
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    @foreach($mapels as $m)
                                        <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:600; background:rgba(2, 132, 199, 0.08); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">
                                            {{ $m }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted, #94a3b8); font-size:12px;">-</span>
                            @endif
                        </td>

                        {{-- Status Pengajuan (Filament Status Badge dengan Dot) --}}
                        <td style="padding:12px 16px;">
                            @if($guru->mapel_status === 'menunggu')
                                <div style="display:inline-flex; flex-direction:column; gap:4px;">
                                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#fef3c7; color:#b45309; border:1px solid #fde68a;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#d97706; display:inline-block;"></span>
                                        <span>Menunggu Persetujuan</span>
                                    </span>
                                    @if(!empty($guru->daftar_mapel_diajukan))
                                        <div style="font-size:11px; color:#92400e;">
                                            Diajukan: {{ implode(', ', $guru->daftar_mapel_diajukan) }}
                                        </div>
                                    @endif
                                </div>
                            @elseif($guru->mapel_status === 'disetujui')
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                                    <span>Disetujui</span>
                                </span>
                            @elseif($guru->mapel_status === 'ditolak')
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
                                    <span>Ditolak</span>
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:600; background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">
                                    <span style="width:6px; height:6px; border-radius:50%; background:#94a3b8; display:inline-block;"></span>
                                    <span>Belum Mengajukan</span>
                                </span>
                            @endif
                        </td>

                        {{-- Aksi (Filament Action Buttons) --}}
                        <td style="padding:12px 16px; text-align:center;">
                            <div style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                @if($guru->mapel_status === 'menunggu')
                                    {{-- Tombol Approve --}}
                                    <form action="{{ route('guru-sekolah.approve', $guru->id) }}" method="POST" style="display:inline; margin:0;">
                                        @csrf
                                        <button type="submit" class="btn" title="Setujui Pengajuan"
                                                style="padding:4px 9px; font-size:11.5px; background:#10b981; border:none; color:#ffffff; font-weight:700; border-radius:6px; display:inline-flex; align-items:center; gap:3px; cursor:pointer;">
                                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                            <span>Approve</span>
                                        </button>
                                    </form>

                                    {{-- Tombol Tolak --}}
                                    <button type="button" class="btn" onclick="bukaModalTolak({{ $guru->id }}, '{{ addslashes($guru->name) }}')" title="Tolak Pengajuan"
                                            style="padding:4px 8px; font-size:11.5px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); color:#dc2626; font-weight:600; border-radius:6px; cursor:pointer;">
                                        Tolak
                                    </button>
                                @endif

                                {{-- Tombol Ubah Guru --}}
                                <button type="button" class="btn btn-secondary" title="Ubah Data Guru"
                                        onclick="bukaModalUbah({{ $guru->id }}, '{{ addslashes($guru->name) }}', {{ $guru->primary_lembaga_id ?: 'null' }}, '{{ addslashes($guru->mata_pelajaran ?? '') }}')"
                                        style="padding:5px 8px; border-radius:6px; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:4px;">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    <span>Ubah</span>
                                </button>

                                {{-- Tombol Detail --}}
                                <button type="button" class="btn btn-secondary" onclick="bukaModalDetail({{ $guru->id }}, '{{ addslashes($guru->name) }}', '{{ addslashes($guru->email) }}', '{{ addslashes($guru->nip ?? '-') }}', '{{ addslashes($lembagaSingkat) }}', '{{ addslashes($guru->mata_pelajaran ?? '-') }}', '{{ $guru->mapel_status }}')" title="Detail"
                                        style="padding:5px 7px; border-radius:6px; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </button>

                                {{-- Tombol Hapus dari Guru Sekolah --}}
                                <form action="{{ route('guru-sekolah.destroy', $guru->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Hapus {{ $guru->name }} dari daftar Guru Sekolah?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn" title="Hapus dari Guru Sekolah"
                                            style="padding:5px 7px; border-radius:6px; font-size:12px; background:rgba(239, 68, 68, 0.08); color:#dc2626; border:1px solid rgba(239, 68, 68, 0.2); cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:48px 16px; color:var(--text-muted, #94a3b8);">
                            <div style="font-size:14px; font-weight:600; color:var(--text-secondary, #64748b);">Tidak ada guru sekolah yang terdaftar.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Filament Style --}}
        @if($users->hasPages())
        <div style="padding:12px 18px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="font-size:12.5px; color:var(--text-muted, #94a3b8);">
                Menampilkan {{ $users->firstItem() }} sampai {{ $users->lastItem() }} dari {{ $users->total() }} hasil
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

{{-- ========================================================
     MODAL 1: TAMBAH GURU SEKOLAH
     Pop up form tambah guru berisi:
     - Nama (diambil dari akun yang terdaftar)
     - Lembaga (SMP/SMA) isinya hanya SMP dan SMA
     - Mapel (diambil dari mapel di menu plotting mapel sesuai lembaga)
     - Bersih tanpa deskripsi
     ======================================================== --}}
<div id="modalTambahGuru" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:520px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left;">
        <form action="{{ route('guru-sekolah.store') }}" method="POST" id="formTambahGuru" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Tambah Guru</h3>
                <button type="button" onclick="tutupModalTambahGuru()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                {{-- Nama Guru (Akun Terdaftar) --}}
                <div>
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Nama Guru <span style="color:#ef4444;">*</span></label>
                    <select name="user_id" id="tambah_user_id" class="form-select" required
                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                        <option value="">-- Pilih Akun Terdaftar --</option>
                        @foreach($akunUsers as $akun)
                            <option value="{{ $akun->id }}">{{ $akun->name }} ({{ $akun->nip ? 'NIP: ' . $akun->nip : $akun->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Lembaga (HANYA SMP dan SMA) --}}
                <div>
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Lembaga <span style="color:#ef4444;">*</span></label>
                    <select name="lembaga_id" id="tambah_lembaga_id" class="form-select" required onchange="filterMapelTambah(this.value)"
                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                        <option value="">-- Pilih Lembaga (SMP / SMA) --</option>
                        @foreach($sekolahLembagas as $lem)
                            <option value="{{ $lem->id }}">{{ $lem->nama_singkat ?? $lem->singkatan }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Mata Pelajaran yang Diampu (Dropdown Multi-Select) --}}
                <div style="position:relative;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Mata Pelajaran yang Diampu <span style="color:#ef4444;">*</span></label>
                    
                    {{-- Dropdown Trigger (Sama Persis Seperti Select Lembaga) --}}
                    <div id="tambah_mapel_dropdown_btn" class="form-select" onclick="toggleMapelDropdown('tambah', event)"
                         style="width:100%; min-height:38px; padding:6px 36px 6px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc); color:var(--text-primary); cursor:pointer; display:flex; align-items:center; position:relative; box-sizing:border-box; user-select:none;">
                        <div id="tambah_mapel_display" style="display:flex; align-items:center; flex-wrap:wrap; gap:4px; width:100%;">
                            <span style="color:var(--text-muted, #94a3b8);">-- Pilih Lembaga Terlebih Dahulu --</span>
                        </div>
                        <div style="position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--text-muted, #94a3b8); display:flex; align-items:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>

                    {{-- Dropdown Menu List --}}
                    <div id="tambah_mapel_menu"
                         style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.12); z-index:1000; max-height:220px; overflow-y:auto; padding:6px;">
                        <div style="font-size:12.5px; color:var(--text-muted, #94a3b8); padding:8px 10px;">Pilih lembaga sekolah terlebih dahulu.</div>
                    </div>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc);">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahGuru()" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700;">Simpan Guru</button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================
     MODAL 2: UBAH GURU SEKOLAH
     ======================================================== --}}
<div id="modalUbahGuru" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:520px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left;">
        <form action="" method="POST" id="formUbahGuru" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Ubah Data Guru</h3>
                <button type="button" onclick="tutupModalUbahGuru()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                {{-- Nama Guru (Static Display) --}}
                <div>
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Nama Guru</label>
                    <input type="text" id="ubah_nama_guru" class="form-control" readonly
                           style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:#f1f5f9; color:var(--text-primary); font-weight:600;">
                </div>

                {{-- Lembaga (HANYA SMP dan SMA) --}}
                <div>
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Lembaga <span style="color:#ef4444;">*</span></label>
                    <select name="primary_lembaga_id" id="ubah_lembaga_id" class="form-select" required onchange="filterMapelUbah(this.value)"
                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                        @foreach($sekolahLembagas as $lem)
                            <option value="{{ $lem->id }}">{{ $lem->nama_singkat ?? $lem->singkatan }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Mata Pelajaran yang Diampu (Dropdown Multi-Select) --}}
                <div style="position:relative;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Mata Pelajaran yang Diampu <span style="color:#ef4444;">*</span></label>
                    
                    {{-- Dropdown Trigger (Sama Persis Seperti Select Lembaga) --}}
                    <div id="ubah_mapel_dropdown_btn" class="form-select" onclick="toggleMapelDropdown('ubah', event)"
                         style="width:100%; min-height:38px; padding:6px 36px 6px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc); color:var(--text-primary); cursor:pointer; display:flex; align-items:center; position:relative; box-sizing:border-box; user-select:none;">
                        <div id="ubah_mapel_display" style="display:flex; align-items:center; flex-wrap:wrap; gap:4px; width:100%;">
                            <span style="color:var(--text-muted, #94a3b8);">-- Pilih Mata Pelajaran --</span>
                        </div>
                        <div style="position:absolute; right:12px; top:50%; transform:translateY(-50%); pointer-events:none; color:var(--text-muted, #94a3b8); display:flex; align-items:center;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>

                    {{-- Dropdown Menu List --}}
                    <div id="ubah_mapel_menu"
                         style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.12); z-index:1000; max-height:220px; overflow-y:auto; padding:6px;">
                    </div>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc);">
                <button type="button" class="btn btn-secondary" onclick="tutupModalUbahGuru()" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================
     MODAL 3: TOLAK PENGAJUAN MAPEL
     ======================================================== --}}
<div id="modalTolakPengajuan" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:460px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left;">
        <form action="" method="POST" id="formTolakPengajuan" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:#dc2626;">Tolak Pengajuan Mapel</h3>
                <button type="button" onclick="tutupModalTolak()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:12px;">
                <div style="font-size:13px; color:var(--text-primary);">
                    Tolak pengajuan mata pelajaran untuk guru <strong id="tolak_guru_nama"></strong>?
                </div>
                <div>
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Alasan Penolakan <span style="color:#ef4444;">*</span></label>
                    <textarea name="catatan" id="tolak_catatan" rows="3" class="form-control" required placeholder="Tuliskan catatan perbaikan..."
                              style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);"></textarea>
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc);">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTolak()" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700; background:#dc2626; border:none; color:#ffffff;">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

{{-- ========================================================
     MODAL 4: DETAIL GURU SEKOLAH
     ======================================================== --}}
<div id="modalDetailGuru" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:460px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between;">
            <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Detail Guru Sekolah</h3>
            <button type="button" onclick="tutupModalDetail()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
        </div>

        <div style="padding:20px; display:flex; flex-direction:column; gap:12px; font-size:13px;">
            <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Nama</span>
                <strong id="det_nama" style="color:var(--text-primary, #0f172a);"></strong>
            </div>
            <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Email</span>
                <span id="det_email" style="color:var(--text-primary, #0f172a);"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">NIP</span>
                <span id="det_nip" style="color:var(--text-primary, #0f172a);"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Sekolah</span>
                <span id="det_sekolah" style="color:var(--text-primary, #0f172a); font-weight:700;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Mata Pelajaran</span>
                <span id="det_mapel" style="color:#0284c7; font-weight:700;"></span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-muted, #64748b);">Status</span>
                <span id="det_status"></span>
            </div>
        </div>

        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; background:var(--bg-main, #f8fafc);">
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetail()" style="padding:7px 16px; border-radius:8px; font-size:12.5px;">Tutup</button>
        </div>
    </div>
</div>

<script>
// Data Mapping Mapel per Lembaga dari tabel Plotting Mata Pelajaran
const mapelByLembaga = {
@foreach($sekolahLembagas as $lem)
    "{{ $lem->id }}": [
        @foreach($plottingMapels->where('lembaga_id', $lem->id) as $mp)
            { id: {{ $mp->id }}, kode: "{{ $mp->kode }}", nama: "{{ addslashes($mp->mata_pelajaran) }}" },
        @endforeach
    ],
@endforeach
};

// Toggle Dropdown Menu
function toggleMapelDropdown(prefix, event) {
    if (event) event.stopPropagation();
    const menu = document.getElementById(prefix + '_mapel_menu');
    const otherPrefix = prefix === 'tambah' ? 'ubah' : 'tambah';
    const otherMenu = document.getElementById(otherPrefix + '_mapel_menu');
    if (otherMenu) otherMenu.style.display = 'none';

    if (menu) {
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }
}

// Render Dropdown List Items
function renderMapelDropdown(prefix, lembagaId, preselectedMapels = []) {
    const menu = document.getElementById(prefix + '_mapel_menu');
    const display = document.getElementById(prefix + '_mapel_display');
    if (!menu || !display) return;

    if (!lembagaId) {
        menu.innerHTML = '<div style="font-size:12.5px; color:var(--text-muted, #94a3b8); padding:8px 10px;">Pilih lembaga sekolah terlebih dahulu.</div>';
        display.innerHTML = '<span style="color:var(--text-muted, #94a3b8);">-- Pilih Lembaga Terlebih Dahulu --</span>';
        return;
    }

    const items = mapelByLembaga[lembagaId] || [];
    if (items.length === 0) {
        menu.innerHTML = '<div style="font-size:12.5px; color:#b45309; background:#fef3c7; border:1px solid #fde68a; padding:8px 12px; border-radius:6px; margin:4px;">Belum ada mata pelajaran untuk lembaga ini di menu Plotting Mapel.</div>';
        display.innerHTML = '<span style="color:#b45309;">(Tidak ada mapel tersedia)</span>';
        return;
    }

    let html = '';
    items.forEach(item => {
        const isChecked = preselectedMapels.includes(item.nama);
        html += `
            <div class="mapel-option-item" onclick="toggleOptionItem(this, '${prefix}', event)"
                 style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text-primary); transition:background 0.12s; background:${isChecked ? 'rgba(2, 132, 199, 0.06)' : 'transparent'};"
                 onmouseover="this.style.background='rgba(0,0,0,0.035)'" onmouseout="this.style.background='${isChecked ? 'rgba(2, 132, 199, 0.06)' : 'transparent'}'">
                <input type="checkbox" name="mata_pelajaran[]" value="${item.nama}" ${isChecked ? 'checked' : ''}
                       style="width:16px; height:16px; accent-color:#0284c7; cursor:pointer;"
                       onclick="event.stopPropagation(); syncMapelDisplay('${prefix}');">
                <span style="flex:1;">${item.nama}</span>
                <span style="font-size:11px; color:var(--text-muted, #94a3b8); font-weight:500;">(${item.kode})</span>
            </div>
        `;
    });
    menu.innerHTML = html;

    syncMapelDisplay(prefix);
}

// Toggle option item when clicking the row
function toggleOptionItem(rowEl, prefix, event) {
    if (event) event.stopPropagation();
    const cb = rowEl.querySelector('input[type="checkbox"]');
    if (cb) {
        cb.checked = !cb.checked;
        rowEl.style.background = cb.checked ? 'rgba(2, 132, 199, 0.06)' : 'transparent';
        syncMapelDisplay(prefix);
    }
}

// Synchronize the display text / badges inside the trigger button
function syncMapelDisplay(prefix) {
    const menu = document.getElementById(prefix + '_mapel_menu');
    const display = document.getElementById(prefix + '_mapel_display');
    if (!menu || !display) return;

    const checkedBoxes = menu.querySelectorAll('input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) {
        display.innerHTML = '<span style="color:var(--text-muted, #94a3b8);">-- Pilih Mata Pelajaran --</span>';
    } else {
        let html = '';
        checkedBoxes.forEach(cb => {
            html += `<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:700; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">${cb.value}</span>`;
        });
        display.innerHTML = html;
    }
}

// Modal Tambah Guru
function bukaModalTambahGuru() {
    const modal = document.getElementById('modalTambahGuru');
    if (modal) {
        modal.style.display = 'flex';
        const lembagaSelect = document.getElementById('tambah_lembaga_id');
        if (lembagaSelect && lembagaSelect.value) {
            renderMapelDropdown('tambah', lembagaSelect.value, []);
        } else {
            renderMapelDropdown('tambah', '', []);
        }
    }
}

function tutupModalTambahGuru() {
    const modal = document.getElementById('modalTambahGuru');
    const menu = document.getElementById('tambah_mapel_menu');
    if (menu) menu.style.display = 'none';
    if (modal) modal.style.display = 'none';
}

function filterMapelTambah(lembagaId) {
    renderMapelDropdown('tambah', lembagaId, []);
}

// Modal Ubah Guru
let currentUbahMapels = [];
function bukaModalUbah(guruId, guruName, lembagaId, mapelStr) {
    const modal = document.getElementById('modalUbahGuru');
    const form = document.getElementById('formUbahGuru');
    const namaInput = document.getElementById('ubah_nama_guru');
    const lembagaSelect = document.getElementById('ubah_lembaga_id');

    if (!modal || !form) return;

    form.action = '{{ url("guru-sekolah") }}/' + guruId + '/assign';
    if (namaInput) namaInput.value = guruName;

    currentUbahMapels = mapelStr ? mapelStr.split(',').map(s => s.trim()).filter(Boolean) : [];

    if (lembagaSelect) {
        if (lembagaId) {
            lembagaSelect.value = lembagaId;
        }
        renderMapelDropdown('ubah', lembagaSelect.value, currentUbahMapels);
    }

    modal.style.display = 'flex';
}

function tutupModalUbahGuru() {
    const modal = document.getElementById('modalUbahGuru');
    const menu = document.getElementById('ubah_mapel_menu');
    if (menu) menu.style.display = 'none';
    if (modal) modal.style.display = 'none';
}

function filterMapelUbah(lembagaId) {
    renderMapelDropdown('ubah', lembagaId, currentUbahMapels);
}

// Modal Tolak Pengajuan
function bukaModalTolak(guruId, guruName) {
    const modal = document.getElementById('modalTolakPengajuan');
    const form = document.getElementById('formTolakPengajuan');
    const namaEl = document.getElementById('tolak_guru_nama');

    if (!modal || !form) return;
    form.action = '{{ url("guru-sekolah") }}/' + guruId + '/reject';
    if (namaEl) namaEl.textContent = guruName;
    modal.style.display = 'flex';
}

function tutupModalTolak() {
    const modal = document.getElementById('modalTolakPengajuan');
    if (modal) modal.style.display = 'none';
}

// Modal Detail Guru
function bukaModalDetail(id, nama, email, nip, sekolah, mapel, status) {
    const modal = document.getElementById('modalDetailGuru');
    if (!modal) return;

    document.getElementById('det_nama').textContent = nama;
    document.getElementById('det_email').textContent = email;
    document.getElementById('det_nip').textContent = nip;
    document.getElementById('det_sekolah').textContent = sekolah;
    document.getElementById('det_mapel').textContent = mapel;

    const statusEl = document.getElementById('det_status');
    if (status === 'disetujui') {
        statusEl.innerHTML = '<span style="color:#047857; font-weight:700;">Disetujui</span>';
    } else if (status === 'menunggu') {
        statusEl.innerHTML = '<span style="color:#b45309; font-weight:700;">Menunggu Persetujuan</span>';
    } else if (status === 'ditolak') {
        statusEl.innerHTML = '<span style="color:#b91c1c; font-weight:700;">Ditolak</span>';
    } else {
        statusEl.innerHTML = '<span style="color:#64748b; font-weight:600;">Belum Mengajukan</span>';
    }

    modal.style.display = 'flex';
}

function tutupModalDetail() {
    const modal = document.getElementById('modalDetailGuru');
    if (modal) modal.style.display = 'none';
}

// Validasi Form Submit: pastikan minimal 1 mapel dipilih
document.addEventListener('DOMContentLoaded', function() {
    const formTambah = document.getElementById('formTambahGuru');
    if (formTambah) {
        formTambah.addEventListener('submit', function(e) {
            const checked = this.querySelectorAll('input[name="mata_pelajaran[]"]:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Pilih setidaknya satu mata pelajaran yang diampu.');
                return false;
            }
        });
    }

    const formUbah = document.getElementById('formUbahGuru');
    if (formUbah) {
        formUbah.addEventListener('submit', function(e) {
            const checked = this.querySelectorAll('input[name="mata_pelajaran[]"]:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Pilih setidaknya satu mata pelajaran yang diampu.');
                return false;
            }
        });
    }
});

// Global Click Listener: Tutup dropdown menu saat klik di luar
document.addEventListener('click', function(e) {
    const tambahTrigger = document.getElementById('tambah_mapel_dropdown_btn');
    const tambahMenu = document.getElementById('tambah_mapel_menu');
    if (tambahMenu && tambahTrigger && !tambahTrigger.contains(e.target) && !tambahMenu.contains(e.target)) {
        tambahMenu.style.display = 'none';
    }

    const ubahTrigger = document.getElementById('ubah_mapel_dropdown_btn');
    const ubahMenu = document.getElementById('ubah_mapel_menu');
    if (ubahMenu && ubahTrigger && !ubahTrigger.contains(e.target) && !ubahMenu.contains(e.target)) {
        ubahMenu.style.display = 'none';
    }

    if (e.target && e.target.classList.contains('clean-modal-backdrop')) {
        e.target.style.display = 'none';
    }
});
</script>
@endsection
