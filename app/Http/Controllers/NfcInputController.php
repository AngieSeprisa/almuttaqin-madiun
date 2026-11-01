<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
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

        // Daftar guru & pegawai untuk dropdown / selector
        $gurus = User::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'nip', 'role', 'jabatan', 'rfid_uid']);

        // Riwayat kartu terakhir yang diinput (santri + guru)
        $recentSantri = Siswa::forLembaga($activeRoom)
            ->whereNotNull('rfid_uid')
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($s) {
                return (object)[
                    'id' => $s->id,
                    'tipe' => 'siswa',
                    'nama_lengkap' => $s->nama_lengkap,
                    'nama' => $s->nama_lengkap,
                    'jenjang' => $s->jenjang ?: 'Pondok',
                    'kelas' => $s->kelas,
                    'kamar' => $s->kamar ?: '-',
                    'rfid_uid' => $s->rfid_uid,
                    'updated_at' => $s->updated_at,
                ];
            });

        $recentGuru = User::where('is_active', true)
            ->whereNotNull('rfid_uid')
            ->orderBy('updated_at', 'desc')
            ->limit(15)
            ->get()
            ->map(function ($u) {
                return (object)[
                    'id' => $u->id,
                    'tipe' => 'guru',
                    'nama_lengkap' => $u->name,
                    'nama' => $u->name,
                    'jenjang' => 'Guru',
                    'kelas' => $u->jabatan ?: ucfirst($u->role ?? 'Pegawai'),
                    'kamar' => 'Staf / Pendidik',
                    'rfid_uid' => $u->rfid_uid,
                    'updated_at' => $u->updated_at,
                ];
            });

        $recentPairings = $recentSantri->concat($recentGuru)
            ->sortByDesc('updated_at')
            ->take(15)
            ->values();

        $totalSiswa = Siswa::forLembaga($activeRoom)->where('status', 'aktif')->count();
        $totalTerdaftar = Siswa::forLembaga($activeRoom)->where('status', 'aktif')->whereNotNull('rfid_uid')->count();
        $totalBelum = max(0, $totalSiswa - $totalTerdaftar);

        $totalGuru = User::where('is_active', true)->count();
        $totalGuruTerdaftar = User::where('is_active', true)->whereNotNull('rfid_uid')->count();
        $totalGuruBelum = max(0, $totalGuru - $totalGuruTerdaftar);

        return view('nfc.index', compact(
            'siswas',
            'gurus',
            'recentPairings',
            'totalSiswa',
            'totalTerdaftar',
            'totalBelum',
            'totalGuru',
            'totalGuruTerdaftar',
            'totalGuruBelum'
        ));
    }

    /**
     * Konversi berbagai format serial RFID / NFC ke Format Desimal 32-bit:
     * - Wiegand Split (;42992,47057 atau 42992,47057) -> 2817570769
     * - HEX 8-digit (A7F0B7D1)                        -> 2817570769
     * - Desimal 32-bit murni (2817570769)             -> 2817570769
     */
    public static function toDecimal32(string $uid): string
    {
        $clean = rtrim(trim($uid), '?');
        if (empty($clean)) {
            return '';
        }

        // Format split desimal: ;42992,47057 atau 42992,47057
        if (preg_match('/^;?(\d{1,5}),(\d{1,5})$/', $clean, $m)) {
            $w1 = (int)$m[1];
            $w2 = (int)$m[2];
            $hex = str_pad(dechex($w1), 4, '0', STR_PAD_LEFT) . str_pad(dechex($w2), 4, '0', STR_PAD_LEFT);
            return (string)hexdec($hex);
        }

        // Format HEX 8-digit dari ESP32 (misal A7F0B7D1)
        if (preg_match('/^[0-9A-Fa-f]{8}$/', $clean) && !ctype_digit($clean)) {
            return (string)hexdec($clean);
        }

        // Format angka murni (sudah desimal 32-bit atau serial angka)
        if (preg_match('/^\d+$/', $clean)) {
            return $clean;
        }

        // Format HEX umum
        if (preg_match('/^[0-9A-Fa-f]{4,16}$/', $clean)) {
            return (string)hexdec($clean);
        }

        return $clean;
    }

    /**
     * Membalik urutan byte dalam string HEX 8-digit (Big-Endian <-> Little-Endian).
     * Contoh: A7F0B7D1 -> D1B7F0A7
     */
    public static function reverseBytesHex(string $hex): string
    {
        $hex = strtoupper(trim($hex));
        if (strlen($hex) !== 8) {
            return $hex;
        }
        return substr($hex, 6, 2) . substr($hex, 4, 2) . substr($hex, 2, 2) . substr($hex, 0, 2);
    }

    /**
     * Menghasilkan semua varian representasi format serial RFID / NFC:
     * 1. Format raw apa adanya (misal: ';42992,47057' atau 'A7F0B7D1')
     * 2. Format tanpa titik koma: '42992,47057'
     * 3. Format angka gabungan: '4299247057'
     * 4. Format HEX 8-karakter (Wiegand 26/32-bit): 'A7F0B7D1'
     * 5. Format desimal 32-bit: '2817570769'
     * 6. Varian Byte-Reversed (Big-Endian <-> Little-Endian):
     *    - USB Reader membaca : 2817570769 (Hex: A7F0B7D1, Split: ;42992,47057)
     *    - ESP32 RC522 membaca: 3518492839 (Hex: D1B7F0A7, Split: ;53687,61607)
     */
    public static function getUidVariants(string $uid): array
    {
        $uid = rtrim(trim($uid), '?');
        if (empty($uid)) {
            return [];
        }

        $variants = [$uid];
        $upper = strtoupper($uid);
        $variants[] = $upper;
        $variants[] = strtolower($uid);

        // Jika diawali titik koma (;42992,47057)
        if (str_starts_with($uid, ';')) {
            $variants[] = substr($uid, 1);
        }

        $clean = ltrim($uid, ';');
        if (str_starts_with(strtoupper($clean), '0X')) {
            $clean = substr($clean, 2);
        }
        $variants[] = $clean;
        $variants[] = strtoupper($clean);
        $variants[] = strtolower($clean);

        // Normalisasi angka desimal jika pure digits (misal: 0086028247 <-> 86028247)
        if (ctype_digit($clean)) {
            $stripped = ltrim($clean, '0');
            if ($stripped !== '') {
                $variants[] = $stripped;
                $variants[] = str_pad($stripped, 10, '0', STR_PAD_LEFT);
                $variants[] = str_pad($stripped, 9, '0', STR_PAD_LEFT);
                $variants[] = str_pad($stripped, 8, '0', STR_PAD_LEFT);
            } else {
                $variants[] = '0';
                $variants[] = str_pad('0', 10, '0', STR_PAD_LEFT);
            }
        }

        $hexCandidates = [];

        // 1. Jika format split desimal: ;42992,47057 atau 42992,47057
        if (preg_match('/^;?(\d{1,5}),(\d{1,5})$/', $uid, $m)) {
            $w1 = (int)$m[1];
            $w2 = (int)$m[2];
            $variants[] = ";{$w1},{$w2}";
            $variants[] = "{$w1},{$w2}";
            $variants[] = "{$m[1]}{$m[2]}";

            $hex1 = str_pad(dechex($w1), 4, '0', STR_PAD_LEFT);
            $hex2 = str_pad(dechex($w2), 4, '0', STR_PAD_LEFT);
            $hexCandidates[] = strtoupper($hex1 . $hex2);
        }
        // 2. Jika format HEX 8-digit dari ESP32 (misal: A7F0B7D1 atau D1B7F0A7)
        elseif (preg_match('/^[0-9A-Fa-f]{8}$/', $clean) && !ctype_digit($clean)) {
            $hexCandidates[] = strtoupper($clean);
        }
        // 3. Jika format desimal 32-bit (angka murni, misal 2817570769, 86028247, atau 0086028247)
        elseif (preg_match('/^\d{1,12}$/', $clean)) {
            $decVal = (float)$clean;
            if ($decVal >= 0 && $decVal <= 4294967295) {
                $hexCandidates[] = strtoupper(str_pad(dechex((int)$decVal), 8, '0', STR_PAD_LEFT));
            }
        }

        // Kembangkan setiap kandidat HEX ke representasi normal dan byte-reversed
        foreach ($hexCandidates as $hex) {
            $hex = str_pad(strtoupper($hex), 8, '0', STR_PAD_LEFT);
            $variants[] = $hex;
            $variants[] = strtolower($hex);

            $dec = hexdec($hex);
            $decStr = (string)$dec;
            $variants[] = $decStr;
            $variants[] = str_pad($decStr, 10, '0', STR_PAD_LEFT);
            $variants[] = str_pad($decStr, 9, '0', STR_PAD_LEFT);
            $variants[] = str_pad($decStr, 8, '0', STR_PAD_LEFT);
            $variants[] = ltrim($decStr, '0');

            $w1 = hexdec(substr($hex, 0, 4));
            $w2 = hexdec(substr($hex, 4, 4));
            $variants[] = ";{$w1},{$w2}";
            $variants[] = "{$w1},{$w2}";
            $variants[] = "{$w1}{$w2}";

            // Varian Byte-Reversed (B0 B1 B2 B3 <-> B3 B2 B1 B0)
            if (strlen($hex) === 8) {
                $hexRev = self::reverseBytesHex($hex);
                $variants[] = $hexRev;
                $variants[] = strtolower($hexRev);

                $decRev = hexdec($hexRev);
                $decRevStr = (string)$decRev;
                $variants[] = $decRevStr;
                $variants[] = str_pad($decRevStr, 10, '0', STR_PAD_LEFT);
                $variants[] = str_pad($decRevStr, 9, '0', STR_PAD_LEFT);
                $variants[] = str_pad($decRevStr, 8, '0', STR_PAD_LEFT);
                $variants[] = ltrim($decRevStr, '0');

                $w1Rev = hexdec(substr($hexRev, 0, 4));
                $w2Rev = hexdec(substr($hexRev, 4, 4));
                $variants[] = ";{$w1Rev},{$w2Rev}";
                $variants[] = "{$w1Rev},{$w2Rev}";
                $variants[] = "{$w1Rev}{$w2Rev}";
            }
        }

        return array_values(array_unique(array_filter($variants, function ($v) {
            return $v !== null && $v !== '';
        })));
    }

    /**
     * Mencari siswa berdasarkan serial RFID dengan dukungan seluruh varian format
     * dan penanganan fleksibel leading-zero (misal: 0086028247 <-> 86028247).
     */
    public static function findSiswaByUid(string $serial, $excludeId = null): ?Siswa
    {
        $serial = rtrim(trim($serial), '?');
        if (empty($serial)) {
            return null;
        }

        $variants = self::getUidVariants($serial);
        $strippedDigits = array_values(array_unique(array_filter(array_map(function ($v) {
            return ctype_digit($v) ? ltrim($v, '0') : null;
        }, $variants))));

        $query = Siswa::query();
        $query->where(function ($q) use ($variants, $strippedDigits) {
            $q->whereIn('rfid_uid', $variants);
            if (!empty($strippedDigits)) {
                $driver = \DB::connection()->getDriverName();
                $placeholders = implode(',', array_fill(0, count($strippedDigits), '?'));
                if ($driver === 'sqlite') {
                    $q->orWhereRaw("LTRIM(rfid_uid, '0') IN ({$placeholders})", $strippedDigits);
                } else {
                    $q->orWhereRaw("TRIM(LEADING '0' FROM TRIM(rfid_uid)) IN ({$placeholders})", $strippedDigits);
                }
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Mencari user (guru / pegawai) berdasarkan serial RFID dengan seluruh varian format.
     */
    public static function findUserByUid(string $serial, $excludeId = null): ?User
    {
        $serial = rtrim(trim($serial), '?');
        if (empty($serial)) {
            return null;
        }

        $variants = self::getUidVariants($serial);
        $strippedDigits = array_values(array_unique(array_filter(array_map(function ($v) {
            return ctype_digit($v) ? ltrim($v, '0') : null;
        }, $variants))));

        $query = User::query();
        $query->where(function ($q) use ($variants, $strippedDigits) {
            $q->whereIn('rfid_uid', $variants);
            if (!empty($strippedDigits)) {
                $driver = \DB::connection()->getDriverName();
                $placeholders = implode(',', array_fill(0, count($strippedDigits), '?'));
                if ($driver === 'sqlite') {
                    $q->orWhereRaw("LTRIM(rfid_uid, '0') IN ({$placeholders})", $strippedDigits);
                } else {
                    $q->orWhereRaw("TRIM(LEADING '0' FROM TRIM(rfid_uid)) IN ({$placeholders})", $strippedDigits);
                }
            }
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * Cek status nomor serial kartu (apakah baru atau sudah terdaftar di Santri maupun Guru).
     */
    public function checkCard(Request $request)
    {
        $serial = rtrim(trim($request->input('serial', '')), '?');

        if (empty($serial)) {
            return response()->json([
                'status' => 'empty',
                'message' => 'Nomor serial belum diisi.',
            ]);
        }

        $siswa = self::findSiswaByUid($serial);
        if ($siswa) {
            return response()->json([
                'status' => 'assigned',
                'tipe' => 'siswa',
                'serial' => $serial,
                'entity' => [
                    'id' => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'jenjang' => $siswa->jenjang ?? 'Pondok',
                    'kelas' => $siswa->kelas ?? '-',
                    'kamar' => $siswa->kamar ?? '-',
                ],
                'siswa' => [
                    'id' => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap,
                    'jenjang' => $siswa->jenjang ?? 'Pondok',
                    'kelas' => $siswa->kelas ?? '-',
                    'kamar' => $siswa->kamar ?? '-',
                ],
                'message' => "Kartu ini sudah terdaftar atas nama Santri: {$siswa->nama_lengkap} (" . ($siswa->jenjang ?: 'Pondok') . ' • ' . ($siswa->kamar ?: '-') . ').',
            ]);
        }

        $user = self::findUserByUid($serial);
        if ($user) {
            return response()->json([
                'status' => 'assigned',
                'tipe' => 'guru',
                'serial' => $serial,
                'entity' => [
                    'id' => $user->id,
                    'nama_lengkap' => $user->name,
                    'role' => ucfirst($user->role ?? 'Guru/Pegawai'),
                    'jabatan' => $user->jabatan ?? '-',
                ],
                'message' => "Kartu ini sudah terdaftar atas nama Guru/Pegawai: {$user->name} (" . ($user->jabatan ?: ucfirst($user->role ?? 'Guru')) . ').',
            ]);
        }

        return response()->json([
            'status' => 'available',
            'serial' => $serial,
            'message' => 'Kartu baru / belum terdaftar. Siap dipasangkan.',
        ]);
    }

    /**
     * Simpan / pasangkan nomor kartu NFC ke santri atau guru terpilih.
     */
    public function assign(Request $request)
    {
        $tipe = $request->input('tipe', 'siswa');
        if ($request->has('user_id') && !$request->has('siswa_id')) {
            $tipe = 'guru';
        }

        if ($tipe === 'guru') {
            $request->validate([
                'rfid_uid' => 'required|string|max:32',
                'user_id'  => 'required|exists:users,id',
            ], [
                'rfid_uid.required' => 'Nomor serial kartu wajib ada (tempelkan kartu pada reader).',
                'user_id.required'  => 'Silakan pilih nama Guru/Pegawai terlebih dahulu.',
                'user_id.exists'    => 'Data Guru/Pegawai tidak ditemukan.',
            ]);

            $serial = rtrim(trim($request->rfid_uid), '?');
            $user = User::findOrFail($request->user_id);

            // Lepas kartu jika sebelumnya dimiliki santri atau user lain
            $prevSiswa = self::findSiswaByUid($serial);
            if ($prevSiswa) {
                $prevSiswa->update(['rfid_uid' => null]);
            }
            $prevUser = self::findUserByUid($serial, $user->id);
            if ($prevUser) {
                $prevUser->update(['rfid_uid' => null]);
            }

            $decimalSerial = self::toDecimal32($serial);
            $user->update(['rfid_uid' => $decimalSerial]);

            $msg = "Kartu {$decimalSerial} berhasil dipasangkan ke Guru/Pegawai {$user->name}.";
            if ($prevUser) {
                $msg .= " (Dialihkan dari {$prevUser->name})";
            } elseif ($prevSiswa) {
                $msg .= " (Dialihkan dari Santri {$prevSiswa->nama_lengkap})";
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'tipe' => 'guru',
                    'entity' => [
                        'id' => $user->id,
                        'nama' => $user->name,
                        'keterangan' => $user->jabatan ?: ucfirst($user->role ?? 'Guru/Pegawai'),
                        'rfid_uid' => $user->rfid_uid,
                        'updated_at' => $user->updated_at->locale('id')->diffForHumans(),
                    ],
                ]);
            }

            return redirect()->route('nfc.index')->with('success', $msg);
        }

        // Tipe Siswa
        $request->validate([
            'rfid_uid' => 'required|string|max:32',
            'siswa_id' => 'required|exists:siswas,id',
        ], [
            'rfid_uid.required' => 'Nomor serial kartu wajib ada (tempelkan kartu pada reader).',
            'siswa_id.required' => 'Silakan pilih nama santri terlebih dahulu.',
            'siswa_id.exists'   => 'Data santri tidak ditemukan.',
        ]);

        $serial = rtrim(trim($request->rfid_uid), '?');
        $siswa = Siswa::findOrFail($request->siswa_id);

        // Lepas kartu dari pemilik sebelumnya jika ada
        $prevSiswa = self::findSiswaByUid($serial, $siswa->id);
        if ($prevSiswa) {
            $prevSiswa->update(['rfid_uid' => null]);
        }
        $prevUser = self::findUserByUid($serial);
        if ($prevUser) {
            $prevUser->update(['rfid_uid' => null]);
        }

        $decimalSerial = self::toDecimal32($serial);
        $siswa->update(['rfid_uid' => $decimalSerial]);

        $msg = "Kartu {$decimalSerial} berhasil dipasangkan ke {$siswa->nama_lengkap}.";
        if ($prevSiswa) {
            $msg .= " (Dialihkan dari {$prevSiswa->nama_lengkap})";
        } elseif ($prevUser) {
            $msg .= " (Dialihkan dari Guru {$prevUser->name})";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'tipe' => 'siswa',
                'entity' => [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama_lengkap,
                    'keterangan' => ($siswa->jenjang ?: 'Pondok') . ($siswa->kamar ? " • Kamar {$siswa->kamar}" : ''),
                    'rfid_uid' => $siswa->rfid_uid,
                    'updated_at' => $siswa->updated_at->locale('id')->diffForHumans(),
                ],
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
     * Lepas pairing kartu dari santri atau guru.
     */
    public function unpair(Request $request)
    {
        $tipe = $request->input('tipe', 'siswa');
        if ($request->has('user_id') && !$request->has('siswa_id')) {
            $tipe = 'guru';
        }

        if ($tipe === 'guru') {
            $request->validate([
                'user_id' => 'required|exists:users,id',
            ]);
            $user = User::findOrFail($request->user_id);
            $oldSerial = $user->rfid_uid;
            $user->update(['rfid_uid' => null]);
            $msg = "Kartu {$oldSerial} berhasil dilepas dari Guru/Pegawai {$user->name}.";
        } else {
            $request->validate([
                'siswa_id' => 'required|exists:siswas,id',
            ]);
            $siswa = Siswa::findOrFail($request->siswa_id);
            $oldSerial = $siswa->rfid_uid;
            $siswa->update(['rfid_uid' => null]);
            $msg = "Kartu {$oldSerial} berhasil dilepas dari {$siswa->nama_lengkap}.";
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('nfc.index')->with('success', $msg);
    }
}

