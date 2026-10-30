<?php

namespace App\Http\Controllers;

use App\Models\MasterKelasPengajian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlottingKelasPengajianController extends Controller
{
    /**
     * Normalisasi input siswa_id (mendukung single ID, array siswa_id[], maupun string dipisah koma).
     */
    protected function parseSiswaIds(mixed $raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } elseif (is_string($raw) && str_contains($raw, ',')) {
            $items = explode(',', $raw);
        } else {
            $items = [$raw];
        }

        return collect($items)
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Tampilkan halaman Plotting Kelas Pengajian & Manajemen Master Kelas.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'view')
            && !$authUser->canDo('plotting_kamar', 'view')
            && !$authUser->canDo('pengaturan_pondok', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Kelas Pengajian.');
        }

        // Pastikan kelas pengajian standar tersedia
        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $masterKelasList = MasterKelasPengajian::withCount(['siswas' => function ($q) {
            $q->where('status', 'aktif');
        }])->orderBy('id')->get();

        // Query seluruh santri aktif beserta relasi pendaftaran dan kelas pengajian
        $query = Siswa::where('status', 'aktif')
            ->with(['pendaftaran', 'kelasPengajian']);

        // 1. Filter Pencarian Cepat
        if ($request->filled('search')) {
            $search = mb_strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nama_lengkap) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(kamar) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(kelas) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('pendaftaran', function ($p) use ($search) {
                      $p->whereRaw('LOWER(nisn) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(no_induk) LIKE ?', ["%{$search}%"]);
                  })
                  ->orWhereHas('kelasPengajian', function ($kp) use ($search) {
                      $kp->whereRaw('LOWER(nama_kelas) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        // 2. Filter Kelas Pengajian
        if ($request->filled('kelas_pengajian_id')) {
            $filterKelas = $request->kelas_pengajian_id;
            if ($filterKelas === 'unassigned') {
                $query->whereNull('kelas_pengajian_id');
            } elseif ($filterKelas === 'assigned') {
                $query->whereNotNull('kelas_pengajian_id');
            } elseif (is_numeric($filterKelas) && (int) $filterKelas > 0) {
                $query->where('kelas_pengajian_id', (int) $filterKelas);
            }
        }

        // 3. Filter Gender (Putra/Putri dari data santri pendaftaran)
        if ($request->filled('gender')) {
            $g = strtolower(trim($request->gender));
            if (in_array($g, ['l', 'laki-laki', 'putra'])) {
                $query->whereHas('pendaftaran', function ($p) {
                    $p->where('jenis_kelamin', 'laki-laki');
                });
            } elseif (in_array($g, ['p', 'perempuan', 'putri'])) {
                $query->whereHas('pendaftaran', function ($p) {
                    $p->where('jenis_kelamin', 'perempuan');
                });
            }
        }

        // 4. Filter Jenjang Sekolah (SMP, SMA, Reguler)
        if ($request->filled('jenjang')) {
            $j = strtolower(trim($request->jenjang));
            if ($j !== 'all' && $j !== '') {
                $query->whereRaw('LOWER(jenjang) = ?', [$j]);
            }
        }

        $allSantri = $query->orderBy('nama_lengkap')->get();

        // Statistik Keseluruhan Santri Aktif
        $totalSantriOverall = Siswa::where('status', 'aktif')->count();
        $totalTerplotting = Siswa::where('status', 'aktif')->whereNotNull('kelas_pengajian_id')->count();
        $totalBelumTerplotting = Siswa::where('status', 'aktif')->whereNull('kelas_pengajian_id')->count();
        $totalKelas = $masterKelasList->count();

        // Hitung persentase plotting
        $persenTerplotting = $totalSantriOverall > 0
            ? round(($totalTerplotting / $totalSantriOverall) * 100)
            : 0;

        return view('plotting-pengajian.index', compact(
            'masterKelasList',
            'allSantri',
            'totalSantriOverall',
            'totalTerplotting',
            'totalBelumTerplotting',
            'totalKelas',
            'persenTerplotting'
        ));
    }

    /**
     * Mutasi / tetapkan santri (tunggal maupun massal) ke Kelas Pengajian tujuan.
     */
    public function mutasi(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memutasi kelas pengajian santri.');
        }

        $request->validate([
            'siswa_id'           => 'required',
            'kelas_pengajian_id' => 'nullable',
        ]);

        $siswaIds = $this->parseSiswaIds($request->siswa_id);

        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih untuk ditetapkan.');
        }

        $targetId = $request->kelas_pengajian_id;

        // Jika opsi kosongkan / unassign dipilih
        if ($targetId === 'unassign' || $targetId === '' || $targetId === null || $targetId === '0') {
            $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => null]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} santri berhasil dikeluarkan dari kelas pengajian (Belum Diplot).",
                ]);
            }

            return back()->with('success', "{$count} santri berhasil dikeluarkan dari kelas pengajian (Belum Diplot).");
        }

        $kelas = MasterKelasPengajian::find($targetId);
        if (!$kelas) {
            return back()->with('error', 'Kelas pengajian tujuan tidak ditemukan di sistem.');
        }

        $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => $kelas->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} santri berhasil ditetapkan ke {$kelas->nama_kelas}.",
                'kelas'   => $kelas->nama_kelas,
            ]);
        }

        return back()->with('success', "{$count} santri berhasil ditetapkan ke {$kelas->nama_kelas}.");
    }

    /**
     * Keluarkan santri dari kelas pengajian (unassign).
     */
    public function unassign(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengeluarkan santri dari kelas pengajian.');
        }

        $request->validate([
            'siswa_id' => 'required',
        ]);

        $siswaIds = $this->parseSiswaIds($request->siswa_id);
        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih.');
        }

        $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => null]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} santri berhasil dikeluarkan dari kelas pengajian.",
            ]);
        }

        return back()->with('success', "{$count} santri berhasil dikeluarkan dari kelas pengajian.");
    }

    /**
     * Tambah Master Kelas Pengajian baru.
     */
    public function storeKelas(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'create')
            && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah kelas pengajian.');
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100', Rule::unique('master_kelas_pengajians', 'nama_kelas')],
            'kode_kelas' => ['nullable', 'string', 'max:50', Rule::unique('master_kelas_pengajians', 'kode_kelas')],
            'jenjang'    => ['required', 'in:smp,sma,reguler,umum'],
        ], [
            'nama_kelas.required' => 'Nama kelas pengajian wajib diisi.',
            'nama_kelas.unique'   => 'Nama kelas pengajian tersebut sudah ada.',
            'kode_kelas.unique'   => 'Kode kelas pengajian tersebut sudah ada.',
        ]);

        $kode = !empty($validated['kode_kelas'])
            ? strtoupper(trim($validated['kode_kelas']))
            : strtoupper('KLS_' . Str::slug($validated['nama_kelas'], '_'));

        $kelas = MasterKelasPengajian::create([
            'kode_kelas' => $kode,
            'nama_kelas' => trim($validated['nama_kelas']),
            'jenjang'    => $validated['jenjang'],
        ]);

        return redirect()->route('plotting-pengajian.index')
            ->with('success', "Master {$kelas->nama_kelas} berhasil ditambahkan.");
    }

    /**
     * Perbarui Master Kelas Pengajian.
     */
    public function updateKelas(Request $request, MasterKelasPengajian $kelas)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah kelas pengajian.');
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100', Rule::unique('master_kelas_pengajians', 'nama_kelas')->ignore($kelas->id)],
            'kode_kelas' => ['required', 'string', 'max:50', Rule::unique('master_kelas_pengajians', 'kode_kelas')->ignore($kelas->id)],
            'jenjang'    => ['required', 'in:smp,sma,reguler,umum'],
        ]);

        $kelas->update([
            'kode_kelas' => strtoupper(trim($validated['kode_kelas'])),
            'nama_kelas' => trim($validated['nama_kelas']),
            'jenjang'    => $validated['jenjang'],
        ]);

        return redirect()->route('plotting-pengajian.index')
            ->with('success', "Data {$kelas->nama_kelas} berhasil diperbarui.");
    }

    /**
     * Hapus Master Kelas Pengajian (dengan proteksi santri aktif).
     */
    public function destroyKelas(Request $request, MasterKelasPengajian $kelas)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'delete')
            && !$authUser->canDo('plotting_kamar', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kelas pengajian.');
        }

        $santriCount = $kelas->siswas()->where('status', 'aktif')->count();
        if ($santriCount > 0) {
            return back()->with('error', "Kelas \"{$kelas->nama_kelas}\" tidak dapat dihapus karena masih memiliki {$santriCount} santri aktif. Silakan mutasikan santri ke kelas lain terlebih dahulu.");
        }

        $nama = $kelas->nama_kelas;
        $kelas->delete();

        return redirect()->route('plotting-pengajian.index')
            ->with('success', "Kelas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Muat kelas pengajian standar secara otomatis.
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'create')
            && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $added = MasterKelasPengajian::seedStandar();

        return redirect()->route('plotting-pengajian.index')
            ->with('success', "Berhasil memeriksa dan menambahkan {$added} kelas pengajian standar.");
    }
}
