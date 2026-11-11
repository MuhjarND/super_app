-- PEMBARUAN TIM ZI - SK 984/KPTA.W31-A/SK.OT1.6/VIII/2026
-- Sumber: Lampiran I halaman PDF 4-5, dikonfirmasi dengan bagan halaman 6.
-- Jalankan pada database aplikasi yang dipilih di phpMyAdmin/MySQL/MariaDB.
-- 34 orang, 37 penugasan per kelompok = 74 relasi pada 12 area Pengungkit/Reform.
-- Kelompok Hasil tetap. ID area, kode internal, akun, peran global, kegiatan dan eviden tetap.
-- Daftar lama pada 12 area diganti tepat sesuai SK, bukan ditambahkan ke anggota lama.
-- Koordinator ditetapkan sebagai pic_user_id; sekretaris dan anggota masuk zi_area_pic.
-- Nama dibaca dari users aktif; tidak memakai ID dari komputer pengembang dan tidak membuat akun baru.
-- Bila nama tidak ditemukan/ambigu atau area tidak lengkap, SEMUA perubahan anggota dilewati.
-- Hasil pemeriksaan dan status akhir muncul sebagai result set. Jangan hanya melihat 'query sukses'.
-- Untuk nama bermasalah: isi manual_user_id pada data orang di bawah dengan ID users aktif
-- yang benar, atau perbaiki/buat akun melalui aplikasi, lalu jalankan ulang seluruh file.
-- MySQL 5.7+/MariaDB 10.4+, tabel aplikasi InnoDB dan kolom users.status_aktif_pegawai.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_people;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_assignments;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_area_names;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_users;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_area_candidates;
DROP TEMPORARY TABLE IF EXISTS tmp_zi984_areas;
CREATE TEMPORARY TABLE tmp_zi984_people (
 person_key INT PRIMARY KEY, full_name VARCHAR(255), manual_user_id BIGINT UNSIGNED NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TEMPORARY TABLE tmp_zi984_assignments (
 area_no INT, person_key INT, team_role VARCHAR(20), sort_no INT, PRIMARY KEY(area_no,person_key)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TEMPORARY TABLE tmp_zi984_area_names (
 area_no INT, area_name VARCHAR(255)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO tmp_zi984_people (person_key, full_name, manual_user_id) VALUES
  (1, 'Drs. Muhammad Takdir, S.H., M.H.', NULL),
  (2, 'Drs. Erwin Romel, M.H.', NULL),
  (3, 'Drs. Khotibul Umam', NULL),
  (4, 'Khoiriyah, S.Ag., M.H.', NULL),
  (5, 'Baida Makasar, S.Ag.', NULL),
  (6, 'Muhjar Nias Dani, S.Kom.', NULL),
  (7, 'Drs. Basyirun, M.H.', NULL),
  (8, 'Raswin, S.H.I.', NULL),
  (9, 'Drs. Komsun, S.H., M.H.E.S.', NULL),
  (10, 'Musa Sholawat, S.H.I.', NULL),
  (11, 'Ummu Mukhlisa, S.H., M.H.', NULL),
  (12, 'Yudhis Salvania Pradana, S.I.Kom.', NULL),
  (13, 'Yatino', NULL),
  (14, 'Drs. Rahmat Farid, M.H.', NULL),
  (15, 'Syamsul Bahri, S.H.I.', NULL),
  (16, 'Drs. Dindin Syarief Nurwahyudin', NULL),
  (17, 'Muhammad Lutfi Khakim, A.Md.Kom.', NULL),
  (18, 'Rafid Bahar, S.H.', NULL),
  (19, 'Drs. Mahzumi, M.H.', NULL),
  (20, 'Manik Rochmani, S.H.', NULL),
  (21, 'Drs. Syamsul Bahri, M.H.', NULL),
  (22, 'Gilang Arief Maulana, S.E.', NULL),
  (23, 'Muslim Amin, A.Md.A.B.', NULL),
  (24, 'Hamzah Syam', NULL),
  (25, 'Drs. Ihsan, M.H.', NULL),
  (26, 'Drs. Muhammad Iskandar Eko Putro, M.H.', NULL),
  (27, 'Ahmad Nur Fajri, S.H.', NULL),
  (28, 'Iksan Ohorella', NULL),
  (29, 'Drs. H. Masnun, S.H.', NULL),
  (30, 'Suria Kencana, S.E.', NULL),
  (31, 'Drs. Syafrudin Mohamad, M.H.', NULL),
  (32, 'Akbar, S.H.', NULL),
  (33, 'Said Salasa', NULL),
  (34, 'Nur Komariah, S.Si.', NULL);
INSERT INTO tmp_zi984_assignments (area_no, person_key, team_role, sort_no) VALUES
  (1, 1, 'koordinator', 1),
  (1, 2, 'sekretaris', 2),
  (1, 3, 'anggota', 3),
  (1, 4, 'anggota', 4),
  (1, 5, 'anggota', 5),
  (1, 6, 'anggota', 6),
  (2, 7, 'koordinator', 1),
  (2, 8, 'sekretaris', 2),
  (2, 9, 'anggota', 3),
  (2, 10, 'anggota', 4),
  (2, 11, 'anggota', 5),
  (2, 12, 'anggota', 6),
  (2, 13, 'anggota', 7),
  (3, 14, 'koordinator', 1),
  (3, 15, 'sekretaris', 2),
  (3, 16, 'anggota', 3),
  (3, 17, 'anggota', 4),
  (3, 6, 'anggota', 5),
  (3, 18, 'anggota', 6),
  (4, 19, 'koordinator', 1),
  (4, 20, 'sekretaris', 2),
  (4, 21, 'anggota', 3),
  (4, 22, 'anggota', 4),
  (4, 23, 'anggota', 5),
  (4, 24, 'anggota', 6),
  (5, 25, 'koordinator', 1),
  (5, 4, 'sekretaris', 2),
  (5, 26, 'anggota', 3),
  (5, 27, 'anggota', 4),
  (5, 17, 'anggota', 5),
  (5, 28, 'anggota', 6),
  (6, 29, 'koordinator', 1),
  (6, 30, 'sekretaris', 2),
  (6, 31, 'anggota', 3),
  (6, 32, 'anggota', 4),
  (6, 33, 'anggota', 5),
  (6, 34, 'anggota', 6);
INSERT INTO tmp_zi984_area_names (area_no, area_name) VALUES
  (1, 'Manajemen Perubahan'),
  (2, 'Penataan Tatalaksana'),
  (3, 'Penataan Sistem Manajemen SDM'),
  (4, 'Penguatan Akuntabilitas Kinerja'),
  (5, 'Penguatan Pengawasan'),
  (6, 'Peningkatan Kualitas Pelayanan Publik'),
  (3, 'Penataan Sistem Manajemen SDM Aparatur'),
  (4, 'Penguatan Akuntabilitas');

CREATE TEMPORARY TABLE tmp_zi984_users AS
 SELECT p.person_key, p.full_name, COUNT(u.id) AS match_count,
        IF(COUNT(u.id)=1, MIN(u.id), NULL) AS user_id,
        GROUP_CONCAT(CONCAT(u.id, ': ', u.name) ORDER BY u.id SEPARATOR ' | ') AS candidates
 FROM tmp_zi984_people p LEFT JOIN users u
   ON u.status_aktif_pegawai = 1 AND (
      (p.manual_user_id IS NOT NULL AND u.id=p.manual_user_id) OR
      (p.manual_user_id IS NULL AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(CONVERT(u.name USING utf8mb4) COLLATE utf8mb4_unicode_ci), '.', ''), ',', ''), ' ', ''), '	', ''), '', ''), '
', '') = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(CONVERT(p.full_name USING utf8mb4) COLLATE utf8mb4_unicode_ci), '.', ''), ',', ''), ' ', ''), '	', ''), '', ''), '
', ''))
   )
 GROUP BY p.person_key, p.full_name;
CREATE TEMPORARY TABLE tmp_zi984_area_candidates AS
 SELECT DISTINCT a.id AS area_id, a.group_type, n.area_no
 FROM zi_areas a JOIN tmp_zi984_area_names n
   ON REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(CONVERT(a.name USING utf8mb4) COLLATE utf8mb4_unicode_ci), '.', ''), ',', ''), ' ', ''), '	', ''), '', ''), '
', '') = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(CONVERT(n.area_name USING utf8mb4) COLLATE utf8mb4_unicode_ci), '.', ''), ',', ''), ' ', ''), '	', ''), '', ''), '
', '')
 WHERE a.group_type IN ('pengungkit','reform');
CREATE TEMPORARY TABLE tmp_zi984_areas AS
 SELECT group_type, area_no, COUNT(*) AS match_count,
        IF(COUNT(*)=1, MIN(area_id), NULL) AS area_id
 FROM tmp_zi984_area_candidates GROUP BY group_type, area_no;

-- Hasil pemeriksaan 1: harus kosong. Dua Syamsul Bahri adalah orang berbeda:
-- Syamsul Bahri, S.H.I. (area 3) dan Drs. Syamsul Bahri, M.H. (area 4).
SELECT full_name AS nama_sk, match_count AS jumlah_akun_aktif, candidates AS kandidat_akun
 FROM tmp_zi984_users WHERE match_count <> 1 ORDER BY person_key;
-- Hasil pemeriksaan 2: harus berisi tepat 12 baris dan jumlah_area = 1 semuanya.
SELECT group_type AS kelompok, area_no AS nomor_area, match_count AS jumlah_area, area_id
 FROM tmp_zi984_areas ORDER BY group_type, area_no;
SELECT SUM(match_count=1), COUNT(DISTINCT user_id) INTO @zi984_user_count, @zi984_distinct_users FROM tmp_zi984_users;
SELECT COUNT(*) INTO @zi984_area_count FROM tmp_zi984_areas WHERE match_count=1;
SET @zi984_ready = (@zi984_user_count=34 AND @zi984_area_count=12 AND @zi984_distinct_users=34);

-- Cadangan satu tabel, dibuat sebelum transaksi agar DDL tidak memutus transaksi data.
CREATE TABLE IF NOT EXISTS zi_sk984_team_backups (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NULL,
 record_type VARCHAR(10) NOT NULL,
 zi_area_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 original_created_at TIMESTAMP NULL DEFAULT NULL,
 original_updated_at TIMESTAMP NULL DEFAULT NULL,
 backup_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY zi984_backup_batch (batch_id,record_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

START TRANSACTION;
INSERT INTO zi_sk984_team_backups (record_type) SELECT 'batch' WHERE @zi984_ready=1;
SET @zi984_batch = IF(@zi984_ready=1,LAST_INSERT_ID(),NULL);
INSERT INTO zi_sk984_team_backups
 (batch_id,record_type,zi_area_id,user_id,original_created_at,original_updated_at)
 SELECT @zi984_batch,'area',a.id,a.pic_user_id,a.created_at,a.updated_at
 FROM zi_areas a JOIN tmp_zi984_areas x ON x.area_id=a.id WHERE @zi984_ready=1;
INSERT INTO zi_sk984_team_backups
 (batch_id,record_type,zi_area_id,user_id,original_created_at,original_updated_at)
 SELECT @zi984_batch,'member',p.zi_area_id,p.user_id,p.created_at,p.updated_at
 FROM zi_area_pic p JOIN tmp_zi984_areas x ON x.area_id=p.zi_area_id WHERE @zi984_ready=1;

DELETE p FROM zi_area_pic p JOIN tmp_zi984_areas x ON x.area_id=p.zi_area_id
 WHERE @zi984_ready=1;
INSERT INTO zi_area_pic (zi_area_id,user_id,created_at,updated_at)
 SELECT x.area_id,u.user_id,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP
 FROM tmp_zi984_areas x JOIN tmp_zi984_assignments t ON t.area_no=x.area_no
 JOIN tmp_zi984_users u ON u.person_key=t.person_key
 WHERE @zi984_ready=1 ORDER BY x.area_id,t.sort_no;
UPDATE zi_areas a JOIN tmp_zi984_areas x ON x.area_id=a.id
 JOIN tmp_zi984_assignments t ON t.area_no=x.area_no AND t.team_role='koordinator'
 JOIN tmp_zi984_users u ON u.person_key=t.person_key
 SET a.pic_user_id=u.user_id,a.updated_at=CURRENT_TIMESTAMP WHERE @zi984_ready=1;
COMMIT;

SELECT IF(@zi984_ready=1,'BERHASIL: 12 area diperbarui; kelompok Hasil tetap.',
 'DIBATALKAN: tidak ada anggota/PIC yang diubah. Periksa akun dan area di atas.') AS status_pembaruan,
 @zi984_batch AS id_cadangan_untuk_pemulihan;
SELECT x.group_type AS kelompok, CONCAT('AREA ',x.area_no) AS area,
 t.team_role AS kedudukan_sk, u.full_name AS nama_dalam_sk, p.user_id AS id_akun_terpasang
 FROM tmp_zi984_areas x JOIN tmp_zi984_assignments t ON t.area_no=x.area_no
 JOIN tmp_zi984_users u ON u.person_key=t.person_key
 JOIN zi_area_pic p ON p.zi_area_id=x.area_id AND p.user_id=u.user_id
 WHERE @zi984_ready=1 ORDER BY x.group_type,x.area_no,t.sort_no;
