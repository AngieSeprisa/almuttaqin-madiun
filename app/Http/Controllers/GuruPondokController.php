<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\MasterKelasPengajian;
use App\Models\PondokMateri;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuruPondokController extends Controller
{
    /**
     * Tampilkan daftar guru pondok yang sudah di-plotting beserta kelas pengajian & materi yang diampu.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_pondok', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Plotting Guru Pondok.');
        }

        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $kelasPengajians = MasterKelasPengajian::orderBy('id')->get();
        $kelasMap = $kelasPengajians->keyBy('id');

        $pondokMateris = PondokMateri::where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama_materi')
            ->get();

        $query = User::with(['primaryLembaga', 'lembagas'])
            ->where('is_guru_pondok', true);

        // Filter berdasarkan kelas pengajian yang diampu
        if ($request->filled('kelas_pengajian_id')) {
            $kelasId = (int) $request->kelas_pengajian_id;
            $query->where(function ($q) use ($kelasId) {
                $q->whereJsonContains('pondok_kelas_ids', $kelasId)
                  ->orWhereJsonContains('pondok_kelas_ids', (string) $kelasId);
            });
        }

        // Filter berdasarkan materi pondok
        if ($request->filled('materi')) {
            $materiFilter = trim((string) $request->materi);
            $query->where('pondok_materi', 'like', "%{$materiFilter}%");
        }

        // Filter pencarian teks
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('pondok_materi', 'like', "%{$search}%")
                  ->orWhere('pondok_catatan', 'like', "%{$search}%");
            });
        }

        // Statistik ringkas
        $allGuruPondok = User::where('is_guru_pondok', true)
            ->get(['id', 'pondok_kelas_ids', 'pondok_materi']);

        $validKelasIds = $kelasPengajians->pluck('id')->map(fn ($id) => (int) $id)->all();
        $coveredKelasIds = [];
        $coveredMateris = [];

        foreach ($allGuruPondok as $gp) {
            foreach ($gp->daftar_kelas_pengajian_ids as $kId) {
                if (in_array($kId, $validKelasIds, true)) {
                    $coveredKelasIds[$kId] = true;
                }
            }
            foreach ($gp->daftar_materi_pondok as $mat) {
                $coveredMateris[mb_strtolower($mat)] = true;
            }
        }

        $totalKelas = $kelasPengajians->count();
        $kelasTerampu = count($coveredKelasIds);

        $stats = [
            'total_guru'       => $allGuruPondok->count(),
            'total_kelas'      => $totalKelas,
            'kelas_terampu'    => $kelasTerampu,
            'kelas_tanpa_guru' => max(0, $totalKelas - $kelasTerampu),
            'materi_diampu'    => count($coveredMateris),
        ];

        $perPage = (int) $request->get('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $users = $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Daftar akun aktif yang BELUM terdaftar sebagai Guru Pondok
        $akunUsers = User::where('is_active', true)
            ->where('is_guru_pondok', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip']);

        return view('guru-pondok.index', compact(
            'users',
            'stats',
            'kelasPengajians',
            'kelasMap',
            'pondokMateris',
            'akunUsers',
            'authUser'
        ));
    }

    /**
     * Daftarkan akun pengguna terdaftar sebagai Guru Pondok dan plot kelas pengajian / materinya.
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_pondok', 'create')) {
            abort(403, 'Anda tidak memiliki hak untuk menambah Guru Pondok.');
        }

        $request->validate([
            'user_id'               => ['required', 'exists:users,id'],
            'kelas_pengajian_ids'   => ['required', 'array', 'min:1'],
            'kelas_pengajian_ids.*' => ['integer', 'exists:master_kelas_pengajians,id'],
            'pondok_materi'         => ['nullable'],
            'pondok_catatan'        => ['nullable', 'string', 'max:255'],
        ], [
            'user_id.required'             => 'Pilih akun guru yang akan didaftarkan.',
            'kelas_pengajian_ids.required' => 'Pilih setidaknya satu kelas pengajian yang diampu.',
            'kelas_pengajian_ids.min'      => 'Pilih setidaknya satu kelas pengajian yang diampu.',
        ]);

        $user = User::findOrFail($request->user_id);

        $kelasIds = array_values(array_unique(array_map('intval', $request->input('kelas_pengajian_ids', []))));
        $materiStr = $this->normalizeMateri($request->input('pondok_materi'));

        $user->is_guru_pondok = true;
        $user->pondok_kelas_ids = $kelasIds;
        $user->pondok_materi = $materiStr !== '' ? $materiStr : null;
        $user->pondok_catatan = $request->filled('pondok_catatan') ? trim((string) $request->pondok_catatan) : null;

        $this->attachPondokLembaga($user);

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin', 'musyrif'], true)) {
            $user->role = 'guru';
        }

        $user->save();

        $kelasNames = MasterKelasPengajian::whereIn('id', $kelasIds)->pluck('nama_kelas')->implode(', ');

        return redirect()->route('guru-pondok.index')
            ->with('success', "Guru {$user->name} berhasil di-plotting sebagai Guru Pondok ({$kelasNames}).");
    }

    /**
     * Perbarui plotting kelas pengajian dan materi pondok untuk Guru Pondok.
     */
    public function update(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_pondok', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah data Guru Pondok.');
        }

        $request->validate([
            'kelas_pengajian_ids'   => ['required', 'array', 'min:1'],
            'kelas_pengajian_ids.*' => ['integer', 'exists:master_kelas_pengajians,id'],
            'pondok_materi'         => ['nullable'],
            'pondok_catatan'        => ['nullable', 'string', 'max:255'],
        ], [
            'kelas_pengajian_ids.required' => 'Pilih setidaknya satu kelas pengajian yang diampu.',
            'kelas_pengajian_ids.min'      => 'Pilih setidaknya satu kelas pengajian yang diampu.',
        ]);

        $kelasIds = array_values(array_unique(array_map('intval', $request->input('kelas_pengajian_ids', []))));
        $materiStr = $this->normalizeMateri($request->input('pondok_materi'));

        $user->is_guru_pondok = true;
        $user->pondok_kelas_ids = $kelasIds;
        $user->pondok_materi = $materiStr !== '' ? $materiStr : null;
        $user->pondok_catatan = $request->filled('pondok_catatan') ? trim((string) $request->pondok_catatan) : null;

        $this->attachPondokLembaga($user);

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin', 'musyrif'], true)) {
            $user->role = 'guru';
        }

        $user->save();

        return redirect()->route('guru-pondok.index')
            ->with('success', "Plotting Guru Pondok untuk {$user->name} berhasil diperbarui.");
    }

    /**
     * Hapus seorang guru dari daftar Guru Pondok (tanpa menghapus akun pengguna).
     */
    public function destroy(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_pondok', 'delete')) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus Guru Pondok.');
        }

        $nama = $user->name;
        $user->is_guru_pondok = false;
        $user->pondok_kelas_ids = null;
        $user->pondok_materi = null;
        $user->pondok_catatan = null;
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Guru {$nama} berhasil dihapus dari daftar Guru Pondok.",
            ]);
        }

        return redirect()->route('guru-pondok.index')
            ->with('success', "Guru {$nama} berhasil dihapus dari daftar Guru Pondok.");
    }

    /**
     * Normalisasi input materi pondok (array atau string dipisah koma).
     */
    protected function normalizeMateri(mixed $raw): string
    {
        if (is_array($raw)) {
            $items = array_values(array_unique(array_filter(array_map('trim', $raw))));
            return implode(', ', $items);
        }

        $items = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,|;]/', (string) $raw)))));
        return implode(', ', $items);
    }

    /**
     * Pastikan lembaga Pondok terhubung ke akun tanpa menimpa lembaga sekolah utama yang sudah ada.
     */
    protected function attachPondokLembaga(User $user): void
    {
        $pondokLembaga = Lembaga::whereRaw('LOWER(kode) = ?', ['pondok'])
            ->orWhereRaw('UPPER(singkatan) = ?', ['PONDOK'])
            ->first();

        if ($pondokLembaga) {
            $user->lembagas()->syncWithoutDetaching([$pondokLembaga->id]);
            if (empty($user->primary_lembaga_id)) {
                $user->primary_lembaga_id = $pondokLembaga->id;
            }
        }
    }
}
