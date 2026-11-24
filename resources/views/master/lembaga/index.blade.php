@extends('layouts.app')

@section('title', 'Master Lembaga')

@section('header-actions')
@if($isYayasan || (auth()->check() && auth()->user()->canDo('master_lembaga', 'create')))
<div class="mstr-header-actions">
    <button type="button" class="btn btn-primary mstr-btn-action" onclick="bukaModalTambahLembaga()" title="Tambah Lembaga Baru">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M12 5v14M5 12h14"></path>
        </svg>
        <span class="btn-text">Tambah Lembaga</span>
    </button>
</div>
@endif
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

    /* Lembaga Grid Cards */
    .lembaga-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        width: 100%;
    }

    .lembaga-card {
        background: var(--bg-surface);
        border: 1px solid var(--border-light);
        border-radius: 16px;
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
    }

    .lembaga-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
    }

    .lembaga-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .lembaga-logo-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: var(--bg-page);
        border: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .lembaga-logo-img {
        width: 38px;
        height: 38px;
        object-fit: contain;
    }

    .lembaga-badge-kategori {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 4px 8px;
        border-radius: 6px;
    }

    .kategori-formal { background: rgba(59, 130, 246, 0.1); color: #2563EB; border: 1px solid rgba(59, 130, 246, 0.2); }
    .kategori-pesantren { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
    .kategori-yayasan { background: rgba(139, 92, 246, 0.1); color: #7C3AED; border: 1px solid rgba(139, 92, 246, 0.2); }

    .lembaga-card-body {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .lembaga-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.3;
    }

    .lembaga-code-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-family: monospace;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        background: var(--table-th-bg);
        border: 1px solid var(--border-light);
        padding: 2px 8px;
        border-radius: 6px;
        align-self: flex-start;
    }

    /* Meta Stats List */
    .lembaga-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 12px 14px;
        background: var(--table-th-bg);
        border-radius: 12px;
        border: 1px solid var(--border-light);
        margin-top: 4px;
    }

    .lembaga-meta-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 2px;
    }

    .lembaga-meta-val {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
    }

    .lembaga-meta-lbl {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    /* Card Footer Actions */
    .lembaga-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 14px;
        border-top: 1px solid var(--border-light);
        margin-top: auto;
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

    .mstr-badge-aktif { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .mstr-badge-nonaktif { background: rgba(107, 114, 128, 0.1); color: #4B5563; }

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
        flex-shrink: 0;
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
        flex: 1 1 auto;
        min-height: 0;
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

    .mstr-modal-footer {
        padding: 16px 22px;
        border-top: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        background: var(--table-th-bg);
        flex-shrink: 0;
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
        .lembaga-cards-grid {
            grid-template-columns: 1fr;
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

    @if($errors->any())
    <div style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); color:#DC2626; padding:12px 18px; border-radius:12px; display:flex; align-items:flex-start; gap:10px; font-size:13.5px; font-weight:500;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;margin-top:2px;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <div style="font-weight:700; margin-bottom:4px;">Gagal menyimpan data lembaga:</div>
            <ul style="margin:0; padding-left:18px; display:flex; flex-direction:column; gap:2px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Stats Grid --}}
    <div class="mstr-stats-grid">
        <div class="mstr-stat-card">
            <div class="mstr-stat-icon purple">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['total'] }}</span>
                <span class="mstr-stat-label">Total Unit Lembaga</span>
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
                <span class="mstr-stat-label">Lembaga Aktif</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['formal'] }}</span>
                <span class="mstr-stat-label">Lembaga Formal</span>
            </div>
        </div>

        <div class="mstr-stat-card">
            <div class="mstr-stat-icon amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
                </svg>
            </div>
            <div class="mstr-stat-info">
                <span class="mstr-stat-value">{{ $stats['pesantren'] }}</span>
                <span class="mstr-stat-label">Pesantren / Non-Formal</span>
            </div>
        </div>
    </div>

    {{-- Cards Grid Lembaga --}}
    <div class="lembaga-cards-grid">
        @foreach($lembagas as $item)
        @php
            $isCore = in_array(strtolower($item->kode), ['yayasan', 'pondok', 'smp', 'sma']);
        @endphp
        <div class="lembaga-card">
            <div class="lembaga-card-header">
                <div class="lembaga-logo-wrap">
                    <img src="{{ $item->logo_url }}" alt="{{ $item->nama }}" class="lembaga-logo-img">
                </div>
                <span class="lembaga-badge-kategori kategori-{{ $item->kategori }}">
                    {{ $item->kategori }}
                </span>
            </div>

            <div class="lembaga-card-body">
                <h4 class="lembaga-title">{{ $item->nama }}</h4>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="lembaga-code-pill">kode: {{ $item->kode }}</span>
                    @if($item->singkatan)
                    <span style="font-size:12px; color:var(--text-secondary); font-weight:600;">({{ $item->singkatan }})</span>
                    @endif
                    @if($item->prefix_registrasi)
                    <span style="font-size:11px; font-weight:700; font-family:monospace; padding:2px 7px; border-radius:6px; background:rgba(0, 82, 255, 0.08); color:#0052FF; border:1px solid rgba(0, 82, 255, 0.2);" title="Prefix Nomor Registrasi SPMB">No. Reg: {{ $item->prefix_registrasi }}</span>
                    @endif
                </div>
                @if($item->alamat)
                <div style="font-size:12px; color:var(--text-secondary); line-height:1.4; margin-top:4px;">
                    {{ $item->alamat }}
                </div>
                @endif
                @if($item->telepon || $item->email)
                <div style="font-size:11.5px; color:var(--text-muted); display:flex; flex-wrap:wrap; gap:8px;">
                    @if($item->telepon)<span>{{ $item->telepon }}</span>@endif
                    @if($item->email)<span>{{ $item->email }}</span>@endif
                </div>
                @endif

                <div class="lembaga-meta-grid">
                    <div class="lembaga-meta-item">
                        <span class="lembaga-meta-val">{{ $item->users_count }}</span>
                        <span class="lembaga-meta-lbl">User</span>
                    </div>
                    <div class="lembaga-meta-item">
                        <span class="lembaga-meta-val">{{ $item->classrooms_count }}</span>
                        <span class="lembaga-meta-lbl">Kelas</span>
                    </div>
                    <div class="lembaga-meta-item">
                        <span class="lembaga-meta-val">{{ $item->mapel_count }}</span>
                        <span class="lembaga-meta-lbl">Mapel</span>
                    </div>
                </div>
            </div>

            <div class="lembaga-card-footer">
                <div>
                    @if(($isYayasan || (auth()->check() && auth()->user()->canDo('master_lembaga', 'edit'))) && strtolower($item->kode) !== 'yayasan')
                    <form action="{{ route('master.lembaga.toggle-status', $item->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="mstr-badge {{ $item->is_active ? 'mstr-badge-aktif' : 'mstr-badge-nonaktif' }}" style="border:none; cursor:pointer;" title="Klik untuk ubah status aktif/non-aktif">
                            <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                            {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                        </button>
                    </form>
                    @else
                    <span class="mstr-badge {{ $item->is_active ? 'mstr-badge-aktif' : 'mstr-badge-nonaktif' }}">
                        <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                        {{ $item->is_active ? 'Aktif' : 'Non-Aktif' }}
                    </span>
                    @endif
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    @if($isYayasan || (auth()->check() && auth()->user()->canDo('master_lembaga', 'edit')))
                    @php
                        $editPayload = [
                            'id'                => $item->id,
                            'kode'              => $item->kode,
                            'nama'              => $item->nama,
                            'singkatan'         => $item->singkatan,
                            'prefix_registrasi' => $item->prefix_registrasi ?? '',
                            'kategori'          => $item->kategori,
                            'alamat'          => $item->alamat ?? '',
                            'telepon'         => $item->telepon ?? '',
                            'email'           => $item->email ?? '',
                            'logo_url'        => $item->logo_url,
                            'has_custom_logo' => !empty($item->logo),
                            'urutan'          => $item->urutan,
                            'is_core'         => $isCore,
                            'is_active'       => (bool) $item->is_active,
                        ];
                    @endphp
                    @php
                        $editPayloadJson = json_encode($editPayload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                    @endphp
                    <button type="button" class="mstr-btn-icon" title="Edit Lembaga"
                            data-lembaga="{{ $editPayloadJson }}"
                            onclick="bukaModalEditLembaga(JSON.parse(this.getAttribute('data-lembaga')))">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    @endif

                    @if(!$isCore && ($isYayasan || (auth()->check() && auth()->user()->canDo('master_lembaga', 'delete'))))
                    <form action="{{ route('master.lembaga.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lembaga {{ $item->nama }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="mstr-btn-icon danger" title="Hapus Lembaga">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- MODAL TAMBAH LEMBAGA --}}
<div class="mstr-modal-overlay {{ ($errors->any() && old('_form_mode') === 'tambah') ? 'show' : '' }}" id="modalTambahLembaga" onclick="if(event.target === this) tutupModalTambahLembaga()">
    <div class="mstr-modal-box" style="max-width:540px; max-height:90vh; display:flex; flex-direction:column;">
        <form action="{{ route('master.lembaga.store') }}" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; max-height:90vh; min-height:0; overflow:hidden;">
            @csrf
            <input type="hidden" name="_form_mode" value="tambah">
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Tambah Lembaga</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalTambahLembaga()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                @if($errors->any() && old('_form_mode') === 'tambah')
                <div style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); color:#DC2626; padding:10px 14px; border-radius:10px; font-size:12.5px;">
                    <ul style="margin:0; padding-left:16px;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Kode Unik *</label>
                        <input type="text" name="kode" id="tambahKode" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('kode') : '' }}" placeholder="smk / sdit / ra" required maxlength="60" autocomplete="off">
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Singkatan *</label>
                        <input type="text" name="singkatan" id="tambahSingkatan" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('singkatan') : '' }}" placeholder="SMK" required maxlength="60">
                    </div>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Nama Lembaga *</label>
                    <input type="text" name="nama" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('nama') : '' }}" placeholder="SMK AL MUTTAQIN" required maxlength="120">
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Prefix No. Registrasi SPMB <span style="font-weight:400; color:var(--text-muted);">(Opsional)</span></label>
                    <input type="text" name="prefix_registrasi" id="tambahPrefixRegistrasi" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('prefix_registrasi') : '' }}" placeholder="Contoh: RAKT / RAGM / SDIT / SMP" maxlength="20" style="text-transform:uppercase; font-family:monospace;">
                    <span style="font-size:11.5px; color:var(--text-muted);">Hasil contoh: <strong id="tambahPrefixPreview" style="font-family:monospace;">{{ old('_form_mode') === 'tambah' && old('prefix_registrasi') ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', old('prefix_registrasi'))) : 'OTOMATIS' }}-01-202627</strong></span>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Alamat Lengkap</label>
                    <textarea name="alamat" rows="2" class="mstr-form-control" placeholder="Jln. Nogososro No. 26 Josenan Taman Kota Madiun...">{{ old('_form_mode') === 'tambah' ? old('alamat') : '' }}</textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">No. Telepon</label>
                        <input type="text" name="telepon" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('telepon') : '' }}" placeholder="+62857..." maxlength="40">
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Email</label>
                        <input type="text" inputmode="email" name="email" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('email') : '' }}" placeholder="info@almuttaqin.id" maxlength="120">
                    </div>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Logo Lembaga</label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/gif" class="mstr-form-control">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Kategori *</label>
                        @php $oldKat = old('_form_mode') === 'tambah' ? old('kategori', 'formal') : 'formal'; @endphp
                        <select name="kategori" class="mstr-form-control" required>
                            <option value="formal" {{ $oldKat === 'formal' ? 'selected' : '' }}>Formal</option>
                            <option value="pesantren" {{ $oldKat === 'pesantren' ? 'selected' : '' }}>Pesantren</option>
                            <option value="yayasan" {{ $oldKat === 'yayasan' ? 'selected' : '' }}>Yayasan</option>
                        </select>
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Urutan</label>
                        <input type="number" name="urutan" class="mstr-form-control" value="{{ old('_form_mode') === 'tambah' ? old('urutan') : '' }}" placeholder="Otomatis" min="1">
                    </div>
                    <div class="mstr-form-group" style="justify-content:center;">
                        <label class="mstr-form-label">&nbsp;</label>
                        @php $oldAktif = old('_form_mode') === 'tambah' ? (bool) old('is_active') : true; @endphp
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600;">
                            <input type="checkbox" name="is_active" value="1" {{ $oldAktif ? 'checked' : '' }} style="width:16px;height:16px;">
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mstr-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalTambahLembaga()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT LEMBAGA --}}
<div class="mstr-modal-overlay" id="modalEditLembaga" onclick="if(event.target === this) tutupModalEditLembaga()">
    <div class="mstr-modal-box" style="max-width:540px; max-height:90vh; display:flex; flex-direction:column;">
        <form id="formEditLembaga" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; max-height:90vh; min-height:0; overflow:hidden;">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form_mode" value="edit">
            <div class="mstr-modal-header">
                <h3 class="mstr-modal-title">Edit Lembaga</h3>
                <button type="button" class="mstr-modal-close" onclick="tutupModalEditLembaga()">&times;</button>
            </div>
            <div class="mstr-modal-body">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Kode Unik</label>
                        <input type="text" name="kode" id="editKode" class="mstr-form-control" readonly style="background:var(--table-th-bg);">
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Singkatan *</label>
                        <input type="text" name="singkatan" id="editSingkatan" class="mstr-form-control" required>
                    </div>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Nama Lembaga *</label>
                    <input type="text" name="nama" id="editNama" class="mstr-form-control" required maxlength="120">
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Prefix No. Registrasi SPMB <span style="font-weight:400; color:var(--text-muted);">(Opsional)</span></label>
                    <input type="text" name="prefix_registrasi" id="editPrefixRegistrasi" class="mstr-form-control" placeholder="Contoh: RAKT / RAGM / SDIT / SMP" maxlength="20" style="text-transform:uppercase; font-family:monospace;">
                    <span style="font-size:11.5px; color:var(--text-muted);">Hasil contoh: <strong id="editPrefixPreview" style="font-family:monospace;">OTOMATIS-01-202627</strong></span>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Alamat Lengkap</label>
                    <textarea name="alamat" id="editAlamat" rows="2" class="mstr-form-control" placeholder="Alamat lengkap lembaga untuk KOP surat"></textarea>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">No. Telepon</label>
                        <input type="text" name="telepon" id="editTelepon" class="mstr-form-control" placeholder="+62857...">
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Email</label>
                        <input type="text" inputmode="email" name="email" id="editEmail" class="mstr-form-control" placeholder="info@almuttaqin.id" maxlength="120">
                    </div>
                </div>

                <div class="mstr-form-group">
                    <label class="mstr-form-label">Logo Lembaga</label>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="lembaga-logo-wrap" style="width:48px; height:48px;">
                            <img id="editLogoPreview" src="" alt="Logo" class="lembaga-logo-img" style="width:36px; height:36px;">
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; gap:6px;">
                            <input type="file" name="logo" id="editLogoInput" accept="image/png,image/jpeg,image/webp,image/gif" class="mstr-form-control">
                            <label id="editHapusLogoWrap" style="display:none; align-items:center; gap:6px; font-size:12px; color:#DC2626; cursor:pointer;">
                                <input type="checkbox" name="hapus_logo" id="editHapusLogo" value="1">
                                <span>Hapus logo unggahan</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Kategori *</label>
                        <select name="kategori" id="editKategori" class="mstr-form-control" required>
                            <option value="formal">Formal</option>
                            <option value="pesantren">Pesantren</option>
                            <option value="yayasan">Yayasan</option>
                        </select>
                    </div>
                    <div class="mstr-form-group">
                        <label class="mstr-form-label">Urutan</label>
                        <input type="number" name="urutan" id="editUrutan" class="mstr-form-control" min="1">
                    </div>
                    <div class="mstr-form-group" style="justify-content:center;">
                        <label class="mstr-form-label">&nbsp;</label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:600;">
                            <input type="checkbox" name="is_active" id="editIsActive" value="1" style="width:16px;height:16px;">
                            <span>Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mstr-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="tutupModalEditLembaga()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModalTambahLembaga() {
        document.getElementById('modalTambahLembaga').classList.add('show');
    }

    function tutupModalTambahLembaga() {
        document.getElementById('modalTambahLembaga').classList.remove('show');
    }

    function bukaModalEditLembaga(data) {
        var form = document.getElementById('formEditLembaga');
        form.action = "{{ url('master/lembaga') }}/" + data.id;

        var kodeInput = document.getElementById('editKode');
        kodeInput.value = data.kode || '';
        if (data.is_core) {
            kodeInput.setAttribute('readonly', 'readonly');
        } else {
            kodeInput.removeAttribute('readonly');
        }

        document.getElementById('editNama').value = data.nama || '';
        document.getElementById('editSingkatan').value = data.singkatan || '';
        var editPrefixEl = document.getElementById('editPrefixRegistrasi');
        if (editPrefixEl) {
            editPrefixEl.value = data.prefix_registrasi || '';
            updatePrefixPreview(editPrefixEl, 'editPrefixPreview', data.singkatan || data.kode || '');
        }
        document.getElementById('editKategori').value = data.kategori || 'formal';
        document.getElementById('editAlamat').value = data.alamat || '';
        document.getElementById('editTelepon').value = data.telepon || '';
        document.getElementById('editEmail').value = data.email || '';
        document.getElementById('editUrutan').value = data.urutan || '';
        document.getElementById('editIsActive').checked = Boolean(data.is_active);
        document.getElementById('editLogoPreview').src = data.logo_url || '';
        document.getElementById('editLogoInput').value = '';
        document.getElementById('editHapusLogo').checked = false;
        document.getElementById('editHapusLogoWrap').style.display = data.has_custom_logo ? 'inline-flex' : 'none';

        document.getElementById('modalEditLembaga').classList.add('show');
    }

    function tutupModalEditLembaga() {
        document.getElementById('modalEditLembaga').classList.remove('show');
    }

    function sanitizeKodeLembaga(val) {
        return String(val || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9_-]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^[-_]+|[-_]+$/g, '');
    }

    var tambahKodeInput = document.getElementById('tambahKode');
    var tambahSingkatanInput = document.getElementById('tambahSingkatan');
    if (tambahKodeInput) {
        tambahKodeInput.addEventListener('blur', function () {
            if (this.value.trim() !== '') {
                this.value = sanitizeKodeLembaga(this.value);
            }
        });
    }
    if (tambahSingkatanInput && tambahKodeInput) {
        tambahSingkatanInput.addEventListener('blur', function () {
            if (tambahKodeInput.value.trim() === '' && this.value.trim() !== '') {
                tambahKodeInput.value = sanitizeKodeLembaga(this.value);
            }
        });
    }

    function sanitizePrefixRegistrasi(val) {
        return String(val || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20);
    }

    function updatePrefixPreview(inputEl, previewId, fallbackSource) {
        var previewEl = document.getElementById(previewId);
        if (!previewEl) return;
        var clean = sanitizePrefixRegistrasi(inputEl ? inputEl.value : '');
        if (!clean && fallbackSource) {
            clean = sanitizePrefixRegistrasi(fallbackSource).slice(0, 12);
        }
        previewEl.textContent = (clean || 'OTOMATIS') + '-01-202627';
    }

    var tambahPrefixInput = document.getElementById('tambahPrefixRegistrasi');
    if (tambahPrefixInput) {
        tambahPrefixInput.addEventListener('input', function () {
            var clean = sanitizePrefixRegistrasi(this.value);
            if (this.value !== clean) this.value = clean;
            updatePrefixPreview(this, 'tambahPrefixPreview', document.getElementById('tambahSingkatan')?.value || '');
        });
    }

    var editPrefixInput = document.getElementById('editPrefixRegistrasi');
    if (editPrefixInput) {
        editPrefixInput.addEventListener('input', function () {
            var clean = sanitizePrefixRegistrasi(this.value);
            if (this.value !== clean) this.value = clean;
            updatePrefixPreview(this, 'editPrefixPreview', document.getElementById('editSingkatan')?.value || '');
        });
    }

    document.getElementById('editLogoInput')?.addEventListener('change', function (e) {
        var file = e.target.files && e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function (ev) {
                document.getElementById('editLogoPreview').src = ev.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupModalTambahLembaga();
            tutupModalEditLembaga();
        }
    });
</script>
@endsection
