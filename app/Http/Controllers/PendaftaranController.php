<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
use App\Models\Pendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use App\Services\ImageCompressor;
use RuntimeException;

class PendaftaranController extends Controller
{
    /**
     * Menampilkan daftar calon santri yang mendaftar.
     */
    public function index(Request $request)
    {
        $activeRoom = session('active_lembaga');
        $query = Pendaftaran::query()->latest();

        // Scope berdasarkan ruang kerja
        if ($activeRoom === 'smp') {
            $query->where('jenjang', 'SMP');
        } elseif ($activeRoom === 'sma') {
            $query->where('jenjang', 'SMA');
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status_pendaftaran', $request->status);
        }

        // Filter jenjang manual jika di Yayasan/Pondok
        if ((!$activeRoom || in_array($activeRoom, ['yayasan', 'pondok'])) && $request->filled('jenjang')) {
            $query->where('jenjang', $request->jenjang);
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

        $baseStats = Pendaftaran::query();
        if ($activeRoom === 'smp') {
            $baseStats->where('jenjang', 'SMP');
        } elseif ($activeRoom === 'sma') {
            $baseStats->where('jenjang', 'SMA');
        }

        $stats = [
            'total' => (clone $baseStats)->count(),
            'pending' => (clone $baseStats)->where('status_pendaftaran', 'Pending')->count(),
            'diterima' => (clone $baseStats)->where('status_pendaftaran', 'Diterima')->count(),
            'ditolak' => (clone $baseStats)->where('status_pendaftaran', 'Ditolak')->count(),
        ];

        return view('pendaftaran.index', compact('pendaftarans', 'stats', 'activeRoom'));
    }

    /**
     * Menampilkan form formulir pendaftaran bertahap/rapi.
     */
    public function create()
    {
        return view('pendaftaran.create');
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

        // Generate No. Induk otomatis jika kosong
        if (empty($data['no_induk'])) {
            $tahun = date('Y');
            $urut = str_pad(Pendaftaran::whereYear('created_at', $tahun)->count() + 1, 4, '0', STR_PAD_LEFT);
            $data['no_induk'] = "PSB-{$tahun}-{$urut}";
        }

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
        $pendaftaran = Pendaftaran::findOrFail($id);

        return view('pendaftaran.show', compact('pendaftaran'));
    }

    /**
     * Menampilkan formulir pendaftaran siap cetak / unduh PDF (format kertas A4).
     */
    public function cetak($id)
    {
        $pendaftaran = Pendaftaran::findOrFail($id);

        return view('pendaftaran.cetak', compact('pendaftaran'));
    }

    /**
     * Update status pendaftaran (Pending/Diterima/Ditolak).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_pendaftaran' => 'required|in:Pending,Diterima,Ditolak',
        ]);

        $pendaftaran = Pendaftaran::findOrFail($id);
        $oldStatus = $pendaftaran->status_pendaftaran;
        $newStatus = $request->status_pendaftaran;

        $pendaftaran->update([
            'status_pendaftaran' => $newStatus,
        ]);

        if ($newStatus === 'Diterima') {
            // Jika diterima, pastikan terdaftar di tabel Siswa
            $siswa = Siswa::where('pendaftaran_id', $pendaftaran->id)
                ->orWhere('nama_lengkap', $pendaftaran->nama_lengkap)
                ->first();

            if (!$siswa) {
                Siswa::create([
                    'nama_lengkap'   => $pendaftaran->nama_lengkap,
                    'kamar'          => 'Reguler',
                    'status'         => 'aktif',
                    'pendaftaran_id' => $pendaftaran->id,
                ]);
            } else {
                $siswa->update([
                    'pendaftaran_id' => $pendaftaran->id,
                    'status'         => 'aktif',
                ]);
            }
        } elseif ($newStatus === 'Ditolak' || $newStatus === 'Pending') {
            // Jika pendaftaran Ditolak atau Pending, hapus dari daftar Siswa
            Siswa::where('pendaftaran_id', $pendaftaran->id)
                ->orWhere('nama_lengkap', $pendaftaran->nama_lengkap)
                ->delete();
        }

        return redirect()->back()->with('success', 'Status pendaftaran berhasil diperbarui menjadi ' . $newStatus . '.');
    }
}
