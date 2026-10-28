@extends('layouts.presensi-nfc', [
    'nfcPage' => 'dashboard',
    'activeSidebarKey' => $initialTab ?? 'pondok',
    'sesiCount' => $allSesis->count(),
    'deviceCount' => $totalDevicesCount ?? $allDevices->count(),
])

@section('title', 'Monitoring NFC Tap Real-Time')

@section('body-attrs')
x-data="dashboardApp()" x-init="initApp()" @nfc-tab-switch.window="changeTab($event.detail.tab)"
@endsection

@section('content')
<div class="space-y-6">

    <!-- ======================================================= -->
    <!-- FILAMENT V4 PAGE HEADER: BREADCRUMB + TITLE + BLUE CTA  -->
    <!-- ======================================================= -->
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-[13px] font-medium text-gray-500 mb-1.5" aria-label="Breadcrumb">
                <span>Monitoring NFC Tap</span>
                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-700 font-semibold" x-text="tabLabel()">Pondok / Asrama</span>
            </nav>

            <!-- Page Heading -->
            <h1 class="text-2xl sm:text-[28px] font-extrabold tracking-tight text-gray-950 leading-tight"
                x-text="tabHeading()">
                Presensi Pondok / Asrama
            </h1>

            <!-- Active Session Live Status -->
            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs sm:text-[13px] text-gray-500">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-[#EFF4FF] text-[#0052FF] border border-[#0052FF]/15 font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#0052FF] animate-ping"></span>
                    <span>Live 3s</span>
                </span>
                <span>Sesi Aktif:</span>
                <strong class="text-gray-900 font-semibold" x-text="sesiAktif.nama || 'Memuat Sesi...'"></strong>
                <span class="font-mono text-xs text-gray-500"
                      x-show="sesiAktif.jam_mulai && sesiAktif.jam_selesai"
                      x-text="'(' + (sesiAktif.jam_mulai || '').substring(0, 5) + ' - ' + (sesiAktif.jam_selesai || '').substring(0, 5) + ' WIB)'"></span>
                <span class="hidden sm:inline text-gray-300">•</span>
                <span class="hidden sm:inline text-gray-500" x-text="currentDate"></span>
            </div>
        </div>

        <!-- Right Controls & Electric Blue Primary Action -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Filter Sesi -->
            <div class="inline-flex items-center bg-white border border-gray-200 rounded-lg shadow-[0_1px_2px_rgba(0,0,0,0.03)] px-3 py-2 text-xs sm:text-sm">
                <span class="text-gray-500 font-medium mr-2">Sesi:</span>
                <select x-model="selectedSesi"
                        @change="currentPage = 1; fetchData()"
                        class="bg-transparent text-gray-900 font-semibold outline-none cursor-pointer pr-1">
                    <option value="">Otomatis (Sesi Saat Ini)</option>
                    @foreach($allSesis as $sesi)
                        <option value="{{ $sesi->id }}">{{ $sesi->nama_sesi }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Kamar (Khusus Tab Pondok) -->
            <div x-show="currentTab === 'pondok'"
                 class="inline-flex items-center bg-white border border-gray-200 rounded-lg shadow-[0_1px_2px_rgba(0,0,0,0.03)] px-3 py-2 text-xs sm:text-sm">
                <span class="text-gray-500 font-medium mr-2">Kamar:</span>
                <select x-model="selectedKamar"
                        @change="currentPage = 1; fetchData()"
                        class="bg-transparent text-[#0052FF] font-bold outline-none cursor-pointer pr-1">
                    <option value="Reguler" class="text-gray-900">Kamar Reguler</option>
                    <option value="all" class="text-gray-900">Semua Kamar Asrama</option>
                    @foreach($kamars as $kmr)
                        @if($kmr !== 'Reguler')
                            <option value="{{ $kmr }}" class="text-gray-900">Kamar {{ $kmr }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <!-- Primary CTA Button: Electric Blue (#0052FF) -->
            <a href="{{ route('pengaturan.sesi') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#0052FF] hover:bg-[#0043D4] active:bg-[#0038B3] text-white text-xs sm:text-sm font-semibold shadow-sm shadow-[#0052FF]/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Pengaturan Sesi</span>
            </a>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- 3 KARTU STATISTIK RINGKAS (CLEAN LIGHT CARDS)           -->
    <!-- ======================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Wajib Hadir -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-semibold text-gray-500">Total Wajib Hadir</div>
                    <div class="text-3xl font-extrabold text-gray-950 font-mono mt-1.5" x-text="summary.totalTarget">0</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-[#EFF4FF] border border-[#0052FF]/15 text-[#0052FF] flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2.5 text-xs font-medium text-gray-500"
                 x-text="currentTab === 'pondok' ? 'Santri mukim aktif pada filter kamar' : (currentTab === 'sekolah' ? 'Siswa formal SMP & SMA terdaftar' : 'Guru & pegawai yayasan terdaftar')"></div>
        </div>

        <!-- Sudah Tap Hadir -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-semibold text-gray-500">Sudah Tap Hadir</div>
                    <div class="text-3xl font-extrabold text-emerald-600 font-mono mt-1.5" x-text="summary.sudahHadir">0</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200/70 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2.5">
                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-[#0052FF] h-2 rounded-full transition-all duration-500" :style="'width: ' + summary.persentase + '%'"></div>
                </div>
                <span class="text-xs font-bold text-[#0052FF] font-mono" x-text="summary.persentase + '%'">0%</span>
            </div>
        </div>

        <!-- Belum Tap / Absen -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-[0_1px_3px_rgba(0,0,0,0.03)]">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-semibold text-gray-500">Belum Tap / Absen</div>
                    <div class="text-3xl font-extrabold text-rose-600 font-mono mt-1.5" x-text="summary.belumHadir">0</div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-rose-50 border border-rose-200/70 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2.5 text-xs font-medium text-rose-600">Perlu disisir pamong / wali kelas</div>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MAIN GRID: FILAMENT V4 TABLE CARD + LIVE TAP FEED       -->
    <!-- ======================================================= -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

        <!-- KOLOM UTAMA (8 Kolom): TABEL PESERTA GAYA FILAMENT V4 -->
        <div class="xl:col-span-8 bg-white border border-gray-200 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.03)] overflow-hidden">

            <!-- Toolbar Atas Kartu Tabel (Filter Pills + Search + Funnel Filter) -->
            <div class="px-4 py-3.5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3 bg-white">
                <!-- Quick Status Filter Tabs -->
                <div class="inline-flex items-center p-1 rounded-lg bg-gray-100/80 border border-gray-200/70 text-xs">
                    <button type="button"
                            @click="statusFilter = 'all'; currentPage = 1"
                            :class="statusFilter === 'all' ? 'bg-white text-gray-900 font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                            class="px-2.5 py-1 rounded-md transition flex items-center gap-1.5">
                        <span>Semua</span>
                        <span class="px-1.5 py-0.2 rounded text-[11px] font-mono bg-gray-100 text-gray-700" x-text="summary.totalTarget">0</span>
                    </button>
                    <button type="button"
                            @click="statusFilter = 'belum'; currentPage = 1"
                            :class="statusFilter === 'belum' ? 'bg-white text-rose-600 font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                            class="px-2.5 py-1 rounded-md transition flex items-center gap-1.5">
                        <span>Belum Tap</span>
                        <span class="px-1.5 py-0.2 rounded text-[11px] font-mono bg-rose-50 text-rose-600" x-text="waitingList.length">0</span>
                    </button>
                    <button type="button"
                            @click="statusFilter = 'hadir'; currentPage = 1"
                            :class="statusFilter === 'hadir' ? 'bg-white text-emerald-700 font-semibold shadow-2xs border border-gray-200/80' : 'text-gray-600 hover:text-gray-900 font-medium border border-transparent'"
                            class="px-2.5 py-1 rounded-md transition flex items-center gap-1.5">
                        <span>Sudah Hadir</span>
                        <span class="px-1.5 py-0.2 rounded text-[11px] font-mono bg-emerald-50 text-emerald-700" x-text="hadirList.length">0</span>
                    </button>
                </div>

                <!-- Search Input + Funnel Button (Persis Gambar Referensi) -->
                <div class="flex items-center gap-2.5 ml-auto">
                    <div class="relative w-56 sm:w-64">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               x-model="tableSearch"
                               @input="currentPage = 1"
                               placeholder="Cari nama atau kartu..."
                               class="w-full pl-9 pr-8 py-1.5 rounded-lg bg-white border border-gray-200 focus:border-[#0052FF] focus:ring-2 focus:ring-[#0052FF]/15 text-sm text-gray-900 placeholder-gray-400 outline-none transition">
                        <button type="button"
                                x-show="tableSearch.length > 0"
                                @click="tableSearch = ''; currentPage = 1"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Funnel Filter Button with Electric Blue Badge -->
                    <div class="relative" @click.outside="showFilterMenu = false">
                        <button type="button"
                                @click="showFilterMenu = !showFilterMenu"
                                class="relative w-9 h-9 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-500 hover:text-gray-800 flex items-center justify-center transition"
                                title="Filter Status">
                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd" />
                            </svg>
                            <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-md text-[10px] font-bold flex items-center justify-center border"
                                  :class="activeFilterCount() > 0 ? 'bg-[#0052FF] text-white border-[#0052FF]' : 'bg-[#EFF4FF] text-[#0052FF] border-[#0052FF]/20'"
                                  x-text="activeFilterCount()">0</span>
                        </button>

                        <!-- Filter Popover -->
                        <div x-show="showFilterMenu" x-cloak x-transition
                             class="absolute right-0 mt-2 w-60 bg-white border border-gray-200 rounded-xl shadow-lg p-3.5 z-30 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-900">Filter Tabel</span>
                                <button type="button"
                                        @click="statusFilter = 'all'; sortField = 'status'; sortAsc = true; currentPage = 1"
                                        class="text-xs font-semibold text-[#0052FF] hover:underline">Reset</button>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Status Presensi</label>
                                <select x-model="statusFilter" @change="currentPage = 1"
                                        class="w-full rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-800 outline-none focus:border-[#0052FF]">
                                    <option value="all">Semua Peserta</option>
                                    <option value="belum">Belum Tap (Waiting List)</option>
                                    <option value="hadir">Sudah Tap Hadir</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Utama (Persis Filament v4 Reference) -->
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#FAFAFA] border-b border-gray-200 text-xs font-semibold text-gray-700">
                            <th class="py-3 pl-4 pr-2 w-10">
                                <span class="inline-block w-4 h-4 rounded border border-gray-300 bg-white"></span>
                            </th>
                            <th class="py-3 px-4 cursor-pointer select-none hover:text-gray-950" @click="toggleSort('nama')">
                                <div class="inline-flex items-center gap-1">
                                    <span>Nama Peserta</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </th>
                            <th class="py-3 px-4">Kamar / Unit</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-center">Tap NFC</th>
                            <th class="py-3 px-4 cursor-pointer select-none hover:text-gray-950" @click="toggleSort('waktu')">
                                <div class="inline-flex items-center gap-1">
                                    <span>Waktu Tap</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </th>
                            <th class="py-3 pl-4 pr-5 text-right">UID Kartu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm bg-white">
                        <template x-for="item in paginatedRows()" :key="(item.is_hadir ? 'H-' : 'W-') + item.id">
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <!-- Checkbox Visual -->
                                <td class="py-3.5 pl-4 pr-2">
                                    <span class="inline-block w-4 h-4 rounded border border-gray-300 bg-white"></span>
                                </td>

                                <!-- Nama Peserta -->
                                <td class="py-3.5 px-4 font-semibold text-gray-900" x-text="item.nama"></td>

                                <!-- Kamar / Kelas / Jabatan -->
                                <td class="py-3.5 px-4 text-gray-600" x-text="item.sub"></td>

                                <!-- Status Badge Pill -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <template x-if="item.is_hadir && item.status_kehadiran !== 'terlambat'">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            hadir
                                        </span>
                                    </template>
                                    <template x-if="item.is_hadir && item.status_kehadiran === 'terlambat'">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                            terlambat
                                        </span>
                                    </template>
                                    <template x-if="!item.is_hadir">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-500/20">
                                            belum tap
                                        </span>
                                    </template>
                                </td>

                                <!-- Check / Cross Circle Icon (Persis Gambar Referensi) -->
                                <td class="py-3.5 px-4 text-center">
                                    <template x-if="item.is_hadir">
                                        <svg class="w-5 h-5 text-emerald-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="!item.is_hadir">
                                        <svg class="w-5 h-5 text-rose-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                </td>

                                <!-- Waktu Tap -->
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs"
                                    :class="item.waktu_tap ? 'text-gray-800 font-semibold' : 'text-gray-400'"
                                    x-text="item.waktu_tap ? (item.waktu_tap + ' WIB') : '—'">
                                </td>

                                <!-- UID Kartu NFC -->
                                <td class="py-3.5 pl-4 pr-5 text-right whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono"
                                          :class="(item.rfid_uid && item.rfid_uid !== '-' && item.rfid_uid !== 'Belum ada kartu') ? 'bg-[#EFF4FF] text-[#0052FF] font-semibold' : 'bg-gray-100 text-gray-400'"
                                          x-text="item.rfid_uid || '-'"></span>
                                </td>
                            </tr>
                        </template>

                        <!-- Empty State -->
                        <tr x-show="filteredRows().length === 0">
                            <td colspan="7" class="py-14 px-6 text-center">
                                <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-sm font-bold text-gray-900"
                                     x-text="statusFilter === 'belum' && summary.totalTarget > 0 && waitingList.length === 0 ? 'Alhamdulillah, Semua Peserta Sudah Tap Hadir!' : 'Tidak ada data peserta yang sesuai'"></div>
                                <p class="text-xs text-gray-500 mt-1">Gunakan filter atau kolom pencarian di kanan atas tabel untuk menyesuaikan tampilan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination & Per Page (Persis Gambar Referensi) -->
            <div class="px-4 py-3 border-t border-gray-200 bg-white flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
                <div class="font-medium text-gray-700" x-text="paginationLabel()">
                    Menampilkan 0 hasil
                </div>

                <!-- Segmented Per Page Selector -->
                <div class="inline-flex items-center rounded-lg border border-gray-200 bg-white overflow-hidden shadow-2xs">
                    <span class="px-3 py-1.5 text-xs font-medium text-gray-500 border-r border-gray-200 bg-gray-50/60">Per page</span>
                    <select x-model.number="perPage"
                            @change="currentPage = 1"
                            class="px-2.5 py-1.5 text-xs font-semibold text-gray-900 bg-white outline-none cursor-pointer">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>

                <!-- Prev / Next Page Buttons -->
                <div class="inline-flex items-center gap-1.5" x-show="totalPages() > 1">
                    <button type="button"
                            @click="if (currentPage > 1) currentPage--"
                            :disabled="currentPage <= 1"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 text-xs font-semibold text-gray-700 transition">
                        Sebelumnya
                    </button>
                    <span class="px-2 text-xs font-semibold text-gray-600" x-text="currentPage + ' / ' + totalPages()"></span>
                    <button type="button"
                            @click="if (currentPage < totalPages()) currentPage++"
                            :disabled="currentPage >= totalPages()"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 disabled:opacity-40 text-xs font-semibold text-gray-700 transition">
                        Berikutnya
                    </button>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (4 Kolom): LIVE TAP FEED REAL-TIME -->
        <div class="xl:col-span-4 bg-white border border-gray-200 rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.03)] overflow-hidden flex flex-col">
            <div class="px-4 py-3.5 border-b border-gray-200 flex items-center justify-between bg-white">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0052FF] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#0052FF]"></span>
                    </span>
                    <h2 class="text-sm font-bold text-gray-900">Live Tap Feed</h2>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-[#EFF4FF] text-[#0052FF]"
                      x-text="liveFeeds.length + ' Tap Terakhir'">0 Tap Terakhir</span>
            </div>

            <div class="p-3.5 space-y-2.5 max-h-[540px] overflow-y-auto custom-scroll divide-y divide-gray-100">
                <template x-for="(feed, index) in liveFeeds" :key="feed.id">
                    <div class="pt-2.5 first:pt-0 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 border"
                                 :class="index === 0 ? 'bg-[#0052FF] text-white border-[#0052FF]' : 'bg-gray-50 text-gray-700 border-gray-200'"
                                 x-text="feed.nama ? feed.nama.substring(0, 2).toUpperCase() : 'NF'">
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 truncate" x-text="feed.nama"></div>
                                <div class="text-xs text-gray-500 truncate">
                                    <span class="font-medium text-[#0052FF]" x-text="feed.kamar_or_role"></span>
                                    <span>•</span>
                                    <span x-text="feed.sesi"></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono font-bold text-gray-900" x-text="feed.waktu"></div>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold mt-0.5"
                                  :class="feed.status === 'terlambat' ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'"
                                  x-text="feed.status === 'terlambat' ? 'Terlambat' : 'Berhasil Tap'">
                            </span>
                        </div>
                    </div>
                </template>

                <div x-show="liveFeeds.length === 0" class="py-14 text-center">
                    <div class="w-11 h-11 rounded-xl bg-gray-50 border border-gray-200 text-gray-400 flex items-center justify-center mx-auto mb-2.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="text-xs font-semibold text-gray-700">Belum ada tap kartu hari ini</div>
                    <p class="text-[11px] text-gray-400 mt-0.5">Data akan muncul otomatis saat kartu ditempelkan ke mesin ESP32.</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function dashboardApp() {
        return {
            currentTab: @json($initialTab ?? 'pondok'),
            selectedSesi: '',
            selectedKamar: 'Reguler',
            statusFilter: 'all',
            tableSearch: '',
            sortField: 'status',
            sortAsc: true,
            perPage: 10,
            currentPage: 1,
            showFilterMenu: false,
            clock: '00:00:00',
            currentDate: '',
            sesiAktif: {},
            summary: {
                totalTarget: 0,
                sudahHadir: 0,
                belumHadir: 0,
                persentase: 0
            },
            liveFeeds: [],
            waitingList: [],
            hadirList: [],
            timerInterval: null,

            initApp() {
                this.updateClock();
                setInterval(() => this.updateClock(), 1000);
                this.fetchData();
                this.timerInterval = setInterval(() => this.fetchData(), 3000);
            },

            updateClock() {
                const now = new Date();
                this.clock = now.toLocaleTimeString('id-ID', { hour12: false });
            },

            tabLabel() {
                if (this.currentTab === 'sekolah') return 'Sekolah (SMP/SMA)';
                if (this.currentTab === 'pegawai') return 'Pegawai & Guru';
                return 'Pondok / Asrama';
            },

            tabHeading() {
                if (this.currentTab === 'sekolah') return 'Presensi Sekolah (SMP & SMA)';
                if (this.currentTab === 'pegawai') return 'Presensi Pegawai & Guru';
                return 'Presensi Pondok / Asrama';
            },

            changeTab(tab) {
                if (!['pondok', 'sekolah', 'pegawai'].includes(tab)) return;
                this.currentTab = tab;
                this.currentPage = 1;
                window.dispatchEvent(new CustomEvent('nfc-sync-sidebar', { detail: { tab } }));
                this.fetchData();
            },

            activeFilterCount() {
                return this.statusFilter !== 'all' ? 1 : 0;
            },

            toggleSort(field) {
                if (this.sortField === field) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortField = field;
                    this.sortAsc = true;
                }
            },

            filteredRows() {
                let rows = [];
                if (this.statusFilter === 'hadir') {
                    rows = [...this.hadirList];
                } else if (this.statusFilter === 'belum') {
                    rows = [...this.waitingList];
                } else {
                    rows = [...this.hadirList, ...this.waitingList];
                }

                const q = this.tableSearch.trim().toLowerCase();
                if (q) {
                    rows = rows.filter(item =>
                        (item.nama && item.nama.toLowerCase().includes(q)) ||
                        (item.sub && item.sub.toLowerCase().includes(q)) ||
                        (item.rfid_uid && item.rfid_uid.toLowerCase().includes(q))
                    );
                }

                rows.sort((a, b) => {
                    if (this.sortField === 'nama') {
                        const cmp = (a.nama || '').localeCompare(b.nama || '', 'id');
                        return this.sortAsc ? cmp : -cmp;
                    }
                    if (this.sortField === 'waktu') {
                        const wa = a.waktu_tap || '99:99:99';
                        const wb = b.waktu_tap || '99:99:99';
                        return this.sortAsc ? wa.localeCompare(wb) : wb.localeCompare(wa);
                    }
                    if (a.is_hadir !== b.is_hadir) {
                        return a.is_hadir ? -1 : 1;
                    }
                    return (a.nama || '').localeCompare(b.nama || '', 'id');
                });

                return rows;
            },

            totalPages() {
                const total = this.filteredRows().length;
                return Math.max(1, Math.ceil(total / this.perPage));
            },

            paginatedRows() {
                const all = this.filteredRows();
                const safePage = Math.min(this.currentPage, Math.max(1, Math.ceil(all.length / this.perPage)));
                const start = (safePage - 1) * this.perPage;
                return all.slice(start, start + this.perPage);
            },

            paginationLabel() {
                const total = this.filteredRows().length;
                if (total === 0) return 'Showing 0 results';
                const safePage = Math.min(this.currentPage, this.totalPages());
                const from = (safePage - 1) * this.perPage + 1;
                const to = Math.min(safePage * this.perPage, total);
                return `Menampilkan ${from} sampai ${to} dari ${total} peserta`;
            },

            fetchData() {
                const params = new URLSearchParams({
                    tab: this.currentTab,
                    sesi_id: this.selectedSesi,
                    kamar: this.selectedKamar
                });

                fetch('{{ route("dashboard-presensi.data") }}?' + params.toString())
                    .then(res => res.json())
                    .then(data => {
                        this.currentDate = data.currentDate || '';
                        this.sesiAktif = data.sesiAktif || {};
                        this.summary = data.summary || this.summary;
                        this.liveFeeds = data.liveFeeds || [];
                        this.waitingList = data.waitingList || [];
                        this.hadirList = data.hadirList || [];
                    })
                    .catch(err => console.error('Error fetching live NFC data:', err));
            }
        };
    }
</script>
@endpush
