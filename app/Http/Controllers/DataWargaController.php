<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;

class DataWargaController extends Controller
{
    /**
     * Menampilkan daftar Guru yang sudah didaftarkan di menu Guru masing-masing lembaga.
     * Akun yang baru dibuat dan belum didaftarkan di menu Guru lembaga tidak ditampilkan.
     */
    public function guru(Request $request)
    {
        $activeRoom = strtolower((string) session('active_lembaga', ''));

        $sekolahLembagas = Lembaga::where(function ($q) {
            $q->whereIn('kode', ['smp', 'sma'])
              ->orWhere('singkatan', 'SMP')
              ->orWhere('singkatan', 'SMA');
        })->where('is_active', true)->orderBy('urutan')->get();

        $smpLembaga = $sekolahLembagas->first(fn ($l) => strtolower((string) $l->kode) === 'smp' || strtoupper((string) $l->singkatan) === 'SMP');
        $smaLembaga = $sekolahLembagas->first(fn ($l) => strtolower((string) $l->kode) === 'sma' || strtoupper((string) $l->singkatan) === 'SMA');

        $baseRegisteredQuery = fn () => User::with(['primaryLembaga', 'lembagas'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->where('is_guru_sekolah', true)
            ->where(function ($q) {
                $q->whereNull('mapel_status')
                  ->orWhereNotIn('mapel_status', ['ditolak', 'menunggu']);
            });

        $query = $baseRegisteredQuery();

        // Filter lembaga dari request atau dari session active_lembaga jika SMP/SMA
        $selectedLembagaId = $request->input('lembaga_id');
        if ($selectedLembagaId === null && in_array($activeRoom, ['smp', 'sma'], true)) {
            $activeLembagaModel = $activeRoom === 'smp' ? $smpLembaga : $smaLembaga;
            if ($activeLembagaModel) {
                $selectedLembagaId = (string) $activeLembagaModel->id;
            }
        }

        if (!empty($selectedLembagaId) && $selectedLembagaId !== 'semua') {
            $query->where(function ($q) use ($selectedLembagaId) {
                $q->where('primary_lembaga_id', $selectedLembagaId)
                  ->orWhereHas('lembagas', function ($l) use ($selectedLembagaId) {
                      $l->where('lembagas.id', $selectedLembagaId);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'like', "%{$search}%");
            });
        }

        $gurus = $query->orderBy('name')->paginate(20)->withQueryString();

        $totalGuru = $baseRegisteredQuery()->count();
        $totalSmp  = $smpLembaga ? $baseRegisteredQuery()->where(function ($q) use ($smpLembaga) {
            $q->where('primary_lembaga_id', $smpLembaga->id)
              ->orWhereHas('lembagas', fn ($l) => $l->where('lembagas.id', $smpLembaga->id));
        })->count() : 0;
        $totalSma  = $smaLembaga ? $baseRegisteredQuery()->where(function ($q) use ($smaLembaga) {
            $q->where('primary_lembaga_id', $smaLembaga->id)
              ->orWhereHas('lembagas', fn ($l) => $l->where('lembagas.id', $smaLembaga->id));
        })->count() : 0;

        return view('data-warga.guru', compact(
            'gurus',
            'sekolahLembagas',
            'selectedLembagaId',
            'totalGuru',
            'totalSmp',
            'totalSma'
        ));
    }

    /**
     * Menampilkan daftar siswa yang lulus dari Pondok maupun Sekolah.
     */
    public function lulus(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $query = Siswa::query()
            ->with('pendaftaran')
            ->where('status', 'lulus');

        $query->forLembaga($activeRoom);

        if ($request->filled('kategori_lulus')) {
            $query->where('kategori_lulus', $request->kategori_lulus);
        }

        if ($request->filled('jenjang')) {
            $jenjang = $request->jenjang;
            $query->where(function ($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)
                  ->orWhereHas('pendaftaran', fn ($p) => $p->where('jenjang', $jenjang));
            });
        }

        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%")
                  ->orWhere('tahun_lulus', 'like', "%{$search}%");
            });
        }

        $siswaLulus = $query->latest('tanggal_lulus')->latest('id')->paginate(20)->withQueryString();

        $totalLulus   = Siswa::forLembaga($activeRoom)->where('status', 'lulus')->count();
        $lulusPondok  = Siswa::forLembaga($activeRoom)->where('status', 'lulus')
            ->whereIn('kategori_lulus', ['Pondok', 'Pondok & Sekolah'])->count();
        $lulusSekolah = Siswa::forLembaga($activeRoom)->where('status', 'lulus')
            ->whereIn('kategori_lulus', ['Sekolah', 'Pondok & Sekolah'])->count();

        $tahunList = Siswa::where('status', 'lulus')
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '!=', '')
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus');

        $siswaAktifList = Siswa::forLembaga($activeRoom)
            ->where(function ($q) {
                $q->whereNotIn('status', ['lulus', 'mutasi'])
                  ->orWhereNull('status');
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jenjang', 'kelas', 'kamar']);

        $jenjangList = ['Reguler', 'SMP', 'SMA'];
        $kategoriLulusList = ['Pondok', 'Sekolah', 'Pondok & Sekolah'];

        return view('data-warga.lulus', compact(
            'siswaLulus',
            'totalLulus',
            'lulusPondok',
            'lulusSekolah',
            'tahunList',
            'siswaAktifList',
            'jenjangList',
            'kategoriLulusList'
        ));
    }

    /**
     * Menambahkan siswa ke daftar Lulus.
     */
    public function storeLulus(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'kategori_lulus'    => 'required|in:Pondok,Sekolah,Pondok & Sekolah',
            'tahun_lulus'       => 'required|string|max:20',
            'tanggal_lulus'     => 'nullable|date',
            'keterangan_status' => 'nullable|string|max:1000',
        ], [
            'siswa_id.required'       => 'Pilih nama siswa.',
            'kategori_lulus.required' => 'Pilih kategori kelulusan.',
            'tahun_lulus.required'    => 'Tahun lulus wajib diisi.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $siswa->update([
            'status'            => 'lulus',
            'kategori_lulus'    => $validated['kategori_lulus'],
            'tahun_lulus'       => trim($validated['tahun_lulus']),
            'tanggal_lulus'     => $validated['tanggal_lulus'] ?: now()->toDateString(),
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.lulus')
            ->with('success', "Data kelulusan {$siswa->nama_lengkap} berhasil disimpan.");
    }

    /**
     * Memperbarui data kelulusan siswa.
     */
    public function updateLulus(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'kategori_lulus'    => 'required|in:Pondok,Sekolah,Pondok & Sekolah',
            'tahun_lulus'       => 'required|string|max:20',
            'tanggal_lulus'     => 'nullable|date',
            'keterangan_status' => 'nullable|string|max:1000',
        ]);

        $siswa->update([
            'status'            => 'lulus',
            'kategori_lulus'    => $validated['kategori_lulus'],
            'tahun_lulus'       => trim($validated['tahun_lulus']),
            'tanggal_lulus'     => $validated['tanggal_lulus'] ?: $siswa->tanggal_lulus,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.lulus')
            ->with('success', "Data kelulusan {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    /**
     * Menampilkan daftar siswa yang mutasi.
     */
    public function mutasi(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $query = Siswa::query()
            ->with('pendaftaran')
            ->where('status', 'mutasi');

        $query->forLembaga($activeRoom);

        if ($request->filled('jenjang')) {
            $jenjang = $request->jenjang;
            $query->where(function ($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)
                  ->orWhereHas('pendaftaran', fn ($p) => $p->where('jenjang', $jenjang));
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%")
                  ->orWhere('tujuan_mutasi', 'like', "%{$search}%")
                  ->orWhere('keterangan_status', 'like', "%{$search}%");
            });
        }

        $siswaMutasi = $query->latest('tanggal_mutasi')->latest('id')->paginate(20)->withQueryString();

        $totalMutasi   = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->count();
        $mutasiSmp     = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'SMP')->count();
        $mutasiSma     = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'SMA')->count();
        $mutasiReguler = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'Reguler')->count();

        $siswaAktifList = Siswa::forLembaga($activeRoom)
            ->where(function ($q) {
                $q->whereNotIn('status', ['lulus', 'mutasi'])
                  ->orWhereNull('status');
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jenjang', 'kelas', 'kamar']);

        $jenjangList = ['Reguler', 'SMP', 'SMA'];

        return view('data-warga.mutasi', compact(
            'siswaMutasi',
            'totalMutasi',
            'mutasiSmp',
            'mutasiSma',
            'mutasiReguler',
            'siswaAktifList',
            'jenjangList'
        ));
    }

    /**
     * Menambahkan siswa ke daftar Mutasi.
     */
    public function storeMutasi(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tanggal_mutasi'    => 'required|date',
            'tujuan_mutasi'     => 'nullable|string|max:255',
            'keterangan_status' => 'nullable|string|max:1000',
        ], [
            'siswa_id.required'       => 'Pilih nama siswa.',
            'tanggal_mutasi.required' => 'Tanggal mutasi wajib diisi.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $siswa->update([
            'status'            => 'mutasi',
            'tanggal_mutasi'    => $validated['tanggal_mutasi'],
            'tujuan_mutasi'     => $validated['tujuan_mutasi'] ?? null,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.mutasi')
            ->with('success', "Data mutasi {$siswa->nama_lengkap} berhasil disimpan.");
    }

    /**
     * Memperbarui data mutasi siswa.
     */
    public function updateMutasi(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'tanggal_mutasi'    => 'required|date',
            'tujuan_mutasi'     => 'nullable|string|max:255',
            'keterangan_status' => 'nullable|string|max:1000',
        ]);

        $siswa->update([
            'status'            => 'mutasi',
            'tanggal_mutasi'    => $validated['tanggal_mutasi'],
            'tujuan_mutasi'     => $validated['tujuan_mutasi'] ?? null,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.mutasi')
            ->with('success', "Data mutasi {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    /**
     * Mengembalikan status siswa dari Lulus / Mutasi menjadi Aktif kembali.
     */
    public function kembalikanAktif(Request $request, Siswa $siswa)
    {
        $asalStatus = $siswa->status;
        $siswa->update([
            'status'            => 'aktif',
            'kategori_lulus'    => null,
            'tahun_lulus'       => null,
            'tanggal_lulus'     => null,
            'tanggal_mutasi'    => null,
            'tujuan_mutasi'     => null,
            'keterangan_status' => null,
        ]);

        $redirectRoute = $asalStatus === 'mutasi' ? 'data-warga.mutasi' : 'data-warga.lulus';

        return redirect()->route($redirectRoute)
            ->with('success', "{$siswa->nama_lengkap} dikembalikan ke daftar Siswa Aktif.");
    }
}

