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
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

        $user = auth()->user();
        $baseQuery = RapatApproval::with([
            'approver',
            'rapat.kategoriSuratKode.parent.parent.parent',
            'rapat.creator',
            'rapat.pesertas',
            'rapat.approvals.approver',
            'rapat.approvalHistories.approver',
        ])->orderBy('step_order');

        if (!$user->isMeetingAdmin()) {
            $baseQuery->where('approver_id', $user->id);
        }

        $pendingApprovals = (clone $baseQuery)
            ->where('status', 'pending')
            ->get()
            ->sortBy(function ($approval) {
                return optional($approval->rapat->tanggal)->format('Ymd') . sprintf('%02d', $approval->step_order);
            })
            ->values();

        $waitingApprovals = (clone $baseQuery)
            ->where('status', 'waiting')
            ->get()
            ->values();

        $historyQuery = RapatApprovalHistory::with(['rapat', 'approver', 'approval'])
            ->orderByDesc('acted_at')
            ->orderByDesc('id');

        if (!$user->isMeetingAdmin()) {
            $historyQuery->where('approver_id', $user->id);
        }

        $historyEntries = $historyQuery->limit(50)->get();

        return view('rapat.approval.index', compact('pendingApprovals', 'waitingApprovals', 'historyEntries'));
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

        $this->approvalService->approve(
            $rapatApproval->load('rapat'),
            auth()->user(),
            $request->input('catatan')
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
