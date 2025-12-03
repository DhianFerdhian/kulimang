-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `alamat` text,
  `role` enum('admin','kuli','user') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `users`
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `username`, `password`, `email`, `nama_lengkap`, `no_telepon`, `alamat`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@kulimang.com', 'Administrator', NULL, NULL, 'admin', '2025-09-29 06:55:19'),
(2, 'kuli', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kuli@kulimang.com', 'kuli', NULL, NULL, 'kuli', '2025-09-29 06:55:19'),
(3, 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@kulimang.com', 'user', NULL, NULL, 'user', '2025-09-29 06:55:19'),
(4, 'FERDHIAN', '$2y$10$iiyo3tlNCqpMPOf0jhMhweKe8NGcXP/RJCqcRxBOTBSaOb7hU3IA2', 'ferdhian@123', 'FERDHIAN', '0987654998877', 'ABI', 'user', '2025-09-29 07:13:02');

-- --------------------------------------------------------
-- Table structure for table `kategori_pekerjaan`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `kategori_pekerjaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `harga_per_meter` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `kategori_pekerjaan`
-- --------------------------------------------------------

INSERT INTO `kategori_pekerjaan` (`id`, `nama_kategori`, `deskripsi`, `harga_per_meter`) VALUES
(1, 'Pasang Keramik', 'Pemasangan keramik lantai atau dinding', 75000.00),
(2, 'Gali Sumur', 'Penggalian sumur bor atau tradisional', 250000.00),
(3, 'Pasang Genteng', 'Pemasangan genteng atap rumah', 90000.00);

-- --------------------------------------------------------
-- Table structure for table `pekerjaan`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pekerjaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `id_kategori` int NOT NULL,
  `id_user` int NOT NULL,
  `panjang` decimal(8,2) NOT NULL,
  `lebar` decimal(8,2) NOT NULL,
  `luas` decimal(10,2) GENERATED ALWAYS AS ((`panjang` * `lebar`)) VIRTUAL,
  `total_biaya` decimal(12,2) DEFAULT NULL,
  `dp_dibayar` decimal(12,2) DEFAULT '0.00',
  `status` enum('menunggu','diproses','selesai') DEFAULT 'menunggu',
  `lokasi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_kategori` (`id_kategori`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `pekerjaan`
-- --------------------------------------------------------

INSERT INTO `pekerjaan` (`id`, `judul`, `deskripsi`, `id_kategori`, `id_user`, `panjang`, `lebar`, `total_biaya`, `dp_dibayar`, `status`, `lokasi`, `created_at`) VALUES
(1, 'pasang keramik', 'mati kau', 1, 3, 7.00, 5.00, 2625000.00, 787500.00, 'menunggu', 'jambi', '2025-09-29 07:07:01'),
(2, 'GALI SUMUR', 'KAMU HARUS GALI SUMUR SEDALAM-DALAMNYA', 2, 4, 100.00, 1.00, 25000000.00, 7500000.00, 'menunggu', 'NERAKA', '2025-09-29 07:14:33');

-- --------------------------------------------------------
-- Table structure for table `aplikasi_pekerjaan`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `aplikasi_pekerjaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_pekerjaan` int NOT NULL,
  `id_kuli` int NOT NULL,
  `pesan` text,
  `status` enum('menunggu','diterima','ditolak') DEFAULT 'menunggu',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_pekerjaan` (`id_pekerjaan`),
  KEY `id_kuli` (`id_kuli`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `aplikasi_pekerjaan`
-- --------------------------------------------------------

INSERT INTO `aplikasi_pekerjaan` (`id`, `id_pekerjaan`, `id_kuli`, `pesan`, `status`, `created_at`) VALUES
(1, 1, 2, 'males capek', 'menunggu', '2025-09-29 07:07:42'),
(2, 2, 2, 'bapak kau', 'menunggu', '2025-09-29 07:15:46');

-- --------------------------------------------------------
-- Add foreign key constraints after all tables are created
-- --------------------------------------------------------

ALTER TABLE `pekerjaan`
ADD CONSTRAINT `pekerjaan_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_pekerjaan` (`id`),
ADD CONSTRAINT `pekerjaan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`);

ALTER TABLE `aplikasi_pekerjaan`
ADD CONSTRAINT `aplikasi_pekerjaan_ibfk_1` FOREIGN KEY (`id_pekerjaan`) REFERENCES `pekerjaan` (`id`),
ADD CONSTRAINT `aplikasi_pekerjaan_ibfk_2` FOREIGN KEY (`id_kuli`) REFERENCES `users` (`id`);