@extends('layouts.app')

@section('title', 'Plotting Guru Pondok')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    @if(!auth()->check() || auth()->user()->canDo('guru_pondok', 'create'))
    <button type="button" class="btn btn-primary" onclick="bukaModalTambahGuruPondok()" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Guru Pondok</span>
    </button>
    @endif
</div>
@endsection

@section('content')
@php
    $kelasOptionsPayload = $kelasPengajians->map(function ($k) {
        return [
            'id'      => (int) $k->id,
            'kode'    => (string) ($k->kode_kelas ?? ''),
            'nama'    => (string) $k->nama_kelas,
            'jenjang' => strtoupper((string) ($k->jenjang ?? '')),
        ];
    })->values()->all();

    $materiOptionsPayload = $pondokMateris->map(function ($m) {
        return [
            'id'       => (int) $m->id,
            'kode'     => (string) ($m->kode_materi ?? ''),
            'nama'     => (string) $m->nama_materi,
            'kategori' => (string) $m->kategori_label,
        ];
    })->values()->all();

    $guruPondokRowsMap = [];
    foreach ($users as $gRow) {
        $kIds = $gRow->daftar_kelas_pengajian_ids;
        $kNames = [];
        foreach ($kIds as $kid) {
            if (isset($kelasMap[$kid])) {
                $kNames[] = $kelasMap[$kid]->nama_kelas;
            }
        }
        $guruPondokRowsMap[$gRow->id] = [
            'id'          => (int) $gRow->id,
            'name'        => (string) $gRow->name,
            'email'       => (string) ($gRow->email ?? '-'),
            'nip'         => (string) ($gRow->nip ?: '-'),
            'kelas_ids'   => $kIds,
            'kelas_names' => $kNames,
            'materis'     => $gRow->daftar_materi_pondok,
            'catatan'     => (string) ($gRow->pondok_catatan ?? ''),
        ];
    }
    $guruPondokBaseUrl = url('guru-pondok');
@endphp

<div style="display:flex; flex-direction:column; gap:18px; width:100%;">

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

    {{-- Kartu Statistik Ringkas --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(16, 185, 129, 0.08); display:flex; align-items:center; justify-content:center; color:#059669; flex-shrink:0; border:1px solid rgba(16, 185, 129, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Total Guru Pondok</div>
                <div style="font-size:24px; font-weight:800; color:var(--text-primary, #0f172a); margin-top:2px; line-height:1.2;">{{ $stats['total_guru'] }}</div>
            </div>
        </div>

        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(59, 130, 246, 0.08); display:flex; align-items:center; justify-content:center; color:#2563eb; flex-shrink:0; border:1px solid rgba(59, 130, 246, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Kelas Terampu</div>
                <div style="font-size:24px; font-weight:800; color:#2563eb; margin-top:2px; line-height:1.2;">{{ $stats['kelas_terampu'] }} <span style="font-size:14px; font-weight:600; color:var(--text-muted, #94a3b8);">/ {{ $stats['total_kelas'] }}</span></div>
            </div>
        </div>

        <div style="background:var(--bg-surface); border:1px solid var(--border-light, #e2e8f0); border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(2, 132, 199, 0.08); display:flex; align-items:center; justify-content:center; color:#0284c7; flex-shrink:0; border:1px solid rgba(2, 132, 199, 0.15);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted, #64748b);">Materi Diampu</div>
                <div style="font-size:24px; font-weight:800; color:#0284c7; margin-top:2px; line-height:1.2;">{{ $stats['materi_diampu'] }}</div>
            </div>
        </div>

        <div style="background:var(--bg-surface); border:1px solid {{ $stats['kelas_tanpa_guru'] > 0 ? 'rgba(245, 158, 11, 0.4)' : 'var(--border-light, #e2e8f0)' }}; border-radius:12px; padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
            <div style="width:42px; height:42px; border-radius:10px; background:rgba(245, 158, 11, 0.1); display:flex; align-items:center; justify-content:center; color:#d97706; flex-shrink:0; border:1px solid rgba(245, 158, 11, 0.2);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div>
                <div style="font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#d97706;">Kelas Tanpa Guru</div>
                <div style="font-size:24px; font-weight:800; color:#d97706; margin-top:2px; line-height:1.2;">{{ $stats['kelas_tanpa_guru'] }}</div>
            </div>
        </div>
    </div>

    {{-- Filter Card (Auto-dock ke Filament v4 Toolbar) --}}
    <div class="filter-card">
        <form action="{{ route('guru-pondok.index') }}" method="GET" class="filter-form">
            <div class="filter-group">
                <div class="filter-select-wrap">
                    <select name="kelas_pengajian_id" id="filter_kelas_pengajian_id" class="form-select filter-select" aria-label="Kelas Pengajian" onchange="this.form.submit()">
                        <option value="">Semua Kelas Pengajian</option>
                        @foreach($kelasPengajians as $kls)
                        <option value="{{ $kls->id }}" {{ (string) request('kelas_pengajian_id') === (string) $kls->id ? 'selected' : '' }}>
                            {{ $kls->nama_kelas }}
                        </option>
                        @endforeach
                    </select>
                </div>

                @if($pondokMateris->isNotEmpty())
                <div class="filter-select-wrap">
                    <select name="materi" id="filter_materi" class="form-select filter-select" aria-label="Materi Pondok" onchange="this.form.submit()">
                        <option value="">Semua Materi</option>
                        @foreach($pondokMateris as $mat)
                        <option value="{{ $mat->nama_materi }}" {{ request('materi') === $mat->nama_materi ? 'selected' : '' }}>
                            {{ $mat->nama_materi }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(request('kelas_pengajian_id') || request('materi') || request('search'))
                    <a href="{{ route('guru-pondok.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari guru, NIP, materi...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Plotting Guru Pondok --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="min-width: 220px;">Guru Pondok</th>
                        <th style="min-width: 220px;">Kelas Pengajian</th>
                        <th style="min-width: 210px;">Materi / Kitab</th>
                        <th style="min-width: 150px;">Keterangan</th>
                        <th class="col-aksi-head" style="text-align:center; min-width: 120px; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $guru)
                    @php
                        $kelasIdsRow = $guru->daftar_kelas_pengajian_ids;
                        $materisRow = $guru->daftar_materi_pondok;
                    @endphp
                    <tr data-delete-url="{{ route('guru-pondok.destroy', $guru->id) }}">
                        {{-- Guru Pondok --}}
                        <td class="cell-nama">
                            <div style="display:flex; align-items:center; gap:10px;">
                                @if($guru->foto_url)
                                    <img src="{{ $guru->foto_url }}" alt="{{ $guru->name }}" style="width:34px; height:34px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid var(--border-light, #e2e8f0);">
                                @else
                                    <div style="width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg, #059669, #047857); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:12.5px; flex-shrink:0;">
                                        {{ $guru->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary, #0f172a); font-size:13.5px; display:flex; align-items:center; gap:6px;">
                                        <span>{{ $guru->name }}</span>
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

                        {{-- Kelas Pengajian --}}
                        <td>
                            @if(!empty($kelasIdsRow))
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    @foreach($kelasIdsRow as $kId)
                                        @if(isset($kelasMap[$kId]))
                                        <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:600; background:rgba(16, 185, 129, 0.1); color:#047857; border:1px solid rgba(16, 185, 129, 0.25);">
                                            {{ $kelasMap[$kId]->nama_kelas }}
                                        </span>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted, #94a3b8); font-size:12px;">-</span>
                            @endif
                        </td>

                        {{-- Materi / Kitab --}}
                        <td>
                            @if(!empty($materisRow))
                                <div style="display:flex; flex-wrap:wrap; gap:5px;">
                                    @foreach($materisRow as $matItem)
                                        <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; font-size:12px; font-weight:600; background:rgba(2, 132, 199, 0.08); color:#0284c7; border:1px solid rgba(2, 132, 199, 0.2);">
                                            {{ $matItem }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted, #94a3b8); font-size:12px;">-</span>
                            @endif
                        </td>

                        {{-- Keterangan --}}
                        <td style="font-size:12.5px; color:var(--text-secondary, #475569);">
                            {{ $guru->pondok_catatan ?: '-' }}
                        </td>

                        {{-- Aksi --}}
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                {{-- Tombol Detail --}}
                                <button type="button" class="action-btn action-btn-view" onclick="bukaModalDetailGuruPondok({{ $guru->id }})" title="Detail Guru Pondok" aria-label="Detail Guru Pondok">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>

                                {{-- Tombol Ubah --}}
                                @if(!auth()->check() || auth()->user()->canDo('guru_pondok', 'edit'))
                                <button type="button" class="action-btn action-btn-edit" title="Ubah Plotting Guru Pondok" aria-label="Ubah Plotting Guru Pondok"
                                        onclick="bukaModalUbahGuruPondok({{ $guru->id }})">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                @endif

                                {{-- Tombol Hapus dari Guru Pondok --}}
                                @if(!auth()->check() || auth()->user()->canDo('guru_pondok', 'delete'))
                                <form action="{{ route('guru-pondok.destroy', $guru->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Hapus {{ addslashes($guru->name) }} dari daftar Guru Pondok?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-delete" title="Hapus dari Guru Pondok" aria-label="Hapus dari Guru Pondok">
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
                        <td colspan="5" class="cell-empty" style="text-align:center; padding:48px 16px; color:var(--text-muted, #94a3b8);">
                            <div style="font-size:14px; font-weight:600; color:var(--text-secondary, #64748b);">Belum ada guru pondok yang di-plotting.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
     MODAL 1: TAMBAH GURU PONDOK
     ======================================================== --}}
@if(!auth()->check() || auth()->user()->canDo('guru_pondok', 'create'))
<div id="modalTambahGuruPondok" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:flex-start; justify-content:center; padding:32px 16px; overflow-y:auto; backdrop-filter:blur(3px); -webkit-overflow-scrolling:touch;">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:520px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:visible !important; text-align:left; position:relative; margin:auto;">
        <form action="{{ route('guru-pondok.store') }}" method="POST" id="formTambahGuruPondok" style="margin:0;">
            @csrf
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between; border-top-left-radius:14px; border-top-right-radius:14px; background:var(--bg-surface, #ffffff);">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Tambah Guru Pondok</h3>
                <button type="button" onclick="tutupModalTambahGuruPondok()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:16px; position:relative;">
                {{-- Nama Guru (Akun Terdaftar) --}}
                <div style="position:relative; z-index:40;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Nama Guru <span style="color:#ef4444;">*</span></label>
                    <select name="user_id" id="tambah_gp_user_id" class="form-select" required style="width:100%;">
                        <option value="">-- Pilih Akun Terdaftar --</option>
                        @foreach($akunUsers as $akun)
                            <option value="{{ $akun->id }}">{{ $akun->name }} ({{ $akun->nip ? 'NIP: ' . $akun->nip : $akun->email }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kelas Pengajian yang Diampu (Multi-Select) --}}
                <div style="position:relative; z-index:30;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Kelas Pengajian yang Diampu <span style="color:#ef4444;">*</span></label>
                    <div class="fi-select-wrap" id="tambah_kelas_wrap" style="position:relative;">
                        <div class="fi-select-trigger" onclick="togglePondokDropdown('tambah', 'kelas', event)"
                             style="cursor:pointer; background:var(--bg-main, #f8fafc); border:1px solid var(--border-light, #cbd5e1); min-height:38px;">
                            <div class="fi-select-main">
                                <span class="fi-select-prefix-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                </span>
                                <div class="fi-select-label-wrap" id="tambah_kelas_display">
                                    <span class="fi-select-placeholder">-- Pilih Kelas Pengajian --</span>
                                </div>
                            </div>
                            <div class="fi-select-actions">
                                <span class="fi-select-chevron">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="fi-select-popover" id="tambah_kelas_menu"
                             style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.15); z-index:1060; max-height:220px; overflow-y:auto; padding:6px; scrollbar-width:thin;">
                        </div>
                    </div>
                </div>

                {{-- Materi / Kitab Pondok (Multi-Select Opsional) --}}
                <div style="position:relative; z-index:20;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Materi / Kitab Pondok</label>
                    <div class="fi-select-wrap" id="tambah_materi_wrap" style="position:relative;">
                        <div class="fi-select-trigger" onclick="togglePondokDropdown('tambah', 'materi', event)"
                             style="cursor:pointer; background:var(--bg-main, #f8fafc); border:1px solid var(--border-light, #cbd5e1); min-height:38px;">
                            <div class="fi-select-main">
                                <span class="fi-select-prefix-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                    </svg>
                                </span>
                                <div class="fi-select-label-wrap" id="tambah_materi_display">
                                    <span class="fi-select-placeholder">-- Pilih Materi / Kitab (Opsional) --</span>
                                </div>
                            </div>
                            <div class="fi-select-actions">
                                <span class="fi-select-chevron">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="fi-select-popover" id="tambah_materi_menu"
                             style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.15); z-index:1050; max-height:220px; overflow-y:auto; padding:6px; scrollbar-width:thin;">
                        </div>
                    </div>
                </div>

                {{-- Keterangan / Peran --}}
                <div style="position:relative; z-index:10;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Keterangan</label>
                    <input type="text" name="pondok_catatan" id="tambah_gp_catatan" class="form-control" maxlength="255" placeholder="Contoh: Pengampu Ba'da Maghrib">
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc); border-bottom-left-radius:14px; border-bottom-right-radius:14px; position:relative; z-index:1;">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahGuruPondok()" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700;">Simpan Guru</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ========================================================
     MODAL 2: UBAH PLOTTING GURU PONDOK
     ======================================================== --}}
@if(!auth()->check() || auth()->user()->canDo('guru_pondok', 'edit'))
<div id="modalUbahGuruPondok" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:flex-start; justify-content:center; padding:32px 16px; overflow-y:auto; backdrop-filter:blur(3px); -webkit-overflow-scrolling:touch;">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:520px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:visible !important; text-align:left; position:relative; margin:auto;">
        <form action="" method="POST" id="formUbahGuruPondok" style="margin:0;">
            @csrf
            @method('PUT')
            <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between; border-top-left-radius:14px; border-top-right-radius:14px; background:var(--bg-surface, #ffffff);">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Ubah Plotting Guru Pondok</h3>
                <button type="button" onclick="tutupModalUbahGuruPondok()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:16px; position:relative;">
                {{-- Nama Guru (Readonly) --}}
                <div style="position:relative; z-index:40;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Nama Guru</label>
                    <div class="fi-select-trigger" style="background:#f1f5f9; border:1px solid var(--border-light, #cbd5e1); cursor:default; min-height:38px; width:100%;">
                        <div class="fi-select-main">
                            <span class="fi-select-prefix-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"/>
                                </svg>
                            </span>
                            <div class="fi-select-label-wrap">
                                <input type="text" id="ubah_gp_nama" readonly
                                       style="border:none; background:transparent; font-size:13px; font-weight:700; color:var(--text-primary); outline:none; width:100%; padding:0;">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kelas Pengajian yang Diampu --}}
                <div style="position:relative; z-index:30;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Kelas Pengajian yang Diampu <span style="color:#ef4444;">*</span></label>
                    <div class="fi-select-wrap" id="ubah_kelas_wrap" style="position:relative;">
                        <div class="fi-select-trigger" onclick="togglePondokDropdown('ubah', 'kelas', event)"
                             style="cursor:pointer; background:var(--bg-main, #f8fafc); border:1px solid var(--border-light, #cbd5e1); min-height:38px;">
                            <div class="fi-select-main">
                                <span class="fi-select-prefix-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                </span>
                                <div class="fi-select-label-wrap" id="ubah_kelas_display">
                                    <span class="fi-select-placeholder">-- Pilih Kelas Pengajian --</span>
                                </div>
                            </div>
                            <div class="fi-select-actions">
                                <span class="fi-select-chevron">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="fi-select-popover" id="ubah_kelas_menu"
                             style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.15); z-index:1060; max-height:220px; overflow-y:auto; padding:6px; scrollbar-width:thin;">
                        </div>
                    </div>
                </div>

                {{-- Materi / Kitab Pondok --}}
                <div style="position:relative; z-index:20;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Materi / Kitab Pondok</label>
                    <div class="fi-select-wrap" id="ubah_materi_wrap" style="position:relative;">
                        <div class="fi-select-trigger" onclick="togglePondokDropdown('ubah', 'materi', event)"
                             style="cursor:pointer; background:var(--bg-main, #f8fafc); border:1px solid var(--border-light, #cbd5e1); min-height:38px;">
                            <div class="fi-select-main">
                                <span class="fi-select-prefix-icon">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                                    </svg>
                                </span>
                                <div class="fi-select-label-wrap" id="ubah_materi_display">
                                    <span class="fi-select-placeholder">-- Pilih Materi / Kitab (Opsional) --</span>
                                </div>
                            </div>
                            <div class="fi-select-actions">
                                <span class="fi-select-chevron">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="fi-select-popover" id="ubah_materi_menu"
                             style="display:none; position:absolute; top:calc(100% + 4px); left:0; width:100%; background:var(--bg-surface, #ffffff); border:1px solid var(--border-light, #cbd5e1); border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.15); z-index:1050; max-height:220px; overflow-y:auto; padding:6px; scrollbar-width:thin;">
                        </div>
                    </div>
                </div>

                {{-- Keterangan / Peran --}}
                <div style="position:relative; z-index:10;">
                    <label class="form-label" style="font-size:12.5px; font-weight:600; color:var(--text-primary); margin-bottom:5px; display:block;">Keterangan</label>
                    <input type="text" name="pondok_catatan" id="ubah_gp_catatan" class="form-control" maxlength="255" placeholder="Contoh: Pengampu Ba'da Maghrib">
                </div>
            </div>

            <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-main, #f8fafc); border-bottom-left-radius:14px; border-bottom-right-radius:14px; position:relative; z-index:1;">
                <button type="button" class="btn btn-secondary" onclick="tutupModalUbahGuruPondok()" style="padding:7px 14px; border-radius:8px; font-size:12.5px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 18px; border-radius:8px; font-size:12.5px; font-weight:700;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ========================================================
     MODAL 3: DETAIL GURU PONDOK
     ======================================================== --}}
<div id="modalDetailGuruPondok" class="clean-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:flex-start; justify-content:center; padding:32px 16px; overflow-y:auto; backdrop-filter:blur(3px); -webkit-overflow-scrolling:touch;">
    <div style="background:var(--bg-surface, #ffffff); border-radius:14px; max-width:460px; width:100%; border:1px solid var(--border-light, #e2e8f0); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; text-align:left; position:relative; margin:auto;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-subtle, #f1f5f9); display:flex; align-items:center; justify-content:space-between; border-top-left-radius:14px; border-top-right-radius:14px; background:var(--bg-surface, #ffffff);">
            <h3 style="margin:0; font-size:15px; font-weight:700; color:var(--text-primary, #0f172a);">Detail Guru Pondok</h3>
            <button type="button" onclick="tutupModalDetailGuruPondok()" style="background:none; border:none; color:var(--text-muted, #94a3b8); cursor:pointer; padding:4px; font-size:18px;">&times;</button>
        </div>

        <div style="padding:20px; display:flex; flex-direction:column; gap:12px; font-size:13px;">
            <div style="display:flex; justify-content:space-between; gap:12px; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Nama</span>
                <strong id="det_gp_nama" style="color:var(--text-primary, #0f172a); text-align:right;"></strong>
            </div>
            <div style="display:flex; justify-content:space-between; gap:12px; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Email</span>
                <span id="det_gp_email" style="color:var(--text-primary, #0f172a); text-align:right;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; gap:12px; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">NIP</span>
                <span id="det_gp_nip" style="color:var(--text-primary, #0f172a); text-align:right;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; gap:12px; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Kelas Pengajian</span>
                <span id="det_gp_kelas" style="color:#047857; font-weight:700; text-align:right;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; gap:12px; padding-bottom:8px; border-bottom:1px solid var(--border-subtle, #f1f5f9);">
                <span style="color:var(--text-muted, #64748b);">Materi / Kitab</span>
                <span id="det_gp_materi" style="color:#0284c7; font-weight:700; text-align:right;"></span>
            </div>
            <div style="display:flex; justify-content:space-between; gap:12px;">
                <span style="color:var(--text-muted, #64748b);">Keterangan</span>
                <span id="det_gp_catatan" style="color:var(--text-primary, #0f172a); text-align:right;"></span>
            </div>
        </div>

        <div style="padding:14px 20px; border-top:1px solid var(--border-subtle, #f1f5f9); display:flex; justify-content:flex-end; background:var(--bg-main, #f8fafc);">
            <button type="button" class="btn btn-secondary" onclick="tutupModalDetailGuruPondok()" style="padding:7px 16px; border-radius:8px; font-size:12.5px;">Tutup</button>
        </div>
    </div>
</div>

<script>
const pondokKelasOptions = @json($kelasOptionsPayload);
const pondokMateriOptions = @json($materiOptionsPayload);
const guruPondokRowsMap = @json($guruPondokRowsMap);
const guruPondokBaseUrl = @json($guruPondokBaseUrl);

function escapeHtmlGp(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function closeAllPondokDropdowns(exceptWrapId) {
    ['tambah_kelas', 'tambah_materi', 'ubah_kelas', 'ubah_materi'].forEach(function(key) {
        const w = document.getElementById(key + '_wrap');
        const m = document.getElementById(key + '_menu');
        if (w && (key + '_wrap') !== exceptWrapId) {
            w.classList.remove('is-open');
        }
        if (m && (key + '_wrap') !== exceptWrapId) {
            m.style.display = 'none';
        }
    });
}

function togglePondokDropdown(prefix, kind, event) {
    if (event) event.stopPropagation();
    const wrapId = prefix + '_' + kind + '_wrap';
    const wrap = document.getElementById(wrapId);
    const menu = document.getElementById(prefix + '_' + kind + '_menu');

    closeAllPondokDropdowns(wrapId);
    document.querySelectorAll('.fi-select-wrap.is-open').forEach(function(w) {
        if (w !== wrap) w.classList.remove('is-open');
    });

    if (menu) {
        const isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';
        if (wrap) wrap.classList.toggle('is-open', !isOpen);
    }
}

function renderKelasDropdown(prefix, selectedIds) {
    const menu = document.getElementById(prefix + '_kelas_menu');
    const display = document.getElementById(prefix + '_kelas_display');
    if (!menu || !display) return;

    if (!pondokKelasOptions.length) {
        menu.innerHTML = '<div style="font-size:12.5px; color:#b45309; background:#fef3c7; border:1px solid #fde68a; padding:8px 12px; border-radius:6px; margin:4px;">Belum ada kelas pengajian terdaftar.</div>';
        display.innerHTML = '<span class="fi-select-placeholder" style="color:#b45309;">(Belum ada kelas pengajian)</span>';
        return;
    }

    const selectedSet = new Set((selectedIds || []).map(Number));
    let html = '';
    pondokKelasOptions.forEach(function(item) {
        const checked = selectedSet.has(Number(item.id));
        const safeNama = escapeHtmlGp(item.nama);
        const safeJenjang = item.jenjang ? escapeHtmlGp(item.jenjang) : '';
        html += `
            <div onclick="togglePondokOption(this, '${prefix}', 'kelas', event)"
                 style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text-primary); transition:background 0.12s; background:${checked ? 'rgba(16, 185, 129, 0.08)' : 'transparent'};">
                <input type="checkbox" name="kelas_pengajian_ids[]" value="${item.id}" data-label="${safeNama}" ${checked ? 'checked' : ''}
                       style="width:16px; height:16px; accent-color:#059669; cursor:pointer;"
                       onclick="event.stopPropagation(); syncPondokDisplay('${prefix}', 'kelas');">
                <span style="flex:1;">${safeNama}</span>
                ${safeJenjang ? `<span style="font-size:11px; color:var(--text-muted, #94a3b8); font-weight:500;">${safeJenjang}</span>` : ''}
            </div>
        `;
    });
    menu.innerHTML = html;
    syncPondokDisplay(prefix, 'kelas');
}

function renderMateriDropdown(prefix, selectedNames) {
    const menu = document.getElementById(prefix + '_materi_menu');
    const display = document.getElementById(prefix + '_materi_display');
    if (!menu || !display) return;

    if (!pondokMateriOptions.length) {
        menu.innerHTML = '<div style="font-size:12.5px; color:var(--text-muted, #64748b); padding:8px 10px;">Belum ada materi di menu Daftar Materi Pondok.</div>';
        display.innerHTML = '<span class="fi-select-placeholder">-- Belum ada materi tersedia --</span>';
        return;
    }

    const selectedSet = new Set((selectedNames || []).map(function(s) { return String(s).trim(); }));
    let html = '';
    pondokMateriOptions.forEach(function(item) {
        const checked = selectedSet.has(item.nama);
        const safeNama = escapeHtmlGp(item.nama);
        const safeKat = escapeHtmlGp(item.kategori);
        html += `
            <div onclick="togglePondokOption(this, '${prefix}', 'materi', event)"
                 style="display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:6px; cursor:pointer; font-size:13px; font-weight:600; color:var(--text-primary); transition:background 0.12s; background:${checked ? 'rgba(2, 132, 199, 0.08)' : 'transparent'};">
                <input type="checkbox" name="pondok_materi[]" value="${safeNama}" data-label="${safeNama}" ${checked ? 'checked' : ''}
                       style="width:16px; height:16px; accent-color:#0284c7; cursor:pointer;"
                       onclick="event.stopPropagation(); syncPondokDisplay('${prefix}', 'materi');">
                <span style="flex:1;">${safeNama}</span>
                <span style="font-size:11px; color:var(--text-muted, #94a3b8); font-weight:500;">${safeKat}</span>
            </div>
        `;
    });
    menu.innerHTML = html;
    syncPondokDisplay(prefix, 'materi');
}

function togglePondokOption(rowEl, prefix, kind, event) {
    if (event) event.stopPropagation();
    const cb = rowEl.querySelector('input[type="checkbox"]');
    if (cb) {
        cb.checked = !cb.checked;
        const activeBg = kind === 'kelas' ? 'rgba(16, 185, 129, 0.08)' : 'rgba(2, 132, 199, 0.08)';
        rowEl.style.background = cb.checked ? activeBg : 'transparent';
        syncPondokDisplay(prefix, kind);
    }
}

function syncPondokDisplay(prefix, kind) {
    const menu = document.getElementById(prefix + '_' + kind + '_menu');
    const display = document.getElementById(prefix + '_' + kind + '_display');
    if (!menu || !display) return;

    const checkedBoxes = menu.querySelectorAll('input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) {
        const ph = kind === 'kelas' ? '-- Pilih Kelas Pengajian --' : '-- Pilih Materi / Kitab (Opsional) --';
        display.innerHTML = `<span class="fi-select-placeholder" style="color:var(--text-muted, #94a3b8);">${ph}</span>`;
        return;
    }

    const pillStyle = kind === 'kelas'
        ? 'display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;'
        : 'display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:11.5px; font-weight:700; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;';

    let html = '';
    checkedBoxes.forEach(function(cb) {
        const lbl = cb.getAttribute('data-label') || cb.value;
        html += `<span class="fi-select-pill" style="${pillStyle}">${escapeHtmlGp(lbl)}</span>`;
    });
    display.innerHTML = html;
}

function bukaModalTambahGuruPondok() {
    const modal = document.getElementById('modalTambahGuruPondok');
    if (!modal) return;
    closeAllPondokDropdowns(null);
    const cat = document.getElementById('tambah_gp_catatan');
    if (cat) cat.value = '';
    renderKelasDropdown('tambah', []);
    renderMateriDropdown('tambah', []);
    modal.style.display = 'flex';
}

function tutupModalTambahGuruPondok() {
    closeAllPondokDropdowns(null);
    const modal = document.getElementById('modalTambahGuruPondok');
    if (modal) modal.style.display = 'none';
}

function bukaModalUbahGuruPondok(guruId) {
    const row = guruPondokRowsMap[guruId];
    const modal = document.getElementById('modalUbahGuruPondok');
    const form = document.getElementById('formUbahGuruPondok');
    if (!row || !modal || !form) return;

    closeAllPondokDropdowns(null);
    form.action = guruPondokBaseUrl + '/' + row.id;
    const namaInput = document.getElementById('ubah_gp_nama');
    const catInput = document.getElementById('ubah_gp_catatan');
    if (namaInput) namaInput.value = row.name;
    if (catInput) catInput.value = row.catatan || '';

    renderKelasDropdown('ubah', row.kelas_ids || []);
    renderMateriDropdown('ubah', row.materis || []);
    modal.style.display = 'flex';
}

function tutupModalUbahGuruPondok() {
    closeAllPondokDropdowns(null);
    const modal = document.getElementById('modalUbahGuruPondok');
    if (modal) modal.style.display = 'none';
}

function bukaModalDetailGuruPondok(guruId) {
    const row = guruPondokRowsMap[guruId];
    const modal = document.getElementById('modalDetailGuruPondok');
    if (!row || !modal) return;

    document.getElementById('det_gp_nama').textContent = row.name || '-';
    document.getElementById('det_gp_email').textContent = row.email || '-';
    document.getElementById('det_gp_nip').textContent = row.nip || '-';
    document.getElementById('det_gp_kelas').textContent = (row.kelas_names && row.kelas_names.length) ? row.kelas_names.join(', ') : '-';
    document.getElementById('det_gp_materi').textContent = (row.materis && row.materis.length) ? row.materis.join(', ') : '-';
    document.getElementById('det_gp_catatan').textContent = row.catatan || '-';

    modal.style.display = 'flex';
}

function tutupModalDetailGuruPondok() {
    const modal = document.getElementById('modalDetailGuruPondok');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    ['formTambahGuruPondok', 'formUbahGuruPondok'].forEach(function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            const checked = this.querySelectorAll('input[name="kelas_pengajian_ids[]"]:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Pilih setidaknya satu kelas pengajian yang diampu.');
                return false;
            }
        });
    });
});

document.addEventListener('click', function(e) {
    ['tambah_kelas', 'tambah_materi', 'ubah_kelas', 'ubah_materi'].forEach(function(key) {
        const wrap = document.getElementById(key + '_wrap');
        const menu = document.getElementById(key + '_menu');
        if (wrap && !wrap.contains(e.target)) {
            if (menu) menu.style.display = 'none';
            wrap.classList.remove('is-open');
        }
    });

    if (e.target && e.target.classList.contains('clean-modal-backdrop')) {
        e.target.style.display = 'none';
        closeAllPondokDropdowns(null);
    }
});
</script>
@endsection
