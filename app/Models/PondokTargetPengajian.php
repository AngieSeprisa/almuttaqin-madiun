<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PondokTargetPengajian extends Model
{
    use HasFactory;

    protected $table = 'pondok_target_pengajians';

    protected $fillable = [
        'tanggal',
        'kelas_pengajian_id',
        'pondok_materi_id',
        'target_halaman',
        'target_baris',
        'halaman_awal',
        'halaman_akhir',
        'baris_awal',
        'baris_akhir',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'        => 'date',
            'target_halaman' => 'integer',
            'target_baris'   => 'integer',
            'halaman_awal'   => 'integer',
            'halaman_akhir'  => 'integer',
            'baris_awal'     => 'integer',
            'baris_akhir'    => 'integer',
        ];
    }

    public function kelasPengajian(): BelongsTo
    {
        return $this->belongsTo(MasterKelasPengajian::class, 'kelas_pengajian_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(PondokMateri::class, 'pondok_materi_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(PondokJurnalMengaji::class, 'target_pengajian_id');
    }

    public function getTargetLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;
        $hal = (int) $this->target_halaman;
        $rangeStr = '';

        if ($this->halaman_awal && $this->halaman_akhir) {
            $rangeStr = " (Hal. {$this->halaman_awal}–{$this->halaman_akhir})";
        }

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $baris = (int) ($this->target_baris ?? 0);
            return "{$hal} Halaman • {$baris} Baris{$rangeStr}";
        }

        return "{$hal} Halaman{$rangeStr}";
    }
}

