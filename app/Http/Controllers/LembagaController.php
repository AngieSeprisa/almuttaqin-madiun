<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LembagaController extends Controller
{
    /**
     * Normalisasi input lembaga sebelum validasi agar spasi, huruf kapital pada kode,
     * atau tanda '-' pada email tidak menyebabkan kegagalan validasi yang membingungkan.
     */
    protected function normalizeLembagaInput(Request $request, bool $includeKode = true): void
    {
        $merged = [];

        if ($includeKode && $request->has('kode')) {
            $rawKode = strtolower(trim((string) $request->input('kode')));
            $cleanKode = preg_replace('/[^a-z0-9_-]+/', '-', $rawKode);
            $cleanKode = preg_replace('/-+/', '-', (string) $cleanKode);
            $merged['kode'] = trim((string) $cleanKode, '-_');
        }

        foreach (['nama', 'singkatan', 'alamat', 'telepon'] as $field) {
            if ($request->has($field)) {
                $val = trim((string) $request->input($field));
                $merged[$field] = $val === '' ? null : $val;
            }
        }

        if ($request->has('email')) {
            $email = trim((string) $request->input('email'));
            $merged['email'] = ($email === '' || $email === '-') ? null : $email;
        }

        if ($request->has('urutan') && trim((string) $request->input('urutan')) === '') {
            $merged['urutan'] = null;
        }

        if (!empty($merged)) {
            $request->merge($merged);
        }
    }

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

        $lembagasQuery = Lembaga::withCount('users')
                           ->orderBy('urutan')
                           ->orderBy('id');

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if (!$authUser->isYayasan()) {
            if ($scopedFormal && !$authUser->canDo('master_lembaga', 'create')) {
                $lembagasQuery->whereKey($scopedFormal->id);
            } else {
                $lembagasQuery->whereIn('id', $authUser->accessibleLembagas()->pluck('id'));
            }
        }

        $lembagas = $lembagasQuery->get();

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

        $this->normalizeLembagaInput($request, true);

        $validated = $request->validate([
            'kode'      => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('lembagas', 'kode')],
            'nama'      => 'required|string|max:120',
            'singkatan' => 'required|string|max:60',
            'kategori'  => 'required|in:formal,pesantren,yayasan',
            'alamat'    => 'nullable|string|max:1000',
            'telepon'   => 'nullable|string|max:40',
            'email'     => 'nullable|email|max:120',
            'logo'      => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'urutan'    => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ], [
            'kode.required'      => 'Kode unik lembaga wajib diisi.',
            'kode.max'           => 'Kode lembaga maksimal 60 karakter.',
            'kode.unique'        => 'Kode lembaga tersebut sudah digunakan. Gunakan kode unik lain.',
            'kode.alpha_dash'    => 'Kode lembaga hanya boleh berupa huruf, angka, tanda minus (-), atau garis bawah (_).',
            'nama.required'      => 'Nama lembaga wajib diisi.',
            'nama.max'           => 'Nama lembaga maksimal 120 karakter.',
            'singkatan.required' => 'Singkatan lembaga wajib diisi.',
            'singkatan.max'      => 'Singkatan lembaga maksimal 60 karakter.',
            'kategori.required'  => 'Kategori lembaga wajib dipilih.',
            'kategori.in'        => 'Kategori lembaga tidak valid.',
            'email.email'        => 'Format email lembaga tidak valid. Kosongkan jika tidak ada email.',
            'logo.image'         => 'File logo harus berupa gambar.',
            'logo.mimes'         => 'Format logo harus JPG, PNG, WEBP, atau GIF.',
            'logo.max'           => 'Ukuran logo maksimal 5 MB.',
            'urutan.integer'     => 'Urutan harus berupa angka bulat.',
            'urutan.min'         => 'Urutan minimal bernilai 1.',
        ]);

        // Pastikan unik secara case-insensitive di seluruh driver database (MySQL & SQLite)
        if (Lembaga::whereRaw('LOWER(kode) = ?', [$validated['kode']])->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kode' => 'Kode lembaga tersebut sudah digunakan. Gunakan kode unik lain.']);
        }

        unset($validated['logo']);
        if ($request->hasFile('logo')) {
            try {
                $validated['logo'] = $request->file('logo')->store('lembaga', 'public');
            } catch (\Throwable $e) {
                Log::error('Gagal mengunggah logo lembaga baru: ' . $e->getMessage());
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal menyimpan file logo lembaga. Silakan coba lagi atau simpan tanpa logo terlebih dahulu.');
            }
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['urutan'] = $validated['urutan'] ?? ((Lembaga::max('urutan') ?? 0) + 1);

        $lembaga = Lembaga::create($validated);

        // Jika dibuat oleh akun non-Yayasan yang memiliki hak create, langsung berikan akses ke lembaga baru tersebut
        if (!$authUser->isYayasan()) {
            $authUser->lembagas()->syncWithoutDetaching([$lembaga->id]);
        }

        // Buat pengaturan absensi default jika formal/pesantren
        if ($lembaga->kategori !== 'yayasan') {
            try {
                $lembaga->getOrCreatePengaturanAbsensi();
            } catch (\Throwable $e) {
                Log::warning("Gagal membuat pengaturan absensi default untuk lembaga {$lembaga->kode}: " . $e->getMessage());
            }
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
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
            abort(403, 'Anda hanya dapat mengubah data lembaga yang berada dalam akses Anda.');
        }

        $isCore = in_array(strtolower($lembaga->kode), ['yayasan', 'pondok', 'smp', 'sma']);
        $this->normalizeLembagaInput($request, !$isCore);

        $validated = $request->validate([
            'nama'      => 'required|string|max:120',
            'singkatan' => 'required|string|max:60',
            'kategori'  => 'required|in:formal,pesantren,yayasan',
            'alamat'    => 'nullable|string|max:1000',
            'telepon'   => 'nullable|string|max:40',
            'email'     => 'nullable|email|max:120',
            'logo'      => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'urutan'    => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ], [
            'nama.required'      => 'Nama lembaga wajib diisi.',
            'singkatan.required' => 'Singkatan lembaga wajib diisi.',
            'email.email'        => 'Format email lembaga tidak valid. Kosongkan jika tidak ada email.',
            'logo.image'         => 'File logo harus berupa gambar.',
            'logo.mimes'         => 'Format logo harus JPG, PNG, WEBP, atau GIF.',
            'logo.max'           => 'Ukuran logo maksimal 5 MB.',
        ]);

        unset($validated['logo']);
        if ($request->hasFile('logo')) {
            try {
                $newLogo = $request->file('logo')->store('lembaga', 'public');
                if ($lembaga->logo && Storage::disk('public')->exists($lembaga->logo)) {
                    Storage::disk('public')->delete($lembaga->logo);
                }
                $validated['logo'] = $newLogo;
            } catch (\Throwable $e) {
                Log::error('Gagal mengunggah logo lembaga: ' . $e->getMessage());
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Gagal menyimpan file logo lembaga. Silakan coba lagi.');
            }
        } elseif ($request->boolean('hapus_logo')) {
            if ($lembaga->logo && Storage::disk('public')->exists($lembaga->logo)) {
                Storage::disk('public')->delete($lembaga->logo);
            }
            $validated['logo'] = null;
        }

        // Jangan izinkan ubah kode core jika core lembaga
        if (!$isCore && $request->filled('kode')) {
            $reqKode = strtolower(trim((string) $request->kode));
            $request->validate([
                'kode' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('lembagas', 'kode')->ignore($lembaga->id)],
            ], [
                'kode.unique'     => 'Kode lembaga tersebut sudah digunakan. Gunakan kode unik lain.',
                'kode.alpha_dash' => 'Kode lembaga hanya boleh berupa huruf, angka, tanda minus (-), atau garis bawah (_).',
            ]);
            if (Lembaga::where('id', '!=', $lembaga->id)->whereRaw('LOWER(kode) = ?', [$reqKode])->exists()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['kode' => 'Kode lembaga tersebut sudah digunakan. Gunakan kode unik lain.']);
            }
            $validated['kode'] = $reqKode;
        }

        $oldKode = $lembaga->kode;
        $oldSingkatan = $lembaga->singkatan;
        $oldJenjangKey = $lembaga->jenjang_key;
        $oldAliases = array_values(array_unique(array_filter(array_merge(
            Lembaga::jenjangAliasesForRoom($oldKode),
            [$oldKode, $oldSingkatan, $oldJenjangKey]
        ))));

        $validated['is_active'] = $request->has('is_active');
        $lembaga->update($validated);
        $lembaga->refresh();

        $newJenjangKey = $lembaga->jenjang_key;
        if (strcasecmp($oldJenjangKey, $newJenjangKey) !== 0 || strcasecmp((string) $oldKode, (string) $lembaga->kode) !== 0 || strcasecmp((string) $oldSingkatan, (string) $lembaga->singkatan) !== 0) {
            Lembaga::propagateRename($lembaga, $oldAliases, $newJenjangKey);
        }

        // Jika sesi active_lembaga sedang berada pada kode lama, perbarui ke kode baru
        if (session('active_lembaga') && strcasecmp((string) session('active_lembaga'), (string) $oldKode) === 0 && $oldKode !== $lembaga->kode) {
            session(['active_lembaga' => $lembaga->kode]);
        }

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
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
            abort(403, 'Anda hanya dapat mengubah status lembaga yang berada dalam akses Anda.');
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
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
            abort(403, 'Anda hanya dapat menghapus lembaga yang berada dalam akses Anda.');
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
