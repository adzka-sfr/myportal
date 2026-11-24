-- Skema aplikasi Kelola Hunian (MySQL / MariaDB).
-- Jalankan sekali pada database portal, mis. lewat phpMyAdmin atau: mysql -u root nama_db < kelola_hunian.sql
--
-- Jika sebelumnya sudah menjalankan versi lama (tabel `hunian_rooms` berisi kolom status/penghuni),
-- hapus dulu tabel lama agar skema baru terbentuk:  DROP TABLE `hunian_rooms`;
--
-- Pembagian data:
--   Master      : hunian_facilities, hunian_rooms, hunian_room_facility
--   Operasional : hunian_occupancies (penghuni/booking), hunian_maintenances (perbaikan)
--   Log         : hunian_logs (riwayat semua aktivitas, tanpa foreign key agar tetap ada saat master dihapus)

CREATE TABLE IF NOT EXISTS `hunian_facilities` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(80) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hunian_facilities_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hunian_rooms` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_number`   VARCHAR(20) NOT NULL COMMENT 'Nomor / kode kamar',
    `floor`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `room_type`     VARCHAR(60) NULL,
    `monthly_price` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Harga sewa standar per bulan (Rp)',
    `notes`         VARCHAR(500) NULL,
    `created_at`    TIMESTAMP NULL DEFAULT NULL,
    `updated_at`    TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hunian_rooms_room_number_unique` (`room_number`),
    KEY `hunian_rooms_floor_index` (`floor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hunian_room_facility` (
    `room_id`     BIGINT UNSIGNED NOT NULL,
    `facility_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`room_id`, `facility_id`),
    KEY `hunian_room_facility_facility_id_index` (`facility_id`),
    CONSTRAINT `hunian_room_facility_room_fk` FOREIGN KEY (`room_id`) REFERENCES `hunian_rooms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `hunian_room_facility_facility_fk` FOREIGN KEY (`facility_id`) REFERENCES `hunian_facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu baris per masa tinggal / booking. reserved = dipesan, active = sedang menghuni, ended = riwayat.
CREATE TABLE IF NOT EXISTS `hunian_occupancies` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id`        BIGINT UNSIGNED NOT NULL,
    `status`         ENUM('reserved','active','ended') NOT NULL DEFAULT 'active',
    `occupant_name`  VARCHAR(120) NOT NULL,
    `occupant_phone` VARCHAR(30) NULL,
    `check_in_date`  DATE NULL COMMENT 'Tanggal masuk (atau rencana masuk bila reserved)',
    `paid_until`     DATE NULL COMMENT 'Sewa terbayar sampai tanggal ini',
    `rent_price`     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Harga sewa per bulan yang disepakati',
    `notes`          VARCHAR(500) NULL,
    `ended_at`       DATE NULL,
    `created_at`     TIMESTAMP NULL DEFAULT NULL,
    `updated_at`     TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hunian_occupancies_room_status_index` (`room_id`, `status`),
    CONSTRAINT `hunian_occupancies_room_fk` FOREIGN KEY (`room_id`) REFERENCES `hunian_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kamar dianggap sedang diperbaiki selama finished_at masih NULL.
CREATE TABLE IF NOT EXISTS `hunian_maintenances` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id`     BIGINT UNSIGNED NOT NULL,
    `reason`      VARCHAR(255) NOT NULL,
    `started_at`  DATE NOT NULL,
    `finished_at` DATE NULL,
    `created_at`  TIMESTAMP NULL DEFAULT NULL,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hunian_maintenances_room_finished_index` (`room_id`, `finished_at`),
    CONSTRAINT `hunian_maintenances_room_fk` FOREIGN KEY (`room_id`) REFERENCES `hunian_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log aktivitas. Nomor kamar, nama penghuni, dan pelaku disimpan sebagai salinan (snapshot).
CREATE TABLE IF NOT EXISTS `hunian_logs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id`       BIGINT UNSIGNED NULL,
    `room_number`   VARCHAR(20) NULL,
    `action`        VARCHAR(40) NOT NULL,
    `description`   VARCHAR(500) NOT NULL,
    `occupant_name` VARCHAR(120) NULL,
    `amount`        BIGINT UNSIGNED NULL COMMENT 'Nominal pembayaran (Rp) bila ada',
    `actor_id`      BIGINT UNSIGNED NULL,
    `actor_name`    VARCHAR(120) NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hunian_logs_room_created_index` (`room_id`, `created_at`),
    KEY `hunian_logs_action_created_index` (`action`, `created_at`),
    KEY `hunian_logs_created_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Master fasilitas awal (boleh diubah dari halaman Master).
INSERT IGNORE INTO `hunian_facilities` (`name`, `created_at`, `updated_at`) VALUES
    ('Kasur', NOW(), NOW()),
    ('Lemari', NOW(), NOW()),
    ('Meja', NOW(), NOW()),
    ('Kipas angin', NOW(), NOW()),
    ('AC', NOW(), NOW()),
    ('Kamar mandi dalam', NOW(), NOW()),
    ('WiFi', NOW(), NOW());
