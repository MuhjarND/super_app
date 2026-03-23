<?php

namespace App\Http\Controllers;

use App\LeaveApproval;
use App\LeaveRequest;
use App\Services\LeaveDocumentService;
use Illuminate\Http\Request;

class LeaveSignatureVerificationController extends Controller
{
    protected $documentService;

    public function __construct(LeaveDocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function show(Request $request, LeaveRequest $leaveRequest, LeaveApproval $approval)
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless((int) $approval->leave_request_id === (int) $leaveRequest->id, 404);

        $data = $this->documentService->buildSignatureVerificationData($leaveRequest, $approval);

        return view('cuti.signature.verify', compact('data'));
    }

    public function showApplicant(Request $request, LeaveRequest $leaveRequest)
    {
        abort_unless($request->hasValidSignature(), 403);

        $data = $this->documentService->buildApplicantSignatureVerificationData($leaveRequest);

        return view('cuti.signature.verify', compact('data'));
    }
}
