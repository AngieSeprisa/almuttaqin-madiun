<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\DataWargaController;
use App\Http\Controllers\EkskulJurnalController;
use App\Http\Controllers\GuruPondokController;
use App\Http\Controllers\GuruSekolahController;
use App\Http\Controllers\ImportSiswaController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\LembagaController;
use App\Http\Controllers\NfcInputController;
use App\Http\Controllers\PelanggaranController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\PenerimaanSantriController;
use App\Http\Controllers\PengaturanAbsensiController;
use App\Http\Controllers\PengaturanSesiController;
use App\Http\Controllers\PermissionTemplateController;
use App\Http\Controllers\PlottingKamarController;
use App\Http\Controllers\PlottingKelasController;
use App\Http\Controllers\PlottingKelasPengajianController;
use App\Http\Controllers\PlottingMapelController;
use App\Http\Controllers\PerizinanController;
use App\Http\Controllers\PerizinanHaidController;
use App\Http\Controllers\PerpustakaanController;
use App\Http\Controllers\PlottingEkskulController;
use App\Http\Controllers\PondokCapaianMateriController;
use App\Http\Controllers\PondokPengajianController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickSearchController;
use App\Http\Controllers\RekapAbsensiController;
use App\Http\Controllers\RekapEkskulController;
use App\Http\Controllers\RekapPondokController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SpmbPublicController;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\UjianController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| Halaman Publik SPMB / Penerimaan Santri (Tanpa Login)
|--------------------------------------------------------------------------
*/
Route::prefix('spmb')->name('spmb.')->group(function () {
    Route::get('/{slug}/info', [SpmbPublicController::class, 'info'])
        ->middleware('throttle:120,1')
        ->name('info');
    Route::get('/{slug}', [SpmbPublicController::class, 'show'])->name('show');
    Route::post('/{slug}', [SpmbPublicController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('store');
    Route::get('/{slug}/cek-status', [SpmbPublicController::class, 'cekStatus'])
        ->middleware('throttle:30,1')
        ->name('cek-status');
    Route::get('/{slug}/bukti/{token}', [SpmbPublicController::class, 'bukti'])->name('bukti');
    Route::get('/{slug}/bukti/{token}/cetak', [SpmbPublicController::class, 'cetak'])->name('cetak');
});

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
        Route::post('/siswa/{id}/rfid', [SiswaController::class, 'updateRfid'])->name('siswa.updateRfid');
        Route::get('/siswa-rfid/last-tap', [SiswaController::class, 'lastRfidTap'])->name('siswa.lastRfidTap');
    });

    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy'])
        ->middleware('permission:siswa,delete')
        ->name('siswa.destroy');

    // Menu Data Warga (Siswa, Guru, Lulus, Mutasi)
    Route::prefix('data-warga')->name('data-warga.')->group(function () {
        Route::get('/guru', [DataWargaController::class, 'guru'])
            ->middleware('permission:guru_warga,view')
            ->name('guru');

        Route::get('/lulus', [DataWargaController::class, 'lulus'])
            ->middleware('permission:lulus_warga,view')
            ->name('lulus');
        Route::post('/lulus', [DataWargaController::class, 'storeLulus'])
            ->middleware('permission:lulus_warga,create')
            ->name('lulus.store');
        Route::put('/lulus/{siswa}', [DataWargaController::class, 'updateLulus'])
            ->middleware('permission:lulus_warga,edit')
            ->name('lulus.update');

        Route::get('/mutasi', [DataWargaController::class, 'mutasi'])
            ->middleware('permission:mutasi_warga,view')
            ->name('mutasi');
        Route::post('/mutasi', [DataWargaController::class, 'storeMutasi'])
            ->middleware('permission:mutasi_warga,create')
            ->name('mutasi.store');
        Route::put('/mutasi/{siswa}', [DataWargaController::class, 'updateMutasi'])
            ->middleware('permission:mutasi_warga,edit')
            ->name('mutasi.update');

        Route::patch('/{siswa}/kembalikan-aktif', [DataWargaController::class, 'kembalikanAktif'])
            ->middleware('permission:lulus_warga|mutasi_warga,edit')
            ->name('kembalikan-aktif');
    });

    // Menu Khusus Input Kartu NFC
    Route::middleware('permission:nfc,view')->group(function () {
        Route::get('/nfc-input', [NfcInputController::class, 'index'])->name('nfc.index');
        Route::get('/nfc-input/check-card', [NfcInputController::class, 'checkCard'])->name('nfc.check-card');
    });

    Route::middleware('permission:nfc,create|edit')->group(function () {
        Route::post('/nfc-input/assign', [NfcInputController::class, 'assign'])->name('nfc.assign');
    });

    Route::middleware('permission:nfc,edit|delete')->group(function () {
        Route::post('/nfc-input/unpair', [NfcInputController::class, 'unpair'])->name('nfc.unpair');
    });

    // Menu Kelola Link Event Penerimaan (SPMB / Slug)
    Route::middleware('permission:pendaftaran,view')->prefix('penerimaan')->name('penerimaan.')->group(function () {
        Route::get('/', [PenerimaanSantriController::class, 'index'])->name('index');
    });

    Route::middleware('permission:pendaftaran,create')->prefix('penerimaan')->name('penerimaan.')->group(function () {
        Route::get('/create', [PenerimaanSantriController::class, 'create'])->name('create');
        Route::post('/', [PenerimaanSantriController::class, 'store'])->name('store');
    });

    Route::middleware('permission:pendaftaran,edit')->prefix('penerimaan')->name('penerimaan.')->group(function () {
        Route::get('/{id}/edit', [PenerimaanSantriController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [PenerimaanSantriController::class, 'update'])->whereNumber('id')->name('update');
        Route::patch('/{id}/toggle', [PenerimaanSantriController::class, 'toggleStatus'])->whereNumber('id')->name('toggle');
        Route::get('/{id}/formulir', [PenerimaanSantriController::class, 'editFormBuilder'])->whereNumber('id')->name('formulir.edit');
        Route::put('/{id}/formulir', [PenerimaanSantriController::class, 'updateFormBuilder'])->whereNumber('id')->name('formulir.update');
        Route::post('/{id}/formulir/reset', [PenerimaanSantriController::class, 'resetFormBuilder'])->whereNumber('id')->name('formulir.reset');
    });

    Route::delete('/penerimaan/{id}', [PenerimaanSantriController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:pendaftaran,delete')
        ->name('penerimaan.destroy');

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

    Route::delete('/pendaftaran/{id}', [PendaftaranController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware('permission:pendaftaran,delete')
        ->name('pendaftaran.destroy');

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

    // Menu Perizinan: Haid (Udzur Sholat Santriwati)
    Route::middleware('permission:perizinan_haid,view')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/haid', [PerizinanHaidController::class, 'index'])->name('haid');
    });

    Route::middleware('permission:perizinan_haid,create')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::get('/haid/tambah', [PerizinanHaidController::class, 'create'])->name('haid.create');
        Route::post('/haid', [PerizinanHaidController::class, 'store'])->name('haid.store');
    });

    Route::middleware('permission:perizinan_haid,edit')->prefix('perizinan')->name('perizinan.')->group(function () {
        Route::post('/haid/{id}/suci', [PerizinanHaidController::class, 'suci'])->name('haid.suci');
        Route::put('/haid/{id}', [PerizinanHaidController::class, 'update'])->name('haid.update');
    });

    Route::delete('/perizinan/haid/{id}', [PerizinanHaidController::class, 'destroy'])
        ->middleware('permission:perizinan_haid,delete')
        ->name('perizinan.haid.destroy');

    // Menu Presensi Guru (Scan QR dari Perangkat Guru & Layar Kiosk)
    Route::prefix('presensi')->name('presensi.')->group(function () {
        Route::get('/', [PresensiController::class, 'index'])->name('index');
        Route::post('/scan', [PresensiController::class, 'scan'])->name('scan');
        Route::post('/izin', [PresensiController::class, 'izin'])->name('izin');
        Route::get('/kiosk', [PresensiController::class, 'kiosk'])->name('kiosk');
        Route::get('/kiosk-data', [PresensiController::class, 'kioskData'])->name('kiosk-data');
    });

    // Dashboard Monitoring Real-Time Multi-Lokasi (Proyektor / TV Asrama)
    Route::middleware('permission:monitor_rfid,view')->group(function () {
        Route::get('/dashboard-presensi', [\App\Http\Controllers\PresensiDashboardController::class, 'index'])->name('dashboard-presensi');
        Route::get('/dashboard-presensi/data', [\App\Http\Controllers\PresensiDashboardController::class, 'data'])->name('dashboard-presensi.data');
    });

    // Pengaturan Sesi & Perangkat Presensi RFID
    Route::middleware('permission:pengaturan_sesi,view')->group(function () {
        Route::get('/pengaturan-sesi', [PengaturanSesiController::class, 'index'])->name('pengaturan.sesi');
        Route::get('/pengaturan-sesi/last-device', [PengaturanSesiController::class, 'lastDetectedDevice'])->name('pengaturan.sesi.last-device');
    });
    Route::middleware('permission:pengaturan_sesi,create')->group(function () {
        Route::get('/pengaturan-sesi/create', [PengaturanSesiController::class, 'create'])->name('pengaturan.sesi.create');
        Route::post('/pengaturan-sesi/store', [PengaturanSesiController::class, 'store'])->name('pengaturan.sesi.store');
        Route::post('/pengaturan-sesi/device', [PengaturanSesiController::class, 'storeDevice'])->name('pengaturan.sesi.device.store');
    });
    Route::middleware('permission:pengaturan_sesi,edit')->group(function () {
        Route::get('/pengaturan-sesi/{id}/edit', [PengaturanSesiController::class, 'edit'])->whereNumber('id')->name('pengaturan.sesi.edit');
        Route::put('/pengaturan-sesi/{id}', [PengaturanSesiController::class, 'update'])->whereNumber('id')->name('pengaturan.sesi.update');
        Route::put('/pengaturan-sesi/device/{id}', [PengaturanSesiController::class, 'updateDevice'])->whereNumber('id')->name('pengaturan.sesi.device.update');
        Route::patch('/pengaturan-sesi/device/{id}/toggle', [PengaturanSesiController::class, 'toggleDevice'])->whereNumber('id')->name('pengaturan.sesi.device.toggle');
    });
    Route::middleware('permission:pengaturan_sesi,delete')->group(function () {
        Route::post('/pengaturan-sesi/bulk-delete', [PengaturanSesiController::class, 'destroyBulk'])->name('pengaturan.sesi.bulk-destroy');
        Route::delete('/pengaturan-sesi/{id}', [PengaturanSesiController::class, 'destroy'])->whereNumber('id')->name('pengaturan.sesi.destroy');
        Route::delete('/pengaturan-sesi/device/{id}', [PengaturanSesiController::class, 'destroyDevice'])->whereNumber('id')->name('pengaturan.sesi.device.destroy');
    });

    // Menu Rekap Pondok (Sholat & Pengajian)
    Route::get('/rekap-absensi/sholat', [RekapPondokController::class, 'sholat'])
        ->middleware('permission:rekap_sholat,view')
        ->name('rekap.sholat.index');
    Route::get('/rekap-absensi/sholat/export', [RekapPondokController::class, 'exportSholat'])
        ->middleware('permission:rekap_sholat,view')
        ->name('rekap.sholat.export');

    Route::get('/rekap-absensi/pengajian', [RekapPondokController::class, 'pengajian'])
        ->middleware('permission:rekap_pengajian,view')
        ->name('rekap.pengajian.index');
    Route::get('/rekap-absensi/pengajian/export', [RekapPondokController::class, 'exportPengajian'])
        ->middleware('permission:rekap_pengajian,view')
        ->name('rekap.pengajian.export');

    // Rute kompatibilitas rekap.pondok
    Route::get('/rekap-absensi/pondok', [RekapPondokController::class, 'index'])
        ->middleware('permission:rekap_sholat|rekap_pengajian,view')
        ->name('rekap.pondok.index');
    Route::get('/rekap-absensi/pondok/export', [RekapPondokController::class, 'export'])
        ->middleware('permission:rekap_sholat|rekap_pengajian,view')
        ->name('rekap.pondok.export');

    // Ubah status kehadiran per sesi langsung dari modal rincian presensi
    Route::post('/rekap-absensi/update-status-sesi', [RekapPondokController::class, 'updateStatusSesi'])
        ->middleware('permission:rekap_sholat|rekap_pengajian,edit')
        ->name('rekap.pondok.update-status');

    Route::prefix('rekap-absensi')->name('rekap-absensi.')->group(function () {
        Route::get('/sekolah', [SekolahController::class, 'rekapAbsensi'])
            ->middleware('permission:rekap_sekolah,view')
            ->name('sekolah');
        Route::get('/guru', [RekapAbsensiController::class, 'guru'])
            ->middleware('permission:rekap_guru,view')
            ->name('guru');
        Route::get('/ekskul', [RekapEkskulController::class, 'index'])
            ->middleware('permission:rekap_ekskul,view')
            ->name('ekskul');
        Route::put('/koreksi/{id}', [RekapAbsensiController::class, 'koreksi'])
            ->middleware('permission:rekap_guru,edit')
            ->name('koreksi');
        Route::get('/export', [RekapAbsensiController::class, 'export'])
            ->middleware('permission:rekap_guru,view')
            ->name('export');
        Route::get('/cetak', [RekapAbsensiController::class, 'cetak'])
            ->middleware('permission:rekap_guru,view')
            ->name('cetak');
    });

    // Menu Sekolah (Jurnal, Rekap Jurnal, Rekap Absensi, Ekstrakurikuler)
    Route::prefix('sekolah')->name('sekolah.')->group(function () {
        Route::get('/jurnal', [SekolahController::class, 'jurnal'])
            ->middleware('permission:sekolah_jurnal,view')
            ->name('jurnal');
        Route::post('/jurnal', [SekolahController::class, 'storeJurnal'])
            ->middleware('permission:sekolah_jurnal,create|edit')
            ->name('jurnal.store');
        Route::get('/rekap-jurnal', [SekolahController::class, 'rekapJurnal'])
            ->middleware('permission:sekolah_rekap_jurnal,view')
            ->name('rekap-jurnal');
        Route::delete('/rekap-jurnal/{jurnal}', [SekolahController::class, 'destroyJurnal'])
            ->middleware('permission:sekolah_rekap_jurnal|sekolah_jurnal,delete')
            ->name('jurnal.destroy');
        Route::get('/rekap-absensi', [SekolahController::class, 'rekapAbsensi'])
            ->middleware('permission:rekap_sekolah,view')
            ->name('rekap-absensi');
        Route::get('/kelas-anggota/{kelas}', [SekolahController::class, 'getAnggotaKelas'])
            ->middleware('permission:sekolah_jurnal|sekolah_rekap_jurnal|rekap_sekolah,view')
            ->name('kelas-anggota');

        // Submenu Ekstrakurikuler (Input Jurnal, Rekap Jurnal, Siswa Dynamic List)
        Route::prefix('ekskul')->name('ekskul.')->group(function () {
            Route::get('/', [EkskulJurnalController::class, 'index'])
                ->middleware('permission:ekskul,view')
                ->name('index');
            Route::post('/', [EkskulJurnalController::class, 'store'])
                ->middleware('permission:ekskul,create|edit')
                ->name('store');
            Route::get('/rekap-jurnal', [EkskulJurnalController::class, 'rekapJurnal'])
                ->middleware('permission:ekskul,view')
                ->name('rekap-jurnal');
            Route::get('/jurnal/{jurnalEkskul}', [EkskulJurnalController::class, 'show'])
                ->middleware('permission:ekskul,view')
                ->name('jurnal.show');
            Route::delete('/jurnal/{jurnalEkskul}', [EkskulJurnalController::class, 'destroy'])
                ->middleware('permission:ekskul,delete')
                ->name('jurnal.destroy');
            Route::get('/siswa/{ekstrakurikuler}', [EkskulJurnalController::class, 'siswaByEkskul'])
                ->middleware('permission:ekskul,view')
                ->name('siswa-by-ekskul');
        });
    });

    // Menu Pondok (Daftar Materi, Target Pengajian, Jurnal Mengaji)
    Route::prefix('pondok')->name('pondok.')->group(function () {
        // 1. Daftar Materi Pondok (Al-Qur'an & Al-Hadist)
        Route::get('/materi', [PondokPengajianController::class, 'materiIndex'])
            ->middleware('permission:materi_pondok,view')
            ->name('materi.index');
        Route::post('/materi', [PondokPengajianController::class, 'storeMateri'])
            ->middleware('permission:materi_pondok,create')
            ->name('materi.store');
        Route::post('/materi/muat-standar', [PondokPengajianController::class, 'muatStandarMateri'])
            ->middleware('permission:materi_pondok,create')
            ->name('materi.muat-standar');
        Route::put('/materi/{materi}', [PondokPengajianController::class, 'updateMateri'])
            ->middleware('permission:materi_pondok,edit')
            ->name('materi.update');
        Route::delete('/materi/{materi}', [PondokPengajianController::class, 'destroyMateri'])
            ->middleware('permission:materi_pondok,delete')
            ->name('materi.destroy');

        // 2. Target Pengajian (Per Tanggal & Per Kelas Pengajian)
        Route::get('/target-pengajian', [PondokPengajianController::class, 'targetIndex'])
            ->middleware('permission:target_pengajian,view')
            ->name('target.index');
        Route::post('/target-pengajian', [PondokPengajianController::class, 'storeTarget'])
            ->middleware('permission:target_pengajian,create')
            ->name('target.store');
        Route::put('/target-pengajian/{target}', [PondokPengajianController::class, 'updateTarget'])
            ->middleware('permission:target_pengajian,edit')
            ->name('target.update');
        Route::delete('/target-pengajian/{target}', [PondokPengajianController::class, 'destroyTarget'])
            ->middleware('permission:target_pengajian,delete')
            ->name('target.destroy');

        // 3. Jurnal Mengaji & Absensi Pengajian + Rekap Jurnal
        Route::get('/jurnal-mengaji', [PondokPengajianController::class, 'jurnalIndex'])
            ->middleware('permission:jurnal_mengaji,view')
            ->name('jurnal.index');
        Route::post('/jurnal-mengaji', [PondokPengajianController::class, 'storeJurnal'])
            ->middleware('permission:jurnal_mengaji,create')
            ->name('jurnal.store');
        Route::put('/jurnal-mengaji/{jurnal}', [PondokPengajianController::class, 'updateJurnal'])
            ->middleware('permission:jurnal_mengaji,edit')
            ->name('jurnal.update');
        Route::delete('/jurnal-mengaji/{jurnal}', [PondokPengajianController::class, 'destroyJurnal'])
            ->middleware('permission:jurnal_mengaji,delete')
            ->name('jurnal.destroy');
        Route::get('/rekap-jurnal', [PondokPengajianController::class, 'rekapJurnal'])
            ->middleware('permission:jurnal_mengaji,view')
            ->name('rekap-jurnal');
        Route::get('/rekap-absensi', [RekapPondokController::class, 'pengajian'])
            ->middleware('permission:rekap_pengajian,view')
            ->name('rekap-absensi');

        // 4. Capaian Materi Makna Siswa (Al-Qur'an & Al-Hadist)
        Route::get('/capaian-materi', [PondokCapaianMateriController::class, 'index'])
            ->middleware('permission:capaian_materi,view')
            ->name('capaian.index');
        Route::get('/capaian-materi/{siswa}', [PondokCapaianMateriController::class, 'show'])
            ->whereNumber('siswa')
            ->middleware('permission:capaian_materi,view')
            ->name('capaian.show');
        Route::post('/capaian-materi/{siswa}/toggle', [PondokCapaianMateriController::class, 'toggle'])
            ->whereNumber('siswa')
            ->middleware('permission:capaian_materi,create|edit')
            ->name('capaian.toggle');
    });

    // Menu Pelanggaran (Input Pelanggaran & Rekap Pelanggaran)
    Route::prefix('pelanggaran')->name('pelanggaran.')->group(function () {
        Route::get('/input', [PelanggaranController::class, 'input'])
            ->middleware('permission:pelanggaran_input,view|create')
            ->name('input');
        Route::post('/', [PelanggaranController::class, 'store'])
            ->middleware('permission:pelanggaran_input,create')
            ->name('store');
        Route::get('/rekap', [PelanggaranController::class, 'rekap'])
            ->middleware('permission:pelanggaran_rekap,view')
            ->name('rekap');
        Route::put('/{pelanggaran}', [PelanggaranController::class, 'update'])
            ->whereNumber('pelanggaran')
            ->middleware('permission:pelanggaran_rekap,edit')
            ->name('update');
        Route::delete('/{pelanggaran}', [PelanggaranController::class, 'destroy'])
            ->whereNumber('pelanggaran')
            ->middleware('permission:pelanggaran_rekap,delete')
            ->name('destroy');
    });

    // Menu Perpustakaan (Daftar Buku & Pinjam Buku)
    Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
        // 1. Daftar Buku
        Route::get('/buku', [PerpustakaanController::class, 'bukuIndex'])
            ->middleware('permission:daftar_buku,view')
            ->name('buku.index');
        Route::get('/buku/create', [PerpustakaanController::class, 'createBuku'])
            ->middleware('permission:daftar_buku,create')
            ->name('buku.create');
        Route::get('/buku/generate-kode', [PerpustakaanController::class, 'generateKodeBuku'])
            ->middleware('permission:daftar_buku,create')
            ->name('buku.generate-kode');
        Route::post('/buku', [PerpustakaanController::class, 'storeBuku'])
            ->middleware('permission:daftar_buku,create')
            ->name('buku.store');
        Route::put('/buku/{buku}', [PerpustakaanController::class, 'updateBuku'])
            ->middleware('permission:daftar_buku,edit')
            ->name('buku.update');
        Route::delete('/buku/{buku}', [PerpustakaanController::class, 'destroyBuku'])
            ->middleware('permission:daftar_buku,delete')
            ->name('buku.destroy');

        // 2. Pinjam Buku
        Route::get('/pinjam', [PerpustakaanController::class, 'pinjamIndex'])
            ->middleware('permission:pinjam_buku,view')
            ->name('pinjam.index');
        Route::get('/pinjam/create', [PerpustakaanController::class, 'createPinjam'])
            ->middleware('permission:pinjam_buku,create')
            ->name('pinjam.create');
        Route::post('/pinjam', [PerpustakaanController::class, 'storePinjam'])
            ->middleware('permission:pinjam_buku,create')
            ->name('pinjam.store');
        Route::patch('/pinjam/{pinjam}/kembali', [PerpustakaanController::class, 'kembalikanPinjam'])
            ->middleware('permission:pinjam_buku,edit')
            ->name('pinjam.kembali');
        Route::put('/pinjam/{pinjam}', [PerpustakaanController::class, 'updatePinjam'])
            ->middleware('permission:pinjam_buku,edit')
            ->name('pinjam.update');
        Route::delete('/pinjam/{pinjam}', [PerpustakaanController::class, 'destroyPinjam'])
            ->middleware('permission:pinjam_buku,delete')
            ->name('pinjam.destroy');
    });

    // Menu Jadwal Pelajaran Multi-Jenjang (SMP & SMA)
    Route::prefix('jadwal-pelajaran')->name('jadwal-pelajaran.')->group(function () {
        Route::get('/', [JadwalPelajaranController::class, 'index'])
            ->middleware('permission:jadwal_pelajaran,view')
            ->name('index');
        Route::get('/saya', [JadwalPelajaranController::class, 'saya'])
            ->middleware('permission:jadwal_saya,view')
            ->name('saya');
        Route::post('/saya', [JadwalPelajaranController::class, 'storeSaya'])
            ->middleware('permission:jadwal_saya|jadwal_pelajaran,create|edit')
            ->name('saya.store');
        Route::delete('/{id}', [JadwalPelajaranController::class, 'destroy'])
            ->middleware('permission:jadwal_saya|jadwal_pelajaran,delete')
            ->name('destroy');
        Route::post('/toggle-lock', [JadwalPelajaranController::class, 'toggleLock'])
            ->middleware('permission:jadwal_pelajaran,edit')
            ->name('toggle-lock');
        Route::get('/cetak', [JadwalPelajaranController::class, 'cetak'])
            ->middleware('permission:jadwal_pelajaran,view')
            ->name('cetak');
        Route::get('/ajax/classes-and-subjects', [JadwalPelajaranController::class, 'ajaxClassesAndSubjects'])
            ->middleware('permission:jadwal_saya|jadwal_pelajaran,view')
            ->name('ajax-classes-subjects');
    });

    // Menu Ujian & Koreksi LJK Otomatis (OMR + QR Code)
    Route::prefix('ujian')->name('ujian.')->group(function () {
        // Submenu 1: Soal Ujian & Manajemen Soal Word
        Route::get('/soal', [UjianController::class, 'soalIndex'])
            ->middleware('permission:ujian_soal|ujian,view')
            ->name('soal');
        Route::get('/download-template', [UjianController::class, 'downloadTemplate'])
            ->middleware('permission:ujian_soal|ujian,view')
            ->name('download-template');
        Route::post('/{ujian}/upload-word', [UjianController::class, 'uploadWordSoal'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('upload-word');
        Route::post('/{ujian}/timer', [UjianController::class, 'updateTimer'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('update-timer');

        // CRUD Ujian & Input Kunci
        Route::get('/', [UjianController::class, 'index'])
            ->middleware('permission:ujian_soal|ujian,view')
            ->name('index');
        Route::get('/create', [UjianController::class, 'create'])
            ->middleware('permission:ujian_soal|ujian,create')
            ->name('create');
        Route::post('/', [UjianController::class, 'store'])
            ->middleware('permission:ujian_soal|ujian,create')
            ->name('store');
        Route::get('/{ujian}/edit', [UjianController::class, 'edit'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('edit');
        Route::put('/{ujian}', [UjianController::class, 'update'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('update');
        Route::delete('/{ujian}', [UjianController::class, 'destroy'])
            ->middleware('permission:ujian_soal|ujian,delete')
            ->name('destroy');
        Route::get('/{ujian}/kunci', [UjianController::class, 'kunci'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('kunci');
        Route::match(['post', 'put'], '/{ujian}/kunci', [UjianController::class, 'updateKunci'])
            ->middleware('permission:ujian_soal|ujian,edit')
            ->name('update-kunci');

        // Submenu 2: LJK
        Route::get('/ljk', [UjianController::class, 'ljkIndex'])
            ->middleware('permission:ujian_ljk|ujian,view')
            ->name('ljk');
        Route::post('/{ujian}/peraturan', [UjianController::class, 'updatePeraturan'])
            ->middleware('permission:ujian_ljk|ujian,edit')
            ->name('update-peraturan');
        Route::get('/{ujian}/cetak-ljk', [UjianController::class, 'cetakLjk'])
            ->middleware('permission:ujian_ljk|ujian,view')
            ->name('cetak-ljk');

        // Submenu 3: Sesi Ujian (Mode Full Screen Proyektor)
        Route::get('/sesi', [UjianController::class, 'sesiIndex'])
            ->middleware('permission:ujian_sesi|ujian,view')
            ->name('sesi');
        Route::get('/{ujian}/proyektor', [UjianController::class, 'proyektor'])
            ->middleware('permission:ujian_sesi|ujian,view')
            ->name('proyektor');

        // Submenu 4: Koreksi Ujian (OMR + QR)
        Route::get('/koreksi', [UjianController::class, 'koreksiIndex'])
            ->middleware('permission:ujian_koreksi|ujian,view')
            ->name('koreksi');
        Route::match(['get', 'post'], '/{ujian}/upload-scan', [UjianController::class, 'uploadScan'])
            ->middleware('permission:ujian_koreksi|ujian,create|edit')
            ->name('upload-scan');
        Route::post('/{ujian}/process-scan', [UjianController::class, 'processScan'])
            ->middleware('permission:ujian_koreksi|ujian,create|edit')
            ->name('process-scan');
        Route::post('/{ujian}/assign-hasil-siswa', [UjianController::class, 'assignHasilSiswa'])
            ->middleware('permission:ujian_koreksi|ujian,create|edit')
            ->name('assign-hasil-siswa');
        Route::post('/{ujian}/nilai-essay', [UjianController::class, 'saveNilaiEssay'])
            ->middleware('permission:ujian_koreksi|ujian_nilai|ujian,create|edit')
            ->name('save-essay');
        Route::get('/{ujian}/template-nilai-essay', [UjianController::class, 'downloadTemplateNilaiEssay'])
            ->middleware('permission:ujian_koreksi|ujian_nilai|ujian,view')
            ->name('template-essay');
        Route::post('/{ujian}/upload-nilai-essay', [UjianController::class, 'uploadNilaiEssay'])
            ->middleware('permission:ujian_koreksi|ujian_nilai|ujian,create|edit')
            ->name('upload-essay');

        // Submenu 5: Nilai Ujian & Analisis Soal
        Route::get('/nilai', [UjianController::class, 'nilaiIndex'])
            ->middleware('permission:ujian_nilai|ujian,view')
            ->name('nilai');
        Route::get('/{ujian}/hasil', [UjianController::class, 'hasil'])
            ->middleware('permission:ujian_nilai|ujian,view')
            ->name('hasil');
        Route::match(['post', 'put'], '/{ujian}/hasil/{hasil}/manual', [UjianController::class, 'updateHasilManual'])
            ->middleware('permission:ujian_nilai|ujian,edit')
            ->name('update-hasil-manual');
        Route::delete('/{ujian}/hasil/{hasil}', [UjianController::class, 'destroyHasil'])
            ->middleware('permission:ujian_nilai|ujian,delete')
            ->name('destroy-hasil');
        Route::get('/{ujian}/export-excel', [UjianController::class, 'exportExcel'])
            ->middleware('permission:ujian_nilai|ujian,view')
            ->name('export-excel');
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
        Route::post('/store', [GuruSekolahController::class, 'store'])
            ->middleware('permission:guru_sekolah,create')
            ->name('store');
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
        Route::delete('/{user}', [GuruSekolahController::class, 'destroy'])
            ->middleware('permission:guru_sekolah,delete')
            ->name('destroy');
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
        Route::post('/kelas-bulk-delete', [PlottingKelasController::class, 'destroyBulkKelas'])
            ->middleware('permission:plotting_kelas|master_kelas,delete')
            ->name('destroy-bulk-kelas');
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

    // Menu Pengaturan Sekolah: Plotting Ekstrakurikuler & Kelola Ekstrakurikuler
    Route::prefix('plotting-ekskul')->name('plotting-ekskul.')->group(function () {
        Route::get('/', [PlottingEkskulController::class, 'index'])
            ->middleware('permission:plotting_ekskul,view')
            ->name('index');
        Route::post('/ekskul', [PlottingEkskulController::class, 'storeEkskul'])
            ->middleware('permission:plotting_ekskul,create')
            ->name('store-ekskul');
        Route::put('/ekskul/{ekstrakurikuler}', [PlottingEkskulController::class, 'updateEkskul'])
            ->middleware('permission:plotting_ekskul,edit')
            ->name('update-ekskul');
        Route::delete('/ekskul/{ekstrakurikuler}', [PlottingEkskulController::class, 'destroyEkskul'])
            ->middleware('permission:plotting_ekskul,delete')
            ->name('destroy-ekskul');
        Route::post('/ekskul-bulk-delete', [PlottingEkskulController::class, 'destroyBulkEkskul'])
            ->middleware('permission:plotting_ekskul,delete')
            ->name('destroy-bulk-ekskul');
        Route::get('/anggota/{ekstrakurikuler}', [PlottingEkskulController::class, 'anggota'])
            ->middleware('permission:plotting_ekskul,view')
            ->name('anggota');
        Route::post('/tambah-anggota', [PlottingEkskulController::class, 'tambahAnggota'])
            ->middleware('permission:plotting_ekskul,edit')
            ->name('tambah-anggota');
        Route::post('/hapus-anggota', [PlottingEkskulController::class, 'hapusAnggota'])
            ->middleware('permission:plotting_ekskul,edit')
            ->name('hapus-anggota');
    });

    // Menu Pengaturan Pondok: Plotting Kamar & Kelola Kamar Asrama
    Route::prefix('plotting-kamar')->name('plotting-kamar.')->group(function () {
        Route::get('/', [PlottingKamarController::class, 'index'])
            ->middleware('permission:plotting_kamar,view')
            ->name('index');
        Route::post('/kamar', [PlottingKamarController::class, 'storeKamar'])
            ->middleware('permission:plotting_kamar,create')
            ->name('store-kamar');
        Route::put('/kamar/{kamar}', [PlottingKamarController::class, 'updateKamar'])
            ->middleware('permission:plotting_kamar,edit')
            ->name('update-kamar');
        Route::delete('/kamar/{kamar}', [PlottingKamarController::class, 'destroyKamar'])
            ->middleware('permission:plotting_kamar,delete')
            ->name('destroy-kamar');
        Route::post('/kamar-bulk-delete', [PlottingKamarController::class, 'destroyBulkKamar'])
            ->middleware('permission:plotting_kamar,delete')
            ->name('destroy-bulk-kamar');
        Route::post('/muat-standar', [PlottingKamarController::class, 'muatStandar'])
            ->middleware('permission:plotting_kamar,create')
            ->name('muat-standar');
        Route::post('/mutasi', [PlottingKamarController::class, 'mutasi'])
            ->middleware('permission:plotting_kamar,edit')
            ->name('mutasi');
        Route::post('/unassign', [PlottingKamarController::class, 'unassign'])
            ->middleware('permission:plotting_kamar,edit')
            ->name('unassign');
        Route::get('/anggota/{kamar}', [PlottingKamarController::class, 'anggota'])
            ->middleware('permission:plotting_kamar,view')
            ->name('anggota');
    });

    // Menu Pengaturan Pondok: Plotting Kelas Pengajian
    Route::prefix('plotting-pengajian')->name('plotting-pengajian.')->group(function () {
        Route::get('/', [PlottingKelasPengajianController::class, 'index'])
            ->middleware('permission:plotting_pengajian,view')
            ->name('index');
        Route::post('/kelas', [PlottingKelasPengajianController::class, 'storeKelas'])
            ->middleware('permission:plotting_pengajian,create')
            ->name('store-kelas');
        Route::put('/kelas/{kelas}', [PlottingKelasPengajianController::class, 'updateKelas'])
            ->middleware('permission:plotting_pengajian,edit')
            ->name('update-kelas');
        Route::delete('/kelas/{kelas}', [PlottingKelasPengajianController::class, 'destroyKelas'])
            ->middleware('permission:plotting_pengajian,delete')
            ->name('destroy-kelas');
        Route::post('/kelas-bulk-delete', [PlottingKelasPengajianController::class, 'destroyBulkKelas'])
            ->middleware('permission:plotting_pengajian,delete')
            ->name('destroy-bulk-kelas');
        Route::post('/muat-standar', [PlottingKelasPengajianController::class, 'muatStandar'])
            ->middleware('permission:plotting_pengajian,create')
            ->name('muat-standar');
        Route::post('/mutasi', [PlottingKelasPengajianController::class, 'mutasi'])
            ->middleware('permission:plotting_pengajian,edit')
            ->name('mutasi');
        Route::post('/unassign', [PlottingKelasPengajianController::class, 'unassign'])
            ->middleware('permission:plotting_pengajian,edit')
            ->name('unassign');
        Route::get('/anggota/{kelas}', [PlottingKelasPengajianController::class, 'anggota'])
            ->middleware('permission:plotting_pengajian,view')
            ->name('anggota');
    });

    // Menu Pengaturan Pondok: Plotting Guru Pondok
    Route::prefix('guru-pondok')->name('guru-pondok.')->group(function () {
        Route::get('/', [GuruPondokController::class, 'index'])
            ->middleware('permission:guru_pondok,view')
            ->name('index');
        Route::post('/store', [GuruPondokController::class, 'store'])
            ->middleware('permission:guru_pondok,create')
            ->name('store');
        Route::put('/{user}', [GuruPondokController::class, 'update'])
            ->middleware('permission:guru_pondok,edit')
            ->name('update');
        Route::delete('/{user}', [GuruPondokController::class, 'destroy'])
            ->middleware('permission:guru_pondok,delete')
            ->name('destroy');
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

    // Menu Template Hak Akses
    Route::prefix('template-akses')->name('template-akses.')->group(function () {
        Route::get('/', [PermissionTemplateController::class, 'index'])
            ->middleware('permission:pengguna,view')
            ->name('index');
        Route::get('/create', [PermissionTemplateController::class, 'create'])
            ->middleware('permission:pengguna,create')
            ->name('create');
        Route::post('/', [PermissionTemplateController::class, 'store'])
            ->middleware('permission:pengguna,create')
            ->name('store');
        Route::get('/{templateAkses}/edit', [PermissionTemplateController::class, 'edit'])
            ->middleware('permission:pengguna,edit')
            ->name('edit');
        Route::put('/{templateAkses}', [PermissionTemplateController::class, 'update'])
            ->middleware('permission:pengguna,edit')
            ->name('update');
        Route::delete('/{templateAkses}', [PermissionTemplateController::class, 'destroy'])
            ->middleware('permission:pengguna,delete')
            ->name('destroy');
    });

    // Menu Data Master (Ruang Kelas, Jam Pelajaran, Lembaga)
    Route::prefix('master')->name('master.')->group(function () {
        // Master Ruang Kelas (Dialihkan ke Plotting Kelas untuk integrasi 1-pintu)
        Route::prefix('kelas')->name('kelas.')->group(function () {
            Route::get('/', function () {
                return redirect()->route('plotting-kelas.index');
            })->middleware('permission:plotting_kelas,view')->name('index');
            Route::post('/', [PlottingKelasController::class, 'storeKelas'])
                ->middleware('permission:plotting_kelas,create')
                ->name('store');
            Route::put('/{classroom}', [PlottingKelasController::class, 'updateKelas'])
                ->middleware('permission:plotting_kelas,edit')
                ->name('update');
            Route::patch('/{classroom}/toggle-status', [ClassroomController::class, 'toggleStatus'])
                ->middleware('permission:plotting_kelas,edit')
                ->name('toggle-status');
            Route::delete('/{classroom}', [PlottingKelasController::class, 'destroyKelas'])
                ->middleware('permission:plotting_kelas,delete')
                ->name('destroy');
            Route::post('/muat-standar', [PlottingKelasController::class, 'muatStandar'])
                ->middleware('permission:plotting_kelas,create')
                ->name('muat-standar');
        });

        // Master Jam Pelajaran / Time Slot
        Route::prefix('jam-pelajaran')->name('jam-pelajaran.')->group(function () {
            Route::get('/', [TimeSlotController::class, 'index'])
                ->middleware('permission:master_jam,view')
                ->name('index');
            Route::post('/', [TimeSlotController::class, 'store'])
                ->middleware('permission:master_jam,create')
                ->name('store');
            Route::put('/{jamPelajaran}', [TimeSlotController::class, 'update'])
                ->middleware('permission:master_jam,edit')
                ->name('update');
            Route::patch('/{jamPelajaran}/toggle-status', [TimeSlotController::class, 'toggleStatus'])
                ->middleware('permission:master_jam,edit')
                ->name('toggle-status');
            Route::delete('/{jamPelajaran}', [TimeSlotController::class, 'destroy'])
                ->middleware('permission:master_jam,delete')
                ->name('destroy');
            Route::post('/muat-standar', [TimeSlotController::class, 'muatStandar'])
                ->middleware('permission:master_jam,create')
                ->name('muat-standar');
        });

        // Master Lembaga Pendidikan
        Route::prefix('lembaga')->name('lembaga.')->group(function () {
            Route::get('/', [LembagaController::class, 'index'])
                ->middleware('permission:master_lembaga,view')
                ->name('index');
            Route::post('/', [LembagaController::class, 'store'])
                ->middleware('permission:master_lembaga,create')
                ->name('store');
            Route::put('/{lembaga}', [LembagaController::class, 'update'])
                ->middleware('permission:master_lembaga,edit')
                ->name('update');
            Route::patch('/{lembaga}/toggle-status', [LembagaController::class, 'toggleStatus'])
                ->middleware('permission:master_lembaga,edit')
                ->name('toggle-status');
            Route::delete('/{lembaga}', [LembagaController::class, 'destroy'])
                ->middleware('permission:master_lembaga,delete')
                ->name('destroy');
        });

        // Master Import Data Siswa
        Route::prefix('import-siswa')->name('import-siswa.')->group(function () {
            Route::get('/', [ImportSiswaController::class, 'index'])
                ->middleware('permission:master_import_siswa,view')
                ->name('index');
            Route::get('/template', [ImportSiswaController::class, 'downloadTemplate'])
                ->middleware('permission:master_import_siswa,view')
                ->name('template');
            Route::post('/', [ImportSiswaController::class, 'import'])
                ->middleware('permission:master_import_siswa,create')
                ->name('store');
        });
    });
});
