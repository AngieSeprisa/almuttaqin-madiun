<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lembaga extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'singkatan',
        'kategori',
        'icon',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * User yang terafiliasi dengan lembaga ini.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_lembagas', 'lembaga_id', 'user_id');
    }

    /**
     * URL logo khusus lembaga atau fallback ke logo utama.
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->kode === 'smp' && file_exists(public_path('images/logo-smp.png'))) {
            return asset('images/logo-smp.png');
        }

        if ($this->kode === 'sma' && file_exists(public_path('images/logo-sma.png'))) {
            return asset('images/logo-sma.png');
        }

        return asset('images/logo.png');
    }

    /**
     * Pengaturan absensi lembaga.
     */
    public function pengaturanAbsensi()
    {
        return $this->hasOne(PengaturanAbsensi::class);
    }

    /**
     * Relasi mata pelajaran yang diajarkan di lembaga ini.
     */
    public function mataPelajarans()
    {
        return $this->hasMany(MataPelajaran::class);
    }

    /**
     * Dapatkan atau buat pengaturan absensi default untuk lembaga ini.
     */
    public function getOrCreatePengaturanAbsensi(): PengaturanAbsensi
    {
        return $this->pengaturanAbsensi ?? $this->pengaturanAbsensi()->create([
            'jam_masuk'                  => '07:00:00',
            'toleransi_terlambat_menit'  => 15,
            'jam_pulang'                 => '15:30:00',
            'latitude'                   => -7.327434,
            'longitude'                  => 108.220761,
            'radius_meter'               => 100,
            'is_active'                  => true,
            'qr_secret_key'              => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Teks judul lembaga untuk sidebar.
     */
    public function getBrandTitleAttribute(): string
    {
        return match ($this->kode) {
            'smp'     => 'SMP AL MUTTAQIN',
            'sma'     => 'SMA AL MUTTAQIN',
            'yayasan' => 'YAYASAN PONDOK PESANTREN MUTTAQIN',
            default   => 'PONDOK PESANTREN AL MUTTAQIN',
        };
    }

    /**
     * Nama singkat lembaga untuk tampilan filter & ringkas (Yayasan, Pondok, SMA, SMP).
     */
    public function getNamaSingkatAttribute(): string
    {
        return match (strtolower($this->kode)) {
            'yayasan' => 'Yayasan',
            'pondok'  => 'Pondok',
            'sma'     => 'SMA',
            'smp'     => 'SMP',
            default   => $this->singkatan ?: $this->nama,
        };
    }
}

