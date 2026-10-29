@php
    $sesisJson = $sesis->map(function ($s) {
        return [
            'id' => $s->id,
            'kode_sesi' => $s->kode_sesi,
            'nama_sesi' => $s->nama_sesi,
            'jam_mulai' => substr((string) $s->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $s->jam_selesai, 0, 5),
            'toleransi_menit' => (int) ($s->toleransi_menit ?? 15),
            'device_id_target' => $s->device_id_target ?? '',
            'nama_lokasi' => $s->nama_lokasi ?? '',
            'kategori' => strtolower((string) ($s->kategori ?? 'sholat')),
            'target_kategori' => $s->target_kategori ?? 'semua_santri',
            'target_filter' => $s->target_filter ?? '',
        ];
    })->values();

    $devicesJson = $allDevices->map(function ($d) {
        return [
            'id' => $d->id,
            'device_id' => $d->device_id,
            'nama_lokasi' => $d->nama_lokasi ?? '',
            'kategori_area' => strtolower((string) ($d->kategori_area ?? 'pondok')),
            'ip_address' => $d->ip_address ?? '',
            'status_aktif' => (bool) $d->status_aktif,
        ];
    })->values();

    $initialTab = request('tab') === 'devices' ? 'devices' : 'sesi';
@endphp

@extends('layouts.presensi-nfc', [
    'nfcPage' => 'pengaturan',
    'activeSidebarKey' => $initialTab,
    'sesiCount' => $sesis->count(),
    'deviceCount' => $allDevices->count(),
])

@section('title', 'Pengaturan Sesi & Perangkat Presensi RFID')

@section('body-attrs')
x-data="pengaturanSesiApp()" x-init="initApp()" @nfc-tab-switch.window="switchTab($event.detail.tab)"
@endsection

@section('content')
<div class="space-y-6">

    <!-- ======================================================= -->
    <!-- FILAMENT V4 PAGE HEADER: BREADCRUMB + TITLE + BLUE CTA  -->
    <!-- ======================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-[13px] font-medium text-gray-500 mb-1.5" aria-label="Breadcrumb">
                <span>Pengaturan NFC</span>
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-700 font-semibold"
                      x-text="activeTab === 'devices' ? 'Perangkat ESP32' : 'Jadwal Sesi'">Jadwal Sesi</span>
            </nav>

            <!-- Bold Page Title -->
            <h1 class="text-2xl sm:text-[28px] font-extrabold tracking-tight text-gray-950 leading-tight">
                <span x-show="activeTab === 'sesi'">Pengaturan Sesi &amp; Perangkat Presensi</span>
                <span x-show="activeTab === 'devices'" x-cloak>Perangkat Mesin NFC (ESP32)</span>
            </h1>

            <p class="text-xs sm:text-[13px] text-gray-500 mt-1"
               x-text="activeTab === 'sesi'
                   ? 'Kelola jadwal presensi otomatis, toleransi keterlambatan, alokasi mesin ESP32, dan target peserta.'
                   : 'Kelola nomor seri hardware ESP32 yang terdeteksi otomatis dan tetapkan nama lokasi pemasangannya.'">
                Kelola jadwal presensi otomatis, toleransi keterlambatan, alokasi mesin ESP32, dan target peserta.
            </p>
        </div>

        <!-- Primary Action Button: Electric Blue (#0052FF) -->
        <div class="flex items-center gap-2.5 shrink-0">
            <button type="button"
                    x-show="activeTab === 'sesi'"
                    @click="openCreateModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] active:bg-[#0038B3] text-white text-sm font-semibold shadow-sm shadow-[#0052FF]/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Tambah Sesi Baru</span>
            </button>

            <button type="button"
                    x-show="activeTab === 'devices'"
                    x-cloak
                    @click="openCreateDeviceModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] active:bg-[#0038B3] text-white text-sm font-semibold shadow-sm shadow-[#0052FF]/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
                <span>Deteksi / Daftarkan ESP32</span>
            </button>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- NOTIFIKASI FLASH SUKSES / VALIDASI ERROR (LIGHT MODE)   -->
    <!-- ======================================================= -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 shadow-2xs">
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="p-1 rounded-lg text-emerald-600 hover:bg-emerald-100 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition
             class="flex items-start justify-between gap-3 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 shadow-2xs">
            <div class="flex items-start gap-3">
                <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="text-sm">
                    @if(session('error'))
                        <div class="font-semibold">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="font-semibold mb-1">Mohon periksa kembali input Anda:</div>
                        <ul class="list-disc list-inside text-xs text-rose-700 space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <button type="button" @click="show = false" class="p-1 rounded-lg text-rose-600 hover:bg-rose-100 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- ======================================================= -->
    <!-- 4 KARTU RINGKASAN STATISTIK (CLEAN WHITE CARDS)         -->
    <!-- ======================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Total Sesi -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-start justify-between">
            <div>
                <div class="text-xs font-semibold text-gray-500">Total Sesi Presensi</div>
                <div class="text-2xl sm:text-3xl font-extrabold text-gray-950 font-mono mt-1">{{ $sesis->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Jadwal otomatis terdaftar</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-[#EFF4FF] border border-[#0052FF]/15 text-[#0052FF] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        <!-- Sesi Aktif Jam Ini -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-start justify-between">
            <div class="min-w-0 pr-2">
                <div class="text-xs font-semibold text-emerald-600 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>Sesi Aktif Jam Ini</span>
                </div>
                <div class="text-base sm:text-lg font-extrabold text-gray-950 truncate mt-1.5"
                     x-text="activeSessionsNow().length > 0 ? activeSessionsNow()[0].nama_sesi : 'Standby / Di Luar Jam'"></div>
                <div class="text-xs text-gray-500 mt-1"
                     x-text="activeSessionsNow().length > 0 ? (activeSessionsNow().length + ' sesi sedang berjalan aktif') : 'Menunggu jadwal berikutnya'"></div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
        </div>

        <!-- Mesin ESP32 Aktif -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-start justify-between">
            <div>
                <div class="text-xs font-semibold text-gray-500">Mesin ESP32 Aktif</div>
                <div class="text-2xl sm:text-3xl font-extrabold text-gray-950 font-mono mt-1">
                    {{ $devices->count() }}
                    <span class="text-sm font-medium text-gray-400">/ {{ $allDevices->count() }}</span>
                </div>
                <div class="text-xs text-gray-500 mt-1">Titik pembaca kartu NFC</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-[#EFF4FF] border border-[#0052FF]/15 text-[#0052FF] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
            </div>
        </div>

        <!-- Kamar Santri Terdata -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 sm:p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)] flex items-start justify-between">
            <div>
                <div class="text-xs font-semibold text-gray-500">Kamar Santri Terdata</div>
                <div class="text-2xl sm:text-3xl font-extrabold text-gray-950 font-mono mt-1">{{ $kamars->count() }}</div>
                <div class="text-xs text-gray-500 mt-1">Asrama, SMP/SMA &amp; Pegawai</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 text-gray-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- VIEW 1: KARTU TABEL JADWAL SESI (FILAMENT V4 STYLE)     -->
    <!-- ======================================================= -->
    <section x-show="activeTab === 'sesi'"
             class="bg-white border border-gray-200 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.03)] overflow-visible">

        <!-- Card Top Toolbar: Bulk Actions + Category Pills + Search + Funnel Filter -->
        <div class="px-4 py-3.5 border-b border-gray-200 rounded-t-xl flex flex-wrap items-center justify-between gap-3 bg-white">
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Bulk Actions Dropdown (Gambar 5 Style - Aktif Setelah Data Dipilih) -->
                <div class="relative" @click.outside="showBulkMenu = false">
                    <button type="button"
                            :disabled="selectedSesiIds.length === 0"
                            @click="if (selectedSesiIds.length > 0) showBulkMenu = !showBulkMenu"
                            :class="selectedSesiIds.length > 0 ? 'bg-white text-gray-800 border-gray-300 hover:bg-gray-50 shadow-2xs cursor-pointer' : 'bg-gray-50 text-gray-400 border-gray-200 opacity-65 cursor-not-allowed'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs sm:text-[13px] font-semibold transition">
                        <svg class="w-4 h-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                        </svg>
                        <span>Aksi</span>
                        <span x-show="selectedSesiIds.length > 0"
                              x-cloak
                              x-text="selectedSesiIds.length"
                              class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-[#0052FF] text-white text-[11px] font-bold"></span>
                    </button>

                    <div x-show="showBulkMenu && selectedSesiIds.length > 0"
                         x-cloak
                         x-transition
                         class="absolute left-0 mt-1.5 w-52 bg-white border border-gray-200 rounded-xl shadow-xl p-1.5 z-40">
                        <button type="button"
                                @click="showBulkMenu = false; showBulkDeleteModal = true"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left text-[13px] font-semibold text-rose-600 hover:bg-rose-50 transition">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus terpilih</span>
                        </button>
                        <div class="my-1 border-t border-gray-100"></div>
                        <button type="button"
                                @click="selectedSesiIds = []; showBulkMenu = false"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-left text-[13px] font-medium text-gray-600 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>Batalkan pilihan</span>
                        </button>
                    </div>
                </div>

                <!-- Quick Category Pills -->
                <div class="inline-flex flex-wrap items-center gap-1 p-1 rounded-lg bg-gray-100/80 border border-gray-200/70">
                    <template x-for="kat in kategoriFilters" :key="kat.value">
                        <button type="button"
                                @click="filterKategori = kat.value; draftKategori = kat.value; sesiPage = 1"
                                :class="filterKategori === kat.value ? 'bg-white text-[#0052FF] font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                                class="px-2.5 py-1 rounded-md text-xs transition"
                                x-text="kat.label">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right: Search Input + Funnel Filter Button (Persis Gambar 3 & 4) -->
            <div class="flex items-center gap-2 ml-auto w-full sm:w-auto">
                <div class="relative flex-1 sm:w-64 sm:flex-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           @input="sesiPage = 1"
                           placeholder="Cari sesi, kode, lokasi..."
                           class="w-full pl-9 pr-8 py-1.5 rounded-lg bg-white border border-gray-200 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                    <button type="button"
                            x-show="searchQuery.length > 0"
                            @click="searchQuery = ''; sesiPage = 1"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Funnel Filter Button with Active Count Badge -->
                <div class="relative shrink-0" @click.outside="showSesiFilterPopover = false">
                    <button type="button"
                            @click="draftKategori = filterKategori; draftTarget = filterTarget; showSesiFilterPopover = !showSesiFilterPopover"
                            :class="(showSesiFilterPopover || activeFilterCount() > 0) ? 'border-[#0052FF] bg-[#EFF4FF]/60 text-[#0052FF]' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-500 hover:text-gray-800'"
                            class="relative w-9 h-9 rounded-lg border flex items-center justify-center transition"
                            title="Filter Jadwal Sesi">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                        </svg>
                        <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center border"
                              :class="activeFilterCount() > 0 ? 'bg-[#0052FF] text-white border-[#0052FF]' : 'bg-[#EFF4FF] text-[#0052FF] border-[#0052FF]/20'"
                              x-text="activeFilterCount()">0</span>
                    </button>

                    <!-- Popover Filter (Persis Gambar 4) -->
                    <div x-show="showSesiFilterPopover" x-cloak x-transition
                         class="absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-xl shadow-xl z-40 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-900">Filter</span>
                            <button type="button"
                                    @click="resetSesiFilters()"
                                    class="text-xs font-semibold text-rose-600 hover:underline">Reset</button>
                        </div>
                        <div class="p-4 space-y-3.5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kategori Sesi</label>
                                <select x-model="draftKategori"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-800 outline-none focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15">
                                    <option value="all">Semua Kategori</option>
                                    <option value="sholat">Sholat</option>
                                    <option value="pengajian">Pengajian</option>
                                    <option value="sekolah">Sekolah</option>
                                    <option value="makan">Makan</option>
                                    <option value="pegawai">Pegawai</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Target Peserta</label>
                                <select x-model="draftTarget"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs font-medium text-gray-800 outline-none focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15">
                                    <option value="all">Semua Target</option>
                                    <option value="semua_santri">Semua Santri</option>
                                    <option value="kamar_khusus">Kamar Khusus</option>
                                    <option value="sekolah">Siswa Sekolah</option>
                                    <option value="pegawai">Guru &amp; Pegawai</option>
                                </select>
                            </div>
                            <button type="button"
                                    @click="applySesiFilters()"
                                    class="w-full py-2 px-3 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] text-white text-xs font-semibold shadow-sm shadow-[#0052FF]/20 transition">
                                Terapkan filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selection Indicator Strip (Gambar 5 Style) -->
        <div x-show="selectedSesiIds.length > 0"
             x-cloak
             class="px-4 py-2.5 bg-[#F8FAFF] border-b border-gray-200 flex flex-wrap items-center justify-between gap-2 text-xs sm:text-[13px]">
            <span class="font-semibold text-gray-800">
                <span x-text="selectedSesiIds.length"></span> jadwal sesi dipilih
            </span>
            <div class="inline-flex items-center gap-3">
                <button type="button"
                        @click="selectAllFilteredSesi()"
                        class="font-semibold text-[#0052FF] hover:underline">
                    Pilih semua <span x-text="filteredSesiIds().length"></span>
                </button>
                <button type="button"
                        @click="selectedSesiIds = []"
                        class="font-semibold text-rose-600 hover:underline">
                    Batalkan pilihan
                </button>
            </div>
        </div>

        <!-- Tabel Sesi Presensi -->
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAFAFA] border-b border-gray-200 text-xs font-semibold text-gray-900">
                        <th class="py-3 pl-4 pr-2 w-10">
                            <input type="checkbox"
                                   :checked="isAllPageSesiSelected()"
                                   @change="toggleSelectPageSesi($event.target.checked)"
                                   class="w-4 h-4 rounded border-gray-300 text-[#0052FF] accent-[#0052FF] focus:ring-[#0052FF]/25 cursor-pointer"
                                   aria-label="Pilih semua sesi di halaman ini">
                        </th>
                        <th class="py-3 px-4">
                            <div class="inline-flex items-center gap-1">
                                <span>Nama &amp; Kode Sesi</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3 px-4">Perangkat ESP32</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4 text-center">Aktif Jam Ini</th>
                        <th class="py-3 px-4">
                            <div class="inline-flex items-center gap-1">
                                <span>Jadwal &amp; Target</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </th>
                        <th class="py-3 pl-4 pr-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm bg-white">
                    @forelse($sesis as $s)
                        @php
                            $jamMulaiShort = substr((string) $s->jam_mulai, 0, 5);
                            $jamSelesaiShort = substr((string) $s->jam_selesai, 0, 5);
                            $targetKat = $s->target_kategori ?? 'semua_santri';
                            $kat = strtolower((string) ($s->kategori ?? 'sholat'));

                            $kategoriPill = match($kat) {
                                'sholat' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                'pengajian' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
                                'sekolah' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                'makan' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                'pegawai' => 'bg-[#EFF4FF] text-[#0052FF] ring-[#0052FF]/20',
                                default => 'bg-gray-100 text-gray-700 ring-gray-500/20',
                            };
                        @endphp
                        <tr x-show="isSesiRowVisible({{ $s->id }})"
                            :class="isSesiSelected({{ $s->id }}) ? 'bg-[#F8FAFF] shadow-[inset_3px_0_0_#0052FF]' : 'hover:bg-gray-50/80'"
                            class="transition-colors">

                            <!-- Checkbox Row -->
                            <td class="py-3.5 pl-4 pr-2">
                                <input type="checkbox"
                                       :checked="isSesiSelected({{ $s->id }})"
                                       @change="toggleSesiSelect({{ $s->id }}, $event.target.checked)"
                                       class="w-4 h-4 rounded border-gray-300 text-[#0052FF] accent-[#0052FF] focus:ring-[#0052FF]/25 cursor-pointer"
                                       aria-label="Pilih {{ $s->nama_sesi }}">
                            </td>

                            <!-- Nama Sesi & Kode -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-gray-900">{{ $s->nama_sesi }}</div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-mono text-[11px] font-semibold">
                                        {{ $s->kode_sesi }}
                                    </span>
                                    <span>•</span>
                                    @if(($s->toleransi_menit ?? 0) > 0)
                                        <span>Toleransi {{ $s->toleransi_menit }} menit</span>
                                    @else
                                        <span>Tanpa batas terlambat</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Perangkat ESP32 -->
                            <td class="py-3.5 px-4">
                                @if($s->device_id_target)
                                    <div class="font-mono text-xs font-semibold text-[#0052FF]">{{ $s->device_id_target }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $s->nama_lokasi ?? 'Lokasi belum diatur' }}</div>
                                @else
                                    <span class="text-gray-700 font-medium">Semua Perangkat</span>
                                    <div class="text-xs text-gray-400">Lintas lokasi (Global)</div>
                                @endif
                            </td>

                            <!-- Kategori Pill (Persis Status Pill Gambar Referensi) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold ring-1 ring-inset {{ $kategoriPill }}">
                                    {{ $kat }}
                                </span>
                            </td>

                            <!-- Aktif Jam Ini (Check / Cross Circle Persis Gambar Referensi) -->
                            <td class="py-3.5 px-4 text-center">
                                <template x-if="isTimeActive('{{ $jamMulaiShort }}', '{{ $jamSelesaiShort }}')">
                                    <svg class="w-5 h-5 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Sedang Aktif Jam Ini">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </template>
                                <template x-if="!isTimeActive('{{ $jamMulaiShort }}', '{{ $jamSelesaiShort }}')">
                                    <svg class="w-5 h-5 text-rose-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Di Luar Jam Aktif">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </template>
                            </td>

                            <!-- Jadwal & Target -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-mono text-xs font-semibold text-gray-800">
                                    {{ $jamMulaiShort }} - {{ $jamSelesaiShort }} WIB
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    @if($targetKat === 'kamar_khusus')
                                        Kamar: <span class="font-semibold text-gray-700">{{ $s->target_filter ?: 'Reguler' }}</span>
                                    @elseif($targetKat === 'sekolah')
                                        Sekolah ({{ $s->target_filter ?: 'Semua Siswa' }})
                                    @elseif($targetKat === 'pegawai')
                                        Guru &amp; Pegawai
                                    @else
                                        Semua Santri
                                    @endif
                                </div>
                            </td>

                            <!-- Inline Actions: Electric Blue Edit + Red Delete -->
                            <td class="py-3.5 pl-4 pr-5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end gap-3.5">
                                    <button type="button"
                                            @click="openEditModal({{ $s->id }})"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#0052FF] hover:text-[#0043D4] transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>

                                    <button type="button"
                                            @click="confirmDeleteSesi({{ $s->id }}, @js($s->nama_sesi), @js($s->kode_sesi))"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 px-6 text-center">
                                <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-bold text-gray-900">Belum Ada Sesi Presensi</div>
                                <p class="text-xs text-gray-500 mt-1">Klik tombol "Tambah Sesi Baru" di kanan atas untuk membuat jadwal presensi pertama.</p>
                            </td>
                        </tr>
                    @endforelse

                    @if($sesis->isNotEmpty())
                        <tr x-show="filteredSesiIds().length === 0" x-cloak>
                            <td colspan="7" class="py-12 px-6 text-center text-sm text-gray-500">
                                Tidak ada jadwal sesi yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Bottom Footer: Showing X to Y + Per Page Selector -->
        <div class="px-4 py-3 border-t border-gray-200 rounded-b-xl bg-white flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
            <div class="font-medium text-gray-700" x-text="sesiPaginationLabel()">
                Menampilkan {{ $sesis->count() }} jadwal sesi
            </div>

            <div class="inline-flex items-center rounded-lg border border-gray-200 bg-white overflow-hidden shadow-2xs">
                <span class="px-3 py-1.5 text-xs font-medium text-gray-500 border-r border-gray-200 bg-gray-50/60">Per halaman</span>
                <select x-model.number="sesiPerPage"
                        @change="sesiPage = 1"
                        class="px-2.5 py-1.5 text-xs font-semibold text-gray-900 bg-white outline-none cursor-pointer">
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
            </div>

            <div class="inline-flex items-center gap-1.5" x-show="sesiTotalPages() > 1" x-cloak>
                <button type="button"
                        @click="if (sesiPage > 1) sesiPage--"
                        :disabled="sesiPage <= 1"
                        class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 text-xs font-semibold text-gray-700 transition">
                    Sebelumnya
                </button>
                <span class="px-2 text-xs font-semibold text-gray-600" x-text="sesiPage + ' / ' + sesiTotalPages()"></span>
                <button type="button"
                        @click="if (sesiPage < sesiTotalPages()) sesiPage++"
                        :disabled="sesiPage >= sesiTotalPages()"
                        class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 text-xs font-semibold text-gray-700 transition">
                    Berikutnya
                </button>
            </div>
        </div>
    </section>

    <!-- ======================================================= -->
    <!-- VIEW 2: KARTU TABEL PERANGKAT ESP32 (FILAMENT V4 STYLE) -->
    <!-- ======================================================= -->
    <section x-show="activeTab === 'devices'"
             x-cloak
             class="bg-white border border-gray-200 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.03)] overflow-hidden">

        <!-- Card Top Toolbar -->
        <div class="px-4 py-3.5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 bg-white">
            <div class="inline-flex items-center gap-1 p-1 rounded-lg bg-gray-100/80 border border-gray-200/70 text-xs">
                <button type="button"
                        @click="deviceStatusFilter = 'all'"
                        :class="deviceStatusFilter === 'all' ? 'bg-white text-[#0052FF] font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                        class="px-2.5 py-1 rounded-md transition">
                    Semua ({{ $allDevices->count() }})
                </button>
                <button type="button"
                        @click="deviceStatusFilter = 'aktif'"
                        :class="deviceStatusFilter === 'aktif' ? 'bg-white text-emerald-700 font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                        class="px-2.5 py-1 rounded-md transition">
                    Aktif ({{ $devices->count() }})
                </button>
                <button type="button"
                        @click="deviceStatusFilter = 'nonaktif'"
                        :class="deviceStatusFilter === 'nonaktif' ? 'bg-white text-gray-800 font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                        class="px-2.5 py-1 rounded-md transition">
                    Nonaktif ({{ max(0, $allDevices->count() - $devices->count()) }})
                </button>
            </div>

            <div class="flex items-center gap-2.5 ml-auto">
                <div class="relative w-56 sm:w-64">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                           x-model="deviceSearchQuery"
                           placeholder="Cari serial ESP32 atau lokasi..."
                           class="w-full pl-9 pr-8 py-1.5 rounded-lg bg-white border border-gray-200 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                    <button type="button"
                            x-show="deviceSearchQuery.length > 0"
                            @click="deviceSearchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabel Perangkat ESP32 -->
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-[#FAFAFA] border-b border-gray-200 text-xs font-semibold text-gray-700">
                        <th class="py-3 pl-4 pr-2 w-10">
                            <span class="inline-block w-4 h-4 rounded border border-gray-300 bg-white"></span>
                        </th>
                        <th class="py-3 px-4">Serial Hardware ESP32</th>
                        <th class="py-3 px-4">Nama Lokasi</th>
                        <th class="py-3 px-4">Area</th>
                        <th class="py-3 px-4 text-center">Aktif</th>
                        <th class="py-3 px-4">Koneksi &amp; Sesi</th>
                        <th class="py-3 pl-4 pr-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm bg-white">
                    @forelse($allDevices as $dev)
                        @php
                            $isNewUnlabeled = str_contains(strtolower((string) $dev->nama_lokasi), 'belum diberi lokasi');
                            $lastSeenFormatted = $dev->last_seen_at
                                ? \Carbon\Carbon::parse($dev->last_seen_at)->diffForHumans()
                                : null;
                        @endphp
                        <tr x-show="matchesDeviceFilter({{ $dev->id }})"
                            class="hover:bg-gray-50/80 transition-colors">

                            <td class="py-3.5 pl-4 pr-2">
                                <span class="inline-block w-4 h-4 rounded border border-gray-300 bg-white"></span>
                            </td>

                            <!-- Serial Hardware -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#EFF4FF] text-[#0052FF] font-mono text-xs font-bold border border-[#0052FF]/15">
                                    {{ $dev->device_id }}
                                </span>
                            </td>

                            <!-- Nama Lokasi -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold {{ $isNewUnlabeled ? 'text-amber-700' : 'text-gray-900' }}">
                                    {{ $dev->nama_lokasi }}
                                </div>
                                @if($isNewUnlabeled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 mt-1">
                                        Beri Nama Lokasi
                                    </span>
                                @endif
                            </td>

                            <!-- Area Pill -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-500/20">
                                    {{ strtolower($dev->kategori_area) }}
                                </span>
                            </td>

                            <!-- Status Aktif Icon -->
                            <td class="py-3.5 px-4 text-center">
                                @if($dev->status_aktif)
                                    <svg class="w-5 h-5 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-rose-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </td>

                            <!-- Koneksi & Sesi -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-xs">
                                <div class="font-medium text-gray-800">
                                    @if(!empty($dev->ip_address))
                                        <span class="font-mono">IP: {{ $dev->ip_address }}</span>
                                        <span class="text-gray-300 mx-1">•</span>
                                    @endif
                                    <span class="font-semibold text-[#0052FF]">{{ $dev->total_sesi }} sesi</span>
                                </div>
                                <div class="text-gray-500 mt-0.5">
                                    @if($lastSeenFormatted)
                                        Terhubung {{ $lastSeenFormatted }}
                                    @else
                                        Belum pernah mengirim sinyal
                                    @endif
                                </div>
                            </td>

                            <!-- Inline Actions -->
                            <td class="py-3.5 pl-4 pr-5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center justify-end gap-3.5">
                                    <!-- Edit Lokasi (Electric Blue) -->
                                    <button type="button"
                                            @click="openEditDeviceModal({{ $dev->id }}, @js($dev->device_id), @js($dev->nama_lokasi), @js($dev->kategori_area))"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-[#0052FF] hover:text-[#0043D4] transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit Lokasi</span>
                                    </button>

                                    <!-- Toggle Aktif / Nonaktif -->
                                    <form action="{{ route('pengaturan.sesi.device.toggle', $dev->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                                            <span>{{ $dev->status_aktif ? 'Matikan' : 'Aktifkan' }}</span>
                                        </button>
                                    </form>

                                    <!-- Delete Perangkat -->
                                    <form action="{{ route('pengaturan.sesi.device.destroy', $dev->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus perangkat {{ $dev->device_id }}? Sesi yang terhubung ke alat ini akan otomatis dikembalikan ke Semua Perangkat (Global).');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 px-6 text-center">
                                <div class="w-12 h-12 rounded-xl bg-[#EFF4FF] border border-[#0052FF]/15 text-[#0052FF] flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-bold text-gray-900">Belum Ada Perangkat Fisik ESP32 Terdaftar</div>
                                <p class="text-xs text-gray-500 mt-1 max-w-lg mx-auto leading-relaxed">
                                    Begitu mesin ESP32 dinyalakan atau digunakan men-tap kartu NFC, <strong class="text-gray-800">Serial Unik Hardware ESP32</strong> akan otomatis terdeteksi di sini dan Anda tinggal menamai lokasinya.
                                </p>
                                <button type="button"
                                        @click="openCreateDeviceModal()"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] text-white text-xs font-semibold shadow-sm transition">
                                    <span>Pindai / Daftarkan Serial ESP32 Sekarang</span>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 bg-white flex items-center justify-between text-xs sm:text-sm">
            <div class="font-medium text-gray-700" x-text="'Menampilkan ' + filteredDevicesCount() + ' dari {{ $allDevices->count() }} perangkat ESP32'"></div>
            <div class="text-xs text-gray-400">Auto-discovery MQTT aktif</div>
        </div>
    </section>

    <!-- ======================================================= -->
    <!-- MODAL TAMBAH / EDIT SESI PRESENSI (CLEAN LIGHT + BLUE)  -->
    <!-- ======================================================= -->
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
         @keydown.escape.window="showModal = false">

        <div x-show="showModal"
             x-transition.opacity
             @click="showModal = false"
             class="fixed inset-0 bg-gray-900/45 backdrop-blur-[2px]"></div>

        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative w-full max-w-2xl bg-white border border-gray-200 rounded-2xl shadow-2xl overflow-hidden z-10">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-white">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[#EFF4FF] border border-[#0052FF]/15 text-[#0052FF] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-gray-950"
                            x-text="modalMode === 'create' ? 'Tambah Sesi Presensi Baru' : 'Perbarui Pengaturan Sesi'"></h3>
                        <p class="text-xs text-gray-500">Konfigurasi rentang waktu tap kartu, mesin ESP32, dan target peserta.</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Tambah / Update -->
            <form :action="modalMode === 'create' ? '{{ route('pengaturan.sesi.store') }}' : ('/pengaturan-sesi/' + form.id)" method="POST">
                @csrf
                <template x-if="modalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto custom-scroll bg-white">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <!-- Kode Sesi -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Kode Sesi (Unik) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="kode_sesi"
                                   x-model="form.kode_sesi"
                                   @input="form.kode_sesi = form.kode_sesi.toUpperCase().replace(/\s+/g, '_')"
                                   placeholder="Contoh: TAHFIDZ_SORE"
                                   required
                                   maxlength="30"
                                   class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm font-mono font-semibold text-gray-900 placeholder-gray-400 outline-none transition">
                            <p class="text-[11px] text-gray-500 mt-1">Ditampilkan singkat pada layar LCD 16x2 mesin ESP32.</p>
                        </div>

                        <!-- Nama Sesi -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Nama Sesi <span class="text-rose-500">*</span>
                            </label>
                            <input type="text"
                                   name="nama_sesi"
                                   x-model="form.nama_sesi"
                                   placeholder="Contoh: Mengaji Tahfidz Sore"
                                   required
                                   maxlength="100"
                                   class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                            <p class="text-[11px] text-gray-500 mt-1">Ditampilkan pada header Monitoring NFC Tap.</p>
                        </div>

                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Jam Mulai <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                   name="jam_mulai"
                                   x-model="form.jam_mulai"
                                   required
                                   class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm font-mono text-gray-900 outline-none transition">
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Jam Selesai <span class="text-rose-500">*</span>
                            </label>
                            <input type="time"
                                   name="jam_selesai"
                                   x-model="form.jam_selesai"
                                   required
                                   class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm font-mono text-gray-900 outline-none transition">
                        </div>

                        <!-- Perangkat Khusus (ESP32) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Perangkat ESP32 Target
                            </label>
                            <select name="device_id_target"
                                    x-model="form.device_id_target"
                                    class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 outline-none transition">
                                @if($devices->isEmpty())
                                    <option value="">Semua Perangkat / Global (Belum ada alat terdaftar)</option>
                                @else
                                    <option value="">Semua Perangkat (Global)</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->device_id }}">{{ $d->nama_lokasi }} ({{ $d->device_id }})</option>
                                    @endforeach
                                @endif
                            </select>
                            <p class="text-[11px] text-gray-500 mt-1">
                                {{ $devices->isEmpty() ? 'Belum ada mesin spesifik terdaftar. Sesi berlaku otomatis untuk alat mana pun yang aktif.' : 'Pilih lokasi & serial spesifik, atau biarkan Global.' }}
                            </p>
                        </div>

                        <!-- Toleransi Menit & Kategori -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Toleransi (Menit)</label>
                                <input type="number"
                                       name="toleransi_menit"
                                       x-model="form.toleransi_menit"
                                       min="0"
                                       max="240"
                                       class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm font-mono text-gray-900 outline-none transition">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kategori Sesi</label>
                                <select name="kategori"
                                        x-model="form.kategori"
                                        class="w-full px-3 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 outline-none transition">
                                    <option value="sholat">Sholat</option>
                                    <option value="pengajian">Pengajian</option>
                                    <option value="sekolah">Sekolah</option>
                                    <option value="makan">Makan</option>
                                    <option value="pegawai">Pegawai</option>
                                </select>
                            </div>
                        </div>

                        <!-- Target Partisipan -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Target Partisipan</label>
                            <select name="target_kategori"
                                    x-model="form.target_kategori"
                                    @change="onTargetKategoriChange()"
                                    class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 outline-none transition">
                                <option value="semua_santri">Semua Santri</option>
                                <option value="kamar_khusus">Kamar Tertentu</option>
                                <option value="sekolah">Siswa Sekolah (SMP / SMA)</option>
                                <option value="pegawai">Guru &amp; Pegawai</option>
                            </select>
                        </div>

                        <!-- Filter Target Dinamis -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Filter Target (Kamar / Jenjang)</label>
                            <input type="hidden"
                                   name="target_filter"
                                   :value="(form.target_kategori === 'kamar_khusus' || form.target_kategori === 'sekolah') ? form.target_filter : ''">

                            <!-- Mode Kamar Khusus -->
                            <div x-show="form.target_kategori === 'kamar_khusus'">
                                <input type="text"
                                       x-model="form.target_filter"
                                       list="daftarKamarSantri"
                                       placeholder="Pilih atau ketik kamar (misal: Reguler)"
                                       class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                                <datalist id="daftarKamarSantri">
                                    @foreach($kamars as $kamar)
                                        <option value="{{ $kamar }}"></option>
                                    @endforeach
                                </datalist>
                                @if($kamars->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @foreach($kamars as $kamar)
                                            <button type="button"
                                                    @click="form.target_filter = @js($kamar)"
                                                    :class="form.target_filter === @js($kamar) ? 'bg-[#0052FF] text-white border-[#0052FF]' : 'bg-gray-100 text-gray-700 border-gray-200 hover:border-gray-300'"
                                                    class="px-2 py-0.5 rounded text-[11px] font-semibold border transition">
                                                {{ $kamar }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Mode Siswa Sekolah -->
                            <div x-show="form.target_kategori === 'sekolah'">
                                <input type="text"
                                       x-model="form.target_filter"
                                       placeholder="Ketik SMP, SMA, atau ALL"
                                       class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <template x-for="jenjang in ['SMP', 'SMA', 'ALL']" :key="jenjang">
                                        <button type="button"
                                                @click="form.target_filter = jenjang"
                                                :class="form.target_filter === jenjang ? 'bg-[#0052FF] text-white border-[#0052FF] font-bold' : 'bg-gray-100 text-gray-700 border-gray-200 hover:border-gray-300 font-semibold'"
                                                class="px-2.5 py-0.5 rounded text-[11px] border transition"
                                                x-text="jenjang === 'ALL' ? 'ALL (SMP & SMA)' : jenjang">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Mode Global -->
                            <div x-show="form.target_kategori === 'semua_santri' || form.target_kategori === 'pegawai'"
                                 class="px-3.5 py-2 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-500">
                                Otomatis mencakup seluruh <strong class="text-gray-800" x-text="form.target_kategori === 'pegawai' ? 'Guru & Pegawai' : 'Santri Aktif'"></strong> tanpa filter tambahan.
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/70 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold border border-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] active:bg-[#0038B3] text-white text-sm font-semibold shadow-sm shadow-[#0052FF]/20 transition"
                            x-text="modalMode === 'create' ? 'Simpan Sesi Baru' : 'Simpan Perubahan'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL KONFIRMASI HAPUS SESI TUNGGAL (GAMBAR 5 STYLE)    -->
    <!-- ======================================================= -->
    <div x-show="deleteTarget.show" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center p-4"
         @keydown.escape.window="deleteTarget.show = false">
        <div x-show="deleteTarget.show" x-transition.opacity @click="deleteTarget.show = false" class="fixed inset-0 bg-gray-900/45 backdrop-blur-[2px]"></div>

        <div x-show="deleteTarget.show"
             x-transition
             class="relative w-full max-w-[400px] bg-white border border-gray-200 rounded-2xl shadow-2xl p-6 text-center z-10">
            <button type="button"
                    @click="deleteTarget.show = false"
                    class="absolute top-4 right-4 w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition"
                    aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-950">Hapus jadwal sesi</h3>
            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                Apakah Anda yakin ingin menghapus sesi <strong class="text-gray-900" x-text="deleteTarget.nama"></strong>
                (<span class="font-mono text-rose-600 font-semibold" x-text="deleteTarget.kode"></span>)?
            </p>

            <form :action="'/pengaturan-sesi/' + deleteTarget.id" method="POST" class="mt-6 grid grid-cols-2 gap-3">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="deleteTarget.show = false"
                        class="w-full py-2.5 px-4 rounded-lg bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold border border-gray-300 shadow-2xs transition">
                    Batal
                </button>
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-sm transition">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL KONFIRMASI HAPUS SESI TERPILIH / BULK (GAMBAR 5)  -->
    <!-- ======================================================= -->
    <div x-show="showBulkDeleteModal" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center p-4"
         @keydown.escape.window="showBulkDeleteModal = false">
        <div x-show="showBulkDeleteModal" x-transition.opacity @click="showBulkDeleteModal = false" class="fixed inset-0 bg-gray-900/45 backdrop-blur-[2px]"></div>

        <div x-show="showBulkDeleteModal"
             x-transition
             class="relative w-full max-w-[400px] bg-white border border-gray-200 rounded-2xl shadow-2xl p-6 text-center z-10">
            <button type="button"
                    @click="showBulkDeleteModal = false"
                    class="absolute top-4 right-4 w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition"
                    aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-950">Hapus jadwal sesi terpilih</h3>
            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                Apakah Anda yakin ingin menghapus <strong class="text-gray-900" x-text="selectedSesiIds.length"></strong> jadwal sesi yang dipilih?
            </p>

            <form action="{{ route('pengaturan.sesi.bulk-destroy') }}" method="POST" class="mt-6 grid grid-cols-2 gap-3">
                @csrf
                <input type="hidden" name="sesi_ids" :value="selectedSesiIds.join(',')">
                <button type="button"
                        @click="showBulkDeleteModal = false"
                        class="w-full py-2.5 px-4 rounded-lg bg-white hover:bg-gray-50 text-gray-800 text-sm font-semibold border border-gray-300 shadow-2xs transition">
                    Batal
                </button>
                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-sm transition">
                    Hapus
                </button>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL DETEKSI / DAFTARKAN / EDIT LOKASI ESP32           -->
    <!-- ======================================================= -->
    <div x-show="showDeviceModal" x-cloak
         class="fixed inset-0 z-[200] flex items-center justify-center p-4"
         @keydown.escape.window="closeDeviceModal()">
        <div x-show="showDeviceModal" x-transition.opacity @click="closeDeviceModal()" class="fixed inset-0 bg-gray-900/45 backdrop-blur-[2px]"></div>

        <div x-show="showDeviceModal"
             x-transition
             class="relative w-full max-w-lg bg-white border border-gray-200 rounded-2xl shadow-2xl overflow-hidden z-10">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-white">
                <div>
                    <h3 class="text-base font-extrabold text-gray-950"
                        x-text="deviceModalMode === 'create' ? 'Deteksi / Daftarkan Serial ESP32' : 'Ubah Nama Lokasi Perangkat'"></h3>
                    <p class="text-xs text-gray-500 mt-0.5"
                       x-text="deviceModalMode === 'create' ? 'Nyalakan ESP32 atau tap 1 kartu agar Serial Hardware terbaca otomatis.' : 'Berikan nama lokasi agar mudah dikenali pada pilihan sesi.'"></p>
                </div>
                <button type="button" @click="closeDeviceModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="deviceModalMode === 'create' ? '{{ route('pengaturan.sesi.device.store') }}' : ('/pengaturan-sesi/device/' + deviceForm.id)" method="POST">
                @csrf
                <template x-if="deviceModalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4 bg-white">
                    <!-- Radar Auto-Detect Serial Live (Electric Blue Theme) -->
                    <div x-show="deviceModalMode === 'create'"
                         class="p-3.5 rounded-xl border transition"
                         :class="detectedDevice.device_id ? 'bg-[#EFF4FF] border-[#0052FF]/30' : 'bg-gray-50 border-gray-200'">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full"
                                      :class="detectedDevice.device_id ? 'bg-[#0052FF] animate-ping' : 'bg-gray-400 animate-pulse'"></span>
                                <span class="text-xs font-bold uppercase tracking-wider"
                                      :class="detectedDevice.device_id ? 'text-[#0052FF]' : 'text-gray-500'"
                                      x-text="detectedDevice.device_id ? 'Serial Alat Terdeteksi!' : 'Memindai Sinyal MQTT ESP32...'"></span>
                            </div>
                            <button type="button"
                                    x-show="detectedDevice.device_id"
                                    @click="deviceForm.device_id = detectedDevice.device_id"
                                    class="px-2.5 py-1 rounded-md bg-[#0052FF] hover:bg-[#0043D4] text-white text-[11px] font-semibold transition">
                                Pakai Serial Ini
                            </button>
                        </div>

                        <template x-if="detectedDevice.device_id">
                            <div class="mt-2 text-xs text-gray-700 font-mono">
                                ID: <strong class="text-gray-950" x-text="detectedDevice.device_id"></strong>
                                <span x-show="detectedDevice.ip" x-text="' • IP: ' + detectedDevice.ip"></span>
                                <span x-show="detectedDevice.timestamp" class="text-gray-500" x-text="' (' + detectedDevice.timestamp + ')'"></span>
                            </div>
                        </template>
                        <template x-if="!detectedDevice.device_id">
                            <p class="mt-1.5 text-[11px] text-gray-500">
                                Colokkan daya ke ESP32 atau tempelkan kartu apa saja pada reader — nomor seri akan muncul otomatis di sini.
                            </p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Serial Hardware ESP32 (Unik) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="device_id"
                               x-model="deviceForm.device_id"
                               :readonly="deviceModalMode === 'edit'"
                               required
                               maxlength="50"
                               placeholder="Contoh: ESP32-A4CF1298B3E0"
                               :class="deviceModalMode === 'edit' ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white text-gray-900'"
                               class="w-full px-3.5 py-2 rounded-lg border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm font-mono font-bold placeholder-gray-400 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Nama Lokasi Pemasangan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="nama_lokasi"
                               x-model="deviceForm.nama_lokasi"
                               required
                               maxlength="100"
                               placeholder="Contoh: Pintu Kamar Reguler / Teras Masjid"
                               class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Kategori Area</label>
                        <select name="kategori_area"
                                x-model="deviceForm.kategori_area"
                                class="w-full px-3.5 py-2 rounded-lg bg-white border border-gray-300 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 outline-none transition">
                            <option value="pondok">Pondok / Asrama</option>
                            <option value="sekolah">Sekolah (SMP / SMA)</option>
                            <option value="dapur">Dapur / Ruang Makan</option>
                            <option value="umum">Area Umum / Kantor</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/70 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="closeDeviceModal()"
                            class="px-4 py-2 rounded-lg bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold border border-gray-300 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] active:bg-[#0038B3] text-white text-sm font-semibold shadow-sm shadow-[#0052FF]/20 transition"
                            x-text="deviceModalMode === 'create' ? 'Simpan Perangkat' : 'Simpan Nama Lokasi'">
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function pengaturanSesiApp() {
        return {
            activeTab: @json($initialTab),
            clock: '00:00:00',
            currentHHMM: '00:00',
            searchQuery: '',
            filterKategori: 'all',
            filterTarget: 'all',
            draftKategori: 'all',
            draftTarget: 'all',
            selectedSesiIds: [],
            showBulkMenu: false,
            showBulkDeleteModal: false,
            sesiPerPage: 10,
            sesiPage: 1,
            showSesiFilterPopover: false,
            deviceSearchQuery: '',
            deviceStatusFilter: 'all',
            showModal: false,
            showDeviceModal: false,
            deviceModalMode: 'create',
            devicePollTimer: null,
            detectedDevice: {
                device_id: null,
                ip: null,
                timestamp: null
            },
            deviceForm: {
                id: null,
                device_id: '',
                nama_lokasi: '',
                kategori_area: 'pondok'
            },
            modalMode: 'create',
            sesisData: @json($sesisJson),
            devicesData: @json($devicesJson),
            kategoriFilters: [
                { value: 'all', label: 'Semua' },
                { value: 'sholat', label: 'Sholat' },
                { value: 'pengajian', label: 'Pengajian' },
                { value: 'sekolah', label: 'Sekolah' },
                { value: 'makan', label: 'Makan' },
                { value: 'pegawai', label: 'Pegawai' }
            ],
            form: {
                id: null,
                kode_sesi: '',
                nama_sesi: '',
                jam_mulai: '04:00',
                jam_selesai: '05:30',
                toleransi_menit: 15,
                device_id_target: '',
                kategori: 'sholat',
                target_kategori: 'semua_santri',
                target_filter: ''
            },
            deleteTarget: {
                show: false,
                id: null,
                nama: '',
                kode: ''
            },

            initApp() {
                this.updateClock();
                setInterval(() => this.updateClock(), 1000);
            },

            switchTab(tab) {
                if (tab === 'devices' || tab === 'sesi') {
                    this.activeTab = tab;
                    window.dispatchEvent(new CustomEvent('nfc-sync-sidebar', { detail: { tab } }));
                }
            },

            updateClock() {
                const now = new Date();
                this.clock = now.toLocaleTimeString('id-ID', { hour12: false });
                const hh = String(now.getHours()).padStart(2, '0');
                const mm = String(now.getMinutes()).padStart(2, '0');
                this.currentHHMM = `${hh}:${mm}`;
            },

            isTimeActive(start, end) {
                if (!start || !end) return false;
                if (start <= end) {
                    return this.currentHHMM >= start && this.currentHHMM <= end;
                }
                return this.currentHHMM >= start || this.currentHHMM <= end;
            },

            activeSessionsNow() {
                return this.sesisData.filter(s => this.isTimeActive(s.jam_mulai, s.jam_selesai));
            },

            activeFilterCount() {
                let count = 0;
                if (this.filterKategori !== 'all') count++;
                if (this.filterTarget !== 'all') count++;
                return count;
            },

            applySesiFilters() {
                this.filterKategori = this.draftKategori;
                this.filterTarget = this.draftTarget;
                this.sesiPage = 1;
                this.showSesiFilterPopover = false;
            },

            resetSesiFilters() {
                this.filterKategori = 'all';
                this.filterTarget = 'all';
                this.draftKategori = 'all';
                this.draftTarget = 'all';
                this.sesiPage = 1;
                this.showSesiFilterPopover = false;
            },

            filteredSesiIds() {
                const q = this.searchQuery.trim().toLowerCase();
                return this.sesisData
                    .filter(s => {
                        if (this.filterKategori !== 'all' && s.kategori !== this.filterKategori) {
                            return false;
                        }
                        if (this.filterTarget !== 'all' && (s.target_kategori || 'semua_santri') !== this.filterTarget) {
                            return false;
                        }
                        if (!q) return true;
                        return (s.kode_sesi && s.kode_sesi.toLowerCase().includes(q))
                            || (s.nama_sesi && s.nama_sesi.toLowerCase().includes(q))
                            || (s.nama_lokasi && s.nama_lokasi.toLowerCase().includes(q))
                            || (s.device_id_target && s.device_id_target.toLowerCase().includes(q))
                            || (s.target_filter && s.target_filter.toLowerCase().includes(q));
                    })
                    .map(s => Number(s.id));
            },

            pageSesiIds() {
                const allIds = this.filteredSesiIds();
                const safePage = Math.min(this.sesiPage, Math.max(1, Math.ceil(allIds.length / this.sesiPerPage)));
                const start = (safePage - 1) * this.sesiPerPage;
                return allIds.slice(start, start + this.sesiPerPage);
            },

            isSesiSelected(id) {
                return this.selectedSesiIds.includes(Number(id));
            },

            toggleSesiSelect(id, checked) {
                const numId = Number(id);
                if (checked) {
                    if (!this.selectedSesiIds.includes(numId)) {
                        this.selectedSesiIds.push(numId);
                    }
                } else {
                    this.selectedSesiIds = this.selectedSesiIds.filter(x => x !== numId);
                }
            },

            isAllPageSesiSelected() {
                const pIds = this.pageSesiIds();
                return pIds.length > 0 && pIds.every(id => this.selectedSesiIds.includes(id));
            },

            toggleSelectPageSesi(checked) {
                const pIds = this.pageSesiIds();
                if (checked) {
                    const merged = new Set([...this.selectedSesiIds, ...pIds]);
                    this.selectedSesiIds = Array.from(merged);
                } else {
                    this.selectedSesiIds = this.selectedSesiIds.filter(id => !pIds.includes(id));
                }
            },

            selectAllFilteredSesi() {
                const merged = new Set([...this.selectedSesiIds, ...this.filteredSesiIds()]);
                this.selectedSesiIds = Array.from(merged);
            },

            sesiTotalPages() {
                const total = this.filteredSesiIds().length;
                return Math.max(1, Math.ceil(total / this.sesiPerPage));
            },

            isSesiRowVisible(id) {
                return this.pageSesiIds().includes(Number(id));
            },

            sesiPaginationLabel() {
                const total = this.filteredSesiIds().length;
                if (total === 0) return 'Menampilkan 0 jadwal sesi';
                const safePage = Math.min(this.sesiPage, this.sesiTotalPages());
                const from = (safePage - 1) * this.sesiPerPage + 1;
                const to = Math.min(safePage * this.sesiPerPage, total);
                return `Menampilkan ${from} sampai ${to} dari ${total} jadwal sesi`;
            },

            matchesDeviceFilter(id) {
                const d = this.devicesData.find(item => Number(item.id) === Number(id));
                if (!d) return true;
                if (this.deviceStatusFilter === 'aktif' && !d.status_aktif) return false;
                if (this.deviceStatusFilter === 'nonaktif' && d.status_aktif) return false;
                const q = this.deviceSearchQuery.trim().toLowerCase();
                if (!q) return true;
                return (d.device_id && d.device_id.toLowerCase().includes(q))
                    || (d.nama_lokasi && d.nama_lokasi.toLowerCase().includes(q))
                    || (d.ip_address && d.ip_address.toLowerCase().includes(q))
                    || (d.kategori_area && d.kategori_area.toLowerCase().includes(q));
            },

            filteredDevicesCount() {
                return this.devicesData.filter(d => this.matchesDeviceFilter(d.id)).length;
            },

            openCreateModal() {
                this.modalMode = 'create';
                this.form = {
                    id: null,
                    kode_sesi: '',
                    nama_sesi: '',
                    jam_mulai: '04:00',
                    jam_selesai: '05:30',
                    toleransi_menit: 15,
                    device_id_target: '',
                    kategori: 'sholat',
                    target_kategori: 'semua_santri',
                    target_filter: ''
                };
                this.showModal = true;
            },

            openEditModal(id) {
                const item = this.sesisData.find(s => Number(s.id) === Number(id));
                if (!item) return;
                this.modalMode = 'edit';
                this.form = {
                    id: item.id,
                    kode_sesi: item.kode_sesi,
                    nama_sesi: item.nama_sesi,
                    jam_mulai: item.jam_mulai,
                    jam_selesai: item.jam_selesai,
                    toleransi_menit: item.toleransi_menit,
                    device_id_target: item.device_id_target || '',
                    kategori: item.kategori || 'sholat',
                    target_kategori: item.target_kategori || 'semua_santri',
                    target_filter: item.target_filter || ''
                };
                this.showModal = true;
            },

            onTargetKategoriChange() {
                if (this.form.target_kategori === 'kamar_khusus' && !this.form.target_filter) {
                    this.form.target_filter = 'Reguler';
                } else if (this.form.target_kategori === 'sekolah' && !this.form.target_filter) {
                    this.form.target_filter = 'SMP';
                } else if (this.form.target_kategori === 'semua_santri' || this.form.target_kategori === 'pegawai') {
                    this.form.target_filter = '';
                }
            },

            confirmDeleteSesi(id, nama, kode) {
                this.deleteTarget = {
                    show: true,
                    id: id,
                    nama: nama,
                    kode: kode
                };
            },

            openCreateDeviceModal() {
                this.deviceModalMode = 'create';
                this.deviceForm = {
                    id: null,
                    device_id: '',
                    nama_lokasi: '',
                    kategori_area: 'pondok'
                };
                this.showDeviceModal = true;
                this.pollDetectedDevice();
                if (this.devicePollTimer) clearInterval(this.devicePollTimer);
                this.devicePollTimer = setInterval(() => this.pollDetectedDevice(), 2000);
            },

            openEditDeviceModal(id, deviceId, namaLokasi, kategoriArea) {
                this.deviceModalMode = 'edit';
                this.deviceForm = {
                    id: id,
                    device_id: deviceId,
                    nama_lokasi: (namaLokasi || '').includes('Belum Diberi Lokasi') ? '' : namaLokasi,
                    kategori_area: kategoriArea || 'pondok'
                };
                if (this.devicePollTimer) {
                    clearInterval(this.devicePollTimer);
                    this.devicePollTimer = null;
                }
                this.showDeviceModal = true;
            },

            closeDeviceModal() {
                this.showDeviceModal = false;
                if (this.devicePollTimer) {
                    clearInterval(this.devicePollTimer);
                    this.devicePollTimer = null;
                }
            },

            pollDetectedDevice() {
                fetch('{{ route("pengaturan.sesi.last-device") }}')
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.device_id) {
                            this.detectedDevice = data;
                            if (!this.deviceForm.device_id) {
                                this.deviceForm.device_id = data.device_id;
                            }
                            if (!this.deviceForm.nama_lokasi && data.nama_lokasi && !data.nama_lokasi.includes('Belum Diberi Lokasi')) {
                                this.deviceForm.nama_lokasi = data.nama_lokasi;
                            }
                        }
                    })
                    .catch(() => {});
            }
        };
    }
</script>
@endpush
