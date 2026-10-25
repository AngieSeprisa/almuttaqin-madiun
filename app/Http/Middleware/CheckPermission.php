<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param string $menuCode kode menu (siswa, pendaftaran, perizinan_sambang, perizinan_sakit, pengguna)
     * @param string $action tindakan (view, create, edit, delete)
     */
    public function handle(Request $request, Closure $next, string $menuCode, string $action = 'view'): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $menus = preg_split('/[,|]/', $menuCode);
        $allowed = false;
        foreach ($menus as $m) {
            if ($user->canDo(trim($m), $action)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => 'Anda tidak memiliki hak akses untuk tindakan ini.'], 403);
            }

            return redirect()->route('beranda')->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses menu ini.');
        }

        return $next($request);
    }
}
