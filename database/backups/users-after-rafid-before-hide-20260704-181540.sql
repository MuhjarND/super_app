-- MariaDB dump 10.19  Distrib 10.4.27-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: persuratan_pta
-- ------------------------------------------------------
-- Server version	10.4.27-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `jabatan_id` bigint(20) unsigned DEFAULT NULL,
  `jabatan_keterangan` varchar(255) DEFAULT NULL,
  `hirarki` int(10) unsigned NOT NULL DEFAULT 999,
  `unit_id` bigint(20) unsigned DEFAULT NULL,
  `bidang_id` bigint(20) unsigned DEFAULT NULL,
  `nip` varchar(255) DEFAULT NULL,
  `no_hp` varchar(255) DEFAULT NULL,
  `status_asn` varchar(30) NOT NULL DEFAULT 'PNS',
  `tmt_pns` date DEFAULT NULL,
  `atasan_langsung_id` bigint(20) unsigned DEFAULT NULL,
  `pejabat_berwenang_id` bigint(20) unsigned DEFAULT NULL,
  `jumlah_anak` int(10) unsigned NOT NULL DEFAULT 0,
  `status_aktif_pegawai` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_jabatan_id_foreign` (`jabatan_id`),
  KEY `users_unit_id_foreign` (`unit_id`),
  KEY `users_bidang_id_foreign` (`bidang_id`),
  CONSTRAINT `users_bidang_id_foreign` FOREIGN KEY (`bidang_id`) REFERENCES `bidangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_jabatan_id_foreign` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--
-- WHERE:  id IN (170,168,148,169,154,150,166,152,158,157,162,156,159,149,161,153,160,155,167,151,165,163,164)

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (148,'Ahyas Widyatmaka','ahyaswidyatmaka','ahyaswidyatmaka@pta-papuabarat.go.id',NULL,'$2y$10$DgiE5OAPN5YMp2mfj4SEvuBEQoTo7u34tX3Jz299rxr998bLVYPBC',NULL,NULL,0,NULL,NULL,NULL,'Teknisi Sarana dan Prasarana',999,NULL,NULL,NULL,NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:20','2026-07-04 08:34:20'),(149,'Drs. H. Urip, M.H.','uriphnur','uriphnur@pta-papuabarat.go.id',NULL,'$2y$10$ERPkNfktIwXUsDHPxQYgGOhtWH/qqgTYr05s8HiymvzI9f.xaIAr2',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19640315.199303.1.003',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(150,'Dr. Drs. H. Abdul Ghofur, S.H., M.H.','abdulghofur','abdulghofur@pta-papuabarat.go.id',NULL,'$2y$10$nkjJj4mbOOdwfilZyUPt0eGsBPo/f59ya9QJ/S6BjrmCwuVYwRAgW',NULL,NULL,0,NULL,NULL,2,'Wakil',999,1,NULL,'19630301.198903.1.007',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(151,'Drs.H. MUHAMMAD UMAR, S.H., M.Sy.','muhammadumar','muhammadumar@pta-papuabarat.go.id',NULL,'$2y$10$6VuOYr699UnvovuWla1nYOoC9lGdywdflB.qhxQQVN98QEjHQe90C',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19630605.199203.1.007',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(152,'Dr. Nur Yahya, M.H.','nuryahya','nuryahya@pta-papuabarat.go.id',NULL,'$2y$10$OdW4HQFMEO5eNUSnMggz5.OuH6jz3bu3B15FpbNh/k1V.0yLTRqWa',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19650507.199203.1.007',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(153,'Drs. Makmur, M.H.','makmur','makmur@pta-papuabarat.go.id',NULL,'$2y$10$LDKsluk2DANpUOkjoHnud.jUMGoRc9KWigG3HgEvsG5fgiU/.BbPu',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19621231.199103.1.046',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(154,'Dr. Abdurrahman Masykur, SH., MH.','abdurrahmanmasykur','abdurrahmanmasykur@pta-papuabarat.go.id',NULL,'$2y$10$/RP/hqx79S6gQcUZn2M4IOw2q0ez2jlKs77j2e7VtxB7He/J/qzsW',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19620407.199203.1.002',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(155,'Drs. Muhidin, M.H.','muhidin','muhidin@pta-papuabarat.go.id',NULL,'$2y$10$jcY4VbtIDp56xJlZtDcp1eftm1.J.RfbFNBcETUomLpSFykQGS37m',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19631231.199403.1.040',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(156,'Drs. H. ABDUL GHOFUR, M.H.','abdulghofur1','abdul_ghofur@pta-papuabarat.go.id',NULL,'$2y$10$BooPFMYlhnzl1Dw15zgztuHz7dO7Fsj4FEH2RLKjtl/XQOegpIT8q',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19630211.199203.1.004',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(157,'Drs. FAISOL CHADID','faisolchadid','faisolchadid@pta-papuabarat.go.id',NULL,'$2y$10$y/x20hHvrJdzvZEJWZNw6u3jkNQHjbVAJVLWJ1NfYbzNrrltWIKMS',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19630405.199403.1.003',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(158,'Drs. Abdul Razak Payapo','abdulrazakpayapo','abdulrazakpayapo@pta-papuabarat.go.id',NULL,'$2y$10$zj8PF8GniCPdp7gAt6F57eLzDnB9rKgO6Say5mIrZRPiPinN4FY9q',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19631012.199303.1.005',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(159,'Drs. H. Abdul Kholik., M.H.','abdulkholik','abdulkholik@pta-papuabarat.go.id',NULL,'$2y$10$fJlTGF34/2PBsegbHfo8d.8RZqWL1uFtwXvUVbXcD7yn.siSFhMji',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19620612.199103.1.008',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(160,'Drs. Muhammadong, M.H.','muhammadong','muhammadong@pta-papuabarat.go.id',NULL,'$2y$10$yH.QHGG86/AgAkwQLEYAWeKxkx4cSMjsNx0N0/vK8KWeZwvE0NBXO',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19631231.198203.1.017',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:21','2026-07-04 08:34:21'),(161,'Drs. H.M. Hayat, S.H., M.H.','hmhayat','hmhayat@pta-papuabarat.go.id',NULL,'$2y$10$nd64gVg32QwlAolCcvT8I./vY7oaRwYw4ICJ0iBlIkBycBZaRQowC',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19630915.199203.1.017',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(162,'Drs. Fakhrurazi, M.H.','fakhrurazi','fakhrurazi@pta-papuabarat.go.id',NULL,'$2y$10$yHloy/5hdR6by0JeIRArE.59ZsGBKGPaAW168c2PflryiZFhJqFp2',NULL,NULL,0,NULL,NULL,4,'Panitera',999,3,NULL,'19661231.199203.1.036',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(163,'Suriakencana, S.E.','pltkasubagkepegawaian','pltkasubagkepegawaian@pta-papuabarat.go.id',NULL,'$2y$10$SDKj0bBN/up6R1sYggOhiOiTbWP4tA9SwWyISdCSE2FsHFo4rFh5y',NULL,NULL,0,NULL,NULL,9,'Kasubag Kepegawaian dan Teknologi Informasi',999,2,NULL,'19661202.200012.1.001','08114801296','PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(164,'Zilva Kurnia Aji, S.H','zilvakurniaaji','zilvakurniaaji@pta-papuabarat.go.id',NULL,'$2y$10$pqBJvT4wAOQgO6GTtdtH5.p8mvfmPpG68Da/AADuaOOTi73DJk/OK',NULL,NULL,0,NULL,NULL,NULL,'Satpam',999,2,NULL,NULL,NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(165,'Hari Supriyanto, S.T','harisupriyanto','harisupriyanto@pta-papuabarat.go.id',NULL,'$2y$10$6SEa2WqyNqHu39rueO0vAeWavpf.8Uw.Yloci5eyFi0zCz4WQ2uH6',NULL,NULL,0,NULL,NULL,NULL,'Pramubakti',999,2,NULL,NULL,NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(166,'Dr. H. Ahmad Fathoni, S.H., M.Hum.','ahmadfathoni','ahmadfathoni@pta-papuabarat.go.id',NULL,'$2y$10$SOgoz.Ke.9Og6BvYKziNUO6fcUJ1dL4.I9./7kdHSTcZPWaabLA0W',NULL,NULL,0,NULL,NULL,1,'Ketua',999,1,NULL,'19590303.198403.1.002',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(167,'Drs. Syamsul Arifin, S.H., M.H.','syamsularifin','syamsularifin@pta-papuabarat.go.id',NULL,'$2y$10$faUC411e9Z6wKRAF.gjdleSUGF28GWaNluHNwHXAroLMj60CR92Gi',NULL,NULL,0,NULL,NULL,NULL,'Hakim Tinggi',999,3,NULL,'19650504.199103.1.006',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(168,'Agus Gumbira, S.H.','agusgumbira','agusgumbira@pta-papuabarat.go.id',NULL,'$2y$10$a2rg.HGsJeS6U8qfNy6Ob.qqq/QCMyblvDb2tuVwB.rBJ8zifJL7q',NULL,NULL,0,NULL,NULL,NULL,'Panitera Pengganti',999,3,NULL,'19650818.199203.1.002',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(169,'Ahyas Widyatmaka, A.Md.','ahyasw','ahyasw@pta-papuabarat.go.id',NULL,'$2y$10$05pfZA613.Gl/.DyO4GQi.7Q2.qE6Ui7z9UZ9fStuEkMC.xsbARNi',NULL,NULL,0,NULL,NULL,NULL,'Teknisi Sarana dan Prasarana',999,2,NULL,'19910517.202012.1.006',NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22'),(170,'Admin','admin','admin@pta-papuabarat.go.id',NULL,'$2y$10$P7Qey/J7cuDHwkN4oe2DG.zviTfXGlmrl9bFVSZO3q7MNLSvLkA0q',NULL,NULL,0,NULL,NULL,NULL,'Pramubakti',999,2,NULL,NULL,NULL,'PNS',NULL,NULL,NULL,0,1,NULL,'2026-07-04 08:34:22','2026-07-04 08:34:22');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-04 18:15:40
-- MariaDB dump 10.19  Distrib 10.4.27-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: persuratan_pta
-- ------------------------------------------------------
-- Server version	10.4.27-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_user_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=766 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--
-- WHERE:  user_id IN (170,168,148,169,154,150,166,152,158,157,162,156,159,149,161,153,160,155,167,151,165,163,164)

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (743,148,21,NULL,NULL),(744,149,21,NULL,NULL),(745,150,21,NULL,NULL),(746,151,21,NULL,NULL),(747,152,21,NULL,NULL),(748,153,21,NULL,NULL),(749,154,21,NULL,NULL),(750,155,21,NULL,NULL),(751,156,21,NULL,NULL),(752,157,21,NULL,NULL),(753,158,21,NULL,NULL),(754,159,21,NULL,NULL),(755,160,21,NULL,NULL),(756,161,21,NULL,NULL),(757,162,21,NULL,NULL),(758,163,21,NULL,NULL),(759,164,21,NULL,NULL),(760,165,21,NULL,NULL),(761,166,21,NULL,NULL),(762,167,21,NULL,NULL),(763,168,21,NULL,NULL),(764,169,21,NULL,NULL),(765,170,21,NULL,NULL);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-04 18:15:40
