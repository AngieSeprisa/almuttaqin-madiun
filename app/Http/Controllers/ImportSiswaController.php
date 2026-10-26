<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportSiswaController extends Controller
{
    /**
     * Definisi seluruh kolom import: field, label, tipe, wajib, keterangan, contoh.
     * Tipe: text, date, int, bool, enum:<opsi|opsi>, text_str (selalu string, mis. NIK/HP)
     */
    protected function columnDefinitions(): array
    {
        return [
            // --- Data Siswa (tabel siswas) ---
            ['field' => 'nama_lengkap', 'label' => 'Nama Lengkap', 'type' => 'text', 'required' => true, 'desc' => 'Nama lengkap santri/siswa', 'example' => 'Muhammad Fajar Ramadhan'],
            ['field' => 'kelas', 'label' => 'Ruang Kelas', 'type' => 'siswa', 'required' => false, 'desc' => 'Contoh: VII A, X A (opsional)', 'example' => 'VII A'],
            ['field' => 'kamar', 'label' => 'Kamar Santri', 'type' => 'siswa', 'required' => false, 'desc' => 'Nama kamar/asrama (default: Reguler)', 'example' => 'Abu Bakar'],
            ['field' => 'status', 'label' => 'Status', 'type' => 'siswa', 'required' => false, 'desc' => 'aktif / pulang / sakit (default: aktif)', 'example' => 'aktif'],

            // --- Identitas Pribadi ---
            ['field' => 'jenjang', 'label' => 'Jenjang', 'type' => 'jenjang', 'required' => false, 'desc' => 'SMP / SMA / Reguler (default: Reguler)', 'example' => 'SMP'],
            ['field' => 'nik', 'label' => 'NIK', 'type' => 'text_str', 'required' => false, 'desc' => 'NIK 16 digit', 'example' => '3519011205090001'],
            ['field' => 'nisn', 'label' => 'NISN', 'type' => 'text_str', 'required' => false, 'desc' => 'Nomor Induk Siswa Nasional', 'example' => '0091234567'],
            ['field' => 'nama_panggilan', 'label' => 'Nama Panggilan', 'type' => 'text', 'required' => false, 'desc' => 'Default: kata pertama nama lengkap', 'example' => 'Fajar'],
            ['field' => 'tempat_lahir', 'label' => 'Tempat Lahir', 'type' => 'text', 'required' => false, 'desc' => 'Kota/Kabupaten kelahiran', 'example' => 'Madiun'],
            ['field' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'required' => false, 'desc' => 'YYYY-MM-DD atau DD/MM/YYYY', 'example' => '2009-05-12'],
            ['field' => 'jenis_kelamin', 'label' => 'Jenis Kelamin', 'type' => 'jk', 'required' => false, 'desc' => 'Laki-laki / Perempuan', 'example' => 'Laki-laki'],
            ['field' => 'anak_ke', 'label' => 'Anak Ke', 'type' => 'int', 'required' => false, 'desc' => 'Angka', 'example' => '2'],
            ['field' => 'jumlah_saudara', 'label' => 'Jumlah Saudara', 'type' => 'int', 'required' => false, 'desc' => 'Angka', 'example' => '3'],
            ['field' => 'tinggal_bersama', 'label' => 'Tinggal Bersama', 'type' => 'text', 'required' => false, 'desc' => 'Orang tua / wali / dll', 'example' => 'Orang Tua'],
            ['field' => 'mulai_pengajian', 'label' => 'Mulai Pengajian', 'type' => 'text', 'required' => false, 'desc' => 'Tahun/tanggal mulai mengaji', 'example' => '2015'],

            // --- Alamat ---
            ['field' => 'alamat_jalan', 'label' => 'Alamat Jalan', 'type' => 'text', 'required' => false, 'desc' => 'Alamat domisili', 'example' => 'Jl. Merpati No. 12'],
            ['field' => 'rt_rw', 'label' => 'RT/RW', 'type' => 'text_str', 'required' => false, 'desc' => 'Contoh: 01/02', 'example' => '01/02'],
            ['field' => 'desa_kelurahan', 'label' => 'Desa/Kelurahan', 'type' => 'text', 'required' => false, 'desc' => 'Desa atau kelurahan', 'example' => 'Josenan'],
            ['field' => 'kecamatan', 'label' => 'Kecamatan', 'type' => 'text', 'required' => false, 'desc' => 'Kecamatan', 'example' => 'Taman'],
            ['field' => 'kab_kota', 'label' => 'Kabupaten/Kota', 'type' => 'text', 'required' => false, 'desc' => 'Kabupaten atau kota', 'example' => 'Kota Madiun'],
            ['field' => 'kode_pos', 'label' => 'Kode Pos', 'type' => 'text_str', 'required' => false, 'desc' => 'Kode pos', 'example' => '63131'],
            ['field' => 'provinsi', 'label' => 'Provinsi', 'type' => 'text', 'required' => false, 'desc' => 'Provinsi', 'example' => 'Jawa Timur'],
            ['field' => 'warga_negara', 'label' => 'Warga Negara', 'type' => 'text', 'required' => false, 'desc' => 'Default: Indonesia', 'example' => 'Indonesia'],

            // --- Alamat Pengajian ---
            ['field' => 'kelompok', 'label' => 'Kelompok', 'type' => 'text', 'required' => false, 'desc' => 'Kelompok pengajian', 'example' => 'Kelompok 1'],
            ['field' => 'desa', 'label' => 'Desa Pengajian', 'type' => 'text', 'required' => false, 'desc' => 'Desa pengajian', 'example' => 'Desa A'],
            ['field' => 'daerah', 'label' => 'Daerah Pengajian', 'type' => 'text', 'required' => false, 'desc' => 'Daerah pengajian', 'example' => 'Madiun'],

            // --- Pendidikan ---
            ['field' => 'pendidikan_terakhir', 'label' => 'Pendidikan Terakhir', 'type' => 'text', 'required' => false, 'desc' => 'SD / SMP / SMA / dll', 'example' => 'SD'],
            ['field' => 'nama_sekolah_asal', 'label' => 'Nama Sekolah Asal', 'type' => 'text', 'required' => false, 'desc' => 'Sekolah asal', 'example' => 'SDN 1 Madiun'],
            ['field' => 'jurusan', 'label' => 'Jurusan', 'type' => 'text', 'required' => false, 'desc' => 'Jurusan (jika ada)', 'example' => ''],
            ['field' => 'tahun_lulus', 'label' => 'Tahun Lulus', 'type' => 'text_str', 'required' => false, 'desc' => 'Tahun lulus', 'example' => '2023'],

            // --- Status & PJ Biaya ---
            ['field' => 'status_mondok', 'label' => 'Status Mondok', 'type' => 'text', 'required' => false, 'desc' => 'Person / Utusan/Kiriman', 'example' => 'Person'],
            ['field' => 'pj_biaya_nama', 'label' => 'PJ Biaya Nama', 'type' => 'text', 'required' => false, 'desc' => 'Penanggung jawab biaya', 'example' => 'Bambang Sutrisno'],
            ['field' => 'pj_biaya_hp', 'label' => 'PJ Biaya HP/WA', 'type' => 'text_str', 'required' => false, 'desc' => 'Nomor HP/WhatsApp PJ biaya', 'example' => '081234567890'],
            ['field' => 'penerima_kip', 'label' => 'Penerima KIP', 'type' => 'bool', 'required' => false, 'desc' => 'Ya / Tidak', 'example' => 'Tidak'],
            ['field' => 'nomor_kip', 'label' => 'Nomor KIP', 'type' => 'text_str', 'required' => false, 'desc' => 'Nomor KIP', 'example' => ''],

            // --- Kesehatan ---
            ['field' => 'penyakit_diderita', 'label' => 'Penyakit Diderita', 'type' => 'text', 'required' => false, 'desc' => 'Riwayat penyakit', 'example' => '-'],
            ['field' => 'alergi', 'label' => 'Alergi', 'type' => 'text', 'required' => false, 'desc' => 'Alergi', 'example' => '-'],
            ['field' => 'gol_darah', 'label' => 'Golongan Darah', 'type' => 'text', 'required' => false, 'desc' => 'A / B / AB / O', 'example' => 'O'],
            ['field' => 'tinggi_badan', 'label' => 'Tinggi Badan', 'type' => 'int', 'required' => false, 'desc' => 'cm', 'example' => '155'],
            ['field' => 'berat_badan', 'label' => 'Berat Badan', 'type' => 'int', 'required' => false, 'desc' => 'kg', 'example' => '45'],
            ['field' => 'keahlian_prestasi', 'label' => 'Keahlian/Prestasi', 'type' => 'text', 'required' => false, 'desc' => 'Keahlian atau prestasi', 'example' => 'Hafalan Juz 30'],

            // --- Data Ayah ---
            ['field' => 'ayah_nama', 'label' => 'Ayah Nama', 'type' => 'text', 'required' => false, 'desc' => 'Nama ayah', 'example' => 'Bambang Sutrisno'],
            ['field' => 'ayah_status', 'label' => 'Ayah Status', 'type' => 'text', 'required' => false, 'desc' => 'Hidup / Meninggal', 'example' => 'Hidup'],
            ['field' => 'ayah_ttl', 'label' => 'Ayah TTL', 'type' => 'text', 'required' => false, 'desc' => 'Tempat, tanggal lahir', 'example' => 'Madiun, 1 Januari 1975'],
            ['field' => 'ayah_pendidikan', 'label' => 'Ayah Pendidikan', 'type' => 'text', 'required' => false, 'desc' => 'Pendidikan ayah', 'example' => 'SMA'],
            ['field' => 'ayah_pekerjaan', 'label' => 'Ayah Pekerjaan', 'type' => 'text', 'required' => false, 'desc' => 'Pekerjaan ayah', 'example' => 'Wiraswasta'],
            ['field' => 'ayah_penghasilan', 'label' => 'Ayah Penghasilan', 'type' => 'text', 'required' => false, 'desc' => 'Penghasilan ayah', 'example' => '3.000.000'],
            ['field' => 'ayah_agama', 'label' => 'Ayah Agama', 'type' => 'text', 'required' => false, 'desc' => 'Default: Islam', 'example' => 'Islam'],
            ['field' => 'ayah_alamat', 'label' => 'Ayah Alamat', 'type' => 'text', 'required' => false, 'desc' => 'Alamat ayah', 'example' => 'Jl. Merpati No. 12'],
            ['field' => 'ayah_wn', 'label' => 'Ayah WN', 'type' => 'text', 'required' => false, 'desc' => 'Default: Indonesia', 'example' => 'Indonesia'],
            ['field' => 'ayah_hp', 'label' => 'Ayah HP', 'type' => 'text_str', 'required' => false, 'desc' => 'No HP ayah', 'example' => '081234567890'],
            ['field' => 'ayah_daerah', 'label' => 'Ayah Daerah', 'type' => 'text', 'required' => false, 'desc' => 'Daerah pengajian ayah', 'example' => ''],
            ['field' => 'ayah_desa', 'label' => 'Ayah Desa', 'type' => 'text', 'required' => false, 'desc' => 'Desa pengajian ayah', 'example' => ''],
            ['field' => 'ayah_kelompok', 'label' => 'Ayah Kelompok', 'type' => 'text', 'required' => false, 'desc' => 'Kelompok pengajian ayah', 'example' => ''],

            // --- Data Ibu ---
            ['field' => 'ibu_nama', 'label' => 'Ibu Nama', 'type' => 'text', 'required' => false, 'desc' => 'Nama ibu', 'example' => 'Siti Aminah'],
            ['field' => 'ibu_status', 'label' => 'Ibu Status', 'type' => 'text', 'required' => false, 'desc' => 'Hidup / Meninggal', 'example' => 'Hidup'],
            ['field' => 'ibu_ttl', 'label' => 'Ibu TTL', 'type' => 'text', 'required' => false, 'desc' => 'Tempat, tanggal lahir', 'example' => 'Madiun, 1 Februari 1980'],
            ['field' => 'ibu_pendidikan', 'label' => 'Ibu Pendidikan', 'type' => 'text', 'required' => false, 'desc' => 'Pendidikan ibu', 'example' => 'SMA'],
            ['field' => 'ibu_pekerjaan', 'label' => 'Ibu Pekerjaan', 'type' => 'text', 'required' => false, 'desc' => 'Pekerjaan ibu', 'example' => 'Ibu Rumah Tangga'],
            ['field' => 'ibu_penghasilan', 'label' => 'Ibu Penghasilan', 'type' => 'text', 'required' => false, 'desc' => 'Penghasilan ibu', 'example' => '-'],
            ['field' => 'ibu_agama', 'label' => 'Ibu Agama', 'type' => 'text', 'required' => false, 'desc' => 'Default: Islam', 'example' => 'Islam'],
            ['field' => 'ibu_alamat', 'label' => 'Ibu Alamat', 'type' => 'text', 'required' => false, 'desc' => 'Alamat ibu', 'example' => 'Jl. Merpati No. 12'],
            ['field' => 'ibu_wn', 'label' => 'Ibu WN', 'type' => 'text', 'required' => false, 'desc' => 'Default: Indonesia', 'example' => 'Indonesia'],
            ['field' => 'ibu_hp', 'label' => 'Ibu HP', 'type' => 'text_str', 'required' => false, 'desc' => 'No HP ibu', 'example' => '081234567891'],
            ['field' => 'ibu_daerah', 'label' => 'Ibu Daerah', 'type' => 'text', 'required' => false, 'desc' => 'Daerah pengajian ibu', 'example' => ''],
            ['field' => 'ibu_desa', 'label' => 'Ibu Desa', 'type' => 'text', 'required' => false, 'desc' => 'Desa pengajian ibu', 'example' => ''],
            ['field' => 'ibu_kelompok', 'label' => 'Ibu Kelompok', 'type' => 'text', 'required' => false, 'desc' => 'Kelompok pengajian ibu', 'example' => ''],

            // --- Persyaratan Berkas ---
            ['field' => 'berkas_akta', 'label' => 'Berkas Akta', 'type' => 'bool', 'required' => false, 'desc' => 'Ya / Tidak', 'example' => 'Ya'],
            ['field' => 'berkas_kk', 'label' => 'Berkas KK', 'type' => 'bool', 'required' => false, 'desc' => 'Ya / Tidak', 'example' => 'Ya'],
            ['field' => 'berkas_ktp_ortu', 'label' => 'Berkas KTP Ortu', 'type' => 'bool', 'required' => false, 'desc' => 'Ya / Tidak', 'example' => 'Ya'],
            ['field' => 'berkas_surat_pernyataan', 'label' => 'Berkas Surat Pernyataan', 'type' => 'bool', 'required' => false, 'desc' => 'Ya / Tidak', 'example' => 'Tidak'],
        ];
    }

    protected function normalizeHeader(string $name): string
    {
        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $name)));

        return trim($clean, '_');
    }

    /**
     * Sinonim nama kolom => field baku.
     */
    protected function headerAliases(): array
    {
        return [
            'nama' => 'nama_lengkap', 'nama_santri' => 'nama_lengkap', 'nama_siswa' => 'nama_lengkap', 'fullname' => 'nama_lengkap',
            'ruang_kelas' => 'kelas', 'nama_kelas' => 'kelas',
            'kamar_santri' => 'kamar', 'asrama' => 'kamar',
            'status_santri' => 'status', 'status_siswa' => 'status',
            'lembaga' => 'jenjang',
            'no_nik' => 'nik', 'nomor_nik' => 'nik', 'ktp' => 'nik',
            'jk' => 'jenis_kelamin', 'gender' => 'jenis_kelamin',
            'tgl_lahir' => 'tanggal_lahir',
            'alamat' => 'alamat_jalan', 'alamat_lengkap' => 'alamat_jalan',
            'rt_rw' => 'rt_rw', 'rt' => 'rt_rw',
            'desa_kelurahan' => 'desa_kelurahan', 'kelurahan' => 'desa_kelurahan',
            'kabupaten' => 'kab_kota', 'kota' => 'kab_kota',
            'nama_ayah' => 'ayah_nama', 'nama_ibu' => 'ibu_nama',
            'no_hp' => 'pj_biaya_hp', 'whatsapp' => 'pj_biaya_hp', 'wa' => 'pj_biaya_hp', 'kontak' => 'pj_biaya_hp', 'telepon' => 'pj_biaya_hp',
            'ayah_ttl' => 'ayah_ttl', 'ibu_ttl' => 'ibu_ttl',
            'golongan_darah' => 'gol_darah',
            'desa_pengajian' => 'desa', 'daerah_pengajian' => 'daerah',
        ];
    }

    /**
     * Tampilkan halaman formulir upload dan panduan import data siswa.
     */
    public function index()
    {
        $activeRoom = session('active_lembaga');
        $totalSiswa = Siswa::count();
        $totalPendaftaran = Pendaftaran::count();
        $columns = $this->columnDefinitions();

        return view('master.import-siswa.index', compact('activeRoom', 'totalSiswa', 'totalPendaftaran', 'columns'));
    }

    /**
     * Unduh template Excel (.xlsx) berisi seluruh kolom registrasi pendaftaran.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $columns = $this->columnDefinitions();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $sampleRows = [
            [],
            [],
        ];
        // Dua baris contoh
        foreach ($columns as $col) {
            $sampleRows[0][$col['field']] = $col['example'];
        }
        $sampleRows[1] = array_merge($sampleRows[0], [
            'nama_lengkap' => 'Nur Aisyah Rahma', 'jenjang' => 'SMA', 'kelas' => 'X A', 'kamar' => 'Fathimah',
            'nik' => '3519014510080002', 'nisn' => '0087654321', 'nama_panggilan' => 'Aisyah', 'jenis_kelamin' => 'Perempuan',
            'tempat_lahir' => 'Magetan', 'tanggal_lahir' => '2008-10-15', 'ayah_nama' => 'Hendra Wijaya', 'ibu_nama' => 'Dewi Kartika',
            'pj_biaya_hp' => '085678901234',
        ]);

        foreach ($columns as $i => $col) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($letter.'1', $col['field']);
            $sheet->getColumnDimension($letter)->setAutoSize(true);

            // Kolom teks: paksa format teks agar angka depan 0 / NIK tidak rusak
            if (in_array($col['type'], ['text_str'])) {
                $sheet->getStyle($letter.'2:'.$letter.'1000')
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            }

            foreach ($sampleRows as $r => $row) {
                $value = (string) ($row[$col['field']] ?? '');
                if ($value !== '') {
                    $sheet->setCellValueExplicit($letter.($r + 2), $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
        }

        $lastLetter = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle("A1:{$lastLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0052FF']],
        ]);
        $sheet->freezePane('B2');

        // Sheet panduan
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Panduan');
        $guide->fromArray(['Kolom', 'Wajib', 'Keterangan', 'Contoh'], null, 'A1');
        $guide->getStyle('A1:D1')->getFont()->setBold(true);
        foreach ($columns as $i => $col) {
            $guide->fromArray([$col['field'], $col['required'] ? 'Ya' : 'Tidak', $col['desc'], (string) $col['example']], null, 'A'.($i + 2));
        }
        foreach (['A', 'B', 'C', 'D'] as $l) {
            $guide->getColumnDimension($l)->setAutoSize(true);
        }
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'template_import_siswa_almuttaqin.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Konversi nilai sel mentah menjadi string bersih.
     */
    protected function cellToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }
        if (is_float($value) || is_int($value)) {
            return (floor((float) $value) == (float) $value) ? sprintf('%.0f', $value) : (string) $value;
        }

        return trim((string) $value);
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        $raw = trim((string) $value);
        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        $ts = strtotime($raw);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * Memproses file Excel upload untuk import data siswa.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file_siswa' => 'required|file|mimes:xlsx,xls|max:10240',
            'update_existing' => 'nullable|boolean',
        ], [
            'file_siswa.required' => 'Silakan pilih file Excel data siswa yang akan diimport.',
            'file_siswa.file' => 'File yang diunggah tidak valid.',
            'file_siswa.mimes' => 'Format file harus Excel (.xlsx atau .xls).',
            'file_siswa.max' => 'Ukuran file maksimal adalah 10MB.',
        ]);

        $file = $request->file('file_siswa');
        $updateExisting = $request->boolean('update_existing', true);

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $rows = $spreadsheet->getSheet(0)->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return back()->with('error', 'File Excel tidak dapat dibaca: '.$e->getMessage());
        }

        if (count($rows) < 2) {
            return back()->with('error', 'File tidak memiliki baris data.');
        }

        // Peta header
        $validFields = array_column($this->columnDefinitions(), 'field');
        $labelMap = [];
        foreach ($this->columnDefinitions() as $c) {
            $labelMap[$this->normalizeHeader($c['label'])] = $c['field'];
        }
        $aliases = $this->headerAliases();

        $headerMap = [];
        foreach ($rows[0] as $idx => $colName) {
            $clean = $this->normalizeHeader((string) $colName);
            if ($clean === '') {
                continue;
            }
            $field = in_array($clean, $validFields, true) ? $clean : ($labelMap[$clean] ?? ($aliases[$clean] ?? null));
            if ($field && ! isset($headerMap[$field])) {
                $headerMap[$field] = $idx;
            }
        }

        if (! isset($headerMap['nama_lengkap'])) {
            return back()->with('error', 'Kolom "nama_lengkap" tidak ditemukan pada baris header. Gunakan template yang disediakan.');
        }

        $types = array_column($this->columnDefinitions(), 'type', 'field');
        $siswaFields = ['nama_lengkap', 'kelas', 'kamar', 'status'];

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $rowNumber = 1;

        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNumber = $i + 1;

                // Ambil & normalisasi semua nilai kolom
                $data = [];
                foreach ($headerMap as $field => $idx) {
                    $raw = $row[$idx] ?? null;
                    $type = $types[$field] ?? 'text';
                    $str = $this->cellToString($raw);

                    switch ($type) {
                        case 'date':
                            $data[$field] = $this->parseDate($raw);
                            break;
                        case 'int':
                            $data[$field] = ($str !== '' && is_numeric($str)) ? (int) $str : null;
                            break;
                        case 'bool':
                            $data[$field] = $str === '' ? null : in_array(strtolower($str), ['ya', 'y', '1', 'true', 'yes', 'ada', 'lengkap', 'v', 'x'], true);
                            break;
                        case 'jk':
                            $data[$field] = null;
                            if ($str !== '') {
                                $data[$field] = (stripos($str, 'p') === 0 || stripos($str, 'perempuan') !== false) ? 'Perempuan'
                                    : ((stripos($str, 'l') === 0 || stripos($str, 'laki') !== false) ? 'Laki-laki' : null);
                            }
                            break;
                        default:
                            $data[$field] = ($str === '' || $str === '-' && in_array($field, ['nik', 'nisn'])) ? null : $str;
                    }
                }

                $nama = $data['nama_lengkap'] ?? null;
                if (! $nama) {
                    $skippedCount++;

                    continue;
                }

                // Normalisasi khusus
                $jenjangRaw = strtoupper($data['jenjang'] ?? '');
                $data['jenjang'] = in_array($jenjangRaw, ['SMP', 'SMA']) ? $jenjangRaw : 'Reguler';

                $statusRaw = strtolower($data['status'] ?? '');
                $status = in_array($statusRaw, ['aktif', 'pulang', 'sakit']) ? $statusRaw : 'aktif';
                $kelas = $data['kelas'] ?? null;
                $kamar = $data['kamar'] ?? null;

                if (! empty($data['nik'])) {
                    $data['nik'] = substr(preg_replace('/\s+/', '', $data['nik']), 0, 16);
                }
                if (! empty($data['nisn'])) {
                    $data['nisn'] = substr($data['nisn'], 0, 20);
                }

                // Data pendaftaran = semua field selain milik tabel siswa
                $pendaftaranData = array_diff_key($data, array_flip(array_diff($siswaFields, ['nama_lengkap'])));
                // buang nilai null agar tidak menimpa data lama
                $pendaftaranData = array_filter($pendaftaranData, fn ($v) => $v !== null);

                $siswa = Siswa::where('nama_lengkap', $nama)->first();

                if ($siswa && ! $updateExisting) {
                    $skippedCount++;

                    continue;
                }

                $pendaftaran = ($siswa && $siswa->pendaftaran_id) ? Pendaftaran::find($siswa->pendaftaran_id) : null;

                // Hindari bentrok NIK unik
                if (! empty($pendaftaranData['nik'])) {
                    $nikOwner = Pendaftaran::where('nik', $pendaftaranData['nik'])
                        ->when($pendaftaran, fn ($q) => $q->where('id', '!=', $pendaftaran->id))
                        ->exists();
                    if ($nikOwner) {
                        unset($pendaftaranData['nik']);
                    }
                }

                if ($pendaftaran) {
                    if (! empty($pendaftaranData)) {
                        $pendaftaran->update($pendaftaranData);
                    }
                } else {
                    $tahun = date('Y');
                    $urut = str_pad(Pendaftaran::whereYear('created_at', $tahun)->count() + 1, 4, '0', STR_PAD_LEFT);
                    $pendaftaranData['no_induk'] = "PSB-{$tahun}-{$urut}";
                    $pendaftaranData['nama_panggilan'] = $pendaftaranData['nama_panggilan'] ?? (explode(' ', $nama)[0] ?? 'Santri');
                    $pendaftaranData['status_pendaftaran'] = 'Diterima';
                    $pendaftaran = Pendaftaran::create($pendaftaranData);
                }

                if ($siswa) {
                    $siswa->update([
                        'jenjang' => $data['jenjang'],
                        'kelas' => $kelas ?? $siswa->kelas,
                        'kamar' => $kamar ?? $siswa->kamar,
                        'status' => isset($headerMap['status']) ? $status : $siswa->status,
                        'pendaftaran_id' => $pendaftaran->id,
                    ]);
                    $updatedCount++;
                } else {
                    Siswa::create([
                        'nama_lengkap' => $nama,
                        'jenjang' => $data['jenjang'],
                        'kelas' => $kelas,
                        'kamar' => $kamar ?? 'Reguler',
                        'status' => $status,
                        'pendaftaran_id' => $pendaftaran->id,
                    ]);
                    $insertedCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Terjadi kesalahan saat memproses data import baris '.$rowNumber.': '.$e->getMessage());
        }

        $msg = "Import selesai! Berhasil menambahkan {$insertedCount} siswa baru";
        $msg .= $updatedCount > 0 ? " dan memperbarui {$updatedCount} siswa." : '.';
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} baris dilewati karena sudah ada atau kosong).";
        }

        return redirect()->route('siswa')->with('success', $msg);
    }
}
