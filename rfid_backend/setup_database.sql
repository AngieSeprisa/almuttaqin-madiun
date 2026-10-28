-- ==========================================================
-- SETUP DATABASE RFID PRESENSI SANTRI & MULTI-LOKASI
-- Pondok Pesantren Yayasan Muttaqin Kota Madiun
-- ==========================================================

-- 1. Tambah kolom rfid_uid ke tabel siswas (jika belum ada)
SET @dbname = DATABASE();
SET @tablename = 'siswas';
SET @columnname = 'rfid_uid';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE siswas ADD COLUMN rfid_uid VARCHAR(32) NULL UNIQUE AFTER id;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Tambah kolom rfid_uid ke tabel users (jika belum ada)
SET @tablename_user = 'users';
SET @preparedStatement_user = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename_user)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE users ADD COLUMN rfid_uid VARCHAR(32) NULL UNIQUE AFTER id;"
));
PREPARE alterIfNotExistsUser FROM @preparedStatement_user;
EXECUTE alterIfNotExistsUser;
DEALLOCATE PREPARE alterIfNotExistsUser;

-- 3. Tabel master_devices
CREATE TABLE IF NOT EXISTS `master_devices` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(50) UNIQUE NOT NULL,
    `nama_lokasi` VARCHAR(100) NOT NULL,
    `kategori_area` ENUM('pondok', 'sekolah', 'dapur', 'umum') DEFAULT 'pondok',
    `status_aktif` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel master_sesis
CREATE TABLE IF NOT EXISTS `master_sesis` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `device_id_target` VARCHAR(50) NULL,
    `kode_sesi` VARCHAR(30) UNIQUE NOT NULL,
    `nama_sesi` VARCHAR(100) NOT NULL,
    `jam_mulai` TIME NOT NULL,
    `jam_selesai` TIME NOT NULL,
    `toleransi_menit` INT DEFAULT 15,
    `kategori` ENUM('sholat', 'pengajian', 'sekolah', 'makan', 'pegawai') DEFAULT 'sholat',
    `target_kategori` ENUM('semua_santri', 'kamar_khusus', 'sekolah', 'pegawai') DEFAULT 'semua_santri',
    `target_filter` VARCHAR(100) NULL COMMENT 'Contoh: Reguler, SMP, SMA, atau ALL',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`device_id_target`) REFERENCES `master_devices`(`device_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `master_sesis`
    ADD COLUMN IF NOT EXISTS `target_kategori` ENUM('semua_santri', 'kamar_khusus', 'sekolah', 'pegawai') DEFAULT 'semua_santri' AFTER `kategori`,
    ADD COLUMN IF NOT EXISTS `target_filter` VARCHAR(100) NULL COMMENT 'Contoh: Reguler, SMP, SMA, atau ALL' AFTER `target_kategori`;

-- 5. Tabel presensis (Konsolidasi Log Terpadu)
CREATE TABLE IF NOT EXISTS `presensis` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(50) NOT NULL,
    `sesi_id` BIGINT NOT NULL,
    `tipe_entitas` ENUM('santri', 'guru_pegawai') NOT NULL,
    `entitas_id` BIGINT UNSIGNED NOT NULL,
    `rfid_uid` VARCHAR(32) NOT NULL,
    `waktu_tap` DATETIME NOT NULL,
    `status_kehadiran` ENUM('tepat_waktu', 'terlambat', 'sudah_makan') DEFAULT 'tepat_waktu',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`sesi_id`) REFERENCES `master_sesis`(`id`) ON DELETE CASCADE,
    INDEX `idx_sesi_waktu` (`sesi_id`, `waktu_tap`),
    INDEX `idx_sesi_entitas` (`sesi_id`, `tipe_entitas`, `entitas_id`),
    INDEX `idx_rfid_uid` (`rfid_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel kompatibilitas lama presensi_santris
CREATE TABLE IF NOT EXISTS `presensi_santris` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `siswa_id` BIGINT UNSIGNED NOT NULL,
    `rfid_uid` VARCHAR(32) NOT NULL,
    `device_id` VARCHAR(50) NOT NULL DEFAULT 'ESP32_Presensi_Santri',
    `tanggal` DATE NOT NULL,
    `waktu` TIME NOT NULL,
    `status` ENUM('Hadir', 'Izin', 'Sakit', 'Terlambat') DEFAULT 'Hadir',
    `keterangan` VARCHAR(150) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`siswa_id`) REFERENCES `siswas`(`id`) ON DELETE CASCADE,
    INDEX `idx_rfid_uid` (`rfid_uid`),
    INDEX `idx_presensi_harian` (`tanggal`, `siswa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Inisialisasi Master Devices
INSERT INTO `master_devices` (`device_id`, `nama_lokasi`, `kategori_area`, `status_aktif`) VALUES
('ESP32_Presensi_Santri', 'Asrama Santri Kamar Reguler', 'pondok', 1),
('ESP32_MASJID', 'Masjid Pondok Al Muttaqin', 'pondok', 1),
('ESP32_SEKOLAH', 'Lobi Utama Sekolah (SMP/SMA)', 'sekolah', 1),
('ESP32_DAPUR', 'Dapur & Ruang Makan Asrama', 'dapur', 1)
ON DUPLICATE KEY UPDATE `nama_lokasi` = VALUES(`nama_lokasi`), `kategori_area` = VALUES(`kategori_area`);

-- 8. Inisialisasi Master Sesi
INSERT INTO `master_sesis` (`device_id_target`, `kode_sesi`, `nama_sesi`, `jam_mulai`, `jam_selesai`, `toleransi_menit`, `kategori`) VALUES
('ESP32_Presensi_Santri', 'REGULER_HARIAN', 'Presensi Kamar Reguler (24 Jam)', '00:00:00', '23:59:59', 15, 'sholat'),
('ESP32_MASJID', 'SUBUH', 'Sholat Subuh Berjamaah', '04:00:00', '05:30:00', 15, 'sholat'),
('ESP32_MASJID', 'DZUHUR', 'Sholat Dzuhur Berjamaah', '11:30:00', '13:00:00', 15, 'sholat'),
('ESP32_MASJID', 'ASHAR', 'Sholat Ashar Berjamaah', '14:45:00', '16:00:00', 15, 'sholat'),
('ESP32_MASJID', 'MAGHRIB', 'Sholat Maghrib & Pengajian', '17:30:00', '18:45:00', 15, 'sholat'),
('ESP32_MASJID', 'ISYA', 'Sholat Isya Berjamaah', '18:45:00', '20:30:00', 15, 'sholat'),
('ESP32_SEKOLAH', 'SEKOLAH_MASUK', 'Masuk Sekolah Pagi (SMP/SMA)', '06:30:00', '07:30:00', 15, 'sekolah'),
('ESP32_SEKOLAH', 'SEKOLAH_PULANG', 'Kepulangan Sekolah (SMP/SMA)', '13:30:00', '15:30:00', 0, 'sekolah'),
('ESP32_DAPUR', 'MAKAN_SIANG', 'Jatah Makan Siang Asrama', '11:45:00', '13:30:00', 0, 'makan'),
(NULL, 'PEGAWAI_MASUK', 'Presensi Datang Guru & Pegawai', '06:15:00', '07:30:00', 15, 'pegawai')
ON DUPLICATE KEY UPDATE `nama_sesi` = VALUES(`nama_sesi`), `jam_mulai` = VALUES(`jam_mulai`), `jam_selesai` = VALUES(`jam_selesai`);

-- 9. Sinkronisasi Data 16 Santri Kamar Reguler & Pasang Contoh UID Kartu
INSERT INTO `siswas` (`rfid_uid`, `nama_lengkap`, `kamar`, `status`, `created_at`, `updated_at`) VALUES
('C1F8EFA7', 'Moch Rokib Fastabiqul Huda', 'Reguler', 'aktif', NOW(), NOW()),
('6116F1A7', 'Aprilian Dias Saifudin', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Muhammad Hafid Wicaksono', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Moch Roichan Khayul Huda', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Umar Hadi Pamungkas', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Iza Septian Syah', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Bintang Nur Ihsan', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Muhammad Rizki Firdaus', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Angga Hutri Prasetyo', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Ahnaf Dzaki Amrullah', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Ascha Yudha Febriano', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Muhammad Rafli Rifaldo', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Muhamad Ar Royan', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Octoberlian Rifki Mahaposya', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Firman Maulana', 'Reguler', 'aktif', NOW(), NOW()),
(NULL, 'Yan Parlindungan', 'Reguler', 'aktif', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    `rfid_uid` = VALUES(`rfid_uid`),
    `kamar` = VALUES(`kamar`),
    `status` = VALUES(`status`);
