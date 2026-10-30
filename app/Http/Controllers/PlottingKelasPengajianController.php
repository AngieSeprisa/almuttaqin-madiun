<?php

namespace App\Http\Controllers;

use App\Models\MasterKelasPengajian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlottingKelasPengajianController extends Controller
{
    /**
     * Normalisasi input siswa_id / kelas_id (mendukung single ID, array, maupun string dipisah koma).
     */
    protected function parseIds(mixed $raw): array
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
     * Tampilkan halaman Plotting Kelas Pengajian bergaya Plotting Kamar (Daftar Kelas & Belum Terdaftar).
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'view')
            && !$authUser->canDo('plotting_kamar', 'view')
            && !$authUser->canDo('pengaturan_pondok', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Kelas Pengajian.');
        }

        // Pastikan kelas pengajian standar tersedia
        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $dbMasterKelas = MasterKelasPengajian::orderBy('id')->get();

        // Ambil seluruh santri aktif beserta relasi pendaftaran dan kelas pengajian
        $allActiveSiswa = Siswa::where('status', 'aktif')
            ->with(['pendaftaran', 'kelasPengajian'])
            ->orderBy('nama_lengkap')
            ->get();

        // Susun data kelas beserta anggota santri di dalamnya
        $dataKelas = [];
        foreach ($dbMasterKelas as $mk) {
            $anggota = $allActiveSiswa->filter(fn ($s) => (int) $s->kelas_pengajian_id === (int) $mk->id)->values();

            $dataKelas[] = (object) [
                'id'           => $mk->id,
                'kelas'        => $mk,
                'nama_kelas'   => $mk->nama_kelas,
                'kode_kelas'   => $mk->kode_kelas,
                'jenjang'      => $mk->jenjang,
                'jumlah_siswa' => $anggota->count(),
                'anggota'      => $anggota,
            ];
        }

        // Santri aktif yang belum terdaftar di kelas pengajian manapun
        $belumTerdaftar = $allActiveSiswa->filter(function ($s) {
            return empty($s->kelas_pengajian_id);
        })->values();

        $activeTab = $request->get('tab', 'kelas');
        if (!in_array($activeTab, ['kelas', 'belum_terdaftar'], true)) {
            $activeTab = 'kelas';
        }

        return view('plotting-pengajian.index', compact(
            'dbMasterKelas',
            'dataKelas',
            'belumTerdaftar',
            'allActiveSiswa',
            'activeTab'
        ));
    }

    /**
     * Mutasi / tetapkan santri (tunggal maupun massal) ke Kelas Pengajian tujuan.
     */
    public function mutasi(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memutasi kelas pengajian santri.');
        }

        $request->validate([
            'siswa_id'           => 'required',
            'kelas_pengajian_id' => 'nullable',
        ]);

        $siswaIds = $this->parseIds($request->siswa_id);

        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih untuk ditetapkan.');
        }

        $targetId = $request->kelas_pengajian_id;

        // Jika opsi kosongkan / unassign dipilih
        if ($targetId === 'unassign' || $targetId === '' || $targetId === null || $targetId === '0') {
            $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => null]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "{$count} santri berhasil dikeluarkan dari kelas pengajian (Belum Terdaftar).",
                ]);
            }

            return redirect()->route('plotting-pengajian.index', ['tab' => 'belum_terdaftar'])
                ->with('success', "{$count} santri berhasil dikeluarkan dari kelas pengajian (Belum Terdaftar).");
        }

        $kelas = MasterKelasPengajian::find($targetId);
        if (!$kelas) {
            return back()->with('error', 'Kelas pengajian tujuan tidak ditemukan di sistem.');
        }

        $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => $kelas->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} santri berhasil ditempatkan ke {$kelas->nama_kelas}.",
                'kelas'   => $kelas->nama_kelas,
            ]);
        }

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
            ->with('success', "{$count} santri berhasil ditempatkan ke {$kelas->nama_kelas}.");
    }

    /**
     * Keluarkan santri dari kelas pengajian (unassign -> pindah ke Belum Terdaftar).
     */
    public function unassign(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengeluarkan santri dari kelas pengajian.');
        }

        $request->validate([
            'siswa_id' => 'required',
        ]);

        $siswaIds = $this->parseIds($request->siswa_id);
        if (empty($siswaIds)) {
            return back()->with('error', 'Tidak ada santri yang dipilih.');
        }

        $count = Siswa::whereIn('id', $siswaIds)->update(['kelas_pengajian_id' => null]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} santri berhasil dikeluarkan dari kelas pengajian.",
            ]);
        }

        return redirect()->back()
            ->with('success', "{$count} santri berhasil dikeluarkan dari kelas pengajian.");
    }

    /**
     * Tambah Master Kelas Pengajian baru.
     */
    public function storeKelas(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'create')
            && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah kelas pengajian.');
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100', Rule::unique('master_kelas_pengajians', 'nama_kelas')],
            'kode_kelas' => ['nullable', 'string', 'max:50', Rule::unique('master_kelas_pengajians', 'kode_kelas')],
            'jenjang'    => ['required', 'in:smp,sma,reguler,umum'],
        ], [
            'nama_kelas.required' => 'Nama kelas pengajian wajib diisi.',
            'nama_kelas.unique'   => 'Nama kelas pengajian tersebut sudah ada.',
            'kode_kelas.unique'   => 'Kode kelas pengajian tersebut sudah ada.',
        ]);

        $kode = !empty($validated['kode_kelas'])
            ? strtoupper(trim($validated['kode_kelas']))
            : strtoupper('KLS_' . Str::slug($validated['nama_kelas'], '_'));

        $kelas = MasterKelasPengajian::create([
            'kode_kelas' => $kode,
            'nama_kelas' => trim($validated['nama_kelas']),
            'jenjang'    => $validated['jenjang'],
        ]);

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
            ->with('success', "Kelas {$kelas->nama_kelas} berhasil ditambahkan dan siap ditempati santri.");
    }

    /**
     * Perbarui Master Kelas Pengajian.
     */
    public function updateKelas(Request $request, MasterKelasPengajian $kelas)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'edit')
            && !$authUser->canDo('plotting_kamar', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah kelas pengajian.');
        }

        $validated = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100', Rule::unique('master_kelas_pengajians', 'nama_kelas')->ignore($kelas->id)],
            'kode_kelas' => ['required', 'string', 'max:50', Rule::unique('master_kelas_pengajians', 'kode_kelas')->ignore($kelas->id)],
            'jenjang'    => ['required', 'in:smp,sma,reguler,umum'],
        ]);

        $kelas->update([
            'kode_kelas' => strtoupper(trim($validated['kode_kelas'])),
            'nama_kelas' => trim($validated['nama_kelas']),
            'jenjang'    => $validated['jenjang'],
        ]);

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
            ->with('success', "Data kelas {$kelas->nama_kelas} berhasil diperbarui.");
    }

    /**
     * Hapus Master Kelas Pengajian (dengan proteksi santri aktif).
     */
    public function destroyKelas(Request $request, MasterKelasPengajian $kelas)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'delete')
            && !$authUser->canDo('plotting_kamar', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kelas pengajian.');
        }

        $santriCount = $kelas->siswas()->where('status', 'aktif')->count();
        if ($santriCount > 0) {
            return redirect()->back()->with(
                'error',
                "Kelas \"{$kelas->nama_kelas}\" tidak dapat dihapus karena masih memiliki {$santriCount} santri aktif. Silakan pindahkan santri terlebih dahulu."
            );
        }

        $nama = $kelas->nama_kelas;
        $kelas->delete();

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
            ->with('success', "Kelas \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Hapus kelas pengajian terpilih secara massal (dengan proteksi santri aktif).
     */
    public function destroyBulkKelas(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'delete')
            && !$authUser->canDo('plotting_kamar', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus kelas pengajian.');
        }

        $request->validate([
            'kelas_ids' => 'required',
        ]);

        $ids = $this->parseIds($request->kelas_ids);
        if (empty($ids)) {
            return redirect()->back()->with('error', 'Tidak ada kelas yang dipilih untuk dihapus.');
        }

        $classes = MasterKelasPengajian::whereIn('id', $ids)->get();
        $deletedCount = 0;
        $blockedNames = [];

        foreach ($classes as $c) {
            $santriCount = $c->siswas()->where('status', 'aktif')->count();
            if ($santriCount > 0) {
                $blockedNames[] = "{$c->nama_kelas} ({$santriCount} santri)";
                continue;
            }
            $c->delete();
            $deletedCount++;
        }

        if ($deletedCount > 0 && empty($blockedNames)) {
            return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
                ->with('success', "{$deletedCount} kelas terpilih berhasil dihapus.");
        }

        if ($deletedCount > 0 && !empty($blockedNames)) {
            return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])->with(
                'success',
                "{$deletedCount} kelas kosong berhasil dihapus. Kelas berikut dilewati karena masih memiliki santri: " . implode(', ', $blockedNames) . '.'
            );
        }

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])->with(
            'error',
            'Kelas terpilih tidak dapat dihapus karena masih memiliki santri aktif: ' . implode(', ', $blockedNames) . '.'
        );
    }

    /**
     * Muat kelas pengajian standar secara otomatis.
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan()
            && !$authUser->canDo('plotting_pengajian', 'create')
            && !$authUser->canDo('plotting_kamar', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $added = MasterKelasPengajian::seedStandar();

        return redirect()->route('plotting-pengajian.index', ['tab' => 'kelas'])
            ->with('success', "Berhasil memeriksa dan menambahkan {$added} kelas pengajian standar.");
    }

    /**
     * Endpoint JSON data santri anggota kelas pengajian tertentu (untuk modal anggota).
     */
    public function anggota(MasterKelasPengajian $kelas)
    {
        $anggota = Siswa::where('status', 'aktif')
            ->where('kelas_pengajian_id', $kelas->id)
            ->with(['pendaftaran', 'kelasPengajian'])
            ->orderBy('nama_lengkap')
            ->get()
            ->map(function ($s) {
                $gender = strtolower((string) ($s->pendaftaran?->jenis_kelamin ?? ''));
                $genderLabel = in_array($gender, ['p', 'perempuan', 'putri']) ? 'Putri (P)' : 'Putra (L)';

                return [
                    'id'            => $s->id,
                    'nama_lengkap'  => $s->nama_lengkap,
                    'nisn'          => $s->pendaftaran?->nisn ?: ($s->pendaftaran?->no_induk ?: '-'),
                    'jenis_kelamin' => $genderLabel,
                    'kamar'         => $s->kamar ?: '-',
                    'kelas'         => $s->kelas ?: '-',
                    'jenjang'       => strtoupper($s->jenjang ?: 'Reguler'),
                ];
            });

        return response()->json([
            'kelas'   => $kelas,
            'anggota' => $anggota,
        ]);
    }
}
