<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PelanggaranController extends Controller
{
    protected function scopedFormal(): ?Lembaga
    {
        return Lembaga::activeScopedFormal(auth()->user());
    }

    protected function ensureSiswaInRoom(?int $siswaId): void
    {
        $scoped = $this->scopedFormal();
        if (!$scoped) {
            return;
        }

        if (!$siswaId || !Siswa::forLembaga($scoped->kode)->whereKey($siswaId)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data pelanggaran siswa lembaga lain.');
        }
    }

    /**
     * Halaman Input Pelanggaran.
     */
    public function input()
    {
        $scoped = $this->scopedFormal();
        $query = Siswa::query()
            ->with('kelasPengajian')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereNotIn('status', ['keluar', 'lulus', 'alumni', 'nonaktif', 'ditolak', 'Ditolak', 'Lulus', 'mutasi', 'Mutasi']);
            })
            ->orderBy('nama_lengkap');

        if ($scoped) {
            $query->forLembaga($scoped->kode);
        }

        $daftarSiswa = $query->get(['id', 'nama_lengkap', 'kelas', 'jenjang', 'kelas_pengajian_id']);

        return view('pelanggaran.input', compact('daftarSiswa'));
    }

    /**
     * Simpan data pelanggaran baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id'    => ['required', 'integer', 'exists:siswas,id'],
            'pelanggaran' => ['required', 'string', 'max:2000'],
        ], [
            'siswa_id.required'    => 'Nama siswa wajib dipilih.',
            'siswa_id.exists'      => 'Siswa yang dipilih tidak ditemukan.',
            'pelanggaran.required' => 'Pelanggaran wajib diisi.',
        ]);

        $this->ensureSiswaInRoom((int) $validated['siswa_id']);

        $siswa = Siswa::with('kelasPengajian')->findOrFail($validated['siswa_id']);

        Pelanggaran::create([
            'siswa_id'    => $siswa->id,
            'nama_siswa'  => $siswa->nama_lengkap,
            'kelas'       => $siswa->kelas_tampil,
            'pelanggaran' => trim($validated['pelanggaran']),
            'created_by'  => Auth::id(),
        ]);

        return redirect()->route('pelanggaran.input')
            ->with('success', 'Data pelanggaran berhasil disimpan.');
    }

    /**
     * Halaman Rekap Pelanggaran.
     */
    public function rekap(Request $request)
    {
        $scoped = $this->scopedFormal();
        $query = Pelanggaran::query()->with('siswa');

        if ($scoped) {
            $query->whereHas('siswa', fn ($s) => $s->forLembaga($scoped->kode));
        }

        $kelasQuery = Pelanggaran::query()
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', '-');
        if ($scoped) {
            $kelasQuery->whereHas('siswa', fn ($s) => $s->forLembaga($scoped->kode));
        }
        $kelasList = $kelasQuery->distinct()->orderBy('kelas')->pluck('kelas');

        if ($request->filled('kelas')) {
            $query->where('kelas', trim((string) $request->kelas));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_siswa', 'like', "%{$s}%")
                  ->orWhere('kelas', 'like', "%{$s}%")
                  ->orWhere('pelanggaran', 'like', "%{$s}%");
            });
        }

        $pelanggarans = $query->latest('created_at')->latest('id')->paginate(20)->withQueryString();

        return view('pelanggaran.rekap', compact('pelanggarans', 'kelasList'));
    }

    /**
     * Perbarui data pelanggaran dari halaman Rekap Pelanggaran.
     */
    public function update(Request $request, Pelanggaran $pelanggaran)
    {
        $this->ensureSiswaInRoom($pelanggaran->siswa_id);

        $validated = $request->validate([
            'pelanggaran' => ['required', 'string', 'max:2000'],
        ], [
            'pelanggaran.required' => 'Pelanggaran wajib diisi.',
        ]);

        $pelanggaran->update([
            'pelanggaran' => trim($validated['pelanggaran']),
        ]);

        return redirect()->back()
            ->with('success', 'Data pelanggaran berhasil diperbarui.');
    }

    /**
     * Hapus data pelanggaran dari halaman Rekap Pelanggaran.
     */
    public function destroy(Pelanggaran $pelanggaran)
    {
        $this->ensureSiswaInRoom($pelanggaran->siswa_id);

        $pelanggaran->delete();

        return redirect()->back()
            ->with('success', 'Data pelanggaran berhasil dihapus.');
    }
}
