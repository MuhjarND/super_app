<?php

namespace App\Http\Controllers;

use App\ActivityAudit;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isPimpinan() || $user->isSuperAdmin(), 403);

        $filters = [
            'module' => $request->query('module', 'all'),
            'event' => $request->query('event', 'all'),
            'actor' => $request->query('actor', 'all'),
            'search' => trim((string) $request->query('search', '')),
        ];

        $query = ActivityAudit::with(['actor', 'targetUser'])->orderByDesc('created_at')->orderByDesc('id');

        if ($filters['module'] !== 'all') {
            $query->where('module', $filters['module']);
        }

        if ($filters['event'] !== 'all') {
            $query->where('event', $filters['event']);
        }

        if ($filters['actor'] !== 'all') {
            $query->where('actor_name', $filters['actor']);
        }

        if ($filters['search'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('subject_title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('note', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('actor_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('target_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        $audits = $query->paginate(25)->appends($filters);
        $allAudits = ActivityAudit::query();

        return view('audit-trail.index', [
            'audits' => $audits,
            'filters' => $filters,
            'moduleOptions' => ActivityAudit::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
            'eventOptions' => ActivityAudit::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'actorOptions' => ActivityAudit::query()->whereNotNull('actor_name')->select('actor_name')->distinct()->orderBy('actor_name')->pluck('actor_name'),
            'summary' => [
                'total' => (clone $allAudits)->count(),
                'today' => (clone $allAudits)->whereDate('created_at', today())->count(),
                'persuratan' => (clone $allAudits)->where('module', 'persuratan')->count(),
                'approval' => (clone $allAudits)->whereIn('module', ['rapat', 'cuti', 'progress_zi', 'persuratan'])->count(),
            ],
        ]);
    }
}
