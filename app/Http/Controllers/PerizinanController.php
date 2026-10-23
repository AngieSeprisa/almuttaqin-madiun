<?php

namespace App\Http\Controllers;

use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PerizinanController extends Controller
{
    /**
     * Tampilkan halaman perizinan sambang siswa.
     */
    public function sambang(Request $request)
    {
        $query = PerizinanSambang::query()->with('siswa');
        $today = Carbon::today()->toDateString();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'melewati_batas') {
                $query->where('status', 'sambang')
                      ->where('tanggal_kembali', '<', $today);
            } elseif ($status === 'sambang') {
                $query->where('status', 'sambang')
                      ->where('tanggal_kembali', '>=', $today);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('kamar')) {
            $query->where('kamar', $request->kamar);
        }

        $perizinan = $query->latest('id')->get();

        // Ambil daftar kamar unik
        $kamars = PerizinanSambang::whereNotNull('kamar')->where('kamar', '!=', '')->distinct()->orderBy('kamar')->pluck('kamar');

        // Ambil daftar seluruh siswa untuk prediksi pencarian nama & auto kamar
        $daftarSiswa = Siswa::orderBy('nama_lengkap', 'asc')->get(['id', 'nama_lengkap', 'kamar', 'status']);

        return view('perizinan.sambang', compact('perizinan', 'daftarSiswa', 'today', 'kamars'));
    }

    /**
     * Tampilkan formulir halaman tersendiri untuk input perizinan sambang.
     */
    public function createSambang()
    {
        $daftarSiswa = Siswa::orderBy('nama_lengkap', 'asc')->get(['id', 'nama_lengkap', 'kamar', 'status']);
        $today = Carbon::today()->toDateString();

        return view('perizinan.sambang_create', compact('daftarSiswa', 'today'));
    }

    /**
     * Simpan perizinan sambang baru.
     * Mengubah status siswa dari aktif menjadi pulang.
     */
    public function storeSambang(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'        => 'required|exists:siswas,id',
            'tanggal_mulai'   => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'nullable|string|max:500',
        ], [
            'siswa_id.required'              => 'Silakan pilih siswa yang mengajukan izin.',
            'siswa_id.exists'                => 'Data siswa tidak valid.',
            'tanggal_mulai.required'         => 'Tanggal mulai sambang wajib diisi.',
            'tanggal_kembali.required'       => 'Tanggal estimasi kembali wajib diisi.',
            'tanggal_kembali.after_or_equal' => 'Tanggal estimasi kembali tidak boleh sebelum tanggal mulai sambang.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        PerizinanSambang::create([
            'siswa_id'        => $siswa->id,
            'nama'            => $siswa->nama_lengkap,
            'kamar'           => $siswa->kamar,
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_kembali' => $validated['tanggal_kembali'],
            'keterangan'      => $validated['keterangan'] ?? null,
            'status'          => 'sambang',
        ]);

        // Perbarui status siswa menjadi pulang
        $siswa->update(['status' => 'pulang']);

        return redirect()->route('perizinan.sambang')
            ->with('success', 'Perizinan sambang untuk ' . $siswa->nama_lengkap . ' berhasil disimpan. Status siswa telah diperbarui menjadi Pulang.');
    }

    /**
     * Tandai siswa sudah kembali dari sambang.
     * Mengubah status perizinan menjadi sudah_kembali dan status siswa menjadi aktif.
     */
    public function kembaliSambang($id)
    {
        $sambang = PerizinanSambang::findOrFail($id);
        $sambang->update(['status' => 'sudah_kembali']);

        if ($sambang->siswa_id) {
            Siswa::where('id', $sambang->siswa_id)->update(['status' => 'aktif']);
        }

        return redirect()->route('perizinan.sambang')
            ->with('success', 'Santri ' . $sambang->nama . ' telah ditandai Sudah Kembali. Status siswa kembali Aktif.');
    }

    /**
     * Perbarui data perizinan sambang (modal edit).
     */
    public function updateSambang(Request $request, $id)
    {
        $sambang = PerizinanSambang::findOrFail($id);

        $validated = $request->validate([
            'tanggal_mulai'   => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_mulai',
            'status'          => 'required|in:sambang,sudah_kembali,melewati_batas',
            'keterangan'      => 'nullable|string|max:500',
        ]);

        $sambang->update([
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_kembali' => $validated['tanggal_kembali'],
            'status'          => $validated['status'],
            'keterangan'      => $validated['keterangan'] ?? null,
        ]);

        // Sinkronkan status siswa jika status perizinan berubah
        if ($sambang->siswa_id) {
            if ($validated['status'] === 'sudah_kembali') {
                Siswa::where('id', $sambang->siswa_id)->update(['status' => 'aktif']);
            } elseif ($validated['status'] === 'sambang' || $validated['status'] === 'melewati_batas') {
                Siswa::where('id', $sambang->siswa_id)->update(['status' => 'pulang']);
            }
        }

        return redirect()->route('perizinan.sambang')
            ->with('success', 'Data perizinan sambang santri ' . $sambang->nama . ' berhasil diperbarui.');
    }

    /**
     * Hapus data perizinan sambang.
     */
    public function destroySambang($id)
    {
        $sambang = PerizinanSambang::findOrFail($id);
        $nama = $sambang->nama;
        $sambang->delete();

        return redirect()->route('perizinan.sambang')
            ->with('success', 'Data perizinan sambang ' . $nama . ' berhasil dihapus.');
    }

    /**
     * Tampilkan halaman perizinan sakit siswa.
     */
    public function sakit(Request $request)
    {
        $query = PerizinanSakit::query()->with('siswa');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kamar', 'like', "%{$search}%")
                  ->orWhere('sakit', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kamar')) {
            $query->where('kamar', $request->kamar);
        }

        $perizinan = $query->latest('id')->get();

        // Ambil daftar kamar unik
        $kamars = PerizinanSakit::whereNotNull('kamar')->where('kamar', '!=', '')->distinct()->orderBy('kamar')->pluck('kamar');

        // Ambil daftar seluruh siswa untuk prediksi pencarian nama & auto kamar
        $daftarSiswa = Siswa::orderBy('nama_lengkap', 'asc')->get(['id', 'nama_lengkap', 'kamar', 'status']);
        $today = Carbon::today()->toDateString();

        return view('perizinan.sakit', compact('perizinan', 'daftarSiswa', 'today', 'kamars'));
    }

    /**
     * Tampilkan formulir halaman tersendiri untuk input perizinan sakit.
     */
    public function createSakit()
    {
        $daftarSiswa = Siswa::orderBy('nama_lengkap', 'asc')->get(['id', 'nama_lengkap', 'kamar', 'status']);
        $today = Carbon::today()->toDateString();

        return view('perizinan.sakit_create', compact('daftarSiswa', 'today'));
    }

    /**
     * Simpan perizinan sakit baru.
     * Mengubah status siswa menjadi sakit.
     */
    public function storeSakit(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'      => 'required|exists:siswas,id',
            'tanggal_sakit' => 'required|date',
            'sakit'         => 'required|string|max:255',
            'keterangan'    => 'nullable|string|max:500',
        ], [
            'siswa_id.required'      => 'Silakan pilih siswa yang sakit.',
            'siswa_id.exists'        => 'Data siswa tidak valid.',
            'tanggal_sakit.required' => 'Tanggal sakit wajib diisi.',
            'sakit.required'         => 'Keluhan / jenis penyakit wajib diisi.',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        PerizinanSakit::create([
            'siswa_id'      => $siswa->id,
            'nama'          => $siswa->nama_lengkap,
            'kamar'         => $siswa->kamar,
            'tanggal_sakit' => $validated['tanggal_sakit'],
            'sakit'         => $validated['sakit'],
            'keterangan'    => $validated['keterangan'] ?? null,
            'status'        => 'sakit',
        ]);

        // Perbarui status siswa menjadi sakit
        $siswa->update(['status' => 'sakit']);

        return redirect()->route('perizinan.sakit')
            ->with('success', 'Perizinan sakit untuk ' . $siswa->nama_lengkap . ' berhasil disimpan. Status siswa telah diperbarui menjadi Sakit.');
    }

    /**
     * Tandai siswa sudah sembuh dari sakit.
     * Mengubah status perizinan menjadi sembuh dan status siswa kembali menjadi aktif.
     */
    public function sembuhSakit($id)
    {
        $sakit = PerizinanSakit::findOrFail($id);
        $sakit->update(['status' => 'sembuh']);

        if ($sakit->siswa_id) {
            Siswa::where('id', $sakit->siswa_id)->update(['status' => 'aktif']);
        }

        return redirect()->route('perizinan.sakit')
            ->with('success', 'Santri ' . $sakit->nama . ' telah ditandai Sembuh. Status siswa kembali Aktif.');
    }

    /**
     * Perbarui data perizinan sakit (modal edit).
     */
    public function updateSakit(Request $request, $id)
    {
        $sakit = PerizinanSakit::findOrFail($id);

        $validated = $request->validate([
            'tanggal_sakit' => 'required|date',
            'sakit'         => 'required|string|max:255',
            'status'        => 'required|in:sakit,sembuh',
            'keterangan'    => 'nullable|string|max:500',
        ]);

        $sakit->update([
            'tanggal_sakit' => $validated['tanggal_sakit'],
            'sakit'         => $validated['sakit'],
            'status'        => $validated['status'],
            'keterangan'    => $validated['keterangan'] ?? null,
        ]);

        // Sinkronkan status siswa
        if ($sakit->siswa_id) {
            if ($validated['status'] === 'sembuh') {
                Siswa::where('id', $sakit->siswa_id)->update(['status' => 'aktif']);
            } elseif ($validated['status'] === 'sakit') {
                Siswa::where('id', $sakit->siswa_id)->update(['status' => 'sakit']);
            }
        }

        return redirect()->route('perizinan.sakit')
            ->with('success', 'Data perizinan sakit santri ' . $sakit->nama . ' berhasil diperbarui.');
    }

    /**
     * Hapus data perizinan sakit.
     */
    public function destroySakit($id)
    {
        $sakit = PerizinanSakit::findOrFail($id);
        $nama = $sakit->nama;
        $sakit->delete();

        return redirect()->route('perizinan.sakit')
            ->with('success', 'Data perizinan sakit ' . $nama . ' berhasil dihapus.');
    }
}
