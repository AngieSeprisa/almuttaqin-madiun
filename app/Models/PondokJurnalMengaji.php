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
        'target_surat_akhir_nomor',
        'target_nama_surat_akhir',
        'target_ayat_akhir',
        'target_halaman',
        'target_baris',
        'target_halaman_awal',
        'target_halaman_akhir',
        'target_baris_awal',
        'target_baris_akhir',
        'capaian_surat_nomor',
        'capaian_nama_surat',
        'capaian_ayat',
        'capaian_surat_akhir_nomor',
        'capaian_nama_surat_akhir',
        'capaian_ayat_akhir',
        'capaian_halaman',
        'capaian_baris',
        'halaman_awal',
        'halaman_akhir',
        'baris_awal',
        'baris_akhir',
        'is_khatam',
        'lanjutan_materi_id',
        'lanjutan_halaman_awal',
        'lanjutan_baris_awal',
        'lanjutan_halaman_akhir',
        'lanjutan_baris_akhir',
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
            'tanggal'                   => 'date',
            'target_surat_nomor'        => 'integer',
            'target_ayat'               => 'integer',
            'target_surat_akhir_nomor'  => 'integer',
            'target_ayat_akhir'         => 'integer',
            'target_halaman'            => 'integer',
            'target_baris'              => 'integer',
            'target_halaman_awal'       => 'integer',
            'target_halaman_akhir'      => 'integer',
            'target_baris_awal'         => 'integer',
            'target_baris_akhir'        => 'integer',
            'capaian_surat_nomor'       => 'integer',
            'capaian_ayat'              => 'integer',
            'capaian_surat_akhir_nomor' => 'integer',
            'capaian_ayat_akhir'        => 'integer',
            'capaian_halaman'           => 'integer',
            'capaian_baris'             => 'integer',
            'halaman_awal'              => 'integer',
            'halaman_akhir'             => 'integer',
            'baris_awal'                => 'integer',
            'baris_akhir'               => 'integer',
            'is_khatam'                 => 'boolean',
            'lanjutan_materi_id'        => 'integer',
            'lanjutan_halaman_awal'     => 'integer',
            'lanjutan_baris_awal'       => 'integer',
            'lanjutan_halaman_akhir'    => 'integer',
            'lanjutan_baris_akhir'      => 'integer',
            'total_santri'              => 'integer',
            'total_hadir'               => 'integer',
            'total_terlambat'           => 'integer',
            'total_izin'                => 'integer',
            'total_sakit'               => 'integer',
            'total_alpa'                => 'integer',
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

    public function lanjutanMateri(): BelongsTo
    {
        return $this->belongsTo(PondokMateri::class, 'lanjutan_materi_id');
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
     * - Al-Qur'an : jika ada surat & ayat, bandingkan batas akhir [surat_nomor, ayat]; jika tidak, bandingkan halaman.
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
        ?int $capaianAyat = null,
        bool $isKhatam = false
    ): string {
        if ($isKhatam) {
            return self::STATUS_TERLAMPAUI;
        }

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
            $hAwal = (int) ($this->target_halaman_awal ?: $this->targetPengajian?->halaman_awal ?: $this->target_halaman ?: 1);
            $bAwal = (int) ($this->target_baris_awal ?: $this->targetPengajian?->baris_awal ?: $this->target_baris ?: 1);
            $hAkhir = (int) ($this->target_halaman_akhir ?: $this->targetPengajian?->halaman_akhir ?: $this->target_halaman ?: $hAwal);
            $bAkhir = (int) ($this->target_baris_akhir ?: $this->targetPengajian?->baris_akhir ?: $this->target_baris ?: $bAwal);

            if ($hAwal === $hAkhir) {
                if ($bAwal === $bAkhir) {
                    return "Hal. {$hAwal} Baris {$bAwal}";
                }
                return "Hal. {$hAwal} (Baris {$bAwal}–{$bAkhir})";
            }
            return "Hal. {$hAwal} Baris {$bAwal} – Hal. {$hAkhir} Baris {$bAkhir}";
        }

        if ($this->target_nama_surat && $this->target_ayat) {
            $sAwal  = $this->target_nama_surat;
            $aAwal  = (int) $this->target_ayat;
            $sAkhir = $this->target_nama_surat_akhir ?: $sAwal;
            $aAkhir = (int) ($this->target_ayat_akhir ?: $aAwal);

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

    public function getCapaianLabelAttribute(): string
    {
        $kategori = $this->materi?->kategori ?? PondokMateri::KATEGORI_AL_QURAN;

        if ($kategori === PondokMateri::KATEGORI_AL_HADIST) {
            $hAwal = (int) ($this->halaman_awal ?: $this->capaian_halaman ?: 1);
            $bAwal = (int) ($this->baris_awal ?: $this->capaian_baris ?: 1);
            $hAkhir = (int) ($this->halaman_akhir ?: $this->capaian_halaman ?: $hAwal);
            $bAkhir = (int) ($this->baris_akhir ?: $this->capaian_baris ?: $bAwal);

            if ($hAwal === $hAkhir && $bAwal === $bAkhir) {
                $baseText = "Hal. {$hAwal} Baris {$bAwal}";
            } elseif ($hAwal === $hAkhir) {
                $baseText = "Hal. {$hAwal} (Baris {$bAwal}–{$bAkhir})";
            } else {
                $baseText = "Hal. {$hAwal} Baris {$bAwal} – Hal. {$hAkhir} Baris {$bAkhir}";
            }

            if ($this->is_khatam) {
                if ($this->lanjutan_materi_id && $this->lanjutanMateri) {
                    $lName   = $this->lanjutanMateri->nama_materi;
                    $lhAkhir = (int) ($this->lanjutan_halaman_akhir ?: 1);
                    $lbAkhir = (int) ($this->lanjutan_baris_akhir ?: 1);

                    return "{$baseText} · Lanjut {$lName} s/d Hal. {$lhAkhir} Baris {$lbAkhir}";
                }
                return $baseText;
            }

            return $baseText;
        }

        if ($this->capaian_nama_surat && $this->capaian_ayat) {
            $sAwal  = $this->capaian_nama_surat;
            $aAwal  = (int) $this->capaian_ayat;
            $sAkhir = $this->capaian_nama_surat_akhir ?: $sAwal;
            $aAkhir = (int) ($this->capaian_ayat_akhir ?: $aAwal);

            if ($sAkhir === $sAwal) {
                if ($aAkhir === $aAwal) {
                    return "QS. {$sAwal} Ayat {$aAwal}";
                }
                return "QS. {$sAwal} Ayat {$aAwal}–{$aAkhir}";
            }

            return "QS. {$sAwal}: {$aAwal} – {$sAkhir}: {$aAkhir}";
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

        $tSuratEnd = (int) ($this->target_surat_akhir_nomor ?: $this->target_surat_nomor);
        $tAyatEnd  = (int) ($this->target_ayat_akhir ?: $this->target_ayat);
        $cSuratEnd = (int) ($this->capaian_surat_akhir_nomor ?: $this->capaian_surat_nomor);
        $cAyatEnd  = (int) ($this->capaian_ayat_akhir ?: $this->capaian_ayat);

        if ($kategori === PondokMateri::KATEGORI_AL_QURAN && $tSuratEnd && $cSuratEnd) {
            if ($tSuratEnd === $cSuratEnd) {
                $diffAyat = $cAyatEnd - $tAyatEnd;
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
