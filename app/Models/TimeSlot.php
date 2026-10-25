<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenjang',
        'hari',
        'jam_ke',
        'waktu_mulai',
        'waktu_selesai',
        'is_break',
        'tipe_kegiatan',
        'label',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'jam_ke'    => 'integer',
        'urutan'    => 'integer',
        'is_break'  => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Jadwal yang menempati slot ini.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'time_slot_id');
    }

    /**
     * Scope hanya slot KBM (bukan istirahat/upacara/isoma).
     */
    public function scopeKbm($query)
    {
        return $query->where('is_break', false);
    }

    /**
     * Scope filter hari tertentu.
     */
    public function scopeHari($query, string $hari)
    {
        return $query->where('hari', ucfirst(strtolower($hari)));
    }

    /**
     * Scope hanya slot aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Keterangan slot (alias untuk label/tipe_kegiatan).
     */
    public function getKeteranganAttribute(): string
    {
        return $this->label ?? $this->tipe_kegiatan ?? 'Istirahat';
    }

    /**
     * Scope filter jenjang (SMP, SMA, atau Semua).
     */
    public function scopeForJenjang($query, string $jenjang)
    {
        $jenjang = strtoupper($jenjang);
        return $query->where(function ($q) use ($jenjang) {
            $q->where('jenjang', $jenjang)
              ->orWhere('jenjang', 'Semua');
        });
    }

    /**
     * Format jam mulai (HH:MM).
     */
    public function getWaktuMulaiFormattedAttribute(): string
    {
        return substr($this->waktu_mulai, 0, 5);
    }

    /**
     * Format jam selesai (HH:MM).
     */
    public function getWaktuSelesaiFormattedAttribute(): string
    {
        return substr($this->waktu_selesai, 0, 5);
    }

    /**
     * Rentang waktu string (contoh: "07:45 - 08:30").
     */
    public function getRentangWaktuAttribute(): string
    {
        return $this->waktu_mulai_formatted . ' - ' . $this->waktu_selesai_formatted;
    }
}
