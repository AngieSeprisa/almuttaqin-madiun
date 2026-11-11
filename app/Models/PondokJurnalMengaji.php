<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondokJurnalMengaji extends Model
{
    use HasFactory;

    public const STATUS_TERCAPAI   = 'tercapai';
    public const STATUS_KURANG     = 'kurang';
    public const STATUS_TERLAMPAUI = 'terlampaui';

    protected $table = 'pondok_jurnal_mengajis';

    protected $fillable = [
        'tanggal',
        'kelas_pengajian_id',
        'pondok_materi_id',
        'target_pengajian_id',
        'pengajar_id',
        'nama_pengajar',
        'target_halaman',
        'target_baris',
        'capaian_halaman',
        'capaian_baris',
        'halaman_awal',
        'halaman_akhir',
        'status_ketercapaian',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'         => 'date',
            'target_halaman'  => 'integer',
            'target_baris'    => 'integer',
            'capaian_halaman' => 'integer',
            'capaian_baris'   => 'integer',
            'halaman_awal'    => 'integer',
            'halaman_akhir'   => 'integer',
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

    public function targetPengajian(): BelongsTo
    {
        return $this->belongsTo(PondokTargetPengajian::class, 'target_pengajian_id');
    }

    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * Hitung indikator ketercapaian: tercapai | kurang | terlampaui
     */
    public static function hitungStatusKetercapaian(
        string $kategori,
        int $targetHalaman,
        int $targetBaris,
        int $capaianHalaman,
        int $capaianBaris
    ): string {
        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $cmp = [$capaianHalaman, $capaianBaris] <=> [$targetHalaman, $targetBaris];
        } else {
            $cmp = $capaianHalaman <=> $targetHalaman;
        }

        if ($cmp > 0) {
            return self::STATUS_TERLAMPAUI;
        }
        if ($cmp < 0) {
            return self::STATUS_KURANG;
        }

        return self::STATUS_TERCAPAI;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_ketercapaian) {
            self::STATUS_TERLAMPAUI => 'Terlampaui',
            self::STATUS_KURANG     => 'Kurang',
            default                 => 'Tercapai',
        };
    }

    public function getTargetLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;
        $hal = (int) $this->target_halaman;

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $baris = (int) ($this->target_baris ?? 0);
            return "{$hal} Halaman • {$baris} Baris";
        }

        return "{$hal} Halaman";
    }

    public function getCapaianLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;
        $hal = (int) $this->capaian_halaman;
        $rangeStr = '';

        if ($this->halaman_awal && $this->halaman_akhir) {
            $rangeStr = " (Hal. {$this->halaman_awal}–{$this->halaman_akhir})";
        }

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $baris = (int) ($this->capaian_baris ?? 0);
            return "{$hal} Halaman • {$baris} Baris{$rangeStr}";
        }

        return "{$hal} Halaman{$rangeStr}";
    }

    public function getSelisihLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;
        $diffHal = (int) $this->capaian_halaman - (int) $this->target_halaman;
        $diffBaris = (int) ($this->capaian_baris ?? 0) - (int) ($this->target_baris ?? 0);

        if ($this->status_ketercapaian === self::STATUS_TERCAPAI) {
            return '100% Sesuai Target';
        }

        $parts = [];
        if ($diffHal !== 0) {
            $sign = $diffHal > 0 ? '+' : '';
            $parts[] = "{$sign}{$diffHal} Hal";
        }
        if ($kategori === PondokMateri::KATEGORI_AL_HADIST && $diffBaris !== 0) {
            $signB = $diffBaris > 0 ? '+' : '';
            $parts[] = "{$signB}{$diffBaris} Baris";
        }

        return !empty($parts) ? implode(', ', $parts) : 'Sesuai Target';
    }
}

