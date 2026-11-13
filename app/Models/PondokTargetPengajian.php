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
        'surat_akhir_nomor',
        'nama_surat_akhir',
        'ayat_akhir',
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
            'tanggal'           => 'date',
            'surat_nomor'       => 'integer',
            'ayat'              => 'integer',
            'surat_akhir_nomor' => 'integer',
            'ayat_akhir'        => 'integer',
            'target_halaman'    => 'integer',
            'target_baris'      => 'integer',
            'halaman_awal'      => 'integer',
            'halaman_akhir'     => 'integer',
            'baris_awal'        => 'integer',
            'baris_akhir'       => 'integer',
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
            $hAwal = (int) ($this->halaman_awal ?: $this->target_halaman ?: 1);
            $bAwal = (int) ($this->baris_awal ?: $this->target_baris ?: 1);
            $hAkhir = (int) ($this->halaman_akhir ?: $hAwal);
            $bAkhir = (int) ($this->baris_akhir ?: $bAwal);

            if ($hAwal === $hAkhir) {
                if ($bAwal === $bAkhir) {
                    return "Hal. {$hAwal} Baris {$bAwal}";
                }
                return "Hal. {$hAwal} (Baris {$bAwal}–{$bAkhir})";
            }
            return "Hal. {$hAwal} Baris {$bAwal} – Hal. {$hAkhir} Baris {$bAkhir}";
        }

        if ($this->nama_surat && $this->ayat) {
            $sAwal  = $this->nama_surat;
            $aAwal  = (int) $this->ayat;
            $sAkhir = $this->nama_surat_akhir ?: $sAwal;
            $aAkhir = (int) ($this->ayat_akhir ?: $aAwal);

            if ($sAkhir === $sAwal) {
                if ($aAkhir === $aAwal) {
                    return "QS. {$sAwal} Ayat {$aAwal}";
                }
                return "QS. {$sAwal} Ayat {$aAwal}–{$aAkhir}";
            }

            return "QS. {$sAwal}: {$aAwal} – {$sAkhir}: {$aAkhir}";
        }

        $hal = (int) $this->target_halaman;
        return "{$hal} Halaman";
    }
}
