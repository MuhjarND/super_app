# Impor SQL Panduan Eviden ZI

Berkas: `panduan_eviden_zi.sql` (UTF-8). Sumber: `PANDUAN PENYUSUNAN EVIDEN.xlsx`, lembar `Interpretasi_LKE ZI`.

## Cara impor melalui phpMyAdmin

1. Pilih database aplikasi yang sudah memiliki tabel modul Progress ZI.
2. Buka **Import**, pilih `panduan_eviden_zi.sql`, lalu jalankan impor dengan encoding UTF-8.
3. Hasil pemeriksaan di akhir impor harus menunjukkan `poin_panduan_target_113 = 113` dan `poin_terpasang_target_113 = 113`.
4. Gunakan kode aplikasi terbaru agar halaman panduan membaca data SQL dan menampilkan judul **Catatan/Keterangan/Penjelasan**. Jika tampilan masih tersimpan di cache, jalankan `php artisan view:clear`.
5. Sertakan 8 gambar pada folder `public/images/progress-zi/guide/` dari paket pendamping. Berkas SQL menyimpan referensi gambarnya, sedangkan berkas gambar berada di folder tersebut.

Berkas SQL tidak menetapkan nama database. Pilih database yang benar di phpMyAdmin. Tabel dasar yang diperlukan: `zi_areas` (termasuk `group_type`), `zi_guideline_points`, `zi_guideline_sub_points`, dan `zi_evidences`. Akun impor memerlukan izin baca/tulis, membuat/mengubah tabel, serta membuat tabel sementara. Tidak memerlukan izin membuat stored routine.

## Isi data

- 14 area, 45 bagian, dan 113 poin eviden.
- `zi_evidence_guides.notes` berisi **Catatan/Keterangan/Penjelasan** dari kolom O Excel. Ada 106 poin dengan isi pada kolom tersebut; sel yang kosong tidak diisi dengan teks buatan.
- `explanation` berisi penjelasan pemenuhan dari kolom I.
- `area_guidance` dan `point_guidance` menyimpan catatan pada tingkat area/bagian.
- `supporting_data` menyimpan 24 rincian data pendukung dan `images` menyimpan 8 referensi gambar, dalam teks JSON.
- Nomor lembar/baris sumber dan SHA-256 disimpan untuk menelusuri asal data. Contoh jawaban/nilai Excel tidak menjadi hasil penilaian aplikasi.

Impor menambahkan tabel `zi_evidence_guides`, kolom pemetaan `evidence_guide_key` jika belum ada, dan `drive_url` jika belum ada. Pencocokan mendahulukan pemetaan lama, kemudian nama/pertanyaan yang sama. Nomor/kode yang sama dengan arti berbeda tidak dipindahkan ke poin lain. ID tidak ditetapkan secara tetap dan impor dapat diulang tanpa menggandakan acuan.

Kegiatan, PIC, persetujuan, lampiran, tautan dan catatan pengumpul tidak diubah. Deskripsi poin lokal yang sudah terisi dipertahankan. Isi panduan pada `zi_evidence_guides` akan diperbarui sesuai Excel saat SQL diimpor ulang.

SQL disusun untuk MySQL 5.7+ / MariaDB 10.4+. Pengujian dilakukan pada MariaDB 10.4.27: impor awal, impor ulang, mode `NO_BACKSLASH_ESCAPES`, pencocokan data lama, dan migrasi Laravel sesudah impor.

Untuk mengekspor ulang dari data acuan yang disertakan aplikasi:

```powershell
php scripts/export_zi_evidence_guide_sql.php
```

Untuk memeriksa catatan melalui SQL:

```sql
SELECT guide_key, title, notes AS `Catatan/Keterangan/Penjelasan`
FROM zi_evidence_guides
ORDER BY source_row;
```
