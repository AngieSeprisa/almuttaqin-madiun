<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Siswa;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    /**
     * Menampilkan halaman beranda dengan statistik sesuai ruang kerja (lembaga) aktif.
     */
    public function index()
    {
        $activeRoom = session('active_lembaga');

        $query = Siswa::query()->forLembaga($activeRoom);

        $statistik = [
            'total_siswa'  => (clone $query)->count(),
            'siswa_aktif'  => (clone $query)->where('status', 'aktif')->count(),
            'siswa_pulang' => (clone $query)->where('status', 'pulang')->count(),
            'siswa_sakit'  => (clone $query)->where('status', 'sakit')->count(),
        ];

        $lembagaSummary = null;
        if ($activeRoom === 'yayasan' || (auth()->check() && auth()->user()->isYayasan())) {
            $lembagaSummary = [
                'smp'    => Siswa::forLembaga('smp')->count(),
                'sma'    => Siswa::forLembaga('sma')->count(),
                'pondok' => Siswa::count(),
            ];
        }

        $currentLembaga = Lembaga::where('kode', $activeRoom)->first();

        return view('beranda', compact('statistik', 'activeRoom', 'currentLembaga', 'lembagaSummary'));
    }
}
