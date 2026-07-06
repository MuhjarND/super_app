<?php

namespace App\Services\ProgressZi;

use App\Disposisi;
use App\LeaveRequest;
use App\Rapat;
use App\RapatLaporan;
use App\RapatNotulensi;
use App\SuratKeluar;
use App\SuratMasuk;
use App\User;
use Illuminate\Support\Str;

class ZiEvidenceSourceService
{
    public function buildOptionsForUser(User $user)
    {
        return [
            'surat_keluar' => SuratKeluar::visibleTo($user)->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'surat_keluar:' . $item->id, 'label' => ($item->nomor_surat_formatted ?: $item->nomor_surat) . ' - ' . Str::limit($item->perihal, 80)];
            }),
            'surat_masuk' => SuratMasuk::visibleTo($user)->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'surat_masuk:' . $item->id, 'label' => ($item->nomor_surat ?: '-') . ' - ' . Str::limit($item->perihal, 80)];
            }),
            'disposisi' => Disposisi::with('suratMasuk')->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'disposisi:' . $item->id, 'label' => (optional($item->suratMasuk)->nomor_surat ?: 'Disposisi') . ' - ' . Str::limit((string) $item->petunjuk, 70)];
            }),
            'rapat' => Rapat::visibleTo($user)->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'rapat:' . $item->id, 'label' => ($item->nomor_undangan ?: 'Undangan') . ' - ' . Str::limit($item->judul, 80)];
            }),
            'rapat_notulensi' => RapatNotulensi::with('rapat')->whereHas('rapat', function ($query) use ($user) { $query->visibleTo($user); })->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'rapat_notulensi:' . $item->id, 'label' => (optional($item->rapat)->nomor_undangan ?: 'Notulensi') . ' - ' . Str::limit($item->judul ?: optional($item->rapat)->judul, 80)];
            }),
            'rapat_laporan' => RapatLaporan::with('rapat')->where('jenis', 'tindak_lanjut')->whereHas('rapat', function ($query) use ($user) { $query->visibleTo($user); })->latest()->take(25)->get()->map(function ($item) {
                return ['value' => 'rapat_laporan:' . $item->id, 'label' => (optional($item->rapat)->nomor_undangan ?: 'Laporan') . ' - ' . Str::limit($item->judul, 80)];
            }),
            'leave_request' => $this->leaveRequestOptions($user),
        ];
    }

    public function resolveLinkedSource($sourceKey)
    {
        [$type, $id] = array_pad(explode(':', (string) $sourceKey, 2), 2, null);
        $id = (int) $id;

        switch ($type) {
            case 'surat_keluar':
                $item = SuratKeluar::findOrFail($id);
                return ['source_type' => 'persuratan', 'source_reference_type' => 'surat_keluar', 'source_reference_id' => $item->id, 'title' => ($item->nomor_surat_formatted ?: $item->nomor_surat) . ' - ' . $item->perihal, 'description' => $item->perihal, 'evidence_type' => 'surat_keluar'];
            case 'surat_masuk':
                $item = SuratMasuk::findOrFail($id);
                return ['source_type' => 'persuratan', 'source_reference_type' => 'surat_masuk', 'source_reference_id' => $item->id, 'title' => ($item->nomor_surat ?: '-') . ' - ' . $item->perihal, 'description' => $item->pengirim, 'evidence_type' => 'surat_masuk'];
            case 'disposisi':
                $item = Disposisi::with('suratMasuk')->findOrFail($id);
                return ['source_type' => 'persuratan', 'source_reference_type' => 'disposisi', 'source_reference_id' => $item->id, 'title' => 'Disposisi - ' . (optional($item->suratMasuk)->nomor_surat ?: '-'), 'description' => trim(($item->petunjuk ?: '') . ' ' . ($item->catatan ?: '')), 'evidence_type' => 'disposisi'];
            case 'rapat':
                $item = Rapat::findOrFail($id);
                return ['source_type' => 'rapat', 'source_reference_type' => 'rapat', 'source_reference_id' => $item->id, 'title' => ($item->nomor_undangan ?: 'Undangan') . ' - ' . $item->judul, 'description' => $item->deskripsi, 'evidence_type' => 'rapat_undangan'];
            case 'rapat_notulensi':
                $item = RapatNotulensi::with('rapat')->findOrFail($id);
                return ['source_type' => 'rapat', 'source_reference_type' => 'rapat_notulensi', 'source_reference_id' => $item->id, 'title' => 'Notulensi - ' . ($item->judul ?: optional($item->rapat)->judul), 'description' => $item->hasil_rapat, 'evidence_type' => 'rapat_notulensi'];
            case 'rapat_laporan':
                $item = RapatLaporan::with('rapat')->findOrFail($id);
                return ['source_type' => 'rapat', 'source_reference_type' => 'rapat_laporan', 'source_reference_id' => $item->id, 'title' => 'Laporan Tindak Lanjut - ' . $item->judul, 'description' => $item->deskripsi, 'evidence_type' => 'rapat_laporan'];
            case 'leave_request':
                $item = LeaveRequest::with('user', 'leaveType')->findOrFail($id);
                return ['source_type' => 'cuti', 'source_reference_type' => 'leave_request', 'source_reference_id' => $item->id, 'title' => ($item->display_number ?: 'Cuti') . ' - ' . optional($item->leaveType)->name, 'description' => optional($item->user)->name, 'evidence_type' => 'cuti'];
        }

        abort(404);
    }

    protected function leaveRequestOptions(User $user)
    {
        $query = LeaveRequest::with(['user', 'leaveType'])->latest()->take(25);
        if (!$user->isSuperAdmin()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('user_id', $user->id)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                        $approvalQuery->whereIn('approver_id', $user->effectiveAssignmentUserIds());
                    });
            });
        }

        return $query->get()->map(function ($item) {
            return ['value' => 'leave_request:' . $item->id, 'label' => ($item->display_number ?: 'Cuti') . ' - ' . optional($item->leaveType)->name . ' - ' . optional($item->user)->name];
        });
    }
}
