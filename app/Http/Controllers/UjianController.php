<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\UjianKunci;
use App\Models\UjianHasil;
use App\Models\UjianSesiJam;
use App\Models\UjianSoal;
use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\Omr\LjkTemplateService;
use App\Services\Omr\OmrScannerService;
use App\Services\Omr\QrCodeGenerator;
use App\Services\Omr\WordQuestionParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UjianController extends Controller
{
    /**
     * Dapatkan room lembaga formal aktif (null bila room global Yayasan/Pondok).
     */
    protected function scopedFormal(): ?Lembaga
    {
        return Lembaga::activeScopedFormal(Auth::user());
    }

    /**
     * Pastikan ujian yang diakses berada di dalam room lembaga aktif.
     */
    protected function ensureUjianInRoom(Ujian $ujian): void
    {
        $scoped = $this->scopedFormal();
        if ($scoped && !Ujian::forLembaga($scoped)->whereKey($ujian->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data ujian lembaga lain.');
        }
    }

    protected function scopedKelases()
    {
        $scoped = $this->scopedFormal();
        $q = Classroom::active();
        if ($scoped) {
            $q->forJenjang($scoped->kode);
        }

        return $q->orderBy('tingkat')->orderBy('nama_kelas')->get();
    }

    protected function scopedMapels(bool $withLembaga = false)
    {
        $scoped = $this->scopedFormal();
        $q = $withLembaga ? MataPelajaran::with('lembaga') : MataPelajaran::query();
        if ($scoped) {
            $q->forJenjang($scoped->kode);
        }

        return $q->orderBy('mata_pelajaran')->get();
    }

    protected function scopedGurus()
    {
        $scoped = $this->scopedFormal();
        $q = User::where(function ($sub) {
            $sub->where('role', 'guru')->orWhere('is_guru_sekolah', true);
        })->where('is_active', true);

        if ($scoped) {
            $q->forLembagaModel($scoped);
        }

        return $q->orderBy('name')->get();
    }

    /**
     * Redirect default /ujian ke sub menu Soal Ujian
     */
    public function index(Request $request)
    {
        return $this->soalIndex($request);
    }

    // =========================================================================
    // 1. SUB MENU: SOAL UJIAN
    // =========================================================================

    /**
     * Sub menu 1: Daftar Soal Ujian
     */
    public function soalIndex(Request $request)
    {
        $scoped = $this->scopedFormal();
        $query = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount(['soals', 'soalsPg', 'soalsEssay', 'kuncis'])
            ->forLembaga($scoped);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama_ujian', 'like', "%$s%")
                  ->orWhere('kode_ujian', 'like', "%$s%");
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        $ujians = $query->latest('tanggal_ujian')->paginate(12)->withQueryString();
        $kelases = $this->scopedKelases();
        $mapels = $this->scopedMapels();

        return view('ujian.soal-index', compact('ujians', 'kelases', 'mapels'));
    }

    /**
     * Halaman Pengaturan Ujian (Jam Sesi Ujian & Cetak Nomor Meja per Kelas)
     */
    public function pengaturan()
    {
        $scoped = $this->scopedFormal();

        $sesiJams = UjianSesiJam::forLembaga($scoped)
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get();

        $kelases = $this->scopedKelases();

        $siswaCountsByKelas = [];
        foreach ($kelases as $k) {
            $namaKelas = $k->nama_kelas;
            $q = Siswa::where(function ($sub) use ($namaKelas) {
                $sub->where('kelas', $namaKelas)
                    ->orWhereHas('pendaftaran', fn ($sq) => $sq->where('kelas', $namaKelas));
            })->where('status', 'aktif');
            if ($scoped) {
                $q->forLembaga($scoped->kode);
            }
            $siswaCountsByKelas[$k->id] = $q->count();
        }

        $nextUrutan = ($sesiJams->max('urutan') ?? 0) + 1;

        return view('ujian.pengaturan', compact('sesiJams', 'kelases', 'siswaCountsByKelas', 'nextUrutan'));
    }

    /**
     * Tambah Jam Sesi Ujian
     */
    public function storeSesiJam(Request $request)
    {
        $scoped = $this->scopedFormal();

        $validated = $request->validate([
            'nama_sesi'   => 'required|string|max:100',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'urutan'      => 'nullable|integer|min:1|max:50',
        ], [
            'nama_sesi.required'   => 'Nama sesi wajib diisi.',
            'jam_mulai.required'   => 'Jam mulai wajib diisi.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.after'    => 'Jam selesai harus lebih akhir daripada jam mulai.',
        ]);

        UjianSesiJam::create([
            'lembaga_id'  => $scoped?->id,
            'nama_sesi'   => trim($validated['nama_sesi']),
            'jam_mulai'   => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'urutan'      => (int) ($validated['urutan'] ?? 1),
            'is_active'   => true,
        ]);

        return redirect()->route('ujian.pengaturan')
            ->with('success', 'Jam sesi ujian berhasil ditambahkan.');
    }

    /**
     * Ubah Jam Sesi Ujian
     */
    public function updateSesiJam(Request $request, UjianSesiJam $sesiJam)
    {
        $scoped = $this->scopedFormal();
        if ($scoped && (int) $sesiJam->lembaga_id !== (int) $scoped->id) {
            abort(403, 'Anda tidak memiliki akses ke jam sesi lembaga lain.');
        }

        $validated = $request->validate([
            'nama_sesi'   => 'required|string|max:100',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'urutan'      => 'required|integer|min:1|max:50',
            'is_active'   => 'nullable|boolean',
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih akhir daripada jam mulai.',
        ]);

        $sesiJam->update([
            'nama_sesi'   => trim($validated['nama_sesi']),
            'jam_mulai'   => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'urutan'      => (int) $validated['urutan'],
            'is_active'   => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('ujian.pengaturan')
            ->with('success', 'Jam sesi ujian berhasil diperbarui.');
    }

    /**
     * Hapus Jam Sesi Ujian
     */
    public function destroySesiJam(UjianSesiJam $sesiJam)
    {
        $scoped = $this->scopedFormal();
        if ($scoped && (int) $sesiJam->lembaga_id !== (int) $scoped->id) {
            abort(403, 'Anda tidak memiliki akses ke jam sesi lembaga lain.');
        }

        $sesiJam->delete();

        return redirect()->route('ujian.pengaturan')
            ->with('success', 'Jam sesi ujian berhasil dihapus.');
    }

    /**
     * Cetak Kartu Nomor Meja Ujian per Kelas (Siap Print A4)
     */
    public function cetakNomorMeja(Request $request)
    {
        $scoped = $this->scopedFormal();
        $validated = $request->validate([
            'kelas_id'    => 'required|exists:classrooms,id',
            'judul_ujian' => 'nullable|string|max:120',
            'ruang'       => 'nullable|string|max:60',
            'awalan'      => 'nullable|string|max:20',
        ]);

        $kelas = Classroom::with('lembaga')->findOrFail($validated['kelas_id']);
        if ($scoped && !Classroom::forJenjang($scoped->kode)->whereKey($kelas->id)->exists()) {
            abort(403, 'Kelas yang dipilih bukan milik lembaga ruang kerja Anda.');
        }

        $namaKelas = $kelas->nama_kelas;
        $siswaQuery = Siswa::with('pendaftaran')
            ->where(function ($sub) use ($namaKelas) {
                $sub->where('kelas', $namaKelas)
                    ->orWhereHas('pendaftaran', fn ($sq) => $sq->where('kelas', $namaKelas));
            })
            ->where('status', 'aktif');
        if ($scoped) {
            $siswaQuery->forLembaga($scoped->kode);
        }
        $siswas = $siswaQuery->orderBy('nama_lengkap')->get();

        $lembagaNama = $kelas->lembaga?->nama ?? $scoped?->nama ?? 'YAYASAN PONDOK PESANTREN MUTTAQIN';
        $judulUjian = trim((string) ($validated['judul_ujian'] ?? '')) ?: 'NOMOR MEJA UJIAN';
        $ruangLabel = trim((string) ($validated['ruang'] ?? '')) ?: ('Ruang ' . $kelas->nama_kelas);
        $awalan = trim((string) ($validated['awalan'] ?? ''));

        return view('ujian.cetak-nomor-meja', compact(
            'kelas',
            'siswas',
            'lembagaNama',
            'judulUjian',
            'ruangLabel',
            'awalan'
        ));
    }

    /**
     * Form buat ujian baru
     */
    public function create()
    {
        $kelases = $this->scopedKelases();
        $mapels = $this->scopedMapels();
        $gurus = $this->scopedGurus();

        $prefix = 'UJN-' . date('Ym') . '-';
        $last = Ujian::where('kode_ujian', 'like', "$prefix%")->orderBy('id', 'desc')->first();
        $nextNum = $last ? (((int) substr($last->kode_ujian, -3)) + 1) : 1;
        $suggestedKode = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        return view('ujian.form', [
            'ujian' => new Ujian([
                'kode_ujian'          => $suggestedKode,
                'jumlah_soal'         => 40,
                'jumlah_soal_essay'   => 0,
                'jumlah_opsi'         => 4,
                'bobot_benar'         => 1.00,
                'bobot_pg'            => 100.00,
                'bobot_essay'         => 0.00,
                'timer_default'       => 90,
                'timer_essay_default' => 180,
                'tanggal_ujian'       => now()->toDateString(),
                'status'              => 'draft',
            ]),
            'isEdit'  => false,
            'kelases' => $kelases,
            'mapels'  => $mapels,
            'gurus'   => $gurus,
        ]);
    }

    /**
     * Hitung dan validasi pasangan bobot persen PG & Essay agar total konsisten 100%.
     */
    protected function resolveBobotPersen(int $jumlahPg, int $jumlahEssay, mixed $bobotPg, mixed $bobotEssay): array
    {
        if ($jumlahPg <= 0 && $jumlahEssay <= 0) {
            throw ValidationException::withMessages([
                'jumlah_soal' => 'Ujian minimal harus memiliki 1 soal Pilihan Ganda atau 1 soal Essay.',
            ]);
        }

        if ($jumlahPg > 0 && $jumlahEssay <= 0) {
            return ['bobot_pg' => 100.00, 'bobot_essay' => 0.00];
        }

        if ($jumlahPg <= 0 && $jumlahEssay > 0) {
            return ['bobot_pg' => 0.00, 'bobot_essay' => 100.00];
        }

        // Keduanya ada (PG + Essay)
        $hasInputPg = $bobotPg !== null && $bobotPg !== '';
        $hasInputEssay = $bobotEssay !== null && $bobotEssay !== '';

        if (!$hasInputPg && !$hasInputEssay) {
            return ['bobot_pg' => 70.00, 'bobot_essay' => 30.00];
        }

        $pgVal = $hasInputPg ? round((float) $bobotPg, 2) : round(100.0 - (float) $bobotEssay, 2);
        $esVal = $hasInputEssay ? round((float) $bobotEssay, 2) : round(100.0 - $pgVal, 2);

        if ($pgVal <= 0 || $esVal <= 0 || abs(($pgVal + $esVal) - 100.0) > 0.05) {
            throw ValidationException::withMessages([
                'bobot_essay' => 'Jika ujian memuat PG dan Essay, total Bobot PG (%) + Bobot Essay (%) harus tepat 100% dan masing-masing lebih dari 0%.',
            ]);
        }

        return ['bobot_pg' => $pgVal, 'bobot_essay' => $esVal];
    }

    /**
     * Simpan ujian baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_ujian'          => 'required|string|max:50|unique:ujian,kode_ujian',
            'nama_ujian'          => 'required|string|max:200',
            'mapel_id'            => 'nullable|exists:mata_pelajarans,id',
            'kelas_id'            => 'nullable|exists:classrooms,id',
            'guru_id'             => 'nullable|exists:users,id',
            'jumlah_soal'         => 'required|integer|min:0|max:100',
            'jumlah_soal_essay'   => 'nullable|integer|min:0|max:50',
            'jumlah_opsi'         => 'required|integer|in:4,5',
            'bobot_benar'         => 'nullable|numeric|min:0.1|max:100',
            'bobot_pg'            => 'nullable|numeric|min:0|max:100',
            'bobot_essay'         => 'nullable|numeric|min:0|max:100',
            'timer_default'       => 'nullable|integer|min:10|max:1800',
            'timer_essay_default' => 'nullable|integer|min:15|max:3600',
            'tanggal_ujian'       => 'required|date',
            'status'              => 'required|in:draft,aktif,selesai',
            'file_word'           => 'nullable|file|max:15360',
        ]);

        $jumlahPg = (int) $validated['jumlah_soal'];
        $jumlahEssay = (int) ($validated['jumlah_soal_essay'] ?? 0);
        $bobotPersen = $this->resolveBobotPersen(
            $jumlahPg,
            $jumlahEssay,
            $validated['bobot_pg'] ?? null,
            $validated['bobot_essay'] ?? null
        );

        $scoped = $this->scopedFormal();
        if ($scoped) {
            if (!empty($validated['kelas_id']) && !Classroom::forJenjang($scoped->kode)->whereKey($validated['kelas_id'])->exists()) {
                abort(403, 'Kelas yang dipilih bukan milik lembaga ruang kerja Anda.');
            }
            if (!empty($validated['mapel_id']) && !MataPelajaran::forJenjang($scoped->kode)->whereKey($validated['mapel_id'])->exists()) {
                abort(403, 'Mata pelajaran yang dipilih bukan milik lembaga ruang kerja Anda.');
            }
            $lembagaId = $scoped->id;
        } else {
            $lembagaId = (!empty($validated['kelas_id']) ? Classroom::whereKey($validated['kelas_id'])->value('lembaga_id') : null)
                ?? (!empty($validated['mapel_id']) ? MataPelajaran::whereKey($validated['mapel_id'])->value('lembaga_id') : null);
        }

        $timerDefault = (int) ($validated['timer_default'] ?? 90);
        $timerEssayDefault = (int) ($validated['timer_essay_default'] ?? 180);
        $bobotBenar = (float) ($validated['bobot_benar'] ?? 1.00);

        $ujian = DB::transaction(function () use ($validated, $jumlahPg, $jumlahEssay, $bobotPersen, $bobotBenar, $timerDefault, $timerEssayDefault, $request, $lembagaId) {
            $ujian = Ujian::create([
                'lembaga_id'          => $lembagaId,
                'kode_ujian'          => $validated['kode_ujian'],
                'nama_ujian'          => $validated['nama_ujian'],
                'mapel_id'            => $validated['mapel_id'] ?? null,
                'kelas_id'            => $validated['kelas_id'] ?? null,
                'guru_id'             => $validated['guru_id'] ?? null,
                'jumlah_soal'         => $jumlahPg,
                'jumlah_soal_essay'   => $jumlahEssay,
                'jumlah_opsi'         => $validated['jumlah_opsi'],
                'bobot_benar'         => $bobotBenar,
                'bobot_pg'            => $bobotPersen['bobot_pg'],
                'bobot_essay'         => $bobotPersen['bobot_essay'],
                'timer_default'       => $timerDefault,
                'timer_essay_default' => $timerEssayDefault,
                'tanggal_ujian'       => $validated['tanggal_ujian'],
                'status'              => $validated['status'],
            ]);

            // Inisialisasi kunci jawaban default PG jika ada soal PG
            if ($ujian->jumlah_soal > 0) {
                $kunciData = [];
                for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                    $kunciData[] = [
                        'ujian_id'      => $ujian->id,
                        'nomor_soal'    => $no,
                        'kunci_jawaban' => 'A',
                        'bobot'         => $ujian->bobot_benar,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
                UjianKunci::insert($kunciData);
            }

            // Jika ada file Word (.docx) diunggah langsung saat pembuatan
            if ($request->hasFile('file_word')) {
                $this->processUploadedWordFile($request->file('file_word'), $ujian, $timerDefault, $timerEssayDefault);
            }

            return $ujian;
        });

        $ujian->refresh();
        if ($ujian->hasPg()) {
            return redirect()->route('ujian.kunci', $ujian->id)
                ->with('success', "Ujian {$ujian->nama_ujian} berhasil dibuat. Silakan atur kunci jawaban atau unggah naskah soal.");
        }

        return redirect()->route('ujian.soal')
            ->with('success', "Ujian Essay {$ujian->nama_ujian} berhasil dibuat.");
    }

    /**
     * Form edit ujian
     */
    public function edit(Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $kelases = $this->scopedKelases();
        $mapels = $this->scopedMapels();
        $gurus = $this->scopedGurus();

        return view('ujian.form', [
            'ujian'   => $ujian,
            'isEdit'  => true,
            'kelases' => $kelases,
            'mapels'  => $mapels,
            'gurus'   => $gurus,
        ]);
    }

    /**
     * Update data ujian
     */
    public function update(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);

        $validated = $request->validate([
            'kode_ujian'          => 'required|string|max:50|unique:ujian,kode_ujian,' . $ujian->id,
            'nama_ujian'          => 'required|string|max:200',
            'mapel_id'            => 'nullable|exists:mata_pelajarans,id',
            'kelas_id'            => 'nullable|exists:classrooms,id',
            'guru_id'             => 'nullable|exists:users,id',
            'jumlah_soal'         => 'required|integer|min:0|max:100',
            'jumlah_soal_essay'   => 'nullable|integer|min:0|max:50',
            'jumlah_opsi'         => 'required|integer|in:4,5',
            'bobot_benar'         => 'nullable|numeric|min:0.1|max:100',
            'bobot_pg'            => 'nullable|numeric|min:0|max:100',
            'bobot_essay'         => 'nullable|numeric|min:0|max:100',
            'timer_default'       => 'nullable|integer|min:10|max:1800',
            'timer_essay_default' => 'nullable|integer|min:15|max:3600',
            'tanggal_ujian'       => 'required|date',
            'status'              => 'required|in:draft,aktif,selesai',
        ]);

        $jumlahPg = (int) $validated['jumlah_soal'];
        $jumlahEssay = (int) ($validated['jumlah_soal_essay'] ?? 0);
        $bobotPersen = $this->resolveBobotPersen(
            $jumlahPg,
            $jumlahEssay,
            $validated['bobot_pg'] ?? null,
            $validated['bobot_essay'] ?? null
        );

        $validated['jumlah_soal'] = $jumlahPg;
        $validated['jumlah_soal_essay'] = $jumlahEssay;
        $validated['bobot_benar'] = (float) ($validated['bobot_benar'] ?? $ujian->bobot_benar ?? 1.00);
        $validated['bobot_pg'] = $bobotPersen['bobot_pg'];
        $validated['bobot_essay'] = $bobotPersen['bobot_essay'];
        $validated['timer_default'] = (int) ($validated['timer_default'] ?? $ujian->timer_default ?? 90);
        $validated['timer_essay_default'] = (int) ($validated['timer_essay_default'] ?? $ujian->timer_essay_default ?? 180);

        $scoped = $this->scopedFormal();
        if ($scoped) {
            if (!empty($validated['kelas_id']) && !Classroom::forJenjang($scoped->kode)->whereKey($validated['kelas_id'])->exists()) {
                abort(403, 'Kelas yang dipilih bukan milik lembaga ruang kerja Anda.');
            }
            if (!empty($validated['mapel_id']) && !MataPelajaran::forJenjang($scoped->kode)->whereKey($validated['mapel_id'])->exists()) {
                abort(403, 'Mata pelajaran yang dipilih bukan milik lembaga ruang kerja Anda.');
            }
            $validated['lembaga_id'] = $scoped->id;
        }

        $prevJumlahSoal = $ujian->jumlah_soal;

        DB::transaction(function () use ($ujian, $validated, $prevJumlahSoal) {
            $ujian->update($validated);

            if ($ujian->jumlah_soal > $prevJumlahSoal) {
                $newKunci = [];
                for ($no = $prevJumlahSoal + 1; $no <= $ujian->jumlah_soal; $no++) {
                    $newKunci[] = [
                        'ujian_id'      => $ujian->id,
                        'nomor_soal'    => $no,
                        'kunci_jawaban' => 'A',
                        'bobot'         => $ujian->bobot_benar,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
                UjianKunci::insert($newKunci);
            } elseif ($ujian->jumlah_soal < $prevJumlahSoal) {
                UjianKunci::where('ujian_id', $ujian->id)
                    ->where('nomor_soal', '>', $ujian->jumlah_soal)
                    ->delete();
            }

            $this->recalculateAllScores($ujian->fresh(['kuncis', 'hasils']));
        });

        return redirect()->route('ujian.soal')
            ->with('success', "Data ujian {$ujian->nama_ujian} berhasil diperbarui.");
    }

    /**
     * Hapus ujian
     */
    public function destroy(Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $nama = $ujian->nama_ujian;
        $ujian->delete();

        return redirect()->route('ujian.soal')
            ->with('success', "Ujian {$nama} berhasil dihapus.");
    }

    /**
     * Validasi struktural berkas Word (.docx) yang tahan terhadap perbedaan MIME libmagic (application/zip vs docx).
     */
    protected function validateDocxFile($file): ?string
    {
        if (!$file || !$file->isValid()) {
            $maxUpload = ini_get('upload_max_filesize') ?: '2M';
            return "Berkas gagal diunggah ke server. Pastikan ukuran file Word tidak melebihi batas upload server ({$maxUpload}).";
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === 'doc') {
            return 'Format .doc (Word 97-2003) belum didukung. Buka file di Microsoft Word lalu pilih Save As menjadi Word Document (.docx).';
        }
        if ($ext !== 'docx') {
            return 'Format file harus Word (.docx).';
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            return 'File .docx tidak dapat dibuka. Pastikan berkas tidak rusak dan bukan hasil ganti ekstensi manual.';
        }

        $hasDocXml = $zip->locateName('word/document.xml') !== false;
        $zip->close();

        if (!$hasDocXml) {
            return 'Struktur berkas Word (.docx) tidak valid (word/document.xml tidak ditemukan).';
        }

        return null;
    }

    /**
     * Unggah naskah soal Word (.docx) dan ekstrak butir soal PG & Essay otomatis
     */
    public function uploadWordSoal(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);

        // Tangani lebih dulu jika file melebihi batas upload PHP server (upload_max_filesize / post_max_size)
        $rawError = $_FILES['file_word']['error'] ?? UPLOAD_ERR_OK;
        if (in_array($rawError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $maxUpload = ini_get('upload_max_filesize') ?: '2M';
            return redirect()->back()->with('error', "Ukuran file Word melebihi batas upload server ({$maxUpload}). Silakan perkecil ukuran gambar di dalam file Word lalu unggah ulang.");
        }

        $request->validate([
            'file_word'      => 'required|file|max:15360', // max 15MB, validasi struktural .docx di bawah
            'timer_per_soal' => 'nullable|integer|min:10|max:1800',
            'timer_essay'    => 'nullable|integer|min:15|max:3600',
        ], [
            'file_word.required' => 'Silakan pilih file Word (.docx) terlebih dahulu.',
            'file_word.uploaded' => 'Berkas gagal diunggah ke server. Pastikan ukuran file tidak melebihi batas upload server (' . (ini_get('upload_max_filesize') ?: '2M') . ').',
            'file_word.max'      => 'Ukuran berkas Word maksimal 15 MB.',
        ]);

        $file = $request->file('file_word');
        if ($docxError = $this->validateDocxFile($file)) {
            return redirect()->back()->with('error', $docxError);
        }

        $timerPg = (int) ($request->input('timer_per_soal') ?: ($ujian->timer_default ?: 90));
        $timerEssay = (int) ($request->input('timer_essay') ?: ($ujian->timer_essay_default ?: 180));

        try {
            $parsed = $this->processUploadedWordFile($file, $ujian, $timerPg, $timerEssay);
            $parts = [];
            if ($parsed['total_soal_pg'] > 0) {
                $parts[] = "{$parsed['total_soal_pg']} soal PG";
            }
            if ($parsed['total_soal_essay'] > 0) {
                $parts[] = "{$parsed['total_soal_essay']} soal Essay";
            }
            $summary = implode(' dan ', $parts);

            return redirect()->back()->with('success', "Naskah soal Word berhasil diproses! {$summary} terbaca dan kunci jawaban telah disinkronkan.");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal upload/parse soal Word ujian #' . $ujian->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses file Word: ' . $e->getMessage());
        }
    }

    /**
     * Helper pemrosesan file Word (.docx) untuk PG dan/atau Essay
     */
    private function processUploadedWordFile($uploadedFile, Ujian $ujian, int $timerPg = 90, int $timerEssay = 180): array
    {
        $parser = new WordQuestionParserService();
        $parsed = $parser->parse($uploadedFile->getRealPath());

        $soalsPg = $parsed['soals_pg'] ?? $parsed['soals'] ?? [];
        $soalsEssay = $parsed['soals_essay'] ?? [];

        // Jika ujian khusus Essay (0 PG) dan guru menulis nomor 1, 2, 3 tanpa pemisah [ESSAY] serta tanpa opsi A-D, konversi otomatis ke Essay
        if (empty($soalsEssay) && !empty($soalsPg) && !$ujian->hasPg() && $ujian->hasEssay()) {
            $allWithoutOptions = collect($soalsPg)->every(fn ($s) => empty($s['opsi_a']) && empty($s['opsi_b']) && empty($s['opsi_c']) && empty($s['opsi_d']));
            if ($allWithoutOptions) {
                $soalsEssay = array_map(function ($s) {
                    $s['tipe_soal'] = 'essay';
                    $s['kunci'] = null;
                    return $s;
                }, $soalsPg);
                $soalsPg = [];
                $parsed['soals'] = [];
                $parsed['soals_pg'] = [];
                $parsed['soals_essay'] = $soalsEssay;
                $parsed['total_soal'] = 0;
                $parsed['total_soal_pg'] = 0;
                $parsed['total_soal_essay'] = count($soalsEssay);
            }
        }

        if (empty($soalsPg) && empty($soalsEssay)) {
            throw new \Exception("Tidak ada butir soal yang berhasil terdeteksi dalam file Word. Pastikan format penomoran sesuai template (misal: 1. Pertanyaan, A. Pilihan, B. Pilihan... atau bagian [ESSAY]).");
        }

        // Simpan file asli ke storage
        $storedPath = $uploadedFile->store('naskah_ujian', 'public');

        DB::transaction(function () use ($ujian, $parsed, $soalsPg, $soalsEssay, $storedPath, $timerPg, $timerEssay) {
            // Hapus soal lama
            UjianSoal::where('ujian_id', $ujian->id)->delete();

            $soalInserts = [];
            $kunciMap = [];
            $now = now();

            foreach ($soalsPg as $item) {
                $no = (int) $item['nomor'];
                $soalInserts[] = [
                    'ujian_id'        => $ujian->id,
                    'tipe_soal'       => 'pg',
                    'nomor_soal'      => $no,
                    'pertanyaan'      => $item['pertanyaan'],
                    'opsi_a'          => $item['opsi_a'],
                    'opsi_b'          => $item['opsi_b'],
                    'opsi_c'          => $item['opsi_c'],
                    'opsi_d'          => $item['opsi_d'],
                    'opsi_e'          => $item['opsi_e'],
                    'kunci_jawaban'   => $item['kunci'] ?? 'A',
                    'skor_maks'       => null,
                    'pedoman_jawaban' => null,
                    'timer_detik'     => !empty($item['timer_detik']) ? (int) $item['timer_detik'] : $timerPg,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                $kunciMap[$no] = strtoupper($item['kunci'] ?? 'A');
            }

            foreach ($soalsEssay as $item) {
                $no = (int) $item['nomor'];
                $soalInserts[] = [
                    'ujian_id'        => $ujian->id,
                    'tipe_soal'       => 'essay',
                    'nomor_soal'      => $no,
                    'pertanyaan'      => $item['pertanyaan'],
                    'opsi_a'          => null,
                    'opsi_b'          => null,
                    'opsi_c'          => null,
                    'opsi_d'          => null,
                    'opsi_e'          => null,
                    'kunci_jawaban'   => null,
                    'skor_maks'       => $item['skor_maks'] ?? null,
                    'pedoman_jawaban' => $item['pedoman_jawaban'] ?? null,
                    'timer_detik'     => !empty($item['timer_detik']) ? (int) $item['timer_detik'] : $timerEssay,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            if (!empty($soalInserts)) {
                UjianSoal::insert($soalInserts);
            }

            $pgCount = count($soalsPg);
            $essayCount = count($soalsEssay);

            // Tentukan bobot persen otomatis jika berubah komposisi
            $bobotPg = (float) $ujian->bobot_pg;
            $bobotEssay = (float) $ujian->bobot_essay;
            if ($pgCount > 0 && $essayCount === 0) {
                $bobotPg = 100.00;
                $bobotEssay = 0.00;
            } elseif ($pgCount === 0 && $essayCount > 0) {
                $bobotPg = 0.00;
                $bobotEssay = 100.00;
            } elseif ($pgCount > 0 && $essayCount > 0 && ($bobotEssay <= 0 || $bobotPg <= 0 || abs(($bobotPg + $bobotEssay) - 100.0) > 0.05)) {
                $bobotPg = 70.00;
                $bobotEssay = 30.00;
            }

            // Update informasi ujian
            $ujian->update([
                'file_soal'           => $storedPath,
                'jumlah_soal'         => $pgCount,
                'jumlah_soal_essay'   => $essayCount,
                'jumlah_opsi'         => $parsed['jumlah_opsi'] ?? $ujian->jumlah_opsi,
                'bobot_pg'            => $bobotPg,
                'bobot_essay'         => $bobotEssay,
                'timer_default'       => $timerPg,
                'timer_essay_default' => $timerEssay,
            ]);

            // Sinkronkan ke tabel ujian_kunci (khusus PG)
            foreach ($kunciMap as $no => $kunciLetter) {
                UjianKunci::updateOrCreate(
                    ['ujian_id' => $ujian->id, 'nomor_soal' => $no],
                    ['kunci_jawaban' => $kunciLetter, 'bobot' => $ujian->bobot_benar]
                );
            }

            // Hapus kunci yang melebihi jumlah soal PG baru
            UjianKunci::where('ujian_id', $ujian->id)
                ->where('nomor_soal', '>', $pgCount)
                ->delete();
        });

        return $parsed;
    }

    /**
     * Unduh Template Naskah Soal Word (.docx)
     */
    public function downloadTemplate()
    {
        $parser = new WordQuestionParserService();
        $tempFile = $parser->generateTemplateDocx();

        return response()->download($tempFile, 'Template_Naskah_Soal_Ujian.docx')->deleteFileAfterSend(true);
    }

    /**
     * Halaman Kunci Jawaban
     */
    public function kunci(Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $ujian->load('kuncis');
        $kuncis = $ujian->kuncis->keyBy('nomor_soal');

        return view('ujian.kunci', compact('ujian', 'kuncis'));
    }

    /**
     * Simpan pembaruan Kunci Jawaban
     */
    public function updateKunci(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $keys = $request->input('kunci', []);
        $bobots = $request->input('bobot', []);

        DB::transaction(function () use ($ujian, $keys, $bobots) {
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $huruf = strtoupper(trim($keys[$no] ?? 'A'));
                if (!in_array($huruf, ['A', 'B', 'C', 'D', 'E'])) {
                    $huruf = 'A';
                }

                $bobot = isset($bobots[$no]) ? (float)$bobots[$no] : (float)$ujian->bobot_benar;

                UjianKunci::updateOrCreate(
                    [
                        'ujian_id'   => $ujian->id,
                        'nomor_soal' => $no,
                    ],
                    [
                        'kunci_jawaban' => $huruf,
                        'bobot'         => $bobot,
                    ]
                );
            }

            $this->recalculateAllScores($ujian);
        });

        return redirect()->back()->with('success', 'Kunci jawaban berhasil disimpan dan skor hasil ujian telah disinkronkan.');
    }

    /**
     * Update timer per soal (PG dan Essay)
     */
    public function updateTimer(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $validated = $request->validate([
            'timer_default'       => 'required|integer|min:10|max:1800',
            'timer_essay_default' => 'nullable|integer|min:15|max:3600',
            'soal_timer'          => 'nullable|array',
            'soal_timer.*'        => 'integer|min:10|max:3600',
        ]);

        $timerEssay = (int) ($validated['timer_essay_default'] ?? $ujian->timer_essay_default ?? 180);

        DB::transaction(function () use ($ujian, $validated, $timerEssay) {
            $ujian->update([
                'timer_default'       => $validated['timer_default'],
                'timer_essay_default' => $timerEssay,
            ]);

            if (!empty($validated['soal_timer'])) {
                foreach ($validated['soal_timer'] as $soalId => $detik) {
                    UjianSoal::where('id', $soalId)
                        ->where('ujian_id', $ujian->id)
                        ->update(['timer_detik' => $detik]);
                }
            } else {
                UjianSoal::where('ujian_id', $ujian->id)
                    ->where('tipe_soal', 'pg')
                    ->update(['timer_detik' => $validated['timer_default']]);

                UjianSoal::where('ujian_id', $ujian->id)
                    ->where('tipe_soal', 'essay')
                    ->update(['timer_detik' => $timerEssay]);
            }
        });

        return redirect()->back()->with('success', 'Waktu hitung mundur soal berhasil diperbarui.');
    }

    // =========================================================================
    // 2. SUB MENU: LJK (LEMBAR JAWAB KOMPUTER)
    // =========================================================================

    /**
     * Sub menu 2: Halaman Manajemen & Cetak LJK
     */
    public function ljkIndex(Request $request)
    {
        $scoped = $this->scopedFormal();
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->forLembaga($scoped)
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? $ujians->firstWhere('id', (int) $selectedUjianId) : null;

        $siswas = collect();
        if ($ujian && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $siswaQuery = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->where('status', 'aktif');
            if ($scoped) {
                $siswaQuery->forLembaga($scoped->kode);
            }
            $siswas = $siswaQuery->orderBy('nama_lengkap')->get();
        }

        return view('ujian.ljk-index', compact('ujians', 'ujian', 'siswas'));
    }

    /**
     * Simpan teks peraturan ujian
     */
    public function updatePeraturan(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $validated = $request->validate([
            'peraturan_ujian' => 'nullable|string|max:2000',
        ]);

        $ujian->update([
            'peraturan_ujian' => $validated['peraturan_ujian'],
        ]);

        return redirect()->back()->with('success', 'Peraturan dan tata tertib LJK berhasil diperbarui.');
    }

    /**
     * Cetak LJK Kelas Siap Print (A4 dengan 4 Corner Fiducial Markers + QR Code + Grid OMR Presisi)
     */
    public function cetakLjk(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $scoped = $this->scopedFormal();
        $ujian->load(['mapel', 'kelas', 'guru']);

        $query = Siswa::query();
        if ($scoped) {
            $query->forLembaga($scoped->kode);
        }
        if ($ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $query->where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            });
        }

        if ($request->filled('siswa_id')) {
            $query->where('id', $request->siswa_id);
        }

        $siswas = $query->where('status', 'aktif')->orderBy('nama_lengkap')->get();

        if ($siswas->isEmpty()) {
            $siswas = collect([
                new Siswa([
                    'id'           => 0,
                    'nama_lengkap' => '................................................',
                    'kamar'        => '-',
                ]),
            ]);
        }

        $tipeKode = $request->input('tipe_kode', 'qr') === 'barcode' ? 'barcode' : 'qr';

        $qrCodes = [];
        $barcodes = [];
        foreach ($siswas as $s) {
            $payload = LjkTemplateService::makeQrPayload($ujian, $s);
            $qrCodes[$s->id] = QrCodeGenerator::svg($payload, 110);

            $barcodePayload = LjkTemplateService::makeBarcodePayload($ujian, $s);
            $barcodes[$s->id] = QrCodeGenerator::barcode128Svg($barcodePayload, 165, 62);
        }

        $bubbleCoords = $ujian->jumlah_soal > 0
            ? LjkTemplateService::getBubbleCoordinates($ujian->jumlah_soal, $ujian->jumlah_opsi)
            : [];
        $essayLayout = LjkTemplateService::calculateEssayLayout(
            (int) $ujian->jumlah_soal,
            (int) ($ujian->jumlah_soal_essay ?? 0)
        );

        return view('ujian.cetak-ljk', compact('ujian', 'siswas', 'qrCodes', 'barcodes', 'tipeKode', 'bubbleCoords', 'essayLayout'));
    }

    // =========================================================================
    // 3. SUB MENU: SESI UJIAN (MODE FULL SCREEN PROYEKTOR)
    // =========================================================================

    /**
     * Sub menu 3: Form Pengaturan Sesi Ujian (Pilih Kelas, Mapel, Guru Auto, Jam)
     */
    public function sesiIndex(Request $request)
    {
        $scoped = $this->scopedFormal();
        $kelases = $this->scopedKelases();
        $mapels = $this->scopedMapels(true);
        $gurus = $this->scopedGurus();

        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('soals')
            ->forLembaga($scoped)
            ->latest('tanggal_ujian')
            ->get();

        $sesiJams = UjianSesiJam::active()
            ->forLembaga($scoped)
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get();

        if ($sesiJams->isEmpty() && $scoped) {
            $sesiJams = UjianSesiJam::active()
                ->whereNull('lembaga_id')
                ->orderBy('urutan')
                ->orderBy('jam_mulai')
                ->get();
        }

        return view('ujian.sesi-index', compact('kelases', 'mapels', 'gurus', 'ujians', 'sesiJams'));
    }

    /**
     * Tampilan Mode Layar Penuh (Proyektor Kelas) dengan Timer per Nomor Soal
     */
    public function proyektor(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $ujian->load(['mapel', 'kelas', 'guru', 'soals']);

        $soalsPg = $ujian->soals->filter(fn ($s) => ($s->tipe_soal ?? 'pg') !== 'essay')->values();
        $soalsEssay = $ujian->soals->filter(fn ($s) => ($s->tipe_soal ?? 'pg') === 'essay')->values();

        // Fallback PG jika belum ada butir soal PG di tabel ujian_soal
        if ($soalsPg->isEmpty() && $ujian->hasPg()) {
            $dummyPg = [];
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $dummyPg[] = new UjianSoal([
                    'id'          => $no,
                    'tipe_soal'   => 'pg',
                    'nomor_soal'  => $no,
                    'pertanyaan'  => "Soal Pilihan Ganda nomor {$no}. Silakan kerjakan dan hitamkan bulatan jawaban pada lembar jawab LJK Anda.",
                    'opsi_a'      => 'Pilihan A',
                    'opsi_b'      => 'Pilihan B',
                    'opsi_c'      => 'Pilihan C',
                    'opsi_d'      => 'Pilihan D',
                    'opsi_e'      => $ujian->jumlah_opsi == 5 ? 'Pilihan E' : null,
                    'timer_detik' => $ujian->timer_default ?: 90,
                ]);
            }
            $soalsPg = collect($dummyPg);
        }

        // Fallback Essay jika belum ada butir soal Essay di tabel ujian_soal
        if ($soalsEssay->isEmpty() && $ujian->hasEssay()) {
            $dummyEssay = [];
            for ($no = 1; $no <= $ujian->jumlah_soal_essay; $no++) {
                $dummyEssay[] = new UjianSoal([
                    'id'          => 10000 + $no,
                    'tipe_soal'   => 'essay',
                    'nomor_soal'  => $no,
                    'pertanyaan'  => "Soal Essay nomor {$no}. Silakan tuliskan jawaban uraian Anda pada kolom Jawaban Essay di Lembar Jawab.",
                    'timer_detik' => $ujian->timer_essay_default ?: 180,
                ]);
            }
            $soalsEssay = collect($dummyEssay);
        }

        $soals = $soalsPg->concat($soalsEssay)->values();
        $activeSlot = $request->input('jam_ujian', 'Jam Sesi Ujian');

        return view('ujian.proyektor', compact('ujian', 'soals', 'activeSlot'));
    }

    // =========================================================================
    // 4. SUB MENU: KOREKSI UJIAN (OMR + QR CODE BATCH SCAN)
    // =========================================================================

    /**
     * Helper daftar siswa kelas untuk ujian tertentu (aktif)
     */
    protected function getSiswasForUjian(?Ujian $ujian, ?Lembaga $scoped)
    {
        $siswasKelas = collect();
        if ($ujian && $ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $q = Siswa::where(function ($sub) use ($namaKelas) {
                $sub->where('kelas', $namaKelas)
                    ->orWhereHas('pendaftaran', fn ($sq) => $sq->where('kelas', $namaKelas));
            })->where('status', 'aktif');
            if ($scoped) {
                $q->forLembaga($scoped->kode);
            }
            $siswasKelas = $q->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nisn']);
        }
        if ($siswasKelas->isEmpty()) {
            $fallback = Siswa::where('status', 'aktif');
            if ($scoped) {
                $fallback->forLembaga($scoped->kode);
            }
            $siswasKelas = $fallback->orderBy('nama_lengkap')->take(100)->get(['id', 'nama_lengkap', 'nisn']);
        }

        return $siswasKelas;
    }

    /**
     * Sub menu 4: Halaman Koreksi LJK & Input/Upload Nilai Essay
     */
    public function koreksiIndex(Request $request)
    {
        $scoped = $this->scopedFormal();
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('hasils')
            ->forLembaga($scoped)
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? $ujians->firstWhere('id', (int) $selectedUjianId) : null;

        $siswasKelas = $this->getSiswasForUjian($ujian, $scoped);
        $hasilsBySiswa = $ujian
            ? UjianHasil::where('ujian_id', $ujian->id)->get()->keyBy('siswa_id')
            : collect();
        $activeTab = $request->input('tab', ($ujian && !$ujian->hasPg() && $ujian->hasEssay()) ? 'essay' : 'omr');

        return view('ujian.upload-scan', compact('ujians', 'ujian', 'siswasKelas', 'hasilsBySiswa', 'activeTab'));
    }

    /**
     * Upload scan LJK & Input/Upload Nilai Essay untuk satu ujian
     */
    public function uploadScan(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $scoped = $this->scopedFormal();
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils')->forLembaga($scoped)->latest('tanggal_ujian')->get();

        $siswasKelas = $this->getSiswasForUjian($ujian, $scoped);
        $hasilsBySiswa = UjianHasil::where('ujian_id', $ujian->id)->get()->keyBy('siswa_id');
        $activeTab = $request->input('tab', (!$ujian->hasPg() && $ujian->hasEssay()) ? 'essay' : 'omr');

        return view('ujian.upload-scan', compact('ujians', 'ujian', 'siswasKelas', 'hasilsBySiswa', 'activeTab'));
    }

    /**
     * Proses Pemindaian File PDF / Gambar (Sequential Batch Processing & Per-Page Client-Rendered JPEG)
     */
    public function processScan(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        // Tangani kasus ukuran file melebihi batas upload PHP server sebelum validasi Laravel
        $rawError = $_FILES['file_scan']['error'] ?? UPLOAD_ERR_OK;
        if (in_array($rawError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $maxUpload = ini_get('upload_max_filesize') ?: '2M';
            return response()->json([
                'success' => false,
                'message' => "Ukuran file melebihi batas upload server ({$maxUpload}). Silakan muat ulang halaman (Ctrl+F5) agar fitur kompresi otomatis per halaman aktif.",
            ], 422);
        }

        $request->validate([
            'file_scan'   => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:51200',
            'page_number' => 'nullable|integer|min:1|max:500',
            'qr_payload'  => 'nullable|string|max:1000',
        ], [
            'file_scan.required' => 'Berkas hasil scan wajib dipilih.',
            'file_scan.uploaded' => 'Berkas gagal diunggah ke server. Pastikan ukuran berkas tidak melebihi batas upload server (' . (ini_get('upload_max_filesize') ?: '2M') . ').',
            'file_scan.mimes'    => 'Format berkas harus berupa PDF, JPG, JPEG, atau PNG.',
            'file_scan.max'      => 'Ukuran berkas maksimal 50 MB.',
        ]);

        $file = $request->file('file_scan');
        $tempPath = $file->getRealPath();
        $pageNumber = max(1, (int) $request->input('page_number', 1));
        $qrPayload = $request->input('qr_payload');

        $scanner = new OmrScannerService();

        try {
            $result = $scanner->processFile($ujian, $tempPath, null, $pageNumber, is_string($qrPayload) ? $qrPayload : null);

            return response()->json([
                'success' => true,
                'message' => "Pemindaian selesai: {$result['total_halaman']} halaman diproses ({$result['berhasil']} siswa berhasil dicocokkan, {$result['perlu_cek']} perlu cek manual).",
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simpan input langsung Nilai Essay (0-100) oleh Guru per siswa dan hitung ulang Nilai Akhir
     */
    public function saveNilaiEssay(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $scoped = $this->scopedFormal();

        $request->validate([
            'nilai_essay'   => 'required|array',
            'nilai_essay.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $inputs = $request->input('nilai_essay', []);
        $savedCount = 0;

        DB::transaction(function () use ($ujian, $scoped, $inputs, &$savedCount) {
            foreach ($inputs as $siswaIdRaw => $valRaw) {
                $siswaId = (int) $siswaIdRaw;
                if ($siswaId <= 0) {
                    continue;
                }

                $siswaQuery = Siswa::whereKey($siswaId);
                if ($scoped) {
                    $siswaQuery->forLembaga($scoped->kode);
                }
                if (!$siswaQuery->exists()) {
                    continue;
                }

                $existing = UjianHasil::where('ujian_id', $ujian->id)
                    ->where('siswa_id', $siswaId)
                    ->first();

                $isBlank = ($valRaw === null || trim((string) $valRaw) === '');

                if ($isBlank) {
                    if ($existing && $existing->nilai_essay !== null) {
                        $nilaiPg = $existing->nilai_pg !== null ? (float) $existing->nilai_pg : 0.0;
                        $existing->update([
                            'nilai_essay' => null,
                            'nilai_akhir' => $ujian->hitungNilaiAkhir($nilaiPg, null),
                        ]);
                        $savedCount++;
                    }
                    continue;
                }

                $nilaiEssay = round((float) str_replace(',', '.', (string) $valRaw), 2);
                $nilaiEssay = max(0.0, min(100.0, $nilaiEssay));

                if ($existing) {
                    $nilaiPg = $existing->nilai_pg !== null ? (float) $existing->nilai_pg : 0.0;
                    $existing->update([
                        'nilai_pg'    => $nilaiPg,
                        'nilai_essay' => $nilaiEssay,
                        'nilai_akhir' => $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay),
                    ]);
                } else {
                    $nilaiPg = 0.0;
                    UjianHasil::create([
                        'ujian_id'      => $ujian->id,
                        'siswa_id'      => $siswaId,
                        'jawaban_siswa' => [],
                        'jumlah_benar'  => 0,
                        'jumlah_salah'  => 0,
                        'jumlah_kosong' => (int) $ujian->jumlah_soal,
                        'nilai_pg'      => $nilaiPg,
                        'nilai_essay'   => $nilaiEssay,
                        'nilai_akhir'   => $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay),
                        'status_scan'   => 'valid',
                        'catatan_scan'  => $ujian->hasPg()
                            ? 'Nilai Essay diinput Guru (LJK PG belum discan).'
                            : 'Nilai Essay diinput oleh Guru.',
                        'waktu_scan'    => now(),
                    ]);
                }

                $savedCount++;
            }
        });

        return redirect()->route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay'])
            ->with('success', "Nilai Essay untuk {$savedCount} siswa berhasil disimpan dan Nilai Akhir otomatis diperbarui.");
    }

    /**
     * Unduh Template Excel (.xlsx) untuk Upload Nilai Essay oleh Guru
     */
    public function downloadTemplateNilaiEssay(Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $ujian->load(['mapel', 'kelas']);

        $siswas = $this->getSiswasForUjian($ujian, $this->scopedFormal());
        $hasilsBySiswa = UjianHasil::where('ujian_id', $ujian->id)->get()->keyBy('siswa_id');
        $weights = $ujian->getEffectiveWeights();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Essay');

        $sheet->setCellValue('A1', 'TEMPLATE UPLOAD NILAI ESSAY UJIAN');
        $sheet->setCellValue('A2', 'Ujian: ' . $ujian->nama_ujian . ' | Kelas: ' . ($ujian->kelas?->nama_kelas ?? '-') . ' | Mapel: ' . ($ujian->mapel?->mata_pelajaran ?? '-'));
        $sheet->setCellValue('A3', "Bobot Penilaian: PG {$weights['pg']}% | Essay {$weights['essay']}% — Isi kolom Nilai Essay dengan angka 0 s.d. 100, jangan ubah ID Siswa.");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->getColor()->setRGB('475569');

        $headers = ['ID Siswa', 'NISN', 'Nama Siswa', 'Nilai PG', 'Nilai Essay (0-100)'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0052FF');
            $sheet->getStyle($col . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $row = 6;
        foreach ($siswas as $siswa) {
            $hasil = $hasilsBySiswa->get($siswa->id);
            $sheet->setCellValue('A' . $row, $siswa->id);
            $sheet->setCellValueExplicit('B' . $row, (string) ($siswa->nisn ?: '-'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $siswa->nama_lengkap);
            $sheet->setCellValue('D' . $row, $hasil && $hasil->nilai_pg !== null ? (float) $hasil->nilai_pg : '');
            $sheet->setCellValue('E' . $row, $hasil && $hasil->nilai_essay !== null ? (float) $hasil->nilai_essay : '');

            $sheet->getStyle('A' . $row . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row . ':E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
            $row++;
        }

        foreach (range('A', 'E') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $ujian->nama_ujian);
        $filename = 'Template_Nilai_Essay_' . trim($safeName, '_') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Upload file Excel/CSV Nilai Essay dari Guru dan hitung ulang Nilai Akhir
     */
    public function uploadNilaiEssay(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $scoped = $this->scopedFormal();

        $request->validate([
            'file_essay' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file_essay.required' => 'Silakan pilih file Excel/CSV nilai essay terlebih dahulu.',
            'file_essay.mimes'    => 'Format file harus .xlsx, .xls, atau .csv.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file_essay')->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return redirect()->route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay'])
                ->with('error', 'Gagal membaca file Excel: ' . $e->getMessage());
        }

        // Deteksi baris header otomatis
        $headerIdx = null;
        $colId = null;
        $colNisn = null;
        $colNama = null;
        $colEssay = null;

        foreach ($rows as $idx => $r) {
            if ($idx > 15) {
                break;
            }
            $normalized = array_map(fn ($v) => strtolower(trim((string) $v)), $r);
            foreach ($normalized as $cIdx => $label) {
                if ($label === '') {
                    continue;
                }
                if ($colId === null && (str_contains($label, 'id siswa') || $label === 'id')) {
                    $colId = $cIdx;
                } elseif ($colNisn === null && (str_contains($label, 'nisn') || $label === 'nis')) {
                    $colNisn = $cIdx;
                } elseif ($colNama === null && str_contains($label, 'nama')) {
                    $colNama = $cIdx;
                } elseif ($colEssay === null && (str_contains($label, 'essay') || str_contains($label, 'esai') || str_contains($label, 'uraian'))) {
                    $colEssay = $cIdx;
                }
            }
            if ($colEssay !== null && ($colId !== null || $colNisn !== null || $colNama !== null)) {
                $headerIdx = $idx;
                break;
            }
            $colId = $colNisn = $colNama = $colEssay = null;
        }

        if ($headerIdx === null || $colEssay === null) {
            return redirect()->route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay'])
                ->with('error', 'Header kolom tidak dikenali. Gunakan tombol "Unduh Template Excel" agar format kolom (ID Siswa, NISN, Nama Siswa, Nilai Essay) sesuai.');
        }

        // Indeks siswa di kelas/room agar pencocokan cepat dan aman lintas lembaga
        $allSiswaQuery = Siswa::query();
        if ($scoped) {
            $allSiswaQuery->forLembaga($scoped->kode);
        }
        $allSiswas = $allSiswaQuery->get(['id', 'nama_lengkap', 'nisn']);
        $byId = $allSiswas->keyBy('id');
        $byNisn = [];
        $byNama = [];
        foreach ($allSiswas as $sw) {
            $cleanNisn = preg_replace('/\D+/', '', (string) ($sw->nisn ?? ''));
            if ($cleanNisn !== '') {
                $byNisn[$cleanNisn] = $sw;
            }
            $cleanNama = strtolower(trim(preg_replace('/\s+/', ' ', (string) $sw->nama_lengkap)));
            if ($cleanNama !== '') {
                $byNama[$cleanNama] = $sw;
            }
        }

        $imported = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $headerIdx, $colId, $colNisn, $colNama, $colEssay, $byId, $byNisn, $byNama, $ujian, &$imported, &$skipped) {
            for ($i = $headerIdx + 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $rawScore = $r[$colEssay] ?? null;

                if ($rawScore === null || trim((string) $rawScore) === '') {
                    continue;
                }

                $scoreStr = str_replace(',', '.', trim((string) $rawScore));
                if (!is_numeric($scoreStr)) {
                    $skipped++;
                    continue;
                }

                $nilaiEssay = round((float) $scoreStr, 2);
                if ($nilaiEssay < 0 || $nilaiEssay > 100) {
                    $skipped++;
                    continue;
                }

                $siswa = null;
                if ($colId !== null && !empty($r[$colId])) {
                    $siswa = $byId->get((int) $r[$colId]);
                }
                if (!$siswa && $colNisn !== null && !empty($r[$colNisn])) {
                    $cleanNisn = preg_replace('/\D+/', '', (string) $r[$colNisn]);
                    $siswa = $byNisn[$cleanNisn] ?? null;
                }
                if (!$siswa && $colNama !== null && !empty($r[$colNama])) {
                    $cleanNama = strtolower(trim(preg_replace('/\s+/', ' ', (string) $r[$colNama])));
                    $siswa = $byNama[$cleanNama] ?? null;
                }

                if (!$siswa) {
                    $skipped++;
                    continue;
                }

                $existing = UjianHasil::where('ujian_id', $ujian->id)
                    ->where('siswa_id', $siswa->id)
                    ->first();

                if ($existing) {
                    $nilaiPg = $existing->nilai_pg !== null ? (float) $existing->nilai_pg : 0.0;
                    $existing->update([
                        'nilai_pg'    => $nilaiPg,
                        'nilai_essay' => $nilaiEssay,
                        'nilai_akhir' => $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay),
                    ]);
                } else {
                    $nilaiPg = 0.0;
                    UjianHasil::create([
                        'ujian_id'      => $ujian->id,
                        'siswa_id'      => $siswa->id,
                        'jawaban_siswa' => [],
                        'jumlah_benar'  => 0,
                        'jumlah_salah'  => 0,
                        'jumlah_kosong' => (int) $ujian->jumlah_soal,
                        'nilai_pg'      => $nilaiPg,
                        'nilai_essay'   => $nilaiEssay,
                        'nilai_akhir'   => $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay),
                        'status_scan'   => 'valid',
                        'catatan_scan'  => $ujian->hasPg()
                            ? 'Nilai Essay diupload Excel (LJK PG belum discan).'
                            : 'Nilai Essay diupload via Excel oleh Guru.',
                        'waktu_scan'    => now(),
                    ]);
                }

                $imported++;
            }
        });

        if ($imported === 0) {
            return redirect()->route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay'])
                ->with('error', 'Tidak ada nilai essay valid yang dapat diimpor. Pastikan kolom Nilai Essay berisi angka 0-100 dan nama/ID siswa cocok.');
        }

        $msg = "Berhasil mengupload Nilai Essay untuk {$imported} siswa dan menghitung ulang Nilai Akhir.";
        if ($skipped > 0) {
            $msg .= " ({$skipped} baris dilewati karena nilai tidak valid atau siswa tidak ditemukan.)";
        }

        return redirect()->route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay'])
            ->with('success', $msg);
    }

    // =========================================================================
    // 5. SUB MENU: NILAI UJIAN & ANALISIS BUTIR SOAL
    // =========================================================================

    /**
     * Sub menu 5: Rekapitulasi Nilai & Analisis Butir Soal
     */
    public function nilaiIndex(Request $request)
    {
        $scoped = $this->scopedFormal();
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('hasils')
            ->forLembaga($scoped)
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? $ujians->firstWhere('id', (int) $selectedUjianId) : null;

        if (!$ujian && $ujians->isNotEmpty()) {
            $ujian = $ujians->first();
        }

        if (!$ujian) {
            return view('ujian.hasil-empty', compact('ujians'));
        }

        return $this->hasil($ujian);
    }

    /**
     * Dashboard Rekap Nilai Ujian Tertentu
     */
    public function hasil(Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $scoped = $this->scopedFormal();
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils')->forLembaga($scoped)->latest('tanggal_ujian')->get();
        $ujian->load(['mapel', 'kelas', 'guru', 'kuncis']);

        $hasils = UjianHasil::with('siswa')
            ->where('ujian_id', $ujian->id)
            ->join('siswas', 'siswas.id', '=', 'ujian_hasil.siswa_id')
            ->orderBy('siswas.nama_lengkap')
            ->select('ujian_hasil.*')
            ->paginate(30);

        $kuncis = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $statistik = $ujian->statistik;
        $analisis = $ujian->analisis_soal;

        $siswaList = collect();
        if ($ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $q = Siswa::where(function ($sub) use ($namaKelas) {
                $sub->where('kelas', $namaKelas)
                    ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            });
            if ($scoped) {
                $q->forLembaga($scoped->kode);
            }
            $siswaList = $q->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        }
        if ($siswaList->isEmpty()) {
            $fallback = Siswa::query();
            if ($scoped) {
                $fallback->forLembaga($scoped->kode);
            }
            $siswaList = $fallback->orderBy('nama_lengkap')->take(100)->get(['id', 'nama_lengkap']);
        }

        return view('ujian.hasil', compact('ujians', 'ujian', 'hasils', 'kuncis', 'statistik', 'analisis', 'siswaList'));
    }

    /**
     * Update Koreksi Manual oleh Guru
     */
    public function updateHasilManual(Request $request, Ujian $ujian, UjianHasil $hasil)
    {
        $this->ensureUjianInRoom($ujian);
        if ($request->filled('siswa_id')) {
            $newSiswaId = (int) $request->input('siswa_id');
            if ($newSiswaId > 0 && $newSiswaId !== (int) $hasil->siswa_id) {
                UjianHasil::where('ujian_id', $ujian->id)
                    ->where('siswa_id', $newSiswaId)
                    ->where('id', '!=', $hasil->id)
                    ->delete();
                $hasil->siswa_id = $newSiswaId;
            }
        }

        $currentAnswers = $hasil->jawaban_siswa ?? [];

        if ($request->has('jawaban') && is_array($request->input('jawaban'))) {
            $inputAnswers = $request->input('jawaban');
            foreach ($inputAnswers as $no => $ans) {
                $noInt = (int) $no;
                if ($noInt >= 1 && $noInt <= $ujian->jumlah_soal) {
                    $ansClean = trim((string) $ans);
                    $currentAnswers[$noInt] = $ansClean !== '' ? strtoupper($ansClean) : null;
                }
            }
        } elseif ($request->filled('nomor_soal') && $ujian->jumlah_soal > 0) {
            $validated = $request->validate([
                'nomor_soal' => 'required|integer|min:1|max:' . $ujian->jumlah_soal,
                'jawaban'    => 'nullable|string|in:A,B,C,D,E,GANDA,null',
            ]);

            $nomor = $validated['nomor_soal'];
            $jawabanBaru = $validated['jawaban'] === 'null' ? null : $validated['jawaban'];
            $currentAnswers[$nomor] = $jawabanBaru;
        }

        $nilaiEssay = $hasil->nilai_essay !== null ? (float) $hasil->nilai_essay : null;
        if ($request->has('nilai_essay')) {
            $rawEs = $request->input('nilai_essay');
            if ($rawEs === null || trim((string) $rawEs) === '') {
                $nilaiEssay = null;
            } else {
                $request->validate(['nilai_essay' => 'numeric|min:0|max:100']);
                $nilaiEssay = round((float) $rawEs, 2);
            }
        }

        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
        $totalSkorMaks = array_sum($bobotMap) ?: ($ujian->jumlah_soal * ($ujian->bobot_benar ?: 1.0));

        $benar = 0;
        $salah = 0;
        $kosong = 0;
        $totalDidapat = 0.0;
        $hasGanda = false;

        for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
            $jawab = $currentAnswers[$no] ?? null;
            $kunci = $kunciMap[$no] ?? null;
            $b = (float) ($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);

            if ($jawab === 'GANDA') {
                $hasGanda = true;
                $salah++;
            } elseif ($jawab === null) {
                $kosong++;
            } elseif ($kunci && strtoupper($jawab) === strtoupper($kunci)) {
                $benar++;
                $totalDidapat += $b;
            } else {
                $salah++;
            }
        }

        $nilaiPg = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;
        $nilaiAkhir = $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay);

        $hasil->update([
            'siswa_id'      => $hasil->siswa_id,
            'jawaban_siswa' => $currentAnswers,
            'jumlah_benar'  => $benar,
            'jumlah_salah'  => $salah,
            'jumlah_kosong' => $kosong,
            'nilai_pg'      => $nilaiPg,
            'nilai_essay'   => $nilaiEssay,
            'nilai_akhir'   => $nilaiAkhir,
            'status_scan'   => $hasGanda ? 'perlu_cek_manual' : 'valid',
            'catatan_scan'  => $hasGanda ? 'Masih terdapat jawaban ganda.' : 'Telah diverifikasi manual oleh Guru.',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Jawaban berhasil diperbarui. Nilai akhir: $nilaiAkhir",
                'data'    => $hasil->fresh(),
            ]);
        }

        return redirect()->route('ujian.hasil', $ujian->id)->with('success', 'Hasil koreksi siswa berhasil diperbarui.');
    }

    /**
     * Hubungkan atau ubah siswa hasil scan secara manual
     */
    public function assignHasilSiswa(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $validated = $request->validate([
            'hasil_id'      => 'nullable|integer',
            'siswa_id'      => 'required|exists:siswas,id',
            'jawaban'       => 'nullable|array',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $scoped = $this->scopedFormal();
        if ($scoped && !Siswa::forLembaga($scoped->kode)->whereKey($siswa->id)->exists()) {
            abort(403, 'Siswa tersebut bukan milik lembaga ruang kerja Anda.');
        }
        $hasil = null;

        if (!empty($validated['hasil_id'])) {
            $hasil = UjianHasil::where('ujian_id', $ujian->id)->find($validated['hasil_id']);
        }

        if ($hasil) {
            UjianHasil::where('ujian_id', $ujian->id)
                ->where('siswa_id', $siswa->id)
                ->where('id', '!=', $hasil->id)
                ->delete();

            $hasil->siswa_id = $siswa->id;
            $hasil->status_scan = 'valid';
            $hasil->catatan_scan = 'Telah diverifikasi manual oleh Guru (' . $siswa->nama_lengkap . ')';
            $hasil->save();
        } else {
            $currentAnswers = $validated['jawaban'] ?? [];
            $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
            $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
            $totalSkorMaks = array_sum($bobotMap) ?: ($ujian->jumlah_soal * ($ujian->bobot_benar ?: 1.0));

            $benar = 0; $salah = 0; $kosong = 0; $totalDidapat = 0.0;
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $ans = $currentAnswers[$no] ?? null;
                $k = $kunciMap[$no] ?? null;
                $w = (float)($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);
                if ($ans === null) {
                    $kosong++;
                } elseif ($k && strtoupper($ans) === strtoupper($k)) {
                    $benar++;
                    $totalDidapat += $w;
                } else {
                    $salah++;
                }
            }
            $nilaiPg = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;
            $existing = UjianHasil::where('ujian_id', $ujian->id)->where('siswa_id', $siswa->id)->first();
            $existingEssay = ($existing && $existing->nilai_essay !== null) ? (float) $existing->nilai_essay : null;
            $nilaiAkhir = $ujian->hitungNilaiAkhir($nilaiPg, $existingEssay);

            $hasil = UjianHasil::updateOrCreate(
                [
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                ],
                [
                    'jawaban_siswa' => $currentAnswers,
                    'jumlah_benar'  => $benar,
                    'jumlah_salah'  => $salah,
                    'jumlah_kosong' => $kosong,
                    'nilai_pg'      => $nilaiPg,
                    'nilai_akhir'   => $nilaiAkhir,
                    'status_scan'   => 'valid',
                    'catatan_scan'  => 'Telah diverifikasi manual oleh Guru (' . $siswa->nama_lengkap . ')',
                    'waktu_scan'    => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil dihubungkan: ' . $siswa->nama_lengkap,
            'data'    => [
                'hasil_id'    => $hasil->id,
                'siswa_id'    => $siswa->id,
                'nama_siswa'  => $siswa->nama_lengkap,
                'status_scan' => 'valid',
                'nilai_akhir' => $hasil->nilai_akhir,
            ],
        ]);
    }

    /**
     * Hapus data hasil ujian seorang siswa
     */
    public function destroyHasil(Ujian $ujian, UjianHasil $hasil)
    {
        $this->ensureUjianInRoom($ujian);
        if ($hasil->ujian_id !== $ujian->id) {
            abort(404);
        }
        $hasil->delete();

        return redirect()->route('ujian.hasil', $ujian->id)
            ->with('success', 'Data hasil scan siswa berhasil dihapus.');
    }

    /**
     * Export Hasil Nilai ke Excel (.xlsx)
     */
    public function exportExcel(Request $request, Ujian $ujian)
    {
        $this->ensureUjianInRoom($ujian);
        $ujian->load(['mapel', 'kelas', 'guru']);
        $hasils = UjianHasil::with('siswa')
            ->where('ujian_id', $ujian->id)
            ->join('siswas', 'siswas.id', '=', 'ujian_hasil.siswa_id')
            ->orderBy('siswas.nama_lengkap')
            ->select('ujian_hasil.*')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil Ujian');

        $hasEssay = $ujian->hasEssay();
        $weights = $ujian->getEffectiveWeights();

        $sheet->setCellValue('A1', 'YAYASAN PONDOK PESANTREN AL-MUTTAQIN');
        $sheet->setCellValue('A2', 'REKAPITULASI HASIL UJIAN & KOREKSI LJK');
        $sheet->setCellValue('A4', 'Nama Ujian: ' . $ujian->nama_ujian);
        $sheet->setCellValue('A5', 'Mata Pelajaran: ' . ($ujian->mapel?->mata_pelajaran ?? '-'));
        $sheet->setCellValue('D4', 'Kelas: ' . ($ujian->kelas?->nama_kelas ?? '-'));
        $sheet->setCellValue('D5', 'Tanggal: ' . $ujian->tanggal_ujian?->format('d/m/Y'));
        if ($hasEssay) {
            $sheet->setCellValue('G4', 'Komposisi: ' . $ujian->ringkasan_soal);
            $sheet->setCellValue('G5', "Bobot: PG {$weights['pg']}% | Essay {$weights['essay']}%");
        }

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A4:G5')->getFont()->setBold(true);

        if ($hasEssay) {
            $headers = [
                'No',
                'NISN',
                'Nama Siswa',
                'Benar PG',
                'Salah PG',
                'Kosong PG',
                "Nilai PG ({$weights['pg']}%)",
                "Nilai Essay ({$weights['essay']}%)",
                'Nilai Akhir',
                'Status Koreksi',
                'Catatan / Rincian',
            ];
            $lastCol = 'K';
        } else {
            $headers = [
                'No',
                'NISN',
                'Nama Siswa',
                'Jumlah Benar',
                'Jumlah Salah',
                'Jumlah Kosong',
                'Nilai Akhir',
                'Status Koreksi',
                'Catatan / Rincian',
            ];
            $lastCol = 'I';
        }

        $col = 'A';
        $row = 7;
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0052FF');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        $no = 1;
        $row = 8;
        foreach ($hasils as $h) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $h->siswa?->nisn ?? '-');
            $sheet->setCellValue('C' . $row, $h->siswa?->nama_lengkap ?? '-');
            $sheet->setCellValue('D' . $row, $h->jumlah_benar);
            $sheet->setCellValue('E' . $row, $h->jumlah_salah);
            $sheet->setCellValue('F' . $row, $h->jumlah_kosong);

            if ($hasEssay) {
                $sheet->setCellValue('G' . $row, $h->nilai_pg !== null ? (float) $h->nilai_pg : 0);
                $sheet->setCellValue('H' . $row, $h->nilai_essay !== null ? (float) $h->nilai_essay : '-');
                $sheet->setCellValue('I' . $row, (float) $h->nilai_akhir);
                $sheet->setCellValue('J' . $row, $h->status_scan === 'valid' ? 'Valid' : 'Perlu Cek');
                $sheet->setCellValue('K' . $row, $h->catatan_scan ?? '-');

                $sheet->getStyle('A' . $row . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row . ':J' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if ($h->status_scan !== 'valid') {
                    $sheet->getStyle('J' . $row)->getFont()->getColor()->setRGB('D97706');
                }
            } else {
                $sheet->setCellValue('G' . $row, (float) $h->nilai_akhir);
                $sheet->setCellValue('H' . $row, $h->status_scan === 'valid' ? 'Valid' : 'Perlu Cek');
                $sheet->setCellValue('I' . $row, $h->catatan_scan ?? '-');

                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if ($h->status_scan !== 'valid') {
                    $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('D97706');
                }
            }

            $no++;
            $row++;
        }

        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'Hasil_Ujian_' . str_replace(' ', '_', $ujian->nama_ujian) . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Hitung ulang skor seluruh siswa
     */
    private function recalculateAllScores(Ujian $ujian): void
    {
        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
        $totalSkorMaks = array_sum($bobotMap) ?: (count($kunciMap) * ($ujian->bobot_benar ?: 1.0));

        foreach ($ujian->hasils as $hasil) {
            $jawaban = $hasil->jawaban_siswa ?: [];
            $benar = 0;
            $salah = 0;
            $kosong = 0;
            $totalDidapat = 0.0;

            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $jawab = $jawaban[$no] ?? null;
                $kunci = $kunciMap[$no] ?? null;
                $b = (float)($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);

                if ($jawab === null) {
                    $kosong++;
                } elseif ($kunci && strtoupper($jawab) === strtoupper($kunci)) {
                    $benar++;
                    $totalDidapat += $b;
                } else {
                    $salah++;
                }
            }

            $nilaiPg = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;
            $nilaiEssay = $hasil->nilai_essay !== null ? (float) $hasil->nilai_essay : null;
            $nilaiAkhir = $ujian->hitungNilaiAkhir($nilaiPg, $nilaiEssay);

            $hasil->update([
                'jumlah_benar'  => $benar,
                'jumlah_salah'  => $salah,
                'jumlah_kosong' => $kosong,
                'nilai_pg'      => $nilaiPg,
                'nilai_akhir'   => $nilaiAkhir,
            ]);
        }
    }
}
