<?php

namespace App\Http\Controllers;

use App\LeaveBalance;
use App\LeaveRequest;
use App\LeaveType;
use App\Unit;
use App\User;
use App\Services\LeaveBalanceService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LeaveReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function balances(Request $request)
    {
        $this->abortIfBalanceUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Rekap Saldo Cuti Belum Siap');
        }

        [$balances, $filters, $leaveTypes, $units] = $this->buildBalanceReport($request);
        $employees = $this->balanceUsersQuery()->orderBy('name')->get();

        return view('cuti.reports.balances', compact('balances', 'filters', 'leaveTypes', 'units', 'employees'));
    }

    public function storeAnnualBalance(Request $request, LeaveBalanceService $balanceService)
    {
        $this->abortIfBalanceUnauthorized();
        abort_unless($this->moduleReady(), 503);

        $currentYear = (int) now()->year;
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'year' => 'required|integer|min:2000|max:' . $currentYear,
            'carry_forward_previous_year' => 'required|integer|min:0|max:6',
            'carry_forward_two_years_ago' => 'required|integer|min:0|max:6',
            'unused_two_consecutive_years' => 'nullable|boolean',
            'used_days' => 'required|integer|min:0|max:24',
        ], [], [
            'user_id' => 'pegawai',
            'year' => 'tahun',
            'carry_forward_previous_year' => 'sisa tahun pertama',
            'carry_forward_two_years_ago' => 'sisa tahun kedua',
            'unused_two_consecutive_years' => 'status tidak cuti dua tahun berturut-turut',
            'used_days' => 'jumlah terpakai',
        ]);

        $unusedTwoConsecutiveYears = $request->boolean('unused_two_consecutive_years');

        if ((int) $validated['carry_forward_two_years_ago'] > 0 && !$unusedTwoConsecutiveYears) {
            return back()
                ->withErrors([
                    'carry_forward_two_years_ago' => 'Sisa dua tahun sebelumnya hanya dapat dibawa jika pegawai tidak mengambil cuti tahunan sama sekali pada dua tahun sebelumnya.',
                ])
                ->withInput();
        }

        if ($unusedTwoConsecutiveYears
            && ((int) $validated['carry_forward_previous_year'] < 6
                || (int) $validated['carry_forward_two_years_ago'] < 6)) {
            return back()
                ->withErrors([
                    'unused_two_consecutive_years' => 'Hak 24 hari hanya berlaku jika cuti tahunan tidak digunakan sama sekali pada dua tahun sebelumnya.',
                ])
                ->withInput();
        }

        $user = $this->balanceUsersQuery()->whereKey($validated['user_id'])->firstOrFail();
        $balanceService->recordAnnualRecap(
            $user,
            $validated['year'],
            $validated['carry_forward_previous_year'],
            $validated['carry_forward_two_years_ago'],
            $validated['used_days'],
            auth()->user(),
            $unusedTwoConsecutiveYears
        );

        return redirect()->route('cuti.balances.index', ['year' => $validated['year']])
            ->with('success', 'Rekap cuti tahunan ' . $user->name . ' berhasil disimpan.');
    }

    public function balancesPdf(Request $request)
    {
        $this->abortIfBalanceUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Rekap Saldo Cuti Belum Siap');
        }

        [$balances, $filters] = $this->buildBalanceReport($request, false);
        $verifier = app(\App\Services\PdfVerificationService::class);
        $verification = $verifier->begin('cuti', 'rekap_saldo_cuti', null, 'Rekap Saldo Cuti', [], $filters);
        $pdfVerification = $verifier->viewData($verification);
        $pdf = PDF::loadView('cuti.reports.pdf.balances', compact('balances', 'filters', 'pdfVerification'))->setPaper('a4', 'landscape');

        return $verifier->response($pdf->output(), $verification, 'rekap-saldo-cuti.pdf');
    }

    public function balancesExcel(Request $request)
    {
        $this->abortIfBalanceUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Rekap Saldo Cuti Belum Siap');
        }

        [$balances, $filters] = $this->buildBalanceReport($request, false);
        $content = view('cuti.reports.excel.balances', compact('balances', 'filters'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rekap-saldo-cuti.xls"',
        ]);
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Laporan Cuti Belum Siap');
        }

        [$leaveRequests, $filters, $leaveTypes, $units] = $this->buildRequestReport($request);

        return view('cuti.reports.index', compact('leaveRequests', 'filters', 'leaveTypes', 'units'));
    }

    public function pdf(Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Laporan Cuti Belum Siap');
        }

        [$leaveRequests, $filters] = $this->buildRequestReport($request, false);
        $verifier = app(\App\Services\PdfVerificationService::class);
        $verification = $verifier->begin('cuti', 'laporan_pengajuan_cuti', null, 'Laporan Pengajuan Cuti', [], $filters);
        $pdfVerification = $verifier->viewData($verification);
        $pdf = PDF::loadView('cuti.reports.pdf.requests', compact('leaveRequests', 'filters', 'pdfVerification'))->setPaper('a4', 'landscape');

        return $verifier->response($pdf->output(), $verification, 'laporan-pengajuan-cuti.pdf');
    }

    public function excel(Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Laporan Cuti Belum Siap');
        }

        [$leaveRequests, $filters] = $this->buildRequestReport($request, false);
        $content = view('cuti.reports.excel.requests', compact('leaveRequests', 'filters'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-pengajuan-cuti.xls"',
        ]);
    }

    protected function buildBalanceReport(Request $request, $withOptions = true)
    {
        $year = (int) ($request->year ?: now()->year);
        $balanceService = app(LeaveBalanceService::class);
        $leaveTypesQuery = LeaveType::where('requires_balance', true)->orderBy('name');

        if ($request->filled('leave_type_id')) {
            $leaveTypesQuery->where('id', $request->leave_type_id);
        }

        $usersQuery = $this->balanceUsersQuery();

        if ($request->filled('unit_id')) {
            $usersQuery->where('unit_id', $request->unit_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $usersQuery->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $leaveTypes = $leaveTypesQuery->get();
        $rows = collect();
        $usersQuery->orderBy('name')->get()->each(function ($user) use ($leaveTypes, $year, $balanceService, $rows) {
            foreach ($leaveTypes as $leaveType) {
                $snapshot = $balanceService->getBalanceSnapshot($user, $leaveType, $year);
                $carryForwardByYear = (array) ($snapshot['carry_forward_by_year'] ?? []);
                $previousYear = $year - 1;
                $twoYearsAgo = $year - 2;
                $totalBalance = (int) ($snapshot['opening_balance'] ?? 0)
                    + (int) ($snapshot['entitlement'] ?? 0)
                    + (int) ($snapshot['carry_forward'] ?? 0)
                    + (int) ($snapshot['adjustment_plus'] ?? 0)
                    - (int) ($snapshot['adjustment_minus'] ?? 0);

                $carryPreviousYear = (int) ($carryForwardByYear[$previousYear] ?? $carryForwardByYear[(string) $previousYear] ?? 0);
                $carryTwoYearsAgo = (int) ($carryForwardByYear[$twoYearsAgo] ?? $carryForwardByYear[(string) $twoYearsAgo] ?? 0);
                $unusedTwoConsecutiveYears = (bool) data_get($snapshot, 'meta_json.annual_recap.unused_two_consecutive_years', false)
                    || ($leaveType->code === LeaveType::CODE_TAHUNAN
                        && $carryPreviousYear === 6
                        && $carryTwoYearsAgo === 6);

                $rows->push((object) [
                    'user' => $user,
                    'leaveType' => $leaveType,
                    'year' => $year,
                    'opening_balance' => (int) ($snapshot['opening_balance'] ?? 0),
                    'entitlement' => (int) ($snapshot['entitlement'] ?? 0),
                    'carry_forward' => (int) ($snapshot['carry_forward'] ?? 0),
                    'carry_forward_by_year' => $carryForwardByYear,
                    'carry_forward_previous_year' => $carryPreviousYear,
                    'carry_forward_two_years_ago' => $carryTwoYearsAgo,
                    'unused_two_consecutive_years' => $unusedTwoConsecutiveYears,
                    'adjustment_plus' => (int) ($snapshot['adjustment_plus'] ?? 0),
                    'adjustment_minus' => (int) ($snapshot['adjustment_minus'] ?? 0),
                    'used_days' => (int) ($snapshot['used_days'] ?? 0),
                    'reserved_days' => (int) ($snapshot['reserved_days'] ?? 0),
                    'remaining_balance' => (int) ($snapshot['remaining_balance'] ?? 0),
                    'total_balance' => $totalBalance,
                    'rule_note' => $this->balanceRuleNote($leaveType, (int) ($snapshot['carry_forward'] ?? 0)),
                ]);
            }
        });

        if ($withOptions) {
            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 20;
            $balances = new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
            $balances->appends($request->query());
        } else {
            $balances = $rows->values();
        }

        $filters = [
            'year' => $year,
            'unit_id' => $request->unit_id,
            'leave_type_id' => $request->leave_type_id,
            'search' => $request->search,
        ];

        if (!$withOptions) {
            return [$balances, $filters];
        }

        return [$balances, $filters, LeaveType::orderBy('name')->get(), Unit::orderBy('nama')->get()];
    }

    protected function balanceRuleNote(LeaveType $leaveType, $carryForward)
    {
        if ($leaveType->code !== LeaveType::CODE_TAHUNAN) {
            return 'Mengikuti kebijakan saldo jenis cuti.';
        }

        if ($carryForward >= 12) {
            return 'SE Sekma 2019: akumulasi sisa dua tahun maksimal 12 hari.';
        }

        if ($carryForward > 0) {
            return 'SE Sekma 2019: sisa per tahun maksimal 6 hari.';
        }

        return 'SE Sekma 2019: hak cuti tahunan 12 hari kerja.';
    }

    protected function balanceUsersQuery()
    {
        return User::with(['unit', 'roles'])
            ->active()
            ->whereHas('roles', function ($builder) {
                $builder->whereIn('name', [
                    'pegawai',
                    'atasan_langsung',
                    'admin_kepegawaian',
                    'verifikator_dokumen',
                    'ppk',
                ]);
            });
    }

    protected function buildRequestReport(Request $request, $withOptions = true)
    {
        $query = LeaveRequest::with(['user.unit', 'leaveType', 'approvals.approver']);

        if (!$this->canSeeAll()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('unit_id')) {
            $query->whereHas('user', function ($builder) use ($request) {
                $builder->where('unit_id', $request->unit_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('request_number', 'like', "%{$search}%")
                    ->orWhere('letter_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        $leaveRequests = $withOptions
            ? $query->orderByDesc('id')->paginate(20)->appends($request->query())
            : $query->orderByDesc('id')->get();
        $filters = $request->only(['status', 'leave_type_id', 'unit_id', 'date_from', 'date_to', 'search']);

        if (!$withOptions) {
            return [$leaveRequests, $filters];
        }

        return [$leaveRequests, $filters, LeaveType::orderBy('name')->get(), Unit::orderBy('nama')->get()];
    }

    protected function canSeeAll()
    {
        return auth()->user()->canManageLeaveMasterData() || auth()->user()->canApproveLeave();
    }

    protected function abortIfUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canAccessLeaveReports(), 403);
    }

    protected function abortIfBalanceUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canAccessLeaveBalanceReport(), 403);
    }

    protected function moduleReady()
    {
        return Schema::hasTable('leave_requests')
            && Schema::hasTable('leave_balances')
            && Schema::hasTable('leave_types');
    }

    protected function setupResponse($title)
    {
        return response()->view('cuti.setup', [
            'title' => $title,
            'message' => 'Schema dan data referensi laporan cuti belum siap sepenuhnya pada database.',
        ]);
    }
}
