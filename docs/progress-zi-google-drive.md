# Eviden Progress ZI melalui Google Drive

Pengumpulan eviden baru pada Monitoring Kegiatan ZI menggunakan tautan file atau folder Google Drive. Google Docs, Sheets, Slides, Drawings, dan Forms juga didukung. Pengguna tidak lagi mengunggah file atau menautkan dokumen internal sebagai eviden baru.

1. Buka Progress ZI → Monitoring Kegiatan, kemudian pilih area dan sub poin.
2. Klik **Eviden**, isi judul, tambahkan **Catatan Eviden** bila diperlukan, dan tempel tautan Google Drive. Catatan dapat berisi beberapa baris dan tampil pada daftar eviden, halaman review/verifikasi, serta ringkasan PDF.
3. Berikan akses lihat kepada akun pemeriksa melalui pengaturan **Bagikan** di Google Drive.
4. Klik **Simpan Eviden**, lalu ajukan **Review** seperti biasa.

Reviewer membuka dokumen melalui **Buka Google Drive**. Status terhubung berarti tautan tersimpan; aplikasi tidak memeriksa izin Google Drive atau otomatis menandainya valid. Verifikasi dan persetujuan tetap dilakukan oleh pemeriksa.

Bundel PDF mencantumkan tautan Google Drive yang dapat diklik. Dokumen eksternal tidak diunduh atau digabungkan oleh server. File dan referensi internal yang sudah tersimpan sebelumnya tetap dapat diakses.

Untuk penerapan pada lingkungan lain, jalankan migrasi tambahan:

```powershell
php artisan migrate --path=database/migrations/2026_11_07_000001_add_drive_url_to_zi_evidences.php
php artisan view:clear
```

Migrasi hanya menambahkan kolom nullable `drive_url`; data eviden lama dipertahankan.
