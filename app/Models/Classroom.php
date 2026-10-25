<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'lembaga_id',
        'jenjang',
        'nama_kelas',
        'tingkat',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'tingkat'   => 'integer',
        'urutan'    => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Lembaga (SMP / SMA).
     */
    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class, 'lembaga_id');
    }

    /**
     * Relasi ke Jadwal Pelajaran di kelas ini.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'classroom_id');
    }

    /**
     * Scope filter per jenjang (SMP atau SMA).
     */
    public function scopeForJenjang($query, string $jenjang)
    {
        return $query->where('jenjang', strtoupper($jenjang));
    }

    /**
     * Scope kelas yang aktif saja.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format tampilan kelas yang rapi (contoh: "Kelas 7A").
     */
    public function getDisplayNameAttribute(): string
    {
        return 'Kelas ' . $this->nama_kelas;
    }
}
