<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\JurnalEkskul;
use App\Models\JurnalEkskulAbsensi;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EkskulJurnalController extends Controller
{
    /**
     * Hitung semester dan tahun pelajaran otomatis berdasarkan tanggal.
     */
    public static function hitungPeriodeOtomatis(?string $tgl = null): array
    {
        $date = $tgl ? Carbon::parse($tgl)->locale('id') : Carbon::now('Asia/Jakarta')->locale('id');
        $bulan = (int) $date->format('m');
        $tahun = (int) $date->format('Y');

        if ($bulan >= 7) {
            $semester = 'Ganjil';
            $tahunPelajaran = $tahun . '/' . ($tahun + 1);
        } else {
            $semester = 'Genap';
            $tahunPelajaran = ($tahun - 1) . '/' . $tahun;
        }

        return [
            'hari'            => $date->translatedFormat('l'),
            'tanggal'         => $date->format('Y-m-d'),
            'semester'        => $semester,
            'tahun_pelajaran' => $tahunPelajaran,
        ];
    }

    /**
     * Form Input Jurnal Ekstrakurikuler + Absensi Anggota
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('ekskul', 'view') && !$authUser->canDo('sekolah', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Jurnal Ekstrakurikuler.');
        }

        // Lembaga sekolah formal: SMP & SMA
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        if ($sekolahLembagas->isEmpty()) {
            $sekolahLembagas = Lembaga::whereIn('kode', ['smp', 'sma'])->get();
        }

        $isYayasan = $authUser->isYayasan();
        $selectedLembaga = null;

        if ($isYayasan) {
            $reqKode = strtolower($request->get('lembaga', ''));
            if ($reqKode && in_array($reqKode, ['smp', 'sma'])) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', $reqKode);
            }
            if (!$selectedLembaga) {
                $activeKode = strtolower((string) session('active_lembaga'));
                if (in_array($activeKode, ['smp', 'sma'])) {
                    $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode);
                }
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', 'smp') ?: $sekolahLembagas->first();
            }
        } else {
            $activeKode = strtolower((string) session('active_lembaga'));
            $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode)
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());
        }

        if (!$selectedLembaga) {
            $selectedLembaga = Lembaga::where('kode', 'smp')->first() ?: Lembaga::first();
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        // Ambil daftar ekskul aktif
        $ekskuls = Ekstrakurikuler::where(function ($q) use ($selectedLembaga, $jenjang) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', $jenjang)
                  ->orWhere('jenjang', 'ALL');
            })
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->with(['pembina', 'siswas'])
            ->orderBy('nama_ekskul')
            ->get();

        // Safety fallback jika belum ada ekskul di lembaga ini tapi ada di sekolah lainnya
        if ($ekskuls->isEmpty() && !$request->filled('lembaga')) {
            $fallbackEkskuls = Ekstrakurikuler::where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->with(['pembina', 'siswas'])
                ->orderBy('nama_ekskul')
                ->get();

            if ($fallbackEkskuls->isNotEmpty()) {
                $firstMatch = $fallbackEkskuls->first(fn($e) => $e->lembaga_id && $sekolahLembagas->contains('id', $e->lembaga_id));
                if ($firstMatch) {
                    $selectedLembaga = $sekolahLembagas->firstWhere('id', $firstMatch->lembaga_id);
                    $kodeLembaga = strtolower($selectedLembaga->kode);
                    $jenjang = strtoupper($kodeLembaga);
                    $ekskuls = $fallbackEkskuls->filter(fn($e) => $e->lembaga_id == $selectedLembaga->id || $e->jenjang === $jenjang || $e->jenjang === 'ALL')->values();
                } else {
                    $ekskuls = $fallbackEkskuls;
                }
            }
        }

        // Tentukan ekskul terpilih
        $selectedEkskulId = $request->get('ekskul_id');
        $selectedEkskul = null;
        if ($selectedEkskulId) {
            $selectedEkskul = $ekskuls->firstWhere('id', $selectedEkskulId);
        }
        if (!$selectedEkskul && $ekskuls->isNotEmpty()) {
            $selectedEkskul = $ekskuls->first();
        }

        // Ambil siswa anggota ekskul terpilih
        $anggotaSiswa = collect();
        if ($selectedEkskul) {
            $anggotaSiswa = $selectedEkskul->siswas()
                ->where(function ($q) {
                    $q->whereIn('siswas.status', ['aktif', 'pulang', 'sakit'])
                      ->orWhereNull('siswas.status');
                })
                ->orderBy('nama_lengkap')
                ->get();
        }

        // Daftar guru resmi yang terdaftar di menu Guru Sekolah sesuai lembaga aktif (SMP / SMA)
        $gurus = User::with(['primaryLembaga', 'lembagas'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->where(function ($q) {
                $q->where('is_guru_sekolah', true)
                  ->orWhere('mapel_status', 'disetujui');
            })
            ->where('mapel_status', '!=', 'ditolak')
            ->where(function ($q) use ($selectedLembaga) {
                $q->where('primary_lembaga_id', $selectedLembaga->id)
                  ->orWhereHas('lembagas', function ($l) use ($selectedLembaga) {
                      $l->where('lembagas.id', $selectedLembaga->id);
                  })
                  ->orWhere('jabatan', 'like', "%{$selectedLembaga->singkatan}%")
                  ->orWhere('jabatan', 'like', "%{$selectedLembaga->kode}%");
            })
            ->orderBy('name')
            ->get();

        // Fallback jika belum ada approval resmi
        if ($gurus->isEmpty()) {
            $gurus = User::where(function ($q) use ($selectedLembaga) {
                    $q->where('primary_lembaga_id', $selectedLembaga->id)
                      ->orWhereHas('lembagas', fn($l) => $l->where('lembagas.id', $selectedLembaga->id));
                })
                ->where(function ($q) {
                    $q->where('role', 'guru')->orWhere('is_guru_sekolah', true);
                })
                ->orderBy('name')
                ->get();
        }

        // Pastikan pembina ekskul terpilih masuk dalam dropdown bila belum ada
        if ($selectedEkskul && $selectedEkskul->pembina && !$gurus->contains('id', $selectedEkskul->pembina_id)) {
            $gurus->prepend($selectedEkskul->pembina);
        }

        $periode = self::hitungPeriodeOtomatis($request->get('tanggal'));

        return view('sekolah.ekskul.jurnal', compact(
            'selectedLembaga',
            'kodeLembaga',
            'isYayasan',
            'sekolahLembagas',
            'jenjang',
            'ekskuls',
            'selectedEkskul',
            'anggotaSiswa',
            'gurus',
            'periode'
        ));
    }

    /**
     * Endpoint JSON anggota ekskul untuk form dinamis
     */
    public function siswaByEkskul(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $siswa = $ekstrakurikuler->siswas()
            ->where(function ($q) {
                $q->whereIn('siswas.status', ['aktif', 'pulang', 'sakit'])
                  ->orWhereNull('siswas.status');
            })
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($s) {
                return [
                    'id'           => $s->id,
                    'nama_lengkap' => $s->nama_lengkap,
                    'kelas'        => $s->kelas ?? '-',
                    'jenjang'      => $s->jenjang ?? '-',
                    'nisn'         => $s->pendaftaran?->nisn ?? $s->nisn ?? '-',
                    'status'       => $s->status ?? 'aktif',
                ];
            });

        return response()->json([
            'success'      => true,
            'ekskul_id'    => $ekstrakurikuler->id,
            'nama_ekskul'  => $ekstrakurikuler->nama_ekskul,
            'pembina_id'   => $ekstrakurikuler->pembina_id,
            'nama_pembina' => $ekstrakurikuler->nama_pembina,
            'hari'         => $ekstrakurikuler->hari,
            'jam_mulai'    => $ekstrakurikuler->jam_mulai ? substr((string) $ekstrakurikuler->jam_mulai, 0, 5) : null,
            'jam_selesai'  => $ekstrakurikuler->jam_selesai ? substr((string) $ekstrakurikuler->jam_selesai, 0, 5) : null,
            'tempat'       => $ekstrakurikuler->tempat,
            'total_siswa'  => $siswa->count(),
            'siswa'        => $siswa,
        ]);
    }

    /**
     * Simpan Jurnal Ekstrakurikuler baru beserta absensi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ekstrakurikuler_id' => 'required|exists:ekstrakurikulers,id',
            'tanggal'            => 'required|date',
            'hari'               => 'required|string|max:20',
            'jam_mulai'          => 'nullable|string|max:20',
            'jam_selesai'        => 'nullable|string|max:20',
            'tempat'             => 'nullable|string|max:100',
            'pembina_id'         => 'nullable|exists:users,id',
            'nama_pembina'       => 'nullable|string|max:100',
            'materi'             => 'required|string',
            'catatan'            => 'nullable|string',
            'absensi'            => 'nullable|array',
            'absensi.*.status'   => 'required|in:hadir,sakit,izin,alpa',
            'absensi.*.keterangan' => 'nullable|string|max:255',
        ], [
            'ekstrakurikuler_id.required' => 'Pilih kegiatan ekstrakurikuler.',
            'tanggal.required'            => 'Tanggal kegiatan wajib diisi.',
            'materi.required'             => 'Materi / ringkasan kegiatan ekskul wajib diisi.',
        ]);

        $ekskul = Ekstrakurikuler::findOrFail($validated['ekstrakurikuler_id']);

        $namaPembina = $validated['nama_pembina'] ?? null;
        if (!empty($validated['pembina_id']) && empty($namaPembina)) {
            $p = User::find($validated['pembina_id']);
            $namaPembina = $p?->name;
        }
        if (empty($namaPembina)) {
            $namaPembina = $ekskul->nama_pembina ?: (Auth::user()?->name ?? 'Pembina Ekskul');
        }

        $absensiData = $request->input('absensi', []);
        $totalSiswa = count($absensiData);
        $totalHadir = 0;
        $totalSakit = 0;
        $totalIzin  = 0;
        $totalAlpa  = 0;

        foreach ($absensiData as $item) {
            $st = $item['status'] ?? 'hadir';
            if ($st === 'hadir') $totalHadir++;
            elseif ($st === 'sakit') $totalSakit++;
            elseif ($st === 'izin') $totalIzin++;
            elseif ($st === 'alpa') $totalAlpa++;
        }

        DB::beginTransaction();
        try {
            $jurnal = JurnalEkskul::create([
                'ekstrakurikuler_id' => $ekskul->id,
                'lembaga_id'         => $ekskul->lembaga_id,
                'pembina_id'         => $validated['pembina_id'] ?? $ekskul->pembina_id,
                'nama_pembina'       => $namaPembina,
                'nama_ekskul'        => $ekskul->nama_ekskul,
                'tanggal'            => $validated['tanggal'],
                'hari'               => $validated['hari'],
                'jam_mulai'          => $validated['jam_mulai'] ?? null,
                'jam_selesai'        => $validated['jam_selesai'] ?? null,
                'tempat'             => $validated['tempat'] ?? $ekskul->tempat,
                'materi'             => $validated['materi'],
                'catatan'            => $validated['catatan'] ?? null,
                'total_siswa'        => $totalSiswa,
                'total_hadir'        => $totalHadir,
                'total_sakit'        => $totalSakit,
                'total_izin'         => $totalIzin,
                'total_alpa'         => $totalAlpa,
            ]);

            foreach ($absensiData as $siswaId => $data) {
                $siswa = Siswa::find($siswaId);
                if ($siswa) {
                    JurnalEkskulAbsensi::create([
                        'jurnal_ekskul_id' => $jurnal->id,
                        'siswa_id'         => $siswa->id,
                        'nama_siswa'       => $siswa->nama_lengkap,
                        'kelas'            => $siswa->kelas,
                        'status'           => $data['status'] ?? 'hadir',
                        'keterangan'       => $data['keterangan'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('sekolah.ekskul.rekap-jurnal')
                ->with('success', "Jurnal kegiatan ekstrakurikuler {$ekskul->nama_ekskul} tanggal " . Carbon::parse($validated['tanggal'])->translatedFormat('d F Y') . " berhasil disimpan.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan jurnal ekstrakurikuler: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan tabel riwayat Rekap Jurnal Ekstrakurikuler
     */
    public function rekapJurnal(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Lembaga sekolah formal: SMP & SMA
        $sekolahLembagas = $authUser->accessibleLembagas()->filter(function ($l) {
            return in_array(strtolower($l->kode), ['smp', 'sma']) || $l->kategori === 'formal';
        })->values();

        if ($sekolahLembagas->isEmpty()) {
            $sekolahLembagas = Lembaga::whereIn('kode', ['smp', 'sma'])->get();
        }

        $isYayasan = $authUser->isYayasan();
        $selectedLembaga = null;

        if ($isYayasan) {
            $reqKode = strtolower($request->get('lembaga', ''));
            if ($reqKode && in_array($reqKode, ['smp', 'sma'])) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', $reqKode);
            }
            if (!$selectedLembaga) {
                $activeKode = strtolower((string) session('active_lembaga'));
                if (in_array($activeKode, ['smp', 'sma'])) {
                    $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode);
                }
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $sekolahLembagas->firstWhere('kode', 'smp') ?: $sekolahLembagas->first();
            }
        } else {
            $activeKode = strtolower((string) session('active_lembaga'));
            $selectedLembaga = $sekolahLembagas->firstWhere('kode', $activeKode)
                ?: ($sekolahLembagas->firstWhere('id', $authUser->primary_lembaga_id) ?: $sekolahLembagas->first());
        }

        if (!$selectedLembaga) {
            $selectedLembaga = Lembaga::where('kode', 'smp')->first() ?: Lembaga::first();
        }

        $kodeLembaga = strtolower($selectedLembaga->kode);
        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        $ekskuls = Ekstrakurikuler::where(function ($q) use ($selectedLembaga, $jenjang) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', $jenjang)
                  ->orWhere('jenjang', 'ALL');
            })
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->orderBy('nama_ekskul')
            ->get();

        if ($ekskuls->isEmpty() && !$request->filled('lembaga')) {
            $fallbackEkskuls = Ekstrakurikuler::where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->orderBy('nama_ekskul')
                ->get();
            if ($fallbackEkskuls->isNotEmpty()) {
                $firstMatch = $fallbackEkskuls->first(fn($e) => $e->lembaga_id && $sekolahLembagas->contains('id', $e->lembaga_id));
                if ($firstMatch) {
                    $selectedLembaga = $sekolahLembagas->firstWhere('id', $firstMatch->lembaga_id);
                    $kodeLembaga = strtolower($selectedLembaga->kode);
                    $jenjang = strtoupper($kodeLembaga);
                    $ekskuls = $fallbackEkskuls->filter(fn($e) => $e->lembaga_id == $selectedLembaga->id || $e->jenjang === $jenjang || $e->jenjang === 'ALL')->values();
                } else {
                    $ekskuls = $fallbackEkskuls;
                }
            }
        }

        $query = JurnalEkskul::with(['ekstrakurikuler', 'pembina'])
            ->whereHas('ekstrakurikuler', function ($q) use ($selectedLembaga, $jenjang) {
                $q->where('lembaga_id', $selectedLembaga->id)
                  ->orWhere('jenjang', $jenjang)
                  ->orWhere('jenjang', 'ALL');
            });

        // Filter Ekskul
        if ($request->filled('ekskul_id')) {
            $query->where('ekstrakurikuler_id', $request->ekskul_id);
        }

        // Filter Bulan & Tahun
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun', date('Y'));
        if ($bulan) {
            $query->whereMonth('tanggal', $bulan);
        }
        if ($tahun) {
            $query->whereYear('tanggal', $tahun);
        }

        // Filter Tanggal spesifik
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $jurnals = $query->orderBy('tanggal', 'desc')->paginate(15)->withQueryString();

        // Ringkasan statistik
        $statsQuery = clone $query;
        $totalPertemuan = $statsQuery->count();
        $totalHadirAll  = $statsQuery->sum('total_hadir');
        $totalSiswaAll  = $statsQuery->sum('total_siswa');
        $rataPersenHadir = $totalSiswaAll > 0 ? round(($totalHadirAll / $totalSiswaAll) * 100, 1) : 0;

        return view('sekolah.ekskul.rekap_jurnal', compact(
            'selectedLembaga',
            'kodeLembaga',
            'isYayasan',
            'sekolahLembagas',
            'jenjang',
            'ekskuls',
            'jurnals',
            'totalPertemuan',
            'totalHadirAll',
            'totalSiswaAll',
            'rataPersenHadir',
            'bulan',
            'tahun'
        ));
    }

    /**
     * Detail Jurnal (JSON untuk Modal)
     */
    public function show(JurnalEkskul $jurnalEkskul)
    {
        $jurnalEkskul->load(['ekstrakurikuler', 'absensis.siswa']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $jurnalEkskul->id,
                'nama_ekskul'  => $jurnalEkskul->nama_ekskul,
                'nama_pembina' => $jurnalEkskul->nama_pembina,
                'tanggal'      => Carbon::parse($jurnalEkskul->tanggal)->translatedFormat('l, d F Y'),
                'hari'         => $jurnalEkskul->hari,
                'jam'          => ($jurnalEkskul->jam_mulai && $jurnalEkskul->jam_selesai) ? "{$jurnalEkskul->jam_mulai} - {$jurnalEkskul->jam_selesai} WIB" : '-',
                'tempat'       => $jurnalEkskul->tempat ?? '-',
                'materi'       => $jurnalEkskul->materi,
                'catatan'      => $jurnalEkskul->catatan ?? '-',
                'total_siswa'  => $jurnalEkskul->total_siswa,
                'total_hadir'  => $jurnalEkskul->total_hadir,
                'total_sakit'  => $jurnalEkskul->total_sakit,
                'total_izin'   => $jurnalEkskul->total_izin,
                'total_alpa'   => $jurnalEkskul->total_alpa,
                'absensis'     => $jurnalEkskul->absensis->map(function ($a) {
                    return [
                        'id'         => $a->id,
                        'nama_siswa' => $a->nama_siswa,
                        'kelas'      => $a->kelas ?? '-',
                        'status'     => $a->status,
                        'keterangan' => $a->keterangan ?? '-',
                    ];
                }),
            ]
        ]);
    }

    /**
     * Hapus jurnal ekstrakurikuler
     */
    public function destroy(JurnalEkskul $jurnalEkskul)
    {
        $ekskul = $jurnalEkskul->nama_ekskul;
        $tgl = Carbon::parse($jurnalEkskul->tanggal)->translatedFormat('d M Y');
        $jurnalEkskul->delete();

        return redirect()->back()->with('success', "Jurnal kegiatan {$ekskul} tanggal {$tgl} berhasil dihapus.");
    }
}

