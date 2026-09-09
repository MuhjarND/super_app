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
        $user = auth()->user();

        $suratKeluarApproval->load([
            'approver.jabatan',
            'parafUser.jabatan',
            'requester.jabatan',
            'histories.approver',
            'suratKeluar.creator',
            'suratKeluar.kategoriSurat',
            'suratKeluar.klasifikasiKode',
            'suratKeluar.kodeFungsi',
            'suratKeluar.kodeKegiatan',
            'suratKeluar.penerimaInternal.jabatan',
        ]);

        $isApprover = $user->canActAsAssignedUser($suratKeluarApproval->approver_id);
        $isParafUser = $suratKeluarApproval->paraf_user_id
            && $user->canActAsAssignedUser($suratKeluarApproval->paraf_user_id);
        abort_unless($user->isSuperAdmin() || $isApprover || $isParafUser, 403);

        $canAct = $suratKeluarApproval->status === 'pending'
            && $suratKeluarApproval->isParafReady()
            && $this->canProcessApproval($suratKeluarApproval, $user);
        $canParaf = $suratKeluarApproval->status === 'pending'
            && $suratKeluarApproval->paraf_status === 'pending'
            && ($user->isSuperAdmin() || $isParafUser);

        return view('surat-keluar.approval.show', compact('suratKeluarApproval', 'canAct', 'canParaf'));
    }

    public function preview(SuratKeluarApproval $suratKeluarApproval)
    {
        $user = auth()->user();
        $canView = $user->canActAsAssignedUser($suratKeluarApproval->approver_id)
            || ($suratKeluarApproval->paraf_user_id && $user->canActAsAssignedUser($suratKeluarApproval->paraf_user_id));
        abort_unless($user->isSuperAdmin() || $canView, 403);

        return $this->documentService->streamApprovalPreview($suratKeluarApproval);
    }

    public function approve(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        $user = auth()->user();
        abort_unless($this->canProcessApproval($suratKeluarApproval, $user), 403);
        $this->approvalService->approve($suratKeluarApproval, $user, $request->note);

        return back()->with('success', 'Surat keluar berhasil di-approve.');
    }

    public function reject(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        $user = auth()->user();
        abort_unless($this->canProcessApproval($suratKeluarApproval, $user), 403);

        $request->validate(['note' => 'required|string|max:2000']);
        $this->approvalService->reject($suratKeluarApproval, $user, $request->note);

        return back()->with('success', $suratKeluarApproval->template_slug === \App\Services\SuratKeluarEsignService::TEMPLATE_SLUG
            ? 'Permohonan e-sign ditolak dan dikembalikan untuk revisi.'
            : 'Surat keluar berhasil ditolak.');
    }

    protected function canProcessApproval(SuratKeluarApproval $approval, $user)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->canActAsAssignedUser($approval->approver_id)) {
            return false;
        }

        // Pegawai yang dituju pada pengajuan e-sign boleh memproses tugasnya
        // walaupun akun tersebut tidak memakai role approval umum.
        return $approval->template_slug === \App\Services\SuratKeluarEsignService::TEMPLATE_SLUG
            || $user->canApproveSuratKeluarTemplate();
    }

    public function approveParaf(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->canActAsAssignedUser($suratKeluarApproval->paraf_user_id), 403);
        $request->validate(['note' => 'nullable|string|max:2000']);

        $this->approvalService->approveParaf($suratKeluarApproval, $user, $request->note);

        return back()->with('success', 'Surat Tugas berhasil diparaf dan diteruskan kepada penanda tangan.');
    }

    public function rejectParaf(ApprovalActionRequest $request, SuratKeluarApproval $suratKeluarApproval)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $user->canActAsAssignedUser($suratKeluarApproval->paraf_user_id), 403);
        $request->validate(['note' => 'required|string|max:2000']);

        $this->approvalService->rejectParaf($suratKeluarApproval, $user, $request->note);

        return back()->with('success', 'Paraf ditolak dan Surat Tugas dikembalikan untuk diperbaiki.');
    }
}
