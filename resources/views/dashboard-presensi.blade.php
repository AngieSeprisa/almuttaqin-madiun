<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Presensi Real-Time - Yayasan Muttaqin Kota Madiun</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN Standalone for Instant Projector Rendering) -->
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
        .custom-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        .custom-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #475569; }
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(1.02); }
        }
        .pulse-live { animation: pulse-subtle 2s infinite ease-in-out; }
    </style>
</head>
<body class="bg-[#0b1120] text-slate-100 min-h-screen font-sans antialiased overflow-x-hidden"
      x-data="dashboardApp()"
      x-init="initApp()">

    <!-- TOP HEADER BAR -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            
            <!-- Logo & Brand -->
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-500/20 ring-1 ring-white/20">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold tracking-tight text-white flex items-center gap-2">
                        <span>Pondok Pesantren Al Muttaqin</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Kota Madiun
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">Monitoring Presensi RFID Multi-Lokasi Real-Time</p>
                </div>
            </div>

            <!-- Sesi Aktif Badge -->
            <div class="flex items-center gap-3 bg-slate-800/70 border border-slate-700/60 rounded-2xl px-4 py-2 shadow-inner">
                <div class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </div>
                <div>
                    <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">Sesi Aktif Sekarang:</div>
                    <div class="text-sm font-bold text-white flex items-center gap-2">
                        <span x-text="sesiAktif.nama || 'Memuat Sesi...'"></span>
                        <span class="text-xs text-emerald-400 font-mono" x-show="sesiAktif.jam_mulai" x-text="'(' + sesiAktif.jam_mulai.substring(0,5) + ' - ' + sesiAktif.jam_selesai.substring(0,5) + ')'"></span>
                    </div>
                </div>
            </div>

            <!-- Jam Digital Real-Time & Tanggal -->
            <div class="text-right flex items-center gap-4">
                <div class="hidden sm:block text-right">
                    <div class="text-2xl font-black font-mono tracking-tight text-white" x-text="clock">00:00:00</div>
                    <div class="text-xs font-medium text-slate-400" x-text="currentDate">Memuat Tanggal...</div>
                </div>
                <a href="{{ route('siswa') }}" class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 transition" title="Kembali ke Dashboard Utama">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- 3 TAB NAVIGATION & FILTER SECTION -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-slate-900/40 p-2 rounded-2xl border border-slate-800/80">
            
            <!-- Tab Buttons (3 Mode) -->
            <div class="grid grid-cols-3 sm:flex items-center gap-1.5 p-1 bg-slate-950/50 rounded-xl border border-slate-800">
                <button type="button"
                        @click="changeTab('pondok')"
                        :class="currentTab === 'pondok' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30 font-semibold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="px-4 py-2.5 rounded-lg text-xs sm:text-sm transition flex items-center justify-center gap-2">
                    <span class="text-base">🕌</span>
                    <span>Pondok / Asrama</span>
                </button>

                <button type="button"
                        @click="changeTab('sekolah')"
                        :class="currentTab === 'sekolah' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 font-semibold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="px-4 py-2.5 rounded-lg text-xs sm:text-sm transition flex items-center justify-center gap-2">
                    <span class="text-base">🏫</span>
                    <span>Sekolah (SMP/SMA)</span>
                </button>

                <button type="button"
                        @click="changeTab('pegawai')"
                        :class="currentTab === 'pegawai' ? 'bg-purple-600 text-white shadow-lg shadow-purple-600/30 font-semibold' : 'text-slate-400 hover:text-slate-200 font-medium'"
                        class="px-4 py-2.5 rounded-lg text-xs sm:text-sm transition flex items-center justify-center gap-2">
                    <span class="text-base">👥</span>
                    <span>Pegawai & Guru</span>
                </button>
            </div>

            <!-- Dropdown Filters (Sesi & Kamar) -->
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Filter Sesi -->
                <div class="flex items-center gap-2 bg-slate-800/70 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs text-slate-300">
                    <span class="text-slate-400 font-medium">Sesi:</span>
                    <select x-model="selectedSesi" @change="fetchData()" class="bg-transparent text-white font-semibold outline-none cursor-pointer">
                        <option value="" class="bg-slate-900 text-white">Otomatis (Sesi Saat Ini)</option>
                        @foreach($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" class="bg-slate-900 text-white">{{ $sesi->nama_sesi }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kamar (Khusus Tab Pondok) -->
                <div x-show="currentTab === 'pondok'" class="flex items-center gap-2 bg-slate-800/70 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs text-slate-300">
                    <span class="text-slate-400 font-medium">Kamar:</span>
                    <select x-model="selectedKamar" @change="fetchData()" class="bg-transparent text-emerald-400 font-bold outline-none cursor-pointer">
                        <option value="Reguler" class="bg-slate-900 text-white">Kamar Reguler (16 Santri Target)</option>
                        <option value="all" class="bg-slate-900 text-white">Semua Kamar Asrama</option>
                        @foreach($kamars as $kmr)
                            @if($kmr !== 'Reguler')
                                <option value="{{ $kmr }}" class="bg-slate-900 text-white">Kamar {{ $kmr }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Auto Refresh Indicator -->
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/40 border border-slate-800 text-[11px] text-slate-400 font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live 3s</span>
                </div>
            </div>
        </div>

        <!-- 3 KARTU RINGKASAN BESAR (STATISTIK) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
            
            <!-- Total Target -->
            <div class="bg-slate-900/60 border border-slate-800/90 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Wajib Hadir</div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-white mt-1.5 font-mono" x-text="summary.totalTarget">0</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-slate-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-400" x-text="currentTab === 'pondok' ? 'Santri Mukim Terdata' : (currentTab === 'sekolah' ? 'Siswa Formal Terdaftar' : 'Pegawai Yayasan')"></div>
            </div>

            <!-- Sudah Hadir (Hijau) -->
            <div class="bg-gradient-to-br from-emerald-950/40 to-slate-900/80 border border-emerald-500/30 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Sudah Tap Hadir</div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-emerald-400 mt-1.5 font-mono" x-text="summary.sudahHadir">0</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" :style="'width: ' + summary.persentase + '%'"></div>
                    </div>
                    <span class="text-xs font-bold text-emerald-400 font-mono" x-text="summary.persentase + '%'">0%</span>
                </div>
            </div>

            <!-- Belum Hadir (Merah) -->
            <div class="bg-gradient-to-br from-rose-950/40 to-slate-900/80 border border-rose-500/30 rounded-2xl p-5 shadow-lg relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-rose-400">Belum Tap / Absen</div>
                        <div class="text-3xl sm:text-4xl font-extrabold text-rose-400 mt-1.5 font-mono" x-text="summary.belumHadir">0</div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center text-rose-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-xs text-rose-300 font-medium">Perlu disisir oleh pamong / wali kelas</div>
            </div>
        </div>

        <!-- TWO-COLUMN SPLIT LAYOUT -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- KOLOM KIRI: LIVE TAP FEED (5 Kolom) -->
            <div class="lg:col-span-5 bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col h-[650px]">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></div>
                        <h2 class="text-sm font-bold text-white tracking-wide uppercase">Live Tap Feed (Aktivitas Terakhir)</h2>
                    </div>
                    <span class="text-xs text-slate-400 font-mono" x-text="liveFeeds.length + ' Tap Terakhir'"></span>
                </div>

                <!-- Feed List -->
                <div class="flex-1 overflow-y-auto space-y-2.5 mt-3.5 pr-1 custom-scroll">
                    <template x-for="(feed, index) in liveFeeds" :key="feed.id">
                        <div class="p-3.5 rounded-xl bg-slate-800/60 border border-slate-700/60 hover:border-emerald-500/50 transition flex items-center justify-between gap-3"
                             :class="index === 0 ? 'ring-1 ring-emerald-500/50 bg-emerald-950/20' : ''">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-slate-700/60 border border-slate-600 flex items-center justify-center font-bold text-sm text-emerald-400 shrink-0"
                                     x-text="feed.nama ? feed.nama.substring(0, 2).toUpperCase() : 'ST'">
                                </div>
                                <div class="truncate">
                                    <div class="text-sm font-bold text-white truncate" x-text="feed.nama"></div>
                                    <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                        <span class="text-emerald-400 font-medium" x-text="feed.kamar_or_role"></span>
                                        <span>•</span>
                                        <span x-text="feed.sesi"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-mono font-bold text-emerald-400" x-text="feed.waktu"></div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mt-1">
                                    Berhasil Tap
                                </span>
                            </div>
                        </div>
                    </template>

                    <div x-show="liveFeeds.length === 0" class="text-center py-20 text-slate-500 text-xs">
                        Belum ada aktivitas tap kartu untuk hari ini.
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: WAITING LIST / BELUM HADIR (7 Kolom) -->
            <div class="lg:col-span-7 bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col h-[650px]">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-rose-500"></div>
                        <h2 class="text-sm font-bold text-white tracking-wide uppercase">Waiting List (Belum Tap Pada Sesi Ini)</h2>
                    </div>
                    <span class="text-xs font-bold text-rose-400 font-mono" x-text="waitingList.length + ' Belum Hadir'"></span>
                </div>

                <!-- Waiting List Grid / Cards -->
                <div class="flex-1 overflow-y-auto mt-3.5 pr-1 custom-scroll">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="item in waitingList" :key="item.id">
                            <div class="p-3.5 rounded-xl bg-slate-800/40 border border-rose-500/20 hover:border-rose-500/50 transition flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-rose-950/40 border border-rose-500/30 flex items-center justify-center font-bold text-sm text-rose-400 shrink-0"
                                         x-text="item.nama ? item.nama.substring(0, 2).toUpperCase() : '?'">
                                    </div>
                                    <div class="truncate">
                                        <div class="text-sm font-semibold text-slate-100 truncate" x-text="item.nama"></div>
                                        <div class="text-xs text-slate-400 mt-0.5" x-text="item.sub"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        Belum Absen
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- All Present Banner -->
                    <div x-show="waitingList.length === 0 && summary.totalTarget > 0" class="text-center py-20">
                        <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-white">Alhamdulillah, Semua Sudah Hadir!</h3>
                        <p class="text-xs text-slate-400 mt-1">Seluruh target entitas telah berhasil melakukan presensi tap RFID.</p>
                    </div>

                    <div x-show="summary.totalTarget === 0" class="text-center py-20 text-slate-500 text-xs">
                        Tidak ada entitas terdaftar pada kategori yang dipilih.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ALPINE.JS COMPONENT LOGIC -->
    <script>
        function dashboardApp() {
            return {
                currentTab: 'pondok',
                selectedSesi: '',
                selectedKamar: 'Reguler',
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
                timerInterval: null,

                initApp() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    
                    // Fetch data pertama kali
                    this.fetchData();

                    // Polling real-time setiap 3 detik
                    this.timerInterval = setInterval(() => {
                        this.fetchData();
                    }, 3000);
                },

                updateClock() {
                    const now = new Date();
                    this.clock = now.toLocaleTimeString('id-ID', { hour12: false });
                },

                changeTab(tab) {
                    this.currentTab = tab;
                    this.fetchData();
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
                            this.currentDate = data.currentDate;
                            this.sesiAktif = data.sesiAktif || {};
                            this.summary = data.summary || this.summary;
                            this.liveFeeds = data.liveFeeds || [];
                            this.waitingList = data.waitingList || [];
                        })
                        .catch(err => console.error("Error fetching live data:", err));
                }
            }
        }
    </script>
</body>
</html>
