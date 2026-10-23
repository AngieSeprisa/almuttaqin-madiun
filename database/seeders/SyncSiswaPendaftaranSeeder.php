<?php

namespace Database\Seeders;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SyncSiswaPendaftaranSeeder extends Seeder
{
    public function run(): void
    {
        // Hubungkan Angie Seprisa Pamungkas jika ada
        $angiePendaftaran = Pendaftaran::where('nama_lengkap', 'like', '%angie seprisa%')->first();
        if ($angiePendaftaran) {
            Siswa::where('nama_lengkap', 'like', '%angie seprisa%')->update([
                'pendaftaran_id' => $angiePendaftaran->id,
            ]);
        }

        $siswas = Siswa::whereNull('pendaftaran_id')->get();
        $cities = ['Kota Madiun', 'Surabaya', 'Ponorogo', 'Magetan', 'Ngawi', 'Kediri', 'Nganjuk'];
        $addresses = [
            ['jalan' => 'Jl. Nogososro No. 12', 'rt' => '002/003', 'desa' => 'Josenan', 'kec' => 'Taman', 'kota' => 'Kota Madiun', 'pos' => '63134'],
            ['jalan' => 'Jl. Diponegoro No. 45', 'rt' => '001/002', 'desa' => 'Oro-oro Ombo', 'kec' => 'Kartoharjo', 'kota' => 'Kota Madiun', 'pos' => '63119'],
            ['jalan' => 'Jl. Pahlawan No. 78', 'rt' => '004/001', 'desa' => 'Pangongangan', 'kec' => 'Manguharjo', 'kota' => 'Kota Madiun', 'pos' => '63121'],
            ['jalan' => 'Jl. Trunojoyo No. 90', 'rt' => '003/005', 'desa' => 'Demangan', 'kec' => 'Taman', 'kota' => 'Kota Madiun', 'pos' => '63136'],
        ];

        $currentMaxUrut = Pendaftaran::count();

        foreach ($siswas as $idx => $s) {
            $existing = Pendaftaran::where('nama_lengkap', $s->nama_lengkap)->first();
            if ($existing) {
                $s->update(['pendaftaran_id' => $existing->id]);
                continue;
            }

            $currentMaxUrut++;
            $urut = str_pad($currentMaxUrut, 4, '0', STR_PAD_LEFT);
            $parts = explode(' ', trim($s->nama_lengkap));
            $panggilan = $parts[0] ?? 'Santri';
            $addr = $addresses[$s->id % count($addresses)];
            $city = $cities[$s->id % count($cities)];
            $thn = 2005 + ($s->id % 5);
            $bln = str_pad(($s->id % 12) + 1, 2, '0', STR_PAD_LEFT);
            $tgl = str_pad(($s->id % 28) + 1, 2, '0', STR_PAD_LEFT);
            $nik = '3519' . str_pad($s->id, 2, '0', STR_PAD_LEFT) . $tgl . $bln . str_pad($thn % 100, 2, '0', STR_PAD_LEFT) . '0001';

            $p = Pendaftaran::create([
                'no_induk' => 'PSB-2026-' . $urut,
                'nik' => $nik,
                'nama_lengkap' => $s->nama_lengkap,
                'nama_panggilan' => $panggilan,
                'tempat_lahir' => $city,
                'tanggal_lahir' => "{$thn}-{$bln}-{$tgl}",
                'jenis_kelamin' => 'Laki-laki',
                'anak_ke' => ($s->id % 3) + 1,
                'jumlah_saudara' => ($s->id % 4) + 2,
                'tinggal_bersama' => 'Orang Tua',
                'mulai_pengajian' => 'sejak kecil',
                'alamat_jalan' => $addr['jalan'],
                'rt_rw' => $addr['rt'],
                'desa_kelurahan' => $addr['desa'],
                'kecamatan' => $addr['kec'],
                'kab_kota' => $addr['kota'],
                'kode_pos' => $addr['pos'],
                'provinsi' => 'Jawa Timur',
                'warga_negara' => 'Indonesia',
                'kelompok' => 'Bina Syarekah',
                'desa' => 'Mentawai',
                'daerah' => 'Madiun Kota',
                'pendidikan_terakhir' => 'SMP/MTs',
                'jurusan' => 'Reguler',
                'tahun_lulus' => '2023',
                'status_mondok' => 'Person',
                'pj_biaya_nama' => 'Bapak ' . $panggilan,
                'pj_biaya_hp' => '0857' . rand(10000000, 99999999),
                'penyakit_diderita' => 'Tidak Ada',
                'alergi' => '-',
                'gol_darah' => ['A', 'B', 'AB', 'O'][$s->id % 4],
                'tinggi_badan' => 160 + ($s->id % 15),
                'berat_badan' => 50 + ($s->id % 20),
                'keahlian_prestasi' => 'Pencak Silat, Pidato Bahasa Arab',
                'ayah_nama' => 'Bapak ' . $panggilan,
                'ayah_status' => 'Hidup',
                'ayah_ttl' => $city . ', 15-05-1975',
                'ayah_pendidikan' => 'SMA/SMK',
                'ayah_pekerjaan' => 'Wiraswasta',
                'ayah_penghasilan' => 'Rp 3.000.000 - Rp 5.000.000',
                'ayah_agama' => 'Islam',
                'ayah_alamat' => $addr['jalan'] . ', ' . $addr['kota'],
                'ayah_wn' => 'Indonesia',
                'ayah_hp' => '0812' . rand(10000000, 99999999),
                'ayah_daerah' => 'Madiun Kota',
                'ayah_desa' => 'Mentawai',
                'ayah_kelompok' => 'Bina Syarekah',
                'ibu_nama' => 'Ibu ' . $panggilan,
                'ibu_status' => 'Hidup',
                'ibu_ttl' => $city . ', 20-08-1978',
                'ibu_pendidikan' => 'SMA/SMK',
                'ibu_pekerjaan' => 'Ibu Rumah Tangga',
                'ibu_penghasilan' => '-',
                'ibu_agama' => 'Islam',
                'ibu_alamat' => $addr['jalan'] . ', ' . $addr['kota'],
                'ibu_wn' => 'Indonesia',
                'ibu_hp' => '0813' . rand(10000000, 99999999),
                'ibu_daerah' => 'Madiun Kota',
                'ibu_desa' => 'Mentawai',
                'ibu_kelompok' => 'Bina Syarekah',
                'berkas_akta' => true,
                'berkas_kk' => true,
                'berkas_ktp_ortu' => true,
                'berkas_surat_pernyataan' => true,
                'status_pendaftaran' => 'Diterima',
            ]);

            $s->update(['pendaftaran_id' => $p->id]);
        }
    }
}
