<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovalActionRequest;
use App\SuratKeluarApproval;
use App\Services\SuratKeluarApprovalService;
use App\Services\SuratTemplateDocumentService;

class SuratKeluarApprovalController extends Controller
{
    protected $approvalService;
    protected $documentService;

    public function __construct(SuratKeluarApprovalService $approvalService, SuratTemplateDocumentService $documentService)
    {
        $this->middleware('auth');
        $this->approvalService = $approvalService;
        $this->documentService = $documentService;
    }

    public function show(SuratKeluarApproval $suratKeluarApproval)
    {
        abort_unless(auth()->user()->canApproveSuratKeluarTemplate(), 403);

        $suratKeluarApproval->load([
            'approver.jabatan',
            'requester.jabatan',
            'histories.approver',
            'suratKeluar.creator',
            'suratKeluar.kategoriSurat',
            'suratKeluar.klasifikasiKode',
            'suratKeluar.kodeFungsi',
            'suratKeluar.kodeKegiatan',
            'suratKeluar.penerimaInternal.jabatan',
        ]);

        abort_unless(auth()->user()->isSuperAdmin() || (int) $suratKeluarApproval->approver_id === (int) auth()->id(), 403);

        $canAct = $suratKeluarApproval->status === 'pending'
            && (auth()->user()->isSuperAdmin() || (int) $suratKeluarApproval->approver_id === (int) auth()->id());

        return view('surat-keluar.approval.show', compact('suratKeluarApproval', 'canAct'));
    }

    public function preview(SuratKeluarApproval $suratKeluarApproval)
    {
        abort_unless(auth()->user()->canApproveSuratKeluarTemplate(), 403);
        abort_unless(auth()->user()->isSuperAdmin() || (int) $suratKeluarApproval->approver_id === (int) auth()->id(), 403);

        return $this->documentService->streamApprovalPreview($suratKeluarApproval);
    }

    public function approve(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        abort_unless(auth()->user()->canApproveSuratKeluarTemplate(), 403);
        abort_unless(auth()->user()->isSuperAdmin() || (int) $suratKeluarApproval->approver_id === (int) auth()->id(), 403);

        $this->approvalService->approve($suratKeluarApproval, auth()->user(), $request->note);

        return back()->with('success', 'Surat keluar berhasil di-approve.');
    }

    public function reject(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        abort_unless(auth()->user()->canApproveSuratKeluarTemplate(), 403);
        abort_unless(auth()->user()->isSuperAdmin() || (int) $suratKeluarApproval->approver_id === (int) auth()->id(), 403);

        $request->validate(['note' => 'required|string|max:2000']);
        $this->approvalService->reject($suratKeluarApproval, auth()->user(), $request->note);

        return back()->with('success', 'Surat keluar berhasil ditolak.');
    }
}
