<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ujian extends Model
{
    use HasFactory;

    protected $table = 'ujian';

    protected $fillable = [
        'lembaga_id',
        'kode_ujian',
        'nama_ujian',
        'mapel_id',
        'kelas_id',
        'guru_id',
        'jumlah_soal',
        'jumlah_soal_essay',
        'jumlah_opsi',
        'bobot_benar',
        'bobot_pg',
        'bobot_essay',
        'tanggal_ujian',
        'status',
        'file_soal',
        'timer_default',
        'timer_essay_default',
        'peraturan_ujian',
        'jam_mulai',
        'jam_selesai',
    ];

    protected $casts = [
        'jumlah_soal'         => 'integer',
        'jumlah_soal_essay'   => 'integer',
        'jumlah_opsi'         => 'integer',
        'timer_default'       => 'integer',
        'timer_essay_default' => 'integer',
        'bobot_benar'         => 'decimal:2',
        'bobot_pg'            => 'decimal:2',
        'bobot_essay'         => 'decimal:2',
        'tanggal_ujian'       => 'date',
    ];

    /**
     * Relasi ke Lembaga
     */
    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class, 'lembaga_id');
    }

    /**
     * Scope filter ujian berdasarkan lembaga/room tertentu.
     */
    public function scopeForLembaga($query, ?Lembaga $lembaga)
    {
        if (!$lembaga || Lembaga::isGlobalRoom($lembaga->kode)) {
            return $query;
        }

        return $query->where(function ($q) use ($lembaga) {
            $q->where('lembaga_id', $lembaga->id)
              ->orWhereHas('kelas', fn ($k) => $k->where('lembaga_id', $lembaga->id))
              ->orWhereHas('mapel', fn ($m) => $m->where('lembaga_id', $lembaga->id));
        });
    }

    /**
     * Relasi ke Butir Soal Ujian (PG lebih dulu lalu Essay)
     */
    public function soals()
    {
        return $this->hasMany(UjianSoal::class, 'ujian_id')
            ->orderByRaw("CASE WHEN tipe_soal = 'essay' THEN 1 ELSE 0 END")
            ->orderBy('nomor_soal');
    }

    /**
     * Relasi ke Butir Soal Pilihan Ganda
     */
    public function soalsPg()
    {
        return $this->hasMany(UjianSoal::class, 'ujian_id')
            ->where('tipe_soal', 'pg')
            ->orderBy('nomor_soal');
    }

    /**
     * Relasi ke Butir Soal Essay
     */
    public function soalsEssay()
    {
        return $this->hasMany(UjianSoal::class, 'ujian_id')
            ->where('tipe_soal', 'essay')
            ->orderBy('nomor_soal');
    }

    public function hasPg(): bool
    {
        return (int) $this->jumlah_soal > 0;
    }

    public function hasEssay(): bool
    {
        return (int) ($this->jumlah_soal_essay ?? 0) > 0;
    }

    /**
     * Kembalikan persentase bobot efektif PG & Essay (total selalu 100%)
     */
    public function getEffectiveWeights(): array
    {
        $hasPg = $this->hasPg();
        $hasEssay = $this->hasEssay();

        if ($hasPg && !$hasEssay) {
            return ['pg' => 100.0, 'essay' => 0.0];
        }

        if (!$hasPg && $hasEssay) {
            return ['pg' => 0.0, 'essay' => 100.0];
        }

        $pg = max(0.0, (float) ($this->bobot_pg ?? 70));
        $es = max(0.0, (float) ($this->bobot_essay ?? 30));
        $total = $pg + $es;

        if ($total <= 0.0) {
            return ['pg' => 70.0, 'essay' => 30.0];
        }

        return [
            'pg'    => round(($pg / $total) * 100, 2),
            'essay' => round(($es / $total) * 100, 2),
        ];
    }

    /**
     * Hitung Nilai Akhir gabungan PG & Essay memakai bobot persen
     */
    public function hitungNilaiAkhir(?float $nilaiPg, ?float $nilaiEssay): float
    {
        $weights = $this->getEffectiveWeights();
        $pg = $nilaiPg ?? 0.0;
        $es = $nilaiEssay ?? 0.0;

        return round((($pg * $weights['pg']) + ($es * $weights['essay'])) / 100.0, 2);
    }

    /**
     * Label ringkas komposisi soal (misal: "25 PG + 5 Essay")
     */
    public function getRingkasanSoalAttribute(): string
    {
        $parts = [];
        if ($this->hasPg()) {
            $parts[] = $this->jumlah_soal . ' PG';
        }
        if ($this->hasEssay()) {
            $parts[] = $this->jumlah_soal_essay . ' Essay';
        }

        return !empty($parts) ? implode(' + ', $parts) : '0 Soal';
    }

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
        $essayDinilai = $hasils->whereNotNull('nilai_essay')->count();
        $rataPg = $totalPeserta > 0 ? round($hasils->avg(fn ($h) => $h->nilai_pg ?? $h->nilai_akhir), 2) : 0;
        $rataEssay = $essayDinilai > 0 ? round($hasils->whereNotNull('nilai_essay')->avg('nilai_essay'), 2) : 0;

        return [
            'total_peserta'   => $totalPeserta,
            'rata_rata'       => $rataRata,
            'rata_rata_pg'    => $rataPg,
            'rata_rata_essay' => $rataEssay,
            'essay_dinilai'   => $essayDinilai,
            'nilai_tertinggi' => $tertinggi,
            'nilai_terendah'  => $terendah,
            'perlu_cek'       => $perluCek,
            'valid'           => $valid,
        ];
    }

    /**
     * Teks peraturan ujian efektif (default jika belum diatur)
     */
    public function getPeraturanEfektifAttribute(): string
    {
        if (!empty(trim($this->peraturan_ujian ?? ''))) {
            return $this->peraturan_ujian;
        }

        return "1. Periksa identitas yang tercetak pada lembar jawab sebelum mulai mengerjakan.\n" .
               "2. Hitamkan bulatan pilihan jawaban secara penuh menggunakan pensil 2B atau pulpen bertinta hitam pekat.\n" .
               "3. Dilarang melipat, meremas, atau mengotori 4 kotak hitam pekat di setiap sudut lembar kertas.\n" .
               "4. Soal ujian ditampilkan berurutan di layar proyektor kelas dengan batas waktu otomatis per nomor.\n" .
               "5. Dilarang berbicara, berbuat curang, atau bekerja sama selama sesi ujian berlangsung.";
    }

    /**
     * Hitung analisis butir soal (tingkat kesukaran, benar, salah, dan distribusi pengecoh/distraktor)
     */
    public function getAnalisisSoalAttribute(): array
    {
        $hasils = $this->hasils;
        $totalSiswa = $hasils->count();
        $kunciMap = $this->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $opsiLetters = $this->jumlah_opsi == 5 ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];

        $analisis = [];

        for ($no = 1; $no <= $this->jumlah_soal; $no++) {
            $kunci = strtoupper($kunciMap[$no] ?? 'A');
            $benar = 0;
            $salah = 0;
            $kosong = 0;
            $ganda = 0;
            $distribusi = array_fill_keys($opsiLetters, 0);

            foreach ($hasils as $h) {
                $jawab = $h->jawaban_siswa[$no] ?? null;

                if ($jawab === null) {
                    $kosong++;
                } elseif ($jawab === 'GANDA') {
                    $ganda++;
                    $salah++;
                } else {
                    $letter = strtoupper($jawab);
                    if (isset($distribusi[$letter])) {
                        $distribusi[$letter]++;
                    }
                    if ($letter === $kunci) {
                        $benar++;
                    } else {
                        $salah++;
                    }
                }
            }

            $persenBenar = $totalSiswa > 0 ? round(($benar / $totalSiswa) * 100, 1) : 0;

            $tingkatKesukaran = match (true) {
                $persenBenar >= 75 => 'Mudah',
                $persenBenar >= 35 => 'Sedang',
                default            => 'Sukar',
            };

            $analisis[$no] = [
                'nomor_soal'        => $no,
                'kunci'             => $kunci,
                'jumlah_benar'      => $benar,
                'jumlah_salah'      => $salah,
                'jumlah_kosong'     => $kosong,
                'jumlah_ganda'      => $ganda,
                'persen_benar'      => $persenBenar,
                'tingkat_kesukaran' => $tingkatKesukaran,
                'distribusi'        => $distribusi,
            ];
        }

        return $analisis;
    }
}

