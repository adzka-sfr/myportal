-- Skema aplikasi Kelola Hunian (MySQL / MariaDB).
-- Jalankan sekali pada database portal, mis. lewat phpMyAdmin atau: mysql -u root nama_db < kelola_hunian.sql
--
-- Jika sebelumnya sudah menjalankan versi lama (tabel `hunian_rooms` berisi kolom status/penghuni),
-- hapus dulu tabel lama agar skema baru terbentuk:  DROP TABLE `hunian_rooms`;
--
-- Pembagian data:
--   Master      : hunian_facilities, hunian_rooms, hunian_room_facility
--   Operasional : hunian_occupancies (penghuni/booking), hunian_maintenances (perbaikan), hunian_payments (pembayaran & bukti)
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
    `tenant_id`      BIGINT UNSIGNED NULL COMMENT 'Penghuni (hunian_tenants); occupant_name/phone adalah salinan',
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

-- Pembayaran sewa. Dianggap pemasukan hanya setelah status = matched (dicocokkan dengan bukti).
-- Tanpa foreign key (seperti log) agar riwayat keuangan tetap ada walau kamar/penghuni dihapus.
-- proof_path = lokasi gambar bukti di storage/app/private; NULL bila tanpa bukti atau sudah dihapus.
CREATE TABLE IF NOT EXISTS `hunian_payments` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `occupancy_id`     BIGINT UNSIGNED NULL,
    `room_id`          BIGINT UNSIGNED NULL,
    `room_number`      VARCHAR(20) NULL,
    `occupant_name`    VARCHAR(120) NOT NULL,
    `period_type`      ENUM('daily','weekly','monthly','yearly','custom') NOT NULL DEFAULT 'monthly',
    `period_qty`       SMALLINT UNSIGNED NULL COMMENT 'Jumlah hari/minggu/bulan/tahun; NULL untuk custom',
    `period_start`     DATE NOT NULL,
    `period_end`       DATE NOT NULL COMMENT 'Terbayar sampai tanggal ini',
    `amount`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `proof_path`       VARCHAR(255) NULL,
    `proof_deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `status`           ENUM('pending','matched') NOT NULL DEFAULT 'pending',
    `matched_at`       TIMESTAMP NULL DEFAULT NULL,
    `matched_by_name`  VARCHAR(120) NULL,
    `created_at`       TIMESTAMP NULL DEFAULT NULL,
    `updated_at`       TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hunian_payments_status_matched_index` (`status`, `matched_at`),
    KEY `hunian_payments_occupancy_index` (`occupancy_id`),
    KEY `hunian_payments_room_index` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data penghuni (profil). user_id menghubungkan ke akun login portal agar penghuni bisa memakai aplikasi Penyewa.
-- Foto KTP disimpan privat di storage/app/private/hunian/ktp.
CREATE TABLE IF NOT EXISTS `hunian_tenants` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NULL,
    `full_name`      VARCHAR(120) NOT NULL,
    `nik`            CHAR(16) NOT NULL,
    `phone`          VARCHAR(30) NOT NULL,
    `ktp_address`    VARCHAR(500) NOT NULL COMMENT 'Alamat sesuai KTP',
    `ktp_photo_path` VARCHAR(255) NULL,
    `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP NULL DEFAULT NULL,
    `updated_at`     TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `hunian_tenants_nik_unique` (`nik`),
    UNIQUE KEY `hunian_tenants_user_id_unique` (`user_id`),
    KEY `hunian_tenants_active_name_index` (`is_active`, `full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Khusus instalasi yang sudah punya tabel hunian_occupancies: tambah kolom tenant_id (aman dijalankan berulang di MariaDB).
ALTER TABLE `hunian_occupancies`
    ADD COLUMN IF NOT EXISTS `tenant_id` BIGINT UNSIGNED NULL AFTER `room_id`,
    ADD INDEX IF NOT EXISTS `hunian_occupancies_tenant_index` (`tenant_id`);

-- Keluhan penghuni (ticketing). Pesan pertama = isi keluhan; foto disimpan privat di storage/app/private/hunian/tickets.
CREATE TABLE IF NOT EXISTS `hunian_tickets` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`       BIGINT UNSIGNED NOT NULL,
    `room_id`         BIGINT UNSIGNED NULL,
    `room_number`     VARCHAR(20) NULL,
    `subject`         VARCHAR(150) NOT NULL,
    `category`        VARCHAR(40) NOT NULL,
    `status`          ENUM('open','in_progress','resolved') NOT NULL DEFAULT 'open',
    `manager_unread`  TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Ada pesan baru dari penghuni',
    `tenant_unread`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ada balasan baru dari pengelola',
    `last_message_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NULL DEFAULT NULL,
    `updated_at`      TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `hunian_tickets_tenant_status_index` (`tenant_id`, `status`),
    KEY `hunian_tickets_status_last_index` (`status`, `last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hunian_ticket_messages` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id`   BIGINT UNSIGNED NOT NULL,
    `sender`      ENUM('tenant','manager') NOT NULL,
    `sender_name` VARCHAR(120) NULL,
    `body`        TEXT NULL,
    `image_path`  VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `hunian_ticket_messages_ticket_index` (`ticket_id`, `id`),
    CONSTRAINT `hunian_ticket_messages_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `hunian_tickets` (`id`) ON DELETE CASCADE
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
