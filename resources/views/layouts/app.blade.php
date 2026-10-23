@php
    $authUser = auth()->user();
    $accessibleLembagas = $authUser ? $authUser->accessibleLembagas() : collect();
    $currentLembaga = $authUser ? $authUser->currentLembaga() : null;

    $brandLogo = $currentLembaga ? $currentLembaga->logo_url : asset('images/logo.png');
    $brandTitle = $currentLembaga ? $currentLembaga->brand_title : 'PONDOK PESANTREN AL MUTTAQIN';
    $brandAlt = $brandTitle;
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
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay"></div>

    <div class="app-layout">
        <!-- Floating Sidebar -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-header">
                <a href="{{ route('beranda') }}" class="brand-wrapper" title="{{ $brandAlt }}">
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
                @endif

                {{-- Menu Sekolah dengan Submenu Jurnal & Rekap Jurnal --}}
                @if(!$authUser || $authUser->canDo('sekolah', 'view'))
                <div class="nav-group {{ request()->routeIs('sekolah.jurnal*') || request()->routeIs('sekolah.rekap-jurnal*') ? 'open' : '' }}" id="navGroupSekolah">
                    <button type="button" class="nav-item nav-item-has-sub {{ request()->routeIs('sekolah.jurnal*') || request()->routeIs('sekolah.rekap-jurnal*') ? 'active' : '' }}" id="toggleSekolah" title="Sekolah">
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

            {{-- User Profile Card (Bottom Section - Untitled UI Style) --}}
            <div class="sidebar-footer">
                @if($authUser)
                <a href="{{ route('profile') }}" class="sidebar-profile-card {{ request()->routeIs('profile*') ? 'active' : '' }}" title="Lihat Profil Pengguna: {{ $authUser->name }}">
                    <div class="sidebar-profile-avatar">
                        {{ strtoupper(substr($authUser->name, 0, 1)) }}
                    </div>
                    <div class="sidebar-profile-info">
                        <span class="sidebar-profile-name">{{ $authUser->name }}</span>
                        <span class="sidebar-profile-email">{{ $authUser->email }}</span>
                    </div>
                    <div class="sidebar-profile-action">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar-profile-chevron">
                            <path d="M7 15l5 5 5-5M7 9l5-5 5 5"/>
                        </svg>
                    </div>
                </a>
                @endif
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-wrapper">
            <header class="app-header">
                <div class="header-left">
                    <!-- Mobile Hamburger Button -->
                    <button type="button" class="mobile-sidebar-toggle js-sidebar-toggle" aria-label="Buka Menu Sidebar" title="Buka Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <h1 class="page-title">@yield('title', 'Beranda')</h1>
                </div>

                <div class="header-right">
                    @yield('header-actions')

                    {{-- User Profile Pill & Logout (Simplified for topbar) --}}
                    @if($authUser)
                    <div class="user-menu-wrap">
                        <button type="button" class="user-profile-btn" id="userDropdownToggle" aria-label="Menu Akun">
                            <div class="user-avatar">
                                {{ strtoupper(substr($authUser->name, 0, 1)) }}
                            </div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--text-muted);">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdownMenu">
                            <div class="user-dropdown-info">
                                <div style="font-weight:700;font-size:13.5px;color:var(--text-primary);">{{ $authUser->name }}</div>
                                <div class="user-dropdown-email">{{ $authUser->email }}</div>
                                <div style="margin-top:4px;">
                                    <span class="badge-lembaga badge-lembaga-{{ $authUser->role === 'yayasan' ? 'yayasan' : 'smp' }}">
                                        {{ str_replace('_', ' ', $authUser->role) }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('profile') }}" class="user-dropdown-item" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:13px;font-weight:500;color:var(--text-primary);text-decoration:none;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:var(--text-muted);">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span>Profil Pengguna</span>
                            </a>
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

                    <!-- Theme Toggle -->
                    <button type="button" class="theme-toggle" id="themeToggleBtn" aria-label="Ganti Mode Siang/Malam">
                        <span class="theme-toggle-icon theme-icon-moon">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                            </svg>
                        </span>
                        <span class="theme-toggle-knob"></span>
                        <span class="theme-toggle-icon theme-icon-sun">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <circle cx="12" cy="12" r="4.5" fill="none" stroke="currentColor"></circle>
                                <line x1="12" y1="2" x2="12" y2="4.5"></line>
                                <line x1="12" y1="19.5" x2="12" y2="22"></line>
                                <line x1="4.93" y1="4.93" x2="6.7" y2="6.7"></line>
                                <line x1="17.3" y1="17.3" x2="19.07" y2="19.07"></line>
                                <line x1="2" y1="12" x2="4.5" y2="12"></line>
                                <line x1="19.5" y1="12" x2="22" y2="12"></line>
                                <line x1="4.93" y1="19.07" x2="6.7" y2="17.3"></line>
                                <line x1="17.3" y1="6.7" x2="19.07" y2="4.93"></line>
                            </svg>
                        </span>
                    </button>
                </div>
            </header>

            <main class="app-content">
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
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        // Dropdown toggles
        document.addEventListener('DOMContentLoaded', function() {
            var roomBtn = document.getElementById('sidebarRoomToggle');
            var roomMenu = document.getElementById('sidebarRoomMenu');
            var userBtn = document.getElementById('userDropdownToggle');
            var userMenu = document.getElementById('userDropdownMenu');

            if (roomBtn && roomMenu) {
                roomBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    roomMenu.classList.toggle('show');
                    if (userMenu) userMenu.classList.remove('show');
                });
            }

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                    if (roomMenu) roomMenu.classList.remove('show');
                });
            }

            document.addEventListener('click', function(e) {
                if (roomMenu && !roomMenu.contains(e.target) && e.target !== roomBtn) {
                    roomMenu.classList.remove('show');
                }
                if (userMenu && !userMenu.contains(e.target) && e.target !== userBtn) {
                    userMenu.classList.remove('show');
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
