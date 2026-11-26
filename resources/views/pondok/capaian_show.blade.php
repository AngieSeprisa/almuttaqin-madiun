@extends('layouts.app')

@section('title', 'Capaian Materi')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    @if($canEdit)
        @if($isEditMode)
        <a href="{{ route('pondok.capaian.show', ['siswa' => $siswa->id, 'mode' => 'lihat', 'tab' => $activeTab]) }}" class="btn btn-secondary">
            Lihat
        </a>
        @else
        <a href="{{ route('pondok.capaian.show', ['siswa' => $siswa->id, 'mode' => 'edit', 'tab' => $activeTab]) }}" class="btn btn-primary">
            Edit
        </a>
        @endif
    @endif
    <a href="{{ route('pondok.capaian.index') }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        <span>Kembali</span>
    </a>
</div>
@endsection

@push('styles')
<style>
    .cm-student-bar {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .cm-student-identity {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .cm-student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
        font-size: 14px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .cm-student-name {
        font-size: 16px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
        line-height: 1.25;
    }

    .cm-student-kelas {
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

    .cm-mode-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .cm-mode-badge.is-edit {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }

    .cm-mode-badge.is-view {
        background: var(--bg-page, #f1f5f9);
        color: var(--text-secondary, #475569);
        border: 1px solid var(--border-light, #e2e8f0);
    }

    .cm-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    @media (max-width: 768px) {
        .cm-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    .cm-summary-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .cm-summary-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
    }

    .cm-summary-title {
        font-size: 13.5px;
        font-weight: 700;
        color: var(--text-secondary, #475569);
    }

    .cm-summary-pct {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .cm-summary-pct.is-quran {
        color: #059669;
    }

    .cm-summary-pct.is-hadist {
        color: #2563eb;
    }

    .cm-summary-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: var(--border-light, #e2e8f0);
        overflow: hidden;
    }

    .cm-summary-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.25s ease;
    }

    .cm-summary-fill.is-quran {
        background: #059669;
    }

    .cm-summary-fill.is-hadist {
        background: #2563eb;
    }

    .cm-summary-meta {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        font-variant-numeric: tabular-nums;
    }

    .cm-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .cm-tabs {
        display: inline-flex;
        padding: 4px;
        border-radius: 12px;
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        gap: 4px;
    }

    .cm-tab-btn {
        border: none;
        background: transparent;
        color: var(--text-secondary, #475569);
        font-size: 13.5px;
        font-weight: 700;
        padding: 8px 18px;
        border-radius: 9px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .cm-tab-btn:hover {
        color: var(--text-primary, #0f172a);
    }

    .cm-tab-btn.is-active-quran {
        background: #059669;
        color: #ffffff;
    }

    .cm-tab-btn.is-active-hadist {
        background: #2563eb;
        color: #ffffff;
    }

    .cm-filter-select {
        min-width: 190px;
        max-width: 260px;
    }

    .cm-cards-stack {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .cm-group-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }

    .cm-group-header {
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        border-bottom: 1px solid var(--border-light, #e2e8f0);
    }

    .cm-group-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cm-group-title {
        font-size: 14.5px;
        font-weight: 800;
        color: var(--text-primary, #0f172a);
    }

    .cm-group-range {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        padding: 2px 9px;
        border-radius: 999px;
        background: var(--bg-page, #f1f5f9);
        font-variant-numeric: tabular-nums;
    }

    .cm-group-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cm-group-count {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--text-secondary, #475569);
        font-variant-numeric: tabular-nums;
    }

    .cm-group-pct {
        font-size: 12px;
        font-weight: 800;
        padding: 3px 10px;
        border-radius: 999px;
        font-variant-numeric: tabular-nums;
    }

    .cm-group-pct.is-quran {
        background: #ecfdf5;
        color: #047857;
    }

    .cm-group-pct.is-hadist {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .cm-bulk-btn {
        border: 1px solid var(--border-light, #e2e8f0);
        background: var(--card-bg, #ffffff);
        color: var(--text-secondary, #475569);
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .cm-bulk-btn:hover {
        border-color: #94a3b8;
        color: var(--text-primary, #0f172a);
        background: var(--bg-page, #f8fafc);
    }

    .cm-group-bar {
        width: 100%;
        height: 4px;
        background: var(--bg-page, #f1f5f9);
    }

    .cm-group-bar-fill {
        height: 100%;
        transition: width 0.25s ease;
    }

    .cm-group-bar-fill.is-quran {
        background: #059669;
    }

    .cm-group-bar-fill.is-hadist {
        background: #2563eb;
    }

    .cm-matrix {
        padding: 16px 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .cm-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        user-select: none;
        transition: background-color 0.14s ease, border-color 0.14s ease, color 0.14s ease, transform 0.1s ease, box-shadow 0.14s ease;
    }

    .cm-circle.is-clickable {
        cursor: pointer;
    }

    .cm-circle.is-clickable:active {
        transform: scale(0.92);
    }

    .cm-circle.is-readonly {
        cursor: default;
    }

    .cm-circle.is-quran.is-clickable:hover:not(.is-done) {
        border-color: #059669;
        background: #ecfdf5;
        color: #047857;
    }

    .cm-circle.is-hadist.is-clickable:hover:not(.is-done) {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .cm-circle.is-quran.is-done {
        background: #059669;
        border-color: #059669;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(5, 150, 105, 0.28);
    }

    .cm-circle.is-hadist.is-done {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.28);
    }

    .cm-empty-box {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 40px 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        color: var(--text-secondary, #64748b);
        font-weight: 600;
    }

    @media (max-width: 640px) {
        .cm-matrix {
            padding: 14px;
            gap: 7px;
        }

        .cm-circle {
            width: 34px;
            height: 34px;
            font-size: 11.5px;
        }
    }

    .dark-mode .cm-circle {
        background: #0f172a;
        border-color: #334155;
        color: #cbd5e1;
    }

    .dark-mode .cm-circle.is-quran.is-clickable:hover:not(.is-done) {
        border-color: #34d399;
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
    }

    .dark-mode .cm-circle.is-hadist.is-clickable:hover:not(.is-done) {
        border-color: #60a5fa;
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
    }

    .dark-mode .cm-circle.is-quran.is-done {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
    }

    .dark-mode .cm-circle.is-hadist.is-done {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #ffffff;
    }

    .dark-mode .cm-summary-pct.is-quran {
        color: #34d399;
    }

    .dark-mode .cm-summary-pct.is-hadist {
        color: #60a5fa;
    }

    .dark-mode .cm-summary-track,
    .dark-mode .cm-group-bar {
        background: #1e293b;
    }

    .dark-mode .cm-group-pct.is-quran {
        background: rgba(16, 185, 129, 0.16);
        color: #34d399;
    }

    .dark-mode .cm-group-pct.is-hadist {
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
    }

    .dark-mode .cm-mode-badge.is-edit {
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
        border-color: rgba(59, 130, 246, 0.35);
    }
</style>
@endpush

@section('content')
@php
    $words = preg_split('/\s+/', trim((string) $siswa->nama_lengkap));
    $initials = count($words) >= 2
        ? strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
        : strtoupper(mb_substr(trim((string) $siswa->nama_lengkap), 0, 2));
@endphp

<div style="display:flex; flex-direction:column; gap:16px; width:100%;" id="capaianApp"
     data-toggle-url="{{ route('pondok.capaian.toggle', $siswa->id) }}"
     data-csrf="{{ csrf_token() }}"
     data-editable="{{ $isEditMode ? '1' : '0' }}">

    {{-- Identitas Siswa --}}
    <div class="cm-student-bar">
        <div class="cm-student-identity">
            <div class="cm-student-avatar">{{ $initials ?: 'S' }}</div>
            <span class="cm-student-name">{{ $siswa->nama_lengkap }}</span>
            <span class="cm-student-kelas">{{ $siswa->kelas_tampil }}</span>
        </div>
        <span class="cm-mode-badge {{ $isEditMode ? 'is-edit' : 'is-view' }}">
            {{ $isEditMode ? 'Mode Edit' : 'Mode Lihat' }}
        </span>
    </div>

    {{-- Ringkasan Persentase Terpisah: Al-Qur'an & Al-Hadist --}}
    <div class="cm-summary-grid">
        <div class="cm-summary-card">
            <div class="cm-summary-head">
                <span class="cm-summary-title">Al-Qur'an</span>
                <span class="cm-summary-pct is-quran" id="quranPercentText">{{ $quranSummary['persen_label'] }}</span>
            </div>
            <div class="cm-summary-track">
                <div class="cm-summary-fill is-quran" id="quranProgressBar" style="width: {{ $quranSummary['persen'] }}%;"></div>
            </div>
            <div class="cm-summary-meta" id="quranCountText">{{ $quranSummary['selesai'] }} / {{ $quranSummary['total'] }} halaman</div>
        </div>

        <div class="cm-summary-card">
            <div class="cm-summary-head">
                <span class="cm-summary-title">Al-Hadist</span>
                <span class="cm-summary-pct is-hadist" id="hadistPercentText">{{ $hadistSummary['persen_label'] }}</span>
            </div>
            <div class="cm-summary-track">
                <div class="cm-summary-fill is-hadist" id="hadistProgressBar" style="width: {{ $hadistSummary['persen'] }}%;"></div>
            </div>
            <div class="cm-summary-meta" id="hadistCountText">{{ $hadistSummary['selesai'] }} / {{ $hadistSummary['total'] }} halaman</div>
        </div>
    </div>

    {{-- Tab Pemisah & Filter --}}
    <div class="cm-toolbar">
        <div class="cm-tabs" role="tablist">
            <button type="button"
                    class="cm-tab-btn {{ $activeTab === 'quran' ? 'is-active-quran' : '' }}"
                    id="tabBtnQuran"
                    data-tab="quran"
                    role="tab"
                    aria-selected="{{ $activeTab === 'quran' ? 'true' : 'false' }}">
                Al-Qur'an
            </button>
            <button type="button"
                    class="cm-tab-btn {{ $activeTab === 'hadist' ? 'is-active-hadist' : '' }}"
                    id="tabBtnHadist"
                    data-tab="hadist"
                    role="tab"
                    aria-selected="{{ $activeTab === 'hadist' ? 'true' : 'false' }}">
                Al-Hadist
            </button>
        </div>

        <select id="filterJuz" class="form-select cm-filter-select" aria-label="Pilih Juz" style="{{ $activeTab === 'quran' ? '' : 'display:none;' }}">
            <option value="">Semua Juz</option>
            @foreach($juzItems as $jItem)
                <option value="{{ $jItem['juz'] }}">{{ $jItem['label'] }}</option>
            @endforeach
        </select>

        <select id="filterHadist" class="form-select cm-filter-select" aria-label="Pilih Kitab" style="{{ $activeTab === 'hadist' ? '' : 'display:none;' }}">
            <option value="">Semua Kitab</option>
            @foreach($hadistItems as $hItem)
                <option value="{{ $hItem['id'] }}">{{ $hItem['nama'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- PANEL 1: AL-QUR'AN PER JUZ --}}
    <div id="panelQuran" class="cm-cards-stack" style="{{ $activeTab === 'quran' ? '' : 'display:none;' }}">
        @foreach($juzItems as $jItem)
        @php
            $doneMap = array_flip($jItem['halaman_selesai']);
        @endphp
        <div class="cm-group-card js-juz-card"
             data-group-key="quran_juz_{{ $jItem['juz'] }}"
             data-kategori="al_quran"
             data-juz="{{ $jItem['juz'] }}"
             data-total="{{ $jItem['jumlah_halaman'] }}">
            <div class="cm-group-header">
                <div class="cm-group-left">
                    <span class="cm-group-title">{{ $jItem['label'] }}</span>
                    <span class="cm-group-range">Hal. {{ $jItem['halaman_awal'] }} - {{ $jItem['halaman_akhir'] }}</span>
                </div>
                <div class="cm-group-right">
                    <span class="cm-group-count js-group-count">{{ $jItem['jumlah_selesai'] }} / {{ $jItem['jumlah_halaman'] }}</span>
                    <span class="cm-group-pct is-quran js-group-pct">{{ $jItem['persen_label'] }}</span>
                    @if($isEditMode)
                    <button type="button" class="cm-bulk-btn js-bulk-action" data-action="select_all">Tandai Semua</button>
                    <button type="button" class="cm-bulk-btn js-bulk-action" data-action="clear_all">Reset</button>
                    @endif
                </div>
            </div>
            <div class="cm-group-bar">
                <div class="cm-group-bar-fill is-quran js-group-bar" style="width: {{ $jItem['persen'] }}%;"></div>
            </div>
            <div class="cm-matrix">
                @for($page = $jItem['halaman_awal']; $page <= $jItem['halaman_akhir']; $page++)
                    @php $isDone = isset($doneMap[$page]); @endphp
                    <button type="button"
                            class="cm-circle is-quran {{ $isDone ? 'is-done' : '' }} {{ $isEditMode ? 'is-clickable' : 'is-readonly' }}"
                            data-page="{{ $page }}"
                            aria-pressed="{{ $isDone ? 'true' : 'false' }}"
                            @if(!$isEditMode) tabindex="-1" @endif>
                        {{ $page }}
                    </button>
                @endfor
            </div>
        </div>
        @endforeach
    </div>

    {{-- PANEL 2: AL-HADIST SESUAI DAFTAR MATERI --}}
    <div id="panelHadist" class="cm-cards-stack" style="{{ $activeTab === 'hadist' ? '' : 'display:none;' }}">
        @forelse($hadistItems as $hItem)
        @php
            $doneMap = array_flip($hItem['halaman_selesai']);
        @endphp
        <div class="cm-group-card js-hadist-card"
             data-group-key="hadist_{{ $hItem['id'] }}"
             data-kategori="al_hadist"
             data-materi-id="{{ $hItem['id'] }}"
             data-total="{{ $hItem['jumlah_halaman'] }}">
            <div class="cm-group-header">
                <div class="cm-group-left">
                    <span class="cm-group-title">{{ $hItem['nama'] }}</span>
                    <span class="cm-group-range">Hal. 1 - {{ $hItem['jumlah_halaman'] }}</span>
                </div>
                <div class="cm-group-right">
                    <span class="cm-group-count js-group-count">{{ $hItem['jumlah_selesai'] }} / {{ $hItem['jumlah_halaman'] }}</span>
                    <span class="cm-group-pct is-hadist js-group-pct">{{ $hItem['persen_label'] }}</span>
                    @if($isEditMode)
                    <button type="button" class="cm-bulk-btn js-bulk-action" data-action="select_all">Tandai Semua</button>
                    <button type="button" class="cm-bulk-btn js-bulk-action" data-action="clear_all">Reset</button>
                    @endif
                </div>
            </div>
            <div class="cm-group-bar">
                <div class="cm-group-bar-fill is-hadist js-group-bar" style="width: {{ $hItem['persen'] }}%;"></div>
            </div>
            <div class="cm-matrix">
                @for($page = 1; $page <= $hItem['jumlah_halaman']; $page++)
                    @php $isDone = isset($doneMap[$page]); @endphp
                    <button type="button"
                            class="cm-circle is-hadist {{ $isDone ? 'is-done' : '' }} {{ $isEditMode ? 'is-clickable' : 'is-readonly' }}"
                            data-page="{{ $page }}"
                            aria-pressed="{{ $isDone ? 'true' : 'false' }}"
                            @if(!$isEditMode) tabindex="-1" @endif>
                        {{ $page }}
                    </button>
                @endfor
            </div>
        </div>
        @empty
        <div class="cm-empty-box">
            <span>Belum ada materi Al-Hadist</span>
            @if(!auth()->check() || auth()->user()->canDo('materi_pondok', 'view'))
            <a href="{{ route('pondok.materi.index') }}" class="btn btn-primary btn-sm">Daftar Materi</a>
            @endif
        </div>
        @endforelse
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var appEl = document.getElementById('capaianApp');
    if (!appEl) return;

    var toggleUrl = appEl.getAttribute('data-toggle-url');
    var csrfToken = appEl.getAttribute('data-csrf');
    var isEditable = appEl.getAttribute('data-editable') === '1';

    var tabBtnQuran = document.getElementById('tabBtnQuran');
    var tabBtnHadist = document.getElementById('tabBtnHadist');
    var panelQuran = document.getElementById('panelQuran');
    var panelHadist = document.getElementById('panelHadist');
    var filterJuz = document.getElementById('filterJuz');
    var filterHadist = document.getElementById('filterHadist');

    function switchTab(tab) {
        var isQuran = tab !== 'hadist';
        panelQuran.style.display = isQuran ? '' : 'none';
        panelHadist.style.display = isQuran ? 'none' : '';
        filterJuz.style.display = isQuran ? '' : 'none';
        filterHadist.style.display = isQuran ? 'none' : '';

        tabBtnQuran.classList.toggle('is-active-quran', isQuran);
        tabBtnQuran.setAttribute('aria-selected', isQuran ? 'true' : 'false');
        tabBtnHadist.classList.toggle('is-active-hadist', !isQuran);
        tabBtnHadist.setAttribute('aria-selected', !isQuran ? 'true' : 'false');
    }

    if (tabBtnQuran) tabBtnQuran.addEventListener('click', function () { switchTab('quran'); });
    if (tabBtnHadist) tabBtnHadist.addEventListener('click', function () { switchTab('hadist'); });

    if (filterJuz) {
        filterJuz.addEventListener('change', function () {
            var val = this.value;
            document.querySelectorAll('.js-juz-card').forEach(function (card) {
                card.style.display = (!val || card.getAttribute('data-juz') === val) ? '' : 'none';
            });
        });
    }

    if (filterHadist) {
        filterHadist.addEventListener('change', function () {
            var val = this.value;
            document.querySelectorAll('.js-hadist-card').forEach(function (card) {
                card.style.display = (!val || card.getAttribute('data-materi-id') === val) ? '' : 'none';
            });
        });
    }

    function formatPct(done, total) {
        if (!total || done <= 0) return { val: 0, label: '0%' };
        var pct = Math.min(100, Math.round((done / total) * 1000) / 10);
        var label = Math.abs(pct - Math.round(pct)) < 0.05
            ? String(Math.round(pct)) + '%'
            : pct.toFixed(1).replace('.', ',') + '%';
        return { val: pct, label: label };
    }

    function updateGroupUI(card, doneCount, totalCount, pctVal, pctLabel) {
        var countEl = card.querySelector('.js-group-count');
        var pctEl = card.querySelector('.js-group-pct');
        var barEl = card.querySelector('.js-group-bar');
        if (countEl) countEl.textContent = doneCount + ' / ' + totalCount;
        if (pctEl) pctEl.textContent = pctLabel;
        if (barEl) barEl.style.width = pctVal + '%';
    }

    function updateSummaryUI(quran, hadist) {
        if (quran) {
            var qPct = document.getElementById('quranPercentText');
            var qBar = document.getElementById('quranProgressBar');
            var qCnt = document.getElementById('quranCountText');
            if (qPct) qPct.textContent = quran.persen_label;
            if (qBar) qBar.style.width = quran.persen + '%';
            if (qCnt) qCnt.textContent = quran.selesai + ' / ' + quran.total + ' halaman';
        }
        if (hadist) {
            var hPct = document.getElementById('hadistPercentText');
            var hBar = document.getElementById('hadistProgressBar');
            var hCnt = document.getElementById('hadistCountText');
            if (hPct) hPct.textContent = hadist.persen_label;
            if (hBar) hBar.style.width = hadist.persen + '%';
            if (hCnt) hCnt.textContent = hadist.selesai + ' / ' + hadist.total + ' halaman';
        }
    }

    function recalcLocal(card) {
        var total = parseInt(card.getAttribute('data-total') || '0', 10);
        var done = card.querySelectorAll('.cm-circle.is-done').length;
        var p = formatPct(done, total);
        updateGroupUI(card, done, total, p.val, p.label);

        var isQuran = card.getAttribute('data-kategori') === 'al_quran';
        var cards = document.querySelectorAll(isQuran ? '.js-juz-card' : '.js-hadist-card');
        var sumDone = 0;
        var sumTotal = 0;
        cards.forEach(function (c) {
            sumDone += c.querySelectorAll('.cm-circle.is-done').length;
            sumTotal += parseInt(c.getAttribute('data-total') || '0', 10);
        });
        var sp = formatPct(sumDone, sumTotal);
        if (isQuran) {
            updateSummaryUI({ selesai: sumDone, total: sumTotal, persen: sp.val, persen_label: sp.label }, null);
        } else {
            updateSummaryUI(null, { selesai: sumDone, total: sumTotal, persen: sp.val, persen_label: sp.label });
        }
    }

    var groupQueues = {};
    function enqueueRequest(groupKey, task) {
        var prev = groupQueues[groupKey] || Promise.resolve();
        var next = prev.then(task).catch(function () {});
        groupQueues[groupKey] = next;
        return next;
    }

    function sendToggle(card, payload) {
        var groupKey = card.getAttribute('data-group-key');
        return enqueueRequest(groupKey, function () {
            return fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.ok) return;
                if (data.item && Array.isArray(data.item.halaman_selesai)) {
                    var doneSet = new Set(data.item.halaman_selesai.map(Number));
                    card.querySelectorAll('.cm-circle').forEach(function (btn) {
                        var pg = parseInt(btn.getAttribute('data-page'), 10);
                        var active = doneSet.has(pg);
                        btn.classList.toggle('is-done', active);
                        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });
                    updateGroupUI(card, data.item.selesai, data.item.total, data.item.persen, data.item.persen_label);
                }
                updateSummaryUI(data.quran, data.hadist);
            })
            .catch(function (err) {
                console.error('Gagal menyimpan capaian materi:', err);
            });
        });
    }

    if (isEditable) {
        appEl.addEventListener('click', function (e) {
            var circle = e.target.closest('.cm-circle');
            if (circle) {
                var card = circle.closest('.cm-group-card');
                if (!card) return;
                var willBeDone = !circle.classList.contains('is-done');
                circle.classList.toggle('is-done', willBeDone);
                circle.setAttribute('aria-pressed', willBeDone ? 'true' : 'false');
                recalcLocal(card);

                var kategori = card.getAttribute('data-kategori');
                var payload = {
                    kategori: kategori,
                    action: 'toggle_page',
                    halaman: parseInt(circle.getAttribute('data-page'), 10)
                };
                if (kategori === 'al_quran') {
                    payload.juz = parseInt(card.getAttribute('data-juz'), 10);
                } else {
                    payload.pondok_materi_id = parseInt(card.getAttribute('data-materi-id'), 10);
                }
                sendToggle(card, payload);
                return;
            }

            var bulkBtn = e.target.closest('.js-bulk-action');
            if (bulkBtn) {
                var groupCard = bulkBtn.closest('.cm-group-card');
                if (!groupCard) return;
                var action = bulkBtn.getAttribute('data-action');
                var markAll = action === 'select_all';
                groupCard.querySelectorAll('.cm-circle').forEach(function (btn) {
                    btn.classList.toggle('is-done', markAll);
                    btn.setAttribute('aria-pressed', markAll ? 'true' : 'false');
                });
                recalcLocal(groupCard);

                var kat = groupCard.getAttribute('data-kategori');
                var body = { kategori: kat, action: action };
                if (kat === 'al_quran') {
                    body.juz = parseInt(groupCard.getAttribute('data-juz'), 10);
                } else {
                    body.pondok_materi_id = parseInt(groupCard.getAttribute('data-materi-id'), 10);
                }
                sendToggle(groupCard, body);
            }
        });
    }
});
</script>
@endpush
@endsection
