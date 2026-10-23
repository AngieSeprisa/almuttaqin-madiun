<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresensiGuru extends Model
{
    use HasFactory;

    protected $table = 'presensi_gurus';

    protected $fillable = [
        'user_id',
        'lembaga_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'lat_masuk',
        'long_masuk',
        'jarak_masuk_meter',
        'lat_pulang',
        'long_pulang',
        'jarak_pulang_meter',
        'keterangan',
        'dokumen_pendukung',
        'metode_masuk',
        'metode_pulang',
    ];

    protected $casts = [
        'tanggal'            => 'date',
        'lat_masuk'          => 'float',
        'long_masuk'         => 'float',
        'jarak_masuk_meter'  => 'integer',
        'lat_pulang'         => 'float',
        'long_pulang'        => 'float',
        'jarak_pulang_meter' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class);
    }

    /**
     * Label status yang ramah dibaca.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'hadir'     => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin'      => 'Izin',
            'sakit'     => 'Sakit',
            'alpa'      => 'Alpa',
            default     => ucfirst($this->status),
        };
    }

    /**
     * Tentukan apakah scan masuk terhitung hadir tepat waktu atau terlambat.
     */
    public static function tentukanStatusMasuk(string $jamMasukStr, ?PengaturanAbsensi $setting): string
    {
        if (!$setting || empty($setting->jam_masuk)) {
            return 'hadir';
        }

        $jamBatas = Carbon::createFromTimeString($setting->jam_masuk)
            ->addMinutes($setting->toleransi_terlambat_menit ?? 0);
        $jamActual = Carbon::createFromTimeString($jamMasukStr);

        return $jamActual->greaterThan($jamBatas) ? 'terlambat' : 'hadir';
    }
}
