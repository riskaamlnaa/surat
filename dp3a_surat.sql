-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 12:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dp3a_surat`
--

-- --------------------------------------------------------

--
-- Table structure for table `surat`
--

CREATE TABLE `surat` (
  `id` int(11) NOT NULL,
  `no_surat` varchar(50) NOT NULL,
  `jenis_surat` enum('masuk','keluar') NOT NULL,
  `bidang` varchar(100) NOT NULL,
  `pengirim` varchar(100) NOT NULL,
  `tanggal_kirim` date NOT NULL,
  `tanggal_terima` date NOT NULL,
  `perihal` text NOT NULL,
  `disposisi` text DEFAULT NULL,
  `status` enum('diterima','diproses','selesai','ditolak') DEFAULT 'diterima',
  `keterangan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surat`
--

INSERT INTO `surat` (`id`, `no_surat`, `jenis_surat`, `bidang`, `pengirim`, `tanggal_kirim`, `tanggal_terima`, `perihal`, `disposisi`, `status`, `keterangan`, `file_surat`, `created_at`, `updated_at`) VALUES
(1, 'SM/001/DP3A/2026', 'masuk', 'Perlindungan Khusus Anak', 'Kementerian PPPA', '2026-01-15', '2026-01-18', 'Undangan Rapat Koordinasi', 'Kabid PKA', 'diproses', 'Segera ditindaklanjuti', NULL, '2026-05-19 02:08:39', '2026-05-19 02:08:39'),
(2, 'SK/002/DP3A/2026', 'keluar', 'Perlindungan Perempuan', 'DP3A Kab.', '2026-02-10', '2026-02-10', 'Laporan Sosialisasi', 'Kepala Dinas', 'diterima', 'Laporan triwulan I', NULL, '2026-05-19 02:08:39', '2026-05-19 02:13:50'),
(4, '00900909', 'masuk', 'Perlindungan Perempuan', 'mjo', '2026-05-19', '2026-05-19', 'dsjiodw', 'kdwijd', 'diterima', 'ooo', 'uploads/0090_1779157612.png', '2026-05-19 02:26:52', '2026-05-19 08:03:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `surat`
--
ALTER TABLE `surat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_surat` (`no_surat`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `surat`
--
ALTER TABLE `surat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
