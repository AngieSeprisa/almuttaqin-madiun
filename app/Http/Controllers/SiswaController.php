<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    /**
     * Pastikan siswa berada di dalam room lembaga aktif (mencegah akses lintas lembaga).
     */
    protected function ensureSiswaInActiveRoom(Siswa $siswa): void
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        if ($scopedFormal && !Siswa::forLembaga($scopedFormal->kode)->whereKey($siswa->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data siswa lembaga lain.');
        }
    }

    /**
     * Menampilkan daftar siswa.
     */
    public function index(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');
        $query = Siswa::query()
            ->with('pendaftaran')
            ->where(function ($q) {
                $q->whereNotIn('status', ['lulus', 'mutasi'])
                  ->orWhereNull('status');
            });

        // Filter berdasarkan ruang kerja/lembaga aktif
        $query->forLembaga($activeRoom);

        // Filter berdasarkan jenjang (hanya berlaku di room global Yayasan/Pondok)
        if (!$scopedFormal && $request->filled('jenjang')) {
            $jenjang = trim((string) $request->jenjang);
            if (strcasecmp($jenjang, 'Reguler') === 0) {
                $query->where(function ($q) {
                    $q->whereRaw('LOWER(TRIM(jenjang)) = ?', ['reguler'])
                      ->orWhereHas('pendaftaran', fn ($p) => $p->whereRaw('LOWER(TRIM(jenjang)) = ?', ['reguler']));
                });
            } else {
                $aliases = array_map('strtolower', Lembaga::jenjangAliasesForRoom($jenjang));
                if (empty($aliases)) {
                    $aliases = [strtolower($jenjang)];
                }
                $query->where(function ($q) use ($aliases) {
                    $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(jenjang))'), $aliases)
                      ->orWhereHas('pendaftaran', function ($p) use ($aliases) {
                          $p->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(jenjang))'), $aliases);
                      });
                });
            }
        }

        // Filter berdasarkan kamar
        if ($request->filled('kamar')) {
            $query->where('kamar', $request->kamar);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%");
            });
        }

        $siswa = $query->latest('id')->get();

        // Ambil daftar unik kamar dari master kamars + data siswa
        $kamars = Kamar::daftarNamaKamarAktif();

        $jenjangList = $scopedFormal
            ? [$scopedFormal->jenjang_key]
            : Lembaga::allJenjangOptions(true);

        return view('siswa', compact('siswa', 'activeRoom', 'kamars', 'jenjangList', 'scopedFormal'));
    }

    /**
     * Menyimpan data siswa baru beserta pendaftaran otomatis.
     */
    public function store(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');
        $defaultJenjang = Lembaga::defaultJenjangForRoom($activeRoom);
        $allowedJenjangs = Lembaga::allJenjangOptions(true);

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenjang'      => ['nullable', 'string', 'max:50', Rule::in($allowedJenjangs)],
            'kamar'        => 'required|string|max:100',
            'status'       => 'required|in:aktif,pulang,sakit',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'kamar.required'        => 'Kamar santri wajib diisi.',
            'status.required'       => 'Status santri wajib dipilih.',
            'status.in'             => 'Status santri tidak valid.',
        ]);

        // Kunci jenjang sesuai room formal aktif agar tidak bisa menyuntikkan siswa ke lembaga lain
        $jenjang = $scopedFormal ? $scopedFormal->jenjang_key : ($validated['jenjang'] ?? $defaultJenjang);

        $tahun = date('Y');
        $count = Pendaftaran::whereYear('created_at', $tahun)->count() + 1;
        $urut = str_pad($count, 4, '0', STR_PAD_LEFT);
        $panggilan = explode(' ', trim($validated['nama_lengkap']))[0] ?? 'Santri';

        $pendaftaran = Pendaftaran::create([
            'jenjang' => $jenjang,
            'no_induk' => "PSB-{$tahun}-{$urut}",
            'nik' => '3519' . rand(10, 99) . date('dmy') . rand(1000, 9999),
            'nama_lengkap' => $validated['nama_lengkap'],
            'nama_panggilan' => $panggilan,
            'tempat_lahir' => 'Kota Madiun',
            'tanggal_lahir' => '2008-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'anak_ke' => 1,
            'jumlah_saudara' => 2,
            'tinggal_bersama' => 'Orang Tua',
            'mulai_pengajian' => 'sejak kecil',
            'alamat_jalan' => 'Jl. Nogososro No. 26',
            'rt_rw' => '001/001',
            'desa_kelurahan' => 'Josenan',
            'kecamatan' => 'Taman',
            'kab_kota' => 'Kota Madiun',
            'kode_pos' => '63134',
            'provinsi' => 'Jawa Timur',
            'warga_negara' => 'Indonesia',
            'kelompok' => 'Bina Syarekah',
            'desa' => 'Mentawai',
            'daerah' => 'Madiun Kota',
            'pendidikan_terakhir' => 'SMP/MTs',
            'jurusan' => 'Reguler',
            'tahun_lulus' => '2023',
            'status_mondok' => 'Person',
            'pj_biaya_nama' => 'Orang Tua',
            'pj_biaya_hp' => '-',
            'penyakit_diderita' => 'Tidak Ada',
            'alergi' => '-',
            'gol_darah' => 'O',
            'tinggi_badan' => 165,
            'berat_badan' => 55,
            'ayah_nama' => 'Bapak ' . $panggilan,
            'ayah_status' => 'Hidup',
            'ayah_agama' => 'Islam',
            'ayah_wn' => 'Indonesia',
            'ayah_daerah' => 'Madiun Kota',
            'ayah_desa' => 'Mentawai',
            'ayah_kelompok' => 'Bina Syarekah',
            'ibu_nama' => 'Ibu ' . $panggilan,
            'ibu_status' => 'Hidup',
            'ibu_agama' => 'Islam',
            'ibu_wn' => 'Indonesia',
            'ibu_daerah' => 'Madiun Kota',
            'ibu_desa' => 'Mentawai',
            'ibu_kelompok' => 'Bina Syarekah',
            'berkas_akta' => true,
            'berkas_kk' => true,
            'berkas_ktp_ortu' => true,
            'berkas_surat_pernyataan' => true,
            'status_pendaftaran' => 'Diterima',
        ]);

        $validated['jenjang'] = $jenjang;
        $validated['pendaftaran_id'] = $pendaftaran->id;
        Siswa::create($validated);

        return redirect()->route('siswa')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Dapatkan atau sinkronkan data pendaftaran dari data siswa.
     */
    protected function getOrCreatePendaftaran(Siswa $siswa): Pendaftaran
    {
        $pendaftaran = $siswa->pendaftaran;
        if (!$pendaftaran && !empty($siswa->pendaftaran_id)) {
            $pendaftaran = Pendaftaran::find($siswa->pendaftaran_id);
        }

        if (!$pendaftaran) {
            $pendaftaran = Pendaftaran::where('nama_lengkap', $siswa->nama_lengkap)->first();
            if ($pendaftaran) {
                $siswa->update(['pendaftaran_id' => $pendaftaran->id]);
            }
        }

        if (!$pendaftaran) {
            $tahun = date('Y');
            $count = Pendaftaran::whereYear('created_at', $tahun)->count() + 1;
            $urut = str_pad($count, 4, '0', STR_PAD_LEFT);
            $panggilan = explode(' ', trim($siswa->nama_lengkap))[0] ?? 'Santri';

            $pendaftaran = Pendaftaran::create([
                'jenjang' => $siswa->jenjang ?? 'Reguler',
                'no_induk' => "PSB-{$tahun}-{$urut}",
                'nik' => '3519' . str_pad($siswa->id % 99, 2, '0', STR_PAD_LEFT) . date('dmy') . rand(1000, 9999),
                'nama_lengkap' => $siswa->nama_lengkap,
                'nama_panggilan' => $panggilan,
                'tempat_lahir' => 'Kota Madiun',
                'tanggal_lahir' => '2008-01-01',
                'jenis_kelamin' => 'Laki-laki',
                'anak_ke' => 1,
                'jumlah_saudara' => 2,
                'tinggal_bersama' => 'Orang Tua',
                'mulai_pengajian' => 'sejak kecil',
                'alamat_jalan' => 'Jl. Nogososro No. 26',
                'rt_rw' => '001/001',
                'desa_kelurahan' => 'Josenan',
                'kecamatan' => 'Taman',
                'kab_kota' => 'Kota Madiun',
                'kode_pos' => '63134',
                'provinsi' => 'Jawa Timur',
                'warga_negara' => 'Indonesia',
                'kelompok' => 'Bina Syarekah',
                'desa' => 'Mentawai',
                'daerah' => 'Madiun Kota',
                'pendidikan_terakhir' => 'SMP/MTs',
                'status_mondok' => 'Person',
                'pj_biaya_nama' => 'Orang Tua',
                'pj_biaya_hp' => '-',
                'penyakit_diderita' => 'Tidak Ada',
                'alergi' => '-',
                'gol_darah' => 'O',
                'ayah_nama' => 'Bapak ' . $panggilan,
                'ayah_status' => 'Hidup',
                'ayah_agama' => 'Islam',
                'ayah_wn' => 'Indonesia',
                'ayah_daerah' => 'Madiun Kota',
                'ayah_desa' => 'Mentawai',
                'ayah_kelompok' => 'Bina Syarekah',
                'ibu_nama' => 'Ibu ' . $panggilan,
                'ibu_status' => 'Hidup',
                'ibu_agama' => 'Islam',
                'ibu_wn' => 'Indonesia',
                'ibu_daerah' => 'Madiun Kota',
                'ibu_desa' => 'Mentawai',
                'ibu_kelompok' => 'Bina Syarekah',
                'berkas_akta' => true,
                'berkas_kk' => true,
                'berkas_ktp_ortu' => true,
                'berkas_surat_pernyataan' => true,
                'status_pendaftaran' => 'Diterima',
            ]);

            $siswa->update(['pendaftaran_id' => $pendaftaran->id]);
        }

        return $pendaftaran;
    }

    /**
     * Menampilkan detail data siswa (membuka halaman View Pendaftaran santri secara penuh).
     */
    public function show($id)
    {
        $siswa = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($siswa);

        if (request()->wantsJson()) {
            return response()->json($siswa);
        }

        $pendaftaran = $this->getOrCreatePendaftaran($siswa);

        return redirect()->route('pendaftaran.show', ['id' => $pendaftaran->id, 'from' => 'siswa']);
    }

    /**
     * Menampilkan tampilan formulir cetak santri (kertas A4 / print preview).
     */
    public function cetak($id)
    {
        $siswa = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($siswa);

        if (request()->wantsJson()) {
            return response()->json($siswa);
        }

        $pendaftaran = $this->getOrCreatePendaftaran($siswa);

        return redirect()->route('pendaftaran.cetak', ['id' => $pendaftaran->id, 'from' => 'siswa', 'print' => 1]);
    }

    /**
     * Menampilkan form edit data siswa (mengalihkan ke formulir pendaftaran).
     */
    public function edit($id)
    {
        $siswa = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($siswa);

        if (request()->wantsJson()) {
            return response()->json($siswa);
        }

        $pendaftaran = $this->getOrCreatePendaftaran($siswa);

        return redirect()->route('pendaftaran.edit', ['id' => $pendaftaran->id, 'from' => 'siswa']);
    }

    /**
     * Memperbarui data siswa.
     */
    public function update(Request $request, $id)
    {
        $item = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($item);

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'kamar'        => 'required|string|max:100',
            'status'       => 'required|in:aktif,pulang,sakit',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'kamar.required'        => 'Kamar santri wajib diisi.',
            'status.required'       => 'Status santri wajib dipilih.',
            'status.in'             => 'Status santri tidak valid.',
        ]);

        $item->update($validated);

        if ($item->pendaftaran) {
            $item->pendaftaran->update([
                'nama_lengkap' => $item->nama_lengkap,
            ]);
        }

        return redirect()->route('siswa')->with('success', "Data siswa \"{$item->nama_lengkap}\" berhasil diperbarui.");
    }

    /**
     * Memperbarui UID kartu RFID santri (Pairing RFID).
     */
    public function updateRfid(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($siswa);

        $validated = $request->validate([
            'rfid_uid' => 'nullable|string|max:32|unique:siswas,rfid_uid,' . $siswa->id,
        ], [
            'rfid_uid.unique' => 'UID RFID ini sudah terdaftar untuk santri lain!',
        ]);

        $rawUid = !empty($validated['rfid_uid']) ? trim($validated['rfid_uid']) : null;
        if ($rawUid) {
            $existing = NfcInputController::findSiswaByUid($rawUid, $siswa->id);
            if ($existing) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => "UID RFID ini sudah terdaftar untuk santri {$existing->nama_lengkap}!",
                        'errors' => ['rfid_uid' => ["UID RFID ini sudah terdaftar untuk santri {$existing->nama_lengkap}!"]],
                    ], 422);
                }
                return redirect()->back()->withErrors(['rfid_uid' => "UID RFID ini sudah terdaftar untuk santri {$existing->nama_lengkap}!"]);
            }
        }
        $uid = $rawUid ? NfcInputController::toDecimal32($rawUid) : null;
        $siswa->update(['rfid_uid' => $uid]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Kartu RFID santri {$siswa->nama_lengkap} berhasil diperbarui.",
                'rfid_uid' => $uid,
            ]);
        }

        return redirect()->back()->with('success', "Kartu RFID santri \"{$siswa->nama_lengkap}\" berhasil diperbarui.");
    }

    /**
     * Mengambil info kartu RFID yang terakhir di-tap (membantu pairing live).
     */
    public function lastRfidTap()
    {
        $path = '/tmp/rfid_last_tap.json';
        if (file_exists($path)) {
            $content = json_decode(file_get_contents($path), true);
            return response()->json($content);
        }

        return response()->json(['uid' => null]);
    }

    /**
     * Menghapus data siswa.
     */
    public function destroy($id)
    {
        $item = Siswa::findOrFail($id);
        $this->ensureSiswaInActiveRoom($item);
        $nama = $item->nama_lengkap;
        $item->delete();

        return redirect()->route('siswa')->with('success', "Data siswa \"{$nama}\" berhasil dihapus.");
    }
}
