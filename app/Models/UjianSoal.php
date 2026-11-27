<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjianSoal extends Model
{
    use HasFactory;

    protected $table = 'ujian_soal';

    protected $fillable = [
        'ujian_id',
        'tipe_soal',
        'nomor_soal',
        'pertanyaan',
        'opsi_a',
        'opsi_b',
        'opsi_c',
        'opsi_d',
        'opsi_e',
        'kunci_jawaban',
        'skor_maks',
        'pedoman_jawaban',
        'timer_detik',
        'gambar',
    ];

    protected $casts = [
        'nomor_soal'  => 'integer',
        'timer_detik' => 'integer',
        'skor_maks'   => 'decimal:2',
    ];

    public function isEssay(): bool
    {
        return ($this->tipe_soal ?? 'pg') === 'essay';
    }

    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    /**
     * Dapatkan daftar opsi pilihan non-empty
     */
    public function getDaftarOpsiAttribute(): array
    {
        $opsi = [
            'A' => $this->opsi_a,
            'B' => $this->opsi_b,
            'C' => $this->opsi_c,
            'D' => $this->opsi_d,
        ];

        if (!empty($this->opsi_e)) {
            $opsi['E'] = $this->opsi_e;
        }

        return $opsi;
    }
}

