<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalEkskulAbsensi extends Model
{
    use HasFactory;

    protected $table = 'jurnal_ekskul_absensis';

    protected $fillable = [
        'jurnal_ekskul_id',
        'siswa_id',
        'nama_siswa',
        'kelas',
        'status',
        'keterangan',
    ];

    public function jurnalEkskul(): BelongsTo
    {
        return $this->belongsTo(JurnalEkskul::class, 'jurnal_ekskul_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
