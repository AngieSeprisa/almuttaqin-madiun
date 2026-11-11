<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PondokMateri extends Model
{
    use HasFactory;

    public const KATEGORI_AL_QURAN = 'al_quran';
    public const KATEGORI_AL_HADIST = 'al_hadist';

    protected $table = 'pondok_materis';

    protected $fillable = [
        'kode_materi',
        'nama_materi',
        'kategori',
        'jumlah_halaman',
        'jumlah_baris',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_halaman' => 'integer',
            'jumlah_baris'   => 'integer',
            'is_active'      => 'boolean',
        ];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(PondokTargetPengajian::class, 'pondok_materi_id');
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(PondokJurnalMengaji::class, 'pondok_materi_id');
    }

    public function isAlQuran(): bool
    {
        return $this->kategori === self::KATEGORI_AL_QURAN;
    }

    public function isAlHadist(): bool
    {
        return $this->kategori === self::KATEGORI_AL_HADIST;
    }

    public function getKategoriLabelAttribute(): string
    {
        return $this->kategori === self::KATEGORI_AL_HADIST ? 'Al-Hadist' : "Al-Qur'an";
    }

    /**
     * Format volume materi:
     * - Al-Qur'an : menampilkan Jumlah Halaman
     * - Al-Hadist : menampilkan Halaman dan Baris
     */
    public function getVolumeLabelAttribute(): string
    {
        $hal = (int) $this->jumlah_halaman;
        if ($this->kategori === self::KATEGORI_AL_HADIST) {
            $baris = (int) ($this->jumlah_baris ?? 0);
            return "{$hal} Halaman • {$baris} Baris";
        }

        return "{$hal} Halaman";
    }
}

