<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
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
     * Pastikan ekstrakurikuler termasuk dalam room yang dapat diakses user.
     */
    protected function ensureEkskulInRoom(User $authUser, Ekstrakurikuler $ekskul): void
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !$authUser->isYayasan()) {
            $aliasesLower = array_map('strtolower', Lembaga::jenjangAliasesForRoom($scopedFormal->kode));
            $matches = (int) $ekskul->lembaga_id === (int) $scopedFormal->id
                || strcasecmp((string) $ekskul->jenjang, 'ALL') === 0
                || in_array(strtolower(trim((string) $ekskul->jenjang)), $aliasesLower, true);
            if (!$matches) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola ekstrakurikuler lembaga lain.');
            }
        }
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

        $isYayasan = $authUser->isYayasan();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);

        // Lembaga sekolah formal dinamis (SMP, SMA, SD IT, RA, dll)
        if ($isYayasan) {
            $sekolahLembagas = Lembaga::formalOptions();
        } elseif ($scopedFormal) {
            $sekolahLembagas = collect([$scopedFormal]);
        } else {
            $sekolahLembagas = $authUser->accessibleLembagas()
                ->filter(fn (Lembaga $l) => $l->isFormalSchool())
                ->values();
        }

        if ($sekolahLembagas->isEmpty()) {
            abort(403, 'Menu Ekstrakurikuler hanya diperuntukkan bagi lembaga sekolah formal.');
        }

        $selectedLembaga = null;

        if ($isYayasan) {
            $reqKode = trim((string) $request->get('lembaga', ''));
            if ($reqKode !== '') {
                $resolved = Lembaga::resolveByKode($reqKode);
                if ($resolved) {
                    $selectedLembaga = $sekolahLembagas->firstWhere('id', $resolved->id);
                }
            }
            if (!$selectedLembaga && $scopedFormal) {
                $selectedLembaga = $sekolahLembagas->firstWhere('id', $scopedFormal->id);
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', 'smp') ?: $sekolahLembagas->first();
            }
        } else {
            $selectedLembaga = $scopedFormal
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());

            if (!$selectedLembaga || !$selectedLembaga->isFormalSchool()) {
                abort(403, 'Menu Ekstrakurikuler hanya dapat dibuka dari lembaga sekolah formal.');
            }
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = $selectedLembaga->jenjang_key;
        $aliasesLower = array_map('strtolower', Lembaga::jenjangAliasesForRoom($selectedLembaga->kode));

        // Ambil daftar ekskul di jenjang/lembaga ini
        $ekskuls = Ekstrakurikuler::where(function ($q) use ($selectedLembaga, $aliasesLower) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', 'ALL');
                if (!empty($aliasesLower)) {
                    $q->orWhereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(jenjang))'), $aliasesLower);
                }
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
            ->forLembaga($selectedLembaga->kode)
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

        // Daftar guru resmi yang terdaftar di menu Guru Sekolah sesuai lembaga aktif
        $gurus = User::with(['primaryLembaga', 'lembagas'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->where(function ($q) {
                $q->where('is_guru_sekolah', true)
                  ->orWhere('mapel_status', 'disetujui');
            })
            ->where('mapel_status', '!=', 'ditolak')
            ->forLembagaModel($selectedLembaga)
            ->orderBy('name')
            ->get();

        // Fallback jika belum ada approval resmi
        if ($gurus->isEmpty()) {
            $gurus = User::forLembagaModel($selectedLembaga)
                ->where(function ($q) {
                    $q->where('role', 'guru')->orWhere('is_guru_sekolah', true);
                })
                ->orderBy('name')
                ->get();
        }

        // Tab aktif: 'ekskul' atau 'belum_terdaftar'
        $activeTab = $request->get('tab', 'ekskul');
        if (!in_array($activeTab, ['ekskul', 'belum_terdaftar'])) {
            $activeTab = 'ekskul';
        }

        // Ambil daftar ruang kelas persis seperti yang ada di menu Plotting Kelas
        $dbClassrooms = Classroom::where('lembaga_id', $selectedLembaga->id)
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->get();

        if ($dbClassrooms->isEmpty()) {
            PlottingKelasController::seedStandar($selectedLembaga);
            $dbClassrooms = Classroom::where('lembaga_id', $selectedLembaga->id)
                ->orderBy('urutan')
                ->orderBy('nama_kelas')
                ->get();
        }

        $classrooms = $dbClassrooms;
        $daftarKelas = $classrooms->pluck('nama_kelas')->all();

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
            'activeTab',
            'classrooms',
            'daftarKelas'
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
            'jenjang'      => 'nullable|string|max:50',
            'nama_ekskul'  => [
                'required',
                'string',
                'max:100',
                Rule::unique('ekstrakurikulers')->where(function ($q) use ($request) {
                    return $q->where('lembaga_id', $request->lembaga_id);
                }),
            ],
            'pembina_id'   => 'nullable|exists:users,id',
        ], [
            'nama_ekskul.required' => 'Nama ekstrakurikuler wajib diisi.',
            'nama_ekskul.unique'   => 'Ekstrakurikuler tersebut sudah terdaftar.',
        ]);

        $lembaga = Lembaga::findOrFail($validated['lembaga_id']);
        if (!$lembaga->isFormalSchool()) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga harus merupakan sekolah formal.']);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if (!$authUser->isYayasan()) {
            if (!$authUser->hasAccessToLembaga($lembaga->kode) || ($scopedFormal && (int) $scopedFormal->id !== (int) $lembaga->id)) {
                abort(403, 'Anda hanya dapat menambah ekstrakurikuler pada lembaga ruang kerja Anda.');
            }
        }

        $validated['jenjang'] = ($authUser->isYayasan() && strtoupper((string) ($validated['jenjang'] ?? '')) === 'ALL')
            ? 'ALL'
            : $lembaga->jenjang_key;

        if (!empty($validated['pembina_id'])) {
            $p = User::find($validated['pembina_id']);
            $validated['nama_pembina'] = $p?->name;
        } else {
            $validated['nama_pembina'] = null;
        }

        $validated['is_active'] = true;
        $ekskul = Ekstrakurikuler::create($validated);

        return redirect()->route('plotting-ekskul.index', [
            'lembaga' => strtolower($lembaga->kode),
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

        $this->ensureEkskulInRoom($authUser, $ekstrakurikuler);

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
            'pembina_id'   => 'nullable|exists:users,id',
        ], [
            'nama_ekskul.required' => 'Nama ekstrakurikuler wajib diisi.',
            'nama_ekskul.unique'   => 'Ekstrakurikuler tersebut sudah terdaftar.',
        ]);

        if (!empty($validated['pembina_id'])) {
            $p = User::find($validated['pembina_id']);
            $validated['nama_pembina'] = $p?->name;
        } else {
            $validated['nama_pembina'] = null;
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

        $this->ensureEkskulInRoom($authUser, $ekstrakurikuler);

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

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $deleteQuery = Ekstrakurikuler::whereIn('id', $ids);
        if ($scopedFormal && !$authUser->isYayasan()) {
            $deleteQuery->where('lembaga_id', $scopedFormal->id);
        }
        $deletedCount = $deleteQuery->delete();

        return redirect()->back()->with('success', "{$deletedCount} ekstrakurikuler terpilih berhasil dihapus.");
    }

    /**
     * Ambil data anggota ekskul (JSON) untuk modal detail
     */
    public function anggota(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser) {
            $this->ensureEkskulInRoom($authUser, $ekstrakurikuler);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $anggotaQuery = EkstrakurikulerSiswa::where('ekstrakurikuler_id', $ekstrakurikuler->id)
            ->with(['siswa.pendaftaran']);
        if ($scopedFormal) {
            $anggotaQuery->whereHas('siswa', fn ($q) => $q->forLembaga($scopedFormal->kode));
        }

        $anggotaList = $anggotaQuery->get()
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

        $ekskul = Ekstrakurikuler::findOrFail((int) $request->ekstrakurikuler_id);
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser) {
            $this->ensureEkskulInRoom($authUser, $ekskul);
        }

        $ekskulId = $ekskul->id;
        $siswaIds = $this->parseIds($request->siswa_id);
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal) {
            $siswaIds = Siswa::forLembaga($scopedFormal->kode)->whereIn('id', $siswaIds)->pluck('id')->all();
        }
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

        $msg = "{$addedCount} siswa berhasil dimasukkan ke ekstrakurikuler " . ($ekskul->nama_ekskul ?? '') . ".";

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

        $ekskul = Ekstrakurikuler::findOrFail((int) $request->ekstrakurikuler_id);
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser) {
            $this->ensureEkskulInRoom($authUser, $ekskul);
        }

        $ekskulId = $ekskul->id;
        $siswaIds = $this->parseIds($request->siswa_id);
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal) {
            $siswaIds = Siswa::forLembaga($scopedFormal->kode)->whereIn('id', $siswaIds)->pluck('id')->all();
        }

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

