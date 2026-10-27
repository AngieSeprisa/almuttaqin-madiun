-- ==========================================================
-- SETUP DATABASE RFID PRESENSI SANTRI
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

-- 2. Buat tabel presensi_santris
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

-- 3. Buat View `santri` agar kompatibel dengan query tabel santri maupun siswas
CREATE OR REPLACE VIEW `santri` AS
SELECT 
    `id`,
    `rfid_uid`,
    `nama_lengkap`,
    SUBSTRING_INDEX(`nama_lengkap`, ' ', 2) AS `nama_panggilan`,
    `kamar`,
    `status` AS `status_aktif`,
    `created_at`,
    `updated_at`
FROM `siswas`;

-- 4. Sinkronisasi Data 16 Santri Kamar Reguler & Pasang Contoh UID Kartu
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
