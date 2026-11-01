@extends('layouts.app')

@section('title', 'Perizinan Haid (Udzur Sholat)')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('perizinan_haid', 'create') || auth()->user()->canDo('rekap_pondok', 'edit'))
<a href="{{ route('perizinan.haid.create') }}" class="btn btn-primary" style="background:#db2777; border-color:#db2777;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    Catat Santriwati Haid
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

    {{-- Banner Informasi Udzur Syar'i Sholat --}}
    <div style="background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border: 1px solid #fbcfe8; border-radius: 14px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: #f472b6; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(244, 114, 182, 0.35);">
                🌸
            </div>
            <div>
                <h4 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #9d174d;">Sistem Udzur Sholat Santriwati (Haid)</h4>
                <p style="margin: 0; font-size: 13px; color: #be185d; line-height: 1.5;">
                    Santriwati yang berstatus <strong>Sedang Haid</strong> otomatis tercatat <strong>HAID / UDZUR</strong> pada seluruh 5 waktu sholat harian tanpa perlu tap kartu dan tidak menjadi Alpa.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 12px;">
            <div style="background: #ffffff; padding: 8px 16px; border-radius: 10px; border: 1px solid #fbcfe8; text-align: center;">
                <div style="font-size: 11px; font-weight: 700; color: #9d174d; text-transform: uppercase;">Sedang Haid</div>
                <div style="font-size: 20px; font-weight: 800; color: #be185d;">{{ $totalAktif }}</div>
            </div>
            <div style="background: #ffffff; padding: 8px 16px; border-radius: 10px; border: 1px solid #fbcfe8; text-align: center;">
                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Bulan Ini</div>
                <div style="font-size: 20px; font-weight: 800; color: #334155;">{{ $totalBulanIni }}</div>
            </div>
        </div>
    </div>

    {{-- Filter Dropdown & Search Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('perizinan.haid') }}" method="GET" class="filter-form">
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
                        <option value="sedang_haid" {{ request('status') === 'sedang_haid' ? 'selected' : '' }}>Sedang Haid</option>
                        <option value="sudah_suci" {{ request('status') === 'sudah_suci' ? 'selected' : '' }}>Sudah Suci</option>
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
                    <a href="{{ route('perizinan.haid') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            {{-- Kolom Pencarian --}}
            <div class="filter-group-right">
                <div class="search-input-wrap">
                    <div class="search-input-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Cari santriwati atau kamar..." autocomplete="off">
                </div>
            </div>
        </form>
    </div>

    {{-- Tabel Perizinan Haid --}}
    <div class="data-table-container">
        <div class="data-table-wrapper" style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th>Nama Santriwati</th>
                        <th>Kamar</th>
                        <th>Mulai Haid</th>
                        <th>Selesai / Estimasi</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th style="width:140px; text-align:center;">Aksi</th>
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
                        @endphp
                        <tr>
                            <td style="text-align:center; color:var(--text-muted); font-size:13px;">{{ $index + 1 }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:34px; height:34px; border-radius:50%; background:#fce7f3; color:#db2777; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px;">
                                        {{ mb_substr($item->nama, 0, 1) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600; color:var(--text-primary);">{{ $item->nama }}</div>
                                        @if($item->siswa && $item->siswa->nisn)
                                            <div style="font-size:11.5px; color:var(--text-muted);">NISN: {{ $item->siswa->nisn }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:12.5px; color:#475569; background:#f1f5f9; padding:3px 8px; border-radius:6px; font-weight:500;">
                                    {{ $item->kamar ?: '-' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--text-primary); font-size:13px;">
                                    {{ $item->tanggal_mulai ? $item->tanggal_mulai->translatedFormat('d M Y') : '-' }}
                                </div>
                                <div style="font-size:11.5px; color:var(--text-muted);">
                                    {{ $item->tanggal_mulai ? $item->tanggal_mulai->translatedFormat('l') : '' }}
                                </div>
                            </td>
                            <td>
                                @if($item->tanggal_selesai)
                                    <div style="font-weight:600; color:var(--text-primary); font-size:13px;">
                                        {{ $item->tanggal_selesai->translatedFormat('d M Y') }}
                                    </div>
                                    <div style="font-size:11.5px; color:var(--text-muted);">
                                        {{ $item->tanggal_selesai->translatedFormat('l') }}
                                    </div>
                                @else
                                    <span style="font-size:12px; color:#94a3b8; font-style:italic;">Belum ditentukan</span>
                                @endif
                            </td>
                            <td>
                                @if($isSedangHaid)
                                    <span style="display:inline-block; font-size:12px; font-weight:700; color:#db2777; background:#fdf2f8; padding:3px 9px; border-radius:6px; border:1px solid #fbcfe8;">
                                        Hari ke-{{ $hariKe }}
                                    </span>
                                @else
                                    <span style="font-size:12px; font-weight:600; color:#475569;">
                                        {{ $durasiTotal }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($isSedangHaid)
                                    <span class="status-badge" style="background:#fdf2f8; color:#db2777; border:1px solid #fbcfe8; display:inline-flex; align-items:center; gap:5px; font-weight:700;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#db2777;"></span>
                                        Sedang Haid
                                    </span>
                                @else
                                    <span class="status-badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; display:inline-flex; align-items:center; gap:5px; font-weight:700;">
                                        <span style="width:6px; height:6px; border-radius:50%; background:#16a34a;"></span>
                                        Sudah Suci
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:12.5px; color:var(--text-muted); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $item->keterangan }}">
                                    {{ $item->keterangan ?: '-' }}
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
                                    @if($isSedangHaid)
                                        {{-- Tombol 1-Klik Sudah Suci --}}
                                        <form action="{{ route('perizinan.haid.suci', $item->id) }}" method="POST" style="display:inline; margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Tandai Sudah Suci (Mandi Wajib)" style="padding:5px 9px; font-size:11.5px; background:#16a34a; border-color:#16a34a; color:#ffffff; font-weight:600; border-radius:7px; display:inline-flex; align-items:center; gap:4px;">
                                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                <span>Suci</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Tombol Edit Modal --}}
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditModal({{ $item->id }})" title="Edit Data" style="padding:5px 8px; border-radius:7px;">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>

                                    {{-- Tombol Hapus --}}
                                    <form action="{{ route('perizinan.haid.destroy', $item->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan haid santriwati {{ $item->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus Data" style="padding:5px 8px; border-radius:7px; background:#fee2e2; color:#dc2626; border:1px solid #fecaca;">
                                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        </button>
                                    </form>
                                </div>

                                {{-- MODAL EDIT PERIZINAN HAID --}}
                                <div id="modalEditHaid{{ $item->id }}" class="modal" tabindex="-1" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.45); z-index:9999; align-items:center; justify-content:center;">
                                    <div class="modal-dialog" style="background:#ffffff; border-radius:14px; width:90%; max-width:520px; overflow:hidden; box-shadow:0 20px 25px -5px rgba(0,0,0,0.15);">
                                        <form action="{{ route('perizinan.haid.update', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
                                                <h3 style="font-size:16px; font-weight:700; margin:0; color:#0f172a;">Edit Perizinan Haid: {{ $item->nama }}</h3>
                                                <button type="button" onclick="closeEditModal({{ $item->id }})" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
                                            </div>
                                            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600;">Kamar</label>
                                                    <input type="text" name="kamar" value="{{ old('kamar', $item->kamar) }}" class="form-control">
                                                </div>
                                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                                    <div>
                                                        <label class="form-label" style="font-size:12.5px; font-weight:600;">Mulai Haid</label>
                                                        <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', $item->tanggal_mulai ? $item->tanggal_mulai->format('Y-m-d') : '') }}" required class="form-control">
                                                    </div>
                                                    <div>
                                                        <label class="form-label" style="font-size:12.5px; font-weight:600;">Selesai / Estimasi</label>
                                                        <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai', $item->tanggal_selesai ? $item->tanggal_selesai->format('Y-m-d') : '') }}" class="form-control">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600;">Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="sedang_haid" {{ $item->status === 'sedang_haid' ? 'selected' : '' }}>Sedang Haid</option>
                                                        <option value="sudah_suci" {{ $item->status === 'sudah_suci' ? 'selected' : '' }}>Sudah Suci</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label" style="font-size:12.5px; font-weight:600;">Keterangan</label>
                                                    <textarea name="keterangan" rows="2" class="form-control">{{ old('keterangan', $item->keterangan) }}</textarea>
                                                </div>
                                            </div>
                                            <div style="padding:14px 20px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; background:#f8fafc;">
                                                <button type="button" class="btn btn-secondary" onclick="closeEditModal({{ $item->id }})">Batal</button>
                                                <button type="submit" class="btn btn-primary" style="background:#db2777; border-color:#db2777;">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:48px 16px; color:#94a3b8;">
                                <div style="font-size:32px; margin-bottom:8px;">🌸</div>
                                <div style="font-weight:600; color:#475569; font-size:14.5px;">Belum ada data perizinan haid</div>
                                <div style="font-size:12.5px; color:#94a3b8; margin-top:3px;">Klik tombol "Catat Santriwati Haid" di atas untuk menambahkan data baru.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
</script>
@endsection

