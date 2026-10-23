<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('beranda');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda sedang dinonaktifkan. Silakan hubungi pengurus yayasan.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Atur active_lembaga pertama kali login
            $currentLembaga = $user->currentLembaga();
            if ($currentLembaga) {
                $request->session()->put('active_lembaga', $currentLembaga->kode);
            }

            return redirect()->intended(route('beranda'))->with('success', 'Selamat datang, ' . $user->name);
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Keluar dari sesi (Logout)
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Beralih ruang kerja lembaga (Switch Room)
     */
    public function switchLembaga(Request $request, string $kode)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $lembaga = Lembaga::where('kode', $kode)->where('is_active', true)->first();
        if (!$lembaga) {
            return back()->with('error', 'Lembaga tidak ditemukan.');
        }

        if (!$user->hasAccessToLembaga($kode)) {
            return back()->with('error', 'Anda tidak memiliki hak akses ke ruang kerja ' . $lembaga->nama);
        }

        $request->session()->put('active_lembaga', $kode);

        return back()->with('success', 'Beralih ke ruang kerja: ' . $lembaga->nama);
    }
}
