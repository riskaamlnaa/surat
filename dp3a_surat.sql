-- Buat Database
CREATE DATABASE dp3a_surat;

-- Gunakan Database
USE dp3a_surat;

-- Buat Tabel surat
CREATE TABLE surat (
  id INT(11) NOT NULL AUTO_INCREMENT,
  no_surat VARCHAR(50) NOT NULL,
  jenis_surat ENUM('masuk','keluar') NOT NULL,
  bidang VARCHAR(100) NOT NULL,
  pengirim VARCHAR(100) NOT NULL,
  tanggal_kirim DATE NOT NULL,
  tanggal_terima DATE NOT NULL,
  perihal TEXT NOT NULL,
  disposisi TEXT DEFAULT NULL,
  status ENUM('diterima','diproses','selesai','ditolak') DEFAULT 'diterima',
  keterangan TEXT DEFAULT NULL,
  file_surat VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
  PRIMARY KEY (id),
  UNIQUE KEY no_surat (no_surat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert Data Contoh (Opsional)
INSERT INTO surat (no_surat, jenis_surat, bidang, pengirim, tanggal_kirim, tanggal_terima, perihal, disposisi, status, keterangan) VALUES
('SM/001/DP3A/2026', 'masuk', 'Perlindungan Khusus Anak', 'Kementerian PPPA', '2026-01-15', '2026-01-18', 'Undangan Rapat Koordinasi', 'Kabid PKA', 'diproses', 'Segera ditindaklanjuti');