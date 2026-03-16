<?php

namespace App\Providers;

use App\SuratKeluar;
use App\SuratMasuk;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $counts = [
                'sidebarSuratMasukOpenCount' => 0,
                'sidebarSuratKeluarDraftCount' => 0,
                'sidebarArsipCount' => 0,
            ];

            $user = Auth::user();
            if (!$user) {
                $view->with($counts);
                return;
            }

            $suratMasukVisible = SuratMasuk::visibleTo($user);
            $suratKeluarVisible = SuratKeluar::visibleTo($user);

            $counts['sidebarSuratMasukOpenCount'] = (clone $suratMasukVisible)
                ->where('status', '!=', 'selesai')
                ->count();
            $counts['sidebarSuratKeluarDraftCount'] = (clone $suratKeluarVisible)
                ->where('status', 'draft')
                ->count();
            $counts['sidebarArsipCount'] = (clone $suratKeluarVisible)
                ->where('status', 'lengkap')
                ->count();

            $view->with($counts);
        });
    }
}
