<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    use HasFactory;

    protected $table = 'ujian';

    protected $fillable = [
        'kode_ujian',
        'nama_ujian',
        'mapel_id',
        'kelas_id',
        'guru_id',
        'jumlah_soal',
        'jumlah_opsi',
        'bobot_benar',
        'tanggal_ujian',
        'status',
    ];

    protected $casts = [
        'jumlah_soal'   => 'integer',
        'jumlah_opsi'   => 'integer',
        'bobot_benar'   => 'decimal:2',
        'tanggal_ujian' => 'date',
    ];

    /**
     * Relasi ke Mata Pelajaran
     */
    public function mapel()
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    /**
     * Relasi ke Kelas
     */
    public function kelas()
    {
        return $this->belongsTo(Classroom::class, 'kelas_id');
    }

    /**
     * Relasi ke Guru Pengampu
     */
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Relasi ke Kunci Jawaban
     */
    public function kuncis()
    {
        return $this->hasMany(UjianKunci::class, 'ujian_id')->orderBy('nomor_soal');
    }

    /**
     * Relasi ke Hasil Ujian Siswa
     */
    public function hasils()
    {
        return $this->hasMany(UjianHasil::class, 'ujian_id');
    }

    /**
     * Scope filter per status
     */
    public function scopeStatus($query, $status)
    {
        if (!empty($status)) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Accessor label status dengan badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'aktif'   => '<span class="status-badge badge-success">Aktif</span>',
            'selesai' => '<span class="status-badge badge-secondary">Selesai</span>',
            default   => '<span class="status-badge badge-warning">Draft</span>',
        };
    }

    /**
     * Hitung ringkasan statistik hasil ujian
     */
    public function getStatistikAttribute(): array
    {
        $hasils = $this->hasils;
        $totalPeserta = $hasils->count();
        $rataRata = $totalPeserta > 0 ? round($hasils->avg('nilai_akhir'), 2) : 0;
        $tertinggi = $totalPeserta > 0 ? $hasils->max('nilai_akhir') : 0;
        $terendah = $totalPeserta > 0 ? $hasils->min('nilai_akhir') : 0;
        $perluCek = $hasils->where('status_scan', 'perlu_cek_manual')->count();
        $valid = $hasils->where('status_scan', 'valid')->count();

        return [
            'total_peserta' => $totalPeserta,
            'rata_rata'     => $rataRata,
            'nilai_tertinggi' => $tertinggi,
            'nilai_terendah'  => $terendah,
            'perlu_cek'     => $perluCek,
            'valid'         => $valid,
        ];
    }
}

