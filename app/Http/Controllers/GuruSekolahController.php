<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuruSekolahController extends Controller
{
    /**
     * Daftar lembaga formal yang dapat diakses user sesuai room aktif.
     */
    protected function resolveAllowedFormalLembagas(User $authUser)
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($authUser->isYayasan()) {
            return Lembaga::formalOptions();
        }

        if ($scopedFormal) {
            return collect([$scopedFormal]);
        }

        return $authUser->accessibleLembagas()
            ->filter(fn (Lembaga $l) => $l->isFormalSchool())
            ->values();
    }

    /**
     * Pastikan target guru termasuk dalam room/lembaga yang dapat dikelola user aktif.
     */
    protected function ensureTargetTeacherInRoom(User $authUser, User $target): void
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal) {
            $inRoom = User::forLembagaModel($scopedFormal)->whereKey($target->id)->exists();
            if (!$inRoom) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola guru dari lembaga lain.');
            }
            return;
        }

        if (!$authUser->isYayasan()) {
            $allowedIds = $this->resolveAllowedFormalLembagas($authUser)->pluck('id')->all();
            $inAllowed = User::whereKey($target->id)
                ->where(function ($q) use ($allowedIds) {
                    $q->whereIn('primary_lembaga_id', $allowedIds)
                      ->orWhereHas('lembagas', fn ($l) => $l->whereIn('lembagas.id', $allowedIds));
                })->exists();
            if (!$inAllowed) {
                abort(403, 'Anda tidak memiliki akses untuk mengelola guru dari lembaga lain.');
            }
        }
    }

    /**
     * Tampilkan data guru sekolah dan daftar pengajuan mata pelajaran ke yayasan.
     * Hanya menampilkan akun yang didaftarkan sebagai Guru Sekolah atau yang sedang mengajukan mapel.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Cek izin akses: yayasan atau yang diberi izin guru_sekolah
        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Guru Sekolah.');
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $sekolahLembagas = $this->resolveAllowedFormalLembagas($authUser);
        $sekolahIds = $sekolahLembagas->pluck('id')->toArray();

        // Tentukan filter lembaga aktif (terkunci untuk non-Yayasan di room formal; default ke room aktif untuk Yayasan)
        $selectedLembaga = null;
        if (!$authUser->isYayasan() && $scopedFormal) {
            $selectedLembaga = $scopedFormal;
        } elseif ($request->filled('lembaga_id')) {
            $selectedLembaga = $sekolahLembagas->firstWhere('id', (int) $request->lembaga_id);
        } elseif ($scopedFormal) {
            $selectedLembaga = $scopedFormal;
        }

        $applyScope = function ($q) use ($selectedLembaga, $sekolahIds, $authUser) {
            if ($selectedLembaga) {
                $q->forLembagaModel($selectedLembaga);
            } elseif (!$authUser->isYayasan()) {
                if (empty($sekolahIds)) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->where(function ($sub) use ($sekolahIds) {
                        $sub->whereIn('primary_lembaga_id', $sekolahIds)
                            ->orWhereHas('lembagas', fn ($l) => $l->whereIn('lembagas.id', $sekolahIds));
                    });
                }
            }
        };

        // Query: HANYA akun yang terdaftar sebagai guru sekolah atau yang sedang menunggu persetujuan mapel
        $query = User::with(['primaryLembaga', 'lembagas', 'mapelDiverifikasiBy'])
            ->where(function ($q) {
                $q->where('is_guru_sekolah', true)
                  ->orWhere('mapel_status', 'menunggu');
            });
        $applyScope($query);

        // Filter status pengajuan
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'menunggu') {
                $query->where('mapel_status', 'menunggu');
            } elseif ($status === 'disetujui') {
                $query->where('mapel_status', 'disetujui');
            } elseif ($status === 'ditolak') {
                $query->where('mapel_status', 'ditolak');
            }
        }

        // Filter pencarian teks
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('mapel_diajukan', 'like', "%{$search}%");
            });
        }

        // Statistik dinamis sesuai ruang kerja/lembaga yang dapat diakses
        $baseTotalQuery = User::where('is_guru_sekolah', true);
        $baseMenungguQuery = User::where('mapel_status', 'menunggu');
        $baseDisetujuiQuery = User::where('is_guru_sekolah', true)->where('mapel_status', 'disetujui');
        $applyScope($baseTotalQuery);
        $applyScope($baseMenungguQuery);
        $applyScope($baseDisetujuiQuery);

        $statsLembagas = $selectedLembaga ? collect([$selectedLembaga]) : $sekolahLembagas;
        $lembagaStats = [];
        foreach ($statsLembagas as $lemb) {
            $lembagaStats[] = [
                'id'        => $lemb->id,
                'kode'      => $lemb->kode,
                'nama'      => $lemb->nama,
                'singkatan' => $lemb->nama_singkat,
                'count'     => User::where('is_guru_sekolah', true)->forLembagaModel($lemb)->count(),
            ];
        }

        $smpLembaga = $statsLembagas->first(fn ($l) => strtolower($l->kode) === 'smp' || strtoupper((string) $l->singkatan) === 'SMP');
        $smaLembaga = $statsLembagas->first(fn ($l) => strtolower($l->kode) === 'sma' || strtoupper((string) $l->singkatan) === 'SMA');

        $stats = [
            'total_guru' => $baseTotalQuery->count(),
            'menunggu'   => $baseMenungguQuery->count(),
            'disetujui'  => $baseDisetujuiQuery->count(),
            'smp'        => $smpLembaga ? User::where('is_guru_sekolah', true)->forLembagaModel($smpLembaga)->count() : 0,
            'sma'        => $smaLembaga ? User::where('is_guru_sekolah', true)->forLembagaModel($smaLembaga)->count() : 0,
        ];

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        // Urutkan: yang pending/menunggu approval ditaruh paling atas, kemudian alfabetis nama
        $users = $query->orderByRaw("CASE WHEN mapel_status = 'menunggu' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Ambil data mata pelajaran yang disinkronkan langsung dari menu Plotting Mata Pelajaran
        $mapelLembagaIds = $selectedLembaga ? [$selectedLembaga->id] : $sekolahIds;
        $plottingMapels = MataPelajaran::with('lembaga')
            ->whereIn('lembaga_id', $mapelLembagaIds)
            ->orderBy('mata_pelajaran')
            ->get();

        // Daftar akun terdaftar yang BELUM masuk sebagai Guru Sekolah (untuk pilihan pop up form tambah guru)
        $akunUsersQuery = User::where('is_active', true)
            ->where('is_guru_sekolah', false);
        if ($selectedLembaga) {
            $akunUsersQuery->forLembagaModel($selectedLembaga);
        } elseif (!$authUser->isYayasan()) {
            $akunUsersQuery->where(function ($sub) use ($sekolahIds) {
                $sub->whereIn('primary_lembaga_id', $sekolahIds)
                    ->orWhereHas('lembagas', fn ($l) => $l->whereIn('lembagas.id', $sekolahIds));
            });
        }
        $akunUsers = $akunUsersQuery->orderBy('name')->get(['id', 'name', 'email', 'nip']);

        $selectedLembagaId = $selectedLembaga?->id;

        return view('guru-sekolah.index', compact(
            'users',
            'stats',
            'lembagaStats',
            'sekolahLembagas',
            'selectedLembaga',
            'selectedLembagaId',
            'scopedFormal',
            'plottingMapels',
            'akunUsers',
            'authUser'
        ));
    }

    /**
     * Tambah guru sekolah baru dari akun pengguna yang terdaftar.
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'create')) {
            abort(403, 'Anda tidak memiliki hak untuk menambah Guru Sekolah.');
        }

        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'lembaga_id'     => 'required|exists:lembagas,id',
            'mata_pelajaran' => 'required',
        ], [
            'user_id.required'        => 'Pilih akun guru yang akan didaftarkan.',
            'lembaga_id.required'     => 'Pilih lembaga sekolah tujuan.',
            'mata_pelajaran.required' => 'Pilih setidaknya satu mata pelajaran yang diampu.',
        ]);

        $lembaga = Lembaga::findOrFail($request->lembaga_id);
        if (!$lembaga->isFormalSchool()) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga yang dipilih harus merupakan lembaga sekolah formal.']);
        }

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !$authUser->isYayasan() && (int) $lembaga->id !== (int) $scopedFormal->id) {
            abort(403, 'Anda hanya dapat mendaftarkan guru pada lembaga ruang kerja Anda.');
        }
        if (!$authUser->isYayasan() && !$authUser->hasAccessToLembaga($lembaga->kode)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga tersebut.');
        }

        $user = User::findOrFail($request->user_id);

        $rawMapel = $request->input('mata_pelajaran');
        if (is_array($rawMapel)) {
            $items = array_values(array_unique(array_filter(array_map('trim', $rawMapel))));
            $mapel = implode(', ', $items);
        } else {
            $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,|;]/', (string)$rawMapel)))));
            $mapel = implode(', ', $items);
        }

        if (empty($mapel)) {
            return redirect()->back()->withErrors(['mata_pelajaran' => 'Pilih setidaknya satu mata pelajaran yang diampu.']);
        }

        $user->is_guru_sekolah = true;
        $user->primary_lembaga_id = $lembaga->id;
        $user->lembagas()->syncWithoutDetaching([$lembaga->id]);
        $user->mata_pelajaran = $mapel;
        $user->mapel_diajukan = $mapel;
        $user->mapel_status = 'disetujui';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = 'Didaftarkan sebagai Guru Sekolah';

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin'])) {
            $user->role = 'guru';
        }

        $school = $user->resolveSchoolLembaga();
        if ($school && $school->isFormalSchool()) {
            $user->lembagas()->syncWithoutDetaching([$school->id]);
            if (!$user->isYayasan() || empty($user->primary_lembaga_id)) {
                $user->primary_lembaga_id = $school->id;
            }
        }

        $user->save();

        return redirect()->route('guru-sekolah.index')
            ->with('success', "Guru {$user->name} berhasil didaftarkan sebagai Guru {$lembaga->nama_singkat} ({$mapel}).");
    }

    /**
     * Menyetujui (Approve) registrasi mata pelajaran yang diajukan oleh guru (1 atau lebih mapel).
     */
    public function approve(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk menyetujui pengajuan.');
        }

        $this->ensureTargetTeacherInRoom($authUser, $user);

        $rawMapel = $request->input('mata_pelajaran');
        if (!empty($rawMapel)) {
            if (is_array($rawMapel)) {
                $items = array_values(array_unique(array_filter(array_map('trim', $rawMapel))));
                $mapelDisetujui = implode(', ', $items);
            } else {
                $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,|;]/', (string)$rawMapel)))));
                $mapelDisetujui = implode(', ', $items);
            }
        } else {
            $mapelDisetujui = $user->mapel_diajukan ?: $user->mata_pelajaran;
        }

        if (empty($mapelDisetujui)) {
            return redirect()->back()->withErrors(['mata_pelajaran' => 'Pilih setidaknya satu mata pelajaran.']);
        }

        $user->is_guru_sekolah = true;
        $user->mata_pelajaran = $mapelDisetujui;
        $user->mapel_diajukan = $mapelDisetujui;
        $user->mapel_status = 'disetujui';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = $request->filled('catatan') ? trim($request->catatan) : 'Disetujui oleh Yayasan';

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin'])) {
            $user->role = 'guru';
        }

        $school = $user->resolveSchoolLembaga();
        if ($school && $school->isFormalSchool()) {
            $user->lembagas()->syncWithoutDetaching([$school->id]);
            if (!$user->isYayasan() || empty($user->primary_lembaga_id)) {
                $user->primary_lembaga_id = $school->id;
            }
        }

        $user->save();

        return redirect()->back()->with('success', 'Mata pelajaran "' . $mapelDisetujui . '" untuk Guru ' . $user->name . ' berhasil DISETUJUI oleh Yayasan.');
    }

    /**
     * Menolak pengajuan registrasi mata pelajaran dari guru.
     */
    public function reject(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk menolak pengajuan.');
        }

        $this->ensureTargetTeacherInRoom($authUser, $user);

        $request->validate([
            'catatan' => 'required|string|max:500',
        ], [
            'catatan.required' => 'Mohon sertakan alasan penolakan/catatan agar guru dapat melakukan revisi.',
        ]);

        $user->mapel_status = 'ditolak';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = trim($request->catatan);
        $user->save();

        return redirect()->back()->with('success', 'Pengajuan mata pelajaran untuk Guru ' . $user->name . ' telah DITOLAK dengan catatan perbaikan.');
    }

    /**
     * Tetapkan / ubah mata pelajaran secara langsung oleh yayasan (1 atau lebih mapel).
     */
    public function assign(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data guru sekolah.');
        }

        $this->ensureTargetTeacherInRoom($authUser, $user);

        $rawMapel = $request->input('mata_pelajaran');
        if (is_array($rawMapel)) {
            $items = array_values(array_unique(array_filter(array_map('trim', $rawMapel))));
            $mapel = implode(', ', $items);
        } else {
            $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,|;]/', (string)$rawMapel)))));
            $mapel = implode(', ', $items);
        }

        if (empty($mapel)) {
            return redirect()->back()->withErrors(['mata_pelajaran' => 'Pilih setidaknya satu mata pelajaran yang diampu.']);
        }

        $user->is_guru_sekolah = true;
        $user->mata_pelajaran = $mapel;
        $user->mapel_diajukan = $mapel;
        $user->mapel_status = 'disetujui';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = $request->filled('catatan') ? trim($request->catatan) : 'Ditetapkan oleh Yayasan';

        if ($request->filled('primary_lembaga_id')) {
            $lembaga = Lembaga::find($request->primary_lembaga_id);
            $scopedFormal = Lembaga::activeScopedFormal($authUser);
            $canAssignLembaga = $lembaga && $lembaga->isFormalSchool()
                && ($authUser->isYayasan() || ($authUser->hasAccessToLembaga($lembaga->kode) && (!$scopedFormal || (int) $scopedFormal->id === (int) $lembaga->id)));
            if ($canAssignLembaga) {
                $user->primary_lembaga_id = $lembaga->id;
                $user->lembagas()->syncWithoutDetaching([$lembaga->id]);
            }
        }

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin'])) {
            $user->role = 'guru';
        }

        $school = $user->resolveSchoolLembaga();
        if ($school && $school->isFormalSchool()) {
            $user->lembagas()->syncWithoutDetaching([$school->id]);
            if (!$user->isYayasan() || empty($user->primary_lembaga_id)) {
                $user->primary_lembaga_id = $school->id;
            }
        }

        $user->save();

        return redirect()->back()->with('success', 'Mata pelajaran untuk Guru ' . $user->name . ' berhasil diperbarui menjadi: ' . $mapel . '.');
    }

    /**
     * Hapus seorang guru dari daftar Guru Sekolah (tanpa menghapus akun pengguna).
     */
    public function destroy(User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'delete')) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus Guru Sekolah.');
        }

        $this->ensureTargetTeacherInRoom($authUser, $user);

        $nama = $user->name;
        $user->is_guru_sekolah = false;
        $user->mata_pelajaran = null;
        $user->mapel_diajukan = null;
        $user->mapel_status = 'belum_mengajukan';
        $user->mapel_catatan = null;
        $user->save();

        return redirect()->route('guru-sekolah.index')
            ->with('success', "Guru {$nama} berhasil dihapus dari daftar Guru Sekolah.");
    }

    /**
     * Reset status pengajuan mata pelajaran seorang guru.
     */
    public function reset(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $this->ensureTargetTeacherInRoom($authUser, $user);

        $user->mapel_status = !empty($user->mata_pelajaran) ? 'disetujui' : 'belum_mengajukan';
        $user->mapel_diajukan = $user->mata_pelajaran;
        $user->mapel_catatan = null;
        $user->save();

        return redirect()->back()->with('success', 'Status pengajuan mata pelajaran Guru ' . $user->name . ' telah di-reset.');
    }
}
