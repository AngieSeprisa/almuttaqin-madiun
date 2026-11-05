<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JurnalEkskul extends Model
{
    use HasFactory;

    protected $table = 'jurnal_ekskuls';

    protected $fillable = [
        'ekstrakurikuler_id',
        'lembaga_id',
        'pembina_id',
        'nama_pembina',
        'nama_ekskul',
        'tanggal',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'tempat',
        'materi',
        'catatan',
        'total_siswa',
        'total_hadir',
        'total_sakit',
        'total_izin',
        'total_alpa',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_siswa' => 'integer',
        'total_hadir' => 'integer',
        'total_sakit' => 'integer',
        'total_izin' => 'integer',
        'total_alpa' => 'integer',
    ];

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'ekstrakurikuler_id');
    }

    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(JurnalEkskulAbsensi::class, 'jurnal_ekskul_id');
    }

    public function getPersentaseHadirAttribute(): float
    {
        if ($this->total_siswa > 0) {
            return round(($this->total_hadir / $this->total_siswa) * 100, 1);
        }
        return 0.0;
    }
}

