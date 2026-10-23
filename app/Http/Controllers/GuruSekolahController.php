<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuruSekolahController extends Controller
{
    /**
     * Tampilkan data guru sekolah dan daftar pengajuan mata pelajaran ke yayasan.
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        // Cek izin akses: yayasan atau yang diberi izin guru_sekolah
        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka menu Guru Sekolah.');
        }

        // Query dasar: fokus ke akun guru, atau akun yang punya mata pelajaran / pengajuan mapel
        $query = User::with(['primaryLembaga', 'lembagas', 'mapelDiverifikasiBy']);

        // Filter tipe cakupan pengguna (default: Guru)
        $cakupan = $request->get('cakupan', 'guru');
        if ($cakupan === 'guru') {
            $query->where(function ($q) {
                $q->where('role', 'guru')
                  ->orWhereNotNull('mata_pelajaran')
                  ->orWhereNotNull('mapel_diajukan');
            });
        }

        // Filter status pengajuan
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'menunggu') {
                $query->where('mapel_status', 'menunggu');
            } elseif ($status === 'disetujui') {
                $query->where('mapel_status', 'disetujui');
            } elseif ($status === 'ditolak') {
                $query->where('mapel_status', 'ditolak');
            } elseif ($status === 'belum_mengajukan') {
                $query->where(function ($q) {
                    $q->whereNull('mapel_status')
                      ->orWhere('mapel_status', 'belum_mengajukan');
                });
            }
        }

        // Filter lembaga
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
                  ->orWhere('mapel_diajukan', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        // Statistik
        $stats = [
            'total_guru' => User::where(function ($q) {
                $q->where('role', 'guru')
                  ->orWhereNotNull('mata_pelajaran')
                  ->orWhereNotNull('mapel_diajukan');
            })->count(),
            'menunggu' => User::where('mapel_status', 'menunggu')->count(),
            'disetujui' => User::where('mapel_status', 'disetujui')->whereNotNull('mata_pelajaran')->count(),
            'ditolak' => User::where('mapel_status', 'ditolak')->count(),
            'belum_mengajukan' => User::where(function ($q) {
                $q->where('role', 'guru')
                  ->orWhereNotNull('mata_pelajaran');
            })->where(function ($q) {
                $q->whereNull('mapel_status')
                  ->orWhere('mapel_status', 'belum_mengajukan');
            })->count(),
        ];

        // Urutkan: yang pending/menunggu approval ditaruh paling atas, kemudian alfabetis nama
        $users = $query->orderByRaw("CASE WHEN mapel_status = 'menunggu' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $lembagas = Lembaga::where('is_active', true)->orderBy('urutan')->get();

        // Rekomendasi mata pelajaran standar
        $daftarMapel = self::daftarMataPelajaran();

        return view('guru-sekolah.index', compact('users', 'stats', 'lembagas', 'daftarMapel', 'authUser'));
    }

    /**
     * Menyetujui (Approve) registrasi mata pelajaran yang diajukan oleh guru.
     */
    public function approve(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk menyetujui pengajuan.');
        }

        $request->validate([
            'mata_pelajaran' => 'nullable|string|max:100',
            'catatan'        => 'nullable|string|max:500',
        ]);

        // Gunakan mapel yang diajukan atau mapel yang diedit yayasan jika diisi
        $mapelDisetujui = $request->filled('mata_pelajaran') 
            ? trim($request->mata_pelajaran) 
            : ($user->mapel_diajukan ?: $user->mata_pelajaran);

        if (empty($mapelDisetujui)) {
            return redirect()->back()->withErrors(['mata_pelajaran' => 'Nama mata pelajaran tidak boleh kosong.']);
        }

        $user->mata_pelajaran = $mapelDisetujui;
        $user->mapel_diajukan = $mapelDisetujui;
        $user->mapel_status = 'disetujui';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = $request->filled('catatan') ? trim($request->catatan) : 'Disetujui oleh Yayasan';

        // Pastikan role setidaknya guru jika akun ini belum admin/yayasan
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
     * Tetapkan mata pelajaran secara langsung oleh yayasan tanpa perlu pengajuan mandiri.
     */
    public function assign(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('guru_sekolah', 'edit')) {
            abort(403, 'Anda tidak memiliki hak untuk menetapkan mata pelajaran.');
        }

        $validated = $request->validate([
            'mata_pelajaran'     => 'required|string|max:100',
            'primary_lembaga_id' => 'nullable|exists:lembagas,id',
            'catatan'            => 'nullable|string|max:500',
        ], [
            'mata_pelajaran.required' => 'Mata pelajaran wajib dipilih atau diisi.',
        ]);

        $mapel = trim($validated['mata_pelajaran']);

        $user->mata_pelajaran = $mapel;
        $user->mapel_diajukan = $mapel;
        $user->mapel_status = 'disetujui';
        $user->mapel_diverifikasi_at = now();
        $user->mapel_diverifikasi_by = $authUser->id;
        $user->mapel_catatan = $validated['catatan'] ?: 'Ditetapkan langsung oleh Yayasan';

        if ($request->filled('primary_lembaga_id')) {
            $user->primary_lembaga_id = $request->primary_lembaga_id;
            $user->lembagas()->syncWithoutDetaching([$request->primary_lembaga_id]);
        }

        if (!in_array($user->role, ['yayasan', 'admin_lembaga', 'super_admin'])) {
            $user->role = 'guru';
        }

        $user->save();

        return redirect()->back()->with('success', 'Mata pelajaran untuk Guru ' . $user->name . ' berhasil diperbarui menjadi "' . $mapel . '".');
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

    /**
     * Daftar rekomendasi mata pelajaran yang umum di pesantren dan sekolah formal.
     */
    public static function daftarMataPelajaran(?int $lembagaId = null): array
    {
        $defaultMapel = [
            'Informatika',
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Ilmu Pengetahuan Alam (IPA)',
            'Fisika',
            'Biologi',
            'Kimia',
            'Ilmu Pengetahuan Sosial (IPS)',
            'Sejarah Indonesia',
            'Geografi',
            'Ekonomi',
            'Sosiologi',
            'Pendidikan Agama Islam (PAI)',
            'Al-Qur\'an Hadits',
            'Fiqih',
            'Akidah Akhlak',
            'Sejarah Kebudayaan Islam (SKI)',
            'Bahasa Arab',
            'Tahfidz Al-Qur\'an',
            'Nahwu & Shorof',
            'Pendidikan Pancasila & Kewarganegaraan (PPKn)',
            'Pendidikan Jasmani, Olahraga & Kesehatan (PJOK)',
            'Seni Budaya',
            'Prakarya & Kewirausahaan',
            'Bimbingan Konseling (BK)',
        ];

        try {
            $query = \App\Models\MataPelajaran::query();
            if ($lembagaId) {
                $query->where('lembaga_id', $lembagaId);
            }
            $dbMapel = $query->pluck('mata_pelajaran')->toArray();
            return array_values(array_unique(array_merge($dbMapel, $defaultMapel)));
        } catch (\Throwable $e) {
            return $defaultMapel;
        }
    }
}
