@extends('layouts.app')

@php
    $isEdit = isset($sesi);
    $title = $isEdit ? 'Edit Sesi Presensi: ' . $sesi->nama_sesi : 'Tambah Sesi Presensi Baru';

    $currentKategori = old('target_kategori', $sesi->target_kategori ?? 'semua_santri');
    $currentFilter = old('target_filter', $sesi->target_filter ?? '');

    $initKamars = [];
    $initSekolah = [];
    $initKelasPengajian = [];
    $initGender = 'semua';

    if ($currentKategori === 'kamar_khusus' && $currentFilter) {
        $initKamars = array_values(array_filter(array_map('trim', explode(',', $currentFilter))));
    } elseif ($currentKategori === 'sekolah' && $currentFilter) {
        $initSekolah = array_values(array_filter(array_map('trim', explode(',', $currentFilter))));
    } elseif ($currentKategori === 'kelas_pengajian' && $currentFilter) {
        if (preg_match('/\[Gender:\s*Putra\]/i', $currentFilter)) {
            $initGender = 'putra';
        } elseif (preg_match('/\[Gender:\s*Putri\]/i', $currentFilter)) {
            $initGender = 'putri';
        }
        $cleanFilter = trim(preg_replace('/\[Gender:[^\]]+\]/i', '', $currentFilter));
        if ($cleanFilter) {
            $initKelasPengajian = array_values(array_filter(array_map('trim', explode(',', $cleanFilter))));
        }
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
<div class="sesi-form-page-container" x-data="sesiFormManager({
    targetKategori: '{{ $currentKategori }}',
    selectedKamars: @js($initKamars),
    selectedSekolah: @js($initSekolah),
    selectedKelasPengajian: @js($initKelasPengajian),
    selectedGender: '{{ $initGender }}',
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

        {{-- Hidden Input for computed target_filter --}}
        <input type="hidden" name="target_filter" id="computedTargetFilter" :value="computeTargetFilterValue()">

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
                            <label for="inputNamaSesi" class="sesi-field-label">
                                Nama Sesi Presensi <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="nama_sesi"
                                   id="inputNamaSesi"
                                   value="{{ old('nama_sesi', $sesi->nama_sesi ?? '') }}"
                                   placeholder="Contoh: Sholat Subuh & Kultum, Pengajian Tahfidz Sore"
                                   required
                                   maxlength="100"
                                   class="sesi-input-text"
                                   autocomplete="off">
                            <span class="sesi-field-hint">Nama sesi yang muncul pada dashboard monitoring dan log kehadiran harian.</span>
                        </div>

                        {{-- Kode Sesi --}}
                        <div class="sesi-field-group">
                            <label for="inputKodeSesi" class="sesi-field-label">
                                Kode Sesi (Unik) <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="kode_sesi"
                                   id="inputKodeSesi"
                                   value="{{ old('kode_sesi', $sesi->kode_sesi ?? '') }}"
                                   placeholder="Contoh: SHOLAT_SUBUH, PENGAJIAN_SORE"
                                   required
                                   maxlength="30"
                                   class="sesi-input-text font-mono"
                                   autocomplete="off"
                                   oninput="this.value = this.value.toUpperCase().replace(/\s+/g, '_')">
                            <span class="sesi-field-hint">Kode singkat tanpa spasi, ditampilkan pada layar LCD 16x2 mesin ESP32.</span>
                        </div>
                    </div>

                    <div class="sesi-form-row-3" style="margin-top: 14px;">
                        {{-- Kategori Sesi --}}
                        <div class="sesi-field-group">
                            <label for="selectKategori" class="sesi-field-label">
                                Kategori Aktivitas <span class="text-danger">*</span>
                            </label>
                            <select name="kategori" id="selectKategori" class="sesi-select" data-prefix-icon="clock" required>
                                @php
                                    $kategoriVal = old('kategori', $sesi->kategori ?? 'sholat');
                                @endphp
                                <option value="sholat" {{ $kategoriVal === 'sholat' ? 'selected' : '' }}>Sholat Berjamaah</option>
                                <option value="pengajian" {{ $kategoriVal === 'pengajian' ? 'selected' : '' }}>Pengajian / Halaqah</option>
                                <option value="sekolah" {{ $kategoriVal === 'sekolah' ? 'selected' : '' }}>Kegiatan Sekolah</option>
                                <option value="makan" {{ $kategoriVal === 'makan' ? 'selected' : '' }}>Makan Bersama</option>
                                <option value="pegawai" {{ $kategoriVal === 'pegawai' ? 'selected' : '' }}>Presensi Pegawai / Guru</option>
                            </select>
                            <span class="sesi-field-hint">Kategori sesi untuk pengelompokan rekap absensi.</span>
                        </div>

                        {{-- Jam Mulai & Jam Selesai --}}
                        <div class="sesi-field-group">
                            <label for="inputJamMulai" class="sesi-field-label">
                                Jam Mulai <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   name="jam_mulai"
                                   id="inputJamMulai"
                                   value="{{ $jamMulai }}"
                                   required
                                   class="sesi-input-text font-mono">
                            <span class="sesi-field-hint">Waktu dimulainya jendela tap.</span>
                        </div>

                        <div class="sesi-field-group">
                            <label for="inputJamSelesai" class="sesi-field-label">
                                Jam Selesai <span class="text-danger">*</span>
                            </label>
                            <input type="time"
                                   name="jam_selesai"
                                   id="inputJamSelesai"
                                   value="{{ $jamSelesai }}"
                                   required
                                   class="sesi-input-text font-mono">
                            <span class="sesi-field-hint">Batas akhir tap kartu presensi.</span>
                        </div>
                    </div>

                    <div class="sesi-form-row-2" style="margin-top: 14px;">
                        {{-- Toleransi Menit --}}
                        <div class="sesi-field-group">
                            <label for="inputToleransi" class="sesi-field-label">
                                Toleransi Keterlambatan (Menit)
                            </label>
                            <div class="sesi-input-suffix-wrap">
                                <input type="number"
                                       name="toleransi_menit"
                                       id="inputToleransi"
                                       min="0"
                                       max="240"
                                       value="{{ $toleransiMenit }}"
                                       class="sesi-input-text font-mono">
                                <span class="sesi-input-suffix">Menit</span>
                            </div>
                            <span class="sesi-field-hint">Tap sebelum batas toleransi dicatat Tepat Waktu; sesudahnya dicatat Terlambat.</span>
                        </div>

                        <div class="sesi-field-group" style="justify-content: center;">
                            <div class="sesi-info-pill">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span>Status Kehadiran: Tepat Waktu (Hijau), Terlambat (Kuning), Ditolak jika di luar jam.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- SECTION 2: ALOKASI PERANGKAT ESP32                       --}}
            {{-- ======================================================== --}}
            <div class="sesi-card-section">
                <div class="sesi-card-header">
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
                        <p class="sesi-card-subtitle">Tetapkan lokasi mesin reader RFID khusus atau buat sesi berlaku global untuk seluruh mesin.</p>
                    </div>
                </div>

                <div class="sesi-card-body">
                    <div class="sesi-field-group">
                        <label for="selectPerangkat" class="sesi-field-label">
                            Pilih Perangkat ESP32 Target
                        </label>
                        @php
                            $selectedDev = old('device_id_target', $sesi->device_id_target ?? '');
                        @endphp
                        <select name="device_id_target"
                                id="selectPerangkat"
                                class="sesi-select"
                                data-prefix-icon="cpu-chip"
                                data-searchable="true"
                                data-search-prompt="Cari serial ESP32 atau nama lokasi...">
                            @if($devices->isEmpty())
                                <option value="">Semua Perangkat / Global (Belum ada alat terdaftar)</option>
                            @else
                                <option value="" {{ $selectedDev === '' ? 'selected' : '' }}>
                                    Semua Perangkat / Global (Berlaku di Semua Mesin Aktif)
                                </option>
                                @foreach($devices as $dev)
                                    <option value="{{ $dev->device_id }}" {{ $selectedDev === $dev->device_id ? 'selected' : '' }}>
                                        {{ $dev->nama_lokasi }} ({{ $dev->device_id }}) &bull; Area {{ ucfirst($dev->kategori_area ?? 'Pondok') }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="sesi-field-hint">
                            Jika dipilih lokasi tertentu (misal: Masjid Timur), santri hanya dapat melakukan presensi pada mesin yang terpasang di lokasi tersebut.
                        </span>
                    </div>
                </div>
            </div>

            {{-- ======================================================== --}}
            {{-- SECTION 3: TARGET PESERTA & HAK AKSES                    --}}
            {{-- ======================================================== --}}
            <div class="sesi-card-section">
                <div class="sesi-card-header">
                    <div class="sesi-card-icon sesi-card-icon-emerald">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="sesi-card-title">Target Peserta &amp; Hak Akses Presensi</h2>
                        <p class="sesi-card-subtitle">Batasi peserta presensi berdasarkan kamar santri, kelas pengajian dan gender, atau jenjang sekolah.</p>
                    </div>
                </div>

                <div class="sesi-card-body">
                    {{-- Dropdown Tipe Target --}}
                    <div class="sesi-field-group">
                        <label for="selectTargetKategori" class="sesi-field-label">
                            Tipe Target Peserta <span class="text-danger">*</span>
                        </label>
                        <select name="target_kategori"
                                id="selectTargetKategori"
                                x-model="targetKategori"
                                class="sesi-select"
                                data-prefix-icon="user"
                                required>
                            <option value="semua_santri">Semua Santri (Global)</option>
                            <option value="kamar_khusus">Berdasarkan Kamar (Multi-Pilih Kamar)</option>
                            <option value="kelas_pengajian">Berdasarkan Kelas Pengajian &amp; Gender</option>
                            <option value="sekolah">Berdasarkan Kelas Sekolah (SMP / SMA)</option>
                            <option value="pegawai">Guru &amp; Pegawai Pondok / Sekolah</option>
                        </select>
                    </div>

                    {{-- SUB-SECTION A: MODE SEMUA SANTRI / PEGAWAI --}}
                    <div x-show="targetKategori === 'semua_santri' || targetKategori === 'pegawai'"
                         x-transition
                         class="sesi-target-info-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sesi-info-box-icon">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <div>
                            <strong x-text="targetKategori === 'pegawai' ? 'Presensi Khusus Pegawai & Guru' : 'Presensi Berlaku Untuk Semua Santri'"></strong>
                            <p style="margin: 2px 0 0 0; color: #64748B; font-size: 12.5px;">
                                Seluruh kartu RFID yang berstatus aktif akan diizinkan tap pada rentang waktu sesi ini tanpa pembatasan ruangan.
                            </p>
                        </div>
                    </div>

                    {{-- SUB-SECTION B: BERDASARKAN KAMAR (MULTI-SELECT) --}}
                    <div x-show="targetKategori === 'kamar_khusus'" x-transition class="sesi-target-filter-block">
                        <div class="sesi-target-header-row">
                            <label class="sesi-field-label" style="margin: 0;">Pilih Kamar Sasaran (Multi-Pilih)</label>
                            <div class="sesi-selection-helpers">
                                <button type="button" class="sesi-helper-btn" @click="selectAllKamars()">Pilih Semua</button>
                                <span class="sesi-helper-dot">&bull;</span>
                                <button type="button" class="sesi-helper-btn text-danger" @click="clearKamars()">Kosongkan</button>
                            </div>
                        </div>

                        {{-- Search Input Kamar --}}
                        <div class="sesi-search-pill-box">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sesi-search-icon">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text"
                                   x-model="searchKamarQuery"
                                   placeholder="Ketik untuk memfilter nama kamar..."
                                   class="sesi-search-input">
                            <span class="sesi-counter-badge" x-text="selectedKamars.length + ' dipilih'"></span>
                        </div>

                        {{-- Selected Badges Bar --}}
                        <div class="sesi-badges-container" x-show="selectedKamars.length > 0">
                            <template x-for="kamar in selectedKamars" :key="kamar">
                                <span class="sesi-pill-badge sesi-pill-amber">
                                    <span x-text="kamar"></span>
                                    <button type="button" class="sesi-pill-remove" @click="toggleKamar(kamar)">&times;</button>
                                </span>
                            </template>
                        </div>

                        {{-- Kamar Checkbox Grid --}}
                        <div class="sesi-checkbox-grid">
                            <template x-for="kamar in filteredKamars" :key="kamar">
                                <label class="sesi-check-card" :class="selectedKamars.includes(kamar) ? 'is-checked' : ''">
                                    <input type="checkbox"
                                           :value="kamar"
                                           :checked="selectedKamars.includes(kamar)"
                                           @change="toggleKamar(kamar)"
                                           class="fi-ta-checkbox">
                                    <span x-text="kamar" class="sesi-check-text"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- SUB-SECTION C: BERDASARKAN KELAS PENGAJIAN & GENDER --}}
                    <div x-show="targetKategori === 'kelas_pengajian'" x-transition class="sesi-target-filter-block">
                        {{-- Filter Gender --}}
                        <div class="sesi-field-group" style="margin-bottom: 18px;">
                            <label class="sesi-field-label">Target Gender Santri <span class="text-danger">*</span></label>
                            <div class="sesi-gender-segmented">
                                <label class="sesi-gender-opt" :class="selectedGender === 'semua' ? 'is-active' : ''">
                                    <input type="radio" name="target_filter_gender" value="semua" x-model="selectedGender" class="sr-only">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>
                                    <span>Semua Santri (Putra &amp; Putri)</span>
                                </label>
                                <label class="sesi-gender-opt" :class="selectedGender === 'putra' ? 'is-active' : ''">
                                    <input type="radio" name="target_filter_gender" value="putra" x-model="selectedGender" class="sr-only">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="5"></circle>
                                        <line x1="12" y1="1" x2="12" y2="7"></line>
                                        <line x1="12" y1="17" x2="12" y2="23"></line>
                                    </svg>
                                    <span>Khusus Santri Putra (Laki-laki)</span>
                                </label>
                                <label class="sesi-gender-opt" :class="selectedGender === 'putri' ? 'is-active' : ''">
                                    <input type="radio" name="target_filter_gender" value="putri" x-model="selectedGender" class="sr-only">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="5"></circle>
                                        <line x1="12" y1="17" x2="12" y2="23"></line>
                                        <line x1="9" y1="20" x2="15" y2="20"></line>
                                    </svg>
                                    <span>Khusus Santri Putri (Perempuan)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Multi-Select Kelas Pengajian --}}
                        <div class="sesi-target-header-row">
                            <label class="sesi-field-label" style="margin: 0;">Pilih Kelas Pengajian Sasaran (Multi-Pilih)</label>
                            <div class="sesi-selection-helpers">
                                <button type="button" class="sesi-helper-btn" @click="selectAllKelasPengajian()">Pilih Semua</button>
                                <span class="sesi-helper-dot">&bull;</span>
                                <button type="button" class="sesi-helper-btn text-danger" @click="clearKelasPengajian()">Kosongkan</button>
                            </div>
                        </div>

                        <div class="sesi-badges-container" x-show="selectedKelasPengajian.length > 0">
                            <template x-for="kls in selectedKelasPengajian" :key="kls">
                                <span class="sesi-pill-badge sesi-pill-blue">
                                    <span x-text="kls"></span>
                                    <button type="button" class="sesi-pill-remove" @click="toggleKelasPengajian(kls)">&times;</button>
                                </span>
                            </template>
                        </div>

                        <div class="sesi-checkbox-grid">
                            <template x-for="kls in allKelasPengajian" :key="kls">
                                <label class="sesi-check-card" :class="selectedKelasPengajian.includes(kls) ? 'is-checked' : ''">
                                    <input type="checkbox"
                                           :value="kls"
                                           :checked="selectedKelasPengajian.includes(kls)"
                                           @change="toggleKelasPengajian(kls)"
                                           class="fi-ta-checkbox">
                                    <span x-text="kls" class="sesi-check-text"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- SUB-SECTION D: BERDASARKAN KELAS / JENJANG SEKOLAH --}}
                    <div x-show="targetKategori === 'sekolah'" x-transition class="sesi-target-filter-block">
                        <label class="sesi-field-label">Pilih Jenjang Sekolah Target</label>
                        <div class="sesi-checkbox-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            @foreach(['SMP' => 'Sekolah Menengah Pertama (SMP)', 'SMA' => 'Sekolah Menengah Atas (SMA)', 'ALL' => 'Semua Siswa Sekolah (SMP & SMA)'] as $jCode => $jLabel)
                                <label class="sesi-check-card" :class="selectedSekolah.includes('{{ $jCode }}') ? 'is-checked' : ''">
                                    <input type="checkbox"
                                           value="{{ $jCode }}"
                                           :checked="selectedSekolah.includes('{{ $jCode }}')"
                                           @change="toggleSekolah('{{ $jCode }}')"
                                           class="fi-ta-checkbox">
                                    <div style="display:flex; flex-direction:column;">
                                        <span class="sesi-check-text font-bold">{{ $jCode }}</span>
                                        <span style="font-size: 11.5px; color: #64748B;">{{ $jLabel }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
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
.dark-mode .sesi-card-section {
    background: #141C28;
    border-color: #263346;
}

.sesi-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    border-bottom: 1px solid #F1F5F9;
    background: #FAFAFC;
}
.dark-mode .sesi-card-header {
    background: #101722;
    border-bottom-color: #1F2937;
}

.sesi-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.sesi-card-icon svg { width: 20px; height: 20px; }
.sesi-card-icon-blue { background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0, 82, 255, 0.16); }
.sesi-card-icon-amber { background: #FFFBEB; color: #D97706; border: 1px solid rgba(217, 119, 6, 0.18); }
.sesi-card-icon-emerald { background: #ECFDF5; color: #059669; border: 1px solid rgba(5, 150, 105, 0.18); }

.sesi-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #0F172A;
    margin: 0;
    line-height: 1.25;
}
.dark-mode .sesi-card-title { color: #F8FAFC; }

.sesi-card-subtitle {
    font-size: 12.5px;
    color: #64748B;
    margin: 3px 0 0 0;
    line-height: 1.35;
}

.sesi-card-body {
    padding: 22px 24px;
}

.sesi-form-row-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}
.sesi-form-row-3 {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}
@media (max-width: 768px) {
    .sesi-form-row-2, .sesi-form-row-3 {
        grid-template-columns: 1fr;
    }
}

.sesi-field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.sesi-field-label {
    font-size: 13px;
    font-weight: 600;
    color: #1E293B;
}
.dark-mode .sesi-field-label { color: #E2E8F0; }

.sesi-field-hint {
    font-size: 11.5px;
    color: #64748B;
    line-height: 1.35;
}

.sesi-input-text, .sesi-select {
    width: 100%;
    height: 40px;
    padding: 0 13px;
    border-radius: 9px;
    border: 1px solid #D1D5DB;
    background: #FFFFFF;
    color: #0F172A;
    font-size: 13.5px;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}
.sesi-input-text:focus, .sesi-select:focus {
    border-color: #0052FF;
    box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.14);
}
.dark-mode .sesi-input-text, .dark-mode .sesi-select {
    background: #1E293B;
    border-color: #334155;
    color: #F8FAFC;
}

.sesi-input-suffix-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.sesi-input-suffix {
    position: absolute;
    right: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #64748B;
    pointer-events: none;
}

.sesi-info-pill {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 14px;
    background: #F8FAFC;
    border: 1px dashed #CBD5E1;
    border-radius: 9px;
    font-size: 12px;
    color: #475569;
}
.sesi-info-pill svg { width: 17px; height: 17px; color: #0052FF; flex-shrink: 0; }

/* Target Info Box */
.sesi-target-info-box {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    border-radius: 10px;
    color: #065F46;
    font-size: 13px;
    margin-top: 14px;
}
.sesi-info-box-icon { width: 20px; height: 20px; color: #059669; flex-shrink: 0; margin-top: 2px; }

/* Target Filter Sub Block */
.sesi-target-filter-block {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px dashed #E2E8F0;
}
.dark-mode .sesi-target-filter-block { border-top-color: #334155; }

.sesi-target-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}
.sesi-selection-helpers {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}
.sesi-helper-btn {
    background: transparent;
    border: none;
    font-size: 12px;
    font-weight: 600;
    color: #0052FF;
    cursor: pointer;
    padding: 0;
}
.sesi-helper-btn:hover { text-decoration: underline; }
.sesi-helper-btn.text-danger { color: #EF4444; }
.sesi-helper-dot { color: #CBD5E1; }

.sesi-search-pill-box {
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}
.sesi-search-icon {
    position: absolute;
    left: 12px;
    width: 15px;
    height: 15px;
    color: #94A3B8;
}
.sesi-search-input {
    width: 100%;
    height: 38px;
    padding: 0 95px 0 36px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #F8FAFC;
    font-size: 13px;
    outline: none;
}
.sesi-search-input:focus {
    border-color: #0052FF;
    background: #FFFFFF;
}
.sesi-counter-badge {
    position: absolute;
    right: 10px;
    padding: 3px 8px;
    border-radius: 6px;
    background: #E2E8F0;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
}

.sesi-badges-container {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 14px;
}
.sesi-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.sesi-pill-amber { background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; }
.sesi-pill-blue { background: #EFF4FF; color: #0052FF; border: 1px solid rgba(0, 82, 255, 0.2); }
.sesi-pill-remove {
    background: transparent;
    border: none;
    color: inherit;
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    font-weight: 700;
}

.sesi-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
    max-height: 240px;
    overflow-y: auto;
    padding: 4px;
}
.sesi-check-card {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 12px;
    border-radius: 8px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    cursor: pointer;
    transition: all 0.14s ease;
    user-select: none;
}
.sesi-check-card:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
}
.sesi-check-card.is-checked {
    border-color: #0052FF;
    background: #EFF4FF;
}
.dark-mode .sesi-check-card {
    background: #1E293B;
    border-color: #334155;
}
.dark-mode .sesi-check-card.is-checked {
    background: rgba(0, 82, 255, 0.18);
    border-color: #60A5FA;
}
.sesi-check-text {
    font-size: 12.5px;
    font-weight: 500;
    color: #1E293B;
}
.dark-mode .sesi-check-text { color: #F1F5F9; }

/* Gender Segmented */
.sesi-gender-segmented {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}
@media (max-width: 640px) {
    .sesi-gender-segmented { grid-template-columns: 1fr; }
}
.sesi-gender-opt {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 14px;
    border-radius: 9px;
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
}
.sesi-gender-opt svg { width: 17px; height: 17px; }
.sesi-gender-opt:hover { border-color: #CBD5E1; background: #F8FAFC; }
.sesi-gender-opt.is-active {
    border-color: #0052FF;
    background: #0052FF;
    color: #FFFFFF;
    box-shadow: 0 2px 6px rgba(0, 82, 255, 0.25);
}

/* Bottom Action Bar */
.sesi-bottom-actions-card {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    background: #FFFFFF;
    border: 1px solid #E5E7EB;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.dark-mode .sesi-bottom-actions-card {
    background: #141C28;
    border-color: #263346;
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
    border: 1px solid #D1D5DB;
    text-decoration: none;
    transition: all 0.14s ease;
}
.sesi-btn-cancel:hover { background: #F1F5F9; color: #0F172A; }
.dark-mode .sesi-btn-cancel {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
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
function sesiFormManager(config) {
    return {
        targetKategori: config.targetKategori || 'semua_santri',
        selectedKamars: Array.isArray(config.selectedKamars) ? config.selectedKamars : [],
        selectedSekolah: Array.isArray(config.selectedSekolah) ? config.selectedSekolah : [],
        selectedKelasPengajian: Array.isArray(config.selectedKelasPengajian) ? config.selectedKelasPengajian : [],
        selectedGender: config.selectedGender || 'semua',
        searchKamarQuery: '',
        allKamars: config.allKamars || [],
        allKelasPengajian: config.allKelasPengajian || [],

        get filteredKamars() {
            if (!this.searchKamarQuery.trim()) return this.allKamars;
            const q = this.searchKamarQuery.toLowerCase().trim();
            return this.allKamars.filter(k => k.toLowerCase().includes(q));
        },

        toggleKamar(kamar) {
            const idx = this.selectedKamars.indexOf(kamar);
            if (idx >= 0) {
                this.selectedKamars.splice(idx, 1);
            } else {
                this.selectedKamars.push(kamar);
            }
        },

        selectAllKamars() {
            this.selectedKamars = [...this.allKamars];
        },

        clearKamars() {
            this.selectedKamars = [];
        },

        toggleKelasPengajian(kls) {
            const idx = this.selectedKelasPengajian.indexOf(kls);
            if (idx >= 0) {
                this.selectedKelasPengajian.splice(idx, 1);
            } else {
                this.selectedKelasPengajian.push(kls);
            }
        },

        selectAllKelasPengajian() {
            this.selectedKelasPengajian = [...this.allKelasPengajian];
        },

        clearKelasPengajian() {
            this.selectedKelasPengajian = [];
        },

        toggleSekolah(jenjang) {
            const idx = this.selectedSekolah.indexOf(jenjang);
            if (idx >= 0) {
                this.selectedSekolah.splice(idx, 1);
            } else {
                this.selectedSekolah.push(jenjang);
            }
        },

        computeTargetFilterValue() {
            if (this.targetKategori === 'kamar_khusus') {
                return this.selectedKamars.join(', ');
            }
            if (this.targetKategori === 'sekolah') {
                return this.selectedSekolah.join(', ');
            }
            if (this.targetKategori === 'kelas_pengajian') {
                const klsStr = this.selectedKelasPengajian.join(', ');
                if (this.selectedGender === 'putra') {
                    return klsStr ? `${klsStr} [Gender: Putra]` : '[Gender: Putra]';
                }
                if (this.selectedGender === 'putri') {
                    return klsStr ? `${klsStr} [Gender: Putri]` : '[Gender: Putri]';
                }
                return klsStr;
            }
            return '';
        },

        beforeSubmitForm(event) {
            const computedVal = this.computeTargetFilterValue();
            const hiddenInput = document.getElementById('computedTargetFilter');
            if (hiddenInput) {
                hiddenInput.value = computedVal;
            }
        }
    };
}
</script>
@endsection

