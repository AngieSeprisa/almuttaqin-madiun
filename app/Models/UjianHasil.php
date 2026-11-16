<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjianHasil extends Model
{
    use HasFactory;

    protected $table = 'ujian_hasil';

    protected $fillable = [
        'ujian_id',
        'siswa_id',
        'jawaban_siswa',
        'jumlah_benar',
        'jumlah_salah',
        'jumlah_kosong',
        'nilai_akhir',
        'status_scan',
        'catatan_scan',
        'waktu_scan',
    ];

    protected $casts = [
        'jawaban_siswa' => 'array',
        'jumlah_benar'  => 'integer',
        'jumlah_salah'  => 'integer',
        'jumlah_kosong' => 'integer',
        'nilai_akhir'   => 'decimal:2',
        'waktu_scan'    => 'datetime',
    ];

    /**
     * Relasi ke Ujian
     */
    public function ujian()
    {
        return $this->belongsTo(Ujian::class, 'ujian_id');
    }

    /**
     * Relasi ke Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Accessor label status scan
     */
    public function getStatusScanBadgeAttribute(): string
    {
        if ($this->status_scan === 'perlu_cek_manual') {
            return '<span class="status-badge badge-warning" title="Perlu Cek Manual">Perlu Cek Manual</span>';
        }
        return '<span class="status-badge badge-success" title="Valid">Valid</span>';
    }
}

