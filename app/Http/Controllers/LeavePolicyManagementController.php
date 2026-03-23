<?php

namespace App\Http\Controllers;

use App\LeavePolicy;
use App\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeavePolicyManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $query = LeavePolicy::with('leaveType');

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('key', 'like', "%{$search}%")
                    ->orWhereHas('leaveType', function ($typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $policies = $query->orderByDesc('id')->paginate(15)->appends($request->query());
        $leaveTypes = LeaveType::orderBy('name')->get();
        $filters = $request->only(['search', 'leave_type_id']);

        return view('cuti.master.policies.index', compact('policies', 'leaveTypes', 'filters'));
    }

    public function store(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        LeavePolicy::create($this->validatedData($request));

        return redirect()->route('cuti.master.policies.index')->with('success', 'Kebijakan cuti berhasil ditambahkan.');
    }

    public function update(Request $request, LeavePolicy $leavePolicy)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $leavePolicy->update($this->validatedData($request));

        return redirect()->route('cuti.master.policies.index')->with('success', 'Kebijakan cuti berhasil diperbarui.');
    }

    public function destroy(LeavePolicy $leavePolicy)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $leavePolicy->delete();

        return redirect()->route('cuti.master.policies.index')->with('success', 'Kebijakan cuti berhasil dihapus.');
    }

    protected function validatedData(Request $request)
    {
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'key' => 'required|string|max:100',
            'value_text' => 'nullable|string',
            'effective_start' => 'nullable|date',
            'effective_end' => 'nullable|date|after_or_equal:effective_start',
            'is_active' => 'nullable',
        ]);

        return [
            'leave_type_id' => $data['leave_type_id'],
            'key' => $data['key'],
            'value_json' => ['value' => $this->normalizePolicyValue($data['value_text'] ?? null)],
            'is_active' => $request->has('is_active'),
            'effective_start' => $data['effective_start'] ?? null,
            'effective_end' => $data['effective_end'] ?? null,
        ];
    }

    protected function normalizePolicyValue($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }

    protected function abortIfUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canManageLeaveMasterData(), 403);
    }

    protected function ensureReady()
    {
        abort_unless(Schema::hasTable('leave_policies'), 404);
    }
}
