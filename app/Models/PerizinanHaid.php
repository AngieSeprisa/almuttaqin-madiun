<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PerizinanHaid extends Model
{
    protected $fillable = [
        'siswa_id',
        'nama',
        'kamar',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Relasi ke data Siswa
     */
    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Relasi ke pencatat (User)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mengecek apakah izin haid ini aktif pada tanggal tertentu.
     */
    public function isActiveOn(string $dateStr): bool
    {
        if (!$this->tanggal_mulai) {
            return false;
        }

        $mulaiStr = $this->tanggal_mulai->toDateString();
        if ($dateStr < $mulaiStr) {
            return false;
        }

        if ($this->status === 'sudah_suci') {
            if ($this->tanggal_selesai) {
                return $dateStr <= $this->tanggal_selesai->toDateString();
            }
            return false;
        }

        // Jika sedang_haid
        if ($this->tanggal_selesai) {
            return $dateStr <= $this->tanggal_selesai->toDateString();
        }

        // Batas maksimal udzur haid syar'i 15 hari
        $maxDate = $this->tanggal_mulai->copy()->addDays(15)->toDateString();
        return $dateStr <= $maxDate;
    }
}
