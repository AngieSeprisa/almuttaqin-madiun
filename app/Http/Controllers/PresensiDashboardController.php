<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\MasterDevice;
use App\Models\MasterSesi;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PresensiDashboardController extends Controller
{
    /**
     * Tampilan Utama Dashboard Monitoring Real-Time Multi-Lokasi (NFC / RFID).
     */
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->format('H:i:s');

        $initialTab = in_array($request->get('tab'), ['pondok', 'sekolah', 'pegawai'], true)
            ? $request->get('tab')
            : 'pondok';

        // 1. Ambil Sesi yang sedang aktif saat ini sesuai tab awal
        $sesiQuery = MasterSesi::query();
        if ($initialTab === 'pegawai') {
            $sesiQuery->where('kategori', 'pegawai');
        } elseif ($initialTab === 'sekolah') {
            $sesiQuery->where('kategori', 'sekolah');
        } else {
            $sesiQuery->whereNotIn('kategori', ['pegawai', 'sekolah']);
        }

        $activeSesi = (clone $sesiQuery)->where('jam_mulai', '<=', $currentTime)
            ->where('jam_selesai', '>=', $currentTime)
            ->first();

        // Jika tidak ada yang sedang aktif persis jam sekarang, ambil sesi terdekat berikutnya dalam kategori
        if (!$activeSesi) {
            $activeSesi = (clone $sesiQuery)->where('jam_mulai', '>=', $currentTime)
                ->orderBy('jam_mulai', 'asc')
                ->first() ?: (clone $sesiQuery)->first();
        }

        // Fallback global jika kategori belum memiliki master_sesis
        if (!$activeSesi) {
            $activeSesi = MasterSesi::where('jam_mulai', '<=', $currentTime)
                ->where('jam_selesai', '>=', $currentTime)
                ->first() ?: MasterSesi::first();
        }

        $allSesis = MasterSesi::orderBy('jam_mulai')->get();
        $allDevices = MasterDevice::where('status_aktif', true)->get();
        $totalDevicesCount = MasterDevice::count();

        // 2. Daftar Kamar untuk Filter Pondok
        $kamars = Kamar::daftarNamaKamarAktif();

        return view('dashboard-presensi', compact(
            'activeSesi',
            'allSesis',
            'allDevices',
            'totalDevicesCount',
            'kamars',
            'initialTab'
        ));
    }

    /**
     * Endpoint API JSON untuk Real-Time Polling (Alpine.js Feed & Summary).
     */
    public function data(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->format('H:i:s');
        $today = $now->toDateString();

        $tab = $request->get('tab', 'pondok'); // 'pondok', 'sekolah', 'pegawai'
        $sesiId = $request->get('sesi_id');
        $kamar = $request->get('kamar', 'Reguler');

        // Tentukan sesi
        if ($sesiId) {
            $sesi = MasterSesi::find($sesiId);
        } else {
            $sesiQuery = MasterSesi::query();
            if ($tab === 'pegawai') {
                $sesiQuery->where('kategori', 'pegawai');
            } elseif ($tab === 'sekolah') {
                $sesiQuery->where('kategori', 'sekolah');
            } else {
                $sesiQuery->whereNotIn('kategori', ['pegawai', 'sekolah']);
            }

            $sesi = (clone $sesiQuery)->where('jam_mulai', '<=', $currentTime)
                ->where('jam_selesai', '>=', $currentTime)
                ->first();

            if (!$sesi) {
                $sesi = (clone $sesiQuery)->where('jam_mulai', '>=', $currentTime)
                    ->orderBy('jam_mulai', 'asc')
                    ->first() ?: (clone $sesiQuery)->first();
            }

            if (!$sesi) {
                $sesi = MasterSesi::where('jam_mulai', '<=', $currentTime)
                    ->where('jam_selesai', '>=', $currentTime)
                    ->first() ?: MasterSesi::first();
            }
        }

        // Live Feed 12 Tap Terakhir (Global Hari Ini)
        $liveFeeds = Presensi::with(['sesi', 'siswa', 'user'])
            ->whereDate('waktu_tap', $today)
            ->latest('waktu_tap')
            ->take(12)
            ->get()
            ->map(function ($p) {
                $sesiLabel = $p->sesi?->nama_sesi ?? '-';
                if ($p->tipe_entitas === 'guru_pegawai') {
                    if ($p->status_kehadiran === 'pulang' || str_contains(strtoupper((string) $p->sesi?->kode_sesi), 'PULANG')) {
                        $sesiLabel = 'PULANG';
                    } elseif ($p->status_kehadiran === 'hadir' || $p->status_kehadiran === 'terlambat' || str_contains(strtoupper((string) $p->sesi?->kode_sesi), 'MASUK')) {
                        $sesiLabel = 'MASUK';
                    }
                }
                return [
                    'id' => $p->id,
                    'nama' => $p->tipe_entitas === 'santri' ? ($p->siswa?->nama_lengkap ?? 'Santri') : ($p->user?->name ?? 'Pegawai'),
                    'kamar_or_role' => $p->tipe_entitas === 'santri' ? ($p->siswa?->kamar ?? 'Reguler') : 'Guru/Pegawai',
                    'sesi' => $sesiLabel,
                    'waktu' => Carbon::parse($p->waktu_tap)->format('H:i:s'),
                    'status' => $p->status_kehadiran,
                    'tipe' => $p->tipe_entitas,
                ];
            });

        // Hitung Target & Kehadiran Berdasarkan Tab
        $totalTarget = 0;
        $hadirList = collect();
        $waitingList = collect();

        if ($tab === 'pondok') {
            $querySantri = Siswa::where('status', 'aktif');
            if ($kamar && $kamar !== 'all') {
                $querySantri->where('kamar', $kamar);
            }
            $targetEntities = $querySantri->orderBy('nama_lengkap')->get();
            $totalTarget = $targetEntities->count();

            $presensiMap = Presensi::whereDate('waktu_tap', $today)
                ->when($sesi, fn($q) => $q->where('sesi_id', $sesi->id))
                ->where('tipe_entitas', 'santri')
                ->get()
                ->keyBy('entitas_id');

            $hadirIds = $presensiMap->keys()->toArray();

            $hadirList = $targetEntities->whereIn('id', $hadirIds)->map(function ($s) use ($presensiMap) {
                $tap = $presensiMap->get($s->id);
                return [
                    'id' => $s->id,
                    'nama' => $s->nama_lengkap,
                    'sub' => 'Kamar: ' . ($s->kamar ?: '-'),
                    'rfid_uid' => $s->rfid_uid ?: '-',
                    'waktu_tap' => $tap ? Carbon::parse($tap->waktu_tap)->format('H:i:s') : null,
                    'status_kehadiran' => $tap?->status_kehadiran ?? 'tepat_waktu',
                    'is_hadir' => true,
                ];
            })->values();

            $waitingList = $targetEntities->whereNotIn('id', $hadirIds)->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama_lengkap,
                'sub' => 'Kamar: ' . ($s->kamar ?: '-'),
                'rfid_uid' => $s->rfid_uid ?: 'Belum ada kartu',
                'waktu_tap' => null,
                'status_kehadiran' => null,
                'is_hadir' => false,
            ])->values();

        } elseif ($tab === 'sekolah') {
            $targetEntities = Siswa::where('status', 'aktif')
                ->whereIn('jenjang', ['SMP', 'SMA'])
                ->orderBy('nama_lengkap')
                ->get();
            $totalTarget = $targetEntities->count();

            $presensiMap = Presensi::whereDate('waktu_tap', $today)
                ->when($sesi, fn($q) => $q->where('sesi_id', $sesi->id))
                ->where('tipe_entitas', 'santri')
                ->get()
                ->keyBy('entitas_id');

            $hadirIds = $presensiMap->keys()->toArray();

            $hadirList = $targetEntities->whereIn('id', $hadirIds)->map(function ($s) use ($presensiMap) {
                $tap = $presensiMap->get($s->id);
                $infoKelas = trim(strtoupper((string) $s->jenjang) . ($s->kelas ? ' • Kelas ' . $s->kelas : ''));
                return [
                    'id' => $s->id,
                    'nama' => $s->nama_lengkap,
                    'sub' => $infoKelas ?: 'Siswa Sekolah',
                    'rfid_uid' => $s->rfid_uid ?: '-',
                    'waktu_tap' => $tap ? Carbon::parse($tap->waktu_tap)->format('H:i:s') : null,
                    'status_kehadiran' => $tap?->status_kehadiran ?? 'tepat_waktu',
                    'is_hadir' => true,
                ];
            })->values();

            $waitingList = $targetEntities->whereNotIn('id', $hadirIds)->map(function ($s) {
                $infoKelas = trim(strtoupper((string) $s->jenjang) . ($s->kelas ? ' • Kelas ' . $s->kelas : ''));
                return [
                    'id' => $s->id,
                    'nama' => $s->nama_lengkap,
                    'sub' => $infoKelas ?: 'Siswa Sekolah',
                    'rfid_uid' => $s->rfid_uid ?: 'Belum ada kartu',
                    'waktu_tap' => null,
                    'status_kehadiran' => null,
                    'is_hadir' => false,
                ];
            })->values();

        } else { // 'pegawai'
            $targetEntities = User::with('primaryLembaga')->orderBy('name')->get();
            $totalTarget = $targetEntities->count();

            // Cek apakah sesi aktif merupakan sesi pulang
            $isSesiPulang = $sesi && (
                str_contains(strtoupper((string) $sesi->kode_sesi), 'PULANG') ||
                str_contains(strtoupper((string) $sesi->nama_sesi), 'PULANG')
            );

            // Presensi Guru harian dari tabel presensi_gurus
            $presensiGurus = \App\Models\PresensiGuru::whereDate('tanggal', $today)
                ->get()
                ->keyBy('user_id');

            // Log tap presensis untuk fallback/tambahan
            $presensiMap = Presensi::whereDate('waktu_tap', $today)
                ->where('tipe_entitas', 'guru_pegawai')
                ->get()
                ->groupBy('entitas_id');

            $hadirList = collect();
            $waitingList = collect();

            foreach ($targetEntities as $u) {
                $pGuru = $presensiGurus->get($u->id);
                $userTaps = $presensiMap->get($u->id, collect());

                $jamMasuk = null;
                $jamPulang = null;

                if ($pGuru && $pGuru->jam_masuk) {
                    $jamMasuk = strlen((string) $pGuru->jam_masuk) > 8
                        ? Carbon::parse($pGuru->jam_masuk)->format('H:i:s')
                        : substr((string) $pGuru->jam_masuk, 0, 8);
                }
                if ($pGuru && $pGuru->jam_pulang) {
                    $jamPulang = strlen((string) $pGuru->jam_pulang) > 8
                        ? Carbon::parse($pGuru->jam_pulang)->format('H:i:s')
                        : substr((string) $pGuru->jam_pulang, 0, 8);
                }

                // Fallback jika belum tersimpan di presensi_gurus namun ada di presensis
                if (!$jamMasuk) {
                    $tapMasuk = $userTaps->first(function ($t) {
                        return $t->status_kehadiran !== 'pulang' &&
                            !str_contains(strtoupper((string) $t->sesi?->kode_sesi), 'PULANG');
                    });
                    if ($tapMasuk) {
                        $jamMasuk = Carbon::parse($tapMasuk->waktu_tap)->format('H:i:s');
                    }
                }

                if (!$jamPulang) {
                    $tapPulang = $userTaps->first(function ($t) {
                        return $t->status_kehadiran === 'pulang' ||
                            str_contains(strtoupper((string) $t->sesi?->kode_sesi), 'PULANG');
                    });
                    if ($tapPulang) {
                        $jamPulang = Carbon::parse($tapPulang->waktu_tap)->format('H:i:s');
                    }
                }

                // Tentukan status kehadiran
                $statusKehadiran = null;
                if ($jamPulang) {
                    $statusKehadiran = 'pulang';
                } elseif ($pGuru?->status === 'terlambat' || $userTaps->contains('status_kehadiran', 'terlambat')) {
                    $statusKehadiran = 'terlambat';
                } elseif ($jamMasuk) {
                    $statusKehadiran = 'hadir';
                }

                // Status hadir pada sesi saat ini:
                // Jika sedang sesi PULANG, dikatakan hadir bila sudah tap pulang
                // Jika sesi MASUK/lainnya, dikatakan hadir bila sudah tap masuk
                $isHadir = $isSesiPulang ? !empty($jamPulang) : !empty($jamMasuk);
                $waktuTapTampil = $isSesiPulang ? ($jamPulang ?: $jamMasuk) : ($jamMasuk ?: $jamPulang);
                $roleOrLembaga = $u->jabatan ?: ($u->primaryLembaga?->singkatan ?? 'Yayasan');

                $payload = [
                    'id' => $u->id,
                    'nama' => $u->name,
                    'sub' => $roleOrLembaga,
                    'rfid_uid' => $u->rfid_uid ?: '-',
                    'waktu_tap' => $waktuTapTampil,
                    'jam_masuk' => $jamMasuk,
                    'jam_pulang' => $jamPulang,
                    'status_kehadiran' => $statusKehadiran,
                    'is_hadir' => $isHadir,
                    'is_pulang' => !empty($jamPulang),
                ];

                if ($isHadir) {
                    $hadirList->push($payload);
                } else {
                    $waitingList->push($payload);
                }
            }
        }

        return response()->json([
            'currentTime' => $now->format('H:i:s'),
            'currentDate' => $now->translatedFormat('l, d F Y'),
            'sesiAktif' => [
                'id' => $sesi?->id,
                'kode' => $sesi?->kode_sesi,
                'nama' => $sesi?->nama_sesi ?? 'Tidak Ada Sesi Terjadwal',
                'jam_mulai' => $sesi?->jam_mulai,
                'jam_selesai' => $sesi?->jam_selesai,
                'kategori' => $sesi?->kategori,
            ],
            'isSesiPulang' => $tab === 'pegawai' && $sesi && (
                str_contains(strtoupper((string) $sesi->kode_sesi), 'PULANG') ||
                str_contains(strtoupper((string) $sesi->nama_sesi), 'PULANG')
            ),
            'summary' => [
                'totalTarget' => $totalTarget,
                'sudahHadir' => $hadirList->count(),
                'belumHadir' => $waitingList->count(),
                'persentase' => $totalTarget > 0 ? round(($hadirList->count() / $totalTarget) * 100) : 0,
            ],
            'liveFeeds' => $liveFeeds,
            'waitingList' => $waitingList,
            'hadirList' => $hadirList,
        ]);
    }
}
