<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiSantri extends Model
{
    protected $table = 'presensi_santris';

    protected $fillable = [
        'siswa_id',
        'rfid_uid',
        'device_id',
        'tanggal',
        'waktu',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
