<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiProgressService;
use App\ZiPeriod;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;

class ProgressZiDashboardController extends Controller
{
    use InteractsWithProgressZi;

    protected $progressService;

    public function __construct(ZiProgressService $progressService)
    {
        $this->middleware('auth');
        $this->progressService = $progressService;
    }

    public function index(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse();
        }

        $periods = ZiPeriod::orderByDesc('year')->orderByDesc('is_active')->get();
        $selectedPeriod = $request->filled('period_id')
            ? ZiPeriod::find($request->period_id)
            : $periods->firstWhere('is_active', true);

        $dashboard = $this->progressService->buildDashboard($selectedPeriod);

        return view('progress-zi.dashboard', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'dashboard' => $dashboard,
        ]);
    }

    public function verifications(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        $this->abortUnlessCanVerifyProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse();
        }

        [$periodId, $indicators, $evidences] = $this->buildVerificationData($request);

        return view('progress-zi.verifications.index', [
            'periods' => ZiPeriod::orderByDesc('year')->get(),
            'periodId' => $periodId,
            'indicators' => $indicators,
            'evidences' => $evidences,
        ]);
    }

    public function verificationsPdf(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        $this->abortUnlessCanVerifyProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse();
        }

        [$periodId, $indicators, $evidences] = $this->buildVerificationData($request);
        $period = $periodId ? ZiPeriod::find($periodId) : null;
        $pdf = PDF::loadView('progress-zi.verifications.pdf', compact('period', 'indicators', 'evidences'))->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-verifikasi-progress-zi.pdf');
    }

    public function verificationsExcel(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        $this->abortUnlessCanVerifyProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse();
        }

        [$periodId, $indicators, $evidences] = $this->buildVerificationData($request);
        $period = $periodId ? ZiPeriod::find($periodId) : null;
        $content = view('progress-zi.verifications.excel', compact('period', 'indicators', 'evidences'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-verifikasi-progress-zi.xls"',
        ]);
    }

    protected function buildVerificationData(Request $request)
    {
        $periodId = $request->period_id;
        $indicatorQuery = \App\ZiIndicator::with(['activity.area', 'latestReview.reviewer']);
        $evidenceQuery = \App\ZiEvidence::with(['realization.activity.area', 'indicators', 'latestReview.reviewer']);

        if ($periodId) {
            $indicatorQuery->whereHas('activity', function ($query) use ($periodId) { $query->where('zi_period_id', $periodId); });
            $evidenceQuery->whereHas('realization.activity', function ($query) use ($periodId) { $query->where('zi_period_id', $periodId); });
        }

        $indicators = $indicatorQuery->whereIn('status', ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'terpenuhi', 'ditolak'])->latest()->get();
        $evidences = $evidenceQuery->whereIn('status', ['terupload', 'terhubung', 'revisi', 'tidak_valid'])->latest()->get();

        return [$periodId, $indicators, $evidences];
    }
}
