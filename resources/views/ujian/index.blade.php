@extends('layouts.app')

@section('title', 'Sistem Ujian & Koreksi LJK')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    @if(!auth()->check() || auth()->user()->canDo('ujian', 'create'))
    <a href="{{ route('ujian.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; border-radius:10px; font-weight:600;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>+ Buat Ujian Baru</span>
    </a>
    @endif
</div>
@endsection

@section('content')
<style>
    .ujian-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .ujian-stat-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .ujian-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ujian-stat-icon.blue { background: #EFF6FF; color: #2563EB; }
    .ujian-stat-icon.green { background: #ECFDF5; color: #059669; }
    .ujian-stat-icon.purple { background: #F5F3FF; color: #7C3AED; }
    .ujian-stat-icon.amber { background: #FFFBEB; color: #D97706; }
    .ujian-stat-val { font-size: 24px; font-weight: 700; color: var(--text-primary, #0F172A); line-height: 1.2; }
    .ujian-stat-lbl { font-size: 12px; color: var(--text-muted, #64748B); font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

    .fi-table-wrap {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .fi-filter-bar {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-light, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .fi-filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        flex: 1;
    }
    .fi-select, .fi-input {
        padding: 7px 12px;
        font-size: 13px;
        border: 1px solid var(--border-light, #CBD5E1);
        border-radius: 8px;
        background: var(--bg-surface, #ffffff);
        color: var(--text-primary, #0F172A);
    }
    .fi-select:focus, .fi-input:focus {
        outline: none;
        border-color: #2563EB;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
    .action-btn-group {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .act-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-light, #E2E8F0);
        background: var(--bg-surface, #ffffff);
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }
    .act-btn:hover { background: #F1F5F9; color: #0F172A; }
    .act-btn.is-primary:hover { background: #EFF6FF; border-color: #BFDBFE; color: #2563EB; }
    .act-btn.is-success:hover { background: #ECFDF5; border-color: #A7F3D0; color: #059669; }
    .act-btn.is-danger:hover { background: #FEF2F2; border-color: #FECACA; color: #DC2626; }
    .act-btn.is-purple:hover { background: #F5F3FF; border-color: #DDD6FE; color: #7C3AED; }

    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 6px;
    }
    .badge-success { background: #DCFCE7; color: #15803D; }
    .badge-warning { background: #FEF3C7; color: #B45309; }
    .badge-secondary { background: #F1F5F9; color: #475569; }
</style>

<div class="ujian-container">
    {{-- STATS CARDS --}}
    <div class="ujian-stats-grid">
        <div class="ujian-stat-card">
            <div class="ujian-stat-icon blue">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <div>
                <div class="ujian-stat-val">{{ $totalUjian }}</div>
                <div class="ujian-stat-lbl">Total Ujian</div>
            </div>
        </div>

        <div class="ujian-stat-card">
            <div class="ujian-stat-icon green">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <div class="ujian-stat-val">{{ $ujianAktif }}</div>
                <div class="ujian-stat-lbl">Ujian Aktif</div>
            </div>
        </div>

        <div class="ujian-stat-card">
            <div class="ujian-stat-icon purple">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <div>
                <div class="ujian-stat-val">{{ $totalKoreksi }}</div>
                <div class="ujian-stat-lbl">LJK Terkoreksi</div>
            </div>
        </div>

        <div class="ujian-stat-card">
            <div class="ujian-stat-icon amber">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div>
                <div class="ujian-stat-val">{{ $ujianSelesai }}</div>
                <div class="ujian-stat-lbl">Ujian Selesai</div>
            </div>
        </div>
    </div>

    {{-- TABLE WRAPPER --}}
    <div class="fi-table-wrap">
        {{-- FILTER BAR --}}
        <form method="GET" action="{{ route('ujian.index') }}" class="fi-filter-bar">
            <div class="fi-filter-group">
                <input type="text" name="search" class="fi-input" style="min-width:200px;" placeholder="Cari nama atau kode ujian..." value="{{ request('search') }}">

                <select name="kelas_id" class="fi-select" onchange="this.form.submit()">
                    <option value="">-- Semua Kelas --</option>
                    @foreach($kelases as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>

                <select name="mapel_id" class="fi-select" onchange="this.form.submit()">
                    <option value="">-- Semua Mapel --</option>
                    @foreach($mapels as $m)
                    <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->mata_pelajaran }}</option>
                    @endforeach
                </select>

                <select name="status" class="fi-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                </select>

                <button type="submit" class="btn btn-secondary" style="padding:6px 14px; font-size:12px;">Filter</button>
                @if(request()->anyFilled(['search', 'kelas_id', 'mapel_id', 'status']))
                <a href="{{ route('ujian.index') }}" class="btn btn-secondary" style="padding:6px 12px; font-size:12px; color:#64748B;">Reset</a>
                @endif
            </div>
        </form>

        {{-- TABLE CONTENT --}}
        <div style="overflow-x:auto;">
            <table class="table" style="width:100%; border-collapse:collapse; margin:0;">
                <thead>
                    <tr style="background:var(--bg-page, #F8FAFC); border-bottom:1px solid var(--border-light, #E2E8F0); text-align:left;">
                        <th style="padding:12px 18px; font-size:12px; font-weight:600; color:#64748B;">UJIAN</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B;">MAPEL & KELAS</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B;">GURU PENGAMPU</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B; text-align:center;">SOAL / OPSI</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B;">TANGGAL</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B; text-align:center;">STATUS</th>
                        <th style="padding:12px 16px; font-size:12px; font-weight:600; color:#64748B; text-align:center;">KOREKSI</th>
                        <th style="padding:12px 18px; font-size:12px; font-weight:600; color:#64748B; text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ujians as $ujian)
                    <tr style="border-bottom:1px solid var(--border-light, #F1F5F9);">
                        <td style="padding:14px 18px;">
                            <div style="font-weight:600; color:var(--text-primary, #0F172A); font-size:13.5px;">{{ $ujian->nama_ujian }}</div>
                            <div style="font-size:11.5px; color:#64748B; font-family:monospace;">{{ $ujian->kode_ujian }}</div>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-weight:500; font-size:13px; color:var(--text-primary, #0F172A);">{{ $ujian->mapel?->mata_pelajaran ?? '-' }}</div>
                            <div style="font-size:11.5px; color:#64748B;">Kelas: {{ $ujian->kelas?->nama_kelas ?? '-' }}</div>
                        </td>
                        <td style="padding:14px 16px; font-size:13px; color:#334155;">
                            {{ $ujian->guru?->name ?? '-' }}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            <span style="font-weight:600; font-size:12.5px; color:#0F172A;">{{ $ujian->jumlah_soal }}</span>
                            <span style="font-size:11.5px; color:#64748B;">({{ $ujian->jumlah_opsi == 5 ? 'A-E' : 'A-D' }})</span>
                        </td>
                        <td style="padding:14px 16px; font-size:12.5px; color:#475569;">
                            {{ $ujian->tanggal_ujian?->format('d M Y') }}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            {!! $ujian->status_badge !!}
                        </td>
                        <td style="padding:14px 16px; text-align:center;">
                            @if($ujian->hasils_count > 0)
                            <a href="{{ route('ujian.hasil', $ujian->id) }}" style="font-weight:600; font-size:12.5px; color:#2563EB; text-decoration:none;">
                                {{ $ujian->hasils_count }} Siswa
                            </a>
                            @else
                            <span style="color:#94A3B8; font-size:12px;">0 Siswa</span>
                            @endif
                        </td>
                        <td style="padding:14px 18px; text-align:right;">
                            <div class="action-btn-group">
                                {{-- KUNCI JAWABAN --}}
                                <a href="{{ route('ujian.kunci', $ujian->id) }}" class="act-btn is-primary" title="Atur Kunci Jawaban">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                                    </svg>
                                </a>

                                {{-- CETAK LJK --}}
                                <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="act-btn is-purple" title="Cetak Lembar Jawab Komputer (LJK)">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                </a>

                                {{-- UPLOAD SCAN & KOREKSI --}}
                                <a href="{{ route('ujian.upload-scan', $ujian->id) }}" class="act-btn is-success" title="Unggah Scan & Koreksi OMR">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </a>

                                {{-- REKAP HASIL NILAI --}}
                                <a href="{{ route('ujian.hasil', $ujian->id) }}" class="act-btn" title="Lihat Rekapitulasi Nilai">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="20" x2="18" y2="10"></line>
                                        <line x1="12" y1="20" x2="12" y2="4"></line>
                                        <line x1="6" y1="20" x2="6" y2="14"></line>
                                    </svg>
                                </a>

                                {{-- EDIT --}}
                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'edit'))
                                <a href="{{ route('ujian.edit', $ujian->id) }}" class="act-btn" title="Ubah Data Ujian">
                                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif

                                {{-- HAPUS --}}
                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'delete'))
                                <form action="{{ route('ujian.destroy', $ujian->id) }}" method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ujian ini beserta seluruh hasil koreksinya?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="act-btn is-danger" title="Hapus Ujian">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
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
                        <td colspan="8" style="padding:48px 16px; text-align:center; color:#94A3B8;">
                            <div style="font-size:14px; font-weight:500; margin-bottom:8px;">Belum ada data ujian yang tercatat.</div>
                            <div style="font-size:12.5px;">Klik tombol <strong>+ Buat Ujian Baru</strong> di pojok kanan atas untuk memulai.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($ujians->hasPages())
        <div style="padding:14px 20px; border-top:1px solid var(--border-light, #E2E8F0);">
            {{ $ujians->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

