<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\ImageCompressor;
use RuntimeException;

class PendaftaranController extends Controller
{
    /**
     * Pastikan pendaftaran termasuk dalam room lembaga aktif.
     */
    protected function ensurePendaftaranInActiveRoom(Pendaftaran $pendaftaran): void
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        if ($scopedFormal && !Pendaftaran::forLembaga($scopedFormal->kode)->whereKey($pendaftaran->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data pendaftaran lembaga lain.');
        }
    }

    /**
     * Menampilkan daftar calon santri yang mendaftar.
     */
    public function index(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');
        $query = Pendaftaran::query()->with(['penerimaan', 'gelombang'])->latest();

        // Scope berdasarkan ruang kerja (dinamis mendukung SMP, SMA, SD IT, RA, dll)
        $query->forLembaga($activeRoom);

        // Filter Event Penerimaan
        if ($request->filled('penerimaan_id')) {
            $query->where('penerimaan_santri_id', $request->penerimaan_id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status_pendaftaran', $request->status);
        }

        // Filter jenjang manual hanya jika di room global (Yayasan/Pondok)
        if (!$scopedFormal && $request->filled('jenjang')) {
            $jenjang = trim((string) $request->jenjang);
            if (strcasecmp($jenjang, 'Reguler') === 0) {
                $query->whereRaw('LOWER(TRIM(jenjang)) = ?', ['reguler']);
            } else {
                $query->forLembaga($jenjang);
            }
        }

        // Pencarian Nama / NIK / NISN / Jenjang / Sekolah Asal
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('jenjang', 'like', "%{$search}%")
                  ->orWhere('nama_sekolah_asal', 'like', "%{$search}%")
                  ->orWhere('no_induk', 'like', "%{$search}%")
                  ->orWhere('desa_kelurahan', 'like', "%{$search}%")
                  ->orWhere('kab_kota', 'like', "%{$search}%");
            });
        }

        $pendaftarans = $query->paginate(10)->withQueryString();

        $baseStats = Pendaftaran::query()->forLembaga($activeRoom);
        if ($request->filled('penerimaan_id')) {
            $baseStats->where('penerimaan_santri_id', $request->penerimaan_id);
        }

        $stats = [
            'total'         => (clone $baseStats)->count(),
            'pending'       => (clone $baseStats)->where('status_pendaftaran', 'Pending')->count(),
            'lulus_seleksi' => (clone $baseStats)->where('status_pendaftaran', 'Lulus Seleksi')->count(),
            'diterima'      => (clone $baseStats)->where('status_pendaftaran', 'Diterima')->count(),
            'ditolak'       => (clone $baseStats)->where('status_pendaftaran', 'Ditolak')->count(),
        ];

        $penerimaanQuery = PenerimaanSantri::query()->latest();
        if ($scopedFormal) {
            $aliases = array_map('strtolower', Lembaga::jenjangAliasesForRoom($scopedFormal->kode));
            $penerimaanQuery->where(function ($q) use ($aliases) {
                $q->whereRaw('LOWER(TRIM(jenjang)) = ?', ['semua']);
                foreach ($aliases as $alias) {
                    $q->orWhereRaw('LOWER(TRIM(jenjang)) = ?', [$alias])
                      ->orWhereRaw('LOWER(jenjang) LIKE ?', [$alias . ',%'])
                      ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%, ' . $alias])
                      ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%,' . $alias])
                      ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%, ' . $alias . ',%'])
                      ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%,' . $alias . ',%'])
                      ->orWhereRaw('LOWER(jenjangs) LIKE ?', ['%"' . $alias . '"%']);
                }
            });
        }
        $penerimaanList = $penerimaanQuery->get(['id', 'judul', 'tahun_ajaran']);
        $jenjangList = $scopedFormal
            ? [$scopedFormal->jenjang_key]
            : Lembaga::allJenjangOptions(true);

        return view('pendaftaran.index', compact('pendaftarans', 'stats', 'activeRoom', 'penerimaanList', 'jenjangList', 'scopedFormal'));
    }

    /**
     * Menampilkan form formulir pendaftaran bertahap/rapi.
     */
    public function create()
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');
        $jenjangList = $scopedFormal
            ? [$scopedFormal->jenjang_key]
            : Lembaga::allJenjangOptions(true);
        $defaultJenjang = Lembaga::defaultJenjangForRoom($activeRoom);

        return view('pendaftaran.create', compact('scopedFormal', 'activeRoom', 'jenjangList', 'defaultJenjang'));
    }

    /**
     * Validasi form request dan simpan ke database MySQL.
     */
    public function store(PendaftaranRequest $request, ImageCompressor $compressor)
    {
        $data = $request->validated();

        // Kompres & simpan foto berkas (jika ada). Unggahan foto otomatis menandai berkas "sudah".
        $stored = [];
        try {
            foreach (Pendaftaran::FOTO_BERKAS as $field => [$flag]) {
                unset($data[$field]);

                if ($request->hasFile($field)) {
                    $stored[$field] = $compressor->store($request->file($field), 'berkas');
                    $data[$field] = $stored[$field];
                    $data[$flag] = true;
                }
            }
        } catch (RuntimeException $e) {
            foreach ($stored as $path) {
                $compressor->delete($path);
            }

            return back()->withInput()->withErrors(['foto_berkas' => $e->getMessage()]);
        }

        // Normalisasi field opsional agar MySQL unique tidak duplicate empty string
        if (empty($data['nik'])) {
            $data['nik'] = null;
        }
        if (empty($data['nama_lengkap'])) {
            $data['nama_lengkap'] = 'Santri Baru';
        }
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        if ($scopedFormal) {
            $data['jenjang'] = $scopedFormal->jenjang_key;
        } elseif (empty($data['jenjang'])) {
            $data['jenjang'] = Lembaga::defaultJenjangForRoom(session('active_lembaga'));
        }

        // Generate No. Induk otomatis jika kosong
        if (empty($data['no_induk'])) {
            $tahun = date('Y');
            $seq = Pendaftaran::whereYear('created_at', $tahun)->count() + 1;
            do {
                $code = 'PSB-' . $tahun . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $exists = Pendaftaran::where('no_induk', $code)->exists();
                $seq++;
            } while ($exists);
            $data['no_induk'] = $code;
        }
        $data['public_token'] = Str::random(40);

        try {
            $pendaftaran = Pendaftaran::create($data);
        } catch (\Throwable $e) {
            // Jangan tinggalkan file yatim bila penyimpanan database gagal.
            foreach ($stored as $path) {
                $compressor->delete($path);
            }
            throw $e;
        }

        return redirect()->route('pendaftaran.show', $pendaftaran->id)
            ->with('success', 'Formulir pendaftaran santri baru atas nama ' . $pendaftaran->nama_lengkap . ' berhasil disimpan!');
    }

    /**
     * Menampilkan rincian lengkap biodata santri untuk verifikasi.
     */
    public function show($id)
    {
        $pendaftaran = Pendaftaran::with(['penerimaan', 'gelombang'])->findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);

        return view('pendaftaran.show', compact('pendaftaran'));
    }

    /**
     * Menampilkan formulir pendaftaran siap cetak / unduh PDF (format kertas A4).
     */
    public function cetak($id)
    {
        $pendaftaran = Pendaftaran::with('penerimaan')->findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);
        $kop = Lembaga::kopForJenjang($pendaftaran->jenjang);

        return view('pendaftaran.cetak', compact('pendaftaran', 'kop'));
    }

    /**
     * Update status pendaftaran (Pending / Lulus Seleksi / Diterima / Ditolak).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_pendaftaran' => 'required|in:Pending,Lulus Seleksi,Diterima,Ditolak',
        ]);

        $pendaftaran = Pendaftaran::findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);
        $newStatus = $request->status_pendaftaran;

        $pendaftaran->update([
            'status_pendaftaran' => $newStatus,
        ]);

        if ($newStatus === 'Diterima') {
            // Jika sudah daftar ulang / diterima resmi, pastikan terdaftar di tabel Siswa
            $siswa = Siswa::where('pendaftaran_id', $pendaftaran->id)
                ->orWhere('nama_lengkap', $pendaftaran->nama_lengkap)
                ->first();

            if (!$siswa) {
                Siswa::create([
                    'nama_lengkap'   => $pendaftaran->nama_lengkap,
                    'jenjang'        => $pendaftaran->jenjang ?? 'Reguler',
                    'kamar'          => 'Reguler',
                    'status'         => 'aktif',
                    'pendaftaran_id' => $pendaftaran->id,
                ]);
            } else {
                $siswa->update([
                    'pendaftaran_id' => $pendaftaran->id,
                    'jenjang'        => $pendaftaran->jenjang ?? $siswa->jenjang,
                    'status'         => 'aktif',
                ]);
            }
        } else {
            // Jika Pending / Lulus Seleksi / Ditolak, hapus dari daftar Siswa aktif yang terikat pendaftaran ini
            Siswa::where('pendaftaran_id', $pendaftaran->id)->delete();
        }

        return redirect()->back()->with('success', 'Status pendaftaran berhasil diperbarui menjadi ' . $newStatus . '.');
    }

    /**
     * Menampilkan form edit formulir pendaftaran santri.
     */
    public function edit($id)
    {
        $pendaftaran = Pendaftaran::with('penerimaan')->findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');
        $jenjangList = $scopedFormal
            ? [$scopedFormal->jenjang_key]
            : Lembaga::allJenjangOptions(true);

        return view('pendaftaran.edit', compact('pendaftaran', 'activeRoom', 'scopedFormal', 'jenjangList'));
    }

    /**
     * Memperbarui data formulir pendaftaran santri.
     */
    public function update(PendaftaranRequest $request, $id, ImageCompressor $compressor)
    {
        $pendaftaran = Pendaftaran::findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);
        $data = $request->validated();

        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        if ($scopedFormal) {
            $data['jenjang'] = $scopedFormal->jenjang_key;
        }

        $stored = [];
        $oldFilesToDelete = [];

        try {
            foreach (Pendaftaran::FOTO_BERKAS as $field => [$flag]) {
                unset($data[$field]);

                if ($request->hasFile($field)) {
                    $stored[$field] = $compressor->store($request->file($field), 'berkas');
                    $data[$field] = $stored[$field];
                    $data[$flag] = true;

                    if ($pendaftaran->{$field}) {
                        $oldFilesToDelete[] = $pendaftaran->{$field};
                    }
                }
            }
        } catch (RuntimeException $e) {
            foreach ($stored as $path) {
                $compressor->delete($path);
            }

            return back()->withInput()->withErrors(['foto_berkas' => $e->getMessage()]);
        }

        if (array_key_exists('nik', $data) && empty($data['nik'])) {
            $data['nik'] = null;
        }

        try {
            $pendaftaran->update($data);

            // Hapus file lama yang berhasil digantikan
            foreach ($oldFilesToDelete as $oldFile) {
                $compressor->delete($oldFile);
            }

            // Sinkronkan data ke tabel Siswa jika pendaftaran ini sudah terhubung dengan siswa
            $siswa = Siswa::where('pendaftaran_id', $pendaftaran->id)
                ->orWhere('nama_lengkap', $pendaftaran->nama_lengkap)
                ->first();

            if ($siswa) {
                $siswa->update([
                    'nama_lengkap'   => $pendaftaran->nama_lengkap,
                    'jenjang'        => $pendaftaran->jenjang,
                    'pendaftaran_id' => $pendaftaran->id,
                ]);
            }
        } catch (\Throwable $e) {
            foreach ($stored as $path) {
                $compressor->delete($path);
            }
            throw $e;
        }

        if ($request->input('from') === 'siswa') {
            return redirect()->route('siswa')
                ->with('success', 'Formulir pendaftaran santri atas nama ' . $pendaftaran->nama_lengkap . ' berhasil diperbarui.');
        }

        return redirect()->route('pendaftaran.show', $pendaftaran->id)
            ->with('success', 'Formulir pendaftaran santri atas nama ' . $pendaftaran->nama_lengkap . ' berhasil diperbarui.');
    }

    /**
     * Menghapus data pendaftaran beserta foto berkasnya.
     */
    public function destroy(Request $request, $id, ImageCompressor $compressor)
    {
        $pendaftaran = Pendaftaran::findOrFail($id);
        $this->ensurePendaftaranInActiveRoom($pendaftaran);
        $nama = $pendaftaran->nama_lengkap;

        foreach (array_keys(Pendaftaran::FOTO_BERKAS) as $field) {
            if (!empty($pendaftaran->{$field})) {
                $compressor->delete($pendaftaran->{$field});
            }
        }

        if (is_array($pendaftaran->custom_responses)) {
            foreach ($pendaftaran->custom_responses as $resp) {
                if (($resp['type'] ?? '') === 'file' && !empty($resp['value']) && is_string($resp['value'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($resp['value']);
                }
            }
        }

        $pendaftaran->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => 'Data pendaftaran ' . $nama . ' berhasil dihapus.']);
        }

        return redirect()->route('pendaftaran.index')
            ->with('success', 'Data pendaftaran ' . $nama . ' berhasil dihapus.');
    }
}
