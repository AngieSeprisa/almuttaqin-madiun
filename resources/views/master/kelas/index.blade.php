@extends('layouts.app')

@section('title', 'Master Ruang Kelas')

@section('header-actions')
<div class="mstr-header-actions">
    @if($isYayasan)
    <form action="{{ route('master.kelas.muat-standar') }}" method="POST" class="d-inline" onsubmit="return confirm('Muat kelas standar SMP (7A-9B) dan SMA (10A-12B)?');">
        @csrf
        <button type="submit" class="btn btn-secondary mstr-btn-action" title="Muat otomatis kelas standar SMP & SMA">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"></path>
            </svg>
            <span class="btn-text">Muat Standar</span>
        </button>
    </form>
    @endif

    <button type="button" class="btn btn-primary mstr-btn-action" onclick="bukaModalTambahKelas()" title="Tambah Ruang Kelas">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        <span class="btn-text">Tambah Kelas</span>
    </button>
</div>
@endsection

@section('content')
<style>
    .mstr-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    .mstr-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .mstr-btn-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 10px;
        height: 38px;
        white-space: nowrap;
    }

    /* Stats Grid */
    .mstr-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        width: 100%;
    }

    .mstr-stat-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .mstr-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.04);
    }

    .mstr-stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .mstr-stat-icon svg {
        width: 22px;
        height: 22px;
    }

    .mstr-stat-icon.blue { background: rgba(0, 82, 255, 0.1); color: #0052FF; }
    .mstr-stat-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
    .mstr-stat-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
    .mstr-stat-icon.amber { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }

    .mstr-stat-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .mstr-stat-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .mstr-stat-label {
        font-size: 12.5px;
        color: var(--text-secondary);
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Top Bar: Tabs & Search */
    .mstr-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .mstr-tabs-wrap {
        display: inline-flex;
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        padding: 4px;
        border-radius: 12px;
        gap: 4px;
    }

    .mstr-tab-btn {
        padding: 6px 16px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        background: transparent;
        color: var(--text-secondary);
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .mstr-tab-btn.active {
        background: var(--electric-blue);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 82, 255, 0.25);
    }

    .mstr-search-box {
        position: relative;
        flex: 1;
        max-width: 320px;
        min-width: 200px;
    }

    .mstr-search-input {
        width: 100%;
        padding: 8px 12px 8px 36px;
        border-radius: 10px;
        border: 1px solid var(--border-light);
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 13px;
        box-sizing: border-box;
    }

    .mstr-search-input:focus {
        outline: none;
        border-color: var(--electric-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
    }

    .mstr-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: var(--text-muted);
        pointer-events: none;
    }

    /* Table Card */
    .mstr-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }

    .mstr-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
        text-align: left;
    }

    .mstr-table th {
        background: var(--table-th-bg);
        color: var(--text-secondary);
        font-weight: 700;
        font-size: 11.5px;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-light);
    }

    .mstr-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-light);
        color: var(--text-primary);
        vertical-align: middle;
    }

    .mstr-table tbody tr:hover {
        background: var(--table-hover-bg);
    }

    .mstr-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }

    .mstr-badge-smp { background: rgba(59, 130, 246, 0.1); color: #2563EB; border: 1px solid rgba(59, 130, 246, 0.2); }
    .mstr-badge-sma { background: rgba(139, 92, 246, 0.1); color: #7C3AED; border: 1px solid rgba(139, 92, 246, 0.2); }
    .mstr-badge-aktif { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .mstr-badge-nonaktif { background: rgba(107, 114, 128, 0.1); color: #4B5563; }

    .mstr-actions-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mstr-btn-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--border-light);
        background: var(--bg-surface);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .mstr-btn-icon:hover {
        border-color: var(--electric-blue);
        color: var(--electric-blue);
        background: rgba(0, 82, 255, 0.05);
    }

    .mstr-btn-icon.danger:hover {
        border-color: #EF4444;
        color: #EF4444;
        background: rgba(239, 68, 68, 0.05);
    }

    /* Modal Form */
    .mstr-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }

    .mstr-modal-overlay.show {
        display: flex;
    }

    .mstr-modal-box {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        overflow: hidden;
        animation: modalScaleUp 0.2s ease;
    }

    @keyframes modalScaleUp {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .mstr-modal-header {
        padding: 18px 22px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .mstr-modal-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .mstr-modal-close {
        background: transparent;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 6px;
    }

    .mstr-modal-close:hover {
        background: rgba(0,0,0,0.05);
        color: var(--text-primary);
    }

    .mstr-modal-body {
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .mstr-form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .mstr-form-label {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .mstr-form-control {
        width: 100%;
        padding: 9px 12px;
        border-radius: 10px;
        border: 1px solid var(--border-light);
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 13.5px;
        box-sizing: border-box;
    }

    .mstr-form-control:focus {
        outline: none;
        border-color: var(--electric-blue);
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
    }

    .mstr-modal-footer {
        padding: 16px 22px;
        border-top: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        background: var(--table-th-bg);
    }

    @media (max-width: 900px) {
        .mstr-stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .mstr-stats-grid {
            grid-template-columns: 1fr;
        }
        .mstr-topbar {
            flex-direction: column;
            align-items: stretch;
        }
        .mstr-search-box {
            max-width: 100%;
        }
    }
</style>

<div class="mstr-container">

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div style="background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.3); color:#059669; padding:12px 18px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:500;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); color:#DC2626; padding:12px 18px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:13.5px; font-weight:500;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    {{-- Statistik Cards --}}
    <div class="mstr-stats-grid">
        <div class="mstr-stat-card">
            <div class="mstr-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['total'] }}</span>
                <span class="mstr-stat-label">Total Ruang Kelas</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['aktif'] }}</span>
                <span class="mstr-stat-label">Kelas Aktif</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['total_smp'] }}</span>
                <span class="mstr-stat-label">Kelas SMP</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['total_sma'] }}</span>
                <span class="mstr-stat-label">Kelas SMA</span>
            </div>
        </div>
    </div>

    {{-- Top Bar Kontrol --}}
    <div class="mstr-topbar">
        <div class="mstr-tabs-wrap">
            <a href="{{ route('master.kelas.index', ['jenjang' => 'all']) }}" class="mstr-tab-btn {{ $selectedJenjang === 'all' ? 'active' : '' }}">
                Semua Jenjang
            </a>
            <a href="{{ route('master.kelas.index', ['jenjang' => 'smp']) }}" class="mstr-tab-btn {{ strtolower($selectedJenjang) === 'smp' ? 'active' : '' }}">
                SMP
            </a>
            <a href="{{ route('master.kelas.index', ['jenjang' => 'sma']) }}" class="mstr-tab-btn {{ strtolower($selectedJenjang) === 'sma' ? 'active' : '' }}">
                SMA
            </a>
        </div>

        <div class="mstr-search-box">
            <svg class="mstr-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="inputCariKelas" class="mstr-search-input" placeholder="Cari nama kelas atau tingkat..." onkeyup="filterKelas()">
        </div>
    </div>

    {{-- Tabel Master Kelas --}}
    <div class="mstr-card">
        <div style="overflow-x:auto;">
            <table class="mstr-table" id="tabelMasterKelas">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Ruang Kelas</th>
                        <th>Lembaga / Jenjang</th>
                        <th>Tingkat</th>
                        <th>Urutan</th>
                        <th>Status</th>
                        <th style="text-align:right; width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classrooms as $index => $item)
                    <tr class="baris-kelas" data-nama="{{ strtolower($item->nama_kelas) }}" data-tingkat="{{ $item->tingkat }}" data-jenjang="{{ strtolower($item->jenjang) }}">
                        <td style="color:var(--text-muted); font-weight:600;">{{ $index + 1 }}</td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="width:34px; height:34px; border-radius:8px; background:rgba(0, 82, 255, 0.08); color:#0052FF; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">
                                    {{ $item->tingkat }}
                                </div>
                                <div>
                                    <strong style="font-size:14px; color:var(--text-primary);">Kelas {{ $item->nama_kelas }}</strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="mstr-badge {{ $item->jenjang === 'SMP' ? 'mstr-badge-smp' : 'mstr-badge-sma' }}">
                                {{ $item->jenjang }} - {{ $item->lembaga?->singkatan ?? $item->lembaga?->nama ?? '' }}
                            </span>
                        </td>
                        <td>
                            <span style="font-weight:600; color:var(--text-secondary);">Tingkat {{ $item->tingkat }}</span>
                        </td>
                        <td>
                            <span style="color:var(--text-muted); font-weight:500;">#{{ $item->urutan }}</span>
                        </td>
                        <td>
                            <form action="{{ route('master.kelas.toggle-status', $item->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="mstr-badge {{ $item->is_active ? 'mstr-badge-aktif' : 'mstr-badge-nonaktif' }}" style="border:none; cursor:pointer;" title="Klik untuk ubah status aktif/non-aktif">
                                    <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                                    {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </form>
                        </td>
                        <td style="text-align:right;">
                            <div class="mstr-actions-wrap" style="justify-content:flex-end;">
                                <button type="button" class="mstr-btn-icon" title="Edit Kelas"
                                        onclick="bukaModalEditKelas({
                                            id: {{ $item->id }},
                                            lembaga_id: {{ $item->lembaga_id }},
                                            jenjang: '{{ $item->jenjang }}',
                                            nama_kelas: '{{ $item->nama_kelas }}',
                                            tingkat: {{ $item->tingkat }},
                                            urutan: {{ $item->urutan }},
                                            is_active: {{ $item->is_active ? 'true' : 'false' }}
                                        })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>

                                <form action="{{ route('master.kelas.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruang kelas {{ $item->nama_kelas }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mstr-btn-icon danger" title="Hapus Kelas">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px 20px;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;opacity:0.5;">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                                <span style="font-size:15px; font-weight:600; color:var(--text-secondary);">Belum ada data ruang kelas</span>
                                <span style="font-size:13px;">Klik tombol "Tambah Kelas" atau "Muat Standar" untuk mengisi ruang kelas secara otomatis.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH KELAS --}}
<div class="mstr-modal-overlay" id="modalTambahKelas" onclick="if(event.target === this) tutupModalTambahKelas()">
    <div class="mstr-modal-box">
        <form action="{{ route('master.kelas.store') }}" method="POST">
            @csrf
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Tambah Ruang Kelas Baru</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalTambahKelas()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                <div class="mstr-form-group">
                    <label class="mstr-form-label">Lembaga Sekolah *</label>
                    <select name="lembaga_id" id="tambahLembagaId" class="mstr-form-control" required onchange="sesuaikanJenjangOtomatis('tambah')">
                        @foreach($sekolahLembagas as $lem)
                        <option value="{{ $lem->id }}" data-kode="{{ strtolower($lem->kode) }}">
                            {{ $lem->nama }} ({{ $lem->singkatan ?: $lem->kode }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Jenjang *</label>
                    <select name="jenjang" id="tambahJenjang" class="mstr-form-control" required>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Nama Kelas *</label>
                        <input type="text" name="nama_kelas" class="mstr-form-control" placeholder="Contoh: 7C, 10C" required>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Tingkat / Rombel *</label>
                        <select name="tingkat" id="tambahTingkat" class="mstr-form-control" required>
                            <option value="7">7 (SMP)</option>
                            <option value="8">8 (SMP)</option>
                            <option value="9">9 (SMP)</option>
                            <option value="10">10 (SMA)</option>
                            <option value="11">11 (SMA)</option>
                            <option value="12">12 (SMA)</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Urutan Tampilan</label>
                        <input type="number" name="urutan" class="mstr-form-control" placeholder="Otomatis" min="1">
                    </div>

                    <div class="mstr-form-group" style="justify-content:center;">
                        <label class="mstr-form-label">&nbsp;</label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600;">
                            <input type="checkbox" name="is_active" value="1" checked style="width:16px;height:16px;">
                            <span>Status Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mstr-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahKelas()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Ruang Kelas</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT KELAS --}}
<div class="mstr-modal-overlay" id="modalEditKelas" onclick="if(event.target === this) tutupModalEditKelas()">
    <div class="mstr-modal-box">
        <form id="formEditKelas" method="POST">
            @csrf
            @method('PUT')
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Edit Ruang Kelas</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalEditKelas()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                <div class="mstr-form-group">
                    <label class="mstr-form-label">Lembaga Sekolah *</label>
                    <select name="lembaga_id" id="editLembagaId" class="mstr-form-control" required onchange="sesuaikanJenjangOtomatis('edit')">
                        @foreach($sekolahLembagas as $lem)
                        <option value="{{ $lem->id }}" data-kode="{{ strtolower($lem->kode) }}">
                            {{ $lem->nama }} ({{ $lem->singkatan ?: $lem->kode }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Jenjang *</label>
                    <select name="jenjang" id="editJenjang" class="mstr-form-control" required>
                        <option value="SMP">SMP</option>
                        <option value="SMA">SMA</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Nama Kelas *</label>
                        <input type="text" name="nama_kelas" id="editNamaKelas" class="mstr-form-control" required>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Tingkat / Rombel *</label>
                        <select name="tingkat" id="editTingkat" class="mstr-form-control" required>
                            <option value="7">7 (SMP)</option>
                            <option value="8">8 (SMP)</option>
                            <option value="9">9 (SMP)</option>
                            <option value="10">10 (SMA)</option>
                            <option value="11">11 (SMA)</option>
                            <option value="12">12 (SMA)</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Urutan Tampilan</label>
                        <input type="number" name="urutan" id="editUrutan" class="mstr-form-control" min="1">
                    </div>

                    <div class="mstr-form-group" style="justify-content:center;">
                        <label class="mstr-form-label">&nbsp;</label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600;">
                            <input type="checkbox" name="is_active" id="editIsActive" value="1" style="width:16px;height:16px;">
                            <span>Status Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mstr-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalEditKelas()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalTambahKelas() {
        document.getElementById('modalTambahKelas').classList.add('show');
        sesuaikanJenjangOtomatis('tambah');
    }

    function tutupModalTambahKelas() {
        document.getElementById('modalTambahKelas').classList.remove('show');
    }

    function bukaModalEditKelas(data) {
        var form = document.getElementById('formEditKelas');
        form.action = "{{ url('master/kelas') }}/" + data.id;

        document.getElementById('editLembagaId').value = data.lembaga_id;
        document.getElementById('editJenjang').value = data.jenjang;
        document.getElementById('editNamaKelas').value = data.nama_kelas;
        document.getElementById('editTingkat').value = data.tingkat;
        document.getElementById('editUrutan').value = data.urutan;
        document.getElementById('editIsActive').checked = Boolean(data.is_active);

        document.getElementById('modalEditKelas').classList.add('show');
    }

    function tutupModalEditKelas() {
        document.getElementById('modalEditKelas').classList.remove('show');
    }

    function sesuaikanJenjangOtomatis(mode) {
        var lemSelect = document.getElementById(mode + 'LembagaId');
        var jenSelect = document.getElementById(mode + 'Jenjang');
        if (!lemSelect || !jenSelect) return;

        var opt = lemSelect.options[lemSelect.selectedIndex];
        if (opt) {
            var kode = opt.getAttribute('data-kode');
            if (kode === 'smp') {
                jenSelect.value = 'SMP';
            } else if (kode === 'sma') {
                jenSelect.value = 'SMA';
            }
        }
    }

    function filterKelas() {
        var input = document.getElementById('inputCariKelas');
        var query = input.value.toLowerCase().trim();
        var rows = document.querySelectorAll('.baris-kelas');

        rows.forEach(function(row) {
            var nama = row.getAttribute('data-nama') || '';
            var tingkat = row.getAttribute('data-tingkat') || '';
            var jenjang = row.getAttribute('data-jenjang') || '';

            if (nama.indexOf(query) > -1 || tingkat.indexOf(query) > -1 || jenjang.indexOf(query) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Keyboard ESC to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalTambahKelas();
            tutupModalEditKelas();
        }
    });
</script>
@endsection
