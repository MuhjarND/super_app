<?php

use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Auth::routes(['register' => false]); // Disable registration - admin adds users

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
        Route::resource('jabatans', 'Admin\JabatanManagementController')->except(['show']);
        Route::resource('units', 'Admin\UnitManagementController')->except(['show']);
        Route::resource('kategori-surats', 'Admin\KategoriSuratManagementController')->except(['show']);
    });

    // Placeholder modules
    Route::get('/cuti', function () {
        return view('modules.under-development', ['module' => 'Cuti', 'icon' => 'fas fa-calendar-times', 'description' => 'Modul manajemen pengajuan cuti pegawai.']);
    })->name('cuti.index');

    Route::get('/rapat', function () {
        return view('modules.under-development', ['module' => 'Rapat', 'icon' => 'fas fa-users', 'description' => 'Modul manajemen jadwal dan dokumentasi rapat.']);
    })->name('rapat.index');

    Route::get('/persediaan', function () {
        return view('modules.under-development', ['module' => 'Persediaan', 'icon' => 'fas fa-boxes', 'description' => 'Modul manajemen persediaan barang dan aset.']);
    })->name('persediaan.index');
});
