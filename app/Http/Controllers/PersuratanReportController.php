<?php

namespace App\Http\Controllers;

use App\Services\PersuratanRegisterReportService;
use App\SuratKeluar;
use App\SuratMasuk;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PersuratanReportController extends Controller
{
    protected $reportService;

    public function __construct(PersuratanRegisterReportService $reportService)
    {
        $this->middleware('auth');
        $this->reportService = $reportService;
    }

    public function incoming(Request $request)
    {
        [$startDate, $endDate] = $this->validatedPeriod($request);

        $letters = SuratMasuk::visibleTo($request->user())
            ->with([
                'klasifikasiKode',
                'kategoriSurat',
                'creator',
                'disposisis' => function ($query) {
                    $query->latest('created_at');
                },
            ])
            ->whereBetween('tanggal_surat', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('tanggal_surat')
            ->orderByDesc('id')
            ->get();

        return $this->streamReport(
            'masuk',
            'REGISTER SURAT MASUK',
            $this->reportService->incomingRows($letters),
            $startDate,
            $endDate
        );
    }

    public function outgoing(Request $request)
    {
        [$startDate, $endDate] = $this->validatedPeriod($request);

        $letters = SuratKeluar::visibleTo($request->user())
            ->with([
                'creator',
                'penerimaInternal',
            ])
            ->whereBetween('tanggal_surat', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('tanggal_surat')
            ->orderByDesc('nomor_urut')
            ->orderByDesc('id')
            ->get();

        return $this->streamReport(
            'keluar',
            'REGISTER SURAT KELUAR',
            $this->reportService->outgoingRows($letters),
            $startDate,
            $endDate
        );
    }

    protected function validatedPeriod(Request $request)
    {
        $data = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        return [
            Carbon::parse($data['tanggal_mulai'], 'Asia/Jayapura')->startOfDay(),
            Carbon::parse($data['tanggal_selesai'], 'Asia/Jayapura')->endOfDay(),
        ];
    }

    protected function streamReport($type, $title, $rows, Carbon $startDate, Carbon $endDate)
    {
        $periodLabel = $this->reportService->periodLabel($startDate, $endDate);
        $filename = sprintf(
            'register-surat-%s-%s-sampai-%s.pdf',
            $type,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );

        $pdf = PDF::loadView('persuratan.reports.register', compact(
            'type',
            'title',
            'rows',
            'periodLabel',
            'startDate',
            'endDate'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream($filename);
    }
}
