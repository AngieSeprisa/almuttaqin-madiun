<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PengaturanAbsensi;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PengaturanAbsensiController extends Controller
{
    /**
     * Tampilkan halaman Pengaturan Absensi.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $lembagas = $user->accessibleLembagas();

        // Tentukan lembaga aktif yang sedang diatur
        $activeKode = $request->get('lembaga', session('active_lembaga'));
        $currentLembaga = $lembagas->firstWhere('kode', $activeKode) ?: ($lembagas->first() ?: Lembaga::first());

        if (!$currentLembaga) {
            abort(404, 'Data lembaga tidak ditemukan.');
        }

        $pengaturan = $currentLembaga->getOrCreatePengaturanAbsensi();

        return view('pengaturan-absensi.index', compact('lembagas', 'currentLembaga', 'pengaturan'));
    }

    /**
     * Simpan pembaruan pengaturan absensi lembaga.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'lembaga_id'                => 'required|exists:lembagas,id',
            'jam_masuk'                 => 'required|date_format:H:i',
            'toleransi_terlambat_menit' => 'required|integer|min:0|max:180',
            'jam_pulang'                => 'required|date_format:H:i',
            'latitude'                  => 'required|numeric|between:-90,90',
            'longitude'                 => 'required|numeric|between:-180,180',
            'radius_meter'              => 'required|integer|min:10|max:5000',
            'mode_lokasi'               => 'required|in:opsional,wajib,nonaktif',
        ]);

        $lembaga = Lembaga::findOrFail($validated['lembaga_id']);
        $pengaturan = $lembaga->getOrCreatePengaturanAbsensi();

        $pengaturan->update([
            'jam_masuk'                 => $validated['jam_masuk'] . ':00',
            'toleransi_terlambat_menit' => $validated['toleransi_terlambat_menit'],
            'jam_pulang'                => $validated['jam_pulang'] . ':00',
            'latitude'                  => $validated['latitude'],
            'longitude'                 => $validated['longitude'],
            'radius_meter'              => $validated['radius_meter'],
            'mode_lokasi'               => $validated['mode_lokasi'],
        ]);


        return redirect()->route('pengaturan-absensi.index', ['lembaga' => $lembaga->kode])
            ->with('success', 'Pengaturan absensi berhasil disimpan.');
    }

    /**
     * Regenerasi kunci rahasia token QR dinamis lembaga.
     */
    public function regenerateSecret(Request $request)
    {
        $request->validate(['lembaga_id' => 'required|exists:lembagas,id']);
        $lembaga = Lembaga::findOrFail($request->lembaga_id);
        $pengaturan = $lembaga->getOrCreatePengaturanAbsensi();

        $pengaturan->update([
            'qr_secret_key' => Str::random(32),
        ]);

        return redirect()->route('pengaturan-absensi.index', ['lembaga' => $lembaga->kode])
            ->with('success', 'Kunci QR dinamis berhasil diperbarui.');
    }
}
