<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjianKunci extends Model
{
    use HasFactory;

    protected $table = 'ujian_kunci';

    protected $fillable = [
        'ujian_id',
        'nomor_soal',
        'kunci_jawaban',
        'bobot',
    ];

    protected $casts = [
        'nomor_soal' => 'integer',
        'bobot'      => 'decimal:2',
    ];

    /**
     * Relasi ke Ujian
     */
    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }
}

