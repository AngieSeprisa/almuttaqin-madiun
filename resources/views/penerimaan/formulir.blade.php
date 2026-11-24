@extends('layouts.app')

@section('title', 'Desain Formulir — ' . $penerimaan->judul)

@section('header-actions')
<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <a href="{{ $penerimaan->publicUrl() }}" target="_blank" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
            <polyline points="15 3 21 3 21 9"></polyline>
            <line x1="10" y1="14" x2="21" y2="3"></line>
        </svg>
        Lihat Form Publik
    </a>
    <a href="{{ route('penerimaan.index') }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>
    <button type="button" class="btn btn-primary" onclick="window.dispatchEvent(new CustomEvent('spmb-fb-save'))">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
            <polyline points="17 21 17 13 7 13 7 21"></polyline>
            <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Simpan Formulir
    </button>
</div>
@endsection

@section('content')
@php
    $builderPayload = [
        'schema'        => $schema,
        'defaultSchema' => $defaultSchema,
        'compactSchema' => $compactSchema,
        'fieldTypes'    => $fieldTypes,
        'hasCustom'     => $penerimaan->hasCustomFormSchema(),
    ];
@endphp

<style>
    .fb-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 290px;
        gap: 20px;
        align-items: start;
        width: 100%;
    }
    .fb-canvas {
        display: flex;
        flex-direction: column;
        gap: 16px;
        min-width: 0;
    }
    .fb-sidebar {
        position: sticky;
        top: 78px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .fb-card {
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-light, #E5E7EB);
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
    }
    .fb-header-card {
        border-top: 5px solid #0052FF;
    }
    .fb-section-card {
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-light, #E5E7EB);
        border-top: 4px solid #4F46E5;
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .fb-section-card.is-disabled {
        opacity: 0.65;
        border-top-color: #94A3B8;
    }
    .fb-section-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-light, #E5E7EB);
    }
    .fb-section-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 3px 9px;
        border-radius: 999px;
        background: rgba(79, 70, 229, 0.1);
        color: #4F46E5;
        margin-bottom: 8px;
    }
    .fb-title-input {
        width: 100%;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-primary);
        background: transparent;
        border: none;
        border-bottom: 1.5px solid transparent;
        padding: 4px 0;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .fb-title-input:hover,
    .fb-title-input:focus {
        border-bottom-color: #4F46E5;
    }
    .fb-desc-input {
        width: 100%;
        font-size: 13px;
        color: var(--text-secondary);
        background: transparent;
        border: none;
        border-bottom: 1px solid transparent;
        padding: 4px 0;
        margin-top: 2px;
        outline: none;
    }
    .fb-desc-input:hover,
    .fb-desc-input:focus {
        border-bottom-color: var(--border-color, #CBD5E1);
    }
    .fb-field-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .fb-field-card {
        background: var(--bg-surface, #F8FAFC);
        border: 1px solid var(--border-light, #E5E7EB);
        border-left: 4px solid #CBD5E1;
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    }
    .fb-field-card.is-active {
        background: var(--card-bg, #FFFFFF);
        border-left-color: #0052FF;
        box-shadow: 0 4px 14px rgba(0, 82, 255, 0.08);
    }
    .fb-field-card.is-custom {
        border-left-color: #10B981;
    }
    .fb-field-card.is-custom.is-active {
        border-left-color: #0052FF;
    }
    .fb-field-card.is-off {
        opacity: 0.56;
    }
    .fb-field-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 190px;
        gap: 10px;
        align-items: center;
    }
    .fb-input,
    .fb-select {
        width: 100%;
        padding: 8px 11px;
        border-radius: 9px;
        border: 1px solid var(--border-light, #D1D5DB);
        background: var(--card-bg, #FFFFFF);
        color: var(--text-primary);
        font-size: 13.5px;
        box-sizing: border-box;
    }
    .fb-input:focus,
    .fb-select:focus {
        outline: none;
        border-color: #0052FF;
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.1);
    }
    .fb-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .fb-pill-locked { background: rgba(239, 68, 68, 0.1); color: #DC2626; }
    .fb-pill-builtin { background: rgba(59, 130, 246, 0.1); color: #2563EB; }
    .fb-pill-custom { background: rgba(16, 185, 129, 0.12); color: #059669; }
    .fb-field-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        padding-top: 10px;
        border-top: 1px dashed var(--border-light, #E5E7EB);
    }
    .fb-icon-btn {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid var(--border-light, #E5E7EB);
        background: var(--card-bg, #FFFFFF);
        color: var(--text-secondary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        transition: all 0.15s ease;
    }
    .fb-icon-btn:hover:not(:disabled) {
        border-color: #0052FF;
        color: #0052FF;
    }
    .fb-icon-btn.danger:hover:not(:disabled) {
        border-color: #EF4444;
        color: #EF4444;
    }
    .fb-icon-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .fb-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-primary);
        cursor: pointer;
        user-select: none;
    }
    .fb-toggle input {
        width: 15px;
        height: 15px;
        accent-color: #0052FF;
        cursor: pointer;
    }
    .fb-options-wrap {
        display: flex;
        flex-direction: column;
        gap: 7px;
        padding: 10px 12px;
        border-radius: 10px;
        background: var(--card-bg, #FFFFFF);
        border: 1px solid var(--border-light, #E5E7EB);
    }
    .fb-option-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .fb-stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .fb-stat-box {
        padding: 10px 12px;
        border-radius: 10px;
        background: var(--bg-surface, #F8FAFC);
        border: 1px solid var(--border-light, #E5E7EB);
    }
    .fb-stat-num {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.1;
    }
    .fb-stat-lbl {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .fb-layout-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .fb-layout-opt {
        border: 1.5px solid var(--border-light, #E5E7EB);
        border-radius: 10px;
        padding: 10px 12px;
        cursor: pointer;
        background: var(--bg-surface, #F8FAFC);
        transition: all 0.15s ease;
    }
    .fb-layout-opt.is-selected {
        border-color: #0052FF;
        background: rgba(0, 82, 255, 0.06);
    }
    @media (max-width: 1024px) {
        .fb-shell {
            grid-template-columns: 1fr;
        }
        .fb-sidebar {
            position: static;
            order: -1;
        }
    }
    @media (max-width: 640px) {
        .fb-field-row,
        .fb-layout-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div x-data="spmbFormBuilder()" x-init="init()" @spmb-fb-save.window="submitForm()" x-cloak>
    <form id="formBuilderSave" action="{{ route('penerimaan.formulir.update', $penerimaan->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="form_schema_json" :value="JSON.stringify(schema)">
    </form>

    <form id="formBuilderReset" action="{{ route('penerimaan.formulir.reset', $penerimaan->id) }}" method="POST" style="display:none;">
        @csrf
    </form>

    <div class="fb-shell">
        {{-- CANVAS UTAMA ALA GOOGLE FORM --}}
        <div class="fb-canvas">
            {{-- Kartu Header Formulir --}}
            <div class="fb-card fb-header-card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <h2 style="font-size:18px; font-weight:800; margin:0; color:var(--text-primary);">{{ $penerimaan->judul }}</h2>
                            <span class="fb-pill" :class="hasCustom ? 'fb-pill-custom' : 'fb-pill-builtin'" x-text="hasCustom ? 'Form Kustom' : 'Form Standar'"></span>
                        </div>
                        <div style="font-size:12.5px; color:var(--text-muted); margin-top:4px;">
                            Link: <code>/spmb/{{ $penerimaan->slug }}</code> · Jenjang: <strong>{{ $penerimaan->jenjangLabel() }}</strong>
                        </div>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-sm btn-secondary" @click="applyPreset('compact')" title="Cocok untuk RA / SD IT">
                            Preset Ringkas (RA / SD IT)
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" @click="applyPreset('default')">
                            Preset Standar Lengkap
                        </button>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:6px;">Mode Tampilan Formulir Publik</label>
                        <div class="fb-layout-grid">
                            <div class="fb-layout-opt" :class="schema.layout === 'wizard' ? 'is-selected' : ''" @click="schema.layout = 'wizard'">
                                <div style="font-size:13px; font-weight:700; color:var(--text-primary);">Bertahap (Per Bagian)</div>
                                <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Langkah 1, 2, 3 dengan tombol Selanjutnya</div>
                            </div>
                            <div class="fb-layout-opt" :class="schema.layout === 'single' ? 'is-selected' : ''" @click="schema.layout = 'single'">
                                <div style="font-size:13px; font-weight:700; color:var(--text-primary);">Satu Halaman (Google Form)</div>
                                <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Semua bagian tampil berurutan ke bawah</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label style="display:block; font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:5px;">Deskripsi / Catatan Pengantar Formulir (Opsional)</label>
                        <textarea x-model="schema.description" rows="2" class="fb-input" placeholder="Contoh: Silakan isi formulir pendaftaran dengan data yang benar sesuai Kartu Keluarga."></textarea>
                    </div>
                </div>
            </div>

            {{-- DAFTAR BAGIAN (SECTIONS) --}}
            <template x-for="(section, sIdx) in schema.sections" :key="section.id">
                <div class="fb-section-card" :class="!section.enabled ? 'is-disabled' : ''">
                    <div class="fb-section-top">
                        <div style="flex:1; min-width:220px;">
                            <div class="fb-section-badge">
                                <span x-text="'Bagian ' + (sIdx + 1) + ' dari ' + schema.sections.length"></span>
                                <span x-text="'· ' + countActiveFields(section) + ' aktif'"></span>
                            </div>
                            <input type="text" class="fb-title-input" x-model="section.title" placeholder="Judul Bagian" maxlength="80">
                            <input type="text" class="fb-desc-input" x-model="section.description" placeholder="Deskripsi bagian (opsional)" maxlength="300">
                        </div>

                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                            <label class="fb-toggle" style="margin-right:4px;" :title="sectionHasLocked(section) ? 'Bagian berisi field inti sistem' : 'Tampilkan atau sembunyikan seluruh bagian ini'">
                                <input type="checkbox" x-model="section.enabled" :disabled="sectionHasLocked(section)">
                                <span>Tampil</span>
                            </label>
                            <button type="button" class="fb-icon-btn" title="Pindah Bagian ke Atas" :disabled="sIdx === 0" @click="moveSection(sIdx, -1)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="18 15 12 9 6 15"></polyline></svg>
                            </button>
                            <button type="button" class="fb-icon-btn" title="Pindah Bagian ke Bawah" :disabled="sIdx === schema.sections.length - 1" @click="moveSection(sIdx, 1)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </button>
                            <button type="button" class="fb-icon-btn danger" title="Hapus Bagian" :disabled="schema.sections.length <= 1 || sectionHasLocked(section)" @click="removeSection(sIdx)">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>
                            </button>
                        </div>
                    </div>

                    {{-- DAFTAR PERTANYAAN DALAM BAGIAN --}}
                    <div class="fb-field-list">
                        <template x-for="(field, fIdx) in section.fields" :key="field.id">
                            <div class="fb-field-card"
                                 x-show="!onlyActiveFields || field.enabled"
                                 :class="{
                                     'is-active': selectedFieldId === field.id,
                                     'is-custom': field.source === 'custom',
                                     'is-off': !field.enabled
                                 }"
                                 @click="selectedFieldId = field.id">

                                {{-- Baris Utama: Label + Tipe --}}
                                <div class="fb-field-row">
                                    <div style="display:flex; flex-direction:column; gap:5px; min-width:0;">
                                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                            <span class="fb-pill fb-pill-locked" x-show="field.locked">Inti Sistem</span>
                                            <span class="fb-pill fb-pill-builtin" x-show="field.source === 'builtin' && !field.locked">Bawaan</span>
                                            <span class="fb-pill fb-pill-custom" x-show="field.source === 'custom'">Kustom</span>
                                            <span style="font-size:11px; color:var(--text-muted);" x-show="field.source === 'builtin'" x-text="'(' + field.key + ')'"></span>
                                        </div>
                                        <input type="text" class="fb-input" x-model="field.label" placeholder="Tulis pertanyaan..." maxlength="160">
                                    </div>

                                    <div>
                                        <template x-if="field.source === 'custom'">
                                            <select class="fb-select" x-model="field.type" @change="onCustomTypeChange(field)">
                                                <template x-for="(tLabel, tKey) in fieldTypes" :key="tKey">
                                                    <option :value="tKey" x-text="tLabel"></option>
                                                </template>
                                            </select>
                                        </template>
                                        <template x-if="field.source === 'builtin'">
                                            <div class="fb-input" style="background:var(--bg-surface); color:var(--text-secondary); font-size:12.5px;" x-text="fieldTypes[field.type] || field.type"></div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Detail Tambahan (Placeholder & Petunjuk) saat aktif --}}
                                <div x-show="selectedFieldId === field.id" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap:10px;">
                                    <div x-show="['text', 'textarea', 'number'].includes(field.type)">
                                        <label style="display:block; font-size:11.5px; font-weight:600; color:var(--text-muted); margin-bottom:4px;">Contoh Isian (Placeholder)</label>
                                        <input type="text" class="fb-input" x-model="field.placeholder" placeholder="Teks petunjuk di dalam kolom">
                                    </div>
                                    <div>
                                        <label style="display:block; font-size:11.5px; font-weight:600; color:var(--text-muted); margin-bottom:4px;">Keterangan / Petunjuk Pengisian</label>
                                        <input type="text" class="fb-input" x-model="field.help" placeholder="Opsional, tampil di bawah pertanyaan">
                                    </div>
                                </div>

                                {{-- Editor Opsi untuk Pilihan Ganda / Checkbox / Dropdown --}}
                                <div class="fb-options-wrap" x-show="canEditOptions(field)">
                                    <div style="font-size:11.5px; font-weight:700; color:var(--text-secondary);">Pilihan Jawaban</div>
                                    <template x-for="(opt, oIdx) in field.options" :key="oIdx">
                                        <div class="fb-option-row">
                                            <span style="width:18px; text-align:center; color:var(--text-muted); font-size:12px;"
                                                  x-text="field.type === 'radio' ? '○' : (field.type === 'checkbox' ? '☐' : (oIdx + 1) + '.')"></span>
                                            <input type="text" class="fb-input" style="padding:6px 10px; font-size:13px;"
                                                   x-model="field.options[oIdx]" placeholder="Tulis pilihan...">
                                            <button type="button" class="fb-icon-btn danger" title="Hapus opsi"
                                                    :disabled="field.options.length <= 1"
                                                    @click="field.options.splice(oIdx, 1)">&times;</button>
                                        </div>
                                    </template>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-secondary" style="font-size:12px; padding:4px 10px;"
                                                @click="field.options.push('Opsi ' + (field.options.length + 1))">
                                            + Tambah Opsi
                                        </button>
                                    </div>
                                </div>

                                {{-- Toolbar Aksi Kartu --}}
                                <div class="fb-field-footer">
                                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                        <button type="button" class="fb-icon-btn" title="Naikkan" :disabled="fIdx === 0" @click.stop="moveField(sIdx, fIdx, -1)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="18 15 12 9 6 15"></polyline></svg>
                                        </button>
                                        <button type="button" class="fb-icon-btn" title="Turunkan" :disabled="fIdx === section.fields.length - 1" @click.stop="moveField(sIdx, fIdx, 1)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                        </button>
                                        <select class="fb-select" style="width:auto; padding:4px 8px; font-size:12px; height:30px;"
                                                title="Pindah ke bagian lain"
                                                x-show="schema.sections.length > 1"
                                                @click.stop
                                                @change="moveFieldToSection(sIdx, fIdx, parseInt($event.target.value, 10)); $event.target.value = sIdx;">
                                            <template x-for="(targetSec, tIdx) in schema.sections" :key="targetSec.id">
                                                <option :value="tIdx" :selected="tIdx === sIdx" x-text="'Bagian ' + (tIdx + 1) + ': ' + targetSec.title"></option>
                                            </template>
                                        </select>
                                        <button type="button" class="fb-icon-btn" title="Duplikat pertanyaan" x-show="field.source === 'custom'" @click.stop="duplicateField(sIdx, fIdx)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="9" y="9" width="13" height="13" rx="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                        </button>
                                        <button type="button" class="fb-icon-btn danger" title="Hapus pertanyaan" x-show="field.source === 'custom'" @click.stop="section.fields.splice(fIdx, 1)">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>
                                        </button>
                                    </div>

                                    <div style="display:flex; align-items:center; gap:14px;">
                                        <label class="fb-toggle" @click.stop>
                                            <input type="checkbox" x-model="field.enabled" :disabled="field.locked" @change="if(!field.enabled) field.required = false;">
                                            <span>Tampil</span>
                                        </label>
                                        <label class="fb-toggle" @click.stop>
                                            <input type="checkbox" x-model="field.required" :disabled="field.locked || !field.enabled">
                                            <span>Wajib</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="section.fields.length === 0 || (onlyActiveFields && countActiveFields(section) === 0)"
                             style="padding:18px; text-align:center; border:1px dashed var(--border-light); border-radius:10px; font-size:13px; color:var(--text-muted);">
                            Belum ada pertanyaan aktif di bagian ini.
                        </div>
                    </div>

                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; padding-top:4px;">
                        <button type="button" class="btn btn-sm btn-primary" @click="addCustomField(sIdx)">
                            + Tambah Pertanyaan
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" @click="toggleAllInSection(sIdx, true)">
                            Aktifkan Semua
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" @click="toggleAllInSection(sIdx, false)">
                            Nonaktifkan Opsional
                        </button>
                    </div>
                </div>
            </template>

            <div>
                <button type="button" class="btn btn-secondary" style="width:100%; justify-content:center; padding:12px; border-style:dashed;" @click="addSection()">
                    + Tambah Bagian Baru
                </button>
            </div>
        </div>

        {{-- SIDEBAR ALAT & RINGKASAN --}}
        <aside class="fb-sidebar">
            <div class="fb-card" style="display:flex; flex-direction:column; gap:14px;">
                <div style="font-size:14px; font-weight:800; color:var(--text-primary);">Ringkasan Formulir</div>

                <div class="fb-stat-grid">
                    <div class="fb-stat-box">
                        <div class="fb-stat-num" x-text="totalActiveSections()"></div>
                        <div class="fb-stat-lbl">Bagian Aktif</div>
                    </div>
                    <div class="fb-stat-box">
                        <div class="fb-stat-num" x-text="totalActiveFields()"></div>
                        <div class="fb-stat-lbl">Field Aktif</div>
                    </div>
                    <div class="fb-stat-box">
                        <div class="fb-stat-num" style="color:#059669;" x-text="totalCustomFields()"></div>
                        <div class="fb-stat-lbl">Pertanyaan Kustom</div>
                    </div>
                    <div class="fb-stat-box">
                        <div class="fb-stat-num" style="color:#DC2626;" x-text="totalRequiredFields()"></div>
                        <div class="fb-stat-lbl">Wajib Diisi</div>
                    </div>
                </div>

                <label class="fb-toggle" style="padding:9px 11px; border-radius:9px; background:var(--bg-surface); border:1px solid var(--border-light);">
                    <input type="checkbox" x-model="onlyActiveFields">
                    <span>Tampilkan field aktif saja</span>
                </label>

                <div style="display:flex; flex-direction:column; gap:8px;">
                    <button type="button" class="btn btn-primary" style="width:100%; justify-content:center;" @click="submitForm()">
                        Simpan Formulir
                    </button>
                    <button type="button" class="btn btn-secondary" style="width:100%; justify-content:center;" @click="addCustomField(schema.sections.length - 1)">
                        + Tambah Pertanyaan Baru
                    </button>
                    <button type="button" class="btn btn-secondary" style="width:100%; justify-content:center;" @click="addSection()">
                        + Tambah Bagian Baru
                    </button>
                </div>

                @if($penerimaan->hasCustomFormSchema())
                <div style="padding-top:10px; border-top:1px solid var(--border-light);">
                    <button type="button" class="btn btn-sm btn-secondary" style="width:100%; justify-content:center; color:#DC2626; border-color:rgba(239,68,68,0.3);" @click="confirmReset()">
                        Reset ke Standar Bawaan
                    </button>
                </div>
                @endif
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
function spmbFormBuilder() {
    var payload = @json($builderPayload);
    var cloneObj = function (o) { return JSON.parse(JSON.stringify(o)); };
    var fixedOptionBuiltins = ['jenjang', 'jenis_kelamin', 'penerima_kip', 'ayah_status', 'ibu_status', 'status_mondok'];

    return {
        schema: cloneObj(payload.schema),
        defaultSchema: payload.defaultSchema,
        compactSchema: payload.compactSchema,
        fieldTypes: payload.fieldTypes,
        hasCustom: Boolean(payload.hasCustom),
        selectedFieldId: null,
        onlyActiveFields: false,

        init() {
            if (this.schema.sections.length && this.schema.sections[0].fields.length) {
                this.selectedFieldId = this.schema.sections[0].fields[0].id;
            }
        },

        applyPreset(mode) {
            var source = mode === 'compact' ? this.compactSchema : this.defaultSchema;
            this.schema = cloneObj(source);
            this.onlyActiveFields = mode === 'compact';
            if (this.schema.sections.length && this.schema.sections[0].fields.length) {
                this.selectedFieldId = this.schema.sections[0].fields[0].id;
            }
        },

        sectionHasLocked(sec) {
            return (sec.fields || []).some(function (f) { return Boolean(f.locked); });
        },

        countActiveFields(sec) {
            return (sec.fields || []).filter(function (f) { return Boolean(f.enabled); }).length;
        },

        totalActiveSections() {
            return this.schema.sections.filter(function (s) {
                return s.enabled && (s.fields || []).some(function (f) { return f.enabled; });
            }).length;
        },

        totalActiveFields() {
            var n = 0;
            this.schema.sections.forEach(function (s) {
                if (!s.enabled) return;
                (s.fields || []).forEach(function (f) { if (f.enabled) n++; });
            });
            return n;
        },

        totalCustomFields() {
            var n = 0;
            this.schema.sections.forEach(function (s) {
                if (!s.enabled) return;
                (s.fields || []).forEach(function (f) { if (f.enabled && f.source === 'custom') n++; });
            });
            return n;
        },

        totalRequiredFields() {
            var n = 0;
            this.schema.sections.forEach(function (s) {
                if (!s.enabled) return;
                (s.fields || []).forEach(function (f) { if (f.enabled && f.required) n++; });
            });
            return n;
        },

        canEditOptions(field) {
            if (!['radio', 'checkbox', 'select'].includes(field.type)) return false;
            if (field.source === 'custom') return true;
            return !fixedOptionBuiltins.includes(field.key);
        },

        onCustomTypeChange(field) {
            if (['radio', 'checkbox', 'select'].includes(field.type)) {
                if (!Array.isArray(field.options) || field.options.length === 0) {
                    field.options = ['Opsi 1', 'Opsi 2'];
                }
            }
            field.wide = ['textarea', 'radio', 'checkbox', 'file'].includes(field.type);
        },

        addSection() {
            var num = this.schema.sections.length + 1;
            this.schema.sections.push({
                id: 'sec_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                title: 'Bagian ' + num,
                short_title: 'Bagian ' + num,
                description: '',
                enabled: true,
                fields: []
            });
        },

        moveSection(idx, dir) {
            var target = idx + dir;
            if (target < 0 || target >= this.schema.sections.length) return;
            var item = this.schema.sections.splice(idx, 1)[0];
            this.schema.sections.splice(target, 0, item);
        },

        removeSection(idx) {
            if (this.schema.sections.length <= 1) return;
            var sec = this.schema.sections[idx];
            if (this.sectionHasLocked(sec)) return;
            if (sec.fields.length > 0 && !confirm('Hapus bagian "' + sec.title + '" beserta pertanyaan di dalamnya?')) {
                return;
            }
            this.schema.sections.splice(idx, 1);
        },

        addCustomField(sIdx) {
            var targetIdx = Math.max(0, Math.min(sIdx, this.schema.sections.length - 1));
            var id = 'cf_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 999);
            var newField = {
                id: id,
                source: 'custom',
                key: id,
                label: 'Pertanyaan Baru',
                type: 'text',
                placeholder: '',
                help: '',
                required: false,
                enabled: true,
                locked: false,
                wide: false,
                options: []
            };
            this.schema.sections[targetIdx].enabled = true;
            this.schema.sections[targetIdx].fields.push(newField);
            this.selectedFieldId = id;
        },

        duplicateField(sIdx, fIdx) {
            var orig = this.schema.sections[sIdx].fields[fIdx];
            var copy = cloneObj(orig);
            var id = 'cf_' + Date.now().toString(36) + '_' + Math.floor(Math.random() * 999);
            copy.id = id;
            copy.key = id;
            copy.label = orig.label + ' (Salinan)';
            this.schema.sections[sIdx].fields.splice(fIdx + 1, 0, copy);
            this.selectedFieldId = id;
        },

        moveField(sIdx, fIdx, dir) {
            var list = this.schema.sections[sIdx].fields;
            var target = fIdx + dir;
            if (target < 0 || target >= list.length) return;
            var item = list.splice(fIdx, 1)[0];
            list.splice(target, 0, item);
        },

        moveFieldToSection(fromIdx, fIdx, toIdx) {
            if (fromIdx === toIdx || toIdx < 0 || toIdx >= this.schema.sections.length) return;
            var item = this.schema.sections[fromIdx].fields.splice(fIdx, 1)[0];
            this.schema.sections[toIdx].enabled = true;
            this.schema.sections[toIdx].fields.push(item);
        },

        toggleAllInSection(sIdx, state) {
            (this.schema.sections[sIdx].fields || []).forEach(function (f) {
                if (f.locked) return;
                f.enabled = state;
                if (!state) f.required = false;
            });
        },

        submitForm() {
            document.getElementById('formBuilderSave').submit();
        },

        confirmReset() {
            if (confirm('Kembalikan formulir ini ke desain standar bawaan? Pertanyaan kustom akan dihapus dari tampilan formulir.')) {
                document.getElementById('formBuilderReset').submit();
            }
        }
    };
}
</script>
@endpush
@endsection
