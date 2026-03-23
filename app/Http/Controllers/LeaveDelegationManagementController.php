<?php

namespace App\Http\Controllers;

use App\LeaveDelegation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LeaveDelegationManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $query = LeaveDelegation::with(['delegator', 'delegate']);

        if ($request->filled('scope')) {
            $query->where('scope', $request->scope);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('delegator', function ($delegatorQuery) use ($search) {
                    $delegatorQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('delegate', function ($delegateQuery) use ($search) {
                    $delegateQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $delegations = $query->orderByDesc('id')->paginate(15)->appends($request->query());
        $users = User::orderBy('name')->get();
        $filters = $request->only(['scope', 'search']);

        return view('cuti.master.delegations.index', compact('delegations', 'users', 'filters'));
    }

    public function store(Request $request)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        LeaveDelegation::create($this->validatedData($request));

        return redirect()->route('cuti.master.delegations.index')->with('success', 'Delegasi approval berhasil ditambahkan.');
    }

    public function update(Request $request, LeaveDelegation $leaveDelegation)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $leaveDelegation->update($this->validatedData($request));

        return redirect()->route('cuti.master.delegations.index')->with('success', 'Delegasi approval berhasil diperbarui.');
    }

    public function destroy(LeaveDelegation $leaveDelegation)
    {
        $this->abortIfUnauthorized();
        $this->ensureReady();

        $leaveDelegation->delete();

        return redirect()->route('cuti.master.delegations.index')->with('success', 'Delegasi approval berhasil dihapus.');
    }

    protected function validatedData(Request $request)
    {
        $data = $request->validate([
            'delegator_id' => 'required|exists:users,id|different:delegate_id',
            'delegate_id' => 'required|exists:users,id|different:delegator_id',
            'scope' => 'required|in:leave_approval,document_verification,ppk_approval',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        return [
            'delegator_id' => $data['delegator_id'],
            'delegate_id' => $data['delegate_id'],
            'scope' => $data['scope'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'note' => $data['note'] ?? null,
            'is_active' => $request->has('is_active'),
        ];
    }

    protected function abortIfUnauthorized()
    {
        abort_unless(auth()->check() && auth()->user()->canManageLeaveMasterData(), 403);
    }

    protected function ensureReady()
    {
        abort_unless(Schema::hasTable('leave_delegations'), 404);
    }
}
