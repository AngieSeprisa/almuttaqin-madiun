<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    /**
     * Pemetaan kolom foto berkas => [kolom checklist, label].
     */
    public const FOTO_BERKAS = [
        'foto_akta' => ['berkas_akta', 'Akta Kelahiran'],
        'foto_kk' => ['berkas_kk', 'Kartu Keluarga (KK)'],
        'foto_ktp_ortu' => ['berkas_ktp_ortu', 'KTP Orang Tua / Wali'],
        'foto_surat_pernyataan' => ['berkas_surat_pernyataan', 'Surat Pernyataan'],
    ];

    protected $fillable = [
        'jenjang',
        'no_induk',
        'nik',
        'nisn',
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'anak_ke',
        'jumlah_saudara',
        'tinggal_bersama',
        'mulai_pengajian',
        'alamat_jalan',
        'rt_rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'kode_pos',
        'provinsi',
        'warga_negara',
        'kelompok',
        'desa',
        'daerah',
        'pendidikan_terakhir',
        'nama_sekolah_asal',
        'jurusan',
        'tahun_lulus',
        'status_mondok',
        'pj_biaya_nama',
        'pj_biaya_hp',
        'penerima_kip',
        'nomor_kip',
        'penyakit_diderita',
        'alergi',
        'gol_darah',
        'tinggi_badan',
        'berat_badan',
        'keahlian_prestasi',
        'ayah_nama',
        'ayah_status',
        'ayah_ttl',
        'ayah_pendidikan',
        'ayah_pekerjaan',
        'ayah_penghasilan',
        'ayah_agama',
        'ayah_alamat',
        'ayah_wn',
        'ayah_hp',
        'ayah_daerah',
        'ayah_desa',
        'ayah_kelompok',
        'ibu_nama',
        'ibu_status',
        'ibu_ttl',
        'ibu_pendidikan',
        'ibu_pekerjaan',
        'ibu_penghasilan',
        'ibu_agama',
        'ibu_alamat',
        'ibu_wn',
        'ibu_hp',
        'ibu_daerah',
        'ibu_desa',
        'ibu_kelompok',
        'berkas_akta',
        'berkas_kk',
        'berkas_ktp_ortu',
        'berkas_surat_pernyataan',
        'foto_akta',
        'foto_kk',
        'foto_ktp_ortu',
        'foto_surat_pernyataan',
        'status_pendaftaran',
    ];

    /**
     * URL publik foto berkas (null bila belum diunggah).
     */
    public function fotoUrl(string $field): ?string
    {
        return $this->{$field} ? asset('storage/' . $this->{$field}) : null;
    }

    protected $casts = [
        'tanggal_lahir' => 'date',
        'anak_ke' => 'integer',
        'jumlah_saudara' => 'integer',
        'tinggi_badan' => 'integer',
        'berat_badan' => 'integer',
        'berkas_akta' => 'boolean',
        'berkas_kk' => 'boolean',
        'berkas_ktp_ortu' => 'boolean',
        'berkas_surat_pernyataan' => 'boolean',
        'penerima_kip' => 'boolean',
    ];

    /**
     * Relasi ke data siswa
     */
    public function siswa()
    {
        return $this->hasOne(Siswa::class);
    }
}
