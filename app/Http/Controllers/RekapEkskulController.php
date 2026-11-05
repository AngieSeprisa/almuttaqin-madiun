<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\JurnalEkskul;
use App\Models\JurnalEkskulAbsensi;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RekapEkskulController extends Controller
{
    /**
     * Halaman Rekap Absensi & Jurnal Ekstrakurikuler
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('rekap_ekskul', 'view') && !$authUser->canDo('rekap_absensi', 'view') && !$authUser->canDo('sekolah', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Rekap Ekstrakurikuler.');
        }

        $activeLembagaKode = session('active_lembaga');
        if ($authUser->isYayasan()) {
            $reqKode = strtolower($request->get('lembaga', ''));
            $activeLembagaKode = in_array($reqKode, ['smp', 'sma']) ? $reqKode : ($activeLembagaKode ?: 'smp');
        } else {
            $activeLembagaKode = in_array(strtolower((string)$activeLembagaKode), ['smp', 'sma']) ? strtolower((string)$activeLembagaKode) : 'smp';
        }

        $selectedLembaga = Lembaga::where('kode', $activeLembagaKode)->first() ?: Lembaga::where('kode', 'smp')->first();
        $jenjang = strtoupper($activeLembagaKode);

        // Ambil daftar ekskul
        $ekskuls = Ekstrakurikuler::where(function ($q) use ($selectedLembaga, $jenjang) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', $jenjang)
                  ->orWhere('jenjang', 'ALL');
            })
            ->orderBy('nama_ekskul')
            ->get();

        $selectedEkskulId = $request->get('ekskul_id');
        $selectedEkskul = null;
        if ($selectedEkskulId) {
            $selectedEkskul = $ekskuls->firstWhere('id', $selectedEkskulId);
        }
        if (!$selectedEkskul && $ekskuls->isNotEmpty()) {
            $selectedEkskul = $ekskuls->first();
        }

        $bulan = $request->get('bulan');
        $tahun = (int) $request->get('tahun', date('Y'));

        $jurnals = collect();
        $matriksSiswa = collect();
        $totalPertemuan = 0;
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin  = 0;
        $totalAlpa  = 0;
        $persentaseKehadiran = 0;

        if ($selectedEkskul) {
            // Ambil seluruh jurnal pertemuan pada ekskul dan periode filter
            $jurnalQuery = JurnalEkskul::where('ekstrakurikuler_id', $selectedEkskul->id);
            if ($bulan) {
                $jurnalQuery->whereMonth('tanggal', $bulan);
            }
            if ($tahun) {
                $jurnalQuery->whereYear('tanggal', $tahun);
            }

            $jurnals = $jurnalQuery->orderBy('tanggal', 'asc')->get();
            $totalPertemuan = $jurnals->count();

            // Ambil daftar seluruh siswa yang tergabung di ekskul ini
            $anggotaSiswa = $selectedEkskul->siswas()
                ->where(function ($q) {
                    $q->whereIn('siswas.status', ['aktif', 'pulang', 'sakit'])
                      ->orWhereNull('siswas.status');
                })
                ->orderBy('nama_lengkap')
                ->get();

            // Ambil seluruh data absensi pada jurnal-jurnal tersebut
            $jurnalIds = $jurnals->pluck('id')->all();
            $allAbsensis = JurnalEkskulAbsensi::whereIn('jurnal_ekskul_id', $jurnalIds)->get();

            $absensiMap = [];
            foreach ($allAbsensis as $ab) {
                $absensiMap[$ab->siswa_id][$ab->jurnal_ekskul_id] = $ab->status;
            }

            // Susun matriks per siswa
            foreach ($anggotaSiswa as $siswa) {
                $h = 0;
                $s = 0;
                $i = 0;
                $a = 0;
                $pertemuanStatus = [];

                foreach ($jurnals as $j) {
                    $st = $absensiMap[$siswa->id][$j->id] ?? null;
                    $pertemuanStatus[$j->id] = $st;

                    if ($st === 'hadir') $h++;
                    elseif ($st === 'sakit') $s++;
                    elseif ($st === 'izin') $i++;
                    elseif ($st === 'alpa') $a++;
                }

                $totalHadir += $h;
                $totalSakit += $s;
                $totalIzin  += $i;
                $totalAlpa  += $a;

                $persen = $totalPertemuan > 0 ? round(($h / $totalPertemuan) * 100, 1) : 0;

                $matriksSiswa->push([
                    'id'           => $siswa->id,
                    'nama'         => $siswa->nama_lengkap,
                    'kelas'        => $siswa->kelas ?? '-',
                    'pertemuan'    => $pertemuanStatus,
                    'total_h'      => $h,
                    'total_s'      => $s,
                    'total_i'      => $i,
                    'total_a'      => $a,
                    'persen_hadir' => $persen,
                ]);
            }

            $totalAllAttendance = $totalHadir + $totalSakit + $totalIzin + $totalAlpa;
            if ($totalAllAttendance > 0) {
                $persentaseKehadiran = round(($totalHadir / $totalAllAttendance) * 100, 1);
            }
        }

        return view('rekap-absensi.ekskul', compact(
            'selectedLembaga',
            'jenjang',
            'ekskuls',
            'selectedEkskul',
            'jurnals',
            'matriksSiswa',
            'totalPertemuan',
            'totalHadir',
            'totalSakit',
            'totalIzin',
            'totalAlpa',
            'persentaseKehadiran',
            'bulan',
            'tahun'
        ));
    }
}
