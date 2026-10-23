<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PresensiGuru;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PresensiController extends Controller
{
    /**
     * Tampilan utama presensi guru untuk scan dari perangkat masing-masing.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $currentLembaga = $user->currentLembaga() ?: ($user->accessibleLembagas()->first() ?: Lembaga::first());

        if (!$currentLembaga) {
            abort(404, 'Lembaga tidak ditemukan.');
        }

        $pengaturan = $currentLembaga->getOrCreatePengaturanAbsensi();

        // Jika guru membuka link via scan langsung dari kamera bawaan HP (iPhone/Android)
        if ($request->filled('token')) {
            $hasil = $this->prosesPresensi($user, $currentLembaga, $pengaturan, $request->token);
            if ($hasil['success']) {
                return redirect()->route('presensi.index')->with('success', $hasil['message']);
            } else {
                return redirect()->route('presensi.index')->with('error', $hasil['message']);
            }
        }

        $today = Carbon::today()->toDateString();

        // Presensi user hari ini
        $presensiHariIni = PresensiGuru::where('user_id', $user->id)
            ->where('tanggal', $today)
            ->where('lembaga_id', $currentLembaga->id)
            ->first();

        // Riwayat presensi 7 hari terakhir untuk guru ini
        $riwayatPribadi = PresensiGuru::with('lembaga')
            ->where('user_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->limit(7)
            ->get();

        return view('presensi.index', compact('currentLembaga', 'pengaturan', 'presensiHariIni', 'riwayatPribadi'));
    }

    /**
     * Proses scan QR dari perangkat guru via AJAX.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'token'     => 'required|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $currentLembaga = $user->currentLembaga() ?: ($user->accessibleLembagas()->first() ?: Lembaga::first());
        $pengaturan = $currentLembaga->getOrCreatePengaturanAbsensi();

        $lat = $request->filled('latitude') ? (float) $request->latitude : null;
        $long = $request->filled('longitude') ? (float) $request->longitude : null;

        $hasil = $this->prosesPresensi($user, $currentLembaga, $pengaturan, $request->token, $lat, $long);

        return response()->json($hasil, $hasil['success'] ? 200 : 422);
    }

    /**
     * Logika inti pencatatan presensi guru (Masuk / Pulang).
     */
    private function prosesPresensi($user, $currentLembaga, $pengaturan, string $rawToken, ?float $lat = null, ?float $long = null): array
    {
        $token = trim($rawToken);

        // Jika QR token berupa URL (misal scan lewat kamera bawaan HP), ambil parameter tokennya
        if (filter_var($token, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($token);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParts);
                if (isset($queryParts['token'])) {
                    $token = $queryParts['token'];
                }
            }
        }

        // 1. Validasi Keaslian & Masa Berlaku Token QR
        if (!$pengaturan->validasiQrToken($token)) {
            return [
                'success' => false,
                'message' => 'QR Code tidak valid atau sudah kedaluwarsa. Silakan arahkan kamera ke QR Code terbaru di layar monitor.',
            ];
        }

        // 2. Validasi Radius Geolocation (GPS)
        $jarakMeter = $pengaturan->hitungJarakMeter($lat, $long);

        // Hanya blokir presensi jika mode_lokasi diatur 'wajib'
        if ($pengaturan->perluKunciRadius() && $pengaturan->latitude && $pengaturan->longitude) {
            if (is_null($lat) || is_null($long)) {
                return [
                    'success' => false,
                    'message' => 'Izin lokasi (GPS) diperlukan untuk melakukan presensi. Silakan aktifkan GPS browser Anda.',
                ];
            }

            if ($jarakMeter > $pengaturan->radius_meter) {
                return [
                    'success' => false,
                    'message' => "Posisi Anda berada di luar radius sekolah ({$jarakMeter} meter dari titik lembaga. Maksimal {$pengaturan->radius_meter} meter).",
                ];
            }
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $jamSekarang = $now->format('H:i:s');

        // Cari atau inisialisasi presensi hari ini
        $presensi = PresensiGuru::firstOrNew([
            'user_id'    => $user->id,
            'tanggal'    => $today,
            'lembaga_id' => $currentLembaga->id,
        ]);

        // Skenario 1: Belum pernah scan masuk hari ini
        if (!$presensi->exists || is_null($presensi->jam_masuk)) {
            $status = PresensiGuru::tentukanStatusMasuk($jamSekarang, $pengaturan);

            $presensi->jam_masuk = $jamSekarang;
            $presensi->status = $status;
            $presensi->lat_masuk = $lat;
            $presensi->long_masuk = $long;
            $presensi->jarak_masuk_meter = $jarakMeter;
            $presensi->metode_masuk = 'qr_scan';
            $presensi->save();

            $statusText = $status === 'hadir' ? 'Hadir Tepat Waktu' : 'Terlambat';
            return [
                'success' => true,
                'action'  => 'masuk',
                'status'  => $status,
                'message' => "Presensi MASUK berhasil dicatat ({$jamSekarang} WIB - {$statusText}).",
                'jam'     => substr($jamSekarang, 0, 5),
                'jarak'   => $jarakMeter,
            ];
        }

        // Skenario 2: Sudah absen masuk, sekarang absen pulang
        if (is_null($presensi->jam_pulang)) {
            $presensi->jam_pulang = $jamSekarang;
            $presensi->lat_pulang = $lat;
            $presensi->long_pulang = $long;
            $presensi->jarak_pulang_meter = $jarakMeter;
            $presensi->metode_pulang = 'qr_scan';
            $presensi->save();

            return [
                'success' => true,
                'action'  => 'pulang',
                'status'  => $presensi->status,
                'message' => "Presensi PULANG berhasil dicatat ({$jamSekarang} WIB). Selamat beristirahat!",
                'jam'     => substr($jamSekarang, 0, 5),
                'jarak'   => $jarakMeter,
            ];
        }

        // Skenario 3: Sudah absen masuk dan pulang
        return [
            'success' => true,
            'action'  => 'selesai',
            'status'  => $presensi->status,
            'message' => "Anda sudah menyelesaikan presensi Masuk ({$presensi->jam_masuk}) dan Pulang ({$presensi->jam_pulang}) hari ini.",
            'jam'     => substr($presensi->jam_pulang, 0, 5),
        ];
    }


    /**
     * Pengajuan Izin / Sakit Mandiri oleh Guru.
     */
    public function izin(Request $request)
    {
        $validated = $request->validate([
            'status'            => 'required|in:izin,sakit',
            'keterangan'        => 'required|string|max:500',
            'dokumen_pendukung' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072',
        ]);

        $user = auth()->user();
        $currentLembaga = $user->currentLembaga() ?: ($user->accessibleLembagas()->first() ?: Lembaga::first());
        $today = Carbon::today()->toDateString();

        $presensi = PresensiGuru::firstOrNew([
            'user_id'    => $user->id,
            'tanggal'    => $today,
            'lembaga_id' => $currentLembaga->id,
        ]);

        // Upload berkas jika ada
        if ($request->hasFile('dokumen_pendukung')) {
            if ($presensi->dokumen_pendukung) {
                Storage::disk('public')->delete($presensi->dokumen_pendukung);
            }
            $path = $request->file('dokumen_pendukung')->store('presensi-dokumen', 'public');
            $presensi->dokumen_pendukung = $path;
        }

        $presensi->status = $validated['status'];
        $presensi->keterangan = $validated['keterangan'];
        $presensi->metode_masuk = 'manual';
        $presensi->save();

        return redirect()->route('presensi.index')
            ->with('success', 'Pengajuan ' . ucfirst($validated['status']) . ' berhasil dikirim.');
    }

    /**
     * Layar Kiosk QR Dinamis (untuk ditampilkan di monitor/TV lobi kantor).
     */
    public function kiosk(Request $request)
    {
        $user = auth()->user();
        $currentLembaga = $user->currentLembaga() ?: ($user->accessibleLembagas()->first() ?: Lembaga::first());
        $pengaturan = $currentLembaga->getOrCreatePengaturanAbsensi();

        $today = Carbon::today()->toDateString();
        $hadirHariIni = PresensiGuru::with('user')
            ->where('lembaga_id', $currentLembaga->id)
            ->where('tanggal', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('presensi.kiosk', compact('currentLembaga', 'pengaturan', 'hadirHariIni'));
    }

    /**
     * Endpoint API untuk update token QR dinamis di layar Kiosk.
     */
    public function kioskData()
    {
        $user = auth()->user();
        $currentLembaga = $user->currentLembaga() ?: ($user->accessibleLembagas()->first() ?: Lembaga::first());
        $pengaturan = $currentLembaga->getOrCreatePengaturanAbsensi();

        $today = Carbon::today()->toDateString();
        $hadirHariIni = PresensiGuru::with('user')
            ->where('lembaga_id', $currentLembaga->id)
            ->where('tanggal', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($p) {
                return [
                    'nama'   => $p->user->name ?? '-',
                    'status' => $p->status_label,
                    'masuk'  => $p->jam_masuk ? substr($p->jam_masuk, 0, 5) : '-',
                    'pulang' => $p->jam_pulang ? substr($p->jam_pulang, 0, 5) : '-',
                ];
            });

        $payload = $pengaturan->generateQrPayload();

        return response()->json([
            'token'     => $payload['token'],
            'qr_data'   => request()->root() . '/presensi?token=' . urlencode($payload['token']),
            'timestamp' => $payload['time'],
            'hadir'     => $hadirHariIni,
        ]);

    }
}
