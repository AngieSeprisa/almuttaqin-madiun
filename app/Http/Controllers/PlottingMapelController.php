<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlottingMapelController extends Controller
{
    /**
     * Tampilkan halaman Plotting Mata Pelajaran.
     * Pengaturan Sekolah: HANYA untuk lembaga sekolah (SMP & SMA).
     * Lembaga Pondok dan Yayasan tidak masuk ke dalam pengaturan sekolah.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Cek izin akses: yayasan atau yang diberi izin plotting_mapel
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_mapel', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Mata Pelajaran.');
        }

        // Lembaga sekolah yang valid: SMP dan SMA (kategori formal)
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        // Jika user tidak memiliki akses ke sekolah manapun
        if ($sekolahLembagas->isEmpty()) {
            abort(403, 'Menu Plotting Mata Pelajaran hanya untuk lembaga sekolah (SMP & SMA).');
        }

        $isYayasan = $authUser->isYayasan();

        // Tentukan lembaga yang sedang dipilih/difilter
        $selectedLembagaId = null;
        $currentLembaga = null;

        if ($isYayasan) {
            // Yayasan bisa memilih 'all' (semua sekolah) atau memilih sekolah tertentu
            $reqLembaga = $request->get('lembaga_id');
            if ($reqLembaga && $reqLembaga !== 'all') {
                $selectedLembagaId = (int) $reqLembaga;
                $currentLembaga = $sekolahLembagas->firstWhere('id', $selectedLembagaId);
            } elseif ($request->filled('lembaga')) {
                // Mendukung filter lewat kode lembaga (?lembaga=smp)
                $currentLembaga = $sekolahLembagas->firstWhere('kode', $request->lembaga);
                if ($currentLembaga) {
                    $selectedLembagaId = $currentLembaga->id;
                }
            }
        } else {
            // Non-Yayasan (SMP/SMA): Terkunci pada lembaga sekolahnya sendiri
            $activeKode = session('active_lembaga');
            $currentLembaga = $sekolahLembagas->firstWhere('kode', $activeKode) 
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());

            if (!$currentLembaga) {
                abort(403, 'Anda tidak memiliki hak akses pada lembaga sekolah ini.');
            }

            // Jika user mencoba kirim lembaga_id lain di URL, pastikan tetap terkunci ke lembaga sekolahnya
            if ($request->filled('lembaga_id')) {
                $requested = $sekolahLembagas->firstWhere('id', (int) $request->lembaga_id);
                if ($requested) {
                    $currentLembaga = $requested;
                }
            }

            $selectedLembagaId = $currentLembaga->id;
        }

        // Query dasar Mata Pelajaran (hanya untuk lembaga sekolah)
        $query = MataPelajaran::with('lembaga')
            ->whereIn('lembaga_id', $sekolahLembagas->pluck('id'));

        // Scoping Lembaga yang dipilih:
        if ($selectedLembagaId) {
            $query->where('lembaga_id', $selectedLembagaId);
        }

        // Filter Pencarian Teks (mata pelajaran dan kode)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        // Statistik Metrik
        $baseScopeQuery = MataPelajaran::whereIn('lembaga_id', $sekolahLembagas->pluck('id'));
        if ($selectedLembagaId) {
            $baseScopeQuery->where('lembaga_id', $selectedLembagaId);
        }

        $stats = [
            'total_mapel' => (clone $baseScopeQuery)->count(),
            'total_kode'  => (clone $baseScopeQuery)->whereNotNull('kode')->where('kode', '!=', '')->count(),
        ];

        // Daftar Mata Pelajaran Terurut
        $mataPelajarans = $query->orderBy('lembaga_id')
            ->orderBy('mata_pelajaran')
            ->paginate(15)
            ->withQueryString();

        return view('plotting-mapel.index', [
            'mataPelajarans'     => $mataPelajarans,
            'stats'              => $stats,
            'accessibleLembagas' => $sekolahLembagas, // Khusus sekolah (SMP & SMA)
            'currentLembaga'     => $currentLembaga,
            'selectedLembagaId'  => $selectedLembagaId,
            'isYayasan'          => $isYayasan,
            'authUser'           => $authUser,
        ]);
    }

    /**
     * Simpan mata pelajaran baru ke lembaga sekolah.
     * Hanya memasukkan nama mata pelajaran dan kode mata pelajaran.
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_mapel', 'create')) {
            abort(403, 'Anda tidak memiliki hak untuk menambah mata pelajaran.');
        }

        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        });

        // Dukung input baik mata_pelajaran/nama_pelajaran dan kode/kode_mapel
        $namaMapel = trim((string)($request->input('mata_pelajaran') ?? $request->input('nama_pelajaran') ?? ''));
        $kodeMapel = trim(strtoupper((string)($request->input('kode') ?? $request->input('kode_mapel') ?? '')));

        if ($authUser->isYayasan()) {
            $request->validate(['lembaga_id' => 'required|exists:lembagas,id']);
            $targetLembagaId = (int) $request->input('lembaga_id');
        } else {
            $activeKode = session('active_lembaga');
            $currentLembaga = $sekolahLembagas->firstWhere('kode', $activeKode) ?: $sekolahLembagas->first();
            if (!$currentLembaga) {
                return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga sekolah tidak valid.']);
            }
            $targetLembagaId = $currentLembaga->id;
        }

        // Pastikan target adalah lembaga sekolah (bukan pondok/yayasan)
        if (!$sekolahLembagas->contains('id', $targetLembagaId)) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Plotting mata pelajaran hanya diperuntukkan bagi lembaga sekolah (SMP/SMA).']);
        }

        if (empty($namaMapel) || empty($kodeMapel)) {
            return redirect()->back()->withInput()->withErrors([
                'mata_pelajaran' => 'Mata pelajaran dan kode wajib diisi.',
            ]);
        }

        // Cegah duplikasi nama mata pelajaran pada lembaga yang sama
        $exists = MataPelajaran::where('lembaga_id', $targetLembagaId)
            ->where(function ($q) use ($namaMapel, $kodeMapel) {
                $q->whereRaw('LOWER(mata_pelajaran) = ?', [strtolower($namaMapel)])
                  ->orWhere('kode', $kodeMapel);
            })
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->withErrors([
                'mata_pelajaran' => 'Mata pelajaran atau kode tersebut sudah terdaftar pada lembaga ini.',
            ]);
        }

        $mapel = MataPelajaran::create([
            'lembaga_id'     => $targetLembagaId,
            'mata_pelajaran' => $namaMapel,
            'kode'           => $kodeMapel,
        ]);

        $lembaga = Lembaga::find($targetLembagaId);

        return redirect()->back()->with('success', 'Mata pelajaran "' . $mapel->mata_pelajaran . '" (' . $mapel->kode . ') berhasil ditambahkan ke ' . ($lembaga?->nama ?: 'Sekolah') . '.');
    }

    /**
     * Perbarui data plotting mata pelajaran (hanya nama mata pelajaran dan kode).
     */
    public function update(Request $request, MataPelajaran $mapel)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_mapel', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah mata pelajaran.');
        }

        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        });

        // Pastikan non-yayasan hanya bisa mengedit mapel dari lembaganya
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($mapel->lembaga->kode)) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah mata pelajaran lembaga ini.');
        }

        // Dukung input baik mata_pelajaran/nama_pelajaran dan kode/kode_mapel
        $namaMapel = trim((string)($request->input('mata_pelajaran') ?? $request->input('nama_pelajaran') ?? ''));
        $kodeMapel = trim(strtoupper((string)($request->input('kode') ?? $request->input('kode_mapel') ?? '')));

        if ($authUser->isYayasan() && $request->filled('lembaga_id')) {
            $request->validate(['lembaga_id' => 'required|exists:lembagas,id']);
            $targetLembagaId = (int) $request->input('lembaga_id');
        } else {
            $targetLembagaId = $mapel->lembaga_id;
        }

        // Pastikan target adalah sekolah
        if (!$sekolahLembagas->contains('id', $targetLembagaId)) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Plotting mata pelajaran hanya diperuntukkan bagi lembaga sekolah (SMP/SMA).']);
        }

        if (empty($namaMapel) || empty($kodeMapel)) {
            return redirect()->back()->withInput()->withErrors([
                'mata_pelajaran' => 'Mata pelajaran dan kode wajib diisi.',
            ]);
        }

        // Cek duplikasi selain mapel ini sendiri
        $duplicate = MataPelajaran::where('lembaga_id', $targetLembagaId)
            ->where('id', '!=', $mapel->id)
            ->where(function ($q) use ($namaMapel, $kodeMapel) {
                $q->whereRaw('LOWER(mata_pelajaran) = ?', [strtolower($namaMapel)])
                  ->orWhere('kode', $kodeMapel);
            })
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()->withErrors([
                'mata_pelajaran' => 'Mata pelajaran atau kode tersebut sudah digunakan pada lembaga ini.',
            ]);
        }

        $mapel->update([
            'lembaga_id'     => $targetLembagaId,
            'mata_pelajaran' => $namaMapel,
            'kode'           => $kodeMapel,
        ]);

        return redirect()->back()->with('success', 'Mata pelajaran "' . $mapel->mata_pelajaran . '" berhasil diperbarui.');
    }

    /**
     * Hapus mata pelajaran.
     */
    public function destroy(Request $request, MataPelajaran $mapel)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_mapel', 'delete')) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus mata pelajaran.');
        }

        // Pastikan non-yayasan hanya bisa menghapus mapel dari lembaganya
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($mapel->lembaga->kode)) {
            abort(403, 'Akses ditolak.');
        }

        $nama = $mapel->mata_pelajaran;
        $mapel->delete();

        return redirect()->back()->with('success', 'Mata pelajaran "' . $nama . '" berhasil dihapus.');
    }

    /**
     * Aktifkan / Nonaktifkan status mata pelajaran (kompatibilitas).
     */
    public function toggleStatus(Request $request, MataPelajaran $mapel)
    {
        return redirect()->back()->with('success', 'Status mata pelajaran "' . $mapel->mata_pelajaran . '" diperbarui.');
    }

    /**
     * Muat kurikulum standar default untuk lembaga sekolah.
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_mapel', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate(['lembaga_id' => 'required|exists:lembagas,id']);
        $lembaga = Lembaga::findOrFail($request->lembaga_id);

        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
            abort(403, 'Akses ditolak.');
        }

        $seeder = new \Database\Seeders\PlottingMapelSeeder();
        $seeder->run();

        return redirect()->back()->with('success', 'Daftar mata pelajaran standar berhasil dimuat untuk ' . $lembaga->nama . '.');
    }
}
