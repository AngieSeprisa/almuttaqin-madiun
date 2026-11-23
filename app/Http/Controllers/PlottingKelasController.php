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
        $kode = strtolower(trim($kodeLembaga));
        if ($kode === 'smp') {
            return ['VII A', 'VII B', 'VIII A', 'VIII B', 'IX A', 'IX B'];
        }
        if ($kode === 'sma') {
            return ['X A', 'X B', 'XI A', 'XI B', 'XII A', 'XII B'];
        }
        if (in_array($kode, ['sdit', 'sd'], true)) {
            return ['1 A', '2 A', '3 A', '4 A', '5 A', '6 A'];
        }
        if (in_array($kode, ['ra', 'tk'], true)) {
            return ['RA A', 'RA B'];
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
            'VIII' => '8', 'VII' => '7', 'XII' => '12',
            'XI' => '11', 'IX' => '9', 'X' => '10',
        ];

        $regex = '/(VIII|VII|XII|XI|IX|X)/i';
        $normA = preg_replace_callback($regex, fn($m) => $map[strtoupper($m[1])] ?? $m[1], $cleanA);
        $normB = preg_replace_callback($regex, fn($m) => $map[strtoupper($m[1])] ?? $m[1], $cleanB);

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
        if (preg_match('/^(XII|12)\b/i', $clean)) return 12;
        if (preg_match('/^(XI|11)\b/i', $clean)) return 11;
        if (preg_match('/^(X|10)\b/i', $clean)) return 10;
        if (preg_match('/^(IX|9)\b/i', $clean)) return 9;
        if (preg_match('/^(VIII|8)\b/i', $clean)) return 8;
        if (preg_match('/^(VII|7)\b/i', $clean)) return 7;
        if (preg_match('/^(VI|6)\b/i', $clean)) return 6;
        if (preg_match('/^(V|5)\b/i', $clean)) return 5;
        if (preg_match('/^(IV|4)\b/i', $clean)) return 4;
        if (preg_match('/^(III|3)\b/i', $clean)) return 3;
        if (preg_match('/^(II|2)\b/i', $clean)) return 2;
        if (preg_match('/^(I|1)\b/i', $clean)) return 1;
        if (preg_match('/^(RA|TK)\s*B\b/i', $clean)) return 2;
        if (preg_match('/^(RA|TK)\s*A\b/i', $clean)) return 1;

        $jUpper = strtoupper(trim($jenjang));
        if ($jUpper === 'SMA') return 10;
        if ($jUpper === 'SMP') return 7;
        return 1;
    }

    /**
     * Seed kelas standar otomatis ke dalam tabel classrooms
     */
    public static function seedStandar(Lembaga $lembaga): int
    {
        $kode = strtolower(trim((string) $lembaga->kode));
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
        } elseif (in_array($kode, ['sdit', 'sd'], true)) {
            $classes = [
                ['nama_kelas' => '1 A', 'tingkat' => 1, 'urutan' => 1],
                ['nama_kelas' => '2 A', 'tingkat' => 2, 'urutan' => 2],
                ['nama_kelas' => '3 A', 'tingkat' => 3, 'urutan' => 3],
                ['nama_kelas' => '4 A', 'tingkat' => 4, 'urutan' => 4],
                ['nama_kelas' => '5 A', 'tingkat' => 5, 'urutan' => 5],
                ['nama_kelas' => '6 A', 'tingkat' => 6, 'urutan' => 6],
            ];
        } elseif (in_array($kode, ['ra', 'tk'], true)) {
            $classes = [
                ['nama_kelas' => 'RA A', 'tingkat' => 1, 'urutan' => 1],
                ['nama_kelas' => 'RA B', 'tingkat' => 2, 'urutan' => 2],
            ];
        } else {
            return 0;
        }

        $jenjang = $lembaga->jenjang_key;

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
     * Pastikan ruang kelas termasuk dalam room yang boleh dikelola user.
     */
    protected function ensureClassroomInRoom(User $authUser, Classroom $classroom): void
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !$authUser->isYayasan()) {
            if ((int) $classroom->lembaga_id !== (int) $scopedFormal->id) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola ruang kelas lembaga lain.');
            }
            return;
        }

        if (!$authUser->isYayasan()) {
            $allowedIds = $authUser->accessibleLembagas()
                ->filter(fn (Lembaga $l) => $l->isFormalSchool())
                ->pluck('id')
                ->all();
            if (!in_array((int) $classroom->lembaga_id, $allowedIds, true)) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola ruang kelas lembaga lain.');
            }
        }
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
            abort(403, 'Menu Plotting Kelas hanya diperuntukkan bagi lembaga sekolah formal.');
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
            // Pengguna non-Yayasan terkunci pada room lembaga aktifnya
            $selectedLembaga = $scopedFormal
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());

            if (!$selectedLembaga || !$selectedLembaga->isFormalSchool()) {
                abort(403, 'Menu Plotting Kelas hanya dapat dibuka dari lembaga sekolah formal.');
            }
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = $selectedLembaga->jenjang_key;

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

        // Query seluruh siswa di jenjang sekolah ini (aktif, izin pulang, maupun sakit)
        // PENTING: Siswa dengan jenjang 'Reguler' TIDAK MASUK ke data sekolah
        $siswaQuery = Siswa::where(function ($q) {
                $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
                  ->orWhereNull('status');
            })
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->forLembaga($selectedLembaga->kode)
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
        ], [
            'nama_kelas.unique'   => 'Nama kelas tersebut sudah ada di lembaga ini.',
            'lembaga_id.required' => 'Pilih lembaga yang sesuai.',
        ]);

        $lembaga = Lembaga::findOrFail($validated['lembaga_id']);
        if (!$lembaga->isFormalSchool()) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga yang dipilih harus merupakan lembaga sekolah formal.']);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if (!$authUser->isYayasan()) {
            if (!$authUser->hasAccessToLembaga($lembaga->kode) || ($scopedFormal && (int) $scopedFormal->id !== (int) $lembaga->id)) {
                abort(403, 'Anda hanya dapat menambah ruang kelas untuk lembaga ruang kerja Anda.');
            }
        }

        $validated['jenjang'] = $lembaga->jenjang_key;
        $validated['is_active'] = true;
        $validated['urutan'] = $validated['urutan'] ?? ((Classroom::where('lembaga_id', $validated['lembaga_id'])->max('urutan') ?? 0) + 1);

        $classroom = Classroom::create($validated);

        $redirectUrl = route('plotting-kelas.index', [
            'lembaga' => strtolower($lembaga->kode),
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

        $this->ensureClassroomInRoom($authUser, $classroom);

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

        // Jika nama kelas berubah, sinkronkan data siswa di lembaga yang sama saja
        if ($oldName !== $validated['nama_kelas']) {
            $scopeKey = $classroom->lembaga?->kode ?? $classroom->jenjang;
            Siswa::forLembaga($scopeKey)->where('kelas', $oldName)->update(['kelas' => $validated['nama_kelas']]);
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

        $this->ensureClassroomInRoom($authUser, $classroom);

        // Cek keterkaitan siswa pada lembaga terkait
        $scopeKey = $classroom->lembaga?->kode ?? $classroom->jenjang;
        $siswaList = Siswa::forLembaga($scopeKey)->where(function ($q) {
            $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
              ->orWhereNull('status');
        })->get();
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

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $classroomsQuery = Classroom::with('lembaga')->whereIn('id', $ids);
        if ($scopedFormal && !$authUser->isYayasan()) {
            $classroomsQuery->where('lembaga_id', $scopedFormal->id);
        } elseif (!$authUser->isYayasan()) {
            $allowedIds = $authUser->accessibleLembagas()->filter(fn (Lembaga $l) => $l->isFormalSchool())->pluck('id')->all();
            $classroomsQuery->whereIn('lembaga_id', $allowedIds);
        }
        $classrooms = $classroomsQuery->get();

        $deletedCount = 0;
        $blockedNames = [];

        foreach ($classrooms as $classroom) {
            $scopeKey = $classroom->lembaga?->kode ?? $classroom->jenjang;
            $siswaList = Siswa::forLembaga($scopeKey)->where(function ($q) {
                $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
                  ->orWhereNull('status');
            })->get();
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

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !$authUser->isYayasan()) {
            $lembaga = $scopedFormal;
        } else {
            $targetLembagaId = $request->get('lembaga_id');
            $lembaga = $targetLembagaId ? Lembaga::find($targetLembagaId) : null;
            if (!$lembaga) {
                $reqKode = strtolower((string) $request->get('lembaga', session('active_lembaga', 'smp')));
                $lembaga = Lembaga::resolveByKode($reqKode) ?: Lembaga::where('kode', 'smp')->first();
            }
            if ($lembaga && !$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
                abort(403, 'Anda tidak memiliki akses ke lembaga tersebut.');
            }
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

        // Validasi siswa tidak berstatus reguler dan sesuai room aktif
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $mutasiQuery = Siswa::whereIn('id', $siswaIds)
            ->whereRaw('LOWER(jenjang) != ?', ['reguler']);
        if ($scopedFormal) {
            $mutasiQuery->forLembaga($scopedFormal->kode);
        }
        $updatedCount = $mutasiQuery->update(['kelas' => $targetKelas]);

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

        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $unassignQuery = Siswa::whereIn('id', $siswaIds)
            ->whereRaw('LOWER(jenjang) != ?', ['reguler']);
        if ($scopedFormal) {
            $unassignQuery->forLembaga($scopedFormal->kode);
        }
        $updatedCount = $unassignQuery->update(['kelas' => null]);

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
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $scopeKey = $scopedFormal ? $scopedFormal->kode : (string) $request->get('jenjang', 'SMP');

        $allSiswa = Siswa::where(function ($q) {
                $q->whereIn('status', ['aktif', 'pulang', 'sakit'])
                  ->orWhereNull('status');
            })
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->forLembaga($scopeKey)
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
                'status'       => $s->status ?? 'aktif',
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
