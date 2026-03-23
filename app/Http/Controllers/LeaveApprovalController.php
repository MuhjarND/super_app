<?php

namespace App\Http\Controllers;

use App\Events\LeaveRequestStatusChanged;
use App\Http\Requests\ApprovalActionRequest;
use App\Http\Requests\VerifyLeaveDocumentRequest;
use App\LeaveApproval;
use App\LeaveRequestDocument;
use App\Services\LeaveApprovalService;
use Illuminate\Support\Facades\Schema;

class LeaveApprovalController extends Controller
{
    protected $approvalService;
    public function __construct(LeaveApprovalService $approvalService) { $this->middleware('auth'); $this->approvalService = $approvalService; }

    public function index()
    {
        abort_unless(auth()->user()->canApproveLeave(), 403);
        if (!$this->moduleReady()) { return response()->view('cuti.setup', ['title' => 'Approval Cuti Belum Diaktifkan', 'message' => 'Tabel modul cuti belum dijalankan.']); }
        $approvals = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType'])->where('status', 'pending')->where('approver_id', auth()->id())->orderBy('step_no')->paginate(20);
        return view('cuti.approval.index', compact('approvals'));
    }

    public function show(LeaveApproval $leaveApproval)
    {
        abort_unless(auth()->user()->canApproveLeave(), 403);
        abort_unless((int) $leaveApproval->approver_id === (int) auth()->id() || auth()->user()->isSuperAdmin(), 403);
        $leaveApproval->load(['leaveRequest.user', 'leaveRequest.leaveType', 'leaveRequest.documents.verifier', 'leaveRequest.approvals.approver', 'leaveRequest.audits.actor']);
        return view('cuti.show', ['leaveRequest' => $leaveApproval->leaveRequest, 'leaveApproval' => $leaveApproval]);
    }

    public function approve(ApprovalActionRequest $request, LeaveApproval $leaveApproval)
    {
        abort_unless(auth()->user()->canApproveLeave(), 403);
        abort_unless((int) $leaveApproval->approver_id === (int) auth()->id() || auth()->user()->isSuperAdmin(), 403);
        $oldStatus = $leaveApproval->leaveRequest->status;
        $this->approvalService->approve($leaveApproval, auth()->user(), $request->note);
        event(new LeaveRequestStatusChanged($leaveApproval->leaveRequest->fresh(), auth()->user(), $oldStatus, $leaveApproval->leaveRequest->fresh()->status, 'approved'));
        return back()->with('success', 'Approval cuti berhasil diproses.');
    }

    public function reject(ApprovalActionRequest $request, LeaveApproval $leaveApproval)
    {
        abort_unless(auth()->user()->canApproveLeave(), 403);
        abort_unless((int) $leaveApproval->approver_id === (int) auth()->id() || auth()->user()->isSuperAdmin(), 403);
        $request->validate(['note' => 'required|string|max:2000']);
        $oldStatus = $leaveApproval->leaveRequest->status;
        $this->approvalService->reject($leaveApproval, auth()->user(), $request->note);
        event(new LeaveRequestStatusChanged($leaveApproval->leaveRequest->fresh(), auth()->user(), $oldStatus, $leaveApproval->leaveRequest->fresh()->status, 'rejected'));
        return back()->with('success', 'Pengajuan cuti ditolak.');
    }

    public function verifyDocument(VerifyLeaveDocumentRequest $request, LeaveApproval $leaveApproval)
    {
        abort_unless(auth()->user()->canApproveLeave(), 403);
        abort_unless((int) $leaveApproval->approver_id === (int) auth()->id() || auth()->user()->isSuperAdmin(), 403);
        $document = LeaveRequestDocument::where('leave_request_id', $leaveApproval->leave_request_id)->findOrFail($request->document_id);
        $document->is_verified = (bool) $request->is_verified;
        $document->verification_note = $request->verification_note;
        $document->verified_by = auth()->id();
        $document->verified_at = now();
        $document->save();
        return back()->with('success', 'Verifikasi dokumen cuti diperbarui.');
    }

    protected function moduleReady() { return Schema::hasTable('leave_requests') && Schema::hasTable('leave_approvals'); }
}
