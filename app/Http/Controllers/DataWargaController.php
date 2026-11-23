<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\MasterKelasPengajian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;

class DataWargaController extends Controller
{
    /**
     * Menampilkan daftar Guru yang sudah didaftarkan di menu Guru masing-masing lembaga.
     * Akun yang baru dibuat dan belum didaftarkan di menu Guru lembaga tidak ditampilkan.
     */
    public function guru(Request $request)
    {
        $authUser = auth()->user();
        $isYayasan = $authUser && $authUser->isYayasan();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $activeRoom = strtolower((string) ($scopedFormal?->kode ?? session('active_lembaga', '')));

        $allGuruLembagas = Lembaga::where('is_active', true)
            ->where(function ($q) {
                $q->where('kode', 'pondok')
                  ->orWhere('kategori', 'formal')
                  ->orWhereIn('kode', ['smp', 'sma', 'smk', 'sdit', 'sd', 'ra', 'tk', 'mi', 'mts', 'ma']);
            })
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        // Untuk non-Yayasan, batasi opsi lembaga ke lembaga yang dapat diaksesnya (terkunci ke room aktif)
        if ($isYayasan) {
            $sekolahLembagas = $allGuruLembagas;
        } else {
            $accessibleIds = $authUser ? $authUser->accessibleLembagas()->pluck('id')->all() : [];
            $sekolahLembagas = $allGuruLembagas->whereIn('id', $accessibleIds)->values();
            if ($scopedFormal) {
                $sekolahLembagas = collect([$scopedFormal]);
            }
        }

        $pondokLembaga = $sekolahLembagas->first(fn ($l) => strtolower((string) $l->kode) === 'pondok' || strtoupper((string) $l->singkatan) === 'PONDOK');
        $smpLembaga = $sekolahLembagas->first(fn ($l) => strtolower((string) $l->kode) === 'smp' || strtoupper((string) $l->singkatan) === 'SMP');
        $smaLembaga = $sekolahLembagas->first(fn ($l) => strtolower((string) $l->kode) === 'sma' || strtoupper((string) $l->singkatan) === 'SMA');

        $baseRegisteredQuery = fn () => User::with(['primaryLembaga', 'lembagas'])
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', true);
            })
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('is_guru_sekolah', true)
                        ->where(function ($s) {
                            $s->whereNull('mapel_status')
                              ->orWhereNotIn('mapel_status', ['ditolak', 'menunggu']);
                        });
                })->orWhere('is_guru_pondok', true);
            });

        $query = $baseRegisteredQuery();

        // Tentukan filter lembaga: terkunci ke room aktif untuk non-Yayasan di room formal/pondok
        $selectedLembagaId = $request->input('lembaga_id');
        if (!$isYayasan && $scopedFormal) {
            $selectedLembagaId = (string) $scopedFormal->id;
        } elseif (!$isYayasan && $activeRoom === 'pondok' && $pondokLembaga) {
            $selectedLembagaId = (string) $pondokLembaga->id;
        } elseif ($selectedLembagaId === null && $scopedFormal) {
            $selectedLembagaId = (string) $scopedFormal->id;
        } elseif ($selectedLembagaId === null && $activeRoom === 'pondok' && $pondokLembaga) {
            $selectedLembagaId = (string) $pondokLembaga->id;
        }

        // Validasi non-Yayasan tidak bisa menyuntikkan lembaga_id luar aksesnya
        if (!$isYayasan && !empty($selectedLembagaId) && $selectedLembagaId !== 'semua') {
            if (!$sekolahLembagas->contains('id', (int) $selectedLembagaId)) {
                $selectedLembagaId = (string) ($sekolahLembagas->first()?->id ?? '');
            }
        } elseif (!$isYayasan && ($selectedLembagaId === 'semua' || empty($selectedLembagaId)) && $sekolahLembagas->count() === 1) {
            $selectedLembagaId = (string) $sekolahLembagas->first()->id;
        }

        if (!empty($selectedLembagaId) && $selectedLembagaId !== 'semua') {
            if ($pondokLembaga && (string) $selectedLembagaId === (string) $pondokLembaga->id) {
                $query->where('is_guru_pondok', true);
            } else {
                $query->where('is_guru_sekolah', true)
                    ->where(function ($q) use ($selectedLembagaId) {
                        $q->where('primary_lembaga_id', $selectedLembagaId)
                          ->orWhereHas('lembagas', function ($l) use ($selectedLembagaId) {
                              $l->where('lembagas.id', $selectedLembagaId);
                          });
                    });
            }
        } elseif (!$isYayasan && $sekolahLembagas->isNotEmpty()) {
            $allowedIds = $sekolahLembagas->pluck('id')->all();
            $query->where(function ($q) use ($allowedIds, $pondokLembaga) {
                $q->whereIn('primary_lembaga_id', $allowedIds)
                  ->orWhereHas('lembagas', fn ($l) => $l->whereIn('lembagas.id', $allowedIds));
                if ($pondokLembaga) {
                    $q->orWhere('is_guru_pondok', true);
                }
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('pondok_materi', 'like', "%{$search}%");
            });
        }

        $gurus = $query->orderBy('name')->paginate(20)->withQueryString();
        $kelasPengajianMap = MasterKelasPengajian::pluck('nama_kelas', 'id')->all();

        $scopedStatQuery = fn () => $baseRegisteredQuery()->when(
            !$isYayasan && $scopedFormal,
            fn ($q) => $q->where('is_guru_sekolah', true)->forLembagaModel($scopedFormal)
        );

        $totalGuru   = $scopedStatQuery()->count();
        $totalPondok = (!$isYayasan && $scopedFormal) ? 0 : User::where(function ($q) {
            $q->whereNull('is_active')->orWhere('is_active', true);
        })->where('is_guru_pondok', true)->count();
        $totalSmp    = $smpLembaga ? $baseRegisteredQuery()->where('is_guru_sekolah', true)->forLembagaModel($smpLembaga)->count() : 0;
        $totalSma    = $smaLembaga ? $baseRegisteredQuery()->where('is_guru_sekolah', true)->forLembagaModel($smaLembaga)->count() : 0;

        // Statistik dinamis per lembaga formal yang dapat diakses
        $lembagaGuruStats = [];
        foreach ($sekolahLembagas as $lemb) {
            $isPondokItem = strtolower((string) $lemb->kode) === 'pondok';
            $cnt = $isPondokItem
                ? $totalPondok
                : $baseRegisteredQuery()->where('is_guru_sekolah', true)->forLembagaModel($lemb)->count();
            $lembagaGuruStats[] = [
                'id'    => $lemb->id,
                'kode'  => $lemb->kode,
                'label' => 'Guru ' . ($lemb->nama_singkat ?: $lemb->singkatan ?: $lemb->nama),
                'count' => $cnt,
            ];
        }

        return view('data-warga.guru', compact(
            'gurus',
            'sekolahLembagas',
            'selectedLembagaId',
            'kelasPengajianMap',
            'scopedFormal',
            'lembagaGuruStats',
            'totalGuru',
            'totalPondok',
            'totalSmp',
            'totalSma'
        ));
    }

    private function resolveActiveRoomKode(): ?string
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        return $scopedFormal?->kode ?? session('active_lembaga');
    }

    private function ensureSiswaInActiveRoom(Siswa $siswa): void
    {
        $activeRoom = $this->resolveActiveRoomKode();
        if (Lembaga::isGlobalRoom($activeRoom)) {
            return;
        }

        if (!Siswa::whereKey($siswa->id)->forLembaga($activeRoom)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data siswa lembaga lain.');
        }
    }

    /**
     * Menampilkan daftar siswa yang lulus dari Pondok maupun Sekolah.
     */
    public function lulus(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal?->kode ?? session('active_lembaga');
        $isGlobalRoom = Lembaga::isGlobalRoom($activeRoom);

        $query = Siswa::query()
            ->with('pendaftaran')
            ->where('status', 'lulus');

        $query->forLembaga($activeRoom);

        if ($request->filled('kategori_lulus')) {
            $query->where('kategori_lulus', $request->kategori_lulus);
        }

        if ($isGlobalRoom && $request->filled('jenjang')) {
            $jenjang = $request->jenjang;
            $query->where(function ($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)
                  ->orWhereHas('pendaftaran', fn ($p) => $p->where('jenjang', $jenjang));
            });
        }

        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%")
                  ->orWhere('tahun_lulus', 'like', "%{$search}%");
            });
        }

        $siswaLulus = $query->latest('tanggal_lulus')->latest('id')->paginate(20)->withQueryString();

        $totalLulus   = Siswa::forLembaga($activeRoom)->where('status', 'lulus')->count();
        $lulusPondok  = Siswa::forLembaga($activeRoom)->where('status', 'lulus')
            ->whereIn('kategori_lulus', ['Pondok', 'Pondok & Sekolah'])->count();
        $lulusSekolah = Siswa::forLembaga($activeRoom)->where('status', 'lulus')
            ->whereIn('kategori_lulus', ['Sekolah', 'Pondok & Sekolah'])->count();

        $tahunList = Siswa::forLembaga($activeRoom)
            ->where('status', 'lulus')
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '!=', '')
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus');

        $siswaAktifList = Siswa::forLembaga($activeRoom)
            ->where(function ($q) {
                $q->whereNotIn('status', ['lulus', 'mutasi'])
                  ->orWhereNull('status');
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jenjang', 'kelas', 'kamar']);

        $jenjangList = $isGlobalRoom
            ? Lembaga::allJenjangOptions(true)
            : [Lembaga::defaultJenjangForRoom($activeRoom)];
        $kategoriLulusList = ['Pondok', 'Sekolah', 'Pondok & Sekolah'];

        return view('data-warga.lulus', compact(
            'siswaLulus',
            'totalLulus',
            'lulusPondok',
            'lulusSekolah',
            'tahunList',
            'siswaAktifList',
            'jenjangList',
            'kategoriLulusList',
            'scopedFormal'
        ));
    }

    /**
     * Menambahkan siswa ke daftar Lulus.
     */
    public function storeLulus(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'kategori_lulus'    => 'required|in:Pondok,Sekolah,Pondok & Sekolah',
            'tahun_lulus'       => 'required|string|max:20',
            'tanggal_lulus'     => 'nullable|date',
            'keterangan_status' => 'nullable|string|max:1000',
        ], [
            'siswa_id.required'       => 'Pilih nama siswa.',
            'kategori_lulus.required' => 'Pilih kategori kelulusan.',
            'tahun_lulus.required'    => 'Tahun lulus wajib diisi.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->ensureSiswaInActiveRoom($siswa);

        $siswa->update([
            'status'            => 'lulus',
            'kategori_lulus'    => $validated['kategori_lulus'],
            'tahun_lulus'       => trim($validated['tahun_lulus']),
            'tanggal_lulus'     => $validated['tanggal_lulus'] ?: now()->toDateString(),
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.lulus')
            ->with('success', "Data kelulusan {$siswa->nama_lengkap} berhasil disimpan.");
    }

    /**
     * Memperbarui data kelulusan siswa.
     */
    public function updateLulus(Request $request, Siswa $siswa)
    {
        $this->ensureSiswaInActiveRoom($siswa);

        $validated = $request->validate([
            'kategori_lulus'    => 'required|in:Pondok,Sekolah,Pondok & Sekolah',
            'tahun_lulus'       => 'required|string|max:20',
            'tanggal_lulus'     => 'nullable|date',
            'keterangan_status' => 'nullable|string|max:1000',
        ]);

        $siswa->update([
            'status'            => 'lulus',
            'kategori_lulus'    => $validated['kategori_lulus'],
            'tahun_lulus'       => trim($validated['tahun_lulus']),
            'tanggal_lulus'     => $validated['tanggal_lulus'] ?: $siswa->tanggal_lulus,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.lulus')
            ->with('success', "Data kelulusan {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    /**
     * Menampilkan daftar siswa yang mutasi.
     */
    public function mutasi(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal?->kode ?? session('active_lembaga');
        $isGlobalRoom = Lembaga::isGlobalRoom($activeRoom);

        $query = Siswa::query()
            ->with('pendaftaran')
            ->where('status', 'mutasi');

        $query->forLembaga($activeRoom);

        if ($isGlobalRoom && $request->filled('jenjang')) {
            $jenjang = $request->jenjang;
            $query->where(function ($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)
                  ->orWhereHas('pendaftaran', fn ($p) => $p->where('jenjang', $jenjang));
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('kelas', 'like', "%{$search}%")
                  ->orWhere('tujuan_mutasi', 'like', "%{$search}%")
                  ->orWhere('keterangan_status', 'like', "%{$search}%");
            });
        }

        $siswaMutasi = $query->latest('tanggal_mutasi')->latest('id')->paginate(20)->withQueryString();

        $totalMutasi   = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->count();
        $mutasiSmp     = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'SMP')->count();
        $mutasiSma     = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'SMA')->count();
        $mutasiReguler = Siswa::forLembaga($activeRoom)->where('status', 'mutasi')->where('jenjang', 'Reguler')->count();

        $jenjangList = $isGlobalRoom
            ? Lembaga::allJenjangOptions(true)
            : [Lembaga::defaultJenjangForRoom($activeRoom)];

        $mutasiStats = [];
        if ($isGlobalRoom) {
            foreach ($jenjangList as $jLabel) {
                $lembMatch = Lembaga::resolveByKode($jLabel);
                $cnt = $lembMatch && $lembMatch->isFormalSchool()
                    ? Siswa::forLembaga($lembMatch->kode)->where('status', 'mutasi')->count()
                    : Siswa::where('status', 'mutasi')->whereRaw('LOWER(TRIM(COALESCE(jenjang, ""))) = ?', [strtolower(trim($jLabel))])->count();
                $mutasiStats[] = [
                    'kode'  => strtolower($lembMatch?->kode ?? $jLabel),
                    'label' => 'Mutasi ' . $jLabel,
                    'count' => $cnt,
                ];
            }
        }

        $siswaAktifList = Siswa::forLembaga($activeRoom)
            ->where(function ($q) {
                $q->whereNotIn('status', ['lulus', 'mutasi'])
                  ->orWhereNull('status');
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jenjang', 'kelas', 'kamar']);

        return view('data-warga.mutasi', compact(
            'siswaMutasi',
            'totalMutasi',
            'mutasiSmp',
            'mutasiSma',
            'mutasiReguler',
            'mutasiStats',
            'siswaAktifList',
            'jenjangList',
            'scopedFormal'
        ));
    }

    /**
     * Menambahkan siswa ke daftar Mutasi.
     */
    public function storeMutasi(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'          => 'required|exists:siswas,id',
            'tanggal_mutasi'    => 'required|date',
            'tujuan_mutasi'     => 'nullable|string|max:255',
            'keterangan_status' => 'nullable|string|max:1000',
        ], [
            'siswa_id.required'       => 'Pilih nama siswa.',
            'tanggal_mutasi.required' => 'Tanggal mutasi wajib diisi.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->ensureSiswaInActiveRoom($siswa);

        $siswa->update([
            'status'            => 'mutasi',
            'tanggal_mutasi'    => $validated['tanggal_mutasi'],
            'tujuan_mutasi'     => $validated['tujuan_mutasi'] ?? null,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.mutasi')
            ->with('success', "Data mutasi {$siswa->nama_lengkap} berhasil disimpan.");
    }

    /**
     * Memperbarui data mutasi siswa.
     */
    public function updateMutasi(Request $request, Siswa $siswa)
    {
        $this->ensureSiswaInActiveRoom($siswa);

        $validated = $request->validate([
            'tanggal_mutasi'    => 'required|date',
            'tujuan_mutasi'     => 'nullable|string|max:255',
            'keterangan_status' => 'nullable|string|max:1000',
        ]);

        $siswa->update([
            'status'            => 'mutasi',
            'tanggal_mutasi'    => $validated['tanggal_mutasi'],
            'tujuan_mutasi'     => $validated['tujuan_mutasi'] ?? null,
            'keterangan_status' => $validated['keterangan_status'] ?? null,
        ]);

        return redirect()->route('data-warga.mutasi')
            ->with('success', "Data mutasi {$siswa->nama_lengkap} berhasil diperbarui.");
    }

    /**
     * Mengembalikan status siswa dari Lulus / Mutasi menjadi Aktif kembali.
     */
    public function kembalikanAktif(Request $request, Siswa $siswa)
    {
        $this->ensureSiswaInActiveRoom($siswa);

        $asalStatus = $siswa->status;
        $siswa->update([
            'status'            => 'aktif',
            'kategori_lulus'    => null,
            'tahun_lulus'       => null,
            'tanggal_lulus'     => null,
            'tanggal_mutasi'    => null,
            'tujuan_mutasi'     => null,
            'keterangan_status' => null,
        ]);

        $redirectRoute = $asalStatus === 'mutasi' ? 'data-warga.mutasi' : 'data-warga.lulus';

        return redirect()->route($redirectRoute)
            ->with('success', "{$siswa->nama_lengkap} dikembalikan ke daftar Siswa Aktif.");
    }
}

