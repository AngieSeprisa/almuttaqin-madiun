<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalAbsensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'jurnal_id',
        'siswa_id',
        'nama_siswa',
        'status',
        'keterangan',
    ];

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }
}
