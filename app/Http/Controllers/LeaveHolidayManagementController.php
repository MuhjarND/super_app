<?php

namespace App\Http\Controllers;

use App\LeaveHoliday;
use App\LeaveType;
use App\Services\LeaveExistingRequestSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveHolidayManagementController extends Controller
{
    protected $existingRequestSync;

    public function __construct(LeaveExistingRequestSyncService $existingRequestSync)
    {
        $this->middleware('auth');
        $this->existingRequestSync = $existingRequestSync;
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $query = LeaveHoliday::with('leaveType');

        if ($request->filled('year')) {
            $query->whereYear('holiday_date', (int) $request->year);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        $holidays = $query->orderBy('holiday_date')->paginate(15)->appends($request->query());
        $leaveTypes = LeaveType::orderBy('name')->get();
        $filters = $request->only(['year', 'category', 'search']);

        return view('cuti.master.holidays.index', compact('holidays', 'leaveTypes', 'filters'));
    }

    public function store(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $updatedRequests = DB::transaction(function () use ($request) {
            $holiday = LeaveHoliday::create($this->validatedData($request));

            return $this->existingRequestSync->syncOverlappingDates([$holiday->holiday_date]);
        });

        return redirect()->route('cuti.master.holidays.index')->with('success', $this->successMessage('ditambahkan', $updatedRequests));
    }

    public function update(Request $request, LeaveHoliday $leaveHoliday)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $updatedRequests = DB::transaction(function () use ($request, $leaveHoliday) {
            $oldDate = optional($leaveHoliday->holiday_date)->toDateString();
            $leaveHoliday->update($this->validatedData($request));

            return $this->existingRequestSync->syncOverlappingDates([$oldDate, $leaveHoliday->holiday_date]);
        });

        return redirect()->route('cuti.master.holidays.index')->with('success', $this->successMessage('diperbarui', $updatedRequests));
    }

    public function destroy(LeaveHoliday $leaveHoliday)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $updatedRequests = DB::transaction(function () use ($leaveHoliday) {
            $oldDate = optional($leaveHoliday->holiday_date)->toDateString();
            $leaveHoliday->delete();

            return $this->existingRequestSync->syncOverlappingDates([$oldDate]);
        });

        return redirect()->route('cuti.master.holidays.index')->with('success', $this->successMessage('dihapus', $updatedRequests));
    }

    protected function validatedData(Request $request)
    {
        $data = $request->validate([
            'holiday_date' => 'required|date',
            'name' => 'required|string|max:255',
            'category' => 'required|in:libur_nasional,cuti_bersama,internal',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'deduction_days' => 'nullable|integer|min:0',
            'impacts_balance' => 'nullable',
            'is_national_holiday' => 'nullable',
            'is_collective_leave' => 'nullable',
            'is_active' => 'nullable',
        ]);

        return [
            'holiday_date' => $data['holiday_date'],
            'name' => $data['name'],
            'category' => $data['category'],
            'leave_type_id' => $data['leave_type_id'] ?? null,
            'deduction_days' => $data['deduction_days'] ?? 0,
            'impacts_balance' => $request->has('impacts_balance'),
            'is_national_holiday' => $request->has('is_national_holiday'),
            'is_collective_leave' => $request->has('is_collective_leave'),
            'is_active' => $request->has('is_active'),
        ];
    }

    protected function abortIfUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canManageLeaveMasterData(), 403);
    }

    protected function ensureReady()
    {
        abort_unless(Schema::hasTable('leave_holidays'), 404);
    }

    protected function successMessage($action, $updatedRequests)
    {
        $message = 'Data cuti bersama/libur berhasil ' . $action . '.';

        if ($updatedRequests > 0) {
            $message .= ' ' . $updatedRequests . ' pengajuan cuti lama telah disinkronkan.';
        }

        return $message;
    }
}
