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

class PlottingKelasController extends Controller
{
    /**
     * Daftar kelas standar berdasarkan lembaga / jenjang (kompatibilitas backward)
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
     * Konversi format nama kelas angka ke Romawi untuk tampilan rapi (misal 7A -> VII A, 10A -> X A)
     */
    public static function toRomanDisplay(string $namaKelas): string
    {
        $map = [
            '/^7([A-Za-z0-9\s_-].*|$)/'   => 'VII$1',
            '/^8([A-Za-z0-9\s_-].*|$)/'   => 'VIII$1',
            '/^9([A-Za-z0-9\s_-].*|$)/'   => 'IX$1',
            '/^10([A-Za-z0-9\s_-].*|$)/'  => 'X$1',
            '/^11([A-Za-z0-9\s_-].*|$)/'  => 'XI$1',
            '/^12([A-Za-z0-9\s_-].*|$)/'  => 'XII$1',
        ];

        foreach ($map as $pattern => $replacement) {
            if (preg_match($pattern, $namaKelas)) {
                $formatted = preg_replace($pattern, $replacement, $namaKelas);
                return preg_replace('/^(VII|VIII|IX|X|XI|XII)([A-Za-z])$/', '$1 $2', $formatted);
            }
        }

        return $namaKelas;
    }

    /**
     * Helper fleksibel untuk mencocokkan nama kelas antar-format (angka vs romawi, dengan/tanpa spasi)
     */
    public static function isSameClass(?string $a, ?string $b): bool
    {
        if (!$a || !$b) {
            return false;
        }

        $cleanA = trim(str_ireplace('kelas', '', $a));
        $cleanB = trim(str_ireplace('kelas', '', $b));
        if (strcasecmp($cleanA, $cleanB) === 0) {
            return true;
        }

        $map = [
            'VII' => '7', 'VIII' => '8', 'IX' => '9',
            'X' => '10', 'XI' => '11', 'XII' => '12',
        ];

        $normA = preg_replace_callback('/\b(VII|VIII|IX|X|XI|XII)\b/i', fn($m) => $map[strtoupper($m[1])] ?? $m[1], $cleanA);
        $normB = preg_replace_callback('/\b(VII|VIII|IX|X|XI|XII)\b/i', fn($m) => $map[strtoupper($m[1])] ?? $m[1], $cleanB);

        $normA = str_replace([' ', '-', '_'], '', strtoupper($normA));
        $normB = str_replace([' ', '-', '_'], '', strtoupper($normB));

        return $normA === $normB;
    }

    /**
     * Tebak tingkat kelas dari string nama kelas
     */
    public static function inferTingkat(string $namaKelas, string $jenjang): int
    {
        $clean = strtoupper(trim(str_ireplace('kelas', '', $namaKelas)));
        if (preg_match('/^(VII|7)\b/i', $clean)) return 7;
        if (preg_match('/^(VIII|8)\b/i', $clean)) return 8;
        if (preg_match('/^(IX|9)\b/i', $clean)) return 9;
        if (preg_match('/^(X|10)\b/i', $clean)) return 10;
        if (preg_match('/^(XI|11)\b/i', $clean)) return 11;
        if (preg_match('/^(XII|12)\b/i', $clean)) return 12;

        return strtoupper($jenjang) === 'SMA' ? 10 : 7;
    }

    /**
     * Seed kelas standar otomatis ke dalam tabel classrooms
     */
    public static function seedStandar(Lembaga $lembaga): int
    {
        $kode = strtolower($lembaga->kode);
        $added = 0;

        if ($kode === 'smp') {
            $classes = [
                ['nama_kelas' => 'VII A', 'tingkat' => 7, 'urutan' => 1],
                ['nama_kelas' => 'VII B', 'tingkat' => 7, 'urutan' => 2],
                ['nama_kelas' => 'VIII A', 'tingkat' => 8, 'urutan' => 3],
                ['nama_kelas' => 'VIII B', 'tingkat' => 8, 'urutan' => 4],
                ['nama_kelas' => 'IX A', 'tingkat' => 9, 'urutan' => 5],
                ['nama_kelas' => 'IX B', 'tingkat' => 9, 'urutan' => 6],
            ];
        } elseif ($kode === 'sma') {
            $classes = [
                ['nama_kelas' => 'X A', 'tingkat' => 10, 'urutan' => 1],
                ['nama_kelas' => 'X B', 'tingkat' => 10, 'urutan' => 2],
                ['nama_kelas' => 'XI A', 'tingkat' => 11, 'urutan' => 3],
                ['nama_kelas' => 'XI B', 'tingkat' => 11, 'urutan' => 4],
                ['nama_kelas' => 'XII A', 'tingkat' => 12, 'urutan' => 5],
                ['nama_kelas' => 'XII B', 'tingkat' => 12, 'urutan' => 6],
            ];
        } else {
            return 0;
        }

        $jenjang = strtoupper($kode);

        foreach ($classes as $item) {
            $existing = Classroom::where('lembaga_id', $lembaga->id)->get()->first(function ($c) use ($item) {
                return PlottingKelasController::isSameClass($c->nama_kelas, $item['nama_kelas']);
            });

            if (!$existing) {
                Classroom::create([
                    'lembaga_id' => $lembaga->id,
                    'jenjang'    => $jenjang,
                    'nama_kelas' => $item['nama_kelas'],
                    'tingkat'    => $item['tingkat'],
                    'urutan'     => $item['urutan'],
                    'is_active'  => true,
                ]);
                $added++;
            }
        }

        return $added;
    }

    /**
     * Tampilkan halaman Plotting Kelas & Manajemen Ruang Kelas Terintegrasi
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Cek izin akses: yayasan atau yang memiliki izin plotting_kelas / master_kelas
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'view') && !$authUser->canDo('master_kelas', 'view')) {
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

        // Ambil daftar ruang kelas dari database Classroom
        $dbClassrooms = Classroom::where('lembaga_id', $selectedLembaga->id)
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->get();

        if ($dbClassrooms->isEmpty()) {
            self::seedStandar($selectedLembaga);
            $dbClassrooms = Classroom::where('lembaga_id', $selectedLembaga->id)
                ->orderBy('urutan')
                ->orderBy('nama_kelas')
                ->get();
        }

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

        // Jika ada siswa yang memiliki kelas belum terdaftar di DB, daftarkan otomatis
        foreach ($allSiswa as $s) {
            if (!empty($s->kelas) && $s->kelas !== '-') {
                $matched = $dbClassrooms->first(fn($c) => self::isSameClass($s->kelas, $c->nama_kelas));
                if (!$matched) {
                    $newC = Classroom::create([
                        'lembaga_id' => $selectedLembaga->id,
                        'jenjang'    => $jenjang,
                        'nama_kelas' => $s->kelas,
                        'tingkat'    => self::inferTingkat($s->kelas, $jenjang),
                        'urutan'     => ($dbClassrooms->max('urutan') ?? 0) + 1,
                        'is_active'  => true,
                    ]);
                    $dbClassrooms->push($newC);
                }
            }
        }

        // Susun data per kelas
        $dataKelas = [];
        $allDaftarKelas = [];

        foreach ($dbClassrooms as $c) {
            $anggota = $allSiswa->filter(function ($s) use ($c) {
                return PlottingKelasController::isSameClass($s->kelas, $c->nama_kelas);
            })->values();

            $displayName = self::toRomanDisplay($c->nama_kelas);
            $allDaftarKelas[] = $c->nama_kelas;

            $dataKelas[] = (object) [
                'id'                 => $c->id,
                'classroom'          => $c,
                'nama_kelas'         => $c->nama_kelas,
                'nama_kelas_display' => $displayName,
                'tingkat'            => $c->tingkat,
                'urutan'             => $c->urutan,
                'is_active'          => $c->is_active,
                'jumlah_siswa'       => $anggota->count(),
                'anggota'            => $anggota,
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
            'dbClassrooms',
            'allDaftarKelas',
            'dataKelas',
            'belumTerdaftar',
            'activeTab'
        ));
    }

    /**
     * Buat ruang kelas baru langsung dari Plotting Kelas
     */
    public function storeKelas(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'create') && !$authUser->canDo('master_kelas', 'create')) {
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
        ], [
            'nama_kelas.unique'   => 'Nama kelas tersebut sudah ada di lembaga ini.',
            'lembaga_id.required' => 'Pilih lembaga yang sesuai.',
        ]);

        $validated['is_active'] = true;
        $validated['urutan'] = $validated['urutan'] ?? ((Classroom::where('lembaga_id', $validated['lembaga_id'])->max('urutan') ?? 0) + 1);

        $classroom = Classroom::create($validated);

        $redirectUrl = route('plotting-kelas.index', [
            'lembaga' => strtolower($validated['jenjang']),
            'tab'     => 'kelas',
        ]);

        return redirect($redirectUrl)->with('success', "Ruang kelas {$classroom->nama_kelas} berhasil ditambahkan dan siap digunakan.");
    }

    /**
     * Perbarui data ruang kelas (Nama, Tingkat, Urutan)
     */
    public function updateKelas(Request $request, Classroom $classroom)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'edit') && !$authUser->canDo('master_kelas', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah ruang kelas.');
        }

        $validated = $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms')->where(function ($q) use ($classroom) {
                    return $q->where('lembaga_id', $classroom->lembaga_id);
                })->ignore($classroom->id),
            ],
            'tingkat' => 'required|integer|min:1|max:12',
            'urutan'  => 'nullable|integer',
        ], [
            'nama_kelas.unique' => 'Nama kelas tersebut sudah terdaftar di lembaga ini.',
        ]);

        $oldName = $classroom->nama_kelas;
        $classroom->update($validated);

        // Jika nama kelas berubah, sinkronkan data siswa yang ada di kelas ini
        if ($oldName !== $validated['nama_kelas']) {
            Siswa::where('kelas', $oldName)->update(['kelas' => $validated['nama_kelas']]);
        }

        return redirect()->back()->with('success', "Ruang kelas {$classroom->nama_kelas} berhasil diperbarui.");
    }

    /**
     * Hapus ruang kelas dengan proteksi siswa dan jadwal
     */
    public function destroyKelas(Classroom $classroom)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'delete') && !$authUser->canDo('master_kelas', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus ruang kelas.');
        }

        // Cek keterkaitan siswa
        $siswaList = Siswa::where('status', 'aktif')->get();
        $siswaCount = $siswaList->filter(function ($s) use ($classroom) {
            return self::isSameClass($s->kelas, $classroom->nama_kelas);
        })->count();

        if ($siswaCount > 0) {
            return redirect()->back()->with('error', "Ruang kelas {$classroom->nama_kelas} tidak dapat dihapus karena masih memiliki {$siswaCount} siswa terdaftar. Silakan mutasikan atau kosongkan siswa terlebih dahulu.");
        }

        // Cek keterkaitan jadwal pelajaran
        $schedCount = Schedule::where('classroom_id', $classroom->id)->count();
        if ($schedCount > 0) {
            return redirect()->back()->with('error', "Ruang kelas {$classroom->nama_kelas} tidak dapat dihapus karena masih digunakan dalam {$schedCount} jadwal pelajaran. Silakan hapus atau pindahkan jadwal terlebih dahulu.");
        }

        $nama = $classroom->nama_kelas;
        $classroom->delete();

        return redirect()->back()->with('success', "Ruang kelas {$nama} berhasil dihapus.");
    }

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
     * Hapus ruang kelas terpilih secara massal dengan proteksi siswa dan jadwal pelajaran.
     */
    public function destroyBulkKelas(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'delete') && !$authUser->canDo('master_kelas', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus ruang kelas.');
        }

        $request->validate([
            'kelas_ids' => 'required',
        ]);

        $ids = $this->parseIds($request->kelas_ids);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada ruang kelas yang dipilih untuk dihapus.');
        }

        $classrooms = Classroom::whereIn('id', $ids)->get();
        $siswaList = Siswa::where('status', 'aktif')->get();

        $deletedCount = 0;
        $blockedNames = [];

        foreach ($classrooms as $classroom) {
            $siswaCount = $siswaList->filter(fn ($s) => self::isSameClass($s->kelas, $classroom->nama_kelas))->count();
            $schedCount = Schedule::where('classroom_id', $classroom->id)->count();

            if ($siswaCount > 0 || $schedCount > 0) {
                $reason = $siswaCount > 0 ? "{$siswaCount} siswa" : "{$schedCount} jadwal";
                $blockedNames[] = "{$classroom->nama_kelas} ({$reason})";
                continue;
            }

            $classroom->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0 && empty($blockedNames)) {
            return redirect()->back()->with('success', "{$deletedCount} ruang kelas terpilih berhasil dihapus.");
        }

        if ($deletedCount > 0 && !empty($blockedNames)) {
            return redirect()->back()->with(
                'success',
                "{$deletedCount} ruang kelas kosong berhasil dihapus. Kelas berikut dilewati karena masih aktif digunakan: " . implode(', ', $blockedNames) . '.'
            );
        }

        return redirect()->back()->with(
            'error',
            'Ruang kelas terpilih tidak dapat dihapus karena masih memiliki siswa atau jadwal: ' . implode(', ', $blockedNames) . '.'
        );
    }

    /**
     * Muat kelas standar otomatis
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kelas', 'create') && !$authUser->canDo('master_kelas', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $targetLembagaId = $request->get('lembaga_id');
        $lembaga = null;
        if ($targetLembagaId) {
            $lembaga = Lembaga::find($targetLembagaId);
        }
        if (!$lembaga) {
            $reqKode = strtolower($request->get('lembaga', session('active_lembaga', 'smp')));
            $lembaga = Lembaga::where('kode', $reqKode)->first() ?: Lembaga::where('kode', 'smp')->first();
        }

        if (!$lembaga) {
            return redirect()->back()->with('error', 'Lembaga tidak ditemukan.');
        }

        $added = self::seedStandar($lembaga);

        return redirect()->back()->with('success', "Berhasil memeriksa dan menambahkan {$added} kelas standar untuk {$lembaga->nama}.");
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
        $siswaIds = $this->parseIds($request->siswa_id);
        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada siswa yang dipilih untuk dimutasi.');
        }

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

        $siswaIds = $this->parseIds($request->siswa_id);
        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

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

        $allSiswa = Siswa::where('status', 'aktif')
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->where('jenjang', $jenjang)
            ->with('pendaftaran')
            ->orderBy('nama_lengkap')
            ->get();

        $anggota = $allSiswa->filter(function ($s) use ($kelasDecoded) {
            return self::isSameClass($s->kelas, $kelasDecoded);
        })->values()->map(function ($s) {
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
