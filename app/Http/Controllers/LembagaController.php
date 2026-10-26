<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LembagaController extends Controller
{
    /**
     * Tampilkan daftar master lembaga pendidikan
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('master_lembaga', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Master Lembaga.');
        }

        $lembagas = Lembaga::withCount('users')
                           ->orderBy('urutan')
                           ->orderBy('id')
                           ->get();

        // Tambah penghitungan ruang kelas & mapel per lembaga
        foreach ($lembagas as $l) {
            $l->classrooms_count = Classroom::where('lembaga_id', $l->id)->count();
            $l->mapel_count = MataPelajaran::where('lembaga_id', $l->id)->count();
        }

        $stats = [
            'total'     => $lembagas->count(),
            'aktif'     => $lembagas->where('is_active', true)->count(),
            'formal'    => $lembagas->where('kategori', 'formal')->count(),
            'pesantren' => $lembagas->where('kategori', 'pesantren')->count(),
        ];

        $isYayasan = $authUser->isYayasan();

        return view('master.lembaga.index', compact('lembagas', 'stats', 'isYayasan'));
    }

    /**
     * Simpan lembaga baru
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_lembaga', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah lembaga.');
        }

        $validated = $request->validate([
            'kode'      => 'required|string|max:30|alpha_dash|unique:lembagas,kode',
            'nama'      => 'required|string|max:100',
            'singkatan' => 'required|string|max:30',
            'kategori'  => 'required|in:formal,pesantren,yayasan',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ], [
            'kode.unique' => 'Kode lembaga tersebut sudah digunakan. Gunakan kode unik lain.',
            'kode.alpha_dash' => 'Kode lembaga hanya boleh berupa huruf kecil, angka, atau tanda minus (-).',
        ]);

        $validated['kode'] = strtolower(trim($validated['kode']));
        $validated['is_active'] = $request->has('is_active');
        $validated['urutan'] = $validated['urutan'] ?? ((Lembaga::max('urutan') ?? 0) + 1);

        $lembaga = Lembaga::create($validated);

        // Buat pengaturan absensi default jika formal/pesantren
        if ($lembaga->kategori !== 'yayasan') {
            $lembaga->getOrCreatePengaturanAbsensi();
        }

        return redirect()->route('master.lembaga.index')->with('success', "Lembaga '{$lembaga->nama}' ({$lembaga->singkatan}) berhasil ditambahkan.");
    }

    /**
     * Perbarui data lembaga
     */
    public function update(Request $request, Lembaga $lembaga)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_lembaga', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah lembaga.');
        }

        $validated = $request->validate([
            'nama'      => 'required|string|max:100',
            'singkatan' => 'required|string|max:30',
            'kategori'  => 'required|in:formal,pesantren,yayasan',
            'urutan'    => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Jangan izinkan ubah kode core jika core lembaga
        $isCore = in_array(strtolower($lembaga->kode), ['yayasan', 'pondok', 'smp', 'sma']);
        if (!$isCore && $request->filled('kode')) {
            $reqKode = strtolower(trim($request->kode));
            $request->validate([
                'kode' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('lembagas', 'kode')->ignore($lembaga->id)],
            ]);
            $validated['kode'] = $reqKode;
        }

        $validated['is_active'] = $request->has('is_active');
        $lembaga->update($validated);

        return redirect()->route('master.lembaga.index')->with('success', "Data lembaga '{$lembaga->nama}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif lembaga
     */
    public function toggleStatus(Lembaga $lembaga)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_lembaga', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses mengubah status lembaga.');
        }

        if (strtolower($lembaga->kode) === 'yayasan') {
            return redirect()->back()->with('error', 'Lembaga induk Yayasan tidak dapat dinonaktifkan.');
        }

        $lembaga->update(['is_active' => !$lembaga->is_active]);

        $statusText = $lembaga->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Lembaga {$lembaga->nama} berhasil {$statusText}.");
    }

    /**
     * Hapus lembaga dengan proteksi ketat
     */
    public function destroy(Lembaga $lembaga)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_lembaga', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus lembaga.');
        }

        // Proteksi lembaga inti sistem
        if (in_array(strtolower($lembaga->kode), ['yayasan', 'pondok', 'smp', 'sma'])) {
            return redirect()->back()->with('error', "Lembaga utama ({$lembaga->singkatan}) merupakan fondasi inti sistem dan tidak boleh dihapus.");
        }

        // Cek pengguna terkait
        $userCount = $lembaga->users()->count();
        if ($userCount > 0) {
            return redirect()->back()->with('error', "Lembaga {$lembaga->nama} masih memiliki {$userCount} pengguna terafiliasi. Silakan lepaskan afiliasi pengguna terlebih dahulu.");
        }

        // Cek kelas terkait
        $classCount = Classroom::where('lembaga_id', $lembaga->id)->count();
        if ($classCount > 0) {
            return redirect()->back()->with('error', "Lembaga {$lembaga->nama} masih memiliki {$classCount} ruang kelas terdaftar.");
        }

        $nama = $lembaga->nama;
        $lembaga->delete();

        return redirect()->route('master.lembaga.index')->with('success', "Lembaga {$nama} berhasil dihapus.");
    }
}
