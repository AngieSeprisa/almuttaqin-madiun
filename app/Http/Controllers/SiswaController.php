<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    /**
     * Menampilkan daftar siswa.
     */
    public function index(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $query = Siswa::query()->with('pendaftaran');

        // Filter berdasarkan ruang kerja/lembaga aktif
        $query->forLembaga($activeRoom);

        // Filter berdasarkan jenjang
        if ($request->filled('jenjang')) {
            $jenjang = $request->jenjang;
            $query->where(function ($q) use ($jenjang) {
                $q->where('jenjang', $jenjang)
                  ->orWhereHas('pendaftaran', function ($p) use ($jenjang) {
                      $p->where('jenjang', $jenjang);
                  });
            });
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

        // Ambil daftar unik kamar dari database
        $kamars = Siswa::whereNotNull('kamar')
            ->where('kamar', '!=', '')
            ->distinct()
            ->orderBy('kamar')
            ->pluck('kamar');

        $jenjangList = ['Reguler', 'SMP', 'SMA'];

        return view('siswa', compact('siswa', 'activeRoom', 'kamars', 'jenjangList'));
    }

    /**
     * Menyimpan data siswa baru beserta pendaftaran otomatis.
     */
    public function store(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $defaultJenjang = match ($activeRoom) {
            'smp' => 'SMP',
            'sma' => 'SMA',
            default => 'Reguler',
        };

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'jenjang'      => 'nullable|in:SMP,SMA,Reguler',
            'kamar'        => 'required|string|max:100',
            'status'       => 'required|in:aktif,pulang,sakit',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'kamar.required'        => 'Kamar santri wajib diisi.',
            'status.required'       => 'Status santri wajib dipilih.',
            'status.in'             => 'Status santri tidak valid.',
        ]);

        $jenjang = $validated['jenjang'] ?? $defaultJenjang;

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
     * Menampilkan detail data siswa (mengalihkan ke formulir cetak).
     */
    public function show($id)
    {
        return $this->cetak($id);
    }

    /**
     * Menampilkan tampilan formulir cetak santri.
     */
    public function cetak($id)
    {
        $siswa = Siswa::findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json($siswa);
        }

        // Cari atau hubungkan pendaftaran
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

        return redirect()->route('pendaftaran.cetak', ['id' => $pendaftaran->id, 'from' => 'siswa']);
    }

    /**
     * Menampilkan form edit data siswa.
     */
    public function edit($id)
    {
        $item = Siswa::findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json($item);
        }

        return redirect()->route('siswa');
    }

    /**
     * Memperbarui data siswa.
     */
    public function update(Request $request, $id)
    {
        $item = Siswa::findOrFail($id);

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
     * Menghapus data siswa.
     */
    public function destroy($id)
    {
        $item = Siswa::findOrFail($id);
        $nama = $item->nama_lengkap;
        $item->delete();

        return redirect()->route('siswa')->with('success', "Data siswa \"{$nama}\" berhasil dihapus.");
    }
}
