@extends('layouts.app')

@section('title', $isEdit ? 'Edit Penerimaan' : 'Buat Penerimaan')

@section('header-actions')
<a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Kembali
</a>
@endsection

@section('content')
@php
    $actionUrl = $isEdit ? route('penerimaan.update', $penerimaan->id) : route('penerimaan.store');
    $basePublicUrl = url('/spmb') . '/';
    $dokumenConfig = old('syarat_dokumen', $penerimaan->dokumenConfig());

    $selectedJenjangs = old('jenjangs', $penerimaan->allowedJenjangs());
    if (!is_array($selectedJenjangs)) {
        $selectedJenjangs = $penerimaan->allowedJenjangs();
    }

    $waValues = old('kontak_wa_per_jenjang', $penerimaan->waMap());
    if (!is_array($waValues)) {
        $waValues = [];
    }

    $lokasiValues = old('lokasi_seleksi_per_jenjang', $penerimaan->lokasiMap());
    if (!is_array($lokasiValues)) {
        $lokasiValues = [];
    }

    $initialGelombangs = old('gelombangs');
    if ($initialGelombangs === null) {
        $initialGelombangs = $gelombangs->map(function ($g) use ($selectedJenjangs) {
            $kuotaMap = is_array($g->kuota_per_jenjang) ? $g->kuota_per_jenjang : [];
            if (empty($kuotaMap) && $g->kuota !== null && count($selectedJenjangs) === 1) {
                $kuotaMap[$selectedJenjangs[0]] = $g->kuota;
            }
            return [
                'id'                => $g->id,
                'nama_gelombang'    => $g->nama_gelombang,
                'tanggal_mulai'     => $g->tanggal_mulai?->format('Y-m-d'),
                'tanggal_selesai'   => $g->tanggal_selesai?->format('Y-m-d'),
                'kuota_per_jenjang' => $kuotaMap,
            ];
        })->values()->all();
    }
@endphp

<style>
    /* Filament-style Multi-Select Dropdown */
    .fi-ms-wrap {
        position: relative;
        width: 100%;
    }
    .fi-ms-trigger {
        min-height: 44px;
        width: 100%;
        padding: 6px 38px 6px 10px;
        border: 1px solid var(--border-color, #D1D5DB);
        border-radius: 10px;
        background: var(--input-bg, #FFFFFF);
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        cursor: pointer;
        position: relative;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }
    .fi-ms-trigger:hover {
        border-color: #9CA3AF;
    }
    .fi-ms-wrap.is-open .fi-ms-trigger {
        border-color: #0052FF;
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
    }
    .fi-ms-placeholder {
        font-size: 13.5px;
        color: var(--text-muted, #9CA3AF);
        padding: 3px 4px;
    }
    .fi-ms-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px 4px 10px;
        border-radius: 7px;
        background: rgba(0, 82, 255, 0.1);
        border: 1px solid rgba(0, 82, 255, 0.22);
        color: #0052FF;
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.2;
    }
    html.dark-mode .fi-ms-chip {
        background: rgba(59, 130, 246, 0.18);
        border-color: rgba(59, 130, 246, 0.35);
        color: #60A5FA;
    }
    .fi-ms-chip-rm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border: none;
        border-radius: 4px;
        background: transparent;
        color: inherit;
        cursor: pointer;
        padding: 0;
        opacity: 0.75;
    }
    .fi-ms-chip-rm:hover {
        opacity: 1;
        background: rgba(0, 82, 255, 0.14);
    }
    .fi-ms-chevron {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted, #6B7280);
        pointer-events: none;
        transition: transform 0.15s ease;
    }
    .fi-ms-wrap.is-open .fi-ms-chevron {
        transform: translateY(-50%) rotate(180deg);
    }
    .fi-ms-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 60;
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-light, #E5E7EB);
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        display: none;
    }
    .fi-ms-wrap.is-open .fi-ms-menu {
        display: block;
    }
    .fi-ms-search-wrap {
        padding: 8px;
        border-bottom: 1px solid var(--border-light, #E5E7EB);
    }
    .fi-ms-search {
        width: 100%;
        padding: 7px 10px;
        font-size: 13px;
        border: 1px solid var(--border-light, #E5E7EB);
        border-radius: 8px;
        background: var(--bg-surface, #F9FAFB);
        color: var(--text-primary);
        outline: none;
        box-sizing: border-box;
    }
    .fi-ms-search:focus {
        border-color: #0052FF;
    }
    .fi-ms-list {
        max-height: 220px;
        overflow-y: auto;
        padding: 6px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .fi-ms-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13.5px;
        color: var(--text-primary);
        transition: background 0.12s ease;
        user-select: none;
    }
    .fi-ms-option:hover {
        background: var(--bg-surface, #F3F4F6);
    }
    .fi-ms-option.is-selected {
        background: rgba(0, 82, 255, 0.08);
        color: #0052FF;
        font-weight: 600;
    }
    html.dark-mode .fi-ms-option.is-selected {
        background: rgba(59, 130, 246, 0.16);
        color: #60A5FA;
    }
    .fi-ms-option-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .fi-ms-option input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #0052FF;
        cursor: pointer;
        flex-shrink: 0;
    }
    .fi-ms-option-code {
        font-size: 11.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 6px;
        background: var(--bg-surface, #F1F5F9);
        color: var(--text-secondary);
        flex-shrink: 0;
    }
    .per-lembaga-box {
        border: 1px solid var(--border-light, #E5E7EB);
        border-radius: 12px;
        padding: 14px 16px;
        background: var(--bg-surface, #F8FAFC);
        display: grid;
        grid-template-columns: 120px 1fr 1.4fr;
        gap: 14px;
        align-items: end;
    }
    @media (max-width: 768px) {
        .per-lembaga-box {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }
</style>

<div class="form-container" style="width: 100%; max-width: 100%;">

    @if ($errors->any())
    <div class="alert-banner alert-error" style="margin-bottom: 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <ul style="margin: 0 0 0 18px; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ $actionUrl }}" method="POST" id="formPenerimaan">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="wizard-card">
            {{-- Stepper 3 Tahap --}}
            <div class="wizard-stepper" id="penerimaanStepper">
                <div class="wizard-step is-active" data-step="1" id="p_tab_1">
                    <div class="wizard-step-circle">01</div>
                    <div class="wizard-step-title">Informasi Pendaftaran</div>
                </div>

                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </div>

                <div class="wizard-step" data-step="2" id="p_tab_2">
                    <div class="wizard-step-circle">02</div>
                    <div class="wizard-step-title">Dokumen & Syarat</div>
                </div>

                <div class="wizard-divider" aria-hidden="true">
                    <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                </div>

                <div class="wizard-step" data-step="3" id="p_tab_3">
                    <div class="wizard-step-circle">03</div>
                    <div class="wizard-step-title">Gelombang Pendaftaran</div>
                </div>
            </div>

            <div class="wizard-body">

                {{-- TAHAP 01: INFORMASI PENDAFTARAN --}}
                <div class="wizard-step-pane is-active" id="p_pane_1">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                        <div class="wizard-form-group" style="grid-column: 1 / -1;">
                            <label class="wizard-label" for="judul">
                                Judul Pendaftaran <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" name="judul" id="judul"
                                   value="{{ old('judul', $penerimaan->judul) }}"
                                   class="wizard-input @error('judul') is-invalid @enderror"
                                   placeholder="SPMB Al Muttaqin 2027/2028" required>
                            @error('judul')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="wizard-form-group" style="grid-column: 1 / -1;">
                            <label class="wizard-label" for="slug">
                                Slug <span style="color:#EF4444;">*</span>
                            </label>
                            <div style="display:flex; align-items:stretch; border:1px solid var(--border-color, #D1D5DB); border-radius:10px; overflow:hidden; background:var(--input-bg, #FFF);">
                                <span style="display:inline-flex; align-items:center; padding:0 12px; font-size:13px; color:var(--text-muted); background:var(--bg-surface); border-right:1px solid var(--border-light); white-space:nowrap;">{{ $basePublicUrl }}</span>
                                <input type="text" name="slug" id="slug"
                                       value="{{ old('slug', $penerimaan->slug) }}"
                                       class="wizard-input @error('slug') is-invalid @enderror"
                                       style="border:none; border-radius:0; flex:1; min-width:140px;"
                                       placeholder="spmb-2027-2028" required>
                            </div>
                            @error('slug')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="tahun_ajaran">
                                Tahun Pendaftaran <span style="color:#EF4444;">*</span>
                            </label>
                            <input type="text" name="tahun_ajaran" id="tahun_ajaran"
                                   value="{{ old('tahun_ajaran', $penerimaan->tahun_ajaran) }}"
                                   class="wizard-input @error('tahun_ajaran') is-invalid @enderror"
                                   placeholder="2027/2028" required>
                            @error('tahun_ajaran')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="is_active">Status</label>
                            <select name="is_active" id="is_active" class="wizard-select">
                                <option value="1" {{ old('is_active', $penerimaan->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Dibuka</option>
                                <option value="0" {{ old('is_active', $penerimaan->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Ditutup</option>
                            </select>
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jadwal_seleksi">Jadwal Seleksi Offline</label>
                            <input type="text" name="jadwal_seleksi" id="jadwal_seleksi"
                                   value="{{ old('jadwal_seleksi', $penerimaan->jadwal_seleksi) }}"
                                   class="wizard-input"
                                   placeholder="Sabtu, 15 Mei 2027 · 08.00 WIB">
                        </div>

                        {{-- Dropdown Multi-Select ala Filament (diambil dari Master Lembaga Formal) --}}
                        <div class="wizard-form-group">
                            <label class="wizard-label">
                                Jenjang / Lembaga <span style="color:#EF4444;">*</span>
                            </label>
                            <div class="fi-ms-wrap" id="jenjangDropdown">
                                <div class="fi-ms-trigger" id="jenjangTrigger" tabindex="0" role="combobox" aria-expanded="false">
                                    <div id="jenjangChips" style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;"></div>
                                    <span class="fi-ms-placeholder" id="jenjangPlaceholder">Pilih lembaga...</span>
                                    <svg class="fi-ms-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </div>
                                <div class="fi-ms-menu" id="jenjangMenu">
                                    <div class="fi-ms-search-wrap">
                                        <input type="text" class="fi-ms-search" id="jenjangSearch" placeholder="Cari lembaga...">
                                    </div>
                                    <div class="fi-ms-list" id="jenjangOptionList">
                                        @foreach($jenjangItems as $val => $label)
                                        @php $checked = in_array($val, $selectedJenjangs, true); @endphp
                                        <label class="fi-ms-option {{ $checked ? 'is-selected' : '' }}" data-option-label="{{ strtolower($val . ' ' . $label) }}">
                                            <span class="fi-ms-option-left">
                                                <input type="checkbox" name="jenjangs[]" value="{{ $val }}" data-label="{{ $label }}" class="js-jenjang-checkbox" {{ $checked ? 'checked' : '' }}>
                                                <span>{{ $label }}</span>
                                            </span>
                                            <span class="fi-ms-option-code">{{ $val }}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('jenjangs')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No. WA Panitia & Lokasi Seleksi per Lembaga terpilih --}}
                        <div class="wizard-form-group" style="grid-column: 1 / -1; display:flex; flex-direction:column; gap:12px;" id="perLembagaConfigWrap">
                            @foreach($jenjangItems as $val => $label)
                            @php $isVisible = in_array($val, $selectedJenjangs, true); @endphp
                            <div class="per-lembaga-box js-per-lembaga-row" data-lembaga-row="{{ $val }}" style="{{ $isVisible ? '' : 'display:none;' }}">
                                <div style="display:flex; flex-direction:column; justify-content:center; height:42px;">
                                    <span class="jenjang-pill {{ $val === 'SMP' ? 'jenjang-pill-smp' : ($val === 'SMA' ? 'jenjang-pill-sma' : 'jenjang-pill-reguler') }}" style="align-self:flex-start;">{{ $val }}</span>
                                </div>
                                <div class="wizard-form-group" style="margin-bottom:0;">
                                    <label class="wizard-label" for="wa_{{ $val }}">No. WA Panitia {{ $val }}</label>
                                    <input type="text"
                                           name="kontak_wa_per_jenjang[{{ $val }}]"
                                           id="wa_{{ $val }}"
                                           value="{{ $waValues[$val] ?? '' }}"
                                           class="wizard-input"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="wizard-form-group" style="margin-bottom:0;">
                                    <label class="wizard-label" for="lokasi_{{ $val }}">Lokasi Seleksi {{ $val }}</label>
                                    <input type="text"
                                           name="lokasi_seleksi_per_jenjang[{{ $val }}]"
                                           id="lokasi_{{ $val }}"
                                           value="{{ $lokasiValues[$val] ?? '' }}"
                                           class="wizard-input"
                                           placeholder="Lokasi seleksi {{ $val }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="wizard-footer" style="justify-content: flex-end;">
                        <button type="button" class="btn-wizard-next js-p-next" data-next="2">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- TAHAP 02: DOKUMEN & SYARAT --}}
                <div class="wizard-step-pane" id="p_pane_2">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin-bottom:24px;">
                        @foreach($dokumenMeta as $flagKey => $meta)
                        @php $currentMode = $dokumenConfig[$flagKey] ?? 'opsional'; @endphp
                        <div class="wizard-subcard" style="margin-bottom:0;">
                            <label class="wizard-label" for="dok_{{ $flagKey }}" style="margin-bottom:8px; display:block;">
                                {{ $meta['label'] }}
                            </label>
                            <select name="syarat_dokumen[{{ $flagKey }}]" id="dok_{{ $flagKey }}" class="wizard-select">
                                <option value="wajib" {{ $currentMode === 'wajib' ? 'selected' : '' }}>Wajib</option>
                                <option value="opsional" {{ $currentMode === 'opsional' ? 'selected' : '' }}>Opsional</option>
                                <option value="tidak" {{ $currentMode === 'tidak' ? 'selected' : '' }}>Tidak Diminta</option>
                            </select>
                        </div>
                        @endforeach
                    </div>

                    <div class="wizard-form-group">
                        <label class="wizard-label" for="catatan_syarat">Syarat Pendaftaran (1 baris per poin)</label>
                        <textarea name="catatan_syarat" id="catatan_syarat" rows="4"
                                  class="wizard-textarea"
                                  placeholder="Lulusan SD/MI atau SMP/MTs&#10;Bersedia mengikuti seleksi offline">{{ old('catatan_syarat', $penerimaan->catatan_syarat) }}</textarea>
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-p-prev" data-prev="1">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Sebelumnya
                        </button>
                        <button type="button" class="btn-wizard-next js-p-next" data-next="3">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- TAHAP 03: GELOMBANG PENDAFTARAN (KUOTA PER LEMBAGA) --}}
                <div class="wizard-step-pane" id="p_pane_3">
                    <div style="display:flex; justify-content:flex-end; margin-bottom:14px;">
                        <button type="button" id="btnAddGelombang" class="btn btn-secondary btn-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Tambah Gelombang
                        </button>
                    </div>

                    <div id="gelombangList" style="display:flex; flex-direction:column; gap:14px;"></div>

                    <div id="gelombangEmpty" style="padding:24px; text-align:center; border:1px dashed var(--border-color, #CBD5E1); border-radius:12px; color:var(--text-muted); font-size:13.5px; display:none;">
                        Tanpa gelombang (selalu terbuka selama status Dibuka).
                    </div>

                    <div class="wizard-footer">
                        <button type="button" class="btn-wizard-prev js-p-prev" data-prev="2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Sebelumnya
                        </button>
                        <button type="submit" class="btn-wizard-next" style="padding:12px 28px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Penerimaan' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Stepper navigation
    var panes = [1, 2, 3].map(function (i) { return document.getElementById('p_pane_' + i); });
    var tabs = [1, 2, 3].map(function (i) { return document.getElementById('p_tab_' + i); });

    function goToStep(step) {
        if (step < 1 || step > 3) return;
        panes.forEach(function (p, idx) {
            if (p) p.classList.toggle('is-active', idx + 1 === step);
        });
        tabs.forEach(function (t, idx) {
            if (!t) return;
            t.classList.remove('is-active', 'is-completed');
            if (idx + 1 === step) t.classList.add('is-active');
            else if (idx + 1 < step) t.classList.add('is-completed');
        });
    }

    tabs.forEach(function (t, idx) {
        if (t) t.addEventListener('click', function () { goToStep(idx + 1); });
    });
    document.querySelectorAll('.js-p-next').forEach(function (b) {
        b.addEventListener('click', function () { goToStep(parseInt(this.getAttribute('data-next'), 10)); });
    });
    document.querySelectorAll('.js-p-prev').forEach(function (b) {
        b.addEventListener('click', function () { goToStep(parseInt(this.getAttribute('data-prev'), 10)); });
    });

    // 2. Filament-style Multi-Select Dropdown
    var ddWrap = document.getElementById('jenjangDropdown');
    var ddTrigger = document.getElementById('jenjangTrigger');
    var ddChips = document.getElementById('jenjangChips');
    var ddPlaceholder = document.getElementById('jenjangPlaceholder');
    var ddSearch = document.getElementById('jenjangSearch');
    var checkboxes = Array.from(document.querySelectorAll('.js-jenjang-checkbox'));

    function getSelectedJenjangs() {
        return checkboxes.filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
    }

    function escapeHtml(val) {
        return String(val === null || val === undefined ? '' : val)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderChipsAndSync() {
        var selected = getSelectedJenjangs();
        ddChips.innerHTML = '';

        checkboxes.forEach(function (cb) {
            var opt = cb.closest('.fi-ms-option');
            if (opt) opt.classList.toggle('is-selected', cb.checked);

            var row = document.querySelector('[data-lembaga-row="' + cb.value + '"]');
            if (row) row.style.display = cb.checked ? '' : 'none';

            if (cb.checked) {
                var chip = document.createElement('span');
                chip.className = 'fi-ms-chip';
                chip.innerHTML = '<span>' + escapeHtml(cb.value) + '</span>' +
                    '<button type="button" class="fi-ms-chip-rm" aria-label="Hapus ' + escapeHtml(cb.value) + '">&times;</button>';
                chip.querySelector('.fi-ms-chip-rm').addEventListener('click', function (e) {
                    e.stopPropagation();
                    cb.checked = false;
                    renderChipsAndSync();
                });
                ddChips.appendChild(chip);
            }
        });

        ddPlaceholder.style.display = selected.length > 0 ? 'none' : '';
        var configWrap = document.getElementById('perLembagaConfigWrap');
        if (configWrap) configWrap.style.display = selected.length > 0 ? 'flex' : 'none';

        syncAllGelombangQuotas();
    }

    if (ddTrigger && ddWrap) {
        ddTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var isOpen = ddWrap.classList.toggle('is-open');
            ddTrigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen && ddSearch) {
                ddSearch.value = '';
                filterOptions('');
                setTimeout(function () { ddSearch.focus(); }, 30);
            }
        });
    }

    function filterOptions(q) {
        var term = String(q || '').toLowerCase().trim();
        document.querySelectorAll('#jenjangOptionList .fi-ms-option').forEach(function (opt) {
            var lbl = opt.getAttribute('data-option-label') || '';
            opt.style.display = (!term || lbl.indexOf(term) !== -1) ? '' : 'none';
        });
    }

    if (ddSearch) {
        ddSearch.addEventListener('input', function () { filterOptions(this.value); });
        ddSearch.addEventListener('click', function (e) { e.stopPropagation(); });
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', renderChipsAndSync);
    });

    document.addEventListener('click', function (e) {
        if (ddWrap && !ddWrap.contains(e.target)) {
            ddWrap.classList.remove('is-open');
            if (ddTrigger) ddTrigger.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ddWrap) {
            ddWrap.classList.remove('is-open');
            if (ddTrigger) ddTrigger.setAttribute('aria-expanded', 'false');
        }
    });

    // 3. Auto-slug dari Judul
    var judulEl = document.getElementById('judul');
    var slugEl = document.getElementById('slug');
    var slugTouched = {{ $isEdit || old('slug') ? 'true' : 'false' }};

    function toSlug(val) {
        return String(val || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    if (slugEl) {
        slugEl.addEventListener('input', function () { slugTouched = true; });
        slugEl.addEventListener('blur', function () { this.value = toSlug(this.value); });
    }
    if (judulEl && slugEl) {
        judulEl.addEventListener('input', function () {
            if (!slugTouched) slugEl.value = toSlug(this.value);
        });
    }

    // 4. Dynamic Gelombang Rows + Kuota per Lembaga
    var listEl = document.getElementById('gelombangList');
    var emptyEl = document.getElementById('gelombangEmpty');
    var addBtn = document.getElementById('btnAddGelombang');
    var initialRows = @json($initialGelombangs);
    var counter = 0;

    function updateEmptyState() {
        emptyEl.style.display = listEl.children.length > 0 ? 'none' : 'block';
    }

    function renderQuotaInputsForRow(rowEl) {
        var idx = rowEl.getAttribute('data-row-idx');
        var quotaContainer = rowEl.querySelector('.js-gel-quotas');
        if (!quotaContainer) return;

        // Simpan nilai yang sudah ada di DOM ke cache rowEl._quotaCache
        rowEl._quotaCache = rowEl._quotaCache || {};
        quotaContainer.querySelectorAll('input[data-quota-jenjang]').forEach(function (inp) {
            rowEl._quotaCache[inp.getAttribute('data-quota-jenjang')] = inp.value;
        });

        var selected = getSelectedJenjangs();
        quotaContainer.innerHTML = '';

        selected.forEach(function (jKey) {
            var currentVal = rowEl._quotaCache[jKey] !== undefined && rowEl._quotaCache[jKey] !== null
                ? rowEl._quotaCache[jKey]
                : '';

            var field = document.createElement('div');
            field.className = 'wizard-form-group';
            field.style.marginBottom = '0';
            field.innerHTML =
                '<label class="wizard-label">Kuota ' + escapeHtml(jKey) + '</label>' +
                '<input type="number" min="1" data-quota-jenjang="' + escapeHtml(jKey) + '" ' +
                    'name="gelombangs[' + idx + '][kuota_per_jenjang][' + escapeHtml(jKey) + ']" ' +
                    'value="' + escapeHtml(currentVal) + '" class="wizard-input" placeholder="∞">';
            quotaContainer.appendChild(field);
        });
    }

    function syncAllGelombangQuotas() {
        if (!listEl) return;
        Array.from(listEl.children).forEach(renderQuotaInputsForRow);
    }

    function addGelombangRow(data) {
        var idx = counter++;
        var num = listEl.children.length + 1;
        var item = data || {};
        var nama = item.nama_gelombang !== undefined ? item.nama_gelombang : ('Gelombang ' + num);

        var wrap = document.createElement('div');
        wrap.className = 'wizard-subcard';
        wrap.style.marginBottom = '0';
        wrap.setAttribute('data-row-idx', idx);
        wrap._quotaCache = Object.assign({}, item.kuota_per_jenjang || {});

        wrap.innerHTML =
            '<input type="hidden" name="gelombangs[' + idx + '][id]" value="' + escapeHtml(item.id || '') + '">' +
            '<div style="display:grid; grid-template-columns: 2fr 1.2fr 1.2fr auto; gap:12px; align-items:end;">' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Nama Gelombang</label>' +
                    '<input type="text" name="gelombangs[' + idx + '][nama_gelombang]" value="' + escapeHtml(nama) + '" class="wizard-input" placeholder="Gelombang 1">' +
                '</div>' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Tanggal Mulai</label>' +
                    '<input type="date" name="gelombangs[' + idx + '][tanggal_mulai]" value="' + escapeHtml(item.tanggal_mulai || '') + '" class="wizard-input">' +
                '</div>' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Tanggal Selesai</label>' +
                    '<input type="date" name="gelombangs[' + idx + '][tanggal_selesai]" value="' + escapeHtml(item.tanggal_selesai || '') + '" class="wizard-input">' +
                '</div>' +
                '<button type="button" class="btn btn-secondary js-del-gel" title="Hapus" style="height:42px; padding:0 12px; color:#DC2626;">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                '</button>' +
            '</div>' +
            '<div class="js-gel-quotas" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:12px; margin-top:12px; padding-top:12px; border-top:1px solid var(--border-light, #E5E7EB);"></div>';

        wrap.querySelector('.js-del-gel').addEventListener('click', function () {
            wrap.remove();
            updateEmptyState();
        });

        listEl.appendChild(wrap);
        renderQuotaInputsForRow(wrap);
        updateEmptyState();
    }

    if (Array.isArray(initialRows) && initialRows.length > 0) {
        initialRows.forEach(addGelombangRow);
    } else {
        updateEmptyState();
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () { addGelombangRow({}); });
    }

    // Render awal dropdown chips, WA/Lokasi per lembaga, & kuota gelombang
    renderChipsAndSync();

    @if ($errors->any())
        @php
            $errKeys = $errors->keys();
            $jumpStep = 1;
            foreach ($errKeys as $ek) {
                if (str_starts_with($ek, 'gelombangs')) { $jumpStep = 3; break; }
                if (str_starts_with($ek, 'syarat_dokumen') || $ek === 'catatan_syarat') { $jumpStep = 2; break; }
            }
        @endphp
        goToStep({{ $jumpStep }});
    @endif
});
</script>
@endpush
@endsection
