# Blueprint Modul Cuti ASN

## A. Analisis Kebutuhan
### Tujuan sistem
- Mengelola pengajuan cuti ASN/PNS secara end-to-end.
- Menjamin kepatuhan terhadap PP 11/2017 jo. PP 17/2020 dan Peraturan BKN 24/2017 jo. 7/2021.
- Menyediakan approval, verifikasi dokumen, saldo, audit trail, surat keputusan, dan laporan.
- Menjaga extensibility agar dapat berkembang ke PPPK dan multi-instansi.

### Aktor
- Pegawai
- Atasan Langsung
- Verifikator Dokumen
- Admin Kepegawaian
- PPK / Pejabat Berwenang
- Super Admin

### Kebutuhan fungsional
- Master jenis cuti, policy, cuti bersama, delegasi approval.
- Pengajuan draft, submit, revisi, cancel, approve, reject, verify.
- Perhitungan saldo dan hari kerja.
- Surat persetujuan/penolakan dan laporan.
- Audit trail dan notifikasi.

### Kebutuhan non-fungsional
- Business rule di service layer.
- Auditability, authorization, upload aman, API-ready.

### Risiko dan edge cases
- Salah tafsir rule instansi.
- Konflik tanggal dan lintas tahun.
- Cuti sakit tanpa surat dokter.
- Cuti melahirkan anak ke-4.
- Pembatalan setelah approved.

## B. Analisis Aturan Bisnis
- Cuti tahunan: 12 hari/tahun, masa kerja minimal 1 tahun, saldo otomatis, carry forward configurable.
- Cuti besar: minimal 5 tahun masa kerja, max 3 bulan, sisa hangus jika < 3 bulan.
- Cuti sakit: >1 hari wajib surat dokter, verifikasi dokumen configurable.
- Cuti melahirkan: max 3 bulan, default anak 1-3, anak ke-4 configurable.
- Cuti alasan penting: alasan wajib, dokumen configurable.
- Cuti bersama: dikelola terpusat, dampak saldo configurable.
- CLTN: approval khusus, status kepegawaian dan periode nonaktif tercatat.

Hard rule:
- masa kerja, saldo, surat dokter, lock data setelah approved.
Configurable policy:
- carry forward, dampak cuti bersama, approval tambahan, dokumen wajib, anak ke-4, delegasi.

## C. Desain Database
### ERD teks
- users 1..n leave_requests
- leave_types 1..n leave_requests
- leave_requests 1..n leave_request_documents
- leave_requests 1..n leave_approvals
- leave_requests 1..n leave_audit_trails
- users 1..n leave_balances
- leave_types 1..n leave_balances
- leave_types 1..n leave_policies
- users 1..n leave_delegations
- leave_holidays berdiri sendiri
- leave_number_sequences untuk nomor surat

### Tabel inti
- leave_types
- leave_policies
- leave_requests
- leave_request_documents
- leave_approvals
- leave_balances
- leave_holidays
- leave_audit_trails
- leave_delegations
- leave_number_sequences

### Strategi audit trail
- Event/listener untuk submit, approve, reject, cancel, revise.

### Strategi nomor surat
- leave_number_sequences per tahun dan tipe surat.

### Strategi lock data
- locked_at di leave_requests.

## D. Desain Arsitektur Laravel 7
- Controller: LeaveRequestController, LeaveApprovalController
- Request: StoreLeaveRequest, UpdateLeaveRequest, ApprovalActionRequest, VerifyLeaveDocumentRequest
- Service: LeaveValidationService, LeaveApprovalService, LeaveBalanceService
- Policy: LeaveRequestPolicy
- Event: LeaveRequestSubmitted, LeaveRequestStatusChanged
- Listener: CreateLeaveApprovalSteps, RecordLeaveAuditTrail, SendLeaveStatusNotification
- Notification: LeaveRequestStatusNotification

## E. Flow Sistem
- draft -> submit -> under_review -> verified -> approved -> completed
- rejected -> revise -> submit ulang
- cancelled sebelum atau sesuai policy
- cuti bersama memengaruhi saldo menurut policy

## F. Routes
- /cuti
- /cuti/create
- /cuti/{leaveRequest}
- /cuti/{leaveRequest}/edit
- /cuti/{leaveRequest}/submit
- /cuti/{leaveRequest}/cancel
- /cuti/approval/list
- /cuti/approval/{leaveApproval}

## G. UI
- Daftar pengajuan cuti
- Form cuti
- Detail pengajuan
- Daftar approval cuti
- Master data cuti

## H. Implementasi kode awal
- Migration, model, request, service, controller, policy, blade sederhana, notification, event/listener disiapkan.
- Aktivasi schema tetap manual, tidak dijalankan otomatis.

## I. Validasi penting
- masa kerja
- saldo
- jumlah anak
- surat dokter
- bentrok tanggal
- hari kerja vs hari libur
- cuti bersama
- approval chain
- delegasi
- lock data

## J. Laporan
- saldo cuti
- pengajuan per periode
- per unit
- approval summary
- audit trail
- status dokumen

## K. Testing
- unit test service
- feature test submit/approve/reject/revise/cancel
- edge case konflik tanggal dan rule per jenis cuti

## L. Roadmap
- MVP: pengajuan, approval, saldo, audit, surat
- Lanjutan: cuti bersama penuh, export Excel, dashboard, integrasi SIMPEG, e-sign, WA/email

## Asumsi
- Fokus awal PNS, PPPK disediakan via status_asn/policy.
- Hari kerja default Senin-Jumat.
- Aktivasi modul butuh migrasi manual yang Anda setujui.
