<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil pengguna: Biodata Diri & Kontak.
     */
    public function show()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load(['primaryLembaga', 'lembagas', 'mapelDiverifikasiBy']);

        $theme = $this->resolveTheme($user);

        return view('profile', compact('user', 'theme'));
    }

    /**
     * Perbarui data biodata diri & kontak mandiri oleh guru/pengurus.
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'foto'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'hapus_foto'    => 'nullable|boolean',
            'nip'           => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'tempat_lahir'  => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'jabatan'       => 'nullable|string|max:100',
            'telepon'       => 'nullable|string|max:30',
            'alamat'        => 'nullable|string|max:1000',
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Email wajib diisi.',
            'email.unique'       => 'Email sudah digunakan akun lain.',
            'email.email'        => 'Format email tidak valid.',
            'foto.image'         => 'File foto profil harus berupa gambar.',
            'foto.max'           => 'Ukuran foto profil maksimal 5MB.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
        ]);

        if ($request->boolean('hapus_foto')) {
            if ($user->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->foto);
            }
            $user->foto = null;
        }

        if ($request->hasFile('foto')) {
            if ($user->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->foto);
            }
            $compressor = new \App\Services\ImageCompressor(maxDimension: 800, quality: 80);
            $user->foto = $compressor->store($request->file('foto'), 'avatars');
        }

        unset($validated['foto'], $validated['hapus_foto']);
        $user->fill($validated);
        $user->save();

        return redirect()->route('profile')->with('success', 'Biodata diri dan profil Anda berhasil diperbarui.');
    }

    /**
     * Ajukan registrasi mata pelajaran yang diampu oleh guru ke Yayasan.
     */
    public function ajukanMapel(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'mata_pelajaran' => 'required|string|max:100',
            'catatan'        => 'nullable|string|max:500',
        ], [
            'mata_pelajaran.required' => 'Mata pelajaran wajib dipilih atau diisi.',
            'mata_pelajaran.max'      => 'Nama mata pelajaran maksimal 100 karakter.',
        ]);

        $mapelBaru = trim($validated['mata_pelajaran']);

        $user->mapel_diajukan = $mapelBaru;
        $user->mapel_status = 'menunggu';
        $user->mapel_catatan = $validated['catatan'] ?? null;
        $user->mapel_diajukan_at = now();
        $user->save();

        return redirect()->route('profile')->with('success', 'Pengajuan mata pelajaran "' . $mapelBaru . '" berhasil dikirim ke Yayasan. Silakan tunggu persetujuan dari pihak Yayasan.');
    }

    /**
     * Batalkan pengajuan mata pelajaran yang masih menunggu persetujuan.
     */
    public function batalAjukanMapel()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->mapel_status === 'menunggu') {
            $user->mapel_status = !empty($user->mata_pelajaran) ? 'disetujui' : 'belum_mengajukan';
            $user->mapel_diajukan = $user->mata_pelajaran;
            $user->save();

            return redirect()->route('profile')->with('success', 'Pengajuan mata pelajaran telah dibatalkan.');
        }

        return redirect()->route('profile');
    }

    /**
     * Tampilkan halaman Keamanan & Kata Sandi (Route /user).
     */
    public function security()
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load(['primaryLembaga', 'lembagas']);

        $theme = $this->resolveTheme($user);

        return view('user-security', compact('user', 'theme'));
    }

    /**
     * Perbarui kata sandi akun di halaman /user.
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:6|confirmed',
        ], [
            'current_password.required'         => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required'                 => 'Kata sandi baru wajib diisi.',
            'password.min'                      => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed'                => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('user')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    /**
     * Menentukan konfigurasi tema tombol berdasarkan lembaga aktif pengguna.
     * SMP: Biru Elektrik, SMA: Hijau Kelly, Pondok & Yayasan: Baby Blue.
     */
    private function resolveTheme(User $user): array
    {
        $currentLembaga = $user->currentLembaga();
        $kode = $currentLembaga ? $currentLembaga->kode : ($user->primaryLembaga ? $user->primaryLembaga->kode : 'pondok');

        return match ($kode) {
            'smp' => [
                'kode'      => 'smp',
                'name'      => 'SMP Al Muttaqin',
                'btn_class' => 'btn-theme-smp',
                'color'     => '#0052FF',
                'label'     => 'Biru Elektrik',
            ],
            'sma' => [
                'kode'      => 'sma',
                'name'      => 'SMA Al Muttaqin',
                'btn_class' => 'btn-theme-sma',
                'color'     => '#16A34A',
                'label'     => 'Hijau Kelly',
            ],
            'pondok' => [
                'kode'      => 'pondok',
                'name'      => 'Pondok Pesantren Al Muttaqin',
                'btn_class' => 'btn-theme-pondok',
                'color'     => '#38BDF8',
                'label'     => 'Baby Blue',
            ],
            default => [
                'kode'      => 'yayasan',
                'name'      => 'Yayasan Pondok Pesantren Muttaqin',
                'btn_class' => 'btn-theme-yayasan',
                'color'     => '#38BDF8',
                'label'     => 'Baby Blue',
            ],
        };
    }
}
