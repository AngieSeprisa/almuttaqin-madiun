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
     * Daftar grup menu dan submenu sistem yang dapat diatur hak aksesnya
     */
    public static function systemMenuGroups(): array
    {
        return [
            'Data Warga' => [
                'siswa'        => 'Siswa',
                'guru_warga'   => 'Guru',
                'lulus_warga'  => 'Lulus',
                'mutasi_warga' => 'Mutasi',
            ],
            'Input NFC' => [
                'nfc' => 'Input NFC',
            ],
            'Sekolah' => [
                'sekolah_jurnal'       => 'Jurnal',
                'sekolah_rekap_jurnal' => 'Rekap Jurnal',
                'ekskul'               => 'Ekstrakurikuler',
                'jadwal_pelajaran'     => 'Jadwal Pelajaran',
                'jadwal_saya'          => 'Jadwal Mengajar Saya',
            ],
            'Ujian' => [
                'ujian_soal'    => 'Soal Ujian',
                'ujian_ljk'     => 'LJK',
                'ujian_sesi'    => 'Sesi Ujian',
                'ujian_koreksi' => 'Koreksi Ujian',
                'ujian_nilai'   => 'Nilai Ujian',
            ],
            'Pondok' => [
                'materi_pondok'    => 'Daftar Materi',
                'target_pengajian' => 'Target Pengajian',
                'jurnal_mengaji'   => 'Jurnal Mengaji',
            ],
            'Perpustakaan' => [
                'daftar_buku' => 'Daftar Buku',
                'pinjam_buku' => 'Pinjam Buku',
            ],
            'Perizinan' => [
                'perizinan_sambang' => 'Sambang',
                'perizinan_sakit'   => 'Sakit',
                'perizinan_haid'    => 'Haid',
            ],
            'Pendaftaran' => [
                'pendaftaran' => 'Pendaftaran',
            ],
            'Rekap' => [
                'rekap_sholat'    => 'Sholat',
                'rekap_pengajian' => 'Pengajian',
                'rekap_sekolah'   => 'Sekolah',
                'rekap_guru'      => 'Guru',
                'rekap_ekskul'    => 'Ekstrakurikuler',
            ],
            'Data Master' => [
                'master_jam'          => 'Jam Pelajaran',
                'master_lembaga'      => 'Lembaga',
                'master_import_siswa' => 'Import Siswa',
            ],
            'Pengaturan Sekolah' => [
                'pengaturan_sesi' => 'Sesi Presensi RFID',
                'monitor_rfid'    => 'Monitor Live RFID',
                'guru_sekolah'    => 'Guru Sekolah',
                'plotting_mapel'  => 'Plotting Mata Pelajaran',
                'plotting_kelas'  => 'Plotting Kelas',
                'plotting_ekskul' => 'Ekstrakurikuler',
            ],
            'Pengaturan Pondok' => [
                'plotting_kamar'     => 'Plotting Kamar',
                'plotting_pengajian' => 'Plotting Pengajian',
            ],
            'Pengguna & Akses' => [
                'pengguna' => 'Pengguna & Akses',
            ],
        ];
    }

    /**
     * Daftar menu sistem (flat) yang dapat diatur hak aksesnya
     */
    public static function systemMenus(): array
    {
        $flat = [];
        foreach (self::systemMenuGroups() as $groupName => $items) {
            foreach ($items as $code => $label) {
                $flat[$code] = $groupName === $label ? $label : $groupName . ' - ' . $label;
            }
        }
        return $flat;
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
        $menuGroups = self::systemMenuGroups();

        return view('pengguna.create', compact('lembagas', 'menus', 'menuGroups'));
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

        $primaryLembagaId = $validated['lembagas'][0] ?? null;
        if (!empty($validated['jabatan'])) {
            $jab = strtolower($validated['jabatan']);
            foreach ($validated['lembagas'] as $lemId) {
                $lem = Lembaga::find($lemId);
                if ($lem && (str_contains($jab, strtolower($lem->kode ?? '')) || str_contains($jab, strtolower($lem->singkatan ?? '')))) {
                    $primaryLembagaId = $lem->id;
                    break;
                }
            }
        }

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'password'             => Hash::make($validated['password']),
            'role'                 => $validated['role'],
            'jabatan'              => $validated['jabatan'] ?? null,
            'primary_lembaga_id'   => $primaryLembagaId,
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
        $menuGroups = self::systemMenuGroups();

        // Siapkan permission map untuk seluruh kode menu granular
        $userPerms = [];
        foreach ($pengguna->permissions as $perm) {
            $userPerms[$perm->menu_code] = [
                'view'   => (bool) $perm->can_view,
                'create' => (bool) $perm->can_create,
                'edit'   => (bool) $perm->can_edit,
                'delete' => (bool) $perm->can_delete,
            ];
        }

        foreach ($menus as $code => $label) {
            if (!array_key_exists($code, $userPerms)) {
                $userPerms[$code] = [
                    'view'   => $pengguna->canDo($code, 'view'),
                    'create' => $pengguna->canDo($code, 'create'),
                    'edit'   => $pengguna->canDo($code, 'edit'),
                    'delete' => $pengguna->canDo($code, 'delete'),
                ];
            }
        }

        return view('pengguna.edit', compact('pengguna', 'lembagas', 'menus', 'menuGroups', 'userPerms'));
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

        $primaryLembagaId = $validated['lembagas'][0] ?? null;
        if (!empty($validated['jabatan'])) {
            $jab = strtolower($validated['jabatan']);
            foreach ($validated['lembagas'] as $lemId) {
                $lem = Lembaga::find($lemId);
                if ($lem && (str_contains($jab, strtolower($lem->kode ?? '')) || str_contains($jab, strtolower($lem->singkatan ?? '')))) {
                    $primaryLembagaId = $lem->id;
                    break;
                }
            }
        }

        $updateData = [
            'name'               => $validated['name'],
            'email'              => $validated['email'],
            'role'               => $validated['role'],
            'jabatan'            => $validated['jabatan'] ?? null,
            'primary_lembaga_id' => $primaryLembagaId,
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
        $legacyFallbackMap = User::legacyParentPermissionMap();

        $savedStates = [];

        foreach ($menus as $menuCode => $label) {
            if (array_key_exists($menuCode, $permissionsInput)) {
                $input = $permissionsInput[$menuCode] ?? [];
            } elseif (isset($legacyFallbackMap[$menuCode]) && array_key_exists($legacyFallbackMap[$menuCode], $permissionsInput)) {
                $input = $permissionsInput[$legacyFallbackMap[$menuCode]] ?? [];
            } else {
                $input = [];
            }

            $state = [
                'can_view'   => !empty($input['view']),
                'can_create' => !empty($input['create']),
                'can_edit'   => !empty($input['edit']),
                'can_delete' => !empty($input['delete']),
            ];

            $savedStates[$menuCode] = $state;

            UserPermission::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'menu_code' => $menuCode,
                ],
                $state
            );
        }

        // Sinkronisasi kode grup / parent agar tetap selaras dengan pilihan submenu
        foreach (User::groupPermissionChildrenMap() as $groupCode => $childCodes) {
            $groupState = [
                'can_view'   => false,
                'can_create' => false,
                'can_edit'   => false,
                'can_delete' => false,
            ];

            foreach ($childCodes as $childCode) {
                if (!empty($savedStates[$childCode]['can_view'])) {
                    $groupState['can_view'] = true;
                }
                if (!empty($savedStates[$childCode]['can_create'])) {
                    $groupState['can_create'] = true;
                }
                if (!empty($savedStates[$childCode]['can_edit'])) {
                    $groupState['can_edit'] = true;
                }
                if (!empty($savedStates[$childCode]['can_delete'])) {
                    $groupState['can_delete'] = true;
                }
            }

            UserPermission::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'menu_code' => $groupCode,
                ],
                $groupState
            );
        }

        $user->unsetRelation('permissions');
    }
}
