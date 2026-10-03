-- =====================================================
-- DATABASE: Sistem Informasi Resep Masakan
-- Author: Siti Fatimah Nur Az-Zahra
-- =====================================================

CREATE DATABASE IF NOT EXISTS `resep_masakan` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `resep_masakan`;

-- =====================================================
-- TABEL: users
-- =====================================================
CREATE TABLE `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `foto` VARCHAR(255) DEFAULT 'default.png',
    `bio` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: kategori
-- =====================================================
CREATE TABLE `kategori` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(10) DEFAULT '🍽️',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: jenis_masakan
-- =====================================================
CREATE TABLE `jenis_masakan` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama_jenis` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: resep
-- =====================================================
CREATE TABLE `resep` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `judul` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL UNIQUE,
    `thumbnail` VARCHAR(255) DEFAULT 'default-resep.jpg',
    `deskripsi` TEXT NOT NULL,
    `id_kategori` INT(11) UNSIGNED NOT NULL,
    `id_jenis` INT(11) UNSIGNED NOT NULL,
    `tingkat_kesulitan` ENUM('mudah', 'sedang', 'sulit') NOT NULL DEFAULT 'mudah',
    `waktu_masak` INT(11) NOT NULL COMMENT 'dalam menit',
    `porsi` INT(11) NOT NULL DEFAULT 2,
    `status` ENUM('publik', 'draft') NOT NULL DEFAULT 'draft',
    `views` INT(11) UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`id_jenis`) REFERENCES `jenis_masakan`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: bahan_resep
-- =====================================================
CREATE TABLE `bahan_resep` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_resep` INT(11) UNSIGNED NOT NULL,
    `nama_bahan` VARCHAR(200) NOT NULL,
    `jumlah` VARCHAR(50) NOT NULL,
    `satuan` VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_resep`) REFERENCES `resep`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: langkah_resep
-- =====================================================
CREATE TABLE `langkah_resep` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_resep` INT(11) UNSIGNED NOT NULL,
    `urutan` INT(11) NOT NULL,
    `instruksi` TEXT NOT NULL,
    `foto_langkah` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_resep`) REFERENCES `resep`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: komentar
-- =====================================================
CREATE TABLE `komentar` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_resep` INT(11) UNSIGNED NOT NULL,
    `id_user` INT(11) UNSIGNED NOT NULL,
    `komentar` TEXT NOT NULL,
    `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_resep`) REFERENCES `resep`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: rating
-- =====================================================
CREATE TABLE `rating` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_resep` INT(11) UNSIGNED NOT NULL,
    `id_user` INT(11) UNSIGNED NOT NULL,
    `nilai` TINYINT(1) NOT NULL CHECK (`nilai` BETWEEN 1 AND 5),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_rating` (`id_resep`, `id_user`),
    FOREIGN KEY (`id_resep`) REFERENCES `resep`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- TABEL: favorit
-- =====================================================
CREATE TABLE `favorit` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_resep` INT(11) UNSIGNED NOT NULL,
    `id_user` INT(11) UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_favorit` (`id_resep`, `id_user`),
    FOREIGN KEY (`id_resep`) REFERENCES `resep`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_user`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DATA AWAL: Admin default
-- password: admin123 (sudah di-hash dengan password_hash)
-- =====================================================
INSERT INTO `users` (`nama`, `email`, `password`, `role`) VALUES 
('Administrator', 'admin@resepmasakan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =====================================================
-- DATA AWAL: Kategori
-- =====================================================
INSERT INTO `kategori` (`nama_kategori`, `slug`, `icon`) VALUES
('Ayam', 'ayam', '🍗'),
('Daging Sapi', 'daging-sapi', '🥩'),
('Ikan', 'ikan', '🐟'),
('Seafood', 'seafood', '🦐'),
('Sayuran', 'sayuran', '🥦'),
('Tahu & Tempe', 'tahu-tempe', '🟫'),
('Telur', 'telur', '🥚'),
('Kambing', 'kambing', '🐑'),
('Bebek', 'bebek', '🦆'),
('Buah-buahan', 'buah-buahan', '🍎'),
('Mie & Pasta', 'mie-pasta', '🍜'),
('Nasi', 'nasi', '🍚');

-- =====================================================
-- DATA AWAL: Jenis Masakan
-- =====================================================
INSERT INTO `jenis_masakan` (`nama_jenis`, `slug`) VALUES
('Sarapan', 'sarapan'),
('Makan Siang', 'makan-siang'),
('Makan Malam', 'makan-malam'),
('Camilan', 'camilan'),
('Minuman', 'minuman'),
('Dessert', 'dessert'),
('Sup & Soto', 'sup-soto'),
('Bakar & Panggang', 'bakar-panggang'),
('Goreng', 'goreng'),
('Rebus & Kukus', 'rebus-kukus');
