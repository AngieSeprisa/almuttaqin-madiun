<?php

namespace App\Http\Controllers;

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
     * Tampilan Utama Dashboard Monitoring Real-Time Multi-Lokasi.
     */
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Jakarta');
        $currentTime = $now->format('H:i:s');
        $today = $now->toDateString();

        // 1. Ambil Sesi yang sedang aktif saat ini
        $activeSesi = MasterSesi::where('jam_mulai', '<=', $currentTime)
            ->where('jam_selesai', '>=', $currentTime)
            ->first();

        // Jika tidak ada yang sedang aktif persis jam sekarang, ambil sesi terdekat berikutnya
        if (!$activeSesi) {
            $activeSesi = MasterSesi::where('jam_mulai', '>=', $currentTime)
                ->orderBy('jam_mulai', 'asc')
                ->first() ?: MasterSesi::first();
        }

        $allSesis = MasterSesi::orderBy('jam_mulai')->get();
        $allDevices = MasterDevice::where('status_aktif', true)->get();

        // 2. Daftar Kamar untuk Filter Tab Pondok
        $kamars = Siswa::whereNotNull('kamar')
            ->where('kamar', '!=', '')
            ->distinct()
            ->orderBy('kamar')
            ->pluck('kamar');

        return view('dashboard-presensi', compact(
            'activeSesi',
            'allSesis',
            'allDevices',
            'kamars'
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
        $kamar = $request->get('kamar', 'Reguler'); // Default Kamar Reguler (16 santri)

        // Tentukan sesi
        if ($sesiId) {
            $sesi = MasterSesi::find($sesiId);
        } else {
            $sesi = MasterSesi::where('jam_mulai', '<=', $currentTime)
                ->where('jam_selesai', '>=', $currentTime)
                ->first() ?: MasterSesi::first();
        }

        // Live Feed 10 Tap Terakhir (Global)
        $liveFeeds = Presensi::with(['sesi', 'siswa', 'user'])
            ->whereDate('waktu_tap', $today)
            ->latest('waktu_tap')
            ->take(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->tipe_entitas === 'santri' ? ($p->siswa?->nama_lengkap ?? 'Santri') : ($p->user?->name ?? 'Pegawai'),
                    'kamar_or_role' => $p->tipe_entitas === 'santri' ? ($p->siswa?->kamar ?? 'Reguler') : 'Guru/Pegawai',
                    'sesi' => $p->sesi?->nama_sesi ?? '-',
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

            // ID santri yang sudah tap di sesi ini hari ini
            $hadirIds = Presensi::whereDate('waktu_tap', $today)
                ->when($sesi, fn($q) => $q->where('sesi_id', $sesi->id))
                ->where('tipe_entitas', 'santri')
                ->pluck('entitas_id')
                ->toArray();

            $hadirEntities = $targetEntities->whereIn('id', $hadirIds);
            $waitingEntities = $targetEntities->whereNotIn('id', $hadirIds);

            $hadirList = $hadirEntities->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama_lengkap,
                'sub' => "Kamar: {$s->kamar}",
                'rfid_uid' => $s->rfid_uid ?? '-',
            ])->values();

            $waitingList = $waitingEntities->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama_lengkap,
                'sub' => "Kamar: {$s->kamar}",
                'rfid_uid' => $s->rfid_uid ?? 'Belum ada kartu',
            ])->values();

        } elseif ($tab === 'sekolah') {
            $targetEntities = Siswa::where('status', 'aktif')
                ->whereIn('jenjang', ['SMP', 'SMA'])
                ->orderBy('nama_lengkap')
                ->get();
            $totalTarget = $targetEntities->count();

            $hadirIds = Presensi::whereDate('waktu_tap', $today)
                ->when($sesi, fn($q) => $q->where('sesi_id', $sesi->id))
                ->where('tipe_entitas', 'santri')
                ->pluck('entitas_id')
                ->toArray();

            $hadirList = $targetEntities->whereIn('id', $hadirIds)->values();
            $waitingList = $targetEntities->whereNotIn('id', $hadirIds)->values();

        } else { // 'pegawai'
            $targetEntities = User::orderBy('name')->get();
            $totalTarget = $targetEntities->count();

            $hadirIds = Presensi::whereDate('waktu_tap', $today)
                ->when($sesi, fn($q) => $q->where('sesi_id', $sesi->id))
                ->where('tipe_entitas', 'guru_pegawai')
                ->pluck('entitas_id')
                ->toArray();

            $hadirList = $targetEntities->whereIn('id', $hadirIds)->map(fn($u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'sub' => $u->email,
                'rfid_uid' => $u->rfid_uid ?? '-',
            ])->values();

            $waitingList = $targetEntities->whereNotIn('id', $hadirIds)->map(fn($u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'sub' => $u->email,
                'rfid_uid' => $u->rfid_uid ?? 'Belum ada kartu',
            ])->values();
        }

        return response()->json([
            'currentTime' => $now->format('H:i:s'),
            'currentDate' => $now->translatedFormat('l, d F Y'),
            'sesiAktif' => [
                'id' => $sesi?->id,
                'nama' => $sesi?->nama_sesi ?? 'Tidak Ada Sesi Terjadwal',
                'jam_mulai' => $sesi?->jam_mulai,
                'jam_selesai' => $sesi?->jam_selesai,
                'kategori' => $sesi?->kategori,
            ],
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
