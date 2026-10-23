<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerizinanSakit extends Model
{
    protected $fillable = [
        'siswa_id',
        'nama',
        'kamar',
        'tanggal_sakit',
        'sakit',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'tanggal_sakit' => 'date',
    ];

    /**
     * Relasi ke data siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
