<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PendaftaranRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $routeParam = $this->route('pendaftaran') ?? $this->route('id');
        $id = $routeParam instanceof \App\Models\Pendaftaran ? $routeParam->id : $routeParam;

        return [
            // Pilihan Jenjang Pendidikan
            'jenjang' => 'nullable|in:SMP,SMA,Reguler',

            // Identitas Pribadi
            'no_induk' => 'nullable|string|max:50',
            'nik' => 'nullable|string|max:16' . ($this->filled('nik') ? '|unique:pendaftarans,nik' . ($id ? ",$id" : '') : ''),
            'nisn' => 'nullable|string|max:20',
            'nama_lengkap' => 'nullable|string|max:255',
            'nama_panggilan' => 'nullable|string|max:100',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'anak_ke' => 'nullable|integer|min:1',
            'jumlah_saudara' => 'nullable|integer|min:0',
            'tinggal_bersama' => 'nullable|string|max:100',
            'mulai_pengajian' => 'nullable|string|max:100',

            // Alamat Lengkap
            'alamat_jalan' => 'nullable|string|max:255',
            'rt_rw' => 'nullable|string|max:50',
            'desa_kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kab_kota' => 'nullable|string|max:100',
            'kode_pos' => 'nullable|string|max:10',
            'provinsi' => 'nullable|string|max:100',
            'warga_negara' => 'nullable|string|max:100',

            // Alamat Pengajian
            'kelompok' => 'nullable|string|max:100',
            'desa' => 'nullable|string|max:100',
            'daerah' => 'nullable|string|max:100',

            // Pendidikan Terakhir & Sekolah Asal
            'pendidikan_terakhir' => 'nullable|string|max:100',
            'nama_sekolah_asal' => 'nullable|string|max:255',
            'jurusan' => 'nullable|string|max:100',
            'tahun_lulus' => 'nullable|string|max:10',

            // Status & PJ Biaya
            'status_mondok' => 'nullable|in:Person,Utusan/Kiriman',
            'pj_biaya_nama' => 'nullable|string|max:255',
            'pj_biaya_hp' => 'nullable|string|max:30',

            // Kesejahteraan & KIP / PIP
            'penerima_kip' => 'nullable|boolean',
            'nomor_kip' => 'nullable|string|max:50',

            // Riwayat Kesehatan
            'penyakit_diderita' => 'nullable|string|max:255',
            'alergi' => 'nullable|string|max:255',
            'gol_darah' => 'nullable|string|max:20',
            'tinggi_badan' => 'nullable|integer|min:30|max:250',
            'berat_badan' => 'nullable|integer|min:10|max:300',
            'keahlian_prestasi' => 'nullable|string',

            // Data Ayah
            'ayah_nama' => 'nullable|string|max:255',
            'ayah_status' => 'nullable|in:Hidup,Meninggal',
            'ayah_ttl' => 'nullable|string|max:100',
            'ayah_pendidikan' => 'nullable|string|max:100',
            'ayah_pekerjaan' => 'nullable|string|max:100',
            'ayah_penghasilan' => 'nullable|string|max:100',
            'ayah_agama' => 'nullable|string|max:50',
            'ayah_alamat' => 'nullable|string',
            'ayah_wn' => 'nullable|string|max:100',
            'ayah_hp' => 'nullable|string|max:30',
            'ayah_daerah' => 'nullable|string|max:100',
            'ayah_desa' => 'nullable|string|max:100',
            'ayah_kelompok' => 'nullable|string|max:100',

            // Data Ibu
            'ibu_nama' => 'nullable|string|max:255',
            'ibu_status' => 'nullable|in:Hidup,Meninggal',
            'ibu_ttl' => 'nullable|string|max:100',
            'ibu_pendidikan' => 'nullable|string|max:100',
            'ibu_pekerjaan' => 'nullable|string|max:100',
            'ibu_penghasilan' => 'nullable|string|max:100',
            'ibu_agama' => 'nullable|string|max:50',
            'ibu_alamat' => 'nullable|string',
            'ibu_wn' => 'nullable|string|max:100',
            'ibu_hp' => 'nullable|string|max:30',
            'ibu_daerah' => 'nullable|string|max:100',
            'ibu_desa' => 'nullable|string|max:100',
            'ibu_kelompok' => 'nullable|string|max:100',

            // Berkas
            'berkas_akta' => 'nullable|boolean',
            'berkas_kk' => 'nullable|boolean',
            'berkas_ktp_ortu' => 'nullable|boolean',
            'berkas_surat_pernyataan' => 'nullable|boolean',

            // Foto berkas (dikompresi di browser & server sebelum disimpan)
            'foto_akta' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_kk' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_ktp_ortu' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_surat_pernyataan' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ];
    }

    /**
     * Custom validation attributes.
     */
    public function attributes(): array
    {
        return [
            'jenjang' => 'Jenjang Pendidikan',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'nisn' => 'NISN (Nomor Induk Siswa Nasional)',
            'nama_lengkap' => 'Nama Lengkap',
            'nama_panggilan' => 'Nama Panggilan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'anak_ke' => 'Anak ke-',
            'jumlah_saudara' => 'Jumlah Saudara',
            'tinggal_bersama' => 'Tinggal Bersama',
            'alamat_jalan' => 'Alamat Jalan',
            'rt_rw' => 'RT / RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kab_kota' => 'Kabupaten / Kota',
            'kode_pos' => 'Kode Pos',
            'provinsi' => 'Provinsi',
            'kelompok' => 'Kelompok Pengajian',
            'desa' => 'Desa Pengajian',
            'daerah' => 'Daerah Pengajian',
            'pendidikan_terakhir' => 'Pendidikan Terakhir',
            'nama_sekolah_asal' => 'Nama Sekolah Asal',
            'status_mondok' => 'Status Mondok',
            'pj_biaya_nama' => 'Nama Penanggung Jawab Biaya',
            'pj_biaya_hp' => 'No. HP Penanggung Jawab',
            'penerima_kip' => 'Status Penerima KIP / PIP',
            'nomor_kip' => 'Nomor KIP / KKS / PKH',
            'gol_darah' => 'Golongan Darah',
            'ayah_nama' => 'Nama Lengkap Ayah',
            'ayah_status' => 'Status Ayah',
            'ibu_nama' => 'Nama Lengkap Ibu',
            'ibu_status' => 'Status Ibu',
            'foto_akta' => 'Foto Akta Kelahiran',
            'foto_kk' => 'Foto Kartu Keluarga',
            'foto_ktp_ortu' => 'Foto KTP Orang Tua / Wali',
            'foto_surat_pernyataan' => 'Foto Surat Pernyataan',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        $messages = [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'size' => ':attribute harus berisi :size karakter.',
            'unique' => ':attribute sudah terdaftar di sistem.',
            'integer' => ':attribute harus berupa angka bulat.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'in' => 'Pilihan pada :attribute tidak valid.',
        ];

        foreach (array_keys(\App\Models\Pendaftaran::FOTO_BERKAS) as $field) {
            $messages["{$field}.max"] = ':attribute terlalu besar (maksimal 10 MB).';
            $messages["{$field}.uploaded"] = ':attribute gagal diunggah. Ukuran file melebihi batas server, coba foto yang lebih kecil.';
            $messages["{$field}.image"] = ':attribute harus berupa gambar.';
            $messages["{$field}.mimes"] = ':attribute harus berformat JPG, PNG, atau WEBP.';
            $messages["{$field}.file"] = ':attribute harus berupa file.';
        }

        return $messages;
    }

    /**
     * Prepare data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'penerima_kip' => $this->boolean('penerima_kip'),
            'berkas_akta' => $this->boolean('berkas_akta'),
            'berkas_kk' => $this->boolean('berkas_kk'),
            'berkas_ktp_ortu' => $this->boolean('berkas_ktp_ortu'),
            'berkas_surat_pernyataan' => $this->boolean('berkas_surat_pernyataan'),
        ]);
    }
}
