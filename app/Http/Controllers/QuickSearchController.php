<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuickSearchController extends Controller
{
    /**
     * Pencarian global cerdas untuk navbar (Menu, Siswa, Guru, Pendaftaran).
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json([
                'query' => $q,
                'groups' => [],
                'results' => [],
                'total_count' => 0,
            ]);
        }

        /** @var User $authUser */
        $authUser = Auth::user();
        $qLower = mb_strtolower($q);

        $groups = [];

        // 1. Menu & Navigasi
        $menus = [
            [
                'title' => 'Beranda / Dashboard',
                'description' => 'Ringkasan statistik & sebaran santri',
                'url' => route('beranda'),
                'icon' => 'home',
                'keywords' => 'beranda dashboard ringkasan home statistik',
                'permission' => null,
            ],
            [
                'title' => 'Data Siswa',
                'description' => 'Daftar data santri & siswa madrasah',
                'url' => route('siswa'),
                'icon' => 'users',
                'keywords' => 'siswa santri murid daftar siswa data',
                'permission' => 'siswa',
            ],
            [
                'title' => 'Input Kartu NFC',
                'description' => 'Pairing dan registrasi kartu santri via USB reader',
                'url' => route('nfc.index'),
                'icon' => 'credit-card',
                'keywords' => 'nfc rfid kartu serial tap pairing input santri',
                'permission' => 'siswa',
            ],
            [
                'title' => 'Jurnal Pembelajaran',
                'description' => 'Input jurnal mengajar harian guru',
                'url' => route('sekolah.jurnal'),
                'icon' => 'book',
                'keywords' => 'jurnal sekolah pembelajaran materi guru kelas',
                'permission' => 'sekolah',
            ],
            [
                'title' => 'Rekap Jurnal',
                'description' => 'Laporan rekapitulasi jurnal pembelajaran',
                'url' => route('sekolah.rekap-jurnal'),
                'icon' => 'file-text',
                'keywords' => 'rekap jurnal laporan mengajar',
                'permission' => 'sekolah',
            ],
            [
                'title' => 'Rekap Absensi Sekolah',
                'description' => 'Rekap absensi siswa per jenjang & kelas',
                'url' => route('rekap-absensi.sekolah'),
                'icon' => 'calendar',
                'keywords' => 'rekap absensi sekolah siswa absensi kehadiran',
                'permission' => 'sekolah',
            ],
            [
                'title' => 'Rekap Absensi Guru',
                'description' => 'Laporan presensi kedatangan guru',
                'url' => route('rekap-absensi.guru'),
                'icon' => 'calendar',
                'keywords' => 'rekap guru absensi guru presensi pengajar',
                'permission' => 'rekap_absensi',
            ],
            [
                'title' => 'Perizinan Sambang',
                'description' => 'Data izin kunjungan/sambang wali santri',
                'url' => route('perizinan.sambang'),
                'icon' => 'clipboard',
                'keywords' => 'perizinan sambang kunjungan ortu izin pulang',
                'permission' => 'perizinan_sambang',
            ],
            [
                'title' => 'Perizinan Sakit',
                'description' => 'Data izin santri sakit & istirahat',
                'url' => route('perizinan.sakit'),
                'icon' => 'activity',
                'keywords' => 'perizinan sakit santri sakit uks berobat',
                'permission' => 'perizinan_sakit',
            ],
            [
                'title' => 'Pendaftaran Santri Baru',
                'description' => 'Data formulir & verifikasi pendaftaran PSB',
                'url' => route('pendaftaran.index'),
                'icon' => 'user-plus',
                'keywords' => 'pendaftaran psb santri baru ppdb formulir registrasi',
                'permission' => 'pendaftaran',
            ],
            [
                'title' => 'Presensi Guru',
                'description' => 'Scan QR Code kehadiran & izin harian',
                'url' => route('presensi.index'),
                'icon' => 'check-square',
                'keywords' => 'presensi guru absen masuk pulang qr scan',
                'permission' => 'presensi',
            ],
            [
                'title' => 'Guru Sekolah',
                'description' => 'Daftar guru & verifikasi persetujuan mapel',
                'url' => route('guru-sekolah.index'),
                'icon' => 'award',
                'keywords' => 'guru sekolah verifikasi mapel pengampu mata pelajaran',
                'permission' => 'guru_sekolah',
            ],
            [
                'title' => 'Plotting Mata Pelajaran',
                'description' => 'Pengaturan mata pelajaran & kurikulum',
                'url' => route('plotting-mapel.index'),
                'icon' => 'book-open',
                'keywords' => 'plotting mapel mata pelajaran kurikulum',
                'permission' => 'plotting_mapel',
            ],
            [
                'title' => 'Plotting Kelas',
                'description' => 'Plotting & mutasi siswa ke kelas',
                'url' => route('plotting-kelas.index'),
                'icon' => 'layers',
                'keywords' => 'plotting kelas pembagian kelas mutasi',
                'permission' => 'plotting_kelas',
            ],
            [
                'title' => 'Plotting Kamar',
                'description' => 'Penempatan & mutasi santri ke kamar asrama pondok',
                'url' => route('plotting-kamar.index'),
                'icon' => 'home',
                'keywords' => 'plotting kamar asrama pondok penempatan santri mutasi kamar',
                'permission' => 'plotting_kamar',
            ],
            [
                'title' => 'Pengaturan Absensi Guru',
                'description' => 'Pengaturan radius GPS & jam kerja presensi',
                'url' => route('pengaturan-absensi.index'),
                'icon' => 'settings',
                'keywords' => 'pengaturan absensi radius gps jam masuk batas',
                'permission' => 'pengaturan_absensi',
            ],
            [
                'title' => 'Pengaturan Sesi & Perangkat RFID',
                'description' => 'Kelola jadwal sesi otomatis, mesin ESP32 & target partisipan',
                'url' => route('pengaturan.sesi'),
                'icon' => 'clock',
                'keywords' => 'pengaturan sesi presensi rfid esp32 jadwal alat kamar',
                'permission' => 'pengaturan_absensi',
            ],
            [
                'title' => 'Dashboard Monitoring Presensi RFID',
                'description' => 'Pantau live tap kartu RFID santri, siswa & pegawai real-time',
                'url' => route('dashboard-presensi'),
                'icon' => 'activity',
                'keywords' => 'dashboard presensi live rfid monitor kartu santri asrama',
                'permission' => 'pengaturan_absensi',
            ],
            [
                'title' => 'Master Ruang Kelas',
                'description' => 'Kelola daftar ruang kelas SMP & SMA',
                'url' => route('master.kelas.index'),
                'icon' => 'layers',
                'keywords' => 'master ruang kelas rombel tingkat jenjang',
                'permission' => 'master_kelas',
            ],
            [
                'title' => 'Master Jam Pelajaran',
                'description' => 'Kelola jam KBM, upacara & istirahat',
                'url' => route('master.jam-pelajaran.index'),
                'icon' => 'clock',
                'keywords' => 'master jam pelajaran kbm time slot waktu istirahat',
                'permission' => 'master_jam',
            ],
            [
                'title' => 'Master Lembaga Pendidikan',
                'description' => 'Kelola unit lembaga SMP, SMA, Pondok & Yayasan',
                'url' => route('master.lembaga.index'),
                'icon' => 'award',
                'keywords' => 'master lembaga pendidikan unit sekolah pondok yayasan',
                'permission' => 'master_lembaga',
            ],
            [
                'title' => 'Pengguna & Hak Akses',
                'description' => 'Manajemen akun admin, yayasan, dan guru',
                'url' => route('pengguna.index'),
                'icon' => 'shield',
                'keywords' => 'pengguna user hak akses permissions akun role',
                'permission' => 'pengguna',
            ],
            [
                'title' => 'Profil Pengguna',
                'description' => 'Biodata diri, kontak & foto profil',
                'url' => route('profile'),
                'icon' => 'user',
                'keywords' => 'profil saya akun biodata foto data diri',
                'permission' => null,
            ],
            [
                'title' => 'Keamanan & Kata Sandi',
                'description' => 'Ubah kata sandi akun',
                'url' => route('user'),
                'icon' => 'lock',
                'keywords' => 'keamanan kata sandi password akun ubah sandi',
                'permission' => null,
            ],
        ];

        $matchedMenus = [];
        foreach ($menus as $m) {
            if ($m['permission'] && (!$authUser || !$authUser->canDo($m['permission'], 'view'))) {
                continue;
            }
            if (str_contains(mb_strtolower($m['title']), $qLower) || str_contains($m['keywords'], $qLower)) {
                $matchedMenus[] = [
                    'title' => $m['title'],
                    'subtitle' => $m['description'],
                    'url' => $m['url'],
                    'badge' => 'Menu',
                    'icon' => $m['icon'],
                ];
            }
        }

        if (!empty($matchedMenus)) {
            $groups[] = [
                'name' => 'Menu & Navigasi',
                'items' => array_slice($matchedMenus, 0, 4),
            ];
        }

        // 2. Data Siswa (Dapat dicari oleh Yayasan, Admin Lembaga, Guru, atau pemegang izin siswa)
        $canSearchSiswa = !$authUser 
            || $authUser->isYayasan() 
            || in_array($authUser->role, ['admin_lembaga', 'guru']) 
            || $authUser->canDo('siswa', 'view');

        if ($canSearchSiswa) {
            $siswas = Siswa::query()
                ->where(function($query) use ($q, $qLower) {
                    $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$qLower}%"])
                        ->orWhere('nama_lengkap', 'like', "%{$q}%")
                        ->orWhere('kelas', 'like', "%{$q}%")
                        ->orWhere('kamar', 'like', "%{$q}%")
                        ->orWhere('rfid_uid', 'like', "%{$q}%")
                        ->orWhereHas('pendaftaran', function($p) use ($q, $qLower) {
                            $p->where('nik', 'like', "%{$q}%")
                              ->orWhere('nisn', 'like', "%{$q}%")
                              ->orWhere('no_induk', 'like', "%{$q}%")
                              ->orWhereRaw('LOWER(nama_panggilan) LIKE ?', ["%{$qLower}%"]);
                        });
                })
                ->limit(6)
                ->get();

            if ($siswas->isNotEmpty()) {
                $siswaItems = [];
                foreach ($siswas as $s) {
                    $siswaItems[] = [
                        'title' => $s->nama_lengkap,
                        'subtitle' => 'Jenjang: ' . strtoupper($s->jenjang ?? '-') . ' • Kelas: ' . ($s->kelas ?? '-') . ' • Kamar: ' . ($s->kamar ?? '-'),
                        'url' => route('siswa', ['search' => $s->nama_lengkap]),
                        'badge' => ucfirst($s->status ?? 'Santri'),
                        'icon' => 'user',
                    ];
                }
                $groups[] = [
                    'name' => 'Data Siswa',
                    'items' => $siswaItems,
                ];
            }
        }

        // 3. Guru & Pegawai
        $canSearchGuru = !$authUser || $authUser->canDo('guru_sekolah', 'view') || $authUser->canDo('pengguna', 'view') || $authUser->isYayasan() || in_array($authUser->role, ['admin_lembaga', 'guru']);
        if ($canSearchGuru) {
            $users = User::query()
                ->where(function($query) use ($q, $qLower) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$qLower}%"])
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%")
                        ->orWhere('mata_pelajaran', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(4)
                ->get();

            if ($users->isNotEmpty()) {
                $userItems = [];
                foreach ($users as $u) {
                    $sub = [];
                    if ($u->mata_pelajaran) $sub[] = 'Mapel: ' . $u->mata_pelajaran;
                    if ($u->nip) $sub[] = 'NIP: ' . $u->nip;
                    if (empty($sub)) $sub[] = $u->email;

                    $userItems[] = [
                        'title' => $u->name,
                        'subtitle' => implode(' • ', $sub),
                        'url' => route('guru-sekolah.index'),
                        'badge' => str_replace('_', ' ', $u->role),
                        'icon' => 'award',
                    ];
                }
                $groups[] = [
                    'name' => 'Guru & Pegawai',
                    'items' => $userItems,
                ];
            }
        }

        // 4. Data Pendaftaran Santri Baru
        if (!$authUser || $authUser->canDo('pendaftaran', 'view') || $authUser->isYayasan()) {
            $pendaftar = Pendaftaran::query()
                ->where(function($query) use ($q, $qLower) {
                    $query->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$qLower}%"])
                        ->orWhere('nama_lengkap', 'like', "%{$q}%")
                        ->orWhere('nomor_pendaftaran', 'like', "%{$q}%")
                        ->orWhere('no_induk', 'like', "%{$q}%");
                })
                ->limit(3)
                ->get();

            if ($pendaftar->isNotEmpty()) {
                $pendaftarItems = [];
                foreach ($pendaftar as $p) {
                    $pendaftarItems[] = [
                        'title' => $p->nama_lengkap,
                        'subtitle' => 'No: ' . ($p->nomor_pendaftaran ?? '-') . ' • Jenjang: ' . strtoupper($p->jenjang ?? '-'),
                        'url' => route('pendaftaran.show', $p->id),
                        'badge' => ucfirst($p->status ?? 'Pendaftar'),
                        'icon' => 'user-plus',
                    ];
                }
                $groups[] = [
                    'name' => 'Pendaftaran Santri Baru',
                    'items' => $pendaftarItems,
                ];
            }
        }

        $results = array_map(function($g) {
            return [
                'category' => $g['name'],
                'items' => $g['items'],
            ];
        }, $groups);

        return response()->json([
            'query' => $q,
            'groups' => $groups,
            'results' => $results,
            'total_count' => array_sum(array_map(fn($g) => count($g['items']), $groups)),
        ]);
    }
}
