<?php

namespace App\Http\Controllers;

use App\RapatNotulensiApproval;
use App\RapatNotulensiApprovalHistory;
use App\Services\RapatNotulensiApprovalService;
use Illuminate\Http\Request;

class RapatNotulensiApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(RapatNotulensiApprovalService $approvalService)
    {
        $this->middleware('auth');
        $this->approvalService = $approvalService;
    }

    public function index()
    {
        return redirect()->route('approval.index', ['category' => 'notulensi']);
    }

    public function show(RapatNotulensiApproval $notulensiApproval)
    {
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

        $user = auth()->user();
        $notulensiApproval->load([
            'approver',
            'histories.approver',
            'notulensi.notulis',
            'notulensi.tindakLanjuts.user',
            'notulensi.rapat.creator',
            'notulensi.rapat.kategoriSuratKode',
            'notulensi.rapat.pesertas',
        ]);

        abort_unless($user->isMeetingAdmin() || $user->canActAsAssignedUser($notulensiApproval->approver_id), 403);

        $canAct = $notulensiApproval->status === 'pending'
            && ($user->isMeetingAdmin() || $user->canActAsAssignedUser($notulensiApproval->approver_id));

        return view('rapat.notulensi-approval.show', compact('notulensiApproval', 'canAct'));
    }

    public function approve(Request $request, RapatNotulensiApproval $notulensiApproval)
    {
        $user = auth()->user();
        abort_unless($user->canAccessMeetingApproval(), 403);
        abort_unless($user->isMeetingAdmin() || $user->canActAsAssignedUser($notulensiApproval->approver_id), 403);
        $request->validate([
            'signature_data' => ['nullable', 'string'],
        ]);

        $this->approvalService->approve($notulensiApproval, $user, $request->input('catatan'), $request->input('signature_data'));

        return response()->json([
            'success' => true,
            'message' => 'Notulen berhasil di-approve.',
        ]);
    }

    public function reject(Request $request, RapatNotulensiApproval $notulensiApproval)
    {
        $user = auth()->user();
        abort_unless($user->canAccessMeetingApproval(), 403);
        abort_unless($user->isMeetingAdmin() || $user->canActAsAssignedUser($notulensiApproval->approver_id), 403);

        $request->validate([
            'catatan' => ['required', 'string'],
        ], [
            'catatan.required' => 'Catatan reject wajib diisi.',
        ]);

        $this->approvalService->reject($notulensiApproval, $user, $request->input('catatan'));

        return response()->json([
            'success' => true,
            'message' => 'Notulen berhasil ditolak.',
        ]);
    }
}
