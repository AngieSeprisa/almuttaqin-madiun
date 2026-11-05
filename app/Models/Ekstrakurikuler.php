<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ekstrakurikuler extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikulers';

    protected $fillable = [
        'lembaga_id',
        'pembina_id',
        'nama_ekskul',
        'kode',
        'jenjang',
        'nama_pembina',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'tempat',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(EkstrakurikulerSiswa::class, 'ekstrakurikuler_id');
    }

    public function siswas(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'ekstrakurikuler_siswas', 'ekstrakurikuler_id', 'siswa_id')
            ->withPivot(['id', 'peran', 'tanggal_bergabung', 'is_active'])
            ->withTimestamps();
    }

    public function jurnals(): HasMany
    {
        return $this->hasMany(JurnalEkskul::class, 'ekstrakurikuler_id');
    }

    /**
     * Format jam mulai - selesai untuk display
     */
    public function getWaktuLatihanAttribute(): string
    {
        if ($this->jam_mulai && $this->jam_selesai) {
            $mulai = substr((string) $this->jam_mulai, 0, 5);
            $selesai = substr((string) $this->jam_selesai, 0, 5);
            return "{$mulai} - {$selesai} WIB";
        }
        return '-';
    }
}
