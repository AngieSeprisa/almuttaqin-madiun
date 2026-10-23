<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PerizinanSambang extends Model
{
    protected $fillable = [
        'siswa_id',
        'nama',
        'kamar',
        'tanggal_mulai',
        'tanggal_kembali',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_kembali' => 'date',
    ];

    /**
     * Relasi ke data siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Helper untuk cek apakah sambang melewati batas estimasi kembali
     */
    public function getEffectiveStatusAttribute()
    {
        if ($this->status === 'sambang' && $this->tanggal_kembali && $this->tanggal_kembali->isPast() && !$this->tanggal_kembali->isToday()) {
            return 'melewati_batas';
        }
        return $this->status;
    }
}
