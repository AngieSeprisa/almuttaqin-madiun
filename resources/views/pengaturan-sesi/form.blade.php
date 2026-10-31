@extends('layouts.app')

@php
    $isEdit = isset($sesi);
    $title = $isEdit ? 'Edit Sesi Presensi: ' . $sesi->nama_sesi : 'Tambah Sesi Presensi Baru';

    $currentKategori = old('target_kategori', $sesi->target_kategori ?? 'semua_santri');
    $currentFilter = old('target_filter', $sesi->target_filter ?? '');

    $initialAllocations = [];
    if (isset($allocations) && $allocations->isNotEmpty()) {
        foreach ($allocations as $a) {
            $initialAllocations[] = [
                'device_id' => $a->device_id ?? '',
                'target_kategori' => $a->target_kategori ?? 'semua',
                'target_gender' => $a->target_gender ?? 'ALL',
                'target_filter' => $a->target_filter ?? '',
                'selected_kamars' => ($a->target_kategori === 'kamar' && $a->target_filter) ? array_values(array_filter(array_map('trim', explode(',', $a->target_filter)))) : [],
                'selected_kelas' => ($a->target_kategori === 'kelas_pengajian' && $a->target_filter) ? array_values(array_filter(array_map('trim', explode(',', $a->target_filter)))) : [],
                'selected_sekolah' => ($a->target_kategori === 'kelas_sekolah' && $a->target_filter) ? array_values(array_filter(array_map('trim', explode(',', $a->target_filter)))) : [],
            ];
        }
    } elseif ($isEdit) {
        $legacyGender = 'ALL';
        $cleanFilter = $currentFilter;
        if (preg_match('/\[Gender:\s*Putra\]/i', $currentFilter)) {
            $legacyGender = 'L';
            $cleanFilter = trim(preg_replace('/\[Gender:[^\]]+\]/i', '', $currentFilter));
        } elseif (preg_match('/\[Gender:\s*Putri\]/i', $currentFilter)) {
            $legacyGender = 'P';
            $cleanFilter = trim(preg_replace('/\[Gender:[^\]]+\]/i', '', $currentFilter));
        }

        $mappedCat = match($currentKategori) {
            'kamar_khusus' => 'kamar',
            'sekolah' => 'kelas_sekolah',
            'kelas_pengajian' => 'kelas_pengajian',
            'pegawai' => 'pegawai',
            default => 'semua',
        };

        $initialAllocations[] = [
            'device_id' => $sesi->device_id_target ?? '',
            'target_kategori' => $mappedCat,
            'target_gender' => $legacyGender,
            'target_filter' => $cleanFilter,
            'selected_kamars' => ($mappedCat === 'kamar' && $cleanFilter) ? array_values(array_filter(array_map('trim', explode(',', $cleanFilter)))) : [],
            'selected_kelas' => ($mappedCat === 'kelas_pengajian' && $cleanFilter) ? array_values(array_filter(array_map('trim', explode(',', $cleanFilter)))) : [],
            'selected_sekolah' => ($mappedCat === 'kelas_sekolah' && $cleanFilter) ? array_values(array_filter(array_map('trim', explode(',', $cleanFilter)))) : [],
        ];
    } else {
        $initialAllocations[] = [
            'device_id' => '',
            'target_kategori' => 'semua',
            'target_gender' => 'ALL',
            'target_filter' => '',
            'selected_kamars' => [],
            'selected_kelas' => [],
            'selected_sekolah' => [],
        ];
    }

    $jamMulai = old('jam_mulai', isset($sesi->jam_mulai) ? substr((string) $sesi->jam_mulai, 0, 5) : '04:30');
    $jamSelesai = old('jam_selesai', isset($sesi->jam_selesai) ? substr((string) $sesi->jam_selesai, 0, 5) : '05:30');
    $toleransiMenit = old('toleransi_menit', $sesi->toleransi_menit ?? 15);
@endphp

@section('title', $title)

@section('header-actions')
<a href="{{ route('pengaturan.sesi') }}" class="btn btn-secondary" title="Kembali ke Daftar Sesi & Perangkat">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;flex-shrink:0;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Kembali ke Daftar Sesi</span>
</a>
@endsection

@section('content')
<div class="sesi-form-page-container" x-data="sesiMultiDeviceManager({
    initialAllocations: @js($initialAllocations),
    allKamars: @js($kamars->values()),
    allKelasPengajian: @js($kelasPengajians->pluck('nama_kelas')->values()),
})">

    {{-- Error Banner --}}
    @if ($errors->any())
    <div class="pk-alert pk-alert-danger" style="margin-bottom: 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pk-alert-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <div style="font-weight: 700; margin-bottom: 4px;">Terdapat kesalahan pada input formulir:</div>
            <ul style="margin: 0; padding-left: 20px; font-size: 12.5px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ $isEdit ? route('pengaturan.sesi.update', $sesi->id) : route('pengaturan.sesi.store') }}"
          method="POST"
          id="formSesiFull"
          @submit="beforeSubmitForm($event)">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Serialized JSON untuk alokasi multi-perangkat --}}
        <input type="hidden" name="allocations_json" id="allocationsJsonInput" :value="JSON.stringify(serializeAllocations())">

        {{-- Fallback input legacy agar kompatibel dengan validasi controller lama --}}
        <input type="hidden" name="device_id_target" :value="allocations[0]?.device_id || ''">
        <input type="hidden" name="target_kategori" :value="getLegacyTargetKategori()">
        <input type="hidden" name="target_filter" id="computedTargetFilter" :value="getLegacyTargetFilter()">

        <div class="sesi-form-sections-grid">

            {{-- ======================================================== --}}
            {{-- SECTION 1: INFORMASI DASAR & RENTANG WAKTU TAP           --}}
            {{-- ======================================================== --}}
            <div class="sesi-card-section">
                <div class="sesi-card-header">
                    <div class="sesi-card-icon sesi-card-icon-blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 class="sesi-card-title">Informasi Dasar &amp; Rentang Waktu</h2>
                        <p class="sesi-card-subtitle">Nama sesi, kode unik perangkat ESP32, kategori, dan batas waktu kehadiran.</p>
                    </div>
                </div>

                <div class="sesi-card-body">
                    <div class="sesi-form-row-2">
                        {{-- Nama Sesi --}}
                        <div class="sesi-field-group">
                            <label for="nama_sesi" class="sesi-field-label">
                                Nama Sesi Presensi <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nama_sesi"
                                   id="nama_sesi"
                                   required
                                   maxlength="100"
                                   value="{{ old('nama_sesi', $sesi->nama_sesi ?? '') }}"
                                   placeholder="Contoh: Sholat Dzuhur Berjamaah, Pengajian Sore"
                                   class="sesi-input @error('nama_sesi') sesi-input-error @enderror">
                            <span class="sesi-field-hint">Nama sesi yang muncul pada dashboard monitoring dan log kehadiran harian.</span>
                        </div>

                        {{-- Kode Sesi --}}
                        <div class="sesi-field-group">
                            <label for="kode_sesi" class="sesi-field-label">
                                Kode Sesi (Unik) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="kode_sesi"
                                   id="kode_sesi"
                                   required
                                   maxlength="30"
                                   value="{{ old('kode_sesi', $sesi->kode_sesi ?? '') }}"
                                   oninput="this.value = this.value.toUpperCase().replace(/\s+/g, '_')"
                                   placeholder="Contoh: SHOLAT_DZUHUR, PENGAJIAN_SORE"
                                   class="sesi-input font-mono font-bold @error('kode_sesi') sesi-input-error @enderror">
                            <span class="sesi-field-hint">Kode singkat tanpa spasi, ditampilkan pada layar LCD 16x2 mesin ESP32.</span>
                        </div>
                    </div>

                    <div class="sesi-form-row-3" style="margin-top: 14px;">
                        {{-- Kategori Sesi --}}
                        <div class="sesi-field-group">
                            <label for="kategori" class="sesi-field-label">
                                Kategori Aktivitas <span class="text-danger">*</span>
                            </label>
                            @php
                                $selectedKat = old('kategori', $sesi->kategori ?? 'sholat');
                            @endphp
                            <select name="kategori" id="kategori" class="sesi-select" data-prefix-icon="academic-cap">
                                <option value="sholat" {{ $selectedKat === 'sholat' ? 'selected' : '' }}>Sholat Berjamaah</option>
                                <option value="pengajian" {{ $selectedKat === 'pengajian' ? 'selected' : '' }}>Pengajian / Halaqah</option>
                                <option value="sekolah" {{ $selectedKat === 'sekolah' ? 'selected' : '' }}>Kegiatan Sekolah (KBM)</option>
                                <option value="makan" {{ $selectedKat === 'makan' ? 'selected' : '' }}>Makan Santri (Dapur)</option>
                                <option value="pegawai" {{ $selectedKat === 'pegawai' ? 'selected' : '' }}>Presensi Khusus Pegawai</option>
                            </select>
                            <span class="sesi-field-hint">Kategori sesi untuk pengelompokan rekap absensi.</span>
                        </div>

                        {{-- Jam Mulai --}}
                        <div class="sesi-field-group">
                            <label for="jam_mulai" class="sesi-field-label">
                                Jam Mulai <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   name="jam_mulai"
                                   id="jam_mulai"
                                   required
                                   value="{{ $jamMulai }}"
                                   class="sesi-input font-mono @error('jam_mulai') sesi-input-error @enderror">
                            <span class="sesi-field-hint">Waktu dimulainya jendela tap.</span>
                        </div>

                        {{-- Jam Selesai --}}
                        <div class="sesi-field-group">
                            <label for="jam_selesai" class="sesi-field-label">
                                Jam Selesai <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   name="jam_selesai"
                                   id="jam_selesai"
                                   required
                                   value="{{ $jamSelesai }}"
                                   class="sesi-input font-mono @error('jam_selesai') sesi-input-error @enderror">
                            <span class="sesi-field-hint">Batas akhir tap kartu presensi.</span>
                        </div>
                    </div>

                    {{-- Toleransi Keterlambatan --}}
                    <div class="sesi-field-group" style="margin-top: 14px; max-width: 320px;">
                        <label for="toleransi_menit" class="sesi-field-label">
                            Toleransi Keterlambatan (Menit)
                        </label>
                        <div class="sesi-input-suffix-wrap">
                            <input type="number"
                                   name="toleransi_menit"
                                   id="toleransi_menit"
                                   min="0"
                                   max="240"
                                   value="{{ $toleransiMenit }}"
                                   class="sesi-input font-mono font-bold">
                            <span class="sesi-input-suffix">menit</span>
                        </div>
                        <span class="sesi-field-hint">Santri tap lewat dari jam mulai + toleransi ditandai "Terlambat".</span>
                    </div>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- SECTION 2: ALOKASI MULTI-PERANGKAT ESP32 & TARGET        --}}
            {{-- ======================================================== --}}
            <div class="sesi-card-section">
                <div class="sesi-card-header" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div class="sesi-card-icon sesi-card-icon-amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                                <rect x="9" y="9" width="6" height="6"></rect>
                                <line x1="9" y1="1" x2="9" y2="4"></line>
                                <line x1="15" y1="1" x2="15" y2="4"></line>
                                <line x1="9" y1="20" x2="9" y2="23"></line>
                                <line x1="15" y1="20" x2="15" y2="23"></line>
                                <line x1="20" y1="9" x2="23" y2="9"></line>
                                <line x1="20" y1="14" x2="23" y2="14"></line>
                                <line x1="1" y1="9" x2="4" y2="9"></line>
                                <line x1="1" y1="14" x2="4" y2="14"></line>
                            </svg>
                        </div>
                        <div>
                            <h2 class="sesi-card-title">Alokasi Perangkat Mesin ESP32</h2>
                            <p class="sesi-card-subtitle">
                                Target Peserta &amp; Hak Akses Presensi: Satu sesi dapat dialokasikan ke beberapa mesin ESP32 dengan peruntukan target santri masing-masing (misal: Mesin Putra &amp; Mesin Putri).
                            </p>
                        </div>
                    </div>

                    <button type="button"
                            @click="addAllocation()"
                            class="btn btn-secondary"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 15px; height: 15px;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>+ Tambah Titik Mesin</span>
                    </button>
                </div>

                <div class="sesi-card-body" style="padding-top: 10px;">

                    {{-- Daftar Baris Alokasi Mesin (Repeater) --}}
                    <div style="display: flex; flex-direction: column; gap: 18px;">
                        <template x-for="(alloc, idx) in allocations" :key="idx">
                            <div class="sesi-alloc-row-card">
                                {{-- Row Top Header Bar --}}
                                <div class="sesi-alloc-row-header">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span class="sesi-alloc-num-badge" x-text="'Titik Mesin #' + (idx + 1)"></span>
                                        <template x-if="alloc.device_id">
                                            <span class="sesi-alloc-dev-pill" x-text="alloc.device_id"></span>
                                        </template>
                                        <template x-if="!alloc.device_id">
                                            <span class="sesi-alloc-dev-pill" style="background:#F1F5F9; color:#475569; border-color:#CBD5E1;">Semua Mesin (Global)</span>
                                        </template>

                                        <template x-if="alloc.target_gender === 'L'">
                                            <span class="sesi-alloc-gender-pill" style="background:#EFF4FF; color:#0052FF; border-color:#BFDBFE;">Khusus Putra</span>
                                        </template>
                                        <template x-if="alloc.target_gender === 'P'">
                                            <span class="sesi-alloc-gender-pill" style="background:#FDF2F8; color:#BE185D; border-color:#FBCFE8;">Khusus Putri</span>
                                        </template>
                                    </div>

                                    <button type="button"
                                            x-show="allocations.length > 1"
                                            @click="removeAllocation(idx)"
                                            class="sesi-alloc-delete-btn"
                                            title="Hapus Titik Mesin Ini">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 15px; height: 15px;">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                        <span>Hapus Titik</span>
                                    </button>
                                </div>

                                {{-- Row Form Body --}}
                                <div class="sesi-alloc-row-body">
                                    <div class="sesi-form-row-2">
                                        {{-- 1. Pilihan Mesin ESP32 --}}
                                        <div class="sesi-field-group">
                                            <label class="sesi-field-label">
                                                Pilih Perangkat Mesin ESP32 <span class="text-danger">*</span>
                                            </label>
                                            <select x-model="alloc.device_id" class="sesi-select" data-prefix-icon="cpu-chip">
                                                @if($devices->isEmpty())
                                                    <option value="">Semua Perangkat / Global (Belum ada alat terdaftar)</option>
                                                @else
                                                    <option value="">Semua Perangkat / Global (Berlaku di Semua Mesin Aktif)</option>
                                                    @foreach($devices as $dev)
                                                        <option value="{{ $dev->device_id }}">
                                                            {{ $dev->nama_lokasi }} ({{ $dev->device_id }}) &bull; Area {{ ucfirst($dev->kategori_area ?? 'Pondok') }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <span class="sesi-field-hint">Alat RFID yang dipasang di pintu/lokasi untuk memindai kartu pada titik ini.</span>
                                        </div>

                                        {{-- 2. Tipe Peruntukan / Target Peserta --}}
                                        <div class="sesi-field-group">
                                            <label class="sesi-field-label">
                                                Peruntukan / Target Peserta <span class="text-danger">*</span>
                                            </label>
                                            <select x-model="alloc.target_kategori" class="sesi-select" data-prefix-icon="user">
                                                <option value="semua">Semua Santri (Tanpa Batasan Ruangan)</option>
                                                <option value="gender">Berdasarkan Gender (Khusus Putra / Putri)</option>
                                                <option value="kamar">Berdasarkan Kamar Santri</option>
                                                <option value="kelas_pengajian">Berdasarkan Kelas Pengajian</option>
                                                <option value="kelas_sekolah">Berdasarkan Jenjang Sekolah (SMP / SMA)</option>
                                                <option value="pegawai">Guru &amp; Pegawai Pondok/Sekolah</option>
                                            </select>
                                            <span class="sesi-field-hint">Kelompok peserta yang berhak tap pada mesin ini.</span>
                                        </div>
                                    </div>

                                    {{-- 3. Filter Gender Hak Akses Mesin --}}
                                    <div class="sesi-alloc-gender-row" style="margin-top: 14px;">
                                        <label class="sesi-field-label" style="margin-bottom: 6px;">
                                            Filter Gender Mesin:
                                        </label>
                                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                            <button type="button"
                                                    @click="alloc.target_gender = 'ALL'"
                                                    :class="alloc.target_gender === 'ALL' ? 'sesi-gender-pill-active' : 'sesi-gender-pill'">
                                                Semua Gender (Putra &amp; Putri)
                                            </button>
                                            <button type="button"
                                                    @click="alloc.target_gender = 'L'"
                                                    :class="alloc.target_gender === 'L' ? 'sesi-gender-pill-active-blue' : 'sesi-gender-pill'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 14px; height: 14px;">
                                                    <circle cx="10" cy="14" r="5"></circle>
                                                    <line x1="19" y1="5" x2="13.5" y2="10.5"></line>
                                                    <polyline points="15 5 19 5 19 9"></polyline>
                                                </svg>
                                                <span>Khusus Santri Putra (L)</span>
                                            </button>
                                            <button type="button"
                                                    @click="alloc.target_gender = 'P'"
                                                    :class="alloc.target_gender === 'P' ? 'sesi-gender-pill-active-pink' : 'sesi-gender-pill'">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 14px; height: 14px;">
                                                    <circle cx="12" cy="9" r="5"></circle>
                                                    <line x1="12" y1="14" x2="12" y2="21"></line>
                                                    <line x1="9" y1="18" x2="15" y2="18"></line>
                                                </svg>
                                                <span>Khusus Santri Putri (P)</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- 4. CONDITIONAL SUB-SECTION: BERDASARKAN KAMAR --}}
                                    <div x-show="alloc.target_kategori === 'kamar'" style="margin-top: 14px;">
                                        <div class="sesi-target-header-row" style="margin-bottom: 8px;">
                                            <label class="sesi-field-label" style="margin: 0;">Pilih Kamar Sasaran Mesin Ini:</label>
                                            <div class="sesi-selection-helpers">
                                                <button type="button" class="sesi-helper-btn" @click="selectAllKamarsForAlloc(alloc)">Pilih Semua</button>
                                                <span class="sesi-helper-dot">&bull;</span>
                                                <button type="button" class="sesi-helper-btn text-danger" @click="clearKamarsForAlloc(alloc)">Kosongkan</button>
                                            </div>
                                        </div>

                                        <div class="sesi-checkbox-chips-grid">
                                            @foreach($kamars as $kmr)
                                                <label class="sesi-chip-label" :class="isKamarSelectedInAlloc(alloc, '{{ $kmr }}') ? 'sesi-chip-label-selected' : ''">
                                                    <input type="checkbox"
                                                           class="fi-ta-checkbox"
                                                           :checked="isKamarSelectedInAlloc(alloc, '{{ $kmr }}')"
                                                           @change="toggleKamarInAlloc(alloc, '{{ $kmr }}')">
                                                    <span class="sesi-chip-text">{{ $kmr }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- 5. CONDITIONAL SUB-SECTION: BERDASARKAN KELAS PENGAJIAN --}}
                                    <div x-show="alloc.target_kategori === 'kelas_pengajian'" style="margin-top: 14px;">
                                        <div class="sesi-target-header-row" style="margin-bottom: 8px;">
                                            <label class="sesi-field-label" style="margin: 0;">Pilih Kelas Pengajian Sasaran:</label>
                                            <div class="sesi-selection-helpers">
                                                <button type="button" class="sesi-helper-btn" @click="selectAllKelasForAlloc(alloc)">Pilih Semua</button>
                                                <span class="sesi-helper-dot">&bull;</span>
                                                <button type="button" class="sesi-helper-btn text-danger" @click="clearKelasForAlloc(alloc)">Kosongkan</button>
                                            </div>
                                        </div>

                                        <div class="sesi-checkbox-chips-grid">
                                            @foreach($kelasPengajians as $kp)
                                                <label class="sesi-chip-label" :class="isKelasSelectedInAlloc(alloc, '{{ $kp->nama_kelas }}') ? 'sesi-chip-label-selected' : ''">
                                                    <input type="checkbox"
                                                           class="fi-ta-checkbox"
                                                           :checked="isKelasSelectedInAlloc(alloc, '{{ $kp->nama_kelas }}')"
                                                           @change="toggleKelasInAlloc(alloc, '{{ $kp->nama_kelas }}')">
                                                    <span class="sesi-chip-text">{{ $kp->nama_kelas }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- 6. CONDITIONAL SUB-SECTION: BERDASARKAN SEKOLAH --}}
                                    <div x-show="alloc.target_kategori === 'kelas_sekolah'" style="margin-top: 14px;">
                                        <label class="sesi-field-label" style="margin-bottom: 8px;">Pilih Jenjang Sekolah Sasaran:</label>
                                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                            <label class="sesi-chip-label" :class="alloc.selected_sekolah.includes('SMP') ? 'sesi-chip-label-selected' : ''">
                                                <input type="checkbox"
                                                       class="fi-ta-checkbox"
                                                       :checked="alloc.selected_sekolah.includes('SMP')"
                                                       @change="toggleSekolahInAlloc(alloc, 'SMP')">
                                                <span class="sesi-chip-text">SMP AL MUTTAQIN</span>
                                            </label>
                                            <label class="sesi-chip-label" :class="alloc.selected_sekolah.includes('SMA') ? 'sesi-chip-label-selected' : ''">
                                                <input type="checkbox"
                                                       class="fi-ta-checkbox"
                                                       :checked="alloc.selected_sekolah.includes('SMA')"
                                                       @change="toggleSekolahInAlloc(alloc, 'SMA')">
                                                <span class="sesi-chip-text">SMA AL MUTTAQIN</span>
                                            </label>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tombol Tambah Titik di Bawah List --}}
                    <div style="margin-top: 18px; text-align: center;">
                        <button type="button"
                                @click="addAllocation()"
                                class="btn btn-secondary"
                                style="display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; font-size: 13px; font-weight: 700; border-radius: 9px; width: 100%; justify-content: center; border-style: dashed; background: #F8FAFC;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 16px; height: 16px; color: #0052FF;">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span style="color: #0052FF;">+ Tambah Titik Mesin ESP32 Lainnya</span>
                        </button>
                    </div>

                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- BOTTOM ACTION BUTTONS: SIMPAN (PRIMARY) & BATAL          --}}
            {{-- ======================================================== --}}
            <div class="sesi-bottom-actions-card">
                <a href="{{ route('pengaturan.sesi') }}" class="sesi-btn-cancel">
                    Batal / Kembali
                </a>

                <button type="submit" class="sesi-btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="sesi-btn-submit-icon">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>{{ $isEdit ? 'Simpan Perubahan Sesi' : 'Simpan Sesi Presensi Baru' }}</span>
                </button>
            </div>

        </div>
    </form>
</div>

<style>
/* ================================================================
   PAGE STYLES: DEDICATED FULL-PAGE FORM PENGATURAN SESI (FILAMENT 3.x)
   ================================================================ */
/* Header Action Button: Kembali ke Daftar Sesi */
.page-header-actions .btn-secondary,
a.btn.btn-secondary {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 8px 16px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    border-radius: 10px !important;
    background: #FFFFFF !important;
    color: var(--text-primary, #1E293B) !important;
    border: 1px solid var(--border-light, #E2E8F0) !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all 0.15s ease !important;
}

.page-header-actions .btn-secondary:hover,
a.btn.btn-secondary:hover {
    background: var(--bg-page, #F8FAFC) !important;
    border-color: var(--border-medium, #CBD5E1) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px 0 rgba(0, 0, 0, 0.08) !important;
    color: var(--text-primary, #0F172A) !important;
}

.dark-mode .page-header-actions .btn-secondary,
.dark-mode a.btn.btn-secondary {
    background: #1E293B !important;
    color: #F8FAFC !important;
    border-color: #334155 !important;
}

.dark-mode .page-header-actions .btn-secondary:hover,
.dark-mode a.btn.btn-secondary:hover {
    background: #334155 !important;
    border-color: #475569 !important;
}

.sesi-form-page-container {
    width: 100%;
    max-width: 1080px;
    margin: 0 auto;
    padding-bottom: 40px;
}

.sesi-form-sections-grid {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.sesi-card-section {
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    transition: box-shadow 0.15s ease;
}
.sesi-card-section:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.sesi-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    border-bottom: 1px solid #F1F5F9;
    background: #FAFAFA;
}

.sesi-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.sesi-card-icon svg { width: 20px; height: 20px; }

.sesi-card-icon-blue {
    background: #EFF4FF;
    color: #0052FF;
    border: 1px solid rgba(0, 82, 255, 0.15);
}
.sesi-card-icon-amber {
    background: #FFFBEB;
    color: #D97706;
    border: 1px solid rgba(217, 119, 6, 0.15);
}

.sesi-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    letter-spacing: -0.01em;
}
.dark-mode .sesi-card-title { color: #F8FAFC; }

.sesi-card-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 2px 0 0 0;
}

.sesi-card-body {
    padding: 24px;
}

.sesi-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}
@media (max-width: 640px) {
    .sesi-form-row-2 { grid-template-columns: 1fr; }
}

.sesi-form-row-3 {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 18px;
}
@media (max-width: 768px) {
    .sesi-form-row-3 { grid-template-columns: 1fr; }
}

.sesi-field-group {
    display: flex;
    flex-direction: column;
}

.sesi-field-label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.dark-mode .sesi-field-label { color: #CBD5E1; }

.text-danger { color: #EF4444; }

.sesi-input,
.sesi-select {
    width: 100%;
    height: 42px;
    padding: 8px 13px;
    border-radius: 9px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #0F172A;
    font-size: 13.5px;
    outline: none;
    transition: all 0.15s ease;
}
.sesi-input:focus,
.sesi-select:focus {
    border-color: #0052FF;
    box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
}
.sesi-input-error {
    border-color: #EF4444 !important;
}

.sesi-field-hint {
    font-size: 11.5px;
    color: #64748B;
    margin-top: 4px;
}

.sesi-input-suffix-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.sesi-input-suffix-wrap .sesi-input {
    padding-right: 56px;
}
.sesi-input-suffix {
    position: absolute;
    right: 12px;
    font-size: 12.5px;
    font-weight: 600;
    color: #64748B;
    pointer-events: none;
}

/* REPEATER ROW STYLES */
.sesi-alloc-row-card {
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #FFFFFF;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    transition: all 0.15s ease;
}
.sesi-alloc-row-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
}

.sesi-alloc-row-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.sesi-alloc-num-badge {
    font-size: 12px;
    font-weight: 700;
    color: #0F172A;
    background: #E2E8F0;
    padding: 3px 8px;
    border-radius: 6px;
}

.sesi-alloc-dev-pill {
    font-mono font-size: 11px;
    font-weight: 700;
    background: #EFF4FF;
    color: #0052FF;
    border: 1px solid #BFDBFE;
    padding: 2px 7px;
    border-radius: 5px;
}

.sesi-alloc-gender-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 5px;
    border: 1px solid transparent;
}

.sesi-alloc-delete-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    font-size: 11.5px;
    font-weight: 600;
    color: #EF4444;
    background: transparent;
    border: none;
    cursor: pointer;
    border-radius: 6px;
    transition: background 0.12s ease;
}
.sesi-alloc-delete-btn:hover {
    background: #FEF2F2;
}

.sesi-alloc-row-body {
    padding: 18px;
}

.sesi-gender-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background: #F8FAFC;
    border: 1px solid #CBD5E1;
    cursor: pointer;
    transition: all 0.12s ease;
}
.sesi-gender-pill:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
}

.sesi-gender-pill-active {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #0F172A;
    background: #E2E8F0;
    border: 1px solid #94A3B8;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.sesi-gender-pill-active-blue {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #0052FF;
    background: #EFF4FF;
    border: 1px solid #0052FF;
    box-shadow: 0 1px 3px rgba(0, 82, 255, 0.15);
}

.sesi-gender-pill-active-pink {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #BE185D;
    background: #FDF2F8;
    border: 1px solid #DB2777;
    box-shadow: 0 1px 3px rgba(219, 39, 119, 0.15);
}

.sesi-target-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sesi-selection-helpers {
    display: flex;
    align-items: center;
    gap: 6px;
}

.sesi-helper-btn {
    background: transparent;
    border: none;
    color: #0052FF;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
}
.sesi-helper-btn:hover { text-decoration: underline; }
.sesi-helper-btn.text-danger { color: #EF4444; }
.sesi-helper-dot { color: #CBD5E1; font-size: 11px; }

.sesi-checkbox-chips-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    background: #FAFAFA;
}

.sesi-chip-label {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    font-size: 12px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    user-select: none;
    transition: all 0.12s ease;
}
.sesi-chip-label:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
}
.sesi-chip-label-selected {
    background: #EFF4FF !important;
    border-color: #0052FF !important;
    color: #0052FF !important;
    font-weight: 600 !important;
}

.sesi-chip-text { font-size: 12px; }

/* BOTTOM ACTION BAR */
.sesi-bottom-actions-card {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-radius: 14px;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.sesi-btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 9px 20px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    color: #475569;
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    text-decoration: none;
    transition: all 0.14s ease;
}
.sesi-btn-cancel:hover {
    background: #F8FAFC;
    border-color: #94A3B8;
    color: #0F172A;
}

.sesi-btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 24px;
    border-radius: 9px;
    font-size: 13.5px;
    font-weight: 600;
    color: #FFFFFF;
    background: #0052FF;
    border: 1px solid #0052FF;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0, 82, 255, 0.25);
    transition: all 0.14s ease;
}
.sesi-btn-submit:hover { background: #0043D4; border-color: #0043D4; }
.sesi-btn-submit-icon { width: 17px; height: 17px; }
</style>

<script>
function sesiMultiDeviceManager(config) {
    return {
        allocations: Array.isArray(config.initialAllocations) && config.initialAllocations.length > 0
            ? config.initialAllocations
            : [{
                device_id: '',
                target_kategori: 'semua',
                target_gender: 'ALL',
                target_filter: '',
                selected_kamars: [],
                selected_kelas: [],
                selected_sekolah: []
            }],
        allKamars: config.allKamars || [],
        allKelasPengajian: config.allKelasPengajian || [],

        addAllocation() {
            this.allocations.push({
                device_id: '',
                target_kategori: 'semua',
                target_gender: 'ALL',
                target_filter: '',
                selected_kamars: [],
                selected_kelas: [],
                selected_sekolah: []
            });
        },

        removeAllocation(index) {
            if (this.allocations.length > 1) {
                this.allocations.splice(index, 1);
            }
        },

        // Helpers Kamar
        isKamarSelectedInAlloc(alloc, kamar) {
            return alloc.selected_kamars && alloc.selected_kamars.includes(kamar);
        },
        toggleKamarInAlloc(alloc, kamar) {
            if (!alloc.selected_kamars) alloc.selected_kamars = [];
            const idx = alloc.selected_kamars.indexOf(kamar);
            if (idx >= 0) {
                alloc.selected_kamars.splice(idx, 1);
            } else {
                alloc.selected_kamars.push(kamar);
            }
        },
        selectAllKamarsForAlloc(alloc) {
            alloc.selected_kamars = [...this.allKamars];
        },
        clearKamarsForAlloc(alloc) {
            alloc.selected_kamars = [];
        },

        // Helpers Kelas Pengajian
        isKelasSelectedInAlloc(alloc, kls) {
            return alloc.selected_kelas && alloc.selected_kelas.includes(kls);
        },
        toggleKelasInAlloc(alloc, kls) {
            if (!alloc.selected_kelas) alloc.selected_kelas = [];
            const idx = alloc.selected_kelas.indexOf(kls);
            if (idx >= 0) {
                alloc.selected_kelas.splice(idx, 1);
            } else {
                alloc.selected_kelas.push(kls);
            }
        },
        selectAllKelasForAlloc(alloc) {
            alloc.selected_kelas = [...this.allKelasPengajian];
        },
        clearKelasForAlloc(alloc) {
            alloc.selected_kelas = [];
        },

        // Helpers Sekolah
        toggleSekolahInAlloc(alloc, jenjang) {
            if (!alloc.selected_sekolah) alloc.selected_sekolah = [];
            const idx = alloc.selected_sekolah.indexOf(jenjang);
            if (idx >= 0) {
                alloc.selected_sekolah.splice(idx, 1);
            } else {
                alloc.selected_sekolah.push(jenjang);
            }
        },

        // Serialisasi data alokasi sebelum kirim form
        serializeAllocations() {
            return this.allocations.map(a => {
                let filterStr = '';
                if (a.target_kategori === 'kamar') {
                    filterStr = (a.selected_kamars || []).join(', ');
                } else if (a.target_kategori === 'kelas_pengajian') {
                    filterStr = (a.selected_kelas || []).join(', ');
                } else if (a.target_kategori === 'kelas_sekolah') {
                    filterStr = (a.selected_sekolah || []).join(', ');
                } else if (a.target_kategori === 'gender') {
                    filterStr = a.target_gender === 'L' ? 'Putra' : (a.target_gender === 'P' ? 'Putri' : 'Semua');
                }

                return {
                    device_id: a.device_id || null,
                    target_kategori: a.target_kategori || 'semua',
                    target_gender: a.target_gender || 'ALL',
                    target_filter: filterStr
                };
            });
        },

        getLegacyTargetKategori() {
            const first = this.allocations[0];
            if (!first) return 'semua_santri';
            if (first.target_kategori === 'kamar') return 'kamar_khusus';
            if (first.target_kategori === 'kelas_sekolah') return 'sekolah';
            if (first.target_kategori === 'kelas_pengajian') return 'kelas_pengajian';
            if (first.target_kategori === 'pegawai') return 'pegawai';
            return 'semua_santri';
        },

        getLegacyTargetFilter() {
            const first = this.allocations[0];
            if (!first) return '';
            if (first.target_kategori === 'kamar') return (first.selected_kamars || []).join(', ');
            if (first.target_kategori === 'kelas_sekolah') return (first.selected_sekolah || []).join(', ');
            if (first.target_kategori === 'kelas_pengajian') {
                const kls = (first.selected_kelas || []).join(', ');
                if (first.target_gender === 'L') return kls ? `${kls} [Gender: Putra]` : '[Gender: Putra]';
                if (first.target_gender === 'P') return kls ? `${kls} [Gender: Putri]` : '[Gender: Putri]';
                return kls;
            }
            if (first.target_kategori === 'gender') {
                return first.target_gender === 'L' ? '[Gender: Putra]' : (first.target_gender === 'P' ? '[Gender: Putri]' : '');
            }
            return '';
        },

        beforeSubmitForm(event) {
            const jsonVal = JSON.stringify(this.serializeAllocations());
            const jsonInput = document.getElementById('allocationsJsonInput');
            if (jsonInput) {
                jsonInput.value = jsonVal;
            }
            const computedVal = this.getLegacyTargetFilter();
            const filterInput = document.getElementById('computedTargetFilter');
            if (filterInput) {
                filterInput.value = computedVal;
            }
        }
    };
}
</script>
@endsection
