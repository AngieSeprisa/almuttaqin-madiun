<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Jurnal;
use App\Models\JurnalAbsensi;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SekolahController extends Controller
{
    /**
     * Dapatkan semua variasi penulisan nama kelas yang ekuivalen (Romawi, Angka, dengan/tanpa spasi, atau prefix 'Kelas')
     */
    public static function getEquivalentClassNames(?string $namaKelas): array
    {
        if (!$namaKelas) {
            return [];
        }

        $raw = trim($namaKelas);
        $clean = trim(str_ireplace('kelas', '', $raw));

        $variants = [
            $raw,
            $clean,
            'Kelas ' . $clean,
            str_replace(' ', '', $clean),
        ];

        // Format Romawi
        $roman = PlottingKelasController::toRomanDisplay($clean);
        $cleanRoman = trim(str_ireplace('kelas', '', $roman));
        $variants[] = $roman;
        $variants[] = $cleanRoman;
        $variants[] = 'Kelas ' . $cleanRoman;
        $variants[] = str_replace(' ', '', $cleanRoman);

        // Format Angka Arab (7, 8, 9, 10, 11, 12)
        $map = [
            'VII' => '7', 'VIII' => '8', 'IX' => '9',
            'X' => '10', 'XI' => '11', 'XII' => '12',
        ];
        $arabic = preg_replace_callback('/\b(VII|VIII|IX|X|XI|XII)\b/i', fn($m) => $map[strtoupper($m[1])] ?? $m[1], $cleanRoman);
        $cleanArabic = trim(str_ireplace('kelas', '', $arabic));
        $variants[] = $arabic;
        $variants[] = $cleanArabic;
        $variants[] = 'Kelas ' . $cleanArabic;
        $variants[] = str_replace(' ', '', $cleanArabic);

        // Cek juga dari data yang tersimpan nyata di database siswas & jurnals
        try {
            $dbClasses = DB::table('siswas')
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->distinct()
                ->pluck('kelas');

            foreach ($dbClasses as $dbc) {
                if (PlottingKelasController::isSameClass($dbc, $namaKelas)) {
                    $variants[] = $dbc;
                }
            }

            $jurnalClasses = DB::table('jurnals')
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->distinct()
                ->pluck('kelas');

            foreach ($jurnalClasses as $jc) {
                if (PlottingKelasController::isSameClass($jc, $namaKelas)) {
                    $variants[] = $jc;
                }
            }
        } catch (\Throwable $e) {
        }

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * Daftar kelas berdasarkan jenjang (sinkron dengan tabel classrooms & siswas yang aktif, fallback kelas standar)
     */
    public static function daftarKelas(?string $activeRoom = null): array
    {
        $room = strtolower($activeRoom ?? session('active_lembaga', ''));
        $classes = [];

        // 1. Ambil dari master ruang kelas (Classroom)
        try {
            $query = Classroom::query()->where('is_active', true);
            if ($room === 'smp') {
                $query->where('jenjang', 'SMP');
            } elseif ($room === 'sma') {
                $query->where('jenjang', 'SMA');
            }
            $classes = $query->orderBy('urutan')->orderBy('nama_kelas')->pluck('nama_kelas')->toArray();
        } catch (\Throwable $e) {
            $classes = [];
        }

        // 2. Ambil juga dari kelas yang sudah ditempati siswa aktif non-reguler
        try {
            $siswaQuery = Siswa::where('status', 'aktif')
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->where('kelas', '!=', '-')
                ->whereRaw('LOWER(jenjang) != ?', ['reguler']);

            if ($room === 'smp') {
                $siswaQuery->where('jenjang', 'SMP');
            } elseif ($room === 'sma') {
                $siswaQuery->where('jenjang', 'SMA');
            }
            $siswaClasses = $siswaQuery->distinct()->pluck('kelas')->toArray();
            foreach ($siswaClasses as $sc) {
                $exists = false;
                foreach ($classes as $c) {
                    if (PlottingKelasController::isSameClass($c, $sc)) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $classes[] = $sc;
                }
            }
        } catch (\Throwable $e) {
        }

        // 3. Fallback jika kosong atau lengkapi kelas standar
        $smpStandar = ['VII A', 'VII B', 'VIII A', 'VIII B', 'IX A', 'IX B'];
        $smaStandar = ['X A', 'X B', 'XI A', 'XI B', 'XII A', 'XII B'];

        if (empty($classes)) {
            if ($room === 'smp') {
                return $smpStandar;
            }
            if ($room === 'sma') {
                return $smaStandar;
            }
            return array_merge($smpStandar, $smaStandar);
        }

        $standardPool = ($room === 'smp') ? $smpStandar : (($room === 'sma') ? $smaStandar : array_merge($smpStandar, $smaStandar));
        foreach ($standardPool as $std) {
            $matched = false;
            foreach ($classes as $c) {
                if (PlottingKelasController::isSameClass($c, $std)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $classes[] = $std;
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Hitung semester & tahun pelajaran otomatis berdasarkan tanggal
     */
    public static function hitungPeriodeAkademik(?string $tanggal = null): array
    {
        $date = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $month = (int) $date->format('n');
        $year = (int) $date->format('Y');

        $hariList = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        $hari = $hariList[$date->format('l')] ?? $date->translatedFormat('l');

        if ($month >= 7) {
            $semester = 'Semester Ganjil';
            $tahunPelajaran = $year . '/' . ($year + 1);
        } else {
            $semester = 'Semester Genap';
            $tahunPelajaran = ($year - 1) . '/' . $year;
        }

        return [
            'hari'            => $hari,
            'tanggal'         => $date->format('Y-m-d'),
            'semester'        => $semester,
            'tahun_pelajaran' => $tahunPelajaran,
        ];
    }

    /**
     * Submenu 1: Form Input Jurnal Pembelajaran
     */
    public function jurnal(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $periode = self::hitungPeriodeAkademik();
        $kelasList = self::daftarKelas($activeRoom);

        // Ambil daftar guru sesuai lembaga aktif
        $guruQuery = User::where('is_active', true)
            ->where(function ($q) {
                $q->where('role', 'guru')
                  ->orWhere('role', 'admin_lembaga')
                  ->orWhereNotNull('mata_pelajaran');
            });

        if ($activeRoom && !in_array($activeRoom, ['yayasan', 'pondok'])) {
            $guruQuery->where(function ($q) use ($activeRoom) {
                $q->whereHas('lembagas', function ($l) use ($activeRoom) {
                    $l->where('kode', $activeRoom);
                })->orWhereHas('primaryLembaga', function ($l) use ($activeRoom) {
                    $l->where('kode', $activeRoom);
                });
            });
        }

        $gurus = $guruQuery->orderBy('name')->get();

        // Ambil seluruh siswa aktif non-reguler yang memiliki kelas dan kelompokkan per kelas
        $siswas = Siswa::where('status', 'aktif')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', '-')
            ->whereRaw('LOWER(jenjang) != ?', ['reguler'])
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'kelas', 'jenjang']);

        $siswasByKelas = [];
        foreach ($kelasList as $k) {
            $matched = $siswas->filter(function ($s) use ($k) {
                return PlottingKelasController::isSameClass($s->kelas, $k);
            })->values();

            // Store by original list key
            $siswasByKelas[$k] = $matched;

            // Store by all possible aliases
            $cleanK = trim(str_ireplace('kelas', '', $k));
            $siswasByKelas[$cleanK] = $matched;

            $roman = PlottingKelasController::toRomanDisplay($cleanK);
            $siswasByKelas[$roman] = $matched;
            $siswasByKelas['Kelas ' . $roman] = $matched;
            $siswasByKelas[str_replace(' ', '', $roman)] = $matched;

            $mapArab = [
                'VII' => '7', 'VIII' => '8', 'IX' => '9',
                'X' => '10', 'XI' => '11', 'XII' => '12',
            ];
            $arabic = preg_replace_callback('/\b(VII|VIII|IX|X|XI|XII)\b/i', fn($m) => $mapArab[strtoupper($m[1])] ?? $m[1], $roman);
            $cleanArabic = trim(str_ireplace('kelas', '', $arabic));
            $siswasByKelas[$cleanArabic] = $matched;
            $siswasByKelas['Kelas ' . $cleanArabic] = $matched;
            $siswasByKelas[str_replace(' ', '', $cleanArabic)] = $matched;
        }

        // Tambahkan juga key langsung dari data kelas siswa yang ada
        foreach ($siswas as $s) {
            if (!empty($s->kelas) && !isset($siswasByKelas[$s->kelas])) {
                $siswasByKelas[$s->kelas] = $siswas->filter(function ($other) use ($s) {
                    return PlottingKelasController::isSameClass($other->kelas, $s->kelas);
                })->values();
            }
        }

        return view('sekolah.jurnal', compact(
            'periode',
            'kelasList',
            'gurus',
            'siswasByKelas',
            'activeRoom'
        ));
    }

    /**
     * Simpan data Jurnal Pembelajaran & Detail Absensi Siswa
     */
    public function storeJurnal(Request $request)
    {
        $validated = $request->validate([
            'kelas'           => 'required|string|max:50',
            'semester'        => 'required|string|max:50',
            'tahun_pelajaran' => 'required|string|max:50',
            'hari'            => 'required|string|max:50',
            'tanggal'         => 'required|date',
            'guru_id'         => 'required|exists:users,id',
            'mata_pelajaran'  => 'required|string|max:100',
            'jam_ke'          => 'required|string|max:50',
            'materi'          => 'required|string',
            'catatan'         => 'nullable|string',
            'absensi'         => 'required|array',
            'absensi.*.status' => 'required|in:hadir,sakit,izin,alpa',
        ], [
            'kelas.required'          => 'Silakan pilih kelas terlebih dahulu.',
            'guru_id.required'       => 'Silakan pilih guru pengajar.',
            'mata_pelajaran.required' => 'Mata pelajaran wajib diisi.',
            'jam_ke.required'         => 'Jam pelajaran wajib diisi.',
            'materi.required'         => 'Materi pembelajaran wajib diisi.',
            'absensi.required'        => 'Data absensi siswa wajib tersedia.',
        ]);

        $guru = User::findOrFail($validated['guru_id']);
        $activeRoom = session('active_lembaga');

        // Tentukan lembaga untuk jurnal
        $lembagaId = null;
        if ($activeRoom && $activeRoom !== 'yayasan') {
            $lembaga = Lembaga::where('kode', $activeRoom)->first();
            $lembagaId = $lembaga?->id;
        }
        if (!$lembagaId) {
            $lembagaId = $guru->primary_lembaga_id ?? $guru->lembagas()->first()?->id;
        }

        // Hitung rekap absensi
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin  = 0;
        $totalAlpa  = 0;
        $absensiData = $request->input('absensi', []);

        foreach ($absensiData as $item) {
            $st = $item['status'] ?? 'hadir';
            match ($st) {
                'hadir' => $totalHadir++,
                'sakit' => $totalSakit++,
                'izin'  => $totalIzin++,
                'alpa'  => $totalAlpa++,
                default => $totalHadir++,
            };
        }

        $totalSiswa = count($absensiData);

        DB::beginTransaction();
        try {
            $jurnal = Jurnal::create([
                'lembaga_id'      => $lembagaId,
                'guru_id'         => $guru->id,
                'nama_guru'       => $guru->name,
                'kelas'           => $validated['kelas'],
                'semester'        => $validated['semester'],
                'tahun_pelajaran' => $validated['tahun_pelajaran'],
                'hari'            => $validated['hari'],
                'tanggal'         => $validated['tanggal'],
                'mata_pelajaran'  => $validated['mata_pelajaran'],
                'jam_ke'          => $validated['jam_ke'],
                'materi'          => $validated['materi'],
                'catatan'         => $validated['catatan'] ?? null,
                'total_siswa'     => $totalSiswa,
                'total_hadir'     => $totalHadir,
                'total_sakit'     => $totalSakit,
                'total_izin'      => $totalIzin,
                'total_alpa'      => $totalAlpa,
            ]);

            foreach ($absensiData as $siswaId => $item) {
                $nama = $item['nama'] ?? '';
                if (!$nama) {
                    $siswa = Siswa::find($siswaId);
                    $nama = $siswa ? $siswa->nama_lengkap : 'Siswa #' . $siswaId;
                }

                JurnalAbsensi::create([
                    'jurnal_id'  => $jurnal->id,
                    'siswa_id'   => is_numeric($siswaId) ? $siswaId : null,
                    'nama_siswa' => $nama,
                    'status'     => $item['status'] ?? 'hadir',
                    'keterangan' => $item['keterangan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('sekolah.rekap-jurnal')
                ->with('success', 'Jurnal pembelajaran kelas ' . $jurnal->kelas . ' berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan jurnal: ' . $e->getMessage());
        }
    }

    /**
     * Submenu 2: Rekap Jurnal Pembelajaran
     */
    public function rekapJurnal(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $kelasList = self::daftarKelas($activeRoom);

        $query = Jurnal::with(['guru', 'lembaga', 'absensis.siswa']);

        // Filter lembaga
        if ($activeRoom && !in_array($activeRoom, ['yayasan', 'pondok'])) {
            $query->whereHas('lembaga', function ($q) use ($activeRoom) {
                $q->where('kode', $activeRoom);
            });
        }

        // Filter Kelas (fleksibel mendukung varian Romawi, Angka, dan spasi)
        if ($request->filled('kelas')) {
            $matchingKelas = self::getEquivalentClassNames($request->kelas);
            $query->whereIn('kelas', $matchingKelas);
        }

        // Filter Tanggal
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        // Filter Semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Pencarian (Materi, Guru, Mata Pelajaran)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('materi', 'like', "%{$search}%")
                  ->orWhere('nama_guru', 'like', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%");
            });
        }

        $jurnals = $query->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        return view('sekolah.rekap_jurnal', compact(
            'jurnals',
            'kelasList',
            'activeRoom'
        ));
    }

    /**
     * Hapus data Jurnal
     */
    public function destroyJurnal(Jurnal $jurnal)
    {
        $kelas = $jurnal->kelas;
        $jurnal->delete();

        return redirect()->route('sekolah.rekap-jurnal')
            ->with('success', 'Data jurnal kelas ' . $kelas . ' berhasil dihapus.');
    }

    /**
     * Helper untuk memformat kode singkat mata pelajaran
     */
    public static function formatKodeMapel(?string $mataPelajaran): string
    {
        if (!$mataPelajaran) {
            return '-';
        }

        $nama = trim($mataPelajaran);

        $customMap = [
            'Matematika'                                      => 'MTK',
            'MTK'                                             => 'MTK',
            'Bahasa Indonesia'                                => 'BIN',
            'BIN'                                             => 'BIN',
            'Bahasa Inggris'                                  => 'BIG',
            'BIG'                                             => 'BIG',
            'Ilmu Pengetahuan Alam'                           => 'IPA',
            'Ilmu Pengetahuan Alam (IPA)'                     => 'IPA',
            'IPA'                                             => 'IPA',
            'Ilmu Pengetahuan Sosial'                         => 'IPS',
            'Ilmu Pengetahuan Sosial (IPS)'                   => 'IPS',
            'IPS'                                             => 'IPS',
            'Pendidikan Pancasila'                            => 'PP',
            'Pendidikan Pancasila & Kewarganegaraan'          => 'PP',
            'Pendidikan Pancasila & Kewarganegaraan (PPKn)'   => 'PP',
            'Pendidikan Pancasila dan Kewarganegaraan'        => 'PP',
            'PPKN'                                            => 'PP',
            'PP'                                              => 'PP',
            'Seni Budaya'                                     => 'SBDP',
            'Seni Budaya dan Prakarya'                        => 'SBDP',
            'SBDP'                                            => 'SBDP',
            'Informatika'                                     => 'TIK',
            'Teknologi Informasi & Komunikasi'                => 'TIK',
            'TIK'                                             => 'TIK',
            'Pendidikan Agama Islam'                          => 'PAI',
            'Pendidikan Agama Islam (PAI)'                    => 'PAI',
            'PAI'                                             => 'PAI',
            'Pendidikan Jasmani'                              => 'PJOK',
            'Pendidikan Jasmani, Olahraga & Kesehatan'        => 'PJOK',
            'Pendidikan Jasmani, Olahraga & Kesehatan (PJOK)' => 'PJOK',
            'PJOK'                                            => 'PJOK',
            'Bahasa Arab'                                     => 'ARB',
            'ARB'                                             => 'ARB',
            'Fisika'                                          => 'FIS',
            'Kimia'                                           => 'KIM',
            'Biologi'                                         => 'BIO',
            'Ekonomi'                                         => 'EKO',
            'Ekonomi & Akuntansi'                             => 'EKO',
            'Sosiologi'                                       => 'SOS',
            'Sejarah'                                         => 'SEJ',
            'Sejarah Indonesia'                               => 'SEJ',
            'Asad'                                            => 'ASAD',
            'ASAD'                                            => 'ASAD',
        ];

        if (isset($customMap[$nama])) {
            return $customMap[$nama];
        }

        // Cek jika nama ada di tabel mata_pelajarans
        $mp = MataPelajaran::where('mata_pelajaran', $nama)->first();
        if ($mp && !empty($mp->kode)) {
            $kode = strtoupper($mp->kode);
            if (str_contains($kode, '-')) {
                $parts = explode('-', $kode);
                $clean = end($parts);
                if (isset($customMap[$clean])) return $customMap[$clean];
                if ($clean === 'IND') return 'BIN';
                if ($clean === 'ING') return 'BIG';
                if ($clean === 'PKN') return 'PP';
                if ($clean === 'SNB') return 'SBDP';
                if ($clean === 'INF') return 'TIK';
                return $clean;
            }
            return $kode;
        }

        // Cek jika terdapat singkatan dalam tanda kurung, misal (IPA) -> IPA
        if (preg_match('/\(([^)]+)\)/', $nama, $m)) {
            $val = strtoupper($m[1]);
            if ($val === 'PPKN') return 'PP';
            return $val;
        }

        // Jika string pendek
        if (strlen($nama) <= 4) {
            return strtoupper($nama);
        }

        // Inisial dari kata-kata
        $words = preg_split('/\s+/', $nama);
        if (count($words) >= 2) {
            $initials = '';
            foreach ($words as $w) {
                $initials .= substr($w, 0, 1);
            }
            return strtoupper(substr($initials, 0, 4));
        }

        return strtoupper(substr($nama, 0, 3));
    }

    /**
     * Submenu: Rekap Absensi Siswa Sekolah
     */
    public function rekapAbsensi(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $kelasList = self::daftarKelas($activeRoom);

        // Pilih kelas default jika belum ada filter
        $selectedKelas = $request->get('kelas', $kelasList[0] ?? null);

        // Filter bulan & tahun
        $bulan = $request->has('bulan')
            ? ($request->filled('bulan') ? (int) $request->bulan : null)
            : (int) date('n');

        $tahun = $request->filled('tahun') ? (int) $request->tahun : (int) date('Y');

        // Query Jurnal untuk kelas terpilih (fleksibel mendukung varian Romawi, Angka, dll)
        $jurnalQuery = Jurnal::with(['guru', 'lembaga']);
        if ($selectedKelas) {
            $matchingKelas = self::getEquivalentClassNames($selectedKelas);
            $jurnalQuery->whereIn('kelas', $matchingKelas);
        }
        if ($bulan) {
            $jurnalQuery->whereMonth('tanggal', $bulan);
        }
        if ($tahun) {
            $jurnalQuery->whereYear('tanggal', $tahun);
        }

        $jurnals = $jurnalQuery->orderBy('tanggal', 'asc')
            ->orderBy('jam_ke', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalPertemuan = $jurnals->count();
        $jurnalIds = $jurnals->pluck('id');

        // Format kode mapel dan tanggal untuk tiap jurnal
        foreach ($jurnals as $jurnal) {
            $jurnal->kode_mapel = self::formatKodeMapel($jurnal->mata_pelajaran);
            $jurnal->tgl_nomor  = Carbon::parse($jurnal->tanggal)->format('d');
            $jurnal->tgl_full   = Carbon::parse($jurnal->tanggal)->translatedFormat('d M Y');
        }

        // Kelompokkan tanggal untuk header bertingkat
        $tanggalGroups = [];
        foreach ($jurnals as $jurnal) {
            $tgl = $jurnal->tgl_nomor;
            if (!isset($tanggalGroups[$tgl])) {
                $tanggalGroups[$tgl] = [
                    'tanggal' => $tgl,
                    'count'   => 0,
                    'jurnals' => [],
                ];
            }
            $tanggalGroups[$tgl]['count']++;
            $tanggalGroups[$tgl]['jurnals'][] = $jurnal;
        }

        // Ambil data siswa di kelas terpilih (hanya siswa aktif non-reguler)
        $siswaQuery = Siswa::with('pendaftaran')->where('status', 'aktif')
            ->whereRaw('LOWER(jenjang) != ?', ['reguler']);
        if ($selectedKelas) {
            $matchingKelas = self::getEquivalentClassNames($selectedKelas);
            $siswaQuery->whereIn('kelas', $matchingKelas);
        }

        if ($request->filled('search')) {
            $siswaQuery->where('nama_lengkap', 'like', '%' . $request->search . '%');
        }

        $siswas = $siswaQuery->orderBy('nama_lengkap')->get();

        // Ambil data absensi siswa untuk seluruh jurnal yang terpilih
        $allAbsensis = JurnalAbsensi::whereIn('jurnal_id', $jurnalIds)->get();

        $absensiMatrix = [];
        foreach ($allAbsensis as $abs) {
            $absensiMatrix[$abs->siswa_id][$abs->jurnal_id] = [
                'status'     => strtolower($abs->status),
                'keterangan' => $abs->keterangan ?? '',
            ];
        }

        // Susun rekap per siswa
        $rekapSiswa = [];
        $totalSemuaHadir = 0;
        $totalSemuaSakit = 0;
        $totalSemuaIzin  = 0;
        $totalSemuaAlpa  = 0;

        foreach ($siswas as $siswa) {
            $hadir     = 0;
            $sakit     = 0;
            $izin      = 0;
            $alpa      = 0;
            $terlambat = 0;

            $pertemuanAbsensi = [];
            $riwayatDetail = [];

            foreach ($jurnals as $jurnal) {
                $record = $absensiMatrix[$siswa->id][$jurnal->id] ?? null;
                $status = $record['status'] ?? null;
                $ket    = $record['keterangan'] ?? '';

                if ($status === 'hadir') {
                    $hadir++;
                } elseif ($status === 'sakit') {
                    $sakit++;
                } elseif ($status === 'izin') {
                    $izin++;
                } elseif ($status === 'alpa') {
                    $alpa++;
                }

                $pertemuanAbsensi[$jurnal->id] = $status;

                $riwayatDetail[] = [
                    'jurnal_id'      => $jurnal->id,
                    'tanggal'        => $jurnal->tgl_full,
                    'tanggal_nomor'  => $jurnal->tgl_nomor,
                    'jam_ke'         => $jurnal->jam_ke,
                    'mata_pelajaran' => $jurnal->mata_pelajaran,
                    'kode_mapel'     => $jurnal->kode_mapel,
                    'guru'           => $jurnal->nama_guru,
                    'materi'         => $jurnal->materi,
                    'status'         => $status,
                    'keterangan'     => $ket,
                ];
            }

            $persen = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 0;

            $totalSemuaHadir += $hadir;
            $totalSemuaSakit += $sakit;
            $totalSemuaIzin  += $izin;
            $totalSemuaAlpa  += $alpa;

            $nisn = $siswa->pendaftaran?->nisn ?? $siswa->nisn ?? '-';

            $rekapSiswa[] = (object) [
                'siswa'             => $siswa,
                'nisn'              => $nisn,
                'pertemuan'         => $totalPertemuan,
                'pertemuan_absensi' => $pertemuanAbsensi,
                'hadir'             => $hadir,
                'terlambat'         => $terlambat,
                'sakit'             => $sakit,
                'izin'              => $izin,
                'alpa'              => $alpa,
                'persentase'        => $persen,
                'riwayat'           => $riwayatDetail,
            ];
        }

        return view('sekolah.rekap_absensi', compact(
            'kelasList',
            'selectedKelas',
            'totalPertemuan',
            'jurnals',
            'tanggalGroups',
            'rekapSiswa',
            'totalSemuaHadir',
            'totalSemuaSakit',
            'totalSemuaIzin',
            'totalSemuaAlpa',
            'bulan',
            'tahun',
            'activeRoom'
        ));
    }
}
