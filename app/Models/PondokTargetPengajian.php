<?php

namespace App\Models;

use App\Support\MushafMadinah;
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
        'sesi_pengajian',
        'kelas_pengajian_id',
        'pondok_materi_id',
        'surat_nomor',
        'nama_surat',
        'ayat',
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
            'surat_nomor'    => 'integer',
            'ayat'           => 'integer',
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

    public function getSesiLabelAttribute(): string
    {
        $map = MushafMadinah::daftarSesiPengajian();
        $key = $this->sesi_pengajian ?: 'bada_maghrib';
        return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    public function getTargetLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $hal = (int) $this->target_halaman;
            $baris = (int) ($this->target_baris ?? 0);
            return "Halaman {$hal} • Baris {$baris}";
        }

        if ($this->nama_surat && $this->ayat) {
            $hal = (int) ($this->target_halaman ?: MushafMadinah::estimasiHalaman((int) $this->surat_nomor, (int) $this->ayat));
            return "QS. {$this->nama_surat} Ayat {$this->ayat} • Hal. {$hal}";
        }

        $hal = (int) $this->target_halaman;
        return "{$hal} Halaman";
    }
}
