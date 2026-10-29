<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PlottingKamarController extends Controller
{
    /**
     * Helper fleksibel untuk mencocokkan nama kamar (mengabaikan huruf besar/kecil & awalan "Kamar ").
     */
    public static function isSameKamar(?string $a, ?string $b): bool
    {
        $rawA = trim((string) $a);
        $rawB = trim((string) $b);

        if ($rawA === '' || $rawA === '-' || $rawB === '' || $rawB === '-') {
            return false;
        }

        if (mb_strtolower($rawA) === mb_strtolower($rawB)) {
            return true;
        }

        $cleanA = trim((string) preg_replace('/^kamar\s+/iu', '', $rawA));
        $cleanB = trim((string) preg_replace('/^kamar\s+/iu', '', $rawB));

        return $cleanA !== '' && mb_strtolower($cleanA) === mb_strtolower($cleanB);
    }

    /**
     * Format label tampilan nama kamar.
     */
    public static function formatDisplayName(string $namaKamar): string
    {
        $clean = trim($namaKamar);
        if (str_starts_with(mb_strtolower($clean), 'kamar ')) {
            return $clean;
        }

        return 'Kamar ' . $clean;
    }

    /**
     * Tebak kategori asrama (Putra / Putri / Umum) dari nama kamar & data santri penghuninya.
     */
    public static function inferKategoriAsrama(string $namaKamar, ?Collection $anggota = null): string
    {
        $lower = mb_strtolower(trim($namaKamar));

        $putriKeywords = ['putri', 'banat', 'khadijah', 'aisyah', 'fathimah', 'fatimah', 'hafshah', 'zainab', 'ruqayyah', 'ummu'];
        foreach ($putriKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return 'Putri';
            }
        }

        $putraKeywords = ['putra', 'banin', 'abu bakar', 'umar', 'utsman', 'ali', 'bilal', 'khalid', 'hamzah'];
        foreach ($putraKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                return 'Putra';
            }
        }

        if ($anggota && $anggota->isNotEmpty()) {
            $genders = $anggota->map(fn ($s) => strtolower((string) ($s->pendaftaran?->jenis_kelamin ?? '')))
                ->filter()
                ->unique()
                ->values();

            if ($genders->count() > 1) {
                return 'Umum';
            }
            if ($genders->first() === 'perempuan') {
                return 'Putri';
            }
            if ($genders->first() === 'laki-laki') {
                return 'Putra';
            }
        }

        if (str_contains($lower, 'reguler') || str_contains($lower, 'umum')) {
            return 'Umum';
        }

        return 'Putra';
    }

    /**
     * Seed daftar kamar standar pondok (hanya dijalankan saat tombol Muat Standar diklik).
     */
    public static function seedStandar(?Lembaga $pondok = null): int
    {
        $standarKamars = [
            ['nama_kamar' => 'Reguler',   'kategori_asrama' => 'Umum',  'urutan' => 1],
            ['nama_kamar' => 'Abu Bakar', 'kategori_asrama' => 'Putra', 'urutan' => 2],
            ['nama_kamar' => 'Umar',      'kategori_asrama' => 'Putra', 'urutan' => 3],
            ['nama_kamar' => 'Utsman',    'kategori_asrama' => 'Putra', 'urutan' => 4],
            ['nama_kamar' => 'Ali',       'kategori_asrama' => 'Putra', 'urutan' => 5],
            ['nama_kamar' => 'Khadijah',  'kategori_asrama' => 'Putri', 'urutan' => 6],
            ['nama_kamar' => 'Aisyah',    'kategori_asrama' => 'Putri', 'urutan' => 7],
            ['nama_kamar' => 'Fathimah',  'kategori_asrama' => 'Putri', 'urutan' => 8],
        ];

        $existingKamars = Kamar::all();
        $added = 0;

        foreach ($standarKamars as $item) {
            $exists = $existingKamars->first(fn ($k) => self::isSameKamar($k->nama_kamar, $item['nama_kamar']));
            if (!$exists) {
                $created = Kamar::create([
                    'lembaga_id'      => $pondok?->id,
                    'nama_kamar'      => $item['nama_kamar'],
                    'kategori_asrama' => $item['kategori_asrama'],
                    'urutan'          => $item['urutan'],
                    'is_active'       => true,
                ]);
                $existingKamars->push($created);
                $added++;
            }
        }

        return $added;
    }

    /**
     * Normalisasi input siswa_id (mendukung single ID, array siswa_id[], maupun string dipisah koma).
     */
    protected function parseSiswaIds(mixed $raw): array
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
     * Tampilkan halaman Plotting Kamar & Manajemen Kamar Asrama Pondok.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'view') && !$authUser->canDo('pengaturan_pondok', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Kamar.');
        }

        $pondokLembaga = Lembaga::where('kode', 'pondok')->first();

        // Ambil seluruh santri aktif (mencakup Reguler, SMP, dan SMA karena seluruhnya bermukim di pondok)
        $allActiveSiswa = Siswa::where('status', 'aktif')
            ->with('pendaftaran')
            ->orderBy('nama_lengkap')
            ->get();

        $dbKamars = Kamar::orderBy('urutan')
            ->orderBy('nama_kamar')
            ->get();

        // Auto-sinkronisasi nama kamar yang sudah ada di tabel siswas ke tabel kamars
        foreach ($allActiveSiswa as $s) {
            $namaKamarSiswa = trim((string) ($s->kamar ?? ''));
            if ($namaKamarSiswa !== '' && $namaKamarSiswa !== '-') {
                $matched = $dbKamars->first(fn ($k) => self::isSameKamar($namaKamarSiswa, $k->nama_kamar));
                if (!$matched) {
                    $penghuniKamarIni = $allActiveSiswa->filter(fn ($item) => self::isSameKamar($item->kamar, $namaKamarSiswa));
                    $newKamar = Kamar::create([
                        'lembaga_id'      => $pondokLembaga?->id,
                        'nama_kamar'      => $namaKamarSiswa,
                        'kategori_asrama' => self::inferKategoriAsrama($namaKamarSiswa, $penghuniKamarIni),
                        'urutan'          => ($dbKamars->max('urutan') ?? 0) + 1,
                        'is_active'       => true,
                    ]);
                    $dbKamars->push($newKamar);
                }
            }
        }

        $dbKamars = Kamar::orderBy('urutan')
            ->orderBy('nama_kamar')
            ->get();

        // Filter opsional pencarian dari query string
        $filteredSiswa = $allActiveSiswa;
        if ($request->filled('search')) {
            $search = mb_strtolower(trim($request->search));
            $filteredSiswa = $allActiveSiswa->filter(function ($s) use ($search) {
                $nama = mb_strtolower((string) $s->nama_lengkap);
                $nisn = mb_strtolower((string) ($s->pendaftaran?->nisn ?? ''));
                $noInduk = mb_strtolower((string) ($s->pendaftaran?->no_induk ?? ''));
                $kamar = mb_strtolower((string) ($s->kamar ?? ''));

                return str_contains($nama, $search)
                    || str_contains($nisn, $search)
                    || str_contains($noInduk, $search)
                    || str_contains($kamar, $search);
            })->values();
        }

        $dataKamar = [];
        foreach ($dbKamars as $k) {
            $anggota = $filteredSiswa->filter(fn ($s) => self::isSameKamar($s->kamar, $k->nama_kamar))->values();

            $dataKamar[] = (object) [
                'id'                 => $k->id,
                'kamar'              => $k,
                'nama_kamar'         => $k->nama_kamar,
                'nama_kamar_display' => self::formatDisplayName($k->nama_kamar),
                'kategori_asrama'    => $k->kategori_asrama ?: 'Putra',
                'kapasitas'          => $k->kapasitas,
                'wali_kamar'         => $k->wali_kamar,
                'urutan'             => $k->urutan,
                'is_active'          => $k->is_active,
                'jumlah_siswa'       => $anggota->count(),
                'anggota'            => $anggota,
            ];
        }

        // Santri aktif yang belum memiliki kamar
        $belumTerdaftar = $filteredSiswa->filter(function ($s) {
            $kamarVal = trim((string) ($s->kamar ?? ''));
            return $kamarVal === '' || $kamarVal === '-';
        })->values();

        $activeTab = $request->get('tab', 'kamar');
        if (!in_array($activeTab, ['kamar', 'belum_terdaftar'], true)) {
            $activeTab = 'kamar';
        }

        return view('plotting-kamar.index', compact(
            'pondokLembaga',
            'dbKamars',
            'dataKamar',
            'belumTerdaftar',
            'activeTab'
        ));
    }

    /**
     * Buat kamar asrama baru dari halaman Plotting Kamar.
     */
    public function storeKamar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah kamar asrama.');
        }

        $validated = $request->validate([
            'nama_kamar'      => ['required', 'string', 'max:100', Rule::unique('kamars', 'nama_kamar')],
            'kategori_asrama' => ['required', 'in:Putra,Putri,Umum'],
            'kapasitas'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'wali_kamar'      => ['nullable', 'string', 'max:100'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
        ], [
            'nama_kamar.required' => 'Nama kamar wajib diisi.',
            'nama_kamar.unique'   => 'Nama kamar tersebut sudah terdaftar.',
        ]);

        $namaClean = trim($validated['nama_kamar']);

        // Cek duplikasi case-insensitive / awalan "Kamar "
        $duplicate = Kamar::all()->first(fn ($k) => self::isSameKamar($k->nama_kamar, $namaClean));
        if ($duplicate) {
            return redirect()->back()->with('error', "Kamar \"{$duplicate->nama_kamar}\" sudah terdaftar di sistem.");
        }

        $pondokLembaga = Lembaga::where('kode', 'pondok')->first();

        $kamar = Kamar::create([
            'lembaga_id'      => $pondokLembaga?->id,
            'nama_kamar'      => $namaClean,
            'kategori_asrama' => $validated['kategori_asrama'],
            'kapasitas'       => $validated['kapasitas'] ?? null,
            'wali_kamar'      => !empty($validated['wali_kamar']) ? trim($validated['wali_kamar']) : null,
            'urutan'          => $validated['urutan'] ?? ((Kamar::max('urutan') ?? 0) + 1),
            'is_active'       => true,
        ]);

        return redirect()->route('plotting-kamar.index', ['tab' => 'kamar'])
            ->with('success', "Kamar {$kamar->nama_kamar} berhasil ditambahkan dan siap ditempati santri.");
    }

    /**
     * Perbarui data kamar asrama & sinkronkan nama kamar pada data santri serta sesi RFID.
     */
    public function updateKamar(Request $request, Kamar $kamar)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah kamar asrama.');
        }

        $validated = $request->validate([
            'nama_kamar'      => ['required', 'string', 'max:100', Rule::unique('kamars', 'nama_kamar')->ignore($kamar->id)],
            'kategori_asrama' => ['required', 'in:Putra,Putri,Umum'],
            'kapasitas'       => ['nullable', 'integer', 'min:1', 'max:1000'],
            'wali_kamar'      => ['nullable', 'string', 'max:100'],
            'urutan'          => ['nullable', 'integer', 'min:0'],
        ], [
            'nama_kamar.unique' => 'Nama kamar tersebut sudah digunakan oleh kamar lain.',
        ]);

        $newName = trim($validated['nama_kamar']);
        $duplicate = Kamar::where('id', '!=', $kamar->id)->get()->first(fn ($k) => self::isSameKamar($k->nama_kamar, $newName));
        if ($duplicate) {
            return redirect()->back()->with('error', "Nama kamar berbenturan dengan kamar \"{$duplicate->nama_kamar}\" yang sudah ada.");
        }

        $oldName = $kamar->nama_kamar;

        $kamar->update([
            'nama_kamar'      => $newName,
            'kategori_asrama' => $validated['kategori_asrama'],
            'kapasitas'       => $validated['kapasitas'] ?? null,
            'wali_kamar'      => !empty($validated['wali_kamar']) ? trim($validated['wali_kamar']) : null,
            'urutan'          => $validated['urutan'] ?? $kamar->urutan,
        ]);

        // Jika nama kamar berubah, sinkronkan seluruh santri & target sesi presensi RFID
        if ($oldName !== $newName) {
            $matchingIds = Siswa::whereNotNull('kamar')
                ->get()
                ->filter(fn ($s) => self::isSameKamar($s->kamar, $oldName))
                ->pluck('id')
                ->all();

            if (!empty($matchingIds)) {
                Siswa::whereIn('id', $matchingIds)->update(['kamar' => $newName]);
            }

            if (Schema::hasTable('master_sesis') && Schema::hasColumn('master_sesis', 'target_filter')) {
                DB::table('master_sesis')
                    ->where('target_kategori', 'kamar_khusus')
                    ->where('target_filter', $oldName)
                    ->update(['target_filter' => $newName]);
            }
        }

        return redirect()->back()->with('success', "Data kamar {$kamar->nama_kamar} berhasil diperbarui.");
    }

    /**
     * Hapus kamar asrama dengan proteksi santri penghuni aktif.
     */
    public function destroyKamar(Kamar $kamar)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kamar asrama.');
        }

        $siswaCount = Siswa::where('status', 'aktif')
            ->get()
            ->filter(fn ($s) => self::isSameKamar($s->kamar, $kamar->nama_kamar))
            ->count();

        if ($siswaCount > 0) {
            return redirect()->back()->with(
                'error',
                "Kamar {$kamar->nama_kamar} tidak dapat dihapus karena masih ditempati oleh {$siswaCount} santri aktif. Silakan pindahkan atau keluarkan santri terlebih dahulu."
            );
        }

        $nama = $kamar->nama_kamar;
        $kamar->delete();

        return redirect()->back()->with('success', "Kamar {$nama} berhasil dihapus.");
    }

    /**
     * Hapus kamar asrama terpilih secara massal (dengan proteksi santri penghuni aktif).
     */
    public function destroyBulkKamar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kamar asrama.');
        }

        $request->validate([
            'kamar_ids' => 'required',
        ]);

        $ids = $this->parseSiswaIds($request->kamar_ids);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada kamar yang dipilih untuk dihapus.');
        }

        $kamars = Kamar::whereIn('id', $ids)->get();
        $activeSiswas = Siswa::where('status', 'aktif')->get();

        $deletedCount = 0;
        $blockedNames = [];

        foreach ($kamars as $kamar) {
            $siswaCount = $activeSiswas->filter(fn ($s) => self::isSameKamar($s->kamar, $kamar->nama_kamar))->count();
            if ($siswaCount > 0) {
                $blockedNames[] = "{$kamar->nama_kamar} ({$siswaCount} santri)";
                continue;
            }
            $kamar->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0 && empty($blockedNames)) {
            return redirect()->back()->with('success', "{$deletedCount} kamar terpilih berhasil dihapus.");
        }

        if ($deletedCount > 0 && !empty($blockedNames)) {
            return redirect()->back()->with(
                'success',
                "{$deletedCount} kamar kosong berhasil dihapus. Kamar berikut dilewati karena masih ditempati santri: " . implode(', ', $blockedNames) . '.'
            );
        }

        return redirect()->back()->with(
            'error',
            'Kamar terpilih tidak dapat dihapus karena masih ditempati santri aktif: ' . implode(', ', $blockedNames) . '. Silakan pindahkan santri terlebih dahulu.'
        );
    }

    /**
     * Muat daftar kamar standar pondok secara instan.
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $pondokLembaga = Lembaga::where('kode', 'pondok')->first();
        $added = self::seedStandar($pondokLembaga);

        return redirect()->back()->with('success', "Berhasil memeriksa dan menambahkan {$added} kamar standar pondok.");
    }

    /**
     * Tempatkan / pindahkan santri (tunggal maupun massal) ke kamar tujuan.
     */
    public function mutasi(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memutasi kamar santri.');
        }

        $request->validate([
            'siswa_id' => 'required',
            'kamar'    => 'required|string|max:100',
        ]);

        $targetKamar = trim($request->kamar);
        $siswaIds = $this->parseSiswaIds($request->siswa_id);

        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih untuk ditempatkan.');
        }

        // Pastikan kamar tujuan tercatat di tabel kamars
        $existingKamar = Kamar::all()->first(fn ($k) => self::isSameKamar($k->nama_kamar, $targetKamar));
        if (!$existingKamar) {
            $pondokLembaga = Lembaga::where('kode', 'pondok')->first();
            $existingKamar = Kamar::create([
                'lembaga_id'      => $pondokLembaga?->id,
                'nama_kamar'      => $targetKamar,
                'kategori_asrama' => self::inferKategoriAsrama($targetKamar),
                'urutan'          => (Kamar::max('urutan') ?? 0) + 1,
                'is_active'       => true,
            ]);
        } else {
            // Gunakan penulisan nama_kamar kanonik dari master kamars
            $targetKamar = $existingKamar->nama_kamar;
        }

        $updatedCount = Siswa::whereIn('id', $siswaIds)->update(['kamar' => $targetKamar]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} santri berhasil ditempatkan ke kamar {$targetKamar}.",
                'kamar'   => $targetKamar,
            ]);
        }

        return back()->with('success', "{$updatedCount} santri berhasil ditempatkan ke kamar {$targetKamar}.");
    }

    /**
     * Keluarkan santri dari kamar (unassign -> pindah ke tab Belum Terdaftar).
     */
    public function unassign(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengeluarkan santri dari kamar.');
        }

        $request->validate([
            'siswa_id' => 'required',
        ]);

        $siswaIds = $this->parseSiswaIds($request->siswa_id);
        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih.');
        }

        $updatedCount = Siswa::whereIn('id', $siswaIds)->update(['kamar' => null]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} santri berhasil dikeluarkan dari kamar.",
            ]);
        }

        return back()->with('success', "{$updatedCount} santri berhasil dipindahkan ke daftar Belum Terdaftar.");
    }

    /**
     * Ambil daftar penghuni kamar (JSON) untuk Modal Anggota Kamar.
     */
    public function anggota(Request $request, string $kamar)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('plotting_kamar', 'view') && !$authUser->canDo('pengaturan_pondok', 'view')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $kamarDecoded = urldecode($kamar);

        $allSiswa = Siswa::where('status', 'aktif')
            ->with('pendaftaran')
            ->orderBy('nama_lengkap')
            ->get();

        $anggota = $allSiswa->filter(function ($s) use ($kamarDecoded) {
            return self::isSameKamar($s->kamar, $kamarDecoded);
        })->values()->map(function ($s) {
            $jenjang = $s->jenjang ?? ($s->pendaftaran?->jenjang ?? 'Reguler');
            return [
                'id'           => $s->id,
                'nama_lengkap' => $s->nama_lengkap,
                'nisn'         => $s->pendaftaran?->nisn ?? $s->nisn ?? '-',
                'jenjang'      => $jenjang,
                'kelas'        => $s->kelas ?: '-',
                'kamar'        => $s->kamar,
            ];
        });

        return response()->json([
            'success' => true,
            'kamar'   => $kamarDecoded,
            'total'   => $anggota->count(),
            'anggota' => $anggota,
        ]);
    }
}
