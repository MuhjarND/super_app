<?php

namespace App\Http\Controllers;

use App\Events\LeaveRequestStatusChanged;
use App\Events\LeaveRequestSubmitted;
use App\LeaveRequestDocument;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\LeaveRequest;
use App\LeaveType;
use App\Services\LeaveApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveDocumentService;
use App\Services\LeaveValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class LeaveRequestController extends Controller
{
    protected $validator;
    protected $approvalService;
    protected $balanceService;
    protected $documentService;

    public function __construct(LeaveValidationService $validator, LeaveApprovalService $approvalService, LeaveBalanceService $balanceService, LeaveDocumentService $documentService)
    {
        $this->middleware('auth');
        $this->validator = $validator;
        $this->approvalService = $approvalService;
        $this->balanceService = $balanceService;
        $this->documentService = $documentService;
    }

    public function index()
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $leaveRequests = LeaveRequest::with(['leaveType', 'approvals.approver', 'audits.actor', 'documents'])->where('user_id', auth()->id())->latest('id')->paginate(15);
        $leaveTypes = LeaveType::where('status', 'active')->orderBy('name')->get();
        $balanceYear = (int) request('balance_year', now()->year);
        $leaveBalanceSummaries = $leaveTypes
            ->where('requires_balance', true)
            ->map(function ($leaveType) use ($balanceYear) {
                $balance = $this->balanceService->getBalanceSnapshot(auth()->user(), $leaveType, $balanceYear);
                $carryForwardByYear = (array) ($balance['carry_forward_by_year'] ?? []);
                $previousYear = $balanceYear - 1;
                $twoYearsAgo = $balanceYear - 2;
                $totalBalance = (int) ($balance['opening_balance'] ?? 0)
                    + (int) ($balance['entitlement'] ?? 0)
                    + (int) ($balance['carry_forward'] ?? 0)
                    + (int) ($balance['adjustment_plus'] ?? 0)
                    - (int) ($balance['adjustment_minus'] ?? 0);

                return [
                    'leave_type' => $leaveType,
                    'year' => $balanceYear,
                    'opening_balance' => (int) ($balance['opening_balance'] ?? 0),
                    'entitlement' => (int) ($balance['entitlement'] ?? 0),
                    'carry_forward' => (int) ($balance['carry_forward'] ?? 0),
                    'carry_forward_by_year' => $carryForwardByYear,
                    'carry_forward_previous_year' => (int) ($carryForwardByYear[$previousYear] ?? $carryForwardByYear[(string) $previousYear] ?? 0),
                    'carry_forward_two_years_ago' => (int) ($carryForwardByYear[$twoYearsAgo] ?? $carryForwardByYear[(string) $twoYearsAgo] ?? 0),
                    'adjustment_plus' => (int) ($balance['adjustment_plus'] ?? 0),
                    'adjustment_minus' => (int) ($balance['adjustment_minus'] ?? 0),
                    'used_days' => (int) ($balance['used_days'] ?? 0),
                    'reserved_days' => (int) ($balance['reserved_days'] ?? 0),
                    'remaining_balance' => (int) ($balance['remaining_balance'] ?? 0),
                    'total_balance' => $totalBalance,
                ];
            })
            ->values();

        return view('cuti.index', compact('leaveRequests', 'leaveTypes', 'leaveBalanceSummaries', 'balanceYear'));
    }

    public function create()
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        return redirect()->route('cuti.index', ['open' => 'create']);
    }

    public function store(StoreLeaveRequest $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $leaveRequest = LeaveRequest::create([
            'user_id' => auth()->id(),
            'leave_type_id' => $request->leave_type_id,
            'status' => LeaveRequest::STATUS_DRAFT,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'purpose' => $request->purpose,
            'leave_address' => $request->leave_address,
            'is_abroad' => $request->boolean('is_abroad'),
            'abroad_country' => $request->boolean('is_abroad') ? $request->abroad_country : null,
            'contact_phone' => auth()->user()->no_hp,
            'status_asn_snapshot' => auth()->user()->status_asn ?? 'PNS',
            'unit_snapshot' => optional(auth()->user()->unit)->nama,
            'jabatan_snapshot' => auth()->user()->jabatan_keterangan,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
        $this->documentService->ensureLetterNumber($leaveRequest);
        $this->documentService->syncSuratKeluar($leaveRequest, false);
        $leaveRequest->load('leaveType');
        $this->documentService->storeUploadedDocuments($leaveRequest, $request->file('documents', []));
        $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
        return redirect()->route('cuti.show', $leaveRequest)->with('success', 'Draft cuti berhasil dibuat.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('view', $leaveRequest);
        $leaveRequest->load(['leaveType', 'documents.verifier', 'approvals.approver', 'audits.actor']);
        return view('cuti.show', compact('leaveRequest'));
    }

    public function edit(LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('update', $leaveRequest);
        return redirect()->route('cuti.index', ['edit' => $leaveRequest->id]);
    }

    public function update(UpdateLeaveRequest $request, LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('update', $leaveRequest);
        $leaveRequest->fill($request->only(['leave_type_id', 'start_date', 'end_date', 'purpose', 'leave_address', 'revision_note']));
        $leaveRequest->is_abroad = $request->boolean('is_abroad');
        $leaveRequest->abroad_country = $leaveRequest->is_abroad ? $request->abroad_country : null;
        $leaveRequest->contact_phone = auth()->user()->no_hp;
        $leaveRequest->updated_by = auth()->id();
        $leaveRequest->save();
        $leaveRequest->load('leaveType');
        $this->documentService->storeUploadedDocuments($leaveRequest, $request->file('documents', []));
        $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
        return redirect()->route('cuti.show', $leaveRequest)->with('success', 'Draft cuti diperbarui.');
    }

    public function submit(Request $request, LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('submit', $leaveRequest);
        $request->validate([
            'signature_data' => ['nullable', 'string'],
        ]);
        $leaveRequest->load(['user', 'leaveType', 'documents']);
        $this->validator->validateForSubmit($leaveRequest);
        $leaveRequest->approved_days = $leaveRequest->requested_days;
        $leaveRequest->save();
        $this->approvalService->submit($leaveRequest, $request->input('signature_data'));
        $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
        event(new LeaveRequestSubmitted($leaveRequest, auth()->user()));
        return redirect()->route('cuti.show', $leaveRequest)->with('success', 'Pengajuan cuti berhasil disubmit.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('cancel', $leaveRequest);
        $oldStatus = $leaveRequest->status;
        $leaveRequest->status = LeaveRequest::STATUS_CANCELLED;
        $leaveRequest->cancelled_at = now();
        $leaveRequest->updated_by = auth()->id();
        $leaveRequest->save();
        if (in_array($oldStatus, [LeaveRequest::STATUS_SUBMITTED, LeaveRequest::STATUS_UNDER_REVIEW, LeaveRequest::STATUS_VERIFIED], true)) {
            $this->balanceService->restore($leaveRequest);
        }
        $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
        event(new LeaveRequestStatusChanged($leaveRequest, auth()->user(), $oldStatus, $leaveRequest->status, 'cancelled'));
        return redirect()->route('cuti.show', $leaveRequest)->with('success', 'Pengajuan cuti dibatalkan.');
    }

    public function requestRevision(LeaveRequest $leaveRequest, Request $request)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('update', $leaveRequest);
        $leaveRequest->status = LeaveRequest::STATUS_DRAFT;
        $leaveRequest->revision_number = (int) $leaveRequest->revision_number + 1;
        $leaveRequest->revision_note = $request->input('revision_note');
        $leaveRequest->updated_by = auth()->id();
        $leaveRequest->save();
        $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
        return redirect()->route('cuti.index', ['edit' => $leaveRequest->id])->with('success', 'Pengajuan dibuka untuk revisi.');
    }

    public function surat(LeaveRequest $leaveRequest)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('view', $leaveRequest);

        return $this->documentService->streamDecisionLetter($leaveRequest);
    }

    public function document(LeaveRequest $leaveRequest, LeaveRequestDocument $document)
    {
        $this->abortIfUnauthorized();
        if (!$this->moduleReady()) { return $this->setupResponse('Modul Cuti Belum Diaktifkan'); }
        $this->authorize('view', $leaveRequest);
        abort_unless((int) $document->leave_request_id === (int) $leaveRequest->id, 404);
        abort_unless(Storage::disk('public')->exists($document->file_path), 404);

        return response()->file(Storage::disk('public')->path($document->file_path));
    }

    protected function abortIfUnauthorized() { abort_unless(auth()->check() && auth()->user()->canAccessLeaveModule(), 403); }
    protected function moduleReady() { return Schema::hasTable('leave_requests') && Schema::hasTable('leave_types'); }
    protected function setupResponse($title) { return response()->view('cuti.setup', ['title' => $title, 'message' => 'Schema dan data referensi cuti sudah disiapkan di kode, tetapi belum dijalankan pada database.']); }
}
