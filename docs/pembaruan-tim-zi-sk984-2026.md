# Pembaruan tim ZI berdasarkan SK 984 tahun 2026

Sumber: **984/KPTA.W31-A/SK.OT1.6/VIII/2026**, Lampiran I, halaman PDF 4-5. Susunan anggota juga sesuai bagan pada halaman 6. Keputusan ditandatangani 26 Agustus 2026; kepala lampiran mencantumkan 23 Agustus 2026.

## File dan cara menjalankan

1. Pilih database aplikasi di phpMyAdmin atau klien MySQL/MariaDB.
2. Impor `database/sql/pembaruan_tim_zi_sk984_2026.sql` sebagai satu file lengkap, pada satu koneksi.
3. Periksa result set terakhir: harus tertulis **BERHASIL**, memiliki ID cadangan, dan daftar 74 penugasan.
4. Jika tertulis **DIBATALKAN**, belum ada anggota/PIC yang diubah. Periksa hasil pemeriksaan nama dan area di bagian awal; selesaikan kecocokan akun lalu impor ulang.

SQL membutuhkan MySQL 5.7+/MariaDB 10.4+, tabel aplikasi InnoDB, tabel `users`, `zi_areas`, `zi_area_pic`, dan kolom `users.status_aktif_pegawai`. Akun database perlu izin tabel sementara dan pembuatan tabel cadangan.

SQL menggunakan nama akun aktif di server, bukan ID dari database pengembang. Perbedaan kapital, spasi, titik dan koma diabaikan. Nama lengkap beserta gelar dipakai supaya **Syamsul Bahri, S.H.I.** (area 3) tidak tertukar dengan **Drs. Syamsul Bahri, M.H.** (area 4).

Jika nama tidak ditemukan atau cocok ke beberapa akun, seluruh pembaruan dibatalkan. Isi `manual_user_id` pada baris orang terkait dengan ID akun aktif yang benar, lalu jalankan ulang file lengkap. Contoh: ganti `NULL` pada baris Drs. Erwin Romel dengan ID akun beliau yang sudah diverifikasi. File ini tidak membuat akun/login baru dan tidak mengubah nama ataupun peran global pengguna.

Database utama aplikasi tidak tersedia pada server MySQL lokal saat pembuatan file. Pada backup lokal Juli 2026, **Drs. Erwin Romel, M.H.** dan **Baida Makasar, S.Ag.** belum tercantum. Hal ini bukan kepastian kondisi server terbaru; hasil pemeriksaan SQL akan menentukan kecocokan sebenarnya.

## Cakupan

Setiap tim berikut dipasang pada area **Pengungkit dan Reform** yang namanya bersesuaian. Pemilihan memakai kelompok dan nama area, sehingga tidak bergantung pada ID area yang mungkin berbeda di server. Nama SDM dengan/tanpa kata Aparatur dan Akuntabilitas dengan/tanpa kata Kinerja didukung. Area yang tidak ditemukan atau ambigu membatalkan pembaruan.

| Area | Koordinator/PIC utama | Sekretaris | Jumlah tim termasuk keduanya |
|---|---|---|---|
| 1 - Manajemen Perubahan | Drs. Muhammad Takdir, S.H., M.H. | Drs. Erwin Romel, M.H. | 6 |
| 2 - Penataan Tatalaksana | Drs. Basyirun, M.H. | Raswin, S.H.I. | 7 |
| 3 - Penataan Sistem Manajemen SDM | Drs. Rahmat Farid, M.H. | Syamsul Bahri, S.H.I. | 6 |
| 4 - Penguatan Akuntabilitas Kinerja | Drs. Mahzumi, M.H. | Manik Rochmani, S.H. | 6 |
| 5 - Penguatan Pengawasan | Drs. Ihsan, M.H. | Khoiriyah, S.Ag., M.H. | 6 |
| 6 - Peningkatan Kualitas Pelayanan Publik | Drs. H. Masnun, S.H. | Suria Kencana, S.E. | 6 |

Seluruh nama anggota tercantum di bagian `tmp_zi984_people` dan seluruh kedudukan SK tercantum di `tmp_zi984_assignments` pada SQL. Ada **34 orang berbeda, 37 penugasan per kelompok, 74 relasi pada 12 area**.

Sesuai pilihan pengguna, **kedua area kelompok Hasil mempertahankan anggota dan PIC yang sudah ada**. Pembina, ketua tim, serta koordinator zona tidak ditambahkan ke semua area karena SK tidak menetapkan mereka sebagai anggota setiap area. Erwin Romel dan Raswin tetap masuk pada area yang secara eksplisit mencantumkan mereka.

Daftar anggota lama pada 12 area diganti sesuai SK, termasuk mengeluarkan nama yang tidak lagi tercantum. Koordinator menjadi `pic_user_id`; semua koordinator, sekretaris dan anggota tersimpan di `zi_area_pic`. Kegiatan, berkas/tautan eviden, penilaian, periode dan ID area tidak disentuh. Penanggung jawab kegiatan individual yang sudah ada tidak diubah.

## Cadangan dan pemulihan

Sebelum pembaruan berhasil, SQL menyimpan anggota dan PIC sebelumnya dalam `zi_sk984_team_backups`, dengan ID batch yang ditampilkan pada hasil impor. Menjalankan ulang impor tidak menggandakan anggota; setiap impor berhasil memiliki cadangan sendiri.

Untuk mengembalikan anggota/PIC sebelum impor, jalankan `database/sql/pemulihan_tim_zi_sk984_2026.sql`. Secara default file memulihkan batch terakhir; untuk batch tertentu, isi ID cadangan pada variabel `@zi984_restore_batch`. Pemulihan mengembalikan tim yang dicadangkan, sehingga gunakan batch yang dimaksud.

## Label AREA pada aplikasi

Perubahan tampilan memerlukan pembaruan **kode aplikasi**, selain impor SQL anggota. `ZiArea::display_code` menampilkan `PANDUAN-P-1`, `PANDUAN-R-1` dan `PANDUAN-H-i` sebagai **AREA 1**; `PANDUAN-H-ii` menjadi **AREA 2**, dan seterusnya. Header, filter, dialog, daftar area dan laporan memakai label ini.

Kode database tetap utuh karena `zi_areas.code` memiliki indeks unik lintas kelompok. Mengganti semua kode database menjadi AREA 1 akan bertabrakan antar kelompok. Kode internal masih terlihat saat mengedit master kode area; jangan menggantinya menjadi kode yang dipakai area lain. Label tampilan baru tidak membutuhkan migrasi atau perubahan indeks dan tetap bekerja setelah sinkronisasi panduan.

## Verifikasi

SQL diuji pada database MariaDB terpisah: penugasan 74 anggota, PIC koordinator, Hasil tetap, impor berulang, pembatalan saat nama hilang/nonaktif/ambigu atau area tidak lengkap, serta pemulihan cadangan. Unit test memeriksa label tanpa mengubah kode internal. Tidak ada impor yang dijalankan ke database utama atau server produksi.
