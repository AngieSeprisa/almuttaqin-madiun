@extends('layouts.app')

@section('title', 'Master Jam Pelajaran')

@section('header-actions')
<div class="mstr-header-actions">
    <form action="{{ route('master.jam-pelajaran.muat-standar') }}" method="POST" class="d-inline" onsubmit="return confirm('Muat jadwal jam KBM standar (Senin-Jumat lengkap dengan jam 1-8, Upacara, Istirahat, dan Isoma)?');">
        @csrf
        <button type="submit" class="btn btn-secondary mstr-btn-action" title="Muat template standar Senin-Jumat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                <path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"></path>
            </svg>
            <span class="btn-text">Muat Jam Standar</span>
        </button>
    </form>

    <button type="button" class="btn btn-primary mstr-btn-action" onclick="bukaModalTambahSlot()" title="Tambah Slot Jam Pelajaran">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        <span class="btn-text">Tambah Slot</span>
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

    /* Topbar & Tabs Hari */
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
        overflow-x: auto;
        max-width: 100%;
    }

    .mstr-tab-btn {
        padding: 6px 14px;
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
        white-space: nowrap;
    }

    .mstr-tab-btn.active {
        background: var(--electric-blue);
        color: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 82, 255, 0.25);
    }

    .mstr-tab-badge {
        font-size: 11px;
        padding: 1px 6px;
        border-radius: 10px;
        background: rgba(0,0,0,0.06);
        color: currentColor;
    }

    .mstr-tab-btn.active .mstr-tab-badge {
        background: rgba(255,255,255,0.25);
    }

    .mstr-search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
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

    .mstr-badge-kbm { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
    .mstr-badge-upacara { background: rgba(139, 92, 246, 0.1); color: #7C3AED; border: 1px solid rgba(139, 92, 246, 0.2); }
    .mstr-badge-istirahat { background: rgba(245, 158, 11, 0.1); color: #D97706; border: 1px solid rgba(245, 158, 11, 0.2); }
    .mstr-badge-isoma { background: rgba(14, 165, 233, 0.1); color: #0284C7; border: 1px solid rgba(14, 165, 233, 0.2); }
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
        max-width: 520px;
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
        max-height: 75vh;
        overflow-y: auto;
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

    .mstr-days-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
    }

    .mstr-day-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 8px;
        background: var(--table-th-bg);
        border: 1px solid var(--border-light);
        font-size: 12.5px;
        cursor: pointer;
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
        .mstr-days-grid {
            grid-template-columns: repeat(2, 1fr);
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

    {{-- Stats Grid --}}
    <div class="mstr-stats-grid">
        <div class="mstr-stat-card">
            <div class="mstr-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['total'] }}</span>
                <span class="mstr-stat-label">Total Slot Jam</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['kbm'] }}</span>
                <span class="mstr-stat-label">Slot KBM (Mengajar)</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                    <line x1="6" y1="1" x2="6" y2="4"></line>
                    <line x1="10" y1="1" x2="10" y2="4"></line>
                    <line x1="14" y1="1" x2="14" y2="4"></line>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['non_kbm'] }}</span>
                <span class="mstr-stat-label">Slot Istirahat / Upacara</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['aktif'] }}</span>
                <span class="mstr-stat-label">Slot Aktif</span>
            </div>
        </div>
    </div>

    {{-- Tabs Hari & Pencarian --}}
    <div class="mstr-topbar">
        <div class="mstr-tabs-wrap">
            <a href="{{ route('master.jam-pelajaran.index', ['hari' => 'all']) }}" class="mstr-tab-btn {{ $activeHari === 'all' ? 'active' : '' }}">
                <span>Semua Hari</span>
            </a>
            @foreach($daftarHari as $dh)
            <a href="{{ route('master.jam-pelajaran.index', ['hari' => $dh]) }}" class="mstr-tab-btn {{ $activeHari === $dh ? 'active' : '' }}">
                <span>{{ $dh }}</span>
                @if(isset($slotCountPerHari[$dh]) && $slotCountPerHari[$dh] > 0)
                <span class="mstr-tab-badge">{{ $slotCountPerHari[$dh] }}</span>
                @endif
            </a>
            @endforeach
        </div>

        <div class="mstr-search-box">
            <svg class="mstr-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input type="text" id="inputCariSlot" class="mstr-search-input" placeholder="Cari jam ke, label, waktu..." onkeyup="filterSlot()">
        </div>
    </div>

    {{-- Tabel Master Jam Pelajaran --}}
    <div class="mstr-card table-card">
        <div style="overflow-x:auto;">
            <table class="mstr-table" id="tabelMasterSlot">
                <thead>
                    <tr>
                        <th style="width:60px;">Urutan</th>
                        <th>Jam Ke</th>
                        <th>Label Kegiatan</th>
                        <th>Tipe Kegiatan</th>
                        <th>Rentang Waktu</th>
                        <th>Jenjang</th>
                        @if($activeHari === 'all')
                        <th>Hari</th>
                        @endif
                        <th>Status</th>
                        <th style="text-align:right; width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timeSlots as $item)
                    <tr class="baris-slot" data-label="{{ strtolower($item->label) }}" data-tipe="{{ strtolower($item->tipe_kegiatan) }}" data-hari="{{ strtolower($item->hari) }}" data-jam="{{ $item->jam_ke }}">
                        <td style="color:var(--text-muted); font-weight:600;">#{{ $item->urutan }}</td>
                        <td>
                            @if($item->jam_ke > 0)
                            <span style="font-weight:700; color:var(--text-primary); font-size:14px;">Jam ke-{{ $item->jam_ke }}</span>
                            @else
                            <span style="font-weight:600; color:var(--text-muted); font-size:12.5px;">Non-KBM</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                @if($item->tipe_kegiatan === 'KBM')
                                <div style="width:8px; height:8px; border-radius:50%; background:#10B981;"></div>
                                @elseif($item->tipe_kegiatan === 'Upacara')
                                <div style="width:8px; height:8px; border-radius:50%; background:#8B5CF6;"></div>
                                @elseif($item->tipe_kegiatan === 'Sholat Dhuhur')
                                <div style="width:8px; height:8px; border-radius:50%; background:#0284C7;"></div>
                                @else
                                <div style="width:8px; height:8px; border-radius:50%; background:#F59E0B;"></div>
                                @endif
                                <strong style="font-size:13.5px; color:var(--text-primary);">{{ $item->label }}</strong>
                            </div>
                        </td>
                        <td>
                            @php
                                $badgeClass = 'mstr-badge-kbm';
                                if ($item->tipe_kegiatan === 'Upacara') $badgeClass = 'mstr-badge-upacara';
                                elseif ($item->tipe_kegiatan === 'Istirahat') $badgeClass = 'mstr-badge-istirahat';
                                elseif ($item->tipe_kegiatan === 'Sholat Dhuhur') $badgeClass = 'mstr-badge-isoma';
                            @endphp
                            <span class="mstr-badge {{ $badgeClass }}">
                                {{ $item->tipe_kegiatan }}
                            </span>
                        </td>
                        <td>
                            <div style="display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:13px; color:var(--text-primary); font-family:monospace;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-muted);">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>{{ $item->rentang_waktu }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:12.5px; font-weight:600; color:var(--text-secondary);">
                                {{ $item->jenjang }}
                            </span>
                        </td>
                        @if($activeHari === 'all')
                        <td>
                            <span style="font-weight:600; color:var(--text-primary);">{{ $item->hari }}</span>
                        </td>
                        @endif
                        <td>
                            <form action="{{ route('master.jam-pelajaran.toggle-status', $item->id) }}" method="POST" class="d-inline">
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
                                <button type="button" class="mstr-btn-icon" title="Edit Slot"
                                        onclick="bukaModalEditSlot({
                                            id: {{ $item->id }},
                                            hari: '{{ $item->hari }}',
                                            jenjang: '{{ $item->jenjang }}',
                                            jam_ke: {{ $item->jam_ke }},
                                            waktu_mulai: '{{ substr($item->waktu_mulai, 0, 5) }}',
                                            waktu_selesai: '{{ substr($item->waktu_selesai, 0, 5) }}',
                                            tipe_kegiatan: '{{ $item->tipe_kegiatan }}',
                                            label: '{{ addslashes($item->label) }}',
                                            urutan: {{ $item->urutan }},
                                            is_break: {{ $item->is_break ? 'true' : 'false' }},
                                            is_active: {{ $item->is_active ? 'true' : 'false' }}
                                        })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>

                                <form action="{{ route('master.jam-pelajaran.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus slot {{ $item->label }} ({{ $item->hari }})?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mstr-btn-icon danger" title="Hapus Slot">
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
                        <td colspan="{{ $activeHari === 'all' ? 9 : 8 }}" style="text-align:center; padding:50px 20px;">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:12px; color:var(--text-muted);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;opacity:0.5;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span style="font-size:15px; font-weight:600; color:var(--text-secondary);">Belum ada slot jam untuk hari ini</span>
                                <span style="font-size:13px;">Gunakan tombol "Muat Jam Standar" untuk mengisi slot jam secara instan atau "Tambah Slot" manual.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Card List untuk Tampilan Mobile (<= 768px) --}}
    <div class="mobile-card-list">
        @forelse($timeSlots as $item)
        <div class="mobile-card-item">
            <div class="mobile-card-info">
                <div class="mobile-card-title">{{ $item->label }}</div>
                <div class="mobile-card-subtitle">
                    {{ $item->jam_ke > 0 ? 'Jam ke-' . $item->jam_ke : 'Non-KBM' }} &bull; {{ $item->hari }} &bull; {{ $item->rentang_waktu }}
                </div>

                <div class="mobile-card-meta-grid">
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Tipe</span>
                        <span class="mobile-card-pill mobile-card-pill-blue">{{ $item->tipe_kegiatan }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">Jenjang</span>
                        <span class="mobile-card-pill mobile-card-pill-gray">{{ $item->jenjang }}</span>
                    </div>
                    <div class="mobile-card-meta-item">
                        <span class="mobile-card-meta-label">&nbsp;</span>
                        <form action="{{ route('master.jam-pelajaran.toggle-status', $item->id) }}" method="POST" style="margin:0; display:inline-block;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" style="background:none; border:none; padding:0; cursor:pointer;" title="Klik ubah status">
                                <span class="status-pill {{ $item->is_active ? 'status-pill-active' : 'status-pill-inactive' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mobile-card-actions">
                <span class="mobile-card-action-title">Aksi</span>
                <div class="mobile-card-action-buttons">
                    <button type="button" class="mobile-btn-pastel mobile-btn-edit" title="Edit Slot"
                            onclick="bukaModalEditSlot({
                                id: {{ $item->id }},
                                hari: '{{ $item->hari }}',
                                jenjang: '{{ $item->jenjang }}',
                                jam_ke: {{ $item->jam_ke }},
                                waktu_mulai: '{{ substr($item->waktu_mulai, 0, 5) }}',
                                waktu_selesai: '{{ substr($item->waktu_selesai, 0, 5) }}',
                                tipe_kegiatan: '{{ $item->tipe_kegiatan }}',
                                label: '{{ addslashes($item->label) }}',
                                urutan: {{ $item->urutan }},
                                is_break: {{ $item->is_break ? 'true' : 'false' }},
                                is_active: {{ $item->is_active ? 'true' : 'false' }}
                            })">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>

                    <form action="{{ route('master.jam-pelajaran.destroy', $item->id) }}" method="POST" style="margin:0; display:inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus slot {{ $item->label }} ({{ $item->hari }})?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mobile-btn-pastel mobile-btn-delete" title="Hapus Slot">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div style="background:#fff; border-radius:12px; padding:30px 16px; text-align:center; color:var(--text-muted); border:1px solid #e2e8f0;">
            Belum ada slot jam untuk filter ini.
        </div>
        @endforelse
    </div>
</div>

{{-- MODAL TAMBAH SLOT JAM --}}
<div class="mstr-modal-overlay" id="modalTambahSlot" onclick="if(event.target === this) tutupModalTambahSlot()">
    <div class="mstr-modal-box">
        <form action="{{ route('master.jam-pelajaran.store') }}" method="POST">
            @csrf
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Tambah Slot Jam Pelajaran</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalTambahSlot()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                <div class="mstr-form-group">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <label class="mstr-form-label">Terapkan ke Hari *</label>
                        <button type="button" style="border:none; background:transparent; color:#0052FF; font-size:11.5px; font-weight:600; cursor:pointer;" onclick="pilihSemuaHariKerja()">
                            Pilih Senin - Jumat
                        </button>
                    </div>
                    <div class="mstr-days-grid">
                        @foreach($daftarHari as $dh)
                        <label class="mstr-day-pill">
                            <input type="checkbox" name="hari_list[]" value="{{ $dh }}" class="cb-hari-tambah" {{ $activeHari === $dh || ($activeHari === 'all' && $dh === 'Senin') ? 'checked' : '' }}>
                            <span>{{ $dh }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Jenjang *</label>
                        <select name="jenjang" class="mstr-form-control" required>
                            <option value="Semua">Semua Jenjang</option>
                            <option value="SMP">SMP Saja</option>
                            <option value="SMA">SMA Saja</option>
                        </select>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Tipe Kegiatan *</label>
                        <select name="tipe_kegiatan" id="tambahTipeKegiatan" class="mstr-form-control" required onchange="sesuaikanTipeKegiatan('tambah')">
                            <option value="KBM">KBM (Jam Pelajaran)</option>
                            <option value="Istirahat">Istirahat</option>
                            <option value="Upacara">Upacara</option>
                            <option value="Sholat Dhuhur">Sholat Dhuhur & Isoma</option>
                            <option value="Ekstrakurikuler">Ekstrakurikuler</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Jam Ke *</label>
                        <input type="number" name="jam_ke" id="tambahJamKe" class="mstr-form-control" value="1" min="0" max="20" required>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Label / Nama Kegiatan</label>
                        <input type="text" name="label" id="tambahLabel" class="mstr-form-control" placeholder="Contoh: Jam 1, Istirahat 1">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Waktu Mulai *</label>
                        <input type="time" name="waktu_mulai" class="mstr-form-control" value="07:45" required>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Waktu Selesai *</label>
                        <input type="time" name="waktu_selesai" class="mstr-form-control" value="08:30" required>
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
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahSlot()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Slot Jam</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT SLOT JAM --}}
<div class="mstr-modal-overlay" id="modalEditSlot" onclick="if(event.target === this) tutupModalEditSlot()">
    <div class="mstr-modal-box">
        <form id="formEditSlot" method="POST">
            @csrf
            @method('PUT')
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Edit Slot Jam Pelajaran</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalEditSlot()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Hari *</label>
                        <select name="hari" id="editHari" class="mstr-form-control" required>
                            @foreach($daftarHari as $dh)
                            <option value="{{ $dh }}">{{ $dh }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Jenjang *</label>
                        <select name="jenjang" id="editJenjang" class="mstr-form-control" required>
                            <option value="Semua">Semua Jenjang</option>
                            <option value="SMP">SMP</option>
                            <option value="SMA">SMA</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Tipe Kegiatan *</label>
                        <select name="tipe_kegiatan" id="editTipeKegiatan" class="mstr-form-control" required onchange="sesuaikanTipeKegiatan('edit')">
                            <option value="KBM">KBM (Jam Pelajaran)</option>
                            <option value="Istirahat">Istirahat</option>
                            <option value="Upacara">Upacara</option>
                            <option value="Sholat Dhuhur">Sholat Dhuhur & Isoma</option>
                            <option value="Ekstrakurikuler">Ekstrakurikuler</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Jam Ke *</label>
                        <input type="number" name="jam_ke" id="editJamKe" class="mstr-form-control" min="0" max="20" required>
                    </div>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Label / Nama Kegiatan</label>
                    <input type="text" name="label" id="editLabel" class="mstr-form-control" required>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Waktu Mulai *</label>
                        <input type="time" name="waktu_mulai" id="editWaktuMulai" class="mstr-form-control" required>
                    </div>

                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Waktu Selesai *</label>
                        <input type="time" name="waktu_selesai" id="editWaktuSelesai" class="mstr-form-control" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Urutan</label>
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
                <button type="button" class="btn btn-secondary" onclick="tutupModalEditSlot()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalTambahSlot() {
        document.getElementById('modalTambahSlot').classList.add('show');
    }

    function tutupModalTambahSlot() {
        document.getElementById('modalTambahSlot').classList.remove('show');
    }

    function bukaModalEditSlot(data) {
        var form = document.getElementById('formEditSlot');
        form.action = "{{ url('master/jam-pelajaran') }}/" + data.id;

        document.getElementById('editHari').value = data.hari;
        document.getElementById('editJenjang').value = data.jenjang;
        document.getElementById('editTipeKegiatan').value = data.tipe_kegiatan;
        document.getElementById('editJamKe').value = data.jam_ke;
        document.getElementById('editLabel').value = data.label;
        document.getElementById('editWaktuMulai').value = data.waktu_mulai;
        document.getElementById('editWaktuSelesai').value = data.waktu_selesai;
        document.getElementById('editUrutan').value = data.urutan;
        document.getElementById('editIsActive').checked = Boolean(data.is_active);

        document.getElementById('modalEditSlot').classList.add('show');
    }

    function tutupModalEditSlot() {
        document.getElementById('modalEditSlot').classList.remove('show');
    }

    function pilihSemuaHariKerja() {
        var kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        document.querySelectorAll('.cb-hari-tambah').forEach(function(cb) {
            cb.checked = kerja.includes(cb.value);
        });
    }

    function sesuaikanTipeKegiatan(mode) {
        var tipe = document.getElementById(mode + 'TipeKegiatan').value;
        var jamKeInput = document.getElementById(mode + 'JamKe');
        var labelInput = document.getElementById(mode + 'Label');

        if (tipe !== 'KBM') {
            if (jamKeInput.value > 0) jamKeInput.value = 0;
            if (labelInput && !labelInput.value) {
                labelInput.value = tipe;
            }
        } else {
            if (jamKeInput.value === '0') jamKeInput.value = 1;
        }
    }

    function filterSlot() {
        var input = document.getElementById('inputCariSlot');
        var query = input.value.toLowerCase().trim();
        var rows = document.querySelectorAll('.baris-slot');

        rows.forEach(function(row) {
            var label = row.getAttribute('data-label') || '';
            var tipe = row.getAttribute('data-tipe') || '';
            var hari = row.getAttribute('data-hari') || '';
            var jam = row.getAttribute('data-jam') || '';

            if (label.indexOf(query) > -1 || tipe.indexOf(query) > -1 || hari.indexOf(query) > -1 || jam.indexOf(query) > -1) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        var cards = document.querySelectorAll('.mobile-card-list .mobile-card-item');
        cards.forEach(function(card) {
            var text = card.innerText.toLowerCase();
            card.style.display = text.indexOf(query) > -1 ? '' : 'none';
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalTambahSlot();
            tutupModalEditSlot();
        }
    });
</script>
@endsection
