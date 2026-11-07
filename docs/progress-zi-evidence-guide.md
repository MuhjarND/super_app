# Panduan tetap penyusunan eviden ZI

Acuan: `PANDUAN PENYUSUNAN EVIDEN.xlsx`, lembar `Interpretasi_LKE ZI`, SHA-256 `59662ec2f0c598fc2cf2c8df881f276923a57e85ad12b86d4f2fcf6cf60e8d89`.

Isi acuan disertakan sebagai JSON dalam `resources/data/progress-zi/evidence-guide.json` dan 8 gambar contoh di `public/images/progress-zi/guide/`. Jumlah hasil ekstraksi: 14 area, 45 bagian, 113 poin eviden dan 24 rincian data pendukung. Teks penjelasan, keterangan dan format penilaian dipertahankan. Contoh jawaban/nilai Excel tidak diimpor sebagai hasil penilaian aplikasi.

## Penggunaan

- **Panduan Eviden ZI** menampilkan acuan tetap tanpa formulir tambah/edit poin, sub poin, atau indikator.
- **Monitoring Kegiatan ZI** menampilkan panduan pada tiap poin dan pada formulir pengumpulan.
- Pengumpulan menggunakan judul, tautan Google Drive, dan catatan opsional. Tidak perlu mengisi atau menghubungkan indikator manual.
- Halaman review pimpinan dan ringkasan PDF menyertakan acuan poin terkait.
- Persentase progres menghitung poin yang memiliki eviden dan persetujuan pimpinan. Poin yang belum memiliki kegiatan/eviden tetap masuk penyebut. Persentase ini adalah progres penyelesaian eviden, bukan perhitungan nilai LKE berbobot.

## Penerapan

Alternatif impor langsung melalui phpMyAdmin tersedia pada `database/sql/panduan_eviden_zi.sql`, dengan petunjuk di `database/sql/README-panduan-eviden.md`. SQL menyimpan data lengkap termasuk kolom O **Catatan/Keterangan/Penjelasan** pada `zi_evidence_guides.notes`. Aplikasi mendahulukan data tabel tersebut dan memakai JSON yang disertakan sebagai cadangan.

Jika migrasi tautan Drive belum dijalankan, jalankan terlebih dahulu:

```powershell
php artisan migrate --path=database/migrations/2026_11_07_000001_add_drive_url_to_zi_evidences.php
```

Kemudian jalankan migrasi acuan. Migrasi menambahkan `evidence_guide_key` dan langsung menyinkronkan isi panduan:

```powershell
php artisan migrate --path=database/migrations/2026_11_07_000002_add_evidence_guide_to_zi_sub_points.php
php artisan migrate --path=database/migrations/2026_11_07_000003_create_zi_evidence_guides_table.php
php artisan view:clear
```

Sinkronisasi dapat diulang tanpa menggandakan acuan:

```powershell
php artisan progress-zi:sync-evidence-guide
```

Pencocokan menggunakan kelompok, judul area, bagian dan pertanyaan yang sama. Nomor/kode yang sama dengan isi berbeda tidak dianggap sebagai kecocokan. Data kegiatan, file, tautan, catatan dan relasi lama dipertahankan; poin yang tidak cocok tidak dipindahkan ke pertanyaan lain. Laporan historis tetap dapat menampilkan data lama. Rollback hanya menghapus kolom pemetaan, tidak menghapus acuan yang mungkin telah memiliki eviden.
