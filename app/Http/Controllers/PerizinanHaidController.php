<?php

namespace App\Http\Controllers;

use App\Models\PerizinanHaid;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerizinanHaidController extends Controller
{
    /**
     * Tampilkan daftar perizinan haid santriwati.
     */
    public function index(Request $request)
    {
        $query = PerizinanHaid::query()->with(['siswa', 'creator']);
        $today = Carbon::today()->toDateString();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kamar')) {
            $query->where('kamar', $request->kamar);
        }

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $perizinan = $query->latest('id')->paginate($perPage)->withQueryString();

        // Ambil daftar kamar putri unik
        $kamars = PerizinanHaid::whereNotNull('kamar')
            ->where('kamar', '!=', '')
            ->distinct()
            ->orderBy('kamar')
            ->pluck('kamar');

        // Statistik ringkas
        $totalAktif = PerizinanHaid::where('status', 'sedang_haid')->count();
        $totalBulanIni = PerizinanHaid::whereMonth('tanggal_mulai', Carbon::today()->month)
            ->whereYear('tanggal_mulai', Carbon::today()->year)
            ->count();
        $totalSuci = PerizinanHaid::where('status', 'sudah_suci')->count();

        return view('perizinan.haid', compact('perizinan', 'kamars', 'today', 'totalAktif', 'totalBulanIni', 'totalSuci'));
    }

    /**
     * Tampilkan formulir input perizinan haid baru.
     */
    public function create()
    {
        // Utamakan santriwati (Perempuan / Kamar Putri)
        $daftarSiswa = Siswa::where(function ($q) {
            $q->where('jenis_kelamin', 'Perempuan')
              ->orWhere('jenis_kelamin', 'P')
              ->orWhere('kamar', 'like', '%putri%')
              ->orWhere('kamar', 'like', '%banat%');
        })
        ->orderBy('nama_lengkap', 'asc')
        ->get(['id', 'nama_lengkap', 'kamar', 'status', 'jenis_kelamin']);

        // Jika data siswa putri kosong (misal belum terisi gender), tampilkan semua santri
        if ($daftarSiswa->isEmpty()) {
            $daftarSiswa = Siswa::orderBy('nama_lengkap', 'asc')->get(['id', 'nama_lengkap', 'kamar', 'status', 'jenis_kelamin']);
        }

        $today = Carbon::today()->toDateString();
        // Estimasi selesai standar 7 hari
        $estimasiSelesai = Carbon::today()->addDays(6)->toDateString();

        return view('perizinan.haid_create', compact('daftarSiswa', 'today', 'estimasiSelesai'));
    }

    /**
     * Simpan data perizinan haid baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id'        => 'nullable|exists:siswas,id',
            'nama'            => 'required_without:siswa_id|nullable|string|max:255',
            'kamar'           => 'nullable|string|max:100',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'          => 'nullable|in:sedang_haid,sudah_suci',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $siswa = null;
        if ($request->filled('siswa_id')) {
            $siswa = Siswa::find($request->siswa_id);
        }

        $nama = $siswa ? $siswa->nama_lengkap : $request->nama;
        $kamar = $request->kamar ?: ($siswa ? $siswa->kamar : '-');
        $status = $request->input('status', 'sedang_haid');

        PerizinanHaid::create([
            'siswa_id'        => $siswa ? $siswa->id : null,
            'nama'            => $nama,
            'kamar'           => $kamar,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status'          => $status,
            'keterangan'      => $request->keterangan ?: 'Udzur syar\'i (haid)',
            'created_by'      => Auth::id(),
        ]);

        return redirect()->route('perizinan.haid')
            ->with('success', "Data perizinan haid santriwati {$nama} berhasil dicatat. Selama masa udzur, presensi sholat otomatis tercatat HAID (bukan Alpa).");
    }

    /**
     * Tandai santriwati sudah suci (selesai haid).
     */
    public function suci($id)
    {
        $haid = PerizinanHaid::findOrFail($id);
        $haid->update([
            'status'          => 'sudah_suci',
            'tanggal_selesai' => Carbon::today()->toDateString(),
        ]);

        return redirect()->route('perizinan.haid')
            ->with('success', "Santriwati {$haid->nama} telah ditandai sudah suci. Mulai waktu sholat berikutnya kewajiban presensi sholat kembali aktif.");
    }

    /**
     * Perbarui data perizinan haid.
     */
    public function update(Request $request, $id)
    {
        $haid = PerizinanHaid::findOrFail($id);

        $request->validate([
            'kamar'           => 'nullable|string|max:100',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status'          => 'required|in:sedang_haid,sudah_suci',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $haid->update([
            'kamar'           => $request->kamar,
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status'          => $request->status,
            'keterangan'      => $request->filled('keterangan') ? $request->keterangan : ($haid->keterangan ?: 'Udzur syar\'i (haid)'),
        ]);

        return redirect()->route('perizinan.haid')
            ->with('success', "Data perizinan haid santriwati {$haid->nama} berhasil diperbarui.");
    }

    /**
     * Hapus data perizinan haid.
     */
    public function destroy($id)
    {
        $haid = PerizinanHaid::findOrFail($id);
        $nama = $haid->nama;
        $haid->delete();

        return redirect()->route('perizinan.haid')
            ->with('success', "Data perizinan haid {$nama} berhasil dihapus.");
    }
}

