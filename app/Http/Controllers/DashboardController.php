<?php

namespace App\Http\Controllers;

use App\SuratMasuk;
use App\SuratKeluar;
use App\Disposisi;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        $suratMasukVisible = SuratMasuk::visibleTo($user);
        $suratKeluarVisible = SuratKeluar::visibleTo($user);

        // Main stats
        $totalSuratMasuk = (clone $suratMasukVisible)->count();
        $totalSuratKeluar = (clone $suratKeluarVisible)->count();
        $suratMasukBaru = (clone $suratMasukVisible)->where('status', 'baru')->count();
        $disposisiPending = Disposisi::where('kepada_user_id', $user->id)
            ->where('status', 'pending')->count();
        $suratKeluarDraft = (clone $suratKeluarVisible)->where('status', 'draft')->count();
        $suratKeluarLengkap = (clone $suratKeluarVisible)->where('status', 'lengkap')->count();

        // Monthly chart data (last 6 months)
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthlyData[] = [
                'month' => $date->translatedFormat('M Y'),
                'masuk' => SuratMasuk::visibleTo($user)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'keluar' => SuratKeluar::visibleTo($user)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
            ];
        }

        // Status distribution
        $statusData = [
            'baru' => SuratMasuk::visibleTo($user)->where('status', 'baru')->count(),
            'didisposisi' => SuratMasuk::visibleTo($user)->where('status', 'didisposisi')->count(),
            'selesai' => SuratMasuk::visibleTo($user)->where('status', 'selesai')->count(),
        ];

        // Recent activities (combined surat masuk & disposisi)
        $recentSuratMasuk = SuratMasuk::visibleTo($user)
            ->with('creator', 'klasifikasiKode')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentDisposisi = Disposisi::with('suratMasuk', 'dariUser', 'kepadaUser')
            ->where('kepada_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Today's stats
        $todayMasuk = SuratMasuk::visibleTo($user)->whereDate('created_at', today())->count();
        $todayKeluar = SuratKeluar::visibleTo($user)->whereDate('created_at', today())->count();

        return view('dashboard', compact(
            'totalSuratMasuk',
            'totalSuratKeluar',
            'suratMasukBaru',
            'disposisiPending',
            'suratKeluarDraft',
            'suratKeluarLengkap',
            'monthlyData',
            'statusData',
            'recentSuratMasuk',
            'recentDisposisi',
            'todayMasuk',
            'todayKeluar'
        ));
    }
}
