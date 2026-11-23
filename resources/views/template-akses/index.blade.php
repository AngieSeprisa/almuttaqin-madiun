@extends('layouts.app')

@section('title', 'Template Hak Akses')

@section('header-actions')
<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('pengguna.index') }}" class="btn btn-secondary">
        Daftar Pengguna
    </a>
    @if(!auth()->check() || auth()->user()->canDo('pengguna', 'create'))
    <a href="{{ route('template-akses.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Buat Template
    </a>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:100%; min-width:0;">

    {{-- Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('template-akses.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                @if(request('search'))
                    <a href="{{ route('template-akses.index') }}" class="btn btn-sm btn-secondary filter-btn-reset">
                        <span>Reset</span>
                    </a>
                @endif
            </div>
            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input" placeholder="Cari nama template...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Template --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="min-width:220px;">Nama Template</th>
                        <th style="min-width:160px;">Peran Default</th>
                        <th style="min-width:200px;">Ruang Kerja</th>
                        <th style="min-width:110px; white-space:nowrap;">Hak Akses</th>
                        <th style="text-align:center; min-width:100px; white-space:nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $tpl)
                    @php
                        $lemIds = is_array($tpl->lembaga_ids) ? $tpl->lembaga_ids : [];
                    @endphp
                    <tr>
                        <td style="font-weight:700; color:var(--text-primary);">
                            {{ $tpl->nama }}
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $tpl->roleLabel() }}
                        </td>
                        <td>
                            @if(empty($lemIds))
                                <span style="font-size:12.5px; color:var(--text-muted);">-</span>
                            @else
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    @foreach($lemIds as $lid)
                                        @if(isset($lembagaMap[$lid]))
                                            <span class="jenjang-pill jenjang-pill-reguler">{{ $lembagaMap[$lid]->nama_singkat }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-pending">{{ $tpl->menuCount() }} Menu</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                @if(!auth()->check() || auth()->user()->canDo('pengguna', 'edit'))
                                <a href="{{ route('template-akses.edit', $tpl->id) }}" class="action-btn action-btn-edit" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(!auth()->check() || auth()->user()->canDo('pengguna', 'delete'))
                                <form action="{{ route('template-akses.destroy', $tpl->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus template {{ addslashes($tpl->nama) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn" style="color:#DC2626;" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                            <path d="M10 11v6"></path>
                                            <path d="M14 11v6"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="cell-empty">
                            Belum ada template hak akses.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $templates->links() }}
        </div>
        @endif
    </div>

    {{-- Mobile List --}}
    <div class="mobile-card-list">
        @forelse($templates as $tpl)
        <div class="mobile-card-item" style="flex-direction:column; align-items:stretch; gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                <div>
                    <div class="mobile-card-title">{{ $tpl->nama }}</div>
                    <div style="font-size:12px; color:var(--text-muted);">{{ $tpl->roleLabel() }} · {{ $tpl->menuCount() }} Menu</div>
                </div>
                <div style="display:flex; gap:6px;">
                    @if(!auth()->check() || auth()->user()->canDo('pengguna', 'edit'))
                    <a href="{{ route('template-akses.edit', $tpl->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="mobile-card-empty">Belum ada template hak akses.</div>
        @endforelse
    </div>

</div>
@endsection
