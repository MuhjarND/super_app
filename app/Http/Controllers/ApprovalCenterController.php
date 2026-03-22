<?php

namespace App\Http\Controllers;

use App\RapatApproval;
use App\RapatApprovalHistory;
use App\RapatNotulensiApproval;
use App\RapatNotulensiApprovalHistory;
use Illuminate\Http\Request;

class ApprovalCenterController extends Controller
{
    protected $approvalCards = [
        'undangan' => [
            'label' => 'Undangan',
            'description' => 'Approval dokumen undangan rapat.',
            'icon' => 'fas fa-envelope-open-text',
        ],
        'notulensi' => [
            'label' => 'Notulensi',
            'description' => 'Approval dokumen notulen agenda.',
            'icon' => 'fas fa-file-signature',
        ],
        'absensi' => [
            'label' => 'Absensi',
            'description' => 'Approval dokumen absensi.',
            'icon' => 'fas fa-clipboard-check',
        ],
        'surat_cuti' => [
            'label' => 'Surat Cuti',
            'description' => 'Approval dokumen cuti.',
            'icon' => 'fas fa-calendar-check',
        ],
        'dokumen_lainnya' => [
            'label' => 'Dokumen Lainnya',
            'description' => 'Approval dokumen modul lain ke depannya.',
            'icon' => 'fas fa-folder-open',
        ],
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

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
        abort_unless(auth()->user()->canAccessMeetingApproval(), 403);

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
                'is_active' => $pendingCount > 0 || $historyCount > 0 || in_array($key, ['undangan', 'notulensi'], true),
            ]);
        }

        return $cards;
    }

    protected function resolvePendingCount($category)
    {
        $user = auth()->user();

        if ($category === 'undangan') {
            $query = RapatApproval::where('status', 'pending');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        if ($category === 'notulensi') {
            $query = RapatNotulensiApproval::where('status', 'pending');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        return 0;
    }

    protected function resolveHistoryCount($category)
    {
        $user = auth()->user();

        if ($category === 'undangan') {
            $query = RapatApprovalHistory::query();
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        if ($category === 'notulensi') {
            $query = RapatNotulensiApprovalHistory::query();
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }
            return $query->count();
        }

        return 0;
    }

    protected function resolvePendingDocuments($category)
    {
        $user = auth()->user();

        if ($category === 'undangan') {
            $query = RapatApproval::with([
                'rapat.kategoriSuratKode',
                'rapat.creator',
                'rapat.pesertas',
                'approver',
            ])->where('status', 'pending')->orderByDesc('updated_at');

            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->get()->map(function ($approval) {
                return [
                    'title' => optional($approval->rapat)->judul ?: '-',
                    'number' => optional($approval->rapat)->nomor_undangan ?: '-',
                    'date' => optional(optional($approval->rapat)->tanggal)->translatedFormat('d F Y'),
                    'subtitle' => 'Undangan rapat',
                    'meta' => 'Approver: ' . ($approval->approver_name_snapshot ?: '-'),
                    'count_label' => (optional($approval->rapat)->pesertas ? $approval->rapat->pesertas->count() : 0) . ' peserta',
                    'detail_url' => route('rapat.approval.show', $approval),
                    'status_label' => optional($approval->rapat)->status_label ?: 'Pending',
                ];
            });
        }

        if ($category === 'notulensi') {
            $query = RapatNotulensiApproval::with([
                'notulensi.rapat',
                'notulensi.notulis',
                'approver',
            ])->where('status', 'pending')->orderByDesc('updated_at');

            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->get()->map(function ($approval) {
                return [
                    'title' => optional($approval->notulensi)->judul ?: optional(optional($approval->notulensi)->rapat)->judul ?: '-',
                    'number' => optional(optional($approval->notulensi)->rapat)->nomor_undangan ?: '-',
                    'date' => optional(optional(optional($approval->notulensi)->rapat)->tanggal)->translatedFormat('d F Y'),
                    'subtitle' => 'Notulen agenda',
                    'meta' => 'Notulis: ' . (optional(optional($approval->notulensi)->notulis)->name ?: '-'),
                    'count_label' => 'Approver: ' . ($approval->approver_name_snapshot ?: '-'),
                    'detail_url' => route('rapat.notulensi-approval.show', $approval),
                    'status_label' => optional($approval->notulensi)->status ?: 'pending_approval',
                ];
            });
        }

        return collect();
    }

    protected function resolveHistoryItems($category)
    {
        $user = auth()->user();

        if ($category === 'undangan') {
            $query = RapatApprovalHistory::with(['rapat', 'approver', 'approval'])->orderByDesc('acted_at')->orderByDesc('id');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional($entry->rapat)->judul ?: '-',
                    'number' => optional($entry->rapat)->nomor_undangan ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => $entry->approver_name_snapshot ?: '-',
                    'action' => ucfirst($entry->action),
                    'note' => $entry->catatan,
                    'detail_url' => $entry->approval ? route('rapat.approval.show', $entry->approval) : null,
                ];
            });
        }

        if ($category === 'notulensi') {
            $query = RapatNotulensiApprovalHistory::with(['notulensi.rapat', 'approver', 'approval'])->orderByDesc('acted_at')->orderByDesc('id');
            if (!$user->isMeetingAdmin()) {
                $query->where('approver_id', $user->id);
            }

            return $query->limit(100)->get()->map(function ($entry) {
                return [
                    'title' => optional(optional($entry->notulensi)->rapat)->judul ?: optional($entry->notulensi)->judul ?: '-',
                    'number' => optional(optional($entry->notulensi)->rapat)->nomor_undangan ?: '-',
                    'acted_at' => $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-',
                    'actor' => $entry->approver_name_snapshot ?: '-',
                    'action' => ucfirst($entry->action),
                    'note' => $entry->catatan,
                    'detail_url' => $entry->approval ? route('rapat.notulensi-approval.show', $entry->approval) : null,
                ];
            });
        }

        return collect();
    }
}
