<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    /**
     * Pastikan ruang kelas berada di dalam ruang kerja lembaga yang diizinkan.
     */
    protected function ensureClassroomInRoom(User $authUser, Classroom $classroom): void
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !$authUser->isYayasan() && (int) $classroom->lembaga_id !== (int) $scopedFormal->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola ruang kelas lembaga lain.');
        }

        if (!$authUser->isYayasan()) {
            $allowedIds = $authUser->accessibleLembagas()->filter(fn (Lembaga $l) => $l->isFormalSchool())->pluck('id')->all();
            if (!in_array((int) $classroom->lembaga_id, $allowedIds, true)) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola ruang kelas lembaga lain.');
            }
        }
    }

    /**
     * Tampilkan daftar master ruang kelas
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Master Ruang Kelas.');
        }

        $isYayasan = $authUser->isYayasan();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);

        // Lembaga sekolah formal dinamis
        if ($isYayasan) {
            $sekolahLembagas = Lembaga::formalOptions();
        } elseif ($scopedFormal) {
            $sekolahLembagas = collect([$scopedFormal]);
        } else {
            $sekolahLembagas = $authUser->accessibleLembagas()
                ->filter(fn (Lembaga $l) => $l->isFormalSchool())
                ->values();
        }

        $query = Classroom::with('lembaga');
        $selectedJenjang = $request->get('jenjang', 'all');

        if (!$isYayasan) {
            $currentLembaga = $scopedFormal ?: $sekolahLembagas->first();
            if ($currentLembaga) {
                $query->where('lembaga_id', $currentLembaga->id);
                $selectedJenjang = $currentLembaga->jenjang_key;
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            if ($request->filled('lembaga_id') && $request->lembaga_id !== 'all') {
                $query->where('lembaga_id', (int) $request->lembaga_id);
            } elseif ($selectedJenjang && $selectedJenjang !== 'all') {
                $query->forJenjang($selectedJenjang);
            } elseif ($scopedFormal && !$request->has('jenjang') && !$request->has('lembaga_id')) {
                $query->where('lembaga_id', $scopedFormal->id);
                $selectedJenjang = $scopedFormal->jenjang_key;
            }
        }

        // Pencarian nama kelas
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_kelas', 'like', "%{$search}%")
                  ->orWhere('tingkat', 'like', "%{$search}%");
            });
        }

        $classrooms = $query->orderBy('jenjang')
                            ->orderBy('tingkat')
                            ->orderBy('urutan')
                            ->orderBy('nama_kelas')
                            ->get();

        // Statistik discope sesuai room aktif
        $statsBase = Classroom::query();
        if (!$isYayasan) {
            $statsBase->whereIn('lembaga_id', $sekolahLembagas->pluck('id'));
        } elseif ($scopedFormal && !$request->has('jenjang') && !$request->has('lembaga_id')) {
            $statsBase->where('lembaga_id', $scopedFormal->id);
        }

        $stats = [
            'total'      => (clone $statsBase)->count(),
            'aktif'      => (clone $statsBase)->where('is_active', true)->count(),
            'total_smp'  => (clone $statsBase)->forJenjang('SMP')->count(),
            'total_sma'  => (clone $statsBase)->forJenjang('SMA')->count(),
        ];

        $lembagaStats = [];
        foreach ($sekolahLembagas as $lemb) {
            $lembagaStats[] = [
                'kode'      => $lemb->kode,
                'singkatan' => $lemb->nama_singkat,
                'jenjang'   => $lemb->jenjang_key,
                'count'     => Classroom::where('lembaga_id', $lemb->id)->count(),
            ];
        }

        return view('master.kelas.index', compact('classrooms', 'sekolahLembagas', 'isYayasan', 'selectedJenjang', 'stats', 'lembagaStats', 'scopedFormal'));
    }

    /**
     * Simpan ruang kelas baru
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah ruang kelas.');
        }

        $validated = $request->validate([
            'lembaga_id' => 'required|exists:lembagas,id',
            'jenjang'    => 'nullable|string|max:50',
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where(function ($q) use ($request) {
                    return $q->where('lembaga_id', $request->lembaga_id);
                }),
            ],
            'tingkat'    => 'required|integer|min:1|max:12',
            'urutan'     => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ], [
            'nama_kelas.unique' => 'Nama kelas tersebut sudah ada di lembaga ini.',
            'lembaga_id.required' => 'Pilih lembaga yang sesuai.',
        ]);

        $lembaga = Lembaga::findOrFail($validated['lembaga_id']);
        if (!$lembaga->isFormalSchool()) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga harus merupakan lembaga sekolah formal.']);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if (!$authUser->isYayasan()) {
            if (!$authUser->hasAccessToLembaga($lembaga->kode) || ($scopedFormal && (int) $scopedFormal->id !== (int) $lembaga->id)) {
                abort(403, 'Anda hanya dapat menambah ruang kelas untuk lembaga Anda sendiri.');
            }
        }

        $validated['jenjang'] = $lembaga->jenjang_key;
        $validated['is_active'] = $request->has('is_active');
        $validated['urutan'] = $validated['urutan'] ?? (Classroom::where('lembaga_id', $validated['lembaga_id'])->max('urutan') + 1);

        Classroom::create($validated);

        return redirect()->route('master.kelas.index')->with('success', 'Ruang kelas ' . $validated['nama_kelas'] . ' berhasil ditambahkan.');
    }

    /**
     * Perbarui ruang kelas
     */
    public function update(Request $request, Classroom $kela)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah ruang kelas.');
        }

        $this->ensureClassroomInRoom($authUser, $kela);

        $validated = $request->validate([
            'lembaga_id' => 'required|exists:lembagas,id',
            'jenjang'    => 'nullable|string|max:50',
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where(function ($q) use ($request) {
                    return $q->where('lembaga_id', $request->lembaga_id);
                })->ignore($kela->id),
            ],
            'tingkat'    => 'required|integer|min:1|max:12',
            'urutan'     => 'nullable|integer',
            'is_active'  => 'nullable|boolean',
        ], [
            'nama_kelas.unique' => 'Nama kelas tersebut sudah terdaftar di lembaga ini.',
        ]);

        $lembaga = Lembaga::findOrFail($validated['lembaga_id']);
        if (!$lembaga->isFormalSchool()) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga harus merupakan lembaga sekolah formal.']);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if (!$authUser->isYayasan() && (!$authUser->hasAccessToLembaga($lembaga->kode) || ($scopedFormal && (int) $scopedFormal->id !== (int) $lembaga->id))) {
            abort(403, 'Anda tidak dapat memindahkan ruang kelas ke lembaga lain.');
        }

        $validated['jenjang'] = $lembaga->jenjang_key;
        $validated['is_active'] = $request->has('is_active');
        $kela->update($validated);

        return redirect()->route('master.kelas.index')->with('success', 'Ruang kelas ' . $kela->nama_kelas . ' berhasil diperbarui.');
    }

    /**
     * Toggle status aktif ruang kelas
     */
    public function toggleStatus(Classroom $kela)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'edit')) {
            abort(403, 'Anda tidak memiliki izin mengubah status ruang kelas.');
        }

        $this->ensureClassroomInRoom($authUser, $kela);

        $kela->update(['is_active' => !$kela->is_active]);

        $statusText = $kela->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Ruang kelas {$kela->nama_kelas} berhasil {$statusText}.");
    }

    /**
     * Hapus ruang kelas dengan validasi dependensi
     */
    public function destroy(Classroom $kela)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus ruang kelas.');
        }

        $this->ensureClassroomInRoom($authUser, $kela);

        // Cek keterkaitan jadwal pelajaran
        $scheduleCount = Schedule::where('classroom_id', $kela->id)->count();
        if ($scheduleCount > 0) {
            return redirect()->back()->with('error', "Ruang kelas {$kela->nama_kelas} tidak dapat dihapus karena masih digunakan dalam {$scheduleCount} jadwal pelajaran. Silakan hapus atau pindahkan jadwal terlebih dahulu.");
        }

        // Cek keterkaitan siswa pada lembaga terkait
        $scopeKey = $kela->lembaga?->kode ?? $kela->jenjang;
        $siswaCount = Siswa::forLembaga($scopeKey)->where(function ($q) {
            $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
              ->orWhereNull('status');
        })->get()->filter(function ($s) use ($kela) {
            return PlottingKelasController::isSameClass($s->kelas, $kela->nama_kelas);
        })->count();
        if ($siswaCount > 0) {
            return redirect()->back()->with('error', "Ruang kelas {$kela->nama_kelas} masih memiliki {$siswaCount} siswa terdaftar. Silakan mutasikan siswa terlebih dahulu pada menu Plotting Kelas.");
        }

        $nama = $kela->nama_kelas;
        $kela->delete();

        return redirect()->route('master.kelas.index')->with('success', "Ruang kelas {$nama} berhasil dihapus.");
    }

    /**
     * Muat kelas standar otomatis jika belum ada
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $targets = $scopedFormal
            ? collect([$scopedFormal])
            : ($authUser->isYayasan() ? Lembaga::formalOptions() : $authUser->accessibleLembagas()->filter(fn (Lembaga $l) => $l->isFormalSchool()));

        $added = 0;
        foreach ($targets as $lemb) {
            $added += PlottingKelasController::seedStandar($lemb);
        }

        return redirect()->route('master.kelas.index')->with('success', "Berhasil memeriksa dan menambahkan {$added} kelas standar.");
    }
}
