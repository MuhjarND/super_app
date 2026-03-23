<?php

namespace App\Http\Controllers;

use App\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeaveTypeManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $query = LeaveType::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leaveTypes = $query->orderBy('name')->paginate(15)->appends($request->query());
        $filters = $request->only(['search', 'status']);

        return view('cuti.master.types.index', compact('leaveTypes', 'filters'));
    }

    public function store(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        LeaveType::create($this->validatedData($request));

        return redirect()->route('cuti.master.types.index')->with('success', 'Jenis cuti berhasil ditambahkan.');
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $leaveType->update($this->validatedData($request, $leaveType->id));

        return redirect()->route('cuti.master.types.index')->with('success', 'Jenis cuti berhasil diperbarui.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        if ($leaveType->requests()->exists() || $leaveType->policies()->exists()) {
            return redirect()->route('cuti.master.types.index')->with('error', 'Jenis cuti masih dipakai oleh data lain.');
        }

        $leaveType->delete();

        return redirect()->route('cuti.master.types.index')->with('success', 'Jenis cuti berhasil dihapus.');
    }

    protected function validatedData(Request $request, $ignoreId = null)
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:leave_types,code' . ($ignoreId ? ',' . $ignoreId : ''),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_days' => 'nullable|integer|min:1',
            'max_months' => 'nullable|integer|min:1',
            'service_years_required' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'requires_balance' => 'nullable',
            'requires_document' => 'nullable',
            'requires_verification' => 'nullable',
            'requires_ppk_approval' => 'nullable',
        ]);

        $data['requires_balance'] = $request->has('requires_balance');
        $data['requires_document'] = $request->has('requires_document');
        $data['requires_verification'] = $request->has('requires_verification');
        $data['requires_ppk_approval'] = $request->has('requires_ppk_approval');

        return $data;
    }

    protected function abortIfUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canManageLeaveMasterData(), 403);
    }

    protected function ensureReady()
    {
        abort_unless(Schema::hasTable('leave_types'), 404);
    }
}
