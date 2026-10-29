<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar menu sistem yang dapat diatur hak aksesnya
     */
    public static function systemMenus(): array
    {
        return [
            'siswa'              => 'Data Siswa & Santri',
            'pendaftaran'        => 'Pendaftaran Baru',
            'perizinan_sambang'  => 'Perizinan Sambang',
            'perizinan_sakit'    => 'Perizinan Sakit',
            'presensi'           => 'Presensi Guru (Scan QR)',
            'rekap_absensi'      => 'Rekap Absensi',
            'sekolah'            => 'Sekolah (Jurnal & Absensi)',
            'pengaturan_absensi' => 'Pengaturan Absensi Guru',
            'guru_sekolah'       => 'Guru Sekolah (Pengelolaan & Approval Mapel)',
            'plotting_mapel'     => 'Plotting Mata Pelajaran',
            'plotting_kelas'     => 'Plotting Kelas',
            'plotting_kamar'     => 'Plotting Kamar Santri (Pondok)',
            'master_kelas'       => 'Master Ruang Kelas',
            'master_jam'         => 'Master Jam Pelajaran (Time Slot)',
            'master_lembaga'     => 'Master Lembaga Pendidikan',
            'pengguna'           => 'Pengguna & Hak Akses',
        ];
    }


    /**
     * Menampilkan daftar pengguna
     */
    public function index(Request $request)
    {
        $query = User::with(['primaryLembaga', 'lembagas']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('lembaga_id')) {
            $query->whereHas('lembagas', function ($q) use ($request) {
                $q->where('lembagas.id', $request->lembaga_id);
            });
        }

        $users = $query->latest('id')->paginate(15)->withQueryString();
        $lembagas = Lembaga::where('is_active', true)->orderBy('urutan')->get();

        return view('pengguna.index', compact('users', 'lembagas'));
    }

    /**
     * Form tambah pengguna baru
     */
    public function create()
    {
        $lembagas = Lembaga::where('is_active', true)->orderBy('urutan')->get();
        $menus = self::systemMenus();

        return view('pengguna.create', compact('lembagas', 'menus'));
    }

    /**
     * Simpan pengguna baru beserta hak aksesnya
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email|max:255',
            'password'    => 'required|string|min:6',
            'role'        => 'required|in:yayasan,admin_lembaga,guru',
            'jabatan'     => 'nullable|string|max:100',
            'lembagas'    => 'required|array|min:1',
            'lembagas.*'  => 'exists:lembagas,id',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required'       => 'Nama pengguna wajib diisi.',
            'email.required'      => 'Email wajib diisi.',
            'email.unique'        => 'Email sudah digunakan oleh akun lain.',
            'password.required'   => 'Kata sandi wajib diisi.',
            'password.min'        => 'Kata sandi minimal 6 karakter.',
            'role.required'       => 'Peran pengguna wajib dipilih.',
            'lembagas.required'   => 'Pilih minimal satu lembaga.',
        ]);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'password'             => Hash::make($validated['password']),
            'role'                 => $validated['role'],
            'jabatan'              => $validated['jabatan'] ?? null,
            'primary_lembaga_id'   => $validated['lembagas'][0] ?? null,
            'is_active'            => $request->boolean('is_active', true),
            'must_change_password' => $request->boolean('must_change_password', true),
        ]);

        // Simpan relasi lembaga
        $user->lembagas()->sync($validated['lembagas']);

        // Simpan matriks hak akses
        $this->savePermissions($user, $request->input('permissions', []));

        // Simpan kredensial ke flash session agar Superadmin bisa langsung menyalin format WhatsApp
        session()->flash('user_credentials', [
            'action'    => 'created',
            'name'      => $user->name,
            'email'     => $user->email,
            'password'  => $validated['password'],
            'role'      => $user->role,
            'login_url' => route('login'),
        ]);

        return redirect()->route('pengguna.index')->with('success', 'Akun ' . $user->name . ' berhasil dibuat.');
    }

    /**
     * Form edit pengguna
     */
    public function edit(User $pengguna)
    {
        $pengguna->load(['lembagas', 'permissions']);
        $lembagas = Lembaga::where('is_active', true)->orderBy('urutan')->get();
        $menus = self::systemMenus();

        // Siapkan permission map
        $userPerms = [];
        foreach ($pengguna->permissions as $perm) {
            $userPerms[$perm->menu_code] = [
                'view'   => (bool) $perm->can_view,
                'create' => (bool) $perm->can_create,
                'edit'   => (bool) $perm->can_edit,
                'delete' => (bool) $perm->can_delete,
            ];
        }

        return view('pengguna.edit', compact('pengguna', 'lembagas', 'menus', 'userPerms'));
    }

    /**
     * Perbarui data pengguna dan hak akses
     */
    public function update(Request $request, User $pengguna)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($pengguna->id)],
            'password'    => 'nullable|string|min:6',
            'role'        => 'required|in:yayasan,admin_lembaga,guru',
            'jabatan'     => 'nullable|string|max:100',
            'lembagas'    => 'required|array|min:1',
            'lembagas.*'  => 'exists:lembagas,id',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required'       => 'Nama pengguna wajib diisi.',
            'email.required'      => 'Email wajib diisi.',
            'email.unique'        => 'Email sudah digunakan akun lain.',
            'password.min'        => 'Kata sandi minimal 6 karakter.',
            'role.required'       => 'Peran pengguna wajib dipilih.',
            'lembagas.required'   => 'Pilih minimal satu lembaga.',
        ]);

        $updateData = [
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'role'               => $validated['role'],
            'jabatan'            => $validated['jabatan'] ?? null,
            'primary_lembaga_id' => $validated['lembagas'][0] ?? null,
            'is_active'          => $request->boolean('is_active'),
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $pengguna->update($updateData);
        $pengguna->lembagas()->sync($validated['lembagas']);

        $this->savePermissions($pengguna, $request->input('permissions', []));

        return redirect()->route('pengguna.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Hapus pengguna
     */
    public function destroy(User $pengguna)
    {
        if (Auth::id() === $pengguna->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $pengguna->delete();

        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Reset kata sandi pengguna oleh Superadmin/Admin
     */
    public function resetPassword(Request $request, User $pengguna)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min'      => 'Kata sandi baru minimal 6 karakter.',
        ]);

        $pengguna->password = Hash::make($validated['password']);
        $pengguna->must_change_password = true;
        $pengguna->save();

        session()->flash('user_credentials', [
            'action'    => 'reset',
            'name'      => $pengguna->name,
            'email'     => $pengguna->email,
            'password'  => $validated['password'],
            'role'      => $pengguna->role,
            'login_url' => route('login'),
        ]);

        return redirect()->route('pengguna.index')->with('success', 'Kata sandi akun ' . $pengguna->name . ' berhasil direset.');
    }

    /**
     * Simpan matriks izin menu
     */
    private function savePermissions(User $user, array $permissionsInput): void
    {
        $menus = self::systemMenus();

        foreach ($menus as $menuCode => $label) {
            $input = $permissionsInput[$menuCode] ?? [];

            UserPermission::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'menu_code' => $menuCode,
                ],
                [
                    'can_view'   => !empty($input['view']),
                    'can_create' => !empty($input['create']),
                    'can_edit'   => !empty($input['edit']),
                    'can_delete' => !empty($input['delete']),
                ]
            );
        }
    }
}
