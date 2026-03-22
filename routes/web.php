<?php

use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
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
Route::get('/publik/tindak-lanjut/eviden/{token}', 'PublicFollowUpEvidenceController@show')->name('rapat.notulensi.follow-ups.eviden.public');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/home', 'DashboardController@index')->name('home');

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
    Route::get('/arsip', 'SuratKeluarController@arsip')->name('arsip.index');

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

    Route::middleware('role:admin,approval,super_admin')->group(function () {
        Route::get('/approval', 'ApprovalCenterController@index')->name('approval.index');
        Route::get('/approval/history', 'ApprovalCenterController@history')->name('approval.history');
    });

    // Placeholder modules
    Route::get('/cuti', function () {
        return view('modules.under-development', ['module' => 'Cuti', 'icon' => 'fas fa-calendar-times', 'description' => 'Modul manajemen pengajuan cuti pegawai.']);
    })->name('cuti.index');

    Route::get('/progress-zi', function () {
        return view('modules.under-development', [
            'module' => 'Progress ZI',
            'icon' => 'fas fa-chart-line',
            'description' => 'Modul monitoring progress Zona Integritas, eviden, dan capaian indikator kerja.'
        ]);
    })->name('progress-zi.index');

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

    Route::get('/persediaan', function () {
        return view('modules.under-development', ['module' => 'Persediaan', 'icon' => 'fas fa-boxes', 'description' => 'Modul manajemen persediaan barang dan aset.']);
    })->name('persediaan.index');
});
