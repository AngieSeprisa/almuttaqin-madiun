<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'jenjang',
        'kelas',
        'kamar',
        'status',
        'pendaftaran_id',
    ];

    /**
     * Relasi ke data formulir pendaftaran
     */
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    /**
     * Relasi ke riwayat perizinan sambang
     */
    public function perizinanSambang()
    {
        return $this->hasMany(PerizinanSambang::class);
    }

    /**
     * Relasi ke riwayat perizinan sakit
     */
    public function perizinanSakit()
    {
        return $this->hasMany(PerizinanSakit::class);
    }

    /**
     * Scope filter santri berdasarkan room/lembaga yang aktif.
     * Karena seluruh santri mukim di pondok, room 'pondok' & 'yayasan' menampilkan semua santri,
     * sedangkan room 'smp' atau 'sma' hanya menyaring santri dari jenjang tersebut.
     */
    public function scopeForLembaga(Builder $query, ?string $kodeLembaga): Builder
    {
        if (!$kodeLembaga || in_array($kodeLembaga, ['pondok', 'yayasan'])) {
            return $query;
        }

        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        return $query->where(function ($q) use ($jenjang) {
            $q->where('jenjang', $jenjang)
              ->orWhereHas('pendaftaran', function ($p) use ($jenjang) {
                  $p->where('jenjang', $jenjang);
              });
        });
    }
}
