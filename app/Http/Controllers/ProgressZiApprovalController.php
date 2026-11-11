<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiActivityApprovalService;
use App\Services\ProgressZi\ZiEvidenceBundleService;
use App\ZiActivityApproval;
use Illuminate\Http\Request;

class ProgressZiApprovalController extends Controller
{
    use InteractsWithProgressZi;

    protected $approvalService;
    protected $bundleService;

    public function __construct(ZiActivityApprovalService $approvalService, ZiEvidenceBundleService $bundleService)
    {
        $this->middleware('auth');
        $this->approvalService = $approvalService;
        $this->bundleService = $bundleService;
    }

    public function show(ZiActivityApproval $ziActivityApproval)
    {
        $this->authorizeReviewView($ziActivityApproval);

        $ziActivityApproval->load([
            'approver.jabatan',
            'requester',
            'activity.area',
            'activity.guidelineSubPoint.point',
            'activity.realizations.evidences',
        ]);

        $evidences = $ziActivityApproval->activity->realizations
            ->flatMap(function ($realization) {
                return $realization->evidences;
            })
            ->values();

        return view('progress-zi.approvals.show', [
            'approval' => $ziActivityApproval,
            'activity' => $ziActivityApproval->activity,
            'evidences' => $evidences,
            'bundlePreviewUrl' => route('progress-zi.approvals.bundle', $ziActivityApproval),
            'previewableEvidences' => $evidences->filter(function ($evidence) {
                return !empty($evidence->preview_url);
            })->values(),
            'previewUrl' => optional($evidences->first(function ($evidence) {
                return !empty($evidence->preview_url);
            }))->preview_url,
        ]);
    }

    public function bundle(ZiActivityApproval $ziActivityApproval)
    {
        $this->authorizeReviewView($ziActivityApproval);

        $bundle = $this->bundleService->createBundle($ziActivityApproval->activity);
        $response = response()->file($bundle['path'], [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $bundle['download_name'] . '"',
        ]);

        if (method_exists($response, 'deleteFileAfterSend')) {
            $response->deleteFileAfterSend(true);
        }

        return $response;
    }

    public function approve(ZiActivityApproval $ziActivityApproval, Request $request)
    {
        $this->abortUnlessCanVerifyProgressZi();
        $request->validate(['review_notes' => 'nullable|string']);

        $this->approvalService->approve($ziActivityApproval, auth()->user(), $request->review_notes);

        return redirect()->route('progress-zi.approvals.show', $ziActivityApproval)->with('success', 'Review eviden berhasil disetujui.');
    }

    public function reject(ZiActivityApproval $ziActivityApproval, Request $request)
    {
        $this->abortUnlessCanVerifyProgressZi();
        $request->validate(['review_notes' => 'required|string']);

        $this->approvalService->reject($ziActivityApproval, auth()->user(), $request->review_notes);

        return redirect()->route('progress-zi.approvals.show', $ziActivityApproval)->with('success', 'Review eviden dikembalikan untuk perbaikan.');
    }

    protected function authorizeReviewView(ZiActivityApproval $approval)
    {
        $user = auth()->user();
        abort_unless($user->canVerifyProgressZi() || $user->isSuperAdmin()
            || ($approval->status !== 'pending' && $user->canActAsAssignedUser($approval->approver_id)), 403);
    }
}
