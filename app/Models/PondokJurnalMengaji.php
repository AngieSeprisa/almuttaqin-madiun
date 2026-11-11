<?php

namespace App\Models;

use App\Support\MushafMadinah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PondokJurnalMengaji extends Model
{
    use HasFactory;

    public const STATUS_TERCAPAI   = 'tercapai';
    public const STATUS_KURANG     = 'kurang';
    public const STATUS_TERLAMPAUI = 'terlampaui';

    protected $table = 'pondok_jurnal_mengajis';

    protected $fillable = [
        'tanggal',
        'sesi_pengajian',
        'kelas_pengajian_id',
        'pondok_materi_id',
        'target_pengajian_id',
        'pengajar_id',
        'nama_pengajar',
        'target_surat_nomor',
        'target_nama_surat',
        'target_ayat',
        'target_halaman',
        'target_baris',
        'capaian_surat_nomor',
        'capaian_nama_surat',
        'capaian_ayat',
        'capaian_halaman',
        'capaian_baris',
        'halaman_awal',
        'halaman_akhir',
        'status_ketercapaian',
        'total_santri',
        'total_hadir',
        'total_terlambat',
        'total_izin',
        'total_sakit',
        'total_alpa',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'             => 'date',
            'target_surat_nomor'  => 'integer',
            'target_ayat'         => 'integer',
            'target_halaman'      => 'integer',
            'target_baris'        => 'integer',
            'capaian_surat_nomor' => 'integer',
            'capaian_ayat'        => 'integer',
            'capaian_halaman'     => 'integer',
            'capaian_baris'       => 'integer',
            'halaman_awal'        => 'integer',
            'halaman_akhir'       => 'integer',
            'total_santri'        => 'integer',
            'total_hadir'         => 'integer',
            'total_terlambat'     => 'integer',
            'total_izin'          => 'integer',
            'total_sakit'         => 'integer',
            'total_alpa'          => 'integer',
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

    public function absensis(): HasMany
    {
        return $this->hasMany(PondokJurnalAbsensi::class, 'jurnal_mengaji_id');
    }

    public function getSesiLabelAttribute(): string
    {
        $map = MushafMadinah::daftarSesiPengajian();
        $key = $this->sesi_pengajian ?: 'bada_maghrib';
        return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Hitung indikator ketercapaian: tercapai | kurang | terlampaui
     * - Al-Qur'an : jika ada surat & ayat, bandingkan [surat_nomor, ayat]; jika tidak, bandingkan halaman.
     * - Al-Hadist : bandingkan [halaman, baris].
     */
    public static function hitungStatusKetercapaian(
        string $kategori,
        int $targetHalaman,
        int $targetBaris,
        int $capaianHalaman,
        int $capaianBaris,
        ?int $targetSuratNomor = null,
        ?int $targetAyat = null,
        ?int $capaianSuratNomor = null,
        ?int $capaianAyat = null
    ): string {
        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $cmp = [$capaianHalaman, $capaianBaris] <=> [$targetHalaman, $targetBaris];
        } elseif ($targetSuratNomor && $targetAyat && $capaianSuratNomor && $capaianAyat) {
            $cmp = [$capaianSuratNomor, $capaianAyat] <=> [$targetSuratNomor, $targetAyat];
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

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $hal = (int) $this->target_halaman;
            $baris = (int) ($this->target_baris ?? 0);
            return "Halaman {$hal} • Baris {$baris}";
        }

        if ($this->target_nama_surat && $this->target_ayat) {
            $hal = (int) ($this->target_halaman ?: MushafMadinah::estimasiHalaman((int) $this->target_surat_nomor, (int) $this->target_ayat));
            return "QS. {$this->target_nama_surat} Ayat {$this->target_ayat} • Hal. {$hal}";
        }

        $hal = (int) $this->target_halaman;
        return "{$hal} Halaman";
    }

    public function getCapaianLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $hal = (int) $this->capaian_halaman;
            $baris = (int) ($this->capaian_baris ?? 0);
            return "Halaman {$hal} • Baris {$baris}";
        }

        if ($this->capaian_nama_surat && $this->capaian_ayat) {
            $hal = (int) ($this->capaian_halaman ?: MushafMadinah::estimasiHalaman((int) $this->capaian_surat_nomor, (int) $this->capaian_ayat));
            return "QS. {$this->capaian_nama_surat} Ayat {$this->capaian_ayat} • Hal. {$hal}";
        }

        $hal = (int) $this->capaian_halaman;
        return "{$hal} Halaman";
    }

    public function getSelisihLabelAttribute(): string
    {
        if ($this->status_ketercapaian === self::STATUS_TERCAPAI) {
            return 'Sesuai Target';
        }

        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;

        if ($kategori === PondokMateri::KATEGORI_AL_QURAN && $this->target_surat_nomor && $this->capaian_surat_nomor) {
            if ((int) $this->target_surat_nomor === (int) $this->capaian_surat_nomor) {
                $diffAyat = (int) $this->capaian_ayat - (int) $this->target_ayat;
                $sign = $diffAyat > 0 ? '+' : '';
                return "{$sign}{$diffAyat} Ayat";
            }
            $diffHal = (int) $this->capaian_halaman - (int) $this->target_halaman;
            if ($diffHal !== 0) {
                $sign = $diffHal > 0 ? '+' : '';
                return "{$sign}{$diffHal} Hal";
            }
        }

        $diffHal = (int) $this->capaian_halaman - (int) $this->target_halaman;
        $diffBaris = (int) ($this->capaian_baris ?? 0) - (int) ($this->target_baris ?? 0);

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
