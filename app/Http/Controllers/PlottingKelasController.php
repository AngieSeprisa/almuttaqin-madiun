<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlottingKelasController extends Controller
{
    /**
     * Daftar kelas standar berdasarkan lembaga / jenjang
     */
    public static function daftarKelasStandar(string $kodeLembaga): array
    {
        $kode = strtolower($kodeLembaga);
        if ($kode === 'smp') {
            return ['VII A', 'VII B', 'VIII A', 'VIII B', 'IX A', 'IX B'];
        }
        if ($kode === 'sma') {
            return ['X A', 'X B', 'XI A', 'XI B', 'XII A', 'XII B'];
        }
        return [];
    }

    /**
     * Tampilkan halaman Plotting Kelas
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Cek izin akses: yayasan atau yang memiliki izin plotting_kelas
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Kelas.');
        }

        // Lembaga sekolah formal: SMP & SMA
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        if ($sekolahLembagas->isEmpty()) {
            abort(403, 'Menu Plotting Kelas hanya diperuntukkan bagi lembaga sekolah (SMP & SMA).');
        }

        $isYayasan = $authUser->isYayasan();
        $selectedLembaga = null;

        if ($isYayasan) {
            // Yayasan bisa memilih lembaga SMP atau SMA
            $reqKode = strtolower($request->get('lembaga', ''));
            if ($reqKode && in_array($reqKode, ['smp', 'sma'])) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', $reqKode);
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', 'smp') ?: $sekolahLembagas->first();
            }
        } else {
            // Pengguna sekolah terkunci pada lembaganya
            $activeKode = session('active_lembaga');
            $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode)
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());

            if (!$selectedLembaga || !in_array(strtolower($selectedLembaga->kode), ['smp', 'sma'])) {
                abort(403, 'Menu Plotting Kelas hanya dapat dibuka dari lembaga sekolah (SMP atau SMA).');
            }
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        // Ambil daftar kelas standar
        $kelasList = self::daftarKelasStandar($kodeLembaga);

        // Query seluruh siswa aktif di jenjang sekolah ini
        // PENTING: Siswa dengan jenjang 'Reguler' TIDAK MASUK ke data sekolah
        $siswaQuery = Siswa::where('status', 'aktif')
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->where('jenjang', $jenjang)
            ->with('pendaftaran');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $siswaQuery->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhereHas('pendaftaran', function ($p) use ($search) {
                      $p->where('nisn', 'like', "%{$search}%")
                        ->orWhere('no_induk', 'like', "%{$search}%");
                  });
            });
        }

        $allSiswa = $siswaQuery->orderBy('nama_lengkap')->get();

        // Ambil kelas unik tambahan jika ada yang sudah tersimpan di siswa
        $extraKelas = $allSiswa->pluck('kelas')->filter(function ($k) {
            return !empty($k) && $k !== '-';
        })->unique()->diff($kelasList)->values()->all();

        $allDaftarKelas = array_merge($kelasList, $extraKelas);

        // Susun data per kelas
        $dataKelas = [];
        foreach ($allDaftarKelas as $k) {
            $anggota = $allSiswa->filter(function ($s) use ($k) {
                return $s->kelas === $k;
            })->values();

            $dataKelas[] = (object) [
                'nama_kelas'   => $k,
                'jumlah_siswa' => $anggota->count(),
                'anggota'      => $anggota,
            ];
        }

        // Susun siswa belum terdaftar
        $belumTerdaftar = $allSiswa->filter(function ($s) {
            return empty($s->kelas) || $s->kelas === '-';
        })->values();

        // Active Tab: 'kelas' atau 'belum_terdaftar'
        $activeTab = $request->get('tab', 'kelas');
        if (!in_array($activeTab, ['kelas', 'belum_terdaftar'])) {
            $activeTab = 'kelas';
        }

        return view('plotting-kelas.index', compact(
            'isYayasan',
            'sekolahLembagas',
            'selectedLembaga',
            'kodeLembaga',
            'jenjang',
            'allDaftarKelas',
            'dataKelas',
            'belumTerdaftar',
            'activeTab'
        ));
    }

    /**
     * Mutasikan siswa ke kelas tujuan
     */
    public function mutasi(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required',
            'kelas'    => 'required|string|max:50',
        ]);

        $targetKelas = trim($request->kelas);
        $siswaIds = is_array($request->siswa_id) ? $request->siswa_id : [$request->siswa_id];

        // Validasi siswa tidak berstatus reguler
        $updatedCount = Siswa::whereIn('id', $siswaIds)
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->update(['kelas' => $targetKelas]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} siswa berhasil dimutasi ke kelas {$targetKelas}.",
                'kelas'   => $targetKelas,
            ]);
        }

        return back()->with('success', "Siswa berhasil dimutasi ke kelas {$targetKelas}.");
    }

    /**
     * Keluarkan siswa dari kelas (unassign menjadi belum terdaftar)
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required',
        ]);

        $siswaIds = is_array($request->siswa_id) ? $request->siswa_id : [$request->siswa_id];

        $updatedCount = Siswa::whereIn('id', $siswaIds)
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->update(['kelas' => null]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} siswa berhasil dikeluarkan dari kelas.",
            ]);
        }

        return back()->with('success', "Siswa berhasil dipindahkan ke daftar belum terdaftar.");
    }

    /**
     * Ambil data anggota kelas (JSON) untuk modal detail
     */
    public function anggota(Request $request, string $kelas)
    {
        $kelasDecoded = urldecode($kelas);
        $jenjang = strtoupper($request->get('jenjang', 'SMP'));

        $anggota = Siswa::where('status', 'aktif')
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->where('jenjang', $jenjang)
            ->where('kelas', $kelasDecoded)
            ->with('pendaftaran')
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'nama_lengkap' => $s->nama_lengkap,
                    'nisn'         => $s->pendaftaran?->nisn ?? $s->nisn ?? '-',
                    'kelas'        => $s->kelas,
                    'jenjang'      => $s->jenjang,
                ];
            });

        return response()->json([
            'success' => true,
            'kelas'   => $kelasDecoded,
            'total'   => $anggota->count(),
            'anggota' => $anggota,
        ]);
    }
}
