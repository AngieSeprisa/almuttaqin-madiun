<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Cek status aktif
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin yayasan.');
        }

        // Inisialisasi active_lembaga jika belum ada di session
        if (!$request->session()->has('active_lembaga')) {
            $current = $user->currentLembaga();
            if ($current) {
                $request->session()->put('active_lembaga', $current->kode);
            }
        }

        return $next($request);
    }
}
