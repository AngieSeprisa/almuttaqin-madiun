<?php

namespace Database\Seeders;

use App\Models\Pendaftaran;
use Illuminate\Database\Seeder;

class PendaftaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Pendaftaran::firstOrCreate(
            ['nik' => '3578011205050001'],
            [
                'no_induk' => 'PSB-2026-0001',
                'nama_lengkap' => 'Muhammad Fatih Al-Ayyubi',
                'nama_panggilan' => 'Fatih',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '2008-05-12',
                'jenis_kelamin' => 'Laki-laki',
                'anak_ke' => 2,
                'jumlah_saudara' => 3,
                'tinggal_bersama' => 'Orang Tua',
                'mulai_pengajian' => '2018',
                'alamat_jalan' => 'Jl. Ketintang Baru No. 15',
                'rt_rw' => '003/004',
                'desa_kelurahan' => 'Ketintang',
                'kecamatan' => 'Gayungan',
                'kab_kota' => 'Surabaya',
                'kode_pos' => '60231',
                'provinsi' => 'Jawa Timur',
                'warga_negara' => 'Indonesia',
                'kelompok' => 'Ketintang Selatan',
                'desa' => 'Gayungan',
                'daerah' => 'Surabaya Selatan',
                'pendidikan_terakhir' => 'SMP/MTs',
                'jurusan' => 'Reguler',
                'tahun_lulus' => '2024',
                'status_mondok' => 'Person',
                'pj_biaya_nama' => 'Ahmad Fauzan',
                'pj_biaya_hp' => '081234567890',
                'penyakit_diderita' => 'Tidak Ada',
                'alergi' => 'Tidak Ada',
                'gol_darah' => 'O',
                'tinggi_badan' => 168,
                'berat_badan' => 56,
                'keahlian_prestasi' => 'Tahfidz Al-Quran 3 Juz, Juara Pidato Bahasa Arab',
                'ayah_nama' => 'Ahmad Fauzan',
                'ayah_status' => 'Hidup',
                'ayah_ttl' => 'Surabaya, 15-04-1976',
                'ayah_pendidikan' => 'S1',
                'ayah_pekerjaan' => 'Wiraswasta',
                'ayah_penghasilan' => 'Rp 5.000.000 - Rp 10.000.000',
                'ayah_agama' => 'Islam',
                'ayah_alamat' => 'Jl. Ketintang Baru No. 15, Surabaya',
                'ayah_wn' => 'Indonesia',
                'ayah_hp' => '081234567890',
                'ayah_daerah' => 'Surabaya Selatan',
                'ayah_desa' => 'Gayungan',
                'ayah_kelompok' => 'Ketintang Selatan',
                'ibu_nama' => 'Siti Aminah',
                'ibu_status' => 'Hidup',
                'ibu_ttl' => 'Sidoarjo, 20-08-1979',
                'ibu_pendidikan' => 'SMA',
                'ibu_pekerjaan' => 'Ibu Rumah Tangga',
                'ibu_penghasilan' => '-',
                'ibu_agama' => 'Islam',
                'ibu_alamat' => 'Jl. Ketintang Baru No. 15, Surabaya',
                'ibu_wn' => 'Indonesia',
                'ibu_hp' => '081298765432',
                'ibu_daerah' => 'Surabaya Selatan',
                'ibu_desa' => 'Gayungan',
                'ibu_kelompok' => 'Ketintang Selatan',
                'berkas_akta' => true,
                'berkas_kk' => true,
                'berkas_ktp_ortu' => true,
                'berkas_surat_pernyataan' => true,
                'status_pendaftaran' => 'Pending',
            ]
        );
    }
}
