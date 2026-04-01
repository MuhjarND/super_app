<?php

namespace App\Http\Controllers;

use App\LeaveApproval;
use App\LeaveRequest;
use App\RapatApproval;
use App\RapatApprovalHistory;
use App\RapatNotulensiApproval;
use App\RapatNotulensiApprovalHistory;
use App\SuratKeluarApproval;
use App\SuratKeluarApprovalHistory;
use App\ZiActivityApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApprovalCenterController extends Controller
{
    protected $approvalCards = [
        'dokumen_rapat' => [
            'label' => 'Dokumen Rapat',
            'description' => 'Approval undangan, absensi, dan notulensi rapat.',
            'icon' => 'fas fa-file-alt',
        ],
        'surat_cuti' => [
            'label' => 'Surat Cuti',
            'description' => 'Approval dokumen cuti.',
            'icon' => 'fas fa-calendar-check',
        ],
        'surat_keluar' => [
            'label' => 'Surat Keluar',
            'description' => 'Approval dokumen surat keluar dari template surat.',
            'icon' => 'fas fa-paper-plane',
        ],
        'progress_zi' => [
            'label' => 'Progress ZI',
            'description' => 'Review pimpinan atas tindak lanjut sub poin Progress ZI.',
            'icon' => 'fas fa-chart-line',
        ],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessApprovalCenter(), 403);

        $category = $request->query('category');
        $cards = $this->buildCards();
        $documents = collect();
        $selectedCard = $category && isset($cards[$category]) ? $cards[$category] : null;

        if ($selectedCard) {
            $documents = $this->resolvePendingDocuments($category);
        }

        return view('approval.index', compact('cards', 'documents', 'selectedCard', 'category'));
    }

    public function history(Request $request)
    {
        abort_unless(auth()->user()->canAccessApprovalCenter(), 403);

        $category = $request->query('category');
        $cards = $this->buildCards();
        $historyItems = collect();
        $selectedCard = $category && isset($cards[$category]) ? $cards[$category] : null;

        if ($selectedCard) {
            $historyItems = $this->resolveHistoryItems($category);
        }

        return view('approval.history', compact('cards', 'historyItems', 'selectedCard', 'category'));
    }

    protected function buildCards()
    {
        $cards = [];

        foreach ($this->approvalCards as $key => $meta) {
            $pendingCount = $this->resolvePendingCount($key);
            $historyCount = $this->resolveHistoryCount($key);

            $cards[$key] = array_merge($meta, [
                'key' => $key,
                'pending_count' => $pendingCount,
                'history_count' => $historyCount,
                'is_active' => $pendingCount > 0 || $historyCount > 0 || in_array($key, ['dokumen_rapat', 'surat_cuti', 'surat_keluar', 'progress_zi'], true),
            ]);
        }

        return $cards;
    }

    protected function resolvePendingCount($category)
    {
        $user = auth()->user();

        if ($category === 'dokumen_rapat') {
            $query = RapatApproval::where('status', 'pending');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            $notulensiQuery = RapatNotulensiApproval::where('status', 'pending');
            if (!$user->isMeetingAdmin()) {
                $notulensiQuery->where('approver_id', $user->id);
            }
            return $query->count() + $notulensiQuery->count();
        }

        if ($category === 'surat_cuti' && Schema::hasTable('leave_approvals')) {
            $query = LeaveApproval::where('status', 'pending');
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        if ($category === 'surat_keluar' && Schema::hasTable('surat_keluar_approvals')) {
            $query = SuratKeluarApproval::where('status', 'pending');
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        if ($category === 'progress_zi' && Schema::hasTable('zi_activity_approvals')) {
            $query = ZiActivityApproval::where('status', 'pending');
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        return 0;
    }

    protected function resolveHistoryCount($category)
    {
        $user = auth()->user();

        if ($category === 'dokumen_rapat') {
            $query = RapatApprovalHistory::query();
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            $notulensiQuery = RapatNotulensiApprovalHistory::query();
            if (!$user->isMeetingAdmin()) {
                $notulensiQuery->where('approver_id', $user->id);
            }
            return $query->count() + $notulensiQuery->count();
        }

        if ($category === 'surat_cuti' && Schema::hasTable('leave_requests')) {
            $query = LeaveRequest::whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CANCELLED, LeaveRequest::STATUS_COMPLETED]);
            if (!$user->isSuperAdmin()) {
                $query->whereHas('approvals', function ($approvalQuery) use ($user) {
                    $approvalQuery->where('approver_id', $user->id)->whereIn('status', ['approved', 'rejected']);
                });
            }
            return $query->count();
        }

        if ($category === 'surat_keluar' && Schema::hasTable('surat_keluar_approvals')) {
            $query = SuratKeluarApprovalHistory::query();
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        if ($category === 'progress_zi' && Schema::hasTable('zi_activity_approvals')) {
            $query = ZiActivityApproval::whereIn('status', ['approved', 'rejected']);
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        return 0;
    }

    protected function resolvePendingDocuments($category)
    {
        $user = auth()->user();

        if ($category === 'dokumen_rapat') {
            $query = RapatApproval::with([
                'rapat.kategoriSuratKode',
                'rapat.creator',
                'rapat.pesertas',
                'approver',
            ])->where('status', 'pending')->orderByDesc('updated_at');

            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            $undanganItems = $query->get()->map(function ($approval) {
                return [
                    'title' => optional($approval->rapat)->judul ?: '-',
                    'number' => optional($approval->rapat)->nomor_undangan ?: '-',
                    'date' => optional(optional($approval->rapat)->tanggal)->translatedFormat('d F Y'),
                    'subtitle' => 'Undangan rapat',
                    'meta' => 'Approver: ' . ($approval->approver_name_snapshot ?: '-'),
                    'count_label' => (optional($approval->rapat)->pesertas ? $approval->rapat->pesertas->count() : 0) . ' peserta',
                    'detail_url' => route('rapat.approval.show', $approval),
                    'status_label' => optional($approval->rapat)->status_label ?: 'Pending',
                    'sort_at' => optional($approval->updated_at)->timestamp ?: optional($approval->created_at)->timestamp ?: 0,
                ];
            });

            $query = RapatNotulensiApproval::with([
                'notulensi.rapat',
                'notulensi.notulis',
                'approver',
            ])->where('status', 'pending')->orderByDesc('updated_at');

            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            $notulensiItems = $query->get()->map(function ($approval) {
                return [
                    'title' => optional($approval->notulensi)->judul ?: optional(optional($approval->notulensi)->rapat)->judul ?: '-',
                    'number' => optional(optional($approval->notulensi)->rapat)->nomor_undangan ?: '-',
                    'date' => optional(optional(optional($approval->notulensi)->rapat)->tanggal)->translatedFormat('d F Y'),
                    'subtitle' => 'Notulen agenda',
                    'meta' => 'Notulis: ' . (optional(optional($approval->notulensi)->notulis)->name ?: '-'),
                    'count_label' => 'Approver: ' . ($approval->approver_name_snapshot ?: '-'),
                    'detail_url' => route('rapat.notulensi-approval.show', $approval),
                    'status_label' => optional($approval->notulensi)->status ?: 'pending_approval',
                    'sort_at' => optional($approval->updated_at)->timestamp ?: optional($approval->created_at)->timestamp ?: 0,
                ];
            });

            return $undanganItems->merge($notulensiItems)->sortByDesc('sort_at')->values();
        }

        if ($category === 'surat_cuti' && Schema::hasTable('leave_approvals')) {
            $query = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType'])
                ->where('status', 'pending')
                ->orderByDesc('updated_at');

            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->get()->map(function ($approval) {
                $leaveRequest = $approval->leaveRequest;
                return [
                    'title' => optional($leaveRequest->user)->name ?: '-',
                    'number' => $leaveRequest ? ($leaveRequest->request_number ?: '-') : '-',
                    'date' => optional(optional($leaveRequest)->start_date)->translatedFormat('d F Y'),
                    'subtitle' => optional(optional($approval->leaveRequest)->leaveType)->name ?: 'Pengajuan cuti',
                    'meta' => 'Approver: ' . (optional($approval->approver)->name ?: '-'),
                    'count_label' => 'Status: ' . ($leaveRequest ? $leaveRequest->status_label : '-'),
                    'detail_url' => route('cuti.approval.show', $approval),
                    'status_label' => $leaveRequest ? $leaveRequest->status_label : 'Pending',
                ];
            });
        }

        if ($category === 'surat_keluar' && Schema::hasTable('surat_keluar_approvals')) {
            $query = SuratKeluarApproval::with(['suratKeluar.creator', 'suratKeluar.kategoriSurat', 'approver'])
                ->where('status', 'pending')
                ->orderByDesc('updated_at');

            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->get()->map(function ($approval) {
                $suratKeluar = $approval->suratKeluar;
                return [
                    'title' => $approval->template_name ?: 'Surat Keluar',
                    'number' => optional($suratKeluar)->nomor_surat_formatted ?: '-',
                    'date' => optional(optional($suratKeluar)->tanggal_surat)->translatedFormat('d F Y'),
                    'subtitle' => optional(optional($suratKeluar)->kategoriSurat)->nama ?: 'Surat keluar template',
                    'meta' => 'Penanda tangan: ' . ($approval->signer_name_snapshot ?: '-'),
                    'count_label' => 'Status: ' . ($approval->status_label ?: 'Pending'),
                    'detail_url' => route('surat-keluar.approval.show', $approval),
                    'status_label' => $approval->status_label,
                ];
            });
        }

        if ($category === 'progress_zi' && Schema::hasTable('zi_activity_approvals')) {
            $query = ZiActivityApproval::with(['activity.area', 'activity.guidelineSubPoint.point', 'approver'])
                ->where('status', 'pending')
                ->orderByDesc('requested_at')
                ->orderByDesc('id');

            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->get()->map(function ($approval) {
                $activity = $approval->activity;
                return [
                    'title' => optional($activity)->name ?: 'Review Progress ZI',
                    'number' => optional(optional($activity)->area)->code ?: '-',
                    'date' => optional($approval->requested_at)->translatedFormat('d F Y'),
                    'subtitle' => optional(optional($activity->guidelineSubPoint)->point)->code . '.' . optional($activity->guidelineSubPoint)->code,
                    'meta' => 'Approver: ' . (optional($approval->approver)->name ?: '-'),
                    'count_label' => 'Status: ' . ($approval->status_label ?: 'Pending'),
                    'detail_url' => route('progress-zi.approvals.show', $approval),
                    'status_label' => $approval->status_label,
                ];
            });
        }

        return collect();
    }

    protected function resolveHistoryItems($category)
    {
        $user = auth()->user();

        if ($category === 'dokumen_rapat') {
            $query = RapatApprovalHistory::with(['rapat', 'approver', 'approval'])->orderByDesc('acted_at')->orderByDesc('id');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            $undanganItems = $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional($entry->rapat)->judul ?: '-',
                    'number' => optional($entry->rapat)->nomor_undangan ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => $entry->approver_name_snapshot ?: '-',
                    'action' => ucfirst($entry->action),
                    'note' => $entry->catatan,
                    'detail_url' => $entry->approval ? route('rapat.approval.show', $entry->approval) : null,
                    'sort_at' => optional($entry->acted_at)->timestamp ?: 0,
                ];
            });

            $query = RapatNotulensiApprovalHistory::with(['notulensi.rapat', 'approver', 'approval'])->orderByDesc('acted_at')->orderByDesc('id');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            $notulensiItems = $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional(optional($entry->notulensi)->rapat)->judul ?: optional($entry->notulensi)->judul ?: '-',
                    'number' => optional(optional($entry->notulensi)->rapat)->nomor_undangan ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => $entry->approver_name_snapshot ?: '-',
                    'action' => ucfirst($entry->action),
                    'note' => $entry->catatan,
                    'detail_url' => $entry->approval ? route('rapat.notulensi-approval.show', $entry->approval) : null,
                    'sort_at' => optional($entry->acted_at)->timestamp ?: 0,
                ];
            });

            return $undanganItems->merge($notulensiItems)->sortByDesc('sort_at')->take(100)->values();
        }

        if ($category === 'progress_zi' && Schema::hasTable('zi_activity_approvals')) {
            $query = ZiActivityApproval::with(['activity.area', 'approver'])->whereIn('status', ['approved', 'rejected'])->orderByDesc('acted_at')->orderByDesc('id');
            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional($entry->activity)->name ?: 'Progress ZI',
                    'number' => optional(optional($entry->activity)->area)->code ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => optional($entry->approver)->name ?: '-',
                    'action' => $entry->status_label,
                    'note' => $entry->review_notes,
                    'detail_url' => route('progress-zi.approvals.show', $entry),
                ];
            });
        }

        if ($category === 'surat_cuti' && Schema::hasTable('leave_approvals')) {
            $query = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType', 'approver'])
                ->whereIn('status', ['approved', 'rejected'])
                ->orderByDesc('acted_at')
                ->orderByDesc('id');

            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional(optional($entry->leaveRequest)->user)->name ?: '-',
                    'number' => optional($entry->leaveRequest)->request_number ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => optional($entry->approver)->name ?: '-',
                    'action' => ucfirst((string) $entry->action),
                    'note' => $entry->note,
                    'detail_url' => route('cuti.approval.show', $entry),
                ];
            });
        }

        if ($category === 'surat_keluar' && Schema::hasTable('surat_keluar_approval_histories')) {
            $query = SuratKeluarApprovalHistory::with(['suratKeluar', 'approver', 'approval'])
                ->orderByDesc('acted_at')
                ->orderByDesc('id');

            if (!$user->isSuperAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional($entry->approval)->template_name ?: 'Surat Keluar',
                    'number' => optional(optional($entry->suratKeluar)->nomor_surat_formatted) ?: optional($entry->suratKeluar)->nomor_surat ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => optional($entry->approver)->name ?: $entry->signer_name_snapshot ?: '-',
                    'action' => ucfirst((string) $entry->action),
                    'note' => $entry->note,
                    'detail_url' => $entry->approval ? route('surat-keluar.approval.show', $entry->approval) : null,
                ];
            });
        }

        return collect();
    }
}
