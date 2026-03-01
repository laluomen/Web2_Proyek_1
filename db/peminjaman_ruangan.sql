CREATE DATABASE  IF NOT EXISTS `peminjaman_ruangan` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `peminjaman_ruangan`;
-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: peminjaman_ruangan
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `fasilitas`
--

DROP TABLE IF EXISTS `fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fasilitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_fasilitas` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fasilitas`
--

LOCK TABLES `fasilitas` WRITE;
/*!40000 ALTER TABLE `fasilitas` DISABLE KEYS */;
INSERT INTO `fasilitas` VALUES
(1,'Proyektor'),
(2,'AC'),
(3,'WiFi'),
(4,'Sound System'),
(5,'Papan Tulis'),
(6,'Mikrofon'),
(7,'Kursi'),
(8,'Meja');
/*!40000 ALTER TABLE `fasilitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `log_status`
--

DROP TABLE IF EXISTS `log_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peminjaman_id` int NOT NULL,
  `status_id` int NOT NULL,
  `diubah_oleh` int DEFAULT NULL,
  `waktu` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `catatan` text,
  PRIMARY KEY (`id`),
  KEY `peminjaman_id` (`peminjaman_id`),
  KEY `status_id` (`status_id`),
  KEY `diubah_oleh` (`diubah_oleh`),
  CONSTRAINT `log_status_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `log_status_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status_peminjaman` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `log_status_ibfk_3` FOREIGN KEY (`diubah_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `log_status`
--

LOCK TABLES `log_status` WRITE;
/*!40000 ALTER TABLE `log_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `log_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peminjaman`
--

DROP TABLE IF EXISTS `peminjaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ruangan_id` int NOT NULL,
  `nama_kegiatan` varchar(150) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `jumlah_peserta` int DEFAULT NULL,
  `surat` varchar(255) DEFAULT NULL,
  `status_id` int DEFAULT '1',
  `catatan_admin` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ruangan_id` (`ruangan_id`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `peminjaman_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `status_peminjaman` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peminjaman`
--

LOCK TABLES `peminjaman` WRITE;
/*!40000 ALTER TABLE `peminjaman` DISABLE KEYS */;
/*!40000 ALTER TABLE `peminjaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruangan`
--

DROP TABLE IF EXISTS `ruangan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruangan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_ruangan` varchar(100) NOT NULL,
  `gedung` varchar(100) DEFAULT NULL,
  `kapasitas` int DEFAULT NULL,
  `deskripsi` text,
  `foto` varchar(255) DEFAULT NULL,
  `Lantai` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruangan`
--

LOCK TABLES `ruangan` WRITE;
/*!40000 ALTER TABLE `ruangan` DISABLE KEYS */;
INSERT INTO `ruangan` (`id`, `nama_ruangan`, `gedung`, `kapasitas`, `deskripsi`, `foto`, `Lantai`) VALUES
(1,'Ruang 101','Gedung A',40,'Ruang kelas standar untuk kuliah teori.','ruang101.jpg','1'),
(2,'Ruang 102','Gedung A',30,'Ruang presentasi dengan dukungan multimedia.','ruang102.jpg','1'),
(3,'Ruang 111','Gedung B',30,'Ruang diskusi dan praktikum skala kecil.','ruang111.jpg','2'),
(4,'Ruang 112','Gedung B',40,'Ruang kelas besar untuk kegiatan akademik.','ruang112.jpg','2');
/*!40000 ALTER TABLE `ruangan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruangan_foto`
--

DROP TABLE IF EXISTS `ruangan_foto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruangan_foto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ruangan_id` int NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tipe` enum('detail','cover') NOT NULL DEFAULT 'detail',
  PRIMARY KEY (`id`),
  KEY `ruangan_id` (`ruangan_id`),
  CONSTRAINT `fk_ruangan_foto_ruangan` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruangan_foto`
--

LOCK TABLES `ruangan_foto` WRITE;
/*!40000 ALTER TABLE `ruangan_foto` DISABLE KEYS */;
INSERT INTO `ruangan_foto` (`id`, `ruangan_id`, `nama_file`, `tipe`) VALUES
(1,1,'ruang101.jpg','cover'),
(2,2,'ruang102_cover.jpg','cover'),
(3,2,'ruang102_detail1.jpg','detail'),
(4,2,'ruang102_detail2.jpg','detail'),
(5,3,'ruang111.jpg','cover'),
(6,4,'ruang112.jpg','cover');
/*!40000 ALTER TABLE `ruangan_foto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ruangan_fasilitas`
--

DROP TABLE IF EXISTS `ruangan_fasilitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ruangan_fasilitas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ruangan_id` int DEFAULT NULL,
  `fasilitas_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ruangan_id` (`ruangan_id`),
  KEY `fasilitas_id` (`fasilitas_id`),
  CONSTRAINT `ruangan_fasilitas_ibfk_1` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ruangan_fasilitas_ibfk_2` FOREIGN KEY (`fasilitas_id`) REFERENCES `fasilitas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ruangan_fasilitas`
--

LOCK TABLES `ruangan_fasilitas` WRITE;
/*!40000 ALTER TABLE `ruangan_fasilitas` DISABLE KEYS */;
INSERT INTO `ruangan_fasilitas` (`ruangan_id`, `fasilitas_id`) VALUES
(1,1),(1,2),(1,3),(1,5),(1,7),(1,8),
(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),
(3,2),(3,3),(3,7),(3,8),
(4,1),(4,2),(4,3),(4,4),(4,7),(4,8);
/*!40000 ALTER TABLE `ruangan_fasilitas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `status_peminjaman`
--

DROP TABLE IF EXISTS `status_peminjaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `status_peminjaman`
--

LOCK TABLES `status_peminjaman` WRITE;
/*!40000 ALTER TABLE `status_peminjaman` DISABLE KEYS */;
INSERT INTO `status_peminjaman` VALUES (1,'Menunggu'),(2,'Disetujui'),(3,'Ditolak'),(4,'Selesai');
/*!40000 ALTER TABLE `status_peminjaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') NOT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Fakultas','admin','0192023a7bbd73250516f069df18b500','admin',NULL,'2026-02-14 04:08:43'),(5,'Admin Fasilkom','admin2','$2y$10$92IXUNpkm8QXjDqvZNxjKuKPKZP8vVJLzLTF0.DQkf2f5g3DkXzl6','admin',NULL,'2026-02-14 05:16:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


-- ini Fitur baru di 1 maret 2026: Penambahan tabel gedung dan lantai untuk mengelompokkan ruangan berdasarkan lokasi fisik. Tabel gedung menyimpan nama gedung, sedangkan tabel lantai menyimpan nomor lantai yang terkait dengan gedung tertentu. Relasi antara ruangan dan lantai dibuat melalui kolom lantai_id di tabel ruangan.

-- 1) Buat tabel master
CREATE TABLE IF NOT EXISTS gedung (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_gedung VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lantai (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gedung_id INT NOT NULL,
  nomor INT NOT NULL,
  UNIQUE (gedung_id, nomor),
  CONSTRAINT fk_lantai_gedung
    FOREIGN KEY (gedung_id) REFERENCES gedung(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Masukkan master dari data ruangan lama (gedung & Lantai masih ada)
INSERT IGNORE INTO gedung (nama_gedung)
SELECT DISTINCT r.gedung
FROM ruangan r
WHERE r.gedung IS NOT NULL AND r.gedung <> '';

INSERT IGNORE INTO lantai (gedung_id, nomor)
SELECT g.id, CAST(r.Lantai AS UNSIGNED) AS nomor
FROM ruangan r
JOIN gedung g ON g.nama_gedung = r.gedung
WHERE r.Lantai IS NOT NULL AND r.Lantai <> ''
GROUP BY g.id, CAST(r.Lantai AS UNSIGNED);

-- 3) Tambah kolom lantai_id (kalau belum ada)
-- NOTE: kalau kolom sudah ada, baris ini akan error.
-- Jadi jalankan ini hanya kalau belum ada.
ALTER TABLE ruangan
ADD COLUMN lantai_id INT NULL AFTER nama_ruangan;

-- 4) Isi ruangan.lantai_id berdasarkan data lama
UPDATE ruangan r
JOIN gedung g ON g.nama_gedung = r.gedung
JOIN lantai l ON l.gedung_id = g.id AND l.nomor = CAST(r.Lantai AS UNSIGNED)
SET r.lantai_id = l.id;

-- 5) Cek apakah masih ada yang NULL (kalau masih ada, jangan lanjut drop)
SELECT id, nama_ruangan, gedung, Lantai, lantai_id
FROM ruangan
WHERE lantai_id IS NULL;

-- 6) Tambah foreign key (kalau belum ada)
ALTER TABLE ruangan
ADD CONSTRAINT fk_ruangan_lantai
FOREIGN KEY (lantai_id) REFERENCES lantai(id)
ON DELETE RESTRICT;

-- 7) Baru hapus kolom lama (opsional, setelah yakin lantai_id sudah beres)
ALTER TABLE ruangan
DROP COLUMN gedung,
DROP COLUMN Lantai;

-- 8) Query JOIN untuk tampilan (list ruangan)
SELECT r.*,
       g.nama_gedung AS gedung,
       l.nomor AS Lantai
FROM ruangan r
LEFT JOIN lantai l ON l.id = r.lantai_id
LEFT JOIN gedung g ON g.id = l.gedung_id
ORDER BY r.nama_ruangan ASC;


--
-- Dumping events for database 'peminjaman_ruangan'
--

--
-- Dumping routines for database 'peminjaman_ruangan'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-14 13:51:05
