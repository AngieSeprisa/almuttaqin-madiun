<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanAbsensi extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_absensis';

    protected $fillable = [
        'lembaga_id',
        'jam_masuk',
        'toleransi_terlambat_menit',
        'jam_pulang',
        'latitude',
        'longitude',
        'radius_meter',
        'mode_lokasi',
        'qr_secret_key',
        'is_active',
    ];

    protected $casts = [
        'toleransi_terlambat_menit' => 'integer',
        'latitude'                  => 'float',
        'longitude'                 => 'float',
        'radius_meter'              => 'integer',
        'is_active'                 => 'boolean',
    ];

    /**
     * Cek apakah validasi radius GPS bersifat wajib memblokir guru.
     */
    public function perluKunciRadius(): bool
    {
        return $this->mode_lokasi === 'wajib';
    }


    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class);
    }

    /**
     * Hitung jarak (meter) dari koordinat sekolah menggunakan Haversine Formula.
     */
    public function hitungJarakMeter(?float $lat, ?float $long): ?int
    {
        if (is_null($lat) || is_null($long) || is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }

        $earthRadius = 6371000; // meter

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($long);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return (int) round($angle * $earthRadius);
    }

    /**
     * Cek apakah koordinat berada dalam batas radius absensi.
     */
    public function beradaDalamRadius(?float $lat, ?float $long): bool
    {
        if (is_null($lat) || is_null($long)) {
            return false;
        }

        $jarak = $this->hitungJarakMeter($lat, $long);
        if (is_null($jarak)) {
            return true; // jika koordinat belum di-setting, jangan kunci guru
        }

        return $jarak <= $this->radius_meter;
    }

    /**
     * Generate dynamic QR token dengan validitas 60 detik.
     */
    public function generateQrPayload(): array
    {
        $secret = $this->qr_secret_key ?: 'al-muttaqin-secret-salt';
        $timestamp = time();
        // Window token tiap 20 detik
        $window = floor($timestamp / 20);
        $hash = hash_hmac('sha256', "{$this->lembaga_id}|{$window}", $secret);

        return [
            'lembaga_id' => $this->lembaga_id,
            'time'       => $timestamp,
            'window'     => $window,
            'hash'       => substr($hash, 0, 16),
            'token'      => base64_encode("{$this->lembaga_id}:{$window}:" . substr($hash, 0, 16)),
        ];
    }

    /**
     * Validasi dynamic token (toleransi 1 window sebelum dan 1 sesudah = ~60 detik).
     */
    public function validasiQrToken(string $token): bool
    {
        $decoded = base64_decode($token);
        if (!$decoded) return false;

        $parts = explode(':', $decoded);
        if (count($parts) !== 3) return false;

        [$lembagaId, $window, $hash] = $parts;
        if ((int)$lembagaId !== (int)$this->lembaga_id) return false;

        $secret = $this->qr_secret_key ?: 'al-muttaqin-secret-salt';
        $currentWindow = (int) floor(time() / 20);

        // Perbolehkan window sekarang, atau 1 window sebelumnya/sesudahnya
        for ($w = $currentWindow - 1; $w <= $currentWindow + 1; $w++) {
            $expectedHash = substr(hash_hmac('sha256', "{$this->lembaga_id}|{$w}", $secret), 0, 16);
            if ((int)$window === $w && hash_equals($expectedHash, $hash)) {
                return true;
            }
        }

        return false;
    }
}
