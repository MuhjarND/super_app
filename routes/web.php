<?php

use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect()->route('login');
});

Auth::routes(['register' => false]); // Disable registration - admin adds users

Route::get('/absensi/publik/{publicCode}', 'RapatAbsensiController@publicShow')->name('rapat.absensi.public.show');
Route::post('/absensi/publik/{publicCode}', 'RapatAbsensiController@publicStore')->name('rapat.absensi.public.store');
Route::post('/absensi/publik/{publicCode}/guest', 'RapatAbsensiController@publicStoreGuest')->name('rapat.absensi.public.guest');
Route::get('/voting/publik/{publicCode}', 'VotingPublicController@show')->name('rapat.voting.public.show');
Route::post('/voting/publik/{publicCode}', 'VotingPublicController@store')->name('rapat.voting.public.store');
Route::get('/voting/publik/{publicCode}/hasil', 'VotingPublicController@results')->name('rapat.voting.public.results');
Route::get('/voting/publik/{publicCode}/stats', 'VotingPublicController@stats')->name('rapat.voting.public.stats');
Route::get('/verifikasi/ttd/{token}', 'RapatSignatureVerificationController@show')->name('rapat.signature.verify');
Route::get('/verifikasi/cuti/{leaveRequest}/{approval}', 'LeaveSignatureVerificationController@show')->name('cuti.signature.verify');
Route::get('/verifikasi/cuti/pemohon/{leaveRequest}', 'LeaveSignatureVerificationController@showApplicant')->name('cuti.signature.verify-applicant');
Route::get('/verifikasi/surat-keluar/{approval}', 'SuratKeluarSignatureVerificationController@show')->name('surat-keluar.signature.verify');
Route::get('/publik/tindak-lanjut/eviden/{token}', 'PublicFollowUpEvidenceController@show')->name('rapat.notulensi.follow-ups.eviden.public');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/home', 'DashboardController@index')->name('home');
    Route::get('/kalender-terpadu', 'IntegratedCalendarController@index')->name('calendar.integrated.index');
    Route::get('/kalender-terpadu/events', 'IntegratedCalendarController@events')->name('calendar.integrated.events');
    Route::prefix('persediaan')->name('persediaan.')->group(function () {
        Route::get('/', function () {
            abort_unless(auth()->user()->canAccessInventoryModule(), 403);

            return view('persediaan-dev.index');
        })->name('index');

        Route::get('/dev', function () {
            return redirect()->route('persediaan.index');
        })->name('dev');
    });

    // Surat Masuk
    Route::get('/surat-masuk', 'SuratMasukController@index')->name('surat-masuk.index');
    Route::post('/surat-masuk', 'SuratMasukController@store')->name('surat-masuk.store');
    Route::get('/surat-masuk/{suratMasuk}', 'SuratMasukController@show')->name('surat-masuk.show');
    Route::put('/surat-masuk/{suratMasuk}', 'SuratMasukController@update')->name('surat-masuk.update');
    Route::delete('/surat-masuk/{suratMasuk}', 'SuratMasukController@destroy')->name('surat-masuk.destroy');
    Route::get('/surat-masuk/{suratMasuk}/download', 'SuratMasukController@downloadFile')->name('surat-masuk.download');
    Route::get('/surat-masuk/{suratMasuk}/preview', 'SuratMasukController@previewFile')->name('surat-masuk.preview');
    Route::get('/api/klasifikasi', 'SuratMasukController@getKlasifikasi')->name('api.klasifikasi');

    // Surat Keluar
    Route::get('/surat-keluar', 'SuratKeluarController@index')->name('surat-keluar.index');
    Route::post('/surat-keluar', 'SuratKeluarController@store')->name('surat-keluar.store');
    Route::put('/surat-keluar/{suratKeluar}', 'SuratKeluarController@update')->name('surat-keluar.update');
    Route::delete('/surat-keluar/{suratKeluar}', 'SuratKeluarController@destroy')->name('surat-keluar.destroy');
    Route::post('/surat-keluar/{suratKeluar}/upload', 'SuratKeluarController@uploadLampiran')->name('surat-keluar.upload');
    Route::get('/surat-keluar/{suratKeluar}/file', 'SuratKeluarController@viewFile')->name('surat-keluar.file');
    Route::get('/surat-keluar/preview-nomor', 'SuratKeluarController@previewNomor')->name('surat-keluar.preview-nomor');
    Route::get('/surat-keluar-approval/{suratKeluarApproval}', 'SuratKeluarApprovalController@show')->name('surat-keluar.approval.show');
    Route::get('/surat-keluar-approval/{suratKeluarApproval}/preview', 'SuratKeluarApprovalController@preview')->name('surat-keluar.approval.preview');
    Route::post('/surat-keluar-approval/{suratKeluarApproval}/approve', 'SuratKeluarApprovalController@approve')->name('surat-keluar.approval.approve');
    Route::post('/surat-keluar-approval/{suratKeluarApproval}/reject', 'SuratKeluarApprovalController@reject')->name('surat-keluar.approval.reject');
    Route::prefix('template-surat')->name('surat-template.')->group(function () {
        Route::get('/', 'SuratTemplateController@index')->name('index');
        Route::post('/', 'SuratTemplateController@store')->name('store');
        Route::put('/{suratTemplate}', 'SuratTemplateController@update')->name('update');
        Route::post('/{slug}/preview', 'SuratTemplateController@preview')->name('preview');
        Route::post('/{slug}/surat-keluar', 'SuratTemplateController@handoffToSuratKeluar')->name('handoff');
        Route::post('/proposals', 'SuratTemplateController@storeProposal')->name('proposals.store');
        Route::post('/proposals/{proposal}/process', 'SuratTemplateController@processProposal')->name('proposals.process');
        Route::get('/sample/{type}/{id}', 'SuratTemplateController@sample')->name('sample');
    });
    Route::get('/arsip', 'ArchiveController@index')->name('arsip.index');

    // Disposisi
    Route::post('/disposisi', 'DisposisiController@store')->name('disposisi.store');
    Route::patch('/disposisi/{disposisi}/status', 'DisposisiController@updateStatus')->name('disposisi.update-status');
    Route::get('/api/disposisi/targets', 'DisposisiController@getTargets')->name('api.disposisi.targets');

    // Master data - super admin only
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::resource('users', 'Admin\UserManagementController')->except(['show']);
        Route::post('users/{user}/send-login-info', 'Admin\UserManagementController@sendLoginInfo')->name('users.send-login-info');
        Route::resource('jabatans', 'Admin\JabatanManagementController')->except(['show']);
        Route::resource('units', 'Admin\UnitManagementController')->except(['show']);
        Route::resource('bidangs', 'Admin\BidangManagementController')->except(['show']);
        Route::resource('kategori-surats', 'Admin\KategoriSuratManagementController')->except(['show']);
        Route::resource('kategori-rapats', 'Admin\KategoriRapatManagementController')->except(['show']);
        Route::resource('dasar-hukums', 'Admin\DasarHukumManagementController')->except(['show']);
    });

    Route::middleware('auth')->group(function () {
        Route::get('/approval', 'ApprovalCenterController@index')->name('approval.index');
        Route::get('/approval/history', 'ApprovalCenterController@history')->name('approval.history');
    });

    Route::prefix('cuti')->name('cuti.')->group(function () {
        Route::get('/', 'LeaveRequestController@index')->name('index');
        Route::get('/create', 'LeaveRequestController@create')->name('create');
        Route::post('/', 'LeaveRequestController@store')->name('store');

        Route::prefix('master')->name('master.')->group(function () {
            Route::get('/jenis-cuti', 'LeaveTypeManagementController@index')->name('types.index');
            Route::post('/jenis-cuti', 'LeaveTypeManagementController@store')->name('types.store');
            Route::put('/jenis-cuti/{leaveType}', 'LeaveTypeManagementController@update')->name('types.update');
            Route::delete('/jenis-cuti/{leaveType}', 'LeaveTypeManagementController@destroy')->name('types.destroy');

            Route::get('/kebijakan', 'LeavePolicyManagementController@index')->name('policies.index');
            Route::post('/kebijakan', 'LeavePolicyManagementController@store')->name('policies.store');
            Route::put('/kebijakan/{leavePolicy}', 'LeavePolicyManagementController@update')->name('policies.update');
            Route::delete('/kebijakan/{leavePolicy}', 'LeavePolicyManagementController@destroy')->name('policies.destroy');

            Route::get('/cuti-bersama', 'LeaveHolidayManagementController@index')->name('holidays.index');
            Route::post('/cuti-bersama', 'LeaveHolidayManagementController@store')->name('holidays.store');
            Route::put('/cuti-bersama/{leaveHoliday}', 'LeaveHolidayManagementController@update')->name('holidays.update');
            Route::delete('/cuti-bersama/{leaveHoliday}', 'LeaveHolidayManagementController@destroy')->name('holidays.destroy');

            Route::get('/delegasi', 'LeaveDelegationManagementController@index')->name('delegations.index');
            Route::post('/delegasi', 'LeaveDelegationManagementController@store')->name('delegations.store');
            Route::put('/delegasi/{leaveDelegation}', 'LeaveDelegationManagementController@update')->name('delegations.update');
            Route::delete('/delegasi/{leaveDelegation}', 'LeaveDelegationManagementController@destroy')->name('delegations.destroy');
        });

        Route::get('/saldo', 'LeaveReportController@balances')->name('balances.index');
        Route::get('/saldo/pdf', 'LeaveReportController@balancesPdf')->name('balances.pdf');
        Route::get('/saldo/excel', 'LeaveReportController@balancesExcel')->name('balances.excel');
        Route::get('/laporan', 'LeaveReportController@index')->name('reports.index');
        Route::get('/laporan/pdf', 'LeaveReportController@pdf')->name('reports.pdf');
        Route::get('/laporan/excel', 'LeaveReportController@excel')->name('reports.excel');

        Route::get('/approval/list', 'LeaveApprovalController@index')->name('approval.index');
        Route::get('/approval/{leaveApproval}', 'LeaveApprovalController@show')->name('approval.show');
        Route::post('/approval/{leaveApproval}/approve', 'LeaveApprovalController@approve')->name('approval.approve');
        Route::post('/approval/{leaveApproval}/reject', 'LeaveApprovalController@reject')->name('approval.reject');
        Route::post('/approval/{leaveApproval}/verify-document', 'LeaveApprovalController@verifyDocument')->name('approval.verify-document');

        Route::get('/{leaveRequest}', 'LeaveRequestController@show')->name('show');
        Route::get('/{leaveRequest}/edit', 'LeaveRequestController@edit')->name('edit');
        Route::put('/{leaveRequest}', 'LeaveRequestController@update')->name('update');
        Route::post('/{leaveRequest}/submit', 'LeaveRequestController@submit')->name('submit');
        Route::post('/{leaveRequest}/cancel', 'LeaveRequestController@cancel')->name('cancel');
        Route::post('/{leaveRequest}/revise', 'LeaveRequestController@requestRevision')->name('revise');
        Route::get('/{leaveRequest}/surat', 'LeaveRequestController@surat')->name('surat');
        Route::get('/{leaveRequest}/documents/{document}', 'LeaveRequestController@document')->name('documents.show');
    });

    Route::prefix('progress-zi')->name('progress-zi.')->group(function () {
        Route::get('/', 'ProgressZiDashboardController@index')->name('dashboard');
        Route::get('/dashboard', 'ProgressZiDashboardController@index')->name('index');
        Route::get('/guidelines', 'ProgressZiGuidelineController@index')->name('guidelines.index');
        Route::post('/guidelines/areas/{ziArea}/points', 'ProgressZiGuidelineController@storePoint')->name('guidelines.points.store');
        Route::put('/guidelines/points/{ziGuidelinePoint}', 'ProgressZiGuidelineController@updatePoint')->name('guidelines.points.update');
        Route::delete('/guidelines/points/{ziGuidelinePoint}', 'ProgressZiGuidelineController@destroyPoint')->name('guidelines.points.destroy');
        Route::post('/guidelines/points/{ziGuidelinePoint}/sub-points', 'ProgressZiGuidelineController@storeSubPoint')->name('guidelines.sub-points.store');
        Route::put('/guidelines/sub-points/{ziGuidelineSubPoint}', 'ProgressZiGuidelineController@updateSubPoint')->name('guidelines.sub-points.update');
        Route::delete('/guidelines/sub-points/{ziGuidelineSubPoint}', 'ProgressZiGuidelineController@destroySubPoint')->name('guidelines.sub-points.destroy');
        Route::post('/guidelines/sub-points/{ziGuidelineSubPoint}/indicators', 'ProgressZiGuidelineController@storeIndicator')->name('guidelines.indicators.store');
        Route::put('/guidelines/indicators/{ziGuidelineIndicator}', 'ProgressZiGuidelineController@updateIndicator')->name('guidelines.indicators.update');
        Route::delete('/guidelines/indicators/{ziGuidelineIndicator}', 'ProgressZiGuidelineController@destroyIndicator')->name('guidelines.indicators.destroy');
        Route::get('/verifications', 'ProgressZiDashboardController@verifications')->name('verifications.index');
        Route::get('/verifications/pdf', 'ProgressZiDashboardController@verificationsPdf')->name('verifications.pdf');
        Route::get('/verifications/excel', 'ProgressZiDashboardController@verificationsExcel')->name('verifications.excel');
        Route::get('/reports', 'ProgressZiReportController@index')->name('reports.index');
        Route::get('/reports/matrix', 'ProgressZiReportController@matrix')->name('reports.matrix');
        Route::get('/reports/pdf', 'ProgressZiReportController@pdf')->name('reports.pdf');
        Route::get('/reports/excel', 'ProgressZiReportController@excel')->name('reports.excel');

        Route::get('/periods', 'ProgressZiMasterController@periods')->name('periods.index');
        Route::post('/periods', 'ProgressZiMasterController@storePeriod')->name('periods.store');
        Route::put('/periods/{ziPeriod}', 'ProgressZiMasterController@updatePeriod')->name('periods.update');

        Route::get('/areas', 'ProgressZiMasterController@areas')->name('areas.index');
        Route::post('/areas', 'ProgressZiMasterController@storeArea')->name('areas.store');
        Route::put('/areas/{ziArea}', 'ProgressZiMasterController@updateArea')->name('areas.update');

        Route::get('/activities', 'ProgressZiMasterController@activities')->name('activities.index');
        Route::post('/activities', 'ProgressZiMasterController@storeActivity')->name('activities.store');
        Route::put('/activities/{ziActivity}', 'ProgressZiMasterController@updateActivity')->name('activities.update');
        Route::get('/activities/{ziActivity}', 'ProgressZiMasterController@showActivity')->name('activities.show');
        Route::post('/activities/{ziActivity}/submit-review', 'ProgressZiMasterController@submitLeadershipReview')->name('activities.submit-review');
        Route::post('/activities/{ziActivity}/evidences', 'ProgressZiRealizationController@storeEvidenceFromActivity')->name('activities.evidences.store');
        Route::post('/sub-points/{ziGuidelineSubPoint}/evidences', 'ProgressZiRealizationController@storeEvidenceFromSubPoint')->name('sub-points.evidences.store');
        Route::post('/activities/{ziActivity}/indicators', 'ProgressZiMasterController@storeIndicator')->name('indicators.store');
        Route::put('/indicators/{ziIndicator}', 'ProgressZiMasterController@updateIndicator')->name('indicators.update');

        Route::post('/activities/{ziActivity}/realizations', 'ProgressZiRealizationController@store')->name('realizations.store');
        Route::put('/realizations/{ziRealization}', 'ProgressZiRealizationController@update')->name('realizations.update');
        Route::post('/realizations/{ziRealization}/evidences', 'ProgressZiRealizationController@storeEvidence')->name('evidences.store');
        Route::post('/indicators/{ziIndicator}/review', 'ProgressZiRealizationController@reviewIndicator')->name('indicators.review');
        Route::post('/evidences/{ziEvidence}/review', 'ProgressZiRealizationController@reviewEvidence')->name('evidences.review');
        Route::get('/evidences/{ziEvidence}/file', 'ProgressZiRealizationController@file')->name('evidences.file');
        Route::get('/activities/{ziActivity}/evidences/bundle', 'ProgressZiRealizationController@bundle')->name('activities.evidences.bundle');
        Route::get('/approvals/{ziActivityApproval}', 'ProgressZiApprovalController@show')->name('approvals.show');
        Route::get('/approvals/{ziActivityApproval}/bundle', 'ProgressZiApprovalController@bundle')->name('approvals.bundle');
        Route::post('/approvals/{ziActivityApproval}/approve', 'ProgressZiApprovalController@approve')->name('approvals.approve');
        Route::post('/approvals/{ziActivityApproval}/reject', 'ProgressZiApprovalController@reject')->name('approvals.reject');
    });

    Route::prefix('rapat')->name('rapat.')->middleware('role:admin,operator,notulis,peserta,approval,protokoler,super_admin')->group(function () {
        Route::get('/', 'RapatController@index')->name('index');
        Route::get('/preview-nomor', 'RapatController@previewNomorUndangan')->middleware('role:admin,operator,super_admin')->name('preview-nomor');
        Route::post('/', 'RapatController@store')->middleware('role:admin,operator,super_admin')->name('store');
        Route::put('/{rapat}', 'RapatController@update')->middleware('role:admin,operator,super_admin')->name('update');
        Route::delete('/{rapat}', 'RapatController@destroy')->middleware('role:admin,operator,super_admin')->name('destroy');
        Route::get('/{rapat}/lampiran', 'RapatController@lampiran')->name('lampiran');
        Route::get('/{rapat}/undangan', 'RapatController@previewUndangan')->name('undangan.preview');

        Route::prefix('notulensi')->middleware('role:admin,operator,notulis,super_admin')->name('notulensi.')->group(function () {
            Route::get('/', 'RapatNotulensiController@index')->name('index');
            Route::get('/create/{rapat}', 'RapatNotulensiController@create')->name('create');
            Route::post('/{rapat}', 'RapatNotulensiController@store')->name('store');
            Route::post('/{rapat}/upload', 'RapatNotulensiController@uploadFromRapat')->name('upload-from-rapat');
            Route::post('/{rapat}/skip', 'RapatNotulensiController@skip')->name('skip');
            Route::get('/item/{notulensi}/edit', 'RapatNotulensiController@edit')->name('edit');
            Route::put('/item/{notulensi}', 'RapatNotulensiController@update')->name('update');
            Route::post('/item/{notulensi}/upload', 'RapatNotulensiController@upload')->name('upload');
        });

        Route::prefix('notulensi')->name('notulensi.')->group(function () {
            Route::get('/item/{notulensi}/pdf', 'RapatNotulensiController@pdf')->name('pdf');
            Route::get('/item/{notulensi}/file', 'RapatNotulensiController@file')->name('file');
            Route::get('/tindak-lanjut', 'RapatNotulensiController@followUpIndex')->name('follow-ups');
            Route::post('/tindak-lanjut/{tindakLanjut}/status', 'RapatNotulensiController@updateFollowUpStatus')->name('follow-ups.status');
            Route::post('/tindak-lanjut/{tindakLanjut}/eviden', 'RapatNotulensiController@uploadFollowUpEvidence')->name('follow-ups.eviden');
            Route::get('/tindak-lanjut/{tindakLanjut}/eviden', 'RapatNotulensiController@followUpEvidence')->name('follow-ups.eviden.view');
            Route::post('/tindak-lanjut/{tindakLanjut}/complete', 'RapatNotulensiController@completeFollowUp')->name('follow-ups.complete');
        });

        Route::get('/approval', 'RapatApprovalController@index')->middleware('role:admin,approval,super_admin')->name('approval.index');
        Route::get('/approval/{rapatApproval}', 'RapatApprovalController@show')->middleware('role:admin,approval,super_admin')->name('approval.show');
        Route::post('/approval/{rapatApproval}/approve', 'RapatApprovalController@approve')->middleware('role:admin,approval,super_admin')->name('approval.approve');
        Route::post('/approval/{rapatApproval}/reject', 'RapatApprovalController@reject')->middleware('role:admin,approval,super_admin')->name('approval.reject');

        Route::get('/notulensi-approval', 'RapatNotulensiApprovalController@index')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.index');
        Route::get('/notulensi-approval/{notulensiApproval}', 'RapatNotulensiApprovalController@show')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.show');
        Route::post('/notulensi-approval/{notulensiApproval}/approve', 'RapatNotulensiApprovalController@approve')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.approve');
        Route::post('/notulensi-approval/{notulensiApproval}/reject', 'RapatNotulensiApprovalController@reject')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.reject');

        Route::prefix('agenda-pimpinan')->middleware('role:admin,protokoler,super_admin')->name('agenda.')->group(function () {
            Route::get('/', 'AgendaPimpinanController@index')->name('index');
            Route::post('/', 'AgendaPimpinanController@store')->name('store');
            Route::put('/{agenda}', 'AgendaPimpinanController@update')->name('update');
            Route::post('/{agenda}/send-whatsapp', 'AgendaPimpinanController@sendWhatsapp')->name('send-whatsapp');
            Route::delete('/{agenda}', 'AgendaPimpinanController@destroy')->name('destroy');
        });

        Route::get('/absensi', 'RapatAbsensiController@index')->name('absensi.index');
        Route::get('/absensi/{rapat}', 'RapatAbsensiController@show')->name('absensi.show');
        Route::get('/absensi/{rapat}/pdf', 'RapatAbsensiController@pdf')->name('absensi.pdf');
        Route::post('/absensi/{rapat}/remind', 'RapatAbsensiController@remindPending')->name('absensi.remind');
        Route::get('/absensi/rekap/signature/{attendance}', 'RapatAbsensiController@signature')->name('absensi.signature');

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/', 'RapatLaporanController@index')->name('index');
            Route::get('/arsip', 'RapatLaporanController@arsip')->name('arsip');
            Route::get('/{laporan}/edit', 'RapatLaporanController@edit')->middleware('role:admin,operator,notulis,super_admin')->name('edit');
            Route::put('/{laporan}', 'RapatLaporanController@update')->middleware('role:admin,operator,notulis,super_admin')->name('update');
            Route::get('/{laporan}/preview', 'RapatLaporanController@preview')->name('preview');
            Route::get('/{laporan}/download', 'RapatLaporanController@download')->name('download');
            Route::post('/{laporan}/upload', 'RapatLaporanController@upload')->middleware('role:admin,operator,notulis,super_admin')->name('upload');
            Route::post('/{laporan}/archive', 'RapatLaporanController@archive')->middleware('role:admin,operator,notulis,super_admin')->name('archive');
            Route::post('/{laporan}/unarchive', 'RapatLaporanController@unarchive')->middleware('role:admin,operator,notulis,super_admin')->name('unarchive');
        });

        Route::prefix('voting')->middleware('role:admin,super_admin')->name('voting.')->group(function () {
            Route::get('/', 'VotingController@index')->name('index');
            Route::get('/create', 'VotingController@create')->name('create');
            Route::post('/', 'VotingController@store')->name('store');
            Route::get('/{voting}', 'VotingController@show')->name('show');
            Route::get('/{voting}/edit', 'VotingController@edit')->name('edit');
            Route::put('/{voting}', 'VotingController@update')->name('update');
            Route::post('/{voting}/send-whatsapp', 'VotingController@sendWhatsapp')->name('send-whatsapp');
            Route::delete('/{voting}', 'VotingController@destroy')->name('destroy');
            Route::get('/{voting}/stats', 'VotingController@stats')->name('stats');
            Route::get('/{voting}/pdf', 'VotingController@resultsPdf')->name('pdf');
        });
    });

    Route::prefix('perawatan-alat-dan-mesin')->name('perawatan-alat-mesin.')->group(function () {
        Route::get('/', 'PersediaanDashboardController@index')->name('index');

        Route::get('/barang', 'PersediaanBarangController@index')->name('items.index');
        Route::post('/barang', 'PersediaanBarangController@store')->name('items.store');
        Route::get('/barang/{inventoryItem}', 'PersediaanBarangController@show')->name('items.show');
        Route::put('/barang/{inventoryItem}', 'PersediaanBarangController@update')->name('items.update');
        Route::post('/barang/{inventoryItem}/details', 'PersediaanBarangController@storeDetail')->name('details.store');
        Route::put('/barang/{inventoryItem}/details/{inventoryItemDetail}', 'PersediaanBarangController@updateDetail')->name('details.update');
        Route::get('/barang/details/{inventoryItemDetail}/photo', 'PersediaanBarangController@photo')->name('details.photo');

        Route::get('/master/{type}', 'PersediaanMasterDataController@index')->name('master.index');
        Route::post('/master/{type}', 'PersediaanMasterDataController@store')->name('master.store');
        Route::put('/master/{type}/{id}', 'PersediaanMasterDataController@update')->name('master.update');
        Route::delete('/master/{type}/{id}', 'PersediaanMasterDataController@destroy')->name('master.destroy');
        Route::get('/kuasa-pengguna-barang', 'PersediaanMasterDataController@authority')->name('authority.index');
        Route::post('/kuasa-pengguna-barang', 'PersediaanMasterDataController@storeAuthority')->name('authority.store');

        Route::get('/transaksi-perawatan', 'PersediaanTransaksiController@index')->name('maintenance.index');
        Route::get('/transaksi-perawatan/detail/{inventoryItemDetail}', 'PersediaanTransaksiController@show')->name('maintenance.show');
        Route::post('/transaksi-perawatan', 'PersediaanTransaksiController@store')->name('maintenance.store');
        Route::put('/transaksi-perawatan/{inventoryMaintenanceTransaction}', 'PersediaanTransaksiController@update')->name('maintenance.update');
        Route::delete('/transaksi-perawatan/{inventoryMaintenanceTransaction}', 'PersediaanTransaksiController@destroy')->name('maintenance.destroy');
        Route::get('/transaksi-perawatan/attachments/{inventoryTransactionAttachment}', 'PersediaanTransaksiController@file')->name('maintenance.attachments.file');

        Route::get('/laporan', 'PersediaanLaporanController@index')->name('reports.index');
        Route::get('/laporan/pdf', 'PersediaanLaporanController@pdf')->name('reports.pdf');
        Route::get('/laporan/excel', 'PersediaanLaporanController@excel')->name('reports.excel');

        Route::get('/qrcode', 'PersediaanQrController@index')->name('qrcode.index');
        Route::get('/qrcode/print', 'PersediaanQrController@print')->name('qrcode.print');
    });
});

