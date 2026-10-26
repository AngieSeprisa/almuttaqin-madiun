<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\GuruSekolahController;
use App\Http\Controllers\ImportSiswaController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\LembagaController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PengaturanAbsensiController;
use App\Http\Controllers\PlottingKelasController;
use App\Http\Controllers\PlottingMapelController;
use App\Http\Controllers\PerizinanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickSearchController;
use App\Http\Controllers\RekapAbsensiController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| Autentikasi Pengguna
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Area Berotentikasi (Membutuhkan Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Beranda & Switch Ruang Kerja (Lembaga)
    Route::get('/', [BerandaController::class, 'index'])->name('beranda');
    Route::get('/beranda', [BerandaController::class, 'index']);
    Route::get('/switch-lembaga/{kode}', [AuthController::class, 'switchLembaga'])->name('switch-lembaga');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/registrasi-mapel', [ProfileController::class, 'ajukanMapel'])->name('profile.registrasi-mapel');
    Route::post('/profile/batal-mapel', [ProfileController::class, 'batalAjukanMapel'])->name('profile.batal-mapel');
    Route::get('/user', [ProfileController::class, 'security'])->name('user');
    Route::put('/user', [ProfileController::class, 'updatePassword'])->name('user.password.update');
    Route::get('/quick-search', [QuickSearchController::class, 'search'])->name('quick-search');

    // Menu Data Santri & Siswa
    Route::middleware('permission:siswa,view')->group(function () {
        Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa');
        Route::get('/siswa/{id}', [SiswaController::class, 'show'])->name('siswa.show');
        Route::get('/siswa/{id}/cetak', [SiswaController::class, 'cetak'])->name('siswa.cetak');
    });

    Route::post('/siswa', [SiswaController::class, 'store'])
        ->middleware('permission:siswa,create')
        ->name('siswa.store');

    Route::middleware('permission:siswa,edit')->group(function () {
        Route::get('/siswa/{id}/edit', [SiswaController::class, 'edit'])->name('siswa.edit');
        Route::put('/siswa/{id}', [SiswaController::class, 'update'])->name('siswa.update');
    });

    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])
        ->middleware('permission:siswa,delete')
        ->name('siswa.destroy');

    // Menu Pendaftaran Santri Baru (PSB)
    Route::middleware('permission:pendaftaran,view')->prefix('pendaftaran')->name('pendaftaran.')->group(function () {
        Route::get('/', [PendaftaranController::class, 'index'])->name('index');
        Route::get('/{id}', [PendaftaranController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/cetak', [PendaftaranController::class, 'cetak'])->whereNumber('id')->name('cetak');
    });

    Route::middleware('permission:pendaftaran,create')->prefix('pendaftaran')->name('pendaftaran.')->group(function () {
        Route::get('/create', [PendaftaranController::class, 'create'])->name('create');
        Route::post('/', [PendaftaranController::class, 'store'])->name('store');
    });

    Route::patch('/pendaftaran/{id}/status', [PendaftaranController::class, 'updateStatus'])
        ->middleware('permission:pendaftaran,edit')
        ->name('pendaftaran.updateStatus');

    Route::middleware('permission:pendaftaran|siswa,edit')->prefix('pendaftaran')->name('pendaftaran.')->group(function () {
        Route::get('/{id}/edit', [PendaftaranController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [PendaftaranController::class, 'update'])->whereNumber('id')->name('update');
    });

    // Menu Perizinan: Sambang
    Route::middleware('permission:perizinan_sambang,view')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/sambang', [PerizinanController::class, 'sambang'])->name('sambang');
    });

    Route::middleware('permission:perizinan_sambang,create')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/sambang/create', [PerizinanController::class, 'createSambang'])->name('sambang.create');
        Route::post('/sambang', [PerizinanController::class, 'storeSambang'])->name('sambang.store');
    });

    Route::middleware('permission:perizinan_sambang,edit')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::patch('/sambang/{id}/kembali', [PerizinanController::class, 'kembaliSambang'])->name('sambang.kembali');
        Route::put('/sambang/{id}', [PerizinanController::class, 'updateSambang'])->name('sambang.update');
    });

    Route::delete('/perizinan/sambang/{id}', [PerizinanController::class, 'destroySambang'])
        ->middleware('permission:perizinan_sambang,delete')
        ->name('perizinan.sambang.destroy');

    // Menu Perizinan: Sakit
    Route::middleware('permission:perizinan_sakit,view')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/sakit', [PerizinanController::class, 'sakit'])->name('sakit');
    });

    Route::middleware('permission:perizinan_sakit,create')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/sakit/create', [PerizinanController::class, 'createSakit'])->name('sakit.create');
        Route::post('/sakit', [PerizinanController::class, 'storeSakit'])->name('sakit.store');
    });

    Route::middleware('permission:perizinan_sakit,edit')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::patch('/sakit/{id}/sembuh', [PerizinanController::class, 'sembuhSakit'])->name('sakit.sembuh');
        Route::put('/sakit/{id}', [PerizinanController::class, 'updateSakit'])->name('sakit.update');
    });

    Route::delete('/perizinan/sakit/{id}', [PerizinanController::class, 'destroySakit'])
        ->middleware('permission:perizinan_sakit,delete')
        ->name('perizinan.sakit.destroy');

    // Menu Presensi Guru (Scan QR dari Perangkat Guru & Layar Kiosk)
    Route::prefix('presensi')->name('presensi.')->group(function () {
        Route::get('/', [PresensiController::class, 'index'])->name('index');
        Route::post('/scan', [PresensiController::class, 'scan'])->name('scan');
        Route::post('/izin', [PresensiController::class, 'izin'])->name('izin');
        Route::get('/kiosk', [PresensiController::class, 'kiosk'])->name('kiosk');
        Route::get('/kiosk-data', [PresensiController::class, 'kioskData'])->name('kiosk-data');
    });

    // Menu Rekap (Sekolah & Guru)
    Route::prefix('rekap-absensi')->name('rekap-absensi.')->group(function () {
        Route::get('/sekolah', [SekolahController::class, 'rekapAbsensi'])
            ->middleware('permission:sekolah,view')
            ->name('sekolah');
        Route::get('/guru', [RekapAbsensiController::class, 'guru'])
            ->middleware('permission:rekap_absensi,view')
            ->name('guru');
        Route::put('/koreksi/{id}', [RekapAbsensiController::class, 'koreksi'])
            ->middleware('permission:rekap_absensi,edit')
            ->name('koreksi');
        Route::get('/export', [RekapAbsensiController::class, 'export'])
            ->middleware('permission:rekap_absensi,view')
            ->name('export');
        Route::get('/cetak', [RekapAbsensiController::class, 'cetak'])
            ->middleware('permission:rekap_absensi,view')
            ->name('cetak');
    });

    // Menu Sekolah (Jurnal, Rekap Jurnal, Rekap Absensi)
    Route::middleware('permission:sekolah,view')->prefix('sekolah')->name('sekolah.')->group(function () {
        Route::get('/jurnal', [SekolahController::class, 'jurnal'])->name('jurnal');
        Route::post('/jurnal', [SekolahController::class, 'storeJurnal'])->name('jurnal.store');
        Route::get('/rekap-jurnal', [SekolahController::class, 'rekapJurnal'])->name('rekap-jurnal');
        Route::delete('/rekap-jurnal/{jurnal}', [SekolahController::class, 'destroyJurnal'])->name('jurnal.destroy');
        Route::get('/rekap-absensi', [SekolahController::class, 'rekapAbsensi'])->name('rekap-absensi');
    });

    // Menu Jadwal Pelajaran Multi-Jenjang (SMP & SMA)
    Route::middleware('permission:jadwal_pelajaran|sekolah,view')->prefix('jadwal-pelajaran')->name('jadwal-pelajaran.')->group(function () {
        Route::get('/', [JadwalPelajaranController::class, 'index'])->name('index');
        Route::get('/saya', [JadwalPelajaranController::class, 'saya'])->name('saya');
        Route::post('/saya', [JadwalPelajaranController::class, 'storeSaya'])->name('saya.store');
        Route::delete('/{id}', [JadwalPelajaranController::class, 'destroy'])->name('destroy');
        Route::post('/toggle-lock', [JadwalPelajaranController::class, 'toggleLock'])->name('toggle-lock');
        Route::get('/cetak', [JadwalPelajaranController::class, 'cetak'])->name('cetak');
        Route::get('/ajax/classes-and-subjects', [JadwalPelajaranController::class, 'ajaxClassesAndSubjects'])->name('ajax-classes-subjects');
    });

    // Menu Pengaturan Sekolah: Absensi Guru
    Route::prefix('pengaturan-absensi')->name('pengaturan-absensi.')->group(function () {
        Route::get('/', [PengaturanAbsensiController::class, 'index'])
            ->middleware('permission:pengaturan_absensi,view')
            ->name('index');
        Route::put('/', [PengaturanAbsensiController::class, 'update'])
            ->middleware('permission:pengaturan_absensi,edit')
            ->name('update');
        Route::post('/regenerate-secret', [PengaturanAbsensiController::class, 'regenerateSecret'])
            ->middleware('permission:pengaturan_absensi,edit')
            ->name('regenerate-secret');
    });

    // Menu Pengaturan Sekolah: Guru Sekolah (Kelola Guru & Approval Mapel)
    Route::prefix('guru-sekolah')->name('guru-sekolah.')->group(function () {
        Route::get('/', [GuruSekolahController::class, 'index'])
            ->middleware('permission:guru_sekolah,view')
            ->name('index');
        Route::post('/{user}/approve', [GuruSekolahController::class, 'approve'])
            ->middleware('permission:guru_sekolah,edit')
            ->name('approve');
        Route::post('/{user}/reject', [GuruSekolahController::class, 'reject'])
            ->middleware('permission:guru_sekolah,edit')
            ->name('reject');
        Route::post('/{user}/assign', [GuruSekolahController::class, 'assign'])
            ->middleware('permission:guru_sekolah,edit')
            ->name('assign');
        Route::post('/{user}/reset', [GuruSekolahController::class, 'reset'])
            ->middleware('permission:guru_sekolah,edit')
            ->name('reset');
    });

    // Menu Pengaturan Sekolah: Plotting Mata Pelajaran
    Route::prefix('plotting-mapel')->name('plotting-mapel.')->group(function () {
        Route::get('/', [PlottingMapelController::class, 'index'])
            ->middleware('permission:plotting_mapel,view')
            ->name('index');
        Route::post('/', [PlottingMapelController::class, 'store'])
            ->middleware('permission:plotting_mapel,create')
            ->name('store');
        Route::put('/{mapel}', [PlottingMapelController::class, 'update'])
            ->middleware('permission:plotting_mapel,edit')
            ->name('update');
        Route::delete('/{mapel}', [PlottingMapelController::class, 'destroy'])
            ->middleware('permission:plotting_mapel,delete')
            ->name('destroy');
        Route::patch('/{mapel}/toggle-status', [PlottingMapelController::class, 'toggleStatus'])
            ->middleware('permission:plotting_mapel,edit')
            ->name('toggle-status');
        Route::post('/muat-standar', [PlottingMapelController::class, 'muatStandar'])
            ->middleware('permission:plotting_mapel,create')
            ->name('muat-standar');
    });

    // Menu Pengaturan Sekolah: Plotting Kelas & Kelola Ruang Kelas
    Route::prefix('plotting-kelas')->name('plotting-kelas.')->group(function () {
        Route::get('/', [PlottingKelasController::class, 'index'])
            ->middleware('permission:plotting_kelas|master_kelas,view')
            ->name('index');
        Route::post('/kelas', [PlottingKelasController::class, 'storeKelas'])
            ->middleware('permission:plotting_kelas|master_kelas,create')
            ->name('store-kelas');
        Route::put('/kelas/{classroom}', [PlottingKelasController::class, 'updateKelas'])
            ->middleware('permission:plotting_kelas|master_kelas,edit')
            ->name('update-kelas');
        Route::delete('/kelas/{classroom}', [PlottingKelasController::class, 'destroyKelas'])
            ->middleware('permission:plotting_kelas|master_kelas,delete')
            ->name('destroy-kelas');
        Route::post('/muat-standar', [PlottingKelasController::class, 'muatStandar'])
            ->middleware('permission:plotting_kelas|master_kelas,create')
            ->name('muat-standar');
        Route::post('/mutasi', [PlottingKelasController::class, 'mutasi'])
            ->middleware('permission:plotting_kelas,edit')
            ->name('mutasi');
        Route::post('/unassign', [PlottingKelasController::class, 'unassign'])
            ->middleware('permission:plotting_kelas,edit')
            ->name('unassign');
        Route::get('/anggota/{kelas}', [PlottingKelasController::class, 'anggota'])
            ->middleware('permission:plotting_kelas,view')
            ->name('anggota');
    });

    // Menu Manajemen Pengguna & Hak Akses
    Route::prefix('pengguna')->name('pengguna.')->group(function () {

        Route::get('/', [UserController::class, 'index'])
            ->middleware('permission:pengguna,view')
            ->name('index');

        Route::get('/create', [UserController::class, 'create'])
            ->middleware('permission:pengguna,create')
            ->name('create');

        Route::post('/', [UserController::class, 'store'])
            ->middleware('permission:pengguna,create')
            ->name('store');

        Route::get('/{pengguna}/edit', [UserController::class, 'edit'])
            ->middleware('permission:pengguna,edit')
            ->name('edit');

        Route::put('/{pengguna}', [UserController::class, 'update'])
            ->middleware('permission:pengguna,edit')
            ->name('update');

        Route::post('/{pengguna}/reset-password', [UserController::class, 'resetPassword'])
            ->middleware('permission:pengguna,edit')
            ->name('resetPassword');

        Route::delete('/{pengguna}', [UserController::class, 'destroy'])
            ->middleware('permission:pengguna,delete')
            ->name('destroy');
    });

    // Menu Data Master (Ruang Kelas, Jam Pelajaran, Lembaga)
    Route::prefix('master')->name('master.')->group(function () {
        // Master Ruang Kelas (Dialihkan ke Plotting Kelas untuk integrasi 1-pintu)
        Route::prefix('kelas')->name('kelas.')->group(function () {
            Route::get('/', function () {
                return redirect()->route('plotting-kelas.index');
            })->name('index');
            Route::post('/', [PlottingKelasController::class, 'storeKelas'])->name('store');
            Route::put('/{classroom}', [PlottingKelasController::class, 'updateKelas'])->name('update');
            Route::patch('/{classroom}/toggle-status', [ClassroomController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{classroom}', [PlottingKelasController::class, 'destroyKelas'])->name('destroy');
            Route::post('/muat-standar', [PlottingKelasController::class, 'muatStandar'])->name('muat-standar');
        });

        // Master Jam Pelajaran / Time Slot
        Route::prefix('jam-pelajaran')->name('jam-pelajaran.')->group(function () {
            Route::get('/', [TimeSlotController::class, 'index'])
                ->middleware('permission:master_jam|data_master,view')
                ->name('index');
            Route::post('/', [TimeSlotController::class, 'store'])
                ->middleware('permission:master_jam|data_master,create')
                ->name('store');
            Route::put('/{jamPelajaran}', [TimeSlotController::class, 'update'])
                ->middleware('permission:master_jam|data_master,edit')
                ->name('update');
            Route::patch('/{jamPelajaran}/toggle-status', [TimeSlotController::class, 'toggleStatus'])
                ->middleware('permission:master_jam|data_master,edit')
                ->name('toggle-status');
            Route::delete('/{jamPelajaran}', [TimeSlotController::class, 'destroy'])
                ->middleware('permission:master_jam|data_master,delete')
                ->name('destroy');
            Route::post('/muat-standar', [TimeSlotController::class, 'muatStandar'])
                ->middleware('permission:master_jam|data_master,create')
                ->name('muat-standar');
        });

        // Master Lembaga Pendidikan
        Route::prefix('lembaga')->name('lembaga.')->group(function () {
            Route::get('/', [LembagaController::class, 'index'])
                ->middleware('permission:master_lembaga|data_master,view')
                ->name('index');
            Route::post('/', [LembagaController::class, 'store'])
                ->middleware('permission:master_lembaga|data_master,create')
                ->name('store');
            Route::put('/{lembaga}', [LembagaController::class, 'update'])
                ->middleware('permission:master_lembaga|data_master,edit')
                ->name('update');
            Route::patch('/{lembaga}/toggle-status', [LembagaController::class, 'toggleStatus'])
                ->middleware('permission:master_lembaga|data_master,edit')
                ->name('toggle-status');
            Route::delete('/{lembaga}', [LembagaController::class, 'destroy'])
                ->middleware('permission:master_lembaga|data_master,delete')
                ->name('destroy');
        });

        // Master Import Data Siswa
        Route::prefix('import-siswa')->name('import-siswa.')->group(function () {
            Route::get('/', [ImportSiswaController::class, 'index'])
                ->middleware('permission:master_import_siswa|data_master,view')
                ->name('index');
            Route::get('/template', [ImportSiswaController::class, 'downloadTemplate'])
                ->middleware('permission:master_import_siswa|data_master,view')
                ->name('template');
            Route::post('/', [ImportSiswaController::class, 'import'])
                ->middleware('permission:master_import_siswa|data_master,create')
                ->name('store');
        });
    });
});
