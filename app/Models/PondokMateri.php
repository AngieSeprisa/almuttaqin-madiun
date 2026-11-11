<?php

namespace App\Models;

use App\Support\MushafMadinah;
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
        'paket_quran',
        'halaman_awal',
        'halaman_akhir',
        'jumlah_halaman',
        'jumlah_baris',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'halaman_awal'   => 'integer',
            'halaman_akhir'  => 'integer',
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
     * Deteksi halaman awal efektif untuk Al-Qur'an (Cepatan: 1, Lambatan: 342, Reguler: 1).
     */
    public function getEffectiveHalamanAwalAttribute(): int
    {
        if ($this->isAlHadist()) {
            return 1;
        }

        if ($this->paket_quran === MushafMadinah::PAKET_LAMBATAN) {
            return 342;
        }
        if ($this->paket_quran === MushafMadinah::PAKET_CEPATAN) {
            return 1;
        }
        if ($this->halaman_awal && $this->halaman_awal > 1) {
            return (int) $this->halaman_awal;
        }

        $namaLower = mb_strtolower((string) $this->nama_materi);
        if (str_contains($namaLower, 'lambatan')) {
            return 342;
        }

        return (int) ($this->halaman_awal ?: 1);
    }

    /**
     * Deteksi halaman akhir efektif untuk Al-Qur'an (Cepatan: 341, Lambatan: 604, Reguler: 604).
     */
    public function getEffectiveHalamanAkhirAttribute(): int
    {
        if ($this->isAlHadist()) {
            return max(1, (int) $this->jumlah_halaman);
        }

        if ($this->paket_quran === MushafMadinah::PAKET_CEPATAN) {
            return 341;
        }
        if ($this->paket_quran === MushafMadinah::PAKET_LAMBATAN || $this->paket_quran === MushafMadinah::PAKET_REGULER) {
            return 604;
        }

        $namaLower = mb_strtolower((string) $this->nama_materi);
        if (str_contains($namaLower, 'cepatan')) {
            return 341;
        }
        if (str_contains($namaLower, 'lambatan')) {
            return 604;
        }

        if ($this->halaman_akhir && $this->halaman_akhir > 0) {
            return (int) $this->halaman_akhir;
        }

        return MushafMadinah::TOTAL_HALAMAN;
    }

    public function getRentangHalamanLabelAttribute(): string
    {
        if ($this->isAlHadist()) {
            return "Hal. 1–{$this->jumlah_halaman}";
        }

        $awal = $this->effective_halaman_awal;
        $akhir = $this->effective_halaman_akhir;
        return "Hal. {$awal}–{$akhir}";
    }

    /**
     * Format volume materi:
     * - Al-Qur'an : menampilkan Jumlah Halaman (+ rentang halaman jika Cepatan/Lambatan)
     * - Al-Hadist : menampilkan Halaman dan Baris per Halaman
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

    /**
     * Daftar materi pondok standar (3 Paket Al-Qur'an Mushaf Madinah + 25 Kitab Al-Hadist Himpunan).
     */
    public static function daftarStandar(): array
    {
        return [
            // 1. Paket Al-Qur'an Mushaf Madinah 604 Halaman
            [
                'kode_materi'    => 'QUR-CPT',
                'nama_materi'    => "Al-Qur'an Cepatan",
                'kategori'       => self::KATEGORI_AL_QURAN,
                'paket_quran'    => MushafMadinah::PAKET_CEPATAN,
                'halaman_awal'   => 1,
                'halaman_akhir'  => 341,
                'jumlah_halaman' => 341,
                'jumlah_baris'   => null,
                'keterangan'     => 'Makna Al-Qur\'an Halaman 1–341 • QS. Al-Fatihah s/d Al-Hajj',
            ],
            [
                'kode_materi'    => 'QUR-LMB',
                'nama_materi'    => "Al-Qur'an Lambatan",
                'kategori'       => self::KATEGORI_AL_QURAN,
                'paket_quran'    => MushafMadinah::PAKET_LAMBATAN,
                'halaman_awal'   => 342,
                'halaman_akhir'  => 604,
                'jumlah_halaman' => 263,
                'jumlah_baris'   => null,
                'keterangan'     => 'Makna Al-Qur\'an Halaman 342–604 • QS. Al-Mu\'minun s/d An-Nas',
            ],
            [
                'kode_materi'    => 'QUR-REG',
                'nama_materi'    => "Al-Qur'an Reguler",
                'kategori'       => self::KATEGORI_AL_QURAN,
                'paket_quran'    => MushafMadinah::PAKET_REGULER,
                'halaman_awal'   => 1,
                'halaman_akhir'  => 604,
                'jumlah_halaman' => 604,
                'jumlah_baris'   => null,
                'keterangan'     => 'Makna Al-Qur\'an Halaman 1–604 • Mushaf Madinah Penuh',
            ],

            // 2. Kitab-Kitab Al-Hadist Pondok (25 Kitab Himpunan)
            ['kode_materi' => 'HAD-01', 'nama_materi' => 'K. Sholah',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 144, 'jumlah_baris' => 16, 'keterangan' => 'Kitabush Sholah'],
            ['kode_materi' => 'HAD-02', 'nama_materi' => 'K. Nawawi',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 68,  'jumlah_baris' => 16, 'keterangan' => 'Hadist Arba\'in Nawawi'],
            ['kode_materi' => 'HAD-03', 'nama_materi' => 'K. Adab',             'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 112, 'jumlah_baris' => 19, 'keterangan' => 'Kitabul Adab'],
            ['kode_materi' => 'HAD-04', 'nama_materi' => 'K. Da\'wat',          'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 96,  'jumlah_baris' => 19, 'keterangan' => 'Kitabud Da\'wat'],
            ['kode_materi' => 'HAD-05', 'nama_materi' => 'K. Janaiz',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 88,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Janaiz'],
            ['kode_materi' => 'HAD-06', 'nama_materi' => 'K. Shiyam',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 84,  'jumlah_baris' => 19, 'keterangan' => 'Kitabush Shiyam'],
            ['kode_materi' => 'HAD-07', 'nama_materi' => 'K. Manasik',          'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 120, 'jumlah_baris' => 19, 'keterangan' => 'Kitabul Manasik'],
            ['kode_materi' => 'HAD-08', 'nama_materi' => 'K. Manasik Wal Jihad','kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 136, 'jumlah_baris' => 19, 'keterangan' => 'Kitabul Manasik Wal Jihad'],
            ['kode_materi' => 'HAD-09', 'nama_materi' => 'K. Imarah',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 92,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Imarah'],
            ['kode_materi' => 'HAD-10', 'nama_materi' => 'K. Imarah Min Kanzil','kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 104, 'jumlah_baris' => 19, 'keterangan' => 'Kitabul Imarah Min Kanzil \'Ummal'],
            ['kode_materi' => 'HAD-11', 'nama_materi' => 'K. Khotbah',          'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 76,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Khotbah'],
            ['kode_materi' => 'HAD-12', 'nama_materi' => 'K. Shifatul Jannah',  'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 108, 'jumlah_baris' => 19, 'keterangan' => 'Kitabu Shifatil Jannah Wan Nar'],
            ['kode_materi' => 'HAD-13', 'nama_materi' => 'K. Ilmi',             'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 80,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul \'Ilmi'],
            ['kode_materi' => 'HAD-14', 'nama_materi' => 'K. Thaharah',         'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 72,  'jumlah_baris' => 16, 'keterangan' => 'Kitabuth Thaharah'],
            ['kode_materi' => 'HAD-15', 'nama_materi' => 'K. Faraidh',          'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 64,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Faraidh'],
            ['kode_materi' => 'HAD-16', 'nama_materi' => 'K. Nikah',            'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 96,  'jumlah_baris' => 19, 'keterangan' => 'Kitabun Nikah'],
            ['kode_materi' => 'HAD-17', 'nama_materi' => 'K. Thalaq',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 68,  'jumlah_baris' => 19, 'keterangan' => 'Kitabuth Thalaq'],
            ['kode_materi' => 'HAD-18', 'nama_materi' => 'K. Buyu\'',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 88,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Buyu\''],
            ['kode_materi' => 'HAD-19', 'nama_materi' => 'K. Qadha',            'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 76,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Qadha'],
            ['kode_materi' => 'HAD-20', 'nama_materi' => 'K. Hudud',            'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 80,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Hudud'],
            ['kode_materi' => 'HAD-21', 'nama_materi' => 'K. Ahkam',            'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 92,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Ahkam'],
            ['kode_materi' => 'HAD-22', 'nama_materi' => 'K. Fitnah',           'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 84,  'jumlah_baris' => 19, 'keterangan' => 'Kitabul Fitan'],
            ['kode_materi' => 'HAD-23', 'nama_materi' => 'K. Zuhud',            'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 90,  'jumlah_baris' => 19, 'keterangan' => 'Kitabuz Zuhud War Raqaiq'],
            ['kode_materi' => 'HAD-24', 'nama_materi' => 'K. Birrul Walidain',  'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 72,  'jumlah_baris' => 19, 'keterangan' => 'Kitabu Birril Walidain'],
            ['kode_materi' => 'HAD-25', 'nama_materi' => 'K. Ashhabun Nabi',    'kategori' => self::KATEGORI_AL_HADIST, 'jumlah_halaman' => 116, 'jumlah_baris' => 19, 'keterangan' => 'Kitabu Fadhailish Shahabah'],
        ];
    }

    public static function seedStandar(): int
    {
        $added = 0;
        foreach (self::daftarStandar() as $item) {
            $query = self::where('kode_materi', $item['kode_materi'])
                ->orWhereRaw('LOWER(nama_materi) = ?', [mb_strtolower($item['nama_materi'])]);

            if (!empty($item['paket_quran'])) {
                $shortName = mb_strtolower($item['paket_quran']);
                $shortCode = match ($item['paket_quran']) {
                    MushafMadinah::PAKET_CEPATAN  => 'CPT',
                    MushafMadinah::PAKET_LAMBATAN => 'LBT',
                    default                       => 'REG',
                };
                $query->orWhere('kode_materi', $shortCode)
                      ->orWhere('paket_quran', $item['paket_quran'])
                      ->orWhereRaw('LOWER(nama_materi) = ?', [$shortName]);
            }

            $exists = $query->first();

            if (!$exists) {
                self::create(array_merge([
                    'paket_quran'   => null,
                    'halaman_awal'  => 1,
                    'halaman_akhir' => $item['jumlah_halaman'],
                    'is_active'     => true,
                ], $item));
                $added++;
            } elseif (!empty($item['paket_quran']) && empty($exists->paket_quran)) {
                $exists->update([
                    'paket_quran'    => $item['paket_quran'],
                    'halaman_awal'   => $item['halaman_awal'],
                    'halaman_akhir'  => $item['halaman_akhir'],
                    'jumlah_halaman' => $item['jumlah_halaman'],
                ]);
            }
        }

        return $added;
    }
}
