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
            $user->isMeetingAdmin() || (int) $rapatApproval->approver_id === (int) $user->id,
            403
        );

        $canAct = $rapatApproval->status === 'pending'
            && ($user->isMeetingAdmin() || (int) $rapatApproval->approver_id === (int) $user->id);

        return view('rapat.approval.show', compact('rapatApproval', 'canAct'));
    }

    public function approve(Request $request, RapatApproval $rapatApproval)
    {
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);
        $request->validate([
            'signature_data' => ['required', 'string'],
        ], [
            'signature_data.required' => 'Tanda tangan wajib diisi.',
        ]);

        $this->approvalService->approve(
            $rapatApproval->load('rapat'),
            auth()->user(),
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
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

        $request->validate([
            'catatan' => ['required', 'string'],
        ], [
            'catatan.required' => 'Catatan reject wajib diisi.',
        ]);

        $this->approvalService->reject(
            $rapatApproval->load('rapat'),
            auth()->user(),
            $request->input('catatan')
        );

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil ditolak.',
        ]);
    }
}
