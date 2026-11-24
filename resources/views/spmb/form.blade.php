@extends('spmb.layout')

@section('title', $penerimaan->judul)

@section('content')

@if (session('error'))
<div class="alert-banner alert-error" style="margin-bottom:18px;">
    <div>{{ session('error') }}</div>
</div>
@endif

@if (!$isOpen)
    <div class="spmb-card" style="width:100%; text-align:center; padding:36px 24px;">
        <div style="margin-bottom:14px;">
            <span class="status-pill status-ditolak" style="font-size:13px; padding:6px 16px;">{{ $statusState['label'] }}</span>
        </div>
        <h1 style="font-size:22px; font-weight:800; color:#0F172A; margin:0 0 18px 0;">{{ $penerimaan->judul }}</h1>

        @if($penerimaan->gelombangs->isNotEmpty())
        <div style="text-align:left; border:1px solid #E5E7EB; border-radius:12px; overflow:hidden; margin-bottom:20px;">
            <table class="modern-table" style="margin:0;">
                <thead>
                    <tr>
                        <th>Gelombang</th>
                        <th>Jadwal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($penerimaan->gelombangs as $gel)
                    <tr>
                        <td style="font-weight:600;">{{ $gel->nama_gelombang }}</td>
                        <td>{{ $gel->tanggal_mulai->translatedFormat('d M Y') }} – {{ $gel->tanggal_selesai->translatedFormat('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @php $waLinks = $penerimaan->waLinksByJenjang(); @endphp
        <div class="spmb-actions-stack" style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('spmb.cek-status', $penerimaan->slug) }}" class="btn btn-primary">Cek Status Pendaftaran</a>
            @foreach($waLinks as $jLabel => $jUrl)
                <a href="{{ $jUrl }}" target="_blank" class="btn btn-secondary">WA Panitia {{ $jLabel }}</a>
            @endforeach
        </div>
    </div>
@else
    @php
        $firstLokasi = count($availableJenjangs) === 1
            ? ($lokasiMap[$availableJenjangs[0]] ?? $penerimaan->lokasiForJenjang($availableJenjangs[0]))
            : $penerimaan->lokasiForJenjang(old('jenjang'));

        $layoutMode = ($formSchema['layout'] ?? 'wizard') === 'single' || count($visibleSections) <= 1 ? 'single' : 'wizard';
        $totalSteps = count($visibleSections);
        $dokumenMap = \App\Support\SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG;

        // Mapping error key -> nomor step untuk auto-jump saat validasi gagal
        $errorStepTarget = 1;
        if ($errors->any()) {
            $errKeys = $errors->keys();
            foreach ($visibleSections as $sIdx => $sec) {
                foreach ($sec['fields'] as $fld) {
                    $k = ($fld['source'] ?? '') === 'custom' ? ('custom_fields.' . $fld['id']) : $fld['key'];
                    if (in_array($k, $errKeys, true)) {
                        $errorStepTarget = $sIdx + 1;
                        break 2;
                    }
                }
            }
        }
    @endphp

    <style>
        .spmb-choice-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .spmb-choice-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 13px;
            border-radius: 10px;
            border: 1px solid #E2E8F0;
            background: #FFFFFF;
            cursor: pointer;
            font-size: 13.5px;
            color: #0F172A;
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .spmb-choice-item:hover {
            border-color: #93C5FD;
            background: #F8FAFC;
        }
        .spmb-choice-item input {
            width: 17px;
            height: 17px;
            accent-color: #0052FF;
            flex-shrink: 0;
        }
        .spmb-field-help {
            font-size: 12px;
            color: #64748B;
            margin-top: 4px;
            line-height: 1.4;
        }
        .spmb-single-section {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-top: 4px solid #0052FF;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        }
        .spmb-single-sec-head {
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid #F1F5F9;
        }
        .spmb-single-sec-title {
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            margin: 0;
        }
        .spmb-single-sec-desc {
            font-size: 13px;
            color: #64748B;
            margin: 4px 0 0 0;
            line-height: 1.45;
        }
    </style>

    {{-- Ringkasan Info Event --}}
    <div class="spmb-info-card">
        <div class="spmb-info-top">
            <span class="spmb-wave-badge">
                <span class="spmb-wave-dot"></span>
                @if($activeGelombang)
                    {{ $activeGelombang->nama_gelombang }} · s/d {{ $activeGelombang->tanggal_selesai->translatedFormat('d M Y') }}
                @else
                    Pendaftaran Dibuka
                @endif
            </span>
        </div>

        <div class="spmb-info-grid">
            <div class="spmb-info-item">
                <span class="spmb-info-label">Jenjang</span>
                <span class="spmb-info-value">{{ $penerimaan->jenjangLabel() }}</span>
            </div>
            @if($penerimaan->jadwal_seleksi)
            <div class="spmb-info-item">
                <span class="spmb-info-label">Seleksi</span>
                <span class="spmb-info-value">{{ $penerimaan->jadwal_seleksi }}</span>
            </div>
            @endif
            <div class="spmb-info-item spmb-info-wide" id="chipLokasiSeleksi" style="{{ $firstLokasi ? '' : 'display:none;' }}">
                <span class="spmb-info-label">Lokasi Seleksi</span>
                <span class="spmb-info-value" id="chipLokasiText">{{ $firstLokasi }}</span>
            </div>
        </div>
    </div>

    @if(!empty($formSchema['description']))
    <div class="spmb-card" style="padding:14px 16px; margin-bottom:14px; font-size:13.5px; color:#334155; line-height:1.55;">
        {!! nl2br(e($formSchema['description'])) !!}
    </div>
    @endif

    @php $syaratList = $penerimaan->syaratList(); @endphp
    @if(!empty($syaratList))
    <div class="spmb-card" style="padding:14px 16px; margin-bottom:14px;">
        <div style="font-size:12.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#64748B; margin-bottom:6px;">Persyaratan</div>
        <ul style="margin:0; padding-left:18px; font-size:13.5px; color:#1E293B; display:flex; flex-direction:column; gap:4px; line-height:1.45;">
            @foreach($syaratList as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error" style="margin-bottom: 16px; border-radius:12px;">
        <div>
            <ul style="margin: 0 0 0 18px; font-size: 13px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('spmb.store', $penerimaan->slug) }}" method="POST" enctype="multipart/form-data" id="formSpmbPublic">
        @csrf
        {{-- Honeypot anti-bot --}}
        <div style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
        </div>

        @if($layoutMode === 'wizard')
        <div class="wizard-card">
            <div class="wizard-stepper" id="wizardStepper">
                @foreach($visibleSections as $sIdx => $sec)
                    @php $stepNum = $sIdx + 1; @endphp
                    @if($sIdx > 0)
                    <div class="wizard-divider" aria-hidden="true">
                        <svg viewBox="0 0 16 60" fill="none" preserveAspectRatio="none"><path d="M0 0 L14 30 L0 60" stroke="currentColor" stroke-width="1.5" /></svg>
                    </div>
                    @endif
                    <div class="wizard-step {{ $stepNum === 1 ? 'is-active' : '' }}" data-step="{{ $stepNum }}" id="tab_step_{{ $stepNum }}">
                        <div class="wizard-step-circle">{{ str_pad((string) $stepNum, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="wizard-step-title">
                            <span class="spmb-step-full">{{ $sec['title'] }}</span>
                            <span class="spmb-step-short">{{ $sec['short_title'] ?? $sec['title'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="spmb-progress-track">
                <div class="spmb-progress-bar" id="spmbProgressBar" style="width: {{ round(100 / max(1, $totalSteps)) }}%;"></div>
            </div>

            <div class="wizard-body">
        @endif

                @foreach($visibleSections as $sIdx => $sec)
                @php
                    $stepNum = $sIdx + 1;
                    $regularFields = [];
                    $dokumenFields = [];
                    foreach ($sec['fields'] as $fld) {
                        if (($fld['source'] ?? '') === 'builtin' && isset($dokumenMap[$fld['key']])) {
                            $dokumenFields[] = $fld;
                        } else {
                            $regularFields[] = $fld;
                        }
                    }
                @endphp

                <div class="{{ $layoutMode === 'wizard' ? ('wizard-step-pane ' . ($stepNum === 1 ? 'is-active' : '')) : 'spmb-single-section' }}"
                     id="pane_step_{{ $stepNum }}"
                     data-step-pane="{{ $stepNum }}">

                    @if($layoutMode === 'single' || !empty($sec['description']))
                    <div class="{{ $layoutMode === 'single' ? 'spmb-single-sec-head' : '' }}" style="{{ $layoutMode === 'wizard' ? 'margin-bottom:14px;' : '' }}">
                        @if($layoutMode === 'single')
                            <h2 class="spmb-single-sec-title">{{ $sec['title'] }}</h2>
                        @endif
                        @if(!empty($sec['description']))
                            <p class="spmb-single-sec-desc">{{ $sec['description'] }}</p>
                        @endif
                    </div>
                    @endif

                    @if(!empty($regularFields))
                    <div class="spmb-form-grid" style="margin-bottom: {{ !empty($dokumenFields) ? '20px' : '0' }};">
                        @foreach($regularFields as $fld)
                        @php
                            $isCustom = ($fld['source'] ?? '') === 'custom';
                            $key = $isCustom ? $fld['id'] : $fld['key'];
                            $inputName = $isCustom ? "custom_fields[{$key}]" : $key;
                            $errName = $isCustom ? "custom_fields.{$key}" : $key;
                            $inputId = $isCustom ? "cf_{$key}" : $key;
                            $isReq = !empty($fld['required']);
                            $type = $fld['type'] ?? 'text';
                            $oldVal = $isCustom ? old("custom_fields.{$key}") : old($key);
                            $isWide = !empty($fld['wide']) || in_array($type, ['textarea', 'radio', 'checkbox', 'file'], true);
                            $isNomorKip = !$isCustom && $key === 'nomor_kip';
                        @endphp

                        <div class="wizard-form-group"
                             @if($isNomorKip) id="wrapper_nomor_kip" @endif
                             style="{{ $isWide ? 'grid-column: 1 / -1;' : '' }}{{ $isNomorKip ? (old('penerima_kip') == '1' ? 'display:flex;' : 'display:none;') : '' }}">
                            <label class="wizard-label" for="{{ $inputId }}">
                                {{ $fld['label'] }}
                                @if($isReq)<span style="color:#EF4444;">*</span>@endif
                            </label>

                            {{-- FIELD KHUSUS: JENJANG --}}
                            @if(!$isCustom && $key === 'jenjang')
                                @php $jDisplayMap = $jenjangDisplayLabels ?? []; @endphp
                                @if(count($allowedJenjangs) === 1)
                                    <input type="hidden" name="jenjang" id="jenjang" value="{{ $allowedJenjangs[0] }}">
                                    <input type="text" class="wizard-input" value="{{ $jDisplayMap[$allowedJenjangs[0]] ?? $allowedJenjangs[0] }}" readonly style="background:#F8FAFC;">
                                @else
                                    <select name="jenjang" id="jenjang" class="wizard-select @error('jenjang') is-invalid @enderror" required>
                                        <option value="">Pilih {{ $fld['label'] ?: 'Jenjang' }}</option>
                                        @foreach($allowedJenjangs as $j)
                                            @php
                                                $isAvail = in_array($j, $availableJenjangs, true);
                                                $jText = $jDisplayMap[$j] ?? $j;
                                            @endphp
                                            <option value="{{ $j }}"
                                                    {{ old('jenjang') === $j && $isAvail ? 'selected' : '' }}
                                                    {{ $isAvail ? '' : 'disabled' }}>
                                                {{ $jText }}{{ $isAvail ? '' : ' (Kuota Penuh)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                            {{-- FIELD KHUSUS: PENERIMA KIP --}}
                            @elseif(!$isCustom && $key === 'penerima_kip')
                                <select name="penerima_kip" id="penerima_kip" class="wizard-select">
                                    <option value="0" {{ old('penerima_kip', '0') == '0' ? 'selected' : '' }}>Tidak</option>
                                    <option value="1" {{ old('penerima_kip') == '1' ? 'selected' : '' }}>Ya</option>
                                </select>

                            {{-- TIPE SELECT / DROPDOWN --}}
                            @elseif($type === 'select')
                                @php
                                    $defaultVal = null;
                                    if (!$isCustom && in_array($key, ['ayah_status', 'ibu_status'], true)) $defaultVal = 'Hidup';
                                    elseif (!$isCustom && $key === 'status_mondok') $defaultVal = 'Person';
                                    $selVal = $oldVal !== null ? $oldVal : $defaultVal;
                                @endphp
                                <select name="{{ $inputName }}" id="{{ $inputId }}" class="wizard-select @error($errName) is-invalid @enderror" @if($isReq) required @endif>
                                    @if($defaultVal === null)
                                        <option value="">Pilih</option>
                                    @endif
                                    @foreach(($fld['options'] ?? []) as $opt)
                                        <option value="{{ $opt }}" {{ (string) $selVal === (string) $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>

                            {{-- TIPE RADIO (PILIHAN GANDA) --}}
                            @elseif($type === 'radio')
                                <div class="spmb-choice-list" data-required-group="{{ $isReq ? '1' : '0' }}" data-group-label="{{ $fld['label'] }}">
                                    @foreach(($fld['options'] ?? []) as $oIdx => $opt)
                                    <label class="spmb-choice-item">
                                        <input type="radio" name="{{ $inputName }}" value="{{ $opt }}"
                                               {{ (string) $oldVal === (string) $opt ? 'checked' : '' }}
                                               @if($isReq && $oIdx === 0) required @endif>
                                        <span>{{ $opt }}</span>
                                    </label>
                                    @endforeach
                                </div>

                            {{-- TIPE CHECKBOX (KOTAK CENTANG MULTI PILIHAN) --}}
                            @elseif($type === 'checkbox')
                                @php $oldArr = is_array($oldVal) ? $oldVal : []; @endphp
                                <div class="spmb-choice-list" data-checkbox-group="{{ $isReq ? '1' : '0' }}" data-group-label="{{ $fld['label'] }}">
                                    @foreach(($fld['options'] ?? []) as $opt)
                                    <label class="spmb-choice-item">
                                        <input type="checkbox" name="{{ $inputName }}[]" value="{{ $opt }}"
                                               {{ in_array($opt, $oldArr, true) ? 'checked' : '' }}>
                                        <span>{{ $opt }}</span>
                                    </label>
                                    @endforeach
                                </div>

                            {{-- TIPE TEXTAREA (PARAGRAF) --}}
                            @elseif($type === 'textarea')
                                <textarea name="{{ $inputName }}" id="{{ $inputId }}" rows="3"
                                          class="wizard-textarea @error($errName) is-invalid @enderror"
                                          placeholder="{{ $fld['placeholder'] ?? '' }}"
                                          @if($isReq) required @endif>{{ $oldVal }}</textarea>

                            {{-- TIPE FILE KUSTOM --}}
                            @elseif($type === 'file')
                                <input type="file" name="{{ $inputName }}" id="{{ $inputId }}"
                                       accept="image/jpeg,image/png,image/webp,application/pdf"
                                       class="wizard-input @error($errName) is-invalid @enderror"
                                       style="padding:9px 12px;"
                                       @if($isReq) required @endif>
                                <div class="spmb-field-help">Format: JPG, PNG, WEBP, atau PDF (maks. 10 MB)</div>

                            {{-- TIPE DATE --}}
                            @elseif($type === 'date')
                                <input type="date" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ $oldVal }}"
                                       class="wizard-input @error($errName) is-invalid @enderror"
                                       @if($isReq) required @endif>

                            {{-- TIPE NUMBER --}}
                            @elseif($type === 'number')
                                <input type="number" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ $oldVal }}"
                                       class="wizard-input @error($errName) is-invalid @enderror"
                                       placeholder="{{ $fld['placeholder'] ?? '' }}"
                                       @if(!$isCustom && $key === 'anak_ke') min="1" @elseif(!$isCustom && $key === 'jumlah_saudara') min="0" @endif
                                       @if($isReq) required @endif>

                            {{-- TIPE TEXT (DEFAULT) --}}
                            @else
                                @php
                                    $textVal = $oldVal;
                                    if ($textVal === null && !$isCustom && $key === 'warga_negara') {
                                        $textVal = 'Indonesia';
                                    }
                                @endphp
                                <input type="text" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ $textVal }}"
                                       class="wizard-input @error($errName) is-invalid @enderror"
                                       placeholder="{{ $fld['placeholder'] ?? '' }}"
                                       @if(!$isCustom && $key === 'nik') maxlength="16" @elseif(!$isCustom && $key === 'nisn') maxlength="20" @endif
                                       @if($isReq) required @endif>
                            @endif

                            @if(!empty($fld['help']))
                                <div class="spmb-field-help">{{ $fld['help'] }}</div>
                            @endif
                            @error($errName)<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- DOKUMEN BAWAAN DENGAN UPLOAD KOMPRESI OTOMATIS --}}
                    @if(!empty($dokumenFields))
                    <div class="wizard-subcard" style="margin-top:4px;">
                        <div class="wizard-subcard-title">Dokumen</div>
                        <div class="gform-grid">
                            @foreach($dokumenFields as $df)
                            @php
                                $fotoKey = $df['key'];
                                $flagKey = $dokumenMap[$fotoKey];
                                $docReq = !empty($df['required']);
                            @endphp
                            <div class="gform-card" data-berkas-card>
                                <div class="gform-card-header">
                                    <label class="gform-label">
                                        <input type="checkbox" name="{{ $flagKey }}" value="1" data-berkas-check {{ old($flagKey) ? 'checked' : '' }} class="gform-checkbox">
                                        <span class="gform-title">{{ $df['label'] }} @if($docReq)<span style="color:#EF4444;">*</span>@endif</span>
                                    </label>
                                </div>
                                <div class="gform-upload-area">
                                    <input type="file" name="{{ $fotoKey }}" id="{{ $fotoKey }}"
                                           accept="image/jpeg,image/png,image/webp" class="upload-input" data-berkas-file
                                           @if($docReq) required @endif>
                                    <label for="{{ $fotoKey }}" class="gform-add-btn" data-upload-drop>
                                        <svg class="gform-upload-icon" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                        </svg>
                                        <span>Unggah foto</span>
                                    </label>
                                    <div class="gform-file-chip" data-upload-preview hidden>
                                        <img src="" alt="" class="gform-chip-thumb" data-upload-img>
                                        <div class="gform-chip-info">
                                            <span class="gform-chip-name" data-upload-name></span>
                                            <span class="gform-chip-size" data-upload-stats></span>
                                        </div>
                                        <button type="button" class="gform-chip-remove" data-upload-remove title="Hapus file">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                        </button>
                                    </div>
                                    <div class="upload-error" data-upload-error hidden></div>
                                    @if(!empty($df['help']))<div class="spmb-field-help">{{ $df['help'] }}</div>@endif
                                    @error($fotoKey)<div class="upload-error">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($layoutMode === 'wizard')
                    <div class="wizard-footer" style="{{ $stepNum === 1 ? 'justify-content:flex-end;' : '' }}">
                        @if($stepNum > 1)
                        <button type="button" class="btn-wizard-prev js-btn-prev" data-prev="{{ $stepNum - 1 }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            Sebelumnya
                        </button>
                        @endif

                        @if($stepNum < $totalSteps)
                        <button type="button" class="btn-wizard-next js-btn-next" data-next="{{ $stepNum + 1 }}">
                            Selanjutnya
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                        @else
                        <button type="submit" class="btn-wizard-next" style="padding:12px 28px;">
                            Kirim Pendaftaran
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach

        @if($layoutMode === 'wizard')
            </div>
        </div>
        @else
        <div style="display:flex; justify-content:flex-end; margin-top:8px;">
            <button type="submit" class="btn-wizard-next" style="padding:13px 30px; font-size:15px;">
                Kirim Pendaftaran
            </button>
        </div>
        @endif
    </form>
@endif

@endsection

@if($isOpen)
@push('scripts')
<script src="{{ asset('js/upload-berkas.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var layoutMode = @json($layoutMode);
    var totalSteps = {{ (int) $totalSteps }};
    var currentStep = 1;

    function validatePane(paneEl) {
        if (!paneEl) return true;
        var controls = paneEl.querySelectorAll('input, select, textarea');
        for (var i = 0; i < controls.length; i++) {
            var el = controls[i];
            if (el.disabled || el.type === 'hidden') continue;
            if (el.offsetParent === null && el.type !== 'file') continue;
            if (el.hasAttribute('required')) {
                if (el.type === 'radio') {
                    var radios = paneEl.querySelectorAll('input[type="radio"][name="' + CSS.escape(el.name) + '"]');
                    var anyChecked = Array.prototype.some.call(radios, function (r) { return r.checked; });
                    if (!anyChecked) {
                        el.focus();
                        el.reportValidity();
                        return false;
                    }
                } else if (el.type === 'file') {
                    if (!el.files || el.files.length === 0) {
                        el.reportValidity();
                        return false;
                    }
                } else if (!String(el.value || '').trim()) {
                    el.focus();
                    el.reportValidity();
                    return false;
                }
            }
        }

        // Validasi grup checkbox wajib
        var cbGroups = paneEl.querySelectorAll('[data-checkbox-group="1"]');
        for (var g = 0; g < cbGroups.length; g++) {
            var group = cbGroups[g];
            var checked = group.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length === 0) {
                var lbl = group.getAttribute('data-group-label') || 'Pertanyaan';
                alert(lbl + ' wajib dipilih minimal satu.');
                var firstCb = group.querySelector('input[type="checkbox"]');
                if (firstCb) firstCb.focus();
                return false;
            }
        }

        return true;
    }

    if (layoutMode === 'wizard' && totalSteps > 1) {
        var panes = [];
        var tabs = [];
        for (var s = 1; s <= totalSteps; s++) {
            panes.push(document.getElementById('pane_step_' + s));
            tabs.push(document.getElementById('tab_step_' + s));
        }

        function goToStep(step) {
            if (step < 1 || step > totalSteps) return;
            panes.forEach(function (p, idx) {
                if (p) p.classList.toggle('is-active', idx + 1 === step);
            });
            tabs.forEach(function (t, idx) {
                if (!t) return;
                t.classList.remove('is-active', 'is-completed');
                if (idx + 1 === step) t.classList.add('is-active');
                else if (idx + 1 < step) t.classList.add('is-completed');
            });
            var bar = document.getElementById('spmbProgressBar');
            if (bar) bar.style.width = Math.round((step / totalSteps) * 100) + '%';
            currentStep = step;
            var stepper = document.getElementById('wizardStepper');
            if (stepper) stepper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        tabs.forEach(function (t, idx) {
            if (t) t.addEventListener('click', function () {
                var target = idx + 1;
                if (target > currentStep && !validatePane(panes[currentStep - 1])) return;
                goToStep(target);
            });
        });

        document.querySelectorAll('.js-btn-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!validatePane(panes[currentStep - 1])) return;
                goToStep(parseInt(this.getAttribute('data-next'), 10));
            });
        });

        document.querySelectorAll('.js-btn-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                goToStep(parseInt(this.getAttribute('data-prev'), 10));
            });
        });

        @if ($errors->any())
            goToStep({{ (int) $errorStepTarget }});
        @endif
    }

    // Validasi checkbox wajib saat submit pada mode single / final step
    var formEl = document.getElementById('formSpmbPublic');
    if (formEl) {
        formEl.addEventListener('submit', function (ev) {
            var cbGroups = formEl.querySelectorAll('[data-checkbox-group="1"]');
            for (var g = 0; g < cbGroups.length; g++) {
                var group = cbGroups[g];
                if (group.querySelectorAll('input[type="checkbox"]:checked').length === 0) {
                    ev.preventDefault();
                    var lbl = group.getAttribute('data-group-label') || 'Pertanyaan';
                    alert(lbl + ' wajib dipilih minimal satu.');
                    return false;
                }
            }
        });
    }

    var selectKip = document.getElementById('penerima_kip');
    var wrapperKip = document.getElementById('wrapper_nomor_kip');
    if (selectKip && wrapperKip) {
        selectKip.addEventListener('change', function () {
            wrapperKip.style.display = this.value === '1' ? 'flex' : 'none';
        });
    }

    // Update chip lokasi seleksi saat pendaftar memilih jenjang/lembaga
    var lokasiMap = @json($lokasiMap);
    var jenjangEl = document.getElementById('jenjang');
    var chipLokasi = document.getElementById('chipLokasiSeleksi');
    var chipLokasiText = document.getElementById('chipLokasiText');
    if (jenjangEl && chipLokasi && chipLokasiText) {
        var updateLokasiChip = function () {
            var val = jenjangEl.value;
            var loc = (val && lokasiMap[val]) ? lokasiMap[val] : '';
            if (loc) {
                chipLokasiText.textContent = loc;
                chipLokasi.style.display = '';
            }
        };
        jenjangEl.addEventListener('change', updateLokasiChip);
        updateLokasiChip();
    }
});
</script>
@endpush
@endif
