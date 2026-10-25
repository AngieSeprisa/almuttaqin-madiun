<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'time_slot_id',
        'subject_id',
        'teacher_id',
        'tahun_ajaran',
        'semester',
        'is_locked',
        'catatan',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    /**
     * Relasi ke Ruang Kelas.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    /**
     * Relasi ke Slot Jam / Waktu.
     */
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    /**
     * Relasi ke Mata Pelajaran (Plotting Mata Pelajaran).
     */
    public function subject()
    {
        return $this->belongsTo(MataPelajaran::class, 'subject_id');
    }

    /**
     * Relasi ke Guru Pengampu.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Scope tahun ajaran & semester aktif.
     */
    public function scopeActivePeriod($query, string $tahunAjaran = '2026/2027', string $semester = 'Gasal')
    {
        return $query->where('tahun_ajaran', $tahunAjaran)
                     ->where('semester', $semester);
    }

    /**
     * Scope filter per jenjang melalui relasi kelas.
     */
    public function scopeForJenjang($query, string $jenjang)
    {
        return $query->whereHas('classroom', function ($q) use ($jenjang) {
            $q->where('jenjang', strtoupper($jenjang));
        });
    }

    /**
     * Scope filter guru tertentu.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}
