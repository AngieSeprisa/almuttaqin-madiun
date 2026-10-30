<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;

class NfcInputController extends Controller
{
    /**
     * Tampilan utama menu Input Kartu NFC.
     */
    public function index(Request $request)
    {
        $activeRoom = session('active_lembaga');

        // Daftar santri aktif untuk dropdown / selector
        $siswas = Siswa::forLembaga($activeRoom)
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap', 'asc')
            ->get(['id', 'nama_lengkap', 'jenjang', 'kelas', 'kamar', 'rfid_uid']);

        // Riwayat kartu terakhir yang diinput (10 data terakhir)
        $recentPairings = Siswa::forLembaga($activeRoom)
            ->whereNotNull('rfid_uid')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $totalSiswa = Siswa::forLembaga($activeRoom)->where('status', 'aktif')->count();
        $totalTerdaftar = Siswa::forLembaga($activeRoom)->where('status', 'aktif')->whereNotNull('rfid_uid')->count();
        $totalBelum = max(0, $totalSiswa - $totalTerdaftar);

        return view('nfc.index', compact(
            'siswas',
            'recentPairings',
            'totalSiswa',
            'totalTerdaftar',
            'totalBelum'
        ));
    }

    /**
     * Cek status nomor serial kartu (apakah baru atau sudah terdaftar).
     */
    public function checkCard(Request $request)
    {
        $serial = trim($request->input('serial', ''));

        if (empty($serial)) {
            return response()->json([
                'status' => 'empty',
                'message' => 'Nomor serial belum diisi.',
            ]);
        }

        $siswa = Siswa::where('rfid_uid', $serial)->first();

        if ($siswa) {
            return response()->json([
                'status' => 'assigned',
                'serial' => $serial,
                'siswa' => [
                    'id' => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'jenjang' => $siswa->jenjang ?? 'Pondok',
                    'kelas' => $siswa->kelas ?? '-',
                    'kamar' => $siswa->kamar ?? '-',
                ],
                'message' => "Kartu ini sudah terdaftar atas nama {$siswa->nama_lengkap} (" . ($siswa->jenjang ?: 'Pondok') . ' • ' . ($siswa->kamar ?: '-') . ').',
            ]);
        }

        return response()->json([
            'status' => 'available',
            'serial' => $serial,
            'message' => 'Kartu baru / belum terdaftar. Siap dipasangkan.',
        ]);
    }

    /**
     * Simpan / pasangkan nomor kartu NFC ke santri terpilih.
     */
    public function assign(Request $request)
    {
        $request->validate([
            'rfid_uid' => 'required|string|max:32',
            'siswa_id' => 'required|exists:siswas,id',
        ], [
            'rfid_uid.required' => 'Nomor serial kartu wajib ada (tempelkan kartu pada reader).',
            'siswa_id.required' => 'Silakan pilih nama santri terlebih dahulu.',
            'siswa_id.exists'   => 'Data santri tidak ditemukan.',
        ]);

        $serial = trim($request->rfid_uid);
        $siswa = Siswa::findOrFail($request->siswa_id);

        // Jika kartu ini sebelumnya ada di santri lain, lepas dari santri lama agar tidak duplikat
        $previousOwner = Siswa::where('rfid_uid', $serial)
            ->where('id', '!=', $siswa->id)
            ->first();

        if ($previousOwner) {
            $previousOwner->update(['rfid_uid' => null]);
        }

        $siswa->update(['rfid_uid' => $serial]);

        $msg = "Kartu {$serial} berhasil dipasangkan ke {$siswa->nama_lengkap}.";
        if ($previousOwner) {
            $msg .= " (Dialihkan dari {$previousOwner->nama_lengkap})";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'siswa' => [
                    'id' => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'jenjang' => $siswa->jenjang ?? '-',
                    'kelas' => $siswa->kelas ?? '-',
                    'kamar' => $siswa->kamar ?? '-',
                    'rfid_uid' => $siswa->rfid_uid,
                    'updated_at' => $siswa->updated_at->locale('id')->diffForHumans(),
                ],
            ]);
        }

        return redirect()->route('nfc.index')->with('success', $msg);
    }

    /**
     * Lepas pairing kartu dari santri.
     */
    public function unpair(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
        ]);

        $siswa = Siswa::findOrFail($request->siswa_id);
        $oldSerial = $siswa->rfid_uid;
        $siswa->update(['rfid_uid' => null]);

        $msg = "Kartu {$oldSerial} berhasil dilepas dari {$siswa->nama_lengkap}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('nfc.index')->with('success', $msg);
    }
}
