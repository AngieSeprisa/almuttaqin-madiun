<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UjianSesiJam extends Model
{
    use HasFactory;

    protected $table = 'ujian_sesi_jams';

    protected $fillable = [
        'lembaga_id',
        'nama_sesi',
        'jam_mulai',
        'jam_selesai',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'urutan'    => 'integer',
        'is_active' => 'boolean',
    ];

    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class, 'lembaga_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLembaga($query, ?Lembaga $lembaga)
    {
        if (!$lembaga || Lembaga::isGlobalRoom($lembaga->kode)) {
            return $query;
        }

        return $query->where('lembaga_id', $lembaga->id);
    }

    public function getJamMulaiFormattedAttribute(): string
    {
        return substr((string) $this->jam_mulai, 0, 5);
    }

    public function getJamSelesaiFormattedAttribute(): string
    {
        return substr((string) $this->jam_selesai, 0, 5);
    }

    public function getLabelLengkapAttribute(): string
    {
        return trim($this->nama_sesi) . ' (' . $this->jam_mulai_formatted . ' - ' . $this->jam_selesai_formatted . ')';
    }
}
