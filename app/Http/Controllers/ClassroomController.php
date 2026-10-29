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
     * Tampilkan daftar master ruang kelas
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('master_kelas', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Master Ruang Kelas.');
        }

        // Ambil lembaga sekolah (SMP & SMA)
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        $isYayasan = $authUser->isYayasan();

        $query = Classroom::with('lembaga');

        // Filter lembaga / jenjang
        $selectedJenjang = $request->get('jenjang', 'all');
        if ($selectedJenjang && $selectedJenjang !== 'all') {
            $query->where('jenjang', strtoupper($selectedJenjang));
        }

        if (!$isYayasan) {
            $activeKode = session('active_lembaga');
            $currentLembaga = $sekolahLembagas->firstWhere('kode', $activeKode) ?: $sekolahLembagas->first();
            if ($currentLembaga) {
                $query->where('lembaga_id', $currentLembaga->id);
            }
        } elseif ($request->filled('lembaga_id') && $request->lembaga_id !== 'all') {
            $query->where('lembaga_id', (int) $request->lembaga_id);
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

        // Statistik
        $stats = [
            'total'      => Classroom::count(),
            'aktif'      => Classroom::where('is_active', true)->count(),
            'total_smp'  => Classroom::where('jenjang', 'SMP')->count(),
            'total_sma'  => Classroom::where('jenjang', 'SMA')->count(),
        ];

        return view('master.kelas.index', compact('classrooms', 'sekolahLembagas', 'isYayasan', 'selectedJenjang', 'stats'));
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
            'jenjang'    => 'required|in:SMP,SMA',
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

        $validated = $request->validate([
            'lembaga_id' => 'required|exists:lembagas,id',
            'jenjang'    => 'required|in:SMP,SMA',
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

        // Cek keterkaitan jadwal pelajaran
        $scheduleCount = Schedule::where('classroom_id', $kela->id)->count();
        if ($scheduleCount > 0) {
            return redirect()->back()->with('error', "Ruang kelas {$kela->nama_kelas} tidak dapat dihapus karena masih digunakan dalam {$scheduleCount} jadwal pelajaran. Silakan hapus atau pindahkan jadwal terlebih dahulu.");
        }

        // Cek keterkaitan siswa
        $siswaCount = Siswa::where('status', 'aktif')->get()->filter(function ($s) use ($kela) {
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

        $smp = Lembaga::where('kode', 'smp')->first();
        $sma = Lembaga::where('kode', 'sma')->first();

        $added = 0;

        if ($smp) {
            $smpClasses = [
                ['nama_kelas' => '7A', 'tingkat' => 7, 'urutan' => 1],
                ['nama_kelas' => '7B', 'tingkat' => 7, 'urutan' => 2],
                ['nama_kelas' => '8A', 'tingkat' => 8, 'urutan' => 3],
                ['nama_kelas' => '8B', 'tingkat' => 8, 'urutan' => 4],
                ['nama_kelas' => '9A', 'tingkat' => 9, 'urutan' => 5],
                ['nama_kelas' => '9B', 'tingkat' => 9, 'urutan' => 6],
            ];
            foreach ($smpClasses as $item) {
                $c = Classroom::firstOrCreate(
                    ['lembaga_id' => $smp->id, 'nama_kelas' => $item['nama_kelas']],
                    [
                        'jenjang'   => 'SMP',
                        'tingkat'   => $item['tingkat'],
                        'urutan'    => $item['urutan'],
                        'is_active' => true,
                    ]
                );
                if ($c->wasRecentlyCreated) {
                    $added++;
                }
            }
        }

        if ($sma) {
            $smaClasses = [
                ['nama_kelas' => '10A', 'tingkat' => 10, 'urutan' => 1],
                ['nama_kelas' => '10B', 'tingkat' => 10, 'urutan' => 2],
                ['nama_kelas' => '11A', 'tingkat' => 11, 'urutan' => 3],
                ['nama_kelas' => '11B', 'tingkat' => 11, 'urutan' => 4],
                ['nama_kelas' => '12A', 'tingkat' => 12, 'urutan' => 5],
                ['nama_kelas' => '12B', 'tingkat' => 12, 'urutan' => 6],
            ];
            foreach ($smaClasses as $item) {
                $c = Classroom::firstOrCreate(
                    ['lembaga_id' => $sma->id, 'nama_kelas' => $item['nama_kelas']],
                    [
                        'jenjang'   => 'SMA',
                        'tingkat'   => $item['tingkat'],
                        'urutan'    => $item['urutan'],
                        'is_active' => true,
                    ]
                );
                if ($c->wasRecentlyCreated) {
                    $added++;
                }
            }
        }

        return redirect()->route('master.kelas.index')->with('success', "Berhasil memeriksa dan menambahkan {$added} kelas standar.");
    }
}
