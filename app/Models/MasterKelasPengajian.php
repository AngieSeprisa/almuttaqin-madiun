<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterKelasPengajian extends Model
{
    use HasFactory;

    protected $table = 'master_kelas_pengajians';

    protected $fillable = [
        'kode_kelas',
        'nama_kelas',
        'jenjang',
    ];

    /**
     * Relasi santri yang tergabung dalam kelas pengajian ini.
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_pengajian_id');
    }

    /**
     * Daftar kelas pengajian standar.
     */
    public static function daftarStandar(): array
    {
        return [
            [
                'kode_kelas' => 'KLS_LAMBATAN_SMP',
                'nama_kelas' => 'Kelas Lambatan SMP',
                'jenjang'    => 'smp',
            ],
            [
                'kode_kelas' => 'KLS_CEPATAN_SMP',
                'nama_kelas' => 'Kelas Cepatan SMP',
                'jenjang'    => 'smp',
            ],
            [
                'kode_kelas' => 'KLS_PEGON_SMP',
                'nama_kelas' => 'Kelas Pegon Bacaan SMP',
                'jenjang'    => 'smp',
            ],
            [
                'kode_kelas' => 'KLS_REGULER',
                'nama_kelas' => 'Kelas Reguler',
                'jenjang'    => 'reguler',
            ],
        ];
    }

    /**
     * Seed master kelas pengajian standar jika belum ada.
     */
    public static function seedStandar(): int
    {
        $added = 0;
        foreach (self::daftarStandar() as $item) {
            $exists = self::where('kode_kelas', $item['kode_kelas'])
                ->orWhere('nama_kelas', $item['nama_kelas'])
                ->first();

            if (!$exists) {
                self::create($item);
                $added++;
            }
        }

        return $added;
    }
}

