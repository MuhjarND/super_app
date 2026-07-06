<?php

namespace App\Http\Controllers;

use App\RapatApproval;
use App\RapatApprovalHistory;
use App\Services\RapatApprovalService;
use Illuminate\Http\Request;

class RapatApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(RapatApprovalService $approvalService)
    {
        $this->middleware('auth');
        $this->approvalService = $approvalService;
    }

    public function index()
    {
        return redirect()->route('approval.index', ['category' => 'undangan']);
    }

    public function show(RapatApproval $rapatApproval)
    {
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

        $user = auth()->user();
        $rapatApproval->load([
            'approver',
            'histories.approver',
            'rapat.kategoriSuratKode.parent.parent.parent',
            'rapat.creator',
            'rapat.pesertas',
            'rapat.approver1.jabatan',
            'rapat.approver2.jabatan',
            'rapat.approvals.approver',
            'rapat.approvalHistories.approver',
        ]);

        abort_unless(
            $user->isMeetingAdmin() || $user->canActAsAssignedUser($rapatApproval->approver_id),
            403
        );

        $canAct = $rapatApproval->status === 'pending'
            && ($user->isMeetingAdmin() || $user->canActAsAssignedUser($rapatApproval->approver_id));

        return view('rapat.approval.show', compact('rapatApproval', 'canAct'));
    }

    public function approve(Request $request, RapatApproval $rapatApproval)
    {
        $user = auth()->user();
        abort_unless($user->canAccessMeetingApproval(), 403);
        abort_unless($user->isMeetingAdmin() || $user->canActAsAssignedUser($rapatApproval->approver_id), 403);
        $request->validate([
            'signature_data' => ['nullable', 'string'],
        ]);

        $this->approvalService->approve(
            $rapatApproval->load('rapat'),
            $user,
            $request->input('catatan'),
            $request->input('signature_data')
        );

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil di-approve.',
        ]);
    }

    public function reject(Request $request, RapatApproval $rapatApproval)
    {
        $user = auth()->user();
        abort_unless($user->canAccessMeetingApproval(), 403);
        abort_unless($user->isMeetingAdmin() || $user->canActAsAssignedUser($rapatApproval->approver_id), 403);

        $request->validate([
            'catatan' => ['required', 'string'],
        ], [
            'catatan.required' => 'Catatan reject wajib diisi.',
        ]);

        $this->approvalService->reject(
            $rapatApproval->load('rapat'),
            $user,
            $request->input('catatan')
        );

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil ditolak.',
        ]);
    }
}
