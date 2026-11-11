-- PEMULIHAN TIM ZI SEBELUM IMPOR SK 984
-- Opsional: jalankan hanya jika hendak mengembalikan anggota/PIC sebelum impor.
-- Default: cadangan impor terakhir. Ganti ekspresi MAX(id) dengan ID cadangan
-- dari result set impor bila hendak memulihkan batch tertentu.
-- Hanya 12 area yang tercatat di cadangan dipulihkan. Kelompok Hasil tetap.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @zi984_restore_batch = (SELECT MAX(id) FROM zi_sk984_team_backups WHERE record_type='batch');
START TRANSACTION;
DELETE p FROM zi_area_pic p JOIN zi_sk984_team_backups b ON b.zi_area_id=p.zi_area_id
 WHERE b.batch_id=@zi984_restore_batch AND b.record_type='area';
INSERT INTO zi_area_pic (zi_area_id,user_id,created_at,updated_at)
 SELECT b.zi_area_id,b.user_id,b.original_created_at,b.original_updated_at
 FROM zi_sk984_team_backups b JOIN users u ON u.id=b.user_id JOIN zi_areas a ON a.id=b.zi_area_id
 WHERE b.batch_id=@zi984_restore_batch AND b.record_type='member';
UPDATE zi_areas a JOIN zi_sk984_team_backups b ON b.zi_area_id=a.id AND b.record_type='area'
 LEFT JOIN users u ON u.id=b.user_id
 SET a.pic_user_id=u.id,a.updated_at=b.original_updated_at
 WHERE b.batch_id=@zi984_restore_batch;
COMMIT;
SELECT @zi984_restore_batch AS id_cadangan_dipulihkan;
