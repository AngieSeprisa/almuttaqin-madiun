<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerSiswa;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlottingEkskulController extends Controller
{
    /**
     * Normalisasi input ID (mendukung single ID, array, maupun string dipisah koma).
     */
    protected function parseIds(mixed $raw): array
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
     * Tampilkan halaman Plotting Ekstrakurikuler & Manajemen Ekskul (Filament v4 Style)
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_ekskul', 'view') && !$authUser->canDo('plotting_kelas', 'view') && !$authUser->canDo('sekolah', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Ekstrakurikuler.');
        }

        // Lembaga sekolah formal: SMP & SMA
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        if ($sekolahLembagas->isEmpty()) {
            abort(403, 'Menu Ekstrakurikuler hanya diperuntukkan bagi lembaga sekolah (SMP & SMA).');
        }

        $isYayasan = $authUser->isYayasan();
        $selectedLembaga = null;

        if ($isYayasan) {
            $reqKode = strtolower($request->get('lembaga', ''));
            if ($reqKode && in_array($reqKode, ['smp', 'sma'])) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', $reqKode);
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', 'smp') ?: $sekolahLembagas->first();
            }
        } else {
            $activeKode = session('active_lembaga');
            $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode)
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());

            if (!$selectedLembaga || !in_array(strtolower($selectedLembaga->kode), ['smp', 'sma'])) {
                abort(403, 'Menu Ekstrakurikuler hanya dapat dibuka dari lembaga sekolah (SMP atau SMA).');
            }
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        // Ambil daftar ekskul di jenjang/lembaga ini
        $ekskuls = Ekstrakurikuler::where(function ($q) use ($selectedLembaga, $jenjang) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', $jenjang)
                  ->orWhere('jenjang', 'ALL');
            })
            ->with(['pembina', 'anggota'])
            ->withCount('anggota')
            ->orderBy('nama_ekskul')
            ->get();

        // Query seluruh siswa aktif / sakit / pulang di jenjang sekolah ini
        $siswaQuery = Siswa::where(function ($q) {
                $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
                  ->orWhereNull('status');
            })
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->where('jenjang', $jenjang)
            ->with(['pendaftaran', 'ekstrakurikulers']);

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

        // Siswa yang belum mengikuti ekskul apapun
        $belumTerdaftar = $allSiswa->filter(function ($s) {
            return $s->ekstrakurikulers->isEmpty();
        })->values();

        // Daftar guru untuk pilihan Pembina Ekskul
        $gurus = User::where(function ($q) use ($selectedLembaga) {
                $q->where('primary_lembaga_id', $selectedLembaga->id)
                  ->orWhereHas('lembagas', fn($l) => $l->where('lembagas.id', $selectedLembaga->id))
                  ->orWhere('role', 'guru');
            })
            ->orderBy('name')
            ->get();

        // Tab aktif: 'ekskul' atau 'belum_terdaftar'
        $activeTab = $request->get('tab', 'ekskul');
        if (!in_array($activeTab, ['ekskul', 'belum_terdaftar'])) {
            $activeTab = 'ekskul';
        }

        return view('plotting-ekskul.index', compact(
            'isYayasan',
            'sekolahLembagas',
            'selectedLembaga',
            'kodeLembaga',
            'jenjang',
            'ekskuls',
            'allSiswa',
            'belumTerdaftar',
            'gurus',
            'activeTab'
        ));
    }

    /**
     * Tambah Ekstrakurikuler baru
     */
    public function storeEkskul(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_ekskul', 'create') && !$authUser->canDo('plotting_kelas', 'create') && !$authUser->canDo('sekolah', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah ekstrakurikuler.');
        }

        $validated = $request->validate([
            'lembaga_id'   => 'required|exists:lembagas,id',
            'jenjang'      => 'required|in:SMP,SMA,ALL',
            'nama_ekskul'  => [
                'required',
                'string',
                'max:100',
                Rule::unique('ekstrakurikulers')->where(function ($q) use ($request) {
                    return $q->where('lembaga_id', $request->lembaga_id)
                             ->where('jenjang', $request->jenjang);
                }),
            ],
            'kode'         => 'nullable|string|max:50',
            'pembina_id'   => 'nullable|exists:users,id',
            'nama_pembina' => 'nullable|string|max:100',
            'hari'         => 'nullable|string|max:20',
            'jam_mulai'    => 'nullable',
            'jam_selesai'  => 'nullable',
            'tempat'       => 'nullable|string|max:100',
            'deskripsi'    => 'nullable|string',
        ], [
            'nama_ekskul.required' => 'Nama ekstrakurikuler wajib diisi.',
            'nama_ekskul.unique'   => 'Ekstrakurikuler tersebut sudah terdaftar.',
        ]);

        if (!empty($validated['pembina_id']) && empty($validated['nama_pembina'])) {
            $p = User::find($validated['pembina_id']);
            $validated['nama_pembina'] = $p?->name;
        }

        $validated['is_active'] = true;
        $ekskul = Ekstrakurikuler::create($validated);

        return redirect()->route('plotting-ekskul.index', [
            'lembaga' => strtolower($validated['jenjang'] === 'SMA' ? 'sma' : 'smp'),
            'tab'     => 'ekskul',
        ])->with('success', "Ekstrakurikuler {$ekskul->nama_ekskul} berhasil ditambahkan.");
    }

    /**
     * Update Ekstrakurikuler
     */
    public function updateEkskul(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_ekskul', 'edit') && !$authUser->canDo('plotting_kelas', 'edit') && !$authUser->canDo('sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah ekstrakurikuler.');
        }

        $validated = $request->validate([
            'nama_ekskul'  => [
                'required',
                'string',
                'max:100',
                Rule::unique('ekstrakurikulers')->where(function ($q) use ($ekstrakurikuler) {
                    return $q->where('lembaga_id', $ekstrakurikuler->lembaga_id)
                             ->where('jenjang', $ekstrakurikuler->jenjang);
                })->ignore($ekstrakurikuler->id),
            ],
            'kode'         => 'nullable|string|max:50',
            'pembina_id'   => 'nullable|exists:users,id',
            'nama_pembina' => 'nullable|string|max:100',
            'hari'         => 'nullable|string|max:20',
            'jam_mulai'    => 'nullable',
            'jam_selesai'  => 'nullable',
            'tempat'       => 'nullable|string|max:100',
            'deskripsi'    => 'nullable|string',
        ]);

        if (!empty($validated['pembina_id']) && empty($validated['nama_pembina'])) {
            $p = User::find($validated['pembina_id']);
            $validated['nama_pembina'] = $p?->name;
        }

        $ekstrakurikuler->update($validated);

        return redirect()->back()->with('success', "Data ekstrakurikuler {$ekstrakurikuler->nama_ekskul} berhasil diperbarui.");
    }

    /**
     * Hapus Ekstrakurikuler
     */
    public function destroyEkskul(Ekstrakurikuler $ekstrakurikuler)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_ekskul', 'delete') && !$authUser->canDo('plotting_kelas', 'delete') && !$authUser->canDo('sekolah', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus ekstrakurikuler.');
        }

        $nama = $ekstrakurikuler->nama_ekskul;
        $ekstrakurikuler->delete();

        return redirect()->back()->with('success', "Ekstrakurikuler {$nama} berhasil dihapus.");
    }

    /**
     * Hapus massal ekstrakurikuler terpilih
     */
    public function destroyBulkEkskul(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_ekskul', 'delete') && !$authUser->canDo('plotting_kelas', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $ids = $this->parseIds($request->ekskul_ids);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada ekstrakurikuler yang dipilih.');
        }

        $deletedCount = Ekstrakurikuler::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "{$deletedCount} ekstrakurikuler terpilih berhasil dihapus.");
    }

    /**
     * Ambil data anggota ekskul (JSON) untuk modal detail
     */
    public function anggota(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $anggotaList = EkstrakurikulerSiswa::where('ekstrakurikuler_id', $ekstrakurikuler->id)
            ->with(['siswa.pendaftaran'])
            ->get()
            ->map(function ($item) {
                $s = $item->siswa;
                return [
                    'pivot_id'     => $item->id,
                    'siswa_id'     => $item->siswa_id,
                    'nama_lengkap' => $s?->nama_lengkap ?? '-',
                    'nisn'         => $s?->pendaftaran?->nisn ?? $s?->nisn ?? '-',
                    'kelas'        => $s?->kelas ?? '-',
                    'jenjang'      => $s?->jenjang ?? '-',
                    'status'       => $s?->status ?? 'aktif',
                    'peran'        => $item->peran ?? 'anggota',
                ];
            });

        return response()->json([
            'success'     => true,
            'ekskul_id'   => $ekstrakurikuler->id,
            'nama_ekskul' => $ekstrakurikuler->nama_ekskul,
            'pembina'     => $ekstrakurikuler->nama_pembina ?? '-',
            'total'       => $anggotaList->count(),
            'anggota'     => $anggotaList,
        ]);
    }

    /**
     * Tambahkan 1 atau banyak siswa ke dalam ekskul
     */
    public function tambahAnggota(Request $request)
    {
        $request->validate([
            'ekstrakurikuler_id' => 'required|exists:ekstrakurikulers,id',
            'siswa_id'           => 'required',
            'peran'              => 'nullable|string|max:50',
        ]);

        $ekskulId = (int) $request->ekstrakurikuler_id;
        $siswaIds = $this->parseIds($request->siswa_id);
        $peran = $request->get('peran', 'anggota') ?: 'anggota';

        if (empty($siswaIds)) {
            return back()->with('error', 'Pilih minimal satu siswa.');
        }

        $addedCount = 0;
        foreach ($siswaIds as $sid) {
            $exists = EkstrakurikulerSiswa::where('ekstrakurikuler_id', $ekskulId)
                ->where('siswa_id', $sid)
                ->exists();

            if (!$exists) {
                EkstrakurikulerSiswa::create([
                    'ekstrakurikuler_id' => $ekskulId,
                    'siswa_id'           => $sid,
                    'peran'              => $peran,
                    'tanggal_bergabung'  => now()->toDateString(),
                    'is_active'          => true,
                ]);
                $addedCount++;
            }
        }

        $ekskul = Ekstrakurikuler::find($ekskulId);
        $msg = "{$addedCount} siswa berhasil dimasukkan ke ekstrakurikuler " . ($ekskul?->nama_ekskul ?? '') . ".";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'added' => $addedCount]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Keluarkan siswa dari ekskul
     */
    public function hapusAnggota(Request $request)
    {
        $request->validate([
            'ekstrakurikuler_id' => 'required|exists:ekstrakurikulers,id',
            'siswa_id'           => 'required',
        ]);

        $ekskulId = (int) $request->ekstrakurikuler_id;
        $siswaIds = $this->parseIds($request->siswa_id);

        if (empty($siswaIds)) {
            return back()->with('error', 'Pilih minimal satu siswa.');
        }

        $deletedCount = EkstrakurikulerSiswa::where('ekstrakurikuler_id', $ekskulId)
            ->whereIn('siswa_id', $siswaIds)
            ->delete();

        $msg = "{$deletedCount} siswa berhasil dikeluarkan dari ekstrakurikuler.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg, 'deleted' => $deletedCount]);
        }

        return back()->with('success', $msg);
    }
}
