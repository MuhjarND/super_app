<?php

use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect()->route('login');
});

Auth::routes(['register' => false]); // Disable registration - admin adds users
Route::get('/autologin', 'AutoLoginController@show')->name('autologin');
Route::post('/autologin', 'AutoLoginController@login')->name('autologin.login');
Route::get('/masuk/whatsapp/{token}', 'Auth\WhatsAppMagicLoginController@consume')
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:10,1')
    ->name('whatsapp.magic-login.consume');
Route::get('/login/2fa', 'Auth\TwoFactorChallengeController@show')->name('two-factor.challenge.show');
Route::post('/login/2fa', 'Auth\TwoFactorChallengeController@store')->name('two-factor.challenge.store');

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
Route::get('/verifikasi/pdf/{token}', 'PdfVerificationController@show')->name('pdf-verification.show');
Route::get('/verifikasi/pdf/{token}/preview', 'PdfVerificationController@preview')->name('pdf-verification.preview');
Route::get('/publik/tindak-lanjut/eviden/{token}', 'PublicFollowUpEvidenceController@show')->name('rapat.notulensi.follow-ups.eviden.public');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    Route::get('/profil', 'ProfileController@edit')->name('profile.edit');
    Route::put('/profil', 'ProfileController@update')->name('profile.update');
    Route::put('/profil/password', 'ProfileController@updatePassword')->name('profile.password.update');
    Route::get('/profil/2fa', 'TwoFactorController@edit')->name('two-factor.edit');
    Route::post('/profil/2fa/setup', 'TwoFactorController@setup')->name('two-factor.setup');
    Route::post('/profil/2fa/enable', 'TwoFactorController@enable')->name('two-factor.enable');
    Route::post('/profil/2fa/recovery-codes', 'TwoFactorController@regenerateRecoveryCodes')->name('two-factor.recovery-codes.regenerate');
    Route::delete('/profil/2fa', 'TwoFactorController@disable')->name('two-factor.disable');

    // Dashboard
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/home', 'DashboardController@index')->name('home');
    Route::get('/mobile/menu/{module}', 'MobileModuleMenuController@show')->name('mobile.menu.show');
    Route::get('/dashboard/pimpinan', 'LeadershipDashboardController@index')->name('dashboard.leadership');
    Route::get('/audit-trail', 'AuditTrailController@index')->name('audit-trail.index');
    Route::get('/tindak-lanjut-terpadu', 'UnifiedActionCenterController@index')->name('action-center.index');
    Route::get('/kalender-terpadu', 'IntegratedCalendarController@index')->name('calendar.integrated.index');
    Route::get('/kalender-terpadu/events', 'IntegratedCalendarController@events')->name('calendar.integrated.events');
    Route::prefix('persediaan')->name('persediaan.')->group(function () {
        Route::get('/', function () {
            abort_unless(auth()->user()->canAccessSupplyModule(), 403);

            return redirect()->route('persediaan.requests.create');
        })->name('index');
        Route::get('/barang', 'SupplyItemController@index')->name('items.index');
        Route::post('/barang', 'SupplyItemController@store')->name('items.store');
        Route::put('/barang/{supplyItem}', 'SupplyItemController@update')->name('items.update');
        Route::get('/pengajuan', 'SupplyRequestController@index')->name('requests.index');
        Route::get('/pengajuan/create', 'SupplyRequestController@create')->name('requests.create');
        Route::post('/pengajuan', 'SupplyRequestController@store')->name('requests.store');
        Route::get('/pengajuan/{supplyRequest}', 'SupplyRequestController@show')->name('requests.show');
        Route::post('/pengajuan/{supplyRequest}/fulfill', 'SupplyRequestController@fulfill')->name('requests.fulfill');
        Route::post('/pengajuan/{supplyRequest}/reject', 'SupplyRequestController@reject')->name('requests.reject');
        Route::post('/pengajuan/{supplyRequest}/cancel', 'SupplyRequestController@cancel')->name('requests.cancel');
        Route::get('/barang-diambil', 'SupplyPickupController@index')->name('pickups.index');
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
    Route::get('/arsip', 'ArchiveController@index')->middleware('role:admin')->name('arsip.index');

    // Disposisi
    Route::post('/disposisi', 'DisposisiController@store')->name('disposisi.store');
    Route::patch('/disposisi/{disposisi}/status', 'DisposisiController@updateStatus')->name('disposisi.update-status');
    Route::post('/disposisi/{disposisi}/remind', 'DisposisiController@remind')->name('disposisi.remind');
    Route::get('/disposisi/dokumentasi/{dokumentasi}/preview', 'DisposisiController@previewDokumentasi')->name('disposisi.dokumentasi.preview');
    Route::get('/disposisi/dokumentasi/{dokumentasi}/download', 'DisposisiController@downloadDokumentasi')->name('disposisi.dokumentasi.download');
    Route::get('/api/disposisi/targets', 'DisposisiController@getTargets')->name('api.disposisi.targets');

    // Master data - super admin only
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        Route::post('persuratan/sync-legacy', 'Admin\LegacyPersuratanSyncController@store')
            ->middleware('throttle:2,1')
            ->name('legacy-persuratan.sync');
        Route::resource('users', 'Admin\UserManagementController')->except(['show']);
        Route::post('users/{user}/send-login-info', 'Admin\UserManagementController@sendLoginInfo')->name('users.send-login-info');
        Route::patch('users/{user}/toggle-status', 'Admin\UserManagementController@toggleStatus')->name('users.toggle-status');
        Route::post('whatsapp-notifications/toggle', 'Admin\UserManagementController@toggleWhatsAppNotifications')->name('whatsapp-notifications.toggle');
        Route::get('module-settings', 'Admin\ModuleSettingController@index')->name('module-settings.index');
        Route::put('module-settings', 'Admin\ModuleSettingController@update')->name('module-settings.update');
        Route::resource('jabatans', 'Admin\JabatanManagementController')->except(['show']);
        Route::resource('units', 'Admin\UnitManagementController')->only(['index']);
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
        Route::post('/saldo', 'LeaveReportController@storeAnnualBalance')->name('balances.store');
        Route::get('/saldo/pdf', 'LeaveReportController@balancesPdf')->name('balances.pdf');
        Route::get('/saldo/excel', 'LeaveReportController@balancesExcel')->name('balances.excel');
        Route::get('/laporan', 'LeaveReportController@index')->name('reports.index');
        Route::get('/laporan/pdf', 'LeaveReportController@pdf')->name('reports.pdf');
        Route::get('/laporan/excel', 'LeaveReportController@excel')->name('reports.excel');

        Route::get('/approval/list', 'LeaveApprovalController@index')->name('approval.index');
        Route::get('/approval/{leaveApproval}', 'LeaveApprovalController@show')->name('approval.show');
        Route::post('/approval/{leaveApproval}/approve', 'LeaveApprovalController@approve')->name('approval.approve');
        Route::post('/approval/{leaveApproval}/reject', 'LeaveApprovalController@reject')->name('approval.reject');
        Route::post('/approval/{leaveApproval}/change', 'LeaveApprovalController@change')->name('approval.change');
        Route::post('/approval/{leaveApproval}/defer', 'LeaveApprovalController@defer')->name('approval.defer');
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

    Route::prefix('rapat/virtual-meeting')->name('rapat.virtual-meeting.')->group(function () {
        Route::get('/', 'VirtualMeetingController@index')->name('index');
        Route::put('/{virtualMeeting}', 'VirtualMeetingController@update')->name('update');
        Route::post('/{virtualMeeting}/send-whatsapp', 'VirtualMeetingController@sendWhatsapp')->name('send-whatsapp');
        Route::delete('/{virtualMeeting}', 'VirtualMeetingController@destroy')->name('destroy');
    });

    Route::prefix('rapat/agenda-pimpinan')->name('rapat.agenda.')->group(function () {
        Route::get('/', 'AgendaPimpinanController@index')->name('index');
        Route::post('/', 'AgendaPimpinanController@store')->name('store');
        Route::put('/{agenda}', 'AgendaPimpinanController@update')->name('update');
        Route::patch('/{agenda}/participants', 'AgendaPimpinanController@updateParticipants')->name('participants');
        Route::post('/{agenda}/send-whatsapp', 'AgendaPimpinanController@sendWhatsapp')->name('send-whatsapp');
        Route::delete('/{agenda}', 'AgendaPimpinanController@destroy')->name('destroy');
    });

    Route::prefix('rapat/voting')->name('rapat.voting.')->group(function () {
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
        });

        Route::get('/approval', 'RapatApprovalController@index')->middleware('role:admin,approval,super_admin')->name('approval.index');
        Route::get('/approval/{rapatApproval}', 'RapatApprovalController@show')->middleware('role:admin,approval,super_admin')->name('approval.show');
        Route::post('/approval/{rapatApproval}/approve', 'RapatApprovalController@approve')->middleware('role:admin,approval,super_admin')->name('approval.approve');
        Route::post('/approval/{rapatApproval}/reject', 'RapatApprovalController@reject')->middleware('role:admin,approval,super_admin')->name('approval.reject');

        Route::get('/notulensi-approval', 'RapatNotulensiApprovalController@index')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.index');
        Route::get('/notulensi-approval/{notulensiApproval}', 'RapatNotulensiApprovalController@show')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.show');
        Route::post('/notulensi-approval/{notulensiApproval}/approve', 'RapatNotulensiApprovalController@approve')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.approve');
        Route::post('/notulensi-approval/{notulensiApproval}/reject', 'RapatNotulensiApprovalController@reject')->middleware('role:admin,approval,super_admin')->name('notulensi-approval.reject');

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

    });

    Route::prefix('rapat/notulensi')->name('rapat.notulensi.')->group(function () {
        Route::get('/tindak-lanjut', 'RapatNotulensiController@followUpIndex')->name('follow-ups');
        Route::post('/tindak-lanjut/{tindakLanjut}/status', 'RapatNotulensiController@updateFollowUpStatus')->name('follow-ups.status');
        Route::post('/tindak-lanjut/{tindakLanjut}/eviden', 'RapatNotulensiController@uploadFollowUpEvidence')->name('follow-ups.eviden');
        Route::get('/tindak-lanjut/{tindakLanjut}/eviden', 'RapatNotulensiController@followUpEvidence')->name('follow-ups.eviden.view');
        Route::post('/tindak-lanjut/{tindakLanjut}/complete', 'RapatNotulensiController@completeFollowUp')->name('follow-ups.complete');
    });

    Route::prefix('perpustakaan')->name('library.')->middleware('library.access')->group(function () {
        Route::get('/', 'Library\DashboardController@index')->name('index');

        Route::get('/buku', 'Library\BookController@index')->name('books.index');
        Route::get('/buku/{book}', 'Library\BookController@show')->where('book', '[0-9]+')->name('books.show');
        Route::get('/eksemplar', 'Library\BookCopyController@index')->name('book-copies.index');
        Route::get('/eksemplar/lookup/data', 'Library\BookCopyController@lookup')->name('book-copies.lookup');

        Route::get('/barcode', 'Library\BarcodeController@index')->name('barcode.index');
        Route::get('/barcode/print', 'Library\BarcodeController@print')->name('barcode.print');
        Route::get('/barcode/{bookCopy}', 'Library\BarcodeController@show')->name('barcode.show');
        Route::get('/barcode/{bookCopy}/svg', 'Library\BarcodeController@generateSvg')->name('barcode.svg');

        Route::get('/scan', 'Library\ScanController@index')->name('scan.index');
        Route::post('/scan/lookup', 'Library\ScanController@lookup')->name('scan.lookup');

        Route::get('/anggota/search/api', 'Library\MemberController@search')->name('members.search');
        Route::get('/anggota', 'Library\MemberController@index')->name('members.index');
        Route::get('/anggota/{member}', 'Library\MemberController@show')->where('member', '[0-9]+')->name('members.show');

        Route::get('/peminjaman', 'Library\LoanController@index')->name('loans.index');
        Route::get('/peminjaman/{loan}', 'Library\LoanController@show')->where('loan', '[0-9]+')->name('loans.show');

        Route::get('/pengembalian/search/loan', 'Library\ReturnController@searchLoan')->name('returns.search-loan');
        Route::get('/pengembalian', 'Library\ReturnController@index')->name('returns.index');
        Route::get('/pengembalian/{return}', 'Library\ReturnController@show')->where('return', '[0-9]+')->name('returns.show');

        Route::get('/denda', 'Library\FineController@index')->name('fines.index');
        Route::get('/denda/{fine}', 'Library\FineController@show')->where('fine', '[0-9]+')->name('fines.show');

        Route::prefix('laporan')->name('reports.')->group(function () {
            Route::get('/', 'Library\ReportController@index')->name('index');
            Route::get('/buku', 'Library\ReportController@books')->name('books');
            Route::get('/anggota', 'Library\ReportController@members')->name('members');
            Route::get('/peminjaman', 'Library\ReportController@loans')->name('loans');
            Route::get('/pengembalian', 'Library\ReportController@returns')->name('returns');
            Route::get('/keterlambatan', 'Library\ReportController@lates')->name('lates');
            Route::get('/denda', 'Library\ReportController@fines')->name('fines');
        });

        Route::middleware('library.manage')->group(function () {
            Route::get('/buku/create', 'Library\BookController@create')->name('books.create');
            Route::post('/buku', 'Library\BookController@store')->name('books.store');
            Route::get('/buku/{book}/edit', 'Library\BookController@edit')->name('books.edit');
            Route::put('/buku/{book}', 'Library\BookController@update')->name('books.update');
            Route::delete('/buku/{book}', 'Library\BookController@destroy')->name('books.destroy');

            Route::get('/eksemplar/create', 'Library\BookCopyController@create')->name('book-copies.create');
            Route::post('/eksemplar', 'Library\BookCopyController@store')->name('book-copies.store');
            Route::get('/eksemplar/{bookCopy}/edit', 'Library\BookCopyController@edit')->name('book-copies.edit');
            Route::put('/eksemplar/{bookCopy}', 'Library\BookCopyController@update')->name('book-copies.update');
            Route::delete('/eksemplar/{bookCopy}', 'Library\BookCopyController@destroy')->name('book-copies.destroy');

            Route::get('/anggota/create', 'Library\MemberController@create')->name('members.create');
            Route::post('/anggota', 'Library\MemberController@store')->name('members.store');
            Route::get('/anggota/{member}/edit', 'Library\MemberController@edit')->name('members.edit');
            Route::put('/anggota/{member}', 'Library\MemberController@update')->name('members.update');
            Route::delete('/anggota/{member}', 'Library\MemberController@destroy')->name('members.destroy');

            Route::get('/peminjaman/create', 'Library\LoanController@create')->name('loans.create');
            Route::post('/peminjaman', 'Library\LoanController@store')->name('loans.store');
            Route::delete('/peminjaman/{loan}', 'Library\LoanController@destroy')->name('loans.destroy');

            Route::get('/pengembalian/create', 'Library\ReturnController@create')->name('returns.create');
            Route::post('/pengembalian', 'Library\ReturnController@store')->name('returns.store');

            Route::post('/denda/{fine}/pay', 'Library\FineController@pay')->name('fines.pay');
            Route::post('/denda/pay-all', 'Library\FineController@payAll')->name('fines.pay-all');

            Route::get('/pengaturan', 'Library\SettingController@index')->name('settings.index');
            Route::post('/pengaturan', 'Library\SettingController@update')->name('settings.update');
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

        Route::get('/jadwal-perawatan', 'PersediaanJadwalPerawatanController@index')->name('schedules.index');
        Route::post('/jadwal-perawatan', 'PersediaanJadwalPerawatanController@store')->name('schedules.store');
        Route::put('/jadwal-perawatan/{inventoryMaintenanceSchedule}', 'PersediaanJadwalPerawatanController@update')->name('schedules.update');
        Route::patch('/jadwal-perawatan/{inventoryMaintenanceSchedule}/complete', 'PersediaanJadwalPerawatanController@complete')->name('schedules.complete');
        Route::patch('/jadwal-perawatan/{inventoryMaintenanceSchedule}/cancel', 'PersediaanJadwalPerawatanController@cancel')->name('schedules.cancel');
        Route::delete('/jadwal-perawatan/{inventoryMaintenanceSchedule}', 'PersediaanJadwalPerawatanController@destroy')->name('schedules.destroy');

        Route::get('/laporan', 'PersediaanLaporanController@index')->name('reports.index');
        Route::get('/laporan/pdf', 'PersediaanLaporanController@pdf')->name('reports.pdf');
        Route::get('/laporan/excel', 'PersediaanLaporanController@excel')->name('reports.excel');

        Route::get('/qrcode', 'PersediaanQrController@index')->name('qrcode.index');
        Route::get('/qrcode/print', 'PersediaanQrController@print')->name('qrcode.print');
    });
});

