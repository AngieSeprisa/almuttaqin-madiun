<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    use HasFactory;

    protected $fillable = [
        'lembaga_id',
        'guru_id',
        'nama_guru',
        'kelas',
        'semester',
        'tahun_pelajaran',
        'hari',
        'tanggal',
        'mata_pelajaran',
        'jam_ke',
        'materi',
        'catatan',
        'total_siswa',
        'total_hadir',
        'total_sakit',
        'total_izin',
        'total_alpa',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'total_siswa' => 'integer',
        'total_hadir' => 'integer',
        'total_sakit' => 'integer',
        'total_izin'  => 'integer',
        'total_alpa'  => 'integer',
    ];

    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function absensis()
    {
        return $this->hasMany(JurnalAbsensi::class);
    }

    /**
     * Scope filter jurnal berdasarkan ruang kerja / lembaga aktif
     */
    public function scopeForLembaga(Builder $query, ?string $kodeLembaga): Builder
    {
        if (!$kodeLembaga || in_array($kodeLembaga, ['yayasan', 'pondok'])) {
            return $query;
        }

        return $query->whereHas('lembaga', function ($q) use ($kodeLembaga) {
            $q->where('kode', $kodeLembaga);
        });
    }
}
