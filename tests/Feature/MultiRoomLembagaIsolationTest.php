<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Ekstrakurikuler;
use App\Models\Jurnal;
use App\Models\JurnalAbsensi;
use App\Models\JurnalEkskul;
use App\Models\Lembaga;
use App\Models\MasterDevice;
use App\Models\MasterSesi;
use App\Models\MataPelajaran;
use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Models\PerizinanHaid;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\PerpustakaanBuku;
use App\Models\PerpustakaanPeminjaman;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\TimeSlot;
use App\Models\Ujian;
use App\Models\User;
use App\Models\UserPermission;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiRoomLembagaIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Lembaga $yayasan;
    protected Lembaga $pondok;
    protected Lembaga $smp;
    protected Lembaga $sma;
    protected Lembaga $sdit;
    protected Lembaga $ra;

    protected User $yayasanUser;
    protected User $guruSdit;
    protected User $guruRa;
    protected User $guruSmp;
    protected User $guruSmpPending;

    protected Siswa $siswaSmp;
    protected Siswa $siswaSdit;
    protected Siswa $siswaRa;
    protected Siswa $siswaReguler;
    protected Siswa $siswaLulusSmp;
    protected Siswa $siswaLulusSdit;
    protected Siswa $siswaMutasiSmp;
    protected Siswa $siswaMutasiSdit;

    protected Pendaftaran $pendaftaranSmp;
    protected Pendaftaran $pendaftaranSdit;
    protected Pendaftaran $pendaftaranRa;

    protected PenerimaanSantri $eventSmp;
    protected PenerimaanSantri $eventSdit;
    protected PenerimaanSantri $eventRa;

    protected Classroom $kelasSmp;
    protected Classroom $kelasSdit;
    protected Classroom $kelasRa;

    protected MataPelajaran $mapelSmp;
    protected MataPelajaran $mapelSdit;
    protected MataPelajaran $mapelRa;

    protected Ekstrakurikuler $ekskulSmp;
    protected Ekstrakurikuler $ekskulSdit;
    protected JurnalEkskul $jurnalEkskulSmp;
    protected JurnalEkskul $jurnalEkskulSdit;

    protected Jurnal $jurnalSmp;
    protected Jurnal $jurnalSdit;

    protected Ujian $ujianSmp;
    protected Ujian $ujianSdit;

    protected PerizinanSambang $sambangSmp;
    protected PerizinanSambang $sambangSdit;
    protected PerizinanSakit $sakitSmp;
    protected PerizinanSakit $sakitSdit;
    protected PerizinanHaid $haidSmp;
    protected PerizinanHaid $haidSdit;

    protected PerpustakaanPeminjaman $pinjamSmp;
    protected PerpustakaanPeminjaman $pinjamSdit;

    protected PresensiGuru $presensiGuruSmp;
    protected PresensiGuru $presensiGuruSdit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yayasan = Lembaga::create([
            'kode' => 'yayasan', 'nama' => 'Yayasan Pondok Pesantren Muttaqin', 'singkatan' => 'Yayasan',
            'kategori' => 'yayasan', 'is_active' => true, 'urutan' => 1,
        ]);
        $this->pondok = Lembaga::create([
            'kode' => 'pondok', 'nama' => 'Pondok Pesantren Al Muttaqin', 'singkatan' => 'Pondok',
            'kategori' => 'pesantren', 'is_active' => true, 'urutan' => 2,
        ]);
        $this->smp = Lembaga::create([
            'kode' => 'smp', 'nama' => 'SMP Al Muttaqin', 'singkatan' => 'SMP',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 3,
        ]);
        $this->sma = Lembaga::create([
            'kode' => 'sma', 'nama' => 'SMA Al Muttaqin', 'singkatan' => 'SMA',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 4,
        ]);
        $this->sdit = Lembaga::create([
            'kode' => 'sdit', 'nama' => 'SD IT AL MUTTAQIN', 'singkatan' => 'SD IT',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 5,
        ]);
        $this->ra = Lembaga::create([
            'kode' => 'ra', 'nama' => 'RA AL MUTTAQIN', 'singkatan' => 'RA',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 6,
        ]);

        $pwd = Hash::make('password');

        $this->yayasanUser = User::create([
            'name' => 'Ketua Yayasan', 'email' => 'yayasan.iso@almuttaqin.id', 'password' => $pwd,
            'role' => 'yayasan', 'primary_lembaga_id' => $this->yayasan->id, 'is_active' => true,
        ]);
        $this->yayasanUser->lembagas()->sync([
            $this->yayasan->id, $this->pondok->id, $this->smp->id, $this->sma->id, $this->sdit->id, $this->ra->id,
        ]);

        $this->guruSdit = User::create([
            'name' => 'Ustadzah Fatimah SDIT', 'email' => 'guru.sdit@almuttaqin.id', 'password' => $pwd,
            'role' => 'guru', 'jabatan' => 'Guru Kelas SD IT', 'nip' => 'SDIT-001',
            'primary_lembaga_id' => $this->sdit->id, 'is_active' => true,
            'is_guru_sekolah' => true, 'mata_pelajaran' => 'Tematik SD IT', 'mapel_status' => 'disetujui',
        ]);
        $this->guruSdit->lembagas()->sync([$this->sdit->id]);
        $this->grantAllPermissions($this->guruSdit);

        $this->guruRa = User::create([
            'name' => 'Bunda Aisyah RA', 'email' => 'guru.ra@almuttaqin.id', 'password' => $pwd,
            'role' => 'guru', 'jabatan' => 'Guru RA', 'nip' => 'RA-001',
            'primary_lembaga_id' => $this->ra->id, 'is_active' => true,
            'is_guru_sekolah' => true, 'mata_pelajaran' => 'Calistung RA', 'mapel_status' => 'disetujui',
        ]);
        $this->guruRa->lembagas()->sync([$this->ra->id]);
        $this->grantAllPermissions($this->guruRa);

        $this->guruSmp = User::create([
            'name' => 'Pak Budi SMP', 'email' => 'guru.smp@almuttaqin.id', 'password' => $pwd,
            'role' => 'guru', 'jabatan' => 'Guru SMP', 'nip' => 'SMP-001',
            'primary_lembaga_id' => $this->smp->id, 'is_active' => true,
            'is_guru_sekolah' => true, 'mata_pelajaran' => 'Matematika SMP', 'mapel_status' => 'disetujui',
        ]);
        $this->guruSmp->lembagas()->sync([$this->smp->id]);

        $this->guruSmpPending = User::create([
            'name' => 'Pak Pending SMP', 'email' => 'pending.smp@almuttaqin.id', 'password' => $pwd,
            'role' => 'guru', 'jabatan' => 'Guru SMP',
            'primary_lembaga_id' => $this->smp->id, 'is_active' => true,
            'is_guru_sekolah' => true, 'mapel_diajukan' => 'IPA SMP', 'mapel_status' => 'menunggu',
        ]);
        $this->guruSmpPending->lembagas()->sync([$this->smp->id]);

        // Siswa aktif per lembaga
        $this->siswaSmp = Siswa::create([
            'nama_lengkap' => 'Siswa SMP Alpha', 'jenjang' => 'SMP', 'kelas' => 'VII A',
            'kamar' => 'Kamar Putra 1', 'jenis_kelamin' => 'Perempuan', 'status' => 'aktif',
        ]);
        $this->siswaSdit = Siswa::create([
            'nama_lengkap' => 'Siswa SDIT Beta', 'jenjang' => 'SD IT', 'kelas' => '1 A',
            'kamar' => 'Kamar Putri 1', 'jenis_kelamin' => 'Perempuan', 'status' => 'aktif',
        ]);
        $this->siswaRa = Siswa::create([
            'nama_lengkap' => 'Siswa RA Gamma', 'jenjang' => 'RA', 'kelas' => 'RA A',
            'jenis_kelamin' => 'Laki-laki', 'status' => 'aktif',
        ]);
        $this->siswaReguler = Siswa::create([
            'nama_lengkap' => 'Siswa Reguler Delta', 'jenjang' => 'Reguler',
            'kamar' => 'Kamar Putra 2', 'jenis_kelamin' => 'Laki-laki', 'status' => 'aktif',
        ]);

        // Siswa Lulus & Mutasi
        $this->siswaLulusSmp = Siswa::create([
            'nama_lengkap' => 'Alumni Lulus SMP', 'jenjang' => 'SMP', 'kelas' => 'IX A',
            'status' => 'lulus', 'kategori_lulus' => 'Sekolah', 'tahun_lulus' => '2026', 'tanggal_lulus' => '2026-06-01',
        ]);
        $this->siswaLulusSdit = Siswa::create([
            'nama_lengkap' => 'Alumni Lulus SDIT', 'jenjang' => 'SD IT', 'kelas' => '6 A',
            'status' => 'lulus', 'kategori_lulus' => 'Sekolah', 'tahun_lulus' => '2026', 'tanggal_lulus' => '2026-06-01',
        ]);
        $this->siswaMutasiSmp = Siswa::create([
            'nama_lengkap' => 'Siswa Mutasi SMP', 'jenjang' => 'SMP', 'kelas' => 'VIII A',
            'status' => 'mutasi', 'tanggal_mutasi' => '2026-07-01', 'tujuan_mutasi' => 'SMP Negeri',
        ]);
        $this->siswaMutasiSdit = Siswa::create([
            'nama_lengkap' => 'Siswa Mutasi SDIT', 'jenjang' => 'SD IT', 'kelas' => '2 A',
            'status' => 'mutasi', 'tanggal_mutasi' => '2026-07-01', 'tujuan_mutasi' => 'SD Negeri',
        ]);

        // Event Penerimaan & Pendaftaran
        $this->eventSmp = PenerimaanSantri::create([
            'judul' => 'SPMB Khusus SMP', 'slug' => 'spmb-khusus-smp', 'tahun_ajaran' => '2026/2027',
            'jenjang' => 'SMP', 'jenjangs' => ['SMP'], 'is_active' => true, 'created_by' => $this->yayasanUser->id,
        ]);
        $this->eventSdit = PenerimaanSantri::create([
            'judul' => 'SPMB Khusus SD IT', 'slug' => 'spmb-khusus-sdit', 'tahun_ajaran' => '2026/2027',
            'jenjang' => 'SD IT', 'jenjangs' => ['SD IT'], 'is_active' => true, 'created_by' => $this->yayasanUser->id,
        ]);
        $this->eventRa = PenerimaanSantri::create([
            'judul' => 'SPMB Khusus RA', 'slug' => 'spmb-khusus-ra', 'tahun_ajaran' => '2026/2027',
            'jenjang' => 'RA', 'jenjangs' => ['RA'], 'is_active' => true, 'created_by' => $this->yayasanUser->id,
        ]);

        $this->pendaftaranSmp = Pendaftaran::create([
            'penerimaan_santri_id' => $this->eventSmp->id, 'jenjang' => 'SMP', 'no_induk' => 'SMP-9001',
            'nama_lengkap' => 'Calon Santri SMP', 'status' => 'Pending',
        ]);
        $this->pendaftaranSdit = Pendaftaran::create([
            'penerimaan_santri_id' => $this->eventSdit->id, 'jenjang' => 'SD IT', 'no_induk' => 'SDIT-9001',
            'nama_lengkap' => 'Calon Santri SDIT', 'status' => 'Pending',
        ]);
        $this->pendaftaranRa = Pendaftaran::create([
            'penerimaan_santri_id' => $this->eventRa->id, 'jenjang' => 'RA', 'no_induk' => 'RA-9001',
            'nama_lengkap' => 'Calon Santri RA', 'status' => 'Pending',
        ]);

        // Classroom, Mapel, TimeSlot, Schedule
        $this->kelasSmp = Classroom::create([
            'lembaga_id' => $this->smp->id, 'jenjang' => 'SMP', 'nama_kelas' => 'VII A', 'tingkat' => 7, 'urutan' => 1, 'is_active' => true,
        ]);
        $this->kelasSdit = Classroom::create([
            'lembaga_id' => $this->sdit->id, 'jenjang' => 'SD IT', 'nama_kelas' => '1 A', 'tingkat' => 1, 'urutan' => 1, 'is_active' => true,
        ]);
        $this->kelasRa = Classroom::create([
            'lembaga_id' => $this->ra->id, 'jenjang' => 'RA', 'nama_kelas' => 'RA A', 'tingkat' => 1, 'urutan' => 1, 'is_active' => true,
        ]);

        $this->mapelSmp = MataPelajaran::create([
            'lembaga_id' => $this->smp->id, 'kode' => 'SMP-MTK', 'mata_pelajaran' => 'Matematika SMP',
        ]);
        $this->mapelSdit = MataPelajaran::create([
            'lembaga_id' => $this->sdit->id, 'kode' => 'SDIT-TMK', 'mata_pelajaran' => 'Tematik SD IT',
        ]);
        $this->mapelRa = MataPelajaran::create([
            'lembaga_id' => $this->ra->id, 'kode' => 'RA-CAL', 'mata_pelajaran' => 'Calistung RA',
        ]);

        $slotSmp1 = TimeSlot::create([
            'hari' => 'Senin', 'jenjang' => 'SMP', 'jam_ke' => 1, 'waktu_mulai' => '07:00', 'waktu_selesai' => '07:45',
            'tipe_kegiatan' => 'KBM', 'label' => 'Jam 1 Khusus SMP', 'urutan' => 1, 'is_break' => false, 'is_active' => true,
        ]);
        $slotSdit1 = TimeSlot::create([
            'hari' => 'Senin', 'jenjang' => 'SD IT', 'jam_ke' => 1, 'waktu_mulai' => '07:15', 'waktu_selesai' => '07:50',
            'tipe_kegiatan' => 'KBM', 'label' => 'Jam 1 Khusus SDIT', 'urutan' => 1, 'is_break' => false, 'is_active' => true,
        ]);
        TimeSlot::create([
            'hari' => 'Selasa', 'jenjang' => 'SD IT', 'jam_ke' => 2, 'waktu_mulai' => '08:00', 'waktu_selesai' => '08:35',
            'tipe_kegiatan' => 'KBM', 'label' => 'Jam 2 Selasa SDIT', 'urutan' => 2, 'is_break' => false, 'is_active' => true,
        ]);
        TimeSlot::create([
            'hari' => 'Selasa', 'jenjang' => 'SMP', 'jam_ke' => 2, 'waktu_mulai' => '08:00', 'waktu_selesai' => '08:45',
            'tipe_kegiatan' => 'KBM', 'label' => 'Jam 2 Selasa SMP', 'urutan' => 2, 'is_break' => false, 'is_active' => true,
        ]);
        TimeSlot::create([
            'hari' => 'Senin', 'jenjang' => 'RA', 'jam_ke' => 1, 'waktu_mulai' => '07:30', 'waktu_selesai' => '08:00',
            'tipe_kegiatan' => 'KBM', 'label' => 'Jam 1 Khusus RA', 'urutan' => 1, 'is_break' => false, 'is_active' => true,
        ]);

        Schedule::create([
            'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal', 'time_slot_id' => $slotSmp1->id,
            'classroom_id' => $this->kelasSmp->id, 'subject_id' => $this->mapelSmp->id, 'teacher_id' => $this->guruSmp->id, 'is_locked' => false,
        ]);
        Schedule::create([
            'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal', 'time_slot_id' => $slotSdit1->id,
            'classroom_id' => $this->kelasSdit->id, 'subject_id' => $this->mapelSdit->id, 'teacher_id' => $this->guruSdit->id, 'is_locked' => false,
        ]);

        // Jurnal & Absensi Sekolah
        $today = Carbon::today()->toDateString();
        $this->jurnalSmp = Jurnal::create([
            'lembaga_id' => $this->smp->id, 'guru_id' => $this->guruSmp->id, 'nama_guru' => $this->guruSmp->name,
            'kelas' => 'VII A', 'semester' => 'Gasal', 'tahun_pelajaran' => '2026/2027', 'hari' => 'Senin',
            'tanggal' => $today, 'mata_pelajaran' => 'Matematika SMP', 'jam_ke' => '1', 'materi' => 'Materi Pecahan SMP',
            'total_siswa' => 1, 'total_hadir' => 1, 'total_sakit' => 0, 'total_izin' => 0, 'total_alpa' => 0,
        ]);
        JurnalAbsensi::create([
            'jurnal_id' => $this->jurnalSmp->id, 'siswa_id' => $this->siswaSmp->id, 'nama_siswa' => $this->siswaSmp->nama_lengkap, 'status' => 'hadir',
        ]);

        $this->jurnalSdit = Jurnal::create([
            'lembaga_id' => $this->sdit->id, 'guru_id' => $this->guruSdit->id, 'nama_guru' => $this->guruSdit->name,
            'kelas' => '1 A', 'semester' => 'Gasal', 'tahun_pelajaran' => '2026/2027', 'hari' => 'Senin',
            'tanggal' => $today, 'mata_pelajaran' => 'Tematik SD IT', 'jam_ke' => '1', 'materi' => 'Materi Mengenal Huruf SDIT',
            'total_siswa' => 1, 'total_hadir' => 1, 'total_sakit' => 0, 'total_izin' => 0, 'total_alpa' => 0,
        ]);
        JurnalAbsensi::create([
            'jurnal_id' => $this->jurnalSdit->id, 'siswa_id' => $this->siswaSdit->id, 'nama_siswa' => $this->siswaSdit->nama_lengkap, 'status' => 'hadir',
        ]);

        // Ekskul & Jurnal Ekskul
        $this->ekskulSmp = Ekstrakurikuler::create([
            'lembaga_id' => $this->smp->id, 'pembina_id' => $this->guruSmp->id, 'nama_pembina' => $this->guruSmp->name,
            'nama_ekskul' => 'Pramuka SMP', 'jenjang' => 'SMP', 'is_active' => true,
        ]);
        $this->ekskulSmp->siswas()->attach($this->siswaSmp->id, ['is_active' => true]);

        $this->ekskulSdit = Ekstrakurikuler::create([
            'lembaga_id' => $this->sdit->id, 'pembina_id' => $this->guruSdit->id, 'nama_pembina' => $this->guruSdit->name,
            'nama_ekskul' => 'Tahfidz Cilik SDIT', 'jenjang' => 'SD IT', 'is_active' => true,
        ]);
        $this->ekskulSdit->siswas()->attach($this->siswaSdit->id, ['is_active' => true]);

        $this->jurnalEkskulSmp = JurnalEkskul::create([
            'ekstrakurikuler_id' => $this->ekskulSmp->id, 'lembaga_id' => $this->smp->id,
            'pembina_id' => $this->guruSmp->id, 'nama_pembina' => $this->guruSmp->name, 'nama_ekskul' => 'Pramuka SMP',
            'tanggal' => $today, 'hari' => 'Senin', 'materi' => 'Materi Tali Temali SMP',
            'total_siswa' => 1, 'total_hadir' => 1,
        ]);
        $this->jurnalEkskulSdit = JurnalEkskul::create([
            'ekstrakurikuler_id' => $this->ekskulSdit->id, 'lembaga_id' => $this->sdit->id,
            'pembina_id' => $this->guruSdit->id, 'nama_pembina' => $this->guruSdit->name, 'nama_ekskul' => 'Tahfidz Cilik SDIT',
            'tanggal' => $today, 'hari' => 'Senin', 'materi' => 'Materi Juz Amma SDIT',
            'total_siswa' => 1, 'total_hadir' => 1,
        ]);

        // Ujian
        $this->ujianSmp = Ujian::create([
            'lembaga_id' => $this->smp->id, 'kode_ujian' => 'UJ-SMP-01', 'nama_ujian' => 'UTS Matematika SMP',
            'mapel_id' => $this->mapelSmp->id, 'kelas_id' => $this->kelasSmp->id, 'guru_id' => $this->guruSmp->id,
            'jumlah_soal' => 20, 'jumlah_opsi' => 4, 'bobot_benar' => 5, 'tanggal_ujian' => $today, 'status' => 'aktif',
        ]);
        $this->ujianSdit = Ujian::create([
            'lembaga_id' => $this->sdit->id, 'kode_ujian' => 'UJ-SDIT-01', 'nama_ujian' => 'UTS Tematik SDIT',
            'mapel_id' => $this->mapelSdit->id, 'kelas_id' => $this->kelasSdit->id, 'guru_id' => $this->guruSdit->id,
            'jumlah_soal' => 10, 'jumlah_opsi' => 4, 'bobot_benar' => 10, 'tanggal_ujian' => $today, 'status' => 'aktif',
        ]);

        // Perizinan (Sambang, Sakit, Haid)
        $this->sambangSmp = PerizinanSambang::create([
            'siswa_id' => $this->siswaSmp->id, 'nama' => $this->siswaSmp->nama_lengkap, 'kamar' => 'Kamar Putra 1',
            'tanggal_mulai' => $today, 'tanggal_kembali' => $today, 'status' => 'sambang', 'keterangan' => 'Izin SMP',
        ]);
        $this->sambangSdit = PerizinanSambang::create([
            'siswa_id' => $this->siswaSdit->id, 'nama' => $this->siswaSdit->nama_lengkap, 'kamar' => 'Kamar Putri 1',
            'tanggal_mulai' => $today, 'tanggal_kembali' => $today, 'status' => 'sambang', 'keterangan' => 'Izin SDIT',
        ]);

        $this->sakitSmp = PerizinanSakit::create([
            'siswa_id' => $this->siswaSmp->id, 'nama' => $this->siswaSmp->nama_lengkap, 'kamar' => 'Kamar Putra 1',
            'tanggal_sakit' => $today, 'sakit' => 'Demam SMP', 'status' => 'sakit',
        ]);
        $this->sakitSdit = PerizinanSakit::create([
            'siswa_id' => $this->siswaSdit->id, 'nama' => $this->siswaSdit->nama_lengkap, 'kamar' => 'Kamar Putri 1',
            'tanggal_sakit' => $today, 'sakit' => 'Flu SDIT', 'status' => 'sakit',
        ]);

        $this->haidSmp = PerizinanHaid::create([
            'siswa_id' => $this->siswaSmp->id, 'nama' => $this->siswaSmp->nama_lengkap, 'kamar' => 'Kamar Putra 1',
            'tanggal_mulai' => $today, 'status' => 'sedang_haid', 'keterangan' => 'Udzur SMP',
        ]);
        $this->haidSdit = PerizinanHaid::create([
            'siswa_id' => $this->siswaSdit->id, 'nama' => $this->siswaSdit->nama_lengkap, 'kamar' => 'Kamar Putri 1',
            'tanggal_mulai' => $today, 'status' => 'sedang_haid', 'keterangan' => 'Udzur SDIT',
        ]);

        // Perpustakaan Peminjaman
        $buku = PerpustakaanBuku::create([
            'kode_buku' => 'BK-0001', 'judul_buku' => 'Ensiklopedia Islam', 'stok_total' => 10, 'stok_tersedia' => 8,
        ]);
        $this->pinjamSmp = PerpustakaanPeminjaman::create([
            'kode_pinjam' => 'PJM-SMP-001', 'perpustakaan_buku_id' => $buku->id, 'siswa_id' => $this->siswaSmp->id,
            'nama_peminjam' => $this->siswaSmp->nama_lengkap, 'kelas_kamar' => 'VII A', 'jumlah' => 1,
            'tanggal_pinjam' => $today, 'tanggal_jatuh_tempo' => Carbon::today()->addDays(7)->toDateString(), 'status' => 'dipinjam',
        ]);
        $this->pinjamSdit = PerpustakaanPeminjaman::create([
            'kode_pinjam' => 'PJM-SDIT-001', 'perpustakaan_buku_id' => $buku->id, 'siswa_id' => $this->siswaSdit->id,
            'nama_peminjam' => $this->siswaSdit->nama_lengkap, 'kelas_kamar' => '1 A', 'jumlah' => 1,
            'tanggal_pinjam' => $today, 'tanggal_jatuh_tempo' => Carbon::today()->addDays(7)->toDateString(), 'status' => 'dipinjam',
        ]);

        // Presensi Guru & RFID Dashboard
        $this->presensiGuruSmp = PresensiGuru::create([
            'user_id' => $this->guruSmp->id, 'lembaga_id' => $this->smp->id, 'tanggal' => $today,
            'jam_masuk' => '07:05:00', 'status' => 'hadir',
        ]);
        $this->presensiGuruSdit = PresensiGuru::create([
            'user_id' => $this->guruSdit->id, 'lembaga_id' => $this->sdit->id, 'tanggal' => $today,
            'jam_masuk' => '07:02:00', 'status' => 'hadir',
        ]);

        MasterDevice::create([
            'device_id' => 'DEV_MAIN', 'nama_lokasi' => 'Gerbang Utama', 'kategori_area' => 'umum', 'status_aktif' => true,
        ]);
        $sesiSekolah = MasterSesi::create([
            'kode_sesi' => 'SESI_SEKOLAH_PAGI', 'nama_sesi' => 'Masuk Sekolah',
            'jam_mulai' => '06:30:00', 'jam_selesai' => '08:00:00', 'kategori' => 'sekolah',
        ]);
        $sesiPegawai = MasterSesi::create([
            'kode_sesi' => 'SESI_PEGAWAI_PAGI', 'nama_sesi' => 'Masuk Pegawai',
            'jam_mulai' => '06:30:00', 'jam_selesai' => '08:00:00', 'kategori' => 'pegawai',
        ]);

        Presensi::create([
            'device_id' => 'DEV_MAIN', 'sesi_id' => $sesiSekolah->id, 'tipe_entitas' => 'siswa',
            'entitas_id' => $this->siswaSmp->id, 'rfid_uid' => 'RFID-SMP-01',
            'waktu_tap' => Carbon::today()->setTime(7, 5, 0), 'status_kehadiran' => 'hadir',
        ]);
        Presensi::create([
            'device_id' => 'DEV_MAIN', 'sesi_id' => $sesiSekolah->id, 'tipe_entitas' => 'siswa',
            'entitas_id' => $this->siswaSdit->id, 'rfid_uid' => 'RFID-SDIT-01',
            'waktu_tap' => Carbon::today()->setTime(7, 6, 0), 'status_kehadiran' => 'hadir',
        ]);
        Presensi::create([
            'device_id' => 'DEV_MAIN', 'sesi_id' => $sesiPegawai->id, 'tipe_entitas' => 'guru_pegawai',
            'entitas_id' => $this->guruSmp->id, 'rfid_uid' => 'RFID-GSMP-01',
            'waktu_tap' => Carbon::today()->setTime(7, 5, 0), 'status_kehadiran' => 'hadir',
        ]);
        Presensi::create([
            'device_id' => 'DEV_MAIN', 'sesi_id' => $sesiPegawai->id, 'tipe_entitas' => 'guru_pegawai',
            'entitas_id' => $this->guruSdit->id, 'rfid_uid' => 'RFID-GSDIT-01',
            'waktu_tap' => Carbon::today()->setTime(7, 2, 0), 'status_kehadiran' => 'hadir',
        ]);
    }

    private function grantAllPermissions(User $user): void
    {
        $codes = [
            'siswa', 'guru_warga', 'lulus_warga', 'mutasi_warga', 'nfc',
            'sekolah', 'sekolah_jurnal', 'sekolah_rekap_jurnal', 'ekskul', 'jadwal_pelajaran', 'jadwal_saya',
            'ujian', 'ujian_soal', 'ujian_ljk', 'ujian_sesi', 'ujian_koreksi', 'ujian_nilai',
            'daftar_buku', 'pinjam_buku',
            'perizinan_sambang', 'perizinan_sakit', 'perizinan_haid',
            'pendaftaran', 'rekap_sekolah', 'rekap_guru', 'rekap_ekskul',
            'guru_sekolah', 'plotting_mapel', 'plotting_kelas', 'plotting_ekskul',
            'pengaturan_absensi', 'monitor_rfid', 'pengguna',
            'master_jam', 'master_lembaga', 'master_import_siswa',
        ];

        foreach ($codes as $code) {
            UserPermission::updateOrCreate(
                ['user_id' => $user->id, 'menu_code' => $code],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]
            );
        }
    }

    public function test_switch_lembaga_dan_proteksi_manipulasi_session_room(): void
    {
        // Login sebagai Guru SD IT → room otomatis sdit
        $respBeranda = $this->actingAs($this->guruSdit)->get(route('beranda'));
        $respBeranda->assertStatus(200);
        $respBeranda->assertSee('SD IT');
        $respBeranda->assertDontSee('Siswa SMP Alpha');
        $this->assertEquals('sdit', session('active_lembaga'));

        // Percobaan pindah ke room SMP harus ditolak
        $respSwitch = $this->actingAs($this->guruSdit)->get(route('switch-lembaga', ['kode' => 'smp']));
        $respSwitch->assertRedirect();
        $this->assertEquals('sdit', session('active_lembaga'));

        // Percobaan memanipulasi session active_lembaga ke 'smp' dipaksa balik ke 'sdit' oleh middleware
        $respHijack = $this->actingAs($this->guruSdit)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('siswa'));
        $respHijack->assertStatus(200);
        $this->assertEquals('sdit', session('active_lembaga'));
        $respHijack->assertSee('Siswa SDIT Beta');
        $respHijack->assertDontSee('Siswa SMP Alpha');
    }

    public function test_isolasi_data_warga_dan_nfc_untuk_guru_sdit(): void
    {
        // 1. Siswa (termasuk upaya injeksi ?jenjang=SMP)
        $respSiswa = $this->actingAs($this->guruSdit)->get(route('siswa', ['jenjang' => 'SMP']));
        $respSiswa->assertStatus(200);
        $respSiswa->assertSee('Siswa SDIT Beta');
        $respSiswa->assertDontSee('Siswa SMP Alpha');
        $respSiswa->assertDontSee('Siswa RA Gamma');
        $respSiswa->assertDontSee('Siswa Reguler Delta');

        // Akses detail / edit / hapus siswa SMP ditolak 403
        $this->actingAs($this->guruSdit)->get(route('siswa.show', $this->siswaSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->get(route('siswa.edit', $this->siswaSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('siswa.destroy', $this->siswaSmp->id))->assertStatus(403);

        // Tambah siswa baru dari room SD IT tetap tersimpan dengan jenjang 'SD IT' meski payload kirim 'SMP'
        $respStore = $this->actingAs($this->guruSdit)->post(route('siswa.store'), [
            'nama_lengkap' => 'Murid Baru SDIT',
            'jenjang'      => 'SMP',
            'kamar'        => '-',
            'status'       => 'aktif',
        ]);
        $respStore->assertRedirect(route('siswa'));
        $this->assertDatabaseHas('siswas', [
            'nama_lengkap' => 'Murid Baru SDIT',
            'jenjang'      => 'SD IT',
        ]);

        // 2. Data Warga → Guru (termasuk upaya injeksi ?lembaga_id=smp)
        $respGuru = $this->actingAs($this->guruSdit)->get(route('data-warga.guru', ['lembaga_id' => $this->smp->id]));
        $respGuru->assertStatus(200);
        $respGuru->assertSee('Ustadzah Fatimah SDIT');
        $respGuru->assertSee('Guru SD IT');
        $respGuru->assertDontSee('Pak Budi SMP');
        $respGuru->assertDontSee('Bunda Aisyah RA');
        $respGuru->assertDontSee('Guru SMP');
        $respGuru->assertDontSee('Guru SMA');

        // 3. Data Warga → Lulus & Mutasi
        $respLulus = $this->actingAs($this->guruSdit)->get(route('data-warga.lulus', ['jenjang' => 'SMP']));
        $respLulus->assertStatus(200);
        $respLulus->assertSee('Alumni Lulus SDIT');
        $respLulus->assertDontSee('Alumni Lulus SMP');

        $this->actingAs($this->guruSdit)->post(route('data-warga.lulus.store'), [
            'siswa_id'       => $this->siswaSmp->id,
            'kategori_lulus' => 'Sekolah',
            'tahun_lulus'    => '2026',
        ])->assertStatus(403);

        $respMutasi = $this->actingAs($this->guruSdit)->get(route('data-warga.mutasi', ['jenjang' => 'SMP']));
        $respMutasi->assertStatus(200);
        $respMutasi->assertSee('Siswa Mutasi SDIT');
        $respMutasi->assertDontSee('Siswa Mutasi SMP');
        $respMutasi->assertDontSee('Mutasi SMP');

        $this->actingAs($this->guruSdit)->patch(route('data-warga.kembalikan-aktif', $this->siswaMutasiSmp->id))
            ->assertStatus(403);

        // 4. Input NFC hanya tampilkan warga SD IT
        $respNfc = $this->actingAs($this->guruSdit)->get(route('nfc.index'));
        $respNfc->assertStatus(200);
        $respNfc->assertSee('Siswa SDIT Beta');
        $respNfc->assertDontSee('Siswa SMP Alpha');
    }

    public function test_isolasi_menu_sekolah_plotting_dan_jadwal_untuk_guru_sdit(): void
    {
        // 1. Guru Sekolah
        $respGuruSekolah = $this->actingAs($this->guruSdit)->get(route('guru-sekolah.index', ['lembaga_id' => $this->smp->id]));
        $respGuruSekolah->assertStatus(200);
        $respGuruSekolah->assertSee('Ustadzah Fatimah SDIT');
        $respGuruSekolah->assertDontSee('Pak Budi SMP');
        $respGuruSekolah->assertDontSee('Pak Pending SMP');

        $this->actingAs($this->guruSdit)->post(route('guru-sekolah.approve', $this->guruSmpPending->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('guru-sekolah.destroy', $this->guruSmp->id))->assertStatus(403);

        // 2. Plotting Mapel
        $respMapel = $this->actingAs($this->guruSdit)->get(route('plotting-mapel.index', ['lembaga_id' => $this->smp->id]));
        $respMapel->assertStatus(200);
        $respMapel->assertSee('Tematik SD IT');
        $respMapel->assertDontSee('Matematika SMP');
        $this->actingAs($this->guruSdit)->delete(route('plotting-mapel.destroy', $this->mapelSmp->id))->assertStatus(403);

        // 3. Plotting Kelas
        $respKelas = $this->actingAs($this->guruSdit)->get(route('plotting-kelas.index', ['lembaga' => 'smp']));
        $respKelas->assertStatus(200);
        $respKelas->assertSee('1 A');
        $respKelas->assertDontSee('VII A');
        $this->actingAs($this->guruSdit)->delete(route('plotting-kelas.destroy-kelas', $this->kelasSmp->id))->assertStatus(403);

        // Tambah ruang kelas baru di SD IT berhasil
        $this->actingAs($this->guruSdit)->post(route('plotting-kelas.store-kelas'), [
            'lembaga_id' => $this->sdit->id,
            'nama_kelas' => '1 B',
            'tingkat'    => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'lembaga_id' => $this->sdit->id,
            'jenjang'    => 'SD IT',
            'nama_kelas' => '1 B',
        ]);

        // 4. Plotting Ekskul & Jurnal Ekskul & Rekap Ekskul
        $respPlotEkskul = $this->actingAs($this->guruSdit)->get(route('plotting-ekskul.index', ['lembaga' => 'smp']));
        $respPlotEkskul->assertStatus(200);
        $respPlotEkskul->assertSee('Tahfidz Cilik SDIT');
        $respPlotEkskul->assertDontSee('Pramuka SMP');
        $this->actingAs($this->guruSdit)->delete(route('plotting-ekskul.destroy-ekskul', $this->ekskulSmp->id))->assertStatus(403);

        $respEkskul = $this->actingAs($this->guruSdit)->get(route('sekolah.ekskul.index'));
        $respEkskul->assertStatus(200);
        $respEkskul->assertSee('Tahfidz Cilik SDIT');
        $respEkskul->assertDontSee('Pramuka SMP');

        $this->actingAs($this->guruSdit)->getJson(route('sekolah.ekskul.siswa-by-ekskul', $this->ekskulSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('sekolah.ekskul.jurnal.destroy', $this->jurnalEkskulSmp->id))->assertStatus(403);

        $respRekapEkskul = $this->actingAs($this->guruSdit)->get(route('rekap-absensi.ekskul'));
        $respRekapEkskul->assertStatus(200);
        $respRekapEkskul->assertSee('Tahfidz Cilik SDIT');
        $respRekapEkskul->assertDontSee('Pramuka SMP');

        // 5. Jurnal Sekolah, Rekap Jurnal, Rekap Absensi Sekolah, Anggota Kelas
        $respJurnal = $this->actingAs($this->guruSdit)->get(route('sekolah.jurnal', ['lembaga' => 'smp']));
        $respJurnal->assertStatus(200);
        $respJurnal->assertSee('1 A');
        $respJurnal->assertDontSee('VII A');

        $respRekapJurnal = $this->actingAs($this->guruSdit)->get(route('sekolah.rekap-jurnal'));
        $respRekapJurnal->assertStatus(200);
        $respRekapJurnal->assertSee('Materi Mengenal Huruf SDIT');
        $respRekapJurnal->assertDontSee('Materi Pecahan SMP');
        $this->actingAs($this->guruSdit)->delete(route('sekolah.jurnal.destroy', $this->jurnalSmp->id))->assertStatus(403);

        $respRekapSekolah = $this->actingAs($this->guruSdit)->get(route('rekap-absensi.sekolah', ['kelas' => 'VII A']));
        $respRekapSekolah->assertStatus(200);
        $respRekapSekolah->assertDontSee('Siswa SMP Alpha');

        $respAnggota = $this->actingAs($this->guruSdit)->getJson(route('sekolah.kelas-anggota', ['kelas' => 'VII A']));
        $respAnggota->assertStatus(200);
        $this->assertEmpty($respAnggota->json('data') ?? $respAnggota->json('siswas') ?? []);

        // 6. Jadwal Pelajaran & Jadwal Mengajar Saya
        $respJadwal = $this->actingAs($this->guruSdit)->get(route('jadwal-pelajaran.index', ['jenjang' => 'SMP']));
        $respJadwal->assertStatus(200);
        $respJadwal->assertSee('Matriks Jadwal Pelajaran SD IT Al-Muttaqin');
        $respJadwal->assertSee('Tematik SD IT');
        $respJadwal->assertDontSee('Matematika SMP');

        $respJadwalSaya = $this->actingAs($this->guruSdit)->get(route('jadwal-pelajaran.saya'));
        $respJadwalSaya->assertStatus(200);
        $respJadwalSaya->assertSee('Tematik SD IT');
        $respJadwalSaya->assertDontSee('Matematika SMP');

        // Input jadwal SD IT berhasil
        $slotSelasaSdit = TimeSlot::where('hari', 'Selasa')->where('jenjang', 'SD IT')->where('jam_ke', 2)->firstOrFail();
        $this->actingAs($this->guruSdit)->post(route('jadwal-pelajaran.saya.store'), [
            'classroom_id' => $this->kelasSdit->id,
            'subject_id'   => $this->mapelSdit->id,
            'hari'         => 'Selasa',
            'jam_mulai'    => 2,
            'jam_selesai'  => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->kelasSdit->id,
            'subject_id'   => $this->mapelSdit->id,
            'time_slot_id' => $slotSelasaSdit->id,
        ]);

        // Input jadwal ke kelas SMP ditolak 403
        $this->actingAs($this->guruSdit)->post(route('jadwal-pelajaran.saya.store'), [
            'classroom_id' => $this->kelasSmp->id,
            'subject_id'   => $this->mapelSmp->id,
            'hari'         => 'Selasa',
            'jam_mulai'    => 2,
            'jam_selesai'  => 2,
        ])->assertStatus(403);
    }

    public function test_isolasi_ujian_pendaftaran_perizinan_perpustakaan_rekap_dan_pencarian_untuk_guru_sdit(): void
    {
        // 1. Semua 5 Submenu Ujian
        foreach (['ujian.soal', 'ujian.ljk', 'ujian.sesi', 'ujian.koreksi', 'ujian.nilai'] as $ujianRoute) {
            $resp = $this->actingAs($this->guruSdit)->get(route($ujianRoute));
            $resp->assertStatus(200);
            $resp->assertSee('UTS Tematik SDIT');
            $resp->assertDontSee('UTS Matematika SMP');
        }
        $this->actingAs($this->guruSdit)->get(route('ujian.edit', $this->ujianSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('ujian.destroy', $this->ujianSmp->id))->assertStatus(403);

        // 2. Pendaftaran & Link Penerimaan
        $respDaftar = $this->actingAs($this->guruSdit)->get(route('pendaftaran.index', ['jenjang' => 'SMP']));
        $respDaftar->assertStatus(200);
        $respDaftar->assertSee('Calon Santri SDIT');
        $respDaftar->assertDontSee('Calon Santri SMP');
        $respDaftar->assertDontSee('Calon Santri RA');
        $this->actingAs($this->guruSdit)->get(route('pendaftaran.show', $this->pendaftaranSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('pendaftaran.destroy', $this->pendaftaranSmp->id))->assertStatus(403);

        $respEvent = $this->actingAs($this->guruSdit)->get(route('penerimaan.index'));
        $respEvent->assertStatus(200);
        $respEvent->assertSee('SPMB Khusus SD IT');
        $respEvent->assertDontSee('SPMB Khusus SMP');
        $respEvent->assertDontSee('SPMB Khusus RA');
        $this->actingAs($this->guruSdit)->get(route('penerimaan.edit', $this->eventSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('penerimaan.destroy', $this->eventSmp->id))->assertStatus(403);

        // 3. Rekap Absensi Guru (upaya bypass ?lembaga_id=all dan ?lembaga_id=smp tetap terkunci SD IT)
        $today = Carbon::today()->toDateString();
        $respRekapGuruAll = $this->actingAs($this->guruSdit)->get(route('rekap-absensi.guru', [
            'lembaga_id' => 'all', 'tanggal' => $today,
        ]));
        $respRekapGuruAll->assertStatus(200);
        $respRekapGuruAll->assertSee('Ustadzah Fatimah SDIT');
        $respRekapGuruAll->assertDontSee('Pak Budi SMP');

        $respRekapGuruSmp = $this->actingAs($this->guruSdit)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->smp->id, 'tanggal' => $today,
        ]));
        $respRekapGuruSmp->assertStatus(200);
        $respRekapGuruSmp->assertSee('Ustadzah Fatimah SDIT');
        $respRekapGuruSmp->assertDontSee('Pak Budi SMP');

        $this->actingAs($this->guruSdit)->put(route('rekap-absensi.koreksi', $this->presensiGuruSmp->id), [
            'status' => 'izin',
        ])->assertStatus(403);

        // 4. Perizinan (Sambang, Sakit, Haid)
        $respSambang = $this->actingAs($this->guruSdit)->get(route('perizinan.sambang'));
        $respSambang->assertStatus(200)->assertSee('Siswa SDIT Beta')->assertDontSee('Siswa SMP Alpha');
        $this->actingAs($this->guruSdit)->delete(route('perizinan.sambang.destroy', $this->sambangSmp->id))->assertStatus(403);

        $respSakit = $this->actingAs($this->guruSdit)->get(route('perizinan.sakit'));
        $respSakit->assertStatus(200)->assertSee('Siswa SDIT Beta')->assertDontSee('Siswa SMP Alpha');
        $this->actingAs($this->guruSdit)->delete(route('perizinan.sakit.destroy', $this->sakitSmp->id))->assertStatus(403);

        $respHaid = $this->actingAs($this->guruSdit)->get(route('perizinan.haid'));
        $respHaid->assertStatus(200)->assertSee('Siswa SDIT Beta')->assertDontSee('Siswa SMP Alpha');
        $this->actingAs($this->guruSdit)->delete(route('perizinan.haid.destroy', $this->haidSmp->id))->assertStatus(403);

        // 5. Perpustakaan Pinjam Buku
        $respPinjam = $this->actingAs($this->guruSdit)->get(route('perpustakaan.pinjam.index'));
        $respPinjam->assertStatus(200)->assertSee('Siswa SDIT Beta')->assertDontSee('Siswa SMP Alpha');
        $this->actingAs($this->guruSdit)->delete(route('perpustakaan.pinjam.destroy', $this->pinjamSmp->id))->assertStatus(403);

        // 6. Dashboard Monitoring Presensi Real-Time (JSON)
        $respDashSekolah = $this->actingAs($this->guruSdit)->getJson(route('dashboard-presensi.data', ['tab' => 'sekolah']));
        $respDashSekolah->assertStatus(200);
        $jsonSekolah = json_encode($respDashSekolah->json());
        $this->assertStringContainsString('Siswa SDIT Beta', $jsonSekolah);
        $this->assertStringNotContainsString('Siswa SMP Alpha', $jsonSekolah);

        $respDashPegawai = $this->actingAs($this->guruSdit)->getJson(route('dashboard-presensi.data', ['tab' => 'pegawai']));
        $respDashPegawai->assertStatus(200);
        $jsonPegawai = json_encode($respDashPegawai->json());
        $this->assertStringContainsString('Ustadzah Fatimah SDIT', $jsonPegawai);
        $this->assertStringNotContainsString('Pak Budi SMP', $jsonPegawai);

        // 7. Quick Search tidak membocorkan siswa/guru/pendaftar SMP
        $respSearchSmp = $this->actingAs($this->guruSdit)->getJson(route('quick-search', ['q' => 'SMP']));
        $respSearchSmp->assertStatus(200);
        $searchSmpText = json_encode($respSearchSmp->json());
        $this->assertStringNotContainsString('Siswa SMP Alpha', $searchSmpText);
        $this->assertStringNotContainsString('Pak Budi SMP', $searchSmpText);
        $this->assertStringNotContainsString('Calon Santri SMP', $searchSmpText);

        $respSearchSdit = $this->actingAs($this->guruSdit)->getJson(route('quick-search', ['q' => 'SDIT']));
        $respSearchSdit->assertStatus(200);
        $searchSditText = json_encode($respSearchSdit->json());
        $this->assertStringContainsString('Siswa SDIT Beta', $searchSditText);

        // 8. Master Jam Pelajaran terkunci SD IT
        $respJam = $this->actingAs($this->guruSdit)->get(route('master.jam-pelajaran.index', ['jenjang' => 'SMP', 'hari' => 'Senin']));
        $respJam->assertStatus(200);
        $respJam->assertSee('Jam 1 Khusus SDIT');
        $respJam->assertDontSee('Jam 1 Khusus SMP');

        $this->actingAs($this->guruSdit)->post(route('master.jam-pelajaran.store'), [
            'hari_list'     => ['Selasa'],
            'jenjang'       => 'SMP',
            'jam_ke'        => 2,
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '08:40',
            'tipe_kegiatan' => 'KBM',
        ])->assertSessionHasErrors('jenjang');

        // 9. Manajemen Pengguna hanya tampilkan pengguna SD IT dan blokir edit/hapus guru SMP
        $respUsers = $this->actingAs($this->guruSdit)->get(route('pengguna.index'));
        $respUsers->assertStatus(200);
        $respUsers->assertSee('Ustadzah Fatimah SDIT');
        $respUsers->assertDontSee('Pak Budi SMP');
        $this->actingAs($this->guruSdit)->get(route('pengguna.edit', $this->guruSmp->id))->assertStatus(403);
        $this->actingAs($this->guruSdit)->delete(route('pengguna.destroy', $this->guruSmp->id))->assertStatus(403);
    }

    public function test_guru_ra_terisolasi_penuh_dan_berfungsi_pada_room_ra(): void
    {
        $respSiswa = $this->actingAs($this->guruRa)->get(route('siswa'));
        $respSiswa->assertStatus(200);
        $this->assertEquals('ra', session('active_lembaga'));
        $respSiswa->assertSee('Siswa RA Gamma');
        $respSiswa->assertDontSee('Siswa SDIT Beta');
        $respSiswa->assertDontSee('Siswa SMP Alpha');

        $respDaftar = $this->actingAs($this->guruRa)->get(route('pendaftaran.index'));
        $respDaftar->assertStatus(200)->assertSee('Calon Santri RA')->assertDontSee('Calon Santri SDIT');

        $respEvent = $this->actingAs($this->guruRa)->get(route('penerimaan.index'));
        $respEvent->assertStatus(200)->assertSee('SPMB Khusus RA')->assertDontSee('SPMB Khusus SD IT');

        $respMapel = $this->actingAs($this->guruRa)->get(route('plotting-mapel.index'));
        $respMapel->assertStatus(200)->assertSee('Calistung RA')->assertDontSee('Tematik SD IT');

        // Guru RA dapat membuat kelas RA B dan menambah siswa RA baru
        $this->actingAs($this->guruRa)->post(route('plotting-kelas.store-kelas'), [
            'lembaga_id' => $this->ra->id,
            'nama_kelas' => 'RA B',
            'tingkat'    => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'lembaga_id' => $this->ra->id,
            'jenjang'    => 'RA',
            'nama_kelas' => 'RA B',
        ]);

        $this->actingAs($this->guruRa)->post(route('siswa.store'), [
            'nama_lengkap' => 'Ananda Baru RA',
            'kamar'        => '-',
            'status'       => 'aktif',
        ])->assertRedirect(route('siswa'));
        $this->assertDatabaseHas('siswas', [
            'nama_lengkap' => 'Ananda Baru RA',
            'jenjang'      => 'RA',
        ]);
    }

    public function test_yayasan_switch_ke_room_sdit_dan_kembali_ke_yayasan(): void
    {
        // Yayasan pindah ke room SD IT
        $this->actingAs($this->yayasanUser)
            ->get(route('switch-lembaga', ['kode' => 'sdit']))
            ->assertRedirect();
        $this->assertEquals('sdit', session('active_lembaga'));

        $respSditRoom = $this->actingAs($this->yayasanUser)
            ->withSession(['active_lembaga' => 'sdit'])
            ->get(route('siswa'));
        $respSditRoom->assertStatus(200);
        $respSditRoom->assertSee('Siswa SDIT Beta');
        $respSditRoom->assertDontSee('Siswa SMP Alpha');

        // Yayasan pindah kembali ke room Yayasan → melihat semua siswa
        $this->actingAs($this->yayasanUser)
            ->get(route('switch-lembaga', ['kode' => 'yayasan']))
            ->assertRedirect();
        $this->assertEquals('yayasan', session('active_lembaga'));

        $respAll = $this->actingAs($this->yayasanUser)
            ->withSession(['active_lembaga' => 'yayasan'])
            ->get(route('siswa'));
        $respAll->assertStatus(200);
        $respAll->assertSee('Siswa SMP Alpha');
        $respAll->assertSee('Siswa SDIT Beta');
        $respAll->assertSee('Siswa RA Gamma');
    }
}
