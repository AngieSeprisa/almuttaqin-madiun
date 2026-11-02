@extends('layouts.app')

@section('title', 'Perizinan Haid (Udzur Sholat)')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('perizinan_haid', 'create') || auth()->user()->canDo('rekap_pondok', 'edit'))
<a href="{{ route('perizinan.haid.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600; background:#db2777; border-color:#db2777; box-shadow:0 1px 2px rgba(219, 39, 119, 0.2);">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    <span>Catat Santriwati Haid</span>
</a>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:1300px; margin:0 auto;">

    {{-- Alert Banner --}}
    @if (session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;color:#10B981;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error" style="display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:12px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); color:#991B1B;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;color:#EF4444;">
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

    {{-- Kartu Statistik Ringkas (Filament Style: Bersih Tanpa Deskripsi) --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
        {{-- Sedang Haid --}}
        <div style="background:var(--bg-surface, #ffffff); border:1px solid {{ $totalAktif > 0 ? '#fbcfe8' : 'var(--border-light, #e2e8f0)' }}; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:#fdf2f8; color:#db2777; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid #fbcfe8;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 6v6l4 2"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#be185d;">Sedang Haid</div>
                <div style="font-size:24px; font-weight:800; color:#9d174d; margin-top:2px; line-height:1.2;">{{ $totalAktif }}</div>
            </div>
        </div>

        {{-- Bulan Ini --}}
        <div style="background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(59, 130, 246, 0.08); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid rgba(59, 130, 246, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Bulan Ini</div>
                <div style="font-size:24px; font-weight:800; color:var(--text-primary, #0f172a); margin-top:2px; line-height:1.2;">{{ $totalBulanIni }}</div>
            </div>
        </div>

        {{-- Sudah Suci --}}
        <div style="background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(16, 185, 129, 0.08); color:#059669; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid rgba(16, 185, 129, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Sudah Suci</div>
                <div style="font-size:24px; font-weight:800; color:#059669; margin-top:2px; line-height:1.2;">{{ $totalSuci ?? 0 }}</div>
            </div>
        </div>
    </div>

    {{-- Kontainer Tabel Filament PHP (fi-ta-ctn) --}}
    <div class="fi-ta-ctn" style="background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #e2e8f0); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        
        {{-- Filament Table Toolbar (Search & Filters) --}}
        <div style="padding:14px 18px; border-bottom:1px solid var(--border-subtle, #f1f5f9); background:var(--bg-surface, #ffffff);">
            <form action="{{ route('perizinan.haid') }}" method="GET" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin:0;">
                
                {{-- Group Filter Sisi Kiri --}}
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    {{-- Filter Status --}}
                    <div style="position:relative; display:inline-block;">
                        <select name="status" class="form-select" onchange="this.form.submit()"
                                style="padding:7px 28px 7px 12px; font-size:12.5px; font-weight:600; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a); cursor:pointer;">
                            <option value="">Semua Status</option>
                            <option value="sedang_haid" {{ request('status') === 'sedang_haid' ? 'selected' : '' }}>Sedang Haid</option>
                            <option value="sudah_suci" {{ request('status') === 'sudah_suci' ? 'selected' : '' }}>Sudah Suci</option>
                        </select>
                    </div>

                    {{-- Filter Kamar --}}
                    @if(isset($kamars) && count($kamars) > 0)
                    <div style="position:relative; display:inline-block;">
                        <select name="kamar" class="form-select" onchange="this.form.submit()"
                                style="padding:7px 28px 7px 12px; font-size:12.5px; font-weight:600; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a); cursor:pointer;">
                            <option value="">Semua Kamar</option>
                            @foreach($kamars as $k)
                                <option value="{{ $k }}" {{ request('kamar') === $k ? 'selected' : '' }}>{{ $k }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Tombol Reset --}}
                    @if(request('status') || request('kamar') || request('search'))
                    <a href="{{ route('perizinan.haid') }}" class="btn btn-secondary" style="padding:7px 12px; font-size:12px; font-weight:600; border-radius:8px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                    @endif
                </div>

                {{-- Kolom Pencarian Sisi Kanan (Filament Search Field) --}}
                <div style="position:relative; width:100%; max-width:320px;">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari santriwati atau kamar..." autocomplete="off"
                           style="width:100%; padding:7px 32px 7px 34px; border-radius:8px; font-size:13px; border:1px solid var(--border-light, #cbd5e1); background:var(--bg-main, #f8fafc); color:var(--text-primary, #0f172a);">
                    <div style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-muted, #94a3b8); pointer-events:none; display:flex; align-items:center;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    @if(request('search'))
                    <a href="{{ route('perizinan.haid', request()->except('search')) }}" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); color:var(--text-muted, #94a3b8); text-decoration:none; font-size:16px;">&times;</a>
                    @endif
                </div>

            </form>
        </div>

        {{-- Tabel Data Filament PHP --}}
        <div style="overflow-x:auto;">
            <table class="fi-ta-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:13px;">
                <thead>
                    <tr style="background:var(--bg-main, #f8fafc); border-bottom:1px solid var(--border-light, #e2e8f0); color:var(--text-secondary, #64748b); font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px;">
                        <th style="padding:12px 16px; width:45px; text-align:center;">#</th>
                        <th style="padding:12px 16px;">Santriwati</th>
                        <th style="padding:12px 16px;">Kamar</th>
                        <th style="padding:12px 16px;">Mulai Haid</th>
                        <th style="padding:12px 16px;">Estimasi Selesai</th>
                        <th style="padding:12px 16px;">Durasi</th>
                        <th style="padding:12px 16px;">Status</th>
                        <th style="padding:12px 16px;">Keterangan</th>
                        <th style="padding:12px 16px; text-align:center; width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($perizinan as $index => $item)
                        @php
                            $mulai = $item->tanggal_mulai;
                            $selesai = $item->tanggal_selesai;
                            $isSedangHaid = ($item->status === 'sedang_haid');
                            
                            // Hitung hari
                            $hariKe = $mulai ? $mulai->diffInDays(\Carbon\Carbon::today()) + 1 : 1;
                            $durasiTotal = ($mulai && $selesai) ? ($mulai->diffInDays($selesai) + 1) . ' Hari' : '-';
                            $rowNum = method_exists($perizinan, 'firstItem') ? ($perizinan->firstItem() + $index) : ($index + 1);
                        @endphp
                        <tr style="border-bottom:1px solid var(--border-subtle, #f1f5f9); transition:background 0.15s;" onmouseover="this.style.background='rgba(0,0,0,0.012)'" onmouseout="this.style.background='transparent'">
                            {{-- No --}}
                            <td style="padding:12px 16px; text-align:center; color:var(--text-muted, #94a3b8); font-size:12px;">
                                {{ $rowNum }}
                            </td>

                            {{-- Santriwati --}}
                            <td style="padding:12px 16px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:34px; height:34px; border-radius:50%; background:#fdf2f8; color:#be185d; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12.5px; border:1px solid #fbcfe8; flex-shrink:0;">
                                        {{ mb_substr($item->nama, 0, 1) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:var(--text-primary, #0f172a); font-size:13.5px;">{{ $item->nama }}</div>
                                        @if($item->siswa && $item->siswa->nisn)
                                            <div style="font-size:11px; color:var(--text-muted, #94a3b8); margin-top:1px;">NISN: {{ $item->siswa->nisn }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Kamar --}}
                            <td style="padding:12px 16px;">
                                @if($item->kamar)
                                    <span style="display:inline-flex; align-items:center; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:500; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;">
                                        {{ $item->kamar }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted, #94a3b8); font-size:12px;">-</span>
                                @endif
                            </td>

                            {{-- Mulai Haid --}}
                            <td style="padding:12px 16px;">
                                <div style="font-weight:600; color:var(--text-primary, #0f172a); font-size:13px;">
                                    {{ $item->tanggal_mulai ? $item->tanggal_mulai->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div style="font-size:11px; color:var(--text-muted, #94a3b8);">
                                    {{ $item->tanggal_mulai ? $item->tanggal_mulai->translatedFormat('l') : '' }}
                                </div>
                            </td>

                            {{-- Estimasi Selesai --}}
                            <td style="padding:12px 16px;">
                                @if($item->tanggal_selesai)
                                    <div style="font-weight:600; color:var(--text-primary, #0f172a); font-size:13px;">
                                        {{ $item->tanggal_selesai->translatedFormat('d M Y') }}
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted, #94a3b8);">
                                        {{ $item->tanggal_selesai->translatedFormat('l') }}
                                    </div>
                                @else
                                    <span style="font-size:12px; color:var(--text-muted, #94a3b8);">-</span>
                                @endif
                            </td>

                            {{-- Durasi (Filament Badge) --}}
                            <td style="padding:12px 16px;">
                                @if($isSedangHaid)
                                    <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:700; background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8;">
                                        Hari ke-{{ $hariKe }}
                                    </span>
                                @else
                                    <span style="display:inline-flex; align-items:center; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:600; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;">
                                        {{ $durasiTotal }}
                                    </span>
                                @endif
                            </td>

                            {{-- Status (Filament Status Badge with Dot) --}}
                            <td style="padding:12px 16px;">
                                @if($isSedangHaid)
                                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#fdf2f8; color:#be185d; border:1px solid #fbcfe8;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#db2777; display:inline-block;"></span>
                                        <span>Sedang Haid</span>
                                    </span>
                                @else
                                    <span style="display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border-radius:6px; font-size:11.5px; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                                        <span>Sudah Suci</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Keterangan --}}
                            <td style="padding:12px 16px;">
                                <div style="font-size:12px; color:var(--text-secondary, #64748b); max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $item->keterangan }}">
                                    {{ $item->keterangan ?: '-' }}
                                </div>
                            </td>

                            {{-- Aksi (Filament Action Buttons) --}}
                            <td style="padding:12px 16px; text-align:center;">
                                <div style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                    @if($isSedangHaid)
                                        {{-- Tombol 1-Klik Sudah Suci --}}
                                        <form action="{{ route('perizinan.haid.suci', $item->id) }}" method="POST" style="display:inline; margin:0;">
                                            @csrf
                                            <button type="submit" class="btn" title="Tandai Sudah Suci"
                                                    style="padding:4px 9px; font-size:11.5px; background:#10b981; border:none; color:#ffffff; font-weight:700; border-radius:6px; display:inline-flex; align-items:center; gap:3px; cursor:pointer;">
                                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                <span>Suci</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tombol Edit Modal --}}
                                    <button type="button" class="btn btn-secondary" onclick="openEditModal({{ $item->id }})" title="Edit"
                                            style="padding:5px 7px; border-radius:6px; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('perizinan.haid.destroy', $item->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Hapus catatan haid untuk {{ $item->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn" title="Hapus"
                                                style="padding:5px 7px; border-radius:6px; font-size:12px; background:rgba(239, 68, 68, 0.08); color:#dc2626; border:1px solid rgba(239, 68, 68, 0.2); cursor:pointer; display:inline-flex; align-items:center; justify-content:center;">
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>

                                {{-- MODAL EDIT PERIZINAN HAID (FILAMENT CLEAN) --}}
                                <div id="modalEditHaid{{ $item->id }}" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px; backdrop-filter:blur(3px);">
                                    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:480px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left;">
                                        <form action="{{ route('perizinan.haid.update', $item->id) }}" method="POST" style="margin:0;">
                                            @csrf
                                            @method('PUT')
                                            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between;">
                                                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Edit Perizinan: {{ $item->nama }}</h3>
                                                <button type="button" onclick="closeEditModal({{ $item->id }})" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
                                            </div>

                                            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Kamar</label>
                                                    <input type="text" name="kamar" value="{{ old('kamar', $item->kamar) }}" class="form-control"
                                                           style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                                                </div>
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                                    <div>
                                                        <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Mulai Haid</label>
                                                        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $item->tanggal_mulai ? $item->tanggal_mulai->format('Y-m-d') : '') }}" required class="form-control"
                                                               style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                                                    </div>
                                                    <div>
                                                        <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Estimasi Selesai</label>
                                                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $item->tanggal_selesai ? $item->tanggal_selesai->format('Y-m-d') : '') }}" class="form-control"
                                                               style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Status</label>
                                                    <select name="status" class="form-select"
                                                            style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">
                                                        <option value="sedang_haid" {{ $item->status === 'sedang_haid' ? 'selected' : '' }}>Sedang Haid</option>
                                                        <option value="sudah_suci" {{ $item->status === 'sudah_suci' ? 'selected' : '' }}>Sudah Suci</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Keterangan</label>
                                                    <textarea name="keterangan" rows="2" class="form-control"
                                                              style="width:100%; padding:8px 12px; border-radius:8px; border:1px solid var(--border-light, #cbd5e1); font-size:13px; background:var(--bg-main, #f8fafc);">{{ old('keterangan', $item->keterangan) }}</textarea>
                                                </div>
                                            </div>

                                            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc);">
                                                <button type="button" class="btn btn-secondary" onclick="closeEditModal({{ $item->id }})" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                                                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700; background:#db2777; border-color:#db2777; color:#fff;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:42px 16px; color:var(--text-muted, #94a3b8);">
                                <div style="font-size:14px; font-weight:600; color:var(--text-secondary, #64748b);">Tidak ada data perizinan haid yang sesuai filter.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Filament Pagination Footer --}}
        @if(method_exists($perizinan, 'hasPages') && $perizinan->hasPages())
        <div style="padding:12px 18px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; background:var(--bg-surface, #ffffff);">
            <div style="font-size:12.5px; color:var(--text-muted, #64748b);">
                Menampilkan {{ $perizinan->firstItem() }} - {{ $perizinan->lastItem() }} dari {{ $perizinan->total() }} santriwati
            </div>
            <div>
                {{ $perizinan->links() }}
            </div>
        </div>
        @endif

    </div>

    {{-- Card List untuk Tampilan Mobile (<= 768px) --}}
    <div class="mobile-card-list">
        @forelse ($perizinan as $index => $item)
            @php
                $mulai = $item->tanggal_mulai;
                $selesai = $item->tanggal_selesai;
                $isSedangHaid = ($item->status === 'sedang_haid');
                $hariKe = $mulai ? $mulai->diffInDays(\Carbon\Carbon::today()) + 1 : 1;
                $durasiTotal = ($mulai && $selesai) ? ($mulai->diffInDays($selesai) + 1) . ' Hari' : '-';
            @endphp
            <div class="mobile-card-item">
                <div class="mobile-card-info">
                    <div class="mobile-card-title">
                        <span>{{ $item->nama }}</span>
                    </div>
                    <div class="mobile-card-subtitle">
                        <span>{{ $item->kamar ?: 'Kamar -' }}</span>
                        @if($item->siswa && $item->siswa->nisn)
                            &bull; <span>NISN: {{ $item->siswa->nisn }}</span>
                        @endif
                    </div>

                    <div class="mobile-card-meta-grid">
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Mulai</span>
                            <span style="font-size:12px; font-weight:600; color:var(--text-primary);">
                                {{ $item->tanggal_mulai ? $item->tanggal_mulai->format('d/m/Y') : '-' }}
                            </span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Durasi</span>
                            <span style="font-size:12px; font-weight:700; color:#db2777;">
                                {{ $isSedangHaid ? 'Hari ke-' . $hariKe : $durasiTotal }}
                            </span>
                        </div>
                        <div class="mobile-card-meta-item">
                            <span class="mobile-card-meta-label">Status</span>
                            @if($isSedangHaid)
                                <span class="status-pill status-pill-warning" style="background:#fdf2f8; color:#be185d; border-color:#fbcfe8;">Sedang Haid</span>
                            @else
                                <span class="status-pill status-pill-active" style="background:#ecfdf5; color:#047857; border-color:#a7f3d0;">Sudah Suci</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mobile-card-actions">
                    <span class="mobile-card-action-title">Aksi</span>
                    <div class="mobile-card-action-buttons">
                        @if($isSedangHaid)
                        <form action="{{ route('perizinan.haid.suci', $item->id) }}" method="POST" style="display:inline; margin:0;">
                            @csrf
                            <button type="submit" class="mobile-btn-pastel mobile-btn-success" title="Tandai Suci">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </button>
                        </form>
                        @endif

                        <button type="button" class="mobile-btn-pastel mobile-btn-edit" onclick="openEditModal({{ $item->id }})" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>

                        <form action="{{ route('perizinan.haid.destroy', $item->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Hapus catatan?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="mobile-btn-pastel mobile-btn-delete" title="Hapus">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div style="background:#fff; border-radius:10px; padding:24px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
                Tidak ada data perizinan haid.
            </div>
        @endforelse
    </div>

</div>

<script>
    function openEditModal(id) {
        var m = document.getElementById('modalEditHaid' + id);
        if (m) m.style.display = 'flex';
    }
    function closeEditModal(id) {
        var m = document.getElementById('modalEditHaid' + id);
        if (m) m.style.display = 'none';
    }

    // Tutup modal jika klik backdrop atau tombol Escape
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('clean-modal-backdrop')) {
            event.target.style.display = 'none';
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.clean-modal-backdrop').forEach(function(m) {
                m.style.display = 'none';
            });
        }
    });
</script>
@endsection
