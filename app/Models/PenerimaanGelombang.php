<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanGelombang extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_santri_id',
        'nama_gelombang',
        'tanggal_mulai',
        'tanggal_selesai',
        'kuota',
        'urutan',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'kuota'           => 'integer',
        'urutan'          => 'integer',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanSantri::class, 'penerimaan_santri_id');
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class, 'penerimaan_gelombang_id');
    }
}
