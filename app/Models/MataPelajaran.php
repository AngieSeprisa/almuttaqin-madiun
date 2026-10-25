<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    use HasFactory;

    protected $table = 'mata_pelajarans';

    protected $fillable = [
        'lembaga_id',
        'kode',
        'mata_pelajaran',
        // Alias backward compatibility:
        'kode_mapel',
        'nama_pelajaran',
    ];

    /**
     * Relasi ke Lembaga yang menaungi mata pelajaran ini
     */
    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class, 'lembaga_id');
    }

    /**
     * Scope filter per lembaga
     */
    public function scopeForLembaga($query, $lembagaId)
    {
        return $query->where('lembaga_id', $lembagaId);
    }

    /**
     * Scope filter per jenjang sekolah (SMP / SMA).
     */
    public function scopeForJenjang($query, string $jenjang)
    {
        $kode = strtolower($jenjang);
        return $query->whereHas('lembaga', function ($q) use ($kode) {
            $q->where('kode', $kode);
        });
    }

    /**
     * Scope aktif (kompatibilitas filter).
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Jenjang uppercase (SMP atau SMA).
     */
    public function getJenjangAttribute(): string
    {
        return strtoupper($this->lembaga?->kode ?? 'SMP');
    }

    /**
     * Relasi ke Jadwal Pelajaran
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'subject_id');
    }

    /**
     * Accessor & Mutator alias: nama_pelajaran <-> mata_pelajaran
     */
    public function getNamaPelajaranAttribute(): ?string
    {
        return $this->attributes['mata_pelajaran'] ?? null;
    }

    public function setNamaPelajaranAttribute($value): void
    {
        $this->attributes['mata_pelajaran'] = $value;
    }

    /**
     * Accessor & Mutator alias: kode_mapel <-> kode
     */
    public function getKodeMapelAttribute(): ?string
    {
        return $this->attributes['kode'] ?? null;
    }

    public function setKodeMapelAttribute($value): void
    {
        $this->attributes['kode'] = $value;
    }
}
