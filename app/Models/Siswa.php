<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $fillable = [
        'nama_lengkap',
        'jenjang',
        'kelas',
        'kamar',
        'status',
        'pendaftaran_id',
    ];

    /**
     * Relasi ke data formulir pendaftaran
     */
    public function pendaftaran()
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    /**
     * Relasi ke riwayat perizinan sambang
     */
    public function perizinanSambang()
    {
        return $this->hasMany(PerizinanSambang::class);
    }

    /**
     * Relasi ke riwayat perizinan sakit
     */
    public function perizinanSakit()
    {
        return $this->hasMany(PerizinanSakit::class);
    }

    /**
     * Scope filter santri berdasarkan room/lembaga yang aktif.
     * Karena seluruh santri mukim di pondok, room 'pondok' & 'yayasan' menampilkan semua santri,
     * sedangkan room 'smp' atau 'sma' hanya menyaring santri dari jenjang tersebut.
     */
    public function scopeForLembaga(Builder $query, ?string $kodeLembaga): Builder
    {
        if (!$kodeLembaga || in_array($kodeLembaga, ['pondok', 'yayasan'])) {
            return $query;
        }

        $jenjang = strtoupper($kodeLembaga); // 'SMP' atau 'SMA'

        return $query->where(function ($q) use ($jenjang) {
            $q->where('jenjang', $jenjang)
              ->orWhereHas('pendaftaran', function ($p) use ($jenjang) {
                  $p->where('jenjang', $jenjang);
              });
        });
    }

    /**
     * Memeriksa apakah data diri santri sudah lengkap.
     * Mengembalikan false jika belum memiliki formulir pendaftaran
     * atau masih ada data penting yang belum diisi.
     */
    public function isDataLengkap(): bool
    {
        // 1. Data dasar siswa
        if (empty(trim($this->nama_lengkap ?? '')) || empty(trim($this->kamar ?? '')) || empty(trim($this->jenjang ?? ''))) {
            return false;
        }

        // 2. Data formulir pendaftaran
        if (!$this->pendaftaran_id || !$this->pendaftaran) {
            return false;
        }

        $p = $this->pendaftaran;
        $essentialFields = [
            'nik',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'alamat_jalan',
            'ayah_nama',
            'ibu_nama',
            'pj_biaya_hp',
        ];

        foreach ($essentialFields as $field) {
            $val = trim((string)($p->{$field} ?? ''));
            if ($val === '' || $val === '-' || $val === '0') {
                return false;
            }
        }

        return true;
    }

    /**
     * Mendapatkan daftar nama field data diri yang masih kosong/kurang lengkap.
     */
    public function getFieldBelumLengkapAttribute(): array
    {
        $missing = [];

        if (empty(trim($this->nama_lengkap ?? ''))) $missing[] = 'Nama Lengkap';
        if (empty(trim($this->kamar ?? ''))) $missing[] = 'Kamar';
        if (empty(trim($this->jenjang ?? ''))) $missing[] = 'Jenjang';

        if (!$this->pendaftaran_id || !$this->pendaftaran) {
            $missing[] = 'Formulir Biodata';
            return $missing;
        }

        $p = $this->pendaftaran;
        $labelMap = [
            'nik' => 'NIK',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat_jalan' => 'Alamat Jalan',
            'ayah_nama' => 'Nama Ayah',
            'ibu_nama' => 'Nama Ibu',
            'pj_biaya_hp' => 'No. HP/WA',
        ];

        foreach ($labelMap as $field => $label) {
            $val = trim((string)($p->{$field} ?? ''));
            if ($val === '' || $val === '-' || $val === '0') {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}
