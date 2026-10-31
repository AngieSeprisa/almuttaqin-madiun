@props([
    'title' => null,
    'breadcrumbs' => null,
])

@php
    $rawTitle = $title ?? trim($__env->yieldContent('page-title', $__env->yieldContent('title', '')));
    $displayTitle = (string)$rawTitle;
    while (($decoded = html_entity_decode($displayTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== $displayTitle) {
        $displayTitle = $decoded;
    }
    
    // Auto-generate breadcrumbs if not explicitly provided as an array
    if ($breadcrumbs === null) {
        if (trim($__env->yieldContent('breadcrumb'))) {
            $customBreadcrumbHtml = trim($__env->yieldContent('breadcrumb'));
            $breadcrumbsList = [];
        } else {
            $customBreadcrumbHtml = null;
            $bcParent = 'Beranda';
            $bcParentRoute = route('beranda');
            $bcChild = 'Ringkasan';

            if (request()->routeIs('siswa*')) {
                $bcParent = 'Siswa';
                $bcParentRoute = route('siswa');
                $bcChild = 'Daftar';
            } elseif (request()->routeIs('sekolah.jurnal*')) {
                $bcParent = 'Sekolah';
                $bcParentRoute = route('sekolah.jurnal');
                $bcChild = 'Jurnal';
            } elseif (request()->routeIs('sekolah.rekap-jurnal*')) {
                $bcParent = 'Sekolah';
                $bcParentRoute = route('sekolah.rekap-jurnal');
                $bcChild = 'Rekap Jurnal';
            } elseif (request()->routeIs('sekolah.rekap-absensi*') || request()->routeIs('rekap-absensi.sekolah*')) {
                $bcParent = 'Rekap';
                $bcParentRoute = route('rekap-absensi.sekolah');
                $bcChild = 'Sekolah';
            } elseif (request()->routeIs('rekap-absensi.guru*')) {
                $bcParent = 'Rekap';
                $bcParentRoute = route('rekap-absensi.guru');
                $bcChild = 'Guru';
            } elseif (request()->routeIs('perizinan.sambang*')) {
                $bcParent = 'Perizinan';
                $bcParentRoute = route('perizinan.sambang');
                $bcChild = request()->routeIs('*.create') ? 'Input Sambang' : 'Sambang';
            } elseif (request()->routeIs('perizinan.sakit*')) {
                $bcParent = 'Perizinan';
                $bcParentRoute = route('perizinan.sakit');
                $bcChild = request()->routeIs('*.create') ? 'Input Sakit' : 'Sakit';
            } elseif (request()->routeIs('pendaftaran*')) {
                $bcParent = 'Pendaftaran';
                $bcParentRoute = route('pendaftaran.index');
                $bcChild = request()->routeIs('*.create') ? 'Formulir Baru' : (request()->routeIs('*.show') ? 'Detail Pendaftar' : 'Daftar');
            } elseif (request()->routeIs('presensi*')) {
                $bcParent = 'Presensi Guru';
                $bcParentRoute = route('presensi.index');
                $bcChild = 'Daftar';
            } elseif (request()->routeIs('pengaturan.sesi*')) {
                $customBreadcrumbHtml = null;
                $breadcrumbsList = [
                    ['label' => 'Aplikasi', 'url' => route('beranda')],
                    ['label' => 'Pengaturan Sesi', 'url' => route('pengaturan.sesi')],
                    ['label' => request()->routeIs('*.create') ? 'Tambah Sesi' : (request()->routeIs('*.edit') ? 'Edit Sesi' : 'Daftar Sesi'), 'url' => null],
                ];
            } elseif (request()->routeIs('pengaturan-absensi*')) {
                $bcParent = 'Pengaturan Sekolah';
                $bcParentRoute = route('pengaturan-absensi.index');
                $bcChild = 'Absensi Guru';
            } elseif (request()->routeIs('guru-sekolah*')) {
                $bcParent = 'Pengaturan Sekolah';
                $bcParentRoute = route('guru-sekolah.index');
                $bcChild = 'Guru Sekolah';
            } elseif (request()->routeIs('plotting-mapel*')) {
                $bcParent = 'Pengaturan Sekolah';
                $bcParentRoute = route('plotting-mapel.index');
                $bcChild = 'Plotting Mapel';
            } elseif (request()->routeIs('plotting-kelas*')) {
                $bcParent = 'Pengaturan Sekolah';
                $bcParentRoute = route('plotting-kelas.index');
                $bcChild = 'Plotting Kelas';
            } elseif (request()->routeIs('pengguna*')) {
                $bcParent = 'Pengguna';
                $bcParentRoute = route('pengguna.index');
                $bcChild = request()->routeIs('*.create') ? 'Tambah Pengguna' : (request()->routeIs('*.edit') ? 'Edit Pengguna' : 'Hak Akses');
            } elseif (request()->routeIs('profile*')) {
                $bcParent = 'Akun';
                $bcParentRoute = route('profile');
                $bcChild = 'Profil Pengguna';
            } elseif (request()->routeIs('user*')) {
                $bcParent = 'Akun';
                $bcParentRoute = route('profile');
                $bcChild = 'Keamanan & Sandi';
            } else {
                $bcParent = 'Aplikasi';
                $bcParentRoute = route('beranda');
                $bcChild = $displayTitle ?: 'Halaman';
            }

            $breadcrumbsList = [
                ['label' => $bcParent, 'url' => $bcParentRoute],
                ['label' => $bcChild, 'url' => null],
            ];
        }
    } else {
        $customBreadcrumbHtml = null;
        $breadcrumbsList = is_array($breadcrumbs) ? $breadcrumbs : [];
    }

    if (is_array($breadcrumbsList)) {
        foreach ($breadcrumbsList as &$bcItem) {
            if (isset($bcItem['label'])) {
                $label = (string)$bcItem['label'];
                while (($decoded = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== $label) {
                    $label = $decoded;
                }
                $bcItem['label'] = $label;
            }
        }
        unset($bcItem);
    }

    $actionsContent = isset($actions) && trim($actions) !== '' ? $actions : trim($__env->yieldContent('header-actions'));
@endphp

<header {{ $attributes->merge(['class' => 'page-header']) }}>
    {{-- Baris 1: Breadcrumb rapi dan kecil (text-sm text-gray-500) --}}
    <nav class="page-header-breadcrumbs text-sm text-gray-500" aria-label="Breadcrumb">
        @if(!empty($customBreadcrumbHtml))
            {!! $customBreadcrumbHtml !!}
        @elseif(!empty($breadcrumbsList))
            <ol class="page-breadcrumbs-list" style="display:inline-flex; align-items:center; list-style:none; margin:0; padding:0; flex-wrap:wrap; gap:6px;">
                @foreach($breadcrumbsList as $bc)
                    <li class="page-breadcrumb-item {{ $loop->last ? 'active' : '' }}" style="display:inline-flex; align-items:center; gap:6px;">
                        @if(!$loop->last && !empty($bc['url']))
                            <a href="{{ $bc['url'] }}" class="page-breadcrumb-link">{{ $bc['label'] }}</a>
                            <span class="page-breadcrumb-separator" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </span>
                        @else
                            <span class="page-breadcrumb-current">{{ $bc['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif
    </nav>

    {{-- Baris 2: Judul halaman tebal (text-2xl font-bold text-gray-900) di sebelah kiri, dan tombol aksi utama di sebelah kanan --}}
    <div class="page-header-row">
        <div class="page-header-left">
            <h1 class="page-header-title text-2xl font-bold text-gray-900">{{ $displayTitle }}</h1>
        </div>

        @if(!empty($actionsContent))
            <div class="page-header-actions banner-header-right">
                {!! $actionsContent !!}
            </div>
        @endif
    </div>
</header>
