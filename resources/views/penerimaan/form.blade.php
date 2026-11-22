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

    $initialGelombangs = old('gelombangs');
    if ($initialGelombangs === null) {
        $initialGelombangs = $gelombangs->map(fn ($g) => [
            'id'              => $g->id,
            'nama_gelombang'  => $g->nama_gelombang,
            'tanggal_mulai'   => $g->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $g->tanggal_selesai?->format('Y-m-d'),
            'kuota'           => $g->kuota,
        ])->values()->all();
    }
@endphp

<style>
    .jenjang-check-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .jenjang-check-card {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 10px 16px;
        border: 1.5px solid var(--border-color, #D1D5DB);
        border-radius: 10px;
        background: var(--input-bg, #FFFFFF);
        cursor: pointer;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-primary);
        transition: all 0.15s ease;
        user-select: none;
    }
    .jenjang-check-card:hover {
        border-color: var(--electric-blue, #0052FF);
    }
    .jenjang-check-card.is-checked {
        border-color: var(--electric-blue, #0052FF);
        background: rgba(0, 82, 255, 0.06);
        color: var(--electric-blue, #0052FF);
    }
    .jenjang-check-card input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #0052FF;
        cursor: pointer;
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

                        {{-- Checklist Jenjang (Multi-pilih) --}}
                        <div class="wizard-form-group" style="grid-column: 1 / -1;">
                            <label class="wizard-label">
                                Jenjang <span style="color:#EF4444;">*</span>
                            </label>
                            <div class="jenjang-check-grid" id="jenjangCheckGroup">
                                @foreach($jenjangItems as $val => $label)
                                @php $checked = in_array($val, $selectedJenjangs, true); @endphp
                                <label class="jenjang-check-card {{ $checked ? 'is-checked' : '' }}" data-jenjang-card="{{ $val }}">
                                    <input type="checkbox" name="jenjangs[]" value="{{ $val }}" class="js-jenjang-checkbox" {{ $checked ? 'checked' : '' }}>
                                    <span>{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('jenjangs')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- No. WA Panitia per Jenjang yang di-checklist --}}
                        <div class="wizard-form-group" style="grid-column: 1 / -1;" id="waPerJenjangWrap">
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                                @foreach($jenjangItems as $val => $label)
                                @php $isVisible = in_array($val, $selectedJenjangs, true); @endphp
                                <div class="wizard-form-group js-wa-jenjang-item" data-wa-jenjang="{{ $val }}" style="{{ $isVisible ? '' : 'display:none;' }} margin-bottom:0;">
                                    <label class="wizard-label" for="wa_{{ $val }}">No. WA Panitia {{ $val }}</label>
                                    <input type="text"
                                           name="kontak_wa_per_jenjang[{{ $val }}]"
                                           id="wa_{{ $val }}"
                                           value="{{ $waValues[$val] ?? '' }}"
                                           class="wizard-input"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="jadwal_seleksi">Jadwal Seleksi Offline</label>
                            <input type="text" name="jadwal_seleksi" id="jadwal_seleksi"
                                   value="{{ old('jadwal_seleksi', $penerimaan->jadwal_seleksi) }}"
                                   class="wizard-input"
                                   placeholder="Sabtu, 15 Mei 2027 · 08.00 WIB">
                        </div>

                        <div class="wizard-form-group">
                            <label class="wizard-label" for="lokasi_seleksi">Lokasi Seleksi Offline</label>
                            <input type="text" name="lokasi_seleksi" id="lokasi_seleksi"
                                   value="{{ old('lokasi_seleksi', $penerimaan->lokasi_seleksi) }}"
                                   class="wizard-input"
                                   placeholder="Pondok Pesantren Al Muttaqin Madiun">
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

                {{-- TAHAP 03: GELOMBANG PENDAFTARAN --}}
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

    // 2. Checklist Jenjang -> tampilkan input WA Panitia sesuai jenjang terpilih
    function syncJenjangWaFields() {
        var anyChecked = false;
        document.querySelectorAll('.js-jenjang-checkbox').forEach(function (cb) {
            var val = cb.value;
            var card = cb.closest('[data-jenjang-card]');
            var waBox = document.querySelector('[data-wa-jenjang="' + val + '"]');
            if (card) card.classList.toggle('is-checked', cb.checked);
            if (waBox) waBox.style.display = cb.checked ? '' : 'none';
            if (cb.checked) anyChecked = true;
        });
        var wrap = document.getElementById('waPerJenjangWrap');
        if (wrap) wrap.style.display = anyChecked ? '' : 'none';
    }

    document.querySelectorAll('.js-jenjang-checkbox').forEach(function (cb) {
        cb.addEventListener('change', syncJenjangWaFields);
    });
    syncJenjangWaFields();

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

    // 4. Dynamic Gelombang Rows
    var listEl = document.getElementById('gelombangList');
    var emptyEl = document.getElementById('gelombangEmpty');
    var addBtn = document.getElementById('btnAddGelombang');
    var rows = @json($initialGelombangs);
    var counter = 0;

    function updateEmptyState() {
        emptyEl.style.display = listEl.children.length > 0 ? 'none' : 'block';
    }

    function escapeAttr(val) {
        return String(val === null || val === undefined ? '' : val)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function addGelombangRow(data) {
        var idx = counter++;
        var num = listEl.children.length + 1;
        var item = data || {};
        var nama = item.nama_gelombang !== undefined ? item.nama_gelombang : ('Gelombang ' + num);

        var wrap = document.createElement('div');
        wrap.className = 'wizard-subcard';
        wrap.style.marginBottom = '0';
        wrap.innerHTML =
            '<input type="hidden" name="gelombangs[' + idx + '][id]" value="' + escapeAttr(item.id || '') + '">' +
            '<div style="display:grid; grid-template-columns: 2fr 1.2fr 1.2fr 0.8fr auto; gap:12px; align-items:end;">' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Nama Gelombang</label>' +
                    '<input type="text" name="gelombangs[' + idx + '][nama_gelombang]" value="' + escapeAttr(nama) + '" class="wizard-input" placeholder="Gelombang 1">' +
                '</div>' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Tanggal Mulai</label>' +
                    '<input type="date" name="gelombangs[' + idx + '][tanggal_mulai]" value="' + escapeAttr(item.tanggal_mulai || '') + '" class="wizard-input">' +
                '</div>' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Tanggal Selesai</label>' +
                    '<input type="date" name="gelombangs[' + idx + '][tanggal_selesai]" value="' + escapeAttr(item.tanggal_selesai || '') + '" class="wizard-input">' +
                '</div>' +
                '<div class="wizard-form-group" style="margin-bottom:0;">' +
                    '<label class="wizard-label">Kuota</label>' +
                    '<input type="number" min="1" name="gelombangs[' + idx + '][kuota]" value="' + escapeAttr(item.kuota || '') + '" class="wizard-input" placeholder="∞">' +
                '</div>' +
                '<button type="button" class="btn btn-secondary js-del-gel" title="Hapus" style="height:42px; padding:0 12px; color:#DC2626;">' +
                    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>' +
                '</button>' +
            '</div>';

        wrap.querySelector('.js-del-gel').addEventListener('click', function () {
            wrap.remove();
            updateEmptyState();
        });

        listEl.appendChild(wrap);
        updateEmptyState();
    }

    if (Array.isArray(rows) && rows.length > 0) {
        rows.forEach(addGelombangRow);
    } else {
        updateEmptyState();
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () { addGelombangRow({}); });
    }

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
