@extends('layouts.app')

@section('title', 'Guru')

@section('header-actions')
@if(auth()->check() && (auth()->user()->isYayasan() || auth()->user()->canDo('guru_sekolah', 'view')))
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('guru-sekolah.index') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="8.5" cy="7" r="4"></circle>
            <line x1="20" y1="8" x2="20" y2="14"></line>
            <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
        <span>Kelola Guru Lembaga</span>
    </a>
</div>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    @if (session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- Kartu Statistik --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:var(--text-secondary);">Total Guru Terdaftar</div>
            <div style="font-size:26px; font-weight:800; color:var(--text-primary); margin-top:4px;">{{ number_format($totalGuru) }}</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#2563eb;">Guru SMP</div>
            <div style="font-size:26px; font-weight:800; color:#2563eb; margin-top:4px;">{{ number_format($totalSmp) }}</div>
        </div>
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:#047857;">Guru SMA</div>
            <div style="font-size:26px; font-weight:800; color:#047857; margin-top:4px;">{{ number_format($totalSma) }}</div>
        </div>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('data-warga.guru') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="lembaga_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="semua" {{ ($selectedLembagaId === 'semua' || empty($selectedLembagaId)) ? 'selected' : '' }}>Semua Lembaga</option>
                        @foreach($sekolahLembagas as $lemb)
                            <option value="{{ $lemb->id }}" {{ (string) $selectedLembagaId === (string) $lemb->id ? 'selected' : '' }}>
                                {{ $lemb->singkatan ?: $lemb->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(request('search') || (request('lembaga_id') && request('lembaga_id') !== 'semua'))
                <a href="{{ route('data-warga.guru', ['lembaga_id' => 'semua']) }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
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
                       class="form-control filter-search-input" placeholder="Cari nama guru, NIP, atau mata pelajaran...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Daftar Guru --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px;">No</th>
                        <th>Nama Guru</th>
                        <th>NIP</th>
                        <th>Lembaga</th>
                        <th>Mata Pelajaran</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gurus as $idx => $guru)
                    @php
                        $lembagaLabels = collect();
                        if ($guru->primaryLembaga) {
                            $lembagaLabels->push($guru->primaryLembaga->singkatan ?: $guru->primaryLembaga->nama);
                        }
                        foreach ($guru->lembagas as $l) {
                            $lbl = $l->singkatan ?: $l->nama;
                            if (in_array(strtoupper($lbl), ['SMP', 'SMA']) && !$lembagaLabels->contains($lbl)) {
                                $lembagaLabels->push($lbl);
                            }
                        }
                        $mapelList = array_values(array_filter(array_map('trim', explode(',', (string) $guru->mata_pelajaran))));
                    @endphp
                    <tr>
                        <td>{{ $gurus->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $guru->name }}</div>
                            @if($guru->email)
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">{{ $guru->email }}</div>
                            @endif
                        </td>
                        <td style="font-family:monospace; font-size:12.5px;">{{ $guru->nip ?: '-' }}</td>
                        <td>
                            @if($lembagaLabels->isNotEmpty())
                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    @foreach($lembagaLabels as $lbl)
                                        @php
                                            $badgeClass = strtolower($lbl) === 'smp' ? 'smp' : (strtolower($lbl) === 'sma' ? 'sma' : 'pondok');
                                        @endphp
                                        <span class="badge-lembaga badge-lembaga-{{ $badgeClass }}">{{ $lbl }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted); font-size:12px;">-</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($mapelList))
                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    @foreach($mapelList as $m)
                                        <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:12px; font-weight:600;">
                                            {{ $m }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span style="color:var(--text-muted); font-size:12px;">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill status-sudah_kembali" style="font-size:11.5px;">Aktif</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:36px 16px; text-align:center;">
                            <div style="font-size:14px; font-weight:700; color:var(--text-primary);">Belum ada data guru terdaftar</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($gurus->hasPages())
        <div style="padding:12px 16px; border-top:1px solid var(--border-subtle);">
            {{ $gurus->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
