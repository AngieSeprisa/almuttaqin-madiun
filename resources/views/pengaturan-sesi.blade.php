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
            'kategori' => $s->kategori ?? 'sholat',
            'target_kategori' => $s->target_kategori ?? 'semua_santri',
            'target_filter' => $s->target_filter ?? '',
        ];
    })->values();

    $initialTab = request('tab') === 'devices' ? 'devices' : 'sesi';
@endphp
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sesi &amp; Perangkat Presensi RFID - Yayasan Muttaqin</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#070b14',
                            900: '#0b1120',
                            800: '#0f172a',
                            700: '#1e293b',
                            600: '#334155'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #475569; }
        input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(0.8) brightness(1.2);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-[#0b1120] bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(16,185,129,0.12),transparent)] text-slate-100 min-h-screen font-sans antialiased overflow-x-hidden"
      x-data="pengaturanSesiApp()"
      x-init="initApp()">

    <!-- TOP HEADER BAR -->
    <header class="border-b border-slate-800/80 bg-slate-900/70 backdrop-blur-xl sticky top-0 z-40">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">

            <!-- Brand & Page Title -->
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-500/20 ring-1 ring-white/20 shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-base sm:text-lg font-extrabold tracking-tight text-white">
                            Pengaturan Sesi &amp; Perangkat Presensi
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            RFID Multi-Lokasi
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">Kelola jadwal otomatis, alokasi mesin ESP32, dan target peserta presensi Yayasan Muttaqin.</p>
                </div>
            </div>

            <!-- Right Actions & Live Clock -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                <!-- Live WIB Clock -->
                <div class="hidden md:flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-slate-800/60 border border-slate-700/60">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-medium text-slate-400">Waktu Sistem:</span>
                    <span class="text-sm font-bold font-mono text-white" x-text="clock">00:00:00</span>
                    <span class="text-[11px] font-semibold text-emerald-400">WIB</span>
                </div>

                <!-- Navigasi Cepat ke Dashboard Live -->
                <a href="{{ route('dashboard-presensi') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 text-slate-200 hover:text-white border border-slate-700/80 text-xs sm:text-sm font-semibold transition shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span>Dashboard Live</span>
                </a>

                <!-- Tombol Tambah Sesi Baru -->
                <button type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-600/25 ring-1 ring-white/10 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Sesi Baru</span>
                </button>

                <!-- Kembali ke Portal Utama -->
                <a href="{{ route('beranda') }}"
                   class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/80 transition"
                   title="Kembali ke Portal Utama">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- NOTIFIKASI FLASH SUKSES / ERROR VALIDASI -->
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between gap-3 px-4 py-3.5 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
                <button type="button" @click="show = false" class="p-1.5 rounded-lg text-emerald-400 hover:bg-emerald-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-start justify-between gap-3 px-4 py-3.5 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-200 shadow-lg">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-sm">
                        @if(session('error'))
                            <div class="font-semibold">{{ session('error') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="font-semibold mb-1">Mohon periksa kembali input Anda:</div>
                            <ul class="list-disc list-inside text-xs text-rose-300 space-y-0.5">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <button type="button" @click="show = false" class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- 4 RINGKASAN KARTU STATISTIK -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Total Sesi -->
            <div class="bg-slate-900/60 border border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-lg flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sesi Presensi</div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono mt-1">{{ $sesis->count() }}</div>
                    <div class="text-xs text-slate-400 mt-1">Jadwal otomatis terdaftar</div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-slate-800 border border-slate-700/70 flex items-center justify-center text-slate-300">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

            <!-- Sesi Aktif Sekarang -->
            <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/30 rounded-2xl p-4 sm:p-5 shadow-lg flex items-center justify-between">
                <div class="min-w-0 pr-2">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        <span>Sesi Aktif Jam Ini</span>
                    </div>
                    <div class="text-lg sm:text-xl font-extrabold text-white truncate mt-1" x-text="activeSessionsNow().length > 0 ? activeSessionsNow()[0].nama_sesi : 'Standby / Di Luar Jam'"></div>
                    <div class="text-xs text-emerald-300/80 font-mono mt-1"
                         x-text="activeSessionsNow().length > 0 ? (activeSessionsNow().length + ' sesi sedang berjalan aktif') : 'Menunggu jadwal berikutnya'"></div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>

            <!-- Perangkat ESP32 -->
            <div class="bg-slate-900/60 border border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-lg flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-cyan-400">Mesin ESP32 Aktif</div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono mt-1">
                        {{ $devices->count() }}
                        <span class="text-sm font-medium text-slate-500">/ {{ $allDevices->count() }}</span>
                    </div>
                    <div class="text-xs text-slate-400 mt-1">Titik pembaca kartu RFID</div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-cyan-500/10 border border-cyan-500/25 flex items-center justify-center text-cyan-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                </div>
            </div>

            <!-- Kamar & Target -->
            <div class="bg-slate-900/60 border border-slate-800/90 rounded-2xl p-4 sm:p-5 shadow-lg flex items-center justify-between">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-purple-400">Kamar Santri Terdata</div>
                    <div class="text-2xl sm:text-3xl font-extrabold text-white font-mono mt-1">{{ $kamars->count() }}</div>
                    <div class="text-xs text-slate-400 mt-1">Asrama, SMP/SMA & Pegawai</div>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- TOOLBAR FILTER, PENCARIAN & SWITCH TAB -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-slate-900/50 p-3 rounded-2xl border border-slate-800/90">

            <!-- Switcher Tab: Sesi vs Perangkat ESP32 -->
            <div class="flex items-center gap-1.5 p-1 bg-slate-950/70 rounded-xl border border-slate-800 self-start">
                <button type="button"
                        @click="activeTab = 'sesi'"
                        :class="activeTab === 'sesi' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="px-4 py-2 rounded-lg text-xs sm:text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Jadwal Sesi ({{ $sesis->count() }})</span>
                </button>
                <button type="button"
                        @click="activeTab = 'devices'"
                        :class="activeTab === 'devices' ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/25 font-bold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="px-4 py-2 rounded-lg text-xs sm:text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span>Perangkat ESP32 ({{ $allDevices->count() }})</span>
                </button>
            </div>

            <!-- Filter Kategori & Search (Tampil pada Tab Sesi) -->
            <div x-show="activeTab === 'sesi'" class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Pills -->
                <div class="flex flex-wrap items-center gap-1 bg-slate-950/50 p-1 rounded-xl border border-slate-800/80">
                    <template x-for="kat in kategoriFilters" :key="kat.value">
                        <button type="button"
                                @click="filterKategori = kat.value"
                                :class="filterKategori === kat.value ? 'bg-slate-800 text-white border-slate-600 font-semibold shadow-sm' : 'text-slate-400 hover:text-slate-200 border-transparent'"
                                class="px-2.5 py-1.5 rounded-lg text-xs border transition"
                                x-text="kat.label">
                        </button>
                    </template>
                </div>

                <!-- Search Input -->
                <div class="relative flex-1 sm:w-64 sm:flex-initial">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari sesi, kode, atau lokasi..."
                           class="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-950/70 border border-slate-700/80 focus:border-emerald-500 text-xs sm:text-sm text-white placeholder-slate-500 outline-none transition">
                    <button type="button"
                            x-show="searchQuery.length > 0"
                            @click="searchQuery = ''"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Action pada Tab Perangkat -->
            <div x-show="activeTab === 'devices'" x-cloak class="flex items-center justify-end">
                <button type="button"
                        @click="openCreateDeviceModal()"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-cyan-600/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                    <span>Deteksi / Daftarkan ESP32</span>
                </button>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- TAB 1: TABEL JADWAL SESI PRESENSI                       -->
        <!-- ======================================================= -->
        <section x-show="activeTab === 'sesi'" class="bg-slate-900/60 border border-slate-800/90 rounded-2xl shadow-2xl overflow-hidden">
            <div class="overflow-x-auto custom-scroll">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-950/70 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-3.5 px-5">Kode Sesi</th>
                            <th class="py-3.5 px-5">Nama Sesi</th>
                            <th class="py-3.5 px-5">Jadwal Aktif</th>
                            <th class="py-3.5 px-5">Perangkat (ESP32)</th>
                            <th class="py-3.5 px-5">Target Partisipan</th>
                            <th class="py-3.5 px-5">Kategori</th>
                            <th class="py-3.5 px-5 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 text-sm">
                        @forelse($sesis as $s)
                            @php
                                $jamMulaiShort = substr((string) $s->jam_mulai, 0, 5);
                                $jamSelesaiShort = substr((string) $s->jam_selesai, 0, 5);
                                $targetKat = $s->target_kategori ?? 'semua_santri';
                                $kat = strtolower((string) ($s->kategori ?? 'sholat'));

                                $kategoriBadge = match($kat) {
                                    'sholat' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                                    'pengajian' => 'bg-teal-500/15 text-teal-300 border-teal-500/30',
                                    'sekolah' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                                    'makan' => 'bg-purple-500/15 text-purple-300 border-purple-500/30',
                                    'pegawai' => 'bg-sky-500/15 text-sky-300 border-sky-500/30',
                                    default => 'bg-slate-700/50 text-slate-300 border-slate-600',
                                };
                            @endphp
                            <tr x-show="matchesFilter(@js($s->kode_sesi), @js($s->nama_sesi), @js($s->nama_lokasi ?? ''), @js($s->device_id_target ?? ''), @js($kat), @js($s->target_filter ?? ''))"
                                class="hover:bg-slate-800/40 transition-colors group">

                                <!-- Kode Sesi -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-slate-950/90 text-slate-200 border border-slate-700/80">
                                            <span class="w-1.5 h-1.5 rounded-full"
                                                  :class="isTimeActive('{{ $jamMulaiShort }}', '{{ $jamSelesaiShort }}') ? 'bg-emerald-400 animate-ping' : 'bg-slate-600'"></span>
                                            {{ $s->kode_sesi }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Nama Sesi + Toleransi -->
                                <td class="py-4 px-5">
                                    <div class="font-bold text-white text-sm leading-snug">
                                        {{ $s->nama_sesi }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-2">
                                        @if(($s->toleransi_menit ?? 0) > 0)
                                            <span>Toleransi terlambat: <strong class="text-slate-300">{{ $s->toleransi_menit }} menit</strong></span>
                                        @else
                                            <span>Tanpa batas keterlambatan</span>
                                        @endif
                                        <template x-if="isTimeActive('{{ $jamMulaiShort }}', '{{ $jamSelesaiShort }}')">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                                AKTIF
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Jadwal Aktif -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs font-mono font-semibold text-slate-200">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $jamMulaiShort }} - {{ $jamSelesaiShort }} WIB</span>
                                    </div>
                                </td>

                                <!-- Perangkat ESP32 -->
                                <td class="py-4 px-5">
                                    @if($s->device_id_target)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-semibold bg-cyan-950/50 text-cyan-300 border border-cyan-500/30">
                                            <svg class="w-3.5 h-3.5 text-cyan-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                            <span>{{ $s->device_id_target }}</span>
                                        </div>
                                        <div class="text-xs text-slate-400 mt-1">{{ $s->nama_lokasi ?? 'Lokasi perangkat belum diatur' }}</div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>Semua Perangkat</span>
                                        </span>
                                        <div class="text-xs text-slate-500 mt-1">Berlaku lintas titik lokasi</div>
                                    @endif
                                </td>

                                <!-- Target Presensi -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    @if($targetKat === 'kamar_khusus')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                            Kamar: {{ $s->target_filter ?: 'Reguler' }}
                                        </span>
                                    @elseif($targetKat === 'sekolah')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            Sekolah ({{ $s->target_filter ?: 'Semua Siswa' }})
                                        </span>
                                    @elseif($targetKat === 'pegawai')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-500/15 text-sky-300 border border-sky-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                            Guru & Pegawai
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800/90 text-slate-200 border border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            Semua Santri
                                        </span>
                                    @endif
                                </td>

                                <!-- Kategori -->
                                <td class="py-4 px-5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold tracking-wider uppercase border {{ $kategoriBadge }}">
                                        {{ strtoupper($s->kategori) }}
                                    </span>
                                </td>

                                <!-- Aksi (Edit & Hapus) -->
                                <td class="py-4 px-5 whitespace-nowrap text-center">
                                    <div class="inline-flex items-center justify-center gap-1.5">
                                        <!-- Edit Button -->
                                        <button type="button"
                                                @click="openEditModal({{ $s->id }})"
                                                class="p-2 rounded-xl bg-slate-800/80 hover:bg-blue-600/20 text-slate-300 hover:text-blue-400 border border-slate-700/80 hover:border-blue-500/40 transition"
                                                title="Edit Sesi {{ $s->nama_sesi }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button type="button"
                                                @click="confirmDeleteSesi({{ $s->id }}, @js($s->nama_sesi), @js($s->kode_sesi))"
                                                class="p-2 rounded-xl bg-slate-800/80 hover:bg-rose-600/20 text-slate-300 hover:text-rose-400 border border-slate-700/80 hover:border-rose-500/40 transition"
                                                title="Hapus Sesi {{ $s->nama_sesi }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center mx-auto mb-3 text-slate-500">
                                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-sm font-bold text-white">Belum Ada Sesi Presensi</div>
                                    <p class="text-xs text-slate-400 mt-1">Klik tombol "Tambah Sesi Baru" untuk membuat jadwal presensi otomatis pertama.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- TAB 2: DAFTAR PERANGKAT MESIN ESP32                     -->
        <!-- ======================================================= -->
        <section x-show="activeTab === 'devices'" x-cloak class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @forelse($allDevices as $dev)
                @php
                    $isNewUnlabeled = str_contains(strtolower((string) $dev->nama_lokasi), 'belum diberi lokasi');
                    $lastSeenFormatted = $dev->last_seen_at
                        ? \Carbon\Carbon::parse($dev->last_seen_at)->diffForHumans()
                        : null;
                @endphp
                <div class="bg-slate-900/60 border {{ $dev->status_aktif ? ($isNewUnlabeled ? 'border-amber-500/40 hover:border-amber-400/60' : 'border-slate-800/90 hover:border-cyan-500/40') : 'border-slate-800/50 opacity-75' }} rounded-2xl p-5 shadow-lg transition flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="w-10 h-10 rounded-xl {{ $dev->status_aktif ? 'bg-cyan-500/15 border-cyan-500/30 text-cyan-400' : 'bg-slate-800 border-slate-700 text-slate-500' }} border flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($isNewUnlabeled)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                        Beri Nama Lokasi
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $dev->status_aktif ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dev->status_aktif ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                    {{ $dev->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Serial Hardware ESP32</div>
                            <div class="text-sm font-mono font-bold text-white break-all mt-0.5">{{ $dev->device_id }}</div>
                            <div class="text-xs {{ $isNewUnlabeled ? 'text-amber-300 font-semibold' : 'text-slate-300 font-medium' }} mt-1.5">
                                {{ $dev->nama_lokasi }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-mono mt-1.5 flex flex-wrap items-center gap-1.5">
                                @if(!empty($dev->ip_address))
                                    <span>IP: {{ $dev->ip_address }}</span>
                                    <span>•</span>
                                @endif
                                @if($lastSeenFormatted)
                                    <span class="text-emerald-400/90">Terhubung {{ $lastSeenFormatted }}</span>
                                @else
                                    <span>Belum pernah mengirim sinyal</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-3.5 border-t border-slate-800/80 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                                {{ strtoupper($dev->kategori_area) }}
                            </span>
                            <span class="text-xs text-slate-400 font-mono">{{ $dev->total_sesi }} sesi</span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <!-- Edit Lokasi -->
                            <button type="button"
                                    @click="openEditDeviceModal({{ $dev->id }}, @js($dev->device_id), @js($dev->nama_lokasi), @js($dev->kategori_area))"
                                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-blue-600/20 text-slate-300 hover:text-blue-400 border border-slate-700 hover:border-blue-500/40 transition"
                                    title="Ubah Nama Lokasi Alat">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            <!-- Toggle Aktif / Nonaktif -->
                            <form action="{{ route('pengaturan.sesi.device.toggle', $dev->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition"
                                        title="{{ $dev->status_aktif ? 'Nonaktifkan Perangkat' : 'Aktifkan Perangkat' }}">
                                    {{ $dev->status_aktif ? 'Matikan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <!-- Hapus Perangkat (Sesi terkait otomatis kembali ke Global) -->
                            <form action="{{ route('pengaturan.sesi.device.destroy', $dev->id) }}" method="POST"
                                  onsubmit="return confirm('Hapus perangkat {{ $dev->device_id }}? Sesi yang terhubung ke alat ini akan otomatis dikembalikan ke Semua Perangkat (Global).');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-600/20 text-slate-400 hover:text-rose-400 border border-slate-700 hover:border-rose-500/40 transition"
                                        title="Hapus Perangkat">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-slate-900/60 border border-slate-800 rounded-2xl p-10 sm:p-12 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-cyan-500/10 border border-cyan-500/25 text-cyan-400 flex items-center justify-center mx-auto mb-3.5">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div class="text-base font-extrabold text-white">Belum Ada Perangkat Fisik ESP32 Terdaftar</div>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1.5 max-w-xl mx-auto leading-relaxed">
                        Daftar dikosongkan agar tidak ada alat fiktif yang membingungkan. Begitu mesin ESP32 dinyalakan atau dipakai men-tap kartu,
                        <strong class="text-cyan-300">Serial Unik Hardware ESP32</strong> akan otomatis terdeteksi di sini dan Anda tinggal menamai lokasinya.
                    </p>
                    <button type="button"
                            @click="openCreateDeviceModal()"
                            class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-cyan-600/20 transition">
                        <span>Pindai / Daftarkan Serial ESP32 Sekarang</span>
                    </button>
                </div>
            @endforelse
        </section>

    </main>

    <!-- ======================================================= -->
    <!-- MODAL TAMBAH / EDIT SESI PRESENSI                       -->
    <!-- ======================================================= -->
    <div x-show="showModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
         @keydown.escape.window="showModal = false">

        <!-- Backdrop -->
        <div x-show="showModal"
             x-transition.opacity
             @click="showModal = false"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

        <!-- Modal Dialog -->
        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative w-full max-w-2xl bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden z-10">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         :class="modalMode === 'create' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-blue-500/15 text-blue-400 border border-blue-500/30'">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-white" x-text="modalMode === 'create' ? 'Tambah Sesi Presensi Baru' : 'Perbarui Pengaturan Sesi'"></h3>
                        <p class="text-xs text-slate-400">Konfigurasi rentang waktu tap kartu, mesin ESP32, dan target peserta.</p>
                    </div>
                </div>
                <button type="button" @click="showModal = false" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form Tambah / Update -->
            <form :action="modalMode === 'create' ? '{{ route('pengaturan.sesi.store') }}' : ('/pengaturan-sesi/' + form.id)" method="POST">
                @csrf
                <template x-if="modalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto custom-scroll">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <!-- Kode Sesi -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Kode Sesi (Unik) <span class="text-rose-400">*</span>
                            </label>
                            <input type="text"
                                   name="kode_sesi"
                                   x-model="form.kode_sesi"
                                   @input="form.kode_sesi = form.kode_sesi.toUpperCase().replace(/\s+/g, '_')"
                                   placeholder="Contoh: TAHFIDZ_SORE"
                                   required
                                   maxlength="30"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm font-mono font-semibold text-white placeholder-slate-500 outline-none transition">
                            <p class="text-[11px] text-slate-500 mt-1">Ditampilkan singkat pada layar LCD 16x2 mesin ESP32.</p>
                        </div>

                        <!-- Nama Sesi -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Nama Sesi <span class="text-rose-400">*</span>
                            </label>
                            <input type="text"
                                   name="nama_sesi"
                                   x-model="form.nama_sesi"
                                   placeholder="Contoh: Mengaji Tahfidz Sore"
                                   required
                                   maxlength="100"
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm text-white placeholder-slate-500 outline-none transition">
                            <p class="text-[11px] text-slate-500 mt-1">Ditampilkan pada header Dashboard Monitoring Live.</p>
                        </div>

                        <!-- Jam Mulai -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Jam Mulai <span class="text-rose-400">*</span>
                            </label>
                            <input type="time"
                                   name="jam_mulai"
                                   x-model="form.jam_mulai"
                                   required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm font-mono text-white outline-none transition">
                        </div>

                        <!-- Jam Selesai -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Jam Selesai <span class="text-rose-400">*</span>
                            </label>
                            <input type="time"
                                   name="jam_selesai"
                                   x-model="form.jam_selesai"
                                   required
                                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm font-mono text-white outline-none transition">
                        </div>

                        <!-- Perangkat Khusus (ESP32) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Perangkat ESP32 Target
                            </label>
                            <select name="device_id_target"
                                    x-model="form.device_id_target"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm text-white outline-none transition">
                                @if($devices->isEmpty())
                                    <option value="">Semua Perangkat / Global (Belum ada alat terdaftar)</option>
                                @else
                                    <option value="">Semua Perangkat (Global)</option>
                                    @foreach($devices as $d)
                                        <option value="{{ $d->device_id }}">{{ $d->nama_lokasi }} ({{ $d->device_id }})</option>
                                    @endforeach
                                @endif
                            </select>
                            @if($devices->isEmpty())
                                <p class="text-[11px] text-slate-500 mt-1">Belum ada mesin spesifik terdaftar. Sesi berlaku otomatis untuk alat mana pun yang aktif.</p>
                            @else
                                <p class="text-[11px] text-slate-500 mt-1">Pilih lokasi &amp; serial spesifik, atau biarkan Global.</p>
                            @endif
                        </div>

                        <!-- Toleransi Menit & Kategori -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                    Toleransi (Menit)
                                </label>
                                <input type="number"
                                       name="toleransi_menit"
                                       x-model="form.toleransi_menit"
                                       min="0"
                                       max="240"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm font-mono text-white outline-none transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                    Kategori Sesi
                                </label>
                                <select name="kategori"
                                        x-model="form.kategori"
                                        class="w-full px-3 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm text-white outline-none transition">
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
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Target Partisipan
                            </label>
                            <select name="target_kategori"
                                    x-model="form.target_kategori"
                                    @change="onTargetKategoriChange()"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-emerald-500 text-sm text-white outline-none transition">
                                <option value="semua_santri">Semua Santri</option>
                                <option value="kamar_khusus">Kamar Tertentu</option>
                                <option value="sekolah">Siswa Sekolah (SMP / SMA)</option>
                                <option value="pegawai">Guru & Pegawai</option>
                            </select>
                        </div>

                        <!-- Filter Target Dinamis -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                                Filter Target (Kamar / Jenjang)
                            </label>
                            <input type="hidden"
                                   name="target_filter"
                                   :value="(form.target_kategori === 'kamar_khusus' || form.target_kategori === 'sekolah') ? form.target_filter : ''">

                            <!-- Mode Kamar Khusus -->
                            <div x-show="form.target_kategori === 'kamar_khusus'">
                                <input type="text"
                                       x-model="form.target_filter"
                                       list="daftarKamarSantri"
                                       placeholder="Pilih atau ketik kamar (misal: Reguler)"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-blue-500/40 focus:border-blue-400 text-sm text-white placeholder-slate-500 outline-none transition">
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
                                                    :class="form.target_filter === @js($kamar) ? 'bg-blue-600 text-white border-blue-500' : 'bg-slate-800 text-slate-300 border-slate-700 hover:border-slate-500'"
                                                    class="px-2 py-0.5 rounded-md text-[11px] font-semibold border transition">
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
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-amber-500/40 focus:border-amber-400 text-sm text-white placeholder-slate-500 outline-none transition">
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <template x-for="jenjang in ['SMP', 'SMA', 'ALL']" :key="jenjang">
                                        <button type="button"
                                                @click="form.target_filter = jenjang"
                                                :class="form.target_filter === jenjang ? 'bg-amber-500 text-slate-950 border-amber-400 font-bold' : 'bg-slate-800 text-slate-300 border-slate-700 hover:border-slate-500 font-semibold'"
                                                class="px-2.5 py-0.5 rounded-md text-[11px] border transition"
                                                x-text="jenjang === 'ALL' ? 'ALL (SMP & SMA)' : jenjang">
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Mode Global (Semua Santri / Pegawai) -->
                            <div x-show="form.target_kategori === 'semua_santri' || form.target_kategori === 'pegawai'"
                                 class="px-3.5 py-2.5 rounded-xl bg-slate-950/50 border border-slate-800 text-xs text-slate-400">
                                Otomatis mencakup seluruh <span class="text-slate-200 font-semibold" x-text="form.target_kategori === 'pegawai' ? 'Guru & Pegawai' : 'Santri Aktif'"></span> tanpa filter tambahan.
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/50 flex items-center justify-end gap-2.5">
                    <button type="button"
                            @click="showModal = false"
                            class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-white text-xs sm:text-sm font-bold shadow-lg transition"
                            :class="modalMode === 'create' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-600/20' : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 shadow-blue-600/20'"
                            x-text="modalMode === 'create' ? 'Simpan Sesi Baru' : 'Simpan Perubahan'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL KONFIRMASI HAPUS SESI                             -->
    <!-- ======================================================= -->
    <div x-show="deleteTarget.show" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="deleteTarget.show = false">
        <div x-show="deleteTarget.show" x-transition.opacity @click="deleteTarget.show = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

        <div x-show="deleteTarget.show"
             x-transition
             class="relative w-full max-w-md bg-slate-900 border border-rose-500/30 rounded-2xl shadow-2xl p-6 z-10">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-400 flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-white">Hapus Sesi Presensi?</h3>
            <p class="text-xs sm:text-sm text-slate-400 mt-1.5 leading-relaxed">
                Anda akan menghapus sesi <strong class="text-white" x-text="deleteTarget.nama"></strong>
                (<span class="font-mono text-rose-300" x-text="deleteTarget.kode"></span>). Riwayat presensi yang terhubung ke sesi ini juga akan ikut terhapus.
            </p>

            <form :action="'/pengaturan-sesi/' + deleteTarget.id" method="POST" class="mt-6 flex items-center justify-end gap-2.5">
                @csrf
                @method('DELETE')
                <button type="button"
                        @click="deleteTarget.show = false"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-rose-600/25 transition">
                    Ya, Hapus Sesi
                </button>
            </form>
        </div>
    </div>

    <!-- ======================================================= -->
    <!-- MODAL DETEKSI / DAFTARKAN / EDIT LOKASI MESIN ESP32     -->
    <!-- ======================================================= -->
    <div x-show="showDeviceModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="closeDeviceModal()">
        <div x-show="showDeviceModal" x-transition.opacity @click="closeDeviceModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

        <div x-show="showDeviceModal"
             x-transition
             class="relative w-full max-w-lg bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden z-10">
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-950/50">
                <div>
                    <h3 class="text-base font-extrabold text-white"
                        x-text="deviceModalMode === 'create' ? 'Deteksi / Daftarkan Serial ESP32' : 'Ubah Nama Lokasi Perangkat'"></h3>
                    <p class="text-xs text-slate-400"
                       x-text="deviceModalMode === 'create' ? 'Nyalakan ESP32 atau tap 1 kartu agar Serial Hardware terbaca otomatis.' : 'Berikan nama lokasi agar mudah dikenali pada pilihan sesi.'"></p>
                </div>
                <button type="button" @click="closeDeviceModal()" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form :action="deviceModalMode === 'create' ? '{{ route('pengaturan.sesi.device.store') }}' : ('/pengaturan-sesi/device/' + deviceForm.id)" method="POST">
                @csrf
                <template x-if="deviceModalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="p-6 space-y-4">

                    <!-- Radar Auto-Detect Serial Live (Hanya saat mode Create) -->
                    <div x-show="deviceModalMode === 'create'"
                         class="p-3.5 rounded-xl border transition"
                         :class="detectedDevice.device_id ? 'bg-cyan-950/40 border-cyan-500/40' : 'bg-slate-950/60 border-slate-800'">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full"
                                      :class="detectedDevice.device_id ? 'bg-cyan-400 animate-ping' : 'bg-slate-500 animate-pulse'"></span>
                                <span class="text-xs font-bold uppercase tracking-wider"
                                      :class="detectedDevice.device_id ? 'text-cyan-300' : 'text-slate-400'"
                                      x-text="detectedDevice.device_id ? 'Serial Alat Terdeteksi!' : 'Memindai Sinyal MQTT ESP32...'"></span>
                            </div>
                            <button type="button"
                                    x-show="detectedDevice.device_id"
                                    @click="deviceForm.device_id = detectedDevice.device_id"
                                    class="px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-[11px] font-bold transition">
                                Pakai Serial Ini
                            </button>
                        </div>

                        <template x-if="detectedDevice.device_id">
                            <div class="mt-2 text-xs text-slate-300 font-mono">
                                ID: <strong class="text-white" x-text="detectedDevice.device_id"></strong>
                                <span x-show="detectedDevice.ip" x-text="' • IP: ' + detectedDevice.ip"></span>
                                <span x-show="detectedDevice.timestamp" class="text-slate-400" x-text="' (' + detectedDevice.timestamp + ')'"></span>
                            </div>
                        </template>
                        <template x-if="!detectedDevice.device_id">
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                Colokkan daya ke ESP32 atau tempelkan kartu apa saja pada reader — nomor seri akan muncul otomatis di sini.
                            </p>
                        </template>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Serial Hardware ESP32 (Unik) <span class="text-rose-400">*</span>
                        </label>
                        <input type="text"
                               name="device_id"
                               x-model="deviceForm.device_id"
                               :readonly="deviceModalMode === 'edit'"
                               required
                               maxlength="50"
                               placeholder="Contoh: ESP32-A4CF1298B3E0"
                               :class="deviceModalMode === 'edit' ? 'opacity-70 cursor-not-allowed' : ''"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-cyan-500 text-sm font-mono font-bold text-white placeholder-slate-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                            Nama Lokasi Pemasangan <span class="text-rose-400">*</span>
                        </label>
                        <input type="text"
                               name="nama_lokasi"
                               x-model="deviceForm.nama_lokasi"
                               required
                               maxlength="100"
                               placeholder="Contoh: Pintu Kamar Reguler / Teras Masjid"
                               class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-cyan-500 text-sm text-white placeholder-slate-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">Kategori Area</label>
                        <select name="kategori_area"
                                x-model="deviceForm.kategori_area"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/90 border border-slate-700/80 focus:border-cyan-500 text-sm text-white outline-none transition">
                            <option value="pondok">Pondok / Asrama</option>
                            <option value="sekolah">Sekolah (SMP / SMA)</option>
                            <option value="dapur">Dapur / Ruang Makan</option>
                            <option value="umum">Area Umum / Kantor</option>
                        </select>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/50 flex items-center justify-end gap-2.5">
                    <button type="button" @click="closeDeviceModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-cyan-600/20 transition"
                            x-text="deviceModalMode === 'create' ? 'Simpan Perangkat' : 'Simpan Nama Lokasi'">
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ALPINE.JS LOGIC -->
    <script>
        function pengaturanSesiApp() {
            return {
                activeTab: @json($initialTab),
                clock: '00:00:00',
                currentHHMM: '00:00',
                searchQuery: '',
                filterKategori: 'all',
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

                matchesFilter(kode, nama, lokasi, device, kategori, targetFilter) {
                    if (this.filterKategori !== 'all' && kategori !== this.filterKategori) {
                        return false;
                    }
                    if (!this.searchQuery.trim()) {
                        return true;
                    }
                    const q = this.searchQuery.toLowerCase().trim();
                    return (kode && kode.toLowerCase().includes(q))
                        || (nama && nama.toLowerCase().includes(q))
                        || (lokasi && lokasi.toLowerCase().includes(q))
                        || (device && device.toLowerCase().includes(q))
                        || (targetFilter && targetFilter.toLowerCase().includes(q));
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
                        nama_lokasi: namaLokasi.includes('Belum Diberi Lokasi') ? '' : namaLokasi,
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
</body>
</html>
