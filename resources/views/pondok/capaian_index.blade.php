@extends('layouts.app')

@section('title', 'Capaian Materi')

@push('styles')
<style>
    .cm-prog-wrap {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 155px;
    }

    .cm-prog-top {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        font-variant-numeric: tabular-nums;
    }

    .cm-prog-pct {
        font-size: 13px;
        font-weight: 800;
        line-height: 1.1;
    }

    .cm-prog-pct.is-quran {
        color: #059669;
    }

    .cm-prog-pct.is-hadist {
        color: #2563eb;
    }

    .cm-prog-count {
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
    }

    .cm-prog-track {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background: var(--border-light, #e2e8f0);
        overflow: hidden;
    }

    .cm-prog-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.25s ease;
    }

    .cm-prog-fill.is-quran {
        background: #059669;
    }

    .cm-prog-fill.is-hadist {
        background: #2563eb;
    }

    .cm-kelas-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: var(--bg-page, #f1f5f9);
        color: var(--text-secondary, #475569);
        border: 1px solid var(--border-light, #e2e8f0);
    }

    .cm-action-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 11px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid var(--border-light, #e2e8f0);
        background: var(--card-bg, #ffffff);
        color: var(--text-primary, #0f172a);
        transition: all 0.15s ease;
    }

    .cm-action-link:hover {
        border-color: #94a3b8;
        background: var(--bg-page, #f8fafc);
    }

    .cm-action-link.is-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    .cm-action-link.is-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .cm-action-link svg {
        width: 13.5px;
        height: 13.5px;
        flex-shrink: 0;
    }

    .dark-mode .cm-prog-pct.is-quran {
        color: #34d399;
    }

    .dark-mode .cm-prog-pct.is-hadist {
        color: #60a5fa;
    }

    .dark-mode .cm-prog-track {
        background: #1e293b;
    }
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:16px; width:100%;">

    {{-- Filter Card --}}
    <div class="filter-card" style="display:none;">
        <form action="{{ route('pondok.capaian.index') }}" method="GET">
            @if($kelasList->isNotEmpty())
            <select name="kelas" class="form-select" aria-label="Kelas" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kls)
                    <option value="{{ $kls }}" {{ request('kelas') === $kls ? 'selected' : '' }}>{{ $kls }}</option>
                @endforeach
            </select>
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama siswa...">
            <a href="{{ route('pondok.capaian.index') }}" class="filter-btn-reset" title="Reset">Reset</a>
        </form>
    </div>

    {{-- Tabel Capaian Materi Siswa --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px; text-align:center;">No</th>
                        <th>Nama Siswa</th>
                        <th style="width:140px;">Kelas</th>
                        <th style="width:210px;">Al-Qur'an</th>
                        <th style="width:210px;">Al-Hadist</th>
                        <th style="width:170px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswas as $idx => $siswa)
                    @php
                        $sum = $summaryMap[$siswa->id] ?? [
                            'quran_selesai'       => 0,
                            'quran_total'         => 604,
                            'quran_persen'        => 0,
                            'quran_persen_label'  => '0%',
                            'hadist_selesai'      => 0,
                            'hadist_total'        => 0,
                            'hadist_persen'       => 0,
                            'hadist_persen_label' => '0%',
                        ];
                    @endphp
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">
                            {{ ($siswas->firstItem() ?? 1) + $idx }}
                        </td>
                        <td style="font-weight:700; color:var(--text-primary);">
                            {{ $siswa->nama_lengkap }}
                        </td>
                        <td>
                            <span class="cm-kelas-badge">{{ $siswa->kelas_tampil }}</span>
                        </td>
                        <td>
                            <div class="cm-prog-wrap">
                                <div class="cm-prog-top">
                                    <span class="cm-prog-pct is-quran">{{ $sum['quran_persen_label'] }}</span>
                                    <span class="cm-prog-count">{{ $sum['quran_selesai'] }} / {{ $sum['quran_total'] }}</span>
                                </div>
                                <div class="cm-prog-track">
                                    <div class="cm-prog-fill is-quran" style="width: {{ $sum['quran_persen'] }}%;"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="cm-prog-wrap">
                                <div class="cm-prog-top">
                                    <span class="cm-prog-pct is-hadist">{{ $sum['hadist_persen_label'] }}</span>
                                    <span class="cm-prog-count">{{ $sum['hadist_selesai'] }} / {{ $sum['hadist_total'] }}</span>
                                </div>
                                <div class="cm-prog-track">
                                    <div class="cm-prog-fill is-hadist" style="width: {{ $sum['hadist_persen'] }}%;"></div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                                <a href="{{ route('pondok.capaian.show', ['siswa' => $siswa->id, 'mode' => 'lihat']) }}" class="cm-action-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <span>Lihat</span>
                                </a>
                                @if($canEdit)
                                <a href="{{ route('pondok.capaian.show', ['siswa' => $siswa->id, 'mode' => 'edit']) }}" class="cm-action-link is-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:42px 16px; color:var(--text-secondary); font-weight:600;">
                            Belum ada data siswa
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswas->hasPages())
        <div style="padding:14px 18px; border-top:1px solid var(--border-light);">
            {{ $siswas->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
