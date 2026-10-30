@php
    $authUser = auth()->user();
    $accessibleLembagas = $authUser ? $authUser->accessibleLembagas() : collect();
    $currentLembaga = $authUser ? $authUser->currentLembaga() : null;

    $brandLogo = $currentLembaga ? $currentLembaga->logo_url : asset('images/logo.png');
    $brandTitle = $currentLembaga ? $currentLembaga->brand_title : 'PONDOK PESANTREN AL MUTTAQIN';
    $brandAlt = $brandTitle;

    $isBeranda = request()->routeIs('beranda*') || request()->is('beranda*') || request()->routeIs('dashboard*') || request()->is('dashboard*') || request()->is('/');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Beranda') - {{ $brandTitle }}</title>
    <link rel="icon" type="image/png" href="{{ $brandLogo }}">
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay"></div>

    <div class="app-layout">
        <!-- Floating Sidebar -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-header">
                <a href="{{ route('beranda') }}" class="brand-wrapper" title="{{ $brandAlt }}" data-original-title="{{ $brandAlt }}">
                    <div class="brand-logo-wrap">
                        <img src="{{ $brandLogo }}" alt="Logo {{ $brandAlt }}" class="brand-logo-img">
                    </div>
                    <div class="brand-info">
                        <span class="brand-title">{{ $brandTitle }}</span>
                    </div>
                </a>
                
                <!-- Collapse Button on top right of sidebar -->
                <button type="button" class="sidebar-collapse-btn js-sidebar-toggle" aria-label="Buka/Tutup Menu" title="Buka/Tutup Menu">
                    <svg class="sidebar-icon-desktop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2.5"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    <svg class="sidebar-icon-mobile" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            {{-- Dropdown Pindah Lembaga (Hanya tampil untuk akun yang memiliki akses ke lebih dari 1 lembaga) --}}
            @if($authUser && $currentLembaga && $accessibleLembagas->count() > 1)
            <div class="sidebar-room-area">
                <div class="sidebar-room-wrap" id="sidebarRoomWrap">
                    <button type="button" class="sidebar-room-btn" id="sidebarRoomToggle" aria-label="Pilih Lembaga" title="{{ $currentLembaga->brand_title }}">
                        <span class="sidebar-room-current-name">{{ $currentLembaga->brand_title }}</span>
                        <svg class="sidebar-room-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="sidebar-room-dropdown" id="sidebarRoomMenu">
                        <div class="sidebar-room-list">
                            @foreach($accessibleLembagas as $lem)
                            <a href="{{ route('switch-lembaga', ['kode' => $lem->kode]) }}" class="sidebar-room-item {{ $currentLembaga->kode === $lem->kode ? 'active' : '' }}">
                                <span class="sidebar-room-item-name">{{ $lem->brand_title }}</span>
                                @if($currentLembaga->kode === $lem->kode)
                                <svg class="sidebar-room-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <nav class="sidebar-nav">
                <div class="sidebar-section-label">Menu</div>

                <a href="{{ route('beranda') }}" class="nav-item {{ request()->routeIs('beranda') ? 'active' : '' }}" title="Beranda">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </span>
                    <span class="nav-item-text">Beranda</span>
                </a>

                @if(!$authUser || $authUser->canDo('siswa', 'view'))
                <a href="{{ route('siswa') }}" class="nav-item {{ request()->routeIs('siswa*') ? 'active' : '' }}" title="Siswa">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </span>
                    <span class="nav-item-text">Siswa</span>
                </a>

                <a href="{{ route('nfc.index') }}" class="nav-item {{ request()->routeIs('nfc.*') ? 'active' : '' }}" title="Input NFC">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                        </svg>
                    </span>
                    <span class="nav-item-text">Input NFC</span>
                </a>
                @endif

                {{-- Menu Sekolah dengan Submenu Jurnal, Rekap Jurnal, Jadwal Pelajaran & Jadwal Mengajar Saya --}}
                @if(!$authUser || $authUser->canDo('sekolah', 'view') || $authUser->canDo('jadwal_pelajaran', 'view'))
                <div class="nav-group {{ request()->routeIs('sekolah.jurnal*') || request()->routeIs('sekolah.rekap-jurnal*') || request()->routeIs('jadwal-pelajaran.*') ? 'open' : '' }}" id="navGroupSekolah">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('sekolah.jurnal*') || request()->routeIs('sekolah.rekap-jurnal*') || request()->routeIs('jadwal-pelajaran.*') ? 'active' : '' }}" id="toggleSekolah" title="Sekolah">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                            </svg>
                        </span>
                        <span class="nav-item-text">Sekolah</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        <a href="{{ route('sekolah.jurnal') }}" class="subnav-item {{ request()->routeIs('sekolah.jurnal*') ? 'active' : '' }}" title="Jurnal Pembelajaran">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Jurnal</span>
                        </a>
                        <a href="{{ route('sekolah.rekap-jurnal') }}" class="subnav-item {{ request()->routeIs('sekolah.rekap-jurnal*') ? 'active' : '' }}" title="Rekap Jurnal Pembelajaran">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Rekap Jurnal</span>
                        </a>
                        <a href="{{ route('jadwal-pelajaran.index') }}" class="subnav-item {{ request()->routeIs('jadwal-pelajaran.index') || request()->routeIs('jadwal-pelajaran.cetak') ? 'active' : '' }}" title="Master Jadwal Pelajaran (SMP & SMA)">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Jadwal Pelajaran</span>
                        </a>
                        <a href="{{ route('jadwal-pelajaran.saya') }}" class="subnav-item {{ request()->routeIs('jadwal-pelajaran.saya*') ? 'active' : '' }}" title="Jadwal Mengajar Saya">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Jadwal Mengajar Saya</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- Menu Perizinan dengan Submenu Sambang & Sakit --}}
                @if(!$authUser || $authUser->canDo('perizinan_sambang', 'view') || $authUser->canDo('perizinan_sakit', 'view'))
                <div class="nav-group {{ request()->routeIs('perizinan.*') ? 'open' : '' }}" id="navGroupPerizinan">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('perizinan.*') ? 'active' : '' }}" id="togglePerizinan" title="Perizinan">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                <path d="M9 14l2 2 4-4"></path>
                            </svg>
                        </span>
                        <span class="nav-item-text">Perizinan</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        @if(!$authUser || $authUser->canDo('perizinan_sambang', 'view'))
                        <a href="{{ route('perizinan.sambang') }}" class="subnav-item {{ request()->routeIs('perizinan.sambang*') ? 'active' : '' }}" title="Perizinan Sambang">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Sambang</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('perizinan_sakit', 'view'))
                        <a href="{{ route('perizinan.sakit') }}" class="subnav-item {{ request()->routeIs('perizinan.sakit*') ? 'active' : '' }}" title="Perizinan Sakit">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Sakit</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @if(!$authUser || $authUser->canDo('pendaftaran', 'view'))
                <a href="{{ route('pendaftaran.index') }}" class="nav-item {{ request()->routeIs('pendaftaran*') ? 'active' : '' }}" title="Pendaftaran">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </span>
                    <span class="nav-item-text">Pendaftaran</span>
                </a>
                @endif

                {{-- Menu Presensi Guru --}}

                @if(!$authUser || $authUser->canDo('presensi', 'view'))
                <a href="{{ route('presensi.index') }}" class="nav-item {{ request()->routeIs('presensi*') ? 'active' : '' }}" title="Presensi Guru">
                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </span>
                    <span class="nav-item-text">Presensi Guru</span>
                </a>
                @endif

                {{-- Menu Rekap dengan Submenu Sekolah & Guru --}}
                @if(!$authUser || $authUser->canDo('rekap_absensi', 'view') || $authUser->canDo('sekolah', 'view'))
                <div class="nav-group {{ request()->routeIs('rekap-absensi.*') || request()->routeIs('sekolah.rekap-absensi*') ? 'open' : '' }}" id="navGroupRekapAbsensi">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('rekap-absensi.*') || request()->routeIs('sekolah.rekap-absensi*') ? 'active' : '' }}" id="toggleRekapAbsensi" title="Rekap">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </span>
                        <span class="nav-item-text">Rekap</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        @if(!$authUser || $authUser->canDo('sekolah', 'view'))
                        <a href="{{ route('rekap-absensi.sekolah') }}" class="subnav-item {{ request()->routeIs('rekap-absensi.sekolah*') || request()->routeIs('sekolah.rekap-absensi*') ? 'active' : '' }}" title="Rekap Absensi Sekolah">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Sekolah</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('rekap_absensi', 'view'))
                        <a href="{{ route('rekap-absensi.guru') }}" class="subnav-item {{ request()->routeIs('rekap-absensi.guru*') ? 'active' : '' }}" title="Rekap Absensi Guru">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Guru</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Menu Data Master dengan Submenu Ruang Kelas, Jam Pelajaran & Lembaga --}}
                @if(!$authUser || $authUser->canDo('data_master', 'view') || $authUser->canDo('master_kelas', 'view') || $authUser->canDo('master_jam', 'view') || $authUser->canDo('master_lembaga', 'view'))
                <div class="nav-group {{ request()->routeIs('master.*') ? 'open' : '' }}" id="navGroupDataMaster">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('master.*') ? 'active' : '' }}" id="toggleDataMaster" title="Data Master">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            </svg>
                        </span>
                        <span class="nav-item-text">Data Master</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        @if(!$authUser || $authUser->canDo('master_jam', 'view') || $authUser->canDo('data_master', 'view'))
                        <a href="{{ route('master.jam-pelajaran.index') }}" class="subnav-item {{ request()->routeIs('master.jam-pelajaran.*') ? 'active' : '' }}" title="Master Jam Pelajaran">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Jam Pelajaran</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('master_lembaga', 'view') || $authUser->canDo('data_master', 'view'))
                        <a href="{{ route('master.lembaga.index') }}" class="subnav-item {{ request()->routeIs('master.lembaga.*') ? 'active' : '' }}" title="Master Lembaga Pendidikan">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Lembaga</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('master_import_siswa', 'view') || $authUser->canDo('data_master', 'view'))
                        <a href="{{ route('master.import-siswa.index') }}" class="subnav-item {{ request()->routeIs('master.import-siswa.*') ? 'active' : '' }}" title="Import Data Siswa">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Import Siswa</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Menu Pengaturan Sekolah dengan Submenu Absensi Guru, Guru Sekolah, Plotting Mata Pelajaran & Plotting Kelas --}}
                @if(!$authUser || $authUser->canDo('pengaturan_absensi', 'view') || $authUser->canDo('guru_sekolah', 'view') || $authUser->canDo('plotting_mapel', 'view') || $authUser->canDo('plotting_kelas', 'view'))
                <div class="nav-group {{ request()->routeIs('pengaturan-absensi.*') || request()->routeIs('guru-sekolah.*') || request()->routeIs('plotting-mapel.*') || request()->routeIs('plotting-kelas.*') || request()->routeIs('pengaturan.*') ? 'open' : '' }}" id="navGroupPengaturan">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('pengaturan-absensi.*') || request()->routeIs('guru-sekolah.*') || request()->routeIs('plotting-mapel.*') || request()->routeIs('plotting-kelas.*') || request()->routeIs('pengaturan.*') ? 'active' : '' }}" id="togglePengaturan" title="Pengaturan Sekolah">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                        </span>
                        <span class="nav-item-text">Pengaturan Sekolah</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        @if(!$authUser || $authUser->canDo('pengaturan_absensi', 'view'))
                        <a href="{{ route('pengaturan-absensi.index') }}" class="subnav-item {{ request()->routeIs('pengaturan-absensi*') ? 'active' : '' }}" title="Absensi Guru">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Absensi Guru</span>
                        </a>
                        <a href="{{ route('pengaturan.sesi') }}" class="subnav-item {{ request()->routeIs('pengaturan.sesi*') ? 'active' : '' }}" title="Pengaturan Sesi & Perangkat Presensi RFID">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Sesi Presensi RFID</span>
                        </a>
                        <a href="{{ route('dashboard-presensi') }}" class="subnav-item {{ request()->routeIs('dashboard-presensi*') ? 'active' : '' }}" title="Dashboard Monitoring Presensi RFID Real-Time">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Monitor Live RFID</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->isYayasan() || $authUser->canDo('guru_sekolah', 'view'))
                        <a href="{{ route('guru-sekolah.index') }}" class="subnav-item {{ request()->routeIs('guru-sekolah*') ? 'active' : '' }}" title="Guru Sekolah" style="position:relative;">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Guru Sekolah</span>
                            @php
                                $pendingGuruCount = \App\Models\User::where('mapel_status', 'menunggu')->count();
                            @endphp
                            @if($pendingGuruCount > 0)
                            <span style="margin-left:auto; background:#EF4444; color:#ffffff; font-size:10.5px; font-weight:700; padding:1px 6px; border-radius:10px; line-height:1.2;">{{ $pendingGuruCount }}</span>
                            @endif
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('plotting_mapel', 'view'))
                        <a href="{{ route('plotting-mapel.index') }}" class="subnav-item {{ request()->routeIs('plotting-mapel*') ? 'active' : '' }}" title="Plotting Mata Pelajaran">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Plotting Mata Pelajaran</span>
                        </a>
                        @endif

                        @if(!$authUser || $authUser->canDo('plotting_kelas', 'view'))
                        <a href="{{ route('plotting-kelas.index') }}" class="subnav-item {{ request()->routeIs('plotting-kelas*') ? 'active' : '' }}" title="Plotting Kelas">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Plotting Kelas</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Menu Pengaturan Pondok dengan Submenu Plotting Kamar --}}
                @if(!$authUser || $authUser->canDo('pengaturan_pondok', 'view') || $authUser->canDo('plotting_kamar', 'view'))
                <div class="nav-group {{ request()->routeIs('plotting-kamar.*') ? 'open' : '' }}" id="navGroupPengaturanPondok">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('plotting-kamar.*') ? 'active' : '' }}" id="togglePengaturanPondok" title="Pengaturan Pondok">
                        <span class="nav-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </span>
                        <span class="nav-item-text">Pengaturan Pondok</span>
                        <span class="nav-item-arrow">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </span>
                    </button>
                    <div class="sidebar-subnav">
                        @if(!$authUser || $authUser->canDo('plotting_kamar', 'view') || $authUser->canDo('pengaturan_pondok', 'view'))
                        <a href="{{ route('plotting-kamar.index') }}" class="subnav-item {{ request()->routeIs('plotting-kamar*') ? 'active' : '' }}" title="Plotting Kamar">
                            <span class="subnav-dot"></span>
                            <span class="subnav-text">Plotting Kamar</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                @if(!$authUser || $authUser->canDo('pengguna', 'view'))
                <a href="{{ route('pengguna.index') }}" class="nav-item {{ request()->routeIs('pengguna*') ? 'active' : '' }}" title="Pengguna & Akses">

                    <span class="nav-item-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2a5 5 0 1 0 5 5 5 5 0 0 0-5-5zm0 8a3 3 0 1 1 3-3 3 3 0 0 1-3 3z"></path>
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="19" cy="11" r="2"></circle>
                            <path d="M22 20v-1a3 3 0 0 0-2-2.8"></path>
                        </svg>
                    </span>
                    <span class="nav-item-text">Pengguna & Akses</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-wrapper">
            <header class="app-header">
                <div class="header-left">
                    <!-- Clean Hamburger Button (Minimalist like Image 2) -->
                    <button type="button" class="topbar-toggle-btn js-sidebar-toggle" aria-label="Buka/Tutup Menu" title="Buka/Tutup Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="header-right">
                    <!-- Kolom Pencarian Cerdas (Cari) -->
                    <div class="topbar-search-wrap" id="topbarSearchWrap">
                        <div class="topbar-search-input-box">
                            <svg class="topbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text"
                                   id="topbarSearchInput"
                                   class="topbar-search-input"
                                   placeholder="Cari"
                                   autocomplete="off"
                                   aria-label="Cari data atau halaman">
                            <span class="topbar-search-spinner" id="topbarSearchSpinner" style="display:none;"></span>
                        </div>
                        {{-- Dropdown Hasil Pencarian Interaktif --}}
                        <div class="topbar-search-dropdown" id="topbarSearchDropdown" style="display:none;">
                            <div class="topbar-search-results" id="topbarSearchResults"></div>
                        </div>
                    </div>

                    <!-- Notifikasi Bell -->
                    <div class="topbar-notif-wrap" id="topbarNotifWrap">
                        @php
                            $pendingGuruCount = \App\Models\User::where('mapel_status', 'menunggu')->count();
                        @endphp
                        <button type="button" class="topbar-icon-btn" id="topbarNotifToggle" aria-label="Notifikasi" title="Notifikasi">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            @if($pendingGuruCount > 0)
                                <span class="topbar-notif-badge"></span>
                            @endif
                        </button>
                        <div class="topbar-notif-menu" id="topbarNotifMenu" style="display:none;">
                            <div class="topbar-notif-header">
                                <span style="font-weight:700; font-size:13px; color:var(--text-primary);">Notifikasi</span>
                                @if($pendingGuruCount > 0)
                                    <span class="topbar-notif-count">{{ $pendingGuruCount }} Baru</span>
                                @endif
                            </div>
                            <div class="topbar-notif-body">
                                @if($pendingGuruCount > 0)
                                    <a href="{{ route('guru-sekolah.index') }}" class="topbar-notif-link">
                                        <div class="topbar-notif-dot"></div>
                                        <div>
                                            <div style="font-weight:600; font-size:12.5px; color:var(--text-primary);">Persetujuan Guru Sekolah</div>
                                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">Ada {{ $pendingGuruCount }} pengajuan mapel menunggu verifikasi.</div>
                                        </div>
                                    </a>
                                @else
                                    <div style="padding:16px; text-align:center; color:var(--text-muted); font-size:12.5px;">
                                        Tidak ada notifikasi baru saat ini.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Avatar (Circle Foto / Inisial jika belum) -->
                    @if($authUser)
                    <div class="user-menu-wrap" id="userMenuWrap">
                        <button type="button" class="user-avatar-btn" id="userDropdownToggle" aria-label="Menu Akun: {{ $authUser->name }}" title="{{ $authUser->name }}" style="width:36px; height:36px; min-width:36px; min-height:36px; max-width:36px; max-height:36px; border-radius:50%; overflow:hidden; padding:0; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
                            @if($authUser->foto_url)
                                <img src="{{ $authUser->foto_url }}" alt="{{ $authUser->name }}" class="topbar-avatar-circle topbar-avatar-img" style="width:36px; height:36px; min-width:36px; min-height:36px; max-width:36px; max-height:36px; border-radius:50%; object-fit:cover; display:block; flex-shrink:0;">
                            @else
                                <div class="topbar-avatar-circle topbar-avatar-initials" style="width:36px; height:36px; min-width:36px; min-height:36px; max-width:36px; max-height:36px; border-radius:50%;">
                                    {{ $authUser->initials }}
                                </div>
                            @endif
                        </button>
                        <div class="user-dropdown" id="userDropdownMenu" style="display:none;">
                            <div class="user-dropdown-info">
                                <div style="font-weight:700;font-size:13.5px;color:var(--text-primary);">{{ $authUser->name }}</div>
                                <div class="user-dropdown-email">{{ $authUser->email }}</div>
                                <div style="margin-top:6px;">
                                    <span class="badge-lembaga badge-lembaga-{{ $authUser->role === 'yayasan' ? 'yayasan' : 'smp' }}">
                                        {{ str_replace('_', ' ', $authUser->role) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('profile') }}" class="user-dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-muted);">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>Profil Pengguna</span>
                            </a>
                            <a href="{{ route('user') }}" class="user-dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-muted);">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <span>Keamanan & Sandi</span>
                            </a>
                            <div class="user-dropdown-item js-theme-toggle" id="themeToggleBtn" style="cursor:pointer; display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-muted);">
                                        <circle cx="12" cy="12" r="5"></circle>
                                        <line x1="12" y1="1" x2="12" y2="3"></line>
                                        <line x1="12" y1="21" x2="12" y2="23"></line>
                                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                        <line x1="1" y1="12" x2="3" y2="12"></line>
                                        <line x1="21" y1="12" x2="23" y2="12"></line>
                                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                    </svg>
                                    <span>Mode Gelap / Terang</span>
                                </div>
                                <span id="themeToggleStatus" style="font-size:11px; padding:2px 8px; border-radius:10px; background:var(--bg-page); border:1px solid var(--border-light); font-weight:600; color:var(--text-secondary);">Siang</span>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="user-dropdown-logout-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </header>

            <main class="app-content">
                {{-- Standardized Page Header untuk seluruh modul, KECUALI Beranda (Dashboard) --}}
                @if(!$isBeranda)
                    @if(trim($__env->yieldContent('page_header')))
                        @yield('page_header')
                    @else
                        <x-page-header :title="$__env->yieldContent('page-title', $__env->yieldContent('title'))">
                            <x-slot:actions>
                                @yield('header-actions')
                            </x-slot:actions>
                        </x-page-header>
                    @endif
                @endif
                @if($authUser && $authUser->must_change_password && !request()->routeIs('user*'))
                <div class="alert-banner-warning" style="margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:12px 18px; border-radius:12px; background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); color:#B45309; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px; flex-shrink:0; color:#D97706;">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <div style="font-size:13.5px;">
                            <strong>Perhatian:</strong> Akun Anda saat ini menggunakan kata sandi sementara dari Admin. Demi keamanan, silakan ubah kata sandi akun Anda.
                        </div>
                    </div>
                    <a href="{{ route('user') }}" class="btn btn-sm" style="background:#D97706; color:#FFFFFF; border:none; border-radius:8px; font-weight:600; font-size:12.5px; padding:6px 14px; text-decoration:none;">
                        Ubah Sandi Sekarang
                    </a>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- SweetAlert2 & App Scripts -->
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown Elements
            var roomBtn = document.getElementById('sidebarRoomToggle');
            var roomMenu = document.getElementById('sidebarRoomMenu');
            var userBtn = document.getElementById('userDropdownToggle');
            var userMenu = document.getElementById('userDropdownMenu');
            var notifBtn = document.getElementById('topbarNotifToggle');
            var notifMenu = document.getElementById('topbarNotifMenu');

            // Search Elements
            var searchWrap = document.getElementById('topbarSearchWrap');
            var searchInput = document.getElementById('topbarSearchInput');
            var searchDropdown = document.getElementById('topbarSearchDropdown');
            var searchResults = document.getElementById('topbarSearchResults');
            var searchSpinner = document.getElementById('topbarSearchSpinner');

            // Theme indicator sync
            var themeStatus = document.getElementById('themeToggleStatus');
            var themeBtn = document.getElementById('themeToggleBtn');
            function updateThemeIndicator() {
                if (themeStatus) {
                    var isDark = document.documentElement.classList.contains('dark-mode') || document.body.classList.contains('dark-mode');
                    themeStatus.textContent = isDark ? 'Malam' : 'Siang';
                }
            }
            updateThemeIndicator();
            if (themeBtn) {
                themeBtn.addEventListener('click', function() {
                    setTimeout(updateThemeIndicator, 60);
                });
            }

            // Close all dropdowns helper
            function closeAllDropdowns(except) {
                if (roomMenu && except !== roomMenu) roomMenu.classList.remove('show');
                if (userMenu && except !== userMenu) userMenu.style.display = 'none';
                if (notifMenu && except !== notifMenu) notifMenu.style.display = 'none';
                if (searchDropdown && except !== searchDropdown) searchDropdown.style.display = 'none';
            }

            // Room Switcher toggle
            if (roomBtn && roomMenu) {
                roomBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var willOpen = !roomMenu.classList.contains('show');
                    closeAllDropdowns();
                    if (willOpen) roomMenu.classList.add('show');
                });
            }

            // User dropdown toggle
            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var willOpen = userMenu.style.display === 'none' || !userMenu.style.display;
                    closeAllDropdowns();
                    if (willOpen) userMenu.style.display = 'block';
                });
            }

            // Notification dropdown toggle
            if (notifBtn && notifMenu) {
                notifBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var willOpen = notifMenu.style.display === 'none' || !notifMenu.style.display;
                    closeAllDropdowns();
                    if (willOpen) notifMenu.style.display = 'block';
                });
            }

            // Quick Search Interactive Logic
            var searchDebounceTimer = null;
            var searchCache = {};
            var searchAbort = null;

            function getCategoryIcon(catKey) {
                switch(catKey) {
                    case 'menu':
                        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>';
                    case 'siswa':
                        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
                    case 'guru':
                        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
                    case 'pendaftaran':
                        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>';
                    default:
                        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            function renderSearchResults(categories, query) {
                if (!searchResults) return;
                var totalItems = 0;
                var html = '';

                categories.forEach(function(cat) {
                    if (cat.items && cat.items.length > 0) {
                        totalItems += cat.items.length;
                        html += '<div class="topbar-search-category">' + escapeHtml(cat.category) + '</div>';
                        cat.items.forEach(function(item) {
                            var iconHtml = getCategoryIcon(item.icon);
                            html += '<a href="' + escapeHtml(item.url) + '" class="topbar-search-item">' +
                                        '<div class="topbar-search-item-icon">' + iconHtml + '</div>' +
                                        '<div class="topbar-search-item-content">' +
                                            '<div class="topbar-search-item-title">' + escapeHtml(item.title) + '</div>' +
                                            (item.subtitle ? '<div class="topbar-search-item-meta">' + escapeHtml(item.subtitle) + '</div>' : '') +
                                        '</div>' +
                                        (item.badge ? '<span class="topbar-search-item-badge">' + escapeHtml(item.badge) + '</span>' : '') +
                                    '</a>';
                        });
                    }
                });

                if (totalItems === 0) {
                    html = '<div class="topbar-search-empty">Tidak ada data yang sesuai dengan "' + escapeHtml(query) + '"</div>';
                }

                searchResults.innerHTML = html;
                searchDropdown.style.display = 'block';
            }

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var q = searchInput.value.trim();
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    if (searchAbort) searchAbort.abort();

                    if (q.length === 0) {
                        if (searchSpinner) searchSpinner.style.display = 'none';
                        if (searchDropdown) searchDropdown.style.display = 'none';
                        return;
                    }

                    if (searchCache[q]) {
                        if (searchSpinner) searchSpinner.style.display = 'none';
                        renderSearchResults(searchCache[q], q);
                        return;
                    }

                    if (searchSpinner) searchSpinner.style.display = 'block';

                    searchDebounceTimer = setTimeout(function() {
                        searchAbort = new AbortController();
                        fetch('{{ route("quick-search") }}?q=' + encodeURIComponent(q), {
                            signal: searchAbort.signal,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (searchInput.value.trim() !== q) return;
                            if (searchSpinner) searchSpinner.style.display = 'none';
                            var categories = data.results || [];
                            searchCache[q] = categories;
                            renderSearchResults(categories, q);
                        })
                        .catch(function(err) {
                            if (err.name === 'AbortError') return;
                            if (searchInput.value.trim() === q && searchSpinner) {
                                searchSpinner.style.display = 'none';
                            }
                            console.error('Quick search error:', err);
                        });
                    }, 200);
                });

                searchInput.addEventListener('focus', function() {
                    var q = searchInput.value.trim();
                    if (q.length > 0 && searchResults && searchResults.innerHTML) {
                        closeAllDropdowns(searchDropdown);
                        searchDropdown.style.display = 'block';
                    }
                });

                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        if (searchDropdown) searchDropdown.style.display = 'none';
                    }
                });
            }

            // Global Click Outside handler
            document.addEventListener('click', function(e) {
                if (roomMenu && !roomMenu.contains(e.target) && e.target !== roomBtn && !roomBtn?.contains(e.target)) {
                    roomMenu.classList.remove('show');
                }
                if (userMenu && !userMenu.contains(e.target) && e.target !== userBtn && !userBtn?.contains(e.target)) {
                    userMenu.style.display = 'none';
                }
                if (notifMenu && !notifMenu.contains(e.target) && e.target !== notifBtn && !notifBtn?.contains(e.target)) {
                    notifMenu.style.display = 'none';
                }
                if (searchWrap && !searchWrap.contains(e.target)) {
                    if (searchDropdown) searchDropdown.style.display = 'none';
                }
            });
        });
    </script>

    {{-- SweetAlert2 Flash Messages --}}
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isDark = document.documentElement.classList.contains('dark-mode') || document.body.classList.contains('dark-mode');
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: {!! json_encode(session('success')) !!},
                timer: 3500,
                timerProgressBar: true,
                showConfirmButton: false,
                background: isDark ? '#141C28' : '#FFFFFF',
                color: isDark ? '#F8FAFC' : '#111827',
                customClass: {
                    popup: 'swal2-modern-popup',
                    title: 'swal2-modern-title',
                    htmlContainer: 'swal2-modern-html'
                }
            });
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var isDark = document.documentElement.classList.contains('dark-mode') || document.body.classList.contains('dark-mode');
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan!',
                text: {!! json_encode(session('error')) !!},
                confirmButtonColor: '#DC2626',
                confirmButtonText: 'Tutup',
                background: isDark ? '#141C28' : '#FFFFFF',
                color: isDark ? '#F8FAFC' : '#111827',
                customClass: {
                    popup: 'swal2-modern-popup',
                    title: 'swal2-modern-title',
                    htmlContainer: 'swal2-modern-html'
                }
            });
        });
    </script>
    @endif

    @stack('scripts')
</body>
</html>
