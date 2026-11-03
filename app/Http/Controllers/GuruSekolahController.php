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

        // Ambil lembaga sekolah resmi: HANYA SMP dan SMA
        $sekolahLembagas = Lembaga::where(function ($q) {
            $q->whereIn('kode', ['smp', 'sma'])
              ->orWhere(function ($sub) {
                  $sub->where('kategori', 'formal')->whereIn('kode', ['smp', 'sma']);
              })
              ->orWhere('singkatan', 'SMP')
              ->orWhere('singkatan', 'SMA');
        })->where('is_active', true)->orderBy('urutan')->get();

        if ($sekolahLembagas->isEmpty()) {
            $sekolahLembagas = Lembaga::where('singkatan', 'like', '%SMP%')
                ->orWhere('singkatan', 'like', '%SMA%')
                ->get();
        }

        $sekolahIds = $sekolahLembagas->pluck('id')->toArray();

        // Query: HANYA akun yang terdaftar sebagai guru sekolah atau yang sedang menunggu persetujuan mapel
        $query = User::with(['primaryLembaga', 'lembagas', 'mapelDiverifikasiBy'])
            ->where(function ($q) {
                $q->where('is_guru_sekolah', true)
                  ->orWhere('mapel_status', 'menunggu');
            });

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

        // Filter lembaga sekolah
        if ($request->filled('lembaga_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('primary_lembaga_id', $request->lembaga_id)
                  ->orWhereHas('lembagas', function ($l) use ($request) {
                      $l->where('lembagas.id', $request->lembaga_id);
                  });
            });
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

        // Statistik
        $smpLembaga = $sekolahLembagas->first(fn($l) => strtolower($l->kode) === 'smp' || strtoupper($l->singkatan) === 'SMP');
        $smaLembaga = $sekolahLembagas->first(fn($l) => strtolower($l->kode) === 'sma' || strtoupper($l->singkatan) === 'SMA');

        $stats = [
            'total_guru' => User::where('is_guru_sekolah', true)->count(),
            'menunggu'   => User::where('mapel_status', 'menunggu')->count(),
            'disetujui'  => User::where('is_guru_sekolah', true)->where('mapel_status', 'disetujui')->count(),
            'smp'        => $smpLembaga ? User::where('is_guru_sekolah', true)->where(function ($q) use ($smpLembaga) {
                $q->where('primary_lembaga_id', $smpLembaga->id)
                  ->orWhereHas('lembagas', fn($l) => $l->where('lembagas.id', $smpLembaga->id));
            })->count() : 0,
            'sma'        => $smaLembaga ? User::where('is_guru_sekolah', true)->where(function ($q) use ($smaLembaga) {
                $q->where('primary_lembaga_id', $smaLembaga->id)
                  ->orWhereHas('lembagas', fn($l) => $l->where('lembagas.id', $smaLembaga->id));
            })->count() : 0,
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
        $plottingMapels = MataPelajaran::with('lembaga')
            ->whereIn('lembaga_id', $sekolahIds)
            ->orderBy('mata_pelajaran')
            ->get();

        // Daftar akun terdaftar yang BELUM masuk sebagai Guru Sekolah (untuk pilihan pop up form tambah guru)
        $akunUsers = User::where('is_active', true)
            ->where('is_guru_sekolah', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'nip']);

        return view('guru-sekolah.index', compact('users', 'stats', 'sekolahLembagas', 'plottingMapels', 'akunUsers', 'authUser'));
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
            'lembaga_id.required'     => 'Pilih lembaga sekolah (SMP atau SMA).',
            'mata_pelajaran.required' => 'Pilih setidaknya satu mata pelajaran yang diampu.',
        ]);

        $lembaga = Lembaga::findOrFail($request->lembaga_id);
        if (!in_array(strtolower($lembaga->kode), ['smp', 'sma']) && !in_array(strtoupper($lembaga->singkatan), ['SMP', 'SMA'])) {
            return redirect()->back()->withErrors(['lembaga_id' => 'Lembaga hanya boleh SMP atau SMA.']);
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

        $user->save();

        return redirect()->route('guru-sekolah.index')
            ->with('success', "Guru {$user->name} berhasil didaftarkan sebagai Guru {$lembaga->singkatan} ({$mapel}).");
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
            if ($lembaga && (in_array(strtolower($lembaga->kode), ['smp', 'sma']) || in_array(strtoupper($lembaga->singkatan), ['SMP', 'SMA']))) {
                $user->primary_lembaga_id = $lembaga->id;
                $user->lembagas()->syncWithoutDetaching([$lembaga->id]);
            }
        }

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin'])) {
            $user->role = 'guru';
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

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'delete') && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus Guru Sekolah.');
        }

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

        $user->mapel_status = !empty($user->mata_pelajaran) ? 'disetujui' : 'belum_mengajukan';
        $user->mapel_diajukan = $user->mata_pelajaran;
        $user->mapel_catatan = null;
        $user->save();

        return redirect()->back()->with('success', 'Status pengajuan mata pelajaran Guru ' . $user->name . ' telah di-reset.');
    }
}
