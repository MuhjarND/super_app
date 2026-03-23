<?php

namespace App\Http\Controllers;

use App\LeaveBalance;
use App\LeaveRequest;
use App\LeaveType;
use App\Unit;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeaveReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function balances(Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Rekap Saldo Cuti Belum Siap');
        }

        [$balances, $filters, $leaveTypes, $units] = $this->buildBalanceReport($request);

        return view('cuti.reports.balances', compact('balances', 'filters', 'leaveTypes', 'units'));
    }

    public function balancesPdf(Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) {
            return $this->setupResponse('Rekap Saldo Cuti Belum Siap');
        }

        [$balances, $filters] = $this->buildBalanceReport($request, false);
        $pdf = PDF::loadView('cuti.reports.pdf.balances', compact('balances', 'filters'))->setPaper('a4', 'landscape');

        return $pdf->stream('rekap-saldo-cuti.pdf');
    }

    public function balancesExcel(Request $request)
    {
        $this->abortIfUnauthorized();
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
        $pdf = PDF::loadView('cuti.reports.pdf.requests', compact('leaveRequests', 'filters'))->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-pengajuan-cuti.pdf');
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

        $query = LeaveBalance::with(['user.unit', 'leaveType'])->where('year', $year);

        if (!$this->canSeeAll()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('unit_id')) {
            $query->whereHas('user', function ($builder) use ($request) {
                $builder->where('unit_id', $request->unit_id);
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('user', function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $balances = $withOptions
            ? $query->orderBy('user_id')->paginate(20)->appends($request->query())
            : $query->orderBy('user_id')->get();
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
        abort_unless(auth()->check() && auth()->user()->canAccessLeaveModule(), 403);
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
